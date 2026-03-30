<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Office_model extends CI_Model
{
    const WIFI_STATUS_ACTIVE = 'active';
    const WIFI_STATUS_INACTIVE = 'inactive';

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

    public function count_filtered($filters = array())
    {
        $this->apply_listing_filters($filters);
        return (int) $this->db->count_all_results();
    }

    public function get_paginated($limit, $offset = 0, $filters = array())
    {
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $this->db->select('offices.*');
        $this->db->select("(" . $this->wifi_status_case_sql() . ") AS wifi_active", false);
        $this->apply_listing_filters($filters);
        $this->db->order_by('wifi_active', 'DESC', false);
        $this->db->order_by('is_active', 'DESC');
        $this->db->order_by('name', 'ASC');
        $this->db->order_by('id', 'ASC');
        $this->db->limit($limit, $offset);

        return $this->db->get()->result_array();
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

    private function apply_listing_filters($filters = array())
    {
        $this->db->from('offices');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('name', $search);
            $this->db->or_like('allowed_ssids', $search);
            $this->db->or_like('allowed_bssids', $search);
            $this->db->group_end();
        }

        $wifiFilter = (string) ($filters['wifi_filter'] ?? '');
        if ($wifiFilter === self::WIFI_STATUS_ACTIVE) {
            $this->db->where("(" . $this->wifi_status_case_sql() . ") = 1", null, false);
        } elseif ($wifiFilter === self::WIFI_STATUS_INACTIVE) {
            $this->db->where("(" . $this->wifi_status_case_sql() . ") = 0", null, false);
        }
    }

    private function wifi_status_case_sql()
    {
        return "CASE WHEN TRIM(COALESCE(allowed_ssids, '')) <> '' OR TRIM(COALESCE(allowed_bssids, '')) <> '' THEN 1 ELSE 0 END";
    }
}
