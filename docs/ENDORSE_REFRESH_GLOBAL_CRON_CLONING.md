# Endorse Refresh Global Cron Cloning Guide

This document explains the global `/endorse-campaign` refresh cron that was added on top of the existing endorse refresh queue.

Use this when you want to clone the feature into another CodeIgniter project without depending on the browser-level `Refresh Semua` flow.

## Goal

Replace the UI-only `/endorse-campaign` bulk refresh behavior with a server-side scheduled enqueue flow that:

- runs every day at `06:00` and `23:00`
- covers both `internal` and `external` campaigns
- queues all eligible endorse content globally
- lets the existing async worker process the queue afterward
- updates both detail rows and campaign cards automatically through the existing rollup path

## What Was Added

### New route

- `GET /api/cronjob/endorse-refresh-enqueue-all`

Route mapping:

- [application/config/routes.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/config/routes.php:293)

### New controller entrypoint

- [Api_v2::cronjob_endorse_refresh_enqueue_all()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Api_v2.php:7686)

Behavior:

- returns JSON
- calls `worker_auth_guard()`
- loads `EndorseRefreshQueueService`
- enqueues all eligible active endorse rows using system user id `0`
- does not process rows itself
- does not contain time-gating logic; scheduling is delegated to server cron

### New queue service method

- [EndorseRefreshQueueService::enqueueAllActive()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/EndorseRefreshQueueService.php:72)

Behavior:

- selects all eligible endorse rows globally
- ignores `?p=internal` page filtering
- ignores page pagination and visible campaign cards
- reuses the same queue rules as `enqueueCampaign()`

### Queue helper refactor

Shared enqueue logic now lives in:

- [EndorseRefreshQueueService::enqueueRows()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/EndorseRefreshQueueService.php:278)
- [EndorseRefreshQueueService::buildEnqueueMessage()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/EndorseRefreshQueueService.php:337)

This was done so:

- campaign-level refresh and global cron refresh use the same dedupe rules
- known bad TikTok URL exclusions behave identically
- insert batching is centralized

## Eligibility Rules

The global cron only queues rows from `endorse` where:

- `status = 'Aktif'`
- `status_campaign = 'Aktif'`
- `link_upload != ''`

It does not queue:

- inactive endorse rows
- rows without upload URLs
- rows already in queue with `pending` or `processing`
- rows whose latest queue failure indicates a known bad URL / missing stats condition

Internal vs external coverage is achieved by joining `endorse_campaign` and intentionally not filtering by `is_internal`.

## Why This Covers Both Detail and Card Data

The global cron only enqueues.

The actual metric update still happens in the existing worker:

- [Api_v2::cronjob_endorse_refresh()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Api_v2.php:7709)

That worker:

1. claims pending queue rows
2. fetches social metrics
3. writes `endorse` and `endorse_logs`
4. rolls up touched campaigns through:
   - [Endorse_sync::update_campaign_parent()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/Endorse_sync.php:269)

Because campaign rollup already exists in the worker, the cron automatically refreshes:

- detail item metrics in `endorse`
- daily history in `endorse_logs`
- card totals in `endorse_campaign`
- campaign history in `endorse_campaign_logs`

## Required Cron Setup

### Enqueue cron

Add these two daily entries:

```cron
0 6 * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh-enqueue-all" >/dev/null 2>&1
0 23 * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh-enqueue-all" >/dev/null 2>&1
```

### Worker cron

Keep the worker running continuously:

```cron
* * * * * curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 20; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
* * * * * sleep 40; curl -s "https://YOUR_DOMAIN/api/cronjob/endorse-refresh" >/dev/null 2>&1
```

Important:

- the enqueue cron only creates queue rows
- without the worker cron, nothing will be processed

## Security Requirements

The new enqueue route uses the same worker guard pattern as other protected worker endpoints.

Guard source:

- [Api_v2::worker_auth_guard()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Api_v2.php:100)

If the target project enables either of these env vars:

- `WORKER_SHARED_SECRET`
- `WORKER_IP_ALLOWLIST`

then the cron caller must satisfy them.

## Throughput Note

The worker now reads `ENDORSE_REFRESH_BATCH_SIZE` from env, with a default of `10`.

Source:

- [Api_v2::cronjob_endorse_refresh()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Api_v2.php:7716)

Why:

- comments in the code previously implied parallel fetch throughput
- actual `Template::get_social_media_batch()` is still effectively serial in this repo
- using a safer default avoids over-claiming too many rows in one worker tick

If you clone this feature into another project, keep this in mind:

- if your batch fetch is still serial, stay conservative
- if your batch fetch is truly parallel, you can raise the batch size

## Queue Response Shape

The new enqueue-all route returns JSON shaped like:

```json
{
  "status": true,
  "msg": "120 konten ditambahkan ke antrian. 15 sudah ada di antrian. 3 dilewati karena URL TikTok bermasalah.",
  "campaign_count": 24,
  "candidate_count": 138,
  "enqueued": 120,
  "skipped_duplicates": 15,
  "excluded_known_url": 3
}
```

`campaign_count` is the number of distinct campaigns touched by the eligible candidate set.

## Minimal Pieces To Copy Into Another Project

If another project already has the same queue tables and worker behavior, you need only:

1. the new route
2. the new controller method
3. the new queue service method
4. the shared enqueue helper refactor
5. the enqueue cron entries

If another project does not already have the queue architecture, you also need:

- `endorse_refresh_queue`
- `endorse_refresh_queue_attempts`
- the queue monitoring pages/endpoints
- `Api_v2::cronjob_endorse_refresh()`
- `Endorse_sync`
- the fetch layer used by `Template::get_social_media()` and `get_social_media_batch()`

## Clone Checklist

1. Confirm the target project already has the endorse refresh queue architecture.
2. Copy the new route and controller method.
3. Copy the queue service global enqueue method and shared helper methods.
4. Verify the target project uses the same eligibility rules.
5. Verify `endorse_campaign` has internal/external data if you want the same global coverage.
6. Add the two enqueue cron entries.
7. Confirm the continuous worker cron already exists.
8. Verify `RAPIDAPI_HOST` and `RAPIDAPI_KEY`.
9. If needed, set `ENDORSE_REFRESH_BATCH_SIZE`.
10. Trigger the enqueue route once manually and confirm `/endorse/queue` starts draining.

## Recommended Verification After Clone

1. Call `/api/cronjob/endorse-refresh-enqueue-all` manually.
2. Confirm the response returns non-zero `enqueued` when eligible rows exist.
3. Open `/endorse/queue` and confirm rows move from `pending` to `processing` to `completed`.
4. Open one campaign detail page and confirm endorse metrics changed.
5. Open `/endorse-campaign` and confirm campaign card totals changed.
6. Re-run the enqueue route immediately and confirm duplicates are skipped.

## Related Docs

- [docs/ENDORSE_REFRESH_GUIDE.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_GUIDE.md:1)
- [docs/ENDORSE_REFRESH_QUEUE_RUNBOOK.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_QUEUE_RUNBOOK.md:1)
- [docs/ENDORSE_REFRESH_TRACE.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_TRACE.md:1)
