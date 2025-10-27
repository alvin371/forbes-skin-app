<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once __DIR__ . '/../../application/helpers/env_helper.php';
class Marketplace_account extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->helper('env');
        $this->tiktok_service_id = env('TIKTOK_SERVICE_ID', '');
    }
    public function index()
    {
        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Toko";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'] ?? '';

        $status = $_GET['status'] ?? 'active';
        $data['status'] = $status;

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

        $data['title'] = 'Channel - ' . $this->template->title();

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'Aktif' ORDER BY name ASC");
        $data['brands'] = $query;

        $qry = "1=1";

        if ($keyword) {
            if ($keyword_category == "Nama Toko") {
                $qry .= " AND shop_name LIKE '%$keyword%' ";
            } else if ($keyword_category == "ID Toko") {
                $qry .= " AND shop_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Channel") {
                $qry .= " AND opt LIKE '%$keyword%' ";
            }
        }

        $status_value = ($status == 'active') ? 'Aktif' : 'Nonaktif';
        $qry .= " AND status = '$status_value' ";

        $total_active = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM marketplace_config WHERE status = 'Aktif'")[0]['count'];
        $total_inactive = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM marketplace_config WHERE status = 'Nonaktif'")[0]['count'];

        $data['total_active'] = $total_active;
        $data['total_inactive'] = $total_inactive;

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM marketplace_config WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/marketplace_account/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("marketplace_account/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;

        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Toko";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'] ?? '';

        $status = $_GET['status'] ?? 'active';

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

        $qry = "1=1";

        if ($keyword) {
            if ($keyword_category == "Nama Toko") {
                $qry .= " AND shop_name LIKE '%$keyword%' ";
            } else if ($keyword_category == "ID Toko") {
                $qry .= " AND shop_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Channel") {
                $qry .= " AND opt LIKE '%$keyword%' ";
            }
        }

        $status_value = ($status == 'active') ? 'Aktif' : 'Nonaktif';
        $qry .= " AND status = '$status_value' ";

        $limit = 30;
        $current_page = $_GET['page'] ?? 1;

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace_config
        WHERE $qry 
        ORDER BY shop_name ASC
        LIMIT $offset, $limit
        ");

        $data['data'] = $query;
        $data['start'] = $offset;
        $this->load->view("marketplace_account/item", $data);
    }

    public function update_status()
    {
        $id = $this->input->post('id');
        $status = $this->input->post('status');
        
        if (!in_array($status, ['Aktif', 'Nonaktif'])) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
            return;
        }
        
        $this->mymodel->updateData('marketplace_config', ['status' => $status], ['id' => $id]);
        
        echo json_encode(['success' => true]);
        return;
    }

    public function sync()
    {

        $data = $this->template->get_session('filter');

        $this->load->view("marketplace_account/sync", $data);
    }

    public function sync_process()
    {
        $filter = $this->template->get_session('filter');
        $start_date = $filter['start_date'] . ' 00:00:00';
        $until_date = $filter['until_date'] . ' 00:00:00';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => base_url() . '/api/shopee/get-product',
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

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => base_url() . '/api/lazada/get-product',
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

        $response = str_replace('SuccessToken', '', $response);

        if ($response == "Success") {
            $msg = 'Sync data berhasil!';
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = $response;
            echo $this->template->alert_danger($msg);
            die;
        }
    }


    public function item_stock()
    {


        $id = $_GET['id'];

        $qry = "";
        $qry = " marketplace = '$id' ";

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
                WHERE marketplace = '$id'
                ORDER BY date ASC, id ASC
                LIMIT 0, $offset
            ) AS subquery");

            $data['balance'] = $query[0]['sum'];
        }

        $data['start'] = $offset;
        $this->load->view("marketplace_account/item_stock", $data);
    }



    public function stock()
    {

        $data['title'] = 'Product Stock - ' . $this->template->title();

        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace WHERE id = '$id'");

        $data['data'] = $query[0];

        if (empty($data['data'])) {
            return redirect()->to(site_url('stock'));
        }

        $qry = "";
        $qry = " marketplace = '$id' ";


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

        $url = base_url() . '/marketplace_account/stock?id=' . $_GET['id'];

        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $url);

        $data['content'] = $this->load->view("marketplace_account/stock", $data);
        $this->load->view("TemplateDashboard", $data);
    }

    public function edit()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace_config WHERE id = '$id'");

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
        $this->load->view("marketplace_account/edit", $data);
    }

    public function update()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = array();
        $dtt = $_POST['dtt'];
        $dttt = array();
        foreach ($dtt as $k => $v) {
            $dttt[] = $v;
        }
        // $dt['configuration'] = json_encode($dttt, true);

        if (!empty($_FILES['file']['name'])) {
            $dir  = "./assets/img/marketplace_account/";
            $config['upload_path']   = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite']     = TRUE;
            $config['file_name']     = $id;
            $config['max_size']      = 2048;
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

        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        if ($this->db->update('marketplace_config', $dt, array('id' => $id))) {
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function create()
    {
        $data['data'] = array();

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY name aSC");

        $data['brand'] = $query;

        $this->load->view("marketplace_account/create", $data);
    }


    public function store()
    {
        $dt = $_POST['dt'];
        $app_key = $dt['app_key'];
        $_SESSION['store'] = $dt;
        if ($dt['channel'] == "tiktok") {
        } else if ($dt['channel'] == "shopee") {
        } else {
            $url = 'https://app.acnenosystem.com/api/auth/lazada';
            // $url = base_url() . 'api/auth/lazada';
            $url = 'https://auth.lazada.com/oauth/authorize?response_type=code&force_auth=true&redirect_uri=' . $url . '&client_id=' . $app_key;
            return redirect($url);
        }
    }



    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("marketplace_account/delete", $data);
    }

    public function delete()
    {


        $id = $_POST['id'];


        if ($this->db->delete('marketplace', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
    public function refresh_token_process()
    {
        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $shop_id = $dt['shop_id'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->template->endpoint_url() . 'api/marketplace/token/refresh?marketplace=' . $marketplace . '&shop_id=' . $shop_id,
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
    public function auth()
    {

        $type = $_GET['type'];
        if ($type == "TIKTOK") {
            $service_id = $this->tiktok_service_id;
            $url = "https://services.tiktokshop.com/open/authorize?service_id=$service_id";
            redirect($url);
        } else  if ($type == "LAZADA") {

            $this->app_key_lazada = '128067';
            $this->app_secret_lazada = '3HXnltTtHmEqK50hNMnt8MkCur4WTsod';

            $app_key = $this->app_key_lazada;
            $redirectUrl = base_url() . 'api/marketplace/callback/lazada';
            $redirectUrl = 'https://app.acnenosystem.com/api/marketplace/callback/lazada';

            $url = 'https://auth.lazada.com/oauth/authorize?response_type=code&force_auth=true&redirect_uri=' . $redirectUrl . '&client_id=' . $app_key;
            redirect($url);
        } else  if ($type == "SHOPEE") {

            $this->partner_id_shopee = '2007315';
            $this->partner_key_shopee = '6a53474c517262526c7252416c79446d794a59736b7766747461644b6762636f';

            $partner_id = $this->partner_id_shopee;
            $partner_key = $this->partner_key_shopee;
            $host = 'https://partner.shopeemobile.com';

            $path = "/api/v2/shop/auth_partner";
            $redirectUrl = base_url() . "api/marketplace/callback/shopee";
            $timest = time();
            $baseString = sprintf("%s%s%s", $partner_id, $path, $timest);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s&redirect=%s", $host, $path, $partner_id, $timest, $sign, $redirectUrl);

            return redirect($url);
        } else {
            redirect(base_url() . 'marketplace-account');
        }
    }
}
