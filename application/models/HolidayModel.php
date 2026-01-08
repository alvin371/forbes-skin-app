<?php
defined('BASEPATH') or exit('No direct script access allowed');

class HolidayModel extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_all()
    {
        $this->db->order_by('date', 'ASC');
        return $this->db->get('holidays')->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('holidays', array('id' => (int) $id))->row_array();
    }

    public function get_active_between($startDate, $endDate)
    {
        $this->db->where('is_active', 1);
        $this->db->where('date >=', $startDate);
        $this->db->where('date <=', $endDate);
        return $this->db->get('holidays')->result_array();
    }

    public function get_by_date($date)
    {
        return $this->db->get_where('holidays', array('date' => $date))->row_array();
    }

    public function insert($data)
    {
        $this->db->insert('holidays', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', (int) $id);
        return $this->db->update('holidays', $data);
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->delete('holidays');
    }
}
