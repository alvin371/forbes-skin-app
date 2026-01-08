<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance_log_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    public function insert($data)
    {
        return $this->db->insert('attendance_logs', $data);
    }

    public function has_recent_log($userId, $seconds)
    {
        $seconds = (int) $seconds;
        $userId = (int) $userId;

        $sql = "SELECT id FROM attendance_logs WHERE user_id = ? AND created_at >= (NOW() - INTERVAL ? SECOND) LIMIT 1";
        $query = $this->db->query($sql, array($userId, $seconds));

        return $query->num_rows() > 0;
    }

    public function get_by_user($userId)
    {
        $this->db->select('attendance_logs.*, offices.name as office_name');
        $this->db->from('attendance_logs');
        $this->db->join('offices', 'offices.id = attendance_logs.office_id', 'left');
        $this->db->where('attendance_logs.user_id', (int) $userId);
        $this->db->order_by('attendance_logs.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_by_user_since($userId, $startDate)
    {
        $this->db->select('attendance_logs.*, offices.name as office_name');
        $this->db->from('attendance_logs');
        $this->db->join('offices', 'offices.id = attendance_logs.office_id', 'left');
        $this->db->where('attendance_logs.user_id', (int) $userId);
        $this->db->where('attendance_logs.created_at >=', $startDate);
        $this->db->order_by('attendance_logs.created_at', 'DESC');
        return $this->db->get()->result_array();
    }
}
