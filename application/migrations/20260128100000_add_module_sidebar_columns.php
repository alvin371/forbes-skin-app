<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration: Add module sidebar columns
 *
 * Adds columns required for dynamic sidebar generation:
 * - category: Module category for grouping in Roles permission matrix
 * - url: Custom URL for sidebar navigation
 * - available_permissions: JSON array of permission types this module supports
 * - show_in_sidebar: Whether to show this module in the sidebar
 */
class Migration_Add_module_sidebar_columns extends CI_Migration
{
    public function up()
    {
        // Add category column
        if (!$this->db->field_exists('category', 'modules')) {
            $this->dbforge->add_column('modules', [
                'category' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'System Management',
                    'after' => 'sort_order'
                ]
            ]);
        }

        // Add url column
        if (!$this->db->field_exists('url', 'modules')) {
            $this->dbforge->add_column('modules', [
                'url' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'category'
                ]
            ]);
        }

        // Add available_permissions column (JSON)
        if (!$this->db->field_exists('available_permissions', 'modules')) {
            $this->dbforge->add_column('modules', [
                'available_permissions' => [
                    'type' => 'JSON',
                    'null' => true,
                    'after' => 'url'
                ]
            ]);
        }

        // Add show_in_sidebar column
        if (!$this->db->field_exists('show_in_sidebar', 'modules')) {
            $this->dbforge->add_column('modules', [
                'show_in_sidebar' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'after' => 'available_permissions'
                ]
            ]);
        }

        // Add index for sidebar queries if it doesn't exist
        $index_exists = $this->db
            ->query("SHOW INDEX FROM modules WHERE Key_name = 'idx_modules_sidebar'")
            ->num_rows() > 0;
        if (!$index_exists) {
            $this->db->query("CREATE INDEX idx_modules_sidebar ON modules (show_in_sidebar, is_active, category, sort_order)");
        }
    }

    public function down()
    {
        // Remove index if it exists
        $index_exists = $this->db
            ->query("SHOW INDEX FROM modules WHERE Key_name = 'idx_modules_sidebar'")
            ->num_rows() > 0;
        if ($index_exists) {
            $this->db->query("DROP INDEX idx_modules_sidebar ON modules");
        }

        // Remove columns
        if ($this->db->field_exists('show_in_sidebar', 'modules')) {
            $this->dbforge->drop_column('modules', 'show_in_sidebar');
        }

        if ($this->db->field_exists('available_permissions', 'modules')) {
            $this->dbforge->drop_column('modules', 'available_permissions');
        }

        if ($this->db->field_exists('url', 'modules')) {
            $this->dbforge->drop_column('modules', 'url');
        }

        if ($this->db->field_exists('category', 'modules')) {
            $this->dbforge->drop_column('modules', 'category');
        }
    }
}
