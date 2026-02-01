<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * LeaveLedgerModel
 *
 * Manages the leave ledger - permanent record of approved leave usage.
 */
class LeaveLedgerModel extends CI_Model
{
    protected $table = 'leave_ledger';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get ledger entry by leave request ID
     *
     * @param int $leaveRequestId
     * @return array|null
     */
    public function get_by_leave_request($leaveRequestId)
    {
        $query = $this->db->query("
            SELECT ll.*,
                   lt.name as leave_type_name,
                   lt.code as leave_type_code,
                   u.full_name as user_name,
                   a.full_name as approved_by_name
            FROM {$this->table} ll
            INNER JOIN leave_types lt ON ll.leave_type_id = lt.id
            INNER JOIN user u ON ll.user_id = u.id
            LEFT JOIN user a ON ll.approved_by = a.id
            WHERE ll.leave_request_id = ?
        ", array($leaveRequestId));

        return $query->row_array();
    }

    /**
     * Get ledger entries for a user
     *
     * @param int $userId
     * @param int|null $year
     * @return array
     */
    public function get_by_user($userId, $year = null)
    {
        $where = "ll.user_id = ?";
        $params = array($userId);

        if ($year) {
            $where .= " AND ll.year = ?";
            $params[] = $year;
        }

        $sql = "
            SELECT ll.*,
                   lt.name as leave_type_name,
                   lt.code as leave_type_code,
                   lr.request_no,
                   a.full_name as approved_by_name
            FROM {$this->table} ll
            INNER JOIN leave_types lt ON ll.leave_type_id = lt.id
            INNER JOIN leave_requests lr ON ll.leave_request_id = lr.id
            LEFT JOIN user a ON ll.approved_by = a.id
            WHERE $where
            ORDER BY ll.start_date DESC
        ";

        return $this->db->query($sql, $params)->result_array();
    }

    /**
     * Get ledger entries by leave type
     *
     * @param int $leaveTypeId
     * @param int|null $year
     * @param int $limit
     * @return array
     */
    public function get_by_leave_type($leaveTypeId, $year = null, $limit = 100)
    {
        $where = "ll.leave_type_id = ?";
        $params = array($leaveTypeId);

        if ($year) {
            $where .= " AND ll.year = ?";
            $params[] = $year;
        }

        $params[] = $limit;

        $sql = "
            SELECT ll.*,
                   lt.name as leave_type_name,
                   u.full_name as user_name,
                   lr.request_no,
                   a.full_name as approved_by_name
            FROM {$this->table} ll
            INNER JOIN leave_types lt ON ll.leave_type_id = lt.id
            INNER JOIN user u ON ll.user_id = u.id
            INNER JOIN leave_requests lr ON ll.leave_request_id = lr.id
            LEFT JOIN user a ON ll.approved_by = a.id
            WHERE $where
            ORDER BY ll.approved_at DESC
            LIMIT ?
        ";

        return $this->db->query($sql, $params)->result_array();
    }

    /**
     * Get ledger entries by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $userId
     * @return array
     */
    public function get_by_date_range($startDate, $endDate, $userId = null)
    {
        $where = "(ll.start_date <= ? AND ll.end_date >= ?)";
        $params = array($endDate, $startDate);

        if ($userId) {
            $where .= " AND ll.user_id = ?";
            $params[] = $userId;
        }

        $sql = "
            SELECT ll.*,
                   lt.name as leave_type_name,
                   u.full_name as user_name,
                   u.role_text as user_role,
                   lr.request_no
            FROM {$this->table} ll
            INNER JOIN leave_types lt ON ll.leave_type_id = lt.id
            INNER JOIN user u ON ll.user_id = u.id
            INNER JOIN leave_requests lr ON ll.leave_request_id = lr.id
            WHERE $where
            ORDER BY ll.start_date ASC
        ";

        return $this->db->query($sql, $params)->result_array();
    }

    /**
     * Insert a new ledger entry
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
     * Delete ledger entry by leave request ID
     *
     * @param int $leaveRequestId
     * @return bool
     */
    public function delete_by_leave_request($leaveRequestId)
    {
        return $this->db->delete($this->table, array('leave_request_id' => $leaveRequestId));
    }

    /**
     * Get usage summary by user for a year
     *
     * @param int $userId
     * @param int $year
     * @return array
     */
    public function get_usage_summary($userId, $year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $sql = "
            SELECT
                ll.leave_type_id,
                lt.name as leave_type_name,
                lt.code as leave_type_code,
                lt.is_paid,
                SUM(ll.days_used) as total_days_used,
                SUM(CASE WHEN ll.is_paid = 1 THEN ll.days_used ELSE 0 END) as paid_days,
                SUM(CASE WHEN ll.is_paid = 0 THEN ll.days_used ELSE 0 END) as unpaid_days,
                COUNT(*) as request_count
            FROM {$this->table} ll
            INNER JOIN leave_types lt ON ll.leave_type_id = lt.id
            WHERE ll.user_id = ?
              AND ll.year = ?
            GROUP BY ll.leave_type_id, lt.name, lt.code, lt.is_paid
        ";

        return $this->db->query($sql, array($userId, $year))->result_array();
    }

    /**
     * Get company-wide usage statistics
     *
     * @param int $year
     * @param int|null $month
     * @return array
     */
    public function get_company_statistics($year = null, $month = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $where = "ll.year = ?";
        $params = array($year);

        if ($month) {
            $where .= " AND MONTH(ll.start_date) = ?";
            $params[] = $month;
        }

        $sql = "
            SELECT
                lt.id as leave_type_id,
                lt.name as leave_type_name,
                COUNT(DISTINCT ll.user_id) as unique_users,
                COUNT(*) as total_requests,
                SUM(ll.days_used) as total_days,
                AVG(ll.days_used) as avg_days_per_request
            FROM {$this->table} ll
            INNER JOIN leave_types lt ON ll.leave_type_id = lt.id
            WHERE $where
            GROUP BY lt.id, lt.name
            ORDER BY total_days DESC
        ";

        return $this->db->query($sql, $params)->result_array();
    }

    /**
     * Get monthly trend for a year
     *
     * @param int $year
     * @param int|null $leaveTypeId
     * @return array
     */
    public function get_monthly_trend($year = null, $leaveTypeId = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $where = "ll.year = ?";
        $params = array($year);

        if ($leaveTypeId) {
            $where .= " AND ll.leave_type_id = ?";
            $params[] = $leaveTypeId;
        }

        $sql = "
            SELECT
                MONTH(ll.start_date) as month,
                COUNT(DISTINCT ll.user_id) as unique_users,
                COUNT(*) as total_requests,
                SUM(ll.days_used) as total_days
            FROM {$this->table} ll
            WHERE $where
            GROUP BY MONTH(ll.start_date)
            ORDER BY month ASC
        ";

        return $this->db->query($sql, $params)->result_array();
    }

    /**
     * Check if user has leave on a specific date
     *
     * @param int $userId
     * @param string $date
     * @return array|null
     */
    public function get_leave_on_date($userId, $date)
    {
        $query = $this->db->query("
            SELECT ll.*, lt.name as leave_type_name
            FROM {$this->table} ll
            INNER JOIN leave_types lt ON ll.leave_type_id = lt.id
            WHERE ll.user_id = ?
              AND ? BETWEEN ll.start_date AND ll.end_date
        ", array($userId, $date));

        return $query->row_array();
    }

    /**
     * Get users on leave for a date range (for calendar/planning)
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function get_users_on_leave($startDate, $endDate)
    {
        $sql = "
            SELECT
                ll.*,
                u.full_name as user_name,
                u.role_text as user_role,
                lt.name as leave_type_name
            FROM {$this->table} ll
            INNER JOIN user u ON ll.user_id = u.id
            INNER JOIN leave_types lt ON ll.leave_type_id = lt.id
            WHERE ll.start_date <= ?
              AND ll.end_date >= ?
            ORDER BY ll.start_date ASC
        ";

        return $this->db->query($sql, array($endDate, $startDate))->result_array();
    }
}
