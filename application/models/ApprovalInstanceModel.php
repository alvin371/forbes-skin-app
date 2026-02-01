<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ApprovalInstanceModel
 *
 * Manages approval instances - the snapshot of a route attached to a leave request.
 */
class ApprovalInstanceModel extends CI_Model
{
    protected $table = 'approval_instances';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get instance by leave request ID
     *
     * @param int $leaveRequestId
     * @return array|null
     */
    public function get_by_leave_request($leaveRequestId)
    {
        $query = $this->db->query("
            SELECT ai.*, arv.route_code, arv.name as route_name
            FROM {$this->table} ai
            LEFT JOIN approval_route_versions arv ON ai.route_version_id = arv.id
            WHERE ai.leave_request_id = ?
        ", array($leaveRequestId));

        return $query->row_array();
    }

    /**
     * Get instance by ID
     *
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $query = $this->db->query("
            SELECT ai.*, arv.route_code, arv.name as route_name
            FROM {$this->table} ai
            LEFT JOIN approval_route_versions arv ON ai.route_version_id = arv.id
            WHERE ai.id = ?
        ", array($id));

        return $query->row_array();
    }

    /**
     * Get instances with a specific status
     *
     * @param string $status
     * @param int $limit
     * @return array
     */
    public function get_by_status($status, $limit = 100)
    {
        $sql = "
            SELECT
                ai.*,
                arv.route_code,
                arv.name as route_name,
                lr.request_no,
                lr.user_id,
                lr.start_date,
                lr.end_date,
                lr.days_count,
                u.full_name as requester_name,
                lt.name as leave_type_name
            FROM {$this->table} ai
            LEFT JOIN approval_route_versions arv ON ai.route_version_id = arv.id
            INNER JOIN leave_requests lr ON ai.leave_request_id = lr.id
            INNER JOIN user u ON lr.user_id = u.id
            INNER JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE ai.status = ?
            ORDER BY ai.created_at DESC
            LIMIT ?
        ";

        return $this->db->query($sql, array($status, $limit))->result_array();
    }

    /**
     * Get requests that need route assignment
     *
     * @return array
     */
    public function get_needs_route()
    {
        return $this->get_by_status('NEEDS_ROUTE', 1000);
    }

    /**
     * Get in-progress instances
     *
     * @param int $limit
     * @return array
     */
    public function get_in_progress($limit = 100)
    {
        return $this->get_by_status('IN_PROGRESS', $limit);
    }

    /**
     * Create a new instance
     *
     * @param array $data
     * @return int|false
     */
    public function insert($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update an instance
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, array('id' => $id));
    }

    /**
     * Update instance status
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function update_status($id, $status)
    {
        return $this->update($id, array('status' => $status));
    }

    /**
     * Advance to next step
     *
     * @param int $id
     * @return bool
     */
    public function advance_step($id)
    {
        return $this->db->query("
            UPDATE {$this->table}
            SET current_step = current_step + 1,
                updated_at = NOW()
            WHERE id = ?
        ", array($id));
    }

    /**
     * Delete instance (and related steps)
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->trans_start();

        $this->db->delete('approval_steps', array('approval_instance_id' => $id));
        $this->db->delete($this->table, array('id' => $id));

        $this->db->trans_complete();

        return $this->db->trans_status() !== FALSE;
    }

    /**
     * Get workflow statistics
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function get_statistics($startDate = null, $endDate = null)
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
                status,
                COUNT(*) as count
            FROM {$this->table}
            WHERE $where
            GROUP BY status
        ";

        $result = $this->db->query($sql, $params)->result_array();

        $stats = array(
            'IN_PROGRESS' => 0,
            'COMPLETED' => 0,
            'REJECTED' => 0,
            'CANCELLED' => 0,
            'NEEDS_ROUTE' => 0,
            'total' => 0,
        );

        foreach ($result as $row) {
            $stats[$row['status']] = intval($row['count']);
            $stats['total'] += intval($row['count']);
        }

        return $stats;
    }

    /**
     * Get average approval time (in hours)
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return float
     */
    public function get_average_approval_time($startDate = null, $endDate = null)
    {
        $where = "status = 'COMPLETED'";
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
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours
            FROM {$this->table}
            WHERE $where
        ";

        $result = $this->db->query($sql, $params)->row_array();

        return $result['avg_hours'] ? floatval($result['avg_hours']) : 0;
    }
}
