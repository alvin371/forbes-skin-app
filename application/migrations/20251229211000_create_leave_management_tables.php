<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_leave_management_tables extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'code' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'is_paid' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ),
            'requires_attachment' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ),
            'max_days_per_request' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
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
        $this->dbforge->add_key('code', TRUE);
        $this->dbforge->create_table('leave_types', TRUE);

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
            'approver_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
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
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('approver_id');
        $this->dbforge->add_key('is_active');
        $this->dbforge->create_table('approval_routes', TRUE);

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
            'days_count' => array(
                'type' => 'INT',
                'constraint' => 11,
            ),
            'reason' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'attachment_path' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
            'status' => array(
                'type' => 'ENUM',
                'constraint' => array('SUBMITTED', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'CANCELLED'),
                'default' => 'PENDING_APPROVAL',
            ),
            'current_step' => array(
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
        $this->dbforge->add_key('request_no', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('leave_type_id');
        $this->dbforge->add_key('status');
        $this->dbforge->create_table('leave_requests', TRUE);

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
            'step_no' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ),
            'approver_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
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
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('leave_request_id');
        $this->dbforge->add_key('approver_id');
        $this->dbforge->add_key('action');
        $this->dbforge->create_table('leave_approvals', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('leave_approvals', TRUE);
        $this->dbforge->drop_table('leave_requests', TRUE);
        $this->dbforge->drop_table('approval_routes', TRUE);
        $this->dbforge->drop_table('leave_types', TRUE);
    }
}
