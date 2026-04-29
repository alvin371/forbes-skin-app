<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_overview_kol_generated_date_indexes extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('endorse_logs')) {
            $this->add_generated_date_column_if_missing(
                'endorse_logs',
                'log_date',
                "CASE
                    WHEN `date` IS NULL OR TRIM(`date`) = '' THEN NULL
                    WHEN CHAR_LENGTH(`date`) >= 10 THEN STR_TO_DATE(LEFT(`date`, 10), '%Y-%m-%d')
                    ELSE NULL
                END"
            );

            $this->add_index_if_missing('endorse_logs', 'idx_endorse_logs_log_date_endorse', 'ALTER TABLE endorse_logs ADD INDEX idx_endorse_logs_log_date_endorse (log_date, id_endorse)');
            $this->add_index_if_missing('endorse_logs', 'idx_endorse_logs_endorse_log_date', 'ALTER TABLE endorse_logs ADD INDEX idx_endorse_logs_endorse_log_date (id_endorse, log_date)');
        }

        if ($this->db->table_exists('endorse')) {
            $this->add_generated_date_column_if_missing(
                'endorse',
                'created_at_date',
                "CASE
                    WHEN `created_at` IS NULL OR TRIM(`created_at`) = '' THEN NULL
                    WHEN CHAR_LENGTH(`created_at`) >= 10 THEN STR_TO_DATE(LEFT(`created_at`, 10), '%Y-%m-%d')
                    ELSE NULL
                END"
            );
            $this->add_generated_date_column_if_missing(
                'endorse',
                'posting_at_date',
                "CASE
                    WHEN `posting_at` IS NULL OR TRIM(`posting_at`) = '' THEN NULL
                    WHEN CHAR_LENGTH(`posting_at`) >= 10 THEN STR_TO_DATE(LEFT(`posting_at`, 10), '%Y-%m-%d')
                    ELSE NULL
                END"
            );
            $this->add_generated_date_column_if_missing(
                'endorse',
                'rencana_at_date',
                "CASE
                    WHEN `rencana_at` IS NULL OR TRIM(`rencana_at`) = '' THEN NULL
                    WHEN CHAR_LENGTH(`rencana_at`) >= 10 THEN STR_TO_DATE(LEFT(`rencana_at`, 10), '%Y-%m-%d')
                    ELSE NULL
                END"
            );
            $this->add_generated_date_column_if_missing(
                'endorse',
                'tgl_tf_date',
                "CASE
                    WHEN `tgl_tf` IS NULL OR TRIM(`tgl_tf`) = '' THEN NULL
                    WHEN CHAR_LENGTH(`tgl_tf`) >= 10 THEN STR_TO_DATE(LEFT(`tgl_tf`, 10), '%Y-%m-%d')
                    ELSE NULL
                END"
            );

            $this->add_index_if_missing('endorse', 'idx_endorse_created_at_date', 'ALTER TABLE endorse ADD INDEX idx_endorse_created_at_date (created_at_date)');
            $this->add_index_if_missing('endorse', 'idx_endorse_posting_at_date', 'ALTER TABLE endorse ADD INDEX idx_endorse_posting_at_date (posting_at_date)');
            $this->add_index_if_missing('endorse', 'idx_endorse_rencana_at_date', 'ALTER TABLE endorse ADD INDEX idx_endorse_rencana_at_date (rencana_at_date)');
            $this->add_index_if_missing('endorse', 'idx_endorse_tgl_tf_date', 'ALTER TABLE endorse ADD INDEX idx_endorse_tgl_tf_date (tgl_tf_date)');
            $this->add_index_if_missing('endorse', 'idx_endorse_product', 'ALTER TABLE endorse ADD INDEX idx_endorse_product (product)');
            $this->add_index_if_missing('endorse', 'idx_endorse_pic', 'ALTER TABLE endorse ADD INDEX idx_endorse_pic (pic)');
        }
    }

    public function down()
    {
        if ($this->db->table_exists('endorse_logs')) {
            $this->drop_index_if_exists('endorse_logs', 'idx_endorse_logs_log_date_endorse');
            $this->drop_index_if_exists('endorse_logs', 'idx_endorse_logs_endorse_log_date');

            if ($this->db->field_exists('log_date', 'endorse_logs')) {
                $this->db->query('ALTER TABLE endorse_logs DROP COLUMN log_date');
            }
        }

        if ($this->db->table_exists('endorse')) {
            $this->drop_index_if_exists('endorse', 'idx_endorse_created_at_date');
            $this->drop_index_if_exists('endorse', 'idx_endorse_posting_at_date');
            $this->drop_index_if_exists('endorse', 'idx_endorse_rencana_at_date');
            $this->drop_index_if_exists('endorse', 'idx_endorse_tgl_tf_date');
            $this->drop_index_if_exists('endorse', 'idx_endorse_product');
            $this->drop_index_if_exists('endorse', 'idx_endorse_pic');

            $generated_columns = array('created_at_date', 'posting_at_date', 'rencana_at_date', 'tgl_tf_date');
            foreach ($generated_columns as $column) {
                if ($this->db->field_exists($column, 'endorse')) {
                    $this->db->query("ALTER TABLE endorse DROP COLUMN {$column}");
                }
            }
        }
    }

    private function add_generated_date_column_if_missing($table, $column, $expression)
    {
        if (!$this->db->field_exists($column, $table)) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN {$column} DATE GENERATED ALWAYS AS ({$expression}) STORED");
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
