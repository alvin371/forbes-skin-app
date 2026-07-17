<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_instagram_threads_social_support extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('influencer')) {
            if (!$this->db->field_exists('threads_user_id', 'influencer')) $this->db->query("ALTER TABLE influencer ADD threads_user_id VARCHAR(64) NULL AFTER account_id");
            if (!$this->index_exists('influencer', 'idx_influencer_threads_user_id')) $this->db->query("ALTER TABLE influencer ADD INDEX idx_influencer_threads_user_id (threads_user_id)");
            if (!$this->db->field_exists('threads_access_token', 'influencer')) $this->db->query("ALTER TABLE influencer ADD threads_access_token TEXT NULL AFTER threads_user_id");
            if (!$this->db->field_exists('threads_token_expires_at', 'influencer')) $this->db->query("ALTER TABLE influencer ADD threads_token_expires_at DATETIME NULL AFTER threads_access_token");
        }
        if ($this->db->table_exists('endorse') && !$this->db->field_exists('threads_media_id', 'endorse')) {
            $this->db->query("ALTER TABLE endorse ADD threads_media_id VARCHAR(64) NULL");
        }
        if ($this->db->table_exists('endorse_refresh_provider_health')) {
            $this->db->query("INSERT IGNORE INTO endorse_refresh_provider_health (provider_key, state, generation, updated_at) VALUES ('instagram_rapidapi', 'closed', 1, UTC_TIMESTAMP(6)), ('threads_graph', 'closed', 1, UTC_TIMESTAMP(6))");
        }
    }

    public function down()
    {
        if ($this->db->table_exists('endorse_refresh_provider_health')) $this->db->where_in('provider_key', ['instagram_rapidapi', 'threads_graph'])->delete('endorse_refresh_provider_health');
        if ($this->db->table_exists('endorse') && $this->db->field_exists('threads_media_id', 'endorse')) $this->db->query("ALTER TABLE endorse DROP COLUMN threads_media_id");
        if ($this->db->table_exists('influencer')) {
            if ($this->index_exists('influencer', 'idx_influencer_threads_user_id')) $this->db->query("ALTER TABLE influencer DROP INDEX idx_influencer_threads_user_id");
            foreach (['threads_token_expires_at', 'threads_access_token', 'threads_user_id'] as $field) {
                if ($this->db->field_exists($field, 'influencer')) $this->db->query("ALTER TABLE influencer DROP COLUMN {$field}");
            }
        }
    }

    private function index_exists(string $table, string $index): bool
    {
        return !empty($this->db->query('SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1', [$table, $index])->row_array());
    }
}
