<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Product_3rd extends CI_Controller
{
    public function index()
    {

        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Produk";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'];

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

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;

        $data['title'] = 'Konfigurasi Produk - ' . $this->template->title();

        $data['store'] = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace FROM marketplace_config WHERE status = 'Aktif' ORDER BY marketplace DESC, shop_name ASC");

        $qry = "";
        $qry = " 1=1 ";

        $marketplace = $_GET['marketplace'];
        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }
        $shop_id = $_GET['shop_id'];
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Produk") {
                $qry .= " AND name LIKE '%$keyword%' ";
            } else if ($keyword_category == "SKU Produk") {
                $qry .= " AND sku LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Varian") {
                $qry .= " AND json_varian LIKE '%$keyword%' ";
            } else if ($keyword_category == "SKU Varian") {
                $qry .= " AND json_varian LIKE '%$keyword%' ";
            } else if ($keyword_category == "Marketplace") {
                $qry .= " AND marketplace LIKE '%$keyword%' ";
            }
        }


        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
        FROM product_3rd
        WHERE $qry
        ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/product-3rd/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("product_3rd/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {

        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Produk";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'];

        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-d');
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $qry = "";
        $qry = " 1=1 ";

        $marketplace = $_GET['marketplace'];
        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }
        $shop_id = $_GET['shop_id'];
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Produk") {
                $qry .= " AND name LIKE '%$keyword%' ";
            } else if ($keyword_category == "SKU Produk") {
                $qry .= " AND sku LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Varian") {
                $qry .= " AND json_varian LIKE '%$keyword%' ";
            } else if ($keyword_category == "SKU Varian") {
                $qry .= " AND json_varian LIKE '%$keyword%' ";
            } else if ($keyword_category == "Marketplace") {
                $qry .= " AND marketplace LIKE '%$keyword%' ";
            }
        }

        $limit = 30;

        $current_page = $_GET['page'];

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product_3rd
        WHERE $qry 
        ORDER BY name ASC, name ASC
        LIMIT $offset, $limit
        ");


        $data['data'] = $query;

        $data['start'] = $offset;
        $this->load->view("product_3rd/item", $data);
    }


    public function sync()
    {

        $data = $_SESSION['filter'];

        $data['store'] = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace, shop_code FROM marketplace_config WHERE status = 'Aktif' ORDER BY marketplace DESC, shop_name ASC");

        $this->load->view("product_3rd/sync", $data);
    }

    public function sync_process()
    {
        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $shop_id = $dt['shop_id'];


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => base_url() . 'api/marketplace/product?marketplace=' . $marketplace . '&shop_id=' . $shop_id,
            // CURLOPT_URL => $this->template->endpoint_url() . 'api/marketplace/product?marketplace=' . $marketplace . '&shop_id=' . $shop_id,
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

    public function sync_all_product()
    {
        // Ambil semua toko yang aktif dari database
        $stores = $this->mymodel->selectWithQuery("
            SELECT * FROM marketplace_config
            WHERE status = 'Aktif'
        ");

        // Jika tidak ada toko aktif
        if (empty($stores)) {
            echo 'Tidak ada toko aktif yang ditemukan';
            die;
        }

        $successCount = 0;
        $errorCount = 0;
        $results = [];

        foreach ($stores as $store) {
            $marketplace = $store['opt'];
            $shop_id = $store['shop_id'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => base_url() . 'api/marketplace/product?marketplace=' . $marketplace . '&shop_id=' . $shop_id,
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
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response = json_decode($response, true);

            if ($httpCode == 200 && isset($response['status']) && $response['status'] == true) {
                $successCount++;
                $results[] = "SUCCESS: {$marketplace} (Shop ID: {$shop_id}) - " . $response['msg'];
            } else {
                $errorCount++;
                $errorMsg = $response['msg'] ?? 'Unknown error';
                $results[] = "ERROR: {$marketplace} (Shop ID: {$shop_id}) - " . $errorMsg;
            }

            // Beri jeda 3 detik antar request untuk menghindari rate limit
            sleep(3);
        }

        // Hasil akhir
        $output = "Sync All Process Completed\n";
        $output .= "Total Toko: " . count($stores) . "\n";
        $output .= "Berhasil: {$successCount}\n";
        $output .= "Gagal: {$errorCount}\n";
        $output .= "====================\n";
        $output .= implode("\n", $results);

        echo "<pre>" . $output . "</pre>";
        die;
    }

    public function item_stock()
    {


        $id = $_GET['id'];

        $qry = "";
        $qry = " product_3rd = '$id' ";

        $limit = 30;

        $current_page = $_GET['page'];

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("
        SELECT * FROM stock
        WHERE $qry 
        ORDER BY date ASC, id ASC
        LIMIT $offset, $limit
        ");

        $data['data'] = $query;
        $data['balance'] = 0;
        if ($current_page > 1) {
            $query = $this->mymodel->selectWithQuery("SELECT sum(qty) as sum
            FROM (
                SELECT qty
                FROM stock
                WHERE product_3rd = '$id'
                ORDER BY date ASC, id ASC
                LIMIT 0, $offset
            ) AS subquery");

            $data['balance'] = $query[0]['sum'];
        }

        $data['start'] = $offset;
        $this->load->view("product_3rd/item_stock", $data);
    }



    public function stock()
    {

        $data['title'] = 'Product Stock - ' . $this->template->title();

        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product_3rd WHERE id = '$id'");

        $data['data'] = $query[0];

        if (empty($data['data'])) {
            return redirect()->to(site_url('stock'));
        }

        $qry = "";
        $qry = " product_3rd = '$id' ";


        $limit = 30;

        $offset = intval($_GET['offset']);

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM stock
        WHERE $qry ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/product_3rd/stock?id=' . $_GET['id'];

        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $url);

        $data['content'] = $this->load->view("product_3rd/stock", $data);
        $this->load->view("TemplateDashboard", $data);
    }

    public function edit()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product_3rd WHERE id = '$id'");

        $data['data'] = $query[0];

        $id_parent = $id;
        $data['item'] = $this->mymodel->selectWithQuery("SELECT * FROM product_variant_3rd WHERE id_parent = '$id_parent'
        ORDER BY name ASC
        ");

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0 ORDER BY name ASC");

        $data['product'] = $query;

        $text = '';
        foreach ($data['product'] as $k => $v) {
            $text .= '<option value="' . $v['id'] . '">' . $v['sku'] . ' | ' . $v['name'] . '</option>';
        }
        $data['opt_product'] = $text;
        $this->load->view("product_3rd/edit", $data);
    }

    public function update()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = array();
        $dtt = $_POST['dtt'];
        $dt_id = $_POST['dt_id'];
        $dt_sku = $_POST['dt_sku'];
        foreach ($dt_id as $k2 => $v2) {
            $id_variant = $v2;
            $dttt = array();
            $sku = $dt_sku[$k2];
            foreach ($dtt[$v2] as $k => $v) {
                $dttt[$k] = $v;
                $id_product = $v['product'];

                $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id_product'");

                $dttt[$k]['product_text'] = strval($query[0]['name']);
                $dttt[$k]['brand'] = strval($query[0]['brand']);
                // echo $k;
            }
            $dtt_fix = array();
            $dtt_fix['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt_fix['updated_by'] = $user['id'];
            $dtt_fix['brand'] = $query[0]['brand'];
            $dtt_fix['json'] = json_encode($dttt, true);

            if ($sku) {
                $this->db->update('product_variant_3rd', $dtt_fix, array('sku' => $sku));
            } else {
                $this->db->update('product_variant_3rd', $dtt_fix, array('id' => $id_variant));
            }
        }

        $dt = array();
        $dt['brand'] = strval($dtt_fix['brand']);

        $this->db->update('product_3rd', $dt, array('id' => $id));

        $msg = 'Update data berhasil!';
        echo $this->template->alert_success($msg);
    }

    function query()
    {

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
        ORDER BY id ASC
        ");
        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }


        $data = $this->mymodel->selectWithQuery("SELECT * FROM product_variant_3rd
        ");
        $id = 0;
        foreach ($data as $k => $v) {
            $id = $v['id_parent'];
            $dt = array();
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $json = json_decode($v['json'], true);

            $dt['brand'] = '';
            foreach ($json as $k2 => $v2) {
                $dt['brand'] = $arr_product[$v2['product']]['brand'];
            }
            $this->db->update('product_variant_3rd', $dt, array('id' => $v['id']));
            $dt_2 = array();
            $dt_2['brand'] = strval($dt['brand']);
            $this->db->update('product_3rd', $dt_2, array('id' => $id));
        }
    }

    public function create()
    {
        $data['data'] = array();

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY name aSC");

        $data['brand'] = $query;

        $this->load->view("product_3rd/create", $data);
    }


    public function store()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];

        // $brand = $dt['brand'];
        //
        // $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE id = '$brand'");
        // 
        // $dt['brand_text'] = strval($query[0]['name']);

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
                $dir = str_replace('public/', '', FCPATH . 'assets/img/product_3rd/');
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


        if ($this->db->insert('product_3rd', $dt)) {
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
        $this->load->view("product_3rd/delete", $data);
    }

    public function delete()
    {

        $id = $_POST['id'];
        if ($this->db->delete('product_3rd', array('id' => $id))) {
            $this->db->delete('product_variant_3rd', array('id_parent' => $id));
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }


    public function remove_item()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("product_3rd/delete_item", $data);
    }

    public function delete_item()
    {

        $id = $_POST['id'];
        if ($this->db->delete('product_variant_3rd', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
}
