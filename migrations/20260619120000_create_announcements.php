<?php
/**
 * Migration: create announcements.
 *
 * Company-wide HR announcements with publish window, pinning, categorization,
 * priority and soft-delete. Drives the web CRUD module (controller Announcement)
 * and the mobile deep-link detail endpoint (Api_hrms::announcement_detail), whose
 * push payload carries related_table='announcements', related_id=<id>.
 *
 * Indexes target the list-page filters/sorts (status, category, subcategory,
 * priority, publish window, pinned, author, soft-delete) plus composite indexes
 * for the common "published & in-window" and "pinned first" list queries.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260619120000_create_announcements.php
 * Down: php migrations/run.php 20260619120000_create_announcements.php down
 */

$tableExists = static function (PDO $pdo, string $table): bool {
    return !empty($pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll());
};

if ($direction === 'down') {
    if ($tableExists($pdo, 'announcements')) {
        $pdo->exec("DROP TABLE `announcements`");
        echo "Dropped table announcements.\n";
    } else {
        echo "Table announcements does not exist, skipping.\n";
    }
    return;
}

if ($tableExists($pdo, 'announcements')) {
    echo "Table announcements already exists, skipping.\n";
    return;
}

$pdo->exec(
    "CREATE TABLE `announcements` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `content` LONGTEXT NOT NULL,
        `category` VARCHAR(100) NULL DEFAULT NULL,
        `subcategory` VARCHAR(100) NULL DEFAULT NULL,
        `status` ENUM('DRAFT','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
        `priority` ENUM('LOW','MEDIUM','HIGH','URGENT') NOT NULL DEFAULT 'MEDIUM',
        `publish_start_at` DATETIME NULL DEFAULT NULL,
        `publish_end_at` DATETIME NULL DEFAULT NULL,
        `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
        `created_by` INT UNSIGNED NOT NULL,
        `updated_by` INT UNSIGNED NULL DEFAULT NULL,
        `deleted_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_announcements_status` (`status`),
        KEY `idx_announcements_category` (`category`),
        KEY `idx_announcements_subcategory` (`subcategory`),
        KEY `idx_announcements_priority` (`priority`),
        KEY `idx_announcements_publish_start` (`publish_start_at`),
        KEY `idx_announcements_publish_end` (`publish_end_at`),
        KEY `idx_announcements_is_pinned` (`is_pinned`),
        KEY `idx_announcements_created_by` (`created_by`),
        KEY `idx_announcements_deleted_at` (`deleted_at`),
        KEY `idx_announcements_published` (`status`, `publish_start_at`, `publish_end_at`),
        KEY `idx_announcements_category_status` (`category`, `status`),
        KEY `idx_announcements_pinned_status` (`is_pinned`, `status`),
        KEY `idx_announcements_deleted_status` (`deleted_at`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "Created table announcements.\n";
