<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeTypeModel
 *
 * Handles CRUD operations for overtime_types table.
 */
class OvertimeTypeModel extends CI_Model
{
    protected $table = 'overtime_types';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all overtime types
     *
     * @param bool $activeOnly
     * @return array
     */
    public function get_all($activeOnly = false)
    {
        if ($activeOnly) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get active overtime types
     *
     * @return array
     */
    public function get_active()
    {
        return $this->get_all(true);
    }

    /**
     * Get overtime type by ID
     *
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row_array();
    }

    /**
     * Get overtime type by code
     *
     * @param string $code
     * @return array|null
     */
    public function get_by_code($code)
    {
        return $this->db->get_where($this->table, array('code' => $code))->row_array();
    }

    /**
     * Insert a new overtime type
     *
     * @param array $data
     * @return int|false
     */
    public function insert($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($this->db->insert($this->table, $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * Update an overtime type
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
     * Delete an overtime type
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->db->delete($this->table, array('id' => (int) $id));
    }

    /**
     * Check if code exists (for unique validation)
     *
     * @param string $code
     * @param int|null $excludeId
     * @return bool
     */
    public function code_exists($code, $excludeId = null)
    {
        $this->db->where('code', $code);
        if ($excludeId) {
            $this->db->where('id !=', (int) $excludeId);
        }
        return $this->db->get($this->table)->num_rows() > 0;
    }
}
