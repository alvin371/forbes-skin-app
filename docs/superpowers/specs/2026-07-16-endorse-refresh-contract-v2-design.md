# Endorse Refresh Contract v2 — Atomic Ownership and Safe Finalization

**Date:** 2026-07-16  
**Status:** Revised design pending P0 review

## Problem and evidence

The first Rust endorse-refresh worker claimed queue rows and scraped TikTok pages directly. The
container received the application `.env`, but the worker did not read the RapidAPI configuration
and had no RapidAPI fallback. TikTok frequently returned HTTP 200 application shells without
`webapp.video-detail/itemStruct`, so the worker returned `Stats belum tersedia dari scrape` and
could exhaust three attempts in seconds.

The integration also had concurrency and lifecycle gaps:

- the Rust claim endpoint did not enforce the selected driver, so Rust and cron both claimed;
- a replica-local environment driver cannot be globally atomic during a rolling deployment;
- `/result` accepted only `queue_id`, allowing an old result to race a reset or newer claim;
- `queue.attempts` was used both as a count and as the basis for active identity;
- fallback calls were not deduplicated;
- parsers treated all-zero statistics as missing even though zero is valid data;
- provider authentication and systemic outages could consume every item's retry budget;
- worker logs did not distinguish scrape, fallback, protocol, and claim-conflict failures.

Production is stabilized on the PHP cron driver with the Rust service at zero replicas. Repository
search confirms the Rust worker is the only caller of `/api/endorse-refresh/result` and
`/api/endorse-refresh/fetch-fallback`, so v2 is strict with no legacy payload window.

The authoritative writer audit found that `Endorse_sync::apply()`, `apply_snapshot()`, and
`update_campaign_parent()` perform database work only: they do not call TikTok/RapidAPI, enqueue a
job, or dispatch an external event. A live schema audit on 2026-07-16 confirmed
`endorse_refresh_queue`, `endorse_refresh_queue_attempts`, `endorse`, `endorse_logs`,
`endorse_campaign`, and `endorse_campaign_logs` are InnoDB and have no database triggers.
`endorse_logs` already has a unique `(id_endorse, date)` key. A follow-up live index audit found
`endorse_campaign_logs` has only its primary key and contains 11 exact duplicate
`(id_campaign, date)` groups (11 extra rows). These properties and defects become enforced
preconditions rather than assumptions.

## Goals

1. Make driver ownership globally atomic across all PHP and Rust replicas.
2. Prevent a pre-v2 worker from claiming any row against a v2 application.
3. Give every active claim an explicit audit identity independent of the charged-attempt count.
4. Keep fallback fetches idempotent, deduplicated, and free of queue/business mutations.
5. Reject stale results before they can overwrite a newer claim or completed row.
6. Preserve valid zero metrics, represent absent metrics as null, and label partial observations.
7. Circuit-break systemic failures without consuming per-item attempts.
8. Keep all queue, audit, business, and rollup writes inside one deterministic transaction.
9. Provide operational metrics, alerts, multi-replica tests, and forced rollback recovery.

## Non-goals

- Giving RapidAPI credentials to Rust. PHP remains the provider credential owner.
- Making direct TikTok scraping authoritative. It remains an opportunistic fast path.
- Supporting pre-v2 result/fallback payloads.
- Using Redis. This repository has no Redis dependency; MySQL is already shared by all replicas.
- Treating partial daily data as a complete snapshot.

## Required schema and indexes

Schema changes are additive and run while the Rust service is `0/0`. The sole data-repair exception
is the reviewed, archived campaign-log duplicate reconciliation described below.

### Central runtime control

Create singleton table `endorse_refresh_runtime_control`:

- `id = 1` primary key;
- `contract_state`: `legacy`, `activating_v2`, or `v2`;
- `owner_state`: `cron`, `rust`, `draining_to_cron`, `draining_to_rust`, or `paused`;
- `generation` unsigned bigint incremented on every state transition;
- `circuit_state`: `closed`, `open`, or `half_open`;
- `circuit_reason`, `circuit_open_until`, `circuit_failure_count`;
- `probe_worker_id` nullable UUID for the single half-open probe;
- `updated_at`, `updated_by`.

Create singleton `endorse_refresh_provider_health` rows keyed by provider. Each row stores the
current consecutive failure class/count plus a bounded rolling-window start, total sample count,
and per-class failure counts. Provider outcomes update this row and, when necessary, the runtime
circuit in a short transaction that locks runtime first. This makes systemic thresholds global
across PHP and Rust replicas rather than process-local.

The seed state is contract `legacy`, owner `cron`, circuit closed. The new application keeps the
existing PHP lifecycle only while the centrally stored contract state is `legacy`; it rejects Rust
claims in that mode. `ENDORSE_REFRESH_DRIVER` becomes a deployment bootstrap check only and is
never consulted after contract v2 activation.

### Queue and attempts

Add to `endorse_refresh_queue`:

- `claim_owner` nullable `cron|rust`;
- `attempt_sequence` unsigned integer, default 0;
- `active_attempt_id` nullable attempt audit ID;
- `next_attempt_at` nullable datetime.

Extend attempt audit status with `cancelled`. Add unique index
`uq_queue_attempt (queue_id, attempt_no)` and lookup index
`idx_attempt_active (queue_id, id, worker_id, status)`. Production currently has no duplicate
`(queue_id, attempt_no)` pairs, so the unique index is safe after a migration-time recheck.
Backfill each queue's `attempt_sequence` to the maximum historical audit `attempt_no`, or zero when
none exists. Because legacy PHP remains active during the bridge rollout, the activation operation
repeats this reconciliation after legacy processing reaches zero and before enabling v2; it rejects
any sequence lower than the historical maximum.

Add claim-ready index:

```text
idx_claim_ready
  (status, worker_id, next_attempt_at, priority, attempts, created_at)
```

Add `idx_processing_owner (status, claim_owner, active_attempt_id)` for ownership drains. Keep the
existing worker and campaign indexes for worker-level monitoring and campaign lookups.

### Fallback deduplication

Create `endorse_refresh_fallback_calls` with:

- unique key `(queue_id, attempt_no, worker_id)`;
- `status`: `in_progress`, `completed`, or `failed`;
- `lease_token` UUID and `lease_expires_at`;
- `http_status`, `reason`, and `response_json`;
- `created_at`, `updated_at`, `completed_at`.

Add cleanup index `(status, updated_at)`. Retain terminal cache rows for seven days, then prune them
outside the request path.

### Observation completeness

Add latest-observation metadata to `endorse` and daily observation metadata to `endorse_logs`:

- `stats_completeness`: `complete` or `partial`;
- `stats_fields` JSON array of fields present in one provider response;
- `stats_source`: `tiktok_scrape` or `rapidapi_fallback`;
- `stats_observed_at`.

These columns describe the observation, not the merged cumulative row.

### Business-writer uniqueness

Keep `uniq_endorse_logs_endorse_date (id_endorse, date)`. Add
`uq_endorse_campaign_logs_campaign_date (id_campaign, date)` before v2 activation so concurrent
first writes for a campaign/day cannot create duplicate rollup rows.

Production currently has 11 duplicate campaign/day groups. The migration does not silently discard
them: it creates an archive table containing the full duplicate rows plus `canonical_id`,
`archived_at`, and reason `v2_unique_key_reconciliation`. For each group, a dry-run report selects
the newest row by `COALESCE(updated_at, created_at)` then ID as the proposed canonical row. An
operator reviews the 11 groups; the migration archives and removes only approved non-canonical
rows, verifies source/archive counts, and then creates the unique key. If review, archival, count
verification, deletion, or index creation fails, activation stops.

The v2 writer locks the campaign row before its campaign/day log and uses the unique key for
insert-or-update safety. It does not rely on an unlocked select-then-insert check.

### Migration preconditions

The migration refuses to activate v2 if:

- any critical table is not InnoDB;
- duplicate queue/attempt identities would prevent the unique keys;
- any queue `attempt_sequence` is below its maximum historical audit attempt number;
- the required `endorse_logs (id_endorse,date)` unique key is absent;
- campaign-log duplicate reconciliation is incomplete or the required
  `endorse_campaign_logs (id_campaign,date)` unique key is absent;
- the runtime singleton cannot be created and locked.

## Globally atomic ownership

When `contract_state=v2`, every mutation-capable claim transaction locks runtime row `id=1` first
with `FOR UPDATE`.

- PHP cron may claim only when `owner_state=cron` and the circuit permits work.
- Rust may claim only when `owner_state=rust` and the circuit permits work.
- Manual processing uses the cron claim path and is rejected unless cron owns the queue.
- `draining_*` and `paused` deny all new claims from both consumers.
- Fallback, result, and release endpoints do not reject an already active claim merely because
  ownership entered a draining state.

A row is claim-eligible only when `status='pending'`, `worker_id IS NULL`,
`attempts < max_attempts`, and `next_attempt_at IS NULL OR next_attempt_at <= NOW()`. Both
consumers use the same indexed eligibility predicate.

Changing owners is a state transition, not a direct environment flip:

1. Lock the runtime row and change `cron|rust` to `draining_to_<target>`; increment generation.
2. Commit. From that point every v2 replica denies new claims.
3. Drain active rows belonging to the old `claim_owner`.
4. Lock the runtime row again, verify the old-owner processing count is zero, change to the target
   owner, and increment generation.

No transition can skip the drain-zero assertion. A stuck transition remains safely claim-paused.
All v2 replicas read and lock the database row on every claim; no application cache is allowed.
The authenticated admin transition operation requires `expected_state` and `expected_generation`;
a compare-and-swap mismatch returns 409 `owner_state_changed`, and a premature final transition
returns 409 `active_claims_remaining`. There is no general-purpose configuration write path.

### Initial v2 rollout across mixed application replicas

An old application replica does not know the central gate, and old/v2 PHP must not process rows
with different attempt lifecycles. The initial application release is therefore a database-gated
bridge:

- preflight proves every old app task is configured cron-only and the Rust service is desired and
  observed `0/0`;
- additive migrations seed `contract_state=legacy`, so both old PHP and the new bridge image use
  the existing PHP lifecycle throughout the rolling update;
- the bridge image rejects Rust `/claim` with 503 `contract_not_active` while state is legacy;
- deployment automation refuses activation until `docker service ps` proves every running app task
  uses the bridge/v2 image digest and all old tasks are stopped;
- it then atomically changes state to `activating_v2`, which makes every bridge replica deny new
  claims, drains all legacy processing queues/audits to zero, and activates `contract_state=v2`
  with owner `cron` in one locked transition;
- a failed or incomplete rollout never leaves legacy state and keeps Rust `0/0`.

The activation command requires the expected runtime generation, the expected image digest, zero
old tasks, and zero legacy processing rows; otherwise it fails closed. After activation, the
database state machine is the sole authority for all future rolling deployments, and local driver
values are ignored. CI includes mixed-replica tests for both the legacy bridge phase and v2 owner
switches.

## Contract version and worker identity

The contract version is `2`. Every Rust-to-PHP request includes top-level
`contract_version: 2`.

`/claim` validates the contract and authentication before loading any mutation-capable claim path.
A missing or unsupported version returns HTTP 426 `contract_version_unsupported` before runtime,
queue, or audit mutations. A pre-v2 worker therefore cannot claim through a v2 replica.

Every Rust process calls the UUID v4 generator once before starting its request loop and submits
that value as `worker_id` on every request for that process lifetime. The value cannot come from
configuration, a service name, a hostname, or a Swarm task name. A new process boot necessarily
executes the generator again; tests launch two processes and require different IDs. PHP cron
generates a UUID v4 per PHP worker process/request. The server requires canonical RFC 4122 UUID v4
syntax and version bits, rejecting non-UUID or non-v4 identifiers with HTTP 422
`invalid_worker_id`; logs record first-seen time and application version so accidental reuse is
alertable.

A successful claim returns contract v2, the boot UUID, and per-item `queue_id`, `attempt_no`, and
`active_attempt_id`. The worker verifies all echoed identities before fetching.

The result batch maximum is `ENDORSE_REFRESH_RESULT_BATCH_MAX`, initially **10**, clamped to
`1..500` where 500 is the claim maximum. Rust claims are limited to the smaller of requested claim
size and the result maximum. Increasing above 10 requires recorded load/deadlock results and an
explicit configuration change; it is not part of the first canary.

## Exact attempt lifecycle

`queue.attempts` is the count of **charged, finalized item attempts**. It excludes the currently
active claim and is never used to identify that claim.

1. Claim transaction locks runtime then queue rows.
2. For each queue, increment `attempt_sequence` and insert an audit row with
   `attempt_no=attempt_sequence`, `status=processing`, boot UUID, and start time.
3. Store the inserted audit ID in `queue.active_attempt_id`; set queue status/owner/worker/times.
4. Do not change `queue.attempts` during claim.
5. A successful or item-level failed `/result` finalizes the active audit and increments
   `queue.attempts` exactly once in the same transaction.
6. A no-charge systemic release or stale recovery finalizes the audit as `cancelled`, clears
   `active_attempt_id`, and does not increment `queue.attempts`.
7. Deferral before a network attempt is also cancelled/no-charge, never deleted from history.

Every finalization path clears `worker_id`, `claim_owner`, and `active_attempt_id`. Success sets the
queue completed. A charged failure sets it failed when the incremented count reaches `max_attempts`
or the item is permanent; otherwise it returns to pending with `next_attempt_at`. Cancellation
returns it to pending.

Validation follows `queue.active_attempt_id -> audit.id`, then verifies submitted queue ID,
attempt number, worker UUID, and audit status. It never derives identity as `queue.attempts + 1`.
Monotonic `attempt_sequence` prevents a reset/reclaim from reusing an old attempt number.

## Authentication and driver contracts

Worker endpoints return JSON with stable reasons:

| HTTP | Reason | Worker behavior |
|---:|---|---|
| 401 | `worker_auth_missing` | Fatal local auth circuit; stop claims and alert |
| 401 | `worker_auth_invalid` | Fatal local auth circuit; stop claims and alert |
| 403 | `worker_ip_denied` | Fatal local auth circuit; stop claims and alert |
| 426 | `contract_version_unsupported` | Mark incompatible/unhealthy; stop claims until upgrade |
| 503 | `contract_not_active` | Deployment standby; do not claim or create attempts |
| 409 | `driver_not_owner` | Stand by; poll central ownership with bounded interval |
| 409 | `driver_draining` | Stand by; do not claim; allow active results to finish |
| 503 | `circuit_open` | Honor retry time; do not claim or charge attempts |

Authentication and contract checks happen before database mutation. Driver/circuit rejections occur
while holding only the runtime control lock and create no queue/audit rows. Health output exposes
the stable pause reason without secrets.

## Fetch and fallback behavior

Rust first performs a direct TikTok HTTP/1.1 fetch. A direct response is authoritative only when all
five metrics are present and parseable. Any missing metric, including a missing view with other
metrics present, triggers PHP fallback within the same queue attempt.

`POST /api/endorse-refresh/fetch-fallback` accepts
`{contract_version, queue_id, attempt_no, active_attempt_id, worker_id}`. PHP performs read-only
claim validation against queue and active audit state, then validates the normalized TikTok URL.
Queue errors are 404/409 and invalid URLs are 422 with stable reasons.

Fallback reasons are exact: 404 `queue_not_found`; 409 `queue_completed`,
`queue_not_processing`, `worker_mismatch`, `attempt_mismatch`, or `attempt_not_active`; 422
`invalid_tiktok_url`; 409 `fallback_in_progress`; 503 `provider_auth_failed`; 503
`provider_rate_limited` with `Retry-After`; 504 `provider_timeout`; 502 `provider_unavailable`; and
502 `provider_invalid_response`. Provider reasons include no credentials or response body.

### Deduplicated external call

After read-only claim validation, fallback atomically acquires the unique dedup row in a short
transaction and commits before any network call:

- a completed/failed row returns its cached HTTP status and response;
- an unexpired `in_progress` row returns HTTP 409 `fallback_in_progress` with `Retry-After: 2`;
- a missing row is inserted with a random lease token;
- an expired lease may be taken over with a compare-and-swap update.

The lease is 90 seconds, longer than the bounded PHP provider timeout plus response-write margin.
PHP then calls RapidAPI with no transaction and no row lock held. Its completion transaction locks
runtime, provider health, then the dedup lease row; it records the provider outcome/circuit decision
and writes the cached response only if it still owns the lease. It never changes queue status,
attempts, active identity, business rows, or rollups. Calling fallback twice for the same claim
therefore produces at most one provider call during a valid lease and one cached result for that
attempt.

If the claim changes while the network call runs, the dedup response may still be cached, but
`/result` performs authoritative locked validation and rejects the stale identity.

Provider-error responses include `failure_scope: item|systemic`. Auth and rate-limit responses are
immediately systemic; timeout/connect/5xx become systemic only when the central threshold
transition succeeds. Rust submits an item-scoped failure to `/result` but uses `/release` for a
systemic response. It never decides the scope from a local replica counter.

## Nullable statistics and partial observations

The metrics `like`, `share`, `comment`, `collect`, and `view` are nullable. Null means absent or
unparseable; numeric zero means present with value zero. `stats_found=true` requires at least one
known metric to be present and parseable. `stats_complete=true` requires all five fields from the
same response.

Direct partial results always try fallback. If fallback is complete, it replaces the direct partial
observation. If fallback is partial or unavailable, the final candidate is exactly one provider
response: prefer a response containing view, then the response with more present fields, then the
newer fallback response as a tie-breaker. Metrics from direct and fallback responses are never
combined into one observation. If that candidate remains partial:

- purpose `daily` may complete as an explicitly partial observation;
- purposes `initial` and `final` are transient/incomplete and write nothing.

Daily partial writes merge only present fields into current authoritative values. Null never clears
a stored value. `share_save` updates only when both share and collect are present. If view is null,
existing views, CPM, and FYP are preserved and no view delta is created. A present zero is valid;
the existing non-decreasing view safeguard still prevents lowering a higher cumulative view count.

Both `endorse` and `endorse_logs` store completeness, source, present-field list, and observation
time. Merged values are never labelled complete unless all five came from the same fetch response.
Campaign rollups continue using merged cumulative business values, while completeness metrics make
the mixed observation visible to operators and downstream consumers.

## Result endpoint and all-or-nothing transaction

`POST /api/endorse-refresh/result` accepts contract v2 plus results containing
`queue_id`, `attempt_no`, `active_attempt_id`, `worker_id`, and response.

Before starting a transaction PHP validates payload shape, UUIDs, non-empty list, duplicate queue
IDs, and batch limit. Duplicate IDs return 422 `duplicate_queue_id`; oversized batches return 413
`result_batch_too_large`.

The authoritative transaction uses this global lock order for every mutation path:

1. runtime control singleton;
2. provider-health row when the path records an outcome or evaluates a provider threshold;
3. fallback dedup rows when the path verifies a provider outcome;
4. queue rows ordered by queue ID;
5. attempt audit rows ordered by queue ID then audit ID;
6. endorse rows ordered by endorse ID;
7. existing daily endorse-log rows ordered by endorse ID;
8. campaign rows ordered by campaign ID;
9. existing campaign-log rows ordered by campaign ID/date.

The initial fallback lease acquisition touches only its dedup row and commits. No path acquires
runtime/provider locks after a held dedup lock. Provider completion and release use the order above;
fallback never holds queue/business locks during the network request. Claim, result, release, stale
recovery, force recovery, and manual queue mutations follow the same relative order for every
subset they touch.

After locking, PHP validates the complete batch against `active_attempt_id` and its processing audit
row. Any missing/stale/completed/mismatched identity rolls back the full batch and returns HTTP 409
`claim_conflict` with per-queue stable reasons: `queue_not_found`, `queue_completed`,
`queue_not_processing`, `worker_mismatch`, `attempt_mismatch`, and `attempt_not_active`.

An ownership drain or open circuit blocks new claims only. It does not invalidate an otherwise
matching active result; active identities remain authoritative until normal finalization, release,
stale recovery, or explicit force recovery.

Only after every item validates does PHP apply business rows, audit rows, queue state, and campaign
rollups. All are committed together. No external request, event dispatch, queue dispatch, or
non-transactional side effect is allowed inside the transaction. An implementation guard documents
the permitted DB-only call graph and tests fail if network adapters are introduced into it. Any
optional notification or follow-up dispatch is registered only after a successful commit; rollback
cannot emit it.

The v2 PHP cron follows the same boundary: it claims and commits, performs TikTok/RapidAPI calls
without locks, then invokes this same locked finalizer with the server-held active identities. It
does not retain a separate, less strict queue/business mutation path.

MySQL deadlock/lock-timeout errors retry the whole transaction at most twice with 25-100 ms jitter.
If retries are exhausted, PHP rolls back and returns 503 `transaction_retry_exhausted`; the worker
backs off without charging attempts.

On a well-formed 409, Rust removes the listed conflicting IDs from its in-memory result set and
resubmits only remaining results. Each pass must remove at least one ID, so the loop is finite. A
malformed/no-progress conflict response stops resubmission and emits a protocol alert.

## Complete retry and circuit-breaker policy

### Item-level policy

- `max_attempts` defaults to 3 and is clamped to `1..10` when enqueued.
- One attempt contains direct scrape plus at most one deduplicated fallback call.
- A complete or accepted partial daily success charges one attempt and completes the queue.
- Confirmed permanent item failures (invalid TikTok URL/content ID, deleted/private/not-found post,
  unsupported platform) charge one attempt and fail immediately.
- Per-item transient failures (provider timeout/5xx below systemic threshold, missing statistics
  after fallback, malformed item response) charge one attempt. If below max, return to pending.
- An unclassified item error defaults to charged transient failure; a malformed contract or
  batch-wide protocol invariant opens a local worker circuit instead of walking the queue.
- On charged failure, `claimed_at` is the actual finalization time and `next_attempt_at` is stored.

Cooldown after charged failure is:

```text
base = min(900, 60 * 2^(charged_attempts - 1)) seconds
delay = base * deterministic_jitter(queue_id, attempt_no, 0.80..1.20)
```

Thus typical retries are approximately 48-72 seconds after failure one and 96-144 seconds after
failure two. Eligibility uses `next_attempt_at`, not elapsed time from the original claim.

### Systemic policy

If an item has no acceptable observation because of a systemic failure, that failure does not
charge its attempt. It opens a central circuit and releases the active claim as `cancelled` through
an authenticated v2 release transaction. A daily item that already has an acceptable direct
partial observation may instead finalize as a charged partial success while the provider circuit
still pauses new claims.

- Worker authentication 401/403: local fatal circuit immediately; no further requests; stale
  recovery cancels any in-flight claims if the worker can no longer authenticate.
- Contract 426: incompatible worker exits claim loop; no claim exists because version is checked
  before claim.
- RapidAPI missing/invalid credentials or provider 401/403: central circuit opens indefinitely
  until operator correction and explicit reset.
- Provider 429: central circuit opens for `Retry-After`, or 60 seconds if absent.
- Repeated timeout/connect/5xx: open when five same-class failures occur consecutively or when at
  least 10 samples have >=50% same-class failures.
- Timed transient circuit durations use 60, 120, 240, 480, then 900 seconds, each with 20% jitter.

After a timed circuit expires, the runtime row enters `half_open`. Exactly one worker UUID may claim
one probe item. Probe success closes and resets the circuit; systemic probe failure reopens it at the next
duration. Other replicas receive `circuit_open` while the probe is active.

The provider-health rolling window is five minutes and is updated for every fallback provider
success or failure. A success resets the consecutive counter but remains a sample in the window;
window counters reset when the window expires. Immediate auth/429 rules bypass the sample minimum.
Threshold evaluation and circuit transition lock the runtime and provider-health rows atomically.

`POST /api/endorse-refresh/release` validates active identities with the standard lock order,
atomically opens/extends the central circuit for an allowlisted systemic reason, marks audits
cancelled, clears active claims, and returns rows to pending without incrementing attempts. PHP
accepts an immediate release only for server-observed auth/429 failures or when the central
provider-health threshold is already satisfied; it does not trust a worker-supplied classification
alone. A worker cannot label permanent item errors systemic.

Stale recovery also cancels/no-charges abandoned active audits. It records a separate operational
failure metric. Three stale cancellations for one worker boot UUID or three batch-wide stale
recoveries within 10 minutes open the central `worker_stall` circuit for 60 seconds and alert, so
no-charge recovery cannot silently recycle forever.

## Metrics and alerts

Required metrics:

- runtime owner state/generation and transition age;
- circuit state/reason/open duration/probe state;
- pending/processing by `claim_owner`, oldest pending age, and next-attempt delay;
- charged attempts and cancelled claims by outcome/reason;
- scrape success plus transport/HTTP/parse/stats-missing categories;
- fallback success, dedup hit/in-progress/takeover, auth/timeout/transport/API/invalid categories;
- result conflicts by stable reason, transaction duration, deadlocks, and retry exhaustion;
- complete/partial observations by source and missing-field set;
- worker health, boot UUID, contract version, and last successful claim/result timestamps.

Alerts:

- any contract/auth failure: immediate critical;
- owner transition older than 180 seconds: critical;
- processing rows owned by both cron and Rust outside a drain test: critical;
- circuit open longer than 5 minutes or indefinite auth circuit: critical;
- queue oldest pending over 10 minutes while owner is active: warning, over 30 minutes critical;
- fallback failure ratio over 20% for 5 minutes: warning; over 50% critical;
- transaction deadlock ratio over 1% or any retry exhaustion: warning;
- result claim conflicts above the expected rollback/stale baseline: warning;
- partial daily observations over 20% for 10 minutes or missing-view partials: warning;
- stale recovery cancellations above three in 10 minutes: warning.

## Multi-replica and load verification

Before production canary, automated/integration tests cover:

- two PHP replicas with intentionally conflicting local driver env values still obeying one DB
  owner;
- concurrent cron/Rust claims during every state transition with no mixed-owner new claims;
- initial bridge rollout with pre-v2 and v2 app replicas, legacy lifecycle only, activation blocked
  until the old replica and processing counts are zero, and Rust remaining `0/0`;
- two Rust replicas with distinct boot UUIDs and disjoint claims;
- pre-v2 `/claim` rejection before mutation;
- active audit identity after reset/reclaim without attempt-number reuse;
- fallback dedup concurrency, cached response, expired-lease takeover, and no network-under-lock;
- ordered result locking, duplicate IDs, limit 10, one-conflict full rollback, conflict filtering,
  deadlock retry, and no-progress stop;
- transaction rollback across queue, audit, endorse, logs, campaign, and campaign logs;
- campaign-log duplicate dry-run/archive/count verification and concurrent first-write uniqueness;
- all-zero complete stats, direct partial fallback, final partial daily metadata, missing-view merge,
  and snapshot partial rejection;
- systemic auth/429/timeout circuit transitions, single half-open probe, no-charge release, and
  per-item jittered cooldown;
- driver drain timeout and forced recovery rejecting late results.

Load testing begins at result batches of 10 with at least two app replicas and two simulated workers.
Record p50/p95/p99 transaction time, lock wait, deadlock rate, and rows/minute. Raising the batch
limit requires zero retry exhaustion, deadlocks below 1%, p95 result transaction below one second,
and a documented canary step.

## Coordinated deployment

1. Confirm every old app task is cron-only and Rust is desired and observed `0/0`.
2. Apply additive migrations and verify contract `legacy`, owner `cron`, circuit closed, indexes,
   engines, and uniqueness preconditions.
3. Roll the bridge/v2 PHP application while contract state remains legacy and Rust remains `0/0`.
   Both old and new app tasks use only the legacy PHP lifecycle.
4. Verify every running app task uses the v2 image digest. Abort safely in legacy cron-only mode on
   any mixed or failed rollout.
5. Change contract state to `activating_v2`; bridge replicas stop new claims. Drain legacy processing
   queues and audits to zero, then atomically activate contract v2 with owner `cron`.
6. Exercise one PHP cron batch through the v2 finalizer and verify the v2 audit invariants.
7. Deploy the immutable v2 worker image while preserving `0/0`.
8. Scale one v2 worker in standby. Its v2 claim receives `driver_not_owner` and creates no attempts.
9. Transition central state to `draining_to_rust`; wait for cron-owned processing rows to reach zero.
10. Atomically set owner `rust`, canary batch 10, and verify identity, dedup, transaction, partial,
    circuit, and metrics behavior before bulk draining.

## Coordinated rollback and forced recovery

1. Atomically change runtime state from `rust` to `draining_to_cron`. This stops new Rust and cron
   claims but does not reject fallback/result submissions for active valid Rust claims.
2. Wait up to `ENDORSE_REFRESH_DRAIN_TIMEOUT_SEC`, default 180 seconds, for Rust-owned processing
   queues and processing audits to both reach zero.
3. Normal path: after both are zero, scale Rust to `0/0`, verify the observed replica count, then
   atomically set owner `cron` if PHP remains v2.
4. Timeout path: keep state draining and invoke the authenticated force-recovery operation. In one
   ordered transaction it cancels active Rust audits with reason `rollback_drain_timeout`, clears
   active identities, returns queues to pending without charging attempts, and records an alert.
   Late Rust results then receive 409 `attempt_not_active`.
5. Reconfirm Rust processing/audit counts are zero, scale the worker to `0/0`, and verify zero. If
   PHP remains v2, then set owner `cron`.
6. For a full PHP rollback, keep new claims paused after Rust reaches `0/0`, drain every remaining
   v2 processing queue/audit to zero, and atomically set contract state to `legacy` with owner
   `cron`. All still-running v2 bridge images then use the legacy PHP lifecycle. Roll every app task
   below v2 only after that transition and with the worker still `0/0`.
7. Verify all rolled-back app tasks are cron-configured before normal scheduling resumes. App and
   worker rollbacks are a matched operation; a pre-v2 worker is never run against a v2 endpoint,
   and a v2 worker remains zero while PHP is pre-v2.

The forced operation is auditable, requires worker/admin authentication plus explicit owner and
generation confirmation, and cannot run while runtime owner is directly `rust`; it is allowed only
in `draining_to_cron` after the configured timeout.
