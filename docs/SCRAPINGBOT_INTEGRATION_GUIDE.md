# ScrapingBot Integration Guide

> Step-by-step guide for integrating the ScrapingBot async queue-based social media scraping system into a CodeIgniter 3 application.

> **IMPORTANT: Hybrid Architecture (Feb 2026)**
>
> TikTok uses RapidAPI for synchronous responses, but the live contract is defined in `TIKTOK_INTEGRATION_SPEC.md`. Treat provider-specific notes below as historical unless they match that spec.
>
> | Platform | Provider | Mode | Where |
> |----------|----------|------|-------|
> | TikTok | RapidAPI | Synchronous | `Template::syncTiktokProfile()`, `cronjob_tiktok_sync` |
> | Instagram | ScrapingBot | Async queue | `Template::enqueue_scrape()`, `cronjob_scraping_*` |
>
> Controllers bifurcate by `$type`: TikTok calls `syncTiktokProfile()` directly, Instagram calls `enqueue_scrape()`.

## Architecture Overview

The system uses an **async queue pattern** with three phases (for **Instagram only** — TikTok uses synchronous RapidAPI):

```
[User Action / Cronjob]          [Cronjob: Submit]           [Cronjob: Poll]
        |                              |                           |
        v                              v                           v
  INSERT into                  SELECT pending items         SELECT submitted items
  scraping_queue               POST to ScrapingBot API      GET from ScrapingBot API
  (status=pending)             Update status=submitted      If done: status=completed
                               Store responseId             Parse + update entity
```

**Flow (Instagram):**
1. **Enqueue** - A controller or cronjob inserts a row into `scraping_queue` with `status=pending`
2. **Submit** (every 5 min) - Picks pending items, sends POST to ScrapingBot, stores `responseId`, sets `status=submitted`
3. **Poll** (every 1 min) - Picks submitted items, sends GET to ScrapingBot with `responseId`, parses result, updates the entity table
4. **Auto-enqueue** (every 30 min) - Finds stale records needing a refresh and inserts them into the queue with priority tiers

**Flow (TikTok):**
1. Controller or cronjob calls `Template::syncTiktokProfile()` directly
2. `syncTiktokProfile()` calls RapidAPI endpoints synchronously: `/api/user/info` → `/api/user/posts`
3. Profile + engagement metrics are written to DB immediately
4. A separate `cronjob_tiktok_sync` (every 10 min) handles background auto-sync for TikTok records

**Supported platforms:**
- Instagram profiles (`instagramProfile` scraper) — **async via ScrapingBot**
- TikTok profiles — **sync via RapidAPI** (see separate [TikTok RapidAPI docs](performance-appraisal/TikTok_RapidAPI_Documentation.md))

---

## Prerequisites

- A **CodeIgniter 3** application with MySQL
- A **ScrapingBot** account from [scraping-bot.io](https://www.scraping-bot.io/)
- The `env_helper.php` pattern (or any way to read `.env` variables)
- A model with `selectWithQuery($sql)` that returns an array of rows (or use `$this->db->query()->result_array()`)
- Server-side cron access (crontab or equivalent)

---

## Step 1: Create the `scraping_queue` Table

Run this SQL against your database:

```sql
CREATE TABLE IF NOT EXISTS `scraping_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entity_type` ENUM('influencer', 'influencer_dummy', 'endorse') NOT NULL,
    `entity_id` INT NOT NULL,
    `scraper` VARCHAR(50) NOT NULL COMMENT 'tiktokProfile or instagramProfile',
    `scrape_url` VARCHAR(500) NOT NULL,
    `response_id` VARCHAR(255) NULL,
    `status` ENUM('pending', 'submitted', 'polling', 'completed', 'failed') DEFAULT 'pending',
    `result_data` LONGTEXT NULL,
    `attempts` INT DEFAULT 0,
    `max_attempts` INT DEFAULT 10,
    `error_message` TEXT NULL,
    `priority` INT DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `submitted_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_priority_status` (`priority` DESC, `status`, `created_at` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Customize:** Modify the `entity_type` ENUM values to match the tables in your app. For example, if your app has `kol` and `endorsement` tables instead of `influencer` and `endorse`, change accordingly.

### Optional: Migration Runner

If you want a standalone migration runner (no full CI bootstrap needed):

Create `migrations/run.php`:

```php
<?php
/**
 * Migration runner - execute via CLI:
 *   php migrations/run.php 001_create_scraping_queue.sql
 */
$_SERVER['REQUEST_METHOD'] = 'GET';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

chdir(dirname(__DIR__));
define('BASEPATH', dirname(__DIR__) . '/system/');
require_once 'application/helpers/env_helper.php';

// IMPORTANT: The env key is DB_HOSTNAME, not DB_HOST
$host = env('DB_HOSTNAME', 'localhost');
$user = env('DB_USERNAME', 'root');
$pass = env('DB_PASSWORD', '');
$name = env('DB_DATABASE', '');
$port = 3306;

if (empty($name)) {
    die("ERROR: DB_DATABASE not set in .env\n");
}

$file = $argv[1] ?? null;
if (!$file) {
    die("Usage: php migrations/run.php <filename.sql>\n");
}

$path = __DIR__ . '/' . $file;
if (!file_exists($path)) {
    die("ERROR: File not found: $path\n");
}

$sql = file_get_contents($path);
if (empty(trim($sql))) {
    die("ERROR: SQL file is empty\n");
}

$socket = ($host === 'localhost') ? '/tmp/mysql.sock' : null;
$mysqli = new mysqli($host, $user, $pass, $name, $port, $socket);
if ($mysqli->connect_error) {
    die("DB Connection failed: " . $mysqli->connect_error . "\n");
}

echo "Connected to $name@$host\n";
echo "Running: $file\n\n";

if ($mysqli->multi_query($sql)) {
    $i = 0;
    do {
        $i++;
        $affected = $mysqli->affected_rows;
        echo "Statement #$i: $affected row(s) affected\n";
    } while ($mysqli->more_results() && $mysqli->next_result());

    if ($mysqli->errno) {
        echo "\nERROR on statement #$i: " . $mysqli->error . "\n";
    } else {
        echo "\nDone. All statements executed successfully.\n";
    }
} else {
    echo "ERROR: " . $mysqli->error . "\n";
}

$mysqli->close();
```

Usage: `php migrations/run.php 001_create_scraping_queue.sql`

---

## Step 2: Add Environment Variables

Add to your `.env` file:

```ini
# RapidAPI Configuration (TikTok Profile/Post Scraping - synchronous)
RAPIDAPI_HOST=tiktok-video-no-watermark10.p.rapidapi.com
RAPIDAPI_KEY=your_rapidapi_key

# ScrapingBot Configuration (Instagram Profile Scraping - async queue)
# Get credentials from: https://www.scraping-bot.io/
SCRAPINGBOT_USERNAME=your_username
SCRAPINGBOT_API_KEY=your_api_key
SCRAPINGBOT_BASE_URL=http://api.scraping-bot.io
```

Make sure your `env_helper.php` is loaded so `env()` works. If your app uses a different config pattern, adjust the library in the next step.

---

## Step 3: Create the `Scrapingbot.php` Library

Create `application/libraries/Scrapingbot.php`:

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scrapingbot
{
    private $username;
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        // CUSTOMIZE: Change env() calls if your app uses a different config pattern
        $this->username = env('SCRAPINGBOT_USERNAME', '');
        $this->apiKey = env('SCRAPINGBOT_API_KEY', '');
        $this->baseUrl = rtrim(env('SCRAPINGBOT_BASE_URL', 'http://api.scraping-bot.io'), '/');
    }

    /**
     * Start a scrape job via POST
     *
     * @param string $scraper  Scraper type (e.g. 'tiktokProfile', 'instagramProfile')
     * @param array  $params   Scraper-specific parameters
     * @return array ['status' => bool, 'responseId' => string|null, 'msg' => string]
     */
    public function startScrape($scraper, $params = [])
    {
        $url = $this->baseUrl . '/scrape/data-scraper';

        $body = array_merge(['scraper' => $scraper], $params);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_USERPWD        => $this->username . ':' . $this->apiKey,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['status' => false, 'responseId' => null, 'msg' => "cURL Error: $err"];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($data['responseId'])) {
            return [
                'status'     => true,
                'responseId' => $data['responseId'],
                'msg'        => 'Scrape job submitted'
            ];
        }

        return [
            'status'     => false,
            'responseId' => null,
            'msg'        => 'Failed to start scrape: ' . ($data['message'] ?? $response)
        ];
    }

    /**
     * Poll for scrape result via GET
     *
     * GOTCHA: The data-scraper-response endpoint returns a raw array [{"type":"profile",...}]
     * on success, NOT a wrapped {"status":"success","response":[...]}. You MUST check for
     * isset($data[0]) BEFORE isset($data['status']).
     *
     * @param string $scraper     Scraper type
     * @param string $responseId  The responseId from startScrape
     * @return array ['status' => string, 'data' => mixed, 'msg' => string]
     *               status: 'success', 'pending', 'error'
     */
    public function pollResult($scraper, $responseId)
    {
        $url = $this->baseUrl . '/scrape/data-scraper-response?scraper='
            . urlencode($scraper) . '&responseId=' . urlencode($responseId);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_USERPWD        => $this->username . ':' . $this->apiKey,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['status' => 'error', 'data' => null, 'msg' => "cURL Error: $err"];
        }

        $data = json_decode($response, true);

        if ($httpCode == 200 && is_array($data)) {
            // IMPORTANT: Check for raw array FIRST (success case)
            // ScrapingBot returns [{"type":"profile",...}] directly, not wrapped
            if (isset($data[0]) && !isset($data['status'])) {
                // Detect error responses disguised as success: [{"message":"Something went wrong",...}]
                $firstItem = $data[0];
                if (isset($firstItem['message']) && ($firstItem['type'] ?? null) !== 'profile') {
                    return [
                        'status' => 'error',
                        'data'   => null,
                        'msg'    => 'Scrape returned error: ' . $firstItem['message']
                    ];
                }

                return [
                    'status' => 'success',
                    'data'   => $data,
                    'msg'    => 'Scrape completed'
                ];
            }

            // Fallback: wrapped response format
            if (isset($data['status']) && $data['status'] === 'success') {
                return [
                    'status' => 'success',
                    'data'   => $data['response'] ?? $data,
                    'msg'    => 'Scrape completed'
                ];
            }

            // Still processing
            if (isset($data['status']) && $data['status'] === 'pending') {
                return [
                    'status' => 'pending',
                    'data'   => null,
                    'msg'    => 'Scrape still processing'
                ];
            }

            // Some responses use a message string without status when still processing
            if (isset($data['message']) && stripos($data['message'], 'not finished') !== false) {
                return [
                    'status' => 'pending',
                    'data'   => null,
                    'msg'    => 'Scrape still processing'
                ];
            }
        }

        // Non-JSON or unexpected responses that indicate "not finished" should be treated as pending
        if (is_string($response) && stripos($response, 'not finished') !== false) {
            return [
                'status' => 'pending',
                'data'   => null,
                'msg'    => 'Scrape still processing'
            ];
        }

        return [
            'status' => 'error',
            'data'   => null,
            'msg'    => 'Scrape failed: ' . ($data['message'] ?? $response)
        ];
    }

    /**
     * Convenience: start TikTok profile scrape
     *
     * GOTCHA: tiktokProfile only accepts `url`. Do NOT pass `max_video_count`
     * (that param is only for `tiktokHashtag`).
     */
    public function scrapeTiktokProfile($url)
    {
        return $this->startScrape('tiktokProfile', [
            'url' => $url,
        ]);
    }

    /**
     * Convenience: start Instagram profile scrape
     *
     * GOTCHA: instagramProfile uses `account` (not `username`).
     */
    public function scrapeInstagramProfile($account, $postsNumber = 12)
    {
        return $this->startScrape('instagramProfile', [
            'account'      => $account,
            'posts_number' => $postsNumber,
        ]);
    }

    /**
     * Build scrape parameters for a given platform and URL
     *
     * @param string $type  Platform type ('Tiktok' or 'Instagram')
     * @param string $url   Profile URL
     * @return array|false  ['scraper' => string, 'params' => array] or false
     */
    public function buildScrapeParams($type, $url)
    {
        if ($type === 'Tiktok') {
            $uri = explode("/", parse_url($url, PHP_URL_PATH));
            $username = $uri[1] ?? '';
            $username = str_replace('@', '', $username);
            if (empty($username)) {
                return false;
            }
            return [
                'scraper' => 'tiktokProfile',
                'params'  => [
                    'url' => 'https://www.tiktok.com/@' . $username,
                ],
            ];
        }

        if ($type === 'Instagram') {
            $uri = explode("/", parse_url($url, PHP_URL_PATH));
            $username = $uri[1] ?? '';
            $username = str_replace('@', '', $username);
            if (empty($username)) {
                return false;
            }
            return [
                'scraper' => 'instagramProfile',
                'params'  => [
                    'account'      => $username,
                    'posts_number' => 12,
                ],
            ];
        }

        return false;
    }
}
```

---

## Step 4: Add Queue Helper Functions

Add these methods to your **Template library** (or wherever your shared helpers live). These are the core functions that enqueue scrape requests and process results.

### 4a. `enqueue_scrape()` - Insert a job into the queue

```php
/**
 * Insert a scraping queue item for async processing
 *
 * @param string $entityType  Your entity type (e.g. 'influencer', 'endorse')
 * @param int    $entityId    The entity record ID
 * @param string $type        Platform type ('Tiktok' or 'Instagram')
 * @param string $url         Profile URL
 * @param int    $priority    Priority level (higher = processed first)
 * @return array ['status' => bool, 'msg' => string]
 */
function enqueue_scrape($entityType, $entityId, $type, $url, $priority = 5)
{
    $CI =& get_instance();
    $CI->load->library('scrapingbot');

    $params = $CI->scrapingbot->buildScrapeParams($type, $url);
    if (!$params) {
        return ['status' => false, 'msg' => 'Invalid platform or URL'];
    }

    // Deduplicate: skip if already in queue (pending/submitted)
    $existing = $CI->db->select('id')
        ->where('entity_type', $entityType)
        ->where('entity_id', $entityId)
        ->where_in('status', ['pending', 'submitted'])
        ->get('scraping_queue')
        ->num_rows();

    if ($existing > 0) {
        return ['status' => true, 'msg' => 'Already in queue'];
    }

    $CI->db->insert('scraping_queue', [
        'entity_type' => $entityType,
        'entity_id'   => $entityId,
        'scraper'     => $params['scraper'],
        'scrape_url'  => json_encode($params['params']),
        'status'      => 'pending',
        'priority'    => $priority,
        'created_at'  => date('Y-m-d H:i:s'),
    ]);

    return ['status' => true, 'msg' => 'Added to queue'];
}
```

### 4b. Response Parsers

These parse raw ScrapingBot responses into a normalized structure. **Customize** the field mappings to match what your app stores.

```php
/**
 * Parse ScrapingBot tiktokProfile response
 *
 * @param array $data  Raw response data (first element of the array returned by pollResult)
 * @return array ['profile' => [...], 'posts' => [...]]
 */
function parseTiktokProfileResponse($data)
{
    $result = [
        'profile' => [
            'account_id'  => '',
            'follower'    => 0,
            'media_count' => 0,
            'img'         => '',
            'full_name'   => '',
        ],
        'posts' => [],
    ];

    if (empty($data)) {
        return $result;
    }

    // ScrapingBot may return a flat array of video objects with embedded profile info
    // Save the full array as posts BEFORE unwrapping to a single item
    $allPosts = [];
    if (isset($data[0]) && is_array($data[0])) {
        $allPosts = $data;

        $profileItem = null;
        foreach ($data as $item) {
            if (is_array($item) && ($item['type'] ?? null) === 'profile') {
                $profileItem = $item;
                break;
            }
        }
        $data = $profileItem ?? $data[0];
    }

    // Profile info - handles multiple possible field names from ScrapingBot
    $result['profile']['account_id']  = strval($data['sec_uid'] ?? ($data['id'] ?? ''));
    $result['profile']['follower']    = intval($data['follower_count'] ?? ($data['followers'] ?? 0));
    $result['profile']['media_count'] = intval($data['videos_count'] ?? ($data['video_count'] ?? 0));
    $result['profile']['img']         = strval($data['avatar'] ?? ($data['avatar_thumb'] ?? ''));
    $result['profile']['full_name']   = strval($data['nickname'] ?? ($data['unique_id'] ?? ''));

    // Video stats — use saved $allPosts as fallback for flat array format
    $videos = $data['top_videos'] ?? ($data['videos'] ?? ($allPosts ?: []));
    $videos = array_slice($videos, 0, 10);

    foreach ($videos as $k => $v) {
        $result['posts'][$k] = [
            'like'    => intval($v['diggCount'] ?? ($v['likes'] ?? 0)),
            'share'   => intval($v['shareCount'] ?? ($v['shares'] ?? 0)),
            'comment' => intval($v['commentCount'] ?? ($v['comments'] ?? 0)),
            'collect' => intval($v['collectCount'] ?? ($v['saves'] ?? 0)),
            'view'    => intval($v['playCount'] ?? ($v['views'] ?? 0)),
        ];
    }

    return $result;
}

/**
 * Parse ScrapingBot instagramProfile response
 *
 * GOTCHA: ScrapingBot returns a FLAT ARRAY of post objects with embedded profile info,
 * NOT a profile object with a nested posts array. Each post object contains fields like
 * `author_id`, `profile_name`, `followers`, `likes`, `comments`, etc.
 *
 * @param array $data  Raw response data
 * @return array ['profile' => [...], 'posts' => [...]]
 */
function parseInstagramProfileResponse($data)
{
    $result = [
        'profile' => [
            'account_id'  => '',
            'follower'    => 0,
            'media_count' => 0,
            'img'         => '',
            'full_name'   => '',
        ],
        'posts' => [],
    ];

    if (empty($data)) {
        return $result;
    }

    // ScrapingBot returns a flat array of post objects with embedded profile info
    // Save the full array as posts BEFORE unwrapping to a single item
    $allPosts = [];
    if (isset($data[0]) && is_array($data[0])) {
        $allPosts = $data;

        $profileItem = null;
        foreach ($data as $item) {
            if (is_array($item) && ($item['type'] ?? null) === 'profile') {
                $profileItem = $item;
                break;
            }
        }
        $data = $profileItem ?? $data[0];
    }

    // Parse profile info — check ScrapingBot field names first, then legacy names
    $result['profile']['account_id']  = strval($data['author_id'] ?? ($data['id'] ?? ($data['pk'] ?? '')));
    $result['profile']['follower']    = intval($data['follower_count'] ?? ($data['followers'] ?? 0));
    $result['profile']['media_count'] = intval($data['posts_count'] ?? ($data['post_count'] ?? ($data['media_count'] ?? 0)));
    $result['profile']['img']         = strval($data['profile_image_link'] ?? ($data['profile_picture'] ?? ($data['profile_pic_url'] ?? '')));
    $result['profile']['full_name']   = strval($data['profile_name'] ?? ($data['full_name'] ?? ($data['username'] ?? '')));

    // Post stats — use saved $allPosts as fallback for flat array format
    $posts = $data['posts'] ?? ($data['edge_owner_to_timeline_media']['edges'] ?? ($allPosts ?: []));
    $posts = array_slice($posts, 0, 12);

    foreach ($posts as $k => $v) {
        $node = $v['node'] ?? $v; // Handle nested edge format

        $result['posts'][$k] = [
            'like'    => intval($node['like_count'] ?? ($node['edge_media_preview_like']['count'] ?? ($node['likes'] ?? 0))),
            'share'   => 0, // Instagram doesn't expose share count
            'comment' => intval($node['comment_count'] ?? ($node['edge_media_to_comment']['count'] ?? ($node['comments'] ?? 0))),
            'collect' => 0, // Instagram doesn't expose save count publicly
            'view'    => intval($node['video_view_count'] ?? ($node['views'] ?? 0)),
        ];
    }

    return $result;
}
```

### 4c. `process_scrape_result()` - Update entity with parsed data

This is the function that takes a completed queue item and updates your database. **This is the most app-specific part** - customize the table names, column names, and metric calculations to match your schema.

```php
/**
 * Process a completed scraping queue result and update the entity
 *
 * CUSTOMIZE: Change table names, column names, and metrics to match your app.
 *
 * @param array $queueItem  The scraping_queue row
 * @param array $resultData Parsed ScrapingBot response data
 * @return bool
 */
function process_scrape_result($queueItem, $resultData)
{
    $CI =& get_instance();

    $entityType = $queueItem['entity_type'];
    $entityId = $queueItem['entity_id'];
    $scraper = $queueItem['scraper'];

    // Parse the response based on scraper type
    if ($scraper === 'tiktokProfile') {
        $parsed = $this->parseTiktokProfileResponse($resultData);
    } elseif ($scraper === 'instagramProfile') {
        $parsed = $this->parseInstagramProfileResponse($resultData);
    } else {
        return false;
    }

    // CUSTOMIZE: Map entity_type to your actual table name
    $table = $entityType; // e.g. 'influencer', 'kol', etc.

    // Guard: don't overwrite good data with empty/zero results
    if (empty($parsed['profile']['account_id']) && $parsed['profile']['follower'] <= 0) {
        log_message('error', "ScrapingBot: Empty parse result for {$entityType}#{$entityId}, skipping update");
        return false;
    }

    // Update profile data
    $profileUpdate = [
        'account_id'  => $parsed['profile']['account_id'],
        'img'         => $parsed['profile']['img'],
        'follower'    => $parsed['profile']['follower'],
        'media_count' => $parsed['profile']['media_count'],
        'updated_at'  => date('Y-m-d H:i:s'),
        'updated_by'  => '1', // system user
    ];

    if (!empty($parsed['profile']['full_name'])) {
        $profileUpdate['full_name'] = $parsed['profile']['full_name'];
    }

    $CI->db->update($table, $profileUpdate, ['id' => $entityId]);

    // Calculate engagement metrics from posts
    if (!empty($parsed['posts'])) {
        $like = $comment = $collect = $share = $view = 0;
        $i = 0;

        foreach ($parsed['posts'] as $post) {
            $like    += $post['like'];
            $comment += $post['comment'];
            $collect += $post['collect'];
            $share   += $post['share'];
            $view    += $post['view'];
            $i++;
            if ($i >= 10) break;
        }

        $avg_view = $i ? $view / $i : 0;
        $avg_interaksi = $i ? ($like + $comment + $collect + $share) / $i : 0;
        $er = ($avg_view > 0) ? ($avg_interaksi / $avg_view * 100) : 0;

        // CUSTOMIZE: If you have a ratecard field, calculate CPM
        $record = $CI->db->select('ratecard')->where('id', $entityId)->get($table)->row_array();
        $ratecard = floatval($record['ratecard'] ?? 0);
        $cpm = ($ratecard > 0 && $avg_view > 0) ? ($ratecard / $avg_view * 1000) : 0;

        // CUSTOMIZE: Change these column names to match your schema
        $metricsUpdate = [
            'sync_at'          => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
            'updated_by'       => '1',
            'frequency_2'      => $i,
            'view_2'           => $view,
            'like_2'           => $like,
            'collect_2'        => $collect,
            'share_2'          => $share,
            'comment_2'        => $comment,
            'avg_view_2'       => $avg_view,
            'avg_interaksi_2'  => $avg_interaksi,
            'er'               => $er,
            'cpm_2'            => $cpm,
        ];

        $CI->db->update($table, $metricsUpdate, ['id' => $entityId]);

        // CUSTOMIZE: Optional - log historical data to a separate table
        // Example: influencer_logs table for daily snapshots
        // $today = date('Y-m-d');
        // $existing = $CI->db->select('id')
        //     ->where('id_influencer', $entityId)
        //     ->where('DATE(date)', $today)
        //     ->get('influencer_logs')
        //     ->row_array();
        // if ($existing) {
        //     $CI->db->update('influencer_logs', $logData, ['id' => $existing['id']]);
        // } else {
        //     $CI->db->insert('influencer_logs', $logData);
        // }
    }

    return true;
}
```

---

## Step 5: Add Cronjob Methods to Your API Controller

Add these three methods to your API controller (or create a dedicated `Cronjob` controller).

### 5a. Submit Cronjob (runs every 5 minutes)

Picks `pending` items from the queue, POSTs them to ScrapingBot, and stores the `responseId`.

```php
/**
 * Cronjob A - Submit pending scrape jobs to ScrapingBot
 * Recommended: every 5 minutes
 * Picks up to 5 pending items per run (ordered by priority)
 */
function cronjob_scraping_submit()
{
    header('Content-Type: application/json; charset=utf-8');

    $this->load->library('scrapingbot');
    $this->load->model('mymodel');

    // Pick pending items ordered by priority (highest first), then by age (oldest first)
    $items = $this->mymodel->selectWithQuery("
        SELECT * FROM scraping_queue
        WHERE status = 'pending'
        ORDER BY priority DESC, created_at ASC
        LIMIT 5
    ");

    $submitted = 0;
    $errors = [];

    foreach ($items as $item) {
        $params = json_decode($item['scrape_url'], true);
        if (!$params) {
            $this->db->update('scraping_queue', [
                'status'        => 'failed',
                'error_message' => 'Invalid scrape_url JSON',
                'completed_at'  => date('Y-m-d H:i:s'),
            ], ['id' => $item['id']]);
            $errors[] = "Item #{$item['id']}: invalid params";
            continue;
        }

        $result = $this->scrapingbot->startScrape($item['scraper'], $params);

        if ($result['status'] && !empty($result['responseId'])) {
            $this->db->update('scraping_queue', [
                'status'       => 'submitted',
                'response_id'  => $result['responseId'],
                'submitted_at' => date('Y-m-d H:i:s'),
            ], ['id' => $item['id']]);
            $submitted++;
        } else {
            $attempts = intval($item['attempts']) + 1;
            $newStatus = ($attempts >= intval($item['max_attempts'])) ? 'failed' : 'pending';

            $this->db->update('scraping_queue', [
                'attempts'      => $attempts,
                'status'        => $newStatus,
                'error_message' => $result['msg'],
                'completed_at'  => ($newStatus === 'failed') ? date('Y-m-d H:i:s') : null,
            ], ['id' => $item['id']]);
            $errors[] = "Item #{$item['id']}: " . $result['msg'];
        }
    }

    echo json_encode([
        'status'    => true,
        'submitted' => $submitted,
        'total'     => count($items),
        'errors'    => $errors,
        'msg'       => "$submitted of " . count($items) . " items submitted to ScrapingBot",
    ]);
    die;
}
```

### 5b. Poll Cronjob (runs every 1 minute)

Picks `submitted` items, GETs results from ScrapingBot, and processes completed ones.

```php
/**
 * Cronjob B - Poll submitted scrape jobs for results
 * Recommended: every 1 minute
 * Checks up to 10 submitted items per run
 */
function cronjob_scraping_poll()
{
    header('Content-Type: application/json; charset=utf-8');

    $this->load->library('scrapingbot');
    $this->load->model('mymodel');

    $items = $this->mymodel->selectWithQuery("
        SELECT * FROM scraping_queue
        WHERE status = 'submitted'
        AND attempts < max_attempts
        ORDER BY submitted_at ASC
        LIMIT 10
    ");

    $completed = 0;
    $pending = 0;
    $failed = 0;

    foreach ($items as $item) {
        $result = $this->scrapingbot->pollResult($item['scraper'], $item['response_id']);

        if ($result['status'] === 'success') {
            // Store raw result and mark completed
            $this->db->update('scraping_queue', [
                'status'       => 'completed',
                'result_data'  => json_encode($result['data']),
                'completed_at' => date('Y-m-d H:i:s'),
                'attempts'     => intval($item['attempts']) + 1,
            ], ['id' => $item['id']]);

            // Process the result and update the entity
            // CUSTOMIZE: Replace with your helper/library call
            $this->template->process_scrape_result($item, $result['data']);

            $completed++;

        } elseif ($result['status'] === 'pending') {
            // Still processing - increment attempts
            $this->db->update('scraping_queue', [
                'attempts' => intval($item['attempts']) + 1,
            ], ['id' => $item['id']]);
            $pending++;

        } else {
            // Error
            $attempts = intval($item['attempts']) + 1;
            $newStatus = ($attempts >= intval($item['max_attempts'])) ? 'failed' : 'submitted';

            $this->db->update('scraping_queue', [
                'attempts'      => $attempts,
                'status'        => $newStatus,
                'error_message' => $result['msg'],
                'completed_at'  => ($newStatus === 'failed') ? date('Y-m-d H:i:s') : null,
            ], ['id' => $item['id']]);
            $failed++;
        }
    }

    echo json_encode([
        'status'    => true,
        'completed' => $completed,
        'pending'   => $pending,
        'failed'    => $failed,
        'total'     => count($items),
        'msg'       => "Poll results: $completed completed, $pending pending, $failed failed",
    ]);
    die;
}
```

### 5c. Auto-Enqueue Cronjob (runs every 30 minutes)

Finds entities needing a refresh and inserts them into the queue with priority tiers.

> **Note:** TikTok records are excluded (`AND type != 'Tiktok'`) — they are handled by the separate `cronjob_tiktok_sync` which calls RapidAPI synchronously.

```php
/**
 * Cronjob C - Auto-enqueue entities needing sync (Instagram only)
 * Recommended: every 30 minutes
 * Priority tiers ensure hot/new records are processed first
 * TikTok is excluded — handled by cronjob_tiktok_sync via RapidAPI
 *
 * CUSTOMIZE: Change the SQL queries to match your tables and business logic.
 */
function cronjob_scraping_enqueue()
{
    header('Content-Type: application/json; charset=utf-8');

    $this->load->model('mymodel');

    $enqueued = 0;
    $skipped = 0;

    // Tier 1 (Priority 10): Never synced - new records
    // TikTok excluded — handled by cronjob_tiktok_sync
    $tier1 = $this->mymodel->selectWithQuery("
        SELECT id, type, url FROM influencer
        WHERE status = 'Aktif' AND url != ''
        AND type != 'Tiktok'
        AND sync_at IS NULL
        LIMIT 20
    ");
    foreach ($tier1 as $row) {
        $result = $this->template->enqueue_scrape('influencer', $row['id'], $row['type'], $row['url'], 10);
        if ($result['status']) $enqueued++; else $skipped++;
    }

    // Tier 2 (Priority 7): Has active campaign - every 3 days
    $three_days_ago = date('Y-m-d', strtotime('-3 days'));
    $tier2 = $this->mymodel->selectWithQuery("
        SELECT DISTINCT i.id, i.type, i.url FROM influencer i
        INNER JOIN endorse e ON e.influencer = i.id
        INNER JOIN endorse_campaign ec ON e.id_campaign = ec.id
        WHERE i.status = 'Aktif' AND i.url != ''
        AND i.type != 'Tiktok'
        AND ec.status = 'Aktif'
        AND (DATE(i.sync_at) <= '$three_days_ago' OR i.sync_at IS NULL)
        LIMIT 20
    ");
    foreach ($tier2 as $row) {
        $result = $this->template->enqueue_scrape('influencer', $row['id'], $row['type'], $row['url'], 7);
        if ($result['status']) $enqueued++; else $skipped++;
    }

    // Tier 3 (Priority 5): Regular - synced > 7 days ago
    $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
    $tier3 = $this->mymodel->selectWithQuery("
        SELECT id, type, url FROM influencer
        WHERE status = 'Aktif' AND url != ''
        AND type != 'Tiktok'
        AND DATE(sync_at) <= '$seven_days_ago'
        LIMIT 10
    ");
    foreach ($tier3 as $row) {
        $result = $this->template->enqueue_scrape('influencer', $row['id'], $row['type'], $row['url'], 5);
        if ($result['status']) $enqueued++; else $skipped++;
    }

    // Tier 4 (Priority 3): Cold - synced > 14 days ago
    $fourteen_days_ago = date('Y-m-d', strtotime('-14 days'));
    $tier4 = $this->mymodel->selectWithQuery("
        SELECT id, type, url FROM influencer
        WHERE status = 'Aktif' AND url != ''
        AND type != 'Tiktok'
        AND DATE(sync_at) <= '$fourteen_days_ago'
        LIMIT 5
    ");
    foreach ($tier4 as $row) {
        $result = $this->template->enqueue_scrape('influencer', $row['id'], $row['type'], $row['url'], 3);
        if ($result['status']) $enqueued++; else $skipped++;
    }

    echo json_encode([
        'status'   => true,
        'enqueued' => $enqueued,
        'skipped'  => $skipped,
        'msg'      => "$enqueued records enqueued, $skipped skipped (already in queue or invalid)",
    ]);
    die;
}
```

---

## Step 6: Add Routes and Crontab

### 6a. Routes

Add to `application/config/routes.php`:

```php
// ScrapingBot cronjobs (Instagram async queue)
$route['api/cronjob/scraping-submit']  = 'Api_v2/cronjob_scraping_submit';  // CUSTOMIZE: your controller name
$route['api/cronjob/scraping-poll']    = 'Api_v2/cronjob_scraping_poll';
$route['api/cronjob/scraping-enqueue'] = 'Api_v2/cronjob_scraping_enqueue';

// TikTok sync cronjob (RapidAPI synchronous)
$route['api/cronjob/tiktok-sync']      = 'Api_v2/cronjob_tiktok_sync';
```

### 6b. Crontab

Add to your server's crontab (`crontab -e`):

```cron
# ScrapingBot (Instagram): Poll for results (every 1 minute)
* * * * * curl -s -X GET "https://YOUR_DOMAIN/api/cronjob/scraping-poll" >> /var/log/cron/scraping-poll.log 2>&1

# ScrapingBot (Instagram): Submit pending jobs (every 5 minutes)
*/5 * * * * curl -s -X GET "https://YOUR_DOMAIN/api/cronjob/scraping-submit" >> /var/log/cron/scraping-submit.log 2>&1

# ScrapingBot (Instagram): Auto-enqueue stale records (every 30 minutes)
0,30 * * * * curl -s -X GET "https://YOUR_DOMAIN/api/cronjob/scraping-enqueue" >> /var/log/cron/scraping-enqueue.log 2>&1

# RapidAPI (TikTok): Synchronous auto-sync (every 10 minutes)
*/10 * * * * curl -s -X GET "https://YOUR_DOMAIN/api/cronjob/tiktok-sync" >> /var/log/cron/tiktok-sync.log 2>&1
```

Create the log directory: `sudo mkdir -p /var/log/cron`

---

## Step 7: Wire Up Controllers — Bifurcate by Platform

From any controller, **bifurcate by platform type**: TikTok uses `syncTiktokProfile()` for immediate results, Instagram uses `enqueue_scrape()` for async processing.

**Important:** For manual refresh actions, always update **internal metrics** (endorse aggregation from your local DB) synchronously first. For TikTok, the external scrape is also synchronous. For Instagram, enqueue the async external scrape and use a two-part success message.

```php
// Example: Manual refresh from a "Sync" button (platform bifurcation)
public function sync_process()
{
    $id = $_POST['id'];
    $record = $this->db->where('id', $id)->get('influencer')->row_array();
    if (!$record) {
        echo $this->template->alert_danger('Record not found');
        die;
    }

    // Phase 1: Update internal metrics synchronously from local DB (endorse aggregation)
    // ... (aggregate endorse data and update influencer table) ...

    // Phase 2: External scrape — bifurcate by platform
    if ($record['type'] == 'Tiktok') {
        // TikTok: Synchronous via RapidAPI — immediate result
        $result = $this->template->syncTiktokProfile('influencer', $id, $record['type'], $record['url']);
        if ($result['status']) {
            echo $this->template->alert_success('Data berhasil disinkronkan.');
        } else {
            echo $this->template->alert_danger($result['msg']);
        }
    } else {
        // Instagram: Async via ScrapingBot queue
        $result = $this->template->enqueue_scrape('influencer', $id, $record['type'], $record['url'], 10);
        if ($result['status']) {
            echo $this->template->alert_success('Data internal berhasil diperbarui. Data eksternal sedang diproses, akan diperbarui dalam beberapa menit.');
        } else {
            echo $this->template->alert_danger($result['msg']);
        }
    }
    die;
}

// Example: Bulk refresh (also bifurcated)
public function bulk_refresh()
{
    $ids = $this->input->post('ids');
    $processed = 0;

    foreach ($ids as $id) {
        $record = $this->db->where('id', $id)->get('influencer')->row_array();
        if (!$record || empty($record['url'])) continue;

        if ($record['type'] == 'Tiktok') {
            $result = $this->template->syncTiktokProfile('influencer', $record['id'], $record['type'], $record['url']);
        } else {
            $result = $this->template->enqueue_scrape('influencer', $record['id'], $record['type'], $record['url'], 10);
        }
        if ($result['status']) $processed++;
    }

    echo json_encode([
        'success' => true,
        'msg' => "$processed records processed/queued for sync"
    ]);
}
```

---

## Step 8: (Optional) Frontend Polling for Real-Time UI Updates

If you want the UI to show live sync status (spinner while processing, auto-update when done), add a polling endpoint and JavaScript.

### 8a. Controller: Queue Status Check

```php
/**
 * AJAX endpoint: check queue status for given entity IDs
 * Returns current queue status + updated data for completed items
 */
public function check_queue_status()
{
    header('Content-Type: application/json');

    $ids_param = $this->input->post('ids');
    if (empty($ids_param)) {
        echo json_encode(['status' => 'error', 'message' => 'No IDs provided']);
        return;
    }

    if (is_array($ids_param)) {
        $ids = array_map('intval', $ids_param);
    } else {
        $ids = array_map('intval', explode(',', $ids_param));
    }
    $ids = array_filter($ids);

    if (empty($ids)) {
        echo json_encode(['status' => 'error', 'message' => 'No valid IDs']);
        return;
    }

    // Get latest queue entry for each entity_id
    $placeholders = implode(',', $ids);
    // CUSTOMIZE: Change entity_type and target table to match your app
    $queue_results = $this->db->query("
        SELECT sq1.entity_id, sq1.status, sq1.error_message
        FROM scraping_queue sq1
        INNER JOIN (
            SELECT entity_id, MAX(id) as max_id
            FROM scraping_queue
            WHERE entity_type = 'influencer'
            AND entity_id IN ($placeholders)
            GROUP BY entity_id
        ) sq2 ON sq1.id = sq2.max_id
    ")->result_array();

    $result = [];
    $completed_ids = [];

    foreach ($queue_results as $row) {
        $eid = $row['entity_id'];
        $result[$eid] = [
            'queue_status' => $row['status'],
            'error_message' => $row['error_message'] ?? null,
        ];
        if ($row['status'] === 'completed') {
            $completed_ids[] = $eid;
        }
    }

    // For completed items, fetch updated data
    if (!empty($completed_ids)) {
        $this->db->where_in('id', $completed_ids);
        // CUSTOMIZE: Change table name and returned fields
        $records = $this->db->get('influencer')->result_array();

        foreach ($records as $rec) {
            $id = $rec['id'];
            $result[$id]['data'] = [
                'follower' => (int)($rec['follower'] ?? 0),
                'cpm'      => floatval($rec['cpm_2'] ?? 0),
                'avg_view' => floatval($rec['avg_view_2'] ?? 0),
                'er'       => floatval($rec['er'] ?? 0),
            ];
        }
    }

    echo json_encode(['status' => 'success', 'results' => $result]);
}
```

### 8b. Frontend JavaScript (jQuery)

```javascript
// Track which entity IDs are currently being synced
let pendingRefreshIds = new Set();
let pollerInterval = null;

function startPoller() {
    if (pollerInterval) return;
    pollerInterval = setInterval(pollQueueStatus, 15000); // Poll every 15 seconds
}

function stopPoller() {
    if (pollerInterval) {
        clearInterval(pollerInterval);
        pollerInterval = null;
    }
}

/**
 * Call this when user clicks "Sync" button
 */
function triggerSync(entityId) {
    // Add to pending set and start poller
    pendingRefreshIds.add(String(entityId));
    startPoller();

    // Show syncing indicator in UI
    // CUSTOMIZE: Update selector to match your table structure
    const row = $(`tr[data-id="${entityId}"]`);
    row.find('.engagement-cell').append(
        '<div class="queue-status-display"><span class="badge bg-warning">Syncing...</span></div>'
    );

    // Trigger the enqueue via AJAX
    $.post('/your_controller/refresh_data/' + entityId, function(response) {
        if (!response.success) {
            pendingRefreshIds.delete(String(entityId));
            row.find('.queue-status-display').remove();
            alert('Failed: ' + response.msg);
        }
    }, 'json');
}

/**
 * Polls the server for queue status updates
 */
function pollQueueStatus() {
    if (pendingRefreshIds.size === 0) {
        stopPoller();
        return;
    }

    const ids = Array.from(pendingRefreshIds).join(',');

    $.ajax({
        // CUSTOMIZE: Change URL to your check_queue_status endpoint
        url: '/your_controller/check_queue_status',
        type: 'POST',
        data: { ids: ids },
        dataType: 'json',
        success: function(response) {
            if (response.status !== 'success' || !response.results) return;

            $.each(response.results, function(entityId, info) {
                const row = $(`tr[data-id="${entityId}"]`);
                if (row.length === 0) return;

                if (info.queue_status === 'completed') {
                    pendingRefreshIds.delete(String(entityId));

                    // Remove syncing indicator
                    row.find('.queue-status-display').remove();

                    // Update UI with new data
                    if (info.data) {
                        // CUSTOMIZE: Update your table cells with the new values
                        row.find('.follower-count').text(info.data.follower.toLocaleString());
                        row.find('.cpm-value').text(info.data.cpm.toFixed(2));
                        row.find('.avg-view-value').text(info.data.avg_view.toFixed(0));
                        row.find('.er-value').text(info.data.er.toFixed(2) + '%');
                    }

                    // Flash success briefly
                    row.find('.engagement-cell').append(
                        '<div class="queue-status-display"><span class="badge bg-success">Updated!</span></div>'
                    );
                    setTimeout(function() {
                        row.find('.queue-status-display').remove();
                    }, 3000);

                } else if (info.queue_status === 'failed') {
                    pendingRefreshIds.delete(String(entityId));
                    row.find('.queue-status-display').html(
                        '<span class="badge bg-danger">Sync failed</span>'
                    );
                }
                // pending/submitted: keep showing syncing badge
            });

            if (pendingRefreshIds.size === 0) {
                stopPoller();
            }
        }
    });
}
```

---

## Gotchas and Lessons Learned

These were discovered during the original implementation and will save you debugging time:

### 1. Instagram `instagramProfile` returns a flat array of posts, not a profile object

ScrapingBot's `instagramProfile` response is a **flat array of post objects** where each post embeds profile info (followers, profile_name, author_id, etc.) — NOT a profile object with a nested posts array.

```json
[
  {"account":"rnldyaldy", "author_id":"2190045068", "profile_name":"Renaldi", "followers":4339, "posts_count":14, "id":"3816455773333557313", "likes":107, "comments":2, "media_type":"Carousel", ...},
  {"account":"rnldyaldy", "author_id":"2190045068", ..., "id":"3811900217033829074", "likes":140, "comments":13, ...},
  ...
]
```

**Key field name differences from legacy APIs:**

| ScrapingBot field | Legacy field | Notes |
|---|---|---|
| `author_id` | `id`, `pk` | User ID; `id` in flat format is the POST id |
| `profile_image_link` | `profile_picture`, `profile_pic_url` | Avatar URL |
| `profile_name` | `full_name`, `username` | Display name |
| `posts_count` | `post_count`, `media_count` | Total posts |

**Parser must:** Save the full array as `$allPosts` before unwrapping `$data[0]`, then use `$allPosts` as fallback when looking for posts. Check `author_id` before `id` for account_id to avoid getting the post ID instead of the user ID.

### 2. `pollResult()` returns a raw array on success

The `data-scraper-response` endpoint returns a **raw array** `[{...}]` on success, NOT a wrapped `{"status":"success","response":[...]}`.

**Wrong:**
```php
if (isset($data['status']) && $data['status'] === 'success') { ... }
```

**Correct:**
```php
// Check for raw array FIRST
if (isset($data[0]) && !isset($data['status'])) {
    // This is the success case
}
```

### 3. Error responses can be disguised as success arrays

ScrapingBot sometimes returns HTTP 200 with a raw array that looks like a success but contains an error, e.g. `[{"message":"Something went wrong","top_videos":[]}]`. This passes the `isset($data[0])` check but has no actual profile data.

**Always check for a `message` key before treating raw arrays as success:**
```php
$firstItem = $data[0];
if (isset($firstItem['message']) && ($firstItem['type'] ?? null) !== 'profile') {
    // This is an error, not a valid profile response
    return ['status' => 'error', ...];
}
```

Without this check, the item gets marked `completed` with bad data, and the parser writes zeros to the entity table.

### 4. TikTok: Now uses RapidAPI (not ScrapingBot)

> **Updated Feb 2026:** TikTok was moved back to RapidAPI for synchronous responses. The ScrapingBot `tiktokProfile` scraper is no longer used. TikTok records are filtered out of `cronjob_scraping_enqueue` with `AND type != 'Tiktok'`.

If you still have legacy `tiktokProfile` items in `scraping_queue`, they will still be processed by the parsers. But new TikTok syncs go through `Template::syncTiktokProfile()` → RapidAPI directly.

For the ScrapingBot `tiktokProfile` scraper (legacy reference): it only accepts `url`, not `max_video_count` (that param is for `tiktokHashtag`).

### 5. Instagram: `account`, not `username`

The `instagramProfile` scraper uses `account` as the parameter name, not `username`.

**Wrong:**
```php
$this->startScrape('instagramProfile', [
    'username' => 'someuser',  // WRONG key name
]);
```

**Correct:**
```php
$this->startScrape('instagramProfile', [
    'account' => 'someuser',  // Correct
    'posts_number' => 12,
]);
```

### 6. Environment variable: `DB_HOSTNAME`, not `DB_HOST`

If your CodeIgniter app uses the env_helper pattern, the database host key is `DB_HOSTNAME` (not `DB_HOST`). Check your `application/config/database.php` to verify.

### 7. `scrape_url` stores JSON params, not the actual URL

The `scrape_url` column in `scraping_queue` stores the **JSON-encoded API parameters**, not the raw profile URL. For TikTok it looks like `{"url":"https://www.tiktok.com/@username"}`, for Instagram it looks like `{"account":"username","posts_number":12}`.

### 8. Guard against zero-data overwrites

`process_scrape_result()` must validate parsed data before updating the entity. If the parser returns empty/zero values (e.g. due to an unexpected response format), the update would overwrite good data with zeros.

**Always check before writing:**
```php
if (empty($parsed['profile']['account_id']) && $parsed['profile']['follower'] <= 0) {
    log_message('error', "ScrapingBot: Empty parse result, skipping update");
    return false;
}
```

This allows legitimate zero-follower profiles (which would at least have an `account_id`) while preventing destructive zero-overwrites from parse failures.

### 9. Deduplication is key

The `enqueue_scrape()` function checks for existing `pending`/`submitted` items before inserting. Without this, rapid button clicks or overlapping cronjob runs will create duplicate queue entries and waste API calls.

### 10. Budget awareness

ScrapingBot charges per API call. With the recommended cron intervals:
- **Submit** runs every 5 min, processing up to 5 items = ~1,440 submits/day max
- **Poll** runs every 1 min, processing up to 10 items = ~14,400 polls/day max
- Each scrape = 1 submit + N polls (typically 1-3)
- Budget accordingly based on your plan (e.g., 100K calls/month)

### 11. TikTok `get_social_media()` now uses RapidAPI `/api/post/detail`

> **Updated May 2026:** Follow `TIKTOK_INTEGRATION_SPEC.md` as the source of truth. The live runtime now uses direct TikTok HTML scrape first, then RapidAPI `getVideoInfo` fallback on `tiktok-video-no-watermark10`.

The `videoId` is extracted from URLs using regex patterns (priority order):
1. `/video/(\d+)` — standard video URL
2. `/photo/(\d+)` — photo/slideshow URL
3. `(\d{10,25})` — fallback for any long numeric ID

**Note:** Photo/slideshow posts still have `playCount = 0` from the API response. The code checks for `isset($itemStruct['stats'])` (not `playCount > 0`) so photo posts are handled correctly.

### 12. Platform-aware refresh: TikTok is fully synchronous, Instagram is two-phase

When a user clicks "Refresh", the behavior differs by platform:

**TikTok (synchronous):**
1. Aggregate internal data from local DB (endorse totals)
2. Call `syncTiktokProfile()` which hits RapidAPI synchronously
3. Both internal + external data are updated immediately
4. Show: "Data berhasil disinkronkan."

**Instagram (two-phase):**
1. **Phase 1 (sync):** Aggregate internal data from local DB and write immediately
2. **Phase 2 (async):** Enqueue the ScrapingBot job for external data
3. Show: "Data internal berhasil diperbarui. Data eksternal sedang diproses, akan diperbarui dalam beberapa menit."

**Common mistake:** Only writing aggregate columns like `avg_view` and `cpm` but forgetting the raw totals (`view`, `like`, `comment`, `share`). If your UI displays these raw columns, they'll stay stale even though the data is already available in your local endorse table.

---

## Summary Checklist

### ScrapingBot (Instagram async queue)
- [ ] Create `scraping_queue` table (Step 1)
- [ ] Add `SCRAPINGBOT_*` env vars (Step 2)
- [ ] Create `application/libraries/Scrapingbot.php` (Step 3)
- [ ] Add `enqueue_scrape()`, parsers, and `process_scrape_result()` to your helpers (Step 4)
- [ ] Add 3 ScrapingBot cronjob methods to your API controller (Step 5)
- [ ] Add routes for the 3 ScrapingBot cronjob endpoints (Step 6a)
- [ ] Configure crontab on your server (Step 6b)
- [ ] Test Instagram flow: enqueue -> submit -> poll -> entity updated

### RapidAPI (TikTok synchronous)
- [ ] Add `RAPIDAPI_HOST` and `RAPIDAPI_KEY` env vars (Step 2)
- [ ] Add RapidAPI methods to Template.php: `curlRequestWithRetry()`, `getRapidApiHeaders()`, `getDataFromFirstEndpoint()`, `getDataFromSearchEndpoint()`, `syncTiktokProfile()`
- [ ] Update `get_account_id()`, `get_post_list()`, `get_social_media()` to use RapidAPI for TikTok
- [ ] Add `cronjob_tiktok_sync` method and route
- [ ] Filter TikTok from `cronjob_scraping_enqueue` (`AND type != 'Tiktok'`)
- [ ] Test TikTok manual refresh: immediate data returned (no "sedang diproses" message)

### Controller bifurcation
- [ ] Bifurcate all sync/refresh controllers: TikTok → `syncTiktokProfile()`, Instagram → `enqueue_scrape()` (Step 7)
- [ ] (Optional) Add frontend polling for Instagram live UI updates (Step 8)
