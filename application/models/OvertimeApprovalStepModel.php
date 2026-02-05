<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeApprovalStepModel
 *
 * Handles CRUD operations for overtime_approval_steps table.
 */
class OvertimeApprovalStepModel extends CI_Model
{
    protected $table = 'overtime_approval_steps';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get step by ID
     *
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row_array();
    }

    /**
     * Get steps by approval instance ID
     *
     * @param int $instanceId
     * @return array
     */
    public function get_by_instance($instanceId)
    {
        $this->db->where('approval_instance_id', (int) $instanceId);
        $this->db->order_by('step_no', 'ASC');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get steps by overtime request ID
     *
     * @param int $overtimeRequestId
     * @return array
     */
    public function get_by_overtime_request($overtimeRequestId)
    {
        $this->db->select('s.*, u.full_name as approver_name, u.email as approver_email,
            actual.full_name as actual_approver_name');
        $this->db->from($this->table . ' s');
        $this->db->join('user u', 'u.id = s.assigned_approver_id', 'left');
        $this->db->join('user actual', 'actual.id = s.actual_approver_id', 'left');
        $this->db->where('s.overtime_request_id', (int) $overtimeRequestId);
        $this->db->order_by('s.step_no', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Get pending steps for an approver
     *
     * @param int $approverId
     * @return array
     */
    public function get_pending_for_approver($approverId)
    {
        $query = $this->db->query("
            SELECT
                s.*,
                otr.request_no,
                otr.user_id as requester_id,
                otr.overtime_date,
                otr.start_time,
                otr.end_time,
                otr.duration_hours,
                otr.reason,
                ot.name as overtime_type_name,
                u.full_name as requester_name,
                ai.total_steps,
                ai.current_step
            FROM {$this->table} s
            INNER JOIN overtime_approval_instances ai ON s.approval_instance_id = ai.id
            INNER JOIN overtime_requests otr ON s.overtime_request_id = otr.id
            INNER JOIN overtime_types ot ON otr.overtime_type_id = ot.id
            INNER JOIN user u ON otr.user_id = u.id
            WHERE s.action = 'PENDING'
              AND ai.status = 'IN_PROGRESS'
              AND s.step_no = ai.current_step
              AND s.assigned_approver_id = ?
            ORDER BY otr.created_at ASC
        ", array($approverId));

        return $query->result_array();
    }

    /**
     * Get approval history for an approver
     *
     * @param int $approverId
     * @param int $limit
     * @return array
     */
    public function get_history_for_approver($approverId, $limit = 50)
    {
        $query = $this->db->query("
            SELECT
                s.*,
                otr.request_no,
                otr.user_id as requester_id,
                otr.overtime_date,
                otr.start_time,
                otr.end_time,
                otr.duration_hours,
                ot.name as overtime_type_name,
                u.full_name as requester_name
            FROM {$this->table} s
            INNER JOIN overtime_requests otr ON s.overtime_request_id = otr.id
            INNER JOIN overtime_types ot ON otr.overtime_type_id = ot.id
            INNER JOIN user u ON otr.user_id = u.id
            WHERE s.actual_approver_id = ?
              AND s.action IN ('APPROVED', 'REJECTED')
            ORDER BY s.action_at DESC
            LIMIT ?
        ", array($approverId, $limit));

        return $query->result_array();
    }

    /**
     * Get count of pending steps for an approver
     *
     * @param int $approverId
     * @return int
     */
    public function count_pending_for_approver($approverId)
    {
        $query = $this->db->query("
            SELECT COUNT(DISTINCT s.id) as count
            FROM {$this->table} s
            INNER JOIN overtime_approval_instances ai ON s.approval_instance_id = ai.id
            WHERE s.action = 'PENDING'
              AND ai.status = 'IN_PROGRESS'
              AND s.step_no = ai.current_step
              AND s.assigned_approver_id = ?
        ", array($approverId));

        $result = $query->row_array();
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Insert a new approval step
     *
     * @param array $data
     * @return int|false
     */
    public function insert($data)
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        if ($this->db->insert($this->table, $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * Update an approval step
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, array('id' => (int) $id));
    }

    /**
     * Delete steps by approval instance ID
     *
     * @param int $instanceId
     * @return bool
     */
    public function delete_by_instance($instanceId)
    {
        return $this->db->delete($this->table, array('approval_instance_id' => (int) $instanceId));
    }

    /**
     * Delete steps by overtime request ID
     *
     * @param int $overtimeRequestId
     * @return bool
     */
    public function delete_by_overtime_request($overtimeRequestId)
    {
        return $this->db->delete($this->table, array('overtime_request_id' => (int) $overtimeRequestId));
    }
}
