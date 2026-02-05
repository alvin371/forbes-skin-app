<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Create Overtime Approval Routes Tables
 *
 * This migration creates overtime-specific approval route tables:
 * - overtime_approval_routes
 * - overtime_approval_route_scopes
 * - overtime_approval_route_steps
 *
 * Also modifies:
 * - overtime_approval_instances: add route_id for the selected overtime route
 */
class Migration_Create_overtime_approval_routes extends CI_Migration
{
    public function up()
    {
        // =====================================================
        // 1. OVERTIME_APPROVAL_ROUTES
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
        $this->dbforge->add_key('is_active');
        $this->dbforge->create_table('overtime_approval_routes', TRUE);

        // Add unique index on route_code
        $this->db->query('ALTER TABLE overtime_approval_routes ADD UNIQUE INDEX idx_overtime_route_code (route_code)');

        // =====================================================
        // 2. OVERTIME_APPROVAL_ROUTE_SCOPES
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'route_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'scope_type' => array(
                'type' => 'ENUM',
                'constraint' => array('user', 'role'),
            ),
            'scope_value' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('route_id');
        $this->dbforge->create_table('overtime_approval_route_scopes', TRUE);

        // =====================================================
        // 3. OVERTIME_APPROVAL_ROUTE_STEPS
        // =====================================================
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'route_id' => array(
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
        $this->dbforge->add_key('route_id');
        $this->dbforge->create_table('overtime_approval_route_steps', TRUE);

        // =====================================================
        // 4. MODIFY OVERTIME_APPROVAL_INSTANCES
        // =====================================================
        if ($this->db->table_exists('overtime_approval_instances')) {
            if (!$this->db->field_exists('route_id', 'overtime_approval_instances')) {
                $this->dbforge->add_column('overtime_approval_instances', array(
                    'route_id' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'null' => TRUE,
                        'after' => 'overtime_request_id',
                    ),
                ));
                $this->db->query('ALTER TABLE overtime_approval_instances ADD INDEX idx_overtime_route_id (route_id)');
            }
        }
    }
}
