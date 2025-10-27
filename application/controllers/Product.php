<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Product extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        
        // AJAX methods for status updates require AJAX permission check
        $this->set_method_permissions([
            'update_status' => 'edit',
            'update_status_bulk' => 'edit'
        ]);
    }
    public function index()
    {
        $data['user'] = $_SESSION['user'];

        $data['keyword_category'] = $_GET['keyword_category'] ?? "Nama Produk";
        $keyword = $_GET['keyword'] ?? null;

        $status = $_GET['status'] ?? 'all';
        
        $data['start_date'] = $_GET['start_date'] ?: date("Y-m-d");
        $data['until_date'] = $_GET['until_date'] ?: date("Y-m-d");

        $brand = $_GET['brand'] ?? null;
        $marketplace = $_GET['marketplace'] ?? null;
        $data['brand'] = $brand;

        $data['title'] = 'Produk - ' . $this->template->title();

        $data['marketplace'] = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
        $data['brands'] = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['order_status'] = $this->mymodel->selectWithQuery("SELECT DISTINCT order_status FROM stock WHERE order_status != '' ORDER BY order_status ASC");

        $qry = "1=1";
        if ($brand) $qry .= " AND brand = '$brand'";
        if ($marketplace) $qry .= " AND marketplace = '$marketplace'";
        if ($keyword) {
            switch ($data['keyword_category']) {
                case "Nama Produk":
                    $qry .= " AND name LIKE '%$keyword%'";
                    break;
                case "SKU":
                    $qry .= " AND sku LIKE '%$keyword%'";
                    break;
                case "Brand":
                    $qry .= " AND brand LIKE '%$keyword%'";
                    break;
            }
        }

        if ($status === 'active') {
            $qry .= " AND status = 'Aktif'";
        } elseif ($status === 'inactive') {
            $qry .= " AND status = 'Tidak Aktif'";
        }

        $is_operational = ($_GET['p'] ?? '') === "operasional" ? 1 : 0;

        // Updated count query to match display logic - counts products that would actually be displayed
        $count_query = $this->mymodel->selectWithQuery("SELECT
            (
                -- Count non-variant products
                SELECT COUNT(*) FROM product p1
                WHERE p1.is_operational = $is_operational
                AND p1.is_varian = 0
                AND (p1.parent_id = 0 OR p1.parent_id IS NULL)
            ) + (
                -- Count variant products that have at least one variant
                SELECT COUNT(*) FROM product p2
                WHERE p2.is_operational = $is_operational
                AND p2.is_varian = 1
                AND (p2.parent_id = 0 OR p2.parent_id IS NULL)
                AND EXISTS (SELECT 1 FROM product p3 WHERE p3.parent_id = p2.id)
            ) AS total_all,
            (
                -- Count non-variant active products
                SELECT COUNT(*) FROM product p1
                WHERE p1.is_operational = $is_operational
                AND p1.is_varian = 0
                AND (p1.parent_id = 0 OR p1.parent_id IS NULL)
                AND p1.status = 'Aktif'
            ) + (
                -- Count variant products that have at least one active variant
                SELECT COUNT(*) FROM product p2
                WHERE p2.is_operational = $is_operational
                AND p2.is_varian = 1
                AND (p2.parent_id = 0 OR p2.parent_id IS NULL)
                AND EXISTS (SELECT 1 FROM product p3 WHERE p3.parent_id = p2.id AND p3.status = 'Aktif')
            ) AS total_active,
            (
                -- Count non-variant inactive products
                SELECT COUNT(*) FROM product p1
                WHERE p1.is_operational = $is_operational
                AND p1.is_varian = 0
                AND (p1.parent_id = 0 OR p1.parent_id IS NULL)
                AND p1.status = 'Tidak Aktif'
            ) + (
                -- Count variant products that have at least one inactive variant
                SELECT COUNT(*) FROM product p2
                WHERE p2.is_operational = $is_operational
                AND p2.is_varian = 1
                AND (p2.parent_id = 0 OR p2.parent_id IS NULL)
                AND EXISTS (SELECT 1 FROM product p3 WHERE p3.parent_id = p2.id AND p3.status = 'Tidak Aktif')
            ) AS total_inactive");
        
        $data['total_all'] = $count_query[0]['total_all'] ?? 0;
        $data['total_active'] = $count_query[0]['total_active'] ?? 0;
        $data['total_inactive'] = $count_query[0]['total_inactive'] ?? 0;

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM product WHERE $qry AND is_operational = $is_operational");
        $data['page'] = ceil($query[0]['count'] / 30);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = max(1, intval($_GET['page'] ?? 1));
        $base_url = base_url() . '/product';
        $url = $base_url . ($is_operational ? '?p=operasional&' : '?') . $this->template->get_param();
        
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->option_pagination($data['page'], $current_page, $data['param_pagination']);

        $view_file = $is_operational ? "product/all_operasional" : "product/all";
        $data['content'] = $this->load->view($view_file, $data, true);

        $this->load->view("TemplateDashboard", $data);
    }


    public function item_stock()
    {
        $data['template'] = $this->template;

        $id = $_GET['id'] ?? '';

        $keyword_category = $_GET['keyword_category'] ?? "Nama Produk";
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'] ?? '';

        if ($_GET['start_date'] == "") {
            $start_date = date("Y-m-d H:i:s");
            $start_date = date('Y-m-d H:i:s', strtotime($start_date . " -31 days"));
        } else {
            $start_date = date("Y-m-d H:i:s", strtotime($_GET['start_date']));
        }

        if ($_GET['until_date'] == "") {
            $until_date = date("Y-m-d H:i:s");
        } else {
            $until_date = date("Y-m-d H:i:s", strtotime($_GET['until_date']));
        }

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;

        $brand = $_GET['brand'] ?? '';
        $marketplace = $_GET['marketplace'] ?? '';
        $order_status = $_GET['order_status'] ?? '';
        $payment_type = $_GET['payment_type'] ?? '';

        $qry = "stock.date >= '$start_date' AND stock.date <= '$until_date' AND stock.product = '$id'";

        if (!empty($order_status)) {
            $qry .= " AND stock.order_status = '$order_status'";
        }

        if (!empty($brand)) {
            $qry .= " AND stock.brand = '$brand'";
        }

        if (!empty($marketplace)) {
            $qry .= " AND stock.marketplace = '$marketplace'";
        }

        if (!empty($payment_type)) {
            $qry .= " AND transaction.payment_type = '$payment_type'";
        }

        $limit = $_GET['limit'] ?? 30;
        $per_page_options = [30, 50, 100, 500];
        if (!in_array($limit, $per_page_options)) {
            $limit = 30;
        }

        $current_page = $_GET['page'] ?? 1;
        $offset = ($current_page > 1) ? ($current_page - 1) * $limit : 0;
        
        $initial_stock = $this->mymodel->selectWithQuery("
            SELECT COALESCE(SUM(qty), 0) as sum
            FROM stock
            WHERE product = '$id' AND date < '$start_date'
        ");
        $initial_balance = $initial_stock[0]['sum'];

        $all_transactions_query = "
            SELECT stock.*, transaction.payment_type
            FROM stock
            LEFT JOIN transaction ON stock.id_trx = transaction.id
            WHERE $qry
            ORDER BY stock.date ASC, stock.id ASC
        ";
        $all_transactions = $this->mymodel->selectWithQuery($all_transactions_query);

        $count_query = "
            SELECT COUNT(*) as total
            FROM stock
            LEFT JOIN transaction ON stock.id_trx = transaction.id
            WHERE $qry
        ";
        $total_data = $this->mymodel->selectWithQuery($count_query)[0]['total'];
        $data['page'] = ceil($total_data / $limit);

        $balance = $initial_balance;
        $transactions_with_balance = [];
        
        foreach ($all_transactions as $transaction) {
            $balance += $transaction['qty'];
            $transactions_with_balance[] = [
                'data' => $transaction,
                'balance' => $balance
            ];
        }
        
        $transactions_with_balance = array_reverse($transactions_with_balance);

        $display_data = array_slice($transactions_with_balance, $offset, $limit);
        $data['data'] = $display_data;
        $data['total_data'] = $total_data;
        $data['initial_balance'] = $initial_balance;
        $data['current_page'] = $current_page;
        $data['limit'] = $limit;
        $data['start'] = $offset + 1;
        $data['end'] = min($offset + $limit, $total_data);

        $this->load->view("product/item_stock", $data);
    }



    public function item()
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;

        $keyword_category = $_GET['keyword_category'] ?? "Nama Produk";
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'] ?? '';
        $status = $_GET['status'] ?? 'all';
        $brand = $_GET['brand'] ?? null;
        $marketplace = $_GET['marketplace'] ?? null;

        $qry = "1=1";

        if ($brand) {
            $qry .= " AND brand = '$brand'";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace'";
        }

        if ($keyword) {
            switch ($keyword_category) {
                case "Nama Produk":
                    $qry .= " AND name LIKE '%$keyword%'";
                    break;
                case "SKU":
                    $qry .= " AND sku LIKE '%$keyword%'";
                    break;
                case "Brand":
                    $qry .= " AND brand LIKE '%$keyword%'";
                    break;
            }
        }

        $sort = $_GET['sort'] ?? 'name';
        $order = ($_GET['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        
        $allowed_sort_columns = ['name', 'sku', 'brand', 'price_normal', 'price_reseller', 'price_distributor', 'price_buy', 'stock'];
        if (!in_array($sort, $allowed_sort_columns)) {
            $sort = 'name';
        }

        $limit = 30;
        $current_page = $_GET['page'] ?? 1;
        $offset = max(0, ($current_page - 1) * $limit);

        $base_query = "
            SELECT p.*, 
            IF(p.is_varian = 1, 
                (SELECT SUM(stock) FROM product WHERE parent_id = p.id AND status = 'Aktif'), 
                p.stock
            ) AS total_stock
            FROM product p
            WHERE $qry AND is_operational = 0 AND (parent_id = 0 OR parent_id IS NULL)
        ";

        if ($status === 'active') {
            $base_query .= " AND (p.status = 'Aktif' OR p.is_varian = 1)";
        } elseif ($status === 'inactive') {
            $base_query .= " AND (p.status = 'Tidak Aktif' OR p.is_varian = 1)";
        }

        $base_query .= " ORDER BY $sort $order LIMIT $offset, $limit";

        $query = $this->mymodel->selectWithQuery($base_query);

        foreach ($query as &$product) {
            if ($product['is_varian']) {
                $variant_query = "SELECT * FROM product WHERE parent_id = {$product['id']}";
                
                if ($status === 'active') {
                    $variant_query .= " AND status = 'Aktif'";
                } elseif ($status === 'inactive') {
                    $variant_query .= " AND status = 'Tidak Aktif'";
                }
                
                $variant_query .= " ORDER BY name ASC";
                
                $product['variants'] = $this->mymodel->selectWithQuery($variant_query);
                
                if ($status === 'active' && empty($product['variants'])) {
                    $product = null;
                }
                elseif ($status === 'inactive' && empty($product['variants'])) {
                    $product = null;
                }
            } else {
                if (($status === 'active' && $product['status'] !== 'Aktif') || 
                    ($status === 'inactive' && $product['status'] !== 'Tidak Aktif')) {
                    $product = null;
                }
            }
        }

        $query = array_filter($query);

        $data['data'] = $query;
        $data['start'] = $offset;
        $this->load->view("product/item", $data);
    }

    public function item_operasional()
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;

        $keyword_category = $_GET['keyword_category'] ?? "Nama Produk";
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'] ?? '';
        $status = $_GET['status'] ?? 'all';
        $brand = $_GET['brand'] ?? null;
        $marketplace = $_GET['marketplace'] ?? null;

        $qry = "1=1";

        if ($brand) {
            $qry .= " AND brand = '$brand'";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace'";
        }

        if ($keyword) {
            switch ($keyword_category) {
                case "Nama Produk":
                    $qry .= " AND name LIKE '%$keyword%'";
                    break;
                case "SKU":
                    $qry .= " AND sku LIKE '%$keyword%'";
                    break;
                case "Brand":
                    $qry .= " AND brand LIKE '%$keyword%'";
                    break;
            }
        }

        if ($status === 'active') {
            $qry .= " AND status = 'Aktif'";
        } elseif ($status === 'inactive') {
            $qry .= " AND status = 'Tidak Aktif'";
        }

        $sort = $_GET['sort'] ?? 'name';
        $order = ($_GET['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        
        $allowed_sort_columns = ['name', 'sku', 'brand', 'price_normal', 'price_reseller', 'price_distributor', 'price_buy', 'stock'];
        if (!in_array($sort, $allowed_sort_columns)) {
            $sort = 'name';
        }

        $limit = 30;
        $current_page = $_GET['page'] ?? 1;
        $offset = max(0, ($current_page - 1) * $limit);

        $query = $this->mymodel->selectWithQuery("
            SELECT p.*, 
            IF(p.is_varian = 1, 
                (SELECT SUM(stock) FROM product WHERE parent_id = p.id), 
                p.stock
            ) AS total_stock
            FROM product p
            WHERE $qry AND is_operational = 1 AND (parent_id = 0 OR parent_id IS NULL)
            ORDER BY $sort $order
            LIMIT $offset, $limit
        ");

        foreach ($query as &$product) {
            if ($product['is_varian']) {
                $product['variants'] = $this->mymodel->selectWithQuery("
                    SELECT * FROM product 
                    WHERE parent_id = {$product['id']} 
                    ORDER BY name ASC
                ");
            }
        }

        $data['data'] = $query;
        $data['start'] = $offset;
        $this->load->view("product/item", $data);
    }


    public function stock()
    {

        $start_date = $_GET['start_date'] ?? '';
        $start_time = $_GET['start_time'] ?? '00:00';
        $until_date = $_GET['until_date'] ?? '';
        $until_time = $_GET['until_time'] ?? '';

        if (empty($start_date)) {
            $start_date = date("d/m/Y", strtotime("-31 days"));
            $start_time = date("H:i");
        } else {
            $start_date = date("d/m/Y", strtotime(str_replace('/', '-', $start_date)));
        }

        if (empty($until_date)) {
            $until_date = date("d/m/Y");
            $until_time = date("H:i");
        } else {
            $until_date = date("d/m/Y", strtotime(str_replace('/', '-', $until_date)));
        }

        $start_datetime = date("Y-m-d H:i:s", strtotime(str_replace('/', '-', $start_date) . " $start_time"));
        $until_datetime = date("Y-m-d H:i:s", strtotime(str_replace('/', '-', $until_date) . " $until_time"));

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];
        $payment_type = $_GET['payment_type'];

        $data['start_date'] = $start_datetime;
        $data['until_date'] = $until_datetime;


        $data['template'] = $this->template;

        $data['title'] = 'Stok Produk - ' . $this->template->title();

        $id = $_GET['id'];

        $qry = " stock.date >= '$start_datetime' AND stock.date <= '$until_datetime' AND stock.product = '$id'";


        if (!empty($order_status)) {
            $qry .= " AND stock.order_status = '$order_status'";
        }

        if (!empty($brand)) {
            $qry .= " AND stock.brand = '$brand'";
        }

        if (!empty($marketplace)) {
            $qry .= " AND stock.marketplace = '$marketplace'";
        }

        if (!empty($payment_type)) {
            $qry .= " AND transaction.payment_type = '$payment_type'";
        }


        $limit = $_GET['limit'] ?? 30;
        $per_page_options = [10, 20, 50, 100, 500];
        if (!in_array($limit, $per_page_options)) {
            $limit = 10;
        }


        $query = $this->mymodel->selectWithQuery("SELECT COUNT(stock.id) as count, SUM(stock.qty_in + stock.qty_in_pos) as total_in, SUM(stock.qty_out + stock.qty_out_pos) as total_out FROM stock LEFT JOIN transaction ON stock.id_trx = transaction.id WHERE $qry");

        $total_data = $query[0]['count'];
        $data['page'] = ceil($total_data / $limit);


        $data['notif'] = '
            <p class="mb-2">
                <span class="text-notif">'
                    . $this->template->separator_only($query[0]['count']) . ' data ditemukan!
                </span>
                <span class="fs-11">(
                    <span class="text-green">'
                        . $this->template->separator_only($query[0]['total_in']) . ' In
                    </span>
                    |
                    <span class="text-danger">'
                        . $this->template->separator_only($query[0]['total_out']) . ' Out
                    </span>
                )</span>
            </p>';




        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $data['store'] = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace FROM marketplace_config WHERE status = 'Aktif' ORDER BY marketplace DESC, shop_name ASC");

        $query = $this->mymodel->selectWithQuery("SELECT id, code as opt FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT DISTINCT order_status AS opt FROM `transaction` WHERE order_status != '' AND order_status != 'SELESAI' AND order_status != 'Sselesai';");

        $data['order_status'] = $query;

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id'");

        $data['data'] = $query[0];

        if (empty($data['data'])) {
            return redirect(base_url() . 'stock');
        }

        $url = base_url() . '/product/stock/' . $this->template->get_param();

        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $url);

        $data['content'] = $this->load->view("product/stock", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function edit()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id'");
        $data['data'] = $query[0];

        if ($data['data']['is_varian']) {
            $variants = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE parent_id = '$id'");
            $data['data']['variants'] = $variants;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY name ASC");
        $data['brand'] = $query;
        
        $this->load->view("product/edit", $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $variants = isset($_POST['variants']) ? $_POST['variants'] : [];
        
        $current_product = $this->db->get_where('product', ['id' => $id])->row_array();
        
        $dt = $_POST['dt'];

        // Clean price fields from formatting
        if (isset($dt['price_buy'])) {
            $dt['price_buy'] = str_replace(['.', ','], '', $dt['price_buy']);
        }
        if (isset($dt['price_normal'])) {
            $dt['price_normal'] = str_replace(['.', ','], '', $dt['price_normal']);
        }
        if (isset($dt['price_reseller'])) {
            $dt['price_reseller'] = str_replace(['.', ','], '', $dt['price_reseller']);
        }
        if (isset($dt['price_distributor'])) {
            $dt['price_distributor'] = str_replace(['.', ','], '', $dt['price_distributor']);
        }

        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];
        $dt['sub_name'] = strtoupper($dt['name']);
        
        if (!isset($dt['is_varian'])) {
            $dt['is_varian'] = $current_product['is_varian'];
        }

        // Handle image removal
        if (isset($_POST['remove_main_image']) && $_POST['remove_main_image'] == 'on') {
            $dt['img'] = '';
            // Delete the old image file if it exists
            if (!empty($current_product['img'])) {
                $old_img_path = FCPATH . 'assets/img/product/' . $current_product['img'];
                if (file_exists($old_img_path)) {
                    @unlink($old_img_path);
                }
            }
        }

        // Handle new image upload
        if (!empty($_FILES['file']['name'])) {
            $dir = "./assets/img/product/";
            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite'] = TRUE;
            $config['file_name'] = $id;
            $config['max_size'] = 2048;

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('file')) {
                $error = $this->upload->display_errors();
                echo $this->template->alert_danger($error);
                die;
            } else {
                $file = $this->upload->data();
                $dt['img'] = $file['file_name'];
            }
        }

        $this->db->trans_begin();

        try {
            $this->db->update('product', $dt, array('id' => $id));
            
            if ($dt['is_varian'] && !empty($variants)) {
                $existing_variants = $this->mymodel->selectWithQuery("SELECT id FROM product WHERE parent_id = '$id'");
                $existing_ids = array_column($existing_variants, 'id');
                $submitted_ids = array_filter(array_column($variants, 'id'));
                $deleted_ids = array_diff($existing_ids, $submitted_ids);
                
                if (!empty($deleted_ids)) {
                    $this->db->where_in('id', $deleted_ids)->delete('product');
                }
                
                foreach ($variants as $index => $variant) {
                    $variant_data = [
                        'parent_id' => $id,
                        'brand' => $dt['brand'],
                        'brand_text' => $dt['brand'],
                        'sku' => $variant['sku'],
                        'name' => $variant['name'],
                        'sub_name' => strtoupper($variant['name']),
                        'price_buy' => str_replace(['.', ','], '', $variant['price_buy']),
                        'price_normal' => str_replace(['.', ','], '', $variant['price_normal']),
                        'price_reseller' => str_replace(['.', ','], '', $variant['price_reseller']),
                        'price_distributor' => str_replace(['.', ','], '', $variant['price_distributor']),
                        'weight' => $variant['weight'],
                        'is_gift' => $dt['is_gift'],
                        'is_operational' => $dt['is_operational'],
                        'is_varian' => 0,
                        'updated_at' => DATE("Y-m-d H:i:s"),
                        'updated_by' => $user['id'],
                        'status' => 'Aktif'
                    ];

                    // Handle variant image removal
                    if (isset($variant['remove_img']) && $variant['remove_img'] == 'on') {
                        $variant_data['img'] = '';
                        // Delete old image if exists
                        if (!empty($variant['id'])) {
                            $existing_variant = $this->db->get_where('product', ['id' => $variant['id']])->row_array();
                            if (!empty($existing_variant['img'])) {
                                $old_img_path = FCPATH . 'assets/img/product/' . $existing_variant['img'];
                                if (file_exists($old_img_path)) {
                                    @unlink($old_img_path);
                                }
                            }
                        }
                    }

                    $fileKey = 'variant_img_' . $index;

                    if (!empty($_FILES[$fileKey]['name'])) {
                        $variant_data['img'] = $this->uploadVariantImage($fileKey, $variant['id'] ?? null);
                    } elseif (!empty($variant['existing_img']) && !isset($variant['remove_img'])) {
                        $variant_data['img'] = $variant['existing_img'];
                    }

                    if (!empty($variant['id'])) {
                        $this->db->update('product', $variant_data, array('id' => $variant['id']));
                    } else {
                        $variant_data['created_at'] = DATE("Y-m-d H:i:s");
                        $variant_data['created_by'] = $user['id'];
                        $this->db->insert('product', $variant_data);
                    }
                }
            }
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Gagal mengupdate data produk!');
            } else {
                $this->db->trans_commit();
                $this->update_stock($id);
                echo $this->template->alert_success('Update data berhasil!');
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function uploadVariantImage($fieldName, $variantId = null)
    {
        $dir = FCPATH . 'assets/img/product/variants/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        $config = [
            'upload_path' => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size' => 2048,
            'file_name' => $variantId ? 'variant_'.$variantId : 'variant_'.time().'_'.rand(1000,9999),
            'overwrite' => (bool)$variantId,
            'encrypt_name' => false 
        ];
        
        $this->load->library('upload', $config);
        $this->upload->initialize($config);
        
        if (!$this->upload->do_upload($fieldName)) {
            if ($this->upload->display_errors() != '<p>You did not select a file to upload.</p>') {
                throw new Exception($this->upload->display_errors());
            }
            return null;
        }
        
        $upload_data = $this->upload->data();
        return 'variants/' . $upload_data['file_name']; 
    }

    public function update_stock($id_product)
    {

        $dtp = array();
        $id_product = $id_product;
        $query = $this->mymodel->selectWithQuery("SELECT SUM(qty_in) as qty_in,SUM(qty_out) as qty_out,SUM(qty) as qty FROM stock WHERE product = '$id_product'");

        $dtp['stock_in'] = strval($query[0]['qty_in']);
        $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
        $dtp['stock'] = strval(doubleval($query[0]['qty']));
        $dtp['stock'] = strval(doubleval($query[0]['qty']));
        $this->db->update('product', $dtp, array('id' => $id_product));
    }

    public function create()
    {
        $data['data'] = array(
            'id' => '',
            'name' => '',
            'sku' => '',
            'brand' => '',
            'weight' => 0,
            'price_buy' => 0,
            'price_normal' => 0,
            'price_reseller' => 0,
            'price_distributor' => 0,
            'desc' => '',
            'img' => '',
            'is_gift' => 0,
            'is_varian' => 0,
            'is_operational' => isset($_GET['p']) && $_GET['p'] == 'operasional' ? 1 : 0
        );

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY name ASC");

        $data['brand'] = $query;

        $this->load->view("product/create", $data);
    }


    public function store()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $variants = isset($_POST['variants']) ? $_POST['variants'] : [];

        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];
        $dt['sub_name'] = strtoupper($dt['name']);
        // Fix: Check the actual value, not just if it's set
        $dt['is_varian'] = (isset($dt['is_varian']) && $dt['is_varian'] == '1') ? 1 : 0;

        if (!empty($_FILES['file']['name'])) {
            $dt['img'] = $this->uploadProductImage('file');
        }

        $this->db->trans_begin();

        try {
            if ($id) {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = $user['id'];
                $this->db->where('id', $id)->update('product', $dt);
                $product_id = $id;

                $this->db->where('parent_id', $product_id)->delete('product');
            } else {
                $this->db->insert('product', $dt);
                $product_id = $this->db->insert_id();
            }

            if ($dt['is_varian'] && !empty($variants)) {
                foreach ($variants as $index => $variant) {
                    // Skip empty variants (validate that essential fields are filled)
                    if (empty($variant['name']) || empty($variant['sku'])) {
                        continue;
                    }

                    $variant_data = [
                        'parent_id' => $product_id,
                        'brand' => $dt['brand'],
                        'brand_text' => $dt['brand'],
                        'sku' => $variant['sku'],
                        'name' => $variant['name'],
                        'sub_name' => strtoupper($variant['name']),
                        'price_buy' => $variant['price_buy'],
                        'price_normal' => $variant['price_normal'],
                        'price_reseller' => $variant['price_reseller'],
                        'price_distributor' => $variant['price_distributor'],
                        'weight' => $variant['weight'],
                        'is_gift' => $dt['is_gift'],
                        'is_operational' => $dt['is_operational'],
                        'is_varian' => 0,
                        'created_at' => DATE("Y-m-d H:i:s"),
                        'created_by' => $user['id'],
                        'status' => 'Aktif'
                    ];

                    $fileKey = 'variant_img_' . $index;

                    if (!empty($_FILES[$fileKey]['name'])) {
                        $variant_data['img'] = $this->uploadVariantImage($fileKey, $variant['id'] ?? null);
                    } elseif (!empty($variant['existing_img'])) {
                        $variant_data['img'] = $variant['existing_img'];
                    }


                    $this->db->insert('product', $variant_data);
                }
            }
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Gagal menyimpan data produk!');
            } else {
                $this->db->trans_commit();
                echo $this->template->alert_success('Data produk berhasil disimpan!');
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function uploadProductImage($fieldName)
    {
        $dir = FCPATH . 'assets/img/product/';

        // Create directory if it doesn't exist
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $config['upload_path'] = $dir;
        $config['allowed_types'] = 'jpg|jpeg|png';
        $config['max_size'] = 2048; // 2MB
        $config['file_name'] = 'product_' . DATE("YmdHis");
        $config['overwrite'] = true;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload($fieldName)) {
            throw new Exception($this->upload->display_errors());
        }

        return $this->upload->data('file_name');
    }


    public function sync()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id'");

        $data['data'] = $query[0];
        $this->load->view("product/sync", $data);
    }

    public function sync_process()
    {

        $product = $this->template->get_session('product');
        $id = $_POST['id'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id'");

        $v = $query[0];
        $response = $this->template->get_social_media($v['type'], $v['url']);
        $dt = array();
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $product['id'];
        if ($response['data']['like'] > 0) {
            $dt['like'] = $response['data']['like'];
            $dt['comment'] = $response['data']['comment'];
            $dt['collect'] = $response['data']['collect'];
            $dt['share'] = $response['data']['share'];
            $dt['view'] = $response['data']['view'];
            if ($v['cost'] > 0 && $dt['view'] > 0) {
                $dt['cpm'] = $v['cost'] / $dt['view'] * 1000;
            }
        }

        $model = $db->table('product');
        $model->where('id', $v['id']);
        $model->update($dt);

        if ($response['status'] == true) {
            $msg = 'Sync data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            if ($response) {
                $msg = $response['msg'];
            } else {
                $msg = 'Data product belum tersedia!';
            }
            echo $this->template->alert_danger($msg);
        }
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("product/delete", $data);
    }

    public function delete()
    {
        $id = $_POST['id'];
        
        $this->db->trans_begin();

        try {
            $variants = $this->db->get_where('product', ['parent_id' => $id])->result_array();
            
            if (!empty($variants)) {
                $this->db->where('parent_id', $id)->delete('product');
            }
            
            $this->db->delete('product', ['id' => $id]);
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $msg = 'Hapus data tidak berhasil!';
                echo $this->template->alert_danger($msg);
            } else {
                $this->db->trans_commit();
                $msg = 'Hapus data berhasil!';
                echo $this->template->alert_success($msg);
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $msg = 'Terjadi kesalahan: ' . $e->getMessage();
            echo $this->template->alert_danger($msg);
        }
    }

    public function update_status() {
        // Check AJAX and permission
        if (!$this->require_ajax_permission('product', 'edit')) {
            return;
        }
    
        $id = $this->input->post('id');
        $status = $this->input->post('status');
    
        if (empty($id) || !in_array($status, ['Aktif', 'Tidak Aktif'])) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Data tidak valid'
                ]));
            return;
        }
    
        $this->db->where('id', $id);
        $result = $this->db->update('product', ['status' => $status]);
    
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $result ? true : false,
                'message' => $result ? 'Ubah status produk berhasil!' : 'Ubah status produk gagal!'
            ]));
    }

    public function update_status_bulk() {
        // Check AJAX and permission
        if (!$this->require_ajax_permission('product', 'edit')) {
            return;
        }

        $ids = $this->input->post('ids');
        $status = $this->input->post('status');

        if (empty($ids) || !is_array($ids) || !in_array($status, ['Aktif', 'Tidak Aktif'])) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Data tidak valid'
                ]));
            return;
        }

        $this->db->where_in('id', $ids);
        $result = $this->db->update('product', ['status' => $status]);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $result ? true : false,
                'message' => $result ? 'Ubah status produk berhasil!' : 'Ubah status produk gagal!'
            ]));
    }

    public function action()
    {
        $id_selected_v2 = $_POST['id_selected_v2'];
        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }
        $code = $_GET['code'];
        $data['data']['id'] = $id;
        $data['data']['code'] = $code;
        if ($code == "hapus_data") {
            $data['question'] = "Apakah kamu yakin ingin menghapus data produk ini?";
            $data['btn'] = "Hapus Data";
        } else if ($code == "nonaktifkan") {
            $data['question'] = "Apakah kamu yakin ingin menonaktifkan data produk ini?";
            $data['btn'] = "Nonaktifkan Data";
        } else if ($code == "aktifkan") {
            $data['question'] = "Apakah kamu yakin ingin mengaktifkan data produk ini?";
            $data['btn'] = "Aktifkan Data";
            
        }
        $this->load->view("product/action", $data);
    }

    public function action_process()
    {
        $code = $_POST['code'];
        $user = $_SESSION['user'];
        $id_selected = $_POST['id_selected'] ?? '';

        if (empty($id_selected)) {
            echo $this->template->alert_danger('Pastikan kamu sudah memilih minimal 1 data!');
            return;
        }

        $ids = explode(',', $id_selected);
        if (empty($ids)) {
            echo $this->template->alert_danger('Format data yang dipilih tidak valid!');
            return;
        }

        $this->db->trans_begin();

        try {
            if ($code == "hapus_data") {
                $this->db->where_in('parent_id', $ids)->delete('product');
                
                $this->db->where_in('id', $ids)->delete('product');
                
                $msg = 'Hapus data berhasil!';
            } 
            else if ($code == "nonaktifkan") {
                $this->db->where_in('id', $ids)
                        ->update('product', ['status' => "Tidak Aktif"]);
                
                $this->db->where_in('parent_id', $ids)
                        ->update('product', ['status' => "Tidak Aktif"]);
                
                $msg = 'Nonaktifkan data berhasil!';
            } 
            else if ($code == "aktifkan") {
                $this->db->where_in('id', $ids)
                        ->update('product', ['status' => "Aktif"]);
                
                $this->db->where_in('parent_id', $ids)
                        ->update('product', ['status' => "Aktif"]);
                
                $msg = 'Aktifkan data berhasil!';
            } 
            else {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Aksi tidak dikenali!');
                return;
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Proses gagal!');
            } else {
                $this->db->trans_commit();
                echo $this->template->alert_success($msg);
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function get_price_by_id()
    {
        $id_product = $this->input->post('id_product');
        $price = $this->mymodel->selectWithQuery("SELECT price_normal, price_reseller, price_distributor FROM product WHERE id = '$id_product'");
        $data = array(
            'price_reseller' => $this->template->separator_only($price[0]['price_reseller']),
            'price_distributor' => $this->template->separator_only($price[0]['price_distributor']),
        );
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }
}
