<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Testimoni extends CI_Controller
{
    public function index()
    {

        $data['title'] = 'Testimoni  - ' . $this->template->title();
        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-01');
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

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $brand = $_GET['brand'];

        $qry = "";
        $qry = " DATE(created_at) >= '$start_date'
        AND DATE(created_at) <= '$until_date' ";

        if ($keyword) {
            $qry .= " AND (full_name LIKE '%$keyword%' OR phone LIKE '%$keyword%' OR username LIKE '%$keyword%') ";
        }

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND id_2 = '$cs' ";
        }


        $qry = "";
        $qry = " DATE(created_at) >= '$start_date'
        AND DATE(created_at) <= '$until_date' ";

        if ($keyword) {
            $qry .= " AND (full_name LIKE '%$keyword%' OR phone LIKE '%$keyword%' OR username LIKE '%$keyword%') ";
        }

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND id_2 = '$cs' ";
        }

        $limit = 30;

        $offset = intval($_GET['offset']);

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM testimoni
        WHERE $qry ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        if ($query[0]['count'] > 0) {
            $data['notif'] = '<p><label>' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';
        } else {
            // $data['notif'] = '<p>Data tidak ditemukan!</p>';
        }

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/testimoni?keyword=' . $_GET['keyword'] . '&brand=' . $_GET['brand'] . '&marketplace=' . $_GET['marketplace'] . '&cs=&start_date=' . $start_date . '&until_date=' . $until_date;

        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $url);




        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'ENABLE' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role = '3' 
        ORDER BY full_name ASC
        ");

        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'ENABLE' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $data['content'] = $this->load->view("testimoni/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function detail()
    {

        $id = $_GET['id'];
        $brand = $_GET['brand'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM testimoni
        WHERE id = '$id'");

        $data['testimoni'] = $query[0];
        if (empty($data['testimoni'])) {
            return redirect(base_url() . 'testimoni');
        }
        $data['title'] = 'Detail CRM - ' . $this->template->title();
        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-01');
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

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $brand = $_GET['brand'];
        $id = $_GET['id'];

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        if ($id) {
            $qry .= " AND testimoni = '$id' ";
        }

        if ($keyword) {
            $qry .= " AND (testimoni_text LIKE '%$keyword%' OR c_username LIKE '%$keyword%' OR c_username LIKE '%$keyword%' OR id_trx LIKE '%$keyword%') ";
        }

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND cs = '$cs' ";
        }



        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM transaction
        WHERE $qry AND type_sub = 'POS' ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        if ($query[0]['count'] > 0) {
            $data['notif'] = '<p><label>' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';
        } else {
            // $data['notif'] = '<p>Data tidak ditemukan!</p>';
        }

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $limit = 30;

        $url = base_url() . '/testimoni/detail?id=' . $id . '&brand=' . $_GET['brand'] . '&keyword=' . $_GET['keyword'] . '&marketplace=' . $_GET['marketplace'] . '&cs=&start_date=' . $start_date . '&until_date=' . $until_date;


        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $url);




        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'ENABLE' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role = '3' 
        ORDER BY full_name ASC
        ");

        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'ENABLE' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $data['content'] = $this->load->view("testimoni/detail", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {

        $data['template'] = $this->template;
        $data['brand'] = $_GET['brand'];
        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-01');
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $keyword = $_GET['keyword'];

        $id = $_GET['id_customer'];

        $qry = " 1 = 1 ";

        if ($id) {
            $qry .= " AND customer = '$id' ";
        }

        $limit = 30;

        $current_page = $_GET['page'];

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        // $query = $this->mymodel->selectWithQuery("
        // SELECT * FROM testimoni
        // WHERE customer = '$id'
        // ORDER BY date ASC, id ASC
        // LIMIT $offset, $limit
        // ");
        $query = $this->mymodel->selectWithQuery("
        SELECT * FROM testimoni
        WHERE customer = '$id'
        ORDER BY date ASC, id ASC
        ");



        $data['data'] = $query;

        $data['start'] = $offset;

        $this->load->view("testimoni/item", $data);
    }



    public function edit()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM testimoni WHERE id = '$id'");

        $data['data'] = $query[0];
        $data['data']['brand'] = $_GET['brand'];
        $this->load->view("testimoni/edit", $data);
    }

    public function update()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");

        $dt['updated_by'] = $user['id'];
        if (!empty($_FILES['file_before']['name'])) {
            $dir  = "./assets/img/testimoni/";
            $config['upload_path']   = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite']     = TRUE;
            $config['file_name']     = $id . '-before';
            $config['max_size']      = 2048;
            $this->load->library('upload', $config);
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('file_before')) {
                $error = $this->upload->display_errors();
                echo $this->template->alert_danger($error);
                die;
            } else {
                $file = $this->upload->data();
                $dt['img_before'] = $file['file_name'];
            }
        }

        if (!empty($_FILES['file_after']['name'])) {
            $dir  = "./assets/img/testimoni/";
            $config['upload_path']   = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite']     = TRUE;
            $config['file_name']     = $id . '-after';
            $config['max_size']      = 2048;
            $this->load->library('upload', $config);
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('file_after')) {
                $error = $this->upload->display_errors();
                echo $this->template->alert_danger($error);
                die;
            } else {
                $file = $this->upload->data();
                $dt['img_after'] = $file['file_name'];
            }
        }




        if ($this->db->update('testimoni', $dt, array('id' => $id))) {

            $id_customer = $_POST['id_customer'];
            $this->refresh_testimoni($id_customer);


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
        $data['data']['id'] = $_GET['id'];
        $data['data']['brand'] = $_GET['brand'];

        $this->load->view("testimoni/create", $data);
    }


    public function store()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['customer'] = $id;
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        if (!empty($_FILES['file_before']['name'])) {
            $dir  = "./assets/img/testimoni/";
            $config['upload_path']   = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite']     = TRUE;
            $config['file_name']     = DATE("Ymdhis") . '-before';
            $config['max_size']      = 2048;
            $this->load->library('upload', $config);
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('file_before')) {
                $error = $this->upload->display_errors();
                echo $this->template->alert_danger($error);
                die;
            } else {
                $file = $this->upload->data();
                $dt['img_before'] = $file['file_name'];
            }
        }

        if (!empty($_FILES['file_after']['name'])) {
            $dir  = "./assets/img/testimoni/";
            $config['upload_path']   = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite']     = TRUE;
            $config['file_name']     = DATE("Ymdhis") . '-after';
            $config['max_size']      = 2048;
            $this->load->library('upload', $config);
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('file_after')) {
                $error = $this->upload->display_errors();
                echo $this->template->alert_danger($error);
                die;
            } else {
                $file = $this->upload->data();
                $dt['img_after'] = $file['file_name'];
            }
        }




        if ($this->db->insert('testimoni', $dt)) {

            $id_customer = $_POST['id'];
            $this->refresh_testimoni($id_customer);

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
        $this->load->view("testimoni/delete", $data);
    }

    public function delete()
    {


        $id = $_POST['id'];
        $data = $this->mymodel->selectWithQuery("SELECT customer
        FROM testimoni WHERE id = '$id' ");
        $data = $data[0];



        if ($this->db->delete('testimoni', array('id' => $id))) {
            $id_customer = $data['customer'];
            $this->refresh_testimoni($id_customer);
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
    function refresh_testimoni($id_customer)
    {
        $data = $this->mymodel->selectWithQuery("SELECT * FROM testimoni
        WHERE customer = '$id_customer'
        ORDER BY date ASC");
        $dt = array();
        $dt['testimoni'] = json_encode($data, true);
        $this->db->update('customer', $dt, array('id' => $id_customer));
    }
}
