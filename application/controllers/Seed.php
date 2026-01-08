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

    public function holidays()
    {
        if (!is_cli()) {
            show_404();
            return;
        }

        $message = $this->seed_holidays();
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

    protected function seed_holidays()
    {
        $now = date('Y-m-d H:i:s');

        // Indonesian Public Holidays 2026
        // Source: https://www.timeanddate.com/holidays/indonesia/2026
        $holidays = array(
            array('date' => '2026-01-01', 'name' => 'Tahun Baru Masehi / New Year\'s Day'),
            array('date' => '2026-01-16', 'name' => 'Isra Mikraj Nabi Muhammad SAW / Ascension of the Prophet Muhammad'),
            array('date' => '2026-02-17', 'name' => 'Tahun Baru Imlek / Chinese New Year'),
            array('date' => '2026-03-19', 'name' => 'Hari Raya Nyepi / Bali\'s Day of Silence and Hindu New Year'),
            array('date' => '2026-03-21', 'name' => 'Hari Raya Idul Fitri / Eid al-Fitr'),
            array('date' => '2026-03-22', 'name' => 'Cuti Bersama Idul Fitri / Eid al-Fitr Holiday'),
            array('date' => '2026-04-03', 'name' => 'Wafat Yesus Kristus / Good Friday'),
            array('date' => '2026-04-05', 'name' => 'Paskah / Easter Sunday'),
            array('date' => '2026-05-01', 'name' => 'Hari Buruh Internasional / International Labor Day'),
            array('date' => '2026-05-14', 'name' => 'Kenaikan Yesus Kristus / Ascension Day of Jesus Christ'),
            array('date' => '2026-05-27', 'name' => 'Hari Raya Idul Adha / Eid al-Adha'),
            array('date' => '2026-05-31', 'name' => 'Hari Raya Waisak / Buddha\'s Birthday (Vesak Day)'),
            array('date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila / Pancasila Day'),
            array('date' => '2026-06-16', 'name' => 'Tahun Baru Islam / Islamic New Year (Muharram)'),
            array('date' => '2026-08-17', 'name' => 'Hari Kemerdekaan Republik Indonesia / Indonesian Independence Day'),
            array('date' => '2026-08-25', 'name' => 'Maulid Nabi Muhammad SAW / Birth of the Prophet Muhammad'),
            array('date' => '2026-12-25', 'name' => 'Hari Raya Natal / Christmas Day'),
        );

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($holidays as $holiday) {
            $existing = $this->db->get_where('holidays', array('date' => $holiday['date']))->row_array();

            $payload = array(
                'name' => $holiday['name'],
                'is_active' => 1,
                'updated_at' => $now,
            );

            if ($existing) {
                $this->db->where('id', (int) $existing['id']);
                $this->db->update('holidays', $payload);
                $updated++;
                continue;
            }

            $payload['date'] = $holiday['date'];
            $payload['created_at'] = $now;
            $this->db->insert('holidays', $payload);
            $inserted++;
        }

        return sprintf('Holidays seed completed: %d inserted, %d updated, %d skipped.', $inserted, $updated, $skipped);
    }
}
