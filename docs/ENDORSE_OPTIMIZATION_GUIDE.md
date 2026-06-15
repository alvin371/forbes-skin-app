# Endorse Content Optimization — Feature Guide

Automates the content-optimization workflow that used to live in a manual Google Sheet:
requestors/executors no longer type comments / likes / shares / saves / views before and
after optimization. The app captures a **frozen initial baseline**, a **frozen final
snapshot**, computes **growth automatically**, and **pushes everything to the team's Google
Sheet**.

Applies to: `/endorse-campaign` and `/endorse?id_campaign=<id>`.

---

## 1. What changed in the UI

### Create / Edit content form (`application/views/endorse/{create,edit}.php`)
A new **"Aktifkan Tracking Optimasi Konten"** checkbox reveals the optimization section:

| Field | Notes |
|---|---|
| Tanggal Request | `request_date` (falls back to created date if blank) |
| Request By | `request_by` (free text) |
| **Tools** | dropdown: Manual / Tools SMM.ID (was "Device") |
| Request Keyword | `request_keyword` |
| **Manual Status** | dropdown: Input / On Process / Done — the team's own status |
| **System Status** | dropdown: Not Started / In Progress / Completed — drives auto-fetch |
| Metrics | **read-only for TikTok** (auto-filled), **editable for other platforms** |

- **Platform auto-detect**: paste a content link → the Platform field is set automatically
  from the URL (TikTok / Instagram / YouTube / Twitter / Facebook).
- **Edit** shows the captured Initial / Final / Growth values per metric.

### Content list (`application/views/endorse/item.php` + filters in `all.php`)
- New columns: **Optimasi** (status) and **Growth** (view/like/comment).
- New filters: optimization status, request_by, device/Tools, photo (TikTok media type),
  request date, and an "Optimasi / Non-Optimasi" toggle.
- New buttons: **Export Optimasi (Excel)** and **Sync ke Sheet**.

---

## 2. How it works (end-to-end flow)

```
Requestor                         System (async)                 Executor
---------                         --------------                 --------
enter request date,
content link, request by,
Tools, keyword
        │
        │ link entered (TikTok) ──► enqueue INITIAL snapshot
        │                           (endorse_refresh_queue,
        │                            purpose=initial, priority 50)
        │                                   │
        │                           worker fetches metrics ──► freeze *_initial
        │                                                       + initial_fetched_at
        │                                                              │
        │                                                       ◄── executor sets PIC,
        │                                                            works the content,
        │                                                            System Status → Completed
        │                                                                   │
        │                           enqueue FINAL snapshot ◄────────────────┘
        │                           (purpose=final)
        │                                   │
        │                           worker fetches metrics ──► freeze *_final
        │                                                       + growth = final − initial
        ▼
   Sync ke Sheet (button)  ─or─  cron every 15 min  ──► AUTO_Optimasi tab (full replace)
```

### Metric capture timing
- **Initial baseline**: captured the moment a content link is first added to an
  optimization row (auto-fetch platforms only). Async, high priority — the form saves
  instantly and the baseline fills within seconds. Once set it is **frozen** (never
  overwritten).
- **Final snapshot**: captured when **System Status transitions to `Completed`**
  (transition-only — re-saving a Completed row does not overwrite the frozen final/growth).
- **Growth** = final − initial, computed and stored for all five metrics
  (`comment/like/share/save/view`). `share` and `save` are tracked **separately**.

### Platform support
- **TikTok** = real auto-fetch (live today).
- **Instagram / YouTube / Twitter / Facebook** = **placeholders**: identical data shape, but
  no auto-fetch yet — metrics are entered manually and growth is computed on save.
- Controlled by `application/helpers/social_platform_helper.php`
  (`is_auto_fetch_platform()`, `detect_platform_from_url()`). Flip a flag there when a new
  platform scraper is added.

---

## 3. Data model

### `endorse` table — new columns
- Initial (frozen): `comment_initial`, `like_initial`, `share_initial`, `save_initial`,
  `view_initial`, `initial_fetched_at`
- Final (frozen): `comment_final`, `like_final`, `share_final`, `save_final`, `view_final`,
  `final_fetched_at`
- Growth: `comment_growth`, `like_growth`, `share_growth`, `save_growth`, `view_growth`
- Behavioral: `request_date`, `request_by`, `device` (=Tools), `request_keyword`,
  `optimization_status` (System Status), `manual_status` (Manual Status), `is_optimization`

Note: these are a separate namespace from the existing **daily** metric columns
(`likes/views/share_save` + `endorse_logs`). The daily sync is untouched.

### `endorse_refresh_queue` — new column
- `purpose` = `daily` | `initial` | `final` (default `daily`). The worker branches on this;
  dedup is **purpose-scoped** so a daily, initial and final job for the same row never
  swallow each other.

Migrations: `migrations/20260615000000_*`, `_000100_*`, `_000200_*`
(run with `php migrations/run.php <file>`).

---

## 4. Async queue mechanics

- Snapshot jobs ride the **existing** worker `Api_v2::cronjob_endorse_refresh()`
  (`api/cronjob/endorse-refresh`). It branches: `daily` → `Endorse_sync::apply()` (delta +
  `endorse_logs`); `initial`/`final` → `Endorse_sync::apply_snapshot()` (frozen columns).
  Snapshot jobs do **not** touch campaign rollups.
- Enqueue: `EndorseRefreshQueueService::enqueueSnapshot()` (priority 50, jumps the daily
  backlog).
- **Reconcile sweep**: `Api_v2::cronjob_endorse_final_reconcile()`
  (`api/cronjob/endorse-final-reconcile`) re-enqueues `final` for any Completed row missing
  `final_fetched_at` — covers enqueues lost after a crash. Safe to run repeatedly.

---

## 5. Google Sheets sync

### Target
- Spreadsheet: **"Acneno - Database Optimasi Komen"** (`GOOGLE_SHEETS_SPREADSHEET_ID`).
- Writes an app-owned tab **`AUTO_Optimasi`** (`GOOGLE_SHEETS_TAB`). The team's `Database`
  and `Data` tabs are never touched.
- Each sync is a **full replace** (clear + write header + rows) — idempotent, no drift, no
  duplicates.

### When does it sync?
- **On demand**: the **Sync ke Sheet** button in the campaign view → pushes the currently
  **filtered** rows. Route `endorse/sync-optimization-sheet`.
- **Automatic**: cron `api/cronjob/endorse-optimization-sheet` (suggested **every 15 min**)
  → pushes **all** optimization rows.

### Column layout written to `AUTO_Optimasi`
Mirrors the team's `Database` tab, plus a System Status column; "Jumlah Komentar Optimasi"
is left blank (no app field yet):

`Tanggal · NO · PIC · Link Konten · Platform · REQUEST BY · Tools · Manual Status ·
System Status · Request Keyword · Jumlah Komentar Optimasi · Comment Sebelum/Sesudah · Growth
Comment · Views Sebelum/Sesudah · Growth Views · Like Sebelum/Sesudah · LikeViews · Save
Sebelum/Sesudah · Save Views · Share Sebelum/Sesudah · Share Views`

The same dataset backs the **xlsx export** — both come from
`EndorseOptimizationSheet::buildRows()` so they always match.

### Auth (service account, headless)
- Uses a **Google service account** (no browser/OAuth session — works in cron).
- The spreadsheet must be shared with the service account's `client_email`
  (`acnenosystem@hrms-acneno.iam.gserviceaccount.com`) as **Editor**.
- Library: `application/libraries/GoogleSheets.php` (`ensureTab`, `replaceTab`).

---

## 6. Configuration (`.env`)

```ini
# Provide the service-account key ONE of two ways (B64 wins if both set):
#  1) Inline base64 (recommended for the Docker/.env-only deploy):
#       base64 -i application/config/google-sheets-sa.json | tr -d '\n' | pbcopy
GOOGLE_SHEETS_CREDENTIALS_B64=
#  2) File path (place the JSON key on the server):
GOOGLE_SHEETS_CREDENTIALS_PATH=application/config/google-sheets-sa.json

GOOGLE_SHEETS_SPREADSHEET_ID=1qdCd6b2FqdjafZ83KC6WaJW-WMSI2Q-j2Q2_e-5F2fQ
GOOGLE_SHEETS_TAB=AUTO_Optimasi
```

> **Security**: the real key goes in `.env` (gitignored) or as a mounted file — **never** in
> `.env.example` or any committed file. `application/config/google-sheets-sa.json` is
> gitignored. The base64 loader tolerates a stray trailing `%`/whitespace from pastes.

### Connection test
```
php tools/gsheet_test.php
```
Prints the service-account email, the spreadsheet title, every tab, and each tab's header +
sample rows. Use it to confirm connectivity and that sharing is correct (403 → not shared as
Editor; 404 → wrong spreadsheet ID).

---

## 7. Cron schedule

| Endpoint | Suggested | Purpose |
|---|---|---|
| `api/cronjob/endorse-refresh` | existing (staggered ~20s) | worker — also processes initial/final snapshots |
| `api/cronjob/endorse-final-reconcile` | `*/5 * * * *` | backfill finals for Completed rows missing metrics |
| `api/cronjob/endorse-optimization-sheet` | `*/15 * * * *` | push all optimization rows to the sheet |

Example lines: `docs/cron/forbes-endorse-optimization.cron`.

---

## 8. Routes reference

| Route | Controller |
|---|---|
| `endorse/export-optimization` | `Endorse::export_optimization` (xlsx) |
| `endorse/sync-optimization-sheet` | `Endorse::sync_optimization_sheet` (button) |
| `api/cronjob/endorse-final-reconcile` | `Api_v2::cronjob_endorse_final_reconcile` |
| `api/cronjob/endorse-optimization-sheet` | `Api_v2::cronjob_endorse_optimization_sheet` |

---

## 9. Key files

| File | Role |
|---|---|
| `application/helpers/social_platform_helper.php` | platform detect + auto-fetch registry |
| `application/libraries/Endorse_sync.php` | `apply_snapshot()`, `compute_growth()` |
| `application/libraries/EndorseRefreshQueueService.php` | `enqueueSnapshot()`, reconcile, purpose dedup |
| `application/libraries/GoogleSheets.php` | service-account Sheets client (ensure/replace tab) |
| `application/libraries/EndorseOptimizationSheet.php` | row builder (sheet layout) + `sync()` |
| `application/controllers/Endorse.php` | form triggers, export, sync button |
| `application/controllers/Api_v2.php` | worker branch, reconcile + sheet crons |
| `application/views/endorse/{create,edit,item,all}.php` | UI |
| `tools/gsheet_test.php` | connection inspector |

---

## 10. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Sync: "not valid base64" | Stray char in `GOOGLE_SHEETS_CREDENTIALS_B64` (e.g. trailing `%`). Loader strips these now; regenerate with `… \| tr -d '\n' \| pbcopy`. |
| Sync: 403 "caller does not have permission" | Sheet not shared with the SA `client_email` as **Editor**, or wrong spreadsheet ID. |
| Sync: 404 | `GOOGLE_SHEETS_SPREADSHEET_ID` points to the wrong file. |
| Initial/final metrics never fill | Platform is a placeholder (only TikTok auto-fetches), or the worker cron isn't running, or the row has no content link. |
| Final never captured | System Status must **transition** into `Completed`; the reconcile cron backfills misses. |
| Metrics overwritten unexpectedly | They shouldn't be — initial is frozen on `initial_fetched_at`, final on the Completed transition. |
