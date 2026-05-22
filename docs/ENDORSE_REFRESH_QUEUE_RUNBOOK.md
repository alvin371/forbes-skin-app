# Endorse Refresh Queue Runbook

Related docs:

- [docs/ENDORSE_REFRESH_GUIDE.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_GUIDE.md:1)
- [docs/ENDORSE_REFRESH_GLOBAL_CRON_CLONING.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_GLOBAL_CRON_CLONING.md:1)
- [docs/ENDORSE_REFRESH_TRACE.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_TRACE.md:1)

The `/endorse-campaign` and `/endorse?id_campaign=...` bulk refresh actions only enqueue work.
Queue processing requires the async worker endpoint below to run continuously.

## Required enqueue cron

Add two daily cron entries to enqueue all active endorse content across both internal and external campaigns:

```cron
0 6 * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh-enqueue-all" >/dev/null 2>&1
0 23 * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh-enqueue-all" >/dev/null 2>&1
```

Enqueue route:

- `GET /api/cronjob/endorse-refresh-enqueue-all`

If `WORKER_SHARED_SECRET` or `WORKER_IP_ALLOWLIST` is enabled, make sure the cron caller satisfies that guard.

## Required worker cron

Add three staggered cron entries so pending rows are claimed throughout each minute:

```cron
* * * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 20; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 40; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
```

Worker route:

- `GET /api/cronjob/endorse-refresh`

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

## Retry behavior

- `Retry Gagal Terpilih` creates a new pending queue row.
- The original failed queue row stays intact for audit/history.
- Attempt-level history is stored in `endorse_refresh_queue_attempts`.

Use retry when the failure looks transient, such as timeout or upstream fetch instability.

## Clear queue behavior

- `Clear Semua Data` removes every row from `endorse_refresh_queue`.
- It also removes every row from `endorse_refresh_queue_attempts`.
- Use it only when you intentionally want to discard both active queue state and audit history.
