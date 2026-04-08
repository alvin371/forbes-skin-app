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
        $this->load->model('LeaveApprovalModel');
        $this->load->model('LeaveQuotaModel');
        $this->load->model('ApprovalStepModel');
        $this->authfilter->enforce();
        $this->approverauthfilter->enforce();
    }

    public function index()
    {
        $data['title'] = 'Pending Leave Approvals - ' . $this->template->title();
        $data['requests'] = $this->LeaveRequestModel->get_all_pending_approvals();
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

        if ($request['status'] !== 'PENDING_APPROVAL') {
            $this->output->set_status_header(403);
            $data = array(
                'heading' => 'Access Forbidden',
                'message' => 'This leave request is not pending approval.',
            );
            $this->load->view('errors/html/error_403', $data);
            return;
        }

        $data['title'] = 'Leave Approval Detail - ' . $this->template->title();
        $data['request'] = $request;
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
        if (!$request || $request['status'] !== 'PENDING_APPROVAL') {
            show_error('Leave request not found or already processed.', 404);
            return;
        }

        $approverId = $this->current_user_id();
        $notes = trim((string) $this->input->post('notes', TRUE));
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $this->db->where('leave_request_id', (int) $id);
        $this->db->where('action', 'PENDING');
        $existingApproval = $this->db->get('leave_approvals')->row_array();

        if ($existingApproval) {
            $this->LeaveApprovalModel->update_action($existingApproval['id'], array(
                'action' => 'APPROVED',
                'action_at' => $now,
                'notes' => $notes === '' ? null : $notes,
                'approver_id' => $approverId,
            ));
        } else {
            $this->LeaveApprovalModel->insert(array(
                'leave_request_id' => (int) $id,
                'step_no' => 1,
                'approver_id' => $approverId,
                'action' => 'APPROVED',
                'action_at' => $now,
                'notes' => $notes === '' ? null : $notes,
            ));
        }

        $this->db->where('id', (int) $id);
        $this->db->update('leave_requests', array(
            'status' => 'APPROVED',
            'updated_at' => $now,
        ));

        $this->LeaveQuotaModel->deduct_quota(
            (int) $request['user_id'],
            (int) $request['leave_type_id'],
            (int) $request['days_count']
        );

        $this->db->trans_complete();

        $this->session->set_flashdata('message', 'Leave request approved and quota updated.');
        redirect('approvals/leaves');
    }

    public function reject($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $request = $this->LeaveRequestModel->get_by_id($id);
        if (!$request || $request['status'] !== 'PENDING_APPROVAL') {
            show_error('Leave request not found or already processed.', 404);
            return;
        }

        $notes = trim((string) $this->input->post('notes', TRUE));
        if ($notes === '') {
            $this->session->set_flashdata('error', 'Rejection notes are required.');
            redirect('approvals/leaves/' . $id);
            return;
        }

        $approverId = $this->current_user_id();
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $this->db->where('leave_request_id', (int) $id);
        $this->db->where('action', 'PENDING');
        $existingApproval = $this->db->get('leave_approvals')->row_array();

        if ($existingApproval) {
            $this->LeaveApprovalModel->update_action($existingApproval['id'], array(
                'action' => 'REJECTED',
                'action_at' => $now,
                'notes' => $notes,
                'approver_id' => $approverId,
            ));
        } else {
            $this->LeaveApprovalModel->insert(array(
                'leave_request_id' => (int) $id,
                'step_no' => 1,
                'approver_id' => $approverId,
                'action' => 'REJECTED',
                'action_at' => $now,
                'notes' => $notes,
            ));
        }

        $this->db->where('id', (int) $id);
        $this->db->update('leave_requests', array(
            'status' => 'REJECTED',
            'updated_at' => $now,
        ));
        $this->db->trans_complete();

        $this->session->set_flashdata('message', 'Leave request rejected.');
        redirect('approvals/leaves');
    }

    private function current_user_id()
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
    }
}
