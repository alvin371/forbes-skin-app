<?php
/**
 * Migration: add endorse refresh contract v2 control-plane schema.
 *
 * This is additive. It seeds the central runtime/circuit tables, the strict
 * active-attempt identity columns, fallback deduplication storage, quarantine,
 * and partial-observation metadata. It does not activate contract v2 or create
 * the campaign/day unique key while legacy writers are still active.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 */

// Guarded so the file can be included more than once in one process (the disposable
// integration database rebuilds the canonical schema by running these migrations
// directly). run.php includes each migration once, so production behaviour is unchanged.
//
// The names carry this migration's own prefix on purpose. `run.php --pending` loads
// every migration into ONE process, so migrations share a single global function
// namespace: with a generic name like hasColumn(), a later migration defining its own
// hasColumn() would be silently skipped by the guard and would run THIS file's
// implementation instead. A unique prefix makes that collision impossible.
if (! function_exists('contractV2HasTable')) {
    function contractV2HasTable(PDO $pdo, string $table): bool
    {
        return !empty($pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll());
    }
}

if (! function_exists('contractV2HasColumn')) {
    function contractV2HasColumn(PDO $pdo, string $table, string $column): bool
    {
        return !empty($pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column))->fetchAll());
    }
}

if (! function_exists('contractV2HasIndex')) {
    function contractV2HasIndex(PDO $pdo, string $table, string $index): bool
    {
        return !empty($pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name = " . $pdo->quote($index))->fetchAll());
    }
}

if ($direction === 'down') {
    if (contractV2HasTable($pdo, 'endorse_refresh_campaign_log_duplicate_report')) {
        $pdo->exec("DROP TABLE `endorse_refresh_campaign_log_duplicate_report`");
        echo "Dropped table endorse_refresh_campaign_log_duplicate_report.\n";
    }
    if (contractV2HasTable($pdo, 'endorse_refresh_campaign_log_duplicate_archive')) {
        $pdo->exec("DROP TABLE `endorse_refresh_campaign_log_duplicate_archive`");
        echo "Dropped table endorse_refresh_campaign_log_duplicate_archive.\n";
    }
    if (contractV2HasTable($pdo, 'endorse_refresh_quarantine')) {
        $pdo->exec("DROP TABLE `endorse_refresh_quarantine`");
        echo "Dropped table endorse_refresh_quarantine.\n";
    }
    if (contractV2HasTable($pdo, 'endorse_refresh_fallback_calls')) {
        $pdo->exec("DROP TABLE `endorse_refresh_fallback_calls`");
        echo "Dropped table endorse_refresh_fallback_calls.\n";
    }
    if (contractV2HasTable($pdo, 'endorse_refresh_worker_health')) {
        $pdo->exec("DROP TABLE `endorse_refresh_worker_health`");
        echo "Dropped table endorse_refresh_worker_health.\n";
    }
    if (contractV2HasTable($pdo, 'endorse_refresh_provider_health')) {
        $pdo->exec("DROP TABLE `endorse_refresh_provider_health`");
        echo "Dropped table endorse_refresh_provider_health.\n";
    }
    if (contractV2HasTable($pdo, 'endorse_refresh_runtime_control')) {
        $pdo->exec("DROP TABLE `endorse_refresh_runtime_control`");
        echo "Dropped table endorse_refresh_runtime_control.\n";
    }

    if (contractV2HasTable($pdo, 'endorse_refresh_queue_attempts')) {
        if (contractV2HasIndex($pdo, 'endorse_refresh_queue_attempts', 'idx_attempt_active')) {
            $pdo->exec("ALTER TABLE `endorse_refresh_queue_attempts` DROP INDEX `idx_attempt_active`");
            echo "Dropped index endorse_refresh_queue_attempts.idx_attempt_active.\n";
        }
        if (contractV2HasIndex($pdo, 'endorse_refresh_queue_attempts', 'uq_queue_attempt')) {
            $pdo->exec("ALTER TABLE `endorse_refresh_queue_attempts` DROP INDEX `uq_queue_attempt`");
            echo "Dropped index endorse_refresh_queue_attempts.uq_queue_attempt.\n";
        }
        if (contractV2HasColumn($pdo, 'endorse_refresh_queue_attempts', 'status')) {
            $pdo->exec("
                ALTER TABLE `endorse_refresh_queue_attempts`
                MODIFY COLUMN `status` ENUM('processing','retrying','completed','failed') NOT NULL DEFAULT 'processing'
            ");
            echo "Reverted endorse_refresh_queue_attempts.status enum.\n";
        }
    }

    if (contractV2HasTable($pdo, 'endorse_refresh_queue')) {
        if (contractV2HasIndex($pdo, 'endorse_refresh_queue', 'idx_processing_owner')) {
            $pdo->exec("ALTER TABLE `endorse_refresh_queue` DROP INDEX `idx_processing_owner`");
            echo "Dropped index endorse_refresh_queue.idx_processing_owner.\n";
        }
        if (contractV2HasIndex($pdo, 'endorse_refresh_queue', 'idx_claim_ready')) {
            $pdo->exec("ALTER TABLE `endorse_refresh_queue` DROP INDEX `idx_claim_ready`");
            echo "Dropped index endorse_refresh_queue.idx_claim_ready.\n";
        }
        $dropColumns = [];
        foreach (['claim_owner', 'attempt_sequence', 'active_attempt_id', 'next_attempt_at'] as $column) {
            if (contractV2HasColumn($pdo, 'endorse_refresh_queue', $column)) {
                $dropColumns[] = "DROP COLUMN `{$column}`";
            }
        }
        if (!empty($dropColumns)) {
            $pdo->exec("ALTER TABLE `endorse_refresh_queue` " . implode(', ', $dropColumns));
            echo "Dropped endorse_refresh_queue v2 columns.\n";
        }
    }

    foreach ([
        'endorse' => ['stats_completeness', 'stats_fields', 'stats_source', 'stats_observed_at'],
        'endorse_logs' => ['stats_completeness', 'stats_fields', 'stats_source', 'stats_observed_at'],
    ] as $table => $columns) {
        $dropColumns = [];
        foreach ($columns as $column) {
            if (contractV2HasColumn($pdo, $table, $column)) {
                $dropColumns[] = "DROP COLUMN `{$column}`";
            }
        }
        if (!empty($dropColumns)) {
            $pdo->exec("ALTER TABLE `{$table}` " . implode(', ', $dropColumns));
            echo "Dropped {$table} observation metadata columns.\n";
        }
    }

    return;
}

if (!contractV2HasTable($pdo, 'endorse_refresh_runtime_control')) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_runtime_control` (
            `id` TINYINT UNSIGNED NOT NULL,
            `contract_state` ENUM('legacy','activating_v2','v2') NOT NULL DEFAULT 'legacy',
            `owner_state` ENUM('cron','rust','draining_to_cron','draining_to_rust','paused') NOT NULL DEFAULT 'cron',
            `generation` BIGINT UNSIGNED NOT NULL DEFAULT 1,
            `updated_at` DATETIME(6) NOT NULL,
            `updated_by` VARCHAR(64) NOT NULL DEFAULT 'migration',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_runtime_control.\n";
}
$pdo->exec("
    INSERT INTO `endorse_refresh_runtime_control` (`id`, `contract_state`, `owner_state`, `generation`, `updated_at`, `updated_by`)
    VALUES (1, 'legacy', 'cron', 1, UTC_TIMESTAMP(6), 'migration')
    ON DUPLICATE KEY UPDATE
        `updated_at` = `updated_at`
");
echo "Seeded endorse_refresh_runtime_control row.\n";

if (!contractV2HasTable($pdo, 'endorse_refresh_provider_health')) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_provider_health` (
            `provider_key` VARCHAR(32) NOT NULL,
            `state` ENUM('closed','open','half_open') NOT NULL DEFAULT 'closed',
            `generation` BIGINT UNSIGNED NOT NULL DEFAULT 1,
            `reason_code` VARCHAR(64) NOT NULL DEFAULT '',
            `open_until` DATETIME(6) NULL,
            `cooldown_level` INT UNSIGNED NOT NULL DEFAULT 0,
            `probe_worker_id` CHAR(36) NULL,
            `probe_attempt_id` BIGINT UNSIGNED NULL,
            `failure_class` VARCHAR(64) NOT NULL DEFAULT '',
            `failure_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `window_started_at` DATETIME(6) NULL,
            `sample_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `transport_failures` INT UNSIGNED NOT NULL DEFAULT 0,
            `http_failures` INT UNSIGNED NOT NULL DEFAULT 0,
            `parse_failures` INT UNSIGNED NOT NULL DEFAULT 0,
            `zero_stat_failures` INT UNSIGNED NOT NULL DEFAULT 0,
            `auth_failures` INT UNSIGNED NOT NULL DEFAULT 0,
            `timeout_failures` INT UNSIGNED NOT NULL DEFAULT 0,
            `api_failures` INT UNSIGNED NOT NULL DEFAULT 0,
            `invalid_response_failures` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated_at` DATETIME(6) NOT NULL,
            PRIMARY KEY (`provider_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_provider_health.\n";
}
$pdo->exec("
    INSERT INTO `endorse_refresh_provider_health` (`provider_key`, `state`, `generation`, `updated_at`)
    VALUES ('rapidapi', 'closed', 1, UTC_TIMESTAMP(6))
    ON DUPLICATE KEY UPDATE
        `updated_at` = `updated_at`
");
echo "Seeded endorse_refresh_provider_health row.\n";

if (!contractV2HasTable($pdo, 'endorse_refresh_worker_health')) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_worker_health` (
            `owner_key` VARCHAR(16) NOT NULL,
            `state` ENUM('closed','open','half_open') NOT NULL DEFAULT 'closed',
            `generation` BIGINT UNSIGNED NOT NULL DEFAULT 1,
            `reason_code` VARCHAR(64) NOT NULL DEFAULT '',
            `open_until` DATETIME(6) NULL,
            `cooldown_level` INT UNSIGNED NOT NULL DEFAULT 0,
            `failure_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `probe_worker_id` CHAR(36) NULL,
            `probe_attempt_id` BIGINT UNSIGNED NULL,
            `trigger_worker_boot_id` CHAR(36) NULL,
            `task_identity` VARCHAR(128) NOT NULL DEFAULT '',
            `app_version` VARCHAR(64) NOT NULL DEFAULT '',
            `stale_queue_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `stale_attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `first_triggered_at` DATETIME(6) NULL,
            `last_triggered_at` DATETIME(6) NULL,
            `updated_at` DATETIME(6) NOT NULL,
            PRIMARY KEY (`owner_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_worker_health.\n";
}
$pdo->exec("
    INSERT INTO `endorse_refresh_worker_health` (`owner_key`, `state`, `generation`, `updated_at`)
    VALUES
        ('cron', 'closed', 1, UTC_TIMESTAMP(6)),
        ('rust', 'closed', 1, UTC_TIMESTAMP(6))
    ON DUPLICATE KEY UPDATE
        `updated_at` = `updated_at`
");
echo "Seeded endorse_refresh_worker_health rows.\n";

if (!contractV2HasTable($pdo, 'endorse_refresh_fallback_calls')) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_fallback_calls` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `queue_id` INT UNSIGNED NOT NULL,
            `attempt_no` INT UNSIGNED NOT NULL,
            `worker_id` CHAR(36) NOT NULL,
            `status` ENUM('in_progress','completed','failed') NOT NULL DEFAULT 'in_progress',
            `lease_token` CHAR(36) NOT NULL,
            `lease_expires_at` DATETIME(6) NOT NULL,
            `http_status` SMALLINT NULL,
            `reason_code` VARCHAR(64) NOT NULL DEFAULT '',
            `response_json` MEDIUMTEXT NULL,
            `created_at` DATETIME(6) NOT NULL,
            `updated_at` DATETIME(6) NOT NULL,
            `completed_at` DATETIME(6) NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_fallback_identity` (`queue_id`, `attempt_no`, `worker_id`),
            KEY `idx_fallback_cleanup` (`status`, `updated_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_fallback_calls.\n";
}

if (!contractV2HasTable($pdo, 'endorse_refresh_quarantine')) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_quarantine` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_endorse` INT UNSIGNED NOT NULL,
            `platform` VARCHAR(20) NOT NULL,
            `content_key` VARCHAR(191) NOT NULL,
            `canonical_url_hash` CHAR(64) NOT NULL,
            `url_snapshot` VARCHAR(1024) NOT NULL DEFAULT '',
            `reason_code` VARCHAR(64) NOT NULL,
            `detail` VARCHAR(512) NOT NULL DEFAULT '',
            `source` ENUM('operator','provider_permanent_item') NOT NULL DEFAULT 'operator',
            `source_queue_id` INT UNSIGNED NULL,
            `source_attempt_id` BIGINT UNSIGNED NULL,
            `confirmed_at` DATETIME(6) NOT NULL,
            `confirmed_by` VARCHAR(64) NOT NULL DEFAULT '',
            `cleared_at` DATETIME(6) NULL,
            `cleared_by` VARCHAR(64) NULL,
            `clear_reason` VARCHAR(255) NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_quarantine_content` (`id_endorse`, `content_key`),
            KEY `idx_quarantine_lookup` (`id_endorse`, `cleared_at`, `content_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_quarantine.\n";
}

if (!contractV2HasColumn($pdo, 'endorse_refresh_queue', 'claim_owner')) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` ADD COLUMN `claim_owner` ENUM('cron','rust') NULL AFTER `worker_id`");
    echo "Added endorse_refresh_queue.claim_owner.\n";
}
if (!contractV2HasColumn($pdo, 'endorse_refresh_queue', 'attempt_sequence')) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` ADD COLUMN `attempt_sequence` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `attempts`");
    echo "Added endorse_refresh_queue.attempt_sequence.\n";
}
if (!contractV2HasColumn($pdo, 'endorse_refresh_queue', 'active_attempt_id')) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` ADD COLUMN `active_attempt_id` BIGINT UNSIGNED NULL AFTER `attempt_sequence`");
    echo "Added endorse_refresh_queue.active_attempt_id.\n";
}
if (!contractV2HasColumn($pdo, 'endorse_refresh_queue', 'next_attempt_at')) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` ADD COLUMN `next_attempt_at` DATETIME(6) NULL AFTER `claimed_at`");
    echo "Added endorse_refresh_queue.next_attempt_at.\n";
}
$pdo->exec("
    UPDATE `endorse_refresh_queue` q
    LEFT JOIN (
        SELECT `queue_id`, MAX(`attempt_no`) AS `max_attempt_no`
        FROM `endorse_refresh_queue_attempts`
        GROUP BY `queue_id`
    ) a ON a.queue_id = q.id
    SET q.attempt_sequence = COALESCE(a.max_attempt_no, q.attempts, 0)
    WHERE q.attempt_sequence = 0
");
echo "Backfilled endorse_refresh_queue.attempt_sequence.\n";
if (!contractV2HasIndex($pdo, 'endorse_refresh_queue', 'idx_claim_ready')) {
    $pdo->exec("
        ALTER TABLE `endorse_refresh_queue`
        ADD INDEX `idx_claim_ready` (`status`, `worker_id`, `next_attempt_at`, `priority`, `attempts`, `created_at`)
    ");
    echo "Added endorse_refresh_queue.idx_claim_ready.\n";
}
if (!contractV2HasIndex($pdo, 'endorse_refresh_queue', 'idx_processing_owner')) {
    $pdo->exec("
        ALTER TABLE `endorse_refresh_queue`
        ADD INDEX `idx_processing_owner` (`status`, `claim_owner`, `active_attempt_id`)
    ");
    echo "Added endorse_refresh_queue.idx_processing_owner.\n";
}

if (contractV2HasTable($pdo, 'endorse_refresh_queue_attempts')) {
    $pdo->exec("
        ALTER TABLE `endorse_refresh_queue_attempts`
        MODIFY COLUMN `worker_id` CHAR(36) NULL,
        MODIFY COLUMN `status` ENUM('processing','retrying','completed','failed','cancelled') NOT NULL DEFAULT 'processing'
    ");
    echo "Updated endorse_refresh_queue_attempts columns for v2.\n";
    if (contractV2HasIndex($pdo, 'endorse_refresh_queue_attempts', 'idx_queue_attempt')) {
        $pdo->exec("ALTER TABLE `endorse_refresh_queue_attempts` DROP INDEX `idx_queue_attempt`");
        echo "Dropped endorse_refresh_queue_attempts.idx_queue_attempt.\n";
    }
    if (!contractV2HasIndex($pdo, 'endorse_refresh_queue_attempts', 'uq_queue_attempt')) {
        $pdo->exec("ALTER TABLE `endorse_refresh_queue_attempts` ADD UNIQUE KEY `uq_queue_attempt` (`queue_id`, `attempt_no`)");
        echo "Added endorse_refresh_queue_attempts.uq_queue_attempt.\n";
    }
    if (!contractV2HasIndex($pdo, 'endorse_refresh_queue_attempts', 'idx_attempt_active')) {
        $pdo->exec("ALTER TABLE `endorse_refresh_queue_attempts` ADD INDEX `idx_attempt_active` (`queue_id`, `id`, `worker_id`, `status`)");
        echo "Added endorse_refresh_queue_attempts.idx_attempt_active.\n";
    }
}

foreach ([
    'endorse' => 'updated_by',
    'endorse_logs' => 'updated_by',
] as $table => $afterColumn) {
    if (!contractV2HasColumn($pdo, $table, 'stats_completeness')) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `stats_completeness` ENUM('complete','partial') NULL AFTER `{$afterColumn}`");
        echo "Added {$table}.stats_completeness.\n";
    }
    if (!contractV2HasColumn($pdo, $table, 'stats_fields')) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `stats_fields` JSON NULL AFTER `stats_completeness`");
        echo "Added {$table}.stats_fields.\n";
    }
    if (!contractV2HasColumn($pdo, $table, 'stats_source')) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `stats_source` VARCHAR(32) NULL AFTER `stats_fields`");
        echo "Added {$table}.stats_source.\n";
    }
    if (!contractV2HasColumn($pdo, $table, 'stats_observed_at')) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `stats_observed_at` DATETIME(6) NULL AFTER `stats_source`");
        echo "Added {$table}.stats_observed_at.\n";
    }
}

if (!contractV2HasTable($pdo, 'endorse_refresh_campaign_log_duplicate_archive')) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_campaign_log_duplicate_archive` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `source_id` INT UNSIGNED NOT NULL,
            `canonical_id` INT UNSIGNED NOT NULL,
            `id_campaign` INT UNSIGNED NOT NULL,
            `date` DATE NOT NULL,
            `row_json` MEDIUMTEXT NOT NULL,
            `reason_code` VARCHAR(64) NOT NULL DEFAULT 'v2_unique_key_reconciliation',
            `archived_at` DATETIME(6) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_campaign_date` (`id_campaign`, `date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_campaign_log_duplicate_archive.\n";
}

if (!contractV2HasTable($pdo, 'endorse_refresh_campaign_log_duplicate_report')) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_campaign_log_duplicate_report` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `report_hash` CHAR(64) NOT NULL,
            `query_version` VARCHAR(32) NOT NULL,
            `ordered_source_ids` MEDIUMTEXT NOT NULL,
            `canonical_ids` MEDIUMTEXT NOT NULL,
            `source_count` INT UNSIGNED NOT NULL,
            `duplicate_count` INT UNSIGNED NOT NULL,
            `reviewed_by` VARCHAR(64) NOT NULL DEFAULT '',
            `reviewed_at` DATETIME(6) NULL,
            `created_at` DATETIME(6) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_report_hash` (`report_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_campaign_log_duplicate_report.\n";
}
