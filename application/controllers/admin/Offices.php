<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Offices extends BaseController
{
    protected $public_methods = ['index', 'create', 'edit', 'update', 'duplicate', 'delete', 'activate', 'deactivate'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Office_model');
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

        $data['title'] = 'Office Settings - ' . $this->template->title();
        $data['offices'] = $this->Office_model->get_all();
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/offices/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function create()
    {
        $data['title'] = 'Create Office - ' . $this->template->title();
        $data['office'] = $this->empty_office();
        $data['errors'] = array();
        $data['form_action'] = site_url('admin/offices');
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/offices/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function edit($id)
    {
        $office = $this->Office_model->get_by_id($id);
        if (!$office) {
            show_404();
            return;
        }

        $data['title'] = 'Edit Office - ' . $this->template->title();
        $data['office'] = $office;
        $data['errors'] = array();
        $data['form_action'] = site_url('admin/offices/' . $office['id']);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/offices/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function update($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $office = $this->Office_model->get_by_id($id);
        if (!$office) {
            show_404();
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean) = $this->validate_office($input);

        if (!empty($errors)) {
            $data['title'] = 'Edit Office - ' . $this->template->title();
            $data['office'] = array_merge($office, $clean);
            $data['errors'] = $errors;
            $data['form_action'] = site_url('admin/offices/' . $office['id']);
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/offices/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $updateData = array(
            'name' => $clean['name'],
            'lat' => $clean['lat'],
            'lng' => $clean['lng'],
            'radius_m' => $clean['radius_m'],
            'min_accuracy_m' => $clean['min_accuracy_m'],
            'allowed_ip_cidrs' => $clean['allowed_ip_cidrs'] === '' ? NULL : $clean['allowed_ip_cidrs'],
            'allowed_bssids' => $clean['allowed_bssids'] === '' ? NULL : $clean['allowed_bssids'],
            'allowed_ssids' => $clean['allowed_ssids'] === '' ? NULL : $clean['allowed_ssids'],
            'attendance_response_times' => $clean['attendance_response_times'] === '' ? NULL : $clean['attendance_response_times'],
            'attendance_history_days' => $clean['attendance_history_days'],
            'attendance_recap_months' => $clean['attendance_recap_months'],
            'is_active' => $clean['is_active'] ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->db->where('id', $office['id']);
        $this->db->update('offices', $updateData);

        $this->session->set_flashdata('message', 'Office updated.');
        redirect('admin/offices');
    }

    public function delete($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $this->db->where('id', (int) $id);
        $this->db->delete('offices');
        $this->session->set_flashdata('message', 'Office deleted.');
        redirect('admin/offices');
    }

    public function duplicate($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $office = $this->Office_model->get_by_id($id);
        if (!$office) {
            show_404();
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $insertData = array(
            'name' => $this->Office_model->generate_duplicate_name($office['name']),
            'lat' => $office['lat'],
            'lng' => $office['lng'],
            'radius_m' => $office['radius_m'],
            'min_accuracy_m' => $office['min_accuracy_m'],
            'allowed_ip_cidrs' => $office['allowed_ip_cidrs'],
            'allowed_bssids' => $office['allowed_bssids'],
            'allowed_ssids' => $office['allowed_ssids'],
            'attendance_response_times' => $office['attendance_response_times'],
            'attendance_history_days' => $office['attendance_history_days'],
            'attendance_recap_months' => $office['attendance_recap_months'],
            'is_active' => (int) $office['is_active'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        );

        $this->db->insert('offices', $insertData);

        $this->session->set_flashdata('message', 'Office duplicated.');
        redirect('admin/offices');
    }

    public function activate($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $office = $this->Office_model->get_by_id($id);
        if (!$office) {
            show_404();
            return;
        }

        $this->Office_model->set_active($office['id']);
        $this->db->where('id', (int) $office['id']);
        $this->db->update('offices', array('updated_at' => date('Y-m-d H:i:s')));
        $this->session->set_flashdata('message', 'Office status updated.');
        redirect('admin/offices');
    }

    public function deactivate($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $office = $this->Office_model->get_by_id($id);
        if (!$office) {
            show_404();
            return;
        }

        $this->Office_model->set_inactive($office['id']);
        $this->db->where('id', (int) $office['id']);
        $this->db->update('offices', array('updated_at' => date('Y-m-d H:i:s')));
        $this->session->set_flashdata('message', 'Office status updated.');
        redirect('admin/offices');
    }

    private function store()
    {
        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean) = $this->validate_office($input);

        if (!empty($errors)) {
            $data['title'] = 'Create Office - ' . $this->template->title();
            $data['office'] = array_merge($this->empty_office(), $clean);
            $data['errors'] = $errors;
            $data['form_action'] = site_url('admin/offices');
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/offices/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $insertData = array(
            'name' => $clean['name'],
            'lat' => $clean['lat'],
            'lng' => $clean['lng'],
            'radius_m' => $clean['radius_m'],
            'min_accuracy_m' => $clean['min_accuracy_m'],
            'allowed_ip_cidrs' => $clean['allowed_ip_cidrs'] === '' ? NULL : $clean['allowed_ip_cidrs'],
            'allowed_bssids' => $clean['allowed_bssids'] === '' ? NULL : $clean['allowed_bssids'],
            'allowed_ssids' => $clean['allowed_ssids'] === '' ? NULL : $clean['allowed_ssids'],
            'attendance_response_times' => $clean['attendance_response_times'] === '' ? NULL : $clean['attendance_response_times'],
            'attendance_history_days' => $clean['attendance_history_days'],
            'attendance_recap_months' => $clean['attendance_recap_months'],
            'is_active' => $clean['is_active'] ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->db->insert('offices', $insertData);
        $officeId = $this->db->insert_id();

        $this->session->set_flashdata('message', 'Office created.');
        redirect('admin/offices');
    }

    private function validate_office($input)
    {
        $errors = array();
        $clean = array();

        $clean['name'] = trim((string) ($input['name'] ?? ''));
        if ($clean['name'] === '') {
            $errors['name'] = 'Name is required.';
        }

        $clean['lat'] = $input['lat'] ?? '';
        if (!is_numeric($clean['lat']) || $clean['lat'] < -90 || $clean['lat'] > 90) {
            $errors['lat'] = 'Latitude must be between -90 and 90.';
        } else {
            $clean['lat'] = (float) $clean['lat'];
        }

        $clean['lng'] = $input['lng'] ?? '';
        if (!is_numeric($clean['lng']) || $clean['lng'] < -180 || $clean['lng'] > 180) {
            $errors['lng'] = 'Longitude must be between -180 and 180.';
        } else {
            $clean['lng'] = (float) $clean['lng'];
        }

        $clean['radius_m'] = $input['radius_m'] ?? '';
        if (!is_numeric($clean['radius_m']) || (int) $clean['radius_m'] < 10 || (int) $clean['radius_m'] > 5000) {
            $errors['radius_m'] = 'Radius must be between 10 and 5000 meters.';
        } else {
            $clean['radius_m'] = (int) $clean['radius_m'];
        }

        $clean['min_accuracy_m'] = $input['min_accuracy_m'] ?? '';
        if (!is_numeric($clean['min_accuracy_m']) || (int) $clean['min_accuracy_m'] < 5 || (int) $clean['min_accuracy_m'] > 500) {
            $errors['min_accuracy_m'] = 'Minimum accuracy must be between 5 and 500 meters.';
        } else {
            $clean['min_accuracy_m'] = (int) $clean['min_accuracy_m'];
        }

        $allowedCidrsRaw = trim((string) ($input['allowed_ip_cidrs'] ?? ''));
        $clean['allowed_ip_cidrs'] = $allowedCidrsRaw;
        if ($allowedCidrsRaw !== '') {
            list($cidrErrors, $normalized) = $this->normalize_cidrs($allowedCidrsRaw);
            if (!empty($cidrErrors)) {
                $errors['allowed_ip_cidrs'] = implode(' ', $cidrErrors);
            } else {
                $clean['allowed_ip_cidrs'] = $normalized;
            }
        }

        $clean['allowed_bssids'] = trim((string) ($input['allowed_bssids'] ?? ''));
        $clean['allowed_ssids'] = trim((string) ($input['allowed_ssids'] ?? ''));

        $responseTimesRaw = trim((string) ($input['attendance_response_times'] ?? ''));
        $clean['attendance_response_times'] = $responseTimesRaw;
        if ($responseTimesRaw !== '') {
            list($timeErrors, $normalizedTimes) = $this->normalize_times($responseTimesRaw);
            if (!empty($timeErrors)) {
                $errors['attendance_response_times'] = implode(' ', $timeErrors);
            } else {
                $clean['attendance_response_times'] = $normalizedTimes;
            }
        }

        $historyDaysRaw = $input['attendance_history_days'] ?? '';
        if (!is_numeric($historyDaysRaw) || (int) $historyDaysRaw < 1 || (int) $historyDaysRaw > 365) {
            $errors['attendance_history_days'] = 'History days must be between 1 and 365.';
            $clean['attendance_history_days'] = $historyDaysRaw;
        } else {
            $clean['attendance_history_days'] = (int) $historyDaysRaw;
        }

        $recapMonthsRaw = $input['attendance_recap_months'] ?? '';
        if (!is_numeric($recapMonthsRaw) || (int) $recapMonthsRaw < 1 || (int) $recapMonthsRaw > 36) {
            $errors['attendance_recap_months'] = 'Recap months must be between 1 and 36.';
            $clean['attendance_recap_months'] = $recapMonthsRaw;
        } else {
            $clean['attendance_recap_months'] = (int) $recapMonthsRaw;
        }
        $clean['is_active'] = isset($input['is_active']) && $input['is_active'] === '1';

        return array($errors, $clean);
    }

    private function normalize_times($text)
    {
        $lines = preg_split('/\r\n|\r|\n|,/', $text);
        $normalized = array();
        $errors = array();

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (!preg_match('/^\d{2}:\d{2}$/', $line)) {
                $errors[] = "Invalid time format: {$line}. Use HH:MM.";
                continue;
            }
            $normalized[] = $line;
        }

        return array($errors, implode("\n", $normalized));
    }

    private function normalize_cidrs($text)
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $normalized = array();
        $errors = array();

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (strpos($line, '/') === FALSE) {
                $errors[] = 'Each CIDR must include a subnet mask (e.g. 203.0.113.0/24).';
                continue;
            }

            list($ip, $mask) = explode('/', $line, 2);
            $ip = trim($ip);
            $mask = trim($mask);

            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $errors[] = "Invalid IP address: {$line}.";
                continue;
            }

            if (!is_numeric($mask) || (int) $mask < 0 || (int) $mask > 32) {
                $errors[] = "Invalid CIDR mask: {$line}.";
                continue;
            }

            $normalized[] = $ip . '/' . (int) $mask;
        }

        return array($errors, implode("\n", $normalized));
    }

    private function empty_office()
    {
        return array(
            'id' => null,
            'name' => '',
            'lat' => '',
            'lng' => '',
            'radius_m' => 150,
            'min_accuracy_m' => 50,
            'allowed_ip_cidrs' => '',
            'allowed_bssids' => '',
            'allowed_ssids' => '',
            'attendance_response_times' => '',
            'attendance_history_days' => 30,
            'attendance_recap_months' => 6,
            'is_active' => 0,
        );
    }

}
