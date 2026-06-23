<?php
/**
 * Migration: create password_reset_tokens.
 *
 * Backs the Forgot Password flow (web Auth + mobile Api_hrms::auth_forgot_password).
 * A reset request stores only the sha256 hash of a random 32-byte token, single-use
 * (used_at), short-lived (expires_at, default 30 min) and tagged by channel
 * (web|api). requested_ip + created_at support per-IP request throttling.
 *
 * No change to the `user` table: legacy MD5 hashes stay as-is and upgrade lazily to
 * Argon2id on the owner's next successful login/reset (dual-hash verify). MD5 removal
 * is deferred tech debt.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260621000000_create_password_reset_tokens.php
 * Down: php migrations/run.php 20260621000000_create_password_reset_tokens.php down
 */

$tableExists = static function (PDO $pdo, string $table): bool {
    return !empty($pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll());
};

if ($direction === 'down') {
    if ($tableExists($pdo, 'password_reset_tokens')) {
        $pdo->exec("DROP TABLE `password_reset_tokens`");
        echo "Dropped table password_reset_tokens.\n";
    } else {
        echo "Table password_reset_tokens does not exist, skipping.\n";
    }
    return;
}

if ($tableExists($pdo, 'password_reset_tokens')) {
    echo "Table password_reset_tokens already exists, skipping.\n";
    return;
}

$pdo->exec(
    "CREATE TABLE `password_reset_tokens` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `token_hash` CHAR(64) NOT NULL,
        `channel` VARCHAR(10) NOT NULL DEFAULT 'web',
        `expires_at` DATETIME NOT NULL,
        `used_at` DATETIME NULL DEFAULT NULL,
        `requested_ip` VARCHAR(45) NULL DEFAULT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_password_reset_token_hash` (`token_hash`),
        KEY `idx_password_reset_user` (`user_id`),
        KEY `idx_password_reset_expires` (`expires_at`),
        KEY `idx_password_reset_ip_created` (`requested_ip`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "Created table password_reset_tokens.\n";
