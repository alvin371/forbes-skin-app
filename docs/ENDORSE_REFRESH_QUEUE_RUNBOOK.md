# Endorse Refresh Queue Runbook

The `/endorse-campaign` refresh action only enqueues work. Queue processing requires the async worker endpoint below to run continuously.

## Required worker cron

Add three staggered cron entries so pending rows are claimed throughout each minute:

```cron
* * * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 20; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 40; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
```

## Expected behavior

- `/endorse/queue` shows `pending`, `processing`, `completed`, and `failed` jobs.
- The header badge counts all active `pending/processing` jobs.
- If pending jobs exist with no active worker activity for more than 10 minutes, the badge and queue page show a stalled warning.

## Retry behavior

- `Retry Gagal Terpilih` creates a new pending queue row.
- The original failed queue row stays intact for audit/history.
- Attempt-level history is stored in `endorse_refresh_queue_attempts`.
