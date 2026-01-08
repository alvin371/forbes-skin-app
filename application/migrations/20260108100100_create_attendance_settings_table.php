<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_attendance_settings_table extends CI_Migration
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
            'weekend_type' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'SATURDAY_SUNDAY',
            ),
            'allowed_role_ids' => array(
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
        $this->dbforge->create_table('attendance_settings', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('attendance_settings', TRUE);
    }
}
