# TikTok Integration Spec

## Summary

This document describes the current TikTok integration implemented in this repository so it can be cloned into another application without reverse-engineering the PHP code.

The integration has three responsibilities:

1. Resolve a TikTok profile into internal account metadata.
2. Fetch the latest videos for a TikTok account.
3. Fetch metrics and media details for a TikTok post URL.

It also stores normalized TikTok media fields into the `endorse` table so they can be reused later without re-fetching everything.

All request and response shapes below are derived from the current implementation in `application/libraries/Template.php`.

## Runtime Surface

The TikTok integration is exposed through these internal methods:

- `get_account_id("Tiktok", $url, $influencer_id = null)`
- `get_post_list("Tiktok", $account_id, $influencer_id = null)`
- `get_social_media("Tiktok", $url, $fetch_media_assets = true, $influencer_id = null)`
- `get_tiktok_photo_images($content_id, $url = null)`
- `get_tiktok_video_play($url)`

Main downstream callers:

- Influencer sync flows in `application/controllers/Influencer.php`
- Endorse sync flows in `application/controllers/Endorse.php`
- API sync flows in `application/controllers/Api.php`
- API v2 sync flows in `application/controllers/Api_v2.php`

## Transport Rules

### Base request method

All RapidAPI TikTok calls use:

- HTTP method: `GET`
- Transport: cURL
- Response type: JSON decoded into associative arrays

### Shared cURL behavior

Implemented by `curlRequest($url, $headers = [])`:

- `CURLOPT_RETURNTRANSFER = true`
- `CURLOPT_ENCODING = ""`
- `CURLOPT_MAXREDIRS = 10`
- `CURLOPT_TIMEOUT = 30`
- `CURLOPT_HTTP_VERSION = CURL_HTTP_VERSION_1_1`
- `CURLOPT_CUSTOMREQUEST = "GET"`
- `CURLOPT_HTTPHEADER = $headers`

If cURL fails, the method returns:

```json
{
  "status": false,
  "msg": "cURL Error: <error>",
  "data": []
}
```

### Retry behavior

Implemented by `curlRequestWithRetry($url, $headers, $isValidResponse, $maxRetry = 3, $delayMs = 300)`:

- Max attempts: `3`
- Delay between retries: `300ms`
- Retry condition: the provided validator callback returns false
- Return value: the first valid response, or the last response after retries are exhausted

### RapidAPI headers

TikTok RapidAPI requests use these headers:

```http
Content-Type: application/json
x-rapidapi-host: tiktok-video-no-watermark10.p.rapidapi.com
x-rapidapi-key: <masked>
```

## Flow 1: Resolve TikTok Account Metadata

### Internal method

`get_account_id("Tiktok", $url, $influencer_id = null)`

### Input handling

The method expects a TikTok profile URL and extracts the username from the first path segment.

Example:

- Input URL: `https://www.tiktok.com/@example_user`
- Extracted username: `example_user`

The `@` prefix is removed before the RapidAPI request.

### Upstream request

- Method: `GET`
- URL template:

```text
https://tiktok-video-no-watermark10.p.rapidapi.com/index/Tiktok/getUserInfo?unique_id={username}
```

- Query params:
  - `unique_id`: TikTok username without `@`

### Success condition used by code

The response is treated as valid only if:

```php
intval($resp['code'] ?? -1) === 0 && !empty($resp['data']['user']['uniqueId'])
```

### Raw upstream fields consumed

The current code reads:

- `data.user.uniqueId`
- `data.user.secUid`
- `data.user.avatarLarger`
- `data.user.avatarMedium`
- `data.user.avatarThumb`
- `data.stats.followerCount`
- `data.stats.videoCount`

### Normalized internal response

On success, the app returns:

```json
{
  "status": true,
  "msg": "Data ditemukan",
  "data": {
    "username": "example_user",
    "account_id": "<secUid>",
    "follower": 12345,
    "media_count": 120,
    "img": "<avatar url or array from upstream>",
    "source": "first_endpoint"
  }
}
```

### Field mapping

| Internal field | Upstream source |
| --- | --- |
| `username` | `data.user.uniqueId` |
| `account_id` | `data.user.secUid` |
| `follower` | `data.stats.followerCount` |
| `media_count` | `data.stats.videoCount` |
| `img` | first non-empty of `avatarLarger`, `avatarMedium`, `avatarThumb` |
| `source` | hardcoded to `first_endpoint` |

### Failure response

If no valid user is returned:

```json
{
  "status": false,
  "msg": "Username <b>{username}</b> tidak ditemukan",
  "data": []
}
```

## Flow 2: Fetch TikTok Account Videos

### Internal method

`get_post_list("Tiktok", $account_id, $influencer_id = null)`

### Input handling

Despite the parameter name `account_id`, the TikTok branch treats this value as a username-like identifier.

The method normalizes it as:

1. Trim whitespace.
2. Remove any leading `@`.
3. Add `@` back before sending to RapidAPI.

Example:

- Input: `example_user`
- Sent upstream as: `@example_user`

### Upstream request

- Method: `GET`
- URL template:

```text
https://tiktok-video-no-watermark10.p.rapidapi.com/index/Tiktok/getUserVideos?unique_id={@username}&count=10&cursor=0
```

- Query params:
  - `unique_id`: TikTok username with `@`
  - `count`: `10`
  - `cursor`: `0`

### Success condition used by code

The response is treated as valid only if:

```php
intval($resp['code'] ?? -1) === 0 && !empty($resp['data']['videos'])
```

### Raw upstream fields consumed

Per video item, the current code reads:

- `video_id`
- `aweme_id`
- `digg_count`
- `share_count`
- `comment_count`
- `collect_count`
- `play_count`
- `title`
- `cover`
- `duration`
- `play`
- `wmplay`
- `music`
- `create_time`
- `is_ad`
- `author.id`
- `author.unique_id`
- `author.nickname`
- `author.avatar`

### Normalized internal response

On success, the method returns:

```json
{
  "status": true,
  "msg": "Data ditemukan",
  "data": [
    {
      "like": 0,
      "share": 0,
      "comment": 0,
      "collect": 0,
      "view": 0,
      "video_id": "123",
      "aweme_id": "123",
      "title": "caption",
      "cover": "https://...",
      "duration": 15,
      "play": "https://...",
      "wmplay": "https://...",
      "music": "https://...",
      "create_time": 1710000000,
      "is_ad": false,
      "author_id": "456",
      "author_unique_id": "example_user",
      "author_nickname": "Example User",
      "author_avatar": "https://...",
      "url": "https://www.tiktok.com/@example_user/video/123"
    }
  ]
}
```

### Field mapping

| Internal field | Upstream source |
| --- | --- |
| `like` | `digg_count` |
| `share` | `share_count` |
| `comment` | `comment_count` |
| `collect` | `collect_count` |
| `view` | `play_count` |
| `video_id` | `video_id`, fallback `aweme_id` |
| `aweme_id` | `aweme_id`, fallback `video_id` |
| `title` | `title` |
| `cover` | `cover` |
| `duration` | `duration` |
| `play` | `play` |
| `wmplay` | `wmplay` |
| `music` | `music` |
| `create_time` | `create_time` |
| `is_ad` | `is_ad` |
| `author_id` | `author.id` |
| `author_unique_id` | `author.unique_id` |
| `author_nickname` | `author.nickname` |
| `author_avatar` | `author.avatar` |
| `url` | constructed from `author.unique_id` and `video_id` |

### Failure response

```json
{
  "status": false,
  "msg": "Data video tiktok account id :  <b>{account_id}</b> tidak ditemukan",
  "data": []
}
```

## Flow 3: Fetch TikTok Post Metrics and Media

### Internal method

`get_social_media("Tiktok", $url, $fetch_media_assets = true, $influencer_id = null)`

### Purpose

This method normalizes a TikTok post URL into a reusable internal shape containing:

- engagement metrics
- view count
- created date
- content ID
- media type
- cover URL
- media asset links

### Default response shape

The method initializes this structure:

```json
{
  "status": true,
  "msg": "",
  "data": {
    "like": 0,
    "share": 0,
    "comment": 0,
    "collect": 0,
    "view": 0,
    "created_at": "",
    "content_id": "",
    "media_type": "",
    "video_link": "",
    "cover": "",
    "images": []
  }
}
```

### Input preprocessing

#### Extract content ID

`extract_tiktok_content_id($url)` checks in this order:

1. `/video/{digits}`
2. `/photo/{digits}`
3. any `10-25` digit sequence

If nothing matches, it returns an empty string.

#### Detect media type from URL

`detect_tiktok_media_type_from_url($url)` returns:

- `photo` if URL contains `/photo/`
- `video` if URL contains `/video/`
- empty string otherwise

### Step 1: Direct TikTok page scrape

The method first tries the public TikTok URL directly, not RapidAPI.

#### Request

- Method: `GET`
- URL: original TikTok post URL

#### cURL options specific to this step

- Follows redirects
- Uses a Firefox user agent
- Sends a hardcoded TikTok cookie header
- No JSON decoding at transport level, because it fetches HTML

#### Parsing logic

The code extracts this script tag:

```html
<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application/json">...</script>
```

Then it reads:

```text
__DEFAULT_SCOPE__.webapp.video-detail.itemInfo.itemStruct
```

#### Direct-scrape success condition

The scrape is considered usable only if:

- `item.stats` exists, and
- at least one of these values is greater than zero:
  - `diggCount`
  - `shareCount`
  - `commentCount`
  - `collectCount`
  - `playCount`

### Step 1 normalized mapping

If direct scrape succeeds, these fields are mapped:

| Internal field | Direct scrape source |
| --- | --- |
| `like` | `item.stats.diggCount` |
| `share` | `item.stats.shareCount` |
| `comment` | `item.stats.commentCount` |
| `collect` | `item.stats.collectCount` |
| `view` | `item.stats.playCount` |
| `created_at` | `date("Y-m-d", item.createTime)` |
| `content_id` | `item.id` |
| `media_type` | `photo` if `imagePost.images` or `imagePost.cover` exists, else `video` |
| `cover` | extracted by `extract_tiktok_cover_from_item()` |

#### Cover extraction order

`extract_tiktok_cover_from_item($item)` checks:

1. `item.video.cover`
2. `item.imagePost.cover.imageURL.urlList[0]`
3. `item.video.originCover`

### Step 1 media asset behavior

If `$fetch_media_assets` is true and detected `media_type` is `photo`:

- read each image URL from `item.imagePost.images[*].imageURL.urlList[0]`
- store those URLs into `data.images`
- store `json_encode(images)` into `data.video_link`

If no photo list is found but `cover` exists:

- `data.video_link = json_encode([cover])`

Note:

- In the direct-scrape branch, video posts do not set `video_link`
- `video_link` is only populated for photo posts in this branch

### Step 2: RapidAPI fallback for post detail

If direct scrape fails or returns invalid stats, the method falls back to RapidAPI.

#### Upstream request

- Method: `GET`
- URL template:

```text
https://tiktok-video-no-watermark10.p.rapidapi.com/index/Tiktok/getVideoInfo?url={encoded_tiktok_url}&hd=0
```

- Query params:
  - `url`: full TikTok URL
  - `hd`: `0`

#### Success condition used by code

```php
intval($resp['code'] ?? -1) === 0 && !empty($resp['data']['id'])
```

#### Raw upstream fields consumed

- `data.id`
- `data.digg_count`
- `data.share_count`
- `data.comment_count`
- `data.collect_count`
- `data.play_count`
- `data.create_time`
- `data.cover`
- `data.origin_cover`
- `data.ai_dynamic_cover`
- `data.images`
- `data.play`

### Step 2 normalized mapping

| Internal field | RapidAPI fallback source |
| --- | --- |
| `like` | `data.digg_count` |
| `share` | `data.share_count` |
| `comment` | `data.comment_count` |
| `collect` | `data.collect_count` |
| `view` | `data.play_count` |
| `created_at` | `date("Y-m-d", data.create_time)` |
| `content_id` | `data.id` |
| `cover` | first non-empty of `cover`, `origin_cover`, `ai_dynamic_cover` |
| `media_type` | `photo` if `data.images` exists or current media type is already `photo`, else `video` |

### Step 2 media asset behavior

If `$fetch_media_assets` is true:

- For photo posts with non-empty `data.images`:
  - `data.images` is populated with non-empty strings
  - `data.video_link = json_encode(data.images)`
- For photo posts without image list but with `cover`:
  - `data.video_link = json_encode([cover])`
- For non-photo posts with non-empty `data.play`:
  - `data.video_link = data.play`

### Fallback failure response

If the RapidAPI fallback also fails:

```json
{
  "status": false,
  "msg": "Response tiktok {content_id} tidak ditemukan"
}
```

## Persisted TikTok Fields

TikTok-specific normalized fields are stored into the `endorse` table during sync flows.

### Stored columns

- `tiktok_content_id`
- `tiktok_media_type`
- `tiktok_cover`
- `tiktok_content_link`
- `tiktok_fetched_at`

### Meaning of stored fields

| Column | Meaning |
| --- | --- |
| `tiktok_content_id` | normalized TikTok post ID |
| `tiktok_media_type` | `photo` or `video` |
| `tiktok_cover` | cover image URL, or downloaded/local cover later in FYP flows |
| `tiktok_content_link` | either a direct play URL or a JSON array of image URLs |
| `tiktok_fetched_at` | timestamp of last TikTok normalization |

### When fields are refreshed

The sync logic refreshes TikTok media fields when any of these are true:

- `tiktok_content_id` is empty
- `tiktok_media_type` is empty
- `tiktok_cover` is empty
- `tiktok_content_link` is empty
- current URL-derived content ID does not match the stored content ID

### FYP behavior

For TikTok content marked as FYP:

- the cover may be downloaded and replaced by a local asset path
- the rest of the normalized TikTok metadata is still persisted alongside sync results

## Read-Back Helper Methods

These methods do not call RapidAPI. They reuse stored TikTok fields from the database.

### `get_tiktok_photo_images($content_id, $url = null)`

Purpose:

- return a photo post's image URLs from `endorse.tiktok_content_link`

Lookup rules:

- query by `tiktok_content_id` and/or `link_upload`
- prefer rows where `tiktok_media_type = 'photo'`
- fallback to `tiktok_cover` if decoded image list is empty

Return shape:

```json
{
  "status": true,
  "msg": "",
  "data": [
    "https://..."
  ]
}
```

Failure conditions:

- DB unavailable
- `tiktok_content_link` column missing
- neither `content_id` nor `url` provided
- no matching row
- decoded image list empty and no cover available

### `get_tiktok_video_play($url)`

Purpose:

- return a video play URL from stored `endorse.tiktok_content_link`

Behavior:

- lookup by `link_upload`
- reject rows whose `tiktok_media_type` is `photo`
- return the raw `tiktok_content_link` string for video posts

Expected use:

- when post media was already normalized and stored during sync

## Controller-Level Usage

### Influencer profile sync

These flows use TikTok account metadata and recent posts:

- `get_account_id("Tiktok", profile_url, ...)`
- `get_post_list("Tiktok", account_id, ...)`

The returned data is used to update:

- influencer account ID
- follower count
- media count
- avatar
- aggregated engagement averages

### Endorse/content sync

These flows use:

- `get_social_media("Tiktok", link_upload, fetch_media_assets, influencer_id)`

The returned data is used to update:

- likes
- comments
- shares plus collects
- views
- posting date
- TikTok media metadata fields

### Helper endpoints

The app also exposes DB-backed helper endpoints:

- `POST Endorse/get_tiktok_photo_images`
  - input: `content_id`, `url`
- `POST Endorse/get_tiktok_video_play`
  - input: `url`

## Clone Notes

If you want to clone this integration into another app and preserve behavior, keep these rules unchanged:

1. Use `GET` for all RapidAPI TikTok requests.
2. Keep the same retry contract: 3 attempts, 300ms delay, validator-based success.
3. Resolve account metadata using `getUserInfo` and map `secUid` into internal `account_id`.
4. Fetch account videos using `getUserVideos` and build canonical TikTok URLs from `author.unique_id` plus `video_id`.
5. For post detail, try direct TikTok HTML parsing first and use RapidAPI `getVideoInfo` only as fallback.
6. Preserve the normalized response keys exactly if downstream code expects them:
   - `like`
   - `share`
   - `comment`
   - `collect`
   - `view`
   - `created_at`
   - `content_id`
   - `media_type`
   - `video_link`
   - `cover`
   - `images`
7. Preserve `tiktok_content_link` semantics:
   - video post: plain play URL string
   - photo post: JSON array string of image URLs
8. Preserve the URL-based content ID extraction logic for `/video/`, `/photo/`, and generic digit fallback.

## Inactive Endpoint

There is one commented-out TikTok RapidAPI endpoint in the codebase:

```text
https://tiktok-api23.p.rapidapi.com/api/search/account?keyword={keyword}&cursor=0&search_id=0
```

Current status:

- inactive
- not used in the live runtime flow
- should not be treated as part of the current TikTok contract unless re-enabled intentionally
