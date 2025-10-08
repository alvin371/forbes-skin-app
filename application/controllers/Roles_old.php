<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Roles extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('permission');
        $this->load->library('template');
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        
        // Check if user has permission to view roles (Super Admin only)
        if (!in_array($data['user']['role'], array('1'))) {
            redirect(base_url() . 'dashboard');
        }
        
        // Pass permission data to view
        $data['can_create'] = in_array($data['user']['role'], array('1'));
        $data['can_edit'] = in_array($data['user']['role'], array('1'));
        $data['can_delete'] = in_array($data['user']['role'], array('1'));

        $keyword_category = $_GET['keyword_category'] ?? "Name";
        $keyword = $_GET['keyword'] ?? "";
        
        $data['keyword_category'] = $keyword_category;
        $data['title'] = 'Role Management - ' . $this->template->title();

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Name") {
                $qry .= " AND (r.name LIKE '%$keyword%' OR r.display_name LIKE '%$keyword%')";
            } else if ($keyword_category == "Description") {
                $qry .= " AND r.description LIKE '%$keyword%'";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(r.id) AS count 
            FROM roles r 
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/roles/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("roles/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        $user_id = $_SESSION['user']['id'];
        
        // Pass permission data to view
        $data['can_create'] = in_array($_SESSION['user']['role'], array('1'));
        $data['can_edit'] = in_array($_SESSION['user']['role'], array('1'));
        $data['can_delete'] = in_array($_SESSION['user']['role'], array('1'));
        
        $keyword_category = $_GET['keyword_category'] ?? "Name";
        $keyword = $_GET['keyword'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Name") {
                $qry .= " AND (r.name LIKE '%$keyword%' OR r.display_name LIKE '%$keyword%')";
            } else if ($keyword_category == "Description") {
                $qry .= " AND r.description LIKE '%$keyword%'";
            }
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT r.*, 
            (SELECT COUNT(*) FROM user_roles ur WHERE ur.role_id = r.id) as user_count,
            (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) as permission_count
            FROM roles r 
            WHERE $qry 
            ORDER BY r.display_name ASC 
            LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("roles/item", $data);
    }

    public function create_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1'))) {
            redirect(base_url() . 'dashboard');
        }

        $data['data'] = array();
        
        // Get all modules grouped by category for permission matrix
        $modules = $this->mymodel->selectWithQuery("
            SELECT id, name, display_name, parent_id, sort_order, icon
            FROM modules 
            WHERE is_active = 1 
            ORDER BY sort_order, display_name
        ");
        $data['module_groups'] = $this->group_modules_by_category($modules);
        
        // Pass module permissions configuration
        $data['module_permissions'] = $this->get_module_permissions();
        
        $data['title'] = 'Create Role - ' . $this->template->title();
        $data['content'] = $this->load->view("roles/create_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function store()
    {
        $user = $_SESSION['user'];
        
        if (!in_array($user['role'], array('1'))) {
            echo $this->template->alert_danger('Access denied!');
            return;
        }
        
        $dt = $_POST['dt'];
        $permissions = $_POST['permissions'] ?? array();

        // Validate required fields
        if (empty($dt['name']) || empty($dt['display_name'])) {
            echo $this->template->alert_danger('Name and display name are required!');
            return;
        }

        // Check if role name already exists
        $existing = $this->mymodel->selectWithQuery("SELECT id FROM roles WHERE name = '{$dt['name']}'");
        if (!empty($existing)) {
            echo $this->template->alert_danger('Role name already exists!');
            return;
        }

        // Set default values
        if (!isset($dt['is_active'])) {
            $dt['is_active'] = 1;
        }

        // Start transaction
        $this->db->trans_start();
        
        try {
            // Insert role
            if ($this->db->insert('roles', $dt)) {
                $role_id = $this->db->insert_id();
                
                // Insert permissions
                $this->save_role_permissions($role_id, $permissions);
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    echo $this->template->alert_danger('Failed to create role!');
                } else {
                    $msg = 'Role created successfully!';
                    echo $this->template->alert_success($msg);
                }
            } else {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Failed to create role!');
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Failed to create role!');
        }
    }

    public function edit_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM roles WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'roles');
        }

        $data['data'] = $query[0];
        
        // Get all modules grouped by category for permission matrix
        $modules = $this->mymodel->selectWithQuery("
            SELECT id, name, display_name, parent_id, sort_order, icon
            FROM modules 
            WHERE is_active = 1 
            ORDER BY sort_order, display_name
        ");
        $data['module_groups'] = $this->group_modules_by_category($modules);
        
        // Pass module permissions configuration
        $data['module_permissions'] = $this->get_module_permissions();
        
        // Get current role permissions
        $current_permissions = $this->mymodel->selectWithQuery("
            SELECT module_id, can_view, can_create, can_edit, can_delete, can_approve
            FROM role_permissions 
            WHERE role_id = '$id'
        ");
        
        // Convert to associative array for easy lookup
        $data['current_permissions'] = array();
        foreach ($current_permissions as $perm) {
            $data['current_permissions'][$perm['module_id']] = $perm;
        }
        
        $data['title'] = 'Edit Role - ' . $this->template->title();
        $data['content'] = $this->load->view("roles/edit_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];
        
        if (!in_array($user['role'], array('1'))) {
            echo $this->template->alert_danger('Access denied!');
            return;
        }
        
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $permissions = $_POST['permissions'] ?? array();

        // Validate required fields
        if (empty($dt['name']) || empty($dt['display_name'])) {
            echo $this->template->alert_danger('Name and display name are required!');
            return;
        }

        // Check if role name already exists (excluding current record)
        $existing = $this->mymodel->selectWithQuery("SELECT id FROM roles WHERE name = '{$dt['name']}' AND id != '$id'");
        if (!empty($existing)) {
            echo $this->template->alert_danger('Role name already exists!');
            return;
        }

        // Start transaction
        $this->db->trans_start();
        
        try {
            // Update role
            if ($this->db->update('roles', $dt, array('id' => $id))) {
                // Delete existing permissions
                $this->mymodel->deleteData('role_permissions', array('role_id' => $id));
                
                // Insert new permissions
                $this->save_role_permissions($id, $permissions);
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    echo $this->template->alert_danger('Failed to update role!');
                } else {
                    $msg = 'Role updated successfully!';
                    echo $this->template->alert_success($msg);
                }
            } else {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Failed to update role!');
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Failed to update role!');
        }
    }

    public function detail()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM roles WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'roles');
        }

        $data['data'] = $query[0];
        
        // Get users with this role
        $users = $this->mymodel->selectWithQuery("SELECT u.full_name, u.email, u.username, ur.assigned_at 
            FROM user_roles ur 
            LEFT JOIN user u ON ur.user_id = u.id 
            WHERE ur.role_id = '$id' 
            ORDER BY u.full_name ASC");
        $data['users'] = $users;

        // Get role permissions
        $permissions = $this->mymodel->selectWithQuery("SELECT m.display_name, rp.can_view, rp.can_create, rp.can_edit, rp.can_delete, rp.can_approve
            FROM role_permissions rp
            JOIN modules m ON rp.module_id = m.id
            WHERE rp.role_id = '$id'
            ORDER BY m.sort_order, m.display_name");
        $data['permissions'] = $permissions;
        
        $data['title'] = 'Role Details - ' . $this->template->title();
        $data['content'] = $this->load->view("roles/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("roles/delete", $data);
    }

    public function delete()
    {
        $user = $_SESSION['user'];
        
        if (!in_array($user['role'], array('1'))) {
            echo $this->template->alert_danger('Access denied!');
            return;
        }
        
        $id = $_POST['id'];

        // Check if this role is being used by users
        $users = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM user_roles WHERE role_id = '$id'");
        if ($users[0]['count'] > 0) {
            $msg = 'Cannot delete role because it is assigned to users!';
            echo $this->template->alert_danger($msg);
            return;
        }

        // Check if it's a system role
        $role = $this->mymodel->selectWithQuery("SELECT name FROM roles WHERE id = '$id'");
        if (!empty($role) && in_array($role[0]['name'], ['super_admin', 'admin', 'employee'])) {
            $msg = 'Cannot delete system roles!';
            echo $this->template->alert_danger($msg);
            return;
        }

        if ($this->db->delete('roles', array('id' => $id))) {
            $msg = 'Role deleted successfully!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Failed to delete role!';
            echo $this->template->alert_danger($msg);
        }
    }
    
    /**
     * Save role permissions to database with dynamic permission validation
     */
    private function save_role_permissions($role_id, $permissions)
    {
        if (empty($permissions)) {
            return;
        }
        
        $module_permissions_config = $this->get_module_permissions();
        
        foreach ($permissions as $module_id => $perms) {
            // Get module name to check available permissions
            $module = $this->mymodel->selectWithQuery("SELECT name FROM modules WHERE id = '$module_id'");
            if (empty($module)) {
                continue;
            }
            
            $module_name = $module[0]['name'];
            $available_permissions = $module_permissions_config[$module_name] ?? ['view', 'create', 'edit', 'delete', 'approve'];
            
            // Build permission data based on available permissions for this module
            $permission_data = array(
                'role_id' => $role_id,
                'module_id' => $module_id,
                'can_view' => (in_array('view', $available_permissions) && isset($perms['can_view'])) ? 1 : 0,
                'can_create' => (in_array('create', $available_permissions) && isset($perms['can_create'])) ? 1 : 0,
                'can_edit' => (in_array('edit', $available_permissions) && isset($perms['can_edit'])) ? 1 : 0,
                'can_delete' => (in_array('delete', $available_permissions) && isset($perms['can_delete'])) ? 1 : 0,
                'can_approve' => (in_array('approve', $available_permissions) && isset($perms['can_approve'])) ? 1 : 0
            );
            
            // Only insert if at least one permission is granted
            if (array_sum(array_slice($permission_data, 2)) > 0) {
                $this->mymodel->insertData('role_permissions', $permission_data);
            }
        }
    }
    
    /**
     * Group modules by category for permission matrix
     */
    private function group_modules_by_category($modules)
    {
        $groups = array(
            'System Management' => array(),
            'HR Management' => array(),
            'Marketing' => array(),
            'Operations' => array(),
            'Reports & Analytics' => array()
        );

        foreach ($modules as $module) {
            $category = $this->get_module_category($module['name']);
            if (!isset($groups[$category])) {
                $groups[$category] = array();
            }
            $groups[$category][] = $module;
        }

        // Remove empty groups
        return array_filter($groups, function($group) {
            return !empty($group);
        });
    }
    
    /**
     * Get available permissions for each module based on actual functionality
     */
    private function get_module_permissions()
    {
        return array(
            // Full CRUD modules (Create, Edit, Delete, View)
            'benefit' => ['view', 'create', 'edit', 'delete'],
            'quest_level' => ['view', 'create', 'edit', 'delete'],
            'position' => ['view', 'create', 'edit', 'delete'],
            'modules' => ['view', 'create', 'edit', 'delete'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'user' => ['view', 'create', 'edit', 'delete'],
            'product' => ['view', 'create', 'edit', 'delete'],
            'product_3rd' => ['view', 'create', 'edit', 'delete'],
            'influencer' => ['view', 'create', 'edit', 'delete'],
            'crm' => ['view', 'create', 'edit', 'delete'],
            'crm_mg' => ['view', 'create', 'edit', 'delete'],
            'crm_pome' => ['view', 'create', 'edit', 'delete'],
            'expense' => ['view', 'create', 'edit', 'delete'],
            'discount' => ['view', 'create', 'edit', 'delete'],
            'endorse' => ['view', 'create', 'edit', 'delete'],
            'endorse_campaign' => ['view', 'create', 'edit', 'delete'],
            'group_wa' => ['view', 'create', 'edit', 'delete'],
            'label' => ['view', 'create', 'edit', 'delete'],
            'marketplace' => ['view', 'create', 'edit', 'delete'],
            'marketplace_account' => ['view', 'create', 'edit', 'delete'],
            'meta_account' => ['view', 'create', 'edit', 'delete'],
            'scraper' => ['view', 'create', 'edit', 'delete'],
            'shipping' => ['view', 'create', 'edit', 'delete'],
            'stock' => ['view', 'create', 'edit', 'delete'],
            'testimoni' => ['view', 'create', 'edit', 'delete'],
            'transaction' => ['view', 'create', 'edit', 'delete'],
            'transaction_item' => ['view', 'create', 'edit', 'delete'],
            'customer' => ['view', 'create', 'edit', 'delete'],
            'codeboost' => ['view', 'create', 'edit', 'delete'],
            'admin_fee_configuration' => ['view', 'create', 'edit', 'delete'],
            'quest' => ['view', 'create', 'edit', 'delete'],
            'operasional' => ['view', 'create', 'edit', 'delete'],
            
            // Approval workflow modules (View + Approve only)
            'interview' => ['view', 'approve'],
            'recruitment' => ['view', 'approve'],
            
            // Read-only modules (View only)
            'dashboard' => ['view'],
            'profile' => ['view'],
            'report' => ['view'],
            
            // Special functionality modules  
            'marketing' => ['view'],
            'overview' => ['view'],
            'advertiser' => ['view'],
            'ads_tiktok' => ['view'],
            'ads_meta' => ['view'],
            'ads_shopee' => ['view'],
            'ads_lazada' => ['view'],
            'endorsement' => ['view', 'create', 'edit', 'delete'],
            'influencer_dummy' => ['view', 'create', 'edit', 'delete'],
            'calendar' => ['view'],
            'payment' => ['view'],
            'order_customer' => ['view', 'create', 'edit', 'delete'],
            'milestone' => ['view', 'create', 'edit', 'delete']
        );
    }
    
    /**
     * Get module category based on module name
     */
    private function get_module_category($module_name)
    {
        $categories = array(
            'System Management' => array('dashboard', 'profile', 'modules', 'roles'),
            'HR Management' => array('quest', 'quest_level', 'position', 'benefit', 'milestone'),
            'Marketing' => array('marketing', 'overview', 'advertiser', 'ads_tiktok', 'ads_meta', 'ads_shopee', 'ads_lazada', 'endorsement', 'influencer', 'influencer_dummy', 'endorse_campaign', 'calendar', 'payment', 'codeboost'),
            'Operations' => array('transaction', 'transaction_item', 'marketplace_account', 'order_customer', 'crm_mg', 'crm_pome', 'group_wa', 'stock', 'product', 'product_3rd', 'operasional', 'discount', 'marketplace', 'shipping', 'customer'),
            'Reports & Analytics' => array('report', 'expense')
        );

        foreach ($categories as $category => $module_list) {
            if (in_array($module_name, $module_list)) {
                return $category;
            }
        }

        return 'System Management'; // Default category
    }
}