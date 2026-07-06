<?php
/**
 * Migration: composite index endorse (id_campaign, pic).
 *
 * The reconcile (2026-07-06) routes the campaign chart headline, the summary
 * tiles and the logs footer through Mymodel::endorseCanonicalTotals(), which is
 * `SELECT SUM(views), SUM(likes), ... FROM endorse WHERE id_campaign = ? AND
 * pic IN (...) [+ status/brand]`. The prod harness proved endorse.* already
 * holds each endorse's latest snapshot (SUM(endorse.views) === SUM(latest
 * endorse_logs.views_after), diff 0), so this SUM is THE canonical number every
 * surface shows — and PIC performance grading always filters campaign + pic.
 *
 * (id_campaign, pic) serves that access path directly. The summed columns are
 * left out on purpose: endorse is small (tens of thousands of rows) so the row
 * lookups are cheap; add a covering index only if EXPLAIN later shows a scan.
 *
 * Same guard pattern as 20260623000000_add_endorse_logs_endorse_log_date_index.php.
 *
 * Run:  php migrations/run.php 20260706120000_add_endorse_campaign_pic_index.php
 * Down: php migrations/run.php 20260706120000_add_endorse_campaign_pic_index.php down
 */

$indexes = [
    ['endorse', 'idx_endorse_campaign_pic', '(`id_campaign`, `pic`)'],
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
