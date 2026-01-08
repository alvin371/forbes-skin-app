<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_holidays_table extends CI_Migration
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
            'date' => array(
                'type' => 'DATE',
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
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
        $this->dbforge->add_key('date', TRUE);
        $this->dbforge->add_key('is_active');
        $this->dbforge->create_table('holidays', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('holidays', TRUE);
    }
}
