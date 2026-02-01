<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ApprovalInboxController
 *
 * Controller for approvers to manage their pending leave approval tasks.
 * Displays pending approvals, allows approve/reject actions, and shows history.
 */
class ApprovalInboxController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->library('AuthFilter');
        $this->load->library('ApprovalWorkflowEngine');
        $this->load->library('permission');
        $this->load->library('template');
        $this->load->model('ApprovalStepModel');
        $this->load->model('ApprovalInstanceModel');
        $this->load->model('LeaveRequestModel');
        $this->load->helper('url');

        // Check authentication
        $this->authfilter->check();
    }

    /**
     * Display pending approval inbox
     */
    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];

        // Get pending approvals
        $data['pending_approvals'] = $this->ApprovalStepModel->get_pending_for_approver($userId);
        $data['pending_count'] = count($data['pending_approvals']);

        $data['title'] = 'Approval Inbox - ' . $this->template->title();
        $data['content'] = $this->load->view('approvals/inbox', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * View approval detail
     */
    public function detail($stepId)
    {
        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];

        // Get step details
        $step = $this->ApprovalStepModel->get_by_id($stepId);
        if (!$step) {
            $this->session->set_flashdata('error', 'Langkah approval tidak ditemukan');
            redirect('approvals/inbox');
        }

        // Check if user can approve this step
        if (!$this->approvalworkflowengine->canUserApprove($userId, $step)) {
            $this->session->set_flashdata('error', 'Anda tidak berhak mengakses approval ini');
            redirect('approvals/inbox');
        }

        // Get leave request details
        $data['leave_request'] = $this->LeaveRequestModel->get_by_id($step['leave_request_id']);
        if (!$data['leave_request']) {
            $this->session->set_flashdata('error', 'Pengajuan cuti tidak ditemukan');
            redirect('approvals/inbox');
        }

        // Get workflow progress
        $data['progress'] = $this->approvalworkflowengine->getWorkflowProgress($step['leave_request_id']);
        $data['step'] = $step;
        $data['all_steps'] = $this->ApprovalStepModel->get_by_leave_request($step['leave_request_id']);

        // Get requester info
        $requester = $this->db->query("
            SELECT u.*, up.position_id, p.name as position_name
            FROM user u
            LEFT JOIN user_profile up ON u.id = up.user_id
            LEFT JOIN positions p ON up.position_id = p.id
            WHERE u.id = ?
        ", array($data['leave_request']['user_id']))->row_array();
        $data['requester'] = $requester;

        $data['title'] = 'Detail Approval: ' . $data['leave_request']['request_no'] . ' - ' . $this->template->title();
        $data['content'] = $this->load->view('approvals/inbox_detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Approve a step
     */
    public function approve($stepId)
    {
        if ($this->input->method() !== 'post') {
            redirect('approvals/inbox');
        }

        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];

        $notes = $this->input->post('notes');

        $result = $this->approvalworkflowengine->processApproval(
            $stepId,
            $userId,
            'APPROVED',
            $notes
        );

        if ($result['success']) {
            if (isset($result['is_final']) && $result['is_final']) {
                $this->session->set_flashdata('success', 'Pengajuan cuti telah disetujui sepenuhnya');
            } else {
                $this->session->set_flashdata('success', 'Langkah approval berhasil. Dilanjutkan ke approver berikutnya.');
            }
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }

        redirect('approvals/inbox');
    }

    /**
     * Reject a request
     */
    public function reject($stepId)
    {
        if ($this->input->method() !== 'post') {
            redirect('approvals/inbox');
        }

        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];

        $notes = $this->input->post('notes');

        // Notes are required for rejection
        if (empty($notes)) {
            $this->session->set_flashdata('error', 'Alasan penolakan wajib diisi');
            redirect('approvals/inbox/detail/' . $stepId);
        }

        $result = $this->approvalworkflowengine->processApproval(
            $stepId,
            $userId,
            'REJECTED',
            $notes
        );

        if ($result['success']) {
            $this->session->set_flashdata('success', 'Pengajuan cuti telah ditolak');
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }

        redirect('approvals/inbox');
    }

    /**
     * View approval history
     */
    public function history()
    {
        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];

        $limit = $this->input->get('limit') ?: 50;
        $data['approvals'] = $this->ApprovalStepModel->get_history_for_approver($userId, $limit);

        $data['title'] = 'Riwayat Approval - ' . $this->template->title();
        $data['content'] = $this->load->view('approvals/history', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Get pending count (AJAX)
     */
    public function pending_count()
    {
        $userId = $_SESSION['user']['id'];
        $count = $this->ApprovalStepModel->get_pending_count($userId);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'count' => $count
            )));
    }

    /**
     * View requests that need route assignment (for HR admin)
     */
    public function needs_route()
    {
        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];

        // Check if user has HR admin permissions
        $canAssignRoute = $this->permission->check_permission($userId, 'approval_routes', 'edit');
        if (!$canAssignRoute) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki akses untuk halaman ini');
            redirect('approvals/inbox');
        }

        $data['requests'] = $this->ApprovalInstanceModel->get_needs_route();
        $data['can_assign_route'] = $canAssignRoute;

        // Get available routes for assignment
        $this->load->model('ApprovalRouteVersionModel');
        $data['available_routes'] = $this->ApprovalRouteVersionModel->get_all(true, true);

        $data['title'] = 'Pengajuan Perlu Rute - ' . $this->template->title();
        $data['content'] = $this->load->view('approvals/needs_route', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Assign route to a request (AJAX/POST)
     */
    public function assign_route()
    {
        if ($this->input->method() !== 'post') {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Invalid request method'
                )));
            return;
        }

        $userId = $_SESSION['user']['id'];

        // Check permission
        if (!$this->permission->check_permission($userId, 'approval_routes', 'edit')) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Permission denied'
                )));
            return;
        }

        $leaveRequestId = $this->input->post('leave_request_id');
        $routeVersionId = $this->input->post('route_version_id');

        if (!$leaveRequestId || !$routeVersionId) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Missing required parameters'
                )));
            return;
        }

        $result = $this->approvalworkflowengine->assignRoute($leaveRequestId, $routeVersionId, $userId);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /**
     * Quick approve from inbox (AJAX)
     */
    public function quick_approve()
    {
        if ($this->input->method() !== 'post') {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Invalid request method'
                )));
            return;
        }

        $userId = $_SESSION['user']['id'];
        $stepId = $this->input->post('step_id');
        $notes = $this->input->post('notes') ?: 'Disetujui';

        $result = $this->approvalworkflowengine->processApproval($stepId, $userId, 'APPROVED', $notes);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /**
     * Quick reject from inbox (AJAX)
     */
    public function quick_reject()
    {
        if ($this->input->method() !== 'post') {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Invalid request method'
                )));
            return;
        }

        $userId = $_SESSION['user']['id'];
        $stepId = $this->input->post('step_id');
        $notes = $this->input->post('notes');

        if (empty($notes)) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Alasan penolakan wajib diisi'
                )));
            return;
        }

        $result = $this->approvalworkflowengine->processApproval($stepId, $userId, 'REJECTED', $notes);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }
}
