<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Seed Default Overtime Types
 *
 * Seeds the overtime_types table with common overtime type configurations.
 */
class Migration_Seed_overtime_types extends CI_Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $overtimeTypes = array(
            array(
                'code' => 'REGULAR',
                'name' => 'Lembur Reguler',
                'description' => 'Lembur di hari kerja normal',
                'requires_attachment' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array(
                'code' => 'WEEKEND',
                'name' => 'Lembur Akhir Pekan',
                'description' => 'Lembur di hari Sabtu atau Minggu',
                'requires_attachment' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array(
                'code' => 'HOLIDAY',
                'name' => 'Lembur Hari Libur',
                'description' => 'Lembur di hari libur nasional',
                'requires_attachment' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array(
                'code' => 'PROJECT',
                'name' => 'Lembur Proyek Khusus',
                'description' => 'Lembur untuk proyek atau deadline khusus',
                'requires_attachment' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ),
        );

        foreach ($overtimeTypes as $type) {
            // Check if code already exists
            $existing = $this->db->get_where('overtime_types', array('code' => $type['code']))->row_array();
            if (!$existing) {
                $this->db->insert('overtime_types', $type);
            }
        }
    }

    public function down()
    {
        // Remove seeded overtime types
        $codes = array('REGULAR', 'WEEKEND', 'HOLIDAY', 'PROJECT');
        $this->db->where_in('code', $codes);
        $this->db->delete('overtime_types');
    }
}
