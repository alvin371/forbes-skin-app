<?php
/**
 * Migration: create notification_outbox.
 *
 * FCM Phase 3. The push delivery queue. NotificationDispatcher writes one row per
 * recipient (PushChannel); the Phase 5 cron worker (Api_v2::cronjob_notification_dispatch)
 * claims PENDING rows, expands each to the user's live device_tokens at send time, and
 * marks SENT / retries with exponential backoff (DEAD after max_attempts). Generic title
 * + body live here; routing IDs go in data_json (no PII rides the push).
 *
 * worker_id supports safe concurrent claims (atomic UPDATE lease), mirroring
 * endorse_refresh_queue.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260618150000_create_notification_outbox.php
 * Down: php migrations/run.php 20260618150000_create_notification_outbox.php down
 */

$tableExists = static function (PDO $pdo, string $table): bool {
    return !empty($pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll());
};

if ($direction === 'down') {
    if ($tableExists($pdo, 'notification_outbox')) {
        $pdo->exec("DROP TABLE `notification_outbox`");
        echo "Dropped table notification_outbox.\n";
    } else {
        echo "Table notification_outbox does not exist, skipping.\n";
    }
    return;
}

if ($tableExists($pdo, 'notification_outbox')) {
    echo "Table notification_outbox already exists, skipping.\n";
    return;
}

$pdo->exec(
    "CREATE TABLE `notification_outbox` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `event_key` VARCHAR(80) NULL DEFAULT NULL,
        `title` VARCHAR(255) NOT NULL,
        `body` TEXT NOT NULL,
        `data_json` JSON NULL DEFAULT NULL,
        `status` ENUM('PENDING','SENDING','SENT','FAILED','DEAD') NOT NULL DEFAULT 'PENDING',
        `attempts` INT NOT NULL DEFAULT 0,
        `max_attempts` INT NOT NULL DEFAULT 5,
        `worker_id` VARCHAR(40) NULL DEFAULT NULL,
        `next_attempt_at` TIMESTAMP NULL DEFAULT NULL,
        `last_error` TEXT NULL DEFAULT NULL,
        `claimed_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `sent_at` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_outbox_status_next` (`status`, `next_attempt_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "Created table notification_outbox.\n";
