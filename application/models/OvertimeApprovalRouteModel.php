<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeApprovalRouteModel
 *
 * Manages CRUD operations for overtime approval routes with scopes and steps.
 */
class OvertimeApprovalRouteModel extends CI_Model
{
    protected $table = 'overtime_approval_routes';
    protected $scopes_table = 'overtime_approval_route_scopes';
    protected $steps_table = 'overtime_approval_route_steps';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all overtime routes with optional filtering
     *
     * @param bool $activeOnly
     * @return array
     */
    public function get_all($activeOnly = true)
    {
        $where = '1=1';
        if ($activeOnly) {
            $where .= ' AND r.is_active = 1';
        }

        $sql = "
            SELECT
                r.*,
                u.full_name as created_by_name,
                (SELECT COUNT(*) FROM {$this->scopes_table} WHERE route_id = r.id) as scope_count,
                (SELECT COUNT(*) FROM {$this->steps_table} WHERE route_id = r.id) as step_count
            FROM {$this->table} r
            LEFT JOIN user u ON r.created_by = u.id
            WHERE $where
            ORDER BY r.created_at ASC, r.id ASC
        ";

        return $this->db->query($sql)->result_array();
    }

    /**
     * Get a single route by ID with scopes and steps
     *
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $query = $this->db->query("
            SELECT r.*, u.full_name as created_by_name
            FROM {$this->table} r
            LEFT JOIN user u ON r.created_by = u.id
            WHERE r.id = ?
        ", array($id));

        $route = $query->row_array();
        if ($route) {
            $route['scopes'] = $this->get_scopes($id);
            $route['steps'] = $this->get_steps($id);
        }

        return $route;
    }

    /**
     * Get a route by code
     *
     * @param string $routeCode
     * @return array|null
     */
    public function get_by_code($routeCode)
    {
        $query = $this->db->query("
            SELECT *
            FROM {$this->table}
            WHERE route_code = ?
            LIMIT 1
        ", array($routeCode));

        return $query->row_array();
    }

    /**
     * Get scopes for a route
     *
     * @param int $routeId
     * @return array
     */
    public function get_scopes($routeId)
    {
        return $this->db->query("
            SELECT * FROM {$this->scopes_table}
            WHERE route_id = ?
            ORDER BY id ASC
        ", array($routeId))->result_array();
    }

    /**
     * Get steps for a route
     *
     * @param int $routeId
     * @return array
     */
    public function get_steps($routeId)
    {
        return $this->db->query("
            SELECT * FROM {$this->steps_table}
            WHERE route_id = ?
            ORDER BY step_no ASC
        ", array($routeId))->result_array();
    }

    /**
     * Create a new overtime route with scopes and steps
     *
     * @param array $routeData
     * @param array $scopes
     * @param array $steps
     * @return int|false
     */
    public function create($routeData, $scopes = array(), $steps = array())
    {
        $this->db->trans_start();

        $routeData['created_at'] = date('Y-m-d H:i:s');
        $routeData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->insert($this->table, $routeData);
        $routeId = $this->db->insert_id();

        foreach ($scopes as $scope) {
            $scope['route_id'] = $routeId;
            $this->db->insert($this->scopes_table, $scope);
        }

        foreach ($steps as $step) {
            $step['route_id'] = $routeId;
            $this->db->insert($this->steps_table, $step);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return false;
        }

        return $routeId;
    }

    /**
     * Update a route and replace scopes/steps
     *
     * @param int $id
     * @param array $routeData
     * @param array $scopes
     * @param array $steps
     * @return bool
     */
    public function update($id, $routeData, $scopes = array(), $steps = array())
    {
        $this->db->trans_start();

        $routeData['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update($this->table, $routeData, array('id' => (int) $id));

        $this->db->delete($this->scopes_table, array('route_id' => (int) $id));
        $this->db->delete($this->steps_table, array('route_id' => (int) $id));

        foreach ($scopes as $scope) {
            $scope['route_id'] = $id;
            $this->db->insert($this->scopes_table, $scope);
        }

        foreach ($steps as $step) {
            $step['route_id'] = $id;
            $this->db->insert($this->steps_table, $step);
        }

        $this->db->trans_complete();

        return $this->db->trans_status() !== FALSE;
    }

    /**
     * Deactivate a route
     *
     * @param int $id
     * @return bool
     */
    public function deactivate($id)
    {
        return $this->db->update($this->table, array(
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => (int) $id));
    }

    /**
     * Activate a route
     *
     * @param int $id
     * @return bool
     */
    public function activate($id)
    {
        return $this->db->update($this->table, array(
            'is_active' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => (int) $id));
    }

    /**
     * Delete a route
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->trans_start();
        $this->db->delete($this->steps_table, array('route_id' => (int) $id));
        $this->db->delete($this->scopes_table, array('route_id' => (int) $id));
        $this->db->delete($this->table, array('id' => (int) $id));
        $this->db->trans_complete();

        return $this->db->trans_status() !== FALSE;
    }

    /**
     * Get available scope types
     *
     * @return array
     */
    public function get_scope_types()
    {
        return array(
            'user' => 'User Spesifik',
            'role' => 'Role',
        );
    }

    /**
     * Get available approver types
     *
     * @return array
     */
    public function get_approver_types()
    {
        return array(
            'user' => 'User Spesifik',
            'role' => 'Role',
            'position' => 'Posisi',
            'dynamic' => 'Dinamis',
        );
    }

    /**
     * Get available dynamic approver options
     *
     * @return array
     */
    public function get_dynamic_approvers()
    {
        return array(
            'direct_manager' => 'Atasan Langsung',
            'skip_level_manager' => 'Atasan Tidak Langsung (Skip Level)',
            'hr_head' => 'Head of HR',
            'director' => 'Direktur',
        );
    }

    /**
     * Get all roles for dropdown
     *
     * @return array
     */
    public function get_roles_for_dropdown()
    {
        return $this->db->query("
            SELECT id, name, display_name
            FROM roles
            WHERE is_active = 1
            ORDER BY display_name ASC
        ")->result_array();
    }

    /**
     * Get all users for dropdown
     *
     * @return array
     */
    public function get_users_for_dropdown()
    {
        return $this->db->query("
            SELECT id, full_name, role_text
            FROM user
            WHERE status = 'Aktif'
            ORDER BY full_name ASC
        ")->result_array();
    }
}
