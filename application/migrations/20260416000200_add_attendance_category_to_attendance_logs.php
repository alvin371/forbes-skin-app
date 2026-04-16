<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_attendance_category_to_attendance_logs extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('attendance_category', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs ADD COLUMN attendance_category VARCHAR(32) NOT NULL DEFAULT 'REGULAR'");
        }

        $this->db->query("UPDATE attendance_logs SET attendance_category = 'REGULAR' WHERE attendance_category IS NULL OR attendance_category = ''");
    }

    public function down()
    {
        if ($this->db->field_exists('attendance_category', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs DROP COLUMN attendance_category");
        }
    }
}
