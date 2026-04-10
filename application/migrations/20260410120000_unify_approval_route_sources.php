<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Unify_approval_route_sources extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('approval_route_versions') && !$this->db->field_exists('request_type', 'approval_route_versions')) {
            $this->dbforge->add_column('approval_route_versions', array(
                'request_type' => array(
                    'type' => 'ENUM',
                    'constraint' => array('leave', 'overtime'),
                    'default' => 'leave',
                    'after' => 'description',
                ),
            ));
            $this->db->query("ALTER TABLE approval_route_versions ADD INDEX idx_request_type (request_type)");
        }

        if ($this->db->table_exists('approval_route_versions')) {
            $this->db->query("UPDATE approval_route_versions SET request_type = 'leave' WHERE request_type IS NULL OR request_type = ''");
        }

        if ($this->db->table_exists('modules')) {
            $module = $this->db->get_where('modules', array('name' => 'overtime_approval_routes'))->row_array();
            if ($module && $this->db->table_exists('role_permissions')) {
                $this->db->delete('role_permissions', array('module_id' => $module['id']));
            }
            $this->db->delete('modules', array('name' => 'overtime_approval_routes'));
        }

        if ($this->db->field_exists('route_id', 'overtime_approval_instances')) {
            $index = $this->db->query("SHOW INDEX FROM overtime_approval_instances WHERE Key_name = 'idx_overtime_route_id'")->row_array();
            if ($index) {
                $this->db->query("ALTER TABLE overtime_approval_instances DROP INDEX idx_overtime_route_id");
            }
            $this->dbforge->drop_column('overtime_approval_instances', 'route_id');
        }

        $this->dbforge->drop_table('overtime_approval_route_steps', TRUE);
        $this->dbforge->drop_table('overtime_approval_route_scopes', TRUE);
        $this->dbforge->drop_table('overtime_approval_routes', TRUE);
    }

    public function down()
    {
        if ($this->db->field_exists('request_type', 'approval_route_versions')) {
            $index = $this->db->query("SHOW INDEX FROM approval_route_versions WHERE Key_name = 'idx_request_type'")->row_array();
            if ($index) {
                $this->db->query("ALTER TABLE approval_route_versions DROP INDEX idx_request_type");
            }
            $this->dbforge->drop_column('approval_route_versions', 'request_type');
        }
    }
}
