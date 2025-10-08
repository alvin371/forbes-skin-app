<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Meta_account extends CI_Controller
{
    public function index()
    {
        $data['template'] = $this->template;
        $qry = "";

        $keyword = isset($_GET['keyword']) ? $this->db->escape_like_str($_GET['keyword']) : '';
        if ($keyword) {
            $qry = "account_name LIKE '%$keyword%'";
        } else {
            $qry = "1";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM ads_meta_account WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/meta-account/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("meta_account/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        $qry = "";

        $keyword = isset($_GET['keyword']) ? $this->db->escape_like_str($_GET['keyword']) : '';
        if ($keyword) {
            $qry = "account_name LIKE '%$keyword%'";
        } else {
            $qry = "1";
        }

        $limit = 30;

        $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = ($current_page <= 1) ? 0 : ($current_page - 1) * $limit;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM ads_meta_account WHERE $qry ORDER BY created_at DESC LIMIT $offset, $limit");

        $data['data'] = $query;
        $data['start'] = $offset;
        $this->load->view("meta_account/item", $data);
    }

    public function edit()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM ads_meta_account WHERE id = '$id'");

        $data['data'] = $query[0];


        $this->load->view("meta_account/edit", $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        if ($this->db->update('ads_meta_account', $dt, array('id' => $id))) {
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

        $this->load->view("meta_account/create", $data);
    }

    public function store()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");

        if ($this->db->insert('ads_meta_account', $dt)) {
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
        $this->load->view("meta_account/delete", $data);
    }

    public function delete()
    {
        $id = $_POST['id'];

        if ($this->db->delete('ads_meta_account', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
}
