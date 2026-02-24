# TikTok RapidAPI Documentation

API Provider: **tiktok-api23** by Lundehund on RapidAPI
Base URL: `https://tiktok-api23.p.rapidapi.com`
Source file: `application/libraries/Template.php`

---

## Table of Contents

1. [Authentication](#1-authentication)
2. [Common Patterns](#2-common-patterns)
3. [Endpoints](#3-endpoints)
   - [3.1 Get User Info](#31-get-user-info)
   - [3.2 Search Account](#32-search-account)
   - [3.3 Get User Posts](#33-get-user-posts)
   - [3.4 Get Post Detail](#34-get-post-detail)
   - [3.5 Download Video](#35-download-video)
4. [Application Flow](#4-application-flow)
5. [Internal Endpoint Mapping](#5-internal-endpoint-mapping)

---

## 1. Authentication

All requests require two RapidAPI headers:

| Header | Value |
|--------|-------|
| `x-rapidapi-host` | `tiktok-api23.p.rapidapi.com` |
| `x-rapidapi-key` | `c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064` |

---

## 2. Common Patterns

### Response Status Validation

The API returns status in one of two fields. The codebase checks both:

```php
$ok = isset($resp['status_code'])
    ? intval($resp['status_code']) === 0
    : (isset($resp['statusCode']) && intval($resp['statusCode']) === 0);
```

A status value of `0` means success.

### Retry Logic — `curlRequestWithRetry()`

**Location:** `Template.php` line 752

Used by endpoints 3.1, 3.2, 3.3, and 3.4 (via `get_social_media`). Endpoints 3.4 (via `get_tiktok_photo_images`) and 3.5 implement their own inline retry loop with the same parameters.

| Parameter | Value |
|-----------|-------|
| Max retries | 3 |
| Delay between retries | 300 ms (`usleep(300000)`) |
| Timeout per request | 30 seconds |

Each call accepts a `$isValidResponse` callback that determines whether the response is acceptable. If the callback returns `false`, the request is retried.

```php
function curlRequestWithRetry($url, $headers, $isValidResponse, $maxRetry = 3, $delayMs = 300)
{
    $lastResponse = null;
    for ($attempt = 1; $attempt <= $maxRetry; $attempt++) {
        $lastResponse = $this->curlRequest($url, $headers);
        if (is_callable($isValidResponse) && $isValidResponse($lastResponse)) {
            return $lastResponse;
        }
        if ($attempt < $maxRetry) {
            usleep($delayMs * 1000);
        }
    }
    return $lastResponse;
}
```

### Base cURL Configuration

All requests share these cURL options (set in `curlRequest()` at line 291):

```php
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING       => "",
CURLOPT_MAXREDIRS      => 10,
CURLOPT_TIMEOUT        => 30,
CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST  => "GET",
```

---

## 3. Endpoints

### 3.1 Get User Info

Retrieve a TikTok user's profile by username.

| | |
|---|---|
| **URL** | `GET /api/user/info` |
| **Used in** | `Template::getDataFromFirstEndpoint()` (line 320) |
| **Called by** | `Template::get_account_id()` (line 433) — first attempt for TikTok user lookup |

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `uniqueId` | string | Yes | TikTok username (without `@` prefix) |

#### Sample cURL

```bash
curl -X GET \
  "https://tiktok-api23.p.rapidapi.com/api/user/info?uniqueId=username" \
  -H "x-rapidapi-host: tiktok-api23.p.rapidapi.com" \
  -H "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064"
```

#### Sample PHP (matches codebase)

```php
$url = "https://tiktok-api23.p.rapidapi.com/api/user/info?uniqueId=$username";
$headers = [
    "x-rapidapi-host: tiktok-api23.p.rapidapi.com",
    "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064"
];

$response = $this->curlRequestWithRetry($url, $headers, function ($resp) {
    $ok = isset($resp['status_code']) ? intval($resp['status_code']) === 0
        : (isset($resp['statusCode']) && intval($resp['statusCode']) === 0);
    return $ok && !empty($resp['userInfo']['user']['secUid']);
});
```

#### Validation Callback

Response is valid when status is `0` **and** `userInfo.user.secUid` is not empty.

#### Sample JSON Response

```json
{
  "status_code": 0,
  "userInfo": {
    "user": {
      "secUid": "MS4wLjABAAAA...",
      "uniqueId": "username",
      "avatarLarger": "https://p16-sign-sg.tiktokcdn.com/..."
    },
    "stats": {
      "followerCount": 150000,
      "videoCount": 42
    }
  }
}
```

#### Fields Used by Application

| JSON Path | Mapped To | Type |
|-----------|-----------|------|
| `userInfo.user.secUid` | `data.account_id` | string |
| `userInfo.user.uniqueId` | `data.username` | string |
| `userInfo.user.avatarLarger` | `data.img` | string (URL) |
| `userInfo.stats.followerCount` | `data.follower` | int |
| `userInfo.stats.videoCount` | `data.media_count` | int |

---

### 3.2 Search Account

Search TikTok accounts by keyword. Used as a **fallback** when endpoint 3.1 fails to find the user.

| | |
|---|---|
| **URL** | `GET /api/search/account` |
| **Used in** | `Template::getDataFromSearchEndpoint()` (line 334) |
| **Called by** | `Template::get_account_id()` (line 464) — fallback when user info endpoint fails |

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `keyword` | string | Yes | Search term (URL-encoded username) |
| `cursor` | int | Yes | Pagination offset (always `0` in codebase) |
| `search_id` | int | Yes | Search session ID (always `0` in codebase) |

#### Sample cURL

```bash
curl -X GET \
  "https://tiktok-api23.p.rapidapi.com/api/search/account?keyword=username&cursor=0&search_id=0" \
  -H "x-rapidapi-host: tiktok-api23.p.rapidapi.com" \
  -H "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064"
```

#### Sample PHP (matches codebase)

```php
$keyword = urlencode($username);
$url = "https://tiktok-api23.p.rapidapi.com/api/search/account?keyword=$keyword&cursor=0&search_id=0";
$headers = [
    "x-rapidapi-host: tiktok-api23.p.rapidapi.com",
    "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064"
];

$response = $this->curlRequestWithRetry($url, $headers, function ($resp) {
    $ok = isset($resp['status_code']) ? intval($resp['status_code']) === 0
        : (isset($resp['statusCode']) && intval($resp['statusCode']) === 0);
    return $ok && !empty($resp['user_list'][0]['user_info']);
});
```

#### Validation Callback

Response is valid when status is `0` **and** `user_list[0].user_info` is not empty.

#### Sample JSON Response

```json
{
  "status_code": 0,
  "user_list": [
    {
      "user_info": {
        "unique_id": "username",
        "sec_uid": "MS4wLjABAAAA...",
        "follower_count": 150000,
        "item_count": 42,
        "avatar_thumb": {
          "url_list": [
            "https://p16-sign-sg.tiktokcdn.com/..."
          ]
        }
      }
    }
  ]
}
```

#### Fields Used by Application

| JSON Path | Mapped To | Type |
|-----------|-----------|------|
| `user_list[0].user_info.sec_uid` | `data.account_id` | string |
| `user_list[0].user_info.unique_id` | `data.username` | string |
| `user_list[0].user_info.avatar_thumb.url_list[0]` | `data.img` | string (URL) |
| `user_list[0].user_info.follower_count` | `data.follower` | int |
| `user_list[0].user_info.item_count` | `data.media_count` | int |

#### Notes

- Only the **first result** (`user_list[0]`) is used.
- The `data.source` field is set to `"search_endpoint"` to distinguish from endpoint 3.1 (`"first_endpoint"`).

---

### 3.3 Get User Posts

Retrieve a user's recent TikTok videos by their `secUid`.

| | |
|---|---|
| **URL** | `GET /api/user/posts` |
| **Used in** | `Template::get_post_list()` (line 559) |
| **Called by** | `Influencer`, `Api`, `Api_v2`, `Influencer_dummy` controllers |

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `secUid` | string | Yes | User's secUid (URL-encoded). Obtained from endpoint 3.1 or 3.2 |
| `count` | int | Yes | Number of posts to retrieve (always `10` in codebase) |
| `cursor` | int | Yes | Pagination offset (always `0` in codebase) |

#### Sample cURL

```bash
curl -X GET \
  "https://tiktok-api23.p.rapidapi.com/api/user/posts?secUid=MS4wLjABAAAA...&count=10&cursor=0" \
  -H "x-rapidapi-host: tiktok-api23.p.rapidapi.com" \
  -H "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064" \
  -H "Accept: application/json"
```

#### Sample PHP (matches codebase)

```php
$url = "https://tiktok-api23.p.rapidapi.com/api/user/posts?secUid="
     . urlencode($account_id) . "&count=10&cursor=0";
$headers = [
    "x-rapidapi-host: tiktok-api23.p.rapidapi.com",
    "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064",
    "Accept: application/json"
];

$response = $this->curlRequestWithRetry($url, $headers, function ($resp) {
    $data_block = $resp['data'] ?? [];
    $ok = isset($data_block['status_code']) ? intval($data_block['status_code']) === 0
        : (isset($data_block['statusCode']) && intval($data_block['statusCode']) === 0);
    return $ok && !empty($data_block['itemList']);
});
```

#### Validation Callback

Response is valid when `data.status_code` (or `data.statusCode`) is `0` **and** `data.itemList` is not empty.

> **Note:** Unlike other endpoints, the status code here is nested inside the `data` object.

#### Sample JSON Response

```json
{
  "data": {
    "status_code": 0,
    "itemList": [
      {
        "stats": {
          "diggCount": 5200,
          "shareCount": 310,
          "commentCount": 89,
          "collectCount": 420,
          "playCount": 125000
        }
      }
    ]
  }
}
```

#### Fields Used by Application

For each item in `data.itemList` (capped at 10 items via `array_slice`):

| JSON Path | Mapped To | Type |
|-----------|-----------|------|
| `stats.diggCount` | `like` | int |
| `stats.shareCount` | `share` | int |
| `stats.commentCount` | `comment` | int |
| `stats.collectCount` | `collect` | int |
| `stats.playCount` | `view` | int |

#### Notes

- This endpoint includes an additional `Accept: application/json` header not present in other endpoints.
- Results are sliced to a maximum of 10 items regardless of `count` parameter.

---

### 3.4 Get Post Detail

Get detailed information about a specific TikTok post, including engagement stats and photo images.

| | |
|---|---|
| **URL** | `GET /api/post/detail` |
| **Used in** | `Template::get_social_media()` (line 636) — stats extraction |
| | `Template::get_tiktok_photo_images()` (line 770) — photo image extraction |
| **Called by** | `Endorse`, `User`, `Customer`, `Crm`, `Expense`, `Product`, `Api`, `Api_v2` controllers |

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `videoId` | string | Yes | Numeric post ID extracted from URL |

The `videoId` is extracted from TikTok URLs using regex patterns:

```php
// Pattern priority:
preg_match('/\/video\/(\d+)/', $url, $matches);   // /video/1234567890
preg_match('/\/photo\/(\d+)/', $url, $matches);    // /photo/1234567890
preg_match('/(\d{10,25})/', $url, $matches);       // fallback: any 10-25 digit number
```

#### Sample cURL

```bash
curl -X GET \
  "https://tiktok-api23.p.rapidapi.com/api/post/detail?videoId=7345678901234567890" \
  -H "x-rapidapi-host: tiktok-api23.p.rapidapi.com" \
  -H "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064"
```

#### Sample PHP — Stats Extraction (matches `get_social_media`)

```php
$detail_url = "https://tiktok-api23.p.rapidapi.com/api/post/detail?videoId=$video_id";
$headers = [
    "x-rapidapi-host: tiktok-api23.p.rapidapi.com",
    "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064"
];

$response = $this->curlRequestWithRetry($detail_url, $headers, function ($resp) {
    $ok = isset($resp['status_code']) ? intval($resp['status_code']) === 0
        : (isset($resp['statusCode']) && intval($resp['statusCode']) === 0);
    return $ok && !empty($resp['itemInfo']['itemStruct']['stats']['playCount']);
});
```

#### Sample PHP — Photo Extraction (matches `get_tiktok_photo_images`)

```php
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => "https://tiktok-api23.p.rapidapi.com/api/post/detail?videoId=" . $content_id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => "",
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => "GET",
    CURLOPT_HTTPHEADER     => [
        "x-rapidapi-host: tiktok-api23.p.rapidapi.com",
        "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064"
    ],
]);
$responsee = curl_exec($curl);
curl_close($curl);
$json = json_decode($responsee, true);
```

> **Note:** `get_tiktok_photo_images` uses its own inline retry loop (3 attempts, 300ms delay) instead of `curlRequestWithRetry`.

#### Validation Callback (stats extraction)

Response is valid when status is `0` **and** `itemInfo.itemStruct.stats.playCount` is not empty.

#### Validation (photo extraction)

Response is valid when `itemInfo.itemStruct` is not empty (no status code check).

#### Sample JSON Response

```json
{
  "status_code": 0,
  "itemInfo": {
    "itemStruct": {
      "createTime": 1700000000,
      "stats": {
        "diggCount": 5200,
        "shareCount": 310,
        "commentCount": 89,
        "collectCount": 420,
        "playCount": 125000
      },
      "imagePost": {
        "images": [
          {
            "imageURL": {
              "urlList": [
                "https://p16-sign-sg.tiktokcdn.com/image1.jpg"
              ]
            }
          },
          {
            "imageURL": {
              "urlList": [
                "https://p16-sign-sg.tiktokcdn.com/image2.jpg"
              ]
            }
          }
        ],
        "cover": {
          "imageURL": {
            "urlList": [
              "https://p16-sign-sg.tiktokcdn.com/cover.jpg"
            ]
          }
        }
      },
      "video": {
        "cover": "https://p16-sign-sg.tiktokcdn.com/video-cover.jpg",
        "originCover": "https://p16-sign-sg.tiktokcdn.com/origin-cover.jpg"
      }
    }
  }
}
```

#### Fields Used — Stats (`get_social_media`)

| JSON Path | Mapped To | Type |
|-----------|-----------|------|
| `itemInfo.itemStruct.stats.diggCount` | `data.like` | int |
| `itemInfo.itemStruct.stats.shareCount` | `data.share` | int |
| `itemInfo.itemStruct.stats.commentCount` | `data.comment` | int |
| `itemInfo.itemStruct.stats.collectCount` | `data.collect` | int |
| `itemInfo.itemStruct.stats.playCount` | `data.view` | int |
| `itemInfo.itemStruct.createTime` | `data.created_at` | string (formatted as `Y-m-d`) |

#### Fields Used — Photos (`get_tiktok_photo_images`)

| JSON Path | Mapped To | Fallback Chain |
|-----------|-----------|----------------|
| `itemInfo.itemStruct.imagePost.images[].imageURL.urlList[0]` | Array of image URLs | Primary source |
| `itemInfo.itemStruct.imagePost.cover.imageURL.urlList[0]` | Single cover URL | 1st fallback |
| `itemInfo.itemStruct.video.cover` | Single cover URL | 2nd fallback |
| `itemInfo.itemStruct.video.originCover` | Single cover URL | 3rd fallback |

#### Photo Fallback Logic

```
1. Try imagePost.images[] → collect all image URLs
2. If no images → try imagePost.cover.imageURL.urlList[0]
3. If no cover → try video.cover
4. If no video cover → try video.originCover
5. If nothing found → return error
```

---

### 3.5 Download Video

Get a direct playable/downloadable video URL from a TikTok post URL.

| | |
|---|---|
| **URL** | `GET /api/download/video` |
| **Used in** | `Template::get_tiktok_video_play()` (line 854) |
| **Called by** | `Endorse::get_tiktok_video_play()` (line 3468) |

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `url` | string | Yes | Full TikTok post URL (URL-encoded) |

#### Sample cURL

```bash
curl -X GET \
  "https://tiktok-api23.p.rapidapi.com/api/download/video?url=https%3A%2F%2Fwww.tiktok.com%2F%40user%2Fvideo%2F1234567890" \
  -H "x-rapidapi-host: tiktok-api23.p.rapidapi.com" \
  -H "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064"
```

#### Sample PHP (matches codebase)

```php
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => "https://tiktok-api23.p.rapidapi.com/api/download/video?url=" . urlencode($url),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => "",
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => "GET",
    CURLOPT_HTTPHEADER     => [
        "x-rapidapi-host: tiktok-api23.p.rapidapi.com",
        "x-rapidapi-key: c0132c2445mshed67628a85d605bp1d63c3jsn85aa268b1064"
    ],
]);
$responsee = curl_exec($curl);
curl_close($curl);
$json = json_decode($responsee, true);
```

> **Note:** Uses its own inline retry loop (3 attempts, 300ms delay) instead of `curlRequestWithRetry`.

#### Validation

Response is valid when `play` field is not empty.

#### Sample JSON Response

```json
{
  "play": "https://v16-webapp-prime.tiktok.com/video/...",
  "music": "https://sf16-ies-music-sg.tiktokcdn.com/...",
  "hdplay": "https://v16-webapp-prime.tiktok.com/video/hd/..."
}
```

#### Fields Used by Application

| JSON Path | Mapped To | Type |
|-----------|-----------|------|
| `play` | `data.play` | string (URL) |

---

## 4. Application Flow

```
Frontend (AJAX)
    │
    ▼
Controller (Influencer / Endorse / Api / Api_v2 / User / Crm / etc.)
    │
    ▼
Template Library (application/libraries/Template.php)
    │
    ├── get_account_id()
    │     ├── getDataFromFirstEndpoint()  ──► /api/user/info
    │     └── getDataFromSearchEndpoint() ──► /api/search/account  (fallback)
    │
    ├── get_post_list()                   ──► /api/user/posts
    │
    ├── get_social_media()                ──► /api/post/detail     (stats)
    │
    ├── get_tiktok_photo_images()         ──► /api/post/detail     (photos)
    │
    └── get_tiktok_video_play()           ──► /api/download/video
```

### Typical User Lookup Flow

1. **`get_account_id("Tiktok", $profile_url)`** — Resolves a TikTok profile URL to a `secUid`
   - First tries `/api/user/info` (endpoint 3.1)
   - Falls back to `/api/search/account` (endpoint 3.2) if user info fails
2. **`get_post_list("Tiktok", $secUid)`** — Fetches the user's recent 10 posts with engagement stats
3. For individual post analysis:
   - **`get_social_media("Tiktok", $post_url)`** — Gets engagement stats for a specific post
   - **`get_tiktok_photo_images($content_id)`** — Gets photo images from a carousel/photo post
   - **`get_tiktok_video_play($post_url)`** — Gets direct video download URL

---

## 5. Internal Endpoint Mapping

### Controllers calling Template methods

| Controller | Method/Context | Template Method Called |
|------------|---------------|----------------------|
| **Influencer** | Influencer registration, bulk import | `get_account_id()` → `get_post_list()` |
| **Influencer_dummy** | Testing/development | `get_post_list()`, `get_social_media()` |
| **Endorse** | Endorsement content verification | `get_social_media()`, `get_tiktok_photo_images()`, `get_tiktok_video_play()` |
| **Api** | REST API v1 endpoints | `get_post_list()`, `get_social_media()` |
| **Api_v2** | REST API v2 endpoints | `get_post_list()`, `get_social_media()` |
| **User** | User social media verification | `get_social_media()` |
| **Customer** | Customer social media tracking | `get_social_media()` |
| **Crm** | CRM social media integration | `get_social_media()` |
| **Product** | Product-related social tracking | `get_social_media()` |
| **Expense** | Expense social media verification | `get_social_media()` |

### AJAX Endpoints (Frontend → Controller)

| Frontend AJAX Route | Controller Method | Template Method |
|--------------------|-------------------|-----------------|
| `POST /endorse/get_tiktok_photo_images` | `Endorse::get_tiktok_photo_images()` | `Template::get_tiktok_photo_images()` |
| `POST /endorse/get_tiktok_video_play` | `Endorse::get_tiktok_video_play()` | `Template::get_tiktok_video_play()` |
| `POST /influencer_dummy/get_post_list` | `Influencer_dummy::get_post_list()` | `Template::get_post_list()` |
| `POST /influencer_dummy/get_social_media` | `Influencer_dummy::get_social_media()` | `Template::get_social_media()` |

---

## Error Handling Summary

| Scenario | Behavior |
|----------|----------|
| cURL error (network failure) | Returns `{"status": false, "msg": "cURL Error: ...", "data": []}` |
| Invalid/empty response after 3 retries | Returns the last raw response from the API |
| Status code not `0` | Treated as failure, triggers retry or returns error |
| Missing expected data fields | Returns `{"status": false, "msg": "... tidak ditemukan", "data": []}` |
| Empty `videoId` / content ID | Returns early with error message before making API call |
| Empty URL parameter | Returns early with error message before making API call |
