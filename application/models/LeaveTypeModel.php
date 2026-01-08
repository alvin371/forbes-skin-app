<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LeaveTypeModel extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_all($include_inactive = TRUE)
    {
        if (!$include_inactive) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get('leave_types')->result_array();
    }

    public function get_active()
    {
        return $this->get_all(FALSE);
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('leave_types', array('id' => (int) $id))->row_array();
    }

    public function get_by_code($code)
    {
        return $this->db->get_where('leave_types', array('code' => $code))->row_array();
    }

    public function insert($data)
    {
        $this->db->insert('leave_types', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', (int) $id);
        return $this->db->update('leave_types', $data);
    }

    public function deactivate($id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->update('leave_types', array(
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }
}
