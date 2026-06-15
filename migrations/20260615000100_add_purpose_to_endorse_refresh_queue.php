<?php
/**
 * Migration: Add `purpose` to endorse_refresh_queue.
 *
 * Distinguishes the existing daily metric refresh from the new frozen-snapshot jobs.
 * DEFAULT 'daily' is essential: every existing insert in EndorseRefreshQueueService
 * (enqueueRows / cloneFailedRows) keeps working with zero code change.
 *   - daily   : existing behaviour (Endorse_sync::apply -> daily delta + endorse_logs)
 *   - initial : capture frozen baseline metrics on first content link entry
 *   - final   : capture frozen final metrics when optimization_status -> Completed
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260615000100_add_purpose_to_endorse_refresh_queue.php
 * Down: php migrations/run.php 20260615000100_add_purpose_to_endorse_refresh_queue.php down
 */

if ($direction === 'down') {
    $indexExists = $pdo->query("SHOW INDEX FROM `endorse_refresh_queue` WHERE Key_name = 'idx_purpose_dedup'")->fetchAll();
    if (!empty($indexExists)) {
        $pdo->exec("ALTER TABLE `endorse_refresh_queue` DROP INDEX `idx_purpose_dedup`");
        echo "Dropped index endorse_refresh_queue.idx_purpose_dedup.\n";
    }

    $exists = $pdo->query("SHOW COLUMNS FROM `endorse_refresh_queue` LIKE " . $pdo->quote('purpose'))->fetchAll();
    if (!empty($exists)) {
        $pdo->exec("ALTER TABLE `endorse_refresh_queue` DROP COLUMN `purpose`");
        echo "Dropped column endorse_refresh_queue.purpose.\n";
    } else {
        echo "Column endorse_refresh_queue.purpose does not exist, skipping.\n";
    }
    return;
}

$exists = $pdo->query("SHOW COLUMNS FROM `endorse_refresh_queue` LIKE " . $pdo->quote('purpose'))->fetchAll();
if (empty($exists)) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` ADD COLUMN `purpose` VARCHAR(10) NOT NULL DEFAULT 'daily' AFTER `platform`");
    echo "Added column endorse_refresh_queue.purpose.\n";
} else {
    echo "Column endorse_refresh_queue.purpose already exists, skipping.\n";
}

// Dedup is purpose-scoped: an initial, a final and a daily job for the same endorse
// must NOT swallow each other. This index backs the (id_endorse, purpose, status) lookup.
$indexExists = $pdo->query("SHOW INDEX FROM `endorse_refresh_queue` WHERE Key_name = 'idx_purpose_dedup'")->fetchAll();
if (empty($indexExists)) {
    $pdo->exec("ALTER TABLE `endorse_refresh_queue` ADD INDEX `idx_purpose_dedup` (`id_endorse`, `purpose`, `status`)");
    echo "Added index endorse_refresh_queue.idx_purpose_dedup.\n";
} else {
    echo "Index endorse_refresh_queue.idx_purpose_dedup already exists, skipping.\n";
}
