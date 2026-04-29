<?php
/**
 * Migration: Add attempt audit table + retry source lineage for endorse_refresh_queue.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260430000000_add_endorse_refresh_attempts.php
 * Down: php migrations/run.php 20260430000000_add_endorse_refresh_attempts.php down
 */

if ($direction === 'down') {
    $pdo->exec("DROP TABLE IF EXISTS `endorse_refresh_queue_attempts`");
    echo "Dropped table endorse_refresh_queue_attempts.\n";

    $retryIdx = $pdo->query("SHOW INDEX FROM `endorse_refresh_queue` WHERE Key_name = 'idx_retry_source'")->fetchAll();
    if (!empty($retryIdx)) {
        $pdo->exec("ALTER TABLE `endorse_refresh_queue` DROP INDEX `idx_retry_source`");
        echo "Dropped index endorse_refresh_queue.idx_retry_source.\n";
    }

    $retryCol = $pdo->query("SHOW COLUMNS FROM `endorse_refresh_queue` LIKE 'retry_source_id'")->fetchAll();
    if (!empty($retryCol)) {
        $pdo->exec("ALTER TABLE `endorse_refresh_queue` DROP COLUMN `retry_source_id`");
        echo "Dropped column endorse_refresh_queue.retry_source_id.\n";
    }
    return;
}

$attemptsExists = $pdo->query("SHOW TABLES LIKE 'endorse_refresh_queue_attempts'")->fetchAll();
if (empty($attemptsExists)) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_queue_attempts` (
            `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `queue_id`      INT UNSIGNED NOT NULL,
            `attempt_no`    TINYINT      NOT NULL,
            `worker_id`     VARCHAR(64)  NULL,
            `status`        ENUM('processing','retrying','completed','failed') NOT NULL DEFAULT 'processing',
            `error_class`   VARCHAR(32)  NULL,
            `error_message` TEXT         NULL,
            `started_at`    DATETIME     NOT NULL,
            `finished_at`   DATETIME     NULL,
            `created_at`    DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_queue_attempt` (`queue_id`, `attempt_no`),
            KEY `idx_worker_status` (`worker_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_queue_attempts.\n";
} else {
    echo "Table endorse_refresh_queue_attempts already exists, skipping.\n";
}

$retryCol = $pdo->query("SHOW COLUMNS FROM `endorse_refresh_queue` LIKE 'retry_source_id'")->fetchAll();
if (empty($retryCol)) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` ADD COLUMN `retry_source_id` INT UNSIGNED NULL AFTER `enqueued_by`");
    echo "Added column endorse_refresh_queue.retry_source_id.\n";
} else {
    echo "Column endorse_refresh_queue.retry_source_id already exists, skipping.\n";
}

$retryIdx = $pdo->query("SHOW INDEX FROM `endorse_refresh_queue` WHERE Key_name = 'idx_retry_source'")->fetchAll();
if (empty($retryIdx)) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` ADD INDEX `idx_retry_source` (`retry_source_id`)");
    echo "Added index endorse_refresh_queue.idx_retry_source.\n";
} else {
    echo "Index endorse_refresh_queue.idx_retry_source already exists, skipping.\n";
}
