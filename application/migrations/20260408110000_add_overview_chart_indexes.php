<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_overview_chart_indexes extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('endorse')) {
            $this->add_index_if_missing('endorse', 'idx_endorse_campaign_influencer', 'ALTER TABLE endorse ADD INDEX idx_endorse_campaign_influencer (id_campaign, influencer)');
            $this->add_index_if_missing('endorse', 'idx_endorse_campaign_is_fyp', 'ALTER TABLE endorse ADD INDEX idx_endorse_campaign_is_fyp (id_campaign, is_fyp)');
            $this->add_index_if_missing('endorse', 'idx_endorse_campaign_created_at', 'ALTER TABLE endorse ADD INDEX idx_endorse_campaign_created_at (id_campaign, created_at)');
            $this->add_index_if_missing('endorse', 'idx_endorse_campaign_rencana_at', 'ALTER TABLE endorse ADD INDEX idx_endorse_campaign_rencana_at (id_campaign, rencana_at)');
            $this->add_index_if_missing('endorse', 'idx_endorse_campaign_posting_at', 'ALTER TABLE endorse ADD INDEX idx_endorse_campaign_posting_at (id_campaign, posting_at)');
            $this->add_index_if_missing('endorse', 'idx_endorse_campaign_tgl_tf', 'ALTER TABLE endorse ADD INDEX idx_endorse_campaign_tgl_tf (id_campaign, tgl_tf)');
            $this->add_index_if_missing('endorse', 'idx_endorse_brand_platform_campaign', 'ALTER TABLE endorse ADD INDEX idx_endorse_brand_platform_campaign (brand, platform, id_campaign)');
            $this->add_index_if_missing('endorse', 'idx_endorse_campaign_status', 'ALTER TABLE endorse ADD INDEX idx_endorse_campaign_status (id_campaign, status)');
            $this->add_index_if_missing('endorse', 'idx_endorse_campaign_status_endorse', 'ALTER TABLE endorse ADD INDEX idx_endorse_campaign_status_endorse (id_campaign, status_endorse)');
            $this->add_index_if_missing('endorse', 'idx_endorse_campaign_status_payment', 'ALTER TABLE endorse ADD INDEX idx_endorse_campaign_status_payment (id_campaign, status_payment)');
        }

        if ($this->db->table_exists('endorse_logs')) {
            $this->add_index_if_missing('endorse_logs', 'idx_endorse_logs_date_endorse', 'ALTER TABLE endorse_logs ADD INDEX idx_endorse_logs_date_endorse (date, id_endorse)');
        }
    }

    public function down()
    {
        if ($this->db->table_exists('endorse')) {
            $this->drop_index_if_exists('endorse', 'idx_endorse_campaign_influencer');
            $this->drop_index_if_exists('endorse', 'idx_endorse_campaign_is_fyp');
            $this->drop_index_if_exists('endorse', 'idx_endorse_campaign_created_at');
            $this->drop_index_if_exists('endorse', 'idx_endorse_campaign_rencana_at');
            $this->drop_index_if_exists('endorse', 'idx_endorse_campaign_posting_at');
            $this->drop_index_if_exists('endorse', 'idx_endorse_campaign_tgl_tf');
            $this->drop_index_if_exists('endorse', 'idx_endorse_brand_platform_campaign');
            $this->drop_index_if_exists('endorse', 'idx_endorse_campaign_status');
            $this->drop_index_if_exists('endorse', 'idx_endorse_campaign_status_endorse');
            $this->drop_index_if_exists('endorse', 'idx_endorse_campaign_status_payment');
        }

        if ($this->db->table_exists('endorse_logs')) {
            $this->drop_index_if_exists('endorse_logs', 'idx_endorse_logs_date_endorse');
        }
    }

    private function add_index_if_missing($table, $index_name, $sql)
    {
        if (!$this->index_exists($table, $index_name)) {
            $this->db->query($sql);
        }
    }

    private function drop_index_if_exists($table, $index_name)
    {
        if ($this->index_exists($table, $index_name)) {
            $this->db->query("ALTER TABLE {$table} DROP INDEX {$index_name}");
        }
    }

    private function index_exists($table, $index_name)
    {
        $table = $this->db->escape($table);
        $index_name = $this->db->escape($index_name);

        $row = $this->db->query("
            SELECT 1
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = {$table}
              AND INDEX_NAME = {$index_name}
            LIMIT 1
        ")->row_array();

        return !empty($row);
    }
}
