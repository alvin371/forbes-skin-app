<?php
/**
 * Migration: Create endorse_refresh_queue table for async per-post stat refresh.
 * Adds supporting indexes on endorse_logs and endorse for bulk refresh perf.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260428000000_create_endorse_refresh_queue.php
 * Down: php migrations/run.php 20260428000000_create_endorse_refresh_queue.php down
 */

if ($direction === 'down') {
    $pdo->exec("DROP TABLE IF EXISTS `endorse_refresh_queue`");
    echo "Dropped table endorse_refresh_queue.\n";

    $pdo->exec("ALTER TABLE `endorse_logs` DROP INDEX `idx_id_endorse_date`");
    echo "Dropped index endorse_logs.idx_id_endorse_date.\n";

    $pdo->exec("ALTER TABLE `endorse` DROP INDEX `idx_campaign_status`");
    echo "Dropped index endorse.idx_campaign_status.\n";
    return;
}

// up
$tableExists = $pdo->query("SHOW TABLES LIKE 'endorse_refresh_queue'")->fetchAll();
if (empty($tableExists)) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_queue` (
            `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_endorse`    INT UNSIGNED NOT NULL,
            `id_campaign`   INT UNSIGNED NOT NULL,
            `platform`      VARCHAR(20)  NOT NULL,
            `link_upload`   TEXT         NOT NULL,
            `status`        ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
            `priority`      TINYINT      NOT NULL DEFAULT 10,
            `attempts`      TINYINT      NOT NULL DEFAULT 0,
            `max_attempts`  TINYINT      NOT NULL DEFAULT 3,
            `error_message` TEXT         NULL,
            `enqueued_by`   INT UNSIGNED NULL,
            `created_at`    DATETIME     NOT NULL,
            `started_at`    DATETIME     NULL,
            `completed_at`  DATETIME     NULL,
            PRIMARY KEY (`id`),
            KEY `idx_pop`             (`status`, `priority`, `created_at`),
            KEY `idx_dedup`           (`id_endorse`, `status`),
            KEY `idx_campaign_status` (`id_campaign`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_queue.\n";
} else {
    echo "Table endorse_refresh_queue already exists, skipping.\n";
}

$logsIdx = $pdo->query("SHOW INDEX FROM `endorse_logs` WHERE Key_name = 'idx_id_endorse_date'")->fetchAll();
if (empty($logsIdx)) {
    $pdo->exec("ALTER TABLE `endorse_logs` ADD INDEX `idx_id_endorse_date` (`id_endorse`, `date`)");
    echo "Added index endorse_logs.idx_id_endorse_date.\n";
} else {
    echo "Index endorse_logs.idx_id_endorse_date already exists, skipping.\n";
}

$endorseIdx = $pdo->query("SHOW INDEX FROM `endorse` WHERE Key_name = 'idx_campaign_status'")->fetchAll();
if (empty($endorseIdx)) {
    $pdo->exec("ALTER TABLE `endorse` ADD INDEX `idx_campaign_status` (`id_campaign`, `status`, `status_campaign`)");
    echo "Added index endorse.idx_campaign_status.\n";
} else {
    echo "Index endorse.idx_campaign_status already exists, skipping.\n";
}
