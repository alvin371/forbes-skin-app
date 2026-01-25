<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Allow cancellation of performance submissions
 * Date: 2026-01-20
 *
 * Changes:
 * - Add CANCELLED to performance_submissions.status
 * - Drop unique_submission index to allow resubmissions after cancellation
 */
class Migration_Add_performance_submission_cancellation extends CI_Migration
{
    public function up()
    {
        if ($this->index_exists('performance_submissions', 'unique_submission')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                DROP INDEX unique_submission
            ");
        }

        if ($this->column_exists('performance_submissions', 'status')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                MODIFY COLUMN status ENUM('DRAFT', 'SUBMITTED', 'CANCELLED') NOT NULL DEFAULT 'SUBMITTED'
            ");
        }
    }

    public function down()
    {
        if ($this->column_exists('performance_submissions', 'status')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                MODIFY COLUMN status ENUM('DRAFT', 'SUBMITTED') NOT NULL DEFAULT 'SUBMITTED'
            ");
        }

        if (!$this->index_exists('performance_submissions', 'unique_submission')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                ADD UNIQUE KEY unique_submission (employee_id, template_id, period_year)
            ");
        }
    }

    private function column_exists($table, $column)
    {
        $result = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")->num_rows();
        return $result > 0;
    }

    private function index_exists($table, $index_name)
    {
        $result = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index_name}'")->num_rows();
        return $result > 0;
    }
}
