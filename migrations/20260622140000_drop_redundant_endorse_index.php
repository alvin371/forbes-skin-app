<?php
/**
 * Migration: drop a redundant index on `endorse` to speed up writes.
 *
 * `endorse` carries ~20 indexes; every INSERT/UPDATE maintains all of them
 * (~776ms per endorse insert observed live, 2026-06-22). `idx_endorse_campaign_status`
 * (id_campaign, status) is a strict left-prefix of `idx_campaign_status`
 * (id_campaign, status, status_campaign), so the 3-column index already serves every
 * query the 2-column one could — dropping it removes write overhead with no read loss.
 *
 * Conservative: only the provably-redundant prefix index is dropped here. A broader
 * index audit on this table is a separate decision.
 *
 * Run:  php migrations/run.php 20260622140000_drop_redundant_endorse_index.php
 * Down: php migrations/run.php 20260622140000_drop_redundant_endorse_index.php down
 */

// [table, index_name, columns-for-recreate-on-down]
$indexes = [
    ['endorse', 'idx_endorse_campaign_status', '(`id_campaign`, `status`)'],
];

if ($direction === 'down') {
    foreach ($indexes as [$table, $name, $cols]) {
        $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
        if (empty($exists)) {
            $pdo->exec("ALTER TABLE `$table` ADD INDEX `$name` $cols");
            echo "Recreated index $table.$name.\n";
        } else {
            echo "Index $table.$name already exists, skipping.\n";
        }
    }
    return;
}

foreach ($indexes as [$table, $name, $cols]) {
    $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
    if (!empty($exists)) {
        $pdo->exec("ALTER TABLE `$table` DROP INDEX `$name`");
        echo "Dropped redundant index $table.$name.\n";
    } else {
        echo "Index $table.$name does not exist, skipping.\n";
    }
}
