<?php

class Template
{
    public function index() {}

    function endpoint_url()
    {
        // return 'https://endpoint.acnenosystem.com/';
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
            CURLOPT_TIMEOUT => 30,
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

    function getDataFromFirstEndpoint($username)
    {
        $rapidapi_host = env('RAPIDAPI_HOST', 'tiktok-scraper-api4.p.rapidapi.com');
        $rapidapi_key = env('RAPIDAPI_KEY', '');

        $url = "https://{$rapidapi_host}/api/v1/user/info?unique_id=$username";
        $headers = [
            "X-RapidAPI-Host: {$rapidapi_host}",
            "X-RapidAPI-Key: {$rapidapi_key}"
        ];

        return $this->curlRequest($url, $headers);
    }

    function getDataFromSecondEndpoint($username)
    {
        $rapidapi_host = env('RAPIDAPI_HOST', 'tiktok-scraper-api4.p.rapidapi.com');
        $rapidapi_key = env('RAPIDAPI_KEY', '');

        $url = "https://{$rapidapi_host}/api/v1/search/users?keyword=$username";
        $headers = [
            "X-RapidAPI-Host: {$rapidapi_host}",
            "X-RapidAPI-Key: {$rapidapi_key}"
        ];

        return $this->curlRequest($url, $headers);
    }

    function get_account_id($type, $url)
    {
        $response = [
            "status" => true,
            "msg" => "",
            "data" => []
        ];

        $uri = explode("/", parse_url($url, PHP_URL_PATH));
        $username = $uri[1] ?? null;

        if (empty($url) || empty($username)) {
            return [
                "status" => false,
                "msg" => "Pastikan URL sudah diisi!",
                "data" => []
            ];
        }

        if ($type == "Instagram") {
            $response["status"] = false;
            $response["msg"] = "Layanan belum tersedia";
            $response["data"] = array();

            // $curl = curl_init();

            // curl_setopt_array($curl, [
            //     CURLOPT_URL => "https://api.instagapi.com/userid/$username",
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_ENCODING => "",
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 30,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => "GET",
            //     CURLOPT_HTTPHEADER => [
            //         "X-InstagAPI-Key: 0aaf6108af3c2962ff24720ffe09748b"
            //     ],
            // ]);

            // $responseCurl = curl_exec($curl);
            // curl_close($curl);

            // $responseCurl = json_decode($responseCurl, true);

            // if ($responseCurl['data']) {
            //     $data['account_id'] = intval($responseCurl['data']);

            //     $curl = curl_init();
            //     curl_setopt_array($curl, [
            //         CURLOPT_URL => "https://api.instagapi.com/usercontact/" . $responseCurl['data'],
            //         CURLOPT_RETURNTRANSFER => true,
            //         CURLOPT_ENCODING => "",
            //         CURLOPT_MAXREDIRS => 10,
            //         CURLOPT_TIMEOUT => 30,
            //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //         CURLOPT_CUSTOMREQUEST => "GET",
            //         CURLOPT_HTTPHEADER => [
            //             "X-InstagAPI-Key: 0aaf6108af3c2962ff24720ffe09748b"
            //         ],
            //     ]);
            //     $userDetail = curl_exec($curl);
            //     curl_close($curl);

            //     $userDetail = json_decode($userDetail, true);

            //     $data['img'] = strval($userDetail['data']['user']['hd_profile_pic_url_info']['url']);
            //     $data['follower'] = intval($userDetail['data']['user']['follower_count']);
            //     $data['media_count'] = intval($userDetail['data']['user']['media_count']);

            //     return [
            //         "status" => true,
            //         "msg" => "Data ditemukan",
            //         "data" => $data
            //     ];
            // } else {
            //     return [
            //         "status" => false,
            //         "msg" => "Username <b>$username</b> tidak ditemukan",
            //         "data" => []
            //     ];
            // }
        } else if ($type == "Tiktok") {
            $username = str_replace('@', '', $username);

            $resp1 = $this->getDataFromFirstEndpoint($username);
            $isEmpty = true;

            if (isset($resp1['status']) && $resp1['status'] == 'Successful') {
                if (!empty($resp1['data'][0]['uid'])) {
                    $isEmpty = false;
                    $visible_videos_count = $resp1['data'][0]['aweme_count'] ?? null;
                    $follower_count = $resp1['data'][0]['follower_count'] ?? null;
                    $uid = $resp1['data'][0]['uid'] ?? null;
                    $avatar_urls = $resp1['data'][0]['avatar_larger']['url_list'][0] ?? null;
                    $nickname = $resp1['data'][0]['nickname'] ?? null;

                    $data = [
                        "account_id" => strval($uid),
                        "follower" => intval($follower_count),
                        "media_count" => intval($visible_videos_count),
                        "img" => $avatar_urls,
                        "full_name" => $nickname,
                        "source" => "first_endpoint"
                    ];

                    return [
                        "status" => true,
                        "msg" => "Data ditemukan",
                        "data" => $data
                    ];
                }
            }

            if ($isEmpty) {
                $resp2 = $this->getDataFromSecondEndpoint($username);

                if (isset($resp2['status']) && $resp2['status'] === 'Successful' && !empty($resp2['data'][0]['user_info'])) {
                    $userData = $resp2['data'][0]['user_info'];

                    $uid = $userData['sec_uid'] ?? null;
                    $follower_count = $userData['follower_count'] ?? null;
                    $visible_videos_count = $userData['video_count'] ?? null;
                    $avatar_urls = $userData['avatar_thumb']['url_list'][0] ?? null;
                    $unique_id = $userData['unique_id'] ?? null;
                    $nickname = $userData['nickname'] ?? null;

                    $data = [
                        "account_id"   => strval($uid),
                        "follower"     => intval($follower_count),
                        "media_count"  => intval($visible_videos_count),
                        "img"          => $avatar_urls,
                        "full_name"    => $nickname,
                    ];

                    return [
                        "status" => true,
                        "msg"    => "Data ditemukan",
                        "data"   => $data
                    ];
                }
            }


            return [
                "status" => false,
                "msg" => "Username <b>$username</b> tidak ditemukan dari kedua endpoint",
                "data" => []
            ];
        } else {
            return [
                "status" => false,
                "msg" => "Platform belum tersedia",
                "data" => []
            ];
        }
    }


    function get_post_list($type, $account_id)
    {
        $response = array();
        $response["status"] = true;
        $response["msg"] = "";
        $response["data"] = array();

        if (empty($account_id)) {
            $response["status"] = false;
            $response["msg"] = "Pastikan account id sudah diisi!";
            $response["data"] = array();
        } else if ($type == "Instagram") {
            $response["status"] = false;
            $response["msg"] = "Layanan belum tersedia";
            $response["data"] = array();
            // $curl = curl_init();
            // $end_cursor = '';
            // curl_setopt_array($curl, [
            //     CURLOPT_URL => "https://api.instagapi.com/userreels/$account_id/10/$end_cursor",
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_ENCODING => "",
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 30,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => "GET",
            //     CURLOPT_HTTPHEADER => [
            //         "X-InstagAPI-Key: 0aaf6108af3c2962ff24720ffe09748b"
            //     ],
            // ]);

            // $response = curl_exec($curl);
            // $err = curl_error($curl);

            // curl_close($curl);

            // $response = json_decode($response, true);
            // if ($response['data']['items']) {
            //     $response["status"] = true;
            //     $response["msg"] = "Data ditemukan";
            //     $arr = array();
            //     foreach ($response['data']['items'] as $k => $v) {
            //         $detail = $v['media'];
            //         $arr[$k]["like"] = intval($detail['like_count']);
            //         $arr[$k]["share"] = intval($detail['stats']['shareCount']);
            //         $arr[$k]["comment"] = intval($detail['comment_count']);
            //         $arr[$k]["collect"] = intval($detail['stats']['collectCount']);
            //         $arr[$k]["view"] = intval($detail['play_count']);
            //     }
            //     $response["data"] = $arr;
            // } else {
            //     $response["status"] = false;
            //     $response["msg"] = "Data reels account id :  <b>" . $account_id . "</b> tidak ditemukan";
            //     $response["data"] = array();
            // }
        } else if ($type == "Tiktok") {
            $rapidapi_host = env('RAPIDAPI_HOST', 'tiktok-scraper-api4.p.rapidapi.com');
            $rapidapi_key = env('RAPIDAPI_KEY', '');

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://{$rapidapi_host}/api/v1/user/posts?sec_uid=" . urlencode($account_id) . "&count=10",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "X-RapidAPI-Host: {$rapidapi_host}",
                    "X-RapidAPI-Key: {$rapidapi_key}",
                    "Accept: application/json"
                ],
            ]);

            $response = curl_exec($curl);

            if ($response === false) {
                echo 'cURL error: ' . curl_error($curl);
                die;
            }

            curl_close($curl);

            $response = json_decode($response, true);

            if ($response['status'] == 'Successful') {
                $response["status"] = true;
                $response["msg"] = "Data ditemukan";

                $response['data'] = array_slice($response['data'], 0, 10);

                $arr = array();
                foreach ($response['data'] as $k => $v) {
                    $detail = $v['stats'];
                    $arr[$k]["like"]    = intval($detail['diggCount']);
                    $arr[$k]["share"]   = intval($detail['shareCount']);
                    $arr[$k]["comment"] = intval($detail['commentCount']);
                    $arr[$k]["collect"] = intval($detail['collectCount']);
                    $arr[$k]["view"]    = intval($detail['playCount']);
                }
                $response["data"] = $arr;
            } else {
                $response["status"] = false;
                $response["msg"] = "Data video tiktok account id :  <b>" . $account_id . "</b> tidak ditemukan";
                $response["data"] = array();
            }

        } else {
            $response["status"] = false;
            $response["msg"] = "Platform belum tersedia";
            $response["data"] = array();
        }
        return $response;
    }

    function get_social_media($type, $url)
    {
        $response = array();
        $response["status"] = true;
        $response["msg"] = "";
        $response["data"] = array();
        if ($type == "Tiktok") {
            if ($url) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:100.0) Gecko/20100101 Firefox/100.0',
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Cookie: tt_chain_token=+O8Mw9RH4nKrX/ACdOBhXw==; tt_csrf_token=27TtpaB8-Wftkj0rFR_w6LdtcAp4tdDCFfBY; ttwid=1%7CdJI7LAdiTNKwSISqHad9wDTJ6G_70WU_PGro2isx-ac%7C1705385087%7C518efef116162148489d7f25fa3c7b06633a23c824590f04ea0226d5c2b6f092'
                    ),
                ));

                $responsee = curl_exec($curl);

                curl_close($curl);

                $html = "$responsee";

                $pattern = '/<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application\/json">(.*?)<\/script>/s';
                preg_match($pattern, $html, $matches);
                if (isset($matches[1])) {
                    $jsonContent = $matches[1];
                    $jsonData = json_decode($jsonContent, true)['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct'];
                    $response["status"] = true;
                    $response["msg"] = "";
                    if (intval($jsonData['stats']['playCount']) > 0) {
                        $response["data"]["like"] = intval($jsonData['stats']['diggCount']);
                        $response["data"]["share"] = intval($jsonData['stats']['shareCount']);
                        $response["data"]["comment"] = intval($jsonData['stats']['commentCount']);
                        $response["data"]["collect"] = intval($jsonData['stats']['collectCount']);
                        $response["data"]["view"] = intval($jsonData['stats']['playCount']);
                        $response['data']['created_at'] = DATE("Y-m-d", $jsonData['createTime']);
                    } else {
                        $parts = explode('/photo/', $url);
                        $parts = explode('?', end($parts));
                        $content_id = $parts[0];

                        $rapidapi_host = env('RAPIDAPI_HOST', 'tiktok-scraper-api4.p.rapidapi.com');
                        $rapidapi_key = env('RAPIDAPI_KEY', '');

                        $curl = curl_init();

                        curl_setopt_array($curl, [
                            CURLOPT_URL => "https://{$rapidapi_host}/api/v1/post/info?video_id=$content_id",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "GET",
                            CURLOPT_HTTPHEADER => [
                                "X-RapidAPI-Host: {$rapidapi_host}",
                                "X-RapidAPI-Key: {$rapidapi_key}"
                            ],
                        ]);

                        $responsee = curl_exec($curl);

                        curl_close($curl);

                        $responsee = json_decode($responsee, true);
                        
                        $jsonData = $responsee['data']['stats'];
                        if (intval($jsonData['playCount']) > 0) {
                            $response["data"]["like"] = intval($jsonData['diggCount']);
                            $response["data"]["share"] = intval($jsonData['shareCount']);
                            $response["data"]["comment"] = intval($jsonData['commentCount']);
                            $response["data"]["collect"] = intval($jsonData['collectCount']);
                            $response["data"]["view"] = intval($jsonData['playCount']);
                            $response["data"]["created_at"] = date("Y-m-d", $responsee['data']['createTime']);
                        } else {
                            $response["status"] = false;
                            $response["msg"] = "Response tiktok " . $content_id . " tidak ditemukan";
                            $response["data"] = array();
                        }
                    }
                } else {
                    $response["status"] = false;
                    $response["msg"] = "Response tiktok " . $url . " tidak ditemukan";
                    $response["data"] = array();
                }
            } else {
                $response["status"] = false;
                $response["msg"] = "URL tidak ditemukan";
                $response["data"] = array();
            }
        } else if ($type == "Instagram") {
            $response["status"] = false;
            $response["msg"] = "Layanan belum tersedia";
            $response["data"] = array();

            // if ($url) {

            //     $curl = curl_init();
            //     $code = end(explode("reel/", $url));
            //     if ($code == $url) {
            //         $code = end(explode("p/", $url));
            //     }
            //     $code = explode('/', $code)[0];

            //     curl_setopt_array($curl, array(
            //         CURLOPT_URL => 'https://api.instagapi.com/postdetail/' . $code,
            //         CURLOPT_RETURNTRANSFER => true,
            //         CURLOPT_ENCODING => '',
            //         CURLOPT_MAXREDIRS => 10,
            //         CURLOPT_TIMEOUT => 0,
            //         CURLOPT_FOLLOWLOCATION => true,
            //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //         CURLOPT_CUSTOMREQUEST => 'GET',
            //         CURLOPT_HTTPHEADER => array(
            //             'X-InstagAPI-Key: 0aaf6108af3c2962ff24720ffe09748b',
            //             'Cookie: PHPSESSID=3f6obv20o5p0jbo2j4i94dml0k'
            //         ),
            //     ));

            //     $response_2 = curl_exec($curl);

            //     curl_close($curl);
            //     $json = json_decode($response_2, true);
            //     $jsonData = $json['data'];
            //     if ($jsonData) {
            //         $response["status"] = true;
            //         $response["msg"] = "";
            //         // $response["data"]["like"] = intval($jsonData['like_count']);
            //         // $response["data"]["share"] = intval($jsonData['stats']['shareCount']);
            //         // $response["data"]["comment"] = intval($jsonData['comment_count']);
            //         // $response["data"]["collect"] = intval($jsonData['stats']['collectCount']);
            //         // $response["data"]["view"] = intval($jsonData['play_count']);
            //         curl_setopt_array($curl, array(
            //             CURLOPT_URL => 'https://api.instagapi.com/postlikes/' . $code . '/1/',
            //             CURLOPT_RETURNTRANSFER => true,
            //             CURLOPT_ENCODING => '',
            //             CURLOPT_MAXREDIRS => 10,
            //             CURLOPT_TIMEOUT => 0,
            //             CURLOPT_FOLLOWLOCATION => true,
            //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //             CURLOPT_CUSTOMREQUEST => 'GET',
            //             CURLOPT_HTTPHEADER => array(
            //                 'X-InstagAPI-Key: 0aaf6108af3c2962ff24720ffe09748b',
            //                 'Cookie: PHPSESSID=3f6obv20o5p0jbo2j4i94dml0k'
            //             ),
            //         ));

            //         $response_2 = curl_exec($curl);
            //         $jsonData2 = json_decode($response_2, true);
            //         $jsonData2 = $jsonData2['data'];

            //         $response["data"]["like"] = intval($jsonData2['count']);
            //         $response["data"]["share"] = intval($jsonData['shareCount']);
            //         $response["data"]["comment"] = intval($jsonData['edge_media_to_parent_comment']['count']);
            //         $response["data"]["collect"] = intval($jsonData['collectCount']);
            //         $response["data"]["view"] = intval($jsonData['video_play_count']);
            //         $response['data']['created_at'] = DATE("Y-m-d", $jsonData['taken_at']);
            //     } else {
            //         $response["status"] = false;
            //         $response["msg"] = "Response instagram tidak ditemukan";
            //         $response["data"] = array();
            //     }
            // } else {
            //     $response["status"] = false;
            //     $response["msg"] = "URL tidak ditemukan";
            //     $response["data"] = array();
            // }
        } else {
            $response["status"] = false;
            $response["msg"] = "Platform belum tersedia";
            $response["data"] = array();
        }
        return $response;
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
        $text = str_replace(array("\r", "\n"), '', $text);
        $text = '<script>
                        $( document ).ready(function() {
                        $.toast({
                            heading: "Informasi",
                            text: "' . $text . '",
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
        $text = str_replace(array("\r", "\n"), '', $text);
        $text = '<script success type="text/javascript">
                    $( document ).ready(function() {
                    $.toast({
                        heading: "Informasi",
                        text: "' . $text . '",
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
