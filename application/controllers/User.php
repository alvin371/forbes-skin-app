<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';
class User extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('permission');
        $this->load->library('template');

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'remove' => 'delete',
            'sync' => 'create',
            'sync_process' => 'create',
            'action' => 'edit',
            'action_process' => 'edit'
        ]);
    }
    
    function hasDuplicates($arr)
    {
        $counts = array_count_values($arr);
        $duplicates = array_filter($counts, function ($count) {
            return $count > 1;
        });

        return count($duplicates) > 0;
    }
    public function index()
    {
        $keyword_category = isset($_GET['keyword_category']) ? $_GET['keyword_category'] : "Nama Lengkap";
        $data['keyword_category'] = $keyword_category;
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

        $start_date = isset($_GET['start_date']) && $_GET['start_date'] != "" ? $_GET['start_date'] : DATE("Y-m-01");
        $until_date = isset($_GET['until_date']) && $_GET['until_date'] != "" ? $_GET['until_date'] : DATE("Y-m-d");
        $brand = isset($_GET['brand']) ? $_GET['brand'] : '';

        $data['role'] = $this->mymodel->selectWithQuery("SELECT * FROM role");
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $data['title'] = 'User - ' . $this->template->title();

        $qry = "";
        $qry = " 1=1 ";

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        if ($status) {
            $qry .= " AND status_reach = '$status' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Lengkap") {
                $qry .= " AND LOWER(full_name) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Email") {
                $qry .= " AND LOWER(email) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND LOWER(desc) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Role") {
                $qry .= " AND LOWER(role_text) LIKE LOWER('%$keyword%') ";
            }
        }

        $data['user'] = $_SESSION['user'];
        if (in_array($data['user']['role'], array('1'))) {
            $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
            FROM user
            WHERE $qry
            ");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
            FROM user
            WHERE $qry AND role NOT IN ('1','2')
            ");
        }

        $data['page'] = CEIL($query[0]['count'] / 10);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/user/' . $this->template->get_param();
        $data['url_1'] = $this->template->get_param_without_status($url);
        $data['url_2'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("user/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {



        $data['template'] = $this->template;

        $keyword_category = isset($_GET['keyword_category']) ? $_GET['keyword_category'] : "Nama Lengkap";
        $data['keyword_category'] = $keyword_category;
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

        $start_date = isset($_GET['start_date']) && $_GET['start_date'] != '' ? $_GET['start_date'] : DATE('Y-m-d');
        $until_date = isset($_GET['until_date']) && $_GET['until_date'] != '' ? $_GET['until_date'] : DATE('Y-m-d');
        $brand = isset($_GET['brand']) ? $_GET['brand'] : '';
        $qry = "";
        $qry = " 1=1 ";

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }


        $status = isset($_GET['status']) ? $_GET['status'] : '';
        if ($status) {
            $qry .= " AND status_reach = '$status' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Lengkap") {
                $qry .= " AND LOWER(u.full_name) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Email") {
                $qry .= " AND LOWER(u.email) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND LOWER(u.desc) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Role") {
                $qry .= " AND LOWER(u.role_text) LIKE LOWER('%$keyword%') ";
            }
        }

        $limit = 10;

        $current_page = isset($_GET['page']) ? $_GET['page'] : 1;

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $data['user'] = $_SESSION['user'];

        if (in_array($data['user']['role'], array('1'))) {
            $query = $this->mymodel->selectWithQuery("SELECT u.*, up.jenis_kontrak, up.lama_kontrak FROM user u
            LEFT JOIN user_profile up ON u.id = up.user_id
            WHERE $qry
            ORDER BY u.full_name ASC
            LIMIT $offset, $limit
            ");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            $query = $this->mymodel->selectWithQuery("SELECT u.*, up.jenis_kontrak, up.lama_kontrak FROM user u
            LEFT JOIN user_profile up ON u.id = up.user_id
            WHERE $qry  AND u.role NOT IN ('1','2')
            ORDER BY u.full_name ASC
            LIMIT $offset, $limit
            ");
        }


        $data['data'] = $query;

        $data['start'] = $offset;
        $this->load->view("user/item", $data);
    }

    public function edit()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        $data['data'] = $query[0];

        $data['user'] = $_SESSION['user'];
        if (in_array($data['user']['role'], array('1'))) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM role ORDER BY id ASC");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM role WHERE id NOT IN ('1','2') ORDER BY id ASC");
        }

        $data['role'] = $query;

        $this->load->view("user/edit", $data);
    }

    public function edit_page()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        $data['data'] = $query[0];
        
        // Get user profile data
        $profile_query = $this->mymodel->selectWithQuery("SELECT * FROM user_profile WHERE user_id = '$id'");
        $data['profile'] = !empty($profile_query) ? $profile_query[0] : array();

        $data['user'] = $_SESSION['user'];
        // Use new RBAC roles table instead of old role table
        if (in_array($data['user']['role'], array('1'))) {
            // Super Admin can assign any role
            $query = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 ORDER BY display_name ASC");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            // Admin and HR can assign most roles (excluding super_admin)
            $query = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 AND name != 'super_admin' ORDER BY display_name ASC");
        } else {
            // Other users cannot assign roles
            $query = [];
        }

        $data['role'] = $query;
        
        // Get positions for profile dropdown
        $data['positions'] = $this->mymodel->selectWithQuery("SELECT p.*, ql.name as level_name FROM positions p LEFT JOIN quest_levels ql ON p.level_id = ql.id ORDER BY ql.id ASC, p.name ASC");

        $data['title'] = 'Edit User - ' . $this->template->title();
        $data['content'] = $this->load->view("user/edit_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function update()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];
        $dt['code'] = strtoupper($dt['username']);

        if ($dt['password']) {
            $dt['password'] = MD5($dt['password']);
        } else {
            unset($dt['password']);
        }

        $email = $dt['email'];
        $username = $dt['username'];

        $other_user = $this->mymodel->selectWithQuery("SELECT id FROM user
        WHERE username = '$username' AND id != '$id' ");

        if ($other_user) {
            $msg = 'Username sudah digunakan user lain!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // $other_user = $this->mymodel->selectWithQuery("SELECT id FROM user
        // WHERE email = '$email' AND id != '$id' ");

        // if($other_user){
        //     $msg = 'Email sudah digunakan user lain!';
        //     echo $this->template->alert_danger($msg);
        //     die;
        // }

        $role = $dt['role'];

        // Get role_text from new roles table
        $query = $this->mymodel->selectWithQuery("SELECT display_name FROM roles WHERE id = '$role'");
        if (!empty($query)) {
            $dt['role_text'] = strval($query[0]['display_name']);
        } else {
            // Fallback to old role table if role not found in new table
            $query = $this->mymodel->selectWithQuery("SELECT role FROM role WHERE id = '$role'");
            $dt['role_text'] = !empty($query) ? strval($query[0]['role']) : '';
        }

        if (!empty($_FILES['file']['name'])) {
            $dir  = "./assets/img/user/";
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



        if ($this->db->update('user', $dt, array('id' => $id))) {
            // Update user role in RBAC system
            // First, remove existing role assignments
            $this->db->delete('user_roles', array('user_id' => $id));
            
            // Then add the new role assignment
            $role_assignment = array(
                'user_id' => $id,
                'role_id' => $role,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => $user['id']
            );
            $this->db->insert('user_roles', $role_assignment);
            
            // Handle user profile data
            $profile_data = $_POST['profile'] ?? array();
            if (!empty($profile_data)) {
                // Handle KTP photo upload
                if (!empty($_FILES['ktp_photo']['name'])) {
                    $dir = "./assets/img/ktp/";
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $config['upload_path'] = $dir;
                    $config['allowed_types'] = 'jpg|jpeg|png';
                    $config['overwrite'] = TRUE;
                    $config['file_name'] = 'ktp_' . $id . '_' . DATE("Ymdhis");
                    $config['max_size'] = 2048;
                    $this->load->library('upload', $config);
                    if ($this->upload->do_upload('ktp_photo')) {
                        $file = $this->upload->data();
                        $profile_data['ktp_photo'] = $file['file_name'];
                    }
                }
                
                try {
                    // Check if profile exists
                    $existing_profile = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '$id'");
                    if (!empty($existing_profile)) {
                        // Update existing profile
                        $this->db->update('user_profile', $profile_data, array('user_id' => $id));
                    } else {
                        // Insert new profile
                        $profile_data['user_id'] = $id;
                        $this->db->insert('user_profile', $profile_data);
                    }
                } catch (Exception $e) {
                    // Handle database errors gracefully
                    $error_message = $e->getMessage();
                    if (strpos($error_message, 'position_id') !== false) {
                        $msg = 'Harap pilih posisi jabatan terlebih dahulu untuk melengkapi profil karyawan.';
                        echo $this->template->alert_danger($msg);
                        return;
                    } else {
                        $msg = 'Terjadi kesalahan saat menyimpan profil. Silakan coba lagi.';
                        echo $this->template->alert_danger($msg);
                        return;
                    }
                }
            }
            
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

        $data['user'] = $_SESSION['user'];
        if (in_array($data['user']['role'], array('1'))) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM role ORDER BY id ASC");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM role WHERE id NOT IN ('1','2') ORDER BY id ASC");
        }

        $data['role'] = $query;

        $this->load->view("user/create", $data);
    }

    public function create_page()
    {
        $data['data'] = array();

        $data['user'] = $_SESSION['user'];
        // Use new RBAC roles table instead of old role table
        if (in_array($data['user']['role'], array('1'))) {
            // Super Admin can assign any role
            $query = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 ORDER BY display_name ASC");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            // Admin and HR can assign most roles (excluding super_admin)
            $query = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 AND name != 'super_admin' ORDER BY display_name ASC");
        } else {
            // Other users cannot assign roles
            $query = [];
        }

        $data['role'] = $query;
        
        // Get positions for profile dropdown
        $data['positions'] = $this->mymodel->selectWithQuery("SELECT p.*, ql.name as level_name FROM positions p LEFT JOIN quest_levels ql ON p.level_id = ql.id ORDER BY ql.id ASC, p.name ASC");

        $data['title'] = 'Tambah User - ' . $this->template->title();
        $data['content'] = $this->load->view("user/create_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }


    public function store()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];
        $dt['code'] = strtoupper($dt['username']);

        if ($dt['password']) {
            $dt['password'] = MD5($dt['password']);
        } else {
            unset($dt['password']);
        }
        $email = $dt['email'];
        $username = $dt['username'];

        $other_user = $this->mymodel->selectWithQuery("SELECT id FROM user
        WHERE username = '$username' AND id != '$id' ");

        if ($other_user) {
            $msg = 'Username sudah digunakan user lain!';
            echo $this->template->alert_danger($msg);
            die;
        }

        $role = $dt['role'];

        $query = $this->mymodel->selectWithQuery("SELECT display_name FROM roles WHERE id = '$role'");
        if (!empty($query)) {
            $dt['role_text'] = strval($query[0]['display_name']);
        } else {
            $query = $this->mymodel->selectWithQuery("SELECT role FROM role WHERE id = '$role'");
            $dt['role_text'] = !empty($query) ? strval($query[0]['role']) : '';
        }
        if (!empty($_FILES['file']['name'])) {
            $dir  = "./assets/img/user/";
            $config['upload_path']   = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite']     = TRUE;
            $config['file_name']     = DATE("Ymdhis");
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

        if ($this->db->insert('user', $dt)) {
            $user_id = $this->db->insert_id();
            
            // Add user to RBAC system - assign role
            $role_assignment = array(
                'user_id' => $user_id,
                'role_id' => $role,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => $user['id']
            );
            $this->db->insert('user_roles', $role_assignment);
            
            // Handle user profile data
            $profile_data = $_POST['profile'] ?? array();
            if (!empty($profile_data)) {
                $profile_data['user_id'] = $user_id;
                
                // Handle KTP photo upload
                if (!empty($_FILES['ktp_photo']['name'])) {
                    $dir = "./assets/img/ktp/";
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $config['upload_path'] = $dir;
                    $config['allowed_types'] = 'jpg|jpeg|png';
                    $config['overwrite'] = TRUE;
                    $config['file_name'] = 'ktp_' . $user_id . '_' . DATE("Ymdhis");
                    $config['max_size'] = 2048;
                    $this->load->library('upload', $config);
                    if ($this->upload->do_upload('ktp_photo')) {
                        $file = $this->upload->data();
                        $profile_data['ktp_photo'] = $file['file_name'];
                    }
                }
                
                try {
                    $this->db->insert('user_profile', $profile_data);
                } catch (Exception $e) {
                    // Handle database errors gracefully
                    $error_message = $e->getMessage();
                    if (strpos($error_message, 'position_id') !== false) {
                        $msg = 'Harap pilih posisi jabatan terlebih dahulu untuk melengkapi profil karyawan.';
                        echo $this->template->alert_danger($msg);
                        return;
                    } else {
                        $msg = 'Terjadi kesalahan saat menyimpan profil. Silakan coba lagi.';
                        echo $this->template->alert_danger($msg);
                        return;
                    }
                }
            }
            
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function sync()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        $data['data'] = $query[0];
        $this->load->view("user/sync", $data);
    }

    public function sync_process()
    {

        $user = $_SESSION['user'];
        $id = $_POST['id'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        $v = $query[0];
        $response = $this->template->get_social_media($v['type'], $v['url']);
        $dt = array();
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];
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

        $this->db->update('user', $dt, array('id' => $v['id']));

        if ($response['status'] == true) {
            $msg = 'Sync data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            if ($response) {
                $msg = $response['msg'];
            } else {
                $msg = 'Data user belum tersedia!';
            }
            echo $this->template->alert_danger($msg);
        }
    }

    public function remove()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $data['data']['id'] = $id;
        $this->load->view("user/delete", $data);
    }

    public function delete()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];



        if ($this->db->delete('user', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
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
        $code = isset($_GET['code']) ? $_GET['code'] : '';
        $data['data']['id'] = $id;
        $data['data']['code'] = $code;
        if ($code == "hapus_data") {
            $data['question'] = "Apakah kamu yakin ingin menghapus data user ini?";
            $data['btn'] = "Hapus Data";
        }
        $this->load->view("user/action", $data);
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
        if ($code == "hapus_data") {
            foreach ($id as $k => $v) {
                $list_id .= "'" . $v . "',";
            }

            $list_id = substr($list_id, 0, -1);

            if ($list_id) {
                $dt = array();
                $this->db->delete('user', "id IN ($list_id)");
                $msg = 'Hapus data berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        }
    }

    public function detail()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        if (empty($query)) {
            redirect(base_url() . 'user');
        }

        $data['data'] = $query[0];
        
        // Get user profile data with position information
        $profile_query = $this->mymodel->selectWithQuery("SELECT up.*, p.name as position_name, ql.name as level_name FROM user_profile up LEFT JOIN positions p ON up.position_id = p.id LEFT JOIN quest_levels ql ON p.level_id = ql.id WHERE up.user_id = '$id'");
        $data['profile'] = !empty($profile_query) ? $profile_query[0] : array();

        // Get created_by and updated_by user info
        $created_by_id = $data['data']['created_by'];
        $updated_by_id = $data['data']['updated_by'];

        $created_by_query = $this->mymodel->selectWithQuery("SELECT full_name FROM user WHERE id = '$created_by_id'");
        $updated_by_query = $this->mymodel->selectWithQuery("SELECT full_name FROM user WHERE id = '$updated_by_id'");

        $data['created_by'] = !empty($created_by_query) ? $created_by_query[0] : null;
        $data['updated_by'] = !empty($updated_by_query) ? $updated_by_query[0] : null;

        $data['title'] = 'Detail User - ' . $this->template->title();
        $data['content'] = $this->load->view("user/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }
}
