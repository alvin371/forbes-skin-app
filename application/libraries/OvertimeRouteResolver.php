<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeRouteResolver Library
 *
 * Resolves approval routes for overtime requests using overtime-specific routes.
 * Matching rules:
 * - Scopes only support user and role
 * - No priority scoring; first match by creation order
 */
class OvertimeRouteResolver
{
    protected $CI;
    protected $db;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    /**
     * Resolve the approval route for an overtime request
     *
     * @param int $userId The user requesting overtime
     * @param int $overtimeTypeId The overtime type being requested (unused for matching)
     * @param float $durationHours Duration of overtime (unused for matching)
     * @param string|null $submissionDate Date of submission (unused for matching)
     * @return array|null Returns route data with resolved approvers or null if no route found
     */
    public function resolve($userId, $overtimeTypeId, $durationHours, $submissionDate = null)
    {
        $userData = $this->getUserData($userId);
        if (!$userData) {
            log_message('error', 'OvertimeRouteResolver: User not found: ' . $userId);
            return null;
        }

        $routes = $this->getActiveRoutes();
        if (empty($routes)) {
            log_message('info', 'OvertimeRouteResolver: No active overtime routes found');
            return null;
        }

        foreach ($routes as $route) {
            $scopes = $this->getRouteScopes($route['id']);
            if ($this->matchesAllScopes($scopes, $userData)) {
                $steps = $this->getRouteSteps($route['id']);
                $resolvedSteps = $this->resolveApprovers($steps, $userId);

                return array(
                    'route_id' => $route['id'],
                    'route_code' => $route['route_code'],
                    'route_name' => $route['name'],
                    'steps' => $resolvedSteps,
                    'total_steps' => count($resolvedSteps),
                );
            }
        }

        log_message('info', 'OvertimeRouteResolver: No matching routes for user: ' . $userId);
        return null;
    }

    /**
     * Get active overtime routes ordered by creation (first created first)
     *
     * @return array
     */
    protected function getActiveRoutes()
    {
        return $this->db->query("
            SELECT *
            FROM overtime_approval_routes
            WHERE is_active = 1
            ORDER BY created_at ASC, id ASC
        ")->result_array();
    }

    /**
     * Get scopes for a route
     *
     * @param int $routeId
     * @return array
     */
    protected function getRouteScopes($routeId)
    {
        return $this->db->query("
            SELECT *
            FROM overtime_approval_route_scopes
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
    protected function getRouteSteps($routeId)
    {
        return $this->db->query("
            SELECT *
            FROM overtime_approval_route_steps
            WHERE route_id = ?
            ORDER BY step_no ASC
        ", array($routeId))->result_array();
    }

    /**
     * Check if all scopes match a user
     *
     * @param array $scopes
     * @param array $userData
     * @return bool
     */
    protected function matchesAllScopes($scopes, $userData)
    {
        if (empty($scopes)) {
            return true;
        }

        foreach ($scopes as $scope) {
            if (!$this->matchesScope($scope, $userData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a single scope matches a user
     *
     * @param array $scope
     * @param array $userData
     * @return bool
     */
    protected function matchesScope($scope, $userData)
    {
        switch ($scope['scope_type']) {
            case 'user':
                return (int) $scope['scope_value'] === (int) $userData['id'];
            case 'role':
                return $this->userHasRole($userData, $scope['scope_value']);
            default:
                return false;
        }
    }

    /**
     * Check if user has the given role
     *
     * @param array $userData
     * @param string $roleName
     * @return bool
     */
    protected function userHasRole($userData, $roleName)
    {
        $roleName = trim((string) $roleName);
        if ($roleName === '') {
            return false;
        }

        if (!empty($userData['roles'])) {
            foreach ($userData['roles'] as $role) {
                if (strcasecmp($role['name'], $roleName) === 0 || strcasecmp($role['display_name'], $roleName) === 0) {
                    return true;
                }
            }
        }

        if (!empty($userData['role_text']) && stripos($userData['role_text'], $roleName) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get user data needed for scope matching
     *
     * @param int $userId
     * @return array|null
     */
    protected function getUserData($userId)
    {
        $query = $this->db->query("
            SELECT
                u.id,
                u.full_name,
                u.role_text,
                u.manager_id
            FROM user u
            WHERE u.id = ?
        ", array($userId));

        $result = $query->row_array();
        if (!$result) {
            return null;
        }

        $roles_query = $this->db->query("
            SELECT r.id, r.name, r.display_name
            FROM user_roles ur
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ? AND r.is_active = 1
        ", array($userId));
        $result['roles'] = $roles_query->result_array();

        return $result;
    }

    /**
     * Resolve approvers for route steps
     *
     * @param array $steps
     * @param int $requesterId
     * @return array
     */
    public function resolveApprovers($steps, $requesterId)
    {
        $resolvedSteps = array();

        foreach ($steps as $step) {
            $approverId = $this->resolveApprover(
                $step['approver_type'],
                $step['approver_value'],
                $requesterId
            );

            $resolvedSteps[] = array(
                'step_no' => $step['step_no'],
                'step_name' => $step['step_name'],
                'approver_type' => $step['approver_type'],
                'approver_value' => $step['approver_value'],
                'assigned_approver_id' => $approverId,
                'is_optional' => isset($step['is_optional']) ? $step['is_optional'] : 0,
            );
        }

        return $resolvedSteps;
    }

    /**
     * Resolve a single approver
     *
     * @param string $approverType user, role, position, or dynamic
     * @param string $approverValue The value to resolve
     * @param int $requesterId The user submitting the request
     * @return int|null
     */
    protected function resolveApprover($approverType, $approverValue, $requesterId)
    {
        switch ($approverType) {
            case 'user':
                return intval($approverValue);

            case 'role':
                return $this->findUserByRole($approverValue);

            case 'position':
                return $this->findUserByPosition($approverValue);

            case 'dynamic':
                return $this->resolveDynamicApprover($approverValue, $requesterId);

            default:
                log_message('warning', 'OvertimeRouteResolver: Unknown approver type: ' . $approverType);
                return null;
        }
    }

    /**
     * Find a user by role name
     *
     * @param string $roleName
     * @return int|null
     */
    protected function findUserByRole($roleName)
    {
        $query = $this->db->query("
            SELECT u.id
            FROM user u
            INNER JOIN user_roles ur ON u.id = ur.user_id
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE (LOWER(r.name) = LOWER(?) OR LOWER(r.display_name) = LOWER(?))
              AND r.is_active = 1
              AND u.status = 'Aktif'
            LIMIT 1
        ", array($roleName, $roleName));

        $result = $query->row_array();
        if ($result) {
            return intval($result['id']);
        }

        $query = $this->db->query("
            SELECT id
            FROM user
            WHERE LOWER(role_text) LIKE LOWER(?)
              AND status = 'Aktif'
            LIMIT 1
        ", array('%' . $roleName . '%'));

        $result = $query->row_array();
        return $result ? intval($result['id']) : null;
    }

    /**
     * Find a user by position name
     *
     * @param string $positionName
     * @return int|null
     */
    protected function findUserByPosition($positionName)
    {
        $query = $this->db->query("
            SELECT up.user_id
            FROM user_profile up
            INNER JOIN positions p ON up.position_id = p.id
            INNER JOIN user u ON up.user_id = u.id
            WHERE LOWER(p.name) LIKE LOWER(?)
              AND u.status = 'Aktif'
            LIMIT 1
        ", array('%' . $positionName . '%'));

        $result = $query->row_array();
        return $result ? intval($result['user_id']) : null;
    }

    /**
     * Resolve dynamic approver types
     *
     * @param string $dynamicType
     * @param int $requesterId
     * @return int|null
     */
    protected function resolveDynamicApprover($dynamicType, $requesterId)
    {
        switch ($dynamicType) {
            case 'direct_manager':
                $query = $this->db->query("
                    SELECT manager_id
                    FROM user
                    WHERE id = ?
                ", array($requesterId));
                $result = $query->row_array();
                return $result && $result['manager_id'] ? intval($result['manager_id']) : null;

            case 'skip_level_manager':
                $query = $this->db->query("
                    SELECT m.manager_id
                    FROM user u
                    INNER JOIN user m ON u.manager_id = m.id
                    WHERE u.id = ?
                ", array($requesterId));
                $result = $query->row_array();
                return $result && $result['manager_id'] ? intval($result['manager_id']) : null;

            case 'hr_head':
                return $this->findUserByRole('Head of HR');

            case 'director':
                return $this->findUserByRole('Director');

            default:
                log_message('warning', 'OvertimeRouteResolver: Unknown dynamic approver: ' . $dynamicType);
                return null;
        }
    }
}
