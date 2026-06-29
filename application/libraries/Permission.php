<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Permission Library
 * 
 * Handles dynamic user permissions based on positions and individual overrides
 * Integrates with the quest level system and provides easy permission checking
 */
class Permission
{
    protected $CI;
    protected $user_permissions_cache = [];
    protected $permission_table_capabilities;

    /**
     * Request-scoped cache of the permission-table capability probe. The probe queries
     * INFORMATION_SCHEMA, which showed up as a per-page cost (~120-380ms) because the
     * Permission library is re-instantiated within a single request. A static cache
     * computes it once per PHP request across all Permission instances. The five RBAC
     * tables never change at runtime, so a request-lifetime cache is safe.
     */
    protected static $permission_table_capabilities_cache = null;
    protected $logged_fallback_batches = [];

    /**
     * Session permission map (B2). Built once at login from the same sources the DB
     * checks use, then read on every page so normal navigation does no permission
     * queries. Fail-closed: a map miss denies (never grants). Self-heals after
     * SESSION_PERM_TTL seconds; B3 adds instant invalidation via a version bump.
     */
    const SESSION_PERM_KEY = 'perm_map';
    const SESSION_PERM_TTL = 900; // 15 min safety refresh

    /**
     * Request-cached global permission version (B3). Read once per request from
     * permission_meta and reused across the many permission checks in a request.
     */
    protected static $perm_version_loaded = false;
    protected static $perm_version_cache = null;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('mymodel');
        $this->CI->load->helper('sidebar_registry');
    }

    /**
     * Check if module is active in sidebar registry.
     *
     * @param string $module_name Module name
     * @return bool
     */
    private function is_module_active($module_name)
    {
        if (empty($module_name) || !function_exists('sidebar_registry')) {
            return true;
        }

        $registry = sidebar_registry();
        if (isset($registry[$module_name]) && array_key_exists('is_active', $registry[$module_name])) {
            return (int) $registry[$module_name]['is_active'] === 1;
        }

        return true;
    }

    /**
     * Build and store the session permission map for a user. Call once at login.
     *
     * @param int $user_id
     * @return array the stored map
     */
    public function bootstrap_session_permissions($user_id)
    {
        $map = $this->build_permission_map((int) $user_id);
        $_SESSION[self::SESSION_PERM_KEY] = $map;
        return $map;
    }

    /**
     * Drop the session permission map (e.g. on logout). Forces a rebuild on next use.
     */
    public function clear_session_permissions()
    {
        unset($_SESSION[self::SESSION_PERM_KEY]);
    }

    /**
     * Current global permission version (B3), or null when it cannot be determined
     * (table missing / query error) — in which case callers rely on the TTL refresh.
     * Read once per request and cached, so repeated checks add no extra queries.
     *
     * @return int|null
     */
    public function current_permission_version()
    {
        if (self::$perm_version_loaded) {
            return self::$perm_version_cache;
        }
        self::$perm_version_loaded = true;

        try {
            $row = $this->CI->db->query("SELECT version FROM permission_meta WHERE id = 1")->row_array();
            self::$perm_version_cache = (!empty($row) && isset($row['version'])) ? (int) $row['version'] : null;
        } catch (Exception $e) {
            self::$perm_version_cache = null;
        }

        return self::$perm_version_cache;
    }

    /**
     * Bump the global permission version so every session map rebuilds on its next use.
     * Call after any change to roles / role_permissions / user_module_permissions.
     */
    public function bump_permission_version()
    {
        try {
            $this->CI->db->query(
                "UPDATE permission_meta SET version = version + 1, updated_at = NOW() WHERE id = 1"
            );
            // Invalidate the request cache so a rebuild in this same request sees the bump.
            self::$perm_version_loaded = false;
            self::$perm_version_cache = null;
        } catch (Exception $e) {
            log_message('error', 'bump_permission_version failed: ' . $e->getMessage());
        }
    }

    /**
     * Reset the request-lifetime permission-version cache. Production resets it naturally
     * each request (PHP process boundary); tests that run many cases in one process call
     * this to keep request isolation.
     */
    public static function resetPermissionVersionCache(): void
    {
        self::$perm_version_loaded = false;
        self::$perm_version_cache = null;
    }

    /**
     * Rebuild the user_module_permissions cache rows for a single user from their current
     * role_permissions, then bump the global version so live session maps refresh. Call
     * after any change to a user's role assignment (user_roles writes). This is the
     * user-keyed counterpart to Roles::sync_user_permissions_for_role() (role-keyed),
     * keeping the cache correct on the user side as well as the role side.
     *
     * Non-override rows only are replaced, matching the role-side sync so manual overrides
     * (has_override = 1) survive. Best-effort: failures are logged, never fatal.
     *
     * @param int $user_id
     * @return void
     */
    public function rebuild_user_module_permissions($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return;
        }

        try {
            if (!$this->CI->db->table_exists('user_module_permissions')) {
                return;
            }

            $has_override = $this->CI->db->field_exists('has_override', 'user_module_permissions');
            $has_created_at = $this->CI->db->field_exists('created_at', 'user_module_permissions');
            $has_updated_at = $this->CI->db->field_exists('updated_at', 'user_module_permissions');

            $this->CI->db->trans_start();

            // Drop the cache rows we own (never the manual overrides).
            if ($has_override) {
                $this->CI->db->where('user_id', $user_id);
                $this->CI->db->group_start()
                    ->where('has_override', 0)
                    ->or_where('has_override IS NULL', null, false)
                    ->group_end();
                $this->CI->db->delete('user_module_permissions');
            } else {
                $this->CI->db->where('user_id', $user_id);
                $this->CI->db->delete('user_module_permissions');
            }

            $rows = $this->CI->db->query("
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
                INNER JOIN roles r ON r.id = ur.role_id AND r.is_active = 1
                INNER JOIN role_permissions rp ON rp.role_id = ur.role_id
                INNER JOIN modules m ON m.id = rp.module_id AND m.is_active = 1
                WHERE ur.user_id = ?
                GROUP BY ur.user_id, m.id, m.name, m.display_name, m.controller, m.parent_id
            ", [$user_id])->result_array();

            if (!empty($rows)) {
                $columns = [
                    'user_id', 'module_id', 'module_name', 'module_display_name',
                    'controller', 'parent_id', 'can_view', 'can_create', 'can_edit',
                    'can_delete', 'can_approve',
                ];
                if ($has_override) {
                    $columns[] = 'has_override';
                }
                if ($has_created_at) {
                    $columns[] = 'created_at';
                }
                if ($has_updated_at) {
                    $columns[] = 'updated_at';
                }

                $now = date('Y-m-d H:i:s');
                $batch = [];
                foreach ($rows as $row) {
                    $payload = [
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
                        'can_approve' => (int) $row['can_approve'],
                    ];
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
                }

                $this->CI->db->insert_batch('user_module_permissions', $batch, $columns);
            }

            $this->CI->db->trans_complete();

            // Refresh live session maps that were built before this change.
            $this->bump_permission_version();
        } catch (Exception $e) {
            log_message('error', 'rebuild_user_module_permissions failed for user ' . $user_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Build a compact permission map resolved through the SAME path the runtime DB checks
     * use — cache table (user_module_permissions) with a live role_permissions fallback for
     * any module the cache is missing, plus the admin auto-grant — over the full set of
     * active modules. Resolving the full module universe (not just rows present in the
     * cache) is what keeps the session map at parity with the DB path: an empty or stale
     * cache no longer denies access a user's role grants. Still fail-closed: a module the
     * user has no grant for ends up all-false (deny).
     */
    private function build_permission_map($user_id)
    {
        $modules = [];
        $controllers = [];

        // Module universe + their controllers, mirroring the modules table the fallback
        // (load_fallback_permissions_batch) joins on. is_active filtering is applied again
        // by get_permissions_for_modules(), so inactive modules drop out of the result.
        $module_controllers = $this->active_module_controllers();

        if (!empty($module_controllers)) {
            $resolved = $this->get_permissions_for_modules($user_id, array_keys($module_controllers));
            foreach ($resolved as $name => $actions) {
                $caps = [
                    'view'    => !empty($actions['view']) ? 1 : 0,
                    'create'  => !empty($actions['create']) ? 1 : 0,
                    'edit'    => !empty($actions['edit']) ? 1 : 0,
                    'delete'  => !empty($actions['delete']) ? 1 : 0,
                    'approve' => !empty($actions['approve']) ? 1 : 0,
                ];
                $modules[$name] = $caps;

                $controller = $module_controllers[$name] ?? '';
                if ($controller !== '') {
                    $any = $caps['view'] || $caps['create'] || $caps['edit'] || $caps['delete'];
                    $controllers[$controller] = !empty($controllers[$controller]) ? true : (bool) $any;
                }
            }
        }

        return [
            'user_id'     => (int) $user_id,
            'is_admin'    => $this->user_is_admin($user_id),
            'modules'     => $modules,
            'controllers' => $controllers,
            'version'     => $this->current_permission_version(),
            'built_at'    => time(),
        ];
    }

    /**
     * Map of active module_name => controller, sourced from the modules table (the same
     * universe the role_permissions fallback joins on) and merged with the sidebar
     * registry so registry-only entries are not lost. Returns an empty array on failure,
     * so build_permission_map() degrades to an empty (fail-closed) map rather than erroring
     * at login — callers then fall back to the DB path.
     */
    private function active_module_controllers()
    {
        $map = [];

        try {
            $rows = $this->CI->db->query(
                "SELECT name, controller FROM modules WHERE is_active = 1"
            )->result_array();
            foreach ($rows as $row) {
                $name = $row['name'] ?? '';
                if ($name !== '') {
                    $map[$name] = $row['controller'] ?? '';
                }
            }
        } catch (Exception $e) {
            // Fall through to the registry-only view below.
        }

        if (function_exists('sidebar_registry')) {
            foreach (sidebar_registry() as $name => $meta) {
                if ($name === '' || isset($map[$name])) {
                    continue;
                }
                if (array_key_exists('is_active', $meta) && (int) $meta['is_active'] !== 1) {
                    continue;
                }
                $map[$name] = $meta['controller'] ?? '';
            }
        }

        return $map;
    }

    /**
     * Whether the user holds a super_admin/admin role — mirrors the admin auto-grant
     * in fallback_permission_check() so the session map grants the same access.
     */
    private function user_is_admin($user_id)
    {
        try {
            $roles = $this->CI->mymodel->selectWithQuery(
                "SELECT r.name FROM user_roles ur
                 INNER JOIN roles r ON ur.role_id = r.id
                 WHERE ur.user_id = " . (int) $user_id . " AND r.is_active = 1"
            );
            foreach ((array) $roles as $role) {
                if (in_array(strtolower($role['name'] ?? ''), ['super_admin', 'admin'], true)) {
                    return true;
                }
            }
        } catch (Exception $e) {
            // Unknown — treat as non-admin (fail-closed).
        }
        return false;
    }

    /**
     * Return the session permission map only for the currently logged-in user, or null
     * when there is no usable map (so callers fall back to the DB path). Rebuilds the
     * map once it passes SESSION_PERM_TTL.
     */
    private function session_permission_map($user_id)
    {
        if (empty($_SESSION[self::SESSION_PERM_KEY]) || !is_array($_SESSION[self::SESSION_PERM_KEY])) {
            return null;
        }
        $map = $_SESSION[self::SESSION_PERM_KEY];

        // Only trust the map for the user it was built for — never for arbitrary ids.
        if ((int) ($map['user_id'] ?? 0) !== (int) $user_id) {
            return null;
        }

        // B3: instant invalidation — rebuild when the global permission version moved.
        $current_version = $this->current_permission_version();
        $stored_version = $map['version'] ?? null;
        if ($current_version !== null && $stored_version !== null && (int) $stored_version !== (int) $current_version) {
            return $this->bootstrap_session_permissions($user_id);
        }

        // Secondary safety: rebuild past the TTL (also covers a null/unknown version).
        if (time() - (int) ($map['built_at'] ?? 0) > self::SESSION_PERM_TTL) {
            $map = $this->bootstrap_session_permissions($user_id);
        }

        return $map;
    }

    /**
     * Resolve check_permission() from the session map. Returns bool when the map serves
     * the decision, or null when there is no map (caller should hit the DB).
     */
    private function check_permission_from_session($user_id, $module_name, $action)
    {
        $map = $this->session_permission_map($user_id);
        if ($map === null) {
            return null;
        }
        if (!empty($map['is_admin'])) {
            return true;
        }
        if (isset($map['modules'][$module_name]) && array_key_exists($action, $map['modules'][$module_name])) {
            return (bool) $map['modules'][$module_name][$action];
        }
        if ($action === 'view' && in_array($module_name, ['profile', 'home'], true)) {
            return true;
        }
        return false;
    }

    /**
     * Resolve has_module_access() from the session map. Returns bool when served, or
     * null when there is no map (caller should hit the DB).
     */
    private function has_controller_access_from_session($user_id, $controller)
    {
        $map = $this->session_permission_map($user_id);
        if ($map === null) {
            return null;
        }
        if (!empty($map['is_admin'])) {
            return true;
        }
        if (!empty($map['controllers'][$controller])) {
            return true;
        }
        if (in_array($controller, ['profile', 'home'], true)) {
            return true;
        }
        return false;
    }

    /**
     * Check if user has specific permission for a module
     *
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action (view, create, edit, delete)
     * @return bool
     */
    public function check_permission($user_id, $module_name, $action = 'view')
    {
        // Cache key for performance
        $cache_key = "{$user_id}_{$module_name}_{$action}";
        
        if (isset($this->user_permissions_cache[$cache_key])) {
            return $this->user_permissions_cache[$cache_key];
        }

        if (!$this->is_module_active($module_name)) {
            $this->user_permissions_cache[$cache_key] = false;
            return false;
        }

        // B2: serve from the session permission map (logged-in user, no DB) when available.
        $session_value = $this->check_permission_from_session($user_id, $module_name, $action);
        if ($session_value !== null) {
            $this->user_permissions_cache[$cache_key] = $session_value;
            return $session_value;
        }

        $permissions = $this->get_permissions_for_modules($user_id, [$module_name]);
        if (isset($permissions[$module_name]) && array_key_exists($action, $permissions[$module_name])) {
            return $permissions[$module_name][$action];
        }

        $has_permission = $this->fallback_permission_check($user_id, $module_name, $action);
        $this->cache_module_permissions($user_id, $module_name, [
            'view' => $action === 'view' ? $has_permission : false,
            'create' => $action === 'create' ? $has_permission : false,
            'edit' => $action === 'edit' ? $has_permission : false,
            'delete' => $action === 'delete' ? $has_permission : false,
            'approve' => $action === 'approve' ? $has_permission : false,
        ]);

        return $has_permission;
    }
    
    /**
     * Check if user has access to a controller (any permission)
     * 
     * @param int $user_id User ID
     * @param string $controller Controller name
     * @return bool
     */
    public function has_module_access($user_id, $controller)
    {
        if (!$this->is_module_active($controller)) {
            return false;
        }

        // B2: serve from the session permission map (logged-in user, no DB) when available.
        $session_value = $this->has_controller_access_from_session($user_id, $controller);
        if ($session_value !== null) {
            return $session_value;
        }

        try {
            $capabilities = $this->get_permission_table_capabilities();
            if ($capabilities['cache_table']) {
                $result = $this->CI->db->query("
                    SELECT COUNT(*) as count
                    FROM user_module_permissions
                    WHERE user_id = ?
                    AND controller = ?
                    AND (can_view = 1 OR can_create = 1 OR can_edit = 1 OR can_delete = 1)
                ", [(int) $user_id, $controller])->row_array();

                return !empty($result) && (int) $result['count'] > 0;
            }
        } catch (Exception $e) {
            // Fall through to the role-based fallback.
        }

        // Fallback to role-based check
        return $this->fallback_permission_check($user_id, $controller, 'view');
    }

    /**
     * Bulk-load permissions for a set of modules and hydrate the request cache.
     *
     * @param int $user_id
     * @param array $modules
     * @return array<string, array<string, bool>>
     */
    public function get_permissions_for_modules($user_id, array $modules)
    {
        $module_names = $this->filter_active_module_names($modules);
        if (empty($module_names)) {
            return [];
        }

        $this->prime_permissions_cache($user_id, $module_names);

        $permissions = [];
        foreach ($module_names as $module_name) {
            $permissions[$module_name] = $this->get_cached_permissions_for_module($user_id, $module_name);
        }

        return $permissions;
    }
    
    /**
     * Get all permissions for a user
     * 
     * @param int $user_id User ID
     * @return array
     */
    public function get_user_permissions($user_id)
    {
        try {
            $permissions = $this->CI->db->query("
                SELECT 
                    module_name,
                    module_display_name,
                    controller,
                    parent_id,
                    can_view,
                    can_create,
                    can_edit,
                    can_delete,
                    can_approve,
                    has_override
                FROM user_module_permissions 
                WHERE user_id = ?
                AND (can_view = 1 OR can_create = 1 OR can_edit = 1 OR can_delete = 1 OR can_approve = 1)
                ORDER BY module_name
            ", [(int) $user_id])->result_array();
            return array_values(array_filter($permissions, function ($perm) {
                return $this->is_module_active($perm['module_name'] ?? '');
            }));
        } catch (Exception $e) {
            // Return basic permissions for fallback
            return [];
        }
    }
    
    /**
     * Get user's accessible modules for sidebar
     * 
     * @param int $user_id User ID
     * @return array Hierarchical module structure
     */
    public function get_user_sidebar_modules($user_id)
    {
        $permissions = $this->CI->db->query("
            SELECT 
                m.id,
                m.name,
                m.display_name,
                m.controller,
                m.icon,
                m.parent_id,
                m.sort_order,
                ump.can_view,
                ump.can_create,
                ump.can_edit,
                ump.can_delete
            FROM modules m
            LEFT JOIN user_module_permissions ump ON m.id = ump.module_id AND ump.user_id = ?
            WHERE m.is_active = 1 
            AND (ump.can_view = 1 OR ump.can_create = 1 OR ump.can_edit = 1 OR ump.can_delete = 1)
            ORDER BY m.sort_order, m.display_name
        ", [(int) $user_id])->result_array();

        $permissions = array_values(array_filter($permissions, function ($module) {
            return $this->is_module_active($module['name'] ?? '');
        }));

        return $this->build_module_tree($permissions);
    }
    
    /**
     * Build hierarchical module tree
     * 
     * @param array $modules Flat module array
     * @param int $parent_id Parent ID
     * @return array
     */
    private function build_module_tree($modules, $parent_id = null)
    {
        $tree = [];
        
        foreach ($modules as $module) {
            if ($module['parent_id'] == $parent_id) {
                $module['children'] = $this->build_module_tree($modules, $module['id']);
                $tree[] = $module;
            }
        }
        
        return $tree;
    }
    
    /**
     * Check if user can perform action on current controller
     * Uses current CI controller and method
     * 
     * @param int $user_id User ID
     * @param string $action Permission action
     * @return bool
     */
    public function can_access_current($user_id, $action = 'view')
    {
        $controller = $this->CI->router->fetch_class();
        
        // Map controller to module name
        $module_mapping = $this->get_controller_module_mapping();
        $module_name = isset($module_mapping[$controller]) ? $module_mapping[$controller] : $controller;
        
        return $this->check_permission($user_id, $module_name, $action);
    }
    
    /**
     * Check if permission tables exist
     * 
     * @return bool
     */
    private function permission_tables_exist()
    {
        return $this->get_permission_table_capabilities()['fallback_tables'];
    }

    private function get_permission_table_capabilities()
    {
        if (self::$permission_table_capabilities_cache !== null) {
            return self::$permission_table_capabilities_cache;
        }

        if ($this->permission_table_capabilities !== null) {
            return $this->permission_table_capabilities;
        }

        $capabilities = [
            'cache_table' => false,
            'fallback_tables' => false,
        ];

        // Production short-circuit: when the RBAC tables are known to exist, skip the
        // per-request INFORMATION_SCHEMA probe entirely. The static cache only lives one
        // PHP request, so without this the probe runs on every request (29.7s across the
        // 2026-06-20 load test). The five RBAC tables never disappear at runtime, so once
        // the schema is provisioned this flag is safe. Leave it unset on fresh/partial
        // installs to keep the auto-detecting probe as the default.
        //
        // Read via getenv() rather than the global env() helper: env_helper.php putenv()s
        // every .env value, while in other contexts (e.g. the test container) env() can
        // resolve to a different framework's helper (illuminate/support) whose own
        // dependencies are not installed, throwing during the probe.
        $flag = strtolower(trim((string) getenv('PERMISSION_TABLES_READY')));
        if ($flag === 'true' || $flag === '1') {
            $capabilities = [
                'cache_table' => true,
                'fallback_tables' => true,
            ];
            $this->permission_table_capabilities = $capabilities;
            self::$permission_table_capabilities_cache = $capabilities;
            return $this->permission_table_capabilities;
        }

        try {
            $rows = $this->CI->db->query("
                SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME IN ('user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles')
            ")->result_array();

            $tables = [];
            foreach ($rows as $row) {
                $tables[$row['TABLE_NAME']] = true;
            }

            $capabilities['cache_table'] = isset($tables['user_module_permissions']);
            $capabilities['fallback_tables'] = isset($tables['modules'])
                && isset($tables['roles'])
                && isset($tables['role_permissions'])
                && isset($tables['user_roles']);
        } catch (Exception $e) {
            $capabilities = [
                'cache_table' => false,
                'fallback_tables' => false,
            ];
        }

        $this->permission_table_capabilities = $capabilities;
        self::$permission_table_capabilities_cache = $capabilities;

        return $this->permission_table_capabilities;
    }

    /**
     * Clear the request-lifetime capability cache. In production the static cache is
     * reset naturally each request (PHP process boundary); tests that run many cases in
     * one process must reset it between cases to keep request isolation.
     */
    public static function resetTableCapabilityCache(): void
    {
        self::$permission_table_capabilities_cache = null;
    }

    private function filter_active_module_names(array $modules)
    {
        $module_names = [];
        foreach ($modules as $module_name) {
            $module_name = trim((string) $module_name);
            if ($module_name === '') {
                continue;
            }
            if (!$this->is_module_active($module_name)) {
                continue;
            }
            $module_names[$module_name] = true;
        }

        return array_keys($module_names);
    }

    private function prime_permissions_cache($user_id, array $modules)
    {
        $uncached_modules = [];
        foreach ($modules as $module_name) {
            if (!$this->is_module_cached($user_id, $module_name)) {
                $uncached_modules[] = $module_name;
            }
        }

        if (empty($uncached_modules)) {
            return;
        }

        $capabilities = $this->get_permission_table_capabilities();
        $missing_modules = $uncached_modules;

        if ($capabilities['cache_table']) {
            $loaded_permissions = $this->load_cached_module_permissions($user_id, $uncached_modules);
            foreach ($loaded_permissions as $module_name => $actions) {
                $this->cache_module_permissions($user_id, $module_name, $actions);
            }

            $missing_modules = array_values(array_diff($uncached_modules, array_keys($loaded_permissions)));
        }

        if (empty($missing_modules)) {
            return;
        }

        if ($capabilities['fallback_tables']) {
            $fallback_permissions = $this->load_fallback_permissions_batch($user_id, $missing_modules);
            foreach ($fallback_permissions as $module_name => $actions) {
                $this->cache_module_permissions($user_id, $module_name, $actions);
            }
            $this->log_fallback_batch($user_id, $missing_modules);
            return;
        }

        foreach ($missing_modules as $module_name) {
            $this->cache_module_permissions($user_id, $module_name, [
                'view' => $this->fallback_permission_check($user_id, $module_name, 'view'),
                'create' => $this->fallback_permission_check($user_id, $module_name, 'create'),
                'edit' => $this->fallback_permission_check($user_id, $module_name, 'edit'),
                'delete' => $this->fallback_permission_check($user_id, $module_name, 'delete'),
                'approve' => $this->fallback_permission_check($user_id, $module_name, 'approve'),
            ]);
        }
    }

    private function is_module_cached($user_id, $module_name)
    {
        foreach (['view', 'create', 'edit', 'delete', 'approve'] as $action) {
            if (!array_key_exists($this->build_cache_key($user_id, $module_name, $action), $this->user_permissions_cache)) {
                return false;
            }
        }

        return true;
    }

    private function build_cache_key($user_id, $module_name, $action)
    {
        return "{$user_id}_{$module_name}_{$action}";
    }

    private function get_cached_permissions_for_module($user_id, $module_name)
    {
        $permissions = [];
        foreach (['view', 'create', 'edit', 'delete', 'approve'] as $action) {
            $permissions[$action] = (bool) ($this->user_permissions_cache[$this->build_cache_key($user_id, $module_name, $action)] ?? false);
        }

        return $permissions;
    }

    private function cache_module_permissions($user_id, $module_name, array $permissions)
    {
        foreach (['view', 'create', 'edit', 'delete', 'approve'] as $action) {
            $this->user_permissions_cache[$this->build_cache_key($user_id, $module_name, $action)] = (bool) ($permissions[$action] ?? false);
        }
    }

    private function load_cached_module_permissions($user_id, array $modules)
    {
        if (empty($modules)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($modules), '?'));
        $params = array_merge([(int) $user_id], array_values($modules));
        $rows = $this->CI->db->query("
            SELECT
                module_name,
                can_view,
                can_create,
                can_edit,
                can_delete,
                can_approve
            FROM user_module_permissions
            WHERE user_id = ?
            AND module_name IN ($placeholders)
        ", $params)->result_array();

        $permissions = [];
        foreach ($rows as $row) {
            $permissions[$row['module_name']] = [
                'view' => (int) $row['can_view'] === 1,
                'create' => (int) $row['can_create'] === 1,
                'edit' => (int) $row['can_edit'] === 1,
                'delete' => (int) $row['can_delete'] === 1,
                'approve' => (int) $row['can_approve'] === 1,
            ];
        }

        return $permissions;
    }

    private function load_fallback_permissions_batch($user_id, array $modules)
    {
        $module_names = array_values(array_unique($modules));
        if (empty($module_names)) {
            return [];
        }

        $permissions = [];
        foreach ($module_names as $module_name) {
            $permissions[$module_name] = [
                'view' => false,
                'create' => false,
                'edit' => false,
                'delete' => false,
                'approve' => false,
            ];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($module_names), '?'));
            $params = array_merge([(int) $user_id], $module_names);
            $rows = $this->CI->db->query("
                SELECT
                    m.name AS module_name,
                    MAX(rp.can_view) AS can_view,
                    MAX(rp.can_create) AS can_create,
                    MAX(rp.can_edit) AS can_edit,
                    MAX(rp.can_delete) AS can_delete,
                    MAX(rp.can_approve) AS can_approve
                FROM user_roles ur
                INNER JOIN roles r ON ur.role_id = r.id AND r.is_active = 1
                INNER JOIN role_permissions rp ON r.id = rp.role_id
                INNER JOIN modules m ON rp.module_id = m.id AND m.is_active = 1
                WHERE ur.user_id = ?
                AND m.name IN ($placeholders)
                GROUP BY m.name
            ", $params)->result_array();

            foreach ($rows as $row) {
                $permissions[$row['module_name']] = [
                    'view' => (int) $row['can_view'] === 1,
                    'create' => (int) $row['can_create'] === 1,
                    'edit' => (int) $row['can_edit'] === 1,
                    'delete' => (int) $row['can_delete'] === 1,
                    'approve' => (int) $row['can_approve'] === 1,
                ];
            }

            $role_rows = $this->CI->db->query("
                SELECT r.name
                FROM user_roles ur
                INNER JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = ? AND r.is_active = 1
            ", [(int) $user_id])->result_array();

            $is_admin = false;
            foreach ($role_rows as $role) {
                if (in_array(strtolower($role['name']), ['super_admin', 'admin'], true)) {
                    $is_admin = true;
                    break;
                }
            }

            foreach ($permissions as $module_name => $actions) {
                if ($is_admin) {
                    $permissions[$module_name] = [
                        'view' => true,
                        'create' => true,
                        'edit' => true,
                        'delete' => true,
                        'approve' => true,
                    ];
                    continue;
                }

                if ($module_name === 'profile' || $module_name === 'home') {
                    $permissions[$module_name]['view'] = true;
                }
            }

            return $permissions;
        } catch (Exception $e) {
            foreach ($module_names as $module_name) {
                $permissions[$module_name] = [
                    'view' => $this->fallback_permission_check($user_id, $module_name, 'view'),
                    'create' => $this->fallback_permission_check($user_id, $module_name, 'create'),
                    'edit' => $this->fallback_permission_check($user_id, $module_name, 'edit'),
                    'delete' => $this->fallback_permission_check($user_id, $module_name, 'delete'),
                    'approve' => $this->fallback_permission_check($user_id, $module_name, 'approve'),
                ];
            }

            return $permissions;
        }
    }

    private function log_fallback_batch($user_id, array $modules)
    {
        sort($modules);
        $signature = $user_id . ':' . implode(',', $modules);
        if (isset($this->logged_fallback_batches[$signature])) {
            return;
        }

        $this->logged_fallback_batches[$signature] = true;
        log_message(
            'info',
            'Permission cache fallback batch used for user ' . (int) $user_id . ' modules=' . implode(',', $modules)
        );
    }

    /**
     * Fallback permission check when view doesn't exist
     * Queries role_permissions directly via user_roles
     *
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action
     * @return bool
     */
    private function fallback_permission_check($user_id, $module_name, $action)
    {
        try {
            // Query user permissions through role_permissions table
            $result = $this->CI->mymodel->selectWithQuery("
                SELECT MAX(rp.can_{$action}) as has_permission
                FROM user u
                INNER JOIN user_roles ur ON u.id = ur.user_id
                INNER JOIN roles r ON ur.role_id = r.id AND r.is_active = 1
                INNER JOIN role_permissions rp ON r.id = rp.role_id
                INNER JOIN modules m ON rp.module_id = m.id AND m.is_active = 1
                WHERE u.id = $user_id
                AND m.name = '$module_name'
                GROUP BY u.id, m.name
            ");

            if (!empty($result) && isset($result[0]['has_permission'])) {
                return $result[0]['has_permission'] == 1;
            }

            // If no specific permission found, check if user has admin role
            $user_roles = $this->CI->mymodel->selectWithQuery("
                SELECT r.name, r.level
                FROM user_roles ur
                INNER JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = $user_id AND r.is_active = 1
            ");

            // Super admin and admin roles get full access
            foreach ($user_roles as $role) {
                if (in_array(strtolower($role['name']), ['super_admin', 'admin'])) {
                    return true;
                }
            }

            // Basic modules everyone can view
            $basic_modules = ['profile', 'home'];
            if (in_array($module_name, $basic_modules) && $action === 'view') {
                return true;
            }

            return false;

        } catch (Exception $e) {
            // Last resort: use old role-based system
            $user = $this->CI->mymodel->selectWithQuery("
                SELECT role FROM user WHERE id = $user_id LIMIT 1
            ");

            if (empty($user)) {
                return false;
            }

            $role = $user[0]['role'];

            // Legacy role IDs: HR/Admin roles (1, 2, 7) get full access
            if (in_array($role, ['1', '2', '7'])) {
                return true;
            }

            // Basic modules everyone can view
            $basic_modules = ['profile', 'quest'];
            if (in_array($module_name, $basic_modules) && $action === 'view') {
                return true;
            }

            return false;
        }
    }

    /**
     * Get controller to module name mapping
     * 
     * @return array
     */
    private function get_controller_module_mapping()
    {
        return [
            'admin/attendancesettingscontroller' => 'admin_attendance_settings',
            'admin/holidayscontroller' => 'admin_holidays',
            'admin/leavetypescontroller' => 'admin_leave_types',
            'admin/offices' => 'admin_offices',
            'ads' => 'advertiser', // Special handling for ads with parameters
            'approvals/leaveapprovalcontroller' => 'leave_approvals',
            'attendancepagecontroller' => 'attendance',
            'attendancereport' => 'attendance_report',
            'announcement' => 'announcement',
            'benefit' => 'benefit',
            'calendar' => 'calendar',
            'codeboost' => 'codeboost',
            'crm' => 'crm_mg', // Default, may need brand parameter handling
            'customer' => 'customer',
            'dashboard' => 'dashboard',
            'discount' => 'discount',
            'endorse_campaign' => 'endorse_campaign',
            'expense' => 'expense',
            'group_wa' => 'group_wa',
            'influencer' => 'influencer',
            'influencer_dummy' => 'influencer_dummy',
            'label' => 'label',
            'leavecontroller' => 'leave',
            'marketplace' => 'marketplace',
            'marketplace_account' => 'marketplace_account',
            'meta_account' => 'meta_account',
            'milestone' => 'milestone',
            'overview' => 'overview',
            'overtime' => 'overtime',
            'payment' => 'payment',
            'position' => 'position',
            'product' => 'product',
            'product_3rd' => 'product_3rd',
            'profile' => 'profile',
            'quest' => 'quest',
            'quest_level' => 'quest_level',
            'report' => 'report',
            'scraper' => 'scraper',
            'shipping' => 'shipping',
            'stock' => 'stock',
            'testimoni' => 'testimoni',
            'transaction' => 'transaction',
            'transaction_item' => 'transaction_item',
            'user' => 'user'
        ];
    }
    
    /**
     * Check specific ads module permission based on marketplace parameter
     * 
     * @param int $user_id User ID
     * @param string $marketplace Marketplace (tiktok, meta, shopee, lazada)
     * @param string $action Permission action
     * @return bool
     */
    public function check_ads_permission($user_id, $marketplace, $action = 'view')
    {
        $module_name = 'ads_' . strtolower($marketplace);
        return $this->check_permission($user_id, $module_name, $action);
    }
    
    /**
     * Check CRM permission based on brand parameter
     * 
     * @param int $user_id User ID
     * @param string $brand Brand (MG, POME)
     * @param string $action Permission action
     * @return bool
     */
    public function check_crm_permission($user_id, $brand, $action = 'view')
    {
        $module_name = 'crm_' . strtolower($brand);
        return $this->check_permission($user_id, $module_name, $action);
    }
    
    /**
     * Enforce permission check - redirect if no access
     * 
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action
     * @param string $redirect_url Redirect URL on failure
     */
    public function enforce_permission($user_id, $module_name, $action = 'view', $redirect_url = null)
    {
        if (!$this->check_permission($user_id, $module_name, $action)) {
            if (!$redirect_url) {
                $redirect_url = base_url() . 'dashboard';
            }
            redirect($redirect_url);
        }
    }
    
    /**
     * Show 403 error page for permission denied
     * Alternative to enforce_permission that shows error page instead of redirect
     * 
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action
     * @param array $data Additional data to pass to error page
     */
    public function show_403_if_no_permission($user_id, $module_name, $action = 'view', $data = [])
    {
        if (!$this->check_permission($user_id, $module_name, $action)) {
            // Prepare data for the error page
            $error_data = array_merge([
                'heading' => 'Access Forbidden',
                'message' => 'You do not have permission to access this resource.',
                'module' => $module_name,
                'action' => $action,
                'user_id' => $user_id
            ], $data);

            // Echo the view directly so output is not lost when exit is called
            http_response_code(403);
            echo $this->CI->load->view('errors/html/error_403', $error_data, TRUE);
            exit;
        }
    }
    
    /**
     * Enhanced enforce permission with option to show 403 page
     * 
     * @param int $user_id User ID
     * @param string $module_name Module name
     * @param string $action Permission action
     * @param bool $show_403 Whether to show 403 page instead of redirect
     * @param string $redirect_url Redirect URL on failure (if not showing 403)
     * @param array $error_data Additional data for 403 page
     */
    public function enforce_permission_with_403($user_id, $module_name, $action = 'view', $show_403 = false, $redirect_url = null, $error_data = [])
    {
        if (!$this->check_permission($user_id, $module_name, $action)) {
            if ($show_403) {
                $this->show_403_if_no_permission($user_id, $module_name, $action, $error_data);
            } else {
                if (!$redirect_url) {
                    $redirect_url = base_url() . 'dashboard';
                }
                redirect($redirect_url);
            }
        }
    }
    
    /**
     * Clear permission cache for user
     * 
     * @param int $user_id User ID
     */
    public function clear_user_cache($user_id = null)
    {
        if ($user_id) {
            foreach (array_keys($this->user_permissions_cache) as $key) {
                if (strpos($key, $user_id . '_') === 0) {
                    unset($this->user_permissions_cache[$key]);
                }
            }
        } else {
            $this->user_permissions_cache = [];
        }

        // Drop the session map for the current user so the next check rebuilds it.
        if (
            isset($_SESSION[self::SESSION_PERM_KEY]['user_id'])
            && ($user_id === null || (int) $_SESSION[self::SESSION_PERM_KEY]['user_id'] === (int) $user_id)
        ) {
            $this->clear_session_permissions();
        }
    }
    
    /**
     * Get all positions with their permission counts
     * For management interface
     * 
     * @return array
     */
    public function get_positions_with_permissions()
    {
        return $this->CI->mymodel->selectWithQuery("
            SELECT 
                p.id,
                p.name as position_name,
                ql.name as quest_level_name,
                ql.level_order,
                COUNT(perm.id) as total_permissions,
                SUM(perm.can_view) as view_permissions,
                SUM(perm.can_create) as create_permissions,
                SUM(perm.can_edit) as edit_permissions,
                SUM(perm.can_delete) as delete_permissions
            FROM positions p
            JOIN quest_levels ql ON p.level_id = ql.id
            LEFT JOIN permissions perm ON p.id = perm.position_id
            GROUP BY p.id, p.name, ql.name, ql.level_order
            ORDER BY ql.level_order, p.name
        ");
    }
    
    /**
     * Get permission matrix for a specific position
     * 
     * @param int $position_id Position ID
     * @return array
     */
    public function get_position_permissions($position_id)
    {
        return $this->CI->mymodel->selectWithQuery("
            SELECT 
                m.id as module_id,
                m.name as module_name,
                m.display_name,
                m.parent_id,
                COALESCE(p.can_view, 0) as can_view,
                COALESCE(p.can_create, 0) as can_create,
                COALESCE(p.can_edit, 0) as can_edit,
                COALESCE(p.can_delete, 0) as can_delete
            FROM modules m
            LEFT JOIN permissions p ON m.id = p.module_id AND p.position_id = ?
            WHERE m.is_active = 1
            ORDER BY m.sort_order, m.display_name
        ", [$position_id]);
    }
    
    /**
     * Update position permissions
     * 
     * @param int $position_id Position ID
     * @param array $permissions Permission data
     * @return bool
     */
    public function update_position_permissions($position_id, $permissions)
    {
        // Start transaction
        $this->CI->db->trans_start();
        
        try {
            // Delete existing permissions for this position
            $this->CI->mymodel->deleteData('permissions', ['position_id' => $position_id]);
            
            // Insert new permissions
            foreach ($permissions as $module_id => $perms) {
                if (isset($perms['can_view']) || isset($perms['can_create']) || 
                    isset($perms['can_edit']) || isset($perms['can_delete'])) {
                    
                    $data = [
                        'position_id' => $position_id,
                        'module_id' => $module_id,
                        'can_view' => isset($perms['can_view']) ? 1 : 0,
                        'can_create' => isset($perms['can_create']) ? 1 : 0,
                        'can_edit' => isset($perms['can_edit']) ? 1 : 0,
                        'can_delete' => isset($perms['can_delete']) ? 1 : 0
                    ];
                    
                    $this->CI->mymodel->insertData('permissions', $data);
                }
            }
            
            $this->CI->db->trans_complete();
            
            if ($this->CI->db->trans_status() === FALSE) {
                return false;
            }
            
            // Clear cache
            $this->clear_user_cache();
            
            return true;
            
        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            return false;
        }
    }
}
