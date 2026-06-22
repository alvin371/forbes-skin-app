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

        if (! function_exists('get_instance')) {
            function &get_instance()
            {
                return $GLOBALS['__permission_test_ci'];
            }
        }
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

        // Each test case is a fresh "request"; clear the request-lifetime static cache so
        // the capability probe runs once per case (matches production per-request behaviour).
        Permission::resetTableCapabilityCache();

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
    public $fallbackAggregateQueries    = 0;
    public $roleQueries                 = 0;
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
