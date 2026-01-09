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
        $this->load->model('LeaveTypeModel');
        $this->load->model('LeaveRequestModel');
        $this->load->model('LeaveApprovalModel');
        $this->load->model('ApprovalRouteModel');
        $this->load->model('HolidayModel');
        $this->load->model('LeaveQuotaModel');
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
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('leave/detail', $data, true);
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

        if (!in_array($request['status'], array('PENDING_APPROVAL', 'APPROVED'))) {
            $this->session->set_flashdata('message', 'This request cannot be cancelled.');
            redirect('leave/' . $request['id']);
            return;
        }

        $wasApproved = $request['status'] === 'APPROVED';

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

        if ($wasApproved) {
            $this->LeaveQuotaModel->restore_quota(
                (int) $request['user_id'],
                (int) $request['leave_type_id'],
                (int) $request['days_count']
            );
        }

        $this->db->trans_complete();

        $message = $wasApproved ? 'Leave request cancelled and quota restored.' : 'Leave request cancelled.';
        $this->session->set_flashdata('message', $message);
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
            'status' => 'PENDING_APPROVAL',
            'current_step' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $this->db->trans_complete();

        $this->session->set_flashdata('message', 'Leave request submitted.');
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
            $errors['start_date'] = 'Leave dates overlap with an existing request.';
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
        $uploadDir = FCPATH . 'writable/uploads/leaves/' . $requestNo . '/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return array('error' => 'Failed to create attachment directory.');
            }
        }

        $config['upload_path'] = $uploadDir;
        $config['allowed_types'] = 'pdf|jpg|jpeg|png';
        $config['max_size'] = 2048;
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload($fieldName)) {
            return array('error' => strip_tags($this->upload->display_errors('', '')));
        }

        $file = $this->upload->data();
        $relativePath = 'writable/uploads/leaves/' . $requestNo . '/' . $file['file_name'];

        return array('path' => $relativePath);
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
