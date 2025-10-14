<?php

// PhpSpreadsheet autoload commented out to prevent PHP version conflicts
// Only load when specifically needed for Excel operations
// require FCPATH . 'vendor/autoload.php';

// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Style;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use PhpOffice\PhpSpreadsheet\Style\Color;

defined('BASEPATH') or exit('No direct script access allowed');
class Transaction_item extends CI_Controller
{

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
        // echo $response;die;
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
        // Load cache driver with APC adapter and file backup
        $this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'file'));
        
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

        $brand = $_GET['brand'];
        $keyword = $_GET['keyword'];

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $data['title'] = 'Order Item - ' . $this->template->title();


        // Create cache key for products
        $product_cache_key = 'transaction_item_products_active';
        $query = $this->cache->get($product_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0
            ORDER BY sku ASC
            ");
            $this->cache->save($product_cache_key, $query, 30); // Cache for 30 seconds
        }
        $data['product'] = $query;

        // Create cache key for CS users
        $cs_cache_key = 'transaction_item_cs_users';
        $query = $this->cache->get($cs_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM user
            WHERE role IN ('3')
            ORDER BY full_name ASC
            ");
            $this->cache->save($cs_cache_key, $query, 30); // Cache for 30 seconds
        }
        $data['cs'] = $query;

        // Create cache key for shipping
        $shipping_cache_key = 'transaction_item_shipping';
        $query = $this->cache->get($shipping_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");
            $this->cache->save($shipping_cache_key, $query, 30); // Cache for 30 seconds
        }
        $data['shipping'] = $query;

        // Create cache key for marketplace
        $marketplace_cache_key = 'transaction_item_marketplace';
        $query = $this->cache->get($marketplace_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
            $this->cache->save($marketplace_cache_key, $query, 30); // Cache for 30 seconds
        }
        $data['marketplace'] = $query;

        // Create cache key for brands
        $brands_cache_key = 'transaction_item_brands_enabled';
        $query = $this->cache->get($brands_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT id,code as opt FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
            $this->cache->save($brands_cache_key, $query, 30); // Cache for 30 seconds
        }
        $data['brands'] = $query;

        // Create cache key for store
        $store_cache_key = 'transaction_item_store_active';
        $store_data = $this->cache->get($store_cache_key);
        if (!$store_data) {
            $store_data = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace FROM marketplace_config WHERE status = 'Aktif' ORDER BY marketplace DESC, shop_name ASC");
            $this->cache->save($store_cache_key, $store_data, 30); // Cache for 30 seconds
        }
        $data['store'] = $store_data;

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        $order_id = $_GET['order_id'];
        if ($order_id) {
            $qry .= " AND order_id = '$order_id' ";
        }


        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }



        if ($brand) {
            $qry .= " AND brand = '$brand' ";
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

        $type = $_GET['type'];
        if ($type) {
            $qry .= " AND type = '$type' ";
        }

        $type_sub = $_GET['type_sub'];
        if ($type_sub) {
            $qry .= " AND type_sub = '$type_sub' ";
        }


        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') ";
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
                $qry .= " AND dana_pencairan = 0";
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
                $qry .= " AND pesanan LIKE '%$keyword%' ";
            }
        }

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        }

        $shop_id = $_GET['shop_id'];
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }


        $query = $this->mymodel->selectWithQuery("SELECT sku as id, CONCAT(ANY_VALUE(parent_name),' - ',ANY_VALUE(name),' - ',sku) as opt FROM product_variant_3rd
        GROUP BY sku
        ORDER BY ANY_VALUE(parent_name) ASC,ANY_VALUE(name) ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM stock_product_3rd
        WHERE $qry AND type_sub = 'POS' ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/transaction-item?keyword=' . $_GET['keyword'] . '&brand=' . $_GET['brand'] . '&marketplace=' . $_GET['marketplace'] . '&cs=&start_date=' . $start_date . '&until_date=' . $until_date;

        $url = base_url() . '/transaction-item/' . $this->template->get_param();
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

        $data['content'] = $this->load->view('transaction_item/all', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function item()
    {
        // Load cache driver with APC adapter and file backup
        $this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'file'));
        
        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-d');
            $start_date = date('Y-m-01');
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];
        $keyword_category = $_GET['keyword_category'];

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        // Create cache key for CS users (reuse from index method)
        $cs_cache_key = 'transaction_item_cs_users';
        $query = $this->cache->get($cs_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM user
            WHERE role IN ('3')
            ORDER BY full_name ASC
            ");
            $this->cache->save($cs_cache_key, $query, 30); // Cache for 30 seconds
        }
        $data['cs'] = $query;

        // Create cache key for products (reuse from index method)
        $product_cache_key = 'transaction_item_products_active';
        $query = $this->cache->get($product_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0
            ORDER BY sku ASC
            ");
            $this->cache->save($product_cache_key, $query, 30); // Cache for 30 seconds
        }
        $data['product'] = $query;

        // Create cache key for shipping (reuse from index method)
        $shipping_cache_key = 'transaction_item_shipping';
        $query = $this->cache->get($shipping_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");
            $this->cache->save($shipping_cache_key, $query, 30); // Cache for 30 seconds
        }

        $data['shipping'] = $query;

        // Create cache key for marketplace (reuse from index method)
        $marketplace_cache_key = 'transaction_item_marketplace';
        $query = $this->cache->get($marketplace_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
            $this->cache->save($marketplace_cache_key, $query, 30); // Cache for 30 seconds
        }
        $data['marketplace'] = $query;

        // Create cache key for brands (different query than index method)
        $brands_cache_key = 'transaction_item_brands_all';
        $query = $this->cache->get($brands_cache_key);
        if (!$query) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");
            $this->cache->save($brands_cache_key, $query, 30); // Cache for 30 seconds
        }
        $data['brands'] = $query;

        $qry = "";

        $id_customer = $_GET['id_customer'];
        $p = $_GET['p'];
        if ($id_customer) {
            $qry = " customer = '$id_customer' ";
        } else {
            $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";
        }

        $order_id = $_GET['order_id'];
        if ($order_id) {
            $qry .= " AND order_id = '$order_id' ";
        }



        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }



        if ($brand) {
            $qry .= " AND brand = '$brand' ";
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

        $type = $_GET['type'];
        if ($type) {
            $qry .= " AND type = '$type' ";
        }

        $type_sub = $_GET['type_sub'];
        if ($type_sub) {
            $qry .= " AND type_sub = '$type_sub' ";
        }


        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') ";
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
                $qry .= " AND dana_pencairan = 0";
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
                $qry .= " AND pesanan LIKE '%$keyword%' ";
            }
        }

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        }

        $limit = 30;

        $current_page = $_GET['page'];

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $shop_id = $_GET['shop_id'];
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM stock_product_3rd
        WHERE $qry AND type_sub = 'POS' 
        ORDER BY date DESC, id DESC
        LIMIT $offset, $limit
        ");

        $data['data'] = $query;

        $data['start'] = $offset;
        $this->load->view('transaction_item/item', $data);
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
        FROM product
        ORDER BY name ASC");

        $data['cs'] = $this->mymodel->selectWithQuery("SELECT *
        FROM user
        WHERE role IN ('3')
        ORDER BY code ASC");

        $data['content'] = $this->load->view("transaction_item/edit", $data, true);
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

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id_product' ");

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
            $json[$id_product]['price'] = $product['price_normal'];
            $json[$id_product]['price_total'] = ($json[$id_product]['qty'] + 1) * $product['price_normal'];
        } else {
            $json[$id_product]['product'] = $dt['product'];
            $json[$id_product]['sku'] = $product['sku'];
            $json[$id_product]['product_text'] = $product['name'];
            $json[$id_product]['brand'] = $product['brand'];
            $json[$id_product]['brand_text'] = $product['brand_text'];
            $json[$id_product]['qty'] = 1;
            $json[$id_product]['price'] = $product['price_normal'];
            $json[$id_product]['price_total'] = 1 * $product['price_normal'];
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
						<td class="text-start pt-3" style="min-width:240px!important;     white-space: normal;text-overflow: ellipsis;">
                        ' . $v['sku'] . ' | ' . $v['product_text'] . '
						</td>
						<td class="text-end pt-3">
							' . $v['price'] . '
                            <input type="hidden" class="form-control  text-end m-0 i-price" value="' . $v['price'] . '" id="i-price-' . $k . '">
						</td>
						<td class="text-end" style="width:80px!important;padding-top:6px!important">
							<input autocomplete="off" type="text" data-id="' . $k . '" class="form-control text-end m-0 i-qty" value="' . $v['qty'] . '" id="i-qty-' . $k . '" style="height:35px;width:80px;display:unset;">
						</td>
						<td class="text-end pt-3">
							<span id="txt-price-total-' . $k . '">' . $v['price_total'] . '</span>
                            <input type="hidden" class="form-control  text-end m-0 i-price-total" value="' . $v['price_total'] . '" id="i-price-total-' . $k . '">
						</td>
                        <td style="min-width:40px!important">
                            <a href="#!" onclick="delete_cart_' . $k . '(' . $remove . ')" class="text-red"><i class="bi bi-trash text-icon"></i></a>
                        </td>
					</tr>
                    <script>
                    
                    function get_total_2_' . $k . '() {
                        var val = parseFloat($("#total_1").val()) - parseFloat($("#discount").val()) + parseFloat($("#shipping_fee").val());
                        $("#total_2").val(val);
                        get_grand_total();
                    }

                    function get_grand_total_' . $k . '() {
                        var val = parseFloat($("#total_2").val()) - parseFloat($("#marketplace_fee").val()) - parseFloat($("#return_fee").val());
                        $("#net_price").val(val);
                    }

                    function delete_cart_' . $k . '(id_product) {
                        $.ajax({
                            url: "' . base_url() . '/transaction_item/delete-cart",
                            type: "POST",
                            data: {
                                id: "' . $id . '",
                                id_product: id_product,
                            },
                            success: function(response) {
                                $.ajax({
                                    dataType: "json",
                                    url: "' . base_url() . '/transaction_item/get-cart?id=' . $id . '",
                                    success: function(html) {
                                        $("#tbody").html(html.html);
                                        $("#total").html(html.total);
                                        get_total_2_' . $k . '();
                                    }
                                });
                            }
                        });
                    }
                    $("#i-qty-' . $k . '").keyup(function() {
                        var id = $(this).attr("data-id");
                        var val = parseFloat($("#i-qty-" + id).val()) * parseFloat($("#i-price-" + id).val());
                        if(isNaN(val)){
                            val = 0;
                        }
                        $("#i-price-total-" + id).val(val);
                        $("#txt-price-total-" + id).html(val);
                        get_grand();
                        var qty = parseFloat($("#i-qty-" + id).val());
                        var price = parseFloat($("#i-price-" + id).val());
                        if(isNaN(qty)){
                            qty = 0;
                        }
                        if(isNaN(price)){
                            price = 0;
                        }
                        $.ajax({
                            url: "' . base_url() . '/transaction_item/edit-cart",
                            type: "POST",
                            data: {
                                id: "' . $id . '",
                                id_product: ' . $v['product'] . ',
                                qty: qty,
                                price: price
                            },
                            success: function(response) {
                                var total = 0;
                                for (var i = 0; i < ' . count($json) . '; i++) {
                                    total += parseFloat($("#i-price-total-" + i).val());
                                }
                                $("#total").html(total);
                                $("#total_1").val(total);
                                get_total_2_' . $k . '();
                            }
                        });

                    });
                    function get_grand(){
                    
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


        if ($id) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id' ");

            $query = $query[0];
            $json = json_decode($query['json'], true);
            $json[$id_product]['qty'] = $dt['qty'];
            $json[$id_product]['price_total'] = $dt['qty'] * $dt['price'];
            $dt = array();
            $dt['json'] = json_encode($json, true);
            $this->db->update('transaction', $dt, array('id' => $id));
        } else {
            $trx = $_SESSION['trx'];
            $json = json_decode($trx['json'], true);
            $json[$id_product]['qty'] = $dt['qty'];
            $json[$id_product]['price_total'] = $dt['qty'] * $dt['price'];
            $dt = array();
            $dt['json'] = json_encode($json, true);
            $_SESSION['trx'] = $dt;
        }
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

        $this->load->view('transaction_item/print', $data);
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

        $this->load->view('transaction_item/print_v2', $data);
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

        if ($dt['pay_at']) {
            $dt['payment_status'] = 'Paid';
        } else {
            $dt['payment_status'] = 'Unpaid';
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

        if ($dt['is_endorse']) {
            $dt['is_endorse'] = "1";
            $dt['dana_pencairan'] = 0;
        } else {
            $dt['is_endorse'] = "0";
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

        $dtp = array();
        $id_product = $id_product;
        $query = $this->mymodel->selectWithQuery("SELECT SUM(qty_in) as qty_in,SUM(qty_out) as qty_out,SUM(qty_out_pos) as qty_out_pos,SUM(qty) as qty FROM stock WHERE product = '$id_product'");

        $dtp['stock_in'] = strval($query[0]['qty_in']);
        $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
        $dtp['stock_out_pos'] = strval(abs($query[0]['qty_out_pos']) * -1);
        $dtp['stock'] = strval(doubleval($query[0]['qty']));
        $this->db->update('product', $dtp, array('id' => $id_product));
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
        $dt['payment_status'] = 'Unpaid';
        $dt['order_status'] = 'UNPAID';
        $dt['type'] = 'Out';
        $dt['type_sub'] = 'POS';
        $dt['is_manual'] = 1;

        $this->db->insert('transaction', $dt);
        $id = $this->db->insert_id();
        return redirect(base_url() . 'transaction_item/edit?id=' . $id);

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
        FROM product
        ORDER BY name ASC");

        $data['content'] = $this->load->view("transaction_item/create", $data, true);
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
        $this->load->view("transaction_item/set_cs", $data);
    }

    public function set_cs_process()
    {
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");

        if ($this->db->update('transaction', $dt, array('id' => $id))) {
            $this->db->update('stock', $dt, array('id_trx' => $id));
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
        $this->load->view("transaction_item/set_return", $data);
    }

    public function set_return_process()
    {
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $date = DATE('Y-m-d H:i:s', strtotime($_POST['date']));

        $data['data'] = $this->mymodel->selectWithQuery("SELECT * FROM transaction
        WHERE id = '$id'
        ");

        $dt = $data['data'][0];

        $dt['return_at'] = $date;
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['order_status'] = "RETURN";


        $this->db->update('transaction', $dt, array('id' => $id));

        $this->generate_stock($id, $dt);

        $msg = 'Simpan data berhasil!';
        echo $this->template->alert_success($msg);
    }

    function generate_stock($id, $dt)
    {

        if ($id) {
            $this->db->delete('stock', array('id_trx' => $id));
        }

        $json = json_decode($dt['json'], true);

        if (!in_array($dt['order_status'], array('UNPAID', 'CANCELLED', 'IN_CANCEL'))) {
            foreach ($json as $k2 => $v2) {
                $dts = array();
                $dts['brand'] = strval($v2['brand']);
                $dts['id_trx'] = strval($id);
                $dts['qty'] = 0 - abs($v2['qty']);
                $dts['qty_out'] = abs($v2['qty']);
                $dts['qty_out_pos'] = abs($v2['qty']);
                $dts['qty_in'] = '0';
                $dts['price'] = $v2['price'];
                $dts['hpp'] = doubleval($v2['hpp']);
                $dts['price_total'] = $v2['price_total'];
                $dts['product'] = $v2['product'];
                $dts['product_text'] = $v2['product_text'];
                $dts['sku'] = $v2['sku'];
                $dts['code'] = $dt['order_id'];
                $dts['type'] = "Out";
                $dts['type_sub'] = "POS";
                $dts['created_at'] = DATE("Y-m-d H:i:s");
                $dts['date'] = $dt['date'];
                $dts['desc'] = "Penjualan";
                $dts['created_by'] = strval($user['id']);
                $dts['status'] = "Aktif";
                $this->db->insert('stock', $dts);
                if (in_array($dt['order_status'], array('RETURN'))) {
                    $date = $dt['return_at'];
                    if (empty($date)) {
                        $date = DATE("Y-m-d H:i:s");
                    }
                    $dts['date'] = $date;
                    $dts['type'] = "In";
                    $dts['qty'] =  abs($v2['qty']);
                    $dts['qty_out'] = '0';
                    $dts['qty_out_pos'] = '0';
                    $dts['qty_in'] = '0';
                    $dts['desc'] = "Return";
                    $dts['qty_in_pos'] =  abs($v2['qty']);
                    $this->db->insert('stock', $dts);
                }
            }
        }
    }


    public function set_resi()
    {
        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT id,awb_number FROM transaction
        WHERE id = '$id'
        ");
        $data['data'] = $data['data'][0];

        $this->load->view("transaction_item/set_resi", $data);
    }

    public function set_resi_process()
    {
        // print_r($_POST);die;
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        // print_r($dt);die;


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
        $this->load->view("transaction_item/refresh", $data);
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
            $msg = 'Refresh data tidak berhasil!';
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
        $this->load->view("transaction_item/action", $data);
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
                FROM transaction WHERE id IN ($list_id) AND is_manual = 0 ");
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
        $this->load->view("transaction_item/delete", $data);
    }

    public function delete()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];

        $data = $this->mymodel->selectWithQuery("SELECT customer FROM transaction WHERE id = '$id'");
        $data = $data[0];



        if ($this->db->delete('transaction', array('id' => $id))) {


            $this->db->delete('stock', array('id_trx' => $id));

            $data['product'] = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0
            ORDER BY sku ASC
            ");
            foreach ($data['product'] as $k => $v) {
                $id_product = $v['id'];
                $this->update_stock($id_product);
            }
            $this->customer_summary($data['customer']);

            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }


    public function sync()
    {

        $data['store'] = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace FROM marketplace_config WHERE status = 'Aktif' ORDER BY marketplace DESC, shop_name ASC");

        $this->load->view("transaction_item/sync", $data);
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

        $this->load->view("transaction_item/import_resi", $data);
    }


    public function import_pencairan()
    {

        $data['param'] = $this->template->get_param();

        $this->load->view("transaction_item/import_pencairan", $data);
    }


    public function import_customer()
    {

        $data = $_SESSION['filter'];

        $this->load->view("transaction_item/import_customer", $data);
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

        $this->load->view("transaction_item/import", $data);
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


            $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
            -- ORDER BY brand ASC, sub_name ASC
            ORDER BY id ASC
            ");

            $data['product'] = $query;

            $data['header'] = array();

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
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
            $data['header_3'] = $header_3;

            $data['template'] = $this->template;

            $data['content'] = $this->load->view("transaction_item/import_check", $data, true);
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

            $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
            -- ORDER BY brand ASC, sub_name ASC
            ORDER BY id ASC
            ");

            $data['product'] = $query;


            $marketplace = $_POST['marketplace'];
            $marketplace = "MANUAL";

            if ($marketplace == "MANUAL") {
                $body_1 = array(
                    "id",
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
            } else if ($marketplace == "MARKETPLACE") {
                die;
            } else {
                die;
            }

            // $this->db->transStart();

            foreach ($sheet_data as $k => $v) {
                if ($k > 0 && $v['1']) {
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



                    if ($dt['pay_at']) {
                        $dt['payment_status'] = "Paid";
                    } else {
                        $dt['payment_status'] = "Unpaid";
                    }
                    $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));
                    if ($dt['date'] == '1970-01-01 07:00:00') {
                        $dt['date'] = DATE("Y-m-d 23:00:00");
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

                    if (!in_array($dt['payment_type'], array("TF", "COD", ""))) {
                        $dt['bank'] = $dt['payment_type'];
                        $dt['payment_type'] = "TF";
                    }
                    // print_r($dt);die;
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
                        // 
                        $this->db->insert('transaction', $dt);
                        $dt['id'] = $this->db->insert_id();
                    }


                    // print_r($dt);die;

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
                        $dts['qty_out'] = abs($v2['qty']);
                        $dts['qty_out_pos'] = abs($v2['qty']);
                        $dts['qty_in'] = '0';
                        $dts['price'] = $v2['price'];
                        $dts['hpp'] = doubleval($v2['hpp']);
                        $dts['price_total'] = $v2['price_total'];
                        $dts['product'] = $v2['product'];
                        $dts['product_text'] = $v2['product_text'];
                        $dts['sku'] = $v2['sku'];
                        $dts['code'] = $dt['order_id'];
                        $dts['type'] = "Out";
                        $dts['type_sub'] = "POS";
                        $dts['created_at'] = DATE("Y-m-d H:i:s");
                        $dts['date'] = $dt['date'];
                        $dts['created_by'] = strval($user['id']);
                        $dts['status'] = "Aktif";
                        $this->db->insert('stock', $dts);
                        if (in_array($dt['order_status'], array('RETURN'))) {
                            // $dts['date'] = strval($cancel_at);
                            $dts['type'] = "In";
                            $dts['qty'] =  abs($v2['qty']);
                            $dts['qty_out'] = '0';
                            $dts['qty_out_pos'] = '0';
                            $dts['qty_in'] = '0';
                            $dts['qty_in_pos'] =  abs($v2['qty']);
                            $this->db->insert('stock', $dts);
                            // print_r($dts);die;
                        }
                    }

                    // $this->customer_summary($dt['customer']);

                }
            }
            // $this->db->transComplete();

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

        $order_id = $_GET['order_id'];
        if ($order_id) {
            $qry .= " AND order_id = '$order_id' ";
        }


        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }


        if ($brand) {
            $qry .= " AND brand = '$brand' ";
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

        $type = $_GET['type'];
        if ($type) {
            $qry .= " AND type = '$type' ";
        }

        $type_sub = $_GET['type_sub'];
        if ($type_sub) {
            $qry .= " AND type_sub = '$type_sub' ";
        }


        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') ";
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
                $qry .= " AND dana_pencairan = 0";
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
                $qry .= " AND pesanan LIKE '%$keyword%' ";
            }
        }

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
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


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
        -- ORDER BY brand ASC, sub_name ASC
        ORDER BY id ASC
        ");

        $data['product'] = $query;

        if ($p == "MANUAL") {
            $data['header'] = array();

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
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
        $this->load->view("transaction_item/download_file", $data);
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
        $data['content'] = $this->load->view('transaction_item/tracking', $data, true);
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
