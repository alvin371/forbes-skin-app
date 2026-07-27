<?php
/**
 * Add durable remote-job state for the Threads scraper integration.
 * Run: php migrations/run.php 20260727090000_add_threads_scraper_queue_state.php
 */

require_once __DIR__ . '/bootstrap.php';

if (($argv[1] ?? '') === 'down') {
    $pdo->exec("UPDATE endorse_refresh_queue SET status = 'pending', provider_job_id = NULL, provider_submitted_at = NULL WHERE status = 'submitted'");
    $pdo->exec("UPDATE endorse_refresh_queue_attempts SET status = 'retrying' WHERE status = 'submitted'");
    $pdo->exec("ALTER TABLE endorse_refresh_queue MODIFY status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending'");
    $pdo->exec("ALTER TABLE endorse_refresh_queue_attempts MODIFY status ENUM('processing','retrying','completed','failed','cancelled') NOT NULL DEFAULT 'processing'");
    foreach (['idx_threads_submit'] as $index) {
        $rows = $pdo->query("SHOW INDEX FROM endorse_refresh_queue WHERE Key_name = " . $pdo->quote($index))->fetchAll();
        if ($rows) $pdo->exec("ALTER TABLE endorse_refresh_queue DROP INDEX `$index`");
    }
    foreach (['provider_job_id', 'provider_submitted_at'] as $column) {
        $rows = $pdo->query("SHOW COLUMNS FROM endorse_refresh_queue LIKE " . $pdo->quote($column))->fetchAll();
        if ($rows) $pdo->exec("ALTER TABLE endorse_refresh_queue DROP COLUMN `$column`");
    }
    echo "Reverted Threads scraper queue state.\n";
    return;
}

$pdo->exec("ALTER TABLE endorse_refresh_queue MODIFY status ENUM('pending','processing','submitted','completed','failed') NOT NULL DEFAULT 'pending'");
$pdo->exec("ALTER TABLE endorse_refresh_queue_attempts MODIFY status ENUM('processing','submitted','retrying','completed','failed','cancelled') NOT NULL DEFAULT 'processing'");

foreach ([
    'provider_job_id' => "ALTER TABLE endorse_refresh_queue ADD COLUMN provider_job_id VARCHAR(64) NULL AFTER active_attempt_id",
    'provider_submitted_at' => "ALTER TABLE endorse_refresh_queue ADD COLUMN provider_submitted_at DATETIME NULL AFTER provider_job_id",
] as $column => $sql) {
    $rows = $pdo->query("SHOW COLUMNS FROM endorse_refresh_queue LIKE " . $pdo->quote($column))->fetchAll();
    if (!$rows) $pdo->exec($sql);
}
$index = $pdo->query("SHOW INDEX FROM endorse_refresh_queue WHERE Key_name = 'idx_threads_submit'")->fetchAll();
if (!$index) $pdo->exec("ALTER TABLE endorse_refresh_queue ADD INDEX idx_threads_submit (platform, status, provider_submitted_at)");
echo "Added Threads scraper queue state.\n";
