<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeRequestModel
 *
 * Handles CRUD operations for overtime_requests table.
 */
class OvertimeRequestModel extends CI_Model
{
    protected $table = 'overtime_requests';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get overtime requests by user
     *
     * @param int $userId
     * @param array $filters Optional filters (status, month)
     * @return array
     */
    public function get_by_user($userId, $filters = array())
    {
        $this->db->select('or.*, ot.name as overtime_type_name, ot.code as overtime_type_code');
        $this->db->from($this->table . ' or');
        $this->db->join('overtime_types ot', 'ot.id = or.overtime_type_id', 'left');
        $this->db->where('or.user_id', (int) $userId);

        if (!empty($filters['status'])) {
            $this->db->where('or.status', $filters['status']);
        }

        if (!empty($filters['month'])) {
            // Format: YYYY-MM
            $startDate = $filters['month'] . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
            $this->db->where('or.overtime_date >=', $startDate);
            $this->db->where('or.overtime_date <=', $endDate);
        }

        $this->db->order_by('or.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    /**
     * Get overtime request by ID with details
     *
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $this->db->select('or.*, ot.name as overtime_type_name, ot.code as overtime_type_code,
            ot.requires_attachment, u.full_name as requester_name, u.email as requester_email');
        $this->db->from($this->table . ' or');
        $this->db->join('overtime_types ot', 'ot.id = or.overtime_type_id', 'left');
        $this->db->join('user u', 'u.id = or.user_id', 'left');
        $this->db->where('or.id', (int) $id);
        return $this->db->get()->row_array();
    }

    /**
     * Get overtime request by request number
     *
     * @param string $requestNo
     * @return array|null
     */
    public function get_by_request_no($requestNo)
    {
        $this->db->select('or.*, ot.name as overtime_type_name, ot.code as overtime_type_code');
        $this->db->from($this->table . ' or');
        $this->db->join('overtime_types ot', 'ot.id = or.overtime_type_id', 'left');
        $this->db->where('or.request_no', $requestNo);
        return $this->db->get()->row_array();
    }

    /**
     * Insert a new overtime request
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
     * Update an overtime request
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
     * Delete an overtime request
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->db->delete($this->table, array('id' => (int) $id));
    }

    /**
     * Get user overtime summary for a month
     *
     * @param int $userId
     * @param string $month Format: YYYY-MM
     * @return array
     */
    public function get_user_monthly_summary($userId, $month)
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = $this->db->query("
            SELECT
                COUNT(*) as request_count,
                COALESCE(SUM(CASE WHEN status = 'APPROVED' THEN duration_hours ELSE 0 END), 0) as approved_hours,
                COALESCE(SUM(CASE WHEN status IN ('SUBMITTED', 'IN_REVIEW') THEN duration_hours ELSE 0 END), 0) as pending_hours,
                COALESCE(SUM(duration_hours), 0) as total_hours
            FROM {$this->table}
            WHERE user_id = ?
              AND overtime_date >= ?
              AND overtime_date <= ?
              AND status NOT IN ('CANCELLED', 'REJECTED')
        ", array($userId, $startDate, $endDate));

        return $query->row_array();
    }

    /**
     * Check if user has overlapping overtime request
     *
     * @param int $userId
     * @param string $date
     * @param string $startTime
     * @param string $endTime
     * @param int|null $excludeId
     * @return bool
     */
    public function has_overlap($userId, $date, $startTime, $endTime, $excludeId = null)
    {
        $this->db->where('user_id', (int) $userId);
        $this->db->where('overtime_date', $date);
        $this->db->where_in('status', array('SUBMITTED', 'IN_REVIEW', 'APPROVED'));

        if ($excludeId) {
            $this->db->where('id !=', (int) $excludeId);
        }

        // Check time overlap
        $this->db->group_start();
        $this->db->where("(start_time < '$endTime' AND end_time > '$startTime')");
        $this->db->group_end();

        return $this->db->get($this->table)->num_rows() > 0;
    }

    /**
     * Generate unique request number
     *
     * @return string
     */
    public function generate_request_no()
    {
        $date = date('Ymd');

        for ($i = 0; $i < 5; $i++) {
            $suffix = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $requestNo = 'OT-' . $date . '-' . $suffix;

            $exists = $this->db->get_where($this->table, array('request_no' => $requestNo))->row_array();
            if (!$exists) {
                return $requestNo;
            }
        }

        return 'OT-' . $date . '-' . uniqid();
    }
}
