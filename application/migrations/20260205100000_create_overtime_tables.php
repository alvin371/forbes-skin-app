<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Create Overtime Workflow Tables
 *
 * This migration creates the overtime workflow system tables:
 * - overtime_types: Overtime type configuration
 * - overtime_requests: Main overtime request records
 * - overtime_approval_instances: Workflow instance per request
 * - overtime_approval_steps: Individual approval step records
 * - overtime_ledger: Approved overtime tracking
 *
 * Also modifies:
 * - approval_route_scopes: Add overtime_type and overtime_duration to scope_type ENUM
 */
class Migration_Create_overtime_tables extends CI_Migration
{
    public function up()
    {
        // =====================================================
        // 1. OVERTIME_TYPES - Overtime type configuration
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'code' => array(
                'type' => 'VARCHAR',
                'constraint' => 20,
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
            ),
            'description' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'requires_attachment' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ),
            'is_active' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
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
        $this->dbforge->create_table('overtime_types', TRUE);

        // Add unique index on code
        $this->db->query('ALTER TABLE overtime_types ADD UNIQUE INDEX idx_code (code)');

        // =====================================================
        // 2. OVERTIME_REQUESTS - Main overtime request records
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'request_no' => array(
                'type' => 'VARCHAR',
                'constraint' => 30,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'overtime_type_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'office_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'overtime_date' => array(
                'type' => 'DATE',
            ),
            'start_time' => array(
                'type' => 'TIME',
            ),
            'end_time' => array(
                'type' => 'TIME',
            ),
            'duration_hours' => array(
                'type' => 'DECIMAL',
                'constraint' => '4,2',
            ),
            'reason' => array(
                'type' => 'TEXT',
            ),
            'attachment_path' => array(
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => TRUE,
            ),
            'status' => array(
                'type' => 'ENUM',
                'constraint' => array('SUBMITTED', 'IN_REVIEW', 'APPROVED', 'REJECTED', 'CANCELLED'),
                'default' => 'SUBMITTED',
            ),
            'submitted_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'approval_instance_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'current_step' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ),
            'final_approved_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'final_approved_at' => array(
                'type' => 'DATETIME',
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
            'created_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'updated_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('overtime_type_id');
        $this->dbforge->add_key('status');
        $this->dbforge->add_key('overtime_date');
        $this->dbforge->create_table('overtime_requests', TRUE);

        // Add unique index on request_no
        $this->db->query('ALTER TABLE overtime_requests ADD UNIQUE INDEX idx_request_no (request_no)');

        // =====================================================
        // 3. OVERTIME_APPROVAL_INSTANCES - Workflow instance per request
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'overtime_request_id' => array(
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
                'constraint' => array('IN_PROGRESS', 'COMPLETED', 'REJECTED', 'CANCELLED'),
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
        $this->dbforge->add_key('overtime_request_id');
        $this->dbforge->add_key('route_version_id');
        $this->dbforge->create_table('overtime_approval_instances', TRUE);

        // Add unique index on overtime_request_id
        $this->db->query('ALTER TABLE overtime_approval_instances ADD UNIQUE INDEX idx_overtime_request_unique (overtime_request_id)');

        // =====================================================
        // 4. OVERTIME_APPROVAL_STEPS - Individual approval step records
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
            'overtime_request_id' => array(
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
                'constraint' => array('PENDING', 'APPROVED', 'REJECTED'),
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
        $this->dbforge->add_key('overtime_request_id');
        $this->dbforge->add_key('assigned_approver_id');
        $this->dbforge->create_table('overtime_approval_steps', TRUE);

        // =====================================================
        // 5. OVERTIME_LEDGER - Approved overtime tracking
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'overtime_request_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'overtime_type_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'overtime_date' => array(
                'type' => 'DATE',
            ),
            'start_time' => array(
                'type' => 'TIME',
            ),
            'end_time' => array(
                'type' => 'TIME',
            ),
            'hours_worked' => array(
                'type' => 'DECIMAL',
                'constraint' => '4,2',
            ),
            'year' => array(
                'type' => 'INT',
                'constraint' => 4,
            ),
            'month' => array(
                'type' => 'INT',
                'constraint' => 2,
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
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('overtime_request_id');
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('year');
        $this->dbforge->add_key('month');
        $this->dbforge->create_table('overtime_ledger', TRUE);

        // Add unique index on overtime_request_id
        $this->db->query('ALTER TABLE overtime_ledger ADD UNIQUE INDEX idx_overtime_request_unique (overtime_request_id)');

        // Add composite index for monthly queries
        $this->db->query('ALTER TABLE overtime_ledger ADD INDEX idx_user_year_month (user_id, year, month)');

        // =====================================================
        // 6. MODIFY APPROVAL_ROUTE_SCOPES - Add overtime scope types
        // =====================================================
        // Check if approval_route_scopes table exists
        $tables = $this->db->query("SHOW TABLES LIKE 'approval_route_scopes'")->result_array();
        if (!empty($tables)) {
            // Modify the scope_type ENUM to include overtime types
            $this->db->query("ALTER TABLE approval_route_scopes MODIFY COLUMN scope_type ENUM('company', 'office', 'department', 'role', 'user', 'leave_type', 'leave_duration', 'overtime_type', 'overtime_duration')");
        }
    }

    public function down()
    {
        // Drop tables in reverse order
        $this->dbforge->drop_table('overtime_ledger', TRUE);
        $this->dbforge->drop_table('overtime_approval_steps', TRUE);
        $this->dbforge->drop_table('overtime_approval_instances', TRUE);
        $this->dbforge->drop_table('overtime_requests', TRUE);
        $this->dbforge->drop_table('overtime_types', TRUE);

        // Revert approval_route_scopes ENUM (remove overtime types)
        $tables = $this->db->query("SHOW TABLES LIKE 'approval_route_scopes'")->result_array();
        if (!empty($tables)) {
            $this->db->query("ALTER TABLE approval_route_scopes MODIFY COLUMN scope_type ENUM('company', 'office', 'department', 'role', 'user', 'leave_type', 'leave_duration')");
        }
    }
}
