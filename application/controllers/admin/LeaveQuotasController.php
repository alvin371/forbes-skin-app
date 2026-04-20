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
        $this->load->library('LeaveQuotaService');
        $this->load->database();
        $this->load->library('template');
        $this->set_method_permissions([
            'manage' => 'edit',
            'bulk_set' => 'edit',
            'bulk_update' => 'edit',
            'set_all' => 'edit',
            'copy_from' => 'edit',
        ]);
    }

    public function index()
    {
        $data['title'] = 'Leave Quotas - ' . $this->template->title();
        $data['quotas'] = $this->LeaveQuotaModel->get_all_with_details();
        $activeLeaveTypes = $this->LeaveTypeModel->get_active();
        list($data['leave_types'], $data['unlimited_leave_types']) = $this->separate_leave_types($activeLeaveTypes);
        $data['total_leave_type_count'] = $this->LeaveTypeModel->count_all(TRUE);
        $data['active_leave_type_count'] = count($activeLeaveTypes);
        $data['inactive_leave_type_count'] = max(0, $data['total_leave_type_count'] - $data['active_leave_type_count']);
        $data['quota_managed_leave_type_count'] = count($data['leave_types']);
        $data['unlimited_leave_type_count'] = count($data['unlimited_leave_types']);

        $this->db->select('u.id, u.full_name, u.email');
        $this->db->from('user u');
        $this->db->where('u.status', 'Aktif');
        $this->db->order_by('u.full_name', 'ASC');
        $data['users'] = $this->db->get()->result_array();

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
        $activeLeaveTypes = $this->LeaveTypeModel->get_active();
        list($data['leave_types'], $data['unlimited_leave_types']) = $this->separate_leave_types($activeLeaveTypes);
        $data['total_leave_type_count'] = $this->LeaveTypeModel->count_all(TRUE);
        $data['active_leave_type_count'] = count($activeLeaveTypes);
        $data['inactive_leave_type_count'] = max(0, $data['total_leave_type_count'] - $data['active_leave_type_count']);
        $data['quota_managed_leave_type_count'] = count($data['leave_types']);
        $data['unlimited_leave_type_count'] = count($data['unlimited_leave_types']);
        $data['quotas'] = $this->LeaveQuotaModel->get_by_user($userId);
        $this->db->select('u.id, u.full_name, u.email');
        $this->db->from('user u');
        $this->db->where('u.status', 'Aktif');
        $this->db->order_by('u.full_name', 'ASC');
        $data['users'] = $this->db->get()->result_array();

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
        $activeLeaveTypes = $this->LeaveTypeModel->get_active();
        list($data['leave_types'], $data['unlimited_leave_types']) = $this->separate_leave_types($activeLeaveTypes);

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
            if ($this->is_unlimited_leave_type($leaveTypeId)) {
                continue;
            }

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

        if ($this->is_unlimited_leave_type($leaveTypeId)) {
            $this->session->set_flashdata('error', 'Special Leaves is unlimited and does not use quotas.');
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

    public function bulk_update()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        $updates = $input['quota_updates'] ?? array();
        $onlyUserId = isset($input['only_user_id']) ? (int) $input['only_user_id'] : 0;
        $onlyLeaveTypeId = isset($input['only_leave_type_id']) ? (int) $input['only_leave_type_id'] : 0;

        if (empty($updates)) {
            $this->session->set_flashdata('error', 'No quota updates provided.');
            redirect('admin/leave-quotas');
            return;
        }

        $this->db->trans_start();
        $count = 0;
        $skipped = 0;

        foreach ($updates as $userId => $types) {
            foreach ((array) $types as $leaveTypeId => $totalDays) {
                if ($onlyUserId && (int) $userId !== $onlyUserId) {
                    continue;
                }
                if ($onlyLeaveTypeId && (int) $leaveTypeId !== $onlyLeaveTypeId) {
                    continue;
                }

                if ($this->is_unlimited_leave_type($leaveTypeId)) {
                    $skipped++;
                    continue;
                }

                if ($totalDays === '' || $totalDays === null) {
                    $skipped++;
                    continue;
                }

                if (!is_numeric($totalDays) || (int) $totalDays < 0) {
                    $this->session->set_flashdata('error', 'Total days must be a non-negative number.');
                    redirect('admin/leave-quotas');
                    return;
                }

                $this->LeaveQuotaModel->upsert((int) $userId, (int) $leaveTypeId, (int) $totalDays);
                $count++;
            }
        }

        $this->db->trans_complete();

        if ($count > 0) {
            $this->session->set_flashdata('message', "Leave quotas updated for {$count} item(s)." . ($skipped ? " {$skipped} skipped." : ''));
        } else {
            $this->session->set_flashdata('error', 'No valid quota updates to apply.');
        }
        redirect('admin/leave-quotas');
    }

    public function set_all()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        $leaveTypeId = (int) ($input['leave_type_id'] ?? 0);
        $totalDays = $input['total_days'] ?? null;

        if ($leaveTypeId <= 0) {
            $this->session->set_flashdata('error', 'Please select a leave type.');
            redirect('admin/leave-quotas');
            return;
        }

        if ($this->is_unlimited_leave_type($leaveTypeId)) {
            $this->session->set_flashdata('error', 'Special Leaves is unlimited and does not use quotas.');
            redirect('admin/leave-quotas');
            return;
        }

        if ($totalDays === '' || $totalDays === null || !is_numeric($totalDays) || (int) $totalDays < 0) {
            $this->session->set_flashdata('error', 'Total days must be a non-negative number.');
            redirect('admin/leave-quotas');
            return;
        }

        $this->db->select('id');
        $this->db->from('user');
        $this->db->where('status', 'Aktif');
        $users = $this->db->get()->result_array();

        if (empty($users)) {
            $this->session->set_flashdata('error', 'No active users found.');
            redirect('admin/leave-quotas');
            return;
        }

        $this->db->trans_start();
        $count = 0;
        foreach ($users as $user) {
            $this->LeaveQuotaModel->upsert((int) $user['id'], $leaveTypeId, (int) $totalDays);
            $count++;
        }
        $this->db->trans_complete();

        $this->session->set_flashdata('message', "Leave quotas set for {$count} user(s).");
        redirect('admin/leave-quotas');
    }

    public function copy_from($userId = null)
    {
        if (!$userId) {
            show_404();
            return;
        }

        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }

        $input = $this->input->post(NULL, TRUE);
        $sourceUserId = (int) ($input['source_user_id'] ?? 0);

        if ($sourceUserId <= 0) {
            $this->session->set_flashdata('error', 'Please select a template user.');
            redirect('admin/leave-quotas/manage/' . $userId);
            return;
        }

        if ($sourceUserId === (int) $userId) {
            $this->session->set_flashdata('error', 'Template user must be different.');
            redirect('admin/leave-quotas/manage/' . $userId);
            return;
        }

        $sourceQuotas = $this->LeaveQuotaModel->get_by_user($sourceUserId);
        if (empty($sourceQuotas)) {
            $this->session->set_flashdata('error', 'Template user has no quotas to copy.');
            redirect('admin/leave-quotas/manage/' . $userId);
            return;
        }

        $this->db->trans_start();
        $count = 0;
        foreach ($sourceQuotas as $quota) {
            if ($this->is_unlimited_leave_type($quota['leave_type_id'])) {
                continue;
            }
            $this->LeaveQuotaModel->upsert((int) $userId, (int) $quota['leave_type_id'], (int) $quota['total_days']);
            $count++;
        }
        $this->db->trans_complete();

        $this->session->set_flashdata('message', "Copied {$count} quota(s) from template user.");
        redirect('admin/leave-quotas/manage/' . $userId);
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

    private function separate_leave_types($leaveTypes)
    {
        $quotaManaged = array();
        $unlimited = array();

        foreach ((array) $leaveTypes as $leaveType) {
            if ($this->leavequotaservice->isUnlimitedLeaveTypeRecord($leaveType)) {
                $unlimited[] = $leaveType;
            } else {
                $quotaManaged[] = $leaveType;
            }
        }

        return array($quotaManaged, $unlimited);
    }

    private function is_unlimited_leave_type($leaveTypeId)
    {
        return $this->leavequotaservice->isUnlimitedLeaveType((int) $leaveTypeId);
    }
}
