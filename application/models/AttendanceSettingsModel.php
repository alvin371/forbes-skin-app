<?php
defined('BASEPATH') or exit('No direct script access allowed');

class AttendanceSettingsModel extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_settings()
    {
        $row = $this->db->get('attendance_settings')->row_array();
        if ($row) {
            return $row;
        }

        return array(
            'id' => null,
            'weekend_type' => 'SATURDAY_SUNDAY',
            'allowed_role_ids' => '',
        );
    }

    public function save_settings($data)
    {
        $existing = $this->db->get('attendance_settings')->row_array();
        if ($existing) {
            $this->db->where('id', (int) $existing['id']);
            return $this->db->update('attendance_settings', $data);
        }

        return $this->db->insert('attendance_settings', $data);
    }

    public function get_allowed_role_ids()
    {
        $settings = $this->get_settings();
        $raw = trim((string) ($settings['allowed_role_ids'] ?? ''));
        if ($raw === '') {
            return array();
        }

        $parts = array_filter(array_map('trim', explode(',', $raw)));
        $ids = array();
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $ids[] = (int) $part;
            }
        }

        return array_values(array_unique($ids));
    }
}
