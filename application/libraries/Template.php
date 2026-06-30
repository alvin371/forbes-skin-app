<?php

class Template
{
    public function index() {}

    function endpoint_url()
    {
        // return 'https://endpoint.acnenosystem.com/';
        $endpoint = env('ENDPOINT_URL', '');
        if ($endpoint) {
            return rtrim($endpoint, '/') . '/';
        }
        return base_url();
    }

    function hex_to_rgb($hex, $opacity = 1)
    {
        // Remove '#' if present
        $hex = str_replace('#', '', $hex);

        // Extract RGB components
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Ensure opacity value is within range [0, 1]
        $opacity = max(0, min(1, $opacity));

        // Construct RGBA string
        $rgba = "rgba($r, $g, $b, $opacity)";

        return $rgba;
    }

    function generateNumber($length = 12)
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomCode = '';

        for ($i = 0; $i < $length; $i++) {
            $randomCode .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomCode;
    }

    function get_param()
    {
        $query_string = $_SERVER['QUERY_STRING'];
        parse_str($query_string, $params);
        // unset($params['page']);
        $new_query_string = http_build_query($params);
        return '?' . $new_query_string;
    }
    function get_param_without($column)
    {
        $query_string = $_SERVER['QUERY_STRING'];
        parse_str($query_string, $params);
        unset($params['page']);
        unset($params[$column]);
        $new_query_string = http_build_query($params);
        return '?' . $new_query_string;
    }
    function get_param_without_page()
    {
        $query_string = $_SERVER['QUERY_STRING'];
        parse_str($query_string, $params);
        unset($params['page']);
        $new_query_string = http_build_query($params);
        return '?' . $new_query_string;
    }
    function get_param_without_order_status()
    {
        $query_string = $_SERVER['QUERY_STRING'];
        parse_str($query_string, $params);
        unset($params['order_status']);
        unset($params['page']);
        $new_query_string = http_build_query($params);
        return '?' . $new_query_string;
    }
    function get_param_without_status()
    {
        $query_string = $_SERVER['QUERY_STRING'];
        parse_str($query_string, $params);
        unset($params['status']);
        unset($params['page']);
        $new_query_string = http_build_query($params);
        return '?' . $new_query_string;
    }
    function get_param_without_keyword_category()
    {
        $query_string = $_SERVER['QUERY_STRING'];
        parse_str($query_string, $params);
        unset($params['keyword_category']);
        unset($params['page']);
        $new_query_string = http_build_query($params);
        return '?' . $new_query_string;
    }
    function month_format_indo($date)
    {
        $month = array(
            '',
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
        $date = $month[intval(DATE('m', strtotime($date)))];
        return $date;
    }
    function date_format_indo($date)
    {
        $month = array(
            '',
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
        if ($date) {
            $date = DATE('d', strtotime($date)) . ' ' . substr($month[intval(DATE('m', strtotime($date)))], 0, 3) . ' ' . DATE('Y', strtotime($date));
        } else {
            $date = '';
        }
        return $date;
    }
    function date_format_indo_with_time($date)
    {
        $month = array(
            '',
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
        if ($date) {
            $date = DATE('d', strtotime($date)) . ' ' . substr($month[intval(DATE('m', strtotime($date)))], 0, 3) . ' ' . DATE('Y', strtotime($date)) . ' ' . DATE('H:i', strtotime($date));
        } else {
            $date = '';
        }
        return $date;
    }
    function api_key_ss()
    {
        return 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI3IiwianRpIjoiNDZmNzZmMjQ2MGU0MjMwY2Q4MzZhNTIxOWMzMjNiMjFhMGVlNjUyODFjMGI4MmMzZTZlN2UwMzIxNWM1OWJmNGEyMjcyNzBlNDdhMGRlMTQiLCJpYXQiOjE2OTA1OTgwODkuMzYxNTA0LCJuYmYiOjE2OTA1OTgwODkuMzYxNTA5LCJleHAiOjE3MjIyMjA0ODkuMzMzNjkxLCJzdWIiOiIxMTE1NzEiLCJzY29wZXMiOltdfQ.JXeGlDb5EawVXIFAD9Si-GWgWqv9OF5hFPU2lUUuY_9frcQm-5jfy198czITk3aQNjMTSkRLPooXr2q8_P8VO4m3iyP6l9GZdK_oE6ttGj4hI0cIJEwy9cmT77JLqLe1s0ROLRtMINGUwHEBIauSTFYZLd34BAd6bAC_QxcbFUUsvaOacVnrmv6SdSS6tThsioSH4lZ7IAaF9A7m1yEkt4rQqqrjZhANhE5aq8BoQXQh4pMYpqR4BuydcwSVZTBJg-L19q0jA9-CTgVKON_j0rfUtOx5etvZB_oqJkfs4bHzCfctnnFiasL30ZWp9TO9VtvgsWx72osNGMVwBzILu_TizvmZLwZJGkKWLlstwsmrb9ggdbT45NJVa_Qf7MwAQRwTmWJOdy8MdPGzcdBTLGI5mC_NTFToYWRP1-5ljmeM1lllG2e77rnnnYhtRCMYrpf2yIIsGzq25n1yrzfydu_k4-ledyRus9X0vSPyiiS61fZypBamXXx1oYps-euVGFmxw4N5Tl6LY-w5m4jKHHRUQ3Yq8IbBp5Pq-gZo_HvMq0PNUx_rag9ElVTCAYo19JOSGAg_faH9sE-E_fI9tb1QRncxYKU9E20VyAmuNj-emN6tK5svU4uHaigNses8AdMgMYmJLNdPsxVVksxVTBMGVn0fdzB1VkoUqyBciw0';
    }
    function api_orders_ss($dt)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.smartseller.co.id/api/open/order?start_date=' . $dt['start_date'] . '&end_date=' . $dt['until_date'] . '&per_page=100&pagination_offset=' . $dt['page'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->api_key_ss()
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    function api_products_ss($dt)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.smartseller.co.id/api/open/product?per_page=100&pagination_offset=' . $dt['page'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->api_key_ss()
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    function pagination($page, $current_page, $url)
    {
        if ($page > 0) {
            $page_limit = 4;
            if ($page < 5) {
                $page_limit = $page - 1;
            }

            $item .= '<a class="btn btn-pagination me-1" style="margin-bottom:4px" style="margin-bottom:10px" href="' . $url . '&page=1"><i class="bi bi-chevron-double-left"></i></a>';
            if ($current_page <= 1) {
                $prev_page = 1;
            } else {
                $prev_page = $current_page - 1;
            }
            $item .= '<a class="btn btn-pagination me-1" style="margin-bottom:4px" style="margin-bottom:10px" href="' . $url . '&page=' . ($prev_page) . '"><i class="bi bi-chevron-left"></i></a>';
            $start = $current_page - 2;
            $end = $current_page + 2;
            if ($end > $page) {
                $end = $page;
                $start = $end - $page_limit;
            }
            if ($start < 1) {
                $start = 1;
                $end = $start + $page_limit;
            }
            for ($i = $start; $i <= $end; $i++) {
                if ($current_page != $i) {
                    $class = 'btn-pagination';
                } else {
                    $class = 'btn-pagination-active';
                }
                $item .= '<a class="btn ' . $class . ' me-1" style="margin-bottom:4px" style="margin-bottom:10px" href="' . $url . '&page=' . ($i) . '">' . $i . '</a>';
            }
            $next_page = $current_page + 1;
            if ($next_page > $page) {
                $next_page = $page;
            }
            $item .= '<a class="btn btn-pagination me-1" style="margin-bottom:4px" style="margin-bottom:10px" href="' . $url . '&page=' . ($next_page) . '"><i class="bi bi-chevron-right"></i></a>';
            $item .= '<a class="btn btn-pagination me-1" style="margin-bottom:4px" style="margin-bottom:10px" href="' . $url . '&page=' . ($page) . '"><i class="bi bi-chevron-double-right"></i></a>';
        } else {
            $item .= '<div class="bg-danger p-3 br-10">Hasil pencarian tidak ditemukan, silahkan gunakan filter lain!</div>';
        }
        return '<div class="col-md-12">' . $item . '</div>';
    }

    function option_pagination($page, $current_page, $url)
    {
        if ($page > 0) {
            $page_limit = 4;
            if ($page < 5) {
                $page_limit = $page - 1;
            }

            $item = '<a class="btn btn-pagination me-1" href="' . $url . '&page=1&limit=' . $limit . '"><i class="bi bi-chevron-double-left"></i></a>';

            $prev_page = ($current_page <= 1) ? 1 : $current_page - 1;
            $item .= '<a class="btn btn-pagination me-1" href="' . $url . '&page=' . $prev_page . '&limit=' . $limit . '"><i class="bi bi-chevron-left"></i></a>';

            $start = max(1, $current_page - 2);
            $end = min($page, $current_page + 2);

            for ($i = $start; $i <= $end; $i++) {
                $class = ($current_page == $i) ? 'btn-pagination-active' : 'btn-pagination';
                $item .= '<a class="btn ' . $class . ' me-1" href="' . $url . '&page=' . $i . '&limit=' . $limit . '">' . $i . '</a>';
            }

            $next_page = ($current_page >= $page) ? $page : $current_page + 1;
            $item .= '<a class="btn btn-pagination me-1" href="' . $url . '&page=' . $next_page . '&limit=' . $limit . '"><i class="bi bi-chevron-right"></i></a>';
            $item .= '<a class="btn btn-pagination me-1" href="' . $url . '&page=' . $page . '&limit=' . $limit . '"><i class="bi bi-chevron-double-right"></i></a>';
        } else {
            $item = '<div class="bg-danger p-3 br-10">Hasil pencarian tidak ditemukan, silahkan gunakan filter lain!</div>';
        }

        return '<div class="col-md-12">' . $item . '</div>';
    }

    function curlRequest($url, $headers = [])
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return [
                "status" => false,
                "msg" => "cURL Error: $err",
                "data" => []
            ];
        }

        return json_decode($response, true);
    }

    /**
     * Retry wrapper around curlRequest with validation callback
     */
    function curlRequestWithRetry($url, $headers, $isValidResponse, $maxRetry = 2, $delayMs = 300)
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

    /**
     * Get RapidAPI headers for TikTok API requests
     */
    function getRapidApiHeaders()
    {
        return [
            "Content-Type: application/json",
            "x-rapidapi-host: " . env('RAPIDAPI_HOST', 'tiktok-video-no-watermark10.p.rapidapi.com'),
            "x-rapidapi-key: " . env('RAPIDAPI_KEY', ''),
        ];
    }

    /**
     * Get TikTok user info via RapidAPI /api/user/info endpoint
     */
    function getDataFromFirstEndpoint($username)
    {
        $url = "https://" . env('RAPIDAPI_HOST', 'tiktok-video-no-watermark10.p.rapidapi.com') . "/index/Tiktok/getUserInfo?unique_id=" . urlencode($username);
        $headers = $this->getRapidApiHeaders();

        $response = $this->curlRequestWithRetry($url, $headers, function ($resp) {
            return intval($resp['code'] ?? -1) === 0 && !empty($resp['data']['user']['uniqueId']);
        });

        if (empty($response['data']['user']['uniqueId'])) {
            return [
                "status" => false,
                "msg" => "Username <b>" . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "</b> tidak ditemukan",
                "data" => [],
                "source" => "first_endpoint"
            ];
        }

        $user = $response['data']['user'] ?? [];
        $stats = $response['data']['stats'] ?? [];
        $img = $user['avatarLarger'] ?? ($user['avatarMedium'] ?? ($user['avatarThumb'] ?? ''));

        return [
            "status" => true,
            "msg" => "Data ditemukan",
            "data" => [
                "account_id"  => $user['secUid'] ?? '',
                "username"    => $user['uniqueId'] ?? $username,
                "img"         => $img,
                "follower"    => intval($stats['followerCount'] ?? 0),
                "media_count" => intval($stats['videoCount'] ?? 0),
            ],
            "source" => "first_endpoint"
        ];
    }

    /**
     * Parse ScrapingBot tiktokProfile response into standard data structure
     *
     * @param array $data Raw ScrapingBot tiktokProfile response
     * @return array ['account_id' => ..., 'profile' => [...], 'posts' => [...]]
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

        // Some providers wrap profile details under "profile"
        if (isset($data['profile']) && is_array($data['profile'])) {
            // Preserve any posts array if present at top level
            $profile = $data['profile'];
            if (empty($profile['top_videos']) && !empty($data['top_videos'])) {
                $profile['top_videos'] = $data['top_videos'];
            }
            if (empty($profile['videos']) && !empty($data['videos'])) {
                $profile['videos'] = $data['videos'];
            }
            if (empty($profile['top_videos']) && !empty($data['posts'])) {
                $profile['top_videos'] = $data['posts'];
            }
            $data = $profile;
        }

        // Parse profile info
        $result['profile']['account_id']  = strval($data['sec_uid'] ?? ($data['secu_id'] ?? ($data['influencer_id'] ?? ($data['id'] ?? ''))));
        $result['profile']['follower']    = intval($data['follower_count'] ?? ($data['followers'] ?? ($data['follower'] ?? 0)));
        $result['profile']['media_count'] = intval($data['videos_count'] ?? ($data['video_count'] ?? 0));
        $result['profile']['img']         = strval($data['avatar'] ?? ($data['profile_pic_url_hd'] ?? ($data['avatar_thumb'] ?? '')));
        $result['profile']['full_name']   = strval($data['nickname'] ?? ($data['unique_id'] ?? ''));

        // Parse video stats from top_videos — use saved $allPosts as fallback for flat array format
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
     * Parse ScrapingBot instagramProfile response into standard data structure
     *
     * @param array $data Raw ScrapingBot instagramProfile response
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

        // ScrapingBot sometimes returns a flat array of post objects with embedded profile info
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

        // Some providers wrap profile details under "profile"
        if (isset($data['profile']) && is_array($data['profile'])) {
            $profile = $data['profile'];
            if (empty($profile['posts']) && !empty($data['posts'])) {
                $profile['posts'] = $data['posts'];
            }
            $data = $profile;
        }

        // Parse profile info — check ScrapingBot field names first, then legacy names
        $result['profile']['account_id']  = strval($data['author_id'] ?? ($data['id'] ?? ($data['pk'] ?? '')));
        $result['profile']['follower']    = intval($data['follower_count'] ?? ($data['followers'] ?? 0));
        $result['profile']['media_count'] = intval($data['posts_count'] ?? ($data['post_count'] ?? ($data['media_count'] ?? 0)));
        $result['profile']['img']         = strval($data['profile_image_link'] ?? ($data['profile_picture'] ?? ($data['profile_pic_url'] ?? '')));
        $result['profile']['full_name']   = strval($data['profile_name'] ?? ($data['full_name'] ?? ($data['username'] ?? '')));

        // Parse post stats — use saved $allPosts as fallback for flat array format
        $posts = $data['posts'] ?? ($data['edge_owner_to_timeline_media']['edges'] ?? ($allPosts ?: []));
        $posts = array_slice($posts, 0, 12);

        foreach ($posts as $k => $v) {
            // Handle nested edge format
            $node = $v['node'] ?? $v;

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

    /**
     * Insert a scraping queue item for async processing
     *
     * @param string $entityType  'influencer', 'influencer_dummy', or 'endorse'
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
            return ['status' => false, 'msg' => 'Platform atau URL tidak valid'];
        }

        // Check if already in queue (pending/submitted)
        $existing = $CI->db->select('id')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where_in('status', ['pending', 'submitted'])
            ->get('scraping_queue')
            ->num_rows();

        if ($existing > 0) {
            return ['status' => true, 'msg' => 'Sudah dalam antrian'];
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

        return ['status' => true, 'msg' => 'Ditambahkan ke antrian'];
    }

    /**
     * Process a completed scraping queue result and update the entity
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

        $table = ($entityType === 'influencer_dummy') ? 'influencer_dummy' : $entityType;

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
            'updated_by'  => '1', // system
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

            // Get ratecard for CPM calculation
            $record = $CI->db->select('ratecard')->where('id', $entityId)->get($table)->row_array();
            $ratecard = floatval($record['ratecard'] ?? 0);
            $cpm_2 = ($ratecard > 0 && $avg_view > 0) ? ($ratecard / $avg_view * 1000) : 0;

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
                'cpm_2'            => $cpm_2,
            ];

            $CI->db->update($table, $metricsUpdate, ['id' => $entityId]);

            // Update influencer_logs if entity is influencer
            if ($entityType === 'influencer') {
                $today = date('Y-m-d');
                $logs = $CI->db->select('id')
                    ->where('id_influencer', $entityId)
                    ->where('DATE(date)', $today)
                    ->get('influencer_logs')
                    ->row_array();

                $logData = [
                    'like'           => $like,
                    'comment'        => $comment,
                    'collect'        => $collect,
                    'share'          => $share,
                    'view'           => $view,
                    'avg_view'       => $avg_view,
                    'avg_interaksi'  => $avg_interaksi,
                    'er'             => $er,
                    'sync_at'        => date('Y-m-d H:i:s'),
                ];

                if ($logs) {
                    $logData['updated_at'] = date('Y-m-d H:i:s');
                    $CI->db->update('influencer_logs', $logData, ['id' => $logs['id']]);
                } else {
                    $logData['id_influencer'] = $entityId;
                    $logData['date'] = $today;
                    $logData['status'] = 'Aktif';
                    $logData['created_at'] = date('Y-m-d H:i:s');
                    $CI->db->insert('influencer_logs', $logData);
                }
            }
        }

        return true;
    }

    /**
     * Synchronous TikTok profile sync via RapidAPI
     * Combines get_account_id + get_post_list + metrics calculation + DB update
     *
     * @param string $entityType 'influencer' or 'influencer_dummy'
     * @param int    $entityId   Entity record ID
     * @param string $type       Platform type (should be 'Tiktok')
     * @param string $url        Profile URL
     * @return array ['status' => bool, 'msg' => string]
     */
    function syncTiktokProfile($entityType, $entityId, $type, $url)
    {
        $CI =& get_instance();

        // Step 1: Get account ID (profile data)
        $profileResult = $this->get_account_id($type, $url);
        if (!$profileResult['status'] || empty($profileResult['data'])) {
            return ['status' => false, 'msg' => $profileResult['msg'] ?: 'Gagal mengambil data profil TikTok'];
        }

        $profileData = $profileResult['data'];
        $table = ($entityType === 'influencer_dummy') ? 'influencer_dummy' : $entityType;

        // Step 2: Update profile data
        $profileUpdate = [
            'account_id'  => $profileData['account_id'],
            'img'         => $profileData['img'],
            'follower'    => $profileData['follower'],
            'media_count' => $profileData['media_count'],
            'updated_at'  => date('Y-m-d H:i:s'),
            'updated_by'  => '1', // system
        ];

        if (!empty($profileData['username'])) {
            $profileUpdate['full_name'] = $profileData['username'];
        }

        $CI->db->update($table, $profileUpdate, ['id' => $entityId]);

        // Step 3: Get post list for engagement metrics
        $postResult = $this->get_post_list($type, $profileData['username'] ?? '');
        if ($postResult['status'] && !empty($postResult['data'])) {
            $like = $comment = $collect = $share = $view = 0;
            $i = 0;

            foreach ($postResult['data'] as $post) {
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

            // Get ratecard for CPM calculation
            $record = $CI->db->select('ratecard')->where('id', $entityId)->get($table)->row_array();
            $ratecard = floatval($record['ratecard'] ?? 0);
            $cpm_2 = ($ratecard > 0 && $avg_view > 0) ? ($ratecard / $avg_view * 1000) : 0;

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
                'cpm_2'            => $cpm_2,
            ];

            $CI->db->update($table, $metricsUpdate, ['id' => $entityId]);

            // Update influencer_logs if entity is influencer
            if ($entityType === 'influencer') {
                $today = date('Y-m-d');
                $logs = $CI->db->select('id')
                    ->where('id_influencer', $entityId)
                    ->where('DATE(date)', $today)
                    ->get('influencer_logs')
                    ->row_array();

                $logData = [
                    'like'           => $like,
                    'comment'        => $comment,
                    'collect'        => $collect,
                    'share'          => $share,
                    'view'           => $view,
                    'avg_view'       => $avg_view,
                    'avg_interaksi'  => $avg_interaksi,
                    'er'             => $er,
                    'sync_at'        => date('Y-m-d H:i:s'),
                ];

                if ($logs) {
                    $logData['updated_at'] = date('Y-m-d H:i:s');
                    $CI->db->update('influencer_logs', $logData, ['id' => $logs['id']]);
                } else {
                    $logData['id_influencer'] = $entityId;
                    $logData['date'] = $today;
                    $logData['status'] = 'Aktif';
                    $logData['created_at'] = date('Y-m-d H:i:s');
                    $CI->db->insert('influencer_logs', $logData);
                }
            }
        } else {
            // No post data, still mark sync_at
            $CI->db->update($table, [
                'sync_at'    => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => '1',
            ], ['id' => $entityId]);
        }

        return ['status' => true, 'msg' => 'Data TikTok berhasil disinkronkan'];
    }

    /**
     * Get account ID - TikTok uses RapidAPI (sync), Instagram uses ScrapingBot (async)
     *
     * @param string $type Platform type
     * @param string $url  Profile URL
     * @param string $entityType  Entity type for queue
     * @param int    $entityId    Entity ID for queue
     * @param int    $priority    Queue priority
     * @return array Standard response
     */
    function get_account_id($type, $url, $entityType = null, $entityId = null, $priority = 5)
    {
        $uri = explode("/", parse_url($url, PHP_URL_PATH));
        $username = $uri[1] ?? null;

        if (empty($url) || empty($username)) {
            return [
                "status" => false,
                "msg" => "Pastikan URL sudah diisi!",
                "data" => []
            ];
        }

        if ($type == "Tiktok") {
            // Synchronous via RapidAPI
            $result = $this->getDataFromFirstEndpoint($username);
            return $result;
        }

        if ($type == "Instagram") {
            // Async via ScrapingBot queue
            if ($entityType && $entityId) {
                $queueResult = $this->enqueue_scrape($entityType, $entityId, $type, $url, $priority);
                return [
                    "status"  => false,
                    "msg"     => "Data sedang diproses (async). " . $queueResult['msg'],
                    "data"    => [],
                    "queued"  => true,
                ];
            }

            return [
                "status" => false,
                "msg"    => "Gunakan async queue untuk mengambil data.",
                "data"   => []
            ];
        }

        return [
            "status" => false,
            "msg" => "Platform belum tersedia",
            "data" => []
        ];
    }

    /**
     * Get post list - now returns data from queue result (profile scrape includes posts)
     * This method is kept for backward compatibility but data comes from the same profile scrape
     *
     * @param string $type       Platform type
     * @param string $account_id Account ID (unused in new flow, kept for compatibility)
     * @return array Standard response
     */
    function get_post_list($type, $account_id)
    {
        if ($type == "Tiktok") {
            $username = trim((string) $account_id);
            $username = ltrim($username, '@');
            $uniqueId = '@' . $username;
            $host = env('RAPIDAPI_HOST', 'tiktok-video-no-watermark10.p.rapidapi.com');
            $url = "https://$host/index/Tiktok/getUserVideos?unique_id=" . urlencode($uniqueId) . "&count=10&cursor=0";
            $headers = $this->getRapidApiHeaders();

            $response = $this->curlRequestWithRetry($url, $headers, function ($resp) {
                return intval($resp['code'] ?? -1) === 0 && !empty($resp['data']['videos']);
            });

            $videos = $response['data']['videos'] ?? [];
            if (empty($videos)) {
                return [
                    "status" => false,
                    "msg" => "Data video tiktok account id :  <b>" . htmlspecialchars((string) $account_id, ENT_QUOTES, 'UTF-8') . "</b> tidak ditemukan",
                    "data" => []
                ];
            }

            $posts = [];
            $items = array_slice($videos, 0, 10);
            foreach ($items as $k => $item) {
                $author = $item['author'] ?? [];
                $videoId = strval($item['video_id'] ?? ($item['aweme_id'] ?? ''));
                $authorUniqueId = strval($author['unique_id'] ?? '');
                $posts[$k] = [
                    'like'              => intval($item['digg_count'] ?? 0),
                    'share'             => intval($item['share_count'] ?? 0),
                    'comment'           => intval($item['comment_count'] ?? 0),
                    'collect'           => intval($item['collect_count'] ?? 0),
                    'view'              => intval($item['play_count'] ?? 0),
                    'video_id'          => $videoId,
                    'aweme_id'          => strval($item['aweme_id'] ?? $videoId),
                    'title'             => strval($item['title'] ?? ''),
                    'cover'             => strval($item['cover'] ?? ''),
                    'duration'          => intval($item['duration'] ?? 0),
                    'play'              => strval($item['play'] ?? ''),
                    'wmplay'            => strval($item['wmplay'] ?? ''),
                    'music'             => strval($item['music'] ?? ''),
                    'create_time'       => intval($item['create_time'] ?? 0),
                    'is_ad'             => !empty($item['is_ad']),
                    'author_id'         => strval($author['id'] ?? ''),
                    'author_unique_id'  => $authorUniqueId,
                    'author_nickname'   => strval($author['nickname'] ?? ''),
                    'author_avatar'     => strval($author['avatar'] ?? ''),
                    'url'               => ($authorUniqueId !== '' && $videoId !== '') ? "https://www.tiktok.com/@{$authorUniqueId}/video/{$videoId}" : '',
                ];
            }

            return [
                "status" => true,
                "msg" => "Data ditemukan",
                "data" => $posts
            ];
        }

        // Instagram: post data comes from profile scrape (async queue)
        return [
            "status" => false,
            "msg"    => "Data post didapat dari profile scrape (async queue).",
            "data"   => []
        ];
    }

    function get_social_media($type, $url, $fetch_media_assets = true, $influencer_id = null, $preferRapidApi = false)
    {
        $response = [
            "status" => true,
            "msg" => "",
            "data" => [
                "like" => 0,
                "share" => 0,
                "comment" => 0,
                "collect" => 0,
                "view" => 0,
                "created_at" => "",
                "content_id" => "",
                "media_type" => "",
                "video_link" => "",
                "cover" => "",
                "images" => [],
            ],
        ];
        if ($type == "Tiktok") {
            if ($url) {
                $content_id = $this->extract_tiktok_content_id($url);
                if (empty($content_id)) {
                    $response["status"] = false;
                    $response["msg"] = "Video ID tidak ditemukan dari URL: " . $url;
                    $response["data"] = [];
                    return $response;
                }

                $response["data"]["content_id"] = $content_id;
                $response["data"]["media_type"] = $this->detect_tiktok_media_type_from_url($url);

                // ScrapingBot/direct-HTML scrape is unreliable; RapidAPI is the mandated path.
                // When preferred (param or TIKTOK_METRICS_PREFER_RAPIDAPI env), try RapidAPI
                // first and only fall back to the page scrape.
                $prefer = $preferRapidApi || env('TIKTOK_METRICS_PREFER_RAPIDAPI', '0') == '1';

                $host = env('RAPIDAPI_HOST', 'tiktok-video-no-watermark10.p.rapidapi.com');
                $detailUrl = "https://{$host}/index/Tiktok/getVideoInfo?url=" . urlencode($url) . "&hd=0";

                if ($prefer) {
                    $apiResp = $this->curlRequestWithRetry($detailUrl, $this->getRapidApiHeaders(), function ($resp) {
                        return intval($resp['code'] ?? -1) === 0 && !empty($resp['data']['id']);
                    });
                    if (intval($apiResp['code'] ?? -1) === 0 && !empty($apiResp['data']['id'])) {
                        return $this->mapRapidApiTiktokDetailToResponse($response, $apiResp['data'] ?? [], $fetch_media_assets);
                    }
                    // RapidAPI failed — fall back to page scrape below.
                }

                $itemStruct = $this->scrapeTiktokDetailFromPage($url);
                if ($this->isValidTiktokScrapeItem($itemStruct)) {
                    $response = $this->mapDirectTiktokItemToResponse($response, $itemStruct, $fetch_media_assets);
                    return $response;
                }

                if (!$prefer) {
                    $apiResp = $this->curlRequestWithRetry($detailUrl, $this->getRapidApiHeaders(), function ($resp) {
                        return intval($resp['code'] ?? -1) === 0 && !empty($resp['data']['id']);
                    });
                } else {
                    // Prefer mode already attempted RapidAPI; reuse that failed result shape.
                    $apiResp = $apiResp ?? ['code' => -1, 'data' => []];
                }

                if (intval($apiResp['code'] ?? -1) !== 0 || empty($apiResp['data']['id'])) {
                    $response["status"] = false;
                    $response["msg"] = "Response tiktok " . $content_id . " tidak ditemukan";
                    $response["data"] = [];
                    return $response;
                }

                $response = $this->mapRapidApiTiktokDetailToResponse($response, $apiResp['data'] ?? [], $fetch_media_assets);
            } else {
                $response["status"] = false;
                $response["msg"] = "URL tidak ditemukan";
                $response["data"] = [];
            }
        } else if ($type == "Instagram") {
            $response["status"] = false;
            $response["msg"] = "Individual Instagram post scraping belum tersedia";
            $response["data"] = [];
        } else {
            $response["status"] = false;
            $response["msg"] = "Platform belum tersedia";
            $response["data"] = [];
        }
        return $response;
    }

    /**
     * Fetch per-post stats for many endorse rows in parallel via curl_multi.
     * Each $tasks entry: ['platform' => 'Tiktok'|'Instagram', 'url' => string].
     * Returns array indexed identically; each element matches the get_social_media() shape.
     */
    function get_social_media_batch(array $tasks, int $maxConcurrent = 10, float $deadlineSeconds = 45.0): array
    {
        $results = [];

        if ($maxConcurrent <= 0) {
            $maxConcurrent = 10;
        }

        $startedAt = microtime(true);
        $overBudget = function () use ($startedAt, $deadlineSeconds) {
            return $deadlineSeconds > 0 && (microtime(true) - $startedAt) >= $deadlineSeconds;
        };

        // TikTok page-scrape is unreliable; RapidAPI is the mandated primary path.
        // When the scrape is disabled (default), skip it entirely and fan the chunk
        // out as concurrent RapidAPI calls — the only way a run completes its whole
        // chunk instead of grinding through them sequentially until the deadline.
        $scrapeEnabled = env('ENDORSE_TIKTOK_SCRAPE_ENABLED', '0') == '1';

        $chunks = array_chunk($tasks, $maxConcurrent, true);
        foreach ($chunks as $chunk) {
            // Wall-clock guard: never overrun the worker's HTTP timeout. Out of budget →
            // defer this chunk (and every later one) so the caller returns those rows to
            // the queue instead of the run timing out mid-batch (504 + stuck rows).
            if ($overBudget()) {
                foreach ($chunk as $idx => $task) {
                    $results[$idx] = $this->deferredBatchResult();
                }
                continue;
            }

            if (!$scrapeEnabled) {
                $batch = $this->fetchRapidApiTiktokBatch($chunk);
                foreach ($chunk as $idx => $task) {
                    $results[$idx] = $batch[$idx] ?? ['status' => false, 'msg' => 'No response', 'data' => []];
                }
                continue;
            }

            $pageScrapes = $this->fetchTiktokDetailPagesBatch($chunk);
            foreach ($chunk as $idx => $task) {
                $platform = $task['platform'] ?? '';
                $url = $task['url'] ?? '';

                // Budget can be exhausted mid-chunk by slow sequential fallbacks below.
                if ($overBudget()) {
                    $results[$idx] = $this->deferredBatchResult();
                    continue;
                }

                if ($platform === 'Tiktok' && !empty($pageScrapes[$idx]) && $this->isValidTiktokScrapeItem($pageScrapes[$idx])) {
                    $response = [
                        "status" => true,
                        "msg" => "",
                        "data" => [
                            "like" => 0,
                            "share" => 0,
                            "comment" => 0,
                            "collect" => 0,
                            "view" => 0,
                            "created_at" => "",
                            "content_id" => $this->extract_tiktok_content_id($url),
                            "media_type" => $this->detect_tiktok_media_type_from_url($url),
                            "video_link" => "",
                            "cover" => "",
                            "images" => [],
                        ],
                    ];
                    $results[$idx] = $this->mapDirectTiktokItemToResponse($response, $pageScrapes[$idx], true);
                    continue;
                }

                $results[$idx] = $this->get_social_media($platform, $url, true, null);
            }
        }

        return $results;
    }

    /**
     * Sentinel result for an item the batch ran out of wall-clock budget to fetch.
     * The caller (endorse refresh worker) detects the `deferred` flag and returns the
     * row to the queue without charging a retry attempt, so a deep queue drains across
     * many short, always-completing runs instead of one run that times out.
     */
    private function deferredBatchResult(): array
    {
        return [
            'status'   => false,
            'msg'      => 'Deferred: batch wall-clock budget reached',
            'data'     => [],
            'deferred' => true,
        ];
    }

    protected function fetchTiktokDetailPagesBatch(array $tasks): array
    {
        $multiHandle = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($tasks as $idx => $task) {
            $platform = $task['platform'] ?? '';
            $url = trim((string) ($task['url'] ?? ''));
            if ($platform !== 'Tiktok' || $url === '') {
                $results[$idx] = [];
                continue;
            }

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:100.0) Gecko/20100101 Firefox/100.0',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'Cookie: tt_chain_token=+O8Mw9RH4nKrX/ACdOBhXw==; tt_csrf_token=27TtpaB8-Wftkj0rFR_w6LdtcAp4tdDCFfBY; ttwid=1%7CdJI7LAdiTNKwSISqHad9wDTJ6G_70WU_PGro2isx-ac%7C1705385087%7C518efef116162148489d7f25fa3c7b06633a23c824590f04ea0226d5c2b6f092'
                ],
            ]);
            curl_multi_add_handle($multiHandle, $curl);
            $handles[$idx] = $curl;
        }

        do {
            $status = curl_multi_exec($multiHandle, $active);
            if ($active) {
                curl_multi_select($multiHandle, 1.0);
            }
        } while ($active && $status === CURLM_OK);

        foreach ($handles as $idx => $curl) {
            $html = curl_multi_getcontent($curl);
            $results[$idx] = $this->extractTiktokItemStructFromHtml($html);
            curl_multi_remove_handle($multiHandle, $curl);
            curl_close($curl);
        }

        curl_multi_close($multiHandle);

        return $results;
    }

    /**
     * Parallel RapidAPI getVideoInfo for a batch of TikTok tasks via curl_multi.
     * Mirrors fetchTiktokDetailPagesBatch() but hits the mandated RapidAPI endpoint
     * instead of scraping tiktok.com. Each $tasks entry: ['platform','url'].
     * Returns array indexed identically; each element matches get_social_media().
     * Single attempt per item — queue-level retry/defer handles transient failures.
     */
    protected function fetchRapidApiTiktokBatch(array $tasks): array
    {
        $host = env('RAPIDAPI_HOST', 'tiktok-video-no-watermark10.p.rapidapi.com');
        $headers = $this->getRapidApiHeaders();
        $multiHandle = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($tasks as $idx => $task) {
            $platform = $task['platform'] ?? '';
            $url = trim((string) ($task['url'] ?? ''));

            // Non-TikTok platforms have no working sync path — return the same
            // placeholder get_social_media() would, without an HTTP call.
            if ($platform !== 'Tiktok' || $url === '') {
                $results[$idx] = $this->get_social_media($platform, $url, true, null, true);
                continue;
            }

            $content_id = $this->extract_tiktok_content_id($url);
            if (empty($content_id)) {
                $results[$idx] = [
                    'status' => false,
                    'msg' => 'Video ID tidak ditemukan dari URL: ' . $url,
                    'data' => [],
                ];
                continue;
            }

            $detailUrl = "https://{$host}/index/Tiktok/getVideoInfo?url=" . urlencode($url) . "&hd=0";
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $detailUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers,
            ]);
            curl_multi_add_handle($multiHandle, $curl);
            $handles[$idx] = ['curl' => $curl, 'url' => $url];
        }

        if (!empty($handles)) {
            do {
                $status = curl_multi_exec($multiHandle, $active);
                if ($active) {
                    curl_multi_select($multiHandle, 1.0);
                }
            } while ($active && $status === CURLM_OK);

            foreach ($handles as $idx => $h) {
                $curl = $h['curl'];
                $body = curl_multi_getcontent($curl);
                $err  = curl_error($curl);
                $httpCode = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
                curl_multi_remove_handle($multiHandle, $curl);
                curl_close($curl);

                $base = [
                    'status' => true,
                    'msg' => '',
                    'data' => [
                        'like' => 0, 'share' => 0, 'comment' => 0, 'collect' => 0, 'view' => 0,
                        'created_at' => '',
                        'content_id' => $this->extract_tiktok_content_id($h['url']),
                        'media_type' => $this->detect_tiktok_media_type_from_url($h['url']),
                        'video_link' => '', 'cover' => '', 'images' => [],
                    ],
                ];

                $apiResp = $err ? null : json_decode($body, true);
                if (is_array($apiResp) && intval($apiResp['code'] ?? -1) === 0 && !empty($apiResp['data']['id'])) {
                    $results[$idx] = $this->mapRapidApiTiktokDetailToResponse($base, $apiResp['data'], true);
                } else {
                    // Diagnostics: record WHY the success gate failed so deleted vs
                    // rate-limit (429) vs timeout (http=0/cURL) vs parse mismatch
                    // (apicode=0 dataid=0) are distinguishable in the queue Riwayat.
                    // Stays a transient failure (still retried) — no permanent-trigger
                    // phrases embedded (see Endorse_sync::classify).
                    $apicode = is_array($apiResp) ? strval($apiResp['code'] ?? 'n/a') : 'n/a';
                    $apimsg  = is_array($apiResp) ? strval($apiResp['msg'] ?? 'n/a') : 'n/a';
                    $dataid  = (is_array($apiResp) && !empty($apiResp['data']['id'])) ? 1 : 0;
                    $apimsg  = trim(preg_replace('/\s+/', ' ', $apimsg));
                    if (strlen($apimsg) > 80) {
                        $apimsg = substr($apimsg, 0, 80) . '…';
                    }
                    $detail = "tiktok {$base['data']['content_id']} gagal: http={$httpCode} apicode={$apicode} apimsg={$apimsg} dataid={$dataid}";
                    if ($err) {
                        $detail .= " cURL={$err}";
                    } elseif (!is_array($apiResp) || $dataid === 0) {
                        $snippet = trim(preg_replace('/\s+/', ' ', (string) $body));
                        if (strlen($snippet) > 180) {
                            $snippet = substr($snippet, 0, 180) . '…';
                        }
                        $detail .= " body={$snippet}";
                    }
                    $results[$idx] = [
                        'status' => false,
                        'msg' => $detail,
                        'data' => [],
                    ];
                }
            }
        }

        curl_multi_close($multiHandle);

        return $results;
    }

    protected function extractTiktokItemStructFromHtml($html)
    {
        if (!is_string($html) || $html === '') {
            return [];
        }

        $pattern = '/<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application\/json">(.*?)<\/script>/s';
        if (!preg_match($pattern, $html, $matches) || empty($matches[1])) {
            return [];
        }

        $json = json_decode($matches[1], true);
        if (!is_array($json)) {
            return [];
        }

        return $json['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct'] ?? [];
    }

    public function extract_tiktok_content_id($url)
    {
        if (empty($url)) {
            return '';
        }

        if (preg_match('/\/video\/(\d+)/', $url, $matches)) {
            return strval($matches[1]);
        }

        if (preg_match('/\/photo\/(\d+)/', $url, $matches)) {
            return strval($matches[1]);
        }

        if (preg_match('/(\d{10,25})/', $url, $matches)) {
            return strval($matches[1]);
        }

        return '';
    }

    public function detect_tiktok_media_type_from_url($url)
    {
        if (stripos((string) $url, '/photo/') !== false) {
            return 'photo';
        }
        if (stripos((string) $url, '/video/') !== false) {
            return 'video';
        }
        return '';
    }

    public function extract_tiktok_cover_from_item($item)
    {
        return strval(
            $item['video']['cover']
                ?? ($item['imagePost']['cover']['imageURL']['urlList'][0]
                ?? ($item['video']['originCover'] ?? ''))
        );
    }

    protected function scrapeTiktokDetailFromPage($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:100.0) Gecko/20100101 Firefox/100.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Cookie: tt_chain_token=+O8Mw9RH4nKrX/ACdOBhXw==; tt_csrf_token=27TtpaB8-Wftkj0rFR_w6LdtcAp4tdDCFfBY; ttwid=1%7CdJI7LAdiTNKwSISqHad9wDTJ6G_70WU_PGro2isx-ac%7C1705385087%7C518efef116162148489d7f25fa3c7b06633a23c824590f04ea0226d5c2b6f092'
            ],
        ]);

        $html = curl_exec($curl);
        curl_close($curl);

        return $this->extractTiktokItemStructFromHtml($html);
    }

    protected function isValidTiktokScrapeItem($item)
    {
        if (empty($item['stats']) || !is_array($item['stats'])) {
            return false;
        }
        $stats = $item['stats'];
        return intval($stats['diggCount'] ?? 0) > 0
            || intval($stats['shareCount'] ?? 0) > 0
            || intval($stats['commentCount'] ?? 0) > 0
            || intval($stats['collectCount'] ?? 0) > 0
            || intval($stats['playCount'] ?? 0) > 0;
    }

    protected function mapDirectTiktokItemToResponse(array $response, array $item, bool $fetch_media_assets): array
    {
        $stats = $item['stats'] ?? [];
        $response['data']['like'] = intval($stats['diggCount'] ?? 0);
        $response['data']['share'] = intval($stats['shareCount'] ?? 0);
        $response['data']['comment'] = intval($stats['commentCount'] ?? 0);
        $response['data']['collect'] = intval($stats['collectCount'] ?? 0);
        $response['data']['view'] = intval($stats['playCount'] ?? 0);
        $response['data']['content_id'] = strval($item['id'] ?? $response['data']['content_id']);
        $response['data']['cover'] = $this->extract_tiktok_cover_from_item($item);
        if (!empty($item['createTime'])) {
            $response['data']['created_at'] = date('Y-m-d', intval($item['createTime']));
        }

        if (!empty($item['imagePost']['images']) || !empty($item['imagePost']['cover'])) {
            $response['data']['media_type'] = 'photo';
        } else {
            $response['data']['media_type'] = 'video';
        }

        if ($fetch_media_assets && $response['data']['media_type'] === 'photo') {
            $images = [];
            foreach (($item['imagePost']['images'] ?? []) as $image) {
                $imageUrl = strval($image['imageURL']['urlList'][0] ?? '');
                if ($imageUrl !== '') {
                    $images[] = $imageUrl;
                }
            }
            $response['data']['images'] = $images;
            if (!empty($images)) {
                $response['data']['video_link'] = json_encode($images);
            } elseif ($response['data']['cover'] !== '') {
                $response['data']['video_link'] = json_encode([$response['data']['cover']]);
            }
        }

        return $response;
    }

    protected function mapRapidApiTiktokDetailToResponse(array $response, array $item, bool $fetch_media_assets): array
    {
        $response['data']['like'] = intval($item['digg_count'] ?? 0);
        $response['data']['share'] = intval($item['share_count'] ?? 0);
        $response['data']['comment'] = intval($item['comment_count'] ?? 0);
        $response['data']['collect'] = intval($item['collect_count'] ?? 0);
        $response['data']['view'] = intval($item['play_count'] ?? 0);
        $response['data']['content_id'] = strval($item['id'] ?? $response['data']['content_id']);
        $response['data']['cover'] = strval($item['cover'] ?? ($item['origin_cover'] ?? ($item['ai_dynamic_cover'] ?? '')));
        if (!empty($item['create_time'])) {
            $response['data']['created_at'] = date('Y-m-d', intval($item['create_time']));
        }

        if (!empty($item['images']) || $response['data']['media_type'] === 'photo') {
            $response['data']['media_type'] = 'photo';
        } else {
            $response['data']['media_type'] = 'video';
        }

        if ($fetch_media_assets) {
            if ($response['data']['media_type'] === 'photo') {
                $images = [];
                foreach (($item['images'] ?? []) as $image) {
                    if (is_string($image) && trim($image) !== '') {
                        $images[] = trim($image);
                    }
                }
                $response['data']['images'] = $images;
                if (!empty($images)) {
                    $response['data']['video_link'] = json_encode($images);
                } elseif ($response['data']['cover'] !== '') {
                    $response['data']['video_link'] = json_encode([$response['data']['cover']]);
                }
            } elseif (!empty($item['play'])) {
                $response['data']['video_link'] = strval($item['play']);
            }
        }

        return $response;
    }

    protected function hasEndorseTiktokColumns($CI)
    {
        return $CI->db->field_exists('tiktok_content_link', 'endorse');
    }

    public function get_tiktok_photo_images($content_id, $url = null)
    {
        $CI =& get_instance();
        if (!$this->hasEndorseTiktokColumns($CI)) {
            return ['status' => false, 'msg' => 'Kolom tiktok_content_link belum tersedia', 'data' => []];
        }

        $content_id = trim((string) $content_id);
        $url = trim((string) $url);
        if ($content_id === '' && $url === '') {
            return ['status' => false, 'msg' => 'Content ID atau URL wajib diisi', 'data' => []];
        }

        $where = [];
        if ($content_id !== '') {
            $where[] = "tiktok_content_id = " . $CI->db->escape($content_id);
        }
        if ($url !== '') {
            $where[] = "link_upload = " . $CI->db->escape($url);
        }

        $rows = $CI->mymodel->selectWithQuery("
            SELECT tiktok_media_type, tiktok_cover, tiktok_content_link, link_upload
            FROM endorse
            WHERE (" . implode(' OR ', $where) . ")
            ORDER BY (tiktok_media_type = 'photo') DESC, updated_at DESC, id DESC
            LIMIT 1
        ");

        if (empty($rows)) {
            return ['status' => false, 'msg' => 'Data TikTok photo tidak ditemukan', 'data' => []];
        }

        $row = $rows[0];
        $decoded = json_decode((string) ($row['tiktok_content_link'] ?? ''), true);
        $images = [];
        if (is_array($decoded)) {
            foreach ($decoded as $image) {
                if (is_string($image) && trim($image) !== '') {
                    $images[] = trim($image);
                }
            }
        }
        if (empty($images) && !empty($row['tiktok_cover'])) {
            $images[] = $row['tiktok_cover'];
        }
        if (empty($images)) {
            return ['status' => false, 'msg' => 'Data gambar TikTok tidak ditemukan', 'data' => []];
        }

        return ['status' => true, 'msg' => '', 'data' => $images];
    }

    public function get_tiktok_video_play($url)
    {
        $CI =& get_instance();
        if (!$this->hasEndorseTiktokColumns($CI)) {
            return ['status' => false, 'msg' => 'Kolom tiktok_content_link belum tersedia', 'data' => []];
        }

        $url = trim((string) $url);
        if ($url === '') {
            return ['status' => false, 'msg' => 'URL tidak ditemukan', 'data' => []];
        }

        $rows = $CI->mymodel->selectWithQuery("
            SELECT tiktok_media_type, tiktok_content_link
            FROM endorse
            WHERE link_upload = " . $CI->db->escape($url) . "
            ORDER BY updated_at DESC, id DESC
            LIMIT 1
        ");

        if (empty($rows)) {
            return ['status' => false, 'msg' => 'Data TikTok video tidak ditemukan', 'data' => []];
        }

        $row = $rows[0];
        if (($row['tiktok_media_type'] ?? '') === 'photo') {
            return ['status' => false, 'msg' => 'Konten TikTok ini bertipe photo', 'data' => []];
        }

        $play = trim((string) ($row['tiktok_content_link'] ?? ''));
        if ($play === '') {
            return ['status' => false, 'msg' => 'Play URL TikTok tidak ditemukan', 'data' => []];
        }

        return ['status' => true, 'msg' => '', 'data' => $play];
    }

    function title()
    {
        return 'Acneno System';
    }

    function hex($i)
    {
        $flat_colors = [
            "#009999",
            "#9999FF",
            "#FFD966",
            "#FF0066",
            "#5a99d4",
            "#71ad44",
            "#c4ddcb",
            "#1abc9c",
            "#2ecc71",
            "#3498db",
            "#9b59b6",
            "#34495e",
            "#16a085",
            "#27ae60",
            "#2980b9",
            "#8e44ad",
            "#2c3e50",
            "#f1c40f",
            "#e67e22",
            "#e74c3c",
            "#ecf0f1",
            "#95a5a6",
            "#f39c12",
            "#d35400",
            "#c0392b",
            "#bdc3c7",
            "#7f8c8d"
        ];
        return $flat_colors[$i];
    }
    function get_name_from_number($num)
    {
        $numeric = ($num - 1) % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval(($num - 1) / 26);
        if ($num2 > 0) {
            return $this->get_name_from_number($num2) . $letter;
        } else {
            return $letter;
        }
    }

    function set_session($var, $val)
    {
        $session = \Config\Services::session();
        $session->set($var, $val);
    }

    function get_session($var)
    {
        $session = \Config\Services::session();
        return $session->get($var);
    }

    function date_format($date)
    {
        return DATE("d-M-Y", strtotime($date));
    }

    function datetime_to_date($date)
    {
        return DATE("Y-m-d", strtotime($date));
    }
    function date_to_week($date)
    {
        return DATE("W", strtotime($date));
    }
    function date_to_month($date)
    {
        return DATE("M", strtotime($date));
    }
    function date_to_month_number($date)
    {
        return DATE("m", strtotime($date));
    }
    function date_to_year($date)
    {
        return DATE("Y", strtotime($date));
    }
    public function date_to_date($date)
    {
        $date = explode("-", $date);
        $arr = array();
        $arr['Jan'] = '01';
        $arr['Feb'] = '02';
        $arr['Mar'] = '03';
        $arr['Apr'] = '04';
        $arr['May'] = '05';
        $arr['Jun'] = '06';
        $arr['Jul'] = '07';
        $arr['Aug'] = '08';
        $arr['Sep'] = '09';
        $arr['Oct'] = '10';
        $arr['Nov'] = '11';
        $arr['Dec'] = '12';
        $date[1] = $arr[$date[1]];
        $date = $date[2] . '-' . $date[1] . '-' . $date[0];
        return $date;
    }

    function alert_danger($text)
    {
        $text = str_replace(array("\r", "\n"), ' ', $text);
        $encoded_text = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $text = '<script>
                        $( document ).ready(function() {
                        $.toast({
                            heading: "Informasi",
                            text: ' . $encoded_text . ',
                            showHideTransition: "slide",
                            icon: "error",
                            position: "top-right",
                            loaderBg: "#def7f0",
                            hideAfter: 5000, 
                        });
                    });
                </script>';
        return $text;
    }

    function alert_success($text)
    {
        $text = str_replace(array("\r", "\n"), ' ', $text);
        $encoded_text = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $text = '<script success type="text/javascript">
                    $( document ).ready(function() {
                    $.toast({
                        heading: "Informasi",
                        text: ' . $encoded_text . ',
                        showHideTransition: "slide",
                        icon: "success",
                        position: "top-right",
                        loaderBg: "#def7f0", 
                        hideAfter: 2500,
                    });
                });
            </script>';
        return $text;
    }

    function set_number($angka)
    {
        $angka = str_replace(',', '', $angka);
        // $angka = str_replace('.','',$angka);
        $angka = str_replace('Rp', '', $angka);
        return doubleval($angka);
    }

    public function separator_only($angka)
    {
        // echo $angka;die;
        // echo $angka;die;
        $angka = $this->set_number($angka);
        return number_format(doubleval($angka), 0, ',', '.');
    }


    public function separator($angka)
    {
        $number = $angka;
        if (floor($number) == $number) {
            // No decimal places
            return number_format($number, 0, ',', '.');
        } else {
            // With decimal places
            return number_format($number, 3, ',', '.');
        }
    }
    public function separator_1($angka)
    {
        $number = $angka;
        if (floor($number) == $number) {
            // No decimal places
            return number_format($number, 0, ',', '.');
        } else {
            // With decimal places
            return number_format($number, 1, ',', '.');
        }
    }
    public function separator_2($angka)
    {
        $number = $angka;
        if (floor($number) == $number) {
            // No decimal places
            return number_format($number, 0, ',', '.');
        } else {
            // With decimal places
            return number_format($number, 2, ',', '.');
        }
    }
    public function separator_number_only($angka)
    {
        $angka = $this->set_number($angka);
        return number_format(round($angka), 0, '.', '');
    }
    public function separator_number($angka)
    {
        $number = $angka;
        if (floor($number) == $number) {
            // No decimal places
            return number_format($number, 0, '.', '');
        } else {
            // With decimal places
            return number_format($number, 3, '.', '');
        }
    }
    public function separator_number_1($angka)
    {
        $number = $angka;
        if (floor($number) == $number) {
            // No decimal places
            return number_format($number, 0, '.', '');
        } else {
            // With decimal places
            return number_format($number, 1, '.', '');
        }
    }
    public function separator_number_2($angka)
    {
        $number = $angka;
        if (floor($number) == $number) {
            // No decimal places
            return number_format($number, 0, '.', '');
        } else {
            // With decimal places
            return number_format($number, 2, '.', '');
        }
    }
}
