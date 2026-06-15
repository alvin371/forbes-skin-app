<?php
/**
 * Migration: add manual_status to endorse.
 *
 * The team's Google Sheet tracks a manual workflow status (Input / On Process / Done)
 * separate from the app's system status (optimization_status: Not Started / In Progress /
 * Completed, which drives the final auto-fetch). This column holds the manual one so both
 * can be synced to the sheet side by side.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260615000200_add_manual_status_to_endorse.php
 * Down: php migrations/run.php 20260615000200_add_manual_status_to_endorse.php down
 */

if ($direction === 'down') {
    $exists = $pdo->query("SHOW COLUMNS FROM `endorse` LIKE " . $pdo->quote('manual_status'))->fetchAll();
    if (!empty($exists)) {
        $pdo->exec("ALTER TABLE `endorse` DROP COLUMN `manual_status`");
        echo "Dropped column endorse.manual_status.\n";
    } else {
        echo "Column endorse.manual_status does not exist, skipping.\n";
    }
    return;
}

$exists = $pdo->query("SHOW COLUMNS FROM `endorse` LIKE " . $pdo->quote('manual_status'))->fetchAll();
if (empty($exists)) {
    $pdo->exec("ALTER TABLE `endorse` ADD COLUMN `manual_status` VARCHAR(20) NULL");
    echo "Added column endorse.manual_status.\n";
} else {
    echo "Column endorse.manual_status already exists, skipping.\n";
}
