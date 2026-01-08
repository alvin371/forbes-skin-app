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
        return $this->db->get_where('offices', array('is_active' => 1))->row_array();
    }

    public function get_all()
    {
        return $this->db->order_by('id', 'ASC')->get('offices')->result_array();
    }

    public function set_active($officeId)
    {
        $officeId = (int) $officeId;
        $this->db->trans_start();
        $this->db->update('offices', array('is_active' => 0));
        $this->db->where('id', $officeId);
        $this->db->update('offices', array('is_active' => 1));
        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
