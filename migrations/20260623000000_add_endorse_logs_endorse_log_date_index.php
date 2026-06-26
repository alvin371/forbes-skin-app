<?php
/**
 * Migration: composite index endorse_logs (id_endorse, log_date).
 *
 * Ajax::getChartCampaignLogAggregates() (the /overview?t=kol GRAFIK CAMPAIGN fetch)
 * does `FORCE INDEX (idx_endorse_logs_endorse_log_date)` on every CTE pass over
 * endorse_logs: baseline (`MAX(log_date) ... WHERE log_date < ? GROUP BY id_endorse`),
 * range (`WHERE log_date BETWEEN ? AND ?`), and first_seen (`MIN(log_date) GROUP BY
 * id_endorse`). No migration ever created that index — it only existed (if at all)
 * as a manual change on prod, so FORCE INDEX was either failing or silently ignored,
 * leaving the aggregate as a full endorse_logs scan (~12s observed live).
 *
 * (id_endorse, log_date) covers the join + GROUP BY + log_date range used by all
 * three passes.
 *
 * Same guard pattern as 20260620000000_add_cron_sync_indexes.php.
 *
 * Run:  php migrations/run.php 20260623000000_add_endorse_logs_endorse_log_date_index.php
 * Down: php migrations/run.php 20260623000000_add_endorse_logs_endorse_log_date_index.php down
 */

$indexes = [
    ['endorse_logs', 'idx_endorse_logs_endorse_log_date', '(`id_endorse`, `log_date`)'],
];

if ($direction === 'down') {
    foreach ($indexes as [$table, $name, $cols]) {
        $tableExists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
        if (empty($tableExists)) { echo "Table $table does not exist, skipping.\n"; continue; }
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
    if (empty($tableExists)) { echo "Table $table does not exist, skipping.\n"; continue; }
    $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
    if (empty($exists)) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$name` $cols");
        echo "Added index $table.$name.\n";
    } else {
        echo "Index $table.$name already exists, skipping.\n";
    }
}
