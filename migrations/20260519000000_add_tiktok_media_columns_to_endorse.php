<?php
/**
 * Migration: Add normalized TikTok media columns to endorse.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260519000000_add_tiktok_media_columns_to_endorse.php
 * Down: php migrations/run.php 20260519000000_add_tiktok_media_columns_to_endorse.php down
 */

if ($direction === 'down') {
    $columns = [
        'tiktok_fetched_at',
        'tiktok_content_link',
        'tiktok_cover',
        'tiktok_media_type',
        'tiktok_content_id',
    ];

    foreach ($columns as $column) {
        $exists = $pdo->query("SHOW COLUMNS FROM `endorse` LIKE " . $pdo->quote($column))->fetchAll();
        if (!empty($exists)) {
            $pdo->exec("ALTER TABLE `endorse` DROP COLUMN `$column`");
            echo "Dropped column endorse.$column.\n";
        } else {
            echo "Column endorse.$column does not exist, skipping.\n";
        }
    }
    return;
}

$columns = [
    'tiktok_content_id' => "ALTER TABLE `endorse` ADD COLUMN `tiktok_content_id` VARCHAR(32) NULL AFTER `link_upload`",
    'tiktok_media_type' => "ALTER TABLE `endorse` ADD COLUMN `tiktok_media_type` VARCHAR(16) NULL AFTER `tiktok_content_id`",
    'tiktok_cover' => "ALTER TABLE `endorse` ADD COLUMN `tiktok_cover` TEXT NULL AFTER `tiktok_media_type`",
    'tiktok_content_link' => "ALTER TABLE `endorse` ADD COLUMN `tiktok_content_link` LONGTEXT NULL AFTER `tiktok_cover`",
    'tiktok_fetched_at' => "ALTER TABLE `endorse` ADD COLUMN `tiktok_fetched_at` DATETIME NULL AFTER `tiktok_content_link`",
];

foreach ($columns as $column => $sql) {
    $exists = $pdo->query("SHOW COLUMNS FROM `endorse` LIKE " . $pdo->quote($column))->fetchAll();
    if (empty($exists)) {
        $pdo->exec($sql);
        echo "Added column endorse.$column.\n";
    } else {
        echo "Column endorse.$column already exists, skipping.\n";
    }
}
