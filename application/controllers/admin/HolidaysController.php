<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class HolidaysController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('HolidayModel');
        $this->load->library('template');
        $this->load->library('HrAdminFilter');
        $this->hradminfilter->enforce();
    }

    public function index()
    {
        if ($this->input->method(TRUE) === 'POST') {
            return $this->store();
        }

        $data['title'] = 'Holidays - ' . $this->template->title();
        $data['holidays'] = $this->HolidayModel->get_all();
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/holidays/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function create()
    {
        $data['title'] = 'Create Holiday - ' . $this->template->title();
        $data['holiday'] = $this->empty_holiday();
        $data['errors'] = array();
        $data['form_action'] = site_url('admin/holidays');
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/holidays/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function edit($id)
    {
        $holiday = $this->HolidayModel->get_by_id($id);
        if (!$holiday) {
            show_404();
            return;
        }

        $data['title'] = 'Edit Holiday - ' . $this->template->title();
        $data['holiday'] = $holiday;
        $data['errors'] = array();
        $data['form_action'] = site_url('admin/holidays/' . $holiday['id']);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/holidays/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function update($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $holiday = $this->HolidayModel->get_by_id($id);
        if (!$holiday) {
            show_404();
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean) = $this->validate_holiday($input, $holiday['id']);

        if (!empty($errors)) {
            $data['title'] = 'Edit Holiday - ' . $this->template->title();
            $data['holiday'] = array_merge($holiday, $clean);
            $data['errors'] = $errors;
            $data['form_action'] = site_url('admin/holidays/' . $holiday['id']);
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/holidays/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $this->HolidayModel->update($holiday['id'], array(
            'date' => $clean['date'],
            'name' => $clean['name'],
            'is_active' => $clean['is_active'] ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        $this->session->set_flashdata('message', 'Holiday updated.');
        redirect('admin/holidays');
    }

    public function delete($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $holiday = $this->HolidayModel->get_by_id($id);
        if (!$holiday) {
            show_404();
            return;
        }

        $this->HolidayModel->delete($holiday['id']);
        $this->session->set_flashdata('message', 'Holiday deleted.');
        redirect('admin/holidays');
    }

    private function store()
    {
        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean) = $this->validate_holiday($input);

        if (!empty($errors)) {
            $data['title'] = 'Create Holiday - ' . $this->template->title();
            $data['holiday'] = array_merge($this->empty_holiday(), $clean);
            $data['errors'] = $errors;
            $data['form_action'] = site_url('admin/holidays');
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/holidays/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $this->HolidayModel->insert(array(
            'date' => $clean['date'],
            'name' => $clean['name'],
            'is_active' => $clean['is_active'] ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        $this->session->set_flashdata('message', 'Holiday created.');
        redirect('admin/holidays');
    }

    private function validate_holiday($input, $currentId = null)
    {
        $errors = array();
        $clean = array();

        $clean['date'] = trim((string) ($input['date'] ?? ''));
        if ($clean['date'] === '' || !$this->is_valid_date($clean['date'])) {
            $errors['date'] = 'Valid date is required.';
        } else {
            $existing = $this->HolidayModel->get_by_date($clean['date']);
            if ($existing && (int) $existing['id'] !== (int) $currentId) {
                $errors['date'] = 'Holiday date must be unique.';
            }
        }

        $clean['name'] = trim((string) ($input['name'] ?? ''));
        if ($clean['name'] === '') {
            $errors['name'] = 'Name is required.';
        }

        $clean['is_active'] = isset($input['is_active']) && $input['is_active'] === '1';

        return array($errors, $clean);
    }

    private function is_valid_date($date)
    {
        $format = 'Y-m-d';
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    private function empty_holiday()
    {
        return array(
            'id' => null,
            'date' => '',
            'name' => '',
            'is_active' => 1,
        );
    }
}
