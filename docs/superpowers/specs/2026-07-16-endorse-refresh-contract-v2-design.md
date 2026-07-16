# Endorse Refresh Contract v2 — Safe Fallback and Result Finalization

**Date:** 2026-07-16  
**Status:** Approved design

## Problem

The first Rust endorse-refresh worker claimed queue rows and scraped TikTok pages directly. The
container received the application `.env`, but the worker did not read the RapidAPI configuration
and had no RapidAPI fallback. TikTok frequently returned HTTP 200 application shells without
`webapp.video-detail/itemStruct`, so the worker returned `Stats belum tersedia dari scrape` and
could exhaust three attempts in seconds.

The integration also lacked symmetric ownership and claim-identity enforcement:

- a Rust worker could claim while `ENDORSE_REFRESH_DRIVER=cron`;
- the fallback endpoint collapsed missing, completed, and non-processing queues into one error;
- `/result` accepted only `queue_id`, allowing an old result to race a reset or newer claim;
- parsers treated all-zero statistics as missing even though zero is valid data;
- worker logs did not explain whether scrape or fallback transport, HTTP, parsing, authentication,
  or response validation failed.

Production is stabilized on the PHP cron driver with the Rust service at zero replicas. Repository
search confirms the Rust worker is the only caller of `/api/endorse-refresh/result` and
`/api/endorse-refresh/fetch-fallback`, so the integration can move directly to a strict v2 contract
without a legacy compatibility window.

## Goals

1. Prevent a pre-v2 worker from claiming any row against a v2 application.
2. Keep fallback fetches idempotent with respect to application state and strictly fetch-only.
3. Reject stale or mismatched results before they can overwrite a newer claim or completed row.
4. Preserve valid zero metrics while representing absent or unparseable metrics as `null`.
5. Apply partial daily statistics without clearing previously stored values.
6. Produce structured, actionable scrape, fallback, and conflict diagnostics.
7. Make deploy and rollback ordering safe and explicit.

## Non-goals

- Adding a new database claim-token column. The existing `(queue_id, attempt_no, worker_id)` tuple
  is the v2 claim identity; a random claim token can be added later if needed.
- Giving RapidAPI credentials to the Rust process. PHP remains the credential and provider owner.
- Making direct TikTok scraping authoritative. It remains an opportunistic fast path.
- Supporting pre-v2 result or fallback payloads.

## Contract version negotiation

The constant contract version is `2`.

Every Rust-to-PHP request includes top-level `contract_version: 2`. `/claim` validates the version
before checking the driver and before invoking `claimBatch()`. This ordering is mandatory because
`claimBatch()` performs stale recovery and creates claims/attempt rows. A missing or unsupported
version returns HTTP 426 with:

```json
{
  "status": false,
  "reason": "contract_version_unsupported",
  "required_contract_version": 2
}
```

No queue or attempt mutation may occur on this path. This is the technical barrier that prevents a
pre-v2 worker from claiming rows after the application is upgraded.

A successful claim returns `contract_version: 2`; every claimed item includes `queue_id`,
`attempt_no`, and `worker_id`. The v2 worker verifies the response version before fetching.

The result batch maximum is configured by `ENDORSE_REFRESH_RESULT_BATCH_MAX`, defaults to 100, and
is clamped to `1..500`, where 500 is the existing claim maximum. The claim endpoint limits the Rust
claim size to the smaller of its requested limit and the configured result batch maximum, ensuring
one claim can always be returned in one valid result request.

## Fetch fallback endpoint

`POST /api/endorse-refresh/fetch-fallback` accepts only:

```json
{
  "contract_version": 2,
  "queue_id": 719765,
  "attempt_no": 1,
  "worker_id": "w_xxx"
}
```

PHP performs read-only validation in this order:

1. Contract version and payload shape.
2. Queue exists.
3. Queue status is exactly `processing` (with a distinct completed conflict).
4. Queue `worker_id` equals the submitted worker ID.
5. Submitted `attempt_no` equals `queue.attempts + 1`.
6. A matching attempt audit row exists with status `processing`.
7. Platform is TikTok and the normalized URL is an absolute TikTok video/photo URL with a content
   ID that the existing extractor accepts.

The endpoint does not lock rows and does not open a transaction around the external request. After
validation it calls PHP's existing isolated HTTP/1.1 RapidAPI path and returns the fetch response.
It never increments attempts, changes queue status, finalizes audit rows, enqueues jobs, writes
social statistics, or triggers rollups. Calling it repeatedly while the same claim remains active
may repeat the external GET but has no application-state side effect. If claim state changes during
the external request, `/result` is responsible for rejecting the now-stale response.

Fallback HTTP outcomes are:

| HTTP | Stable reason | Meaning |
|---:|---|---|
| 426 | `contract_version_unsupported` | Missing or unsupported contract version |
| 422 | `invalid_request` | Missing/invalid identity field |
| 404 | `queue_not_found` | Queue ID does not exist |
| 409 | `queue_completed` | Queue is already completed |
| 409 | `queue_not_processing` | Queue is pending or failed |
| 409 | `worker_mismatch` | Queue belongs to a different worker claim |
| 409 | `attempt_mismatch` | Attempt number is not the active queue attempt |
| 409 | `attempt_not_active` | Matching processing audit row does not exist |
| 422 | `invalid_tiktok_url` | Platform or normalized TikTok URL is invalid |

Responses include `queue_id`, `attempt_no`, and the stable reason where applicable. They do not
return the expected worker ID.

## Nullable statistics and business updates

The five source metrics are nullable:

```json
{
  "stats_found": true,
  "like": 0,
  "share": null,
  "comment": 2,
  "collect": null,
  "view": null
}
```

`null` means the source field was absent or could not be parsed. A numeric zero means the source
field was present and its value was zero. `stats_found=true` means at least one of the five known
metric fields was present and parseable; the value does not need to be positive. A response that
contains only content metadata is not a successful statistics response.

The Rust TikTok parser and PHP direct/RapidAPI mappers use field presence plus numeric parsing,
never `value > 0`, to set these values. Missing fields remain null through fallback and `/result`.

### Daily refresh

For purpose `daily`, one present metric is sufficient for a successful partial business update.
The writer merges each present metric into the authoritative current values and preserves stored
values for null metrics. Null never clears a persisted value unless a future, explicitly named
clear operation is introduced.

- Present `like`, `comment`, and `view` fields update their corresponding cumulative values.
- The existing non-decreasing view safeguard remains: a present zero is valid, but it cannot lower
  an already higher cumulative view count.
- `share_save` is updated only when both `share` and `collect` are present, because the database
  stores their sum and cannot reconstruct the missing component. If either is null, the prior
  combined value is preserved.
- If `view` is null, existing `views`, CPM, and FYP values are preserved; no view delta is created.
- Daily `endorse_logs` rows use the merged cumulative values, so absent fields do not turn into
  zero or create artificial negative/positive deltas.
- Present metadata fields may update TikTok metadata; absent metadata does not blank existing
  values.

Thus `stats_found=true` with only one non-view metric is sufficient to complete a daily queue item,
but it is explicitly a partial update and is not treated as evidence that views were fetched.

### Initial and final snapshots

Optimization snapshots require all five metrics to be present because a partial baseline or final
snapshot would corrupt growth calculations. If any snapshot metric is null, the response is
classified as transient/incomplete, no snapshot fields are written, and normal queue retry policy
applies.

## Result endpoint and transaction

`POST /api/endorse-refresh/result` accepts:

```json
{
  "contract_version": 2,
  "results": [
    {
      "queue_id": 719765,
      "attempt_no": 1,
      "worker_id": "w_xxx",
      "response": { "status": true, "msg": "", "data": {} }
    }
  ]
}
```

Before starting a transaction, PHP validates the contract version, payload shape, non-empty result
list, required identity fields, duplicate queue IDs, and batch limit. Duplicate IDs return HTTP 422
`duplicate_queue_id`; an oversized batch returns HTTP 413 `result_batch_too_large`.

For a structurally valid batch PHP performs one all-or-nothing transaction:

1. Sort submitted queue IDs ascending.
2. Lock every queue row in that order using `SELECT ... ORDER BY id FOR UPDATE`.
3. Lock corresponding processing attempt rows in queue-ID/attempt order.
4. Validate every tuple against the locked state:
   - queue exists;
   - queue status is `processing` and is not completed;
   - queue worker ID matches;
   - submitted attempt equals `queue.attempts + 1`;
   - a matching audit row remains `processing`.
5. If any tuple conflicts, roll back without applying any result and return HTTP 409.
6. If the complete batch is valid, apply every response through the authoritative PHP writer,
   finalize audit rows, update queue state, perform required rollups, and commit.
7. Any database/application failure rolls back the complete batch and returns HTTP 500.

The lock is held only around validation and database application, never around TikTok or RapidAPI
network access. Existing reset/reclaim updates block behind these row locks, which closes the race
between validation and final writes.

Every 409 result response contains a `conflicts` array with one entry per conflicting submission:

```json
{
  "status": false,
  "reason": "claim_conflict",
  "conflicts": [
    { "queue_id": 719765, "attempt_no": 1, "reason": "worker_mismatch" }
  ]
}
```

Result conflict reasons are `queue_not_found`, `queue_completed`, `queue_not_processing`,
`worker_mismatch`, `attempt_mismatch`, and `attempt_not_active`. All claim-state conflicts use HTTP
409 so the batch is unambiguously not applied.

On a well-formed 409, the Rust worker removes the listed conflicting queue IDs from its in-memory
batch and resubmits only the remaining results. Each conflict pass must remove at least one item,
making the loop finite. If the conflict response is malformed or removes nothing, the worker logs
the protocol error and stops resubmitting; stale recovery remains the safety net. Conflicting rows
are never resubmitted by that worker.

## Retry cooldown timestamp

`claimed_at` is the cooldown anchor for a pending retry. On a failed applied result it is set to the
actual failure/finalization time, not the original claim time. For example, a 06:00:00 claim that
fails at 06:00:40 stores 06:00:40, so a 60-second cooldown expires at 06:01:40. Stale recovery uses
its reset time as the cooldown anchor. Completed and terminally failed rows do not use cooldown.

## Structured worker logs

Each fetched batch emits structured counts for:

- `claimed`, `scrape_ok`, `fallback_ok`, and `normalized_urls`;
- `scrape_transport_failed`, `scrape_http_failed`, `scrape_parse_failed`, and
  `scrape_stats_missing`;
- `fallback_auth_failed`, `fallback_timeout`, `fallback_transport_failed`,
  `fallback_api_failed`, `fallback_invalid_response`, and `fallback_conflict`;
- result conflicts grouped by their stable reason codes.

Zero-valued metrics are successes and never increment a failure counter. Per-conflict logs include
queue ID, submitted attempt number, and stable reason, but do not print secrets or the expected
worker identity.

## Coordinated deployment

1. Keep `ENDORSE_REFRESH_DRIVER=cron` and confirm the Rust service is `0/0`.
2. Deploy the v2 PHP application first. A pre-v2 worker now receives 426 before `claimBatch()` and
   cannot create a claim or attempt row.
3. Deploy the immutable v2 worker image while preserving `0/0` replicas.
4. Scale the v2 worker to one while cron owns the queue. Confirm its v2 claim request receives
   `driver_not_rust` and creates no attempts.
5. Change ownership to `ENDORSE_REFRESH_DRIVER=rust`.
6. Canary a small batch and verify fallback/result identity, conflict, and structured log behavior
   before bulk draining.

## Coordinated rollback

1. Change ownership back to `ENDORSE_REFRESH_DRIVER=cron`. This immediately blocks new Rust claims,
   but `/fetch-fallback` and `/result` remain available for already active valid v2 claims.
2. Monitor Rust-owned `processing` rows and their matching processing audit rows until both counts
   reach zero. Do not scale the worker down while valid results remain in flight.
3. Scale `forbes_endorse-refresh-worker` to `0/0` and confirm the observed replica count is zero.
4. Only after step 3 may PHP be rolled back below v2. Keep the worker at `0/0` while the application
   is pre-v2.
5. If both application and worker images must be rolled back, treat them as a matched pair. Never
   roll the worker alone to pre-v2 while a v2 application has Rust ownership, and never run a v2
   worker against a pre-v2 application during rollback.

The driver gate affects only new claims. It must not reject fallback or result submissions solely
because ownership changed to cron; those endpoints validate the active claim identity instead.

## Verification

Automated tests cover:

- contract v1/missing `/claim` rejection before any mutation-capable service call;
- fallback 404/409/422 outcomes and repeated read-only invocation;
- strict identity validation against queue and processing audit state;
- duplicate IDs, result batch limit, deterministic lock order, full rollback on one conflict, and
  successful all-item commit;
- stale results after reset/reclaim and duplicate result submission;
- all-zero statistics as valid present values;
- missing/unparseable metrics as null and partial daily merge behavior;
- view-absent daily updates preserving views/CPM/FYP;
- partial snapshots rejected without writes;
- claimed-at cooldown anchored at failure completion;
- Rust v2 request payloads, 426 handling, finite 409 conflict filtering, and structured failure
  category counts.

Release verification also includes PHP tests/lint/style, Rust format/clippy/tests, locked release
build, workflow/deploy syntax checks, and a canary performed while the cron rollback path remains
available.
