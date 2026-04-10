<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ApprovalStepModel
 *
 * Manages individual approval step records with optimistic locking for concurrency.
 */
class ApprovalStepModel extends CI_Model
{
    protected $table = 'approval_steps';

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
        $query = $this->db->query("
            SELECT s.*,
                   assigned.full_name as assigned_approver_name,
                   assigned.role_text as assigned_approver_role,
                   actual.full_name as actual_approver_name
            FROM {$this->table} s
            LEFT JOIN user assigned ON s.assigned_approver_id = assigned.id
            LEFT JOIN user actual ON s.actual_approver_id = actual.id
            WHERE s.id = ?
        ", array($id));

        return $query->row_array();
    }

    /**
     * Get steps by approval instance ID
     *
     * @param int $instanceId
     * @return array
     */
    public function get_by_instance($instanceId)
    {
        $sql = "
            SELECT s.*,
                   assigned.full_name as assigned_approver_name,
                   assigned.role_text as assigned_approver_role,
                   actual.full_name as actual_approver_name
            FROM {$this->table} s
            LEFT JOIN user assigned ON s.assigned_approver_id = assigned.id
            LEFT JOIN user actual ON s.actual_approver_id = actual.id
            WHERE s.approval_instance_id = ?
            ORDER BY s.step_no ASC
        ";

        return $this->db->query($sql, array($instanceId))->result_array();
    }

    /**
     * Get steps by leave request ID
     *
     * @param int $leaveRequestId
     * @return array
     */
    public function get_by_leave_request($leaveRequestId)
    {
        $sql = "
            SELECT s.*,
                   assigned.full_name as assigned_approver_name,
                   assigned.role_text as assigned_approver_role,
                   actual.full_name as actual_approver_name
            FROM {$this->table} s
            LEFT JOIN user assigned ON s.assigned_approver_id = assigned.id
            LEFT JOIN user actual ON s.actual_approver_id = actual.id
            WHERE s.leave_request_id = ?
            ORDER BY s.step_no ASC
        ";

        return $this->db->query($sql, array($leaveRequestId))->result_array();
    }

    /**
     * Get pending steps for an approver
     *
     * @param int $approverId
     * @return array
     */
    public function get_pending_for_approver($approverId)
    {
        $sql = "
            SELECT
                s.*,
                lr.request_no,
                lr.user_id as requester_id,
                lr.start_date,
                lr.end_date,
                lr.days_count,
                lr.reason,
                lt.name as leave_type_name,
                u.full_name as requester_name,
                u.role_text as requester_role,
                ai.total_steps,
                ai.current_step,
                ai.status as instance_status
            FROM {$this->table} s
            INNER JOIN approval_instances ai ON s.approval_instance_id = ai.id
            INNER JOIN leave_requests lr ON s.leave_request_id = lr.id
            INNER JOIN leave_types lt ON lr.leave_type_id = lt.id
            INNER JOIN user u ON lr.user_id = u.id
            WHERE s.action = 'PENDING'
              AND ai.status = 'IN_PROGRESS'
              AND s.step_no = ai.current_step
              AND s.assigned_approver_id = ?
            ORDER BY lr.created_at ASC
        ";

        return $this->db->query($sql, array($approverId))->result_array();
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
        $sql = "
            SELECT
                s.*,
                lr.request_no,
                lr.user_id as requester_id,
                lr.start_date,
                lr.end_date,
                lr.days_count,
                lt.name as leave_type_name,
                u.full_name as requester_name
            FROM {$this->table} s
            INNER JOIN leave_requests lr ON s.leave_request_id = lr.id
            INNER JOIN leave_types lt ON lr.leave_type_id = lt.id
            INNER JOIN user u ON lr.user_id = u.id
            WHERE s.actual_approver_id = ?
              AND s.action IN ('APPROVED', 'REJECTED')
            ORDER BY s.action_at DESC
            LIMIT ?
        ";

        return $this->db->query($sql, array($approverId, $limit))->result_array();
    }

    /**
     * Create a new step
     *
     * @param array $data
     * @return int|false
     */
    public function insert($data)
    {
        $data['version'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update a step with optimistic locking
     *
     * @param int $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     */
    public function update_with_lock($id, $data, $expectedVersion)
    {
        $data['version'] = $expectedVersion + 1;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', $id);
        $this->db->where('version', $expectedVersion);
        $this->db->update($this->table, $data);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Update step action (approve/reject)
     *
     * @param int $id
     * @param string $action
     * @param int $approverId
     * @param string|null $notes
     * @param int $expectedVersion
     * @return bool
     */
    public function update_action($id, $action, $approverId, $notes = null, $expectedVersion = null)
    {
        $data = array(
            'action' => $action,
            'actual_approver_id' => $approverId,
            'action_at' => date('Y-m-d H:i:s'),
            'notes' => $notes,
        );

        if ($expectedVersion !== null) {
            return $this->update_with_lock($id, $data, $expectedVersion);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, array('id' => $id));
    }

    /**
     * Get current step for an instance
     *
     * @param int $instanceId
     * @param int $stepNo
     * @return array|null
     */
    public function get_current_step($instanceId, $stepNo)
    {
        $query = $this->db->query("
            SELECT s.*,
                   assigned.full_name as assigned_approver_name,
                   assigned.role_text as assigned_approver_role
            FROM {$this->table} s
            LEFT JOIN user assigned ON s.assigned_approver_id = assigned.id
            WHERE s.approval_instance_id = ? AND s.step_no = ?
        ", array($instanceId, $stepNo));

        return $query->row_array();
    }

    /**
     * Delete steps for an instance
     *
     * @param int $instanceId
     * @return bool
     */
    public function delete_by_instance($instanceId)
    {
        return $this->db->delete($this->table, array('approval_instance_id' => $instanceId));
    }

    /**
     * Get step count by action
     *
     * @param int $instanceId
     * @return array
     */
    public function get_action_counts($instanceId)
    {
        $sql = "
            SELECT action, COUNT(*) as count
            FROM {$this->table}
            WHERE approval_instance_id = ?
            GROUP BY action
        ";

        $result = $this->db->query($sql, array($instanceId))->result_array();

        $counts = array(
            'PENDING' => 0,
            'APPROVED' => 0,
            'REJECTED' => 0,
            'SKIPPED' => 0,
        );

        foreach ($result as $row) {
            $counts[$row['action']] = intval($row['count']);
        }

        return $counts;
    }

    /**
     * Check if all steps are approved
     *
     * @param int $instanceId
     * @return bool
     */
    public function all_approved($instanceId)
    {
        $counts = $this->get_action_counts($instanceId);
        return $counts['PENDING'] === 0 && $counts['REJECTED'] === 0;
    }

    /**
     * Check if any step is rejected
     *
     * @param int $instanceId
     * @return bool
     */
    public function has_rejection($instanceId)
    {
        $counts = $this->get_action_counts($instanceId);
        return $counts['REJECTED'] > 0;
    }

    /**
     * Get pending steps count for dashboard
     *
     * @param int $approverId
     * @return int
     */
    public function get_pending_count($approverId)
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM {$this->table} s
            INNER JOIN approval_instances ai ON s.approval_instance_id = ai.id
            WHERE s.action = 'PENDING'
              AND ai.status = 'IN_PROGRESS'
              AND s.step_no = ai.current_step
              AND s.assigned_approver_id = ?
        ";

        $result = $this->db->query($sql, array($approverId))->row_array();

        return intval($result['count']);
    }
}
