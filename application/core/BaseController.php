<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BaseController with Permission Middleware
 * 
 * All controllers should extend this base controller to automatically
 * handle permission checking and 403 error redirection
 */
class BaseController extends CI_Controller
{
    protected $user_id;
    protected $user_data;
    protected $module_name;
    protected $public_methods = [];
    protected $method_permissions = [];
    
    // Controller to module mapping based on clear_and_replace_modules.sql
    protected $controller_module_map = [
        'dashboard' => 'dashboard',
        'report' => 'report', 
        'expense' => 'expense',
        'overview' => 'overview',
        'ads' => null, // Special handling - multiple modules based on ?m parameter
        'influencer' => 'influencer',
        'influencer_dummy' => 'influencer_dummy',
        'endorse_campaign' => 'endorse_campaign',
        'endorse' => 'endorse_campaign',
        'calendar' => 'calendar',
        'payment' => 'payment',
        'codeboost' => 'codeboost',
        'marketplace_account' => 'marketplace_account',
        'transaction' => 'transaction',
        'transaction_item' => 'transaction_item',
        'crm' => null, // Special handling - multiple modules based on ?brand parameter
        'group_wa' => 'group_wa',
        'stock' => 'stock',
        'product' => 'product',
        'quest_level' => 'quest_level',
        'position' => 'position',
        'roles' => 'roles',
        'benefit' => 'benefit',
        'quest' => 'quest',
        'milestone' => 'milestone',
        'modules' => 'modules',
        'user' => 'user',
        'profile' => 'profile',
        'scraper' => 'scraper'
    ];
    
    // Method to permission action mapping
    protected $method_action_map = [
        'index' => 'view',
        'item' => 'view',
        'all' => 'view',
        'detail' => 'view',
        'create_page' => 'create',
        'store' => 'create',
        'add' => 'create',
        'edit_page' => 'edit',
        'update' => 'edit',
        'remove' => 'delete',
        'delete' => 'delete',
        'bulk_delete' => 'delete',
        'approve' => 'approve',
        'deny' => 'approve',
        'approve_submission' => 'approve',
        'deny_submission' => 'approve'
    ];

    public function __construct()
    {
        parent::__construct();
        
        // Load required libraries
        $this->load->library('permission');
        $this->load->library('template');
        
        // Initialize user data
        $this->init_user_data();
        
        // Determine module name
        $this->module_name = $this->get_module_name();
        
        // Check permissions (unless it's a public method)
        $this->check_method_permission();
    }
    
    /**
     * Initialize user data from session
     */
    protected function init_user_data()
    {
        // Check if session exists
        if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
            // Redirect to login if not authenticated
            redirect(base_url('auth/login'));
            return;
        }
        
        $this->user_data = $_SESSION['user'];
        $this->user_id = $this->user_data['id'];
    }
    
    /**
     * Get module name for current controller
     */
    protected function get_module_name()
    {
        $controller = $this->router->class;
        
        // Handle special cases with parameters
        if ($controller === 'ads') {
            $platform = $this->input->get('m');
            return $platform ? "ads_{$platform}" : 'overview'; // Default to overview if no platform
        }
        
        if ($controller === 'crm') {
            $brand = $this->input->get('brand');
            return $brand ? "crm_" . strtolower($brand) : 'crm_mg'; // Default to MG
        }
        
        // Standard mapping
        return $this->controller_module_map[$controller] ?? $controller;
    }
    
    /**
     * Check method permission based on current method
     */
    protected function check_method_permission()
    {
        $method = $this->router->method;
        $controller = $this->router->class;
        
        // Skip permission check for public methods
        if (in_array($method, $this->public_methods)) {
            return;
        }
        
        // Skip permission check for certain controllers that should remain public
        $public_controllers = ['auth', 'ajax', 'api', 'api_v2', 'api_v3'];
        if (in_array($controller, $public_controllers)) {
            return;
        }
        
        // Skip if no module mapping exists
        if (!$this->module_name) {
            return;
        }
        
        // Determine required permission action
        $action = $this->get_required_action($method);
        
        // Check if user has permission
        if (!$this->permission->check_permission($this->user_id, $this->module_name, $action)) {
            // Set HTTP status code
            $this->output->set_status_header(403);
            
            // Prepare data for the error page
            $error_data = [
                'heading' => 'Access Forbidden',
                'message' => 'You do not have permission to access this resource.',
                'module' => $this->module_name,
                'action' => $action,
                'user_id' => $this->user_id,
                'controller' => $controller,
                'method' => $method,
                'attempted_action' => $action
            ];
            
            // Load and display the 403 error page directly
            $this->load->view('errors/html/error_403', $error_data);
            exit;
        }
    }
    
    /**
     * Get required permission action for a method
     */
    protected function get_required_action($method)
    {
        // Check for custom method permissions set by child controller
        if (isset($this->method_permissions[$method])) {
            return $this->method_permissions[$method];
        }
        
        // Use default mapping
        return $this->method_action_map[$method] ?? 'view';
    }
    
    /**
     * Set public methods that don't require permission checks
     */
    protected function set_public_methods($methods)
    {
        $this->public_methods = is_array($methods) ? $methods : [$methods];
    }
    
    /**
     * Set custom method to permission mapping
     */
    protected function set_method_permissions($permissions)
    {
        $this->method_permissions = $permissions;
    }
    
    /**
     * Manual permission check (for complex scenarios)
     */
    protected function require_permission($module, $action = 'view')
    {
        if (!$this->permission->check_permission($this->user_id, $module, $action)) {
            $this->permission->show_403_if_no_permission($this->user_id, $module, $action);
        }
    }
    
    /**
     * Check if user has specific permission (for conditional UI)
     */
    protected function has_permission($module, $action = 'view')
    {
        return $this->permission->check_permission($this->user_id, $module, $action);
    }
    
    /**
     * Get permission data for views
     */
    protected function get_permission_data($module = null)
    {
        $module = $module ?? $this->module_name;
        
        return [
            'can_view' => $this->has_permission($module, 'view'),
            'can_create' => $this->has_permission($module, 'create'),
            'can_edit' => $this->has_permission($module, 'edit'),
            'can_delete' => $this->has_permission($module, 'delete'),
            'can_approve' => $this->has_permission($module, 'approve')
        ];
    }
    
    /**
     * Enhanced role-based check (for backward compatibility)
     */
    protected function require_roles($roles)
    {
        $roles = is_array($roles) ? $roles : [$roles];
        
        if (!in_array($this->user_data['role'], $roles)) {
            $this->permission->show_403_if_no_permission(
                $this->user_id, 
                $this->module_name ?? 'system', 
                'view',
                [
                    'required_roles' => $roles,
                    'user_role' => $this->user_data['role']
                ]
            );
        }
    }
    
    /**
     * AJAX permission check
     */
    protected function require_ajax_permission($module, $action = 'view')
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        if (!$this->permission->check_permission($this->user_id, $module, $action)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Access denied. You do not have permission to perform this action.',
                    'error_code' => 403
                ]));
            return false;
        }
        
        return true;
    }
}