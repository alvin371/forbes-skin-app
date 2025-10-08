<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/BaseController.php';

class Stock extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'remove' => 'delete',
            'action' => 'edit',
            'action_process' => 'edit'
        ]);
    }

    public function index()
    {
        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Order ID";
        }

        $data['keyword_category'] = $keyword_category;

        $data['title'] = 'Stok - ' . $this->template->title();
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
        $keyword = $_GET['keyword'];
        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $product = $_GET['product'];
        $type = $_GET['type'];
        $type_sub = $_GET['type_sub'];

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $brand = $_GET['brand'];

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        $order_id = $_GET['order_id'];
        if ($order_id) {
            $qry .= " AND order_id = '$order_id' ";
        }

        if ($product) {
            $qry .= " AND product = '$product' ";
        }

        if ($type) {
            $qry .= " AND type = '$type' ";
        }

        if ($type_sub) {
            $qry .= " AND type_sub = '$type_sub' ";
        }

        $shop_id = $_GET['shop_id'];
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }

        if ($keyword) {
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Product") {
                $qry .= " AND product_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "SKU") {
                $qry .= " AND sku LIKE '%$keyword%' ";
            }
        }

        $limit = 30;

        $offset = intval($_GET['offset']);

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count, SUM(qty_in + qty_in_pos) as total_in, SUM(qty_out + qty_out_pos) as total_out FROM stock
        WHERE $qry ");

        $data['page'] = CEIL($query[0]['count'] / 30);

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



        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/stock?keyword=' . $_GET['keyword'] . '&brand=' . $_GET['brand'] . '&marketplace=' . $_GET['marketplace'] . '&cs=&start_date=' . $start_date . '&until_date=' . $until_date;

        $url = base_url() . '/stock/' . $this->template->get_param();
        $data['url_1'] = $this->template->get_param_without_order_status($url);
        $data['url_2'] = $this->template->get_param_without_keyword_category($url);
        $data['url_3'] = $this->template->get_param_without('order_type');
        $data['url_4'] = $this->template->get_param_without('pencairan');
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $query = $this->mymodel->selectWithQuery("SELECT id, name as opt FROM product WHERE is_varian = 0
        ORDER BY name ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role = '3' 
        ORDER BY full_name ASC
        ");

        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $data['store'] = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace FROM marketplace_config WHERE status = 'Aktif' ORDER BY marketplace DESC, shop_name ASC");

        $query = $this->mymodel->selectWithQuery("SELECT id, code as opt FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $data['content'] = $this->load->view("stock/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $until_date = $_GET['until_date'] ?? date('Y-m-d');
        
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;

        $keyword = $_GET['keyword'] ?? '';
        $brand = $_GET['brand'] ?? '';
        $marketplace = $_GET['marketplace'] ?? '';
        $cs = $_GET['cs'] ?? '';
        $product = $_GET['product'] ?? '';
        $type = $_GET['type'] ?? '';
        $type_sub = $_GET['type_sub'] ?? '';
        $keyword_category = $_GET['keyword_category'] ?? 'Order ID';

        $data['keyword_category'] = $keyword_category;
        $data['brand'] = $brand;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role = '3' 
        ORDER BY full_name ASC
        ");
        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM group_wa WHERE status = 'ENABLE'
        ORDER BY CAST(name AS SIGNED) ASC
        ");
        $data['group_wa'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");
        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0 ORDER BY name ASC");
        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");
        $data['brands'] = $query;

        $qry = "";
        $qry = " DATE(date) >= '".$this->db->escape_str($start_date)."'
        AND DATE(date) <= '".$this->db->escape_str($until_date)."' ";

        $order_id = $_GET['order_id'] ?? '';
        if ($order_id) {
            $qry .= " AND order_id = '".$this->db->escape_str($order_id)."' ";
        }

        if ($product) {
            $qry .= " AND product = '".$this->db->escape_str($product)."' ";
        }

        if ($type) {
            $qry .= " AND type = '".$this->db->escape_str($type)."' ";
        }

        if ($type_sub) {
            $qry .= " AND type_sub = '".$this->db->escape_str($type_sub)."' ";
        }

        $shop_id = $_GET['shop_id'] ?? '';
        if ($shop_id) {
            $qry .= " AND shop_id = '".$this->db->escape_str($shop_id)."' ";
        }

        if ($keyword) {
            $keyword = $this->db->escape_str($keyword);
            switch ($keyword_category) {
                case "Order ID":
                    $qry .= " AND order_id LIKE '%$keyword%' ";
                    break;
                case "Product":
                    $qry .= " AND product_text LIKE '%$keyword%' ";
                    break;
                case "SKU":
                    $qry .= " AND sku LIKE '%$keyword%' ";
                    break;
            }
        }

        $per_page_options = [30, 50, 100, 500];
        $limit = isset($_GET['limit']) && in_array($_GET['limit'], $per_page_options) 
                ? (int)$_GET['limit'] 
                : 30;
        $data['limit'] = $limit;
        $data['per_page_options'] = $per_page_options;

        $current_page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($current_page - 1) * $limit;

        $allowed_columns = ['id', 'date', 'order_id', 'product_text', 'sku', 'type', 'qty', 'price'];
        $sort_column = in_array($_GET['sort_column'] ?? '', $allowed_columns) 
                    ? $_GET['sort_column'] 
                    : 'date';
        $sort_order = strtoupper($_GET['sort_order'] ?? '') === 'ASC' 
                    ? 'ASC' 
                    : 'DESC';

        $count_query = $this->mymodel->selectWithQuery("SELECT COUNT(*) as total FROM stock WHERE $qry");
        $total_data = $count_query[0]['total'] ?? 0;
        $data['total_data'] = $total_data;
        $data['page'] = ceil($total_data / $limit);
        $data['current_page'] = $current_page;

        $query = $this->mymodel->selectWithQuery("
        SELECT * FROM stock
        WHERE $qry 
        ORDER BY $sort_column $sort_order, id DESC
        LIMIT $offset, $limit
        ");

        $data['data'] = $query;
        $data['start'] = $offset + 1;
        $data['end'] = min($offset + $limit, $total_data);

        $this->load->view("stock/item", $data);
    }


    public function update_item()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $de = $_POST['de'];

        if ($dt['date']) {
            $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));
        }

        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        if ($dt['type'] == "Out") {
            $dt['qty'] = abs($dt['qty']) * -1;
            $dt['qty_out'] = abs($dt['qty']);
            $dt['qty_in'] = '0';
        } else {
            $dt['qty'] = abs($dt['qty']);
            $dt['qty_in'] = abs($dt['qty']);
            $dt['qty_out'] = '0';
        }


        if ($_POST['column'] == 'dt[product]') {
            $id_product = $_POST['value'];
            $query = $this->mymodel->selectWithQuery("SELECT brand FROM product WHERE id = '$id_product'");

            $dt['brand'] = strval($query[0]['brand']);
        }

        if (in_array($_POST['column'], array('dt[product]', 'dt[qty]', 'dt[type]')) && $dt['product']) {

            $query = $this->mymodel->selectWithQuery("SELECT product FROM stock WHERE id = '$id'");

            $latest_product = $query[0]['product'];


            $model = $db->table('stock');
            $model->where('id', $id);
            $model->update($dt);

            if ($latest_product) {
                $dtp = array();
                $id_product = $latest_product;
                $query = $this->mymodel->selectWithQuery("SELECT SUM(qty_in) as qty_in,SUM(qty_out) as qty_out,SUM(qty) as qty FROM stock WHERE product = '$id_product'");

                $dtp['stock_in'] = strval($query[0]['qty_in']);
                $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
                $dtp['stock'] = strval(doubleval($query[0]['qty']));
                $model_2 = $this->db->table('product');
                $model_2->where('id', $id_product);
                $model_2->update($dtp);
            }

            $dtp = array();
            $id_product = $dt['product'];
            $query = $this->mymodel->selectWithQuery("SELECT SUM(qty_in) as qty_in,SUM(qty_out) as qty_out,SUM(qty) as qty FROM stock WHERE product = '$id_product'");

            $dtp['stock_in'] = strval($query[0]['qty_in']);
            $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
            $dtp['stock'] = strval(doubleval($query[0]['qty']));
            $model_3 = $this->db->table('product');
            $model_3->where('id', $id_product);
            $model_3->update($dtp);
        } else {

            $model = $db->table('stock');
            $model->where('id', $id);
            $model->update($dt);
        }




        // if($_POST['column'] == 'dt[grup]'){
        //     $query = $this->mymodel->selectWithQuery("SELECT grup FROM customer WHERE id = '$id'");
        //     
        //     $latest_group = $query[0]['grup'];

        //     $grup = $latest_group;
        //     if($grup){
        //         $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM customer WHERE grup = '$grup'");
        //         
        //         $query = $query[0];
        //         $dte = array();
        //         $dte['customer'] = $query['count'];
        //         $model2 = $this->db->table('group_wa');
        //         $model2->where('name', $grup);
        //         $model2->update($dte);
        //     }
        //     $grup = $dt['grup'];
        //     if($grup){
        //         $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM customer WHERE grup = '$grup'");
        //         
        //         $query = $query[0];
        //         $dte = array();
        //         $dte['customer'] = $query['count'];
        //         $model3 = $this->db->table('group_wa');
        //         $model3->where('name', $grup);
        //         $model3->update($dte);
        //     }
        // }

        if ($_POST['column'] == "dt[qty]") {
            $id_product = $dt['product'];
            $this->update_stock($id_product);
        }

        $msg = 'Update data berhasil!';;

        $html = array();
        $html['status'] = true;
        $html['brand'] = strval($dt['brand']);
        $html['msg'] = $this->template->alert_success($msg);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }

    public function edit()
    {
        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT * FROM stock WHERE id = '$id'");
        $data['data'] = $data['data'][0];

        $data['product'] = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0 ORDER BY name ASC");
        $data['brand'] = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");

        $this->load->view("stock/edit", $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));

        if ($dt['type'] == "Out") {
            $dt['qty'] = 0 - doubleval($dt['qty']);
            $dt['qty_out'] = abs(doubleval($dt['qty']));
            $dt['qty_in'] = 0;
        } else {
            $dt['qty_in'] = abs(doubleval($dt['qty']));
            $dt['qty_out'] = 0;
        }

        $dt['type_sub'] = "Stock";

        $product = $dt['product'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$product'");
        $dt['product_text'] = strval($query[0]['name']);
        $dt['brand'] = strval($query[0]['brand']);

        if ($_FILES['file']['name']) {
            $input = $this->validate([
                'file' => [
                    'uploaded[file]',
                    'mime_in[file,image/jpg,image/jpeg,image/png]',
                    'max_size[file,1024]',
                ]
            ]);

            if (!$input) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $img = $this->request->getFile('file');
                $dir = str_replace('public/', '', FCPATH . 'assets/img/stock/');
                $img->move($dir);
                $data = [
                    'name' =>  $img->getName(),
                    'type'  => $img->getClientMimeType()
                ];
                $currentFileName = $dir . $data['name'];
                $newfile = $id . '.' . substr(strrchr($data['name'], "."), 1);
                $newFileName = $dir . $newfile;
                rename($currentFileName, $newFileName);
                $dt['img'] = $newfile;
            }
        }


        if ($this->db->update('stock', $dt, array('id' => $id))) {
            $this->update_stock($dt['product']);
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }



    public function create()
    {

        $data['product'] = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0 ORDER BY name ASC");
        $data['brand'] = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");

        $this->load->view("stock/create", $data);
    }


    public function store()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];

        $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));

        if ($dt['type'] == "Out") {
            $dt['qty'] = 0 - doubleval($dt['qty']);
            $dt['qty_out'] = abs(doubleval($dt['qty']));
            $dt['qty_in'] = 0;
        } else {
            $dt['qty_in'] = abs(doubleval($dt['qty']));
            $dt['qty_out'] = 0;
        }

        $dt['type_sub'] = "Stock";

        $product = $dt['product'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$product'");
        $dt['product_text'] = strval($query[0]['name']);
        $dt['brand'] = strval($query[0]['brand']);

        if ($_FILES['file']['name']) {
            $input = $this->validate([
                'file' => [
                    'uploaded[file]',
                    'mime_in[file,image/jpg,image/jpeg,image/png]',
                    'max_size[file,1024]',
                ]
            ]);

            if (!$input) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $img = $this->request->getFile('file');
                $dir = str_replace('public/', '', FCPATH . 'assets/img/stock/');
                $img->move($dir);
                $data = [
                    'name' =>  $img->getName(),
                    'type'  => $img->getClientMimeType()
                ];
                $currentFileName = $dir . $data['name'];
                $newfile = DATE('Ymdhis') . '.' . substr(strrchr($data['name'], "."), 1);
                $newFileName = $dir . $newfile;
                rename($currentFileName, $newFileName);
                $dt['img'] = $newfile;
            }
        }

        if ($this->db->insert('stock', $dt)) {

            $this->update_stock($dt['product']);

            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }


    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $data['data']['product'] = $_GET['product'];
        $data['data']['type'] = $_GET['type'];
        $data['data']['type_sub'] = $_GET['type_sub'];
        $data['data']['id_trx'] = $_GET['id_trx'];
        $this->load->view("stock/delete", $data);
    }

    public function delete()
    {


        $id = $_POST['id'];
        $id_product = $_POST['id_product'];
        $id_trx = $_POST['id_trx'];

        if ($_POST['type_sub'] == "POS" && $_POST['type'] == "In") {
            $dt = array();
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['return_at'] = "";
            $this->db->update('transaction', $dt, array('id' => $id_trx));
        }


        $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'In' ");

        if ($this->db->delete('stock', array('id' => $id))) {
            $this->update_stock($id_product);

            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function update_stock($id_product)
    {

        $dtp = array();
        $id_product = $id_product;
        $query = $this->mymodel->selectWithQuery("SELECT SUM(qty_in) as qty_in,SUM(qty_in_pos) as qty_in_pos,SUM(qty_out) as qty_out,SUM(qty_out_pos) as qty_out_pos,SUM(qty) as qty FROM stock WHERE product = '$id_product'");

        $dtp['stock_in'] = strval($query[0]['qty_in']);
        $dtp['stock_in_pos'] = strval($query[0]['qty_in_pos']);
        $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
        $dtp['stock_out_pos'] = strval(abs($query[0]['qty_out_pos']) * -1);
        $dtp['stock'] = strval(doubleval($query[0]['qty']));
        $this->db->update('product', $dtp, array('id' => $id_product));
    }

    function sync()
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
}
