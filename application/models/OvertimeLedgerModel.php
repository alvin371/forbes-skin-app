<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeLedgerModel
 *
 * Handles CRUD operations for overtime_ledger table.
 * Records approved overtime for payroll and reporting.
 */
class OvertimeLedgerModel extends CI_Model
{
    protected $table = 'overtime_ledger';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get ledger entry by overtime request ID
     *
     * @param int $overtimeRequestId
     * @return array|null
     */
    public function get_by_overtime_request($overtimeRequestId)
    {
        return $this->db->get_where($this->table, array(
            'overtime_request_id' => (int) $overtimeRequestId
        ))->row_array();
    }

    /**
     * Get ledger entries by user and month
     *
     * @param int $userId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function get_by_user_month($userId, $year, $month)
    {
        $this->db->select('ol.*, ot.name as overtime_type_name, ot.code as overtime_type_code');
        $this->db->from($this->table . ' ol');
        $this->db->join('overtime_types ot', 'ot.id = ol.overtime_type_id', 'left');
        $this->db->where('ol.user_id', (int) $userId);
        $this->db->where('ol.year', (int) $year);
        $this->db->where('ol.month', (int) $month);
        $this->db->order_by('ol.overtime_date', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Get total hours for user by month
     *
     * @param int $userId
     * @param int $year
     * @param int $month
     * @return float
     */
    public function get_total_hours_by_month($userId, $year, $month)
    {
        $query = $this->db->query("
            SELECT COALESCE(SUM(hours_worked), 0) as total_hours
            FROM {$this->table}
            WHERE user_id = ? AND year = ? AND month = ?
        ", array($userId, $year, $month));

        $result = $query->row_array();
        return (float) ($result['total_hours'] ?? 0);
    }

    /**
     * Get monthly summary for user
     *
     * @param int $userId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function get_monthly_summary($userId, $year, $month)
    {
        $query = $this->db->query("
            SELECT
                COUNT(*) as entry_count,
                COALESCE(SUM(hours_worked), 0) as total_hours
            FROM {$this->table}
            WHERE user_id = ? AND year = ? AND month = ?
        ", array($userId, $year, $month));

        return $query->row_array();
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

        if ($this->db->insert($this->table, $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * Create ledger entry from approved overtime request
     *
     * @param array $request Overtime request data
     * @param int $approvedBy User ID who approved
     * @return int|false
     */
    public function create_from_request($request, $approvedBy)
    {
        $overtimeDate = $request['overtime_date'];
        $year = (int) date('Y', strtotime($overtimeDate));
        $month = (int) date('n', strtotime($overtimeDate));

        $data = array(
            'overtime_request_id' => $request['id'],
            'user_id' => $request['user_id'],
            'overtime_type_id' => $request['overtime_type_id'],
            'overtime_date' => $overtimeDate,
            'start_time' => $request['start_time'],
            'end_time' => $request['end_time'],
            'hours_worked' => $request['duration_hours'],
            'year' => $year,
            'month' => $month,
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
        );

        return $this->insert($data);
    }

    /**
     * Delete ledger entry by overtime request ID
     *
     * @param int $overtimeRequestId
     * @return bool
     */
    public function delete_by_overtime_request($overtimeRequestId)
    {
        return $this->db->delete($this->table, array(
            'overtime_request_id' => (int) $overtimeRequestId
        ));
    }

    /**
     * Get all ledger entries for a period (for reporting)
     *
     * @param int $year
     * @param int $month
     * @return array
     */
    public function get_all_by_month($year, $month)
    {
        $this->db->select('ol.*, u.full_name as user_name, u.email as user_email,
            ot.name as overtime_type_name');
        $this->db->from($this->table . ' ol');
        $this->db->join('user u', 'u.id = ol.user_id', 'left');
        $this->db->join('overtime_types ot', 'ot.id = ol.overtime_type_id', 'left');
        $this->db->where('ol.year', (int) $year);
        $this->db->where('ol.month', (int) $month);
        $this->db->order_by('u.full_name', 'ASC');
        $this->db->order_by('ol.overtime_date', 'ASC');
        return $this->db->get()->result_array();
    }
}
