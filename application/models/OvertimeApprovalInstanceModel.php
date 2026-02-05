<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeApprovalInstanceModel
 *
 * Handles CRUD operations for overtime_approval_instances table.
 */
class OvertimeApprovalInstanceModel extends CI_Model
{
    protected $table = 'overtime_approval_instances';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get instance by overtime request ID
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
     * Get instance by ID
     *
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row_array();
    }

    /**
     * Insert a new approval instance
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
     * Update an approval instance
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
     * Delete an approval instance
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->db->delete($this->table, array('id' => (int) $id));
    }

    /**
     * Delete instance by overtime request ID
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
}
