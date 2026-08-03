<?php
/**
 * Migration: scoped request-start rate reservation table for endorse-refresh.
 *
 * One row per outbound provider request (reserved immediately before the request starts,
 * retained for every outcome). provider_scope isolates budgets per provider + API key so
 * unrelated apps/keys never share a limit. The (provider_scope, created_at) index serves
 * the exact rolling-window count. Retention is bounded by pruneExpired().
 *
 * Variables injected by run.php: $pdo (PDO), $direction ('up'|'down').
 * Run:  php migrations/run.php 20260803120000_create_endorse_refresh_rate_tokens.php
 * Down: php migrations/run.php 20260803120000_create_endorse_refresh_rate_tokens.php down
 */

if ($direction === 'down') {
    $pdo->exec("DROP TABLE IF EXISTS `endorse_refresh_rate_tokens`");
    echo "Dropped table endorse_refresh_rate_tokens.\n";
    return;
}

$exists = $pdo->query("SHOW TABLES LIKE 'endorse_refresh_rate_tokens'")->fetchAll();
if (empty($exists)) {
    $pdo->exec("
        CREATE TABLE `endorse_refresh_rate_tokens` (
            `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `provider_scope` VARCHAR(64)   NOT NULL,
            `created_at`    DATETIME(6)     NOT NULL,
            `run_id`        CHAR(32)        NULL,
            `queue_id`      INT UNSIGNED    NULL,
            `attempt_no`    TINYINT         NULL,
            PRIMARY KEY (`id`),
            KEY `idx_scope_window` (`provider_scope`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Created table endorse_refresh_rate_tokens.\n";
} else {
    echo "Table endorse_refresh_rate_tokens already exists, skipping.\n";
}
