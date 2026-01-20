<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class PerformanceAppraisal extends BaseController
{
    protected $public_methods = [
        'index',
        'create',
        'edit',
        'update',
        'delete',
        'item_store',
        'item_update',
        'item_delete',
        'items_reorder',
        'submissions',
        'submission_detail'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('performance_model');
        $this->load->database();
        $this->load->library('template');
        $this->load->library('AdminAuthFilter');
        $this->adminauthfilter->enforce();
    }

    /**
     * Get all active roles for dropdown
     */
    private function get_roles()
    {
        return $this->performance_model->get_all_roles();
    }

    public function index()
    {
        if ($this->input->method(TRUE) === 'POST') {
            return $this->store();
        }

        $data['title'] = 'Performance Appraisal Templates - ' . $this->template->title();
        $data['templates'] = $this->performance_model->get_templates();
        $data['roles'] = $this->get_roles();
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/performance_appraisal/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function create()
    {
        $data['title'] = 'Create Performance Template - ' . $this->template->title();
        $data['template'] = $this->empty_template();
        $data['roles'] = $this->get_roles();
        $data['errors'] = [];
        $data['item_errors'] = [];
        $data['item_rows'] = [$this->empty_item_row(1)];
        $data['form_action'] = site_url('admin/performance-appraisal');
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/performance_appraisal/create', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function edit($id)
    {
        $template = $this->performance_model->get_template_by_id($id, true);
        if (!$template) {
            show_404();
            return;
        }

        $data['title'] = 'Edit Performance Template - ' . $this->template->title();
        $data['template'] = $template;
        $data['roles'] = $this->get_roles();
        $data['errors'] = [];
        $data['item_errors'] = [];
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/performance_appraisal/detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function update($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $template = $this->performance_model->get_template_by_id($id, true);
        if (!$template) {
            show_404();
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        list($errors, $payload) = $this->validate_template($input);

        if (!empty($errors)) {
            $data['title'] = 'Edit Performance Template - ' . $this->template->title();
            $data['template'] = array_merge($template, $payload);
            $data['roles'] = $this->get_roles();
            $data['errors'] = $errors;
            $data['item_errors'] = [];
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/performance_appraisal/detail', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $result = $this->performance_model->update_template($id, $payload);

        if (!$result['success']) {
            $data['title'] = 'Edit Performance Template - ' . $this->template->title();
            $data['template'] = array_merge($template, $payload);
            $data['roles'] = $this->get_roles();
            $data['errors'] = ['is_active' => $result['error']];
            $data['item_errors'] = [];
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/performance_appraisal/detail', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $this->session->set_flashdata('message', 'Template updated.');
        redirect('admin/performance-appraisal/' . $id . '/edit');
    }

    public function delete($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $result = $this->performance_model->delete_template($id);

        if (!$result['success']) {
            $this->session->set_flashdata('error', $result['error']);
        } else {
            $this->session->set_flashdata('message', 'Template deleted.');
        }

        redirect('admin/performance-appraisal');
    }

    public function item_store($template_id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $template = $this->performance_model->get_template_by_id($template_id, true);
        if (!$template) {
            show_404();
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        list($errors, $payload) = $this->validate_item($input);
        $payload['template_id'] = $template_id;

        if (!empty($errors)) {
            $data['title'] = 'Edit Performance Template - ' . $this->template->title();
            $data['template'] = $template;
            $data['roles'] = $this->get_roles();
            $data['errors'] = [];
            $data['item_errors'] = $errors;
            $data['item_form'] = $payload;
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/performance_appraisal/detail', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $result = $this->performance_model->create_template_item($payload);

        if (!$result['success']) {
            $this->session->set_flashdata('error', $result['error']);
        } else {
            $this->session->set_flashdata('message', 'Item added.');
        }

        redirect('admin/performance-appraisal/' . $template_id . '/edit');
    }

    public function item_update($item_id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $item = $this->performance_model->get_template_item($item_id);
        if (!$item) {
            show_404();
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        list($errors, $payload) = $this->validate_item($input, true);

        if (!empty($errors)) {
            $template = $this->performance_model->get_template_by_id($item['template_id'], true);
            $data['title'] = 'Edit Performance Template - ' . $this->template->title();
            $data['template'] = $template;
            $data['roles'] = $this->get_roles();
            $data['errors'] = [];
            $data['item_errors'] = $errors;
            $data['item_form'] = array_merge($item, $payload, ['id' => $item_id]);
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/performance_appraisal/detail', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $result = $this->performance_model->update_template_item($item_id, $payload);

        if (!$result['success']) {
            $this->session->set_flashdata('error', $result['error']);
        } else {
            $this->session->set_flashdata('message', 'Item updated.');
        }

        redirect('admin/performance-appraisal/' . $item['template_id'] . '/edit');
    }

    public function item_delete($item_id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $item = $this->performance_model->get_template_item($item_id);
        if (!$item) {
            show_404();
            return;
        }

        $this->performance_model->delete_template_item($item_id);
        $this->session->set_flashdata('message', 'Item deleted.');
        redirect('admin/performance-appraisal/' . $item['template_id'] . '/edit');
    }

    public function items_reorder($template_id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        $orders = $input['order_no'] ?? [];
        $items = [];

        foreach ($orders as $item_id => $order_no) {
            if (!is_numeric($order_no)) {
                continue;
            }
            $items[] = [
                'id' => $item_id,
                'order_no' => (int) $order_no
            ];
        }

        if (!empty($items)) {
            $this->performance_model->reorder_template_items($items);
            $this->session->set_flashdata('message', 'Item order updated.');
        }

        redirect('admin/performance-appraisal/' . $template_id . '/edit');
    }

    public function submissions()
    {
        $filters = [];

        if ($this->input->get('template_id')) {
            $filters['template_id'] = $this->input->get('template_id');
        }
        if ($this->input->get('period_year')) {
            $filters['period_year'] = $this->input->get('period_year');
        }
        // Use role_id filter (new) or department (legacy)
        if ($this->input->get('role_id') !== null && $this->input->get('role_id') !== '') {
            $filters['role_id'] = $this->input->get('role_id');
        } elseif ($this->input->get('department') !== null && $this->input->get('department') !== '') {
            $filters['department'] = $this->input->get('department');
        }

        $data['title'] = 'Performance Submissions - ' . $this->template->title();
        $data['templates'] = $this->performance_model->get_templates();
        $data['roles'] = $this->get_roles();
        $data['submissions'] = $this->performance_model->get_submissions($filters);
        $data['filters'] = $filters;
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/performance_appraisal/submissions', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function submission_detail($id)
    {
        $submission = $this->performance_model->get_submission_by_id($id, true);
        if (!$submission) {
            show_404();
            return;
        }

        $submission['total_score'] = round($submission['total_score'], 2);
        foreach ($submission['items'] as &$item) {
            $item['score_ratio'] = round($item['score_ratio'], 4);
            $item['final_score'] = round($item['final_score'], 2);
        }
        unset($item);

        $data['title'] = 'Submission Detail - ' . $this->template->title();
        $data['submission'] = $submission;
        $data['content'] = $this->load->view('admin/performance_appraisal/submission_detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    private function store()
    {
        $input = $this->input->post(NULL, TRUE);
        list($errors, $payload) = $this->validate_template($input, true);
        list($item_errors, $item_rows, $clean_items) = $this->validate_item_rows($input['items'] ?? []);

        if (!empty($errors) || !empty($item_errors)) {
            $data['title'] = 'Create Performance Template - ' . $this->template->title();
            $data['template'] = array_merge($this->empty_template(), $payload);
            $data['roles'] = $this->get_roles();
            $data['errors'] = $errors;
            $data['item_errors'] = $item_errors;
            $data['item_rows'] = !empty($item_rows) ? $item_rows : [$this->empty_item_row(1)];
            $data['form_action'] = site_url('admin/performance-appraisal');
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/performance_appraisal/create', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $this->db->trans_begin();

        $template_id = $this->performance_model->create_template($payload);
        $item_error_message = null;

        foreach ($clean_items as $item) {
            $item['template_id'] = $template_id;
            $result = $this->performance_model->create_template_item($item);
            if (!$result['success']) {
                $item_error_message = $result['error'] ?? 'Failed to create template items.';
                break;
            }
        }

        if ($item_error_message || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $data['title'] = 'Create Performance Template - ' . $this->template->title();
            $data['template'] = array_merge($this->empty_template(), $payload);
            $data['roles'] = $this->get_roles();
            $data['errors'] = ['items' => $item_error_message ?? 'Failed to create template.'];
            $data['item_errors'] = [];
            $data['item_rows'] = !empty($item_rows) ? $item_rows : [$this->empty_item_row(1)];
            $data['form_action'] = site_url('admin/performance-appraisal');
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/performance_appraisal/create', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $this->db->trans_commit();
        $this->session->set_flashdata('message', 'Template created.');
        redirect('admin/performance-appraisal/' . $template_id . '/edit');
    }

    private function validate_template($input, $is_create = false)
    {
        $errors = [];
        $clean = [];

        $clean['name'] = trim((string) ($input['name'] ?? ''));
        if ($clean['name'] === '') {
            $errors['name'] = 'Name is required.';
        }

        $clean['period_year'] = isset($input['period_year']) ? (int) $input['period_year'] : 0;
        if ($clean['period_year'] < 2000) {
            $errors['period_year'] = 'Period year is required.';
        }

        // Use role_id instead of department
        $role_id = $input['role_id'] ?? '';
        $clean['role_id'] = ($role_id === '' || $role_id === 'null' || $role_id === null) ? null : (int) $role_id;

        $clean['is_active'] = isset($input['is_active']) && $input['is_active'] === '1' ? 1 : 0;

        if ($is_create && $clean['is_active'] === 1) {
            $errors['is_active'] = 'Template must have at least 1 item before activation.';
        }

        return [$errors, $clean];
    }

    private function validate_item($input, $is_update = false)
    {
        $errors = [];
        $clean = [];

        $clean['order_no'] = isset($input['order_no']) ? (int) $input['order_no'] : 0;

        $clean['objective'] = trim((string) ($input['objective'] ?? ''));
        if ($clean['objective'] === '') {
            $errors['objective'] = 'Objective is required.';
        }

        $clean['kpi'] = trim((string) ($input['kpi'] ?? ''));
        if ($clean['kpi'] === '') {
            $errors['kpi'] = 'KPI is required.';
        }

        $clean['target_value'] = $input['target_value'] ?? '';
        if ($clean['target_value'] === '' || !is_numeric($clean['target_value'])) {
            $errors['target_value'] = 'Target value is required.';
        } else {
            $clean['target_value'] = (float) $clean['target_value'];
        }

        $clean['unit'] = trim((string) ($input['unit'] ?? ''));
        if ($clean['unit'] === '') {
            $errors['unit'] = 'Unit is required.';
        }

        $clean['weight'] = $input['weight'] ?? '';
        if ($clean['weight'] === '' || !is_numeric($clean['weight'])) {
            $errors['weight'] = 'Weight is required.';
        } else {
            $clean['weight'] = (float) $clean['weight'];
        }

        return [$errors, $clean];
    }

    private function validate_item_rows($rows)
    {
        $errors = [];
        $clean_rows = [];
        $display_rows = [];

        if (!is_array($rows)) {
            return [$errors, $display_rows, $clean_rows];
        }

        $index = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                $index++;
                continue;
            }

            $row_key = count($display_rows);
            $normalized = [
                'order_no' => isset($row['order_no']) && is_numeric($row['order_no']) ? (int) $row['order_no'] : ($index + 1),
                'objective' => trim((string) ($row['objective'] ?? '')),
                'kpi' => trim((string) ($row['kpi'] ?? '')),
                'target_value' => $row['target_value'] ?? '',
                'unit' => trim((string) ($row['unit'] ?? '')),
                'weight' => $row['weight'] ?? '',
            ];

            $is_empty = $normalized['objective'] === '' &&
                $normalized['kpi'] === '' &&
                $normalized['target_value'] === '' &&
                $normalized['unit'] === '' &&
                $normalized['weight'] === '';

            if ($is_empty) {
                $index++;
                continue;
            }

            $row_errors = [];
            if ($normalized['objective'] === '') {
                $row_errors['objective'] = 'Objective is required.';
            }
            if ($normalized['kpi'] === '') {
                $row_errors['kpi'] = 'KPI is required.';
            }
            if ($normalized['target_value'] === '' || !is_numeric($normalized['target_value'])) {
                $row_errors['target_value'] = 'Target value is required.';
            } else {
                $normalized['target_value'] = (float) $normalized['target_value'];
            }
            if ($normalized['unit'] === '') {
                $row_errors['unit'] = 'Unit is required.';
            }
            if ($normalized['weight'] === '' || !is_numeric($normalized['weight'])) {
                $row_errors['weight'] = 'Weight is required.';
            } else {
                $normalized['weight'] = (float) $normalized['weight'];
            }

            if (!empty($row_errors)) {
                $errors[$row_key] = $row_errors;
            } else {
                $clean_rows[] = $normalized;
            }

            $display_rows[$row_key] = $normalized;
            $index++;
        }

        return [$errors, $display_rows, $clean_rows];
    }

    private function empty_template()
    {
        return [
            'id' => null,
            'name' => '',
            'period_year' => date('Y'),
            'role_id' => null,
            'is_active' => 0,
        ];
    }

    private function empty_item_row($order_no = 1)
    {
        return [
            'order_no' => $order_no,
            'objective' => '',
            'kpi' => '',
            'target_value' => '',
            'unit' => '%',
            'weight' => ''
        ];
    }
}
