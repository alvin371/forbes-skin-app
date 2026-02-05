<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeApprovalController
 *
 * Controller for approvers to manage their pending overtime approval tasks.
 * Displays pending approvals, allows approve/reject actions, and shows history.
 */
class OvertimeApprovalController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->library('AuthFilter');
        $this->load->library('OvertimeWorkflowEngine');
        $this->load->library('permission');
        $this->load->library('template');
        $this->load->model('OvertimeApprovalStepModel');
        $this->load->model('OvertimeApprovalInstanceModel');
        $this->load->model('OvertimeRequestModel');
        $this->load->helper('url');

        // Check authentication
        $this->authfilter->enforce();

        // Enforce module permission
        $this->enforce_permission('view');
    }

    /**
     * Display pending overtime approval inbox
     */
    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];

        $data['pending_approvals'] = $this->OvertimeApprovalStepModel->get_pending_for_approver($userId);
        $data['pending_count'] = count($data['pending_approvals']);

        $data['title'] = 'Approval Lembur - ' . $this->template->title();
        $data['content'] = $this->load->view('approvals/overtime_inbox', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * View approval detail
     */
    public function detail($stepId)
    {
        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];

        $step = $this->OvertimeApprovalStepModel->get_by_id($stepId);
        if (!$step) {
            $this->session->set_flashdata('error', 'Langkah approval tidak ditemukan');
            redirect('approvals/overtime');
        }

        if (!$this->overtimeworkflowengine->canUserApprove($userId, $step)) {
            $this->session->set_flashdata('error', 'Anda tidak berhak mengakses approval ini');
            redirect('approvals/overtime');
        }

        $data['overtime_request'] = $this->OvertimeRequestModel->get_by_id($step['overtime_request_id']);
        if (!$data['overtime_request']) {
            $this->session->set_flashdata('error', 'Pengajuan lembur tidak ditemukan');
            redirect('approvals/overtime');
        }

        $data['progress'] = $this->overtimeworkflowengine->getWorkflowProgress($step['overtime_request_id']);
        $data['step'] = $step;
        $data['all_steps'] = $this->OvertimeApprovalStepModel->get_by_overtime_request($step['overtime_request_id']);

        $requester = $this->db->query("
            SELECT u.*, up.position_id, p.name as position_name
            FROM user u
            LEFT JOIN user_profile up ON u.id = up.user_id
            LEFT JOIN positions p ON up.position_id = p.id
            WHERE u.id = ?
        ", array($data['overtime_request']['user_id']))->row_array();
        $data['requester'] = $requester;

        $data['title'] = 'Detail Approval Lembur: ' . $data['overtime_request']['request_no'] . ' - ' . $this->template->title();
        $data['content'] = $this->load->view('approvals/overtime_inbox_detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Approve a step
     */
    public function approve($stepId)
    {
        if ($this->input->method() !== 'post') {
            redirect('approvals/overtime');
        }

        $this->enforce_permission('approve');

        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];
        $notes = $this->input->post('notes');

        $result = $this->overtimeworkflowengine->processApproval(
            $stepId,
            $userId,
            'APPROVED',
            $notes
        );

        if ($result['success']) {
            if (isset($result['is_final']) && $result['is_final']) {
                $this->session->set_flashdata('success', 'Pengajuan lembur telah disetujui sepenuhnya');
            } else {
                $this->session->set_flashdata('success', 'Langkah approval berhasil. Dilanjutkan ke approver berikutnya.');
            }
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }

        redirect('approvals/overtime');
    }

    /**
     * Reject a request
     */
    public function reject($stepId)
    {
        if ($this->input->method() !== 'post') {
            redirect('approvals/overtime');
        }

        $this->enforce_permission('approve');

        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];
        $notes = $this->input->post('notes');

        if (empty($notes)) {
            $this->session->set_flashdata('error', 'Alasan penolakan wajib diisi');
            redirect('approvals/overtime/detail/' . $stepId);
        }

        $result = $this->overtimeworkflowengine->processApproval(
            $stepId,
            $userId,
            'REJECTED',
            $notes
        );

        if ($result['success']) {
            $this->session->set_flashdata('success', 'Pengajuan lembur telah ditolak');
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }

        redirect('approvals/overtime');
    }

    /**
     * View approval history
     */
    public function history()
    {
        $data['user'] = $_SESSION['user'];
        $userId = $data['user']['id'];

        $limit = $this->input->get('limit') ?: 50;
        $data['approvals'] = $this->OvertimeApprovalStepModel->get_history_for_approver($userId, $limit);

        $data['title'] = 'Riwayat Approval Lembur - ' . $this->template->title();
        $data['content'] = $this->load->view('approvals/overtime_history', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Get pending count (AJAX)
     */
    public function pending_count()
    {
        $userId = $_SESSION['user']['id'];
        $count = $this->OvertimeApprovalStepModel->count_pending_for_approver($userId);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'count' => $count
            )));
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

        $this->enforce_permission('approve');

        $userId = $_SESSION['user']['id'];
        $stepId = $this->input->post('step_id');
        $notes = $this->input->post('notes') ?: 'Disetujui';

        $result = $this->overtimeworkflowengine->processApproval($stepId, $userId, 'APPROVED', $notes);

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

        $this->enforce_permission('approve');

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

        $result = $this->overtimeworkflowengine->processApproval($stepId, $userId, 'REJECTED', $notes);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    private function enforce_permission($action = 'view')
    {
        $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        if (!$userId) {
            redirect(base_url('auth/login'));
            exit;
        }

        if ($this->permission->check_permission($userId, 'approval_inbox', $action)) {
            return;
        }

        $this->output->set_status_header(403);
        $data = array(
            'heading' => 'Access Forbidden',
            'message' => 'You do not have permission to access this resource.',
        );
        $this->load->view('errors/html/error_403', $data);
        exit;
    }
}
