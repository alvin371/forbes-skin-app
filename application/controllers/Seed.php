<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Seed extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function attendance_office()
    {
        if (!is_cli()) {
            show_404();
            return;
        }

        $message = $this->seed_attendance_office();
        echo $message . "\n";
    }

    public function leave_types()
    {
        if (!is_cli()) {
            show_404();
            return;
        }

        $message = $this->seed_leave_types();
        echo $message . "\n";
    }

    protected function seed_attendance_office()
    {
        $existing = $this->db->get_where('offices', array('id' => 1))->row_array();
        $now = date('Y-m-d H:i:s');

        $data = array(
            'id' => 1,
            'name' => 'Main Office',
            'lat' => -6.2000000, // TODO: replace with actual office latitude
            'lng' => 106.8166667, // TODO: replace with actual office longitude
            'radius_m' => 150,
            'min_accuracy_m' => 50,
            'allowed_ip_cidrs' => "203.0.113.0/24\n198.51.100.10/32", // TODO: replace with office CIDR allowlist
            'allowed_bssids' => null, // TODO: optional future Wi-Fi BSSID allowlist
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        );

        if ($existing) {
            unset($data['id'], $data['created_at']);
            $this->db->where('id', 1);
            $this->db->update('offices', $data);
            return 'Office seed updated.';
        }

        $this->db->insert('offices', $data);
        return 'Office seed inserted.';
    }

    protected function seed_leave_types()
    {
        $now = date('Y-m-d H:i:s');
        $seed = array(
            array(
                'code' => 'ANNUAL',
                'name' => 'Annual Leave',
                'is_paid' => 1,
                'requires_attachment' => 0,
                'max_days_per_request' => null,
            ),
            array(
                'code' => 'SICK',
                'name' => 'Sick Leave',
                'is_paid' => 1,
                'requires_attachment' => 1,
                'max_days_per_request' => null,
            ),
            array(
                'code' => 'UNPAID',
                'name' => 'Unpaid Leave',
                'is_paid' => 0,
                'requires_attachment' => 0,
                'max_days_per_request' => null,
            ),
        );

        foreach ($seed as $leaveType) {
            $existing = $this->db->get_where('leave_types', array('code' => $leaveType['code']))->row_array();
            $payload = array(
                'name' => $leaveType['name'],
                'is_paid' => $leaveType['is_paid'],
                'requires_attachment' => $leaveType['requires_attachment'],
                'max_days_per_request' => $leaveType['max_days_per_request'],
                'is_active' => 1,
                'updated_at' => $now,
            );

            if ($existing) {
                $this->db->where('id', (int) $existing['id']);
                $this->db->update('leave_types', $payload);
                continue;
            }

            $payload['code'] = $leaveType['code'];
            $payload['created_at'] = $now;
            $this->db->insert('leave_types', $payload);
        }

        return 'Leave types seed completed.';
    }
}
