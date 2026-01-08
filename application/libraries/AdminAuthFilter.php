<?php
defined('BASEPATH') or exit('No direct script access allowed');

class AdminAuthFilter
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function enforce()
    {
        $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        if (!$userId) {
            redirect(base_url('auth/login'));
            return;
        }

        if ($this->is_admin_user($userId)) {
            return;
        }

        $this->CI->output->set_status_header(403);
        $data = array(
            'heading' => 'Access Forbidden',
            'message' => 'You do not have permission to access this resource.',
        );
        $this->CI->load->view('errors/html/error_403', $data);
        exit;
    }

    private function is_admin_user($userId)
    {
        $userId = (int) $userId;

        try {
            $rolesTable = $this->CI->db->query("SHOW TABLES LIKE 'roles'")->result_array();
            $userRolesTable = $this->CI->db->query("SHOW TABLES LIKE 'user_roles'")->result_array();

            if (!empty($rolesTable) && !empty($userRolesTable)) {
                $roles = $this->CI->db->query("
                    SELECT r.name
                    FROM user_roles ur
                    INNER JOIN roles r ON ur.role_id = r.id
                    WHERE ur.user_id = ? AND r.is_active = 1
                ", array($userId))->result_array();

                foreach ($roles as $role) {
                    if (in_array(strtolower($role['name']), array('super_admin', 'admin'))) {
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            // fall through to legacy role check
        }

        $legacy = $this->CI->db->query("SELECT role FROM user WHERE id = ? LIMIT 1", array($userId))->row_array();
        if ($legacy && isset($legacy['role'])) {
            return in_array((string) $legacy['role'], array('1', '2', '7'), true);
        }

        return false;
    }
}
