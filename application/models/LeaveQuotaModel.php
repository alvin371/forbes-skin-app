<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LeaveQuotaModel extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_by_user($userId)
    {
        $this->db->select('lq.*, lt.name as leave_type_name, lt.code as leave_type_code');
        $this->db->from('leave_quotas lq');
        $this->db->join('leave_types lt', 'lt.id = lq.leave_type_id', 'left');
        $this->db->where('lq.user_id', (int) $userId);
        $this->db->order_by('lt.name', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_by_user_and_type($userId, $leaveTypeId)
    {
        $this->db->where('user_id', (int) $userId);
        $this->db->where('leave_type_id', (int) $leaveTypeId);
        return $this->db->get('leave_quotas')->row_array();
    }

    public function get_all_with_details()
    {
        $this->db->select('lq.*, u.full_name as user_name, u.email as user_email, lt.name as leave_type_name, lt.code as leave_type_code');
        $this->db->from('leave_quotas lq');
        $this->db->join('user u', 'u.id = lq.user_id', 'left');
        $this->db->join('leave_types lt', 'lt.id = lq.leave_type_id', 'left');
        $this->db->order_by('u.full_name', 'ASC');
        $this->db->order_by('lt.name', 'ASC');
        return $this->db->get()->result_array();
    }

    public function insert($data)
    {
        $this->db->insert('leave_quotas', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', (int) $id);
        return $this->db->update('leave_quotas', $data);
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->delete('leave_quotas');
    }

    public function upsert($userId, $leaveTypeId, $totalDays)
    {
        $existing = $this->get_by_user_and_type($userId, $leaveTypeId);

        $data = array(
            'total_days' => (int) $totalDays,
            'remaining_days' => (int) $totalDays,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if ($existing) {
            $this->update($existing['id'], $data);
            return $existing['id'];
        } else {
            $data['user_id'] = (int) $userId;
            $data['leave_type_id'] = (int) $leaveTypeId;
            return $this->insert($data);
        }
    }

    public function deduct_quota($userId, $leaveTypeId, $days)
    {
        $quota = $this->get_by_user_and_type($userId, $leaveTypeId);
        if (!$quota) {
            return false;
        }

        $newRemaining = max(0, (int) $quota['remaining_days'] - (int) $days);

        return $this->update($quota['id'], array(
            'remaining_days' => $newRemaining,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function restore_quota($userId, $leaveTypeId, $days)
    {
        $quota = $this->get_by_user_and_type($userId, $leaveTypeId);
        if (!$quota) {
            return false;
        }

        $newRemaining = min((int) $quota['total_days'], (int) $quota['remaining_days'] + (int) $days);

        return $this->update($quota['id'], array(
            'remaining_days' => $newRemaining,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }
}
