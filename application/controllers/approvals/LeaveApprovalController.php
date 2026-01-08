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
        $this->load->model('LeaveRequestModel');
        $this->load->model('LeaveApprovalModel');
        $this->authfilter->enforce();
        $this->approverauthfilter->enforce();
    }

    public function index()
    {
        $approverId = $this->current_user_id();
        $data['title'] = 'Pending Leave Approvals - ' . $this->template->title();
        $data['requests'] = $this->LeaveRequestModel->get_pending_for_approver($approverId);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('approvals/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function detail($id)
    {
        $approverId = $this->current_user_id();
        $request = $this->LeaveRequestModel->get_by_id($id);
        if (!$request) {
            show_404();
            return;
        }

        $approval = $this->LeaveApprovalModel->get_by_request_and_approver($request['id'], $approverId);
        if (!$approval) {
            $this->output->set_status_header(403);
            $data = array(
                'heading' => 'Access Forbidden',
                'message' => 'You do not have permission to access this resource.',
            );
            $this->load->view('errors/html/error_403', $data);
            return;
        }

        $data['title'] = 'Leave Approval Detail - ' . $this->template->title();
        $data['request'] = $request;
        $data['approval'] = $approval;
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

        $approverId = $this->current_user_id();
        $approval = $this->LeaveApprovalModel->get_by_request_and_approver($id, $approverId);
        if (!$approval || $approval['action'] !== 'PENDING') {
            show_error('Approval not found or already processed.', 404);
            return;
        }

        $notes = trim((string) $this->input->post('notes', TRUE));
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $this->LeaveApprovalModel->update_action($approval['id'], array(
            'action' => 'APPROVED',
            'action_at' => $now,
            'notes' => $notes === '' ? null : $notes,
        ));
        $this->db->where('id', (int) $id);
        $this->db->update('leave_requests', array(
            'status' => 'APPROVED',
            'updated_at' => $now,
        ));
        $this->db->trans_complete();

        $this->session->set_flashdata('message', 'Leave request approved.');
        redirect('approvals/leaves');
    }

    public function reject($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $approverId = $this->current_user_id();
        $approval = $this->LeaveApprovalModel->get_by_request_and_approver($id, $approverId);
        if (!$approval || $approval['action'] !== 'PENDING') {
            show_error('Approval not found or already processed.', 404);
            return;
        }

        $notes = trim((string) $this->input->post('notes', TRUE));
        if ($notes === '') {
            $this->session->set_flashdata('error', 'Rejection notes are required.');
            redirect('approvals/leaves/' . $id);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->LeaveApprovalModel->update_action($approval['id'], array(
            'action' => 'REJECTED',
            'action_at' => $now,
            'notes' => $notes,
        ));
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
