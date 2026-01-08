<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ApprovalRouteModel extends CI_Model
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
        $this->db->order_by('id', 'DESC');
        return $this->db->get('approval_routes')->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('approval_routes', array('id' => (int) $id))->row_array();
    }

    public function get_active_by_user($userId)
    {
        return $this->db->get_where('approval_routes', array(
            'user_id' => (int) $userId,
            'is_active' => 1,
        ))->row_array();
    }

    public function get_active_by_approver($approverId)
    {
        return $this->db->get_where('approval_routes', array(
            'approver_id' => (int) $approverId,
            'is_active' => 1,
        ))->result_array();
    }

    public function insert($data)
    {
        $this->db->insert('approval_routes', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', (int) $id);
        return $this->db->update('approval_routes', $data);
    }

    public function deactivate($id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->update('approval_routes', array(
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }
}
