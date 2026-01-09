<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LeaveRequestModel extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_by_id($id)
    {
        $this->db->select('lr.*, lt.name as leave_type_name, lt.code as leave_type_code, lt.requires_attachment, lt.max_days_per_request');
        $this->db->from('leave_requests lr');
        $this->db->join('leave_types lt', 'lt.id = lr.leave_type_id', 'left');
        $this->db->where('lr.id', (int) $id);
        return $this->db->get()->row_array();
    }

    public function get_by_user($userId)
    {
        $this->db->select('lr.*, lt.name as leave_type_name, lt.code as leave_type_code');
        $this->db->from('leave_requests lr');
        $this->db->join('leave_types lt', 'lt.id = lr.leave_type_id', 'left');
        $this->db->where('lr.user_id', (int) $userId);
        $this->db->order_by('lr.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function insert($data)
    {
        $this->db->insert('leave_requests', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', (int) $id);
        return $this->db->update('leave_requests', $data);
    }

    public function get_pending_for_approver($approverId)
    {
        $this->db->select('lr.*, lt.name as leave_type_name, lt.code as leave_type_code, u.full_name as requester_name, u.email as requester_email');
        $this->db->from('leave_approvals la');
        $this->db->join('leave_requests lr', 'lr.id = la.leave_request_id', 'inner');
        $this->db->join('leave_types lt', 'lt.id = lr.leave_type_id', 'left');
        $this->db->join('user u', 'u.id = lr.user_id', 'left');
        $this->db->where('la.approver_id', (int) $approverId);
        $this->db->where('la.action', 'PENDING');
        $this->db->order_by('lr.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_all_with_details()
    {
        $this->db->select('lr.*, lt.name as leave_type_name, lt.code as leave_type_code,
            u.full_name as requester_name, u.email as requester_email, u.department as requester_department, u.position as requester_position,
            la.id as approval_id, la.approver_id, la.action as approval_action, la.action_at as approval_action_at, la.notes as approval_notes,
            approver.full_name as approver_name, approver.email as approver_email');
        $this->db->from('leave_requests lr');
        $this->db->join('leave_types lt', 'lt.id = lr.leave_type_id', 'left');
        $this->db->join('user u', 'u.id = lr.user_id', 'left');
        $this->db->join('leave_approvals la', 'la.leave_request_id = lr.id', 'left');
        $this->db->join('user approver', 'approver.id = la.approver_id', 'left');
        $this->db->order_by('lr.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_all_pending_approvals()
    {
        $this->db->select('lr.*, lt.name as leave_type_name, lt.code as leave_type_code, u.full_name as requester_name, u.email as requester_email');
        $this->db->from('leave_requests lr');
        $this->db->join('leave_types lt', 'lt.id = lr.leave_type_id', 'left');
        $this->db->join('user u', 'u.id = lr.user_id', 'left');
        $this->db->where('lr.status', 'PENDING_APPROVAL');
        $this->db->order_by('lr.created_at', 'DESC');
        return $this->db->get()->result_array();
    }
}
