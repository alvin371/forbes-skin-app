<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

if (! function_exists('log_message')) {
    function log_message($level, $message)
    {
        return null;
    }
}

if (! class_exists('CI_Model')) {
    class CI_Model
    {
    }
}

/**
 * @internal
 */
final class PermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];

        if (! function_exists('get_instance')) {
            function &get_instance()
            {
                return $GLOBALS['__permission_test_ci'];
            }
        }
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Seed a fresh session permission map for $userId.
     */
    private function seedSessionMap(int $userId, array $modules, array $controllers, bool $isAdmin = false, ?int $version = null): void
    {
        $map = [
            'user_id'     => $userId,
            'is_admin'    => $isAdmin,
            'modules'     => $modules,
            'controllers' => $controllers,
            'built_at'    => time(),
        ];
        if ($version !== null) {
            $map['version'] = $version;
        }
        $_SESSION[Permission::SESSION_PERM_KEY] = $map;
    }

    public function testSessionMapServesChecksWithoutDb(): void
    {
        $db         = new PermissionTestDb(['tables' => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles']]);
        $permission = $this->makePermission($db);

        $this->seedSessionMap(7, [
            'dashboard' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0, 'approve' => 0],
        ], ['dashboard' => true]);

        $this->assertTrue($permission->check_permission(7, 'dashboard', 'view'));
        $this->assertFalse($permission->check_permission(7, 'dashboard', 'create'));
        $this->assertTrue($permission->has_module_access(7, 'dashboard'));

        // No RBAC tables were touched — served entirely from session.
        $this->assertSame(0, $db->infoSchemaQueries);
        $this->assertSame(0, $db->userModulePermissionQueries);
        $this->assertSame(0, $db->fallbackAggregateQueries);
    }

    public function testSessionMapIsFailClosedForUnknownModule(): void
    {
        $db         = new PermissionTestDb(['tables' => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles']]);
        $permission = $this->makePermission($db);

        $this->seedSessionMap(7, [
            'dashboard' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0, 'approve' => 0],
        ], ['dashboard' => true]);

        $this->assertFalse($permission->check_permission(7, 'report', 'view'));
        $this->assertFalse($permission->has_module_access(7, 'report'));
        $this->assertSame(0, $db->infoSchemaQueries);
    }

    public function testSessionMapAdminAutoGrants(): void
    {
        $db         = new PermissionTestDb(['tables' => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles']]);
        $permission = $this->makePermission($db);

        $this->seedSessionMap(1, [], [], true);

        $this->assertTrue($permission->check_permission(1, 'anything', 'delete'));
        $this->assertTrue($permission->has_module_access(1, 'whatever'));
        $this->assertSame(0, $db->infoSchemaQueries);
    }

    public function testSessionMapGrantsBasicProfileView(): void
    {
        $db         = new PermissionTestDb(['tables' => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles']]);
        $permission = $this->makePermission($db);

        $this->seedSessionMap(7, [], []);

        $this->assertTrue($permission->check_permission(7, 'profile', 'view'));
        $this->assertTrue($permission->has_module_access(7, 'profile'));
        $this->assertFalse($permission->check_permission(7, 'profile', 'edit'));
    }

    public function testSessionMapIsNotUsedForADifferentUser(): void
    {
        $db = new PermissionTestDb([
            'tables'     => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles'],
            'cache_rows' => [
                ['module_name' => 'dashboard', 'can_view' => 1, 'can_create' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
            ],
        ]);
        $permission = $this->makePermission($db);

        // Map belongs to user 7; a check for user 9 must ignore it and hit the DB.
        $this->seedSessionMap(7, [
            'dashboard' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0, 'approve' => 0],
        ], ['dashboard' => true]);

        $this->assertTrue($permission->check_permission(9, 'dashboard', 'view'));
        $this->assertSame(1, $db->userModulePermissionQueries);
    }

    public function testBootstrapBuildsSessionMapFromDb(): void
    {
        $db = new PermissionTestDb([
            'tables'           => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles'],
            'user_permissions' => [
                ['module_name' => 'influencer', 'controller' => 'influencer', 'can_view' => 1, 'can_create' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
                ['module_name' => 'crm', 'controller' => 'crm', 'can_view' => 1, 'can_create' => 1, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
            ],
            'role_rows' => [],
        ]);
        $permission = $this->makePermission($db);

        $map = $permission->bootstrap_session_permissions(7);

        $this->assertSame(7, $map['user_id']);
        $this->assertFalse($map['is_admin']);
        $this->assertTrue((bool) $map['controllers']['influencer']);
        $this->assertSame(1, $map['modules']['crm']['create']);
        $this->assertSame($map, $_SESSION[Permission::SESSION_PERM_KEY]);

        // And the map now serves checks with no further DB access.
        $before = $db->userModulePermissionQueries;
        $this->assertTrue($permission->has_module_access(7, 'influencer'));
        $this->assertSame($before, $db->userModulePermissionQueries);
    }

    public function testStaleVersionTriggersRebuild(): void
    {
        $db = new PermissionTestDb([
            'tables'           => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles'],
            'perm_version'     => 2,
            'user_permissions' => [
                ['module_name' => 'influencer', 'controller' => 'influencer', 'can_view' => 1, 'can_create' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
            ],
            'role_rows' => [],
        ]);
        $permission = $this->makePermission($db);

        // Stale map: built at version 1, granting only the now-removed 'old' controller.
        $this->seedSessionMap(7, ['old' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0, 'approve' => 0]], ['old' => true], false, 1);

        // Version moved to 2 -> the map rebuilds from the DB on use.
        $this->assertTrue($permission->has_module_access(7, 'influencer'));
        $this->assertFalse($permission->has_module_access(7, 'old'));
        $this->assertSame(2, $_SESSION[Permission::SESSION_PERM_KEY]['version']);
        $this->assertGreaterThanOrEqual(1, $db->userPermissionsQueries);
    }

    public function testMatchingVersionServesFromSession(): void
    {
        $db = new PermissionTestDb([
            'tables'       => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles'],
            'perm_version' => 5,
        ]);
        $permission = $this->makePermission($db);

        $this->seedSessionMap(7, ['dashboard' => ['view' => 1, 'create' => 0, 'edit' => 0, 'delete' => 0, 'approve' => 0]], ['dashboard' => true], false, 5);

        $this->assertTrue($permission->has_module_access(7, 'dashboard'));
        $this->assertSame(0, $db->userPermissionsQueries); // no rebuild
        $this->assertSame(1, $db->permissionMetaReads);     // version read once, then cached
    }

    public function testBumpPermissionVersion(): void
    {
        $db         = new PermissionTestDb(['tables' => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles']]);
        $permission = $this->makePermission($db);

        $permission->bump_permission_version();

        $this->assertSame(1, $db->permissionMetaBumps);
    }

    public function testBulkLoadHydratesRequestCache(): void
    {
        $db = new PermissionTestDb([
            'tables'     => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles'],
            'cache_rows' => [
                ['module_name' => 'dashboard', 'can_view' => 1, 'can_create' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
                ['module_name' => 'report', 'can_view' => 1, 'can_create' => 1, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
            ],
        ]);
        $permission = $this->makePermission($db);

        $permissions = $permission->get_permissions_for_modules(7, ['dashboard', 'report']);

        $this->assertTrue($permissions['dashboard']['view']);
        $this->assertTrue($permissions['report']['create']);
        $this->assertSame(1, $db->infoSchemaQueries);
        $this->assertSame(1, $db->userModulePermissionQueries);

        $this->assertTrue($permission->check_permission(7, 'dashboard', 'view'));
        $this->assertSame(1, $db->userModulePermissionQueries);
    }

    public function testMissingCacheRowsUseSingleBatchedFallback(): void
    {
        $db = new PermissionTestDb([
            'tables'        => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles'],
            'cache_rows'    => [],
            'fallback_rows' => [
                ['module_name' => 'expense', 'can_view' => 1, 'can_create' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
                ['module_name' => 'report', 'can_view' => 1, 'can_create' => 1, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
            ],
            'role_rows' => [],
        ]);
        $permission = $this->makePermission($db);

        $permissions = $permission->get_permissions_for_modules(9, ['expense', 'report']);

        $this->assertTrue($permissions['expense']['view']);
        $this->assertTrue($permissions['report']['create']);
        $this->assertSame(1, $db->fallbackAggregateQueries);
        $this->assertSame(1, $db->roleQueries);

        $this->assertTrue($permission->check_permission(9, 'expense', 'view'));
        $this->assertSame(1, $db->fallbackAggregateQueries);
    }

    public function testTableCapabilityChecksAreCachedPerRequest(): void
    {
        $db = new PermissionTestDb([
            'tables'               => ['user_module_permissions', 'modules', 'roles', 'role_permissions', 'user_roles'],
            'cache_rows_by_module' => [
                'dashboard' => ['module_name' => 'dashboard', 'can_view' => 1, 'can_create' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
                'report'    => ['module_name' => 'report', 'can_view' => 1, 'can_create' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0],
            ],
        ]);
        $permission = $this->makePermission($db);

        $this->assertTrue($permission->check_permission(11, 'dashboard', 'view'));
        $this->assertTrue($permission->check_permission(11, 'report', 'view'));

        $this->assertSame(1, $db->infoSchemaQueries);
        $this->assertSame(2, $db->userModulePermissionQueries);
    }

    private function makePermission(PermissionTestDb $db): Permission
    {
        $ci                              = new PermissionTestCi($db);
        $GLOBALS['__permission_test_ci'] = $ci;

        require_once __DIR__ . '/../../application/libraries/Permission.php';

        // Each test case is a fresh "request"; clear the request-lifetime static caches so
        // the capability probe and version read run once per case (matches production).
        Permission::resetTableCapabilityCache();
        Permission::resetPermissionVersionCache();

        return new Permission();
    }
}

final class PermissionTestCi
{
    public $db;
    public $mymodel;
    public $load;

    public function __construct(PermissionTestDb $db)
    {
        $this->db      = $db;
        $this->mymodel = new PermissionTestModel($db);
        $this->load    = new PermissionTestLoader();
    }
}

final class PermissionTestLoader
{
    public function model($name)
    {
        return null;
    }

    public function helper($name)
    {
        return null;
    }
}

final class PermissionTestModel
{
    private $db;

    public function __construct(PermissionTestDb $db)
    {
        $this->db = $db;
    }

    public function selectWithQuery($sql)
    {
        return $this->db->query($sql)->result_array();
    }
}

final class PermissionTestDb
{
    public $infoSchemaQueries           = 0;
    public $userModulePermissionQueries = 0;
    public $userPermissionsQueries      = 0;
    public $fallbackAggregateQueries    = 0;
    public $roleQueries                 = 0;
    public $permissionMetaReads         = 0;
    public $permissionMetaBumps         = 0;
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function query($sql, array $params = [])
    {
        if (str_contains($sql, 'INFORMATION_SCHEMA.TABLES')) {
            $this->infoSchemaQueries++;
            $rows = [];

            foreach ($this->config['tables'] as $table) {
                $rows[] = ['TABLE_NAME' => $table];
            }

            return new PermissionTestResult($rows);
        }

        if (str_contains($sql, 'permission_meta') && str_contains($sql, 'SELECT version')) {
            $this->permissionMetaReads++;

            if (array_key_exists('perm_version', $this->config) && $this->config['perm_version'] !== null) {
                return new PermissionTestResult([['version' => $this->config['perm_version']]]);
            }

            return new PermissionTestResult([]);
        }

        if (str_contains($sql, 'UPDATE permission_meta')) {
            $this->permissionMetaBumps++;

            return new PermissionTestResult([]);
        }

        if (str_contains($sql, 'FROM user_module_permissions') && str_contains($sql, 'ORDER BY module_name')) {
            $this->userPermissionsQueries++;

            return new PermissionTestResult($this->config['user_permissions'] ?? []);
        }

        if (str_contains($sql, 'FROM user_module_permissions') && str_contains($sql, 'module_name IN')) {
            $this->userModulePermissionQueries++;

            if (isset($this->config['cache_rows_by_module'])) {
                $rows = [];

                foreach (array_slice($params, 1) as $moduleName) {
                    if (isset($this->config['cache_rows_by_module'][$moduleName])) {
                        $rows[] = $this->config['cache_rows_by_module'][$moduleName];
                    }
                }

                return new PermissionTestResult($rows);
            }

            return new PermissionTestResult($this->config['cache_rows'] ?? []);
        }

        if (str_contains($sql, 'FROM user_roles ur') && str_contains($sql, 'MAX(rp.can_view)')) {
            $this->fallbackAggregateQueries++;

            return new PermissionTestResult($this->config['fallback_rows'] ?? []);
        }

        if (str_contains($sql, 'SELECT r.name') && str_contains($sql, 'FROM user_roles ur')) {
            $this->roleQueries++;

            return new PermissionTestResult($this->config['role_rows'] ?? []);
        }

        throw new RuntimeException('Unhandled query in test: ' . $sql);
    }
}

final class PermissionTestResult
{
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = array_values($rows);
    }

    public function result_array()
    {
        return $this->rows;
    }

    public function row_array()
    {
        return $this->rows[0] ?? [];
    }
}
