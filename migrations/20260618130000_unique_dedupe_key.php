<?php
/**
 * Migration: enforce race-safe deduplication on notifications.dedupe_key.
 *
 * Phase 0 added a non-unique index (idx_notif_dedupe) on (dedupe_key, created_at),
 * but NotificationDispatcher::emit() still did a check-then-insert with a TOCTOU
 * window: two concurrent identical events could both pass existsByDedupeKey() and
 * both insert. Harmless for in-app today, but the upcoming push channel would turn
 * that into duplicate pushes.
 *
 * This migration makes the database the authority: a UNIQUE index on dedupe_key so
 * NotificationModel::insert() (now INSERT IGNORE) silently drops a duplicate key.
 * MySQL permits multiple NULLs in a unique index, so quota-change notifications
 * (null dedupe_key, intentionally not deduplicated) are unaffected.
 *
 * Every current dedupe key encodes a terminal/once event (request_approved_{id},
 * step_approved_{id}_{step}, ...), so a global UNIQUE matches intent.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260618130000_unique_dedupe_key.php
 * Down: php migrations/run.php 20260618130000_unique_dedupe_key.php down
 */

/** Helper: does an index exist on `notifications`? */
$indexExists = static function (PDO $pdo, string $index): bool {
    return !empty($pdo->query(
        "SHOW INDEX FROM `notifications` WHERE Key_name = " . $pdo->quote($index)
    )->fetchAll());
};

if ($direction === 'down') {
    if ($indexExists($pdo, 'uq_notif_dedupe_key')) {
        $pdo->exec("ALTER TABLE `notifications` DROP INDEX `uq_notif_dedupe_key`");
        echo "Dropped unique index uq_notif_dedupe_key.\n";
    }
    // Restore the plain dedupe index from the harden migration if it is gone.
    if (!$indexExists($pdo, 'idx_notif_dedupe')) {
        $pdo->exec("ALTER TABLE `notifications` ADD INDEX `idx_notif_dedupe` (`dedupe_key`, `created_at`)");
        echo "Restored index idx_notif_dedupe.\n";
    }
    return;
}

if ($indexExists($pdo, 'uq_notif_dedupe_key')) {
    echo "Unique index uq_notif_dedupe_key already exists, skipping.\n";
    return;
}

// ---- collapse existing duplicate dedupe_key rows (keep lowest id) ----
// NULL keys are excluded: they are legitimately repeatable.
$deleted = $pdo->exec(
    "DELETE n FROM `notifications` n
     JOIN (
         SELECT `dedupe_key`, MIN(`id`) AS keep_id
         FROM `notifications`
         WHERE `dedupe_key` IS NOT NULL
         GROUP BY `dedupe_key`
         HAVING COUNT(*) > 1
     ) d ON n.`dedupe_key` = d.`dedupe_key` AND n.`id` <> d.keep_id"
);
echo "Collapsed {$deleted} duplicate dedupe_key row(s).\n";

// ---- drop the plain index so the unique one can own the column ----
if ($indexExists($pdo, 'idx_notif_dedupe')) {
    $pdo->exec("ALTER TABLE `notifications` DROP INDEX `idx_notif_dedupe`");
    echo "Dropped plain index idx_notif_dedupe.\n";
}

// ---- add the unique index ----
$pdo->exec("ALTER TABLE `notifications` ADD UNIQUE INDEX `uq_notif_dedupe_key` (`dedupe_key`)");
echo "Added unique index uq_notif_dedupe_key.\n";
