<?php
/**
 * Migration: Add worker_id + claimed_at to endorse_refresh_queue.
 * Enables atomic claim pattern so multiple staggered cron workers can run in parallel.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260428001000_add_worker_id_to_endorse_refresh_queue.php
 * Down: php migrations/run.php 20260428001000_add_worker_id_to_endorse_refresh_queue.php down
 */

if ($direction === 'down') {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` DROP INDEX `idx_worker`");
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` DROP COLUMN `worker_id`, DROP COLUMN `claimed_at`");
    echo "Rolled back: removed worker_id, claimed_at, idx_worker.\n";
    return;
}

$cols = $pdo->query("SHOW COLUMNS FROM `endorse_refresh_queue` LIKE 'worker_id'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue`
        ADD COLUMN `worker_id`  VARCHAR(64) NULL AFTER `error_message`,
        ADD COLUMN `claimed_at` DATETIME    NULL AFTER `worker_id`");
    echo "Added columns worker_id, claimed_at.\n";
} else {
    echo "Columns worker_id, claimed_at already present, skipping.\n";
}

$idx = $pdo->query("SHOW INDEX FROM `endorse_refresh_queue` WHERE Key_name = 'idx_worker'")->fetchAll();
if (empty($idx)) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` ADD INDEX `idx_worker` (`status`, `worker_id`)");
    echo "Added index idx_worker (status, worker_id).\n";
} else {
    echo "Index idx_worker already exists, skipping.\n";
}
