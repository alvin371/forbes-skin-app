<?php

class Template
{
    protected $ci;
    protected $sidebar_cache = [];

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function index() {}

    function endpoint_url()
    {
        // return 'https://endpoint.acnenosystem.com/';
        return base_url();
    }

    /**
     * Get dynamic sidebar menu for user based on permissions
     *
     * @param int $user_id User ID
     * @return array Hierarchical menu structure grouped by category
     */
    public function get_sidebar_menu($user_id)
    {
        // Return cached result if available
        if (isset($this->sidebar_cache[$user_id])) {
            return $this->sidebar_cache[$user_id];
        }

        $this->ci->load->model('mymodel');

        // Get all sidebar modules with user permissions
        $modules = $this->ci->mymodel->selectWithQuery("
            SELECT
                m.id,
                m.name,
                m.display_name,
                m.icon,
                m.url,
                m.category,
                m.parent_id,
                m.sort_order,
                COALESCE(MAX(rp.can_view), 0) as can_view
            FROM modules m
            LEFT JOIN role_permissions rp ON m.id = rp.module_id
            LEFT JOIN user_roles ur ON rp.role_id = ur.role_id AND ur.user_id = '$user_id'
            WHERE m.is_active = 1
            AND m.show_in_sidebar = 1
            AND m.url IS NOT NULL
            AND m.url != ''
            GROUP BY m.id, m.name, m.display_name, m.icon, m.url, m.category, m.parent_id, m.sort_order
            HAVING can_view = 1
            ORDER BY m.category, m.sort_order, m.display_name
        ");

        // Group by category
        $menu = $this->build_sidebar_structure($modules);

        // Cache the result
        $this->sidebar_cache[$user_id] = $menu;

        return $menu;
    }

    /**
     * Build hierarchical sidebar structure grouped by category
     *
     * @param array $modules Flat array of modules
     * @return array Structured menu
     */
    private function build_sidebar_structure($modules)
    {
        // Define category configuration with display names
        $category_config = [
            'System Management' => [
                'display_name' => 'SYSTEM',
                'is_flat' => true
            ],
            'Reports & Analytics' => [
                'display_name' => 'REPORTS',
                'is_flat' => true
            ],
            'Marketing' => [
                'display_name' => 'MARKETING',
                'is_flat' => false,
                'submenu_groups' => [
                    'overview' => ['marketing'],
                    'advertiser' => ['ads_tiktok', 'ads_meta', 'ads_shopee', 'ads_lazada'],
                    'endorsement' => ['influencer', 'influencer_dummy', 'endorse_campaign', 'calendar', 'payment', 'codeboost']
                ]
            ],
            'Operations' => [
                'display_name' => 'ORDER & CUSTOMER',
                'is_flat' => false
            ],
            'HR Management' => [
                'display_name' => 'HR MANAGEMENT',
                'is_flat' => false
            ]
        ];

        // Category order for sorting
        $category_order = [
            'System Management',
            'Reports & Analytics',
            'Marketing',
            'Operations',
            'HR Management'
        ];

        // Group modules by category
        $grouped = [];
        foreach ($modules as $module) {
            $category = $module['category'] ?? 'System Management';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $module;
        }

        // Build final menu structure
        $menu = [];
        foreach ($category_order as $category) {
            if (!isset($grouped[$category]) || empty($grouped[$category])) {
                continue;
            }

            $config = $category_config[$category] ?? [
                'display_name' => strtoupper($category),
                'is_flat' => false
            ];

            $menu[$category] = [
                'display_name' => $config['display_name'],
                'is_flat' => $config['is_flat'],
                'items' => $grouped[$category],
                'submenu_groups' => $config['submenu_groups'] ?? null
            ];
        }

        // Add any remaining categories not in the predefined order
        foreach ($grouped as $category => $items) {
            if (!isset($menu[$category])) {
                $menu[$category] = [
                    'display_name' => strtoupper($category),
                    'is_flat' => false,
                    'items' => $items
                ];
            }
        }

        return $menu;
    }

    /**
     * Get current active menu based on URI
     *
     * @return array Active menu info [category, module_name, submenu_group]
     */
    public function get_active_menu()
    {
        $uri_1 = $this->ci->uri->segment(1);
        $uri_2 = $this->ci->uri->segment(2);
        $m = isset($_GET['m']) ? $_GET['m'] : '';
        $brand = isset($_GET['brand']) ? $_GET['brand'] : '';

        $active = [
            'category' => null,
            'module' => null,
            'submenu' => null
        ];

        // Map URI to module and category
        $uri_mapping = [
            'dashboard' => ['System Management', 'dashboard', null],
            'report' => ['Reports & Analytics', 'report', null],
            'expense' => ['Reports & Analytics', 'expense', null],
            'overview' => ['Marketing', 'marketing', 'overview'],
            'ads' => ['Marketing', 'ads_' . $m, 'advertiser'],
            'influencer' => ['Marketing', 'influencer', 'endorsement'],
            'influencer-dummy' => ['Marketing', 'influencer_dummy', 'endorsement'],
            'endorse-campaign' => ['Marketing', 'endorse_campaign', 'endorsement'],
            'calendar' => ['Marketing', 'calendar', 'endorsement'],
            'payment' => ['Marketing', 'payment', 'endorsement'],
            'codeboost' => ['Marketing', 'codeboost', 'endorsement'],
            'transaction' => ['Operations', 'transaction', null],
            'transaction-item' => ['Operations', 'transaction_item', null],
            'crm' => ['Operations', $brand ? 'crm_' . strtolower($brand) : 'crm', null],
            'group-wa' => ['Operations', 'group_wa', null],
            'stock' => ['Operations', 'stock', null],
            'product' => ['Operations', 'product', null],
            'attendance' => ['HR Management', 'attendance', null],
            'leave' => ['HR Management', 'leave', null],
            'approvals' => ['HR Management', 'leave_approvals', null],
            'admin' => ['HR Management', $this->map_admin_uri($uri_2), null],
            'user' => ['System Management', 'user', null],
            'roles' => ['System Management', 'roles', null],
            'modules' => ['System Management', 'modules', null],
            'profile' => ['System Management', 'profile', null],
            'quest_level' => ['HR Management', 'quest_level', null],
            'position' => ['HR Management', 'position', null],
            'benefit' => ['HR Management', 'benefit', null],
            'quest' => ['HR Management', 'quest', null],
            'milestone' => ['HR Management', 'milestone', null],
            'recruitment' => ['HR Management', 'recruitment', null]
        ];

        if (isset($uri_mapping[$uri_1])) {
            $active['category'] = $uri_mapping[$uri_1][0];
            $active['module'] = $uri_mapping[$uri_1][1];
            $active['submenu'] = $uri_mapping[$uri_1][2];
        }

        return $active;
    }

    /**
     * Map admin URI segment to module name
     */
    private function map_admin_uri($uri_2)
    {
        $mapping = [
            'offices' => 'offices',
            'leave-types' => 'leave_types',
            'leave-quotas' => 'leave_quotas',
            'approval-routes' => 'approval_routes',
            'holidays' => 'holidays',
            'attendance-settings' => 'attendance_settings',
            'performance-appraisal' => 'performance_admin'
        ];

        return $mapping[$uri_2] ?? 'offices';
    }

    /**
     * Check if a module is active based on current URI
     *
     * @param string $module_name Module name
     * @return bool
     */
    public function is_module_active($module_name)
    {
        $active = $this->get_active_menu();
        return $active['module'] === $module_name;
    }

    /**
     * Check if a category is expanded based on current URI
     *
     * @param string $category_name Category name
     * @return bool
     */
    public function is_category_expanded($category_name)
    {
        $active = $this->get_active_menu();
        return $active['category'] === $category_name;
    }

    /**
     * Clear sidebar cache for a user
     *
     * @param int $user_id User ID (null to clear all)
     */
    public function clear_sidebar_cache($user_id = null)
    {
        if ($user_id) {
            unset($this->sidebar_cache[$user_id]);
        } else {
            $this->sidebar_cache = [];
        }
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
