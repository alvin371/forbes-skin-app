<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Modules extends BaseController
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
            'bulk_delete' => 'delete',
            'create_module' => 'create',
            'update_module' => 'edit',
            'delete_module' => 'delete'
        ]);
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        
        // Check if user has permission (Super Admin only)
        if (!in_array($data['user']['role'], array('1'))) {
            redirect(base_url() . 'dashboard');
        }
        
        // Pass permission data to view
        $data['can_create'] = in_array($data['user']['role'], array('1'));
        $data['can_edit'] = in_array($data['user']['role'], array('1'));
        $data['can_delete'] = in_array($data['user']['role'], array('1'));

        $keyword_category = $_GET['keyword_category'] ?? "Name";
        $keyword = $_GET['keyword'] ?? "";
        $status_filter = $_GET['status_filter'] ?? "";
        $category_filter = $_GET['category_filter'] ?? "";
        
        $data['keyword_category'] = $keyword_category;
        $data['status_filter'] = $status_filter;
        $data['category_filter'] = $category_filter;
        $data['title'] = 'Module Management - ' . $this->template->title();

        // Get module categories for filter dropdown
        $data['module_categories'] = array(
            'System Management' => 'System Management',
            'HR Management' => 'HR Management', 
            'Marketing' => 'Marketing',
            'Operations' => 'Operations',
            'Reports & Analytics' => 'Reports & Analytics'
        );

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Name") {
                $qry .= " AND (m.name LIKE '%$keyword%' OR m.display_name LIKE '%$keyword%')";
            } else if ($keyword_category == "Controller") {
                $qry .= " AND m.controller LIKE '%$keyword%'";
            }
        }
        
        if ($status_filter) {
            $qry .= " AND m.is_active = '$status_filter'";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(m.id) AS count 
            FROM modules m 
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/modules/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("modules/all", $data, true);
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
        $status_filter = $_GET['status_filter'] ?? "";
        $category_filter = $_GET['category_filter'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Name") {
                $qry .= " AND (m.name LIKE '%$keyword%' OR m.display_name LIKE '%$keyword%')";
            } else if ($keyword_category == "Controller") {
                $qry .= " AND m.controller LIKE '%$keyword%'";
            }
        }
        
        if ($status_filter) {
            $qry .= " AND m.is_active = '$status_filter'";
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT m.*, 
            parent.display_name as parent_name,
            COUNT(child.id) as children_count,
            (SELECT COUNT(*) FROM role_permissions rp WHERE rp.module_id = m.id) as permission_count
            FROM modules m 
            LEFT JOIN modules parent ON m.parent_id = parent.id
            LEFT JOIN modules child ON m.id = child.parent_id
            WHERE $qry 
            GROUP BY m.id, m.name, m.display_name, m.controller, m.icon, 
                     m.parent_id, m.sort_order, m.is_active, parent.display_name
            ORDER BY m.sort_order ASC, m.display_name ASC 
            LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("modules/item", $data);
    }

    public function create_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1'))) {
            redirect(base_url() . 'dashboard');
        }

        $data['data'] = array();
        
        // Get all parent modules for dropdown
        $parent_modules = $this->mymodel->selectWithQuery("
            SELECT id, display_name, name
            FROM modules 
            WHERE parent_id IS NULL
            ORDER BY sort_order, display_name
        ");
        $data['parent_modules'] = $parent_modules;
        
        $data['title'] = 'Create Module - ' . $this->template->title();
        $data['content'] = $this->load->view("modules/create_page", $data, true);
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

        // Validate required fields
        if (empty($dt['name']) || empty($dt['display_name'])) {
            echo $this->template->alert_danger('Name and display name are required!');
            return;
        }

        // Check if module name already exists
        $existing = $this->mymodel->selectWithQuery("SELECT id FROM modules WHERE name = '{$dt['name']}';");
        if (!empty($existing)) {
            echo $this->template->alert_danger('Module name already exists!');
            return;
        }

        // Set default values
        if (empty($dt['sort_order'])) {
            $dt['sort_order'] = 0;
        }
        if (!isset($dt['is_active'])) {
            $dt['is_active'] = 1;
        }
        if (empty($dt['parent_id'])) {
            $dt['parent_id'] = null;
        }

        // Start transaction
        $this->db->trans_start();
        
        try {
            // Insert module
            if ($this->db->insert('modules', $dt)) {
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    echo $this->template->alert_danger('Failed to create module!');
                } else {
                    $msg = 'Module created successfully!';
                    echo $this->template->alert_success($msg);
                }
            } else {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Failed to create module!');
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Failed to create module!');
        }
    }

    public function edit_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM modules WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'modules');
        }

        $data['data'] = $query[0];
        
        // Get all parent modules for dropdown
        $parent_modules = $this->mymodel->selectWithQuery("
            SELECT id, display_name, name
            FROM modules 
            WHERE parent_id IS NULL AND id != '$id'
            ORDER BY sort_order, display_name
        ");
        $data['parent_modules'] = $parent_modules;
        
        $data['title'] = 'Edit Module - ' . $this->template->title();
        $data['content'] = $this->load->view("modules/edit_page", $data, true);
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

        // Validate required fields
        if (empty($dt['name']) || empty($dt['display_name'])) {
            echo $this->template->alert_danger('Name and display name are required!');
            return;
        }

        // Check if module name already exists (excluding current record)
        $existing = $this->mymodel->selectWithQuery("SELECT id FROM modules WHERE name = '{$dt['name']}' AND id != '$id'");
        if (!empty($existing)) {
            echo $this->template->alert_danger('Module name already exists!');
            return;
        }

        // Set defaults
        if (empty($dt['parent_id'])) {
            $dt['parent_id'] = null;
        }
        if (empty($dt['sort_order'])) {
            $dt['sort_order'] = 0;
        }

        // Start transaction
        $this->db->trans_start();
        
        try {
            // Update module
            if ($this->db->update('modules', $dt, array('id' => $id))) {
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    echo $this->template->alert_danger('Failed to update module!');
                } else {
                    $msg = 'Module updated successfully!';
                    echo $this->template->alert_success($msg);
                }
            } else {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Failed to update module!');
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Failed to update module!');
        }
    }

    public function detail()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT m.*, parent.display_name as parent_name FROM modules m LEFT JOIN modules parent ON m.parent_id = parent.id WHERE m.id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'modules');
        }

        $data['data'] = $query[0];
        
        // Get child modules
        $children = $this->mymodel->selectWithQuery("SELECT id, name, display_name, is_active FROM modules WHERE parent_id = '$id' ORDER BY sort_order, display_name");
        $data['children'] = $children;

        // Get module permissions across roles
        $permissions = $this->mymodel->selectWithQuery("SELECT r.display_name as role_name, rp.can_view, rp.can_create, rp.can_edit, rp.can_delete, rp.can_approve
            FROM role_permissions rp
            JOIN roles r ON rp.role_id = r.id
            WHERE rp.module_id = '$id'
            ORDER BY r.level DESC, r.display_name");
        $data['permissions'] = $permissions;
        
        $data['title'] = 'Module Details - ' . $this->template->title();
        $data['content'] = $this->load->view("modules/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("modules/delete", $data);
    }

    public function delete()
    {
        $user = $_SESSION['user'];
        
        if (!in_array($user['role'], array('1'))) {
            echo $this->template->alert_danger('Access denied!');
            return;
        }
        
        $id = $_POST['id'];

        // Check if this module has child modules
        $children = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM modules WHERE parent_id = '$id'");
        if ($children[0]['count'] > 0) {
            $msg = 'Cannot delete module because it has child modules!';
            echo $this->template->alert_danger($msg);
            return;
        }

        // Check if it's being used in role permissions
        $permissions = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM role_permissions WHERE module_id = '$id'");
        if ($permissions[0]['count'] > 0) {
            $msg = 'Cannot delete module because it is referenced in role permissions!';
            echo $this->template->alert_danger($msg);
            return;
        }

        if ($this->db->delete('modules', array('id' => $id))) {
            $msg = 'Module deleted successfully!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Failed to delete module!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function bulk_delete()
    {
        $user = $_SESSION['user'];
        
        if (!in_array($user['role'], array('1'))) {
            echo $this->template->alert_danger('Access denied!');
            return;
        }
        
        $module_ids = $_POST['module_ids'];
        
        if (empty($module_ids) || !is_array($module_ids)) {
            echo $this->template->alert_danger('No modules selected for deletion!');
            return;
        }

        $deleted_count = 0;
        $failed_count = 0;
        $failed_modules = array();

        // Start transaction
        $this->db->trans_start();
        
        try {
            foreach ($module_ids as $id) {
                // Check if this module has child modules
                $children = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM modules WHERE parent_id = '$id'");
                if ($children[0]['count'] > 0) {
                    $module = $this->mymodel->selectWithQuery("SELECT display_name FROM modules WHERE id = '$id'");
                    $failed_modules[] = $module[0]['display_name'] . ' (has child modules)';
                    $failed_count++;
                    continue;
                }

                // Check if it's being used in role permissions
                $permissions = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM role_permissions WHERE module_id = '$id'");
                if ($permissions[0]['count'] > 0) {
                    $module = $this->mymodel->selectWithQuery("SELECT display_name FROM modules WHERE id = '$id'");  
                    $failed_modules[] = $module[0]['display_name'] . ' (used in role permissions)';
                    $failed_count++;
                    continue;
                }

                // Delete the module
                if ($this->db->delete('modules', array('id' => $id))) {
                    $deleted_count++;
                } else {
                    $module = $this->mymodel->selectWithQuery("SELECT display_name FROM modules WHERE id = '$id'");
                    $failed_modules[] = $module[0]['display_name'] . ' (database error)';
                    $failed_count++;
                }
            }

            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                echo $this->template->alert_danger('Failed to delete modules due to database error!');
                return;
            }

            // Generate result message
            $messages = array();
            
            if ($deleted_count > 0) {
                $messages[] = "<strong>$deleted_count module(s) deleted successfully!</strong>";
            }
            
            if ($failed_count > 0) {
                $messages[] = "<strong>$failed_count module(s) could not be deleted:</strong><br>" . implode('<br>', $failed_modules);
            }

            if ($deleted_count > 0 && $failed_count == 0) {
                echo $this->template->alert_success(implode('<br>', $messages));
            } else if ($deleted_count > 0 && $failed_count > 0) {
                echo $this->template->alert_warning(implode('<br>', $messages));
            } else {
                echo $this->template->alert_danger(implode('<br>', $messages));
            }

        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Failed to delete modules due to an unexpected error!');
        }
    }

    // Legacy methods for backward compatibility
    public function position_permissions($position_id)
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;
        
        // Check permission
        if (!in_array($data['user']['role'], array('1', '2', '7'))) {
            redirect(base_url() . 'dashboard');
        }

        // Get position details
        $position = $this->mymodel->selectWithQuery("
            SELECT p.id, p.name as position_name, ql.name as quest_level_name, ql.level_order
            FROM positions p
            JOIN quest_levels ql ON p.level_id = ql.id
            WHERE p.id = ?
        ", [$position_id]);

        if (empty($position)) {
            redirect(base_url() . 'modules');
        }

        $data['position'] = $position[0];
        $data['permissions'] = $this->permission->get_position_permissions($position_id);

        $this->load->view("modules/position_permissions", $data);
    }

    public function update_position_permissions()
    {
        // Check permission
        if (!in_array($_SESSION['user']['role'], array('1', '2', '7'))) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }

        $position_id = $this->input->post('position_id');
        $permissions = $this->input->post('permissions');

        if (!$position_id || !$permissions) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
            return;
        }

        $result = $this->permission->update_position_permissions($position_id, $permissions);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Permissions updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update permissions']);
        }
    }

    public function modules_list()
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;
        
        // Check permission
        if (!in_array($data['user']['role'], array('1', '2', '7'))) {
            redirect(base_url() . 'dashboard');
        }

        // Get all modules with hierarchy
        $data['modules'] = $this->mymodel->selectWithQuery("
            SELECT 
                m.id, m.name, m.display_name, m.controller, m.icon, 
                m.parent_id, m.sort_order, m.is_active,
                parent.display_name as parent_name,
                COUNT(child.id) as children_count
            FROM modules m
            LEFT JOIN modules parent ON m.parent_id = parent.id
            LEFT JOIN modules child ON m.id = child.parent_id
            GROUP BY m.id, m.name, m.display_name, m.controller, m.icon, 
                     m.parent_id, m.sort_order, m.is_active, parent.display_name
            ORDER BY m.sort_order, m.display_name
        ");

        $this->load->view("modules/modules_list", $data);
    }

    // Legacy AJAX methods for backward compatibility
    public function create_module()
    {
        // Check permission
        if (!in_array($_SESSION['user']['role'], array('1'))) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }

        $data = [
            'name' => $this->input->post('name'),
            'display_name' => $this->input->post('display_name'),
            'controller' => $this->input->post('controller'),
            'icon' => $this->input->post('icon'),
            'parent_id' => $this->input->post('parent_id') ?: null,
            'sort_order' => $this->input->post('sort_order') ?: 0
        ];

        $result = $this->mymodel->insertData('modules', $data);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Module created successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create module']);
        }
    }

    public function update_module()
    {
        // Check permission
        if (!in_array($_SESSION['user']['role'], array('1'))) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }

        $id = $this->input->post('id');
        $data = [
            'name' => $this->input->post('name'),
            'display_name' => $this->input->post('display_name'),
            'controller' => $this->input->post('controller'),
            'icon' => $this->input->post('icon'),
            'parent_id' => $this->input->post('parent_id') ?: null,
            'sort_order' => $this->input->post('sort_order') ?: 0,
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];

        $result = $this->mymodel->updateData('modules', $data, ['id' => $id]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Module updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update module']);
        }
    }

    public function delete_module()
    {
        // Check permission
        if (!in_array($_SESSION['user']['role'], array('1'))) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }

        $id = $this->input->post('id');

        // Check if module has children
        $children = $this->mymodel->selectWhere('modules', ['parent_id' => $id]);
        if (!empty($children)) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete module with sub-modules']);
            return;
        }

        $result = $this->mymodel->deleteData('modules', ['id' => $id]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Module deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete module']);
        }
    }

    public function get_module_permissions($module_id)
    {
        // Check permission
        if (!in_array($_SESSION['user']['role'], array('1'))) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            return;
        }

        // Get all roles and their permissions for this module
        $permissions = $this->mymodel->selectWithQuery("
            SELECT 
                r.id as role_id,
                r.display_name as role_name,
                r.level as role_level,
                COALESCE(rp.can_view, 0) as can_view,
                COALESCE(rp.can_create, 0) as can_create,
                COALESCE(rp.can_edit, 0) as can_edit,
                COALESCE(rp.can_delete, 0) as can_delete,
                COALESCE(rp.can_approve, 0) as can_approve
            FROM roles r
            LEFT JOIN role_permissions rp ON r.id = rp.role_id AND rp.module_id = ?
            ORDER BY r.level DESC, r.display_name
        ", [$module_id]);

        echo json_encode(['status' => 'success', 'data' => $permissions]);
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