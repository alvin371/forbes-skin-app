<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Create Approval Routing Tables
 *
 * This migration creates the dynamic approval routing system tables:
 * - approval_route_versions: Versioned route definitions
 * - approval_route_scopes: Route matching conditions
 * - approval_route_steps: Approval chain steps
 * - approval_instances: Route snapshot per leave request
 * - approval_steps: Individual approval step records
 * - leave_quota_logs: Quota audit trail
 * - leave_ledger: Final approved leave usage
 *
 * Also modifies existing tables:
 * - user: Add manager_id column
 * - leave_requests: Add new status values and fields
 * - leave_quotas: Add year tracking
 */
class Migration_Create_approval_routing_tables extends CI_Migration
{
    public function up()
    {
        // =====================================================
        // 1. APPROVAL_ROUTE_VERSIONS - Versioned route definitions
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'route_code' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'description' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'version' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ),
            'effective_from' => array(
                'type' => 'DATE',
            ),
            'effective_to' => array(
                'type' => 'DATE',
                'null' => TRUE,
            ),
            'is_active' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ),
            'created_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('route_code');
        $this->dbforge->add_key('effective_from');
        $this->dbforge->add_key('is_active');
        $this->dbforge->create_table('approval_route_versions', TRUE);

        // =====================================================
        // 2. APPROVAL_ROUTE_SCOPES - Route matching conditions
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'route_version_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'scope_type' => array(
                'type' => 'ENUM',
                'constraint' => array('company', 'office', 'department', 'role', 'user', 'leave_type', 'leave_duration'),
            ),
            'scope_value' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
            'operator' => array(
                'type' => 'ENUM',
                'constraint' => array('eq', 'neq', 'in', 'not_in', 'lte', 'gte', 'lt', 'gt'),
                'default' => 'eq',
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('route_version_id');
        $this->dbforge->create_table('approval_route_scopes', TRUE);

        // =====================================================
        // 3. APPROVAL_ROUTE_STEPS - Approval chain steps
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'route_version_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'step_no' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ),
            'approver_type' => array(
                'type' => 'ENUM',
                'constraint' => array('user', 'role', 'position', 'dynamic'),
            ),
            'approver_value' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'step_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => TRUE,
            ),
            'is_optional' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('route_version_id');
        $this->dbforge->create_table('approval_route_steps', TRUE);

        // =====================================================
        // 4. APPROVAL_INSTANCES - Route snapshot per leave request
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'leave_request_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'route_version_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'route_snapshot' => array(
                'type' => 'JSON',
            ),
            'total_steps' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ),
            'current_step' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ),
            'status' => array(
                'type' => 'ENUM',
                'constraint' => array('IN_PROGRESS', 'COMPLETED', 'REJECTED', 'CANCELLED', 'NEEDS_ROUTE'),
                'default' => 'IN_PROGRESS',
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('leave_request_id');
        $this->dbforge->add_key('route_version_id');
        $this->dbforge->create_table('approval_instances', TRUE);

        // Add unique index on leave_request_id
        $this->db->query('ALTER TABLE approval_instances ADD UNIQUE INDEX idx_leave_request_unique (leave_request_id)');

        // =====================================================
        // 5. APPROVAL_STEPS - Individual approval step records
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'approval_instance_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'leave_request_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'step_no' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ),
            'step_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => TRUE,
            ),
            'assigned_approver_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'actual_approver_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'action' => array(
                'type' => 'ENUM',
                'constraint' => array('PENDING', 'APPROVED', 'REJECTED', 'SKIPPED'),
                'default' => 'PENDING',
            ),
            'action_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'version' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('approval_instance_id');
        $this->dbforge->add_key('leave_request_id');
        $this->dbforge->add_key('assigned_approver_id');
        $this->dbforge->create_table('approval_steps', TRUE);

        // =====================================================
        // 6. LEAVE_QUOTA_LOGS - Quota audit trail
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'leave_type_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'leave_request_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'action_type' => array(
                'type' => 'ENUM',
                'constraint' => array('DEDUCT', 'RESTORE', 'ADJUST', 'RESET', 'INITIALIZE'),
            ),
            'old_total_days' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2',
            ),
            'new_total_days' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2',
            ),
            'old_remaining_days' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2',
            ),
            'new_remaining_days' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2',
            ),
            'change_amount' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2',
            ),
            'reason' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'performed_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('leave_type_id');
        $this->dbforge->add_key('leave_request_id');
        $this->dbforge->create_table('leave_quota_logs', TRUE);

        // =====================================================
        // 7. LEAVE_LEDGER - Final approved leave usage
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'leave_request_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'leave_type_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'start_date' => array(
                'type' => 'DATE',
            ),
            'end_date' => array(
                'type' => 'DATE',
            ),
            'days_used' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2',
            ),
            'is_paid' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ),
            'year' => array(
                'type' => 'INT',
                'constraint' => 4,
            ),
            'approved_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'approved_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('leave_request_id');
        $this->dbforge->add_key('user_id');
        $this->dbforge->create_table('leave_ledger', TRUE);

        // Add unique index on leave_request_id
        $this->db->query('ALTER TABLE leave_ledger ADD UNIQUE INDEX idx_leave_request_unique (leave_request_id)');

        // =====================================================
        // 8. MODIFY USER TABLE - Add manager_id
        // =====================================================
        // Check if manager_id column exists first
        $fields = $this->db->field_data('user');
        $has_manager_id = false;
        foreach ($fields as $field) {
            if ($field->name == 'manager_id') {
                $has_manager_id = true;
                break;
            }
        }

        if (!$has_manager_id) {
            $this->dbforge->add_column('user', array(
                'manager_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => TRUE,
                    'after' => 'role'
                ),
            ));
            $this->db->query('ALTER TABLE user ADD INDEX idx_manager (manager_id)');
        }

        // =====================================================
        // 9. MODIFY LEAVE_REQUESTS TABLE - Add new status and fields
        // =====================================================
        // Modify status enum to include new values
        $this->db->query("ALTER TABLE leave_requests MODIFY COLUMN status ENUM('DRAFT','SUBMITTED','PENDING_APPROVAL','IN_REVIEW','APPROVED','REJECTED','CANCELLED','NEEDS_ROUTE') DEFAULT 'DRAFT'");

        // Add new columns if they don't exist
        $fields = $this->db->field_data('leave_requests');
        $existing_fields = array();
        foreach ($fields as $field) {
            $existing_fields[] = $field->name;
        }

        if (!in_array('submitted_at', $existing_fields)) {
            $this->dbforge->add_column('leave_requests', array(
                'submitted_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                    'after' => 'status'
                ),
            ));
        }

        if (!in_array('approval_instance_id', $existing_fields)) {
            $this->dbforge->add_column('leave_requests', array(
                'approval_instance_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => TRUE,
                    'after' => 'submitted_at'
                ),
            ));
        }

        if (!in_array('final_approved_by', $existing_fields)) {
            $this->dbforge->add_column('leave_requests', array(
                'final_approved_by' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => TRUE,
                    'after' => 'approval_instance_id'
                ),
            ));
        }

        if (!in_array('final_approved_at', $existing_fields)) {
            $this->dbforge->add_column('leave_requests', array(
                'final_approved_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                    'after' => 'final_approved_by'
                ),
            ));
        }

        // =====================================================
        // 10. MODIFY LEAVE_QUOTAS TABLE - Add year tracking
        // =====================================================
        $fields = $this->db->field_data('leave_quotas');
        $existing_fields = array();
        foreach ($fields as $field) {
            $existing_fields[] = $field->name;
        }

        if (!in_array('year', $existing_fields)) {
            $current_year = date('Y');
            $this->dbforge->add_column('leave_quotas', array(
                'year' => array(
                    'type' => 'INT',
                    'constraint' => 4,
                    'default' => $current_year,
                    'after' => 'leave_type_id'
                ),
            ));
        }

        if (!in_array('carried_over_days', $existing_fields)) {
            $this->dbforge->add_column('leave_quotas', array(
                'carried_over_days' => array(
                    'type' => 'DECIMAL',
                    'constraint' => '5,2',
                    'default' => 0,
                    'after' => 'remaining_days'
                ),
            ));
        }

        // Add unique index on user_id, leave_type_id, year if not exists
        // First check if index exists
        $result = $this->db->query("SHOW INDEX FROM leave_quotas WHERE Key_name = 'idx_user_type_year'");
        if ($result->num_rows() == 0) {
            // Drop old index if exists
            $result = $this->db->query("SHOW INDEX FROM leave_quotas WHERE Key_name = 'idx_user_type'");
            if ($result->num_rows() > 0) {
                $this->db->query('ALTER TABLE leave_quotas DROP INDEX idx_user_type');
            }
            // Try to add the unique index, but catch duplicates
            $this->db->query('ALTER TABLE leave_quotas ADD UNIQUE INDEX idx_user_type_year (user_id, leave_type_id, year)');
        }
    }

    public function down()
    {
        // Drop new tables in reverse order
        $this->dbforge->drop_table('leave_ledger', TRUE);
        $this->dbforge->drop_table('leave_quota_logs', TRUE);
        $this->dbforge->drop_table('approval_steps', TRUE);
        $this->dbforge->drop_table('approval_instances', TRUE);
        $this->dbforge->drop_table('approval_route_steps', TRUE);
        $this->dbforge->drop_table('approval_route_scopes', TRUE);
        $this->dbforge->drop_table('approval_route_versions', TRUE);

        // Remove added columns from user table
        $fields = $this->db->field_data('user');
        foreach ($fields as $field) {
            if ($field->name == 'manager_id') {
                $this->dbforge->drop_column('user', 'manager_id');
                break;
            }
        }

        // Remove added columns from leave_requests
        $fields = $this->db->field_data('leave_requests');
        $columns_to_drop = array('submitted_at', 'approval_instance_id', 'final_approved_by', 'final_approved_at');
        foreach ($fields as $field) {
            if (in_array($field->name, $columns_to_drop)) {
                $this->dbforge->drop_column('leave_requests', $field->name);
            }
        }

        // Revert status enum (remove new values)
        $this->db->query("ALTER TABLE leave_requests MODIFY COLUMN status ENUM('SUBMITTED','PENDING_APPROVAL','APPROVED','REJECTED','CANCELLED') DEFAULT 'PENDING_APPROVAL'");

        // Remove added columns from leave_quotas
        $fields = $this->db->field_data('leave_quotas');
        $columns_to_drop = array('year', 'carried_over_days');
        foreach ($fields as $field) {
            if (in_array($field->name, $columns_to_drop)) {
                $this->dbforge->drop_column('leave_quotas', $field->name);
            }
        }
    }
}
