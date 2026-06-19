<?php
/**
 * Migration: create device_tokens.
 *
 * FCM Phase 1. Stores per-device push tokens so the (later) push channel can fan a
 * notification out to a user's live devices. One row per FCM registration token
 * (UNIQUE) — a token belongs to exactly one user at a time, reassigned on
 * re-registration (shared device). revoked_at soft-deletes a token the moment FCM
 * reports it dead, without losing history.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260618140000_create_device_tokens.php
 * Down: php migrations/run.php 20260618140000_create_device_tokens.php down
 */

$tableExists = static function (PDO $pdo, string $table): bool {
    return !empty($pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll());
};

if ($direction === 'down') {
    if ($tableExists($pdo, 'device_tokens')) {
        $pdo->exec("DROP TABLE `device_tokens`");
        echo "Dropped table device_tokens.\n";
    } else {
        echo "Table device_tokens does not exist, skipping.\n";
    }
    return;
}

if ($tableExists($pdo, 'device_tokens')) {
    echo "Table device_tokens already exists, skipping.\n";
    return;
}

$pdo->exec(
    "CREATE TABLE `device_tokens` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `token` VARCHAR(255) NOT NULL,
        `platform` ENUM('android','ios','web') NOT NULL,
        `app_version` VARCHAR(30) NULL DEFAULT NULL,
        `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
        `revoked_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_device_token` (`token`),
        KEY `idx_device_user_revoked` (`user_id`, `revoked_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "Created table device_tokens.\n";
