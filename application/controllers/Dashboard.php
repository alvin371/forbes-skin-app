<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Dashboard extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('permission');
        $this->load->library('template');

        // Initialize cache system for dashboard performance optimization
        try {
            // Try Memcached first
            $this->load->driver('cache', array('adapter' => 'memcached'));
            log_message('info', 'Memcached cache initialized successfully');
        } catch (Exception $e) {
            log_message('error', 'Memcached initialization failed, falling back to file cache: ' . $e->getMessage());
            try {
                // Fallback to file cache if Memcached fails
                $this->load->driver('cache', array('adapter' => 'file'));
                log_message('info', 'File cache initialized as fallback');
            } catch (Exception $e2) {
                log_message('error', 'All cache initialization failed: ' . $e2->getMessage());
            }
        }
    }

    public function index()
    {
        // Validate session data with error handling
        $data['checkbox'] = isset($_SESSION['checkbox_dashboard']) ? $_SESSION['checkbox_dashboard'] : [];
        $data['checkbox_campaign'] = isset($_SESSION['checkbox_dashboard_campaign']) ? $_SESSION['checkbox_dashboard_campaign'] : [];
        
        // BaseController already handles session validation and permission checking
        $user = $_SESSION['user'];
        $user_id = $user['id'];
        
        $data['title'] = 'Dashboard - ' . $this->template->title();

        // Cache brands list for 60 seconds to improve performance
        $brands_cache_key = 'brands_list_enable';
        if (isset($this->cache)) {
            $cached_brands = $this->cache->get($brands_cache_key);
            if ($cached_brands !== FALSE) {
                $data['brands'] = $cached_brands;
            } else {
                $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
                $data['brands'] = $query;
                $this->cache->save($brands_cache_key, $query, 60);
            }
        } else {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
            $data['brands'] = $query;
        }

        // Cache all channels list for 60 seconds
        $channels_cache_key = 'channels_list_all';
        if (isset($this->cache)) {
            $cached_channels_all = $this->cache->get($channels_cache_key);
            if ($cached_channels_all !== FALSE) {
                $data['channel_2'] = $cached_channels_all;
            } else {
                $data['channel_2'] = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
                $this->cache->save($channels_cache_key, $data['channel_2'], 60);
            }
        } else {
            $data['channel_2'] = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
        }

        // Cache main channels list for 60 seconds
        $main_channels_cache_key = 'channels_list_main';
        if (isset($this->cache)) {
            $cached_channels_main = $this->cache->get($main_channels_cache_key);
            if ($cached_channels_main !== FALSE) {
                $data['channel'] = $cached_channels_main;
            } else {
                $data['channel'] = $this->mymodel->selectWithQuery("SELECT * FROM marketplace WHERE name IN ('SHOPEE','LAZADA','TIKTOK','WA') ORDER BY name ASC");
                $this->cache->save($main_channels_cache_key, $data['channel'], 60);
            }
        } else {
            $data['channel'] = $this->mymodel->selectWithQuery("SELECT * FROM marketplace WHERE name IN ('SHOPEE','LAZADA','TIKTOK','WA') ORDER BY name ASC");
        }


        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE("Y-m-01");
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $data['title_2'] = $this->template->date_format_indo($start_date) . ' - ' . $this->template->date_format_indo($until_date);

        $url = base_url() . '/dashboard/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['url_2'] = $this->template->get_param_without('status');
        $data['param'] = $this->template->get_param();

        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime("$today -7 days"));
        }
        if (empty($until_date)) {
            $until_date = $today;
        }

        // Get brand filter from GET parameters
        $brand_filter = $_GET['brand'] ?? '';
        $firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';

        $shopee_brand = !empty($firstLetter) ? "AND shop_name LIKE '{$firstLetter}%'" : "";
        $tiktok_brand = !empty($firstLetter) ? "AND advertiser_name LIKE '{$firstLetter}%'" : "";
        $meta_brand = !empty($firstLetter) ? "AND account_name LIKE '{$firstLetter}%'" : "";

        $sql_spend_ads = "
        SELECT 
            COALESCE(SUM(shopee.expense), 0) + 
            COALESCE(SUM(meta.spend), 0) + 
            COALESCE(SUM(tiktok.spend_idr), 0) + 
            COALESCE(SUM(gmv.spend_idr_after_tax), 0) AS total_spend_ads
        FROM (
            SELECT 
                DATE(date) AS date,
                SUM(expense_after_tax) AS expense
            FROM 
                shopee_ads_data
            INNER JOIN 
                marketplace_config 
                ON marketplace_config.shop_id = shopee_ads_data.shop_id
            WHERE 
                DATE(date) BETWEEN '$start_date' AND '$until_date'
                $shopee_brand
            GROUP BY 
                DATE(date)
        ) AS shopee
        LEFT JOIN (
            SELECT 
                DATE(date) AS date,
                SUM(spend_after_tax) AS spend
            FROM 
                meta_ads_data
            INNER JOIN 
                ads_meta_account 
                ON meta_ads_data.account_id = ads_meta_account.account_id
            WHERE 
                DATE(date) BETWEEN '$start_date' AND '$until_date'
                $meta_brand
            GROUP BY 
                DATE(date)
        ) AS meta ON shopee.date = meta.date
        LEFT JOIN (
            SELECT 
                DATE(date) AS date,
                SUM(spend_idr_after_tax) AS spend_idr
            FROM 
                tiktok_ads_data
            WHERE 
                DATE(date) BETWEEN '$start_date' AND '$until_date'
                $tiktok_brand
            GROUP BY 
                DATE(date)
        ) AS tiktok ON shopee.date = tiktok.date
        LEFT JOIN (
            SELECT 
                DATE(date) AS date,
                SUM(spend_idr_after_tax) AS spend_idr_after_tax
            FROM 
                advertiser_spend
            WHERE 
                DATE(date) BETWEEN '$start_date' AND '$until_date'
                AND advertiser_name LIKE '{$firstLetter}%'
            GROUP BY 
                DATE(date)
        ) AS gmv ON shopee.date = gmv.date;
    ";

        // Use optimized ads spending calculation with caching
        $total_spend_ads = $this->calculate_ads_spending($start_date, $until_date, $brand_filter);
        $data['spend_ads'] = ['total_spend_ads' => $total_spend_ads];

        // Use optimized KOL spending calculation with caching
        $total_spend_kol = $this->calculate_kol_spending($start_date, $until_date, $brand_filter);
        $data['spend_kol'] = ['total_spend_kol' => $total_spend_kol];

        // Use optimized etc spending calculation with caching  
        $total_spend_etc = $this->calculate_etc_spending($start_date, $until_date, $brand_filter);
        $data['spend_etc'] = ['total_spend_etc' => $total_spend_etc];

        if ($_GET['t'] == "kol") {
            $data['campaign'] = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_campaign
            ORDER BY title ASC");
            $data['content'] = $this->load->view('dashboard/all-kol', $data, true);
        } else  if ($_GET['t'] == "influencer") {
            $data['campaign'] = $this->mymodel->selectWithQuery("
           SELECT *
           FROM endorse_campaign
           ORDER BY title ASC");
            $data['content'] = $this->load->view('dashboard/all-influencer', $data, true);
        } else {
            $data['content'] = $this->load->view('dashboard/all', $data, true);
        }
        $this->load->view('TemplateDashboard', $data);
    }

    public function expense()
    {
        $data['title'] = 'Dashboard Expense - ' . $this->template->title();
        $today = date('Y-m-d');
        $start_date   = $this->input->get('start_date');
        $until_date   = $this->input->get('until_date');
        $brand_filter = $this->input->get('brand');

        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime("$today -31 days"));
        }
        if (empty($until_date)) {
            $until_date = $today;
        }

        $firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';

        $shopee_brand = !empty($firstLetter) ? "AND shop_name LIKE '{$firstLetter}%'" : "";
        $tiktok_brand = !empty($firstLetter) ? "AND advertiser_name LIKE '{$firstLetter}%'" : "";
        $meta_brand   = !empty($firstLetter) ? "AND account_name LIKE '{$firstLetter}%'" : "";
        // (BARU) brand filter khusus expense, mengikuti gaya yang sama:
        $expense_brand = !empty($firstLetter) ? "AND e.brand LIKE '{$firstLetter}%'" : "";

        // Get net sales (biarkan sesuai gaya kamu)
        $sql_net_sales = "SELECT SUM(omset_kotor-diskon_penjual) as result FROM transaction 
                        WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' 
                        AND type_sub = 'POS' 
                        AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') 
                        $qry";
        $net_sales_result = $this->mymodel->selectWithQuery($sql_net_sales);
        $data['net_sales'] = !empty($net_sales_result) ? $net_sales_result[0]['result'] : 0;

        // ====== Tetap: total Ads dari berbagai sumber (biarkan seperti semula) ======
        $sql_spend_ads = "
            SELECT 
                COALESCE(SUM(shopee.expense), 0) + 
                COALESCE(SUM(meta.spend), 0) + 
                COALESCE(SUM(tiktok.spend_idr), 0) + 
                COALESCE(SUM(gmv.spend_idr_after_tax), 0) AS total_spend_ads
            FROM (
                SELECT DATE(date) AS date, SUM(expense_after_tax) AS expense
                FROM shopee_ads_data
                INNER JOIN marketplace_config ON marketplace_config.shop_id = shopee_ads_data.shop_id
                WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $shopee_brand
                GROUP BY DATE(date)
            ) AS shopee
            LEFT JOIN (
                SELECT DATE(date) AS date, SUM(spend_after_tax) AS spend
                FROM meta_ads_data
                INNER JOIN ads_meta_account ON meta_ads_data.account_id = ads_meta_account.account_id
                WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $meta_brand
                GROUP BY DATE(date)
            ) AS meta ON shopee.date = meta.date
            LEFT JOIN (
                SELECT DATE(date) AS date, SUM(spend_idr_after_tax) AS spend_idr
                FROM tiktok_ads_data
                WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $tiktok_brand
                GROUP BY DATE(date)
            ) AS tiktok ON shopee.date = tiktok.date
            LEFT JOIN (
                SELECT DATE(date) AS date, SUM(spend_idr_after_tax) AS spend_idr_after_tax
                FROM advertiser_spend
                WHERE DATE(date) BETWEEN '$start_date' AND '$until_date'
                AND advertiser_name LIKE '{$firstLetter}%'
                GROUP BY DATE(date)
            ) AS gmv ON shopee.date = gmv.date;
        ";
        $data['spend_ads'] = $this->mymodel->selectWithQuery($sql_spend_ads);
        $data['spend_ads'] = !empty($data['spend_ads']) ? $data['spend_ads'][0] : ['total_spend_ads' => 0];

        // ====== Tetap: total KOL (biarkan seperti semula) ======
        $sql_spend_kol = "
            SELECT
                SUM(pl.nominal_dibayarkan) AS total_spend_kol
            FROM payment_logs pl
            JOIN endorse_campaign ec ON pl.id_campaign = ec.id
            WHERE DATE(pl.created_at) >= '$start_date' AND DATE(pl.created_at) <= '$until_date' AND pl.status_payment IN ('FP', 'DP')
            " . (!empty($firstLetter) ? "AND ec.brand LIKE '{$firstLetter}%'" : "") . "
        ";
        $data['spend_kol'] = $this->mymodel->selectWithQuery($sql_spend_kol);
        $data['spend_kol'] = !empty($data['spend_kol']) ? $data['spend_kol'][0] : ['total_spend_kol' => 0];

        // ====== (BARU) Ambil total expense per kategori secara dinamis ======
        // Catatan:
        // - ABS(SUM(e.price_total)) mengikuti gaya kamu sebelumnya (hilangkan minus)
        // - Kategori kosong/null kita jadikan label "Lain-lain"
        $sql_expense_by_category = "
            SELECT 
                COALESCE(NULLIF(TRIM(e.category), ''), 'Lain-lain') AS category,
                ABS(SUM(e.price_total)) AS total_spend
            FROM expense e
            WHERE DATE(e.date) BETWEEN '$start_date' AND '$until_date'
            $expense_brand
            GROUP BY COALESCE(NULLIF(TRIM(e.category), ''), 'Lain-lain')
            ORDER BY total_spend DESC
        ";
        $data['expense_by_category'] = $this->mymodel->selectWithQuery($sql_expense_by_category);
        if (empty($data['expense_by_category'])) { $data['expense_by_category'] = []; }

        // (Opsional) total expense kumulatif dari seluruh kategori (untuk perhitungan all spend)
        $total_expense_all_cat = 0;
        foreach ($data['expense_by_category'] as $row) {
            $total_expense_all_cat += (float)($row['total_spend'] ?? 0);
        }
        $data['total_expense_all_cat'] = $total_expense_all_cat;

        // (Opsional) Kamu boleh menghapus blok lama 'spend_etc' & 'spend_marketing' kalau tidak dipakai lagi,
        // tapi kalau mau dibiarkan juga tidak masalah agar backward-compatible.

        // Use cached brand list for better performance
        $brands_cache_key = 'brands_list_enable';
        if (isset($this->cache)) {
            $cached_brands = $this->cache->get($brands_cache_key);
            if ($cached_brands !== FALSE) {
                $data['brands'] = $cached_brands;
            } else {
                $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
                $data['brands'] = $query;
                $this->cache->save($brands_cache_key, $query, 60);
            }
        } else {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
            $data['brands'] = $query;
        }

        $data['content'] = $this->load->view('dashboard/expense', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }


    public function expense_data()
    {
        $start_date   = $this->input->get('start_date');
        $until_date   = $this->input->get('until_date');
        $brand_filter = $this->input->get('brand');

        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime("-31 days"));
        }
        if (empty($until_date)) {
            $until_date = date('Y-m-d');
        }

        $firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';

        $shopee_brand = !empty($firstLetter) ? "AND shop_name LIKE '{$firstLetter}%'" : "";
        $tiktok_brand = !empty($firstLetter) ? "AND advertiser_name LIKE '{$firstLetter}%'" : "";
        $meta_brand   = !empty($firstLetter) ? "AND account_name LIKE '{$firstLetter}%'" : "";
        $expense_brand = !empty($firstLetter) ? "AND e.brand LIKE '{$firstLetter}%'" : "";

        $sql_spend_ads = "
            SELECT 
                COALESCE(SUM(shopee.expense), 0) + 
                COALESCE(SUM(meta.spend), 0) + 
                COALESCE(SUM(tiktok.spend_idr), 0) + 
                COALESCE(SUM(gmv.spend_idr_after_tax), 0) AS total_spend_ads
            FROM (
                SELECT DATE(date) AS date, SUM(expense_after_tax) AS expense
                FROM shopee_ads_data
                INNER JOIN marketplace_config ON marketplace_config.shop_id = shopee_ads_data.shop_id
                WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $shopee_brand
                GROUP BY DATE(date)
            ) AS shopee
            LEFT JOIN (
                SELECT DATE(date) AS date, SUM(spend_after_tax) AS spend
                FROM meta_ads_data
                INNER JOIN ads_meta_account ON meta_ads_data.account_id = ads_meta_account.account_id
                WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $meta_brand
                GROUP BY DATE(date)
            ) AS meta ON shopee.date = meta.date
            LEFT JOIN (
                SELECT DATE(date) AS date, SUM(spend_idr_after_tax) AS spend_idr
                FROM tiktok_ads_data
                WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $tiktok_brand
                GROUP BY DATE(date)
            ) AS tiktok ON shopee.date = tiktok.date
            LEFT JOIN (
                SELECT DATE(date) AS date, SUM(spend_idr_after_tax) AS spend_idr_after_tax
                FROM advertiser_spend
                WHERE DATE(date) BETWEEN '$start_date' AND '$until_date'
                " . (!empty($firstLetter) ? "AND advertiser_name LIKE '{$firstLetter}%'" : "") . "
                GROUP BY DATE(date)
            ) AS gmv ON shopee.date = gmv.date;
        ";
        $spend_ads = $this->mymodel->selectWithQuery($sql_spend_ads);

        // $sql_spend_kol = "
        //     SELECT
        //         SUM(subquery.nominal_dibayarkan) AS total_spend_kol
        //     FROM (
        //         SELECT DISTINCT
        //             e.nama_creator,
        //             e.nominal_dibayarkan
        //         FROM endorse e
        //         INNER JOIN endorse_campaign c ON c.id = e.id_campaign
        //         WHERE e.tgl_tf BETWEEN '$start_date' AND '$until_date'
        //         " . (!empty($firstLetter) ? "AND c.brand LIKE '{$firstLetter}%'" : "") . "
        //         GROUP BY e.nama_creator
        //     ) AS subquery;
        // ";

        $sql_spend_kol = "
            SELECT
                SUM(pl.nominal_dibayarkan) AS total_spend_kol
            FROM payment_logs pl
            JOIN endorse_campaign ec ON pl.id_campaign = ec.id
            WHERE DATE(pl.created_at) BETWEEN '$start_date' AND '$until_date' AND pl.status_payment IN ('FP', 'DP')
            " . (!empty($firstLetter) ? "AND ec.brand LIKE '{$firstLetter}%'" : "") . "
        ";
        $spend_kol = $this->mymodel->selectWithQuery($sql_spend_kol);

        $sql_expense_by_category = "
            SELECT 
                COALESCE(NULLIF(TRIM(e.category), ''), 'Lain-lain') AS category,
                ABS(SUM(e.price_total)) AS total_spend
            FROM expense e
            WHERE DATE(e.date) BETWEEN '$start_date' AND '$until_date'
            $expense_brand
            GROUP BY COALESCE(NULLIF(TRIM(e.category), ''), 'Lain-lain')
            ORDER BY total_spend DESC
        ";
        $expense_by_category_rows = $this->mymodel->selectWithQuery($sql_expense_by_category);
        if (empty($expense_by_category_rows)) { $expense_by_category_rows = []; }

        $total_expense_all_cat = 0.0;
        foreach ($expense_by_category_rows as $r) {
            $total_expense_all_cat += (float)($r['total_spend'] ?? 0);
        }

        $sql_spend_etc = "
            SELECT 
                ABS(SUM(e.price_total)) AS total_spend_etc
            FROM expense e
            WHERE DATE(e.date) BETWEEN '$start_date' AND '$until_date'
            AND e.category != 'Affiliate'
            " . (!empty($firstLetter) ? "AND e.brand LIKE '{$firstLetter}%'" : "") . "
        ";
        $spend_etc = $this->mymodel->selectWithQuery($sql_spend_etc);

        $sql_spend_marketing = "
            SELECT 
                ABS(SUM(e.price_total)) AS total_spend_marketing
            FROM expense e
            WHERE DATE(e.date) BETWEEN '$start_date' AND '$until_date'
            AND e.category = 'Affiliate'
            " . (!empty($firstLetter) ? "AND e.brand LIKE '{$firstLetter}%'" : "") . "
        ";
        $spend_marketing = $this->mymodel->selectWithQuery($sql_spend_marketing);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'spend_ads'    => !empty($spend_ads) ? $spend_ads[0] : ['total_spend_ads' => 0],
                'spend_kol'    => !empty($spend_kol) ? $spend_kol[0] : ['total_spend_kol' => 0],
                'spend_etc'    => !empty($spend_etc) ? $spend_etc[0] : ['total_spend_etc' => 0],
                'spend_marketing' => !empty($spend_marketing) ? $spend_marketing[0] : ['total_spend_marketing' => 0],
                'expense_by_category'   => $expense_by_category_rows,
                'total_expense_all_cat' => $total_expense_all_cat,
            ]));
    }


    public function net_sales_data()
    {
        $start_date = $this->input->get('start_date');
        $until_date = $this->input->get('until_date');
        $brand_filter = $this->input->get('brand');

        $firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';
        $qry = !empty($firstLetter) ? "AND brand LIKE '{$firstLetter}%'" : "";

        $sql_net_sales = "SELECT SUM(omset_kotor-diskon_penjual) as net_sales FROM transaction 
                        WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' 
                        AND type_sub = 'POS' 
                        AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') 
                        $qry";
        $net_sales_result = $this->mymodel->selectWithQuery($sql_net_sales);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'net_sales' => !empty($net_sales_result) ? $net_sales_result[0]['net_sales'] : 0
            ]));
    }

    public function marketplace_fee()
    {
        $data['title'] = 'Dashboard Marketplace Fee - ' . $this->template->title();
        $today = date('Y-m-d');
        $start_date = $this->input->get('start_date');
        $until_date = $this->input->get('until_date');
        $brand_filter = $this->input->get('brand');
        $brand = $this->input->get('brand');

        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime("$today -31 days"));
        }
        if (empty($until_date)) {
            $until_date = $today;
        }

        $qry = "";
        $qry_2 = "";
        if ($brand) {
            $qry .= " AND brand = '$brand' ";
            $qry_2 .= " AND brand = '$brand' ";
            $qry_trx = " AND p.brand = '$brand' ";
            $qry_stock = " AND b.brand = '$brand' ";
        }

        $sql_marketplace_fee = $this->mymodel->selectWithQuery("
            SELECT 
                SUM(marketplace_fee) AS marketplace_fee,
                SUM(omset_kotor)    AS omset_kotor,
                CONCAT(
                    marketplace, 
                    ' - ', 
                    COALESCE(NULLIF(shop_name, ''), 'Manual')
                ) AS shop_fullname,
                marketplace,
                shop_id
            FROM transaction
            WHERE 
                DATE(date) >= '$start_date'
                AND DATE(date) <= '$until_date'
                AND order_status NOT IN (
                    'RETURN', 'REFUND', 'CANCELLED', 'IN_CANCELLED', 'UNPAID'
                )
                AND type_sub = 'POS'
                $qry
            GROUP BY 
                shop_fullname
            ORDER BY 
                omset_kotor DESC
        ");

        $data['marketplace_fee'] = $sql_marketplace_fee;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $data['content'] = $this->load->view('dashboard/marketplace_fee', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function laba_bersih()
    {
        $data['title'] = 'Dashboard Expense - ' . $this->template->title();
        $today = date('Y-m-d');
        $start_date = $this->input->get('start_date');
        $until_date = $this->input->get('until_date');
        $brand_filter = $this->input->get('brand');
        $brand = $this->input->get('brand');

        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime("$today -31 days"));
        }
        if (empty($until_date)) {
            $until_date = $today;
        }

        $qry = "";
        $qry_2 = "";
        if ($brand) {
            $qry .= " AND brand = '$brand' ";
            $qry_2 .= " AND brand = '$brand' ";
            $qry_trx = " AND p.brand = '$brand' ";
            $qry_stock = " AND b.brand = '$brand' ";
        }

        $sql_penjualan_bersih = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor-diskon_penjual) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND type_sub = 'POS' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') $qry");
        $sql_penjualan_bersih = $sql_penjualan_bersih[0];
        $data['penjualan_bersih'] = $sql_penjualan_bersih['result'];

        $sql_hpp =$this->mymodel->selectWithQuery("
            SELECT 
                ((qty_out_pos + qty_out) * a.price_buy) AS total_hpp
            FROM (
                SELECT * 
                FROM product WHERE is_varian = 0
            ) a
            LEFT JOIN (
                SELECT 
                    a.product,
                    a.brand,
                    a.marketplace,
                    SUM(a.qty_in) AS qty_in,
                    SUM(a.qty_in_pos) AS qty_in_pos,
                    SUM(a.qty_out) AS qty_out,
                    SUM(a.qty_out_pos) AS qty_out_pos,
                    SUM(a.qty) AS qty
                FROM stock a
                LEFT JOIN product p ON a.product = p.id
                WHERE DATE(a.date) >= '$start_date' AND DATE(a.date) <= '$until_date'
                AND a.type_sub = 'POS'
                AND a.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED')
                GROUP BY a.product
            ) b ON a.id = b.product
            WHERE b.qty_out_pos > 0 $qry_stock
            ORDER BY b.qty_out_pos DESC;
        ");

        foreach($sql_hpp as $item): 
            $grand_total_hpp += $item['total_hpp'];
        endforeach;
        $data['hpp'] = $grand_total_hpp;

        $sql_marketplace_fee = $this->mymodel->selectWithQuery("SELECT SUM(marketplace_fee) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry");
        $sql_marketplace_fee = $sql_marketplace_fee[0];
        $data['marketplace_fee'] = $sql_marketplace_fee['result'];

        $sql_pengeluaran = $this->hitung_pengeluaran($start_date, $until_date, $brand_filter);
        $data['pengeluaran'] = $sql_pengeluaran['text'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $data['content'] = $this->load->view('dashboard/laba_bersih', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }


    public function laba_bersih_data()
    {
        $start_date = $this->input->get('start_date');
        $until_date = $this->input->get('until_date');
        $brand_filter = $this->input->get('brand');
        $today = date('Y-m-d');

        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime("$today -31 days"));
        }
        if (empty($until_date)) {
            $until_date = $today;
        }

        $qry = $brand_filter ? " AND brand = '$brand_filter' " : "";
        $qry_stock = $brand_filter ? " AND b.brand = '$brand_filter' " : "";

        $sql_penjualan_bersih = $this->mymodel->selectWithQuery("
            SELECT SUM(omset_kotor-diskon_penjual) as result 
            FROM transaction 
            WHERE DATE(date) >= '$start_date' 
            AND DATE(date) <= '$until_date' 
            AND type_sub = 'POS' 
            AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') 
            $qry
        ");
        $penjualan_bersih = $sql_penjualan_bersih[0]['result'] ?? 0;

        $sql_hpp = $this->mymodel->selectWithQuery("
            SELECT ((qty_out_pos + qty_out) * a.price_buy) AS total_hpp
            FROM product a
            LEFT JOIN (
                SELECT 
                    a.product,
                    a.brand,
                    a.marketplace,
                    SUM(a.qty_in) AS qty_in,
                    SUM(a.qty_in_pos) AS qty_in_pos,
                    SUM(a.qty_out) AS qty_out,
                    SUM(a.qty_out_pos) AS qty_out_pos,
                    SUM(a.qty) AS qty
                FROM stock a
                LEFT JOIN product p ON a.product = p.id
                WHERE DATE(a.date) >= '$start_date' AND DATE(a.date) <= '$until_date'
                AND a.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED')
                GROUP BY a.product
            ) b ON a.id = b.product
            WHERE a.is_varian = 0 AND b.qty_out_pos > 0 $qry_stock
        ");
        $hpp = array_sum(array_column($sql_hpp, 'total_hpp'));

        $sql_marketplace_fee = $this->mymodel->selectWithQuery("
            SELECT SUM(marketplace_fee) as result 
            FROM transaction 
            WHERE DATE(date) >= '$start_date' 
            AND DATE(date) <= '$until_date' 
            AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') 
            AND type_sub = 'POS' 
            $qry
        ");
        $marketplace_fee = $sql_marketplace_fee[0]['result'] ?? 0;

        $pengeluaran_data = $this->hitung_pengeluaran($start_date, $until_date, $brand_filter);
        $pengeluaran = $pengeluaran_data['text'] ?? 0;

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'penjualan_bersih' => $penjualan_bersih,
                'hpp' => $hpp,
                'marketplace_fee' => $marketplace_fee,
                'pengeluaran' => $pengeluaran
            ]));
    }

    function hitung_pengeluaran($start_date, $until_date, $brand_filter)
    {
        $firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';

        $shopee_brand = !empty($firstLetter) ? "AND shop_name LIKE '{$firstLetter}%'" : "";
        $tiktok_brand = !empty($firstLetter) ? "AND advertiser_name LIKE '{$firstLetter}%'" : "";
        $meta_brand = !empty($firstLetter) ? "AND account_name LIKE '{$firstLetter}%'" : "";

        $sql_spend_ads = "
				SELECT 
					COALESCE(SUM(shopee.expense), 0) + 
					COALESCE(SUM(meta.spend), 0) + 
					COALESCE(SUM(tiktok.spend_idr), 0) + 
					COALESCE(SUM(gmv.spend_idr_after_tax), 0) AS total_spend_ads
				FROM (
					SELECT 
						DATE(date) AS date,
						SUM(expense_after_tax) AS expense
					FROM 
						shopee_ads_data
					INNER JOIN 
						marketplace_config 
						ON marketplace_config.shop_id = shopee_ads_data.shop_id
					WHERE 
						DATE(date) BETWEEN '$start_date' AND '$until_date'
						$shopee_brand
					GROUP BY 
						DATE(date)
				) AS shopee
				LEFT JOIN (
					SELECT 
						DATE(date) AS date,
						SUM(spend_after_tax) AS spend
					FROM 
						meta_ads_data
					INNER JOIN 
						ads_meta_account 
						ON meta_ads_data.account_id = ads_meta_account.account_id
					WHERE 
						DATE(date) BETWEEN '$start_date' AND '$until_date'
						$meta_brand
					GROUP BY 
						DATE(date)
				) AS meta ON shopee.date = meta.date
				LEFT JOIN (
					SELECT 
						DATE(date) AS date,
						SUM(spend_idr_after_tax) AS spend_idr
					FROM 
						tiktok_ads_data
					WHERE 
						DATE(date) BETWEEN '$start_date' AND '$until_date'
						$tiktok_brand
					GROUP BY 
						DATE(date)
				) AS tiktok ON shopee.date = tiktok.date
				LEFT JOIN (
					SELECT 
						DATE(date) AS date,
						SUM(spend_idr_after_tax) AS spend_idr_after_tax
					FROM 
						advertiser_spend
					WHERE 
						DATE(date) BETWEEN '$start_date' AND '$until_date'
						AND advertiser_name LIKE '{$firstLetter}%'
					GROUP BY 
						DATE(date)
				) AS gmv ON shopee.date = gmv.date;
			";

        $data['spend_ads'] = $this->mymodel->selectWithQuery($sql_spend_ads);
        $data['spend_ads'] = !empty($data['spend_ads']) ? $data['spend_ads'][0] : ['total_spend_ads' => 0];

        $sql_spend_kol = "
            SELECT
                SUM(pl.nominal_dibayarkan) AS total_spend_kol
            FROM payment_logs pl
            JOIN endorse_campaign ec ON pl.id_campaign = ec.id
            WHERE DATE(pl.created_at) >= '$start_date' AND DATE(pl.created_at) <= '$until_date' AND pl.status_payment IN ('FP', 'DP')
            " . (!empty($firstLetter) ? "AND ec.brand LIKE '{$firstLetter}%'" : "") . "
        ";

        $data['spend_kol'] = $this->mymodel->selectWithQuery($sql_spend_kol);
        $data['spend_kol'] = !empty($data['spend_kol']) ? $data['spend_kol'][0] : ['total_spend_kol' => 0];

        $sql_spend_etc = "
				SELECT 
					ABS(SUM(e.price_total)) AS total_spend_etc
				FROM expense e
				WHERE DATE(e.date) BETWEEN '$start_date' AND '$until_date'
				AND e.brand LIKE '{$firstLetter}%';
			";

        $data['spend_etc'] = $this->mymodel->selectWithQuery($sql_spend_etc);
        $data['spend_etc'] = !empty($data['spend_etc']) ? $data['spend_etc'][0] : ['total_spend_etc' => 0];

        $total_spend = $data['spend_ads']['total_spend_ads'] + $data['spend_kol']['total_spend_kol'] + $data['spend_etc']['total_spend_etc'];

        $text = $this->template->separator_only($total_spend);

        return [
            'text' => $total_spend,
        ];
    }

    public function hpp()
    {
        $data['title'] = 'Dashboard HPP - ' . $this->template->title();

        $today       = date('Y-m-d');
        $start_date  = $this->input->get('start_date');
        $until_date  = $this->input->get('until_date');
        $brand       = $this->input->get('brand');           
        $jenis       = $this->input->get('jenis');          
        $status      = $this->input->get('status');         

        // Default tanggal
        if (empty($start_date)) $start_date = date('Y-m-d', strtotime("$today -31 days"));
        if (empty($until_date)) $until_date = $today;

        // ====== Build filter untuk PRODUCT (mengikuti kode pembanding) ======
        $statusFilter = "";
        if ($status !== null && $status !== '') {
            $statusFilter = " AND status = " . $this->db->escape($status) . " ";
        }

        $jenisFilter = "";
        if ($jenis === 'produk_jual') {
            $jenisFilter = " AND is_operational = 0 ";
        } else if ($jenis === 'produk_operasional') {
            $jenisFilter = " AND is_operational = 1 ";
        }

        // ====== Filter tanggal untuk tabel stock (alias $qry di kode pembanding) ======
        $qryStockWindow = "
            WHERE DATE(s.date) >= " . $this->db->escape($start_date) . "
            AND DATE(s.date) <= " . $this->db->escape($until_date) . "
        ";

        // ====== Filter transaksi POS untuk denominator net_sales & %HPP ======
        $qry_transaction = "";
        if (!empty($brand)) {
            $qry_transaction .= " AND t.brand = " . $this->db->escape($brand) . " ";
        }

        // ====== Query utama HPP ======
        $sql_hpp = $this->mymodel->selectWithQuery("
            SELECT
                a.*,
                b.qty_in,
                b.qty_in_pos,
                b.qty_out,
                b.qty_out_pos,
                b.qty_out_retur,
                b.qty,                         
                c.qty_retur_in,
                c.qty_retur_out,
                c.qty_retur,

                -- Total terjual + BAD return sesuai kebutuhan HPP
                COALESCE(b.qty_out,0) + COALESCE(b.qty_out_pos,0) + COALESCE(c.qty_retur_out,0) AS qty_sold_plus_bad,

                -- HPP nominal: (terjual + bad return) * price_buy
                ( (COALESCE(b.qty_out,0) + COALESCE(b.qty_out_pos,0) + COALESCE(c.qty_retur_out,0)) * a.price_buy ) AS total_hpp,

                -- Persentase HPP dibandingkan Net Sales POS pada periode yang sama
                ROUND(
                    (
                        (COALESCE(b.qty_out,0) + COALESCE(b.qty_out_pos,0) + COALESCE(c.qty_retur_out,0)) * a.price_buy
                    )
                    / NULLIF((
                        SELECT SUM(t.omset_kotor - t.diskon_penjual)
                        FROM transaction t
                        WHERE DATE(t.date) >= " . $this->db->escape($start_date) . "
                        AND DATE(t.date) <= " . $this->db->escape($until_date) . "
                        AND t.type_sub = 'POS'
                        AND t.order_status NOT IN ('CANCELLED','IN_CANCELLED','RETURN','REFUND','UNPAID')
                        $qry_transaction
                    ), 0) * 100, 2
                ) AS persentase_hpp
            FROM
            (
                -- Mengikuti kode pembanding: ambil product is_varian=0 + statusFilter + jenisFilter
                SELECT * FROM product
                WHERE 1=1 AND is_varian = 0
                $statusFilter
                $jenisFilter
            ) a
            LEFT JOIN
            (
                -- Subquery b: agregat stok normal pada window tanggal, EXCLUDE semua RETURN*
                SELECT
                    s.product,
                    -- sertakan brand dari tabel product agar bisa dipakai filter brand di WHERE akhir
                    p.brand,
                    s.marketplace,
                    SUM(s.qty_in)        AS qty_in,
                    SUM(s.qty_in_pos)    AS qty_in_pos,
                    SUM(s.qty_out)       AS qty_out,
                    SUM(s.qty_out_pos)   AS qty_out_pos,
                    SUM(s.qty_out_retur) AS qty_out_retur,
                    SUM(s.qty_in + s.qty_in_pos - s.qty_out - s.qty_out_pos) AS qty
                FROM stock s
                LEFT JOIN product p ON s.product = p.id
                $qryStockWindow
                AND s.order_status NOT IN ('IN_CANCELLED','REFUND','CANCELLED','RETURN','RETURN_UNSHIPPED')
                GROUP BY s.product
            ) b ON a.id = b.product
            LEFT JOIN
            (
                -- Subquery c: KHUSUS baris RETURN*, pisah GOOD vs BAD
                SELECT
                    s.product,
                    SUM(COALESCE(s.qty_in_pos,0))    AS qty_retur_in,   -- GOOD 
                    SUM(COALESCE(s.qty_out_retur,0)) AS qty_retur_out,  -- BAD 
                    SUM(COALESCE(s.qty_in_pos,0) + COALESCE(s.qty_out_retur,0)) AS qty_retur
                FROM stock s
                $qryStockWindow
                AND s.order_status LIKE '%RETURN%'
                GROUP BY s.product
            ) c ON a.id = c.product
            -- Filter brand diletakkan di WHERE akhir. Kita cek di b.brand (hasil LEFT JOIN product p di subquery b)
            WHERE 1=1
            " . (!empty($brand) ? " AND COALESCE(b.brand, a.brand) = " . $this->db->escape($brand) . " " : "") . "
            ORDER BY COALESCE(b.qty_out_pos,0) DESC
        ");

        $data['hpp'] = $sql_hpp;

        $data['brands'] = $this->mymodel->selectWithQuery("
            SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC
        ");

        $net_sales = $this->mymodel->selectWithQuery("
            SELECT SUM(t.omset_kotor - t.diskon_penjual) as net_sales
            FROM transaction t
            WHERE DATE(t.date) >= " . $this->db->escape($start_date) . "
            AND DATE(t.date) <= " . $this->db->escape($until_date) . "
            AND t.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
            AND t.type_sub = 'POS'
            $qry_transaction
        ");
        $data['net_sales'] = $net_sales[0]['net_sales'] ?? 0;

        $data['content'] = $this->load->view('dashboard/hpp', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    
    public function hpp_bundling()
    {
        $data['title'] = 'Dashboard HPP Bundling - ' . $this->template->title();

        // ==== Filters ====
        $start_date     = $this->input->get('start_date');
        $until_date     = $this->input->get('until_date');
        $brand          = $this->input->get('brand');
        $is_operational = $this->input->get('jenis_produk');

        if (empty($start_date)) $start_date = date('Y-m-01');
        if (empty($until_date)) $until_date = date('Y-m-d');

        $qry_stock = "1=1";
        $qry_transaction = "1=1";

        if ($brand) {
            $brand_esc = $this->db->escape_str($brand);
            $qry_stock       .= " AND p.brand = '{$brand_esc}'";
            $qry_transaction .= " AND t.brand = '{$brand_esc}'";
        }

        if ($is_operational == "Produk Jual") {
            $qry_stock .= " AND p.is_operational = '0'";
        } elseif ($is_operational == "Produk Operasional") {
            $qry_stock .= " AND p.is_operational = '1'";
        }

        // ==== Daftar product_id yang lolos filter brand/jenis (untuk filter item di PHP) ====
        $filterByProduct = ($brand || in_array($is_operational, ['Produk Jual','Produk Operasional']));
        $allowedProductId = null; // null = artinya tidak ada filter item

        if ($filterByProduct) {
            $cond = "1=1";
            if ($brand) {
                $cond .= " AND brand = '".$this->db->escape_str($brand)."'";
            }
            if ($is_operational === 'Produk Jual') {
                $cond .= " AND is_operational = '0'";
            } elseif ($is_operational === 'Produk Operasional') {
                $cond .= " AND is_operational = '1'";
            }

            $rowsAllowed = $this->mymodel->selectWithQuery("SELECT id FROM product WHERE $cond");
            $allowedProductId = [];
            foreach ($rowsAllowed as $ra) {
                $allowedProductId[(int)$ra['id']] = true;
            }
        }

        // ==== 1) Ambil transaksi (tanpa filter EXISTS karena kita akan ambil qty dari stock) ====
        $sql_hpp = $this->mymodel->selectWithQuery("
            SELECT
                t.order_id,
                (t.omset_kotor - t.diskon_penjual) AS omset_bersih,
                t.json,
                t.is_manual
            FROM transaction t
            WHERE DATE(t.date) >= '$start_date'
            AND DATE(t.date) <= '$until_date'
            AND t.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED')
            AND $qry_transaction
        ");

        // ==== 2) Ambil semua data stock yang keluar (POS dan Stock) dalam periode tersebut ====
        $sql_stock_all = $this->mymodel->selectWithQuery("
            SELECT
                s.order_id,
                s.product,
                s.sku,
                s.product_text,
                s.type_sub,
                CASE
                    WHEN s.type_sub = 'POS' THEN COALESCE(s.qty_out_pos, 0)
                    WHEN s.type_sub = 'Stock' AND s.qty_out IS NOT NULL AND s.qty_out <> 0 THEN s.qty_out
                    ELSE 0
                END AS qty_out
            FROM stock s
            JOIN product p ON p.id = s.product
            WHERE DATE(s.date) >= '$start_date'
            AND DATE(s.date) <= '$until_date'
            AND s.type = 'Out'
            AND s.type_sub IN ('POS', 'Stock')
            AND s.status = 'Aktif'
            AND (s.order_status = '' OR s.order_status NOT IN ('CANCELLED','IN_CANCELLED', 'RETURN', 'REFUND'))
            AND $qry_stock
        ");

        $stockByOrderId = [];
        $stockNonTransaction = []; 

        foreach ($sql_stock_all as $sr) {
            $orderId = $sr['order_id'];
            $pid = (int)($sr['product'] ?? 0);
            $qty = (int)($sr['qty_out'] ?? 0);
            
            if (is_array($allowedProductId) && ($pid === 0 || !isset($allowedProductId[$pid]))) {
                continue;
            }
            
            if ($qty <= 0) continue;

            if ($sr['type_sub'] === 'POS' && !empty($orderId)) {
                if (!isset($stockByOrderId[$orderId])) {
                    $stockByOrderId[$orderId] = [];
                }
                if (!isset($stockByOrderId[$orderId][$pid])) {
                    $stockByOrderId[$orderId][$pid] = 0;
                }
                $stockByOrderId[$orderId][$pid] += $qty;
            } else {
                $stockNonTransaction[] = [
                    'product' => $pid,
                    'sku' => trim((string)($sr['sku'] ?? '')),
                    'product_text' => (string)($sr['product_text'] ?? ''),
                    'qty_out' => $qty,
                ];
            }
        }

        $canonByPid = [];
        $canonBySku = [];
        
        foreach ($sql_hpp as $r) {
            $itemsRaw = is_array($r['json']) ? $r['json'] : json_decode($r['json'], true);
            $itemsRaw = $itemsRaw ?: [];
            
            if (is_array($itemsRaw)) {
                foreach ($itemsRaw as $it) {
                    $pid  = isset($it['product']) ? (int)$it['product'] : 0;
                    $sku  = isset($it['sku']) ? trim((string)$it['sku']) : '';
                    $name = (string)($it['product_text'] ?? '');
                    $keyType = ($sku !== '') ? 'sku' : 'name';

                    if ($pid > 0 && !isset($canonByPid[$pid])) {
                        $canonByPid[$pid] = ['keyType'=>$keyType, 'sku'=>$sku, 'name'=>$name];
                    }
                    if ($sku !== '' && !isset($canonBySku[$sku])) {
                        $canonBySku[$sku] = ['name'=>$name];
                    }
                }
            }
        }

        $orders = [];
        foreach ($sql_hpp as $r) {
            $orderId = $r['order_id'];
            $itemsRaw = is_array($r['json']) ? $r['json'] : json_decode($r['json'], true);
            $itemsRaw = $itemsRaw ?: [];

            $itemsFiltered = [];
            if (is_array($itemsRaw)) {
                foreach ($itemsRaw as $it) {
                    $pid = isset($it['product']) ? (int)$it['product'] : null;
                    if (is_array($allowedProductId)) {
                        if ($pid === null || !isset($allowedProductId[$pid])) continue;
                    }
                    
                    $qtyFromStock = 0;
                    if (isset($stockByOrderId[$orderId][$pid])) {
                        $qtyFromStock = $stockByOrderId[$orderId][$pid];
                    }
                    
                    if ($qtyFromStock > 0) {
                        $it['qty'] = $qtyFromStock; 
                        $itemsFiltered[] = $it;
                    }
                }
            }

            if (count($itemsFiltered) === 0) continue;

            $orders[] = [
                'order_id'     => $orderId,
                'omset_bersih' => (float)($r['omset_bersih'] ?? 0),
                'is_manual'    => (int)($r['is_manual'] ?? 0),
                'items'        => $itemsFiltered,
            ];
        }

        $stockOutItems = [];
        foreach ($stockNonTransaction as $sr) {
            $pid = $sr['product'];
            $sku = $sr['sku'];
            $name = $sr['product_text'];
            
            if ($pid > 0 && isset($canonByPid[$pid])) {
                if ($canonByPid[$pid]['keyType'] === 'sku') {
                    $sku  = $canonByPid[$pid]['sku'];
                    $name = $canonByPid[$pid]['name'];
                } else {
                    $sku  = '';
                    $name = $canonByPid[$pid]['name'];
                }
            } elseif ($sku !== '' && isset($canonBySku[$sku])) {
                $name = $canonBySku[$sku]['name'];
            }

            $stockOutItems[] = [
                'product'      => $pid,
                'sku'          => $sku,
                'product_text' => $name,
                'qty_out'      => $sr['qty_out'],
            ];
        }

        // ==== 3) Kumpulkan key produk untuk lookup HPP ====
        $allSku = [];
        $allProductIds = [];
        foreach ($orders as $o) {
            foreach ($o['items'] as $it) {
                $skuRaw = isset($it['sku']) ? trim((string)$it['sku']) : '';
                if ($skuRaw !== '') $allSku[] = $this->db->escape_str($skuRaw);
                if (!empty($it['product'])) $allProductIds[] = (int)$it['product'];
            }
        }
        foreach ($stockOutItems as $si) {
            if (!empty($si['sku'])) $allSku[] = $this->db->escape_str($si['sku']);
            if (!empty($si['product'])) $allProductIds[] = (int)$si['product'];
        }
        $allSku = array_values(array_unique($allSku));
        $allProductIds = array_values(array_unique($allProductIds));

        // ==== 4) Ambil HPP dari product ====
        $hppBySku = [];
        $hppById  = [];

        if (!empty($allSku)) {
            $inSku = "'" . implode("','", $allSku) . "'";
            $rowsSku = $this->mymodel->selectWithQuery("
                SELECT sku, price_buy AS hpp
                FROM product
                WHERE sku IN ($inSku)
            ");
            foreach ($rowsSku as $r) {
                $skuKey = trim((string)$r['sku']);
                $hppBySku[$skuKey] = (float)$r['hpp'];
            }
        }

        if (!empty($allProductIds)) {
            $inId = implode(',', $allProductIds);
            $rowsId = $this->mymodel->selectWithQuery("
                SELECT id, price_buy AS hpp
                FROM product
                WHERE id IN ($inId)
            ");
            foreach ($rowsId as $r) {
                $hppById[(int)$r['id']] = (float)$r['hpp'];
            }
        }

        $getHpp = function(array $item) use ($hppById, $hppBySku) {
            if (!empty($item['product'])) {
                $pid = (int)$item['product'];
                if (isset($hppById[$pid])) return (float)$hppById[$pid];
            }
            if (!empty($item['sku'])) {
                $sku = trim((string)$item['sku']);
                if (isset($hppBySku[$sku])) return (float)$hppBySku[$sku];
            }
            return 0.0;
        };

        // ==== 5) Bentuk baris per order (gabungkan bundling) ====
        $rows = [];

        foreach ($orders as $o) {
            $isManual  = (int)$o['is_manual'] === 1;
            $items     = $o['items'];
            $itemCount = count($items);

            if ($isManual) {
                // Manual: satu baris per item
                foreach ($items as $it) {
                    $sku  = trim((string)($it['sku'] ?? ''));
                    $name = (string)($it['product_text'] ?? '');
                    $qty  = (int)($it['qty'] ?? 0);

                    $hpp_unit = $getHpp($it);
                    $line_hpp = $qty * $hpp_unit;

                    $rows[] = [
                        'sku'              => $sku,
                        'product_text'     => $name,
                        'comp_count'       => ($sku !== '' || $name !== '') ? 1 : 0,
                        'qty'              => $qty,
                        'price_total_hpp'  => $line_hpp,
                        'omset_bersih'     => (float)($it['price_total'] ?? 0),
                    ];
                }

            } else {
                if ($itemCount <= 1) {
                    // Single item 
                    $it   = $itemCount ? reset($items) : [];
                    $sku  = trim((string)($it['sku'] ?? ''));
                    $name = (string)($it['product_text'] ?? '');
                    $qty  = (int)($it['qty'] ?? 0);

                    $hpp_unit = $getHpp($it);
                    $line_hpp = $qty * $hpp_unit;

                    $rows[] = [
                        'sku'              => $sku,
                        'product_text'     => $name,
                        'comp_count'       => ($sku !== '' || $name !== '') ? 1 : 0,
                        'qty'              => $qty,
                        'price_total_hpp'  => $line_hpp,
                        'omset_bersih'     => (float)$o['omset_bersih'],
                    ];

                } else {
                    $pairs = [];
                    $bundleQty = null;
                    $hppSum = 0.0;

                    foreach ($items as $it) {
                        $sku  = (string)($it['sku'] ?? '');
                        $name = (string)($it['product_text'] ?? '');
                        $pairs[] = ['sku' => $sku, 'name' => $name];

                        $q = (int)($it['qty'] ?? 0);
                        $q = max(0, $q);
                        $bundleQty = is_null($bundleQty) ? $q : min($bundleQty, $q);

                        $hpp_unit = $getHpp($it);
                        $hppSum  += $q * $hpp_unit;
                    }
                    if (is_null($bundleQty) || $bundleQty <= 0) $bundleQty = 1;

                    usort($pairs, function($a, $b) {
                        $sa = $a['sku']; $sb = $b['sku'];
                        if ($sa === $sb) return strcmp($a['name'], $b['name']);
                        return strcmp($sa, $sb);
                    });

                    $compKeys = [];
                    foreach ($pairs as $p) {
                        $compKeys[] = ($p['sku'] !== '') ? 'sku:'.$p['sku'] : 'name:'.$p['name'];
                    }
                    $comp_count = count(array_unique(array_filter($compKeys)));

                    $sku_join  = implode(' + ', array_filter(array_column($pairs, 'sku'),  fn($s) => $s !== ''));
                    $name_join = implode(' + ', array_filter(array_column($pairs, 'name'), fn($n) => $n !== ''));

                    $rows[] = [
                        'sku'              => $sku_join,
                        'product_text'     => $name_join,
                        'comp_count'       => $comp_count > 0 ? $comp_count : 1,
                        'qty'              => $bundleQty,
                        'price_total_hpp'  => $hppSum,
                        'omset_bersih'     => (float)$o['omset_bersih'],
                    ];
                }
            }
        }

        // ==== 6) Masukkan baris keluaran stok non-transaksi ====
        foreach ($stockOutItems as $si) {
            $qty  = (int)$si['qty_out'];
            $hpp_unit = $getHpp($si);
            $line_hpp = $qty * $hpp_unit;

            $rows[] = [
                'sku'              => trim((string)($si['sku'] ?? '')),
                'product_text'     => (string)($si['product_text'] ?? ''),
                'comp_count'       => 1,
                'qty'              => $qty,
                'price_total_hpp'  => $line_hpp,
                'omset_bersih'     => 0.0,
            ];
        }

        // ==== 7) Agregasi akhir ====
        $agg = [];
        foreach ($rows as $r) {
            $key = ($r['sku'] !== '') ? $r['sku'] : $r['product_text'];
            if (!isset($agg[$key])) {
                $agg[$key] = [
                    'sku'             => $r['sku'],
                    'product_text'    => $r['product_text'],
                    'comp_count'      => (int)$r['comp_count'],
                    'qty'             => 0,
                    'price_total_hpp' => 0.0,
                    'omset_bersih'    => 0.0,
                ];
            }
            $agg[$key]['comp_count']       = max($agg[$key]['comp_count'], (int)$r['comp_count']);
            $agg[$key]['qty']             += (int)$r['qty'];
            $agg[$key]['price_total_hpp'] += (float)$r['price_total_hpp'];
            $agg[$key]['omset_bersih']    += (float)$r['omset_bersih'];
        }

        // ==== 8) Bentuk array $hpp sesuai format front-end ====
        $hpp = [];
        foreach ($agg as $key => $r) {
            $omset = (float)$r['omset_bersih'];
            $hppv  = (float)$r['price_total_hpp'];
            $laba  = $omset - $hppv;
            $pct   = $omset > 0 ? ($hppv / $omset) * 100 : 0;

            $produk_bundling = $r['product_text'] !== '' ? $r['product_text'] : $r['sku'];

            $hpp[] = [
                'produk_bundling'        => $produk_bundling,
                'jumlah_produk_bundling' => (int)max(1, $r['comp_count']),
                'qty_bundling'           => (int)$r['qty'],
                'total_hpp'              => $hppv,
                'omset_bersih'           => $omset,
                'laba_bundling'          => $laba,
                'persentase_hpp'         => $pct,
            ];
        }

        // ==== 9) Data lain untuk view ====
        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $net_sales = $this->mymodel->selectWithQuery("
            SELECT SUM(t.omset_kotor - t.diskon_penjual) as net_sales
            FROM transaction t
            WHERE DATE(t.date) >= '$start_date'
            AND DATE(t.date) <= '$until_date'
            AND t.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED', 'UNPAID')
            AND t.type_sub = 'POS'
            AND $qry_transaction
        ");
        $data['net_sales']  = (float)($net_sales[0]['net_sales'] ?? 0);

        $data['hpp']        = $hpp;
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;

        $data['content'] = $this->load->view('dashboard/hpp_bundling', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Optimized method to calculate ads spending with caching
     */
    private function calculate_ads_spending($start_date, $until_date, $brand_filter = '')
    {
        // Create cache key
        $cache_key = "ads_spending_" . md5($start_date . $until_date . $brand_filter);
        
        // Try to get from cache first
        if (isset($this->cache)) {
            $cached_result = $this->cache->get($cache_key);
            if ($cached_result !== FALSE) {
                return $cached_result;
            }
        }

        $firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';
        $shopee_brand = !empty($firstLetter) ? "AND mc.shop_name LIKE '{$firstLetter}%'" : "";
        $tiktok_brand = !empty($firstLetter) ? "AND tad.advertiser_name LIKE '{$firstLetter}%'" : "";
        $meta_brand = !empty($firstLetter) ? "AND ama.account_name LIKE '{$firstLetter}%'" : "";

        // Optimized query without DATE() functions
        $sql = "
            SELECT 
                COALESCE(SUM(sad.expense_after_tax), 0) + 
                COALESCE(SUM(mad.spend_after_tax), 0) + 
                COALESCE(SUM(tad.spend_idr_after_tax), 0) + 
                COALESCE(SUM(ads.spend_idr_after_tax), 0) AS total_spend_ads
            FROM (SELECT 1 as dummy) d
            LEFT JOIN shopee_ads_data sad ON sad.date >= '$start_date' AND sad.date <= '$until_date'
            LEFT JOIN marketplace_config mc ON mc.shop_id = sad.shop_id $shopee_brand
            LEFT JOIN meta_ads_data mad ON mad.date >= '$start_date' AND mad.date <= '$until_date'
            LEFT JOIN ads_meta_account ama ON mad.account_id = ama.account_id $meta_brand
            LEFT JOIN tiktok_ads_data tad ON tad.date >= '$start_date' AND tad.date <= '$until_date' $tiktok_brand
            LEFT JOIN advertiser_spend ads ON ads.date >= '$start_date' AND ads.date <= '$until_date'
                AND ads.advertiser_name LIKE '{$firstLetter}%'
        ";

        $result = $this->mymodel->selectWithQuery($sql);
        $total = $result[0]['total_spend_ads'] ?? 0;

        // Cache the result for 60 seconds for better responsiveness
        if (isset($this->cache)) {
            $this->cache->save($cache_key, $total, 60);
        }

        return $total;
    }

    /**
     * Optimized method to calculate KOL spending with caching
     */
    private function calculate_kol_spending($start_date, $until_date, $brand_filter = '')
    {
        $cache_key = "kol_spending_" . md5($start_date . $until_date . $brand_filter);
        
        if (isset($this->cache)) {
            $cached_result = $this->cache->get($cache_key);
            if ($cached_result !== FALSE) {
                return $cached_result;
            }
        }

        $firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';
        $brand_condition = !empty($firstLetter) ? "AND c.brand LIKE '{$firstLetter}%'" : "";

        $sql = "
            SELECT COALESCE(SUM(DISTINCT e.nominal_dibayarkan), 0) AS total_spend_kol
            FROM endorse e
            INNER JOIN endorse_campaign c ON c.id = e.id_campaign
            WHERE e.tgl_tf >= '$start_date' 
            AND e.tgl_tf <= '$until_date'
            $brand_condition
        ";

        $result = $this->mymodel->selectWithQuery($sql);
        $total = $result[0]['total_spend_kol'] ?? 0;

        if (isset($this->cache)) {
            $this->cache->save($cache_key, $total, 60);
        }

        return $total;
    }

    /**
     * Optimized method to calculate etc spending with caching
     */
    private function calculate_etc_spending($start_date, $until_date, $brand_filter = '')
    {
        $cache_key = "etc_spending_" . md5($start_date . $until_date . $brand_filter);
        
        if (isset($this->cache)) {
            $cached_result = $this->cache->get($cache_key);
            if ($cached_result !== FALSE) {
                return $cached_result;
            }
        }

        $firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';
        $brand_condition = !empty($firstLetter) ? "AND e.brand LIKE '{$firstLetter}%'" : "";

        $sql = "
            SELECT COALESCE(ABS(SUM(e.price_total)), 0) AS total_spend_etc
            FROM expense e
            WHERE e.date >= '$start_date' 
            AND e.date <= '$until_date'
            $brand_condition
        ";

        $result = $this->mymodel->selectWithQuery($sql);
        $total = $result[0]['total_spend_etc'] ?? 0;

        if (isset($this->cache)) {
            $this->cache->save($cache_key, $total, 60);
        }

        return $total;
    }


}
