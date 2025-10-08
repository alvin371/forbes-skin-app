<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Quest_level extends BaseController
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
            'remove' => 'delete'
        ]);
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        
        // Permission check handled by BaseController middleware

        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        
        $data['keyword_category'] = $keyword_category;
        $data['title'] = 'Quest Level - ' . $this->template->title();

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND name LIKE '%$keyword%'";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM quest_levels WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/quest_level/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("quest_level/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND name LIKE '%$keyword%'";
            }
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM quest_levels WHERE $qry ORDER BY level_order ASC LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("quest_level/item", $data);
    }

    public function create_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $data['data'] = array();
        $data['title'] = 'Tambah Quest Level - ' . $this->template->title();
        $data['content'] = $this->load->view("quest_level/create_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function store()
    {
        $user = $_SESSION['user'];
        $dt = $_POST['dt'];

        $name = $dt['name'];
        $level_order = $dt['level_order'];
        
        // Check if name already exists
        $existing = $this->mymodel->selectWithQuery("SELECT id FROM quest_levels WHERE name = '$name'");
        if ($existing) {
            $msg = 'Nama level sudah ada!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // Check if level_order already exists
        $existing_order = $this->mymodel->selectWithQuery("SELECT id FROM quest_levels WHERE level_order = '$level_order'");
        if ($existing_order) {
            $msg = 'Urutan level sudah ada! Gunakan nomor urutan yang berbeda.';
            echo $this->template->alert_danger($msg);
            die;
        }

        if ($this->db->insert('quest_levels', $dt)) {
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function edit_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM quest_levels WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'quest_level');
        }

        $data['data'] = $query[0];
        $data['title'] = 'Edit Quest Level - ' . $this->template->title();
        $data['content'] = $this->load->view("quest_level/edit_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $dt = $_POST['dt'];

        $name = $dt['name'];
        $level_order = $dt['level_order'];
        
        // Check if name already exists for other records
        $existing = $this->mymodel->selectWithQuery("SELECT id FROM quest_levels WHERE name = '$name' AND id != '$id'");
        if ($existing) {
            $msg = 'Nama level sudah ada!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // Check if level_order already exists for other records
        $existing_order = $this->mymodel->selectWithQuery("SELECT id FROM quest_levels WHERE level_order = '$level_order' AND id != '$id'");
        if ($existing_order) {
            $msg = 'Urutan level sudah ada! Gunakan nomor urutan yang berbeda.';
            echo $this->template->alert_danger($msg);
            die;
        }

        if ($this->db->update('quest_levels', $dt, array('id' => $id))) {
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function detail()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM quest_levels WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'quest_level');
        }

        $data['data'] = $query[0];
        
        // Get positions associated with this level
        $positions = $this->mymodel->selectWithQuery("SELECT * FROM positions WHERE level_id = '$id' ORDER BY name ASC");
        $data['positions'] = $positions;
        
        $data['title'] = 'Detail Quest Level - ' . $this->template->title();
        $data['content'] = $this->load->view("quest_level/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("quest_level/delete", $data);
    }

    public function delete()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];

        // Check if this level is being used by positions
        $positions = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM positions WHERE level_id = '$id'");
        if ($positions[0]['count'] > 0) {
            $msg = 'Level tidak dapat dihapus karena sedang digunakan oleh posisi!';
            echo $this->template->alert_danger($msg);
            die;
        }

        if ($this->db->delete('quest_levels', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
}