# Endorse Campaign Refresh Connections

This document explains how `/endorse-campaign`, `/endorse`, `/endorse/detail`, queue refresh, and endorse charts are connected in the current app.

## Main Screens

- `/endorse-campaign`
  - Lists campaign cards from `endorse_campaign`
  - Links each card to `/endorse?id_campaign={id}`
  - Campaign-level `Refresh` only enqueues child endorse rows

- `/endorse?id_campaign={id}`
  - Lists endorse rows from `endorse`
  - `Refresh Semua` enqueues eligible child rows for that campaign
  - single-row `Refresh` runs synchronous per-row sync

- `/endorse/detail?id={id}`
  - Shows one endorse row from `endorse`
  - Chart reads historical values from `endorse_logs`

- `/endorse/queue`
  - Shows queue state from `endorse_refresh_queue`
  - Used to monitor whether a campaign refresh has actually finished

## Table-Level Relationships

- `endorse_campaign`
  - parent campaign record
  - stores aggregate counters and rollup totals
  - `updated_at` reflects parent aggregate writes, not necessarily “all children are freshly synced”

- `endorse`
  - child content rows under one campaign
  - `id_campaign` points to `endorse_campaign.id`
  - `sync_at` is the per-row freshness timestamp shown on endorse list/detail views

- `endorse_logs`
  - historical metric snapshots per endorse row
  - `id_endorse` points to `endorse.id`
  - chart endpoints on endorse detail read from this table

- `endorse_campaign_logs`
  - daily campaign rollup snapshots
  - derived from `endorse` and `endorse_logs`

- `endorse_refresh_queue`
  - async refresh work items for child endorse rows
  - `id_campaign` groups queue rows by campaign
  - `id_endorse` points to the child row being refreshed

## Refresh Entry Points

- `/endorse-campaign` card `Refresh`
  - `GET /ajax/refresh-campaign-endorses?id_campaign={id}`
  - controller enqueues eligible child rows into `endorse_refresh_queue`

- `/endorse-campaign` page `Refresh Semua`
  - browser loops visible campaign cards
  - calls the same enqueue route once per visible campaign

- `/endorse?id_campaign={id}` `Refresh Semua`
  - `POST /endorse/bulk-refresh`
  - enqueues all eligible child rows in that campaign

- `/endorse?id_campaign={id}` row `Refresh`
  - `POST /endorse/sync-process`
  - directly fetches the social metrics for one row
  - updates `endorse`, `endorse_logs`, then campaign rollup

## What Writes Which Timestamp

- `endorse.sync_at`
  - written by `Endorse_sync::apply()`
  - means the child row itself has been refreshed

- `endorse.updated_at`
  - also written during per-row sync
  - generic row update timestamp

- `endorse_campaign.updated_at`
  - written by campaign rollup updates
  - means parent aggregate data was recalculated
  - does not guarantee every queued child row is complete

- `endorse_refresh_queue.created_at`
  - when refresh work was queued

- `endorse_refresh_queue.completed_at`
  - when one queued child row finished

## Why Campaign and Child Freshness Can Diverge

- Campaign refresh is async for bulk paths.
- A campaign can have:
  - some child rows already completed
  - some child rows still pending
  - a fresh parent aggregate write from partial progress
- Because of that, campaign `updated_at` can move before every child `sync_at` has caught up.

## Current Freshness Semantics

- Campaign card UI should be read like this:
  - if queue rows are still `pending` or `processing`, the campaign is still refreshing
  - if no active queue rows remain, the effective child freshness is `MAX(endorse.sync_at)` for that campaign

- Endorse list/detail freshness should be read like this:
  - `Tanggal Diupdate` comes from `endorse.sync_at`
  - this is the source of truth for one content row

## Chart Data Flow

- `/endorse/detail?id={id}` loads chart data from:
  - `GET /ajax/get-chart-endorse?id={id}&...`
- The chart endpoint reads `endorse_logs`
- Period logic:
  - daily/weekly/monthly/yearly grouping
  - diff metrics use `SUM(...)`
  - cumulative metrics use `MAX(..._after)`

## Operational Notes

- Queue-backed refresh needs the worker cron on `/api/cronjob/endorse-refresh`
- If bulk refresh looks stale:
  - check `/endorse/queue`
  - verify rows are not stuck in `pending`
  - compare child freshness with `endorse.sync_at`, not only `endorse_campaign.updated_at`
