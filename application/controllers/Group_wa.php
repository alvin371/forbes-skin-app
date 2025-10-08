<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';
class Group_wa extends BaseController
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
            'action' => 'edit'
        ]);
    }


    public function index()
    {

        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Group";
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
        $data['brand'] = $brand;

        $data['title'] = 'Group WA - ' . $this->template->title();





        $qry = "";
        $qry = " 1=1 ";

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        if ($group_wa) {
            $qry .= " AND group_wa = '$group_wa' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Group") {
                $qry .= " AND name LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND group_wa.desc LIKE '%$keyword%' ";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
        FROM group_wa
        WHERE $qry
        ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/group-wa/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("group_wa/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {

        $data['template'] = $this->template;

        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Group";
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

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        if ($group_wa) {
            $qry .= " AND group_wa = '$group_wa' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Group") {
                $qry .= " AND name LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND group_wa.desc LIKE '%$keyword%' ";
            }
        }

        $limit = 30;

        $current_page = $_GET['page'];

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM group_wa
        WHERE $qry 
        ORDER BY CAST(name AS SIGNED) ASC
        LIMIT $offset, $limit
        ");


        $data['data'] = $query;

        $data['start'] = $offset;
        $this->load->view("group_wa/item", $data);
    }

    public function edit()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM group_wa WHERE id = '$id'");

        $data['data'] = $query[0];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user ORDER BY full_name ASC");

        $data['pic'] = $query;

        $this->load->view("group_wa/edit", $data);
    }

    public function update()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];



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
                $dir = str_replace('public/', '', FCPATH . 'assets/img/group_wa/');
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

        if ($this->db->update('group_wa', $dt, array('id' => $id))) {
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

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user ORDER BY full_name ASC");

        $data['pic'] = $query;

        $this->load->view("group_wa/create", $data);
    }


    public function store()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];



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
                $dir = str_replace('public/', '', FCPATH . 'assets/img/group_wa/');
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


        if ($this->db->insert('group_wa', $dt)) {
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
        $this->load->view("group_wa/delete", $data);
    }

    public function delete()
    {


        $id = $_POST['id'];

        if ($this->db->delete('group_wa', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
}
