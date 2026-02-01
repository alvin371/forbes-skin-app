<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ApprovalRouteResolver Library
 *
 * Finds the best matching approval route for a leave request using priority scoring.
 * Supports multi-scope route matching, dynamic approver resolution, and role-based
 * department matching.
 *
 * Priority Scoring:
 * - user: 1000 points
 * - leave_duration: 300 points
 * - leave_type: 200 points
 * - role: 100 points
 * - department: 50 points
 * - office: 25 points
 * - company: 10 points
 *
 * Dynamic Approver Types:
 * - user: Direct user ID lookup
 * - role: Find user with matching role name
 * - position: Find user with matching position
 * - dynamic: Resolve to direct_manager, skip_level_manager, etc.
 */
class ApprovalRouteResolver
{
    protected $CI;
    protected $db;

    /**
     * Priority scores for each scope type
     */
    protected $scope_priority_scores = array(
        'user' => 1000,
        'leave_duration' => 300,
        'leave_type' => 200,
        'role' => 100,
        'department' => 50,
        'office' => 25,
        'company' => 10,
    );

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    /**
     * Resolve the best matching route for a leave request
     *
     * @param int $userId The user requesting leave
     * @param int $leaveTypeId The leave type being requested
     * @param int $daysCount Number of days requested
     * @param string|null $submissionDate Date of submission (defaults to today)
     * @return array|null Returns route data with resolved approvers or null if no route found
     */
    public function resolve($userId, $leaveTypeId, $daysCount, $submissionDate = null)
    {
        if (!$submissionDate) {
            $submissionDate = date('Y-m-d');
        }

        // Get user data for matching
        $userData = $this->getUserData($userId);
        if (!$userData) {
            log_message('error', 'ApprovalRouteResolver: User not found: ' . $userId);
            return null;
        }

        // Get leave type data
        $leaveData = array(
            'leave_type_id' => $leaveTypeId,
            'days_count' => $daysCount,
        );

        // Get active route versions effective on the submission date
        $routes = $this->getActiveRoutes($submissionDate);
        if (empty($routes)) {
            log_message('info', 'ApprovalRouteResolver: No active routes found for date: ' . $submissionDate);
            return null;
        }

        // Score and filter routes
        $matchedRoutes = array();
        foreach ($routes as $route) {
            $scopes = $this->getRouteScopes($route['id']);

            // If route has no scopes, it's a fallback route (matches everyone)
            if (empty($scopes)) {
                $matchedRoutes[] = array(
                    'route' => $route,
                    'score' => 0, // Lowest priority
                );
                continue;
            }

            // Check if all scopes match
            if ($this->matchesAllScopes($scopes, $userData, $leaveData)) {
                $score = $this->calculatePriorityScore($scopes);
                $matchedRoutes[] = array(
                    'route' => $route,
                    'score' => $score,
                );
            }
        }

        if (empty($matchedRoutes)) {
            log_message('info', 'ApprovalRouteResolver: No matching routes for user: ' . $userId);
            return null;
        }

        // Sort by score descending (highest priority first)
        usort($matchedRoutes, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // Get the best matching route
        $bestMatch = $matchedRoutes[0];
        $route = $bestMatch['route'];

        // Get steps and resolve approvers
        $steps = $this->getRouteSteps($route['id']);
        $resolvedSteps = $this->resolveApprovers($steps, $userId);

        return array(
            'route_version_id' => $route['id'],
            'route_code' => $route['route_code'],
            'route_name' => $route['name'],
            'version' => $route['version'],
            'score' => $bestMatch['score'],
            'steps' => $resolvedSteps,
            'total_steps' => count($resolvedSteps),
        );
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
                u.role,
                u.role_text,
                u.manager_id,
                up.position_id,
                p.name as position_name,
                ql.name as level_name
            FROM user u
            LEFT JOIN user_profile up ON u.id = up.user_id
            LEFT JOIN positions p ON up.position_id = p.id
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE u.id = ?
        ", array($userId));

        $result = $query->row_array();
        if (!$result) {
            return null;
        }

        // Get user's roles from RBAC system
        $roles_query = $this->db->query("
            SELECT r.id, r.name, r.display_name, r.level
            FROM user_roles ur
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ? AND r.is_active = 1
        ", array($userId));
        $result['roles'] = $roles_query->result_array();

        // Extract department from role_text or position
        $result['department'] = $this->extractDepartment($result);

        return $result;
    }

    /**
     * Extract department from role_text or position name
     * Since departments are embedded in role names (e.g., "Marketing Staff", "Finance Manager")
     *
     * @param array $userData
     * @return string|null
     */
    protected function extractDepartment($userData)
    {
        $department = null;

        // Try to extract from role_text
        if (!empty($userData['role_text'])) {
            $department = $this->parseDepartmentFromString($userData['role_text']);
        }

        // Fallback to position name
        if (!$department && !empty($userData['position_name'])) {
            $department = $this->parseDepartmentFromString($userData['position_name']);
        }

        // Fallback to level name
        if (!$department && !empty($userData['level_name'])) {
            $department = $userData['level_name'];
        }

        return $department;
    }

    /**
     * Parse department keyword from a string
     *
     * @param string $str
     * @return string|null
     */
    protected function parseDepartmentFromString($str)
    {
        // Common department keywords
        $departments = array(
            'Marketing', 'Finance', 'HR', 'Human Resources', 'IT', 'Technology',
            'Operations', 'Warehouse', 'Sales', 'Customer Service', 'Admin',
            'Legal', 'Accounting', 'Production', 'Engineering', 'Design',
            'Quality', 'Procurement', 'Logistics', 'R&D', 'Research'
        );

        $str_lower = strtolower($str);
        foreach ($departments as $dept) {
            if (stripos($str_lower, strtolower($dept)) !== false) {
                return $dept;
            }
        }

        return null;
    }

    /**
     * Get active route versions effective on a given date
     *
     * @param string $date
     * @return array
     */
    protected function getActiveRoutes($date)
    {
        $query = $this->db->query("
            SELECT *
            FROM approval_route_versions
            WHERE is_active = 1
              AND effective_from <= ?
              AND (effective_to IS NULL OR effective_to >= ?)
            ORDER BY version DESC
        ", array($date, $date));

        return $query->result_array();
    }

    /**
     * Get scopes for a route version
     *
     * @param int $routeVersionId
     * @return array
     */
    protected function getRouteScopes($routeVersionId)
    {
        $query = $this->db->query("
            SELECT *
            FROM approval_route_scopes
            WHERE route_version_id = ?
        ", array($routeVersionId));

        return $query->result_array();
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
     * Check if all scopes match the user and leave data
     *
     * @param array $scopes
     * @param array $userData
     * @param array $leaveData
     * @return bool
     */
    protected function matchesAllScopes($scopes, $userData, $leaveData)
    {
        foreach ($scopes as $scope) {
            if (!$this->matchScope($scope, $userData, $leaveData)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if a single scope matches
     *
     * @param array $scope
     * @param array $userData
     * @param array $leaveData
     * @return bool
     */
    protected function matchScope($scope, $userData, $leaveData)
    {
        $scopeType = $scope['scope_type'];
        $scopeValue = $scope['scope_value'];
        $operator = $scope['operator'];

        switch ($scopeType) {
            case 'user':
                return $this->matchValue($userData['id'], $scopeValue, $operator);

            case 'department':
                return $this->matchValue($userData['department'], $scopeValue, $operator);

            case 'role':
                // Match against any of user's roles
                foreach ($userData['roles'] as $role) {
                    if ($this->matchValue($role['name'], $scopeValue, $operator) ||
                        $this->matchValue($role['display_name'], $scopeValue, $operator)) {
                        return true;
                    }
                }
                // Also try role_text
                return $this->matchValue($userData['role_text'], $scopeValue, $operator);

            case 'leave_type':
                return $this->matchValue($leaveData['leave_type_id'], $scopeValue, $operator);

            case 'leave_duration':
                return $this->matchNumeric($leaveData['days_count'], $scopeValue, $operator);

            case 'office':
                // TODO: Implement when office data is available
                return true;

            case 'company':
                // TODO: Implement when company data is available
                return true;

            default:
                log_message('warning', 'ApprovalRouteResolver: Unknown scope type: ' . $scopeType);
                return true;
        }
    }

    /**
     * Match a value against a scope value using the operator
     *
     * @param mixed $actualValue
     * @param string $scopeValue
     * @param string $operator
     * @return bool
     */
    protected function matchValue($actualValue, $scopeValue, $operator)
    {
        if ($actualValue === null) {
            return false;
        }

        switch ($operator) {
            case 'eq':
                return strtolower($actualValue) == strtolower($scopeValue);

            case 'neq':
                return strtolower($actualValue) != strtolower($scopeValue);

            case 'in':
                $values = array_map('trim', explode(',', $scopeValue));
                $values = array_map('strtolower', $values);
                return in_array(strtolower($actualValue), $values);

            case 'not_in':
                $values = array_map('trim', explode(',', $scopeValue));
                $values = array_map('strtolower', $values);
                return !in_array(strtolower($actualValue), $values);

            default:
                return strtolower($actualValue) == strtolower($scopeValue);
        }
    }

    /**
     * Match a numeric value using comparison operator
     *
     * @param int|float $actualValue
     * @param string $scopeValue
     * @param string $operator
     * @return bool
     */
    protected function matchNumeric($actualValue, $scopeValue, $operator)
    {
        $scopeNum = floatval($scopeValue);
        $actualNum = floatval($actualValue);

        switch ($operator) {
            case 'eq':
                return $actualNum == $scopeNum;
            case 'neq':
                return $actualNum != $scopeNum;
            case 'lt':
                return $actualNum < $scopeNum;
            case 'lte':
                return $actualNum <= $scopeNum;
            case 'gt':
                return $actualNum > $scopeNum;
            case 'gte':
                return $actualNum >= $scopeNum;
            default:
                return $actualNum == $scopeNum;
        }
    }

    /**
     * Calculate priority score for a set of scopes
     *
     * @param array $scopes
     * @return int
     */
    protected function calculatePriorityScore($scopes)
    {
        $score = 0;
        foreach ($scopes as $scope) {
            $type = $scope['scope_type'];
            if (isset($this->scope_priority_scores[$type])) {
                $score += $this->scope_priority_scores[$type];
            }
        }
        return $score;
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
                'is_optional' => $step['is_optional'],
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
                // Direct user ID
                return intval($approverValue);

            case 'role':
                // Find a user with this role name
                return $this->findUserByRole($approverValue);

            case 'position':
                // Find a user with this position
                return $this->findUserByPosition($approverValue);

            case 'dynamic':
                // Resolve dynamic approver type
                return $this->resolveDynamicApprover($approverValue, $requesterId);

            default:
                log_message('warning', 'ApprovalRouteResolver: Unknown approver type: ' . $approverType);
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
        // First try the new RBAC system
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
                // Get manager_id from user record
                $query = $this->db->query("
                    SELECT manager_id
                    FROM user
                    WHERE id = ?
                ", array($requesterId));
                $result = $query->row_array();
                return $result && $result['manager_id'] ? intval($result['manager_id']) : null;

            case 'skip_level_manager':
                // Get manager's manager
                $query = $this->db->query("
                    SELECT m.manager_id
                    FROM user u
                    INNER JOIN user m ON u.manager_id = m.id
                    WHERE u.id = ?
                ", array($requesterId));
                $result = $query->row_array();
                return $result && $result['manager_id'] ? intval($result['manager_id']) : null;

            case 'hr_head':
                // Find user with HR Head role
                return $this->findUserByRole('Head of HR');

            case 'director':
                // Find user with Director role
                return $this->findUserByRole('Director');

            default:
                log_message('warning', 'ApprovalRouteResolver: Unknown dynamic approver: ' . $dynamicType);
                return null;
        }
    }

    /**
     * Preview which users would match a given route
     *
     * @param int $routeVersionId
     * @param int $limit
     * @return array
     */
    public function previewMatchingUsers($routeVersionId, $limit = 50)
    {
        $scopes = $this->getRouteScopes($routeVersionId);

        // Get all active users
        $query = $this->db->query("
            SELECT id
            FROM user
            WHERE status = 'Aktif'
            LIMIT ?
        ", array($limit * 2)); // Get more than limit in case some don't match

        $users = $query->result_array();
        $matchingUsers = array();

        foreach ($users as $user) {
            $userData = $this->getUserData($user['id']);
            if (!$userData) continue;

            // If no scopes, all users match
            if (empty($scopes)) {
                $matchingUsers[] = array(
                    'id' => $userData['id'],
                    'full_name' => $userData['full_name'],
                    'role_text' => $userData['role_text'],
                    'department' => $userData['department'],
                );
            } else {
                // Check if all scopes match (leave data doesn't matter for preview)
                $dummyLeaveData = array('leave_type_id' => 0, 'days_count' => 0);

                // For preview, we check only user-specific scopes
                $userScopes = array_filter($scopes, function($s) {
                    return in_array($s['scope_type'], array('user', 'department', 'role', 'office', 'company'));
                });

                if (empty($userScopes) || $this->matchesAllScopes($userScopes, $userData, $dummyLeaveData)) {
                    $matchingUsers[] = array(
                        'id' => $userData['id'],
                        'full_name' => $userData['full_name'],
                        'role_text' => $userData['role_text'],
                        'department' => $userData['department'],
                    );
                }
            }

            if (count($matchingUsers) >= $limit) {
                break;
            }
        }

        return $matchingUsers;
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

        $route['scopes'] = $this->getRouteScopes($routeVersionId);
        $route['steps'] = $this->getRouteSteps($routeVersionId);

        return $route;
    }
}
