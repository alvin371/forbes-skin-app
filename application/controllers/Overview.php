<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Overview extends CI_Controller
{
    public function index()
    {
        $data['checkbox'] = $_SESSION['checkbox_dashboard'];
        $data['checkbox_campaign'] = $_SESSION['checkbox_dashboard_campaign'];
        $data['title'] = 'Overview - ' . $this->template->title();

        $user = $_SESSION['user'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $data['channel_2'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        ORDER BY name ASC");

        $data['channel'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        WHERE name IN ('SHOPEE','LAZADA','TIKTOK','WA')
        ORDER BY name ASC");


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

        $url = base_url() . '/overview/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['url_2'] = $this->template->get_param_without('status');
        $data['param'] = $this->template->get_param();

        if ($_GET['t'] == "kol") {
            $data['campaign'] = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_campaign
            ORDER BY title ASC");

            // Provide PIC and Product filter options for KOL overview
            $data['pic_options'] = $this->mymodel->selectWithQuery(
                "SELECT DISTINCT pic AS name FROM endorse WHERE pic IS NOT NULL AND pic != '' ORDER BY pic ASC"
            );
            $data['product_options'] = $this->mymodel->selectWithQuery(
                "SELECT DISTINCT p.id AS id, p.name AS name 
                 FROM endorse e 
                 INNER JOIN product p ON p.id = e.product 
                 WHERE e.product IS NOT NULL AND e.product != '' 
                 ORDER BY p.name ASC"
            );

            $data['content'] = $this->load->view('overview/all-kol', $data, true);
        } else if ($_GET['t'] == "influencer") {
            $data['campaign'] = $this->mymodel->selectWithQuery("
           SELECT *
           FROM endorse_campaign
           ORDER BY title ASC");
            $data['content'] = $this->load->view('overview/all-influencer', $data, true);
        } else {
            $today = date('Y-m-d');
            $start_date = $this->input->get('start_date');
            $until_date = $this->input->get('until_date');
            $brand_filter = $this->input->get('brand');

            if (empty($start_date)) {
                $start_date = date('Y-m-d', strtotime("$today -7 days"));
            }
            if (empty($until_date)) {
                $until_date = $today;
            }

            $brand_condition = "";
            if (!empty($brand_filter)) {
                $brand_condition = "AND transaction.brand = " . $this->db->escape($brand_filter);
            }

            $firstLetter = str_split($brand_filter)[0];

            $shopee_brand = "";
            if (!empty($brand_filter)) {
                $shopee_brand = "AND shop_name LIKE '$firstLetter%'";
            }

            $tiktok_brand = "";
            if (!empty($brand_filter)) {
                $tiktok_brand = "AND advertiser_name LIKE '$firstLetter%'";
            }

            $meta_brand = "";
            if ($firstLetter == "P" && $firstLetter == "") {
                $meta_brand = "AND account_name LIKE 'c%'";
                $meta_brand = "OR account_name LIKE 'p%'";
            } else if ($firstLetter == "M") {
                $meta_brand = "AND account_name LIKE 'm%'";
            }

            $sql_pivot = "
                    SELECT 
                        DATE_FORMAT(dates.date, '%d-%m-%Y') AS date,
                        COALESCE(shopee.expense, 0) AS shopee_spend,
                        COALESCE(meta.spend, 0) AS meta_spend,
                        COALESCE(tiktok.spend_idr, 0) AS tiktok_spend,
                        COALESCE(shopee.expense, 0) + COALESCE(meta.spend, 0) + COALESCE(tiktok.spend_idr, 0) +  COALESCE(gmv.spend_idr_after_tax, 0) AS total_spend,
                        COALESCE(shopee.purchase_qty, 0) + COALESCE(meta.purchase_qty, 0) + COALESCE(tiktok.purchase_qty, 0) AS purchase_qty,
                        COALESCE(shopee.purchase_idr, 0) 
                            + COALESCE(meta.purchase_idr, 0) 
                            + COALESCE(tiktok.purchase_idr, 0) AS purchase_idr,
                        COALESCE(ROUND(
                            (COALESCE(shopee.purchase_idr, 0) 
                            + COALESCE(meta.purchase_idr, 0) 
                            + COALESCE(tiktok.purchase_idr, 0)) / (COALESCE(shopee.purchase_qty, 0) 
                            + COALESCE(meta.purchase_qty, 0) 
                            + COALESCE(tiktok.purchase_qty, 0)), 
                            0
                        ),0) AS avg_penjualan,
                        COALESCE((
                            SELECT SUM(omset_kotor - diskon_penjual)
                            FROM transaction
                            WHERE DATE(transaction.date) = dates.date
                            AND transaction.order_status NOT IN ('RETURN', 'REFUND', 'CANCELLED', 'IN_CANCELLED', 'UNPAID')
                            AND transaction.type_sub = 'POS'
                            $brand_condition
                        ), 0) AS result,
                        CASE 
                            WHEN COALESCE((
                                SELECT SUM(omset_kotor - diskon_penjual)
                                FROM transaction
                                WHERE DATE(transaction.date) = dates.date
                                AND transaction.order_status NOT IN ('RETURN', 'REFUND', 'CANCELLED', 'IN_CANCELLED', 'UNPAID')
                                AND transaction.type_sub = 'POS'
                                $brand_condition
                            ), 0) = 0 THEN 0
                            ELSE ROUND(
                                (COALESCE(shopee.expense, 0) + COALESCE(meta.spend, 0) + COALESCE(tiktok.spend_idr, 0)) /
                                COALESCE((
                                    SELECT SUM(omset_kotor - diskon_penjual)
                                    FROM transaction
                                    WHERE DATE(transaction.date) = dates.date
                                    AND transaction.order_status NOT IN ('RETURN', 'REFUND', 'CANCELLED', 'IN_CANCELLED', 'UNPAID')
                                    AND transaction.type_sub = 'POS'
                                    $brand_condition
                                ), 0) * 100, 2)
                        END AS ratio,
                        COALESCE(gmv.spend_idr_after_tax, 0) AS tiktok_gmv
                    FROM 
                        (
                            SELECT DISTINCT DATE(date) AS date FROM shopee_ads_data
                            UNION
                            SELECT DISTINCT DATE(date) AS date FROM meta_ads_data
                            UNION
                            SELECT DISTINCT DATE(date) AS date FROM tiktok_ads_data
                            UNION
                            SELECT DISTINCT DATE(date) AS date FROM transaction
                            UNION
                            SELECT DISTINCT DATE(date) AS date FROM advertiser_spend
                        ) AS dates
                    LEFT JOIN (
                        SELECT DATE(date) AS date, SUM(expense_after_tax) AS expense, SUM(broad_item_sold) AS purchase_qty, SUM(broad_gmv) AS purchase_idr
                        FROM shopee_ads_data
                        INNER JOIN marketplace_config ON marketplace_config.shop_id = shopee_ads_data.shop_id
                        WHERE DATE(date) BETWEEN ? AND ?
                        $shopee_brand
                        GROUP BY DATE(date)
                    ) AS shopee ON shopee.date = dates.date
                    LEFT JOIN (
                        SELECT DATE(date) AS date, SUM(spend_after_tax) AS spend, SUM(purchase_qty) AS purchase_qty, SUM(purchases) AS purchase_idr
                        FROM meta_ads_data
                        INNER JOIN ads_meta_account ON meta_ads_data.account_id = ads_meta_account.account_id
                        WHERE DATE(date) BETWEEN ? AND ?
                        $meta_brand
                        GROUP BY DATE(date)
                    ) AS meta ON meta.date = dates.date
                    LEFT JOIN (
                        SELECT DATE(date) AS date, SUM(spend_idr_after_tax) AS spend_idr, SUM(onsite_shopping) AS purchase_qty, SUM(total_onsite_shopping_value_idr) AS purchase_idr
                        FROM tiktok_ads_data
                        WHERE DATE(date) BETWEEN ? AND ?
                        $tiktok_brand
                        GROUP BY DATE(date)
                    ) AS tiktok ON tiktok.date = dates.date
                    LEFT JOIN (
                        SELECT DATE(date) AS date, SUM(spend_idr_after_tax) AS spend_idr_after_tax
                        FROM advertiser_spend
                        WHERE DATE(date) BETWEEN ? AND ?
                        $tiktok_brand
                        GROUP BY DATE(date)
                    ) AS gmv ON gmv.date = dates.date
                    WHERE dates.date BETWEEN ? AND ?
                    ORDER BY dates.date ASC;
                ";

            $params = [$start_date, $until_date, $start_date, $until_date, $start_date, $until_date, $start_date, $until_date, $start_date, $until_date];

            $data['pivot'] = $this->db->query($sql_pivot, $params)->result_array();

            $sql_spend = "
                    SELECT
                    SUM(spend_idr) as spend_idr,
                    SUM(spend_idr_after_tax) as spend_idr_after_tax,
                    DATE(date) as date,
                    advertiser_name
                    FROM `advertiser_spend`
                ";
            if (!empty($start_date) && !empty($until_date)) {
                $sql_spend .= " WHERE date BETWEEN '$start_date' AND '$until_date'";
            } else {
                $sql_spend .= " WHERE date = CURDATE()";
            }

            $sql_spend .= $tiktok_brand;

            if (!empty($ids_advertiser)) {
                $ids_advertiser_str = implode(",", $ids_advertiser);
                $sql_spend .= " AND advertiser_id IN ($ids_advertiser_str)";
            }

            $sql_spend .= " GROUP BY advertiser_name, date";

            $data['spend'] = $this->mymodel->selectWithQuery($sql_spend);

            $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
            $data['brands'] = $query;

            $query = $this->mymodel->selectWithQuery("SELECT * FROM config WHERE id = 'TAX'");
            $data['tax'] = $query;
            $view_path = 'overview/all-ads';
            $data['content'] = $this->load->view($view_path, $data, true);
        }
        $this->load->view('TemplateDashboard', $data);
    }
}
