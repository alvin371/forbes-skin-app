<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Overtime extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('OvertimeTypeModel');
        $this->load->model('OvertimeRequestModel');
        $this->load->model('Office_model');
        $this->load->library('OvertimeCalculatorService');
        $this->load->library('OvertimeWorkflowEngine');
        $this->load->library('template');

        $this->set_public_methods([]);
        $this->set_method_permissions([
            'create' => 'create',
            'store' => 'create',
            'cancel' => 'delete',
        ]);
    }

    public function index()
    {
        $userId = $this->user_id;
        $status = $this->input->get('status');
        $month = $this->input->get('month');

        $allowedStatuses = array('SUBMITTED', 'IN_REVIEW', 'APPROVED', 'REJECTED', 'CANCELLED');
        if ($status && !in_array($status, $allowedStatuses, true)) {
            $status = null;
        }
        if ($month && !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = null;
        }

        $filters = array(
            'status' => $status,
            'month' => $month,
        );

        $permissionData = $this->get_permission_data('overtime');
        $data['can_create'] = $permissionData['can_create'];
        $data['can_delete'] = $permissionData['can_delete'];

        $data['filters'] = $filters;
        $data['requests'] = $this->OvertimeRequestModel->get_by_user($userId, $filters);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['title'] = 'Overtime Requests - ' . $this->template->title();
        $data['content'] = $this->load->view('overtime/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function create()
    {
        $data['overtime_types'] = $this->OvertimeTypeModel->get_active();
        $data['request'] = $this->empty_request();
        $data['errors'] = array();
        $data['form_action'] = site_url('overtime/store');
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['title'] = 'Submit Overtime Request - ' . $this->template->title();
        $data['content'] = $this->load->view('overtime/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function store()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $userId = $this->user_id;
        $errors = array();

        $overtimeTypeId = (int) $this->input->post('overtime_type_id', true);
        $overtimeDate = trim((string) $this->input->post('overtime_date', true));
        $startTime = trim((string) $this->input->post('start_time', true));
        $endTime = trim((string) $this->input->post('end_time', true));
        $reason = trim((string) $this->input->post('reason', true));

        $overtimeType = null;
        if ($overtimeTypeId <= 0) {
            $errors['overtime_type_id'] = 'Overtime type is required.';
        } else {
            $overtimeType = $this->OvertimeTypeModel->get_by_id($overtimeTypeId);
            if (!$overtimeType || (int) $overtimeType['is_active'] !== 1) {
                $errors['overtime_type_id'] = 'Overtime type is invalid.';
            }
        }

        if ($overtimeDate === '' || !$this->is_valid_date($overtimeDate)) {
            $errors['overtime_date'] = 'Valid overtime date is required.';
        }

        if ($startTime === '' || !$this->overtimecalculatorservice->is_valid_time($startTime)) {
            $errors['start_time'] = 'Valid start time is required.';
        }
        if ($endTime === '' || !$this->overtimecalculatorservice->is_valid_time($endTime)) {
            $errors['end_time'] = 'Valid end time is required.';
        }

        $durationHours = 0;
        if (empty($errors['start_time']) && empty($errors['end_time'])) {
            $durationHours = $this->overtimecalculatorservice->calculate_duration($startTime, $endTime);
            if ($durationHours <= 0) {
                $errors['end_time'] = 'End time must be after start time.';
            }
        }

        if ($reason === '') {
            $errors['reason'] = 'Reason is required.';
        }

        if (empty($errors) && $this->OvertimeRequestModel->has_overlap($userId, $overtimeDate, $startTime, $endTime)) {
            $errors['overtime_date'] = 'An overtime request already exists for this time period.';
        }

        $attachmentPath = null;
        if ($overtimeType && (int) $overtimeType['requires_attachment'] === 1) {
            if (empty($_FILES['attachment']['name'])) {
                $errors['attachment'] = 'Attachment is required for this overtime type.';
            }
        }

        $requestData = array(
            'overtime_type_id' => $overtimeTypeId,
            'overtime_date' => $overtimeDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_hours' => $durationHours,
            'reason' => $reason,
        );

        if (!empty($errors)) {
            $data['overtime_types'] = $this->OvertimeTypeModel->get_active();
            $data['request'] = $requestData;
            $data['errors'] = $errors;
            $data['form_action'] = site_url('overtime/store');
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['title'] = 'Submit Overtime Request - ' . $this->template->title();
            $data['content'] = $this->load->view('overtime/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $requestNo = $this->OvertimeRequestModel->generate_request_no();
        if (!empty($_FILES['attachment']['name'])) {
            $upload = $this->handle_attachment_upload($requestNo, 'attachment');
            if (isset($upload['error'])) {
                $data['overtime_types'] = $this->OvertimeTypeModel->get_active();
                $data['request'] = $requestData;
                $data['errors'] = array('attachment' => $upload['error']);
                $data['form_action'] = site_url('overtime/store');
                $data['csrf_name'] = $this->security->get_csrf_token_name();
                $data['csrf_hash'] = $this->security->get_csrf_hash();
                $data['title'] = 'Submit Overtime Request - ' . $this->template->title();
                $data['content'] = $this->load->view('overtime/form', $data, true);
                $this->load->view('TemplateDashboard', $data);
                return;
            }
            $attachmentPath = $upload['path'];
        }

        $office = $this->Office_model->get_active_office();
        $now = date('Y-m-d H:i:s');

        $requestId = $this->OvertimeRequestModel->insert(array(
            'request_no' => $requestNo,
            'user_id' => $userId,
            'overtime_type_id' => $overtimeTypeId,
            'office_id' => $office ? $office['id'] : null,
            'overtime_date' => $overtimeDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_hours' => $durationHours,
            'reason' => $reason,
            'attachment_path' => $attachmentPath,
            'status' => 'SUBMITTED',
            'submitted_at' => $now,
            'created_by' => $userId,
            'updated_by' => $userId,
        ));

        if (!$requestId) {
            $this->session->set_flashdata('error', 'Failed to create overtime request.');
            redirect('overtime/create');
            return;
        }

        $workflowResult = $this->overtimeworkflowengine->initializeWorkflow($requestId);

        if ($workflowResult['success']) {
            $this->session->set_flashdata('success', 'Overtime request submitted and sent for approval.');
            redirect('overtime');
            return;
        }

        if (isset($workflowResult['code']) && in_array($workflowResult['code'], array('NO_ROUTE', 'NO_STEPS'), true)) {
            $this->OvertimeRequestModel->delete($requestId);
            $this->session->set_flashdata('error', 'Pengajuan lembur gagal: ' . $workflowResult['message']);
            redirect('overtime/create');
            return;
        }

        $this->session->set_flashdata('warning', 'Request saved, but approval workflow could not be started: ' . $workflowResult['message']);
        redirect('overtime');
    }

    public function detail($id)
    {
        $userId = $this->user_id;
        $request = $this->OvertimeRequestModel->get_by_id((int) $id);
        if (!$request || (int) $request['user_id'] !== (int) $userId) {
            show_404();
            return;
        }

        $permissionData = $this->get_permission_data('overtime');
        $data['can_delete'] = $permissionData['can_delete'];
        $data['request'] = $request;
        $data['progress'] = $this->overtimeworkflowengine->getWorkflowProgress((int) $id);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['title'] = 'Overtime Request Detail - ' . $this->template->title();
        $data['content'] = $this->load->view('overtime/detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function cancel($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $userId = $this->user_id;
        $result = $this->overtimeworkflowengine->cancelWorkflow((int) $id, (int) $userId);

        if ($result['success']) {
            $this->session->set_flashdata('success', $result['message']);
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }

        redirect('overtime/' . (int) $id);
    }

    private function empty_request()
    {
        return array(
            'overtime_type_id' => '',
            'overtime_date' => date('Y-m-d'),
            'start_time' => '',
            'end_time' => '',
            'duration_hours' => '',
            'reason' => '',
        );
    }

    private function is_valid_date($date)
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }

    private function handle_attachment_upload($requestNo, $fieldName)
    {
        $uploadDir = FCPATH . 'writable/uploads/overtime/' . $requestNo . '/';
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
        $relativePath = 'writable/uploads/overtime/' . $requestNo . '/' . $file['file_name'];

        return array('path' => $relativePath);
    }
}
