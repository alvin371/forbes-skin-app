<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MigrateUserModulePermissions extends CI_Controller
{
    private $allowed_ips = array('127.0.0.1', '::1');

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function from_roles()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        if (!$this->db->table_exists('user_module_permissions')) {
            return $this->respond_error('user_module_permissions table not found.');
        }

        $has_override = $this->db->field_exists('has_override', 'user_module_permissions');
        $has_created_at = $this->db->field_exists('created_at', 'user_module_permissions');
        $has_updated_at = $this->db->field_exists('updated_at', 'user_module_permissions');

        $columns = array(
            'user_id',
            'module_id',
            'module_name',
            'module_display_name',
            'controller',
            'parent_id',
            'can_view',
            'can_create',
            'can_edit',
            'can_delete',
            'can_approve'
        );

        if ($has_override) {
            $columns[] = 'has_override';
        }
        if ($has_created_at) {
            $columns[] = 'created_at';
        }
        if ($has_updated_at) {
            $columns[] = 'updated_at';
        }

        $this->db->trans_start();

        if ($has_override) {
            $this->db->where('has_override', 0);
            $this->db->or_where('has_override IS NULL', null, false);
            $this->db->delete('user_module_permissions');
        } else {
            $this->db->truncate('user_module_permissions');
        }

        $query = $this->db->query("
            SELECT
                ur.user_id,
                m.id AS module_id,
                m.name AS module_name,
                m.display_name AS module_display_name,
                m.controller,
                m.parent_id,
                MAX(rp.can_view) AS can_view,
                MAX(rp.can_create) AS can_create,
                MAX(rp.can_edit) AS can_edit,
                MAX(rp.can_delete) AS can_delete,
                MAX(rp.can_approve) AS can_approve
            FROM user_roles ur
            INNER JOIN role_permissions rp ON rp.role_id = ur.role_id
            INNER JOIN modules m ON m.id = rp.module_id
            GROUP BY
                ur.user_id,
                m.id,
                m.name,
                m.display_name,
                m.controller,
                m.parent_id
        ");

        $rows = $query->result_array();
        $inserted = 0;

        if (!empty($rows)) {
            $batch = array();
            $now = date('Y-m-d H:i:s');

            foreach ($rows as $row) {
                $payload = array(
                    'user_id' => (int) $row['user_id'],
                    'module_id' => (int) $row['module_id'],
                    'module_name' => $row['module_name'],
                    'module_display_name' => $row['module_display_name'],
                    'controller' => $row['controller'],
                    'parent_id' => $row['parent_id'],
                    'can_view' => (int) $row['can_view'],
                    'can_create' => (int) $row['can_create'],
                    'can_edit' => (int) $row['can_edit'],
                    'can_delete' => (int) $row['can_delete'],
                    'can_approve' => (int) $row['can_approve']
                );

                if ($has_override) {
                    $payload['has_override'] = 0;
                }
                if ($has_created_at) {
                    $payload['created_at'] = $now;
                }
                if ($has_updated_at) {
                    $payload['updated_at'] = $now;
                }

                $batch[] = $payload;

                if (count($batch) >= 500) {
                    $this->db->insert_batch('user_module_permissions', $batch, $columns);
                    $inserted += count($batch);
                    $batch = array();
                }
            }

            if (!empty($batch)) {
                $this->db->insert_batch('user_module_permissions', $batch, $columns);
                $inserted += count($batch);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return $this->respond_error('Migration failed.');
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'inserted' => $inserted
            )));
    }

    private function ensure_allowed()
    {
        $ip = $this->input->ip_address();
        if (!in_array($ip, $this->allowed_ips, true)) {
            $this->output->set_status_header(403);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Forbidden.'
                )));
            return false;
        }

        return true;
    }

    private function respond_error($message)
    {
        $this->output
            ->set_status_header(500)
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => false,
                'message' => $message
            )));
    }
}
