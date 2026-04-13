<?php
defined('BASEPATH') or exit('No direct script access allowed');

class LeaveController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('template');
        $this->load->library('AuthFilter');
        $this->load->library('LeaveCalculatorService');
        $this->load->library('LeaveOverlapService');
        $this->load->library('RequestNoGenerator');
        $this->load->library('ApprovalWorkflowEngine');
        $this->load->library('LeaveQuotaService');
        $this->load->library('UploadService');
        $this->load->model('LeaveTypeModel');
        $this->load->model('LeaveRequestModel');
        $this->load->model('LeaveApprovalModel');
        $this->load->model('ApprovalRouteModel');
        $this->load->model('HolidayModel');
        $this->load->model('LeaveQuotaModel');
        $this->load->model('ApprovalStepModel');
        $this->authfilter->enforce();
    }

    public function index()
    {
        if ($this->input->method(TRUE) === 'POST') {
            return $this->store();
        }

        $userId = $this->current_user_id();
        $data['title'] = 'My Leave Requests - ' . $this->template->title();
        $data['requests'] = $this->LeaveRequestModel->get_by_user($userId);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('leave/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function create()
    {
        $data['title'] = 'Submit Leave Request - ' . $this->template->title();
        $data['leave_types'] = $this->LeaveTypeModel->get_active();
        $data['request'] = $this->empty_request();
        $data['errors'] = array();
        $data['holidays'] = $this->HolidayModel->get_active();
        $data['form_action'] = site_url('leave');
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('leave/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function detail($id)
    {
        $userId = $this->current_user_id();
        $request = $this->LeaveRequestModel->get_by_id($id);
        if (!$request || (int) $request['user_id'] !== (int) $userId) {
            show_404();
            return;
        }

        $data['title'] = 'Leave Request Detail - ' . $this->template->title();
        $data['request'] = $request;

        // Get approval workflow progress
        $data['progress'] = $this->approvalworkflowengine->getWorkflowProgress($id);
        $data['approval_steps'] = $this->ApprovalStepModel->get_by_leave_request($id);

        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('leave/detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * Submit a draft leave request for approval
     */
    public function submit($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $userId = $this->current_user_id();
        $request = $this->LeaveRequestModel->get_by_id($id);

        if (!$request || (int) $request['user_id'] !== (int) $userId) {
            show_404();
            return;
        }

        // Check if request can be submitted
        if (!in_array($request['status'], array('DRAFT', 'NEEDS_ROUTE'))) {
            $this->session->set_flashdata('error', 'Pengajuan ini tidak dapat disubmit.');
            redirect('leave/' . $id);
            return;
        }

        // Validate quota before submission
        $quotaCheck = $this->leavequotaservice->validateQuota(
            $userId,
            $request['leave_type_id'],
            $request['days_count'],
            $id
        );

        if (!$quotaCheck['valid']) {
            $this->session->set_flashdata('error', 'Kuota cuti tidak mencukupi: ' . $quotaCheck['message']);
            redirect('leave/' . $id);
            return;
        }

        // Initialize workflow
        $result = $this->approvalworkflowengine->initializeWorkflow($id);

        if ($result['success']) {
            if (isset($result['needs_route']) && $result['needs_route']) {
                $this->session->set_flashdata('warning', $result['message']);
            } else {
                $this->session->set_flashdata('success', 'Pengajuan cuti berhasil disubmit untuk persetujuan.');
            }
        } else {
            $this->session->set_flashdata('error', 'Gagal submit pengajuan: ' . $result['message']);
        }

        redirect('leave/' . $id);
    }

    /**
     * View approval progress timeline
     */
    public function progress($id)
    {
        $userId = $this->current_user_id();
        $request = $this->LeaveRequestModel->get_by_id($id);

        if (!$request || (int) $request['user_id'] !== (int) $userId) {
            show_404();
            return;
        }

        $data['title'] = 'Approval Progress - ' . $this->template->title();
        $data['request'] = $request;
        $data['progress'] = $this->approvalworkflowengine->getWorkflowProgress($id);
        $data['approval_steps'] = $this->ApprovalStepModel->get_by_leave_request($id);

        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('leave/progress', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * View user's quota summary
     */
    public function quota()
    {
        $userId = $this->current_user_id();
        $year = $this->input->get('year') ?: date('Y');

        $data['title'] = 'Kuota Cuti Saya - ' . $this->template->title();
        $data['quotas'] = $this->leavequotaservice->getQuotaSummary($userId, $year);
        $data['ledger'] = $this->leavequotaservice->getLedgerEntries($userId, $year);
        $data['logs'] = $this->leavequotaservice->getQuotaLogs($userId, 20);
        $data['year'] = $year;
        $data['years'] = range(date('Y') - 2, date('Y') + 1);

        $data['content'] = $this->load->view('leave/quota', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function cancel($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $userId = $this->current_user_id();
        $request = $this->LeaveRequestModel->get_by_id($id);
        if (!$request || (int) $request['user_id'] !== (int) $userId) {
            show_404();
            return;
        }

        // Allow cancellation for more statuses
        $cancellableStatuses = array('DRAFT', 'SUBMITTED', 'PENDING_APPROVAL', 'IN_REVIEW', 'NEEDS_ROUTE', 'APPROVED');
        if (!in_array($request['status'], $cancellableStatuses)) {
            $this->session->set_flashdata('error', 'Pengajuan ini tidak dapat dibatalkan.');
            redirect('leave/' . $request['id']);
            return;
        }

        $wasApproved = $request['status'] === 'APPROVED';

        // Use workflow engine to cancel
        $result = $this->approvalworkflowengine->cancelWorkflow($id, $userId);

        if (!$result['success']) {
            // Fallback to direct update if workflow engine fails
            $now = date('Y-m-d H:i:s');
            $this->db->trans_start();
            $this->LeaveRequestModel->update($request['id'], array(
                'status' => 'CANCELLED',
                'updated_at' => $now,
            ));

            $this->db->where('leave_request_id', (int) $request['id']);
            $this->db->where('action', 'PENDING');
            $this->db->update('leave_approvals', array(
                'action' => 'REJECTED',
                'action_at' => $now,
                'notes' => 'Cancelled by requester.',
            ));
            $this->db->trans_complete();
        }

        // Restore quota if was approved
        if ($wasApproved) {
            $this->leavequotaservice->restoreQuota(
                (int) $request['user_id'],
                (int) $request['leave_type_id'],
                (int) $request['days_count'],
                (int) $request['id'],
                $userId,
                'Pengajuan dibatalkan oleh pemohon'
            );

            // Delete ledger entry
            $this->load->model('LeaveLedgerModel');
            $this->LeaveLedgerModel->delete_by_leave_request($id);
        }

        $message = $wasApproved ? 'Pengajuan cuti dibatalkan dan kuota dikembalikan.' : 'Pengajuan cuti dibatalkan.';
        $this->session->set_flashdata('success', $message);
        redirect('leave');
    }

    private function store()
    {
        $userId = $this->current_user_id();
        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean, $leaveType) = $this->validate_request($input, $userId);

        if (!empty($errors)) {
            $data['title'] = 'Submit Leave Request - ' . $this->template->title();
            $data['leave_types'] = $this->LeaveTypeModel->get_active();
            $data['request'] = array_merge($this->empty_request(), $clean);
            $data['errors'] = $errors;
            $data['holidays'] = $this->HolidayModel->get_active();
            $data['form_action'] = site_url('leave');
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('leave/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $requestNo = $this->requestnogenerator->generate_unique();
        $attachmentPath = null;

        if ($leaveType && (int) $leaveType['requires_attachment'] === 1) {
            $upload = $this->handle_attachment_upload($requestNo, 'attachment');
            if (isset($upload['error'])) {
                $data['title'] = 'Submit Leave Request - ' . $this->template->title();
                $data['leave_types'] = $this->LeaveTypeModel->get_active();
                $data['request'] = array_merge($this->empty_request(), $clean);
                $data['errors'] = array('attachment' => $upload['error']);
                $data['holidays'] = $this->HolidayModel->get_active();
                $data['form_action'] = site_url('leave');
                $data['csrf_name'] = $this->security->get_csrf_token_name();
                $data['csrf_hash'] = $this->security->get_csrf_hash();
                $data['content'] = $this->load->view('leave/form', $data, true);
                $this->load->view('TemplateDashboard', $data);
                return;
            }

            $attachmentPath = $upload['path'];
        } elseif (!empty($_FILES['attachment']['name'])) {
            $upload = $this->handle_attachment_upload($requestNo, 'attachment');
            if (isset($upload['error'])) {
                $data['title'] = 'Submit Leave Request - ' . $this->template->title();
                $data['leave_types'] = $this->LeaveTypeModel->get_active();
                $data['request'] = array_merge($this->empty_request(), $clean);
                $data['errors'] = array('attachment' => $upload['error']);
                $data['holidays'] = $this->HolidayModel->get_active();
                $data['form_action'] = site_url('leave');
                $data['csrf_name'] = $this->security->get_csrf_token_name();
                $data['csrf_hash'] = $this->security->get_csrf_hash();
                $data['content'] = $this->load->view('leave/form', $data, true);
                $this->load->view('TemplateDashboard', $data);
                return;
            }

            $attachmentPath = $upload['path'];
        }

        $now = date('Y-m-d H:i:s');
        // Default to submit immediately unless explicitly set to '0' (draft mode)
        $submitNowField = $this->input->post('submit_now');
        $submitNow = $submitNowField !== '0';

        $this->db->trans_start();
        $requestId = $this->LeaveRequestModel->insert(array(
            'request_no' => $requestNo,
            'user_id' => $userId,
            'leave_type_id' => $clean['leave_type_id'],
            'start_date' => $clean['start_date'],
            'end_date' => $clean['end_date'],
            'days_count' => $clean['days_count'],
            'reason' => $clean['reason'],
            'attachment_path' => $attachmentPath,
            'status' => $submitNow ? 'SUBMITTED' : 'DRAFT',
            'current_step' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $this->db->trans_complete();

        // If submit now, initialize workflow
        if ($submitNow && $requestId) {
            $result = $this->approvalworkflowengine->initializeWorkflow($requestId);
            if ($result['success']) {
                if (isset($result['needs_route']) && $result['needs_route']) {
                    $this->session->set_flashdata('warning', 'Pengajuan tersimpan tapi tidak ada rute approval yang cocok. HR akan menentukan rute secara manual.');
                } else {
                    $this->session->set_flashdata('success', 'Pengajuan cuti berhasil disubmit untuk persetujuan.');
                }
            } else {
                $this->session->set_flashdata('warning', 'Pengajuan tersimpan tapi gagal memulai workflow: ' . $result['message']);
            }
        } else {
            $this->session->set_flashdata('success', 'Pengajuan cuti berhasil disimpan sebagai draft.');
        }

        redirect('leave');
    }

    private function validate_request($input, $userId)
    {
        $errors = array();
        $clean = array();
        $leaveType = null;

        $clean['leave_type_id'] = (int) ($input['leave_type_id'] ?? 0);
        if ($clean['leave_type_id'] <= 0) {
            $errors['leave_type_id'] = 'Leave type is required.';
        } else {
            $leaveType = $this->LeaveTypeModel->get_by_id($clean['leave_type_id']);
            if (!$leaveType || (int) $leaveType['is_active'] !== 1) {
                $errors['leave_type_id'] = 'Leave type is invalid.';
            }
        }

        $clean['start_date'] = trim((string) ($input['start_date'] ?? ''));
        $clean['end_date'] = trim((string) ($input['end_date'] ?? ''));
        if ($clean['start_date'] === '' || $clean['end_date'] === '') {
            $errors['start_date'] = 'Start date and end date are required.';
        } elseif (!$this->is_valid_date($clean['start_date']) || !$this->is_valid_date($clean['end_date'])) {
            $errors['start_date'] = 'Invalid date format.';
        } elseif (strtotime($clean['start_date']) > strtotime($clean['end_date'])) {
            $errors['start_date'] = 'Start date must be before or equal to end date.';
        }

        $clean['reason'] = trim((string) ($input['reason'] ?? ''));
        $clean['days_count'] = $this->leavecalculatorservice->calculate_days($clean['start_date'], $clean['end_date']);
        if ($clean['days_count'] <= 0) {
            $errors['days_count'] = 'Unable to calculate leave days.';
        }

        if ($leaveType && !empty($leaveType['max_days_per_request'])) {
            if ((int) $leaveType['max_days_per_request'] < $clean['days_count']) {
                $errors['days_count'] = 'Requested days exceed the leave type limit.';
            }
        }

        if (empty($errors) && $this->leaveoverlapservice->has_overlap($userId, $clean['start_date'], $clean['end_date'])) {
            $errors['start_date'] = 'A leave application already exists for the selected date range.';
        }

        if ($leaveType && (int) $leaveType['requires_attachment'] === 1) {
            if (empty($_FILES['attachment']['name'])) {
                $errors['attachment'] = 'Attachment is required for this leave type.';
            }
        }

        return array($errors, $clean, $leaveType);
    }

    private function handle_attachment_upload($requestNo, $fieldName)
    {
        return $this->uploadservice->upload('leave', $fieldName, array('subdir' => $requestNo));
    }

    private function is_valid_date($date)
    {
        $format = 'Y-m-d';
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    private function current_user_id()
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
    }

    private function empty_request()
    {
        return array(
            'leave_type_id' => '',
            'start_date' => '',
            'end_date' => '',
            'reason' => '',
            'days_count' => '',
        );
    }
}
