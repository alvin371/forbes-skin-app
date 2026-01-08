<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LeaveApprovalModel extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_by_request_and_approver($requestId, $approverId)
    {
        return $this->db->get_where('leave_approvals', array(
            'leave_request_id' => (int) $requestId,
            'approver_id' => (int) $approverId,
        ))->row_array();
    }

    public function insert($data)
    {
        $this->db->insert('leave_approvals', $data);
        return $this->db->insert_id();
    }

    public function update_action($id, $data)
    {
        $this->db->where('id', (int) $id);
        return $this->db->update('leave_approvals', $data);
    }
}
