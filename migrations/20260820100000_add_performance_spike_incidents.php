<?php

/**
 * Cross-service evidence for CPU incidents.  The collector is deliberately
 * append-only while an incident is open; request logs are summarized only when
 * evidence is captured, never written once per HTTP request.
 */
if ($direction === 'down') {
    foreach (['performance_spike_evidence', 'performance_spike_samples', 'performance_spike_incidents'] as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        echo "Dropped {$table}.\n";
    }

    return;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS `performance_spike_incidents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `incident_key` VARCHAR(100) NOT NULL,
    `service_name` VARCHAR(64) NOT NULL,
    `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
    `started_at` DATETIME(6) NOT NULL,
    `ended_at` DATETIME(6) NULL,
    `duration_seconds` INT UNSIGNED NULL,
    `peak_cpu_percent` DECIMAL(9,2) NOT NULL DEFAULT 0,
    `peak_memory_percent` DECIMAL(9,2) NOT NULL DEFAULT 0,
    `sample_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `request_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `active_user_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `anonymous_request_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `endpoint_summary_json` JSON NULL,
    `summary_json` JSON NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_performance_spike_incident_key` (`incident_key`),
    KEY `idx_performance_spike_service_started` (`service_name`, `started_at`),
    KEY `idx_performance_spike_status_started` (`status`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `performance_spike_samples` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `incident_key` VARCHAR(100) NOT NULL,
    `captured_at` DATETIME(6) NOT NULL,
    `service_name` VARCHAR(64) NOT NULL,
    `cpu_percent` DECIMAL(9,2) NOT NULL,
    `memory_percent` DECIMAL(9,2) NOT NULL,
    `pids` INT UNSIGNED NULL,
    `host_load_1` DECIMAL(9,2) NULL,
    `payload_json` JSON NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_performance_spike_sample_incident_time` (`incident_key`, `captured_at`),
    KEY `idx_performance_spike_sample_service_time` (`service_name`, `captured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `performance_spike_evidence` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `incident_key` VARCHAR(100) NOT NULL,
    `captured_at` DATETIME(6) NOT NULL,
    `source` VARCHAR(32) NOT NULL,
    `phase` ENUM('open','close') NOT NULL,
    `evidence_json` JSON NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_performance_spike_evidence_incident_time` (`incident_key`, `captured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Created performance spike incident tables.\n";
