<?php

/**
 * Persist the evidence required to explain refresh throughput, duplicate enqueues and
 * resource spikes.  These tables are intentionally append-only (except retention).
 */
$hasColumn = static fn (PDO $pdo, string $table, string $column): bool => ! empty($pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column))->fetchAll());

if ($direction === 'down') {
    foreach ([
        'endorse_refresh_spikes', 'endorse_refresh_resource_snapshots',
        'endorse_refresh_queue_attempt_archive', 'endorse_refresh_queue_archive',
        'endorse_refresh_runs',
    ] as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        echo "Dropped {$table}.\n";
    }

    foreach (['enqueue_run_id', 'enqueue_source'] as $column) {
        if ($hasColumn($pdo, 'endorse_refresh_queue', $column)) {
            $pdo->exec("ALTER TABLE `endorse_refresh_queue` DROP COLUMN `{$column}`");
            echo "Dropped endorse_refresh_queue.{$column}.\n";
        }
    }

    return;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS `endorse_refresh_runs` (
    `id` CHAR(36) NOT NULL,
    `source` VARCHAR(40) NOT NULL,
    `request_id` VARCHAR(64) NULL,
    `initiator_user_id` INT UNSIGNED NULL,
    `id_campaign` INT UNSIGNED NULL,
    `status` ENUM('running','completed','failed','cleared') NOT NULL DEFAULT 'running',
    `config_json` JSON NULL,
    `candidate_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `enqueued_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `skipped_duplicate_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `excluded_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `claimed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `completed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `retrying_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `deferred_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `note` VARCHAR(255) NULL,
    `started_at` DATETIME(6) NOT NULL,
    `finished_at` DATETIME(6) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_refresh_run_time` (`started_at`),
    KEY `idx_refresh_run_source_time` (`source`, `started_at`),
    KEY `idx_refresh_run_campaign_time` (`id_campaign`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (! $hasColumn($pdo, 'endorse_refresh_queue', 'enqueue_run_id')) {
    $pdo->exec('ALTER TABLE `endorse_refresh_queue` ADD COLUMN `enqueue_run_id` CHAR(36) NULL AFTER `retry_source_id`, ADD INDEX `idx_enqueue_run` (`enqueue_run_id`)');
}
if (! $hasColumn($pdo, 'endorse_refresh_queue', 'enqueue_source')) {
    $pdo->exec('ALTER TABLE `endorse_refresh_queue` ADD COLUMN `enqueue_source` VARCHAR(40) NULL AFTER `enqueue_run_id`, ADD INDEX `idx_scope_created` (`id_endorse`, `purpose`, `created_at`)');
}

$pdo->exec('CREATE TABLE IF NOT EXISTS `endorse_refresh_queue_archive` LIKE `endorse_refresh_queue`');
if (! $hasColumn($pdo, 'endorse_refresh_queue_archive', 'archived_at')) {
    $pdo->exec('ALTER TABLE `endorse_refresh_queue_archive`
        DROP PRIMARY KEY,
        MODIFY COLUMN `id` INT UNSIGNED NOT NULL,
        ADD COLUMN `archive_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
        ADD COLUMN `archived_at` DATETIME(6) NOT NULL AFTER `completed_at`,
        ADD COLUMN `archived_by` INT UNSIGNED NULL AFTER `archived_at`,
        ADD COLUMN `archive_reason` VARCHAR(255) NOT NULL AFTER `archived_by`,
        ADD INDEX `idx_archive_time` (`archived_at`),
        ADD INDEX `idx_archive_scope_time` (`id_endorse`, `purpose`, `created_at`)');
}

$pdo->exec('CREATE TABLE IF NOT EXISTS `endorse_refresh_queue_attempt_archive` LIKE `endorse_refresh_queue_attempts`');
if (! $hasColumn($pdo, 'endorse_refresh_queue_attempt_archive', 'archived_at')) {
    $pdo->exec('ALTER TABLE `endorse_refresh_queue_attempt_archive`
        DROP PRIMARY KEY,
        MODIFY COLUMN `id` INT UNSIGNED NOT NULL,
        ADD COLUMN `archive_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
        ADD COLUMN `archived_at` DATETIME(6) NOT NULL AFTER `created_at`,
        ADD INDEX `idx_attempt_archive_time` (`archived_at`),
        ADD INDEX `idx_attempt_archive_queue` (`queue_id`, `attempt_no`)');
}

$pdo->exec('CREATE TABLE IF NOT EXISTS `endorse_refresh_resource_snapshots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `captured_at` DATETIME(6) NOT NULL,
    `host_load_1` DECIMAL(8,2) NULL,
    `host_memory_used_bytes` BIGINT UNSIGNED NULL,
    `host_memory_total_bytes` BIGINT UNSIGNED NULL,
    `app_cpu_percent` DECIMAL(8,2) NULL,
    `mysql_cpu_percent` DECIMAL(8,2) NULL,
    `worker_cpu_percent` DECIMAL(8,2) NULL,
    `queue_pending` INT UNSIGNED NULL,
    `queue_processing` INT UNSIGNED NULL,
    `queue_completed` INT UNSIGNED NULL,
    `queue_failed` INT UNSIGNED NULL,
    `payload_json` JSON NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resource_snapshot_time` (`captured_at`),
    KEY `idx_resource_mysql_time` (`mysql_cpu_percent`, `captured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$pdo->exec("CREATE TABLE IF NOT EXISTS `endorse_refresh_spikes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `captured_at` DATETIME(6) NOT NULL,
    `source` VARCHAR(32) NOT NULL,
    `severity` ENUM('warning','critical') NOT NULL DEFAULT 'warning',
    `summary` VARCHAR(255) NOT NULL,
    `evidence_json` JSON NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_spike_time` (`captured_at`),
    KEY `idx_spike_source_time` (`source`, `captured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Created endorse refresh diagnostics tables.\n";
