<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('template');
        $this->load->model('mymodel');
        $this->load->helper('url');
        $this->load->library('form_validation');
        $this->load->library('permission');
    }

    public function index()
    {
        redirect(base_url() . 'auth/login');
    }

    public function login()
    {
        $data['title'] = 'Login - ' . $this->template->title();
        $query = $this->mymodel->selectWithQuery("SELECT * FROM banner WHERE status = 'ENABLE' ORDER BY title ASC");
        $data['data'] = $query;
        $data['content'] = $this->load->view('Login', $data, true);
        $this->load->view('Template', $data);
    }

    public function signup()
    {
        $data['title'] = 'Sign Up - ' . $this->template->title();
        $data['content'] = $this->load->view('Signup', $data, true);
        $this->load->view('Template', $data);
    }

    public function update_process()
    {
        $user = $_SESSION['user'];

        $id = $user['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

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

        $other_user = $this->mymodel->selectWithQuery("SELECT id FROM user
        WHERE email = '$email' AND id != '$id' ");

        if ($other_user) {
            $msg = 'Email sudah digunakan user lain!';
            echo $this->template->alert_danger($msg);
            die;
        }

        unset($dt['role']);

        if (!empty($_FILES['file']['name'])) {
            $dir  = "./assets/img/user/";
            $config['upload_path']   = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite']     = TRUE;
            $config['file_name']     = $_SESSION['user']['id'];
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


            $model = $this->mymodel->selectDataOne('user', array('id' => $id));

            $_SESSION['is_login'] = true;
            $_SESSION['user'] = $model;

            $msg = 'Update data was successful!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data failed!';
            echo $this->template->alert_danger($msg);
        }
    }

    private function validate_password($password)
    {
        // Minimum 8 characters
        if (strlen($password) < 8) {
            return "Password must be at least 8 characters long.";
        }
        
        // Must contain uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return "Password must contain at least one uppercase letter.";
        }
        
        // Must contain lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return "Password must contain at least one lowercase letter.";
        }
        
        // Must contain number
        if (!preg_match('/[0-9]/', $password)) {
            return "Password must contain at least one number.";
        }
        
        // Must contain special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return "Password must contain at least one special character (!@#$%^&*(),.?\":{}|<>).";
        }
        
        return true;
    }

    private function get_user_default_page($user)
    {
        $user_id = $user['id'];
        $user_role = $user['role'];
        
        // Simple role-based redirect with fallbacks - more reliable
        
        // Super Admin and Admin (roles 1, 2)
        if (in_array($user_role, array('1', '2'))) {
            // Try dashboard first
            return base_url();
        }
        
        // HR role (role 7)
        if ($user_role == '7') {
            // Try user management first, then fallback to profile
            try {
                if ($this->permission->has_module_access($user_id, 'user')) {
                    return base_url() . 'user';
                }
            } catch (Exception $e) {
                // Fallback
            }
            return base_url() . 'profile';
        }
        
        // Marketing roles (3, 4)
        if (in_array($user_role, array('3', '4'))) {
            // Try influencer management first
            try {
                if ($this->permission->has_module_access($user_id, 'influencer')) {
                    return base_url() . 'influencer';
                }
            } catch (Exception $e) {
                // Fallback
            }
            // Try CRM as second option
            try {
                if ($this->permission->has_module_access($user_id, 'crm')) {
                    return base_url() . 'crm';
                }
            } catch (Exception $e) {
                // Fallback
            }
            return base_url() . 'profile';
        }
        
        // Operations role (5)
        if ($user_role == '5') {
            // Try transaction management first
            try {
                if ($this->permission->has_module_access($user_id, 'transaction')) {
                    return base_url() . 'transaction';
                }
            } catch (Exception $e) {
                // Fallback
            }
            // Try product management as second option
            try {
                if ($this->permission->has_module_access($user_id, 'product')) {
                    return base_url() . 'product';
                }
            } catch (Exception $e) {
                // Fallback
            }
            return base_url() . 'profile';
        }
        
        // Employee or Guest roles - redirect to profile
        return base_url() . 'profile';
    }

    public function get_redirect_url()
    {
        if (isset($_SESSION['login_redirect_url'])) {
            $url = $_SESSION['login_redirect_url'];
            unset($_SESSION['login_redirect_url']); // Clear it after use
            echo json_encode(['url' => $url]);
        } else {
            echo json_encode(['url' => base_url()]);
        }
    }

    public function signup_process()
    {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($full_name)) {
            $msg = 'Full name is required!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (strlen($full_name) < 2) {
            $msg = 'Full name must be at least 2 characters long!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (empty($username)) {
            $msg = 'Username is required!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (strlen($username) < 3 || strlen($username) > 50) {
            $msg = 'Username must be between 3-50 characters!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $msg = 'Username can only contain letters, numbers, and underscores!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (empty($email)) {
            $msg = 'Email is required!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Please enter a valid email address!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (empty($password)) {
            $msg = 'Password is required!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Validate password strength
        $password_validation = $this->validate_password($password);
        if ($password_validation !== true) {
            echo $this->template->alert_danger($password_validation);
            return;
        }
        
        if ($password !== $confirm_password) {
            $msg = 'Passwords do not match!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Check if username already exists
        $existing_username = $this->mymodel->selectWithQuery("SELECT id FROM user WHERE LOWER(username) = '" . strtolower($this->db->escape_str($username)) . "'");
        if (!empty($existing_username)) {
            $msg = 'Username already exists! Please choose a different username.';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Check if email already exists
        $existing_email = $this->mymodel->selectWithQuery("SELECT id FROM user WHERE LOWER(email) = '" . strtolower($this->db->escape_str($email)) . "'");
        if (!empty($existing_email)) {
            $msg = 'Email already exists! Please use a different email address.';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Get guest role ID - try to find existing guest role first
        $guest_role = $this->mymodel->selectWithQuery("SELECT id, display_name FROM roles WHERE name = 'guest' OR display_name LIKE '%guest%' OR display_name LIKE '%Guest%' ORDER BY id ASC LIMIT 1");
        
        if (empty($guest_role)) {
            // If no guest role exists, create one
            $guest_role_data = array(
                'name' => 'guest',
                'display_name' => 'Guest',
                'description' => 'Default role for new sign-ups',
                'level' => 10, // Lowest level
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            );
            
            if ($this->db->insert('roles', $guest_role_data)) {
                $guest_role_id = $this->db->insert_id();
                $guest_role_display = 'Guest';
            } else {
                $msg = 'Error creating guest role. Please try again.';
                echo $this->template->alert_danger($msg);
                return;
            }
        } else {
            $guest_role_id = $guest_role[0]['id'];
            $guest_role_display = $guest_role[0]['display_name'];
        }
        
        // Prepare user data (exclude created_by if it has foreign key constraint)
        $user_data = array(
            'full_name' => $this->db->escape_str($full_name),
            'username' => $this->db->escape_str($username),
            'email' => $this->db->escape_str($email),
            'password' => password_hash($password, PASSWORD_DEFAULT), // Use secure hashing
            'role' => $guest_role_id,
            'role_text' => $guest_role_display,
            'status' => 'Aktif',
            'created_at' => date('Y-m-d H:i:s')
            // Note: created_by omitted for self-registration to avoid foreign key issues
        );
        
        // Start transaction
        $this->db->trans_start();
        
        // Insert user
        if ($this->db->insert('user', $user_data)) {
            $user_id = $this->db->insert_id();
            
            // Assign role in RBAC system
            $role_assignment = array(
                'user_id' => $user_id,
                'role_id' => $guest_role_id,
                'assigned_at' => date('Y-m-d H:i:s')
                // Note: assigned_by omitted for self-registration (will be NULL by default)
            );
            $this->db->insert('user_roles', $role_assignment);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                $msg = 'Registration failed. Please try again.';
                echo $this->template->alert_danger($msg);
            } else {
                $msg = 'Registration successful! You can now login with your credentials.';
                echo $this->template->alert_success($msg);
            }
        } else {
            $this->db->trans_rollback();
            $msg = 'Registration failed. Please try again.';
            echo $this->template->alert_danger($msg);
        }
    }

    function login_process()
    {

        if (empty($_GET)) {
            $dt = array();
            for ($i = 0; $i <= 4; $i++) {
                $dt[$i] = 'true';
            }
            $_SESSION['checkbox_dashboard'] =  $dt;
            $dt = array();
            for ($i = 0; $i <= 7; $i++) {
                $dt[$i] = 'false';
            }
            $dt[1] = 'true';
            $_SESSION['checkbox_dashboard_campaign'] =  $dt;
        }

        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Get user by username first
        $user = $this->mymodel->selectDataOne('user', array('username' => $email));
        
        if ($user) {
            $is_valid_password = false;
            
            // Check if password is hashed with password_hash (new system)
            if (password_verify($password, $user['password'])) {
                $is_valid_password = true;
            } 
            // Check if password is MD5 hashed (old system)
            else if ($user['password'] === md5($password)) {
                $is_valid_password = true;
                
                // Upgrade password to new secure hash
                $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
                $this->db->update('user', 
                    array('password' => $new_password_hash), 
                    array('id' => $user['id'])
                );
                $user['password'] = $new_password_hash; // Update for session
            }
            
            if ($is_valid_password) {
                if ($user['status'] == "Aktif") {
                    $_SESSION['is_login'] = true;
                    $_SESSION['user'] = $user;
                    
                    // Get the appropriate redirect URL based on user permissions
                    $redirect_url = $this->get_user_default_page($user);
                    $_SESSION['login_redirect_url'] = $redirect_url;
                    
                    $msg = 'Selamat datang di ' . $this->template->title();
                    echo $this->template->alert_success($msg);
                } else {
                    $msg = 'Proses login ditolak! Pastikan akun kamu aktif!';
                    echo $this->template->alert_danger($msg);
                }
            } else {
                $msg = 'Pastikan username dan password kamu sudah benar!';
                echo $this->template->alert_danger($msg);
            }
        } else {
            $msg = 'Pastikan username dan password kamu sudah benar!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function profile()
    {
        $data['title'] = 'Profile - ' . $this->template->title();
        $user = $_SESSION['user'];
        $data['data'] = $user;
        $data['content'] = $this->load->view("Profile", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    function logout_process()
    {
        $_SESSION['is_login'] = false;
        $_SESSION['user'] = array();
        return redirect(base_url() . 'auth/login');
    }
}
