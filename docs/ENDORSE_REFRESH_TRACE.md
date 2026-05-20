# Endorse Refresh Trace

Canonical guide:

- [docs/ENDORSE_REFRESH_GUIDE.md](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/docs/ENDORSE_REFRESH_GUIDE.md:1)

This document traces the refresh buttons related to endorse campaign and endorse content pages, and shows where the new TikTok integration is involved.

Use this file as a code-path appendix.
Use the guide above for cloning requirements, route contracts, schema, and operations.

## Summary

The TikTok changes are directly related to endorse refresh.

They affect the step where the app fetches TikTok metrics and media during refresh:

- single-content refresh on `/endorse?id_campaign=...`
- queued bulk refresh triggered from `/endorse-campaign`
- queued bulk refresh triggered from `/endorse?id_campaign=...`

They do not change how buttons are rendered or how rows are selected. They change the fetch layer used after a refresh request is submitted:

- `Template::get_social_media()`
- `Template::get_social_media_batch()`
- `Endorse_sync::apply()`

## Refresh Buttons

### 1. `/endorse-campaign` card button: `Refresh`

Visible in:

- [application/views/endorse_campaign/item.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse_campaign/item.php:67)

JS handler:

- [application/views/endorse_campaign/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse_campaign/all.php:165)

Flow:

1. Click `Refresh` on one campaign card.
2. Browser calls `GET /ajax/refresh-campaign-endorses?id_campaign={campaign_id}`.
3. [Ajax::refresh_campaign_endorses()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Ajax.php:7556) calls `EndorseRefreshQueueService::enqueueCampaign(...)`.
4. Queue rows are inserted into `endorse_refresh_queue`.
5. Later, [Api_v2::cronjob_endorse_refresh()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Api_v2.php:7578) claims those rows.
6. Worker fetches TikTok stats through `Template::get_social_media_batch()`.
7. Each result is written by `Endorse_sync::apply()`.

Relation to TikTok change:

- Yes, directly related.
- This button uses the queue path, and the queue path now uses the new TikTok integration.

### 2. `/endorse-campaign` page button: `Refresh Semua`

Visible in:

- [application/views/endorse_campaign/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse_campaign/all.php:71)

JS handler:

- [application/views/endorse_campaign/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse_campaign/all.php:183)

Flow:

1. Click `Refresh Semua`.
2. Frontend loops all campaign refresh buttons currently shown on the page.
3. For each campaign, it calls `GET /ajax/refresh-campaign-endorses?id_campaign={campaign_id}`.
4. Each campaign is enqueued through `EndorseRefreshQueueService::enqueueCampaign(...)`.
5. Queue worker later processes all queued endorse rows through `Api_v2::cronjob_endorse_refresh()`.

Relation to TikTok change:

- Yes, directly related.
- It is the same queue-based refresh pipeline as the single campaign refresh.

### 3. `/endorse?id_campaign=...` page button: `Refresh Semua`

Visible in:

- [application/views/endorse/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/all.php:1217)

JS handler:

- [application/views/endorse/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/all.php:1975)
- duplicated fallback version at [application/views/endorse/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/all.php:2675)

Flow:

1. Click `Refresh Semua` inside one campaign’s endorse list.
2. Browser calls `POST /endorse/bulk-refresh` with `id_campaign`.
3. [Endorse::bulk_refresh()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Endorse.php:1803) calls `EndorseRefreshQueueService::enqueueCampaign(...)`.
4. Only active endorse rows in that campaign with non-empty `link_upload` are queued.
5. Queue worker later runs `Api_v2::cronjob_endorse_refresh()`.
6. Worker calls `Template::get_social_media_batch()` and persists results via `Endorse_sync::apply()`.

Relation to TikTok change:

- Yes, directly related.
- This is one of the main entrypoints affected by the new TikTok fetch logic.

### 4. `/endorse?id_campaign=...` bulk action menu: `Refresh Data`

Visible in:

- [application/views/endorse/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/all.php:1691)

JS handler:

- [application/views/endorse/all.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/all.php:1967)

Modal:

- [application/views/endorse/action.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/action.php:1)

Controller flow:

- [Endorse::action()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Endorse.php:2692)
- [Endorse::action_process()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Endorse.php:2726)

Flow:

1. User selects specific endorse rows.
2. Click `Aksi > Refresh Data`.
3. Modal posts to `POST /endorse/action-process`.
4. `action_process()` translates selected IDs into `bulk_refresh()` input.
5. `bulk_refresh()` calls `EndorseRefreshQueueService::enqueueCampaign($id_campaign, $user_id, $ids)`.
6. Queue worker later processes only the selected endorse rows.

Relation to TikTok change:

- Yes, directly related.
- Same queue-based refresh, but scoped to selected rows.

### 5. `/endorse?id_campaign=...` row button: `Refresh`

Visible in:

- card view: [application/views/endorse/item.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/item.php:801)
- table dropdown: [application/views/endorse/item.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/item.php:478)

Modal:

- [application/views/endorse/sync.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/views/endorse/sync.php:1)

Controller flow:

- [Endorse::sync_process()](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Endorse.php:1748)

Flow:

1. Click `Refresh` on a single endorse row.
2. Modal posts to `POST /endorse/sync-process`.
3. `sync_process()` loads that one `endorse` row.
4. It calls `Template::get_social_media($platform, $link_upload)`.
5. Result is applied immediately through `Endorse_sync::apply(...)`.
6. Campaign rollup is updated immediately through `Endorse_sync::update_campaign_parent(...)`.

Relation to TikTok change:

- Yes, directly related.
- This is the direct sync path, not the queue path.
- It now uses the new TikTok detail flow immediately in the request.

## Queue and Worker Layer

### Queue insertion rules

Queue insertion happens in:

- [application/libraries/EndorseRefreshQueueService.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/EndorseRefreshQueueService.php:17)

Important behavior:

- only `endorse` rows with `status = 'Aktif'`
- only rows with `status_campaign = 'Aktif'`
- only rows with `link_upload != ''`
- skips rows already in `pending` or `processing`
- skips known bad TikTok URL rows

### Queue worker

Queue processing happens in:

- [application/controllers/Api_v2.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/controllers/Api_v2.php:7578)

Important behavior:

- claims up to 30 queue rows
- builds HTTP tasks from `platform` and `link_upload`
- calls `Template::get_social_media_batch(...)`
- writes stats through `Endorse_sync::apply(...)`
- updates queue row status and campaign rollup

## Where the TikTok Change Enters

The new TikTok integration changed these runtime methods:

- [application/libraries/Template.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/Template.php:973) `get_social_media()`
- [application/libraries/Template.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/Template.php:1049) `get_social_media_batch()`
- [application/libraries/Template.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/Template.php:908) `get_post_list()`
- [application/libraries/Template.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/Template.php:756) `syncTiktokProfile()`
- [application/libraries/Endorse_sync.php](/Users/alvin/Documents/WorkingSpace/acneno-hrms/htdocs/forbes-skin-app/application/libraries/Endorse_sync.php:64) `apply()`

For endorse refresh specifically:

- queue refresh buttons eventually hit `get_social_media_batch()`
- single-row refresh hits `get_social_media()`
- both now use the new TikTok direct-scrape + RapidAPI fallback logic
- both now persist normalized TikTok fields into `endorse` when available

## Practical Answer

If your question is:

`Is the TikTok API change connected to Refresh on /endorse-campaign and /endorse?id_campaign=?`

Answer:

- Yes.
- `/endorse-campaign` refresh buttons enqueue endorse rows into the refresh queue.
- `/endorse?id_campaign=` refresh-all and bulk refresh actions also enqueue rows into the same queue.
- `/endorse?id_campaign=` per-row refresh bypasses the queue and refreshes immediately.
- All of those refresh paths now use the updated TikTok integration at the fetch step.

If a refresh now behaves differently for TikTok content, the most likely affected points are:

- `Template::get_social_media()`
- `Template::get_social_media_batch()`
- `Endorse_sync::apply()`
- the new `endorse.tiktok_*` persistence fields
