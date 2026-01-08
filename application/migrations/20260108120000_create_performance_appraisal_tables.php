<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Create Performance Appraisal Tables
 * Date: 2026-01-08
 */
class Migration_Create_performance_appraisal_tables extends CI_Migration
{
    public function up()
    {
        // Table: performance_templates
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'period_year' => array(
                'type' => 'INT',
                'constraint' => 4,
            ),
            'department' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => TRUE,
            ),
            'is_active' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
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
        $this->dbforge->add_key('period_year');
        $this->dbforge->add_key('department');
        $this->dbforge->add_key('is_active');
        $this->dbforge->create_table('performance_templates', TRUE);

        // Table: performance_template_items
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'template_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'order_no' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ),
            'objective' => array(
                'type' => 'TEXT',
            ),
            'kpi' => array(
                'type' => 'TEXT',
            ),
            'target_value' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ),
            'unit' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
            ),
            'weight' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2',
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
        $this->dbforge->add_key('template_id');
        $this->dbforge->create_table('performance_template_items', TRUE);

        // Table: performance_submissions
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'template_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'employee_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'employee_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
            'employee_nik' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => TRUE,
            ),
            'employee_department' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => TRUE,
            ),
            'employee_position' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => TRUE,
            ),
            'period_year' => array(
                'type' => 'INT',
                'constraint' => 4,
            ),
            'total_score' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'default' => 0,
            ),
            'status' => array(
                'type' => 'ENUM',
                'constraint' => array('DRAFT', 'SUBMITTED'),
                'default' => 'SUBMITTED',
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
        $this->dbforge->add_key('template_id');
        $this->dbforge->add_key('employee_id');
        $this->dbforge->add_key('period_year');
        $this->db->query('ALTER TABLE performance_submissions ADD UNIQUE KEY unique_submission (employee_id, template_id, period_year)');
        $this->dbforge->create_table('performance_submissions', TRUE);

        // Table: performance_submission_items
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'submission_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'template_item_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'actual_value' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ),
            'score_ratio' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,4',
            ),
            'final_score' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,4',
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
        $this->dbforge->add_key('submission_id');
        $this->dbforge->add_key('template_item_id');
        $this->dbforge->create_table('performance_submission_items', TRUE);

        // Add foreign keys
        $this->db->query('
            ALTER TABLE performance_template_items
            ADD CONSTRAINT fk_template_items_template
            FOREIGN KEY (template_id) REFERENCES performance_templates(id)
            ON DELETE CASCADE
        ');

        $this->db->query('
            ALTER TABLE performance_submissions
            ADD CONSTRAINT fk_submissions_template
            FOREIGN KEY (template_id) REFERENCES performance_templates(id)
            ON DELETE RESTRICT
        ');

        $this->db->query('
            ALTER TABLE performance_submission_items
            ADD CONSTRAINT fk_submission_items_submission
            FOREIGN KEY (submission_id) REFERENCES performance_submissions(id)
            ON DELETE CASCADE
        ');

        $this->db->query('
            ALTER TABLE performance_submission_items
            ADD CONSTRAINT fk_submission_items_template_item
            FOREIGN KEY (template_item_id) REFERENCES performance_template_items(id)
            ON DELETE RESTRICT
        ');
    }

    public function down()
    {
        // Drop foreign keys first
        $this->db->query('ALTER TABLE performance_submission_items DROP FOREIGN KEY fk_submission_items_template_item');
        $this->db->query('ALTER TABLE performance_submission_items DROP FOREIGN KEY fk_submission_items_submission');
        $this->db->query('ALTER TABLE performance_submissions DROP FOREIGN KEY fk_submissions_template');
        $this->db->query('ALTER TABLE performance_template_items DROP FOREIGN KEY fk_template_items_template');

        // Drop tables
        $this->dbforge->drop_table('performance_submission_items', TRUE);
        $this->dbforge->drop_table('performance_submissions', TRUE);
        $this->dbforge->drop_table('performance_template_items', TRUE);
        $this->dbforge->drop_table('performance_templates', TRUE);
    }
}
