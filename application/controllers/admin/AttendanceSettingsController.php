<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class AttendanceSettingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('AttendanceSettingsModel');
        $this->load->library('template');
    }

    public function index()
    {
        if ($this->input->method(TRUE) === 'POST') {
            return $this->store();
        }

        $data['title'] = 'Attendance Settings - ' . $this->template->title();
        $data['settings'] = $this->AttendanceSettingsModel->get_settings();
        $data['role_ids'] = $this->AttendanceSettingsModel->get_allowed_role_ids();
        $data['roles'] = $this->get_roles();
        $data['errors'] = array();
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/attendance_settings/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    private function store()
    {
        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean) = $this->validate_input($input);

        if (!empty($errors)) {
            $data['title'] = 'Attendance Settings - ' . $this->template->title();
            $data['settings'] = $clean;
            $data['role_ids'] = $clean['allowed_role_ids_list'];
            $data['roles'] = $this->get_roles();
            $data['errors'] = $errors;
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/attendance_settings/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $saveData = array(
            'weekend_type' => $clean['weekend_type'],
            'allowed_role_ids' => $clean['allowed_role_ids'],
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $settings = $this->AttendanceSettingsModel->get_settings();
        if (!$settings || empty($settings['id'])) {
            $saveData['created_at'] = date('Y-m-d H:i:s');
        }

        $this->AttendanceSettingsModel->save_settings($saveData);
        $this->session->set_flashdata('message', 'Attendance settings updated.');
        redirect('admin/attendance-settings');
    }

    private function validate_input($input)
    {
        $errors = array();
        $clean = array();

        $weekendType = strtoupper(trim((string) ($input['weekend_type'] ?? '')));
        if (!in_array($weekendType, array('SATURDAY_SUNDAY', 'SUNDAY_ONLY'), true)) {
            $errors['weekend_type'] = 'Weekend type is required.';
            $weekendType = 'SATURDAY_SUNDAY';
        }
        $clean['weekend_type'] = $weekendType;

        $roleIds = $input['allowed_role_ids'] ?? array();
        if (!is_array($roleIds)) {
            $roleIds = array();
        }
        $normalized = array();
        foreach ($roleIds as $roleId) {
            if (is_numeric($roleId)) {
                $normalized[] = (int) $roleId;
            }
        }
        $normalized = array_values(array_unique($normalized));
        if (empty($normalized)) {
            $errors['allowed_role_ids'] = 'Select at least one role.';
        }

        $clean['allowed_role_ids_list'] = $normalized;
        $clean['allowed_role_ids'] = implode(',', $normalized);

        return array($errors, $clean);
    }

    private function get_roles()
    {
        $rolesTable = $this->db->query("SHOW TABLES LIKE 'roles'")->result_array();
        if (empty($rolesTable)) {
            return array();
        }

        return $this->db->query("SELECT id, name, display_name FROM roles WHERE is_active = 1 ORDER BY display_name ASC")->result_array();
    }
}
