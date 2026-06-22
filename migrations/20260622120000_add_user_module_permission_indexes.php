<?php
/**
 * Migration: add lookup indexes for the permission cache table.
 *
 * Hot paths use user_module_permissions by:
 * - (user_id, module_name) for check_permission()
 * - (user_id, controller) for has_module_access()
 * - (user_id, module_id) for sidebar/module joins
 *
 * Run:  php migrations/run.php 20260622120000_add_user_module_permission_indexes.php
 * Down: php migrations/run.php 20260622120000_add_user_module_permission_indexes.php down
 */

$indexes = [
    ['user_module_permissions', 'idx_ump_user_module_name', '(`user_id`, `module_name`)'],
    ['user_module_permissions', 'idx_ump_user_controller', '(`user_id`, `controller`)'],
    ['user_module_permissions', 'idx_ump_user_module_id', '(`user_id`, `module_id`)'],
];

if ($direction === 'down') {
    foreach ($indexes as [$table, $name, $cols]) {
        $tableExists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
        if (empty($tableExists)) {
            echo "Table $table does not exist, skipping.\n";
            continue;
        }

        $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
        if (!empty($exists)) {
            $pdo->exec("ALTER TABLE `$table` DROP INDEX `$name`");
            echo "Dropped index $table.$name.\n";
        } else {
            echo "Index $table.$name does not exist, skipping.\n";
        }
    }

    return;
}

foreach ($indexes as [$table, $name, $cols]) {
    $tableExists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
    if (empty($tableExists)) {
        echo "Table $table does not exist, skipping.\n";
        continue;
    }

    $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
    if (empty($exists)) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$name` $cols");
        echo "Added index $table.$name.\n";
    } else {
        echo "Index $table.$name already exists, skipping.\n";
    }
}
