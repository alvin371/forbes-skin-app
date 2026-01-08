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

    public function performance_2026()
    {
        if (!is_cli()) {
            show_404();
            return;
        }

        $message = $this->seed_performance_2026_template();
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

    protected function seed_performance_2026_template()
    {
        $now = date('Y-m-d H:i:s');

        if (!$this->db->table_exists('performance_templates')) {
            return 'Performance templates table not found. Run migrations first.';
        }

        $templateData = array(
            'name' => 'Performance Appraisal HRGA 2026',
            'period_year' => 2026,
            'department' => 'HRGA',
            'is_active' => 0,
            'updated_at' => $now,
        );

        $existing = $this->db->get_where('performance_templates', array(
            'name' => $templateData['name'],
            'period_year' => $templateData['period_year'],
        ))->row_array();

        $this->db->trans_start();

        if ($existing) {
            $this->db->where('id', (int) $existing['id']);
            $this->db->update('performance_templates', $templateData);
            $templateId = (int) $existing['id'];
        } else {
            $templateData['created_at'] = $now;
            $this->db->insert('performance_templates', $templateData);
            $templateId = (int) $this->db->insert_id();
        }

        $this->db->where('template_id', $templateId);
        $this->db->delete('performance_template_items');

        $items = array(
            array(
                'order_no' => 1,
                'objective' => 'Efisiensi biaya operasional HRGA',
                'kpi' => 'Cost Saving',
                'target_value' => 100,
                'unit' => '%',
                'weight' => 20,
            ),
            array(
                'order_no' => 2,
                'objective' => 'Kepuasan layanan HRGA',
                'kpi' => 'Kepuasan karyawan terhadap kinerja HR',
                'target_value' => 90,
                'unit' => '%',
                'weight' => 20,
            ),
            array(
                'order_no' => 3,
                'objective' => 'Pemenuhan karyawan',
                'kpi' => 'Ketepatan waktu pemenuhan karyawan baru',
                'target_value' => 100,
                'unit' => '%',
                'weight' => 30,
            ),
            array(
                'order_no' => 4,
                'objective' => 'Kontrak dan masa kerja',
                'kpi' => 'Kontrak kerja sesuai ketentuan',
                'target_value' => 100,
                'unit' => '%',
                'weight' => 20,
            ),
            array(
                'order_no' => 5,
                'objective' => 'Presensi',
                'kpi' => 'Tingkat kehadiran karyawan',
                'target_value' => 95,
                'unit' => '%',
                'weight' => 10,
            ),
        );

        foreach ($items as $item) {
            $payload = array(
                'template_id' => $templateId,
                'order_no' => $item['order_no'],
                'objective' => $item['objective'],
                'kpi' => $item['kpi'],
                'target_value' => $item['target_value'],
                'unit' => $item['unit'],
                'weight' => $item['weight'],
                'created_at' => $now,
                'updated_at' => $now,
            );
            $this->db->insert('performance_template_items', $payload);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return 'Performance template seed failed.';
        }

        return 'Performance template 2026 seed completed.';
    }
}
