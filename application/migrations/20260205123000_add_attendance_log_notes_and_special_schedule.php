<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_attendance_log_notes_and_special_schedule extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('notes', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs ADD COLUMN notes TEXT NULL");
        }

        if (!$this->db->field_exists('special_schedule', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs ADD COLUMN special_schedule TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('notes', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs DROP COLUMN notes");
        }

        if ($this->db->field_exists('special_schedule', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs DROP COLUMN special_schedule");
        }
    }
}
