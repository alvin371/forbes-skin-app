<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Roles extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('permission');
        $this->load->library('template');
        $this->load->helper('sidebar_registry');

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
        $user_id = $data['user']['id'];

        // Permission data from BaseController
        $permission_data = $this->get_permission_data();
        $data['can_create'] = $permission_data['can_create'];
        $data['can_edit'] = $permission_data['can_edit'];
        $data['can_delete'] = $permission_data['can_delete'];

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

        // Permission data from BaseController
        $permission_data = $this->get_permission_data();
        $data['can_create'] = $permission_data['can_create'];
        $data['can_edit'] = $permission_data['can_edit'];
        $data['can_delete'] = $permission_data['can_delete'];

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

        $data['data'] = array();

        sidebar_registry_sync($this);

        // Get all modules grouped by category for permission matrix
        $module_names = sidebar_registry_names();
        $module_names_sql = implode(',', array_map([$this->db, 'escape'], $module_names));
        $modules = $this->mymodel->selectWithQuery("
            SELECT id, name, display_name, parent_id, sort_order, icon
            FROM modules
            WHERE is_active = 1
            AND name IN ($module_names_sql)
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

        // Permission check handled by BaseController middleware
        $this->require_ajax_permission('create');

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
                    $this->sync_user_permissions_for_role($role_id);
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

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM roles WHERE id = '$id'");

        if (empty($query)) {
            redirect(base_url() . 'roles');
        }

        $data['data'] = $query[0];

        // Get all modules grouped by category for permission matrix
        sidebar_registry_sync($this);
        $module_names = sidebar_registry_names();
        $module_names_sql = implode(',', array_map([$this->db, 'escape'], $module_names));
        $modules = $this->mymodel->selectWithQuery("
            SELECT id, name, display_name, parent_id, sort_order, icon
            FROM modules
            WHERE is_active = 1
            AND name IN ($module_names_sql)
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

        // Permission check handled by BaseController middleware
        $this->require_ajax_permission('edit');

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
                $this->sync_user_permissions_for_role($id);
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

        // Permission check handled by BaseController middleware
        $this->require_ajax_permission('delete');

        $id = $_POST['id'];

        // sync affected users before removing role (if any)
        $this->sync_user_permissions_for_role($id);

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
     * Sync user_module_permissions for users assigned to a role.
     */
    private function sync_user_permissions_for_role($role_id)
    {
        if (!$this->db->table_exists('user_module_permissions')) {
            return;
        }

        $users = $this->mymodel->selectWithQuery(
            "SELECT DISTINCT user_id FROM user_roles WHERE role_id = " . (int) $role_id
        );

        if (empty($users)) {
            return;
        }

        $user_ids = array_map(static function ($row) {
            return (int) $row['user_id'];
        }, $users);

        $has_override = $this->db->field_exists('has_override', 'user_module_permissions');
        $has_created_at = $this->db->field_exists('created_at', 'user_module_permissions');
        $has_updated_at = $this->db->field_exists('updated_at', 'user_module_permissions');

        $this->db->trans_start();

        if ($has_override) {
            $this->db->where_in('user_id', $user_ids);
            $this->db->group_start()
                ->where('has_override', 0)
                ->or_where('has_override IS NULL', null, false)
                ->group_end();
            $this->db->delete('user_module_permissions');
        } else {
            $this->db->where_in('user_id', $user_ids);
            $this->db->delete('user_module_permissions');
        }

        $user_ids_sql = implode(',', array_map('intval', $user_ids));
        $query = $this->db->query("
            SELECT
                ur.user_id,
                m.id AS module_id,
                m.name AS module_name,
                m.display_name AS module_display_name,
                m.controller,
                m.parent_id,
                MAX(rp.can_view) AS can_view,
                MAX(rp.can_create) AS can_create,
                MAX(rp.can_edit) AS can_edit,
                MAX(rp.can_delete) AS can_delete,
                MAX(rp.can_approve) AS can_approve
            FROM user_roles ur
            INNER JOIN role_permissions rp ON rp.role_id = ur.role_id
            INNER JOIN modules m ON m.id = rp.module_id
            WHERE ur.user_id IN ($user_ids_sql)
            GROUP BY
                ur.user_id,
                m.id,
                m.name,
                m.display_name,
                m.controller,
                m.parent_id
        ");

        $rows = $query->result_array();

        if (!empty($rows)) {
            $columns = array(
                'user_id',
                'module_id',
                'module_name',
                'module_display_name',
                'controller',
                'parent_id',
                'can_view',
                'can_create',
                'can_edit',
                'can_delete',
                'can_approve'
            );

            if ($has_override) {
                $columns[] = 'has_override';
            }
            if ($has_created_at) {
                $columns[] = 'created_at';
            }
            if ($has_updated_at) {
                $columns[] = 'updated_at';
            }

            $batch = [];
            $now = date('Y-m-d H:i:s');

            foreach ($rows as $row) {
                $payload = array(
                    'user_id' => (int) $row['user_id'],
                    'module_id' => (int) $row['module_id'],
                    'module_name' => $row['module_name'],
                    'module_display_name' => $row['module_display_name'],
                    'controller' => $row['controller'],
                    'parent_id' => $row['parent_id'],
                    'can_view' => (int) $row['can_view'],
                    'can_create' => (int) $row['can_create'],
                    'can_edit' => (int) $row['can_edit'],
                    'can_delete' => (int) $row['can_delete'],
                    'can_approve' => (int) $row['can_approve']
                );

                if ($has_override) {
                    $payload['has_override'] = 0;
                }
                if ($has_created_at) {
                    $payload['created_at'] = $now;
                }
                if ($has_updated_at) {
                    $payload['updated_at'] = $now;
                }

                $batch[] = $payload;

                if (count($batch) >= 500) {
                    $this->db->insert_batch('user_module_permissions', $batch, $columns);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                $this->db->insert_batch('user_module_permissions', $batch, $columns);
            }
        }

        $this->db->trans_complete();

        // B3: bump the global permission version so logged-in users' session permission
        // maps rebuild on their next request instead of waiting out the TTL.
        if (isset($this->permission) && method_exists($this->permission, 'bump_permission_version')) {
            $this->permission->bump_permission_version();
        }
    }

    /**
     * Group modules by category for permission matrix
     */
    private function group_modules_by_category($modules)
    {
        $groups = [];
        foreach (sidebar_registry_categories() as $category) {
            $groups[$category] = [];
        }

        $module_map = [];
        foreach ($modules as $module) {
            $module_map[$module['name']] = $module;
        }

        foreach (sidebar_registry() as $name => $config) {
            if (!isset($module_map[$name])) {
                continue;
            }
            $category = $config['category'];
            if (!isset($groups[$category])) {
                $groups[$category] = [];
            }
            $groups[$category][] = $module_map[$name];
        }

        // Remove empty groups
        return array_filter($groups, function ($group) {
            return !empty($group);
        });
    }

    /**
     * Get available permissions for each module based on actual functionality
     */
    private function get_module_permissions()
    {
        return sidebar_registry_permissions();
    }

    /**
     * Get module category based on module name
     */
    private function get_module_category($module_name)
    {
        return sidebar_registry_category_for($module_name);
    }
}
