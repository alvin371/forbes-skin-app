<?php
/**
 * Migration: Index influencer.username for the endorse<->influencer join.
 *
 * Endorse::index() (campaign detail listing) and several sibling queries join
 * endorse.nama_creator = influencer.username. influencer.username had NO index, so
 * the join degraded to a nested-loop full scan of influencer for every endorse row:
 * for campaign 31 that meant ~4,483 endorse rows x 483 influencers = 2,165,415 rows
 * examined per call, taking 20-37s. Run repeatedly from the campaign page this pinned
 * the box (host load ~8 on 3 vCPU; sec-forbes_app ~85% CPU; 2026-06-22 incident).
 *
 * influencer is tiny (~483 rows) so this index builds instantly and is fully online.
 * Same guard pattern as 20260620000000_add_cron_sync_indexes.php.
 *
 * INTERIM ONLY: supports the live CI3/MySQL app until the Postgres migration.
 *
 * Run:  php migrations/run.php 20260622020000_add_influencer_username_index.php
 * Down: php migrations/run.php 20260622020000_add_influencer_username_index.php down
 */

$indexes = [
    ['influencer', 'idx_influencer_username', '(`username`)'],
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
