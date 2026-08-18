<?php

/**
 * Add the database primitives required for atomic endorse-refresh claims.
 *
 * This migration deliberately refuses to create unique active-row indexes when
 * conflicting production data exists. Operators must preview and reconcile those
 * rows first; silently deleting queue history is never acceptable.
 *
 * Variables injected by run.php: $pdo (PDO), $direction ('up'|'down').
 */
// Guarded so the file can be included more than once in a single process (the disposable
// integration database builds the canonical schema by running these migrations directly).
// run.php includes each migration once, so production behaviour is unchanged.
if (! function_exists('refreshClaimsHasColumn')) {
    function refreshClaimsHasColumn(PDO $pdo, string $table, string $column): bool
    {
        return ! empty($pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column))->fetchAll());
    }
}

if (! function_exists('refreshClaimsHasIndex')) {
    function refreshClaimsHasIndex(PDO $pdo, string $table, string $index): bool
    {
        return ! empty($pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name = " . $pdo->quote($index))->fetchAll());
    }
}

if ($direction === 'down') {
    if (refreshClaimsHasIndex($pdo, 'endorse_refresh_queue', 'uq_active_endorse_purpose')) {
        $pdo->exec('ALTER TABLE `endorse_refresh_queue` DROP INDEX `uq_active_endorse_purpose`');
    }
    if (refreshClaimsHasColumn($pdo, 'endorse_refresh_queue', 'active_business_slot')) {
        $pdo->exec('ALTER TABLE `endorse_refresh_queue` DROP COLUMN `active_business_slot`');
    }
    if (refreshClaimsHasColumn($pdo, 'endorse_refresh_queue', 'lease_expires_at')) {
        $pdo->exec('ALTER TABLE `endorse_refresh_queue` DROP COLUMN `lease_expires_at`');
    }

    if (refreshClaimsHasIndex($pdo, 'endorse_refresh_queue_attempts', 'uq_active_queue_attempt')) {
        $pdo->exec('ALTER TABLE `endorse_refresh_queue_attempts` DROP INDEX `uq_active_queue_attempt`');
    }
    if (refreshClaimsHasColumn($pdo, 'endorse_refresh_queue_attempts', 'active_queue_id')) {
        $pdo->exec('ALTER TABLE `endorse_refresh_queue_attempts` DROP COLUMN `active_queue_id`');
    }
    $pdo->exec("
        ALTER TABLE `endorse_refresh_queue_attempts`
        MODIFY COLUMN `attempt_no` TINYINT UNSIGNED NOT NULL,
        MODIFY COLUMN `status` ENUM('processing','submitted','retrying','completed','failed','cancelled') NOT NULL DEFAULT 'processing'
    ");

    return;
}

$duplicateActiveAttempts = (int) $pdo->query("
    SELECT COUNT(*)
    FROM (
        SELECT queue_id
        FROM endorse_refresh_queue_attempts
        WHERE status = 'processing'
        GROUP BY queue_id
        HAVING COUNT(*) > 1
    ) duplicate_attempts
")->fetchColumn();
if ($duplicateActiveAttempts > 0) {
    throw new RuntimeException("Cannot harden attempts: {$duplicateActiveAttempts} queue(s) have multiple processing attempts.");
}

$duplicateActiveItems = (int) $pdo->query("
    SELECT COUNT(*)
    FROM (
        SELECT id_endorse, purpose
        FROM endorse_refresh_queue
        WHERE status IN ('pending','processing','submitted')
        GROUP BY id_endorse, purpose
        HAVING COUNT(*) > 1
    ) duplicate_items
")->fetchColumn();
if ($duplicateActiveItems > 0) {
    throw new RuntimeException("Cannot harden queue: {$duplicateActiveItems} business scope(s) have duplicate active rows.");
}

if (! refreshClaimsHasColumn($pdo, 'endorse_refresh_queue', 'lease_expires_at')) {
    $pdo->exec('ALTER TABLE `endorse_refresh_queue` ADD COLUMN `lease_expires_at` DATETIME(6) NULL AFTER `claimed_at`');
}
if (! refreshClaimsHasColumn($pdo, 'endorse_refresh_queue', 'active_business_slot')) {
    $pdo->exec("
        ALTER TABLE `endorse_refresh_queue`
        ADD COLUMN `active_business_slot` TINYINT UNSIGNED
            GENERATED ALWAYS AS (CASE WHEN status IN ('pending','processing','submitted') THEN 1 ELSE NULL END) STORED
    ");
}
if (! refreshClaimsHasIndex($pdo, 'endorse_refresh_queue', 'uq_active_endorse_purpose')) {
    $pdo->exec('
        ALTER TABLE `endorse_refresh_queue`
        ADD UNIQUE KEY `uq_active_endorse_purpose` (`id_endorse`, `purpose`, `active_business_slot`)
    ');
}

$pdo->exec("
    ALTER TABLE `endorse_refresh_queue_attempts`
    MODIFY COLUMN `attempt_no` INT UNSIGNED NOT NULL,
    MODIFY COLUMN `status` ENUM('processing','submitted','retrying','completed','failed','cancelled','timed_out') NOT NULL DEFAULT 'processing'
");
if (! refreshClaimsHasColumn($pdo, 'endorse_refresh_queue_attempts', 'active_queue_id')) {
    $pdo->exec("
        ALTER TABLE `endorse_refresh_queue_attempts`
        ADD COLUMN `active_queue_id` INT UNSIGNED
            GENERATED ALWAYS AS (CASE WHEN status = 'processing' THEN queue_id ELSE NULL END) STORED
    ");
}
if (! refreshClaimsHasIndex($pdo, 'endorse_refresh_queue_attempts', 'uq_active_queue_attempt')) {
    $pdo->exec('
        ALTER TABLE `endorse_refresh_queue_attempts`
        ADD UNIQUE KEY `uq_active_queue_attempt` (`active_queue_id`)
    ');
}

echo "Hardened endorse-refresh claim leases and active-row invariants.\n";
