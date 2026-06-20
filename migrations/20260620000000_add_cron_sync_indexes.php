<?php
/**
 * Migration: Add sargable indexes for the per-minute sync crons.
 *
 * The endorse / influencer / influencer_dummy sync crons filter on `sync_at`
 * (paired with status, and for endorse also status_campaign). Previously these
 * WHERE clauses wrapped the column in DATE(), which made any sync_at index
 * unusable and forced full-table scans every minute (see
 * docs/2026-06-20-server-cpu-optimization.md). The companion code change strips
 * DATE() so the column is bare; these composite indexes then serve the access path.
 *
 * INTERIM ONLY: these indexes support the live CI3/MySQL app until the Postgres
 * migration; they are not part of the target schema.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260620000000_add_cron_sync_indexes.php
 * Down: php migrations/run.php 20260620000000_add_cron_sync_indexes.php down
 */

// [table, index_name, columns]
$indexes = [
    ['endorse',          'idx_status_sync', '(`status`, `status_campaign`, `sync_at`)'],
    ['influencer',       'idx_status_sync', '(`status`, `sync_at`)'],
    ['influencer_dummy', 'idx_status_sync', '(`status`, `sync_at`)'],
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
