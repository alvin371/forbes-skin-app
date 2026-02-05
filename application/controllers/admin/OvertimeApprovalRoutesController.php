<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

/**
 * OvertimeApprovalRoutesController
 *
 * Admin controller for managing overtime approval route configurations.
 */
class OvertimeApprovalRoutesController extends BaseController
{
    protected $require_permissions = true;
    protected $show_403_on_deny = true;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('OvertimeApprovalRouteModel');
        $this->load->library('permission');
        $this->load->library('template');

        $this->set_public_methods([]);
        $this->set_method_permissions([
            'store' => 'create',
            'update' => 'edit',
            'deactivate' => 'delete',
            'activate' => 'edit',
            'delete' => 'delete',
        ]);
    }

    /**
     * List all overtime approval routes
     */
    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];

        $data['can_create'] = $this->permission->check_permission($user_id, 'overtime_approval_routes', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'overtime_approval_routes', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'overtime_approval_routes', 'delete');

        $activeOnly = !isset($_GET['show_inactive']);
        $data['show_inactive'] = !$activeOnly;
        $data['routes'] = $this->OvertimeApprovalRouteModel->get_all($activeOnly);

        $data['title'] = 'Rute Approval Lembur - ' . $this->template->title();
        $data['content'] = $this->load->view('admin/overtime_approval_routes/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $data['user'] = $_SESSION['user'];

        $data['scope_types'] = $this->OvertimeApprovalRouteModel->get_scope_types();
        $data['approver_types'] = $this->OvertimeApprovalRouteModel->get_approver_types();
        $data['dynamic_approvers'] = $this->OvertimeApprovalRouteModel->get_dynamic_approvers();
        $data['roles'] = $this->OvertimeApprovalRouteModel->get_roles_for_dropdown();
        $data['users'] = $this->OvertimeApprovalRouteModel->get_users_for_dropdown();

        $data['route'] = null;
        $data['is_edit'] = false;

        $data['title'] = 'Buat Rute Approval Lembur - ' . $this->template->title();
        $data['content'] = $this->load->view('admin/overtime_approval_routes/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Store new route
     */
    public function store()
    {
        if ($this->input->method() !== 'post') {
            redirect('admin/overtime-approval-routes');
        }

        $user = $_SESSION['user'];
        $routeCode = $this->input->post('route_code');
        $name = $this->input->post('name');

        if (!$routeCode || !$name) {
            $this->session->set_flashdata('error', 'Harap isi semua field yang wajib');
            redirect('admin/overtime-approval-routes/create');
        }

        $routeCode = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $routeCode));
        if ($this->OvertimeApprovalRouteModel->get_by_code($routeCode)) {
            $this->session->set_flashdata('error', 'Kode rute sudah digunakan. Gunakan kode lain.');
            redirect('admin/overtime-approval-routes/create');
        }

        $routeData = array(
            'route_code' => $routeCode,
            'name' => $name,
            'description' => $this->input->post('description'),
            'is_active' => 1,
            'created_by' => $user['id'],
        );

        $scopes = array();
        $scopeTypes = $this->input->post('scope_type') ?: array();
        $scopeValues = $this->input->post('scope_value') ?: array();

        for ($i = 0; $i < count($scopeTypes); $i++) {
            if (!empty($scopeTypes[$i]) && !empty($scopeValues[$i])) {
                $scopes[] = array(
                    'scope_type' => $scopeTypes[$i],
                    'scope_value' => $scopeValues[$i],
                );
            }
        }

        $steps = array();
        $stepNos = $this->input->post('step_no') ?: array();
        $stepNames = $this->input->post('step_name') ?: array();
        $approverTypes = $this->input->post('approver_type') ?: array();
        $approverValues = $this->input->post('approver_value') ?: array();

        for ($i = 0; $i < count($approverTypes); $i++) {
            if (!empty($approverTypes[$i]) && !empty($approverValues[$i])) {
                $steps[] = array(
                    'step_no' => isset($stepNos[$i]) ? (int) $stepNos[$i] : ($i + 1),
                    'step_name' => isset($stepNames[$i]) ? $stepNames[$i] : null,
                    'approver_type' => $approverTypes[$i],
                    'approver_value' => $approverValues[$i],
                    'is_optional' => 0,
                );
            }
        }

        if (empty($steps)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 step approval');
            redirect('admin/overtime-approval-routes/create');
        }

        $routeId = $this->OvertimeApprovalRouteModel->create($routeData, $scopes, $steps);
        if ($routeId) {
            $this->session->set_flashdata('success', 'Rute approval lembur berhasil dibuat');
            redirect('admin/overtime-approval-routes');
        }

        $this->session->set_flashdata('error', 'Gagal membuat rute approval lembur');
        redirect('admin/overtime-approval-routes/create');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $data['user'] = $_SESSION['user'];
        $route = $this->OvertimeApprovalRouteModel->get_by_id($id);

        if (!$route) {
            $this->session->set_flashdata('error', 'Rute tidak ditemukan');
            redirect('admin/overtime-approval-routes');
        }

        $data['scope_types'] = $this->OvertimeApprovalRouteModel->get_scope_types();
        $data['approver_types'] = $this->OvertimeApprovalRouteModel->get_approver_types();
        $data['dynamic_approvers'] = $this->OvertimeApprovalRouteModel->get_dynamic_approvers();
        $data['roles'] = $this->OvertimeApprovalRouteModel->get_roles_for_dropdown();
        $data['users'] = $this->OvertimeApprovalRouteModel->get_users_for_dropdown();

        $data['route'] = $route;
        $data['is_edit'] = true;

        $data['title'] = 'Edit Rute Approval Lembur - ' . $this->template->title();
        $data['content'] = $this->load->view('admin/overtime_approval_routes/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Update route
     */
    public function update($id)
    {
        if ($this->input->method() !== 'post') {
            redirect('admin/overtime-approval-routes');
        }

        $route = $this->OvertimeApprovalRouteModel->get_by_id($id);
        if (!$route) {
            $this->session->set_flashdata('error', 'Rute tidak ditemukan');
            redirect('admin/overtime-approval-routes');
        }

        $name = $this->input->post('name');
        if (!$name) {
            $this->session->set_flashdata('error', 'Harap isi semua field yang wajib');
            redirect('admin/overtime-approval-routes/' . $id . '/edit');
        }

        $routeData = array(
            'name' => $name,
            'description' => $this->input->post('description'),
        );

        $scopes = array();
        $scopeTypes = $this->input->post('scope_type') ?: array();
        $scopeValues = $this->input->post('scope_value') ?: array();

        for ($i = 0; $i < count($scopeTypes); $i++) {
            if (!empty($scopeTypes[$i]) && !empty($scopeValues[$i])) {
                $scopes[] = array(
                    'scope_type' => $scopeTypes[$i],
                    'scope_value' => $scopeValues[$i],
                );
            }
        }

        $steps = array();
        $stepNos = $this->input->post('step_no') ?: array();
        $stepNames = $this->input->post('step_name') ?: array();
        $approverTypes = $this->input->post('approver_type') ?: array();
        $approverValues = $this->input->post('approver_value') ?: array();

        for ($i = 0; $i < count($approverTypes); $i++) {
            if (!empty($approverTypes[$i]) && !empty($approverValues[$i])) {
                $steps[] = array(
                    'step_no' => isset($stepNos[$i]) ? (int) $stepNos[$i] : ($i + 1),
                    'step_name' => isset($stepNames[$i]) ? $stepNames[$i] : null,
                    'approver_type' => $approverTypes[$i],
                    'approver_value' => $approverValues[$i],
                    'is_optional' => 0,
                );
            }
        }

        if (empty($steps)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 step approval');
            redirect('admin/overtime-approval-routes/' . $id . '/edit');
        }

        $updated = $this->OvertimeApprovalRouteModel->update($id, $routeData, $scopes, $steps);
        if ($updated) {
            $this->session->set_flashdata('success', 'Rute approval lembur berhasil diperbarui');
            redirect('admin/overtime-approval-routes');
        }

        $this->session->set_flashdata('error', 'Gagal memperbarui rute approval lembur');
        redirect('admin/overtime-approval-routes/' . $id . '/edit');
    }

    /**
     * Deactivate a route
     */
    public function deactivate($id)
    {
        if ($this->OvertimeApprovalRouteModel->deactivate($id)) {
            $this->session->set_flashdata('success', 'Rute berhasil dinonaktifkan');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan rute');
        }
        redirect('admin/overtime-approval-routes');
    }

    /**
     * Activate a route
     */
    public function activate($id)
    {
        if ($this->OvertimeApprovalRouteModel->activate($id)) {
            $this->session->set_flashdata('success', 'Rute berhasil diaktifkan');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengaktifkan rute');
        }
        redirect('admin/overtime-approval-routes');
    }

    /**
     * Delete a route
     */
    public function delete($id)
    {
        if ($this->OvertimeApprovalRouteModel->delete($id)) {
            $this->session->set_flashdata('success', 'Rute berhasil dihapus');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus rute');
        }
        redirect('admin/overtime-approval-routes');
    }
}
