<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Convert Performance Appraisal from Department to Role-Based
 * Date: 2026-01-15
 *
 * This migration adds role-based columns to performance tables:
 * - performance_templates.role_id (FK to roles)
 * - performance_submissions.employee_role_id
 * - performance_submissions.employee_role_name
 */
class Migration_Convert_performance_to_role_based extends CI_Migration
{
    public function up()
    {
        // Check if role_id column exists in performance_templates
        if (!$this->column_exists('performance_templates', 'role_id')) {
            $this->db->query("
                ALTER TABLE performance_templates
                ADD COLUMN role_id INT(11) NULL AFTER department
            ");
        } else {
            $column_type = $this->column_type('performance_templates', 'role_id');
            if ($column_type !== null && stripos($column_type, 'unsigned') !== false) {
                $this->db->query("
                    ALTER TABLE performance_templates
                    MODIFY COLUMN role_id INT(11) NULL
                ");
            }
        }

        // Add index for role_id if not exists
        if (!$this->index_exists('performance_templates', 'idx_role_id')) {
            $this->db->query("
                ALTER TABLE performance_templates
                ADD INDEX idx_role_id (role_id)
            ");
        }

        // Add foreign key constraint if not exists
        if (!$this->foreign_key_exists('performance_templates', 'fk_perf_templates_role')) {
            $this->db->query("
                ALTER TABLE performance_templates
                ADD CONSTRAINT fk_perf_templates_role
                FOREIGN KEY (role_id) REFERENCES roles(id)
                ON DELETE SET NULL
            ");
        }

        // Add role snapshot columns to performance_submissions
        if (!$this->column_exists('performance_submissions', 'employee_role_id')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                ADD COLUMN employee_role_id INT(11) UNSIGNED NULL AFTER employee_department
            ");
        }

        if (!$this->column_exists('performance_submissions', 'employee_role_name')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                ADD COLUMN employee_role_name VARCHAR(255) NULL AFTER employee_role_id
            ");
        }

        // Add index for filtering by role if not exists
        if (!$this->index_exists('performance_submissions', 'idx_employee_role_id')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                ADD INDEX idx_employee_role_id (employee_role_id)
            ");
        }
    }

    public function down()
    {
        // Remove foreign key from performance_templates
        if ($this->foreign_key_exists('performance_templates', 'fk_perf_templates_role')) {
            $this->db->query("
                ALTER TABLE performance_templates
                DROP FOREIGN KEY fk_perf_templates_role
            ");
        }

        // Remove index from performance_templates
        if ($this->index_exists('performance_templates', 'idx_role_id')) {
            $this->db->query("
                ALTER TABLE performance_templates
                DROP INDEX idx_role_id
            ");
        }

        // Remove column from performance_templates
        if ($this->column_exists('performance_templates', 'role_id')) {
            $this->db->query("
                ALTER TABLE performance_templates
                DROP COLUMN role_id
            ");
        }

        // Remove index from performance_submissions
        if ($this->index_exists('performance_submissions', 'idx_employee_role_id')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                DROP INDEX idx_employee_role_id
            ");
        }

        // Remove columns from performance_submissions
        if ($this->column_exists('performance_submissions', 'employee_role_name')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                DROP COLUMN employee_role_name
            ");
        }

        if ($this->column_exists('performance_submissions', 'employee_role_id')) {
            $this->db->query("
                ALTER TABLE performance_submissions
                DROP COLUMN employee_role_id
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

    private function foreign_key_exists($table, $fk_name)
    {
        $db_name = $this->db->database;
        $result = $this->db->query("
            SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = '{$db_name}'
            AND TABLE_NAME = '{$table}'
            AND CONSTRAINT_NAME = '{$fk_name}'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ")->row();
        return $result && $result->cnt > 0;
    }

    private function column_type($table, $column)
    {
        $db_name = $this->db->database;
        $row = $this->db->query("
            SELECT COLUMN_TYPE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = '{$db_name}'
            AND TABLE_NAME = '{$table}'
            AND COLUMN_NAME = '{$column}'
        ")->row();

        if (!$row || !isset($row->COLUMN_TYPE)) {
            return null;
        }

        return $row->COLUMN_TYPE;
    }
}
