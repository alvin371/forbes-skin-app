<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_offices_and_attendance_logs extends CI_Migration
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
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'lat' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,7',
            ),
            'lng' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,7',
            ),
            'radius_m' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 150,
            ),
            'min_accuracy_m' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 50,
            ),
            'allowed_ip_cidrs' => array(
                'type' => 'TEXT',
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
        $this->dbforge->create_table('offices', TRUE);

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
            'office_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'type' => array(
                'type' => 'ENUM',
                'constraint' => array('IN', 'OUT'),
            ),
            'lat' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,7',
            ),
            'lng' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,7',
            ),
            'accuracy' => array(
                'type' => 'FLOAT',
            ),
            'distance_m' => array(
                'type' => 'FLOAT',
            ),
            'method' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
            ),
            'ip_address' => array(
                'type' => 'VARCHAR',
                'constraint' => 45,
            ),
            'user_agent' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('office_id');
        $this->dbforge->create_table('attendance_logs', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('attendance_logs', TRUE);
        $this->dbforge->drop_table('offices', TRUE);
    }
}
