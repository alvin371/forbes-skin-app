<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API Performance Controller
 * Handles ADMIN API endpoints for performance template management
 */
class Api_performance extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('performance_model');
        $this->load->model('mymodel');
        $this->load->library('permission');

        // Check if user is logged in
        if (!isset($_SESSION['is_login']) || !$_SESSION['is_login']) {
            $this->json_response(['success' => false, 'error' => 'Unauthorized'], 401);
            return;
        }
    }

    // ============================================
    // ADMIN TEMPLATE ENDPOINTS
    // ============================================

    /**
     * GET /api_performance/templates
     * List all templates with filtering
     */
    public function templates()
    {
        $user_id = $_SESSION['user']['id'];

        // Check permission
        if (!$this->permission->check_permission($user_id, 'performance_admin', 'view')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $filters = [];
        if ($this->input->get('period_year')) {
            $filters['period_year'] = $this->input->get('period_year');
        }
        // Use role_id filter (new) or department (legacy)
        if ($this->input->get('role_id') !== null && $this->input->get('role_id') !== '') {
            $filters['role_id'] = $this->input->get('role_id');
        } elseif ($this->input->get('department')) {
            $filters['department'] = $this->input->get('department');
        }
        if ($this->input->get('is_active') !== null) {
            $filters['is_active'] = $this->input->get('is_active');
        }

        $templates = $this->performance_model->get_templates($filters);

        $this->json_response([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * GET /api_performance/template/:id
     * Get single template with items
     */
    public function template($id = null)
    {
        if (!$id) {
            $this->json_response(['success' => false, 'error' => 'Template ID required'], 400);
            return;
        }

        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'view')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $template = $this->performance_model->get_template_by_id($id, true);

        if (!$template) {
            $this->json_response(['success' => false, 'error' => 'Template not found'], 404);
            return;
        }

        $this->json_response([
            'success' => true,
            'data' => $template
        ]);
    }

    /**
     * POST /api_performance/template/create
     * Create new template
     */
    public function template_create()
    {
        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'create')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $input = json_decode($this->input->raw_input_stream, true);

        if (!isset($input['name']) || !isset($input['period_year'])) {
            $this->json_response(['success' => false, 'error' => 'Name and period_year are required'], 400);
            return;
        }

        $template_id = $this->performance_model->create_template($input);

        $this->json_response([
            'success' => true,
            'id' => $template_id,
            'message' => 'Template created successfully'
        ]);
    }

    /**
     * PUT /api_performance/template/:id
     * Update template
     */
    public function template_update($id = null)
    {
        if (!$id) {
            $this->json_response(['success' => false, 'error' => 'Template ID required'], 400);
            return;
        }

        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'edit')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $input = json_decode($this->input->raw_input_stream, true);

        $result = $this->performance_model->update_template($id, $input);

        if (!$result['success']) {
            $this->json_response($result, 400);
            return;
        }

        $this->json_response([
            'success' => true,
            'message' => 'Template updated successfully'
        ]);
    }

    /**
     * DELETE /api_performance/template/:id
     * Delete template (only if no submissions)
     */
    public function template_delete($id = null)
    {
        if (!$id) {
            $this->json_response(['success' => false, 'error' => 'Template ID required'], 400);
            return;
        }

        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'delete')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $result = $this->performance_model->delete_template($id);

        if (!$result['success']) {
            $this->json_response($result, 400);
            return;
        }

        $this->json_response([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }

    // ============================================
    // ADMIN TEMPLATE ITEMS ENDPOINTS
    // ============================================

    /**
     * POST /api_performance/template/:id/item
     * Create template item
     */
    public function item_create($template_id = null)
    {
        if (!$template_id) {
            $this->json_response(['success' => false, 'error' => 'Template ID required'], 400);
            return;
        }

        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'create')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $input = json_decode($this->input->raw_input_stream, true);
        $input['template_id'] = $template_id;

        $result = $this->performance_model->create_template_item($input);

        if (!$result['success']) {
            $this->json_response($result, 400);
            return;
        }

        $this->json_response([
            'success' => true,
            'id' => $result['id'],
            'message' => 'Item created successfully'
        ]);
    }

    /**
     * PUT /api_performance/item/:id
     * Update template item
     */
    public function item_update($item_id = null)
    {
        if (!$item_id) {
            $this->json_response(['success' => false, 'error' => 'Item ID required'], 400);
            return;
        }

        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'edit')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $input = json_decode($this->input->raw_input_stream, true);

        $result = $this->performance_model->update_template_item($item_id, $input);

        if (!$result['success']) {
            $this->json_response($result, 400);
            return;
        }

        $this->json_response([
            'success' => true,
            'message' => 'Item updated successfully'
        ]);
    }

    /**
     * DELETE /api_performance/item/:id
     * Delete template item
     */
    public function item_delete($item_id = null)
    {
        if (!$item_id) {
            $this->json_response(['success' => false, 'error' => 'Item ID required'], 400);
            return;
        }

        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'delete')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $result = $this->performance_model->delete_template_item($item_id);

        $this->json_response([
            'success' => true,
            'message' => 'Item deleted successfully'
        ]);
    }

    /**
     * POST /api_performance/template/:id/items/reorder
     * Reorder template items
     */
    public function items_reorder($template_id = null)
    {
        if (!$template_id) {
            $this->json_response(['success' => false, 'error' => 'Template ID required'], 400);
            return;
        }

        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'edit')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $input = json_decode($this->input->raw_input_stream, true);

        if (!isset($input['items']) || !is_array($input['items'])) {
            $this->json_response(['success' => false, 'error' => 'Items array required'], 400);
            return;
        }

        $result = $this->performance_model->reorder_template_items($input['items']);

        $this->json_response([
            'success' => true,
            'message' => 'Items reordered successfully'
        ]);
    }

    // ============================================
    // EMPLOYEE SUBMISSION ENDPOINTS
    // ============================================

    /**
     * GET /api_performance/templates/active
     * Get active template for employee based on their role
     */
    public function templates_active()
    {
        $period_year = $this->input->get('period_year');
        $user_id = $_SESSION['user']['id'];
        $is_admin = $this->permission->check_permission($user_id, 'performance_admin', 'view');
        $employee_id = $is_admin ? ($this->input->get('employee_id') ?? $user_id) : $user_id;

        if (!$period_year) {
            $this->json_response(['success' => false, 'error' => 'period_year required'], 400);
            return;
        }

        // Get employee's primary role instead of department
        $employee_role = $this->performance_model->get_employee_primary_role($employee_id);
        $role_id = $employee_role ? $employee_role['id'] : null;

        $template = $this->performance_model->get_active_template_for_employee($period_year, $role_id);

        if (!$template) {
            $this->json_response(['success' => false, 'error' => 'No active template found for this period and role'], 404);
            return;
        }

        $this->json_response([
            'success' => true,
            'data' => $template
        ]);
    }

    /**
     * POST /api_performance/submission/create
     * Create performance submission
     */
    public function submission_create()
    {
        $input = json_decode($this->input->raw_input_stream, true);

        if (!isset($input['template_id']) || !isset($input['items'])) {
            $this->json_response(['success' => false, 'error' => 'template_id and items are required'], 400);
            return;
        }

        $user_id = $_SESSION['user']['id'];
        $is_admin = $this->permission->check_permission($user_id, 'performance_admin', 'view');

        if (!$is_admin) {
            $input['employee_id'] = $user_id;
        } elseif (!isset($input['employee_id'])) {
            $this->json_response(['success' => false, 'error' => 'employee_id is required'], 400);
            return;
        }

        // Get template to extract period_year
        $template = $this->performance_model->get_template_by_id($input['template_id'], false);

        if (!$template) {
            $this->json_response(['success' => false, 'error' => 'Template not found'], 404);
            return;
        }

        $input['period_year'] = $template['period_year'];

        $result = $this->performance_model->create_submission($input);

        if (!$result['success']) {
            $this->json_response($result, 400);
            return;
        }

        $this->json_response([
            'success' => true,
            'id' => $result['id'],
            'total_score' => round($result['total_score'], 2),
            'message' => 'Submission created successfully'
        ]);
    }

    /**
     * GET /api_performance/submissions/me
     * Get submissions for logged in user
     */
    public function submissions_me()
    {
        $employee_id = $_SESSION['user']['id'];

        $filters = ['employee_id' => $employee_id];

        if ($this->input->get('period_year')) {
            $filters['period_year'] = $this->input->get('period_year');
        }

        $submissions = $this->performance_model->get_submissions($filters);

        // Round scores for display
        foreach ($submissions as &$sub) {
            $sub['total_score'] = round($sub['total_score'], 2);
        }

        $this->json_response([
            'success' => true,
            'data' => $submissions
        ]);
    }

    /**
     * GET /api_performance/submission/:id
     * Get submission detail
     */
    public function submission($id = null)
    {
        if (!$id) {
            $this->json_response(['success' => false, 'error' => 'Submission ID required'], 400);
            return;
        }

        $submission = $this->performance_model->get_submission_by_id($id, true);

        if (!$submission) {
            $this->json_response(['success' => false, 'error' => 'Submission not found'], 404);
            return;
        }

        // Check permission - user can only view their own submission unless admin
        $user_id = $_SESSION['user']['id'];
        $is_admin = $this->permission->check_permission($user_id, 'performance_admin', 'view');

        if ($submission['employee_id'] != $user_id && !$is_admin) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        // Round scores for display
        $submission['total_score'] = round($submission['total_score'], 2);
        foreach ($submission['items'] as &$item) {
            $item['score_ratio'] = round($item['score_ratio'], 4);
            $item['final_score'] = round($item['final_score'], 2);
        }

        $this->json_response([
            'success' => true,
            'data' => $submission
        ]);
    }

    /**
     * GET /api_performance/submissions
     * Get all submissions (admin only)
     */
    public function submissions()
    {
        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'view')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $filters = [];

        if ($this->input->get('template_id')) {
            $filters['template_id'] = $this->input->get('template_id');
        }
        if ($this->input->get('period_year')) {
            $filters['period_year'] = $this->input->get('period_year');
        }
        // Use role_id filter (new) or department (legacy)
        if ($this->input->get('role_id') !== null && $this->input->get('role_id') !== '') {
            $filters['role_id'] = $this->input->get('role_id');
        } elseif ($this->input->get('department') !== null && $this->input->get('department') !== '') {
            $filters['department'] = $this->input->get('department');
        }

        $submissions = $this->performance_model->get_submissions($filters);

        // Round scores for display
        foreach ($submissions as &$sub) {
            $sub['total_score'] = round($sub['total_score'], 2);
        }

        $this->json_response([
            'success' => true,
            'data' => $submissions
        ]);
    }

    // ============================================
    // ROLES ENDPOINT
    // ============================================

    /**
     * GET /api_performance/roles
     * Get all active roles for dropdowns
     */
    public function roles()
    {
        $user_id = $_SESSION['user']['id'];

        if (!$this->permission->check_permission($user_id, 'performance_admin', 'view')) {
            $this->json_response(['success' => false, 'error' => 'Access denied'], 403);
            return;
        }

        $roles = $this->performance_model->get_all_roles();

        $this->json_response([
            'success' => true,
            'data' => $roles
        ]);
    }

    // ============================================
    // HELPER METHOD
    // ============================================

    private function json_response($data, $status_code = 200)
    {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
