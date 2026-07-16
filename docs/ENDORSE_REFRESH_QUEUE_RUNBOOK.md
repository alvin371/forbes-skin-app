# Endorse Refresh Queue Runbook

Related docs:

- [docs/ENDORSE_REFRESH_GUIDE.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_GUIDE.md:1)
- [docs/ENDORSE_REFRESH_GLOBAL_CRON_CLONING.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_GLOBAL_CRON_CLONING.md:1)
- [docs/ENDORSE_REFRESH_TRACE.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_TRACE.md:1)

The `/endorse-campaign` and `/endorse?id_campaign=...` bulk refresh actions only enqueue work.
Production remains on PHP cron while the central runtime row stays `contract_state=legacy`.
Do not switch Rust ownership in production until the v2 release gates pass and the runtime row
is intentionally activated.

`ENDORSE_REFRESH_DRIVER` is no longer the authoritative ownership switch after v2 activation.
Ownership comes from `endorse_refresh_runtime_control.owner_state`, and all Rust worker requests
must send `contract_version=2` plus a boot-scoped UUID v4 `worker_id`.

## Required enqueue cron

Add two daily cron entries to enqueue all active endorse content across both internal and external campaigns:

```cron
0 6 * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh-enqueue-all" >/dev/null 2>&1
0 23 * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh-enqueue-all" >/dev/null 2>&1
```

Enqueue route:

- `GET /api/cronjob/endorse-refresh-enqueue-all`

If `WORKER_SHARED_SECRET` or `WORKER_IP_ALLOWLIST` is enabled, make sure the cron caller satisfies that guard.

## PHP cron driver

Add three staggered cron entries so pending rows are claimed throughout each minute:

```cron
* * * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 20; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 40; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
```

Worker route:

- `GET /api/cronjob/endorse-refresh`

## Rust driver

The Swarm service is `forbes_endorse-refresh-worker`. Its image is deployed with an
immutable commit tag, but changing the image does not change its replica count.

Safe activation:

1. Keep production on PHP cron and Rust at `0/0` while bridge migrations, quarantine wiring,
   and v2 endpoint validation are verified.
2. Apply the bridge migration and confirm the runtime row is still `contract_state=legacy`,
   `owner_state=cron`.
3. Stop old application tasks, enter the paused activation phase, drain legacy processing rows,
   complete duplicate-campaign-log reconciliation, and only then change the runtime row to `v2`.
4. Scale Rust from `0/0` only after staging/shadow/load/circuit/drain rehearsals pass.
5. Change runtime ownership from `cron` to `rust` centrally; do not rely on env-only toggles.

Safe rollback:

1. Change runtime ownership to `draining_to_cron` or `cron`; this stops new Rust claims but must
   still allow valid `/result` submissions for already-active Rust claims.
2. Wait until active Rust-owned processing rows and active Rust attempt rows drain to zero.
3. Scale `forbes_endorse-refresh-worker` to `0/0`.
4. Only after Rust is confirmed `0/0` should PHP be rolled back below the v2 contract.

## Expected behavior

- `/endorse/queue` shows `pending`, `processing`, `completed`, and `failed` jobs.
- The header badge counts all active `pending/processing` jobs.
- If pending jobs exist with no active worker activity for more than 10 minutes, the badge and queue page show a stalled warning.

## Status meanings

- `pending`: queued and waiting for worker claim
- `processing`: claimed by a worker and currently running
- `completed`: refresh applied successfully
- `failed`: refresh stopped after a permanent error or after max attempts

Attempt history statuses:

- `processing`
- `retrying`
- `completed`
- `failed`
- `cancelled`

## Stalled queue behavior

The queue is considered stalled when:

- `pending_total > 0`
- `processing_total = 0`
- and worker activity is older than the stale threshold used by `computeHealth()`

Current operator signals:

- header badge changes to warning state
- queue icon color changes
- `/endorse/queue` shows a warning banner

## First checks when jobs do not move

1. Open `/endorse/queue` and confirm rows are stuck in `pending` or `failed`.
2. Confirm the worker cron is still calling `/api/cronjob/endorse-refresh`.
3. Check whether `processing` rows are being recycled after the 5-minute stale window.
4. Inspect failed rows via `Riwayat` to see `error_class` and `error_message`.
5. Verify TikTok fetch credentials such as `RAPIDAPI_HOST` and `RAPIDAPI_KEY`.
6. Verify whether the content is quarantined by TikTok content key; a quarantined content ID will
   not be enqueued or directly synced again until `link_upload` changes to a different content ID
   or the quarantine is explicitly cleared.

Common TikTok failure classes:

- `transient`: upstream 429/5xx or malformed-but-retryable RapidAPI responses
- `infra`: transport-level host failures such as DNS/connect timeout; now fails fast on the first attempt
- `config`: missing/invalid RapidAPI host or key; now fails fast on the first attempt

## Retry behavior

- `Retry Gagal Terpilih` creates a new pending queue row.
- The original failed queue row stays intact for audit/history.
- Attempt-level history is stored in `endorse_refresh_queue_attempts`.
- Automatic retry eligibility uses exponential cooldown. With the default
  `ENDORSE_REFRESH_RETRY_BASE_SEC=60`, attempt 1 waits 60 seconds and attempt 2 waits
  120 seconds; v2 adds deterministic jitter and tracks the next-attempt time explicitly.

Systemic provider failures differ from item failures in v2:

- provider auth/rate/timeout failures open the provider circuit and cancel the active claim
  without charging the queue attempt
- item-level unavailable or quarantined content finalizes the row permanently
- stale or mismatched `/result` payloads return `409 Conflict` and do not mutate the batch

Use retry when the failure looks transient. Do not bulk-retry rows already marked `infra` or `config` until host connectivity or credentials are fixed.

## Clear queue behavior

- `Clear Semua Data` removes every row from `endorse_refresh_queue`.
- It also removes every row from `endorse_refresh_queue_attempts`.
- Use it only when you intentionally want to discard both active queue state and audit history.
