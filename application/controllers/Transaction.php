<?php

// PhpSpreadsheet autoload commented out to prevent PHP version conflicts
// Only load when specifically needed for Excel operations
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Transaction extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        
        // Load required libraries and models
        $this->load->database();
        $this->load->model('mymodel');
        
        // Set public methods that don't require permission checks
        $this->set_public_methods([
            'sync_pencairan' // API endpoint for external sync
        ]);
        
        // Set custom method permissions if needed
        $this->set_method_permissions([
            'export_excel' => 'view', // Export requires view permission
            'import_excel' => 'create' // Import requires create permission
        ]);
    }

    function sync_pencairan()
    {
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM transaction WHERE order_status IN ('COMPLETED','DELIVERED')
        AND dana_pencairan = 0
        ORDER BY updated_at ASC
        LIMIT 10
        ");
        $arr = array();
        foreach ($data as $k => $v) {
            $dt = array();
            $dt['order_id'] = $v['order_id'];
            $dt['marketplace'] = $v['marketplace'];
            $dt['order_status'] = $v['order_status'];
            $dt['updated_at'] = DATE("Y-m-d H:i:s");

            $this->db->update('transaction', $dt, array('id' => $v['id']));

            $url = $this->template->endpoint_url() . 'api/marketplace/order/detail?order_id=' . $dt['order_id'] . '&marketplace=' . $dt['marketplace'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            $response = json_decode($response, true);
            curl_close($curl);

            $dt['msg'] = $response;
            $arr[] = $dt;
        }
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['code'] = 200;
        $html['status'] = true;
        $html['data'] = $arr;
        echo json_encode($html, true);
    }
    public function refresh_token_process()
    {


        $brand = $_GET['brand'];


        $_SESSION['brand'] = $brand;

        $brand = $_SESSION['brand'];

        $url = base_url() . '/api/auth/refresh-token/shopee?brand=' . $brand;

        if ($_GET['channel'] == "SHOPEE") {
            $url = base_url() . '/api/auth/refresh-token/shopee?brand=' . $brand;
        } else if ($_GET['channel'] == "LAZADA") {
            $url = base_url() . '/api/auth/refresh-token/lazada?brand=' . $brand;
        } else if ($_GET['channel'] == "TIKTOK") {
            $url =  base_url() . '/api/auth/refresh-token/tiktok?brand=' . $brand;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: ci_session=ji2dpajlqasgk8200eroqtjrri48iugl'
            ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);

        curl_close($curl);
        if ($response['status'] == true && $_GET['channel']) {
            $msg = $response['msg'];
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = $response['msg'];
            echo $this->template->alert_danger($msg);
            die;
        }
    }
    public function index()
    {
        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Order ID";
        }
        $data['keyword_category'] = $keyword_category;
        $today = DATE("Y-m-d");
        if ($_GET['start_date'] == "") {
            $start_date = DATE("Y-m-01");
            
        } else {
            $start_date = $_GET['start_date'];
        }
        if ($_GET['until_date'] == "") {
            $until_date = DATE("Y-m-d");
        } else {
            $until_date = $_GET['until_date'];
        }
        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];
        $c_type = $_GET['c_type'];

        $brand = $_GET['brand'];
        $keyword = $_GET['keyword'];

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $data['title'] = 'Order - ' . $this->template->title();


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user
        WHERE role IN ('3')
        ORDER BY full_name ASC
        ");

        $data['cs'] = $query;




        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT id,code as opt FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;

        $data['store'] = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace FROM marketplace_config WHERE status = 'Aktif' ORDER BY marketplace DESC, shop_name ASC");

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }

        if ($brand == "LAINNYA") {
            $ids = "";
            foreach ($data['brands'] as $k => $v) {
                $ids .= "'" . $v['opt'] . "',";
            }
            $ids = substr($ids, 0, -1);
            $qry .= " AND brand NOT IN ($ids) ";
        } else {
            if ($brand) {
                $qry .= " AND brand = '$brand' ";
            }
        }

        $ekspedisi = $_GET['ekspedisi'];
        if ($ekspedisi) {
            $qry .= " AND shipping = '$ekspedisi' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND cs = '$cs' ";
        }

        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND dana_pencairan > 0 AND is_disbursement > 0 ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else {
                $qry .= " AND order_status = '$order_status' ";
            }
        }

        $pencairan = $_GET['pencairan'];
        if ($pencairan) {
            if ($pencairan == "Sudah Pencairan") {
                $qry .= " AND dana_pencairan > 0";
            } else if ($pencairan == "Belum Pencairan") {
                $qry .= " AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') AND c_type NOT IN ('Affiliate','Endorse','Free') AND dana_pencairan = 0 AND is_disbursement = 0 ";
            }
        }

        $payment_type = $_GET['payment_type'];
        if ($payment_type) {
            $qry .= " AND payment_type = '$payment_type' ";
        }




        if ($keyword) {
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND c_username LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Pelanggan") {
                $qry .= " AND customer_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Pelanggan") {
                $qry .= " AND phone LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Resi") {
                $qry .= " AND awb_number LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Produk") {
                $qry .= " AND `json` LIKE '%$keyword%' ";
            }
        }

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        } else if ($order_type == "Belum Dikonfigurasi") {
            $qry .= " AND is_configurated = 0 ";
        }

        if ($c_type) {
            $qry .= " AND c_type = '$c_type' ";
        }

        $shop_id = $_GET['shop_id'];
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM transaction
        WHERE $qry AND type_sub = 'POS' ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/transaction?keyword=' . $_GET['keyword'] . '&brand=' . $_GET['brand'] . '&marketplace=' . $_GET['marketplace'] . '&cs=&start_date=' . $start_date . '&until_date=' . $until_date;

        $url = base_url() . '/transaction/' . $this->template->get_param();
        $data['url_1'] = $this->template->get_param_without_order_status($url);
        $data['url_2'] = $this->template->get_param_without_keyword_category($url);
        $data['url_3'] = $this->template->get_param_without('order_type');
        $data['url_4'] = $this->template->get_param_without('pencairan');
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);


        $filter = $_GET;
        $filter['start_date'] = $start_date;
        $filter['until_date'] = $until_date;

        $_SESSION['filter'] = $filter;

        $data['content'] = $this->load->view('transaction/all', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function item()
    {
        $data['template'] = $this->template;

        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $until_date = $_GET['until_date'] ?? date('Y-m-d');
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;

        $brand = $_GET['brand'] ?? '';
        $marketplace = $_GET['marketplace'] ?? '';
        $cs = $_GET['cs'] ?? '';
        $keyword = $_GET['keyword'] ?? '';
        $id = $_GET['id'] ?? '';
        $order_status = $_GET['order_status'] ?? '';
        $keyword_category = $_GET['keyword_category'] ?? 'Order ID';
        $c_type = $_GET['c_type'] ?? '';
        $ff = $this->input->get('filter_field');
        $fv = $this->input->get('filter_value');
        $fo = $this->input->get('filter_operator');

        $filters = [];
        if (is_array($ff) && is_array($fv)) {
            $n = min(count($ff), count($fv));
            for ($i = 0; $i < $n; $i++) {
                $filters[] = [
                    'field' => (string)$ff[$i],
                    'value' => (string)$fv[$i],
                    'op'    => isset($fo[$i]) ? (string)$fo[$i] : 'equals',
                ];
            }
        } elseif (!empty($ff) && !empty($fv)) {
            $filters[] = [
                'field' => (string)$ff,
                'value' => (string)$fv,
                'op'    => !empty($fo) ? (string)$fo : 'equals',
            ];
        }

        $data['order_status']      = $order_status;
        $data['c_type']            = $c_type;
        $data['keyword']           = $keyword;
        $data['keyword_category']  = $keyword_category;
        $data['brand']             = $brand;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role IN ('3') ORDER BY full_name ASC");
        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0 ORDER BY sku ASC");
        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");
        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT id,code as opt FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $qry = "";
        $id_customer = $_GET['id_customer'] ?? '';

        if ($id_customer) {
            $qry = " customer = '".$this->db->escape_str($id_customer)."' ";
        } else {
            $qry = " DATE(date) >= '".$this->db->escape_str($start_date)."'
                    AND DATE(date) <= '".$this->db->escape_str($until_date)."' ";

            if ($brand == "LAINNYA") {
                $ids = "";
                foreach ($data['brands'] as $k => $v) {
                    $ids .= "'" . $this->db->escape_str($v['opt']) . "',";
                }
                $ids = rtrim($ids, ',');
                $qry .= " AND brand NOT IN ($ids) ";
            } elseif ($brand) {
                $qry .= " AND brand = '".$this->db->escape_str($brand)."' ";
            }
        }

        $ids = $_GET['ids'] ?? '';
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id IN ($ids) ";
        }

        $ekspedisi = $_GET['ekspedisi'] ?? '';
        if ($ekspedisi) {
            $qry .= " AND shipping = '".$this->db->escape_str($ekspedisi)."' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '".$this->db->escape_str($marketplace)."' ";
        }

        if ($cs) {
            $qry .= " AND cs = '".$this->db->escape_str($cs)."' ";
        }

        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND dana_pencairan > 0 AND is_disbursement > 0 ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else {
                $qry .= " AND order_status = '".$this->db->escape_str($order_status)."' ";
            }
        }

        $pencairan = $_GET['pencairan'] ?? '';
        if ($pencairan) {
            if ($pencairan == "Sudah Pencairan") {
                $qry .= " AND dana_pencairan > 0";
            } else if ($pencairan == "Belum Pencairan") {
                $qry .= " AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') AND c_type NOT IN ('Affiliate','Endorse','Free') AND dana_pencairan = 0 AND is_disbursement = 0 ";
            }
        }

        $payment_type = $_GET['payment_type'] ?? '';
        if ($payment_type) {
            $qry .= " AND payment_type = '".$this->db->escape_str($payment_type)."' ";
        }

        if ($keyword) {
            $keyword = $this->db->escape_str($keyword);
            switch ($keyword_category) {
                case "Order ID":        $qry .= " AND order_id LIKE '%$keyword%' "; break;
                case "Username":        $qry .= " AND c_username LIKE '%$keyword%' "; break;
                case "Nama Pelanggan":  $qry .= " AND customer_text LIKE '%$keyword%' "; break;
                case "Nomor Pelanggan": $qry .= " AND phone LIKE '%$keyword%' "; break;
                case "Nomor Resi":      $qry .= " AND awb_number LIKE '%$keyword%' "; break;
                case "Nama Produk":     $qry .= " AND `json` LIKE '%$keyword%' "; break;
            }
        }

        $order_type = $_GET['order_type'] ?? '';
        $data['order_type'] = $order_type;
        if ($order_type) {
            if ($order_type == "Manual") {
                $qry .= " AND is_manual = 1 ";
            } else if ($order_type == "Marketplace") {
                $qry .= " AND is_manual = 0 ";
            } else if ($order_type == "Belum Dikonfigurasi") {
                $qry .= " AND is_configurated = 0 ";
            }
        }

        if ($c_type) {
            $qry .= " AND c_type = '".$this->db->escape_str($c_type)."' ";
        }

        $shop_id = $_GET['shop_id'] ?? '';
        if ($shop_id) {
            $qry .= " AND shop_id = '".$this->db->escape_str($shop_id)."' ";
        }

        // Server-side filter dari AG Grid
        if (isset($_GET['filter_field']) && isset($_GET['filter_value']) && isset($_GET['filter_operator'])) {
            $filter_fields = (array)$_GET['filter_field'];
            $filter_values = (array)$_GET['filter_value'];
            $filter_operators = (array)$_GET['filter_operator'];

            $filterConditions = [];
            foreach ($filter_fields as $index => $field) {
                if (isset($filter_values[$index]) && isset($filter_operators[$index])) {
                    $value = $this->db->escape_str($filter_values[$index]);
                    $operator = $this->db->escape_str($filter_operators[$index]);

                    $allowed_fields = [
                        'order_id','customer_text','pesanan_count','customer_price','pencairan_status',
                        'order_status','awb_number','marketplace','shop_name','brand','phone','is_manual',
                        'payment_type','c_username','cs','shipping','shipping_status','reverse_id',
                        'return_status','payment_status','pay_at','dana_pencairan','omset_kotor',
                        'diskon_penjual','biaya_lainnya','omset_bersih','marketplace_fee','komisi_afiliasi',
                        'hpp','c_type'
                    ];
                    if (!in_array($field, $allowed_fields)) continue;

                    if ($value === '-') {
                        $filterConditions[] = "($field IS NULL OR $field = '' OR $field = '-')";
                    } else if ($operator === 'equals') {
                        $filterConditions[] = "$field = '$value'";
                    } else if ($operator === 'contains') {
                        $filterConditions[] = "$field LIKE '%$value%'";
                    } else if ($operator === 'startsWith') {
                        $filterConditions[] = "$field LIKE '$value%'";
                    } else if ($operator === 'endsWith') {
                        $filterConditions[] = "$field LIKE '%$value'";
                    }
                }
            }
            if (!empty($filterConditions)) {
                $qry .= " AND (" . implode(" AND ", $filterConditions) . ")";
            }
        }

        $per_page_options = [30, 50, 100, 500];
        $limit = isset($_GET['limit']) && in_array($_GET['limit'], $per_page_options) ? (int)$_GET['limit'] : 30;
        $data['limit'] = $limit;
        $data['per_page_options'] = $per_page_options;

        $current_page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($current_page - 1) * $limit;

        $allowed_columns = ['id','date','order_id','customer_text','phone','awb_number','order_status','payment_status','pesanan_count','customer_price','hpp','c_type'];
        $sort_column = in_array($_GET['sort_column'] ?? '', $allowed_columns) ? $_GET['sort_column'] : 'date';
        $sort_order = strtoupper($_GET['sort_order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $count_query = $this->mymodel->selectWithQuery("SELECT COUNT(*) as total FROM transaction WHERE $qry AND type_sub = 'POS'");
        $total_data = $count_query[0]['total'] ?? 0;
        $data['total_data'] = $total_data;
        $data['page'] = ceil($total_data / $limit);
        $data['current_page'] = $current_page;

        $query = $this->mymodel->selectWithQuery("
            SELECT * FROM transaction
            WHERE $qry AND type_sub = 'POS'
            ORDER BY $sort_column $sort_order, id DESC
            LIMIT $offset, $limit
        ");
        $data['data'] = $query;
        $data['start'] = $offset + 1;
        $data['end']   = min($offset + $limit, $total_data);

        $data['active_filters'] = [];
        if (isset($_GET['filter_field'])) {
            foreach ((array)$_GET['filter_field'] as $index => $field) {
                if (isset($_GET['filter_value'][$index])) {
                    $data['active_filters'][$field][] = $_GET['filter_value'][$index];
                }
            }
        }

        // === GRAND TOTALS (tanpa LIMIT)
        $sumCols = [
            'pesanan_count','customer_price','dana_pencairan','omset_kotor',
            'diskon_penjual','biaya_lainnya','omset_bersih','marketplace_fee',
            'komisi_afiliasi','hpp'
        ];
        $selectParts = array_map(fn($c) => "SUM(COALESCE($c,0)) AS sum_$c", $sumCols);

        $totalsRow = $this->mymodel->selectWithQuery("
            SELECT ".implode(',', $selectParts)."
            FROM transaction
            WHERE $qry AND type_sub = 'POS'
        ");

        $totals = [];
        if (!empty($totalsRow[0])) {
            foreach ($sumCols as $c) $totals[$c] = (float)($totalsRow[0]["sum_$c"] ?? 0);
        }
        $data['totals'] = $totals; // <- kirim ke view

        // === JSON mode
        $accept = $this->input->get_request_header('Accept', TRUE);
        if (stripos($accept, 'application/json') !== FALSE) {
            $rows = [];
            foreach ($data['data'] as $v) {
                $fmt = function($n){ return is_numeric($n) ? number_format($n,0,'','.') : ($n ?? ''); };
                $marketplaceRow = $this->mymodel->selectWithQuery("SELECT img FROM marketplace WHERE name = '".($v['marketplace']??'')."'");
                $marketplaceImg = !empty($marketplaceRow[0]['img']) ? (base_url().'/assets/img/marketplace/'.$marketplaceRow[0]['img']) : (base_url().'/assets/img/marketplace/default.png');
                $shippingRow = $this->mymodel->selectWithQuery("SELECT img FROM shipping WHERE name = '".($v['shipping']??'')."'");
                $shippingImg = !empty($shippingRow[0]['img']) ? (base_url().'/assets/img/shipping/'.$shippingRow[0]['img']) : (base_url().'/assets/img/shipping/default.png');
                $date_text = !empty($v['date']) ? date('Y-m-d H:i:s', strtotime($v['date'])) : null;
                $customer_price_raw = (float)($v['customer_price'] ?? 0);

                $rows[] = [
                    'id' => (int)$v['id'],
                    'order_id' => $v['order_id'],
                    'date_raw' => $v['date'],
                    'date_text' => $date_text,
                    'customer_id' => (int)$v['customer'],
                    'customer_text' => $v['customer_text'] ?: '-',
                    'pesanan_count' => (int)($v['pesanan_count'] ?? 0),
                    'customer_price' => $customer_price_raw,
                    'customer_price_fmt' => $fmt($customer_price_raw),
                    'pencairan_status' => $v['pencairan_status'] ?: '-',
                    'pencairan_at' => $v['pencairan_at'] ?: null,
                    'order_status' => $v['order_status'] ?: '-',
                    'reverse_status' => $v['reverse_status'] ?: '',
                    'awb_number' => $v['awb_number'] ?: '-',
                    'marketplace' => $v['marketplace'] ?: '-',
                    'shop_name' => $v['shop_name'] ?: 'Manual',
                    'is_manual' => (int)($v['is_manual'] ?? 0),
                    'brand' => $v['brand'] ?: '',
                    'rts_at' => $v['rts_at'] ?: '',
                    'phone' => $v['phone'] ?: '-',
                    'payment_type' => $v['payment_type'] ?? '-',
                    'c_username' => !empty($v['c_username']) ? $v['c_username'] : '-',
                    'cs' => !empty($v['cs']) ? $v['cs'] : '-',
                    'shipping' => !empty($v['shipping']) ? $v['shipping'] : '-',
                    'shipping_status' => '-',
                    'reverse_id' => !empty($v['reverse_id']) ? $v['reverse_id'] : '',
                    'return_status' => !empty($v['return_status']) ? $v['return_status'] : '',
                    'payment_status' => !empty($v['payment_status']) ? $v['payment_status'] : '-',
                    'pay_at' => !empty($v['pay_at']) ? date('Y-m-d H:i:s', strtotime($v['pay_at'])) : '-',
                    'dana_pencairan' => (float)($v['dana_pencairan'] ?? 0),
                    'dana_pencairan_fmt' => $fmt($v['dana_pencairan'] ?? 0),
                    'omset_kotor' => (float)($v['omset_kotor'] ?? 0),
                    'omset_kotor_fmt' => $fmt($v['omset_kotor'] ?? 0),
                    'diskon_penjual' => (float)($v['diskon_penjual'] ?? 0),
                    'diskon_penjual_fmt' => $fmt($v['diskon_penjual'] ?? 0),
                    'biaya_lainnya' => (float)($v['biaya_lainnya'] ?? 0),
                    'biaya_lainnya_fmt' => $fmt($v['biaya_lainnya'] ?? 0),
                    'omset_bersih' => (float)($v['omset_bersih'] ?? 0),
                    'omset_bersih_fmt' => $fmt($v['omset_bersih'] ?? 0),
                    'marketplace_fee' => (float)($v['marketplace_fee'] ?? 0),
                    'marketplace_fee_fmt' => $fmt($v['marketplace_fee'] ?? 0),
                    'komisi_afiliasi' => (float)($v['komisi_afiliasi'] ?? 0),
                    'komisi_afiliasi_fmt' => $fmt($v['komisi_afiliasi'] ?? 0),
                    'marketplace_img' => $marketplaceImg,
                    'shipping_img' => $shippingImg,
                    'json' => $v['json'] ?? '',
                    'hpp' => (float)($v['hpp'] ?? 0),
                    'c_type' => $v['c_type']
                ];
            }

            $meta = [
                'total' => (int)$total_data,
                'page' => (int)$current_page,
                'per_page' => (int)$limit,
                'page_count' => (int)$data['page'],
                'sort_column' => $sort_column,
                'sort_order' => $sort_order,
                'start' => (int)$data['start'],
                'end' => (int)$data['end'],
            ];

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok' => true, 'rows' => $rows, 'meta' => $meta, 'totals' => $totals]));
            return;
        }

        $data['filters'] = $filters;
        $this->load->view('transaction/item', $data);
    }


    public function filter_values()
    {
        header('Content-Type: application/json');

        // ===========================
        // 1) Whitelist kolom untuk DISTINCT
        // ===========================
        $allowed = [
            // kolom lama
            'order_id','customer_text','pesanan_count','customer_price','pencairan_status',
            'pencairan_at','order_status','awb_number','marketplace','shop_name','is_manual',
            'brand','phone',
            // kolom tambahan dari Card
            'payment_type','c_username','cs','shipping','shipping_status','reverse_id','return_status',
            'payment_status','pay_at',
            'dana_pencairan','omset_kotor','diskon_penjual','biaya_lainnya','omset_bersih',
            'marketplace_fee','komisi_afiliasi',
            // opsional asset img (jarang untuk filter, tapi boleh)
            'marketplace_img','shipping_img', 'json'
        ];

        $field = $_GET['field'] ?? '';
        if (!in_array($field, $allowed, true)) {
            echo json_encode(['ok' => false, 'error' => 'Field not allowed']);
            return;
        }

        // ===========================
        // 2) Ambil & sanitasi filter (sesuai item())
        // ===========================
        $start_date       = $_GET['start_date'] ?? date('Y-m-01');
        $until_date       = $_GET['until_date'] ?? date('Y-m-d');
        $brand            = $_GET['brand'] ?? '';
        $marketplace      = $_GET['marketplace'] ?? '';
        $cs               = $_GET['cs'] ?? '';
        $keyword          = $_GET['keyword'] ?? '';
        $keyword_category = $_GET['keyword_category'] ?? 'Order ID';
        $order_status     = $_GET['order_status'] ?? '';
        $order_type       = $_GET['order_type'] ?? '';
        $c_type           = $_GET['c_type'] ?? '';
        $shop_id          = $_GET['shop_id'] ?? '';
        $ids              = $_GET['ids'] ?? '';
        $ekspedisi        = $_GET['ekspedisi'] ?? '';
        $pencairan        = $_GET['pencairan'] ?? '';
        $id_customer      = $_GET['id_customer'] ?? '';

        // ===========================
        // 3) Build WHERE
        // ===========================
        $qry = "";
        if ($id_customer) {
            $qry = " customer = '".$this->db->escape_str($id_customer)."' ";
        } else {
            $qry = " DATE(date) >= '".$this->db->escape_str($start_date)."' AND DATE(date) <= '".$this->db->escape_str($until_date)."' ";
            if ($brand == "LAINNYA") {
                $brands   = $this->mymodel->selectWithQuery("SELECT code as opt FROM brand WHERE status = 'ENABLE'");
                $idsBrand = "";
                foreach ($brands as $b) { $idsBrand .= "'".$this->db->escape_str($b['opt'])."',"; }
                $idsBrand = rtrim($idsBrand, ',');
                if ($idsBrand) $qry .= " AND brand NOT IN ($idsBrand) ";
            } elseif ($brand) {
                $qry .= " AND brand = '".$this->db->escape_str($brand)."' ";
            }
        }

        if ($ids)         { $qry .= " AND id IN ($ids) "; }
        if ($ekspedisi)   { $qry .= " AND shipping = '".$this->db->escape_str($ekspedisi)."' "; }
        if ($marketplace) { $qry .= " AND marketplace = '".$this->db->escape_str($marketplace)."' "; }
        if ($cs)          { $qry .= " AND cs = '".$this->db->escape_str($cs)."' "; }

        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND dana_pencairan > 0 AND is_disbursement > 0 ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else {
                $qry .= " AND order_status = '".$this->db->escape_str($order_status)."' ";
            }
        }

        if ($pencairan) {
            if ($pencairan == "Sudah Pencairan") {
                $qry .= " AND dana_pencairan > 0";
            } else if ($pencairan == "Belum Pencairan") {
                $qry .= " AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') AND c_type NOT IN ('Affiliate','Endorse','Free') AND dana_pencairan = 0 AND is_disbursement = 0 ";
            }
        }

        if ($order_type) {
            if ($order_type == "Manual") {
                $qry .= " AND is_manual = 1 ";
            } else if ($order_type == "Marketplace") {
                $qry .= " AND is_manual = 0 ";
            } else if ($order_type == "Belum Dikonfigurasi") {
                $qry .= " AND is_configurated = 0 ";
            }
        }

        if ($c_type)  { $qry .= " AND c_type = '".$this->db->escape_str($c_type)."' "; }
        if ($shop_id) { $qry .= " AND shop_id = '".$this->db->escape_str($shop_id)."' "; }

        if ($keyword) {
            $kw = $this->db->escape_str($keyword);
            switch ($keyword_category) {
                case "Order ID":        $qry .= " AND order_id LIKE '%$kw%' "; break;
                case "Username":        $qry .= " AND c_username LIKE '%$kw%' "; break;
                case "Nama Pelanggan":  $qry .= " AND customer_text LIKE '%$kw%' "; break;
                case "Nomor Pelanggan": $qry .= " AND phone LIKE '%$kw%' "; break;
                case "Nomor Resi":      $qry .= " AND awb_number LIKE '%$kw%' "; break;
                case "Nama Produk":     $qry .= " AND `json` LIKE '%$kw%' "; break;
            }
        }

        // ===========================
        // 4) DISTINCT values
        // ===========================
        // Catatan:
        // - Untuk field tanggal/teks/angka semua aman di DISTINCT.
        // - Untuk kolom numeric, DISTINCT akan mengembalikan angka unik mentah (JS akan casting ke string).
        // - type_sub = 'POS' sesuai filter kamu.
        $sql  = "SELECT DISTINCT $field AS val FROM transaction WHERE $qry AND type_sub = 'POS' ORDER BY val ASC";
        $rows = $this->mymodel->selectWithQuery($sql);

        $values = [];
        foreach ($rows as $r) {
            $values[] = (is_null($r['val']) || $r['val']==='') ? '-' : (string)$r['val'];
        }

        echo json_encode(['ok' => true, 'field' => $field, 'values' => $values]);
    }



    public function edit()
    {

        $data['title'] = 'Edit Order - ' . $this->template->title();

        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT *
        FROM transaction 
        WHERE id = '$id' AND is_manual = 1 AND type_sub = 'POS'");
        $data['data'] = $data['data'][0];

        if (empty($data['data'])) {
            redirect(base_url() . 'transaction');
        }

        $data['brand'] = $this->mymodel->selectWithQuery("SELECT *
        FROM brand
        ORDER BY code ASC");

        $data['marketplace'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        ORDER BY name ASC");

        $data['shipping'] = $this->mymodel->selectWithQuery("SELECT *
        FROM shipping
        ORDER BY name ASC");

        $data['product'] = $this->mymodel->selectWithQuery("SELECT *
        FROM product WHERE is_varian = 0
        ORDER BY name ASC");

        $data['cs'] = $this->mymodel->selectWithQuery("SELECT *
        FROM user
        WHERE role IN ('3')
        ORDER BY code ASC");

        $data['content'] = $this->load->view("transaction/edit", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }


    public function get_discount()
    {
        $dt = $_GET;
        $code = $dt['code'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM discount WHERE code = '$code'");

        $query = $query[0];

        $text = 0;
        $conf = array();
        $conf = $query;

        if ($dt['total'] >= $conf['min_nominal']) {
            if ($conf['type'] == "Persentase") {
                if (doubleval($conf['nominal']) > 0 && doubleval($dt['total']) > 0) {
                    $text = (doubleval($dt['total']) * doubleval($conf['nominal']) / 100);
                } else {
                    $text = 0;
                }
            } else if ($conf['type'] == "Nominal") {
                $text = doubleval($conf['nominal']);
            }
        }
        $html['type'] = $query['type'];
        $html['min_nominal'] = $query['min_nominal'];
        $html['nominal'] = $query['nominal'];
        $html['html'] = $text;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }

    public function sync_trx()
    {
        $param = $_GET;
        $this->template->sync_trx($param);
    }
    public function get_marketplace_fee()
    {
        $dt = $_POST;
        $id_marketplace = $dt['id_marketplace'];
        $id_trx = $dt['id_trx'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace WHERE name = '$id_marketplace'");

        $query = $query[0];
        $json = json_decode($query['configuration'], true);
        $text = 0;
        $conf = array();


        if ($json) {
            usort($json, function ($a, $b) {
                return $b['date'] <=> $a['date'];
            });
            foreach ($json as $k => $v) {
                if ($v['date'] <= $dt['date']) {
                    $conf = $v;
                    break;
                }
            }
            if ($conf['type'] == "Persentase") {
                if (doubleval($conf['fee']) > 0 && doubleval($dt['total_2']) > 0) {
                    $text = (doubleval($dt['total_2']) * doubleval($conf['fee']) / 100);
                } else {
                    $text = 0;
                }
            } else if ($conf['type'] == "Nominal") {
                $text = doubleval($conf['fee']);
            }
        }

        $dt = array();
        $dt['marketplace_fee'] = $text;
        if ($query) {
            $dt['jenis_potongan'] = "Admin " . $query['name'];
        }

        $this->db->update('transaction', $dt, array('id' => $id_trx));

        $html['html'] = $text;
        $html['jenis_potongan'] = strval($dt['jenis_potongan']);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }


    public function set_product()
    {
        $dt = $_POST;
        $id = $dt['id'];
        $id_product = $dt['product'];
        $customer = $dt['customer'];

        if ($id) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id' ");

            $query = $query[0];
            $json = json_decode($query['json'], true);
        } else {
            $trx = $_SESSION['trx'];
            $json = json_decode($trx['json'], true);
        }


        $text = "";
        $total = 0;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id_product'");

        $product = $query[0];

        if (empty($product)) {
            die;
        }

        if ($json[$id_product]) {
            $json[$id_product]['product'] = $dt['product'];
            $json[$id_product]['sku'] = $product['sku'];
            $json[$id_product]['product_text'] = $product['name'];
            $json[$id_product]['brand'] = $product['brand'];
            $json[$id_product]['brand_text'] = $product['brand_text'];
            $json[$id_product]['qty'] = $json[$id_product]['qty'] + 1;
            if ($customer == "Pelanggan") {
                $json[$id_product]['price'] = $product['price_normal'];
                $json[$id_product]['price_total'] = ($json[$id_product]['qty']) * $product['price_normal'];
            } else if ($customer == "Reseller") {
                $json[$id_product]['price'] = $product['price_reseller'];
                $json[$id_product]['price_total'] = ($json[$id_product]['qty']) * $product['price_reseller'];
            } else if ($customer == "Distributor") {
                $json[$id_product]['price'] = $product['price_distributor'];
                $json[$id_product]['price_total'] = ($json[$id_product]['qty']) * $product['price_distributor'];
            } else if ($customer == "Free" || $customer == "Affiliate") {
                $json[$id_product]['price'] = 0;
                $json[$id_product]['price_total'] = 0;
            }
        } else {
            $json[$id_product]['product'] = $dt['product'];
            $json[$id_product]['sku'] = $product['sku'];
            $json[$id_product]['product_text'] = $product['name'];
            $json[$id_product]['brand'] = $product['brand'];
            $json[$id_product]['brand_text'] = $product['brand_text'];
            $json[$id_product]['qty'] = 1;
            if ($customer == "Pelanggan") {
                $json[$id_product]['price'] = $product['price_normal'];
                $json[$id_product]['price_total'] = 1 * $product['price_normal'];
            } else if ($customer == "Reseller") {
                $json[$id_product]['price'] = $product['price_reseller'];
                $json[$id_product]['price_total'] = 1 * $product['price_reseller'];
            } else if ($customer == "Distributor") {
                $json[$id_product]['price'] = $product['price_distributor'];
                $json[$id_product]['price_total'] = 1 * $product['price_distributor'];
            } else if ($customer == "Free" || $customer == "Affiliate") {
                $json[$id_product]['price'] = 0;
                $json[$id_product]['price_total'] = 0;
            }
        }

        $price_total = 0;
        foreach ($json as $k => $v) {
            $price_total += $v['price_total'];
        }

        if ($id) {
            $dt = array();
            $dt['price_total'] = $price_total;
            $dt['json'] = json_encode($json, true);
            $this->db->update('transaction', $dt, array('id' => $id));
        } else {
            $dt = array();
            $dt['price_total'] = $price_total;
            $dt['json'] = json_encode($json, true);
            $_SESSION['trx'] = $dt;
        }
    }

    public function formatRupiah($angka)
    {
        return number_format($angka, 0, ',', '.');
    }
    public function get_cart()
    {
        $id = $_GET['id'];

        if ($id) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id' ");
            $query = $query[0];
            $json = json_decode($query['json'], true);
        } else {
            $trx = $_SESSION['trx'];
            $json = json_decode($trx['json'], true);
        }

        $text = "";
        $total = 0;
        $k = 0;
        foreach ($json as $k2 => $v) {
            $total += $v['price_total'];
            $remove = "'" . $v['product'] . "'";
            $text .= '
                <tr>
                    <td class="text-start pt-3" style="min-width:240px!important; white-space: normal;text-overflow: ellipsis;">
                        ' . $v['sku'] . ' | ' . $v['product_text'] . '
                    </td>
                    <td class="text-end pt-3">
                        ' . $this->formatRupiah($v['price']) . '
                        <input type="hidden" class="form-control text-end m-0 i-price" value="' . $v['price'] . '" id="i-price-' . $k . '">
                         <!-- Tampilkan diskon jika ada -->
                        <p class="text-danger" id="txt-disc-' . $k . '">
                            ' . (isset($v['discount']) && $v['discount'] > 0 ? 'Diskon: ' . $v['discount'] . (isset($v['discount_type']) && $v['discount_type'] === 'Persentase' ? '%' : '') : '') . '
                        </p>
                    </td>
                    <td class="text-end" style="width:80px!important;padding-top:6px!important">
                        <input autocomplete="off" type="text" data-id="' . $k . '" 
                            class="form-control text-end m-0 i-qty" 
                            value="' . $v['qty'] . '" 
                            id="i-qty-' . $k . '" 
                            style="height:35px;width:80px;box-sizing:border-box;display:inline-block;">
                    </td>
                    <td class="text-end pt-3">
                        <span id="txt-price-total-' . $k . '">' . $this->formatRupiah($v['price_total']) . '</span>
                        <input type="hidden" class="form-control text-end m-0 i-price-total" value="' . $v['price_total'] . '" id="i-price-total-' . $k . '">
                    </td>
                    <td class="text-end pt-3">
                        <div class="dropdown">
                            <button style="height: 20px; display: flex; align-items: center; justify-content: center;" 
                                    class="btn bg-transparent border-0 p-0" 
                                    type="button" 
                                    id="dropdownMenuButton' . $k . '" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                <i class="bi bi-three-dots text-primary"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton' . $k . '">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="showDiscountInput(' . $k . ')">Diskon</a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-red" href="#" onclick="delete_cart_' . $k . '(' . $remove . ')">Hapus</a>
                                </li>
                            </ul>
                        </div>
                        <div id="discount-input-' . $k . '" class="floating-discount text-start" style="display: none; width: 250px;">
                            <div class="discount-header">
                                <label for="discount-value-' . $k . '" style="font-size: 16px;">Disc. Produk</label>
                                <!-- Tombol close -->
                                <span class="close-btn" onclick="hideDiscountInput(' . $k . ')">&times;</span>
                            </div>
                            <div class="discount-content">
                                <label for="">Tipe Diskon</label>
                                <select type="text" class="form-control" id="discount-type-' . $k . '" onchange="updateDiscountedPrice(' . $k . ')">
                                    <option value="Nominal" " . (isset($v["discount_type"]) && $v["discount_type"] === "Nominal" ? "selected" : "") . ">Nominal</option>
                                    <option value="Persentase" " . (isset($v["discount_type"]) && $v["discount_type"] === "Persentase" ? "selected" : "") . ">Persentase</option>
                                </select>
                                <input type="text" class="form-control i-discount" placeholder="Masukkan diskon" 
                                    id="discount-value-' . $k . '" onkeyup="updateDiscountedPrice(' . $k . ')" 
                                    value="' . (isset($v['discount']) ? number_format($v['discount'], 0, ',', '.') : '') . '">
                                <label for="discounted-price-' . $k . '">Harga Setelah Diskon</label>
                                <input type="text" class="form-control text-start mt-2" id="discounted-price-' . $k . '" readonly>
                                <a href="#" class="btn btn-primary btn-sm mt-2" onclick="submitDiscount(' . $k . ')">Terapkan</a>
                            </div>
                        </div>
                    </td>
                </tr>

                <style>
                    .floating-discount {
                        position: absolute;
                        background: white;
                        padding: 15px;
                        border-radius: 10px;
                        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.15);
                        z-index: 100;
                        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
                        width: 250px;
                        max-width: 100%;
                        right: 0; /* Pastikan tetap dalam batas */
                        overflow: hidden; /* Hindari konten meluber */
                        white-space: nowrap; /* Pastikan teks tidak menyebabkan overflow */
                        margin-right: 10px;
                    }


                    .floating-discount {
                        display: flex;
                        align-items: center; /* Sejajarkan vertikal */
                        gap: 8px; /* Beri jarak antara input dan tombol */
                    }
                    .floating-discount input {
                        flex: 1; /* Biar input menyesuaikan lebar */
                        height: 35px; /* Samakan tinggi input */
                    }
                    .floating-discount a {
                        height: 35px; /* Samakan tinggi tombol */
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        white-space: nowrap; /* Agar teks tombol tidak terpotong */
                    }
                    .close-btn {
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        font-size: 20px;
                        cursor: pointer;
                        color: #000;
                    }

                    .close-btn:hover {
                        color: #ff0000;
                    }

                </style>


                <script>
                function hideDiscountInput(rowId) {
                    var discountDiv = document.getElementById("discount-input-" + rowId);
                    discountDiv.classList.add("hide");
                    discountDiv.classList.remove("show");
                    setTimeout(() => {
                        discountDiv.style.display = "none";
                    }, 300);
                }
                function showDiscountInput(rowId) {
                    var discountDiv = document.getElementById("discount-input-" + rowId);
                    if (discountDiv.style.display === "none" || discountDiv.style.display === "") {
                        discountDiv.style.display = "block";
                        setTimeout(() => {
                            discountDiv.classList.add("show");
                            discountDiv.classList.remove("hide");
                        }, 10);
                    } else {
                        discountDiv.classList.add("hide");
                        discountDiv.classList.remove("show");
                        setTimeout(() => {
                            discountDiv.style.display = "none";
                        }, 300);
                    }
                }

                function updateDiscountedPrice(rowId) {
                    var discountInput = document.getElementById("discount-value-" + rowId);
                    var priceElement = document.getElementById("i-price-" + rowId);
                    var discountType = document.getElementById("discount-type-" + rowId).value;
                    
                    // Ambil nilai input dan hapus format ribuan untuk perhitungan
                    var discount = parseFloat(hapusFormatRibuan(discountInput.value)) || 0;
                    var originalPrice = parseFloat(hapusFormatRibuan(priceElement.value));

                    var discountedPrice;
                    
                    if (discountType === "Persentase") {
                        discountedPrice = originalPrice - (originalPrice * (discount / 100));
                    } else {
                        discountedPrice = originalPrice - discount;
                    }

                    // Pastikan harga setelah diskon tidak negatif
                    discountedPrice = discountedPrice < 0 ? 0 : discountedPrice;

                    // Update nilai input harga setelah diskon
                    document.getElementById("discounted-price-" + rowId).value = formatRibuan(discountedPrice);

                    // Format ulang input diskon saat diketik
                    discountInput.value = formatRibuan(discount);
                }

                document.addEventListener("keyup", function (event) {
                    if (event.target.classList.contains("i-discount")) {
                        event.target.value = formatRibuan(hapusFormatRibuan(event.target.value));
                        updateDiscountedPrice(event.target.id.split("-").pop()); // Ambil rowId dari ID input
                    }
                });


                // Fungsi untuk mengubah angka menjadi format ribuan
                function formatRibuan(angka) {
                    return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }

                // Fungsi untuk menghapus format ribuan dan mengembalikan nilai asli
                function hapusFormatRibuan(angka) {
                    return angka.replace(/\./g, "");
                }


                function submitDiscount(rowId) {
                    var discountInput = document.getElementById("discount-value-" + rowId);
                    var discountFormatted = discountInput.value;
                    var discount = parseFloat(discountFormatted.replace(/\./g, "")) || 0; // Hapus titik sebelum parsing
                    var priceElement = document.getElementById("i-price-" + rowId);
                    var totalElement = document.getElementById("txt-price-total-" + rowId);
                    var discountText = document.getElementById("txt-disc-" + rowId);
                    var originalPrice = parseFloat(priceElement.value);
                    var qty = parseFloat($("#i-qty-" + rowId).val()) || 0; // Ambil quantity
                    var discountType = document.getElementById("discount-type-" + rowId).value; // Ambil tipe diskon

                    var discountedPrice;
                    
                    if (discountType === "Persentase") {
                        discountedPrice = originalPrice - (originalPrice * (discount / 100));
                    } else {
                        discountedPrice = originalPrice - discount;
                    }

                    var newTotal = discountedPrice * qty; // Hitung total dengan diskon dan quantity

                    // Update tampilan diskon dan total harga
                    discountText.innerText = "Diskon: " + discountFormatted + (discountType === "Persentase" ? "%" : "");
                    totalElement.innerText = newTotal.toFixed(0);

                    $("#i-price-total-" + rowId).val(newTotal.toFixed(0));
                    $("#txt-price-total-" + rowId).html(newTotal.toFixed(0));

                    $.ajax({
                        url: "' . base_url() . '/transaction/edit-cart",
                        type: "POST",
                        data: {
                            id: "' . $id . '",
                            id_product: ' . $v['product'] . ',
                            price_total: discountedPrice * qty,
                            qty: qty,
                            discount: discount, // Kirim angka tanpa titik
                            discount_type: discountType,
                            price: originalPrice,
                            customer: customer
                        },
                        success: function(response) {
                            var discountDiv = document.getElementById("discount-input-" + rowId);

                            var total = 0;
                            for (var i = 0; i < ' . count($json) . '; i++) {
                                total += parseFloat($("#i-price-total-" + i).val());
                            }
                            $("#total").html(total);
                            $("#total_1_text").text(total);

                            discountDiv.classList.add("hide");
                            discountDiv.classList.remove("show");
                            setTimeout(() => {
                                discountDiv.style.display = "none";
                            }, 300);
                        },
                        error: function(xhr, status, error) {
                            console.error("Gagal mengupdate harga: ", error);
                        }
                    });

                    var discountDiv = document.getElementById("discount-input-" + rowId);
                    discountDiv.classList.add("hide");
                    discountDiv.classList.remove("show");
                    setTimeout(() => {
                        discountDiv.style.display = "none";
                    }, 300);
                }


                function delete_cart_' . $k . '(id_product) {
                    $.ajax({
                        url: "' . base_url() . '/transaction/delete-cart",
                        type: "POST",
                        data: {
                            id: "' . $id . '",
                            id_product: id_product,
                        },
                        success: function(response) {
                            $.ajax({
                                dataType: "json",
                                url: "' . base_url() . '/transaction/get-cart?id=' . $id . '",
                                success: function(html) {
                                    $("#tbody").html(html.html);
                                    $("#total").html(html.total);
                                    
                                    if (html.total == 0) {
                                        $("#total_1_text").text(0);
                                    } else {
                                        $("#total_1_text").text(html.total);
                                    }

                                }
                            });
                        }
                    });
                }

                $("#i-qty-' . $k . '").keyup(function() {
                    var id = $(this).attr("data-id");
                    var qty = parseFloat($("#i-qty-" + id).val());
                    var price = parseFloat($("#i-price-" + id).val());
                    var discount = parseFloat($("#discount-value-" + id).val()) || 0; // Ambil nilai diskon

                    if (isNaN(qty)) {
                        qty = 0;
                    }
                    if (isNaN(price)) {
                        price = 0;
                    }

                    // Hitung total harga dengan memperhitungkan diskon
                    var discountedPrice = price - discount;
                    var val = qty * discountedPrice;

                    // Update tampilan total harga
                    $("#i-price-total-" + id).val(val);
                    $("#txt-price-total-" + id).html(val);

                    get_grand();

                    $.ajax({
                        url: "' . base_url() . '/transaction/edit-cart",
                        type: "POST",
                        data: {
                            id: "' . $id . '",
                            id_product: ' . $v['product'] . ',
                            qty: qty,
                            discount: discount,
                            price: price,
                            price_total: discountedPrice * qty
                        },
                        success: function(response) {
                            var total = 0;
                            for (var i = 0; i < ' . count($json) . '; i++) {
                                total += parseFloat($("#i-price-total-" + i).val());
                            }
                            $("#total").html(total);
                            $("#total_1_text").text(total);
                        }
                    });
                });

            
                function get_grand(){
                    // Implement grand total calculation logic here
                }
                </script>
                ';

            $k += 1;
        }

        $html['html'] = $text;
        $html['total'] = $total;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }

    function edit_cart()
    {
        $dt = $_POST;
        $id = $dt['id'];
        $id_product = $dt['id_product'];
        $qty = (int)$dt['qty'];
        $discount = (int)$dt['discount'];
        $price = (int)$dt['price'];
        $price_total = (int)$dt['price_total'];
        $discount_type = $dt['discount_type'];

        if ($id) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id'");
            $query = $query[0];

            $json = json_decode($query['json'], true);

            if (isset($json[$id_product])) {
                $json[$id_product]['qty'] = $qty;
                $json[$id_product]['price'] = $price;
                $json[$id_product]['price_total'] = $price_total;
                $json[$id_product]['discount'] = $discount;
                $json[$id_product]['discount_type'] = $discount_type;
            }

            $data_to_update = array('json' => json_encode($json, true));
            $this->db->update('transaction', $data_to_update, array('id' => $id));
        } else {
            $trx = $_SESSION['trx'];
            $json = json_decode($trx['json'], true);

            if (isset($json[$id_product])) {
                $json[$id_product]['qty'] = $qty;
                $json[$id_product]['price'] = $price;
                $json[$id_product]['price_total'] = $price_total;
                $json[$id_product]['discount'] = $discount;
                $json[$id_product]['discount_type'] = $discount_type;
            }

            $_SESSION['trx'] = array('json' => json_encode($json, true));
        }

        echo json_encode(['status' => 'success', 'updated_data' => $json[$id_product]]);
    }
    function delete_cart()
    {
        $dt = $_POST;
        $id = $dt['id'];
        $id_product = $dt['id_product'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id' ");

        $query = $query[0];

        $json = json_decode($query['json'], true);
        unset($json[$id_product]);
        $dt = array();
        $dt['json'] = json_encode($json, true);
        $this->db->update('transaction', $dt, array('id' => $id));
    }
    public function print()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id' AND type_sub = 'POS'");
        $data['data'] = $query[0];

        if (empty($data['data'])) {
            redirect(base_url() . 'transaction');
        }

        $data['template'] = $this->template;

        $data['title'] = 'Print ' . $data['data']['order_id'] . ' - ' . $this->template->title();

        $this->load->view('transaction/print', $data);
    }

    public function print_v2()
    {
        $user = $_SESSION['user'];

        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }

        foreach ($id as $k => $v) {
            $list_id .= "'" . $v . "',";
        }
        $list_id = substr($list_id, 0, -1);



        if ($list_id) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id IN ($list_id) AND type_sub = 'POS'
            ORDER BY date DESC, id DESC  ");
            $data['datas'] = $query;

            $dt = array();
            $dt['order_status'] = "PROCESSED";
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);

            $this->db->update('transaction', $dt, "id IN ($list_id) AND is_manual = '1' AND order_status IN ('UNPAID')");
        } else {
            return redirect(previous_url());
        }


        $data['template'] = $this->template;


        $data['title'] = 'Print Order - ' . $this->template->title();

        $this->load->view('transaction/print_v2', $data);
    }

    public function update()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dtt = $_POST['dtt'];
        $check = $_POST['check'];

        // unset($dt['birth_date']);

        if ($dt['date']) {
            $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));
        }
        if ($dt['rts_at']) {
            $dt['rts_at'] = DATE("Y-m-d H:i:s", strtotime($dt['rts_at']));
        }

        if ($dt['customer_text'] == "" && $dt['customer'] < 1) {
            $msg = 'Pelanggan wajib diisi!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // if ($dt['pay_at']) {
        //     $dt['payment_status'] = 'Paid';
        // } else {
        //     $dt['payment_status'] = 'Unpaid';
        // }

        if ($dt['omset_kotor'] == 0) {
            $dt['payment_status'] = "Paid";
        }
        $dt['price_total'] = $dt['omset_kotor'];
        $dt['biaya_lainnya'] = $dt['packing_price'] + $dt['other_price'];
        $dt['dana_pencairan'] = $dt['omset_kotor'] - $dt['diskon_penjual'];
        $dt['omset_bersih'] = $dt['omset_kotor'] - $dt['diskon_penjual'];

        if ($dt['order_status'] == "COMPLETED2") {
            $dt['order_status'] = "COMPLETED";
            $dt['is_disbursement'] = 1;
        } else {
            $dt['is_disbursement'] = 0;
        }

        if ($dt['is_endorse'] == "1" || $dt['c_type'] == "Affiliate" || $dt['c_type'] == "Endorse" || $dt['c_type'] == "Free") {
            $dt['is_endorse'] = "1";
            $dt['dana_pencairan'] = 0 - $dt['ongkir'];
            $dt['pencairan_at'] = '';
            $dt['pencairan_status'] = '';
            $dt['payment_type'] = '';
            $dt['payment_status'] = '';
        } else {
            $dt['is_endorse'] = "0";
        }

        if ($dt['payment_status'] == "Paid" && $dt['c_type'] == "Pelanggan" || $dt['c_type'] == "Reseller" || $dt['c_type'] == "Distributor") {
            $dt['pencairan_status'] = "Settlement";
            $dt['pencairan_at'] = DATE("Y-m-d H:i:s");
            $dt['dana_pencairan'] = $dt['customer_price'];
        }

        if ($check  == '1') {
            $dtc = $customer;
            $dt['c_type'] = "Pelanggan";
            $dtc['akun_type'] = "Pelanggan";
            $dtc['brand'] = $dt['brand'];
            $dtc['marketplace'] = $dt['marketplace'];
            $dtc['status'] = "Aktif";
            $dtc['created_at'] = DATE("Y-m-d H:i:s");
            $dtc['updated_at'] = DATE("Y-m-d H:i:s");
            $dtc['created_by'] = $user['id'];
            $dtc['full_name'] = $dt['customer_text'];
            $dtc['phone'] = $dt['phone'];
            $dtc['username'] = $dt['c_username'];
            $dtc['count_order'] = 1;
            $dtc['return_trx'] = $dt['return'];
            $dtc['customer_price'] = $dt['customer_price'];
            $dtc['price_total'] = $dt['price_total'];
            $dtc['komisi_afiliasi'] = intval($dt['komisi_afiliasi']);
            $dtc['omset_kotor'] = $dt['omset_kotor'];
            $dtc['diskon_penjual'] = $dt['diskon_penjual'];
            $dtc['omset_bersih'] = $dt['omset_bersih'];
            $dtc['dana_pencairan'] = $dt['dana_pencairan'];
            $dtc['marketplace_fee'] = intval($dt['marketplace_fee']);
            $dtc['first_order'] = strval($dt['date']);
            $dtc['last_order'] = strval($dt['date']);
            $dtc['birth_date'] = $dt['birth_date'];
            $dtc['address'] = $dt['address'];
            $dtc['province_text'] = $dt['province_text'];
            $dtc['city_text'] = $dt['city_text'];
            $dtc['subdistrict_text'] = $dt['subdistrict_text'];
            // print_r($dtc);die;
            $this->db->insert('customer', $dtc);
            $dt['customer'] = $this->db->insert_id();
        }

        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        $id_customer = $dt['customer'];

        $this->db->update('transaction', $dt, "id = '$id' AND is_manual = 1");

        $this->customer_summary($id_customer);
        if ($_POST['existing_customer'] > 0 && $_POST['existing_customer'] != $dt['customer']) {
            $this->customer_summary($_POST['existing_customer']);
        }


        $detail = $this->mymodel->selectWithQuery("SELECT json FROM transaction WHERE id = '$id'");
        $detail = $detail[0];
        if (empty($detail['json'])) {
            $msg = 'Produk wajib dipilih!';
            echo $this->template->alert_danger($msg);
            die;
        }
        $js = array();
        $i = 0;
        foreach (json_decode($detail['json'], true) as $k4 => $v4) {
            // print_r($v4);die;
            $js[$i]['qty'] += $v4['qty'];
            $js[$i]['item_sku'] = $v4['sku'];
            $js[$i]['item_name'] = $v4['product_text'];
            // $js[$i]['hpp'] = $v4['hpp'];
            $js[$i]['price_total'] = $v4['price_total'];
            $i++;
        }

        $dt['pesanan'] = json_encode($js, true);
        $dt['pesanan_count'] = intval(count($js));
        $dt['json'] = $detail['json'];

        if ($this->db->update('transaction', $dt, "id = '$id' AND is_manual = 1 ")) {
            $msg = 'Update data berhasil!';
            $this->generate_stock($id, $dt);
            // $this->customer_summary($dt['customer']);
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
    
    function generate_product_stock()
    {
        $data['product'] = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0
        ORDER BY sku ASC
        ");
        foreach ($data['product'] as $k => $v) {
            $id_product = $v['id'];
            $this->update_stock($id_product);
        }
    }
    function customer_summary($id_customer)
    {



        $dtt = array();
        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count, 
        SUM(omset_kotor) as omset_kotor,
        SUM(komisi_afiliasi) as komisi_afiliasi,
        SUM(diskon_penjual) as diskon_penjual,
        SUM(omset_bersih) as omset_bersih,
        SUM(dana_pencairan) as dana_pencairan,
        SUM(price_total) as price_total,
        SUM(marketplace_fee) as marketplace_fee,
        SUM(customer_price) as customer_price,
        SUM(transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'");

        $dtt['count_order'] = strval($query[0]['count']);
        $dtt['return_trx'] = strval($query[0]['returnn']);
        $dtt['omset_kotor'] = strval($query[0]['omset_kotor']);
        $dtt['komisi_afiliasi'] = strval($query[0]['komisi_afiliasi']);
        $dtt['diskon_penjual'] = strval($query[0]['diskon_penjual']);
        $dtt['omset_bersih'] = strval($query[0]['omset_bersih']);
        $dtt['dana_pencairan'] = strval($query[0]['dana_pencairan']);
        $dtt['price_total'] = strval($query[0]['price_total']);
        $dtt['marketplace_fee'] = strval($query[0]['marketplace_fee']);
        $dtt['customer_price'] = strval($query[0]['customer_price']);

        $query = $this->mymodel->selectWithQuery("SELECT id,date FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('UNPAID','CANCELLED','IN_CANCEL','RETURN','REFUND')  ORDER BY date ASC LIMIT 1");

        $dtt['first_order'] = strval($query[0]['date']);

        $dt = array();
        $dt['cb_cl'] = 'CL';
        $id = $query[0]['id'];
        $this->db->update('transaction', $dt, array('customer' => $id_customer));
        $dt = array();
        $dt['cb_cl'] = 'CB';
        $id = $query[0]['id'];
        $this->db->update('transaction', $dt, array('id' => $id));


        $query = $this->mymodel->selectWithQuery("SELECT date FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('UNPAID','CANCELLED','IN_CANCEL','RETURN','REFUND')  ORDER BY date DESC LIMIT 1");

        $dtt['last_order'] = strval($query[0]['date']);


        $json = array();
        $query = $this->mymodel->selectWithQuery("SELECT id,date,json,pesanan,is_manual,order_id FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('UNPAID','CANCELLED','IN_CANCEL','RETURN','REFUND') ORDER BY date ASC");

        foreach ($query as $kk => $vv) {
            $json[$kk]['date'] = $vv['date'];
            $json[$kk]['id'] = $vv['id'];
            $json[$kk]['order_id'] = $vv['order_id'];
            $json[$kk]['is_manual'] = $vv['is_manual'];
            if ($vv['is_manual'] == 1) {
                $json[$kk]['data'] = json_decode($vv['pesanan'], true);
            } else {
                $vv['pesanan'] = array();
                foreach (json_decode($vv['json'], true) as $kkk => $vvv) {
                    $vv['pesanan'][$kkk]['item_name'] = $vvv['product_text'];
                    $vv['pesanan'][$kkk]['item_sku'] = $vvv['sku'];
                    $vv['pesanan'][$kkk]['item_id'] = $vvv['product'];
                    $vv['pesanan'][$kkk]['qty'] = $vvv['qty'];
                }
                $json[$kk]['data'] = $vv['pesanan'];
            }
        }

        $dtt['pesanan'] = json_encode($json, true);


        $mg = '"MG"';
        $check = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE customer = '$id_customer' AND json LIKE '%$mg%' AND order_status NOT IN ('UNPAID','CANCELLED','IN_CANCEL','RETURN','REFUND') LIMIT 1");
        if ($check) {
            $dtt['brand'] = "MG";
        } else {
            $dtt['brand'] = "POME";
        }

        $this->db->update('customer', $dtt, array('id' => $id_customer));
        return $dt;
    }

    public function update_stock($id_product)
    {
        $dtp = [];
        $query = $this->mymodel->selectWithQuery("
            SELECT 
                SUM(qty_in) AS qty_in, 
                SUM(qty_in_pos) AS qty_in_pos, 
                SUM(qty_out) AS qty_out, 
                SUM(qty_out_pos) AS qty_out_pos, 
                SUM(qty_out_retur) AS qty_out_retur,
                SUM(qty) AS qty 
            FROM stock 
            WHERE product = '$id_product'
        ");

        $dtp['stock_in'] = strval($query[0]['qty_in']);
        $dtp['stock_in_pos'] = strval($query[0]['qty_in_pos']);
        $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
        $dtp['stock_out_pos'] = strval(abs($query[0]['qty_out_pos']) * -1);
        $dtp['stock_out_retur'] = strval(abs($query[0]['qty_out_retur']) * -1);
        $dtp['stock'] = strval(doubleval($query[0]['qty']));

        $this->db->update('product', $dtp, ['id' => $id_product]);
    }


    public function create()
    {


        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dtt = $_POST['dtt'];
        $check = $_POST['check'];

        // unset($dt['birth_date']);

        for (;;) {
            $order_id = "BHS" . DATE("Ymdhis") . $this->template->generateNumber(3);
            $check = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE order_id = '$order_id' LIMIT 1");
            if (empty($check)) {
                $dt['order_id'] = $order_id;
                break;
            }
        }
        $dt['date'] = DATE("Y-m-d H:i:s");
        $dt['cs'] = $user['code'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];
        $dt['marketplace'] = 'WA';
        // $dt['payment_status'] = 'Unpaid';
        $dt['order_status'] = 'UNPAID';
        $dt['type'] = 'Out';
        $dt['type_sub'] = 'POS';
        $dt['is_manual'] = 1;

        $this->db->insert('transaction', $dt);
        $id = $this->db->insert_id();
        return redirect(base_url() . 'transaction/edit?id=' . $id);

        die;

        $user = $_SESSION['user'];
        $data['title'] = 'Buat Order - ' . $this->template->title();
        $data['data'] = array();




        $data['brand'] = $this->mymodel->selectWithQuery("SELECT *
        FROM brand
        ORDER BY code ASC");

        $data['marketplace'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        ORDER BY name ASC");

        $data['shipping'] = $this->mymodel->selectWithQuery("SELECT *
        FROM shipping
        ORDER BY name ASC");

        $data['product'] = $this->mymodel->selectWithQuery("SELECT *
        FROM product WHERE is_varian = 0
        ORDER BY name ASC");

        $data['content'] = $this->load->view("transaction/create", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }


    public function store()
    {

        $customer = $_POST['customer'];

        if ($dt['customer_text'] == "" && $dt['customer'] == "") {
            $msg = 'Pelanggan wajib diisi!';
            echo $this->template->alert_danger($msg);
            die;
        }

        for (;;) {
            $order_id = "BHS" . DATE("Ymdhis") . $this->template->generateNumber(3);
            $check = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE order_id = '$order_id' LIMIT 1");
            if (empty($check)) {
                $dt['order_id'] = $order_id;
                break;
            }
        }


        if ($dt['price_total'] == 0) {
            $dt['payment_status'] = "Paid";
        }
        $dt['price_total'] = $dt['omset_kotor'];
        $dt['biaya_lainnya'] = $dt['packing_price'] + $dt['other_price'];
        $dt['dana_pencairan'] = $dt['omset_kotor'] - $dt['diskon_penjual'];
        $dt['omset_bersih'] = $dt['omset_kotor'] - $dt['diskon_penjual'];

        if ($check  == '1') {
            $dtc = $customer;
            $dt['c_type'] = "Pelanggan";
            $dtc['akun_type'] = "Pelanggan";
            $dtc['brand'] = $dt['brand'];
            $dtc['marketplace'] = $dt['marketplace'];
            $dtc['status'] = "Aktif";
            $dtc['created_at'] = DATE("Y-m-d H:i:s");
            $dtc['updated_at'] = DATE("Y-m-d H:i:s");
            $dtc['created_by'] = $user['id'];
            $dtc['full_name'] = $dt['customer_text'];
            $dtc['phone'] = $dt['phone'];
            $dtc['username'] = $dt['c_username'];
            $dtc['count_order'] = 1;
            $dtc['return_trx'] = $dt['return'];
            $dtc['customer_price'] = $dt['customer_price'];
            $dtc['price_total'] = $dt['price_total'];
            $dtc['komisi_afiliasi'] = intval($dt['komisi_afiliasi']);
            $dtc['omset_kotor'] = $dt['omset_kotor'];
            $dtc['diskon_penjual'] = $dt['diskon_penjual'];
            $dtc['omset_bersih'] = $dt['omset_bersih'];
            $dtc['dana_pencairan'] = $dt['dana_pencairan'];
            $dtc['marketplace_fee'] = intval($dt['marketplace_fee']);
            $dtc['first_order'] = strval($dt['date']);
            $dtc['last_order'] = strval($dt['date']);
            $dtc['birth_date'] = $dt['birth_date'];
            $dtc['address'] = $dt['address'];
            $dtc['province_text'] = $dt['province_text'];
            $dtc['city_text'] = $dt['city_text'];
            $dtc['subdistrict_text'] = $dt['subdistrict_text'];
            // print_r($dtc);die;
            $this->db->insert('customer', $dtc);
            $dt['customer'] = $this->db->insert_id();
        }

        // $dt['updated_at'] = DATE("Y-m-d H:i:s");
        // $dt['updated_by'] = $user['id'];

        $id_customer = $dt['customer'];

        $this->db->insert('transaction', $dt);
        $id = $this->db->insert_id();

        $this->customer_summary($id_customer);
        if ($_POST['existing_customer'] > 0 && $_POST['existing_customer'] != $dt['customer']) {
            $this->customer_summary($_POST['existing_customer']);
        }

        $detail = $this->mymodel->selectWithQuery("SELECT json FROM transaction WHERE id = '$id'");
        $detail = $detail[0];


        $js = array();
        $i = 0;
        foreach (json_decode($detail['json'], true) as $k4 => $v4) {
            $js[$i]['qty'] += $v4['qty'];
            $js[$i]['item_sku'] = $v4['sku'];
            $js[$i]['item_name'] = $v4['product_text'];
            $i++;
        }

        $dt['pesanan'] = json_encode($js, true);

        if ($this->db->update('transaction', $dt, array('id' => $id))) {
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function set_cs()
    {
        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT id,cs FROM transaction
        WHERE id = '$id'
        ");
        $data['data'] = $data['data'][0];
        $data['cs'] = $this->mymodel->selectWithQuery("SELECT * FROM user
        WHERE role IN ('3')
        ORDER BY code ASC
        ");
        $this->load->view("transaction/set_cs", $data);
    }

    public function set_cs_process()
    {
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");

        if ($this->db->update('transaction', $dt, array('id' => $id))) {
            $this->db->update('stock', $dt, array('id_trx' => $id));
            $this->db->update('stock_product_3rd', $dt, array('id_trx' => $id));
            $msg = 'Simpan data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Simpan data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function set_return()
    {
        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT id,json,return_at FROM transaction
        WHERE id = '$id'
        ");
        $data['data'] = $data['data'][0];
        $this->load->view("transaction/set_return", $data);
    }

    public function set_return_process()
    {
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $date = DATE('Y-m-d H:i:s', strtotime($_POST['date']));

        $this->db->select('pesanan,json,shipping,awb_number,marketplace,shop_id,shop_name,id,order_id');
        $dt  = $this->mymodel->selectDataOne('transaction', array('id' => $id));
        $dt['order_status'] = "RETURN";
        $order_id = $dt['order_id'];
        $marketplace = $dt['marketplace'];
        $shop_id = $dt['shop_id'];
        $user = $_SESSION['user'];
        $id_trx = $dt['id'];

        $dt['stock_product_3rd'] = json_decode($dt['pesanan'], true);
        $dt['stock'] = json_decode($dt['json'], true);

        if ($id) {
            $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'In' ");
            $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'In' ");
        }

        foreach ($dt['stock_product_3rd'] as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);
            $dts['shop_id'] = strval($dt['shop_id']);
            $dts['shop_name'] = strval($dt['shop_name']);
            $dts['brand'] = strval($dt['brand']);
            $dts['id_trx'] = strval($dt['id']);
            $dts['qty'] = 0 - abs($v2['qty']);
            // $dts['qty_out'] = abs($v2['qty']);
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';

            $dts['original_price'] = doubleval($v2['original_price']);
            $dts['price'] = doubleval($v2['price']);
            $dts['discount'] = doubleval($v2['discount']);
            $dts['price_total'] = doubleval($v2['price_total']);

            $dts['product_id'] = strval($v2['id_product_parent']);
            $dts['product_sku'] = strval($v2['sku_parent']);
            $dts['product_text'] = strval($v2['name_parent']);
            $dts['varian_id'] = strval($v2['id_product']);
            $dts['varian_sku'] = strval($v2['sku']);
            $dts['varian_text'] = strval($v2['name']);
            $dts['order_id'] = $dt['order_id'];
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";
            $dts['desc'] = "Penjualan";

            $dts['date'] = DATE("Y-m-d H:i:s", strtotime($date));
            $dts['type'] = "In";
            $dts['qty'] =  abs($v2['qty']);
            $dts['qty_out'] = '0';
            $dts['qty_out_pos'] = '0';
            $dts['qty_in'] = '0';
            $dts['qty_in_pos'] =  abs($v2['qty']);
            $dts['desc'] = "Return";

            $this->db->insert('stock_product_3rd', $dts);
        }
        foreach ($dt['stock'] as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);
            $dts['shop_id'] = strval($dt['shop_id']);
            $dts['shop_name'] = strval($dt['shop_name']);
            $dts['id_trx'] = strval($dt['id']);
            $dts['qty'] = 0 - abs($v2['qty']);
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';
            $dts['price'] = doubleval($v2['price']);
            $dts['discount'] = doubleval($v2['discount']);
            $dts['hpp'] = doubleval($v2['hpp']);
            $dts['price_total'] = doubleval($v2['price_total']);
            $dts['product'] = $v2['product'];
            $dts['product_text'] = $v2['product_text'];
            $dts['sku'] = $v2['sku'];
            $dts['order_id'] = $dt['order_id'];
            $dts['brand'] = strval($dt['brand']);
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";
            $dts['date'] = DATE("Y-m-d H:i:s", strtotime($date));
            $dts['type'] = "In";
            $dts['qty'] =  abs($v2['qty']);
            $dts['qty_out'] = '0';
            $dts['qty_out_pos'] = '0';
            $dts['qty_in'] = '0';
            $dts['qty_in_pos'] =  abs($v2['qty']);
            $this->db->insert('stock', $dts);
        }


        foreach ($dt['stock'] as $k => $v) {
            $id_product = $v['product'];
            $this->update_stock($id_product);
        }

        $dt = array();

        $dt['return_at'] = $date;
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['order_status'] = "RETURN";

        $this->db->update('transaction', $dt, array('id' => $id));



        $msg = 'Simpan data berhasil!';
        echo $this->template->alert_success($msg);
    }
    
    public function multi_return()
    {
        $data['content'] = $this->load->view('transaction/multi_return', $data, true);
        $data['title'] = 'Multi Return - ' . $this->template->title();
        $this->load->view('TemplateDashboard', $data);
    }
    
    public function get_transaction_by_awb()
    {
        $awb_number = $this->input->post('awb_number');
        
        $transaction = $this->mymodel->selectWithQuery("
            SELECT id, order_id, awb_number, marketplace, date, order_status, json AS pesanan 
            FROM transaction 
            WHERE awb_number = '".$this->db->escape_str($awb_number)."'
            LIMIT 1
        ");
        
        if (empty($transaction)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Transaksi dengan no resi '.$awb_number.' tidak ditemukan'
            ]);
            return;
        }
        
        $transaction = $transaction[0];
        
        if ($transaction['order_status'] === 'RETURN') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Transaksi dengan no resi '.$awb_number.' sudah berstatus RETURN'
            ]);
            return;
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $transaction
        ]);
    }
    
    // public function process_multi_return()
    // {
    //     // Ambil payload
    //     $returnList = json_decode($this->input->post('return_list') ?? '[]', true);
    //     $returnDateRaw = $this->input->post('return_date');

    //     // Normalisasi tanggal retur
    //     $date = date('Y-m-d H:i:s');
    //     if ($returnDateRaw) {
    //         $tmp = strtotime($returnDateRaw);
    //         if ($tmp !== false) $date = date('Y-m-d H:i:s', $tmp);
    //     }

    //     $user = $_SESSION['user'] ?? ['id' => null];

    //     $success = 0;
    //     $errors  = [];
    //     $queries = [];

    //     // --- Kumpulkan semua trxId yang perlu diproses (dari GOOD + dari trx_ids) ---
    //     $trxIdsFromGood = array_keys($grouped);
    //     $allTrxIds = array_values(array_unique(array_merge($trxIdsFromGood, $extraTrxIds)));

    //     if (empty($allTrxIds)) {
    //         echo json_encode([
    //             'status'  => 'error',
    //             'message' => 'Tidak ada transaksi untuk diproses.',
    //         ]);
    //         return;
    //     }

    //     foreach ($allTrxIds as $trxId) {
    //         $this->db->trans_start();
    //         try {
    //             // Pastikan transaksi ada (untuk referensi order_id/awb dsb bila diperlukan)
    //             $dt = $this->mymodel->selectDataOne('transaction', ['id' => $trxId]);
    //             if (!$dt) {
    //                 throw new Exception("Transaksi ID $trxId tidak ditemukan.");
    //             }

    //             $items = $grouped[$trxId] ?? []; // GOOD items utk trx ini (bisa kosong)

    //             // Jika ada GOOD items: hapus stok lama POS (In/Ongoing) -> reinsert In (RETURN) dgn COPY dari baris Out
    //             if (!empty($items)) {
    //                 // Hapus stok POS yang In / Ongoing agar tidak dobel
    //                 $this->db->where('id_trx', $trxId)
    //                         ->where('type_sub', 'POS')
    //                         ->where_in('type', ['Ongoing','In'])
    //                         ->delete('stock');

    //                 // (opsional) jika ada mirror table dan ingin dibersihkan juga:
    //                 $this->db->where('id_trx', $trxId)
    //                         ->where('type_sub', 'POS')
    //                         ->where_in('type', ['Ongoing','In'])
    //                         ->delete('stock_product_3rd');

    //                 foreach ($items as $it) {
    //                     $sku         = $it['sku'] ?? null;
    //                     $productText = $it['product'] ?? null;

    //                     // === Sumber: cari 1 baris Out yang cocok di tabel stock ===
    //                     $this->db->select('*')
    //                             ->from('stock')
    //                             ->where('id_trx', $trxId)
    //                             ->where('type_sub', 'POS')
    //                             ->where('type', 'Out')
    //                             ->group_start()
    //                                 ->where('sku', $sku)
    //                                 ->or_where('product_text', $productText)
    //                             ->group_end()
    //                             ->order_by('id', 'DESC')
    //                             ->limit(1);
    //                     $src = $this->db->get()->row_array();

    //                     if ($src) {
    //                         // Salin baris sumber dan ubah jadi In (RETURN)
    //                         $copy = $src;
    //                         unset($copy['id']); // auto increment

    //                         $copy['type']         = 'In';
    //                         $copy['order_status'] = 'RETURN';
    //                         $copy['status']       = 'Aktif';

    //                         // Pindahkan kuantitas OUT -> IN (sesuaikan kolom dgn skema Anda)
    //                         $copy['qty_in_pos'] = abs((int)($src['qty_out_pos'] ?? 0));
    //                         $copy['qty_in']     = abs((int)($src['qty_out'] ?? 0));

    //                         // Nol-kan field OUT pada salinan
    //                         $copy['qty_out_pos'] = 0;
    //                         $copy['qty_out']     = 0;

    //                         // Kolom ringkas qty (jika ada)
    //                         if (array_key_exists('qty', $copy)) {
    //                             $copy['qty'] = $copy['qty_in_pos'] ?: $copy['qty_in'];
    //                         }

    //                         // Pastikan referensi produk tetap dari sumber
    //                         $copy['sku']          = $src['sku'];
    //                         $copy['product_text'] = $src['product_text'] ?? ($src['product'] ?? $productText);

    //                         // Tanggal & audit
    //                         // Catatan: tidak mengambil dari transaction; gunakan tanggal retur sebagai timestamp pencatatan.
    //                         $copy['date']       = $date;
    //                         $copy['created_at'] = date('Y-m-d H:i:s');
    //                         $copy['updated_at'] = date('Y-m-d H:i:s');
    //                         $copy['created_by'] = $user['id'];

    //                         $this->db->insert('stock', $copy);
    //                     } else {
    //                         // Fallback jika baris Out tidak ditemukan -> tetap insert minimal
    //                         $fallback = [
    //                             'id_trx'       => $trxId,
    //                             'order_status' => 'RETURN',
    //                             'type'         => 'In',
    //                             'type_sub'     => 'POS',
    //                             'sku'          => $sku,
    //                             'product_text' => $productText,
    //                             'qty_in_pos'   => abs((int)($it['qty'] ?? 0)),
    //                             'qty_in'       => 0,
    //                             'qty_out_pos'  => 0,
    //                             'qty_out'      => 0,
    //                             'status'       => 'Aktif',
    //                             'date'         => $date,
    //                             'created_at'   => date('Y-m-d H:i:s'),
    //                             'updated_at'   => date('Y-m-d H:i:s'),
    //                             'created_by'   => $user['id'],
    //                         ];
    //                         if ($this->db->field_exists('qty', 'stock')) {
    //                             $fallback['qty'] = $fallback['qty_in_pos'];
    //                         }
    //                         $this->db->insert('stock', $fallback);
    //                     }

    //                     // Recalculate stok produk terkait (gunakan nama/ID produk sesuai implementasi Anda)
    //                     // Di kode Anda sebelumnya menerima $it['product'] (teks). Sesuaikan bila perlu.
    //                     $this->update_stock($it['product']);
    //                 }
    //             }

    //             // --- PAKSA STATUS RETURN UNTUK SEMUA BARIS STOCK DI TRANSAKSI INI (BAD ikut) ---
    //             $this->db->set('order_status', 'RETURN');
    //             if ($this->db->field_exists('updated_at', 'stock')) {
    //                 $this->db->set('updated_at', date('Y-m-d H:i:s'));
    //             }
    //             $this->db->where('id_trx', $trxId)->update('stock');

    //             // (Opsional) sinkronisasi di mirror table
    //             $this->db->set('order_status', 'RETURN');
    //             if ($this->db->field_exists('updated_at', 'stock_product_3rd')) {
    //                 $this->db->set('updated_at', date('Y-m-d H:i:s'));
    //             }
    //             $this->db->where('id_trx', $trxId)->update('stock_product_3rd');

    //             // Update status di tabel transaction
    //             $this->db->where('id', $trxId)->update('transaction', [
    //                 'return_at'    => $date,
    //                 'updated_at'   => date('Y-m-d H:i:s'),
    //                 'order_status' => 'RETURN'
    //             ]);

    //             $this->db->trans_complete();
    //             $queries = array_merge($queries, $this->db->queries);

    //             if ($this->db->trans_status() === false) {
    //                 throw new Exception('DB Transaction error');
    //             }

    //             $success++;
    //         } catch (Exception $e) {
    //             $this->db->trans_rollback();
    //             $errors[] = "ID $trxId: " . $e->getMessage();
    //         }
    //     }

    //     $message = "$success transaksi berhasil diproses retur.";
    //     if (!empty($errors)) {
    //         $message .= ' Gagal: ' . implode(' | ', $errors);
    //     }

    //     echo json_encode([
    //         'status'  => $success > 0 ? 'success' : 'error',
    //         'message' => $message,
    //         'queries' => $queries
    //     ]);
    // }

    public function process_multi_return()
    {
        $ids = $this->input->post('trx_ids'); 
        $return_list = $this->input->post('return_list');
        $payload = json_decode($return_list, true) ?: [];
        $date = $this->input->post('return_date');

        // Map: $statusMap[order_id_or_trxid][sku] = ['status'=>'GOOD|BAD', 'return_status'=>'RETURN|RETURN_UNSHIPPED', 'qty'=>int]
        $statusMap = [];
        foreach ($payload as $it) {
            $oid   = strval($it['order_id'] ?? '');      // bisa id transaksi dari FE
            $sku   = strval($it['sku'] ?? '');
            $qty   = intval($it['qty'] ?? 0);
            $stt   = strtoupper(strval($it['status'] ?? 'GOOD'));
            $rtn   = strtoupper(strval($it['return_status'] ?? 'RETURN')); // <= penting

            if ($oid && $sku) {
                $statusMap[$oid][$sku] = [
                    'status'        => ($stt === 'BAD' ? 'BAD' : 'GOOD'),
                    'return_status' => ($rtn === 'RETURN_UNSHIPPED' ? 'RETURN_UNSHIPPED' : 'RETURN'),
                    'qty'           => abs($qty),
                ];
            }
        }

        if (empty($ids)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tidak ada transaksi yang dipilih.'
            ]);
            return;
        }

        $user = $_SESSION['user'];
        $success = 0;
        $errors = [];

        foreach ($ids as $id) {
            $this->db->trans_start();

            try {
                $dt = $this->mymodel->selectDataOne('transaction', ['id' => $id]);

                if (!$dt) {
                    throw new Exception("Transaksi ID $id tidak ditemukan.");
                }

                $dt['order_status'] = "RETURN";
                $dt['stock_product_3rd'] = json_decode($dt['pesanan'], true);
                $dt['stock'] = json_decode($dt['json'], true);
                $dt['date'] = $date;

                $trxReturnTypes = []; // kumpulkan tipe retur yang muncul pada transaksi ini

                if (is_array($dt['stock'])) {
                    foreach ($dt['stock'] as $k => &$prod) {
                        $oidTrx   = trim(strval($dt['id']));        // id transaksi
                        $oidOrder = trim(strval($dt['order_id']));  // order marketplace
                        $skuVar   = trim(strval($prod['sku'] ?? ''));

                        $info = $statusMap[$oidTrx][$skuVar]
                            ?? $statusMap[$oidOrder][$skuVar]
                            ?? null;

                        $prod['status']        = $info ? strtoupper($info['status'])        : 'GOOD';
                        $prod['return_status'] = $info ? strtoupper($info['return_status']) : 'RETURN';

                        if ($info) {
                            $prod['qty_return'] = abs(intval($info['qty']));
                            $trxReturnTypes[$prod['return_status']] = true;
                        }
                    }
                    unset($prod);
                }




                // Hapus stok lama
                $this->db->delete('stock_product_3rd', "id_trx = '$id' AND type_sub = 'POS' AND type IN ('Ongoing', 'In')");
                $this->db->delete('stock', "id_trx = '$id' AND type_sub = 'POS' AND type IN ('Ongoing', 'In')");

                // Insert ke stock_product_3rd
                foreach ($dt['stock_product_3rd'] as $item) {
                    $dts = [
                        'shipping' => strval($dt['shipping']),
                        'awb_number' => strval($dt['awb_number']),
                        'order_status' => "RETURN",
                        'marketplace' => strval($dt['marketplace']),
                        'shop_id' => strval($dt['shop_id']),
                        'shop_name' => strval($dt['shop_name']),
                        'brand' => strval($dt['brand'] ?? ''),
                        'id_trx' => $id,
                        'qty' => abs($item['qty']),
                        'qty_out' => 0,
                        'qty_out_pos' => 0,
                        'qty_in' => 0,
                        'qty_in_pos' => abs($item['qty']),
                        'original_price' => doubleval($item['original_price']),
                        'price' => doubleval($item['price']),
                        'discount' => doubleval($item['discount']),
                        'price_total' => doubleval($item['price_total']),
                        'product_id' => strval($item['id_product_parent']),
                        'product_sku' => strval($item['sku_parent']),
                        'product_text' => strval($item['name_parent']),
                        'varian_id' => strval($item['id_product']),
                        'varian_sku' => strval($item['sku']),
                        'varian_text' => strval($item['name']),
                        'order_id' => $dt['order_id'],
                        'type' => "In",
                        'type_sub' => "POS",
                        'created_at' => date("Y-m-d H:i:s"),
                        'date' => $date,
                        'created_by' => $user['id'],
                        'status' => "Aktif",
                        'desc' => "Return"
                    ];
                    $this->db->insert('stock_product_3rd', $dts);
                }

                foreach ($dt['stock'] as $item) {
                    $stt       = strtoupper($item['status'] ?? 'GOOD');           // GOOD | BAD
                    $retType   = strtoupper($item['return_status'] ?? 'RETURN');  // RETURN | RETURN_UNSHIPPED
                    $qtyAbs    = intval($item['qty_return'] ?? abs(intval($item['qty'])));

                    $isBad     = ($stt === 'BAD');

                    // Jika BAD (barang rusak), hapus jejak Out lama (tetap seperti sebelumnya)
                    if ($isBad) {
                        $this->db->delete(
                            'stock',
                            "id_trx = '$id' AND type_sub = 'POS' AND type = 'Out' AND sku = '{$item['sku']}'"
                        );
                    }

                    // Arah & kolom kuantitas ditentukan HANYA oleh Good/Bad:
                    // - GOOD => stok bertambah (In POS)
                    // - BAD  => stok berkurang sebagai retur (Out, qty_out_retur naik)
                    $type          = $isBad ? "Out" : "In";
                    $qty           = $isBad ? -$qtyAbs : +$qtyAbs;
                    $qty_in_pos    = $isBad ? 0       : $qtyAbs;
                    $qty_out_retur = $isBad ? $qtyAbs : 0;

                    $this->db->insert('stock', [
                        'shipping'       => strval($dt['shipping']),
                        'awb_number'     => strval($dt['awb_number']),
                        'order_status'   => $retType, // label saja (RETURN/RETURN_UNSHIPPED)
                        'marketplace'    => strval($dt['marketplace']),
                        'shop_id'        => strval($dt['shop_id']),
                        'shop_name'      => strval($dt['shop_name']),
                        'brand'          => strval($dt['brand'] ?? ''),
                        'id_trx'         => $id,
                        'qty'            => $qty,
                        'qty_out'        => 0,
                        'qty_out_pos'    => 0,
                        'qty_in'         => 0,
                        'qty_in_pos'     => $qty_in_pos,
                        'qty_out_retur'  => $qty_out_retur,
                        'price'          => doubleval($item['price'] ?? 0),
                        'discount'       => doubleval($item['discount'] ?? 0),
                        'hpp'            => doubleval($item['hpp'] ?? 0),
                        'price_total'    => doubleval($item['price_total'] ?? 0),
                        'product'        => $item['product'],
                        'product_text'   => $item['product_text'] ?? '',
                        'sku'            => $item['sku'],
                        'order_id'       => $dt['order_id'],
                        'type'           => $type,      // In / Out
                        'type_sub'       => "POS",
                        'created_at'     => date("Y-m-d H:i:s"),
                        'date'           => $date,
                        'created_by'     => $user['id'] ?? 1,
                        'status'         => "Aktif",
                        'desc'           => "Return Scan"
                    ]);

                    $this->update_stock($item['product']);
                }


                $trxOrderStatus =
                    (isset($trxReturnTypes['RETURN']) ? 'RETURN' :
                    (isset($trxReturnTypes['RETURN_UNSHIPPED']) ? 'RETURN_UNSHIPPED' : 'RETURN'));

                $this->db->update('transaction', [
                    'return_at'    => $date,
                    'updated_at'   => date("Y-m-d H:i:s"),
                    'order_status' => $trxOrderStatus
                ], ['id' => $id]);


                $this->db->trans_complete();
                $queries = $this->db->queries;

                if ($this->db->trans_status() === FALSE) {
                    throw new Exception("DB Transaction error");
                }

                $success++;
            } catch (Exception $e) {
                $this->db->trans_rollback();

                $db_error = $this->db->error();
                $db_error_msg = $db_error['message'] ?? 'Unknown DB error';

                $errors[] = "ID $id: ".$e->getMessage()." | DB Error: $db_error_msg";
            }
        }

        $message = "$success transaksi berhasil diproses retur.";
        if (!empty($errors)) {
            $message .= ' Gagal: '.implode(' | ', $errors);
        }

        echo json_encode([
            'status' => $success > 0 ? 'success' : 'error',
            'message' => $message,
            'queries' => $queries
        ]);

    }

    function generate_stock($id, $dt)
    {

        $user = $_SESSION['user'];
        $id_trx = $id;

        if ($id) {
            $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
            $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
        }

        $json = json_decode($dt['json'], true);
        foreach ($json as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);

            $dts['brand'] = strval($v2['brand']);
            $dts['id_trx'] = strval($id);
            $dts['qty'] = 0 - abs($v2['qty']);
            $dts['qty_out'] = 0;
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';
            $dts['price'] = $v2['price'];
            $dts['hpp'] = doubleval($v2['hpp']);
            $dts['price_total'] = $v2['price_total'];
            $dts['product'] = $v2['product'];
            $dts['product_text'] = $v2['product_text'];
            $dts['sku'] = $v2['sku'];
            $dts['order_id'] = $dt['order_id'];
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['desc'] = "Penjualan";
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";
            $this->db->insert('stock', $dts);
        }
        $this->calculate_stock_product();
    }

    function calculate_stock_product()
    {
        $product = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
        ORDER BY sku ASC
        ");
        $product_arr = array();
        foreach ($product as $k => $v) {
            $product_arr[$v['id']] = $v;
        }

        foreach ($product_arr as $k => $v) {
            $id_product = $v['id'];
            $this->update_stock($id_product);
        }
    }


    public function set_resi()
    {
        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT id,awb_number FROM transaction
        WHERE id = '$id'
        ");
        $data['data'] = $data['data'][0];

        $this->load->view("transaction/set_resi", $data);
    }

    public function set_resi_process()
    {
        // print_r($_POST);die;
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");

        if ($this->db->update('transaction', $dt, array('id' => $id))) {
            $msg = 'Simpan data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Simpan data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }


    public function refresh()
    {
        $dt = $_GET;
        $data['data']['order_id'] = $dt['order_id'];
        $data['data']['marketplace'] = $dt['marketplace'];
        $this->load->view("transaction/refresh", $data);
    }

    public function refresh_process()
    {
        $dt = $_POST;

        $url = $this->template->endpoint_url() . 'api/marketplace/order/detail?order_id=' . $dt['order_id'] . '&marketplace=' . $dt['marketplace'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);
        curl_close($curl);

        if ($response['status'] == true) {
            $msg = 'Refresh data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $errorMessage = isset($response['message']) ? $response['message'] : 'Terjadi kesalahan yang tidak diketahui.';
            $msg = 'Refresh data tidak berhasil! Error: ' . $errorMessage;
            echo $this->template->alert_danger($msg);
        }
    }


    public function action()
    {

        $id_selected_v2 = $_POST['id_selected_v2'];

        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }

        $is_manual = $_POST['is_manual'];
        $marketplace = $_POST['marketplace'];
        $brand = $_POST['brand'];
        $order_id = $_POST['order_id'];
        $code = $_GET['code'];
        $data['data']['id'] = $id;
        $data['data']['code'] = $code;
        if ($code == "barang_diterima") {
            $data['question'] = "Apakah kamu yakin ingin mengubah status order ini manjadi <b>Barang Diterima</b>?";
            $data['btn'] = "Barang Diterima";
        } else if ($code == "hapus_data") {
            $data['question'] = "Apakah kamu yakin ingin menghapus data order ini?";
            $data['btn'] = "Hapus Data";
        } else if ($code == "refresh_data") {
            $data['question'] = "Apakah kamu yakin ingin merefresh data order ini?";
            $data['btn'] = "Refresh Data";
        }
        $this->load->view("transaction/action", $data);
    }

    public function action_process()
    {
        $list_id = "";
        $code = $_POST['code'];
        $user = $_SESSION['user'];

        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }

        $is_manual = $_POST['is_manual'];
        $marketplace = $_POST['marketplace'];
        $brand = $_POST['brand'];
        $order_id = $_POST['order_id'];
        if ($code == "barang_diterima") {
            foreach ($id as $k => $v) {
                if ($is_manual[$k] == "1") {
                    $list_id .= "'" . $v . "',";
                }
            }
            $list_id = substr($list_id, 0, -1);


            if ($list_id) {
                $dt = array();
                $dt['order_status'] = "DELIVERED";
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $this->db->update('transaction', $dt, " id IN ($list_id) AND is_manual = '1' ");
                $msg = 'Ubah status order menjadi <b>Barang Diterima</b> berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data manual!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "hapus_data") {
            foreach ($id as $k => $v) {
                if ($is_manual[$k] == "1") {
                    $list_id .= "'" . $v . "',";
                }
            }
            $list_id = substr($list_id, 0, -1);


            if ($list_id) {
                $dt = array();

                $this->db->delete('transaction', " id IN ($list_id) AND is_manual = '1' ");

                $this->db->delete('stock', " id_trx IN ($list_id) ");

                $msg = 'Hapus data berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data manual!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "refresh_data") {

            foreach ($id as $k => $v) {
                if ($is_manual[$k] == "0") {
                    $list_id .= "'" . $v . "',";
                }
            }
            $list_id = substr($list_id, 0, -1);

            if ($list_id) {
                $trx = $this->mymodel->selectWithQuery("SELECT id,order_id,marketplace
                FROM transaction WHERE is_manual = 0 AND DATE(date) >= '2024-11-01' AND DATE(date) <= '2024-12-31' AND dana_pencairan = 0 AND order_status IN ('SETTLEMENT','COMPLETED') AND customer_price > 0 AND type_sub = 'POS';");
                foreach ($trx as $k => $v) {
                    $url = $this->template->endpoint_url() . 'api/marketplace/order/detail?order_id=' . $v['order_id'] . '&marketplace=' . $v['marketplace'];
                    $curl = curl_init();

                    // echo $url;
                    // die;
                    // echo '<br>';

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 1,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json'
                        ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                }
                $msg = 'Refresh data berhasil! Silahkan tunggu beberapa saat hingga data diperbarui!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data order marketplace!';
                echo $this->template->alert_danger($msg);
            }
        }
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("transaction/delete", $data);
    }

    public function delete()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];

        // $data = $this->mymodel->selectWithQuery("SELECT customer FROM transaction WHERE id = '$id'");
        // $data = $data[0];

        $trx = $this->mymodel->selectDataOne('transaction', array('id' => $id));


        $dt['stock'] = json_decode($trx['json'], true);

        $id_trx = $id;
        if ($id_trx) {
            $this->db->delete('stock_product_3rd', " id_trx = '$id_trx'");
            $this->db->delete('stock', " id_trx = '$id_trx'");
        }


        foreach ($dt['stock'] as $k => $v) {
            $id_product = $v['product'];
            $this->update_stock($id_product);
        }



        if ($this->db->delete('transaction', array('id' => $id))) {

            if ($trx['customer'] > 0) {
                $this->customer_summary($trx['customer']);
            }

            // $msg = 'Hapus data berhasil!';
            // echo $this->template->alert_success($msg);
            redirect(base_url('/transaction'));
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }


    public function sync()
    {

        $data['store'] = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace FROM marketplace_config WHERE status = 'Aktif' ORDER BY marketplace DESC, shop_name ASC");

        $this->load->view("transaction/sync", $data);
    }

    public function sync_process()
    {

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $shop_id = $dt['shop_id'];
        $start_date = $dt['until_date'];
        $until_date = $dt['until_date'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->template->endpoint_url() . 'api/marketplace/order?marketplace=' . $marketplace . '&shop_id=' . $shop_id . '&start_date=' . $start_date . '&until_date=' . $until_date,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: ci_session=ioddoljecqot0p8sffikifou4ons0j40'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);
        if ($response['status'] == true) {
            $msg = $response['msg'];
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = $response['msg'];
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function import_resi()
    {

        $data['param'] = $this->template->get_param();

        $this->load->view("transaction/import_resi", $data);
    }


    public function import_pencairan()
    {

        $data['param'] = $this->template->get_param();

        $this->load->view("transaction/import_pencairan", $data);
    }


    public function import_customer()
    {

        $data = $_SESSION['filter'];

        $this->load->view("transaction/import_customer", $data);
    }

    public function import_customer_process()
    {
        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];
        $marketplace = $_POST['marketplace'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        if ($type != 'xlsx') {
            $msg = 'Importing data tidak berhasil! Pastikan tipe file adalah xlsx!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // $file = $this->request->getFile('file');

        $this->load->library('upload');

        // Set upload configuration
        $config['upload_path'] = './assets/webfile/excel/';  // Ensure this directory exists and is writable
        $config['allowed_types'] = 'xls|xlsx';
        // $config['max_size'] = 2048;  // 2MB
        $config['encrypt_name'] = TRUE;  // To avoid file name conflicts

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('file')) {
            // Upload failed
            $error = $this->upload->display_errors();
            $msg = $error;
            echo $this->template->alert_danger($msg);
            die;
        } else {
            // Upload success
            $upload_data = $this->upload->data();
            $filepath = $upload_data['full_path'];

            // You can store the file information in the database if needed
            $data = array(
                'uploaded_fileinfo' => $upload_data,
                // other data to be saved in the database
            );

            // Load the file helper to interact with the uploaded file
            $this->load->helper('file');

            // Example of handling the uploaded file
            // $file_content = read_file($filepath);
            // Do something with the file content if needed

            // Display or process the uploaded file information
            // echo "File uploaded successfully: " . $filepath;

        }

        if ($data['uploaded_fileinfo']['file_path']) {
            $filepath = $data['uploaded_fileinfo']['file_path'] . $data['uploaded_fileinfo']['file_name'];

            $qry = '';

            $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet  = $reader->load($filepath);
            $sheet_data  = $spreadsheet->getActiveSheet()->toArray();


            $column_index = array();
            if ($marketplace == "SHOPEE") {
                foreach ($sheet_data[0] as $k => $v) {
                    if ($v == 'No. Pesanan') {
                        $column_index['id_trx'] = $k;
                    }
                    if ($v == 'Nama Penerima') {
                        $column_index['customer_text'] = $k;
                    }
                    if ($v == 'No. Telepon') {
                        $column_index['phone'] = $k;
                    }
                    if ($v == 'Username (Pembeli)') {
                        $column_index['c_username'] = $k;
                    }
                    if ($v == 'Alamat Pengiriman') {
                        $column_index['address'] = $k;
                    }
                    if ($v == 'Kota/Kabupaten') {
                        $column_index['city_text'] = $k;
                    }
                    if ($v == 'Provinsi') {
                        $column_index['province_text'] = $k;
                    }
                }
            } else if ($marketplace == "LAZADA") {
                foreach ($sheet_data[0] as $k => $v) {
                    if ($v == 'orderNumber') {
                        $column_index['id_trx'] = $k;
                    }
                    if ($v == 'customerName') {
                        $column_index['customer_text'] = $k;
                    }
                    // if($v == 'customerName'){
                    //     $column_index['phone'] = $k;
                    // }
                    // if($v == 'customerName'){
                    //     $column_index['c_username'] = $k;
                    // }
                    // if($v == 'shippingAddresss'){
                    //     $column_index['address'] = $k;
                    // }
                    // if($v == 'Kota/Kabupaten'){
                    //     $column_index['city_text'] = $k;
                    // }
                    // if($v == 'Provinsi'){
                    //     $column_index['province_text'] = $k;
                    // }
                    if ($v == 'shippingProvider') {
                        $column_index['shipping'] = $k;
                    }
                    if ($v == 'trackingCode') {
                        $column_index['awb_number'] = $k;
                    }
                }
                // print_r($column_index);die;
            } else if ($marketplace == "TIKTOK") {
                foreach ($sheet_data[0] as $k => $v) {
                    if ($v == 'Order ID') {
                        $column_index['id_trx'] = $k;
                    }
                    if ($v == 'Buyer Username') {
                        $column_index['customer_text'] = $k;
                    }
                    if ($v == 'Phone #') {
                        $column_index['phone'] = $k;
                    }
                    if ($v == 'Recipient') {
                        $column_index['c_username'] = $k;
                    }
                    // if($v == 'Alamat Pengiriman'){
                    //     $column_index['address'] = $k;
                    // }
                    if ($v == 'Regency and City') {
                        $column_index['city_text'] = $k;
                    }
                    if ($v == 'Province') {
                        $column_index['province_text'] = $k;
                    }
                }
            } else {
                $msg = 'Importing data tidak berhasil! channel tidak ditemukan!';
                echo $this->template->alert_danger($msg);
                die;
            }
            if ($column_index['id_trx'] == '') {
                $msg = 'Importing data tidak berhasil! header excel tidak sesuai!';
                echo $this->template->alert_danger($msg);
                die;
            }
            foreach ($sheet_data as $k => $v) {
                if ($k > 0) {
                    $dt = array();
                    $i = 0;

                    $order_id = $v[$column_index['id_trx']];
                    $query = $this->mymodel->selectWithQuery("SELECT id,customer 
                    FROM transaction WHERE order_id = '$order_id' AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                    $query = $query[0];

                    $dt = array();
                    $dt['id'] = $query['id'];
                    foreach ($column_index as $k2 => $v2) {
                        $dt[$k2] = strval($v[$v2]);
                    }

                    if ($dt['id']) {
                        $dt['updated_at'] = DATE('Y-m-d H:i:s');
                        $this->db->update('transaction', $dt, array('id' => $dt['id']));

                        $dtt = array();
                        $dtt['updated_at'] = DATE('Y-m-d H:i:s');
                        if ($dt['customer_text']) {
                            $dtt['full_name'] = strval($dt['customer_text']);
                        }
                        if ($dt['phone']) {
                            $dtt['phone'] = strval($dt['phone']);
                        }
                        if ($dt['c_username']) {
                            $dtt['username'] = strval($dt['username']);
                        }
                        if ($dt['address']) {
                            $dtt['address'] = strval($dt['address']);
                        }
                        if ($dt['city_text']) {
                            $dtt['city_text'] = strval($dt['city_text']);
                        }
                        if ($dt['province_text']) {
                            $dtt['province_text'] = strval($dt['province_text']);
                        }
                        $this->db->update('customer', $dtt, array('id' => $query['customer']));
                    }
                }
            }

            // foreach ($data['product'] as $k => $v) {
            //     $id_product = $v['id'];
            //     $this->update_stock($id_product);
            // }

            $msg = 'Importing data berhasil!';
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = 'Importing data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function import_pencairan_process()
    {
        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];
        $marketplace = $_POST['marketplace'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        if ($type != 'xlsx') {
            $msg = 'Importing data tidak berhasil! Pastikan tipe file adalah xlsx!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // $file = $this->request->getFile('file');

        $this->load->library('upload');

        // Set upload configuration
        $config['upload_path'] = './assets/webfile/excel/';  // Ensure this directory exists and is writable
        $config['allowed_types'] = 'xls|xlsx';
        // $config['max_size'] = 2048;  // 2MB
        $config['encrypt_name'] = TRUE;  // To avoid file name conflicts

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('file')) {
            // Upload failed
            $error = $this->upload->display_errors();
            $msg = $error;
            echo $this->template->alert_danger($msg);
            die;
        } else {
            // Upload success
            $upload_data = $this->upload->data();
            $filepath = $upload_data['full_path'];

            // You can store the file information in the database if needed
            $data = array(
                'uploaded_fileinfo' => $upload_data,
                // other data to be saved in the database
            );

            // Load the file helper to interact with the uploaded file
            $this->load->helper('file');

            // Example of handling the uploaded file
            // $file_content = read_file($filepath);
            // Do something with the file content if needed

            // Display or process the uploaded file information
            // echo "File uploaded successfully: " . $filepath;

        }

        if ($data['uploaded_fileinfo']['file_path']) {
            $filepath = $data['uploaded_fileinfo']['file_path'] . $data['uploaded_fileinfo']['file_name'];

            $qry = '';

            $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet  = $reader->load($filepath);
            $sheet_data  = $spreadsheet->getActiveSheet()->toArray();



            $column_index = array();
            if ($marketplace == "SHOPEE") {
                foreach ($sheet_data[17] as $k => $v) {
                    if (strpos($v, 'No. Pesanan') !== false) {
                        $column_index['order_id'] = $k;
                    }
                    if (strpos($v, 'Jumlah') !== false) {
                        $column_index['dana_pencairan'] = $k;
                    }
                }
                for ($i = 0; $i <= 17; $i++) {
                    unset($sheet_data[$i]);
                }
            } else if ($marketplace == "LAZADA") {
                foreach ($sheet_data[0] as $k => $v) {
                }
                // print_r($column_index);die;
            } else if ($marketplace == "TIKTOK") {
                foreach ($sheet_data[0] as $k => $v) {
                    if (strpos($v, 'Order/adjustment ID') !== false) {
                        $column_index['order_id'] = $k;
                    }
                    if (strpos($v, 'Total settlement amount') !== false) {
                        $column_index['dana_pencairan'] = $k;
                    }
                    if (strpos($v, 'Subtotal before discounts') !== false) {
                        $column_index['omset_kotor'] = $k;
                    }
                    if (strpos($v, 'Subtotal after seller discounts') !== false) {
                        $column_index['omset_bersih'] = $k;
                    }
                    if (strpos($v, 'Seller discounts') !== false) {
                        $column_index['diskon_penjual'] = abs($k);
                    }
                    if (strpos($v, 'TikTok Shop commission fee') !== false) {
                        $column_index['marketplace_fee'] = abs($k);
                    }
                    if (strpos($v, 'Customer payment') !== false) {
                        $column_index['customer_price'] = abs($k);
                    }
                }
            } else {
                $msg = 'Importing data tidak berhasil! channel tidak ditemukan!';
                echo $this->template->alert_danger($msg);
                die;
            }
            if ($column_index['order_id'] == '') {
                $msg = 'Importing data tidak berhasil! header excel tidak sesuai!';
                echo $this->template->alert_danger($msg);
                die;
            }
            $column_index_data = array();
            foreach ($column_index as $k => $v) {
                if ($k != 'order_id') {
                    $column_index_data[$k] = $v;
                }
            }
            foreach ($sheet_data as $k => $v) {
                if ($k > 0) {
                    $dt = array();
                    $i = 0;

                    $order_id = $v[$column_index['order_id']];

                    $query = $this->mymodel->selectWithQuery("SELECT id 
                    FROM transaction WHERE order_id = '$order_id' AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                    $query = $query[0];

                    $dt = array();
                    $dt['id'] = $query['id'];
                    foreach ($column_index_data as $k2 => $v2) {
                        $dt[$k2] = strval(abs(doubleval($v[$v2])));
                    }
                    $dt['pencairan_status'] = 'Settlement';



                    if ($dt['id']) {
                        $dt['updated_at'] = DATE('Y-m-d H:i:s');
                        $this->db->update('transaction', $dt, array('id' => $dt['id']));
                    }
                }
            }

            $msg = 'Importing data berhasil!';
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = 'Importing data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }


    public function import()
    {

        $param = $_GET;

        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];

        if (empty($start_date)) {
            $start_date = DATE("Y-m-01");
            
        }
        if (empty($until_date)) {
            $until_date = DATE("Y-m-d");
        }
        $site = $_GET['site'];
        $qry = '';

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];


        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];


        $filename = 'ORDER.';
        if ($marketplace) {
            $filename .= $marketplace . '.';
        }
        $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

        $data['file_name'] = $filename;

        $data['param'] = $this->template->get_param();

        $this->load->view("transaction/import", $data);
    }

    public function import_check()
    {

        $data['title'] = 'Import Order - ' . $this->template->title();

        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        $this->load->library('upload');

        // Set upload configuration
        $config['upload_path'] = './assets/webfile/excel/';  // Ensure this directory exists and is writable
        $config['allowed_types'] = 'xls|xlsx';
        // $config['max_size'] = 2048;  // 2MB
        $config['encrypt_name'] = TRUE;  // To avoid file name conflicts

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('file')) {
            // Upload failed
            $error = $this->upload->display_errors();
            $msg = $error;
            echo $this->template->alert_danger($msg);
            die;
        } else {
            // Upload success
            $upload_data = $this->upload->data();
            $filepath = $upload_data['full_path'];

            // You can store the file information in the database if needed
            $data = array(
                'uploaded_fileinfo' => $upload_data,
                // other data to be saved in the database
            );

            // Load the file helper to interact with the uploaded file
            $this->load->helper('file');

            // Example of handling the uploaded file
            // $file_content = read_file($filepath);
            // Do something with the file content if needed

            // Display or process the uploaded file information
            // echo "File uploaded successfully: " . $filepath;

        }

        if ($data['uploaded_fileinfo']['file_path']) {
            $filepath = $data['uploaded_fileinfo']['file_path'] . $data['uploaded_fileinfo']['file_name'];


            $query = $this->mymodel->selectWithQuery("SELECT * FROM product
            -- ORDER BY brand ASC, sub_name ASC
            WHERE is_gift = 0 AND is_varian = 0
            ORDER BY id ASC
            ");

            $data['product'] = $query;


            $query = $this->mymodel->selectWithQuery("SELECT * FROM product
            -- ORDER BY brand ASC, sub_name ASC
            WHERE is_gift = 1 AND is_varian = 0
            ORDER BY id ASC
            ");

            $data['gift'] = $query;

            $data['header'] = array();

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
            }

            $header_2_gift = array();

            foreach ($data['gift'] as $k => $v) {
                $header_2_gift[] = strtoupper($v['sub_name']);
            }


            $header_3 = array(
                "JENIS PEMBAYARAN",
                "TANGGAL TF",
                "EKSPEDISI",
                "ONGKIR",
                "DISKON",
                "TOTAL BAYAR",
                "ORDER STATUS",
            );

            $header_1 = array(
                "ID",
                "KEBUTUHAN",
                "TANGGAL",
                "NO RESI",
                "BRAND",
                "KET",
                "KODE CS",
                "CB/CL",
                "NAMA",
                "NO HP / NO WA HARUS DIAWALI 62",
                "USERNAME",
                "ALAMAT LENGKAP (SAP MAKS. JAM 3)",
                "KEC",
                "KAB",
                "PROV",
                "PESANAN",
            );


            $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet  = $reader->load($filepath);
            $data['data']  = $spreadsheet->getActiveSheet()->toArray();
            $data['filepath'] = $filepath;
            $data['param'] = $_POST['param'];

            $data['header_1'] = $header_1;
            $data['header_2'] = $header_2;
            $data['header_2_gift'] = $header_2_gift;
            $data['header_3'] = $header_3;

            $data['template'] = $this->template;

            $data['content'] = $this->load->view("transaction/import_check", $data, true);
            $this->load->view("TemplateDashboard", $data);
        } else {
            $msg = 'Import data tidak berhasil! Pastikan file excel sudah sesuai dengan template!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function import_process()
    {

        // $msg = 'Fitur masih dalam proses pengembangan!';
        // echo $this->template->alert_danger($msg);
        // die;

        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        $dt = $_POST;

        $qry = '';
        $filepath = $dt['filepath'];
        $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet  = $reader->load($filepath);
        $sheet_data  = $spreadsheet->getActiveSheet()->toArray();

        if (empty($sheet_data)) {
            $msg = 'Importing data tidak berhasil! Pastikan tipe file adalah xlsx!';
            echo $this->template->alert_danger($msg);
            die;
        }

        if (1 == 1) {

            $query = $this->mymodel->selectWithQuery("SELECT * FROM product
            WHERE is_gift = 0 AND is_varian = 0
            ORDER BY id ASC
            ");

            $data['product'] = $query;


            $query = $this->mymodel->selectWithQuery("SELECT * FROM product
            WHERE is_gift = 1 AND is_varian = 0
            ORDER BY id ASC
            ");

            $data['gift'] = $query;


            $marketplace = $_POST['marketplace'];
            $marketplace = "MANUAL";

            if ($marketplace == "MANUAL") {
                $body_1 = array(
                    "id",
                    "c_type",
                    "date",
                    "awb_number",
                    "brand",
                    "marketplace",
                    "cs",
                    "cb_cl",
                    "customer_text",
                    "phone",
                    "c_username",
                    "address",
                    "subdistrict_text",
                    "city_text",
                    "province_text",
                    "pesanan",

                );

                $body_2 = array();

                foreach ($data['product'] as $k => $v) {
                    $body_2[] = $v['id'];
                }


                $body_2_gift = array();

                foreach ($data['gift'] as $k => $v) {
                    $body_2_gift[] = $v['id'];
                }

                $body_3  = array(
                    "payment_type",
                    "pay_at",
                    "shipping",
                    "ongkir",
                    "diskon_penjual",
                    "customer_price",
                    "order_status",
                );
            } else if ($marketplace == "MARKETPLACE") {
                die;
            } else {
                die;
            }

            foreach ($sheet_data as $k => $v) {
                if ($k > 0 && $v['2']) {
                    $dt = array();
                    $i = 0;
                    foreach ($body_1 as $k2 => $v2) {
                        $dt[$v2] = strval($v[$i]);
                        $i++;
                    }

                    foreach ($body_3 as $k2 => $v2) {
                        $dt[$v2] = strval($v[$i]);
                        $i++;
                    }

                    if ($dt['c_type'] == "AFFILIATE" || $dt['c_type'] == "ENDORSE" || $dt['c_type'] == "FREE") {
                        $dt['is_endorse'] = 1;
                    } else {
                        $dt['is_endorse'] = 0;
                    }

                    $json = array();
                    $pesanan = '';
                    $price_total = 0;
                    foreach ($body_2 as $k2 => $v2) {
                        if ($v[$i] > 0) {
                            $id_product = $v2;
                            $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id_product' ");

                            $product = $query[0];
                            if ($dt['c_type'] == "Pelanggan") {
                                $product['price_normal'] = $product['price_normal'];
                            } else if ($dt['c_type'] == "Distributor") {
                                $product['price_normal'] = $product['price_distributor'];
                            } else if ($dt['c_type'] == "Reseller") {
                                $product['price_normal'] = $product['price_reseller'];
                            } else {
                                $product['price_normal'] = $product['price_normal'];
                            }
                            $json[$id_product]['product']      = $product['id'];
                            $json[$id_product]['sku']          = $product['sku'];
                            $json[$id_product]['product_text'] = $product['name'];
                            $json[$id_product]['brand']        = $product['brand'];
                            $json[$id_product]['brand_text']   = $product['brand_text'];
                            $json[$id_product]['qty']          = $v[$i];
                            $json[$id_product]['hpp']          = $product['price_buy'];
                            $json[$id_product]['price']        = $dt['is_endorse'] == 1 ? 0 : $product['price_normal'];
                            $json[$id_product]['price_total']  = $dt['is_endorse'] == 1 ? 0 : (doubleval($v[$i]) * doubleval($product['price_normal']));
                            $pesanan .= $v[$i] . ' ' . $product['name'];
                            $pesanan .= '<br>';
                            $price_total += $json[$id_product]['price_total'];
                        }
                        $i++;
                    }

                    foreach ($body_2_gift as $k2 => $v2) {
                        if ($v[$i] > 0) {
                            $id_product = $v2;
                            $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id_product' ");

                            $product = $query[0];

                            if ($dt['c_type'] == "Pelanggan") {
                                $product['price_normal'] = $product['price_normal'];
                            } else if ($dt['c_type'] == "Distributor") {
                                $product['price_normal'] = $product['price_distributor'];
                            } else if ($dt['c_type'] == "Reseller") {
                                $product['price_normal'] = $product['price_reseller'];
                            } else {
                                $product['price_normal'] = $product['price_normal'];
                            }
                            $json[$id_product]['product'] = $product['id'];
                            $json[$id_product]['sku'] = $product['sku'];
                            $json[$id_product]['product_text'] = $product['name'];
                            $json[$id_product]['brand'] = $product['brand'];
                            $json[$id_product]['brand_text'] = $product['brand_text'];
                            $json[$id_product]['qty'] = $v[$i];
                            $json[$id_product]['hpp'] = $product['price_buy'];
                            $json[$id_product]['price'] = $product['price_normal'];
                            $json[$id_product]['price_total'] = doubleval($v[$i]) * doubleval($product['price_normal']);
                            $pesanan .= $v[$i] . ' ' . $product['name'];
                            $pesanan .= '<br>';
                            $price_total += $json[$id_product]['price_total'];
                        }
                        $i++;
                    }

                    $brand_selected = "MG";
                    $arr_brand = array();
                    foreach ($json as $k4 => $v4) {
                        $arr_brand[$v4['brand']] += 1;
                    }

                    $max = 0;
                    foreach ($arr_brand as $k => $v) {
                        if ($v >= $max) {
                            $max = $v;
                            $brand_selected = $k;
                        }
                    }

                    $dt['brand'] = $brand_selected;

                    $js = array();
                    $i = 0;
                    foreach ($json as $k4 => $v4) {
                        $js[$i]['qty'] += $v4['qty'];
                        $js[$i]['item_sku'] = $v4['sku'];
                        $js[$i]['item_name'] = $v4['product_text'];
                        $i++;
                    }

                    $dt['pesanan'] = json_encode($js, true);
                    $dt['json'] = json_encode($json, true);
                    $full_name = $dt['customer_text'];
                    $username = $dt['c_username'];
                    $phone = $dt['phone'];


                    $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));
                    if ($dt['date'] == '1970-01-01 07:00:00') {
                        $dt['date'] = DATE("Y-m-d 23:00:00");
                    }

                    $dt['kebutuhan'] = $dt['c_type'] ?? '';
                    $dt['c_type'] = ucfirst(strtolower((string)($dt['c_type'] ?? '')));

                    if ($dt['kebutuhan']) {
                        $dt['customer_price'] = 0;
                        $price_total = 0;
                    }

                    $dt['customer_price'] = $this->template->separator_number_only($dt['customer_price']);
                    $dt['ongkir'] = $this->template->separator_number_only($dt['ongkir']);

                    $dt['order_id'] = str_replace(' ', '', $dt['order_id']);
                    $dt['omset_kotor'] = $price_total;

                    $dt['diskon_penjual'] = abs(doubleval($dt['diskon_penjual']));
                    $dt['discount_nominal'] = abs(doubleval($dt['diskon_penjual']));
                    $dt['discount_type'] = "Nominal";

                    $dt['dibayar'] = $dt['customer_price'];

                    $dt['omset_bersih'] = $dt['customer_price'];

                    $dt['dana_pencairan'] = $dt['customer_price'];

                    $dt['pesanan_count'] = intval(count($js));

                    if ($dt['pay_at']) {
                        $dt['payment_status'] = "Paid";
                    } else {
                        $dt['payment_status'] = "Unpaid";
                    }

                    if ($dt['c_type'] == "Affiliate" || $dt['c_type'] == "Endorse" || $dt['c_type'] == "Free") {
                        $dt['pencairan_at'] = '';
                        $dt['pencairan_status'] = '';
                        $dt['is_disbursement'] = 0;
                        $dt['pay_at'] = "";
                        $dt['payment_type'] = '';
                        $dt['payment_status'] = '';
                        $dt['dana_pencairan'] = 0 - ($dt['ongkir']);
                    }

                    if (!in_array($dt['payment_type'], array("TF", "COD", ""))) {
                        $dt['bank'] = $dt['payment_type'];
                        $dt['payment_type'] = "TF";
                    }

                    if ($dt['payment_type'] == "TF" && $dt['payment_status'] == "Paid") {
                        $dt['pencairan_status'] = "Settlement";
                        $dt['pencairan_at'] = DATE("Y-m-d H:i:s");
                        $dt['is_disbursement'] = 1;
                        $dt['pay_at'] = DATE("Y-m-d H:i:s");
                        $dt['payment_status'] = "Paid";
                    }

                    if ($dt['id']) {
                        unset($dt['order_id']);
                        $dt['updated_at'] = DATE('Y-m-d H:i:s');
                        $this->db->update('transaction', $dt, array('id' => $dt['id']));
                    } else {
                        for (;;) {
                            $order_id = "BHS" . DATE("Ymdhis") . $this->template->generateNumber(3);
                            $check = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE order_id = '$order_id' LIMIT 1");
                            if (empty($check)) {
                                $dt['order_id'] = $order_id;
                                break;
                            }
                        }
                        $dt['is_manual'] = 1;
                        $dt['created_at'] = DATE("Y-m-d H:i:s");
                        $dt['created_by'] = strval($user['id']);
                        $dt['type'] = 'Out';
                        $dt['type_sub'] = 'POS';
                        $dt['status'] = 'ENABLE';

                        unset($dt['id']);
                        $this->db->insert('transaction', $dt);
                        $dt['id'] = $this->db->insert_id();
                    }


                    $id = $dt['id'];

                    if ($id) {
                        $this->db->delete('stock', array('id_trx' => $id));

                        $this->db->delete('stock_product_3rd', array('id_trx' => $id));
                    }

                    $json = json_decode($dt['json'], true);

                    foreach ($json as $k2 => $v2) {
                        $dts = array();
                        $dts['brand'] = strval($v2['brand']);
                        $dts['id_trx'] = strval($id);
                        $dts['qty'] = 0 - abs($v2['qty']);
                        $dts['qty_out'] = 0;
                        $dts['qty_out_pos'] = abs($v2['qty']);
                        $dts['qty_in'] = '0';
                        $dts['price'] = $v2['price'];
                        $dts['hpp'] = doubleval($v2['hpp']);
                        $dts['price_total'] = $v2['price_total'];
                        $dts['product'] = $v2['product'];
                        $dts['product_text'] = $v2['product_text'];
                        $dts['sku'] = $v2['sku'];
                        $dts['order_id'] = $dt['order_id'];
                        $dts['type'] = "Out";
                        $dts['type_sub'] = "POS";
                        $dts['created_at'] = DATE("Y-m-d H:i:s");
                        $dts['date'] = $dt['date'];
                        $dts['created_by'] = strval($user['id']);
                        $dts['status'] = "Aktif";
                        $this->db->insert('stock', $dts);
                        if (in_array($dt['order_status'], array('RETURN'))) {
                            $dts['type'] = "In";
                            $dts['qty'] =  abs($v2['qty']);
                            $dts['qty_out'] = '0';
                            $dts['qty_out_pos'] = '0';
                            $dts['qty_in'] = '0';
                            $dts['qty_in_pos'] =  abs($v2['qty']);
                            $this->db->insert('stock', $dts);
                        }
                    }


                }
            }

            foreach ($data['product'] as $k => $v) {
                $id_product = $v['id'];
                $this->update_stock($id_product);
            }

            $msg = 'Importing data berhasil!';
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = 'Importing data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function import_resi_process()
    {

        // $msg = 'Fitur masih dalam proses pengembangan!';
        // echo $this->template->alert_danger($msg);
        // die;

        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        $dt = $_POST;

        // if ($type != 'xlsx') {
        //     $msg = 'Importing data tidak berhasil! Pastikan tipe file adalah xlsx!';
        //     echo $this->template->alert_danger($msg);
        //     die;
        // }

        $this->load->library('upload');

        // Set upload configuration
        $config['upload_path'] = './assets/webfile/excel/';  // Ensure this directory exists and is writable
        $config['allowed_types'] = 'xls|xlsx';
        // $config['max_size'] = 2048;  // 2MB
        $config['encrypt_name'] = TRUE;  // To avoid file name conflicts

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('file')) {
            // Upload failed
            $error = $this->upload->display_errors();
            $msg = $error;
            echo $this->template->alert_danger($msg);
            die;
        } else {
            // Upload success
            $upload_data = $this->upload->data();
            $filepath = $upload_data['full_path'];

            // You can store the file information in the database if needed
            $data = array(
                'uploaded_fileinfo' => $upload_data,
                // other data to be saved in the database
            );

            // Load the file helper to interact with the uploaded file
            $this->load->helper('file');

            // Example of handling the uploaded file
            // $file_content = read_file($filepath);
            // Do something with the file content if needed

            // Display or process the uploaded file information
            // echo "File uploaded successfully: " . $filepath;

        }

        if ($data['uploaded_fileinfo']['file_path']) {

            $qry = '';

            $filepath = $data['uploaded_fileinfo']['file_path'] . $data['uploaded_fileinfo']['file_name'];


            $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet  = $reader->load($filepath);
            $sheet_data  = $spreadsheet->getActiveSheet()->toArray();

            $body_1 = array(
                "order_id",
                "awb_number",

            );


            foreach ($sheet_data as $k => $v) {
                if ($k > 0 && $v['1']) {
                    $dt = array();
                    $i = 0;

                    $dt['order_id'] = $v['0'];
                    $dt['order_id'] = str_replace(' ', '', $dt['order_id']);
                    $dt['awb_number'] = $v['1'];

                    $dt['updated_at'] = DATE('Y-m-d H:i:s');

                    $this->db->update('transaction', $dt, array('order_id' => $dt['order_id']));
                    // echo "UPDATE transaction SET awb_number = '".$dt['awb_number']."' WHERE order_id = '".$dt['order_id']."';";
                    // echo "<br>";
                }
            }
            $msg = 'Importing data berhasil!';
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = 'Importing data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function download_template()
    {


        $start_date = $_GET['start_date'];
        $start_date = $_GET['start_date'];
        $p = $_GET['p'];

        if (empty($start_date)) {
            $start_date = DATE("Y-m-01");
            
        }
        if (empty($until_date)) {
            $until_date = DATE("Y-m-d");
        }

        $site = $_GET['site'];
        $qry = '';

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];


        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $keyword_category = $_GET['keyword_category'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];


        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        if ($id) {
            $qry .= " AND customer = '$id' ";
        }

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }


        $query = $this->mymodel->selectWithQuery("SELECT id,code as opt FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;


        if ($brand == "LAINNYA") {
            $ids = "";
            foreach ($data['brands'] as $k => $v) {
                $ids .= "'" . $v['opt'] . "',";
            }
            $ids = substr($ids, 0, -1);
            $qry .= " AND brand NOT IN ($ids) ";
        } else {
            if ($brand) {
                $qry .= " AND brand = '$brand' ";
            }
        }


        $ekspedisi = $_GET['ekspedisi'];
        if ($ekspedisi) {
            $qry .= " AND shipping = '$ekspedisi' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND cs = '$cs' ";
        }

        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND dana_pencairan > 0 AND is_disbursement > 0 ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else {
                $qry .= " AND order_status = '$order_status' ";
            }
        }

        $pencairan = $_GET['pencairan'];
        if ($pencairan) {
            if ($pencairan == "Sudah Pencairan") {
                $qry .= " AND dana_pencairan > 0";
            } else if ($pencairan == "Belum Pencairan") {
                $qry .= " AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') AND c_type NOT IN ('Affiliate','Endorse','Free') AND dana_pencairan = 0 AND is_disbursement = 0 ";
            }
        }

        $payment_type = $_GET['payment_type'];
        if ($payment_type) {
            $qry .= " AND payment_type = '$payment_type' ";
        }

        if ($keyword) {
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND c_username LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Pelanggan") {
                $qry .= " AND customer_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Pelanggan") {
                $qry .= " AND phone LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Resi") {
                $qry .= " AND awb_number LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Produk") {
                $qry .= " AND `json` LIKE '%$keyword%' ";
            }
        }

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        } else if ($order_type == "Belum Dikonfigurasi") {
            $qry .= " AND is_configurated = 0 ";
        }

        if ($p == "MANUAL") {
            $qry .= " AND is_manual = '1' ";
        } else if ($p == "MARKETPLACE") {
            $qry .= " AND is_manual = '0' ";
        }


        if ($p == "MANUAL") {
            $data['data'][] = array();
            $data['data'][] = array();
            $data['data'][] = array();
        } else {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction
            WHERE $qry 
            -- AND order_status NOT IN ('CANCELLED','IN_CANCEL') 
            AND type_sub = 'POS' 
            ORDER BY date DESC, id DESC
            ");
            $data['data'] = $query;
        }

        $this->spreadsheet = new Spreadsheet();

        $this->spreadsheet->getProperties()
            ->setCreator('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setLastModifiedBy('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setTitle('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setSubject('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setDescription('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setKeywords('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date));



        $style_col = array(
            'font' => array('bold' => true),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            ),
            'fill' => array(
                'type' => Fill::FILL_SOLID,
                'color' => array('rgb' => 'aeb5bc')
            ),
        );

        $style_row = array(
            'alignment' => array(
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            )
        );


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product
        -- ORDER BY brand ASC, sub_name ASC
        WHERE is_gift = 0 AND is_varian = 0
        ORDER BY id ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product
        -- ORDER BY brand ASC, sub_name ASC
        WHERE is_gift = 1 AND is_varian = 0
        ORDER BY id ASC
        ");

        $data['gift'] = $query;

        if ($p == "MANUAL") {
            $data['header'] = array();

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
            }

            foreach ($data['gift'] as $k => $v) {
                $header_2_gift[] = strtoupper($v['sub_name']);
            }



            $header_3 = array(
                "JENIS PEMBAYARAN",
                "TANGGAL TF",
                "EKSPEDISI",
                "ONGKIR",
                "DISKON",
                "TOTAL BAYAR",
                "ORDER STATUS",
            );

            // $data['header'] = array_merge($header_1, $header_2, $header_3);
            $header_1 = array(
                "ID",
                "KEBUTUHAN",
                "TANGGAL",
                "NO RESI",
                "BRAND",
                "KET",
                "KODE CS",
                "CB/CL",
                "NAMA",
                "NO HP / NO WA HARUS DIAWALI 62",
                "USERNAME",
                "ALAMAT LENGKAP (SAP MAKS. JAM 3)",
                "KEC",
                "KAB",
                "PROV",
                "PESANAN",
            );


            $body_1 = array(
                "id",
                "kebutuhan",
                "date",
                "awb_number",
                "brand",
                "marketplace",
                "cs",
                "cb_cl",
                "customer_text",
                "phone",
                "c_username",
                "address",
                "subdistrict_text",
                "city_text",
                "province_text",
                "pesanan",

            );

            $body_2 = array();

            foreach ($data['product'] as $k => $v) {
                $body_2[] = $v['id'];
            }

            $body_3  = array(
                "payment_type",
                "pay_at",
                "shipping",
                "ongkir",
                "diskon_penjual",
                "customer_price",
                "order_status",
            );

            $data['body'] = array_merge($body_1, $body_2, $body_3);



            $i = 0;
            foreach ($header_1 as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('dcdcdb');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_3 as $k => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('dcdcdb');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_2 as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('ffff00');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_2_gift as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('a5d870');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }


            $column = 2;

            $is_empty = false;

            foreach ($data['data'] as $k => $v) {
                $index = 1;

                foreach ($body_1 as $k2 => $v2) {

                    $v['id'] = '';
                    $v['date'] = DATE("Y-m-d H:i:s");
                    $v['cs'] = 'FIKRI';
                    $v['awb_number'] = 'SPXID047588387014';
                    $v['address'] = 'Jl. Diponegoro No. 61 Genteng Banyuwangi 68465';
                    $v['subdistrict_text'] = 'Genteng';
                    $v['city_text'] = 'Banyuwangi';
                    $v['province_text'] = 'Jawa Timur';
                    $v['payment_type'] = 'TF';
                    $v['pay_at'] = DATE("Y-m-d");
                    $v['cb_cl'] = 'CB';
                    $v['order_status'] = 'PROCESSED';
                    $v['brand'] = 'POME';
                    $v['shipping'] = 'SPX Standard';
                    $v['marketplace'] = 'WA';
                    $v['customer_text'] = 'RIZAL';
                    $v['phone'] = '6282244243948';
                    $v['c_username'] = 'RIZAL';
                    $v['json'] = '{"18":{"product":"18","sku":"LV","product_text":"Lacto-V","brand":"MG","brand_text":"","qty":1,"hpp":"38000","price":"115000","price_total":115000},"3":{"product":"3","sku":"1-MG","product_text":"MISCELLA-G","brand":"MG","brand_text":"Miscella G","qty":2,"hpp":"110000","price":"250000","price_total":500000},"17":{"product":"17","sku":"MV","product_text":"Miscella-V","brand":"MG","brand_text":"Miscella G","qty":3,"hpp":"22000","price":"56000","price_total":168000}}';
                    $v['pesanan'] = '[{"qty":1,"item_sku":"LV","item_name":"Lacto-V","price_total":115000},{"qty":2,"item_sku":"1-MG","item_name":"MISCELLA-G","price_total":500000},{"qty":3,"item_sku":"MV","item_name":"Miscella-V","price_total":0}]';
                    $v['ongkir'] = "0";
                    $v['customer_price'] = "783000";

                    if ($v2 == "order_id") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "phone") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "pesanan") {
                        $list = json_decode($v[$v2], true);
                        $v[$v2] = '';
                        foreach ($list as $kk => $vv) {
                            $v[$v2] .= $vv['qty'] . "x " . $vv['item_name'] . "
";
                        }
                    }
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                foreach ($body_3 as $k2 => $v2) {
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                $json = json_decode($v['json'], true);

                foreach ($body_2 as $k2 => $v2) {
                    $val = $json[$v2]['qty'];
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $val);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                foreach ($data['header'] as $k2 => $v2) {
                    $code = $this->template->get_name_from_number($k2 + 1) . $column;
                    $this->spreadsheet
                        ->getActiveSheet()
                        ->getStyle($code)
                        ->getBorders()
                        ->getOutline()
                        ->setBorderStyle(Border::BORDER_THIN);
                }
                $column++;
            }

            $sheet = $this->spreadsheet->getActiveSheet();
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            $writer = new Xlsx($this->spreadsheet);
            $filename = 'TMP ORDER ML.';
            if ($marketplace) {
                $filename .= $marketplace . '.';
            }
            $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        } else if ($p == "MARKETPLACE") {
            $data['header'] = array();

            $header_1 = array(
                "ID",
                "TANGGAL",
                "ORDER ID",
                "BRAND",
                "KET",
                "KODE CS",
                "CB/CL",
                "NAMA",
                "NO HP",
                "USERNAME",
                "ALAMAT",
                "KAB",
                "PROV",
                "PESANAN",
            );

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
            }

            $header_3 = array(
                "OMSET KOTOR",
                "DISKON & VOUCHER PENJUAL",
                "BIAYA LAINNYA",
                "OMSET BERSIH",
                "MARKETPLACE FEE",
                "AFFILIATE FEE",
                "TOTAL PENCAIRAN DANA",
                "IS_CAIR",
                "RETURN",
                "JENIS PEMBAYARAN",
                "JUMLAH",
                "TANGGAL TF",
                "TANGGAL CEK",
                "ACC",
                "EKSPEDISI",
                "NO RESI",
                "ALAMAT",
                "PROV",
                "KAB",
                "KEC",
                "CATATAN",
                "STATUS ORDER",
                // "IS_MANUAL",
            );

            // $data['header'] = array_merge($header_1, $header_2, $header_3);


            $body_1 = array(
                "id",
                "date",
                "order_id",
                "brand",
                "marketplace",
                "cs",
                "cb_cl",
                "customer_text",
                "phone",
                "c_username",
                "address",
                "city_text",
                "province_text",
                "pesanan",

            );

            $body_2 = array();

            foreach ($data['product'] as $k => $v) {
                $body_2[] = $v['id'];
            }

            $body_3  = array(
                "omset_kotor",
                "diskon_penjual",
                "biaya_lainnya",
                "omset_bersih",
                "marketplace_fee",
                "komisi_afiliasi",
                "dana_pencairan",
                "is_disbursement",
                "return",
                "payment_type",
                "dibayar",
                "pay_at",
                "check_at",
                "acc",
                "shipping",
                "awb_number",
                "address",
                "province_text",
                "city_text",
                "subdistrict_text",
                "desc",
                "order_status",
                // "is_manual",
            );

            $data['body'] = array_merge($body_1, $body_2, $body_3);



            $i = 0;
            foreach ($header_1 as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('dcdcdb');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_2 as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('ffff00');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_3 as $k => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('dcdcdb');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            $column = 2;

            foreach ($data['data'] as $k => $v) {
                $index = 1;

                foreach ($body_1 as $k2 => $v2) {
                    if ($v2 == "order_id") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "phone") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "pesanan") {
                        $list = json_decode($v[$v2], true);
                        $v[$v2] = '';
                        foreach ($list as $kk => $vv) {
                            $v[$v2] .= $vv['qty'] . "x " . $vv['item_name'] . "
";
                        }
                    }
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                $json = json_decode($v['json'], true);

                foreach ($body_2 as $k2 => $v2) {
                    $val = $json[$v2]['qty'];
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $val);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }
                foreach ($body_3 as $k2 => $v2) {
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                foreach ($data['header'] as $k2 => $v2) {
                    $code = $this->template->get_name_from_number($k2 + 1) . $column;
                    $this->spreadsheet
                        ->getActiveSheet()
                        ->getStyle($code)
                        ->getBorders()
                        ->getOutline()
                        ->setBorderStyle(Border::BORDER_THIN);
                }
                $column++;
            }

            $sheet = $this->spreadsheet->getActiveSheet();
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            $writer = new Xlsx($this->spreadsheet);
            $filename = 'TMP ORDER MP.';
            if ($marketplace) {
                $filename .= $marketplace . '.';
            }
            $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        } else {
            echo 'Fitur masih dalam pengembangan!';
            die;
            $data['header'] = array();

            $header_1 = array(
                "ID",
                "TANGGAL",
                "ORDER ID",
                "BRAND",
                "KET",
                "KODE CS",
                "CB/CL",
                "NAMA",
                "NO HP",
                "USERNAME",
                "ALAMAT",
                "KAB",
                "PROV",
                "PESANAN",
            );

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
            }

            $header_3 = array(
                "OMSET KOTOR",
                "DISKON & VOUCHER PENJUAL",
                "BIAYA LAINNYA",
                "OMSET BERSIH",
                "MARKETPLACE FEE",
                "AFFILIATE FEE",
                "TOTAL PENCAIRAN DANA",
                "IS_CAIR",
                "RETURN",
                "JENIS PEMBAYARAN",
                "JUMLAH",
                "TANGGAL TF",
                "TANGGAL CEK",
                "ACC",
                "EKSPEDISI",
                "NO RESI",
                "ALAMAT",
                "PROV",
                "KAB",
                "KEC",
                "CATATAN",
                "STATUS ORDER",
                // "IS_MANUAL",
            );

            // $data['header'] = array_merge($header_1, $header_2, $header_3);


            $body_1 = array(
                "id",
                "date",
                "order_id",
                "brand",
                "marketplace",
                "cs",
                "cb_cl",
                "customer_text",
                "phone",
                "c_username",
                "address",
                "city_text",
                "province_text",
                "pesanan",

            );

            $body_2 = array();

            foreach ($data['product'] as $k => $v) {
                $body_2[] = $v['id'];
            }

            $body_3  = array(
                "omset_kotor",
                "diskon_penjual",
                "biaya_lainnya",
                "omset_bersih",
                "marketplace_fee",
                "komisi_afiliasi",
                "dana_pencairan",
                "is_disbursement",
                "return",
                "payment_type",
                "dibayar",
                "pay_at",
                "check_at",
                "acc",
                "shipping",
                "awb_number",
                "address",
                "province_text",
                "city_text",
                "subdistrict_text",
                "desc",
                "order_status",
                // "is_manual",
            );

            $data['body'] = array_merge($body_1, $body_2, $body_3);



            $i = 0;
            foreach ($header_1 as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('dcdcdb');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_2 as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('ffff00');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_3 as $k => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('dcdcdb');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            $column = 2;

            foreach ($data['data'] as $k => $v) {
                $index = 1;

                foreach ($body_1 as $k2 => $v2) {
                    if ($v2 == "order_id") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "phone") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "pesanan") {
                        $list = json_decode($v[$v2], true);
                        $v[$v2] = '';
                        foreach ($list as $kk => $vv) {
                            $v[$v2] .= $vv['qty'] . "x " . $vv['item_name'] . "
";
                        }
                    }
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                $json = json_decode($v['json'], true);

                foreach ($body_2 as $k2 => $v2) {
                    $val = $json[$v2]['qty'];
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $val);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }
                foreach ($body_3 as $k2 => $v2) {
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                foreach ($data['header'] as $k2 => $v2) {
                    $code = $this->template->get_name_from_number($k2 + 1) . $column;
                    $this->spreadsheet
                        ->getActiveSheet()
                        ->getStyle($code)
                        ->getBorders()
                        ->getOutline()
                        ->setBorderStyle(Border::BORDER_THIN);
                }
                $column++;
            }

            $sheet = $this->spreadsheet->getActiveSheet();
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            $writer = new Xlsx($this->spreadsheet);
            $filename = 'ORDER.';
            if ($marketplace) {
                $filename .= $marketplace . '.';
            }
            $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        }
    }

    function download_process()
    {

        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];

        $datediff = strtotime($until_date) - strtotime($start_date);

        $days = round($datediff / (60 * 60 * 24));

        if ($start_date == "" || $until_date == "") {
            $msg = "Buat file order tidak berhasil. Buat file order hanya bisa maksimal 3 hari!";
            echo $this->template->alert_danger($msg);
            die;
        } else if ($days >= 0 && $days <= 2) {
            // continue...
        } else {
            $msg = "Buat file order tidak berhasil. Buat file order hanya bisa maksimal 3 hari!";
            echo $this->template->alert_danger($msg);
            die;
        }


        $last_data = $this->mymodel->selectWithQuery("SELECT created_at
        FROM download_file
        ORDER BY id DESC
        LIMIT 1");
        $last_data = $last_data[0];
        if ($last_data) {
            $diff =  strtotime(DATE("Y-m-d H:i:s")) - strtotime($last_data['created_at']);
            if ($diff <= 60) {
                $msg = "Buat file order tidak berhasil. Buat file bisa dilakukan " . (61 - $diff) . " detik lagi!";
                echo $this->template->alert_danger($msg);
                die;
            }
        }

        $param = $this->template->get_param();
        $url = base_url() . '/api/marketplace/order/download' . $param;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 1,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: ci_session=sn9ulsif4722g1tahm420n9jspeihuck'
            ),
        ));

        $response = curl_exec($curl);


        $msg = "Buat file order berhasil. Silahkan tunggu hingga proses selesai!";
        echo $this->template->alert_success($msg);
        die;
    }

    function download_file()
    {
        $data['param'] = $this->template->get_param();
        $data['data'] = $this->mymodel->selectWithQuery("SELECT *
    FROM download_file
    ORDER BY id DESC
    LIMIT 10
    ");
        $this->load->view("transaction/download_file", $data);
    }

    function download_ajax()
    {
        $today = DATE('Y-m-d');
        $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));
        $data['data'] = $this->mymodel->selectWithQuery("SELECT *
    FROM download_file
    WHERE updated_at != '' OR (updated_at = '' AND DATE(created_at) >= '$yesterday')
    ORDER BY id DESC
    LIMIT 10
    ");
        $html = '';
        foreach ($data['data'] as $k => $v) {
            $btn = '<div style="margin-top:10px;padding-right:30px"><h4><i class="fa fa-circle-o-notch fa-spin"></i></h4></div>';
            $file_path = $v['file'];
            if (file_exists($file_path)) {
                $file_path = base_url() . '/assets/webfile/excel/' . $v['title'];
                $btn = '<a href="' . $file_path . '" class="btn btn-primary">Download</a>';
            }
            $html .= '<tr>
        <td class="text-start td-breakline" style="padding-left:0px!important;width:50%!important">' . $v['title'] . '</td>
        <td class="text-start" style="vertical-align:middle">' . DATE("d/m/Y H:i", strtotime($v['created_at'])) . '</td>
        <td class="text-end" style="padding-right:0px!important" id="btn-download-' . $v['id'] . '">
        <h4 class="fw-500 mb-1 text-end" id="summary-order-1" style="vertical-align:middle">
        ' . $btn . '
        </td>
    </tr>';
        }
        $html = '<div class="table-responsive">
    <table class="table table-bordered">
    ' . $html . '
        </table>
    </div>	';
        $dt['html'] = $html;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dt, true);
        die;
    }

    function tracking()
    {
        $data['title'] = 'Track Order - ' . $this->template->title();
        $id = $_GET['id'];
        $order_id = $_GET['order_id'];
        $package_number = $_GET['package_number'];
        $marketplace = $_GET['marketplace'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT *
        FROM transaction 
        WHERE id = '$id' AND type_sub = 'POS'");
        $data['data'] = $data['data'][0];
        if (empty($data['data'])) {
            redirect(base_url() . 'transaction');
        }

        $shipping = $data['data']['shipping'];
        $shipping = $this->mymodel->selectWithQuery("SELECT img FROM shipping WHERE name = '$shipping'");
        $data['shipping'] = $shipping[0];

        $data['param'] = $this->template->get_param();
        $data['content'] = $this->load->view('transaction/tracking', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function download_resi()
    {

        $start_date = $_GET['start_date'];
        $start_date = $_GET['start_date'];
        $p = $_GET['p'];

        if (empty($start_date)) {
            $start_date = DATE("Y-m-01");
            
        }
        if (empty($until_date)) {
            $until_date = DATE("Y-m-d");
        }

        $site = $_GET['site'];
        $qry = '';

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];


        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $keyword_category = $_GET['keyword_category'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];


        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        $this->spreadsheet = new Spreadsheet();

        $this->spreadsheet->getProperties()
            ->setCreator('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setLastModifiedBy('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setTitle('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setSubject('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setDescription('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setKeywords('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date));



        $style_col = array(
            'font' => array('bold' => true),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            ),
            'fill' => array(
                'type' => Fill::FILL_SOLID,
                'color' => array('rgb' => 'aeb5bc')
            ),
        );

        $style_row = array(
            'alignment' => array(
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            )
        );


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
        ORDER BY brand ASC, sub_name ASC
        ");

        $data['product'] = $query;


        $data['header'] = array();

        $header_1 = array(
            "ORDER ID",
            "NO RESI",
        );

        $i = 0;
        foreach ($header_1 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $this->spreadsheet
                ->getActiveSheet()
                ->getStyle($code)
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN);
            $i++;
        }

        $body_1 = array(
            "order_id",
            "awb_number",

        );

        $column = 2;

        $data['data'][] = array();
        $data['data'][] = array();
        $data['data'][] = array();

        foreach ($data['data'] as $k => $v) {
            $index = 1;

            foreach ($body_1 as $k2 => $v2) {

                $v['order_id'] = 'BHS20240430081453219';
                $v['awb_number'] = 'SPXID047588387014';

                if ($v2 == "order_id") {
                    $v[$v2] = " " . $v[$v2];
                }

                $index_alpha = $this->template->get_name_from_number($index);
                $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                $this->spreadsheet->getActiveSheet()
                    ->getStyle($index_alpha . $column)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $index++;
            }


            foreach ($data['header'] as $k2 => $v2) {
                $code = $this->template->get_name_from_number($k2 + 1) . $column;
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
            $column++;
        }

        $sheet = $this->spreadsheet->getActiveSheet();
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $writer = new Xlsx($this->spreadsheet);
        $filename = 'TMP RESI.';
        if ($marketplace) {
            $filename .= $marketplace . '.';
        }
        $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
    }



    function tracking_ajax()
    {
        // header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;


        $url = $this->template->endpoint_url() . 'api/marketplace/order/tracking?order_id=' . $dt['order_id'] . '&marketplace=' . $dt['marketplace'];


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: ci_session=ji2dpajlqasgk8200eroqtjrri48iugl'
            ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);

        $text = "";
        if ($response['data']) {
            foreach ($response['data'] as $k => $v) {
                $text .= '
                    
                <div class="timeline-item">
                <div class="timeline-media">
                </div>
                <div class="timeline-content">
                  
                <div class="row mb-2">
                <div class="col-md-2">
                <p class="mb-1">' . $v['update_time'] . '</p>
                </div>
                <div class="col-md-12">
                <p class="mb-1">' . strtoupper($v['detail_type']) . '</p>
                <p class="mb-1">' . $v['title'] . '</p>
                <p class="mb-1">' . $v['description'] . '</p>
                <p class="mb-1">' . $v['datetime'] . '</p>
                </div>
            </div>
                </div>
             </div>
                    ';
            }
        } else {
            $text = '<p class="mb-3">' . $response['msg'] . '</p>';
        }




        $html['html'] = $text;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }

    function objKeySort($obj)
    {
        $newKey = array_keys($obj);
        sort($newKey);
        $newObj = [];
        foreach ($newKey as $key) {
            $newObj[$key] = $obj[$key];
        }
        return $newObj;
    }

    function getEnvVar($k)
    {
        $v = $_ENV[$k] ?? null;
        if ($v !== null) {
            return $v;
        }
        $v = $_SERVER[$k] ?? null;
        if ($v !== null) {
            return $v;
        }
        return null;
    }

    function calSign($dt)
    {
        $secret = $dt['secret'];
        $ts = $dt['timest'];
        $queryParam = $dt['get'];
        $param = [];
        foreach ($queryParam as $key => $value) {
            if ($key == "timestamp") {
                $v = $ts;
            } else {
                $v = $value;
                if ($v == null || $v == "{{" . $key . "}}") {
                    $v = $this->getEnvVar($key);
                }
            }
            $param[$key] = $v;
        }

        unset($param["sign"]);
        unset($param["access_token"]);
        $sortedObj = $this->objKeySort($param);
        $path = parse_url($dt['url'], PHP_URL_PATH);
        $signstring = $secret . $path;
        foreach ($sortedObj as $key => $value) {
            $signstring .= $key . $value;
        }
        $signstring .= $secret;
        $sign = hash_hmac("sha256", $signstring, $secret);
        return $sign;
    }
}
