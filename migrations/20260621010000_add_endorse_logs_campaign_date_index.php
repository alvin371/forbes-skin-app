<?php
/**
 * Migration: Add a sargable composite index for the campaign logs view.
 *
 * Endorse::logs() (the /endorse/logs page) filters endorse_logs by id_campaign +
 * a single day. The WHERE clause previously wrapped the column in
 * DATE(endorse_logs.date) = '$date', which made any index on `date` unusable and
 * forced a full scan (~24.6k rows examined to return ~438 — confirmed in the
 * 2026-06-21 load test, where /endorse/logs p95 blew to ~14.8s under concurrency).
 * The companion code change (Endorse.php) replaces DATE() with a half-open range on
 * the bare column; this index then serves both that lookup and the latest-date
 * fallback (MAX(date) per campaign).
 *
 * Same pattern as 20260620000000_add_cron_sync_indexes.php.
 *
 * INTERIM ONLY: supports the live CI3/MySQL app until the Postgres migration;
 * not part of the target schema.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260621010000_add_endorse_logs_campaign_date_index.php
 * Down: php migrations/run.php 20260621010000_add_endorse_logs_campaign_date_index.php down
 */

// [table, index_name, columns]
$indexes = [
    ['endorse_logs', 'idx_campaign_date', '(`id_campaign`, `date`)'],
];

if ($direction === 'down') {
    foreach ($indexes as [$table, $name, $cols]) {
        $tableExists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
        if (empty($tableExists)) {
            echo "Table $table does not exist, skipping.\n";
            continue;
        }
        $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
        if (!empty($exists)) {
            $pdo->exec("ALTER TABLE `$table` DROP INDEX `$name`");
            echo "Dropped index $table.$name.\n";
        } else {
            echo "Index $table.$name does not exist, skipping.\n";
        }
    }
    return;
}

foreach ($indexes as [$table, $name, $cols]) {
    $tableExists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
    if (empty($tableExists)) {
        echo "Table $table does not exist, skipping.\n";
        continue;
    }
    $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
    if (empty($exists)) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$name` $cols");
        echo "Added index $table.$name.\n";
    } else {
        echo "Index $table.$name already exists, skipping.\n";
    }
}
