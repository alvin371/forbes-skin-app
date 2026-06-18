<?php
/**
 * Migration: harden notifications table.
 *
 * Phase 0 of the notification module refactor. Brings the legacy `notifications`
 * table to a clean, indexed, non-corrupting baseline before the dispatcher/FCM work:
 *
 *  - type enum: add 'error' (code standardizes on 'error'; rejection notifications
 *    were silently stored as '' because 'error' was not a valid enum value).
 *  - is_read: enforce NOT NULL DEFAULT 0 (a static writer left NULLs).
 *  - created_at: default CURRENT_TIMESTAMP; add updated_at (app convention).
 *  - dedupe_key: new column powering cross-process deduplication (replaces the
 *    broken session-only / phantom-row scheme).
 *  - indexes for the real query patterns (per-user unread, per-user recent, dedupe).
 *  - backfill: type='' -> 'error', is_read NULL -> 0.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260618120000_harden_notifications.php
 * Down: php migrations/run.php 20260618120000_harden_notifications.php down
 */

/** Helper: does a column exist on `notifications`? */
$columnExists = static function (PDO $pdo, string $column): bool {
    return !empty($pdo->query(
        "SHOW COLUMNS FROM `notifications` LIKE " . $pdo->quote($column)
    )->fetchAll());
};

/** Helper: does an index exist on `notifications`? */
$indexExists = static function (PDO $pdo, string $index): bool {
    return !empty($pdo->query(
        "SHOW INDEX FROM `notifications` WHERE Key_name = " . $pdo->quote($index)
    )->fetchAll());
};

if ($direction === 'down') {
    foreach (['idx_notif_user_read', 'idx_notif_user_created', 'idx_notif_dedupe'] as $idx) {
        if ($indexExists($pdo, $idx)) {
            $pdo->exec("ALTER TABLE `notifications` DROP INDEX `{$idx}`");
            echo "Dropped index {$idx}.\n";
        }
    }
    if ($columnExists($pdo, 'dedupe_key')) {
        $pdo->exec("ALTER TABLE `notifications` DROP COLUMN `dedupe_key`");
        echo "Dropped column notifications.dedupe_key.\n";
    }
    if ($columnExists($pdo, 'updated_at')) {
        $pdo->exec("ALTER TABLE `notifications` DROP COLUMN `updated_at`");
        echo "Dropped column notifications.updated_at.\n";
    }
    // Revert type enum to its original set (without 'error').
    $pdo->exec(
        "ALTER TABLE `notifications`
         MODIFY `type` ENUM('info','success','warning','danger')
         COLLATE utf8mb4_unicode_ci DEFAULT NULL"
    );
    echo "Reverted notifications.type enum (removed 'error').\n";
    echo "Note: is_read/created_at definition changes are not reverted (non-destructive).\n";
    return;
}

// ---- type enum: add 'error' ----
$pdo->exec(
    "ALTER TABLE `notifications`
     MODIFY `type` ENUM('info','success','warning','danger','error')
     COLLATE utf8mb4_unicode_ci DEFAULT NULL"
);
echo "Updated notifications.type enum (added 'error').\n";

// ---- backfill corrupted rejection rows (type stored as '') ----
$fixed = $pdo->exec("UPDATE `notifications` SET `type` = 'error' WHERE `type` = ''");
echo "Backfilled {$fixed} row(s) with empty type -> 'error'.\n";

// ---- is_read: NOT NULL DEFAULT 0 (backfill NULLs first) ----
$readFixed = $pdo->exec("UPDATE `notifications` SET `is_read` = 0 WHERE `is_read` IS NULL");
echo "Backfilled {$readFixed} row(s) with NULL is_read -> 0.\n";
$pdo->exec("ALTER TABLE `notifications` MODIFY `is_read` TINYINT(1) NOT NULL DEFAULT 0");
echo "Set notifications.is_read NOT NULL DEFAULT 0.\n";

// ---- created_at: default CURRENT_TIMESTAMP ----
$pdo->exec(
    "ALTER TABLE `notifications`
     MODIFY `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP"
);
echo "Set notifications.created_at default CURRENT_TIMESTAMP.\n";

// ---- updated_at column ----
if (!$columnExists($pdo, 'updated_at')) {
    $pdo->exec(
        "ALTER TABLE `notifications`
         ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`"
    );
    echo "Added column notifications.updated_at.\n";
} else {
    echo "Column notifications.updated_at already exists, skipping.\n";
}

// ---- dedupe_key column ----
if (!$columnExists($pdo, 'dedupe_key')) {
    $pdo->exec(
        "ALTER TABLE `notifications`
         ADD COLUMN `dedupe_key` VARCHAR(150)
         COLLATE utf8mb4_unicode_ci NULL AFTER `related_id`"
    );
    echo "Added column notifications.dedupe_key.\n";
} else {
    echo "Column notifications.dedupe_key already exists, skipping.\n";
}

// ---- indexes ----
if (!$indexExists($pdo, 'idx_notif_user_read')) {
    $pdo->exec("ALTER TABLE `notifications` ADD INDEX `idx_notif_user_read` (`user_id`, `is_read`)");
    echo "Added index idx_notif_user_read.\n";
}
if (!$indexExists($pdo, 'idx_notif_user_created')) {
    $pdo->exec("ALTER TABLE `notifications` ADD INDEX `idx_notif_user_created` (`user_id`, `created_at`)");
    echo "Added index idx_notif_user_created.\n";
}
if (!$indexExists($pdo, 'idx_notif_dedupe')) {
    $pdo->exec("ALTER TABLE `notifications` ADD INDEX `idx_notif_dedupe` (`dedupe_key`, `created_at`)");
    echo "Added index idx_notif_dedupe.\n";
}
