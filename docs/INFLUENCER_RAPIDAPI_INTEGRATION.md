# Influencer & Endorse Campaign — RapidAPI Integration

Complete technical documentation of how TikTok profile and post data flows from the RapidAPI TikTok Scraper into every metric displayed in the `/influencer`, `/influencer-dummy`, and `/endorse-campaign` routes.

---

## Table of Contents

1. [Environment Setup](#1-environment-setup)
2. [RapidAPI Endpoints](#2-rapidapi-endpoints)
3. [Template Library Methods (Integration Layer)](#3-template-library-methods-integration-layer)
4. [Data Flow: /influencer Route](#4-data-flow-influencer-route)
5. [Data Flow: /influencer-dummy Route](#5-data-flow-influencer-dummy-route)
6. [Data Flow: /endorse-campaign Route](#6-data-flow-endorse-campaign-route)
7. [Cronjob Data Flow](#7-cronjob-data-flow)
8. [Complete Metric Reference Table](#8-complete-metric-reference-table)
9. [Database Schema](#9-database-schema)
10. [FYP Detection Logic](#10-fyp-detection-logic)

---

## 1. Environment Setup

### RapidAPI Credentials

Defined in `.env` (see `.env.example` lines 76-79):

```env
RAPIDAPI_HOST=tiktok-scraper-api4.p.rapidapi.com
RAPIDAPI_KEY=your_rapidapi_key
```

### How Credentials Are Loaded

The `env()` helper (`application/helpers/env_helper.php`) reads the `.env` file once (using a static cache), parses `KEY=VALUE` pairs, strips quotes, and returns the value:

```php
$rapidapi_host = env('RAPIDAPI_HOST', 'tiktok-scraper-api4.p.rapidapi.com');
$rapidapi_key  = env('RAPIDAPI_KEY', '');
```

Every RapidAPI call in the Template library uses these two values to build the URL and headers.

---

## 2. RapidAPI Endpoints

Four RapidAPI endpoints are used, all on the same host (`tiktok-scraper-api4.p.rapidapi.com`):

| # | Endpoint | Method | Purpose | Called From |
|---|----------|--------|---------|-------------|
| 1 | `/api/v1/user/info?unique_id={username}` | GET | Get TikTok user profile (primary) | `Template.php:324-336` |
| 2 | `/api/v1/search/users?keyword={username}` | GET | Fallback user search when #1 returns empty | `Template.php:338-350` |
| 3 | `/api/v1/user/posts?sec_uid={account_id}&count=10` | GET | Get last 10 posts for a user | `Template.php:564-615` |
| 4 | `/api/v1/post/info?video_id={content_id}` | GET | Get single post metrics (fallback for photo/slideshow posts) | `Template.php:674-711` |

### Common Request Headers

All endpoints use:
```
X-RapidAPI-Host: {RAPIDAPI_HOST}
X-RapidAPI-Key: {RAPIDAPI_KEY}
```

### 2.1 Endpoint #1 — User Info

**URL:** `https://{host}/api/v1/user/info?unique_id={username}`

**Response structure (relevant fields):**
```json
{
  "status": "Successful",
  "data": [
    {
      "uid": "7123456789",
      "follower_count": 150000,
      "aweme_count": 245,
      "nickname": "Creator Name",
      "avatar_larger": {
        "url_list": ["https://...avatar.jpeg"]
      }
    }
  ]
}
```

**Fields extracted:**
| Response Field | Mapped To | Description |
|---|---|---|
| `data[0].uid` | `account_id` | TikTok user unique ID |
| `data[0].follower_count` | `follower` | Follower count |
| `data[0].aweme_count` | `media_count` | Number of visible videos |
| `data[0].avatar_larger.url_list[0]` | `img` | Profile picture URL |
| `data[0].nickname` | `full_name` | Display name |

### 2.2 Endpoint #2 — User Search (Fallback)

**URL:** `https://{host}/api/v1/search/users?keyword={username}`

Used only when Endpoint #1 returns empty data (no `uid`).

**Response structure (relevant fields):**
```json
{
  "status": "Successful",
  "data": [
    {
      "user_info": {
        "sec_uid": "MS4wLjABAAAA...",
        "follower_count": 150000,
        "video_count": 245,
        "nickname": "Creator Name",
        "unique_id": "username",
        "avatar_thumb": {
          "url_list": ["https://...avatar.jpeg"]
        }
      }
    }
  ]
}
```

**Fields extracted:**
| Response Field | Mapped To | Description |
|---|---|---|
| `data[0].user_info.sec_uid` | `account_id` | TikTok sec_uid (used for posts endpoint) |
| `data[0].user_info.follower_count` | `follower` | Follower count |
| `data[0].user_info.video_count` | `media_count` | Number of visible videos |
| `data[0].user_info.avatar_thumb.url_list[0]` | `img` | Profile picture URL |
| `data[0].user_info.nickname` | `full_name` | Display name |

### 2.3 Endpoint #3 — User Posts

**URL:** `https://{host}/api/v1/user/posts?sec_uid={account_id}&count=10`

**Response structure (relevant fields):**
```json
{
  "status": "Successful",
  "data": [
    {
      "stats": {
        "diggCount": 5200,
        "shareCount": 120,
        "commentCount": 340,
        "collectCount": 890,
        "playCount": 125000
      }
    }
  ]
}
```

**Fields extracted per post (up to 10 posts):**
| Response Field | Mapped To | Description |
|---|---|---|
| `data[i].stats.diggCount` | `like` | Number of likes |
| `data[i].stats.shareCount` | `share` | Number of shares |
| `data[i].stats.commentCount` | `comment` | Number of comments |
| `data[i].stats.collectCount` | `collect` | Number of saves/bookmarks |
| `data[i].stats.playCount` | `view` | Number of views |

### 2.4 Endpoint #4 — Single Post Info

**URL:** `https://{host}/api/v1/post/info?video_id={content_id}`

Used as a fallback in `get_social_media()` when HTML scraping returns `playCount = 0` (typically for photo/slideshow posts).

**Response structure (relevant fields):**
```json
{
  "data": {
    "createTime": 1700000000,
    "stats": {
      "diggCount": 5200,
      "shareCount": 120,
      "commentCount": 340,
      "collectCount": 890,
      "playCount": 125000
    }
  }
}
```

**Fields extracted:**
| Response Field | Mapped To | Description |
|---|---|---|
| `data.stats.diggCount` | `like` | Number of likes |
| `data.stats.shareCount` | `share` | Number of shares |
| `data.stats.commentCount` | `comment` | Number of comments |
| `data.stats.collectCount` | `collect` | Number of saves |
| `data.stats.playCount` | `view` | Number of views |
| `data.createTime` | `created_at` | Unix timestamp → formatted as `Y-m-d` |

---

## 3. Template Library Methods (Integration Layer)

All RapidAPI integration logic lives in `application/libraries/Template.php`.

### 3.1 `curlRequest($url, $headers)` — Lines 295-322

Generic cURL GET wrapper used by `getDataFromFirstEndpoint()` and `getDataFromSecondEndpoint()`.

```
Input:  URL string, Headers array
Output: Decoded JSON array, or error array on cURL failure
```

### 3.2 `getDataFromFirstEndpoint($username)` — Lines 324-336

Calls Endpoint #1 (`/api/v1/user/info`). Builds URL and headers from `env()`, delegates to `curlRequest()`.

### 3.3 `getDataFromSecondEndpoint($username)` — Lines 338-350

Calls Endpoint #2 (`/api/v1/search/users`). Same pattern as above.

### 3.4 `get_account_id($type, $url)` — Lines 352-507

**Master account resolver.** Extracts the TikTok username from the profile URL, then:

1. Strips `@` from username
2. Calls `getDataFromFirstEndpoint($username)` (Endpoint #1)
3. If Endpoint #1 returns data with a valid `uid` → extracts `account_id`, `follower`, `media_count`, `img`, `full_name` and returns
4. If Endpoint #1 is empty → calls `getDataFromSecondEndpoint($username)` (Endpoint #2)
5. If Endpoint #2 returns data → extracts same fields (using `sec_uid` as `account_id`) and returns
6. If both fail → returns error

**Return structure:**
```php
[
    "status" => true,
    "msg"    => "Data ditemukan",
    "data"   => [
        "account_id"  => "7123456789",    // uid or sec_uid
        "follower"    => 150000,
        "media_count" => 245,
        "img"         => "https://...",
        "full_name"   => "Creator Name"
    ]
]
```

### 3.5 `get_post_list($type, $account_id)` — Lines 510-623

Fetches up to 10 recent posts for a TikTok user.

1. Calls Endpoint #3 (`/api/v1/user/posts?sec_uid={account_id}&count=10`)
2. Slices response to max 10 posts
3. Maps each post's `stats` object to a normalized array

**Return structure:**
```php
[
    "status" => true,
    "msg"    => "Data ditemukan",
    "data"   => [
        ["like" => 5200, "share" => 120, "comment" => 340, "collect" => 890, "view" => 125000],
        ["like" => 3100, "share" => 80,  "comment" => 210, "collect" => 540, "view" => 98000],
        // ... up to 10 posts
    ]
]
```

### 3.6 `get_social_media($type, $url)` — Lines 625-806

Fetches metrics for a **single post** URL. Used by endorse (individual endorsement) sync.

**Strategy (two-phase):**

1. **Phase 1 — HTML Scraping:** cURL GETs the TikTok post URL directly, parses the `<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__">` JSON from the page HTML, and extracts `stats` from `__DEFAULT_SCOPE__.webapp.video-detail.itemInfo.itemStruct`.

2. **Phase 2 — RapidAPI Fallback:** If HTML scraping returns `playCount = 0` (common for photo/slideshow posts), extracts the `content_id` from the URL and calls Endpoint #4 (`/api/v1/post/info?video_id={content_id}`).

**Return structure:**
```php
[
    "status" => true,
    "msg"    => "",
    "data"   => [
        "like"       => 5200,
        "share"      => 120,
        "comment"    => 340,
        "collect"    => 890,
        "view"       => 125000,
        "created_at" => "2024-03-15"
    ]
]
```

---

## 4. Data Flow: /influencer Route

**Controller:** `application/controllers/Influencer.php`

### 4.1 Manual Sync — `sync_process()` (Lines 672-827)

Triggered when a user clicks "Refresh" on a single influencer record.

#### Step-by-Step Flow:

```
User clicks "Refresh" on influencer #123
        │
        ▼
┌─────────────────────────────────────────────┐
│ PHASE 1: Aggregate Endorsement History      │
│ (Lines 688-716)                             │
│                                             │
│ SQL: SELECT COUNT(id) as frequency,         │
│      SUM(total_cost), SUM(views),           │
│      AVG(views), AVG(likes+comment+         │
│      share_save), SUM(likes),               │
│      SUM(share_save), SUM(comment)          │
│ FROM endorse WHERE influencer = '123'       │
│ AND link_upload != ''                       │
│                                             │
│ Calculates:                                 │
│   • frequency  = COUNT of endorsements      │
│   • total_cost = SUM of all costs           │
│   • cpm = total_cost / views * 1000         │
│   • avg_view (from endorse data)            │
│   • avg_interaksi (from endorse data)       │
│                                             │
│ Writes → influencer table (1st update)      │
└─────────────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────────────┐
│ PHASE 2: Get Account Info via RapidAPI      │
│ (Lines 720-734)                             │
│                                             │
│ Calls: template->get_account_id('Tiktok',   │
│        'https://tiktok.com/@username')      │
│                                             │
│ Returns: account_id, follower, media_count, │
│          img, full_name                     │
│                                             │
│ Writes → influencer table (2nd update)      │
└─────────────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────────────┐
│ PHASE 3: Get Post List via RapidAPI         │
│ (Lines 736-744)                             │
│                                             │
│ Calls: template->get_post_list('Tiktok',    │
│        account_id)                          │
│                                             │
│ Returns: Array of up to 10 posts, each with │
│   { like, share, comment, collect, view }   │
└─────────────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────────────┐
│ PHASE 4: Calculate Engagement Metrics       │
│ (Lines 750-818)                             │
│                                             │
│ Sum totals across all posts (max 10):       │
│   total_like    = Σ post.like               │
│   total_comment = Σ post.comment            │
│   total_collect = Σ post.collect            │
│   total_share   = Σ post.share              │
│   total_view    = Σ post.view               │
│   i = number of posts processed             │
│                                             │
│ Calculate derived metrics:                  │
│   avg_view       = total_view / i           │
│   avg_interaksi  = (like+comment+collect+   │
│                     share) / i              │
│   er             = avg_interaksi /          │
│                     avg_view * 100          │
│                                             │
│ Writes → influencer_logs table              │
│   (insert or update for today's date)       │
│                                             │
│ Then builds dt_2 for "external" metrics:    │
│   frequency_2      = i (post count)         │
│   view_2           = total_view             │
│   like_2           = total_like             │
│   collect_2        = total_collect          │
│   share_2          = total_share            │
│   comment_2        = total_comment          │
│   avg_view_2       = total_view / i         │
│   avg_interaksi_2  = (like+comment+collect+ │
│                       share) / i            │
│   cpm_2            = ratecard / avg_view_2  │
│                       * 1000                │
│                                             │
│ Writes → influencer table (3rd update)      │
└─────────────────────────────────────────────┘
```

### 4.2 Bulk Sync — `sync_all_process()` (Lines 428-660)

Same logic as `sync_process()` but loops through all matching active influencers based on current filter criteria. Each influencer goes through the same 4 phases.

### 4.3 External Sync — `sync_external_process()` (Lines 997-1157)

API endpoint (JSON response) that syncs all active influencers where `avg_interaksi_2 = 0`. Same 4-phase logic.

---

## 5. Data Flow: /influencer-dummy Route

**Controller:** `application/controllers/Influencer_dummy.php`

The "dummy" influencer is a listing/prospecting table. Records can later be "generated" (copied) into the main `influencer` table.

### 5.1 Save with Auto-Sync — `save()` (Lines 89-162)

When saving a new influencer dummy record with `auto_fetch = true` and a URL provided:

1. Inserts/updates the record in `influencer_dummy`
2. Extracts username from URL via regex (`/@([a-zA-Z0-9_.]+)/`)
3. Calls `_sync_engagement_data($id)` if auto-fetch is enabled

### 5.2 `_sync_engagement_data($id)` — Lines 171-277

Private method that performs the actual sync:

```
┌─────────────────────────────────────────────┐
│ STEP 1: Load record from influencer_dummy   │
│ Get: url, type, ratecard                    │
└────────────────────┬────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────┐
│ STEP 2: Get Account Info                    │
│ Calls: template->get_account_id(type, url)  │
│                                             │
│ Writes to influencer_dummy:                 │
│   account_id, img, follower, media_count,   │
│   full_name                                 │
└────────────────────┬────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────┐
│ STEP 3: Get Post List                       │
│ Calls: template->get_post_list(type,        │
│        account_id)                          │
│                                             │
│ Sum across up to 10 posts:                  │
│   like, comment, collect, share, view       │
└────────────────────┬────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────┐
│ STEP 4: Calculate & Store                   │
│                                             │
│ avg_view_2      = view / i                  │
│ avg_interaksi_2 = (like+comment+collect+    │
│                   share) / i                │
│ er              = avg_interaksi / avg_view  │
│                   * 100                     │
│ cpm_2           = ratecard / avg_view_2     │
│                   * 1000                    │
│                                             │
│ Writes to influencer_dummy:                 │
│   sync_at, frequency_2, view_2, like_2,     │
│   collect_2, share_2, comment_2,            │
│   avg_view_2, avg_interaksi_2, er, cpm_2    │
└─────────────────────────────────────────────┘
```

### 5.3 `sync_external_process()` — Lines 436-549

Public endpoint (POST, expects `id` in body). Same logic as `_sync_engagement_data()` but with direct JSON response output.

### 5.4 `refresh_data($list_id)` — Lines 551-639

Bulk refresh for multiple IDs. Same calculation logic applied to each record.

### 5.5 Generate (Copy to Influencer) — `generate($id)` — Lines 349-382

Copies a record from `influencer_dummy` to `influencer` table (including all synced metrics), sets `is_generated = 1` on the dummy record.

---

## 6. Data Flow: /endorse-campaign Route

**Controller:** `application/controllers/Endorse_campaign.php`

### 6.1 Metrics Displayed in Campaign List

**View:** `application/views/endorse_campaign/item.php` (Lines 81-85)

```html
Likes      : <?= separator_only($v['likes']) ?>
Comments   : <?= separator_only($v['comment']) ?>
Save & Share: <?= separator_only($v['share_save']) ?>
Views      : <?= separator_only($v['views']) ?>
CPM        : <?= separator_only($v['cpm']) ?>
```

All five values come from the `endorse_campaign` table columns, populated by `update_endorse_parent()`.

### 6.2 `update_endorse_parent($id_parent, $detail)` — Lines 301-487

This method aggregates metrics from all endorsements within a campaign and writes them back to `endorse_campaign`.

#### Data Sources:

**Source 1 — Endorse table aggregation (Lines 319-323):**
```sql
SELECT SUM(total_cost) as total_cost,
       COUNT(id) as count_endorse,
       SUM(likes) as likes,
       SUM(comment) as comment,
       SUM(share_save) as share_save,
       SUM(views) as views,
       AVG(cpm) as cpm
FROM endorse
WHERE id_campaign = '{id_parent}'
  AND link_upload != ''
  AND status = 'Aktif'
```

**Source 2 — Endorse logs aggregation (Lines 326-330):**
```sql
SELECT SUM(likes) as likes, SUM(comment) as comment,
       SUM(share_save) as share_save, SUM(views) as views, AVG(cpm) as cpm,
       SUM(likes_after), SUM(comment_after), SUM(share_save_after),
       SUM(views_after), AVG(cpm_after),
       SUM(likes_before), SUM(comment_before), SUM(share_save_before),
       SUM(views_before), AVG(cpm_before)
FROM endorse_logs
WHERE id_campaign = '{id_parent}'
```

**CPM Recalculation (Lines 446-455):**
```sql
SELECT SUM(total_cost) as total_cost,
       SUM(views) as views,
       SUM(likes) as likes,
       SUM(share_save) as share_save,
       SUM(comment) as comment
FROM endorse
WHERE id_campaign = '{id_campaign}'
```

```php
$cpm = $total_cost / $views * 1000;
```

**Additional counts written to `endorse_campaign`:**
- `count_endorse` — Total endorsements in campaign
- `count_endorse_active` — Active endorsements
- `count_endorse_processed` — Active endorsements with `link_upload`
- `count_influencer` — Distinct influencers
- `count_influencer_active` — Distinct active influencers
- `count_influencer_processed` — Distinct active influencers with `link_upload`

**Data also written to `endorse_campaign_logs`** with before/after/delta tracking for all counts.

---

## 7. Cronjob Data Flow

### 7.1 `cronjob_influencer()` — `Api_v2.php:4620-4797`

**Route:** `GET /api/cronjob/influencer`
**Schedule:** Runs after 11:00 AM Jakarta time (or force with `?mode=true`)
**Batch size:** 10 records per run

**Selection criteria:**
```sql
SELECT * FROM influencer
WHERE status = 'Aktif'
  AND DATE(sync_at) <= '{7_days_ago}'
  OR DATE(sync_at) IS NULL
  AND url != ''
LIMIT 10
```

**Flow:** Same 4-phase flow as `Influencer::sync_process()`:
1. Aggregate endorsement history → write to `influencer`
2. `get_account_id()` → write follower, img, account_id, media_count to `influencer`
3. `get_post_list()` → get up to 10 posts
4. Calculate engagement metrics → write to `influencer_logs` and `influencer` (external metrics)

**Note:** The cronjob divides by hardcoded `10` for `avg_view` and `avg_interaksi` (line 4737-4740), while the manual sync divides by `$i` (actual post count).

### 7.2 `cronjob_influencer_dummy()` — `Api_v2.php:4799-4919`

**Route:** `GET /api/cronjob/influencer-dummy`
**Schedule:** Runs after 01:00 AM Jakarta time (or force with `?mode=true`)
**Batch size:** 10 records per run

**Selection criteria:**
```sql
SELECT * FROM influencer_dummy
WHERE status = 'Aktif'
  AND (DATE(sync_at) <= '{7_days_ago}' OR sync_at IS NULL)
  AND url != ''
LIMIT 10
```

**Flow:** Same as `Influencer_dummy::_sync_engagement_data()`:
1. `get_account_id()` → write account_id, follower, img, media_count
2. `get_post_list()` → get up to 10 posts
3. Calculate avg_view_2, avg_interaksi_2, er, cpm_2 → write to `influencer_dummy`

### 7.3 `cronjob_endorse()` — `Api_v2.php:5049-5257`

**Route:** `GET /api/cronjob/endorse`
**Schedule:** Runs after 11:00 AM Jakarta time
**Batch size:** 10 records per run

**Selection criteria:**
```sql
SELECT * FROM endorse
WHERE status = 'Aktif'
  AND status_campaign = 'Aktif'
  AND (DATE(sync_at) < '{today}' OR DATE(sync_at) IS NULL)
  AND link_upload != ''
LIMIT 10
```

**Flow:**

1. For each endorsement, calls `template->get_social_media($platform, $link_upload)` (HTML scrape + RapidAPI fallback)
2. Extracts: like, comment, share+collect (as `share_save`), view, created_at (as `posting_at`)
3. Calculates CPM: `total_cost / views * 1000`
4. Performs FYP detection (see [Section 10](#10-fyp-detection-logic))
5. Writes to `endorse` table (current metrics)
6. Writes to `endorse_logs` table with before/after/delta tracking:
   - `likes_after`, `comment_after`, `share_save_after`, `views_after`, `cpm_after` — current totals
   - `likes_before`, `comment_before`, `share_save_before`, `views_before`, `cpm_before` — yesterday's totals
   - `likes`, `comment`, `share_save`, `views`, `cpm` — delta (today - yesterday)
7. After processing all endorsements, calls `update_endorse_parent()` for each active campaign

### 7.4 `cronjob_endorse_campaign()` — `Api_v2.php:5406-5451`

**Route:** `GET /api/cronjob/endorse-campaign`
**Schedule:** Runs after 11:00 AM Jakarta time
**Batch size:** 10 campaigns per run

Simply loops through active `endorse_campaign` records and calls `update_endorse_parent()` for each, which aggregates all endorsement metrics into the campaign.

---

## 8. Complete Metric Reference Table

### 8.1 Influencer Metrics (Internal — from endorsement history)

| UI Label | DB Column | Table | Formula / Source | File:Line |
|----------|-----------|-------|------------------|-----------|
| Total Cost | `total_cost` | `influencer` | `SUM(endorse.total_cost)` | `Influencer.php:700` |
| Views | `view` | `influencer` | `SUM(endorse.views)` | `Influencer.php:703` |
| CPM | `cpm` | `influencer` | `total_cost / views * 1000` | `Influencer.php:711` |
| Likes | `like` | `influencer` | `SUM(endorse.likes)` | `Influencer.php:704` |
| Comments | `comment` | `influencer` | `SUM(endorse.comment)` | `Influencer.php:705` |
| AVG Interaksi | `avg_interaksi` | `influencer` | `AVG(endorse.likes + endorse.comment + endorse.share_save)` | `Influencer.php:709` |
| AVG View | `avg_view` | `influencer` | `AVG(endorse.views)` | `Influencer.php:708` |
| Frequency | `frequency` | `influencer` | `COUNT(endorse.id)` where `link_upload != ''` | `Influencer.php:701` |
| Save & Share | `collect + share` | `influencer` | Displayed as `collect + share` in view | `item.php:461` |

### 8.2 Influencer Metrics (External — from TikTok profile posts via RapidAPI)

| UI Label | DB Column | Table | Formula / Source | File:Line |
|----------|-----------|-------|------------------|-----------|
| Range RC | `ratecard` | `influencer` | User-entered rate card value | `item.php:465` |
| CPM | `cpm_2` | `influencer` | `ratecard / avg_view_2 * 1000` | `Influencer.php:815` |
| AVG Interaksi | `avg_interaksi_2` | `influencer` | `(Σlike + Σcomment + Σcollect + Σshare) / post_count` from last 10 TikTok posts | `Influencer.php:812` |
| AVG View | `avg_view_2` | `influencer` | `Σview / post_count` from last 10 TikTok posts | `Influencer.php:811` |
| ER % | `er` | `influencer` | `avg_interaksi / avg_view * 100` (from post data) | `Influencer.php:781` |

### 8.3 Profile Metrics (from RapidAPI)

| UI Label | DB Column | Table | Source | File:Line |
|----------|-----------|-------|--------|-----------|
| Follower | `follower` | `influencer` / `influencer_dummy` | `get_account_id()` → Endpoint #1 or #2 | `Influencer.php:731` |
| Profile Image | `img` | `influencer` / `influencer_dummy` | `get_account_id()` → avatar URL | `Influencer.php:730` |
| Media Count | `media_count` | `influencer` / `influencer_dummy` | `get_account_id()` → aweme_count or video_count | `Influencer.php:732` |
| Full Name | `full_name` | `influencer` / `influencer_dummy` | `get_account_id()` → nickname | `Influencer_dummy.php:204-210` |

### 8.4 Other Influencer Display Fields

| UI Label | DB Column | Table | Source |
|----------|-----------|-------|--------|
| Brand | `brand` | `influencer` | User-selected (comma-separated) |
| Niche | `niche` | `influencer` | User-entered |
| Status | `status` | `influencer` | `Aktif` / `Tidak Aktif` |
| Status Reach | `status_reach` | `influencer` | Auto-calculated or user-set: `Belum Reachout`, `Sudah Reachout`, `Pernah Kerjasama`, `Repeat Kerjasama`, `Affiliate`, `Off Endorsement`, `Blacklist / Ghosting` |
| No Rekening | `no_rekening` | `influencer` | User-entered |
| PIC | `pic` | `influencer` | User-selected |
| Tanggal Diupdate | `sync_at` | `influencer` | Timestamp of last sync |

### 8.5 Endorse Campaign Metrics

| UI Label | DB Column | Table | Formula / Source | File:Line |
|----------|-----------|-------|------------------|-----------|
| Likes | `likes` | `endorse_campaign` | `SUM(endorse.likes)` via `update_endorse_parent()` | `item.php:81` |
| Comments | `comment` | `endorse_campaign` | `SUM(endorse.comment)` via `update_endorse_parent()` | `item.php:82` |
| Save & Share | `share_save` | `endorse_campaign` | `SUM(endorse.share_save)` via `update_endorse_parent()` | `item.php:83` |
| Views | `views` | `endorse_campaign` | `SUM(endorse.views)` via `update_endorse_parent()` | `item.php:84` |
| CPM | `cpm` | `endorse_campaign` | `SUM(total_cost) / SUM(views) * 1000` | `Endorse_campaign.php:449` |
| Jumlah Influencer | `count_influencer` | `endorse_campaign` | `COUNT(DISTINCT endorse.influencer)` | `Endorse_campaign.php:367-370` |
| Jumlah Endorse | `count_endorse` | `endorse_campaign` | `COUNT(endorse.id)` | `Endorse_campaign.php:347-350` |

### 8.6 Influencer Dummy Metrics

Same as Section 8.2 and 8.3, stored in `influencer_dummy` table with identical column names.

---

## 9. Database Schema

### 9.1 `influencer` Table

| Column | Type | Description | Populated By |
|--------|------|-------------|--------------|
| `id` | INT | Primary key | Auto-increment |
| `username` | VARCHAR | TikTok username (without @) | User / extracted from URL |
| `url` | VARCHAR | Full TikTok profile URL | User input |
| `type` | VARCHAR | Platform: `Tiktok`, `Instagram`, etc. | User input |
| `account_id` | VARCHAR | TikTok uid or sec_uid | `get_account_id()` |
| `img` | TEXT | Profile picture URL | `get_account_id()` |
| `follower` | INT | Follower count | `get_account_id()` |
| `media_count` | INT | Number of videos | `get_account_id()` |
| `full_name` | VARCHAR | Display name | `get_account_id()` |
| `niche` | VARCHAR | Content niche | User input |
| `brand` | VARCHAR | Associated brands (comma-separated) | User input |
| `pic` | VARCHAR | PIC name(s) | User input |
| `ratecard` | DOUBLE | Rate card (cost per post) | User input |
| `status` | VARCHAR | `Aktif` / `Tidak Aktif` | User input |
| `status_reach` | VARCHAR | Outreach status | Auto-calculated / User input |
| `no_rekening` | VARCHAR | Bank account number | User input |
| `desc` | TEXT | Description | User input |
| `contact` | VARCHAR | Contact info (WA/IG/etc) | User input |
| `tipe_kontak` | VARCHAR | Contact type: `WA`, `IG`, `Email`, `HP` | User input |
| **Internal metrics (from endorsements):** | | | |
| `frequency` | INT | Number of endorsements with uploaded links | `COUNT(endorse.id)` |
| `total_cost` | DOUBLE | Total cost across endorsements | `SUM(endorse.total_cost)` |
| `view` | DOUBLE | Total views across endorsements | `SUM(endorse.views)` |
| `like` | DOUBLE | Total likes across endorsements | `SUM(endorse.likes)` |
| `comment` | DOUBLE | Total comments across endorsements | `SUM(endorse.comment)` |
| `collect` | DOUBLE | Total saves across endorsements | `SUM(endorse.collect)` |
| `share` | DOUBLE | Total shares across endorsements | `SUM(endorse.share_save)` |
| `avg_view` | DOUBLE | Average views per endorsement | `AVG(endorse.views)` |
| `avg_interaksi` | DOUBLE | Average interactions per endorsement | `AVG(likes+comment+share_save)` |
| `cpm` | DOUBLE | CPM from endorsements | `total_cost / views * 1000` |
| **External metrics (from TikTok posts via RapidAPI):** | | | |
| `frequency_2` | INT | Number of posts analyzed (max 10) | Post count from `get_post_list()` |
| `view_2` | DOUBLE | Total views from last 10 posts | `Σ post.view` |
| `like_2` | DOUBLE | Total likes from last 10 posts | `Σ post.like` |
| `comment_2` | DOUBLE | Total comments from last 10 posts | `Σ post.comment` |
| `collect_2` | DOUBLE | Total saves from last 10 posts | `Σ post.collect` |
| `share_2` | DOUBLE | Total shares from last 10 posts | `Σ post.share` |
| `avg_view_2` | DOUBLE | Average views per post | `view_2 / frequency_2` |
| `avg_interaksi_2` | DOUBLE | Average interactions per post | `(like_2+comment_2+collect_2+share_2) / frequency_2` |
| `er` | DOUBLE | Engagement rate percentage | `avg_interaksi_2 / avg_view_2 * 100` |
| `cpm_2` | DOUBLE | CPM based on ratecard | `ratecard / avg_view_2 * 1000` |
| `sync_at` | DATETIME | Last sync timestamp | Set on each sync |
| `created_at` | DATETIME | Record creation time | On insert |
| `updated_at` | DATETIME | Last update time | On update |
| `created_by` | INT | User ID who created | Session user |
| `updated_by` | INT | User ID who updated | Session user |

### 9.2 `influencer_dummy` Table

Same structure as `influencer` table. Additionally:

| Column | Type | Description |
|--------|------|-------------|
| `is_generated` | TINYINT | `1` if copied to `influencer` table, `0` otherwise |

### 9.3 `influencer_logs` Table

Daily snapshot of influencer external metrics (from TikTok posts).

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `id_influencer` | INT | FK → `influencer.id` |
| `date` | DATE | Log date |
| `like` | DOUBLE | Total likes from posts (that day's sync) |
| `comment` | DOUBLE | Total comments |
| `collect` | DOUBLE | Total saves |
| `share` | DOUBLE | Total shares |
| `view` | DOUBLE | Total views |
| `avg_view` | DOUBLE | Average views per post |
| `avg_interaksi` | DOUBLE | Average interactions per post |
| `er` | DOUBLE | Engagement rate |
| `sync_at` | DATETIME | Sync timestamp |
| `status` | VARCHAR | `Aktif` |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

### 9.4 `influencer_cost` Table

| Column | Type | Description |
|--------|------|-------------|
| `nama_creator` | VARCHAR | Username (unique key) |
| `bank` | VARCHAR | Bank name |
| `no_rekening` | VARCHAR | Bank account number |
| `description` | TEXT | Notes |

### 9.5 `endorse` Table

Individual endorsement records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `id_campaign` | INT | FK → `endorse_campaign.id` |
| `influencer` | INT | FK → `influencer.id` |
| `nama_creator` | VARCHAR | Influencer username |
| `platform` | VARCHAR | `Tiktok`, `Instagram`, etc. |
| `link_upload` | TEXT | URL of the posted content |
| `total_cost` | DOUBLE | Cost for this endorsement |
| `likes` | DOUBLE | Current like count (from last sync) |
| `comment` | DOUBLE | Current comment count |
| `share_save` | DOUBLE | Current share + save count |
| `views` | DOUBLE | Current view count |
| `cpm` | DOUBLE | `total_cost / views * 1000` |
| `is_fyp` | VARCHAR | `1` if FYP detected, `0`/NULL otherwise |
| `posting_at` | DATE | Date the content was posted |
| `sync_at` | DATETIME | Last sync timestamp |
| `status` | VARCHAR | `Aktif` / `Tidak Aktif` |
| `status_campaign` | VARCHAR | Mirrors parent campaign status |
| `status_endorse` | VARCHAR | Workflow status |
| `brand` | VARCHAR | Brand code |

### 9.6 `endorse_logs` Table

Daily snapshot of individual endorsement metrics with before/after tracking.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `id_endorse` | INT | FK → `endorse.id` |
| `id_campaign` | INT | FK → `endorse_campaign.id` |
| `influencer` | INT | FK → `influencer.id` |
| `date` | DATE | Log date |
| `likes` | DOUBLE | **Delta** (today - yesterday) |
| `comment` | DOUBLE | Delta |
| `share_save` | DOUBLE | Delta |
| `views` | DOUBLE | Delta |
| `cpm` | DOUBLE | Delta CPM |
| `likes_after` | INT | Absolute total (current) |
| `comment_after` | INT | Absolute total (current) |
| `share_save_after` | INT | Absolute total (current) |
| `views_after` | INT | Absolute total (current) |
| `cpm_after` | DOUBLE | CPM at current metrics |
| `likes_before` | INT | Previous day's absolute total |
| `comment_before` | INT | Previous day's absolute total |
| `share_save_before` | INT | Previous day's absolute total |
| `views_before` | INT | Previous day's absolute total |
| `cpm_before` | DOUBLE | Previous day's CPM |
| `is_fyp` | VARCHAR | `1` if FYP detected |
| `brand` | VARCHAR | Brand code |
| `link_upload` | TEXT | Content URL |
| `platform` | VARCHAR | Platform name |
| `total_cost` | DOUBLE | Endorsement cost |
| `status` | VARCHAR | Endorse status |
| `status_campaign` | VARCHAR | Campaign status |

### 9.7 `endorse_campaign` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `title` | VARCHAR | Campaign title |
| `brand` | VARCHAR | Brand code |
| `sku` | VARCHAR | Product SKU |
| `product_text` | VARCHAR | Product name |
| `desc` | TEXT | Description |
| `pic` | VARCHAR | PIC name |
| `spv` | VARCHAR | Supervisor name |
| `start_at` | DATE | Campaign start date |
| `until_at` | DATE | Campaign end date |
| `status` | VARCHAR | `Aktif` / `Tidak Aktif` |
| `is_internal` | TINYINT | `0` = External, `1` = Internal |
| `total_cost` | DOUBLE | SUM of endorsement costs |
| `likes` | DOUBLE | SUM of endorsement likes |
| `comment` | DOUBLE | SUM of endorsement comments |
| `share_save` | DOUBLE | SUM of endorsement share+save |
| `views` | DOUBLE | SUM of endorsement views |
| `cpm` | DOUBLE | `total_cost / views * 1000` |
| `count_endorse` | INT | Total endorsement count |
| `count_endorse_active` | INT | Active endorsement count |
| `count_endorse_processed` | INT | Endorsements with `link_upload` |
| `count_influencer` | INT | Distinct influencer count |
| `count_influencer_active` | INT | Distinct active influencers |
| `count_influencer_processed` | INT | Distinct influencers with uploads |
| `media_file` | VARCHAR | Campaign media filename |
| `media_type` | VARCHAR | `image` or `video` |

### 9.8 `endorse_campaign_logs` Table

Daily snapshot of campaign-level aggregated metrics with before/after/delta tracking for influencer counts and endorsement counts.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `id_campaign` | INT | FK → `endorse_campaign.id` |
| `date` | DATE | Log date |
| `total_cost` | DOUBLE | Campaign total cost |
| `likes`, `comment`, `share_save`, `views`, `cpm` | DOUBLE | From `endorse_logs` aggregation |
| `likes_after`, `comment_after`, `share_save_after`, `views_after`, `cpm_after` | DOUBLE | Current totals |
| `likes_before`, `comment_before`, `share_save_before`, `views_before`, `cpm_before` | DOUBLE | Previous day totals |
| `ce_now`, `ce_active_now`, `ce_processed_now` | INT | Endorsement count deltas |
| `ce_before`, `ce_active_before`, `ce_processed_before` | INT | Previous counts |
| `ce_after`, `ce_active_after`, `ce_processed_after` | INT | Current counts |
| `ci_now`, `ci_active_now`, `ci_processed_now` | INT | Influencer count deltas |
| `ci_before`, `ci_active_before`, `ci_processed_before` | INT | Previous counts |
| `ci_after`, `ci_active_after`, `ci_processed_after` | INT | Current counts |
| `brand` | VARCHAR | Brand code |
| `status` | VARCHAR | Campaign status |

---

## 10. FYP Detection Logic

**Location:** `Api_v2.php:5128-5143` (inside `cronjob_endorse()`)

A post is flagged as FYP (For You Page / viral) when:

```php
if ($views >= 50000) {
    $follower = influencer.follower;
    if ($follower > 0) {
        $batas = $follower * 30 / 100;  // 30% of followers
        if ($views >= $batas) {
            $is_fyp = "1";
        }
    } else {
        // If follower count is 0, auto-flag as FYP
        $is_fyp = "1";
    }
}
```

**Rules:**
1. Views must be >= 50,000 (minimum threshold)
2. Views must be >= 30% of the influencer's follower count
3. If follower count is 0 or unknown, any post with >= 50,000 views is flagged as FYP

The `is_fyp` flag is written to the `endorse_logs` table (but stripped from `endorse` table updates via `unset($dt['is_fyp'])` at line 5148).

---

## Quick Reference: Source Files

| File | Lines | Purpose |
|------|-------|---------|
| `application/libraries/Template.php` | 295-806 | All RapidAPI integration methods |
| `application/controllers/Influencer.php` | 428-827 | Influencer sync (manual & bulk) |
| `application/controllers/Influencer_dummy.php` | 89-639 | Dummy influencer CRUD & sync |
| `application/controllers/Endorse_campaign.php` | 301-487 | Campaign metric aggregation |
| `application/controllers/Api_v2.php` | 4620-5451 | All cronjob endpoints |
| `application/views/influencer/item.php` | 439-473 | Influencer metric display |
| `application/views/endorse_campaign/item.php` | 81-85 | Campaign metric display |
| `application/helpers/env_helper.php` | 1-63 | Environment variable loader |
| `.env.example` | 76-79 | RapidAPI credential template |
