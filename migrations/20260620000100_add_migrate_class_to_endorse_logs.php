<?php
/**
 * Migration: Add `migrate_class` tag column to endorse_logs (migration-readiness prep).
 *
 * Non-destructive. Adds a nullable classification column so the future Postgres
 * migration can cleanly separate live / idle / dead / orphan / junk log data WITHOUT
 * deleting anything from the live CI3 table. Classification is applied later by a
 * separate behavior-based UPDATE pass (see docs/2026-06-20-endorse-logs-profile.md);
 * this migration only creates the column + its index.
 *
 *   values: 'active' | 'idle' | 'dead' | 'orphan' | 'junk' (NULL = not yet classified)
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260620000100_add_migrate_class_to_endorse_logs.php
 * Down: php migrations/run.php 20260620000100_add_migrate_class_to_endorse_logs.php down
 */

if ($direction === 'down') {
    $indexExists = $pdo->query("SHOW INDEX FROM `endorse_logs` WHERE Key_name = 'idx_migrate_class'")->fetchAll();
    if (!empty($indexExists)) {
        $pdo->exec("ALTER TABLE `endorse_logs` DROP INDEX `idx_migrate_class`");
        echo "Dropped index endorse_logs.idx_migrate_class.\n";
    } else {
        echo "Index endorse_logs.idx_migrate_class does not exist, skipping.\n";
    }

    $colExists = $pdo->query("SHOW COLUMNS FROM `endorse_logs` LIKE 'migrate_class'")->fetchAll();
    if (!empty($colExists)) {
        $pdo->exec("ALTER TABLE `endorse_logs` DROP COLUMN `migrate_class`");
        echo "Dropped column endorse_logs.migrate_class.\n";
    } else {
        echo "Column endorse_logs.migrate_class does not exist, skipping.\n";
    }
    return;
}

$colExists = $pdo->query("SHOW COLUMNS FROM `endorse_logs` LIKE 'migrate_class'")->fetchAll();
if (empty($colExists)) {
    $pdo->exec("ALTER TABLE `endorse_logs` ADD COLUMN `migrate_class` VARCHAR(12) NULL");
    echo "Added column endorse_logs.migrate_class.\n";
} else {
    echo "Column endorse_logs.migrate_class already exists, skipping.\n";
}

$indexExists = $pdo->query("SHOW INDEX FROM `endorse_logs` WHERE Key_name = 'idx_migrate_class'")->fetchAll();
if (empty($indexExists)) {
    $pdo->exec("ALTER TABLE `endorse_logs` ADD INDEX `idx_migrate_class` (`migrate_class`)");
    echo "Added index endorse_logs.idx_migrate_class.\n";
} else {
    echo "Index endorse_logs.idx_migrate_class already exists, skipping.\n";
}
