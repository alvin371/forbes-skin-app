<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ApprovalRouteVersionModel
 *
 * Manages CRUD operations for approval route versions, including scopes and steps.
 */
class ApprovalRouteVersionModel extends CI_Model
{
    protected $table = 'approval_route_versions';
    protected $scopes_table = 'approval_route_scopes';
    protected $steps_table = 'approval_route_steps';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all route versions with optional filtering
     *
     * @param bool $activeOnly
     * @param bool $currentOnly Only get current versions (not superseded)
     * @return array
     */
    public function get_all($activeOnly = true, $currentOnly = true)
    {
        $where = '1=1';

        if ($activeOnly) {
            $where .= ' AND arv.is_active = 1';
        }

        if ($currentOnly) {
            $where .= ' AND arv.effective_to IS NULL';
        }

        $sql = "
            SELECT
                arv.*,
                u.full_name as created_by_name,
                (SELECT COUNT(*) FROM {$this->scopes_table} WHERE route_version_id = arv.id) as scope_count,
                (SELECT COUNT(*) FROM {$this->steps_table} WHERE route_version_id = arv.id) as step_count
            FROM {$this->table} arv
            LEFT JOIN user u ON arv.created_by = u.id
            WHERE $where
            ORDER BY arv.route_code ASC, arv.version DESC
        ";

        return $this->db->query($sql)->result_array();
    }

    /**
     * Get a single route version by ID
     *
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $query = $this->db->query("
            SELECT arv.*, u.full_name as created_by_name
            FROM {$this->table} arv
            LEFT JOIN user u ON arv.created_by = u.id
            WHERE arv.id = ?
        ", array($id));

        $route = $query->row_array();

        if ($route) {
            $route['scopes'] = $this->get_scopes($id);
            $route['steps'] = $this->get_steps($id);
        }

        return $route;
    }

    /**
     * Get route by code (latest active version)
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
              AND is_active = 1
              AND effective_to IS NULL
            ORDER BY version DESC
            LIMIT 1
        ", array($routeCode));

        $route = $query->row_array();

        if ($route) {
            $route['scopes'] = $this->get_scopes($route['id']);
            $route['steps'] = $this->get_steps($route['id']);
        }

        return $route;
    }

    /**
     * Get all versions of a route by code
     *
     * @param string $routeCode
     * @return array
     */
    public function get_versions($routeCode)
    {
        $sql = "
            SELECT arv.*, u.full_name as created_by_name
            FROM {$this->table} arv
            LEFT JOIN user u ON arv.created_by = u.id
            WHERE arv.route_code = ?
            ORDER BY arv.version DESC
        ";

        return $this->db->query($sql, array($routeCode))->result_array();
    }

    /**
     * Get scopes for a route version
     *
     * @param int $routeVersionId
     * @return array
     */
    public function get_scopes($routeVersionId)
    {
        return $this->db->query("
            SELECT * FROM {$this->scopes_table}
            WHERE route_version_id = ?
            ORDER BY id ASC
        ", array($routeVersionId))->result_array();
    }

    /**
     * Get steps for a route version
     *
     * @param int $routeVersionId
     * @return array
     */
    public function get_steps($routeVersionId)
    {
        return $this->db->query("
            SELECT * FROM {$this->steps_table}
            WHERE route_version_id = ?
            ORDER BY step_no ASC
        ", array($routeVersionId))->result_array();
    }

    /**
     * Create a new route version with scopes and steps
     *
     * @param array $routeData
     * @param array $scopes
     * @param array $steps
     * @return int|false Route version ID or false on failure
     */
    public function create($routeData, $scopes = array(), $steps = array())
    {
        $this->db->trans_start();

        // Set defaults
        $routeData['version'] = 1;
        $routeData['created_at'] = date('Y-m-d H:i:s');
        $routeData['updated_at'] = date('Y-m-d H:i:s');

        // Check if route_code already exists
        $existing = $this->get_by_code($routeData['route_code']);
        if ($existing) {
            // Increment version
            $routeData['version'] = $existing['version'] + 1;

            // Deactivate old version
            $this->db->update($this->table, array(
                'effective_to' => date('Y-m-d'),
                'updated_at' => date('Y-m-d H:i:s'),
            ), array('id' => $existing['id']));
        }

        // Insert route version
        $this->db->insert($this->table, $routeData);
        $routeVersionId = $this->db->insert_id();

        // Insert scopes
        foreach ($scopes as $scope) {
            $scope['route_version_id'] = $routeVersionId;
            $this->db->insert($this->scopes_table, $scope);
        }

        // Insert steps
        foreach ($steps as $step) {
            $step['route_version_id'] = $routeVersionId;
            $this->db->insert($this->steps_table, $step);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return false;
        }

        return $routeVersionId;
    }

    /**
     * Update a route version (creates a new version)
     *
     * @param int $id
     * @param array $routeData
     * @param array $scopes
     * @param array $steps
     * @return int|false New route version ID or false on failure
     */
    public function update($id, $routeData, $scopes = array(), $steps = array())
    {
        $existing = $this->get_by_id($id);
        if (!$existing) {
            return false;
        }

        // Create new version instead of updating
        $routeData['route_code'] = $existing['route_code'];
        $routeData['version'] = $existing['version'] + 1;
        $routeData['effective_from'] = isset($routeData['effective_from']) ? $routeData['effective_from'] : date('Y-m-d');
        $routeData['created_by'] = isset($routeData['created_by']) ? $routeData['created_by'] : $existing['created_by'];

        $this->db->trans_start();

        // Deactivate old version
        $this->db->update($this->table, array(
            'effective_to' => date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $id));

        // Insert new version
        $routeData['created_at'] = date('Y-m-d H:i:s');
        $routeData['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $routeData);
        $newId = $this->db->insert_id();

        // Insert scopes
        foreach ($scopes as $scope) {
            $scope['route_version_id'] = $newId;
            $this->db->insert($this->scopes_table, $scope);
        }

        // Insert steps
        foreach ($steps as $step) {
            $step['route_version_id'] = $newId;
            $this->db->insert($this->steps_table, $step);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return false;
        }

        return $newId;
    }

    /**
     * Deactivate a route version
     *
     * @param int $id
     * @return bool
     */
    public function deactivate($id)
    {
        return $this->db->update($this->table, array(
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $id));
    }

    /**
     * Activate a route version
     *
     * @param int $id
     * @return bool
     */
    public function activate($id)
    {
        return $this->db->update($this->table, array(
            'is_active' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $id));
    }

    /**
     * Delete a route version (and its scopes/steps)
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->trans_start();

        $this->db->delete($this->steps_table, array('route_version_id' => $id));
        $this->db->delete($this->scopes_table, array('route_version_id' => $id));
        $this->db->delete($this->table, array('id' => $id));

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
            'company' => 'Perusahaan',
            'office' => 'Kantor',
            'department' => 'Departemen',
            'role' => 'Role',
            'user' => 'User Spesifik',
            'leave_type' => 'Tipe Cuti',
            'leave_duration' => 'Durasi Cuti (Hari)',
        );
    }

    /**
     * Get available operators
     *
     * @return array
     */
    public function get_operators()
    {
        return array(
            'eq' => 'Sama dengan (=)',
            'neq' => 'Tidak sama dengan (!=)',
            'in' => 'Salah satu dari',
            'not_in' => 'Bukan salah satu dari',
            'lt' => 'Kurang dari (<)',
            'lte' => 'Kurang dari atau sama (<=)',
            'gt' => 'Lebih dari (>)',
            'gte' => 'Lebih dari atau sama (>=)',
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

    /**
     * Get all leave types for dropdown
     *
     * @return array
     */
    public function get_leave_types_for_dropdown()
    {
        return $this->db->query("
            SELECT id, code, name
            FROM leave_types
            WHERE is_active = 1
            ORDER BY name ASC
        ")->result_array();
    }

    /**
     * Bulk create routes
     *
     * @param array $routes Array of route data with scopes and steps
     * @param int $createdBy
     * @return array Results with success/failure for each route
     */
    public function bulk_create($routes, $createdBy)
    {
        $results = array();

        foreach ($routes as $route) {
            $routeData = array(
                'route_code' => $route['route_code'],
                'name' => $route['name'],
                'description' => isset($route['description']) ? $route['description'] : null,
                'effective_from' => isset($route['effective_from']) ? $route['effective_from'] : date('Y-m-d'),
                'is_active' => 1,
                'created_by' => $createdBy,
            );

            $scopes = isset($route['scopes']) ? $route['scopes'] : array();
            $steps = isset($route['steps']) ? $route['steps'] : array();

            $id = $this->create($routeData, $scopes, $steps);

            $results[] = array(
                'route_code' => $route['route_code'],
                'success' => $id !== false,
                'id' => $id,
            );
        }

        return $results;
    }
}
