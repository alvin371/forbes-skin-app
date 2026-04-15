<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_attendance_reason_fields extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('attendance_reason', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs ADD COLUMN attendance_reason TEXT NULL");
        }

        if (!$this->db->field_exists('attachment_path', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs ADD COLUMN attachment_path VARCHAR(255) NULL");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('attendance_reason', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs DROP COLUMN attendance_reason");
        }

        if ($this->db->field_exists('attachment_path', 'attendance_logs')) {
            $this->db->query("ALTER TABLE attendance_logs DROP COLUMN attachment_path");
        }
    }
}
