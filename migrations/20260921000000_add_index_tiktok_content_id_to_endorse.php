<?php
/**
 * Migration: Index endorse.tiktok_content_id for duplicate-link detection lookups.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260921000000_add_index_tiktok_content_id_to_endorse.php
 * Down: php migrations/run.php 20260921000000_add_index_tiktok_content_id_to_endorse.php down
 */

$indexName = 'idx_endorse_tiktok_content_id';

if ($direction === 'down') {
    $exists = $pdo->query("SHOW INDEX FROM `endorse` WHERE Key_name = " . $pdo->quote($indexName))->fetchAll();
    if (!empty($exists)) {
        $pdo->exec("ALTER TABLE `endorse` DROP INDEX `$indexName`");
        echo "Dropped index endorse.$indexName.\n";
    } else {
        echo "Index endorse.$indexName does not exist, skipping.\n";
    }
    return;
}

$exists = $pdo->query("SHOW INDEX FROM `endorse` WHERE Key_name = " . $pdo->quote($indexName))->fetchAll();
if (empty($exists)) {
    $pdo->exec("ALTER TABLE `endorse` ADD INDEX `$indexName` (`tiktok_content_id`)");
    echo "Added index endorse.$indexName.\n";
} else {
    echo "Index endorse.$indexName already exists, skipping.\n";
}
