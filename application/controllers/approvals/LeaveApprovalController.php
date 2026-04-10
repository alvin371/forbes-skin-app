<?php
defined('BASEPATH') or exit('No direct script access allowed');

class LeaveApprovalController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('template');
        $this->load->library('AuthFilter');
        $this->load->library('ApproverAuthFilter');
        $this->load->library('ApprovalWorkflowEngine');
        $this->load->model('LeaveRequestModel');
        $this->load->model('ApprovalStepModel');
        $this->authfilter->enforce();
        $this->approverauthfilter->enforce();
    }

    public function index()
    {
        $approverId = $this->current_user_id();

        $data['title'] = 'Pending Leave Approvals - ' . $this->template->title();
        $data['requests'] = $this->ApprovalStepModel->get_pending_for_approver($approverId);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('approvals/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function detail($id)
    {
        $request = $this->LeaveRequestModel->get_by_id($id);
        if (!$request) {
            show_404();
            return;
        }

        $currentStep = $this->get_current_pending_step_for_request((int) $id);
        if (!$currentStep) {
            $this->output->set_status_header(403);
            $data = array(
                'heading' => 'Access Forbidden',
                'message' => 'This leave request is not pending your approval.',
            );
            $this->load->view('errors/html/error_403', $data);
            return;
        }

        $approverId = $this->current_user_id();
        if (!$this->approvalworkflowengine->canUserApprove($approverId, $currentStep)) {
            $this->output->set_status_header(403);
            $data = array(
                'heading' => 'Access Forbidden',
                'message' => 'You are not authorized to review this leave request at the current approval step.',
            );
            $this->load->view('errors/html/error_403', $data);
            return;
        }

        $data['title'] = 'Leave Approval Detail - ' . $this->template->title();
        $data['request'] = $request;
        $data['current_step'] = $currentStep;
        $data['can_take_action'] = $currentStep['action'] === 'PENDING';
        $data['progress'] = $this->approvalworkflowengine->getWorkflowProgress((int) $id);
        $data['all_steps'] = $this->ApprovalStepModel->get_by_leave_request((int) $id);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('approvals/detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function approve($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $request = $this->LeaveRequestModel->get_by_id($id);
        if (!$request) {
            show_error('Leave request not found.', 404);
            return;
        }

        $approverId = $this->current_user_id();
        $currentStep = $this->get_current_pending_step_for_request((int) $id);
        if (!$currentStep || !$this->approvalworkflowengine->canUserApprove($approverId, $currentStep)) {
            $this->output->set_status_header(403);
            $data = array(
                'heading' => 'Access Forbidden',
                'message' => 'You are not authorized to approve this leave request at the current step.',
            );
            $this->load->view('errors/html/error_403', $data);
            return;
        }

        $notes = trim((string) $this->input->post('notes', TRUE));
        $result = $this->approvalworkflowengine->processApproval(
            (int) $currentStep['id'],
            $approverId,
            'APPROVED',
            $notes === '' ? null : $notes
        );

        if ($result['success']) {
            if (!empty($result['is_final'])) {
                $message = 'Leave request fully approved.';
                if (!empty($result['quota_warning'])) {
                    $message .= ' Quota warning: ' . $result['quota_warning'];
                }
            } else {
                $message = 'Approval recorded and forwarded to the next approver.';
            }

            $this->session->set_flashdata('message', $message);
        } else {
            $this->session->set_flashdata('error', $result['message']);
            redirect('approvals/leaves/' . $id);
            return;
        }

        redirect('approvals/leaves');
    }

    public function reject($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $request = $this->LeaveRequestModel->get_by_id($id);
        if (!$request) {
            show_error('Leave request not found.', 404);
            return;
        }

        $notes = trim((string) $this->input->post('notes', TRUE));
        if ($notes === '') {
            $this->session->set_flashdata('error', 'Rejection notes are required.');
            redirect('approvals/leaves/' . $id);
            return;
        }

        $approverId = $this->current_user_id();
        $currentStep = $this->get_current_pending_step_for_request((int) $id);
        if (!$currentStep || !$this->approvalworkflowengine->canUserApprove($approverId, $currentStep)) {
            $this->output->set_status_header(403);
            $data = array(
                'heading' => 'Access Forbidden',
                'message' => 'You are not authorized to reject this leave request at the current step.',
            );
            $this->load->view('errors/html/error_403', $data);
            return;
        }

        $result = $this->approvalworkflowengine->processApproval(
            (int) $currentStep['id'],
            $approverId,
            'REJECTED',
            $notes
        );

        if ($result['success']) {
            $this->session->set_flashdata('message', 'Leave request rejected.');
        } else {
            $this->session->set_flashdata('error', $result['message']);
            redirect('approvals/leaves/' . $id);
            return;
        }

        redirect('approvals/leaves');
    }

    private function get_current_pending_step_for_request($leaveRequestId)
    {
        $query = $this->db->query("
            SELECT s.*,
                   ai.current_step,
                   ai.total_steps,
                   ai.status as instance_status
            FROM approval_steps s
            INNER JOIN approval_instances ai ON s.approval_instance_id = ai.id
            WHERE s.leave_request_id = ?
              AND ai.status = 'IN_PROGRESS'
              AND s.action = 'PENDING'
              AND s.step_no = ai.current_step
            LIMIT 1
        ", array($leaveRequestId));

        return $query->row_array();
    }

    private function current_user_id()
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
    }
}
