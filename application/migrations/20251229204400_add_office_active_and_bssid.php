<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_office_active_and_bssid extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('is_active', 'offices')) {
            $this->db->query("ALTER TABLE offices ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 0");
        }

        if (!$this->db->field_exists('allowed_bssids', 'offices')) {
            $this->db->query("ALTER TABLE offices ADD COLUMN allowed_bssids TEXT NULL");
        }

        $index = $this->db->query("SHOW INDEX FROM offices WHERE Key_name = 'idx_offices_is_active'")->result_array();
        if (empty($index)) {
            $this->db->query("CREATE INDEX idx_offices_is_active ON offices (is_active)");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('is_active', 'offices')) {
            $this->db->query("ALTER TABLE offices DROP COLUMN is_active");
        }

        if ($this->db->field_exists('allowed_bssids', 'offices')) {
            $this->db->query("ALTER TABLE offices DROP COLUMN allowed_bssids");
        }
    }
}
