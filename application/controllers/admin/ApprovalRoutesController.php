<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

/**
 * ApprovalRoutesController
 *
 * Admin controller for managing approval route configurations.
 * Supports CRUD operations, versioning, and bulk creation.
 */
class ApprovalRoutesController extends BaseController
{
    protected $require_permissions = true;
    protected $show_403_on_deny = true;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('ApprovalRouteVersionModel');
        $this->load->model('LeaveTypeModel');
        $this->load->library('ApprovalRouteResolver');
        $this->load->library('permission');
        $this->load->library('template');

        $this->set_public_methods([]);

        $this->set_method_permissions([
            'store' => 'create',
            'update' => 'edit',
            'deactivate' => 'delete',
            'activate' => 'edit',
            'preview' => 'view',
            'bulk_create' => 'create',
            'bulk_store' => 'create',
        ]);
    }

    /**
     * List all approval routes
     */
    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];

        $data['can_create'] = $this->permission->check_permission($user_id, 'approval_routes', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'approval_routes', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'approval_routes', 'delete');

        $activeOnly = !isset($_GET['show_inactive']);
        $data['show_inactive'] = !$activeOnly;

        $data['routes'] = $this->ApprovalRouteVersionModel->get_all($activeOnly, true);

        $data['title'] = 'Rute Approval Cuti - ' . $this->template->title();
        $data['content'] = $this->load->view('admin/approval_routes/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $data['user'] = $_SESSION['user'];

        // Get dropdown data
        $data['scope_types'] = $this->ApprovalRouteVersionModel->get_scope_types();
        $data['operators'] = $this->ApprovalRouteVersionModel->get_operators();
        $data['approver_types'] = $this->ApprovalRouteVersionModel->get_approver_types();
        $data['dynamic_approvers'] = $this->ApprovalRouteVersionModel->get_dynamic_approvers();
        $data['roles'] = $this->ApprovalRouteVersionModel->get_roles_for_dropdown();
        $data['users'] = $this->ApprovalRouteVersionModel->get_users_for_dropdown();
        $data['leave_types'] = $this->ApprovalRouteVersionModel->get_leave_types_for_dropdown();

        $data['route'] = null; // New route
        $data['is_edit'] = false;

        $data['title'] = 'Buat Rute Approval Baru - ' . $this->template->title();
        $data['content'] = $this->load->view('admin/approval_routes/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Store new route
     */
    public function store()
    {
        if ($this->input->method() !== 'post') {
            redirect('admin/approval-routes');
        }

        $user = $_SESSION['user'];

        // Validate required fields
        $routeCode = $this->input->post('route_code');
        $name = $this->input->post('name');
        $effectiveFrom = $this->input->post('effective_from');

        if (!$routeCode || !$name || !$effectiveFrom) {
            $this->session->set_flashdata('error', 'Harap isi semua field yang wajib');
            redirect('admin/approval-routes/create');
        }

        // Build route data
        $routeData = array(
            'route_code' => strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $routeCode)),
            'name' => $name,
            'description' => $this->input->post('description'),
            'effective_from' => $effectiveFrom,
            'is_active' => 1,
            'created_by' => $user['id'],
        );

        // Build scopes
        $scopes = array();
        $scopeTypes = $this->input->post('scope_type') ?: array();
        $scopeValues = $this->input->post('scope_value') ?: array();
        $scopeOperators = $this->input->post('scope_operator') ?: array();

        for ($i = 0; $i < count($scopeTypes); $i++) {
            if (!empty($scopeTypes[$i])) {
                $scopes[] = array(
                    'scope_type' => $scopeTypes[$i],
                    'scope_value' => isset($scopeValues[$i]) ? $scopeValues[$i] : null,
                    'operator' => isset($scopeOperators[$i]) ? $scopeOperators[$i] : 'eq',
                );
            }
        }

        // Build steps
        $steps = array();
        $stepNos = $this->input->post('step_no') ?: array();
        $stepNames = $this->input->post('step_name') ?: array();
        $approverTypes = $this->input->post('approver_type') ?: array();
        $approverValues = $this->input->post('approver_value') ?: array();

        for ($i = 0; $i < count($stepNos); $i++) {
            if (!empty($approverTypes[$i]) && !empty($approverValues[$i])) {
                $steps[] = array(
                    'step_no' => intval($stepNos[$i]),
                    'step_name' => isset($stepNames[$i]) ? $stepNames[$i] : 'Step ' . ($i + 1),
                    'approver_type' => $approverTypes[$i],
                    'approver_value' => $approverValues[$i],
                    'is_optional' => 0,
                );
            }
        }

        if (empty($steps)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 step approval');
            redirect('admin/approval-routes/create');
        }

        $routeId = $this->ApprovalRouteVersionModel->create($routeData, $scopes, $steps);

        if ($routeId) {
            $this->session->set_flashdata('success', 'Rute approval berhasil dibuat');
            redirect('admin/approval-routes');
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat rute approval');
            redirect('admin/approval-routes/create');
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $route = $this->ApprovalRouteVersionModel->get_by_id($id);
        if (!$route) {
            $this->session->set_flashdata('error', 'Rute tidak ditemukan');
            redirect('admin/approval-routes');
        }

        $data['user'] = $_SESSION['user'];
        $data['route'] = $route;
        $data['is_edit'] = true;

        // Get dropdown data
        $data['scope_types'] = $this->ApprovalRouteVersionModel->get_scope_types();
        $data['operators'] = $this->ApprovalRouteVersionModel->get_operators();
        $data['approver_types'] = $this->ApprovalRouteVersionModel->get_approver_types();
        $data['dynamic_approvers'] = $this->ApprovalRouteVersionModel->get_dynamic_approvers();
        $data['roles'] = $this->ApprovalRouteVersionModel->get_roles_for_dropdown();
        $data['users'] = $this->ApprovalRouteVersionModel->get_users_for_dropdown();
        $data['leave_types'] = $this->ApprovalRouteVersionModel->get_leave_types_for_dropdown();

        $data['title'] = 'Edit Rute Approval - ' . $this->template->title();
        $data['content'] = $this->load->view('admin/approval_routes/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Update route (creates new version)
     */
    public function update($id)
    {
        if ($this->input->method() !== 'post') {
            redirect('admin/approval-routes');
        }

        $route = $this->ApprovalRouteVersionModel->get_by_id($id);
        if (!$route) {
            $this->session->set_flashdata('error', 'Rute tidak ditemukan');
            redirect('admin/approval-routes');
        }

        $user = $_SESSION['user'];

        // Build route data
        $routeData = array(
            'name' => $this->input->post('name'),
            'description' => $this->input->post('description'),
            'effective_from' => $this->input->post('effective_from') ?: date('Y-m-d'),
            'is_active' => 1,
            'created_by' => $user['id'],
        );

        // Build scopes
        $scopes = array();
        $scopeTypes = $this->input->post('scope_type') ?: array();
        $scopeValues = $this->input->post('scope_value') ?: array();
        $scopeOperators = $this->input->post('scope_operator') ?: array();

        for ($i = 0; $i < count($scopeTypes); $i++) {
            if (!empty($scopeTypes[$i])) {
                $scopes[] = array(
                    'scope_type' => $scopeTypes[$i],
                    'scope_value' => isset($scopeValues[$i]) ? $scopeValues[$i] : null,
                    'operator' => isset($scopeOperators[$i]) ? $scopeOperators[$i] : 'eq',
                );
            }
        }

        // Build steps
        $steps = array();
        $stepNos = $this->input->post('step_no') ?: array();
        $stepNames = $this->input->post('step_name') ?: array();
        $approverTypes = $this->input->post('approver_type') ?: array();
        $approverValues = $this->input->post('approver_value') ?: array();

        for ($i = 0; $i < count($stepNos); $i++) {
            if (!empty($approverTypes[$i]) && !empty($approverValues[$i])) {
                $steps[] = array(
                    'step_no' => intval($stepNos[$i]),
                    'step_name' => isset($stepNames[$i]) ? $stepNames[$i] : 'Step ' . ($i + 1),
                    'approver_type' => $approverTypes[$i],
                    'approver_value' => $approverValues[$i],
                    'is_optional' => 0,
                );
            }
        }

        if (empty($steps)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 step approval');
            redirect('admin/approval-routes/' . $id . '/edit');
        }

        $newId = $this->ApprovalRouteVersionModel->update($id, $routeData, $scopes, $steps);

        if ($newId) {
            $this->session->set_flashdata('success', 'Rute approval berhasil diperbarui (versi baru dibuat)');
            redirect('admin/approval-routes');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui rute approval');
            redirect('admin/approval-routes/' . $id . '/edit');
        }
    }

    /**
     * Deactivate a route
     */
    public function deactivate($id)
    {
        $route = $this->ApprovalRouteVersionModel->get_by_id($id);
        if (!$route) {
            $this->session->set_flashdata('error', 'Rute tidak ditemukan');
            redirect('admin/approval-routes');
        }

        if ($this->ApprovalRouteVersionModel->deactivate($id)) {
            $this->session->set_flashdata('success', 'Rute approval berhasil dinonaktifkan');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan rute approval');
        }

        redirect('admin/approval-routes');
    }

    /**
     * Activate a route
     */
    public function activate($id)
    {
        $route = $this->ApprovalRouteVersionModel->get_by_id($id);
        if (!$route) {
            $this->session->set_flashdata('error', 'Rute tidak ditemukan');
            redirect('admin/approval-routes');
        }

        if ($this->ApprovalRouteVersionModel->activate($id)) {
            $this->session->set_flashdata('success', 'Rute approval berhasil diaktifkan');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengaktifkan rute approval');
        }

        redirect('admin/approval-routes?show_inactive=1');
    }

    /**
     * View version history
     */
    public function versions($routeCode)
    {
        $data['user'] = $_SESSION['user'];
        $data['versions'] = $this->ApprovalRouteVersionModel->get_versions($routeCode);

        if (empty($data['versions'])) {
            $this->session->set_flashdata('error', 'Rute tidak ditemukan');
            redirect('admin/approval-routes');
        }

        $data['route_code'] = $routeCode;
        $data['route_name'] = $data['versions'][0]['name'];

        $data['title'] = 'Riwayat Versi: ' . $data['route_name'] . ' - ' . $this->template->title();
        $data['content'] = $this->load->view('admin/approval_routes/versions', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Preview matching users for a route
     */
    public function preview($id)
    {
        $route = $this->ApprovalRouteVersionModel->get_by_id($id);
        if (!$route) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Rute tidak ditemukan'
                )));
            return;
        }

        $matchingUsers = $this->approvalrouteresolver->previewMatchingUsers($id, 50);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'route_name' => $route['name'],
                'users' => $matchingUsers,
                'count' => count($matchingUsers),
            )));
    }

    /**
     * Show bulk create form
     */
    public function bulk_create()
    {
        $data['user'] = $_SESSION['user'];

        $data['scope_types'] = $this->ApprovalRouteVersionModel->get_scope_types();
        $data['operators'] = $this->ApprovalRouteVersionModel->get_operators();
        $data['approver_types'] = $this->ApprovalRouteVersionModel->get_approver_types();
        $data['dynamic_approvers'] = $this->ApprovalRouteVersionModel->get_dynamic_approvers();
        $data['roles'] = $this->ApprovalRouteVersionModel->get_roles_for_dropdown();

        $data['title'] = 'Buat Rute Approval Bulk - ' . $this->template->title();
        $data['content'] = $this->load->view('admin/approval_routes/bulk', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Process bulk create
     */
    public function bulk_store()
    {
        if ($this->input->method() !== 'post') {
            redirect('admin/approval-routes/bulk');
        }

        $user = $_SESSION['user'];
        $routesJson = $this->input->post('routes_json');

        if (!$routesJson) {
            $this->session->set_flashdata('error', 'Data rute tidak valid');
            redirect('admin/approval-routes/bulk');
        }

        $routes = json_decode($routesJson, true);
        if (!$routes || !is_array($routes)) {
            $this->session->set_flashdata('error', 'Format JSON tidak valid');
            redirect('admin/approval-routes/bulk');
        }

        $results = $this->ApprovalRouteVersionModel->bulk_create($routes, $user['id']);

        $successCount = 0;
        $failCount = 0;
        foreach ($results as $result) {
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        if ($failCount > 0) {
            $this->session->set_flashdata('warning', "Berhasil: $successCount, Gagal: $failCount rute");
        } else {
            $this->session->set_flashdata('success', "$successCount rute berhasil dibuat");
        }

        redirect('admin/approval-routes');
    }

    /**
     * View route details (AJAX)
     */
    public function detail($id)
    {
        $route = $this->ApprovalRouteVersionModel->get_by_id($id);
        if (!$route) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Rute tidak ditemukan'
                )));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'route' => $route,
            )));
    }

    /**
     * Clone a route as template
     */
    public function clone_route($id)
    {
        $route = $this->ApprovalRouteVersionModel->get_by_id($id);
        if (!$route) {
            $this->session->set_flashdata('error', 'Rute tidak ditemukan');
            redirect('admin/approval-routes');
        }

        $data['user'] = $_SESSION['user'];

        // Clone route with new code
        $route['route_code'] = $route['route_code'] . '_COPY';
        $route['name'] = $route['name'] . ' (Copy)';
        $route['id'] = null; // Clear ID to create new

        $data['route'] = $route;
        $data['is_edit'] = false; // Treat as new

        // Get dropdown data
        $data['scope_types'] = $this->ApprovalRouteVersionModel->get_scope_types();
        $data['operators'] = $this->ApprovalRouteVersionModel->get_operators();
        $data['approver_types'] = $this->ApprovalRouteVersionModel->get_approver_types();
        $data['dynamic_approvers'] = $this->ApprovalRouteVersionModel->get_dynamic_approvers();
        $data['roles'] = $this->ApprovalRouteVersionModel->get_roles_for_dropdown();
        $data['users'] = $this->ApprovalRouteVersionModel->get_users_for_dropdown();
        $data['leave_types'] = $this->ApprovalRouteVersionModel->get_leave_types_for_dropdown();

        $data['title'] = 'Clone Rute Approval - ' . $this->template->title();
        $data['content'] = $this->load->view('admin/approval_routes/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }
}
