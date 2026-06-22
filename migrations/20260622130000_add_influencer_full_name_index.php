<?php
/**
 * Migration: index influencer.full_name for the creator dropdowns / lists.
 *
 * Endorse create/edit pages run `SELECT * FROM influencer ORDER BY full_name ASC`
 * to populate the creator dropdown. With no index on full_name MySQL does a
 * filesort on every page load (~451ms observed live, 2026-06-22). A plain index
 * on full_name lets the ORDER BY use the index (no filesort).
 *
 * Same guard pattern as 20260622020000_add_influencer_username_index.php.
 *
 * Run:  php migrations/run.php 20260622130000_add_influencer_full_name_index.php
 * Down: php migrations/run.php 20260622130000_add_influencer_full_name_index.php down
 */

$indexes = [
    ['influencer', 'idx_influencer_full_name', '(`full_name`)'],
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
