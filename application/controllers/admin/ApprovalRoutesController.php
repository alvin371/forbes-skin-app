<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class ApprovalRoutesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ApprovalRouteModel');
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

        $data['title'] = 'Approval Routes - ' . $this->template->title();
        $data['routes'] = $this->ApprovalRouteModel->get_all(TRUE);
        $data['users'] = $this->get_users();
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/approval_routes/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function create()
    {
        $data['title'] = 'Create Approval Route - ' . $this->template->title();
        $data['route'] = $this->empty_route();
        $data['errors'] = array();
        $data['users'] = $this->get_users();
        $data['form_action'] = site_url('admin/approval-routes');
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/approval_routes/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function edit($id)
    {
        $route = $this->ApprovalRouteModel->get_by_id($id);
        if (!$route) {
            show_404();
            return;
        }

        $data['title'] = 'Edit Approval Route - ' . $this->template->title();
        $data['route'] = $route;
        $data['errors'] = array();
        $data['users'] = $this->get_users();
        $data['form_action'] = site_url('admin/approval-routes/' . $route['id']);
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/approval_routes/form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function update($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $route = $this->ApprovalRouteModel->get_by_id($id);
        if (!$route) {
            show_404();
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean) = $this->validate_route($input, $route['id']);

        if (!empty($errors)) {
            $data['title'] = 'Edit Approval Route - ' . $this->template->title();
            $data['route'] = array_merge($route, $clean);
            $data['errors'] = $errors;
            $data['users'] = $this->get_users();
            $data['form_action'] = site_url('admin/approval-routes/' . $route['id']);
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/approval_routes/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $updateData = array(
            'user_id' => $clean['user_id'],
            'approver_id' => $clean['approver_id'],
            'is_active' => $clean['is_active'] ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->ApprovalRouteModel->update($route['id'], $updateData);
        $this->session->set_flashdata('message', 'Approval route updated.');
        redirect('admin/approval-routes');
    }

    public function delete($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $route = $this->ApprovalRouteModel->get_by_id($id);
        if (!$route) {
            show_404();
            return;
        }

        $this->ApprovalRouteModel->deactivate($route['id']);
        $this->session->set_flashdata('message', 'Approval route deactivated.');
        redirect('admin/approval-routes');
    }

    private function store()
    {
        $input = $this->input->post(NULL, TRUE);
        list($errors, $clean) = $this->validate_route($input);

        if (!empty($errors)) {
            $data['title'] = 'Create Approval Route - ' . $this->template->title();
            $data['route'] = array_merge($this->empty_route(), $clean);
            $data['errors'] = $errors;
            $data['users'] = $this->get_users();
            $data['form_action'] = site_url('admin/approval-routes');
            $data['csrf_name'] = $this->security->get_csrf_token_name();
            $data['csrf_hash'] = $this->security->get_csrf_hash();
            $data['content'] = $this->load->view('admin/approval_routes/form', $data, true);
            $this->load->view('TemplateDashboard', $data);
            return;
        }

        $insertData = array(
            'user_id' => $clean['user_id'],
            'approver_id' => $clean['approver_id'],
            'is_active' => $clean['is_active'] ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->ApprovalRouteModel->insert($insertData);
        $this->session->set_flashdata('message', 'Approval route created.');
        redirect('admin/approval-routes');
    }

    private function validate_route($input, $currentId = null)
    {
        $errors = array();
        $clean = array();

        $clean['user_id'] = (int) ($input['user_id'] ?? 0);
        if ($clean['user_id'] <= 0) {
            $errors['user_id'] = 'User ID is required.';
        }

        $clean['approver_id'] = (int) ($input['approver_id'] ?? 0);
        if ($clean['approver_id'] <= 0) {
            $errors['approver_id'] = 'Approver ID is required.';
        }

        if ($clean['user_id'] > 0 && $clean['approver_id'] > 0 && $clean['user_id'] === $clean['approver_id']) {
            $errors['approver_id'] = 'Approver cannot be the same as user.';
        }

        $clean['is_active'] = isset($input['is_active']) && $input['is_active'] === '1';

        if ($clean['user_id'] > 0 && $clean['is_active']) {
            $existing = $this->ApprovalRouteModel->get_active_by_user($clean['user_id']);
            if ($existing && (int) $existing['id'] !== (int) $currentId) {
                $errors['user_id'] = 'Active approver already configured for this user.';
            }
        }

        return array($errors, $clean);
    }

    private function get_users()
    {
        $tables = $this->db->query("SHOW TABLES LIKE 'user'")->result_array();
        if (empty($tables)) {
            return array();
        }

        $columns = $this->db->query("SHOW COLUMNS FROM `user`")->result_array();
        $columnNames = array();
        foreach ($columns as $column) {
            $columnNames[] = $column['Field'];
        }

        $nameColumn = null;
        if (in_array('full_name', $columnNames, true)) {
            $nameColumn = 'full_name';
        } elseif (in_array('name', $columnNames, true)) {
            $nameColumn = 'name';
        } elseif (in_array('username', $columnNames, true)) {
            $nameColumn = 'username';
        } elseif (in_array('email', $columnNames, true)) {
            $nameColumn = 'email';
        }

        $emailColumn = in_array('email', $columnNames, true) ? 'email' : null;

        $select = 'id';
        if ($nameColumn) {
            $select .= ', ' . $nameColumn . ' AS name';
        } else {
            $select .= ", '' AS name";
        }
        if ($emailColumn) {
            $select .= ', email';
        } else {
            $select .= ", '' AS email";
        }

        $this->db->select($select);
        $this->db->from('user');
        $this->db->order_by('id', 'ASC');
        return $this->db->get()->result_array();
    }

    private function empty_route()
    {
        return array(
            'id' => null,
            'user_id' => '',
            'approver_id' => '',
            'is_active' => 1,
        );
    }
}
