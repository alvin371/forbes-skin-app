<?php
/**
 * Migration: permission_meta — a single-row global permission version (B3).
 *
 * The session permission map (B2) is built once at login and otherwise served from
 * the session. To invalidate it the instant an admin changes a role, we bump a global
 * version here; each request compares the version stored in its session map against
 * this value and rebuilds on mismatch. Roles::sync_user_permissions_for_role() bumps
 * it whenever user_module_permissions is resynced.
 *
 * Single fixed row (id = 1). The read is a primary-key single-row lookup (~microseconds)
 * and is request-cached, so it adds at most one trivial query per authenticated request.
 *
 * INTERIM ONLY: supports the live CI3/MySQL app until the Postgres migration.
 *
 * Run:  php migrations/run.php 20260626130000_create_permission_meta.php
 * Down: php migrations/run.php 20260626130000_create_permission_meta.php down
 */

$table = 'permission_meta';

if ($direction === 'down') {
    $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
    if (!empty($exists)) {
        $pdo->exec("DROP TABLE `$table`");
        echo "Dropped table $table.\n";
    } else {
        echo "Table $table does not exist, skipping.\n";
    }
    return;
}

$exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
if (empty($exists)) {
    $pdo->exec(
        "CREATE TABLE `$table` (
            `id` TINYINT UNSIGNED NOT NULL,
            `version` BIGINT UNSIGNED NOT NULL DEFAULT 1,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    echo "Created table $table.\n";
} else {
    echo "Table $table already exists, skipping create.\n";
}

// Ensure the single row exists.
$pdo->exec("INSERT IGNORE INTO `$table` (`id`, `version`, `updated_at`) VALUES (1, 1, NOW())");
echo "Ensured $table row id=1.\n";
