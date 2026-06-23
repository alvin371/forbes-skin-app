<?php
/**
 * Migration: endorse_logs_daily_rollup — precomputed daily aggregate of endorse_logs.
 *
 * The /overview?t=kol GRAFIK CAMPAIGN fetch (Ajax::getChartCampaignLogAggregates)
 * recomputes likes/comment/share/views/cost from raw endorse_logs on every request,
 * scanning the table several times. This table collapses endorse_logs to ONE row per
 * (id_endorse, log_date) so the dashboard reads O(endorse * days) instead of O(raw logs).
 *
 * Populated by Api_v2::cronjob_endorse_rollup (route api/cronjob/endorse-rollup), which
 * rebuilds a rolling window with INSERT ... ON DUPLICATE KEY UPDATE.
 *
 * Column semantics mirror what getChartCampaignLogAggregates derives per day:
 *   *_delta : SUM(GREATEST(COALESCE(col,0),0)) of the per-log delta columns that day
 *   *_after : MAX of the cumulative snapshot columns that day (cumulative => latest = max)
 *   total_cost   : MAX(total_cost) seen that day
 *   last_updated : MAX(updated_at|created_at|date) that day
 *
 * Run:  php migrations/run.php 20260623001000_create_endorse_logs_daily_rollup.php
 * Down: php migrations/run.php 20260623001000_create_endorse_logs_daily_rollup.php down
 */

$table = 'endorse_logs_daily_rollup';

if ($direction === 'down') {
    $pdo->exec("DROP TABLE IF EXISTS `$table`");
    echo "Dropped table $table.\n";
    return;
}

$exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
if (!empty($exists)) {
    echo "Table $table already exists, skipping.\n";
    return;
}

$pdo->exec("
    CREATE TABLE `$table` (
        `id_endorse`        INT NOT NULL,
        `log_date`          DATE NOT NULL,
        `likes_delta`       BIGINT NOT NULL DEFAULT 0,
        `comment_delta`     BIGINT NOT NULL DEFAULT 0,
        `share_save_delta`  BIGINT NOT NULL DEFAULT 0,
        `views_delta`       BIGINT NOT NULL DEFAULT 0,
        `likes_after`       BIGINT NOT NULL DEFAULT 0,
        `comment_after`     BIGINT NOT NULL DEFAULT 0,
        `share_save_after`  BIGINT NOT NULL DEFAULT 0,
        `views_after`       BIGINT NOT NULL DEFAULT 0,
        `total_cost`        DECIMAL(18,2) NOT NULL DEFAULT 0,
        `last_updated`      DATETIME NULL,
        `rolled_up_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_endorse`, `log_date`),
        KEY `idx_rollup_log_date` (`log_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
echo "Created table $table.\n";
