<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class LeaveQuotasController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('LeaveQuotaModel');
        $this->load->model('LeaveTypeModel');
        $this->load->database();
        $this->load->library('template');
        $this->load->library('AdminAuthFilter');
        $this->adminauthfilter->enforce();
    }

    public function index()
    {
        $data['title'] = 'Leave Quotas - ' . $this->template->title();
        $data['quotas'] = $this->LeaveQuotaModel->get_all_with_details();
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/leave_quotas/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function manage($userId = null)
    {
        if (!$userId) {
            show_404();
            return;
        }

        $user = $this->db->get_where('user', array('id' => (int) $userId))->row_array();
        if (!$user) {
            show_404();
            return;
        }

        if ($this->input->method(TRUE) === 'POST') {
            return $this->save_quotas($userId);
        }

        $data['title'] = 'Manage Leave Quotas - ' . $this->template->title();
        $data['user'] = $user;
        $data['leave_types'] = $this->LeaveTypeModel->get_active();
        $data['quotas'] = $this->LeaveQuotaModel->get_by_user($userId);

        $quotasByType = array();
        foreach ($data['quotas'] as $quota) {
            $quotasByType[$quota['leave_type_id']] = $quota;
        }
        $data['quotas_by_type'] = $quotasByType;

        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/leave_quotas/manage', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function users()
    {
        $data['title'] = 'Select User for Leave Quota - ' . $this->template->title();

        $this->db->select('u.id, u.full_name, u.email');
        $this->db->from('user u');
        $this->db->where('u.status', 'Aktif');
        $this->db->order_by('u.full_name', 'ASC');
        $data['users'] = $this->db->get()->result_array();

        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/leave_quotas/users', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function bulk_set()
    {
        if ($this->input->method(TRUE) === 'POST') {
            return $this->process_bulk_set();
        }

        $data['title'] = 'Bulk Set Leave Quotas - ' . $this->template->title();
        $data['leave_types'] = $this->LeaveTypeModel->get_active();

        $this->db->select('id, full_name, email');
        $this->db->from('user');
        $this->db->where('status', 'Aktif');
        $this->db->order_by('full_name', 'ASC');
        $data['users'] = $this->db->get()->result_array();

        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/leave_quotas/bulk_set', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    private function save_quotas($userId)
    {
        $input = $this->input->post(NULL, TRUE);
        $quotas = $input['quotas'] ?? array();

        if (empty($quotas)) {
            $this->session->set_flashdata('error', 'No quotas provided.');
            redirect('admin/leave-quotas/manage/' . $userId);
            return;
        }

        $this->db->trans_start();

        foreach ($quotas as $leaveTypeId => $totalDays) {
            $totalDays = (int) $totalDays;
            if ($totalDays >= 0) {
                $this->LeaveQuotaModel->upsert($userId, $leaveTypeId, $totalDays);
            }
        }

        $this->db->trans_complete();

        $this->session->set_flashdata('message', 'Leave quotas updated successfully.');
        redirect('admin/leave-quotas/manage/' . $userId);
    }

    private function process_bulk_set()
    {
        $input = $this->input->post(NULL, TRUE);
        $leaveTypeId = (int) ($input['leave_type_id'] ?? 0);
        $totalDays = (int) ($input['total_days'] ?? 0);
        $userIds = $input['user_ids'] ?? array();

        if ($leaveTypeId <= 0) {
            $this->session->set_flashdata('error', 'Please select a leave type.');
            redirect('admin/leave-quotas/bulk-set');
            return;
        }

        if ($totalDays < 0) {
            $this->session->set_flashdata('error', 'Total days must be non-negative.');
            redirect('admin/leave-quotas/bulk-set');
            return;
        }

        if (empty($userIds)) {
            $this->session->set_flashdata('error', 'Please select at least one user.');
            redirect('admin/leave-quotas/bulk-set');
            return;
        }

        $this->db->trans_start();

        $count = 0;
        foreach ($userIds as $userId) {
            $this->LeaveQuotaModel->upsert((int) $userId, $leaveTypeId, $totalDays);
            $count++;
        }

        $this->db->trans_complete();

        $this->session->set_flashdata('message', "Leave quotas set for {$count} user(s).");
        redirect('admin/leave-quotas');
    }

    public function delete($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $quota = $this->db->get_where('leave_quotas', array('id' => (int) $id))->row_array();
        if (!$quota) {
            show_404();
            return;
        }

        $this->LeaveQuotaModel->delete($id);
        $this->session->set_flashdata('message', 'Leave quota deleted.');
        redirect('admin/leave-quotas');
    }
}
