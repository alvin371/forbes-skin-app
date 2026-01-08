<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class LeaveTypesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('LeaveTypeModel');
        $this->load->database();
        $this->load->library('template');
        $this->load->library('AdminAuthFilter');
        $this->adminauthfilter->enforce();
    }

    public function index()
    {
        if ($this->input->method(TRUE) === 'POST') {
            return $this->store();
        }

        $data['title'] = 'Leave Types - ' . $this->template->title();
        $data['leave_types'] = $this->LeaveTypeModel->get_all(TRUE);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/leave_types/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function create()
    {
        $data['title'] = 'Create Leave Type - ' . $this->template->title();
        $data['leave_type'] = $this->empty_leave_type();
        $data['errors'] = array();
        $data['form_action'] = site_url('admin/leave-types');
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/leave_types/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function edit($id)
    {
        $leaveType = $this->LeaveTypeModel->get_by_id($id);
        if (!$leaveType) {
            show_404();
            return;
        }

        $data['title'] = 'Edit Leave Type - ' . $this->template->title();
        $data['leave_type'] = $leaveType;
        $data['errors'] = array();
        $data['form_action'] = site_url('admin/leave-types/' . $leaveType['id']);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/leave_types/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function update($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $leaveType = $this->LeaveTypeModel->get_by_id($id);
        if (!$leaveType) {
            show_404();
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean) = $this->validate_leave_type($input, $leaveType['id']);

        if (!empty($errors)) {
            $data['title'] = 'Edit Leave Type - ' . $this->template->title();
            $data['leave_type'] = array_merge($leaveType, $clean);
            $data['errors'] = $errors;
            $data['form_action'] = site_url('admin/leave-types/' . $leaveType['id']);
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/leave_types/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $updateData = array(
            'code' => $clean['code'],
            'name' => $clean['name'],
            'is_paid' => $clean['is_paid'] ? 1 : 0,
            'requires_attachment' => $clean['requires_attachment'] ? 1 : 0,
            'max_days_per_request' => $clean['max_days_per_request'],
            'is_active' => $clean['is_active'] ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->LeaveTypeModel->update($leaveType['id'], $updateData);
        $this->session->set_flashdata('message', 'Leave type updated.');
        redirect('admin/leave-types');
    }

    public function delete($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $leaveType = $this->LeaveTypeModel->get_by_id($id);
        if (!$leaveType) {
            show_404();
            return;
        }

        $this->LeaveTypeModel->deactivate($leaveType['id']);
        $this->session->set_flashdata('message', 'Leave type deactivated.');
        redirect('admin/leave-types');
    }

    private function store()
    {
        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean) = $this->validate_leave_type($input);

        if (!empty($errors)) {
            $data['title'] = 'Create Leave Type - ' . $this->template->title();
            $data['leave_type'] = array_merge($this->empty_leave_type(), $clean);
            $data['errors'] = $errors;
            $data['form_action'] = site_url('admin/leave-types');
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/leave_types/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $insertData = array(
            'code' => $clean['code'],
            'name' => $clean['name'],
            'is_paid' => $clean['is_paid'] ? 1 : 0,
            'requires_attachment' => $clean['requires_attachment'] ? 1 : 0,
            'max_days_per_request' => $clean['max_days_per_request'],
            'is_active' => $clean['is_active'] ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->LeaveTypeModel->insert($insertData);
        $this->session->set_flashdata('message', 'Leave type created.');
        redirect('admin/leave-types');
    }

    private function validate_leave_type($input, $currentId = null)
    {
        $errors = array();
        $clean = array();

        $clean['code'] = strtoupper(trim((string) ($input['code'] ?? '')));
        if ($clean['code'] === '') {
            $errors['code'] = 'Code is required.';
        } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $clean['code'])) {
            $errors['code'] = 'Code may only contain letters, numbers, underscores, or dashes.';
        } else {
            $existing = $this->LeaveTypeModel->get_by_code($clean['code']);
            if ($existing && (int) $existing['id'] !== (int) $currentId) {
                $errors['code'] = 'Code must be unique.';
            }
        }

        $clean['name'] = trim((string) ($input['name'] ?? ''));
        if ($clean['name'] === '') {
            $errors['name'] = 'Name is required.';
        }

        $maxDaysRaw = trim((string) ($input['max_days_per_request'] ?? ''));
        if ($maxDaysRaw === '') {
            $clean['max_days_per_request'] = null;
        } elseif (!is_numeric($maxDaysRaw) || (int) $maxDaysRaw < 1) {
            $errors['max_days_per_request'] = 'Max days per request must be at least 1.';
            $clean['max_days_per_request'] = $maxDaysRaw;
        } else {
            $clean['max_days_per_request'] = (int) $maxDaysRaw;
        }

        $clean['is_paid'] = isset($input['is_paid']) && $input['is_paid'] === '1';
        $clean['requires_attachment'] = isset($input['requires_attachment']) && $input['requires_attachment'] === '1';
        $clean['is_active'] = isset($input['is_active']) && $input['is_active'] === '1';

        return array($errors, $clean);
    }

    private function empty_leave_type()
    {
        return array(
            'id' => null,
            'code' => '',
            'name' => '',
            'is_paid' => 0,
            'requires_attachment' => 0,
            'max_days_per_request' => '',
            'is_active' => 1,
        );
    }
}
