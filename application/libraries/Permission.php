<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Permission Library
 * 
 * Handles dynamic user permissions based on positions and individual overrides
 * Integrates with the quest level system and provides easy permission checking
 */
class Permission
{
    protected $CI;
    protected $user_permissions_cache = [];
    
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('mymodel');
    }
    
    /**
     * Check if user has specific permission for a module
     * 
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action (view, create, edit, delete)
     * @return bool
     */
    public function check_permission($user_id, $module_name, $action = 'view')
    {
        // Cache key for performance
        $cache_key = "{$user_id}_{$module_name}_{$action}";
        
        if (isset($this->user_permissions_cache[$cache_key])) {
            return $this->user_permissions_cache[$cache_key];
        }
        
        // Check if permission tables exist first
        if (!$this->permission_tables_exist()) {
            // Use fallback role-based system
            $has_permission = $this->fallback_permission_check($user_id, $module_name, $action);
        } else {
            // Use the role-based permission system
            try {
                // Use the view for easy permission checking
                $result = $this->CI->mymodel->selectWithQuery("
                    SELECT can_{$action} as has_permission
                    FROM user_module_permissions 
                    WHERE user_id = '$user_id' AND module_name = '$module_name'
                    LIMIT 1
                ");
                
                if (empty($result)) {
                    // No permission found, use fallback
                    $has_permission = $this->fallback_permission_check($user_id, $module_name, $action);
                } else {
                    $has_permission = $result[0]['has_permission'] == 1;
                }
            } catch (Exception $e) {
                // If any error occurs, use fallback
                $has_permission = $this->fallback_permission_check($user_id, $module_name, $action);
            }
        }
        
        // Cache the result
        $this->user_permissions_cache[$cache_key] = $has_permission;
        
        return $has_permission;
    }
    
    /**
     * Check if user has access to a controller (any permission)
     * 
     * @param int $user_id User ID
     * @param string $controller Controller name
     * @return bool
     */
    public function has_module_access($user_id, $controller)
    {
        try {
            $result = $this->CI->mymodel->selectWithQuery("
                SELECT COUNT(*) as count
                FROM user_module_permissions 
                WHERE user_id = $user_id 
                AND controller = '$controller' 
                AND (can_view = 1 OR can_create = 1 OR can_edit = 1 OR can_delete = 1)
            ");
            
            return !empty($result) && $result[0]['count'] > 0;
        } catch (Exception $e) {
            // Fallback to role-based check
            return $this->fallback_permission_check($user_id, $controller, 'view');
        }
    }
    
    /**
     * Get all permissions for a user
     * 
     * @param int $user_id User ID
     * @return array
     */
    public function get_user_permissions($user_id)
    {
        try {
            return $this->CI->mymodel->selectWithQuery("
                SELECT 
                    module_name,
                    module_display_name,
                    controller,
                    parent_id,
                    can_view,
                    can_create,
                    can_edit,
                    can_delete,
                    can_approve,
                    has_override
                FROM user_module_permissions 
                WHERE user_id = $user_id
                AND (can_view = 1 OR can_create = 1 OR can_edit = 1 OR can_delete = 1 OR can_approve = 1)
                ORDER BY module_name
            ");
        } catch (Exception $e) {
            // Return basic permissions for fallback
            return [];
        }
    }
    
    /**
     * Get user's accessible modules for sidebar
     * 
     * @param int $user_id User ID
     * @return array Hierarchical module structure
     */
    public function get_user_sidebar_modules($user_id)
    {
        $permissions = $this->CI->mymodel->selectWithQuery("
            SELECT 
                m.id,
                m.name,
                m.display_name,
                m.controller,
                m.icon,
                m.parent_id,
                m.sort_order,
                ump.can_view,
                ump.can_create,
                ump.can_edit,
                ump.can_delete
            FROM modules m
            LEFT JOIN user_module_permissions ump ON m.id = ump.module_id AND ump.user_id = ?
            WHERE m.is_active = 1 
            AND (ump.can_view = 1 OR ump.can_create = 1 OR ump.can_edit = 1 OR ump.can_delete = 1)
            ORDER BY m.sort_order, m.display_name
        ", [$user_id]);
        
        return $this->build_module_tree($permissions);
    }
    
    /**
     * Build hierarchical module tree
     * 
     * @param array $modules Flat module array
     * @param int $parent_id Parent ID
     * @return array
     */
    private function build_module_tree($modules, $parent_id = null)
    {
        $tree = [];
        
        foreach ($modules as $module) {
            if ($module['parent_id'] == $parent_id) {
                $module['children'] = $this->build_module_tree($modules, $module['id']);
                $tree[] = $module;
            }
        }
        
        return $tree;
    }
    
    /**
     * Check if user can perform action on current controller
     * Uses current CI controller and method
     * 
     * @param int $user_id User ID
     * @param string $action Permission action
     * @return bool
     */
    public function can_access_current($user_id, $action = 'view')
    {
        $controller = $this->CI->router->fetch_class();
        
        // Map controller to module name
        $module_mapping = $this->get_controller_module_mapping();
        $module_name = isset($module_mapping[$controller]) ? $module_mapping[$controller] : $controller;
        
        return $this->check_permission($user_id, $module_name, $action);
    }
    
    /**
     * Check if permission tables exist
     * 
     * @return bool
     */
    private function permission_tables_exist()
    {
        try {
            // Check if role-based permission tables exist
            $modules = $this->CI->mymodel->selectWithQuery("SHOW TABLES LIKE 'modules'");
            $roles = $this->CI->mymodel->selectWithQuery("SHOW TABLES LIKE 'roles'");
            $role_permissions = $this->CI->mymodel->selectWithQuery("SHOW TABLES LIKE 'role_permissions'");
            $user_roles = $this->CI->mymodel->selectWithQuery("SHOW TABLES LIKE 'user_roles'");
            
            return !empty($modules) && !empty($roles) && !empty($role_permissions) && !empty($user_roles);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Fallback permission check when tables don't exist
     * Uses the existing role-based system
     * 
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action
     * @return bool
     */
    private function fallback_permission_check($user_id, $module_name, $action)
    {
        // Get user data - avoid prepared statements that might be causing issues
        $user = $this->CI->mymodel->selectWithQuery("
            SELECT role FROM user WHERE id = $user_id LIMIT 1
        ");
        
        if (empty($user)) {
            return false;
        }
        
        $role = $user[0]['role'];
        
        // HR/Admin roles (1, 2, 7) get full access
        if (in_array($role, ['1', '2', '7'])) {
            return true;
        }
        
        // Basic modules everyone can view
        $basic_modules = ['dashboard', 'profile', 'quest'];
        if (in_array($module_name, $basic_modules) && $action === 'view') {
            return true;
        }
        
        // Deny everything else until proper permissions are set up
        return false;
    }

    /**
     * Get controller to module name mapping
     * 
     * @return array
     */
    private function get_controller_module_mapping()
    {
        return [
            'dashboard' => 'dashboard',
            'report' => 'report',
            'expense' => 'expense',
            'overview' => 'overview',
            'ads' => 'advertiser', // Special handling for ads with parameters
            'influencer' => 'influencer',
            'influencer_dummy' => 'influencer_dummy',
            'endorse_campaign' => 'endorse_campaign',
            'calendar' => 'calendar',
            'payment' => 'payment',
            'codeboost' => 'codeboost',
            'marketplace_account' => 'marketplace_account',
            'transaction' => 'transaction',
            'transaction_item' => 'transaction_item',
            'crm' => 'crm_mg', // Default, may need brand parameter handling
            'group_wa' => 'group_wa',
            'stock' => 'stock',
            'product' => 'product',
            'product_3rd' => 'product_3rd',
            'discount' => 'discount',
            'marketplace' => 'marketplace',
            'shipping' => 'shipping',
            'quest_level' => 'quest_level',
            'position' => 'position',
            'benefit' => 'benefit',
            'quest' => 'quest',
            'milestone' => 'milestone',
            'user' => 'user',
            'profile' => 'profile',
            'customer' => 'customer',
            'label' => 'label',
            'testimoni' => 'testimoni',
            'scraper' => 'scraper',
            'meta_account' => 'meta_account'
        ];
    }
    
    /**
     * Check specific ads module permission based on marketplace parameter
     * 
     * @param int $user_id User ID
     * @param string $marketplace Marketplace (tiktok, meta, shopee, lazada)
     * @param string $action Permission action
     * @return bool
     */
    public function check_ads_permission($user_id, $marketplace, $action = 'view')
    {
        $module_name = 'ads_' . strtolower($marketplace);
        return $this->check_permission($user_id, $module_name, $action);
    }
    
    /**
     * Check CRM permission based on brand parameter
     * 
     * @param int $user_id User ID
     * @param string $brand Brand (MG, POME)
     * @param string $action Permission action
     * @return bool
     */
    public function check_crm_permission($user_id, $brand, $action = 'view')
    {
        $module_name = 'crm_' . strtolower($brand);
        return $this->check_permission($user_id, $module_name, $action);
    }
    
    /**
     * Enforce permission check - redirect if no access
     * 
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action
     * @param string $redirect_url Redirect URL on failure
     */
    public function enforce_permission($user_id, $module_name, $action = 'view', $redirect_url = null)
    {
        if (!$this->check_permission($user_id, $module_name, $action)) {
            if (!$redirect_url) {
                $redirect_url = base_url() . 'dashboard';
            }
            redirect($redirect_url);
        }
    }
    
    /**
     * Show 403 error page for permission denied
     * Alternative to enforce_permission that shows error page instead of redirect
     * 
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action
     * @param array $data Additional data to pass to error page
     */
    public function show_403_if_no_permission($user_id, $module_name, $action = 'view', $data = [])
    {
        if (!$this->check_permission($user_id, $module_name, $action)) {
            // Set HTTP status code
            $this->CI->output->set_status_header(403);
            
            // Prepare data for the error page
            $error_data = array_merge([
                'heading' => 'Access Forbidden',
                'message' => 'You do not have permission to access this resource.',
                'module' => $module_name,
                'action' => $action,
                'user_id' => $user_id
            ], $data);
            
            // Load and display the 403 error page
            $this->CI->load->view('errors/html/error_403', $error_data);
            exit;
        }
    }
    
    /**
     * Enhanced enforce permission with option to show 403 page
     * 
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action
     * @param bool $show_403 Whether to show 403 page instead of redirect
     * @param string $redirect_url Redirect URL on failure (if not showing 403)
     * @param array $error_data Additional data for 403 page
     */
    public function enforce_permission_with_403($user_id, $module_name, $action = 'view', $show_403 = false, $redirect_url = null, $error_data = [])
    {
        if (!$this->check_permission($user_id, $module_name, $action)) {
            if ($show_403) {
                $this->show_403_if_no_permission($user_id, $module_name, $action, $error_data);
            } else {
                if (!$redirect_url) {
                    $redirect_url = base_url() . 'dashboard';
                }
                redirect($redirect_url);
            }
        }
    }
    
    /**
     * Clear permission cache for user
     * 
     * @param int $user_id User ID
     */
    public function clear_user_cache($user_id = null)
    {
        if ($user_id) {
            foreach (array_keys($this->user_permissions_cache) as $key) {
                if (strpos($key, $user_id . '_') === 0) {
                    unset($this->user_permissions_cache[$key]);
                }
            }
        } else {
            $this->user_permissions_cache = [];
        }
    }
    
    /**
     * Get all positions with their permission counts
     * For management interface
     * 
     * @return array
     */
    public function get_positions_with_permissions()
    {
        return $this->CI->mymodel->selectWithQuery("
            SELECT 
                p.id,
                p.name as position_name,
                ql.name as quest_level_name,
                ql.level_order,
                COUNT(perm.id) as total_permissions,
                SUM(perm.can_view) as view_permissions,
                SUM(perm.can_create) as create_permissions,
                SUM(perm.can_edit) as edit_permissions,
                SUM(perm.can_delete) as delete_permissions
            FROM positions p
            JOIN quest_levels ql ON p.level_id = ql.id
            LEFT JOIN permissions perm ON p.id = perm.position_id
            GROUP BY p.id, p.name, ql.name, ql.level_order
            ORDER BY ql.level_order, p.name
        ");
    }
    
    /**
     * Get permission matrix for a specific position
     * 
     * @param int $position_id Position ID
     * @return array
     */
    public function get_position_permissions($position_id)
    {
        return $this->CI->mymodel->selectWithQuery("
            SELECT 
                m.id as module_id,
                m.name as module_name,
                m.display_name,
                m.parent_id,
                COALESCE(p.can_view, 0) as can_view,
                COALESCE(p.can_create, 0) as can_create,
                COALESCE(p.can_edit, 0) as can_edit,
                COALESCE(p.can_delete, 0) as can_delete
            FROM modules m
            LEFT JOIN permissions p ON m.id = p.module_id AND p.position_id = ?
            WHERE m.is_active = 1
            ORDER BY m.sort_order, m.display_name
        ", [$position_id]);
    }
    
    /**
     * Update position permissions
     * 
     * @param int $position_id Position ID
     * @param array $permissions Permission data
     * @return bool
     */
    public function update_position_permissions($position_id, $permissions)
    {
        // Start transaction
        $this->CI->db->trans_start();
        
        try {
            // Delete existing permissions for this position
            $this->CI->mymodel->deleteData('permissions', ['position_id' => $position_id]);
            
            // Insert new permissions
            foreach ($permissions as $module_id => $perms) {
                if (isset($perms['can_view']) || isset($perms['can_create']) || 
                    isset($perms['can_edit']) || isset($perms['can_delete'])) {
                    
                    $data = [
                        'position_id' => $position_id,
                        'module_id' => $module_id,
                        'can_view' => isset($perms['can_view']) ? 1 : 0,
                        'can_create' => isset($perms['can_create']) ? 1 : 0,
                        'can_edit' => isset($perms['can_edit']) ? 1 : 0,
                        'can_delete' => isset($perms['can_delete']) ? 1 : 0
                    ];
                    
                    $this->CI->mymodel->insertData('permissions', $data);
                }
            }
            
            $this->CI->db->trans_complete();
            
            if ($this->CI->db->trans_status() === FALSE) {
                return false;
            }
            
            // Clear cache
            $this->clear_user_cache();
            
            return true;
            
        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            return false;
        }
    }
}