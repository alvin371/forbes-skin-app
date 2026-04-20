<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LeaveQuotaModel extends CI_Model
{
    private $hasDefaultQuotaDaysColumn = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function leave_types_has_default_quota_days()
    {
        if ($this->hasDefaultQuotaDaysColumn === null) {
            $this->hasDefaultQuotaDaysColumn = $this->db->field_exists('default_quota_days', 'leave_types');
        }

        return $this->hasDefaultQuotaDaysColumn;
    }

    public function get_by_user($userId)
    {
        $this->db->select('lq.*, lt.name as leave_type_name, lt.code as leave_type_code');
        $this->db->from('leave_quotas lq');
        $this->db->join('leave_types lt', 'lt.id = lq.leave_type_id', 'left');
        $this->db->where('lq.user_id', (int) $userId);
        $this->db->where('lt.code !=', 'SPECIAL');
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
        $this->db->where('lt.code !=', 'SPECIAL');
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

    public function apply_defaults_for_user($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return 0;
        }

        // Older databases may not have leave_types.default_quota_days yet.
        // In that case, default quota seeding is skipped instead of failing user creation.
        if (!$this->leave_types_has_default_quota_days()) {
            log_message('debug', 'Skipping default leave quota seeding because leave_types.default_quota_days does not exist.');
            return 0;
        }

        $existing = $this->get_by_user($userId);
        $existingTypeIds = array();
        foreach ($existing as $quota) {
            $existingTypeIds[(int) $quota['leave_type_id']] = true;
        }

        $this->db->select('id, default_quota_days');
        $this->db->from('leave_types');
        $this->db->where('is_active', 1);
        $this->db->where('default_quota_days IS NOT NULL', null, false);
        $leaveTypes = $this->db->get()->result_array();

        $count = 0;
        foreach ($leaveTypes as $leaveType) {
            $leaveTypeId = (int) $leaveType['id'];
            if (isset($existingTypeIds[$leaveTypeId])) {
                continue;
            }

            $days = (int) $leaveType['default_quota_days'];
            if ($days < 0) {
                continue;
            }

            $data = array(
                'user_id' => $userId,
                'leave_type_id' => $leaveTypeId,
                'total_days' => $days,
                'remaining_days' => $days,
                'updated_at' => date('Y-m-d H:i:s'),
            );
            $this->insert($data);
            $count++;
        }

        return $count;
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
