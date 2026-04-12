<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ApprovalRouteResolver Library
 *
 * Finds the best matching approval route for approval-backed requests using priority scoring.
 * Supports multi-scope route matching, dynamic approver resolution, and role-based
 * role- and user-based matching.
 *
 * Priority Scoring:
 * - user: 1000 points
 * - leave_duration: 300 points
 * - leave_type: 200 points
 * - overtime_type: 200 points
 * - role: 100 points
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
        'overtime_duration' => 300,
        'leave_type' => 200,
        'overtime_type' => 200,
        'role' => 100,
        'office' => 25,
        'company' => 10,
    );

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    /**
     * Resolve the best matching route for an approval-backed request
     *
     * @param int $userId The user requesting approval
     * @param array $requestData Request-specific criteria
     * @param string|null $submissionDate Date of submission (defaults to today)
     * @return array|null Returns route data with resolved approvers or null if no route found
     */
    public function resolve($userId, $requestData = array(), $submissionDate = null)
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

        // Get active route versions effective on the submission date
        $routes = $this->getActiveRoutes($submissionDate);
        if (empty($routes)) {
            log_message('info', 'ApprovalRouteResolver: No active approval routes found for date: ' . $submissionDate);
            return null;
        }

        // Score and filter routes
        $matchedRoutes = array();
        foreach ($routes as $route) {
            $scopes = $this->getRouteScopes($route['id']);

            if ($this->containsLegacyDepartmentScope($scopes)) {
                log_message('info', 'ApprovalRouteResolver: Skipping legacy department-scoped route: ' . $route['id']);
                continue;
            }

            // If route has no scopes, it's a fallback route (matches everyone)
            if (empty($scopes)) {
                $matchedRoutes[] = array(
                    'route' => $route,
                    'score' => 0, // Lowest priority
                );
                continue;
            }

            // Check if all scopes match
            if ($this->matchesAllScopes($scopes, $userData, $requestData)) {
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
        $selectFields = array(
            'u.id',
            'u.full_name',
            'u.role',
            'u.role_text',
            'u.manager_id',
            'up.position_id',
            'p.name as position_name',
        );

        $query = $this->db->query("
            SELECT
                " . implode(",\n                ", $selectFields) . "
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

        return $result;
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
            SELECT arv.*,
                   COALESCE(scope_meta.scope_count, 0) as scope_count
            FROM approval_route_versions arv
            LEFT JOIN (
                SELECT route_version_id, COUNT(*) as scope_count
                FROM approval_route_scopes
                GROUP BY route_version_id
            ) scope_meta ON scope_meta.route_version_id = arv.id
            WHERE is_active = 1
              AND effective_from <= ?
              AND (effective_to IS NULL OR effective_to >= ?)
            ORDER BY
              CASE WHEN COALESCE(scope_meta.scope_count, 0) = 0 THEN 1 ELSE 0 END ASC,
              route_code ASC,
              version DESC
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
    protected function matchesAllScopes($scopes, $userData, $requestData)
    {
        foreach ($this->groupScopesByType($scopes) as $scopeGroup) {
            if (!$this->matchesAnyScope($scopeGroup, $userData, $requestData)) {
                return false;
            }
        }
        return true;
    }

    protected function matchesAnyScope($scopes, $userData, $requestData)
    {
        foreach ($scopes as $scope) {
            if ($this->matchScope($scope, $userData, $requestData)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a single scope matches
     *
     * @param array $scope
     * @param array $userData
     * @param array $requestData
     * @return bool
     */
    protected function matchScope($scope, $userData, $requestData)
    {
        $scopeType = $scope['scope_type'];
        $scopeValue = $scope['scope_value'];
        $operator = $scope['operator'];

        switch ($scopeType) {
            case 'user':
                return $this->matchValue($userData['id'], $scopeValue, $operator);

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
                return $this->matchValue(isset($requestData['leave_type_id']) ? $requestData['leave_type_id'] : null, $scopeValue, $operator);

            case 'leave_duration':
                return $this->matchNumeric(isset($requestData['days_count']) ? $requestData['days_count'] : null, $scopeValue, $operator);

            case 'overtime_type':
                return $this->matchValue(isset($requestData['overtime_type_id']) ? $requestData['overtime_type_id'] : null, $scopeValue, $operator);

            case 'overtime_duration':
                return $this->matchNumeric(isset($requestData['duration_hours']) ? $requestData['duration_hours'] : null, $scopeValue, $operator);

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
        $actualValue = $this->normalizeScopeValue($actualValue);
        $scopeValue = $this->normalizeScopeValue($scopeValue);

        if ($actualValue === null || $scopeValue === null) {
            return false;
        }

        switch ($operator) {
            case 'eq':
                return $actualValue === $scopeValue;

            case 'neq':
                return $actualValue !== $scopeValue;

            case 'in':
                return in_array($actualValue, $this->normalizeScopeList($scopeValue), true);

            case 'not_in':
                return !in_array($actualValue, $this->normalizeScopeList($scopeValue), true);

            default:
                return $actualValue === $scopeValue;
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
        if ($actualValue === null || $actualValue === '') {
            return false;
        }

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
        foreach (array_keys($this->groupScopesByType($scopes)) as $type) {
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
        $userScopes = array_values(array_filter($scopes, function($scope) {
            return $this->isEmployeePreviewScope($scope['scope_type']);
        }));
        $hasRequestScopes = count($userScopes) !== count($scopes);

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
            if (!$userData) {
                continue;
            }

            // Preview evaluates only employee-identifying scopes.
            if (empty($userScopes)) {
                $matchingUsers[] = array(
                    'id' => $userData['id'],
                    'full_name' => $userData['full_name'],
                    'role_text' => $userData['role_text'],
                );
            } else {
                if ($this->matchesAllScopes($userScopes, $userData, array())) {
                    $matchingUsers[] = array(
                        'id' => $userData['id'],
                        'full_name' => $userData['full_name'],
                        'role_text' => $userData['role_text'],
                    );
                }
            }

            if (count($matchingUsers) >= $limit) {
                break;
            }
        }

        if ($this->containsLegacyDepartmentScope($scopes)) {
            return array(
                'users' => array(),
                'has_request_scopes' => false,
                'preview_note' => 'Route ini memakai scope department yang sudah dihapus. Route tidak lagi ikut matching sampai scope legacy tersebut dihapus.',
            );
        }

        return array(
            'users' => $matchingUsers,
            'has_request_scopes' => $hasRequestScopes,
            'preview_note' => $hasRequestScopes
                ? 'Preview hanya mengevaluasi kondisi karyawan. Kondisi terkait jenis/durasi pengajuan tidak dihitung di sini.'
                : null,
        );
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

    protected function normalizeScopeValue($value)
    {
        $value = $this->cleanScalarValue($value);

        return $value === null ? null : strtolower($value);
    }

    protected function cleanScalarValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function normalizeScopeList($value)
    {
        $parts = array_map('trim', explode(',', (string) $value));
        $parts = array_filter($parts, function($part) {
            return $part !== '';
        });

        return array_values(array_map('strtolower', $parts));
    }

    protected function isEmployeePreviewScope($scopeType)
    {
        return in_array($scopeType, array('user', 'role', 'office', 'company'), true);
    }

    protected function groupScopesByType($scopes)
    {
        $grouped = array();

        foreach ($scopes as $scope) {
            $scopeType = isset($scope['scope_type']) ? $scope['scope_type'] : '';

            if (!isset($grouped[$scopeType])) {
                $grouped[$scopeType] = array();
            }

            $grouped[$scopeType][] = $scope;
        }

        return $grouped;
    }

    protected function containsLegacyDepartmentScope($scopes)
    {
        foreach ($scopes as $scope) {
            if (isset($scope['scope_type']) && $scope['scope_type'] === 'department') {
                return true;
            }
        }

        return false;
    }
}
