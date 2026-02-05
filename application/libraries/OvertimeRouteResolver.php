<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeRouteResolver Library
 *
 * Resolves approval routes for overtime requests.
 * Simplified version - always uses DEFAULT route for overtime.
 * Reuses the existing approval_route_versions, approval_route_steps tables.
 */
class OvertimeRouteResolver
{
    protected $CI;
    protected $db;

    /**
     * Default route code for overtime approval
     */
    const DEFAULT_ROUTE_CODE = 'DEFAULT';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    /**
     * Resolve the approval route for an overtime request
     * Always uses the DEFAULT route for overtime.
     *
     * @param int $userId The user requesting overtime
     * @param int $overtimeTypeId The overtime type being requested
     * @param float $durationHours Duration of overtime
     * @param string|null $submissionDate Date of submission (defaults to today)
     * @return array|null Returns route data with resolved approvers or null if no route found
     */
    public function resolve($userId, $overtimeTypeId, $durationHours, $submissionDate = null)
    {
        if (!$submissionDate) {
            $submissionDate = date('Y-m-d');
        }

        // Get DEFAULT route
        $route = $this->getDefaultRoute($submissionDate);
        if (!$route) {
            log_message('info', 'OvertimeRouteResolver: No DEFAULT route found');
            return null;
        }

        // Get steps and resolve approvers
        $steps = $this->getRouteSteps($route['id']);
        $resolvedSteps = $this->resolveApprovers($steps, $userId);

        return array(
            'route_version_id' => $route['id'],
            'route_code' => $route['route_code'],
            'route_name' => $route['name'],
            'version' => $route['version'],
            'steps' => $resolvedSteps,
            'total_steps' => count($resolvedSteps),
        );
    }

    /**
     * Get the DEFAULT route version effective on a given date
     *
     * @param string $date
     * @return array|null
     */
    protected function getDefaultRoute($date)
    {
        $query = $this->db->query("
            SELECT *
            FROM approval_route_versions
            WHERE route_code = ?
              AND is_active = 1
              AND effective_from <= ?
              AND (effective_to IS NULL OR effective_to >= ?)
            ORDER BY version DESC
            LIMIT 1
        ", array(self::DEFAULT_ROUTE_CODE, $date, $date));

        return $query->row_array();
    }

    /**
     * Get steps for a route version
     *
     * @param int $routeVersionId
     * @return array
     */
    protected function getRouteSteps($routeVersionId)
    {
        $query = $this->db->query("
            SELECT *
            FROM approval_route_steps
            WHERE route_version_id = ?
            ORDER BY step_no ASC
        ", array($routeVersionId));

        return $query->result_array();
    }

    /**
     * Resolve approvers for route steps
     *
     * @param array $steps Route step definitions
     * @param int $requesterId The user submitting the request
     * @return array Resolved steps with actual approver IDs
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
     * @return int|null Resolved user ID or null if not found
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
        // Try the RBAC system first
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

        // Fallback to role_text field
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

    /**
     * Get route details for display
     *
     * @param int $routeVersionId
     * @return array|null
     */
    public function getRouteDetails($routeVersionId)
    {
        $query = $this->db->query("
            SELECT *
            FROM approval_route_versions
            WHERE id = ?
        ", array($routeVersionId));

        $route = $query->row_array();
        if (!$route) {
            return null;
        }

        $route['steps'] = $this->getRouteSteps($routeVersionId);

        return $route;
    }
}
