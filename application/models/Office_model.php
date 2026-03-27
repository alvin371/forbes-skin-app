<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Office_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('offices', array('id' => (int) $id))->row_array();
    }

    public function get_active_office()
    {
        $this->db->from('offices');
        $this->db->where('is_active', 1);
        $this->db->order_by('id', 'ASC');
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }

    public function get_active_offices()
    {
        $this->db->from('offices');
        $this->db->where('is_active', 1);
        $this->db->order_by('id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_all()
    {
        return $this->db->order_by('id', 'ASC')->get('offices')->result_array();
    }

    public function generate_duplicate_name($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            $name = 'Office';
        }

        $this->db->select('name');
        $this->db->from('offices');
        $this->db->group_start();
        $this->db->where('name', $name);
        $this->db->or_like('name', $name . ' (Copy ', 'after');
        $this->db->group_end();
        $names = $this->db->get()->result_array();

        $highestNumber = 0;
        foreach ($names as $row) {
            $existingName = isset($row['name']) ? (string) $row['name'] : '';
            if (preg_match('/^' . preg_quote($name, '/') . ' \(Copy (\d+)\)$/', $existingName, $matches)) {
                $highestNumber = max($highestNumber, (int) $matches[1]);
            }
        }

        return $name . ' (Copy ' . ($highestNumber + 1) . ')';
    }

    public function set_active($officeId)
    {
        $officeId = (int) $officeId;
        $this->db->where('id', $officeId);
        return $this->db->update('offices', array('is_active' => 1));
    }

    public function set_inactive($officeId)
    {
        $officeId = (int) $officeId;
        $this->db->where('id', $officeId);
        return $this->db->update('offices', array('is_active' => 0));
    }
}
