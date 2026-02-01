<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * LeaveQuotaLogModel
 *
 * Manages leave quota audit logs for tracking all quota changes.
 */
class LeaveQuotaLogModel extends CI_Model
{
    protected $table = 'leave_quota_logs';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get logs for a user
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_by_user($userId, $limit = 50, $offset = 0)
    {
        $sql = "
            SELECT
                lql.*,
                lt.name as leave_type_name,
                lt.code as leave_type_code,
                lr.request_no,
                p.full_name as performed_by_name
            FROM {$this->table} lql
            INNER JOIN leave_types lt ON lql.leave_type_id = lt.id
            LEFT JOIN leave_requests lr ON lql.leave_request_id = lr.id
            LEFT JOIN user p ON lql.performed_by = p.id
            WHERE lql.user_id = ?
            ORDER BY lql.created_at DESC
            LIMIT ? OFFSET ?
        ";

        return $this->db->query($sql, array($userId, $limit, $offset))->result_array();
    }

    /**
     * Get logs for a specific leave request
     *
     * @param int $leaveRequestId
     * @return array
     */
    public function get_by_leave_request($leaveRequestId)
    {
        $sql = "
            SELECT
                lql.*,
                lt.name as leave_type_name,
                u.full_name as user_name,
                p.full_name as performed_by_name
            FROM {$this->table} lql
            INNER JOIN leave_types lt ON lql.leave_type_id = lt.id
            INNER JOIN user u ON lql.user_id = u.id
            LEFT JOIN user p ON lql.performed_by = p.id
            WHERE lql.leave_request_id = ?
            ORDER BY lql.created_at ASC
        ";

        return $this->db->query($sql, array($leaveRequestId))->result_array();
    }

    /**
     * Get logs by action type
     *
     * @param string $actionType
     * @param int $limit
     * @return array
     */
    public function get_by_action_type($actionType, $limit = 100)
    {
        $sql = "
            SELECT
                lql.*,
                lt.name as leave_type_name,
                u.full_name as user_name,
                p.full_name as performed_by_name
            FROM {$this->table} lql
            INNER JOIN leave_types lt ON lql.leave_type_id = lt.id
            INNER JOIN user u ON lql.user_id = u.id
            LEFT JOIN user p ON lql.performed_by = p.id
            WHERE lql.action_type = ?
            ORDER BY lql.created_at DESC
            LIMIT ?
        ";

        return $this->db->query($sql, array($actionType, $limit))->result_array();
    }

    /**
     * Get logs within date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $userId
     * @return array
     */
    public function get_by_date_range($startDate, $endDate, $userId = null)
    {
        $where = "lql.created_at BETWEEN ? AND ?";
        $params = array($startDate, $endDate . ' 23:59:59');

        if ($userId) {
            $where .= " AND lql.user_id = ?";
            $params[] = $userId;
        }

        $sql = "
            SELECT
                lql.*,
                lt.name as leave_type_name,
                u.full_name as user_name,
                p.full_name as performed_by_name
            FROM {$this->table} lql
            INNER JOIN leave_types lt ON lql.leave_type_id = lt.id
            INNER JOIN user u ON lql.user_id = u.id
            LEFT JOIN user p ON lql.performed_by = p.id
            WHERE $where
            ORDER BY lql.created_at DESC
        ";

        return $this->db->query($sql, $params)->result_array();
    }

    /**
     * Insert a new log entry
     *
     * @param array $data
     * @return int|false
     */
    public function insert($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Get summary of quota changes for a user by leave type
     *
     * @param int $userId
     * @param int $year
     * @return array
     */
    public function get_summary_by_type($userId, $year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $sql = "
            SELECT
                lql.leave_type_id,
                lt.name as leave_type_name,
                SUM(CASE WHEN lql.action_type = 'DEDUCT' THEN ABS(lql.change_amount) ELSE 0 END) as total_deducted,
                SUM(CASE WHEN lql.action_type = 'RESTORE' THEN lql.change_amount ELSE 0 END) as total_restored,
                SUM(CASE WHEN lql.action_type = 'ADJUST' THEN lql.change_amount ELSE 0 END) as total_adjusted,
                COUNT(*) as transaction_count
            FROM {$this->table} lql
            INNER JOIN leave_types lt ON lql.leave_type_id = lt.id
            WHERE lql.user_id = ?
              AND YEAR(lql.created_at) = ?
            GROUP BY lql.leave_type_id, lt.name
        ";

        return $this->db->query($sql, array($userId, $year))->result_array();
    }

    /**
     * Get action type statistics
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function get_action_statistics($startDate = null, $endDate = null)
    {
        $where = '1=1';
        $params = array();

        if ($startDate) {
            $where .= ' AND created_at >= ?';
            $params[] = $startDate;
        }

        if ($endDate) {
            $where .= ' AND created_at <= ?';
            $params[] = $endDate . ' 23:59:59';
        }

        $sql = "
            SELECT
                action_type,
                COUNT(*) as count,
                SUM(ABS(change_amount)) as total_days
            FROM {$this->table}
            WHERE $where
            GROUP BY action_type
        ";

        return $this->db->query($sql, $params)->result_array();
    }

    /**
     * Count logs for a user
     *
     * @param int $userId
     * @return int
     */
    public function count_by_user($userId)
    {
        $result = $this->db->query("
            SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?
        ", array($userId))->row_array();

        return intval($result['count']);
    }
}
