# Endorse Refresh Guide

This is the canonical implementation guide for the endorse refresh system.

It documents the current behavior exactly as implemented in this repo so the same pattern can be cloned into another CodeIgniter project with minimal guesswork.

For the server-side global cron variant added on top of this queue flow, see:

- [docs/ENDORSE_REFRESH_GLOBAL_CRON_CLONING.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_GLOBAL_CRON_CLONING.md:1)

## Summary

The refresh feature has two execution modes:

- Queue-based refresh for campaign-level and bulk refresh actions
- Direct synchronous refresh for single-row `Refresh`

The queue-backed flow is used by:

- `/endorse-campaign` card `Refresh`
- `/endorse-campaign` page `Refresh Semua`
- `/endorse?id_campaign=...` page `Refresh Semua`
- `/endorse?id_campaign=...` bulk action `Refresh Data`

The direct flow is used by:

- `/endorse?id_campaign=...` row `Refresh`

Operational monitoring lives at:

- `/endorse/queue`
- header badge via `/endorse/queue-count`

## User Entrypoints

### `/endorse-campaign` card button: `Refresh`

Entry UI:

- [application/views/endorse_campaign/item.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse_campaign/item.php:67)
- JS handler in [application/views/endorse_campaign/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse_campaign/all.php:165)

Request:

- `GET /ajax/refresh-campaign-endorses?id_campaign={campaign_id}`

Backend:

- [Ajax::refresh_campaign_endorses()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Ajax.php:7556)
- [EndorseRefreshQueueService::enqueueCampaign()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/EndorseRefreshQueueService.php:17)

Behavior:

- Enqueues all eligible endorse rows under one campaign
- Returns queue counts immediately
- Actual refresh happens later in the worker

### `/endorse-campaign` page button: `Refresh Semua`

Entry UI:

- [application/views/endorse_campaign/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse_campaign/all.php:71)
- JS handler in [application/views/endorse_campaign/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse_campaign/all.php:183)

Request pattern:

- loops visible campaign cards
- calls `GET /ajax/refresh-campaign-endorses?id_campaign={campaign_id}` once per rendered card

Behavior:

- Bulk enqueue at the browser layer, not a dedicated backend batch route
- Only campaigns currently rendered on the page are included

### `/endorse?id_campaign=...` page button: `Refresh Semua`

Entry UI:

- [application/views/endorse/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/all.php:1216)
- primary JS handler in [application/views/endorse/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/all.php:1975)
- fallback JS handler in [application/views/endorse/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/all.php:2675)

Request:

- `POST /endorse/bulk-refresh`

Payload:

- `id_campaign`

Backend:

- [Endorse::bulk_refresh()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Endorse.php:1804)

Behavior:

- Enqueues all eligible endorse rows within the current campaign
- Returns JSON immediately
- UI links users to `/endorse/queue?id_campaign={campaign_id}`

### `/endorse?id_campaign=...` bulk action: `Refresh Data`

Entry UI:

- action menu in [application/views/endorse/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/all.php:1691)
- modal in [application/views/endorse/action.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/action.php:1)

Request chain:

- `POST /endorse/action-process`
- `action_process()` converts selected row IDs into `bulk_refresh()` input

Behavior:

- Refreshes only selected endorse rows
- Still uses the queue path, not direct refresh

### `/endorse?id_campaign=...` row button: `Refresh`

Entry UI:

- [application/views/endorse/item.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/item.php:478)
- [application/views/endorse/item.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/item.php:801)
- modal in [application/views/endorse/sync.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/sync.php:1)

Request:

- `POST /endorse/sync-process`

Backend:

- [Endorse::sync_process()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Endorse.php:1749)

Behavior:

- Loads one `endorse` row
- Calls `Template::get_social_media()` immediately
- Applies stats immediately
- Updates campaign rollup immediately
- Does not use the queue

## Runtime Architecture

### Queue-backed flow

1. UI button sends enqueue request.
2. Controller calls `EndorseRefreshQueueService::enqueueCampaign(...)`.
3. Service inserts rows into `endorse_refresh_queue`.
4. Cron hits `GET /api/cronjob/endorse-refresh`.
5. [Api_v2::cronjob_endorse_refresh()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Api_v2.php:7699) claims pending rows.
6. Worker fetches social metrics through `Template::get_social_media_batch()`.
7. Worker persists endorse + endorse_logs changes through [Endorse_sync::apply()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/Endorse_sync.php:64).
8. Worker rolls up touched campaigns through `Endorse_sync::update_campaign_parent()`.
9. Queue UI and header badge read queue state from `Endorse::queue_data()` and `Endorse::queue_count()`.

### Direct row refresh flow

1. User confirms modal.
2. `Endorse::sync_process()` loads one active endorse row.
3. `Template::get_social_media($platform, $link_upload)` fetches metrics.
4. `Endorse_sync::apply()` updates `endorse` and `endorse_logs`.
5. `Endorse_sync::update_campaign_parent()` recalculates campaign aggregate values.

## Routes And Interfaces

### Queue creation routes

- `GET /ajax/refresh-campaign-endorses` -> `Ajax/refresh_campaign_endorses`
- `POST /endorse/bulk-refresh` -> `Endorse/bulk_refresh`
- `POST /endorse/action-process` -> existing bulk-action modal route, which may delegate to `bulk_refresh()`

### Queue monitoring routes

- `GET /endorse/queue` -> queue dashboard page
- `GET /endorse/queue-data` -> paginated queue rows + summary + health
- `GET /endorse/queue-history?id={queue_id}` -> attempt history for one queue row
- `GET /endorse/queue-count` -> active count for header badge

### Queue control routes

- `POST /endorse/force-retry` -> clone failed rows back into pending queue
- `POST /endorse/clear-queue` -> clear queue and attempt history

### Worker route

- `GET /api/cronjob/endorse-refresh`

### Legacy compatibility route

- `POST /endorse/sync-all-process` still exists and forwards to `bulk_refresh()`

### Queue enqueue response shape

Returned by `Endorse::bulk_refresh()` and `Ajax::refresh_campaign_endorses()`:

```json
{
  "status": true,
  "msg": "10 konten ditambahkan ke antrian.",
  "enqueued": 10,
  "skipped_duplicates": 2,
  "id_campaign": 123
}
```

Possible extra field:

- `excluded_known_url`

### Queue page data response shape

Returned by `GET /endorse/queue-data`:

```json
{
  "recordsTotal": 25,
  "recordsFiltered": 25,
  "data": [],
  "summary": {
    "pending": 0,
    "processing": 0,
    "completed": 0,
    "failed": 0
  },
  "health": {
    "active_total": 0,
    "pending_total": 0,
    "processing_total": 0,
    "oldest_pending_at": null,
    "last_completed_at": null,
    "last_started_at": null,
    "is_stalled": false
  }
}
```

### Queue history response shape

Returned by `GET /endorse/queue-history?id={queue_id}`:

```json
{
  "status": true,
  "data": [
    {
      "attempt_no": 1,
      "worker_id": "w_xxx",
      "status": "failed",
      "error_class": "transient",
      "error_message": "Gagal mengambil data sosial media",
      "started_at": "2026-05-19 10:00:00",
      "finished_at": "2026-05-19 10:00:12",
      "created_at": "2026-05-19 10:00:00"
    }
  ]
}
```

### Queue count response shape

Returned by `GET /endorse/queue-count`:

```json
{
  "count": 4,
  "stalled": false,
  "oldest_pending_at": null
}
```

## Queue Lifecycle

### Queue statuses

Stored in `endorse_refresh_queue.status`:

- `pending`
- `processing`
- `completed`
- `failed`

### Attempt statuses

Stored in `endorse_refresh_queue_attempts.status`:

- `processing`
- `retrying`
- `completed`
- `failed`

### Error classes

Classified in `Endorse_sync`:

- `permanent`
- `transient`
- `empty`

Interpretation:

- `permanent`: do not retry automatically
- `empty`: response structurally valid but metrics not found; treated as non-retryable
- `transient`: retry until `max_attempts`

### Enqueue rules

`EndorseRefreshQueueService::enqueueCampaign()` only queues rows where:

- `endorse.id_campaign` matches target campaign
- `endorse.status = 'Aktif'`
- `endorse.status_campaign = 'Aktif'`
- `endorse.link_upload != ''`

It also:

- skips rows already present in active queue state `pending` or `processing`
- skips rows whose latest failure is a known TikTok URL/content issue
- uses `priority = 10`
- uses `attempts = 0`
- uses `max_attempts = 3`

### Worker behavior

`Api_v2::cronjob_endorse_refresh()` does this every tick:

1. Marks stale processing attempts as `retrying`
2. Resets stale queue rows back to `pending`
3. Claims up to `BATCH_SIZE = 30` rows
4. Creates attempt audit rows
5. Loads endorse rows and previous stats
6. Fetches remote metrics
7. Marks each row as `completed`, `failed`, or back to `pending`
8. Rebuilds touched campaign aggregates once each

Default worker constants:

- `BATCH_SIZE = 30`
- `PARALLEL_HTTP = 10`
- stale threshold `5` minutes

## Database Requirements

### Required queue tables

From migrations:

- [migrations/20260428000000_create_endorse_refresh_queue.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/migrations/20260428000000_create_endorse_refresh_queue.php:1)
- [migrations/20260428001000_add_worker_id_to_endorse_refresh_queue.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/migrations/20260428001000_add_worker_id_to_endorse_refresh_queue.php:1)
- [migrations/20260430000000_add_endorse_refresh_attempts.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/migrations/20260430000000_add_endorse_refresh_attempts.php:1)

`endorse_refresh_queue` fields:

- `id`
- `id_endorse`
- `id_campaign`
- `platform`
- `link_upload`
- `status`
- `priority`
- `attempts`
- `max_attempts`
- `error_message`
- `worker_id`
- `claimed_at`
- `enqueued_by`
- `retry_source_id`
- `created_at`
- `started_at`
- `completed_at`

Required indexes:

- `idx_pop (status, priority, created_at)`
- `idx_dedup (id_endorse, status)`
- `idx_campaign_status (id_campaign, status)`
- `idx_worker (status, worker_id)`
- `idx_retry_source (retry_source_id)`

`endorse_refresh_queue_attempts` fields:

- `id`
- `queue_id`
- `attempt_no`
- `worker_id`
- `status`
- `error_class`
- `error_message`
- `started_at`
- `finished_at`
- `created_at`

Required indexes:

- `idx_queue_attempt (queue_id, attempt_no)`
- `idx_worker_status (worker_id, status)`

### Supporting indexes

The queue feature also depends on these performance indexes:

- `endorse_logs.idx_id_endorse_date (id_endorse, date)`
- `endorse.idx_campaign_status (id_campaign, status, status_campaign)`

### TikTok normalized columns on `endorse`

From [migrations/20260519000000_add_tiktok_media_columns_to_endorse.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/migrations/20260519000000_add_tiktok_media_columns_to_endorse.php:1):

- `tiktok_content_id`
- `tiktok_media_type`
- `tiktok_cover`
- `tiktok_content_link`
- `tiktok_fetched_at`

These are written during refresh when the platform is TikTok.

## External Dependencies

### Fetch layer

The refresh system depends on:

- [Template::get_social_media()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/Template.php:976)
- [Template::get_social_media_batch()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/Template.php:1050)

For TikTok detail fetches the code uses:

- RapidAPI as the default path
- direct TikTok page scrape only when `ENDORSE_TIKTOK_SCRAPE_ENABLED=1`

Required env vars:

- `RAPIDAPI_HOST`
- `RAPIDAPI_KEY`
- `ENDORSE_TIKTOK_SCRAPE_ENABLED` (`0` by default; opt-in legacy fallback)

Defaults currently assume:

- `RAPIDAPI_HOST=tiktok-video-no-watermark10.p.rapidapi.com`

## Operations

### Required cron

Use two enqueue cron entries to populate the queue for the morning and late-night refresh windows:

```cron
0 6 * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh-enqueue-all" >/dev/null 2>&1
0 23 * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh-enqueue-all" >/dev/null 2>&1
```

This route ignores `/endorse-campaign` page pagination and internal/external filters, and queues all active endorse rows with non-empty `link_upload`.

If `WORKER_SHARED_SECRET` or `WORKER_IP_ALLOWLIST` is configured, the enqueue cron must satisfy that guard.

Keep the worker cron running continuously so queued rows are actually processed:

```cron
* * * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 20; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 40; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
```

### Monitoring UI

Queue page:

- `/endorse/queue`

Header badge:

- [application/views/TemplateDashboard.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/TemplateDashboard.php:1293)
- polls `GET /endorse/queue-count`

Stalled warning logic:

- if pending exists
- and processing is zero
- and last activity is older than configured stale window

### Retry behavior

`Retry Gagal Terpilih` does not mutate the old failed row.

It creates a new queue row with:

- same `id_endorse`
- same campaign/platform/link
- `status = pending`
- `attempts = 0`
- `retry_source_id = original_failed_queue_id`

### Clear queue behavior

`Clear Semua Data` deletes:

- all rows in `endorse_refresh_queue`
- all rows in `endorse_refresh_queue_attempts`

Use this only as an operator action. It wipes audit history too.

## Clone Checklist

To port this feature into another project, copy or recreate these parts.

### Required backend pieces

- queue service equivalent to `EndorseRefreshQueueService`
- per-row sync writer equivalent to `Endorse_sync`
- fetch adapter equivalent to `Template::get_social_media()`
- batch worker endpoint equivalent to `Api_v2::cronjob_endorse_refresh()`
- enqueue entrypoints:
  - campaign refresh
  - bulk campaign-content refresh
  - failed-row retry
- queue monitoring endpoints:
  - queue page data
  - queue count
  - queue history

### Required schema

- `endorse_refresh_queue`
- `endorse_refresh_queue_attempts`
- indexes on `endorse` and `endorse_logs`
- optional but recommended TikTok normalized columns on `endorse`

### Required UI pieces

- refresh button on campaign listing
- refresh-all button on campaign detail
- queue page
- header badge linking to queue page

### Required ops pieces

- cron calling the worker continuously
- access to TikTok metric fetch dependency
- RapidAPI credentials if using the same fetch strategy

### Optional legacy parity pieces

Keep these only if the target project wants full parity with this repo:

- direct synchronous single-row `Refresh`
- `sync_all_process()` compatibility wrapper
- modal-based selected-row bulk action

If the target project only needs the queue architecture, these legacy surfaces can be omitted.

## Legacy Endpoints Still Present

The repo still contains older non-queue cronjobs:

- `GET /api/cronjob/endorse`
- `GET /api/cronjob/endorse-campaign`
- `GET /api/cronjob/endorse-sync-campaign`

They are separate from the modern queue worker and should not be confused with:

- `GET /api/cronjob/endorse-refresh-enqueue-all`
- `GET /api/cronjob/endorse-refresh`

For cloning the modern refresh button system:

- `endorse-refresh-enqueue-all` is the global scheduler entrypoint
- `endorse-refresh` is the queue worker route

## Known Caveats

- `Template::get_social_media_batch()` is currently implemented as a serial loop even though comments describe parallel `curl_multi`.
- Queue worker comments mention parallel HTTP concurrency, but the actual batch helper currently delegates to repeated `get_social_media()` calls.
- `queue_count()` comments say “last 24h”, but the implementation returns all active `pending + processing` rows via `computeHealth()`.
- `/endorse-campaign` `Refresh Semua` only acts on campaign cards currently rendered on the page.
- Single-row `Refresh` still bypasses the queue and runs synchronously.
- `translate_uri_dashes = TRUE` makes legacy dashed routes like `/endorse/sync-all-process` resolve to `sync_all_process()`.

## Related Docs

- [docs/ENDORSE_REFRESH_TRACE.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_TRACE.md:1)
- [docs/ENDORSE_REFRESH_QUEUE_RUNBOOK.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_QUEUE_RUNBOOK.md:1)
