<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Harden_leave_indexes_and_constraints extends CI_Migration
{
    public function up()
    {
        $this->ensure_no_duplicates('leave_requests', 'request_no');
        $this->ensure_no_duplicates('leave_types', 'code');

        $this->add_index_if_missing(
            'leave_requests',
            'idx_request_no',
            'ALTER TABLE leave_requests ADD UNIQUE INDEX idx_request_no (request_no)'
        );
        $this->add_index_if_missing(
            'leave_requests',
            'idx_user_created_at',
            'ALTER TABLE leave_requests ADD INDEX idx_user_created_at (user_id, created_at)'
        );
        $this->add_index_if_missing(
            'leave_requests',
            'idx_user_type_status_created_at',
            'ALTER TABLE leave_requests ADD INDEX idx_user_type_status_created_at (user_id, leave_type_id, status, created_at)'
        );
        $this->add_index_if_missing(
            'leave_types',
            'idx_code',
            'ALTER TABLE leave_types ADD UNIQUE INDEX idx_code (code)'
        );
        $this->add_index_if_missing(
            'approval_steps',
            'idx_assigned_action_step',
            'ALTER TABLE approval_steps ADD INDEX idx_assigned_action_step (assigned_approver_id, action, step_no)'
        );
        $this->add_index_if_missing(
            'approval_steps',
            'idx_actual_action_at',
            'ALTER TABLE approval_steps ADD INDEX idx_actual_action_at (actual_approver_id, action, action_at)'
        );
    }

    public function down()
    {
        $this->drop_index_if_exists('approval_steps', 'idx_actual_action_at');
        $this->drop_index_if_exists('approval_steps', 'idx_assigned_action_step');
        $this->drop_index_if_exists('leave_types', 'idx_code');
        $this->drop_index_if_exists('leave_requests', 'idx_user_type_status_created_at');
        $this->drop_index_if_exists('leave_requests', 'idx_user_created_at');
        $this->drop_index_if_exists('leave_requests', 'idx_request_no');
    }

    private function add_index_if_missing($table, $indexName, $sql)
    {
        if ($this->index_exists($table, $indexName)) {
            return;
        }

        $this->db->query($sql);
    }

    private function drop_index_if_exists($table, $indexName)
    {
        if (!$this->index_exists($table, $indexName)) {
            return;
        }

        $this->db->query('ALTER TABLE ' . $table . ' DROP INDEX ' . $indexName);
    }

    private function index_exists($table, $indexName)
    {
        $result = $this->db->query(
            'SHOW INDEX FROM ' . $table . ' WHERE Key_name = ' . $this->db->escape($indexName)
        );

        return $result && $result->num_rows() > 0;
    }

    private function ensure_no_duplicates($table, $column)
    {
        $sql = sprintf(
            'SELECT %1$s, COUNT(*) AS duplicate_count FROM %2$s GROUP BY %1$s HAVING COUNT(*) > 1 LIMIT 1',
            $column,
            $table
        );
        $duplicate = $this->db->query($sql)->row_array();

        if (!$duplicate) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Cannot add unique index on %s.%s because duplicate value "%s" exists (%d rows).',
                $table,
                $column,
                (string) $duplicate[$column],
                (int) $duplicate['duplicate_count']
            )
        );
    }
}
