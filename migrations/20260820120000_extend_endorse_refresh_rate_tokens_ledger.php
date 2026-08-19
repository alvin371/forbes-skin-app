<?php
/**
 * Migration: turn endorse_refresh_rate_tokens into a per-REQUEST ledger.
 *
 * Why extend rather than add a table: this table is already at exactly the right grain — one
 * row per *started* provider request, written by CiDbReservationStore::reserve() immediately
 * before the request goes out, carrying queue_id / attempt_no / created_at(6). Everything a
 * request ledger needs is already there except the outcome.
 *
 * The alternative — a parallel endorse_refresh_request_events table — would double the row
 * count and force a join for every question worth asking. In particular:
 *
 *   -- requests that started and never came back, in one predicate:
 *   WHERE finished_at IS NULL AND created_at < NOW(6) - INTERVAL 60 SECOND
 *
 *   -- the amplification metric the 400/min target is graded on, with no join:
 *   SELECT COUNT(*) / NULLIF(COUNT(DISTINCT CASE WHEN ok = 1 THEN queue_id END), 0)
 *     FROM endorse_refresh_rate_tokens WHERE test_run_id = ?
 *
 * EVERY new column is NULLable with no default change, because the existing INSERT in
 * CiDbReservationStore::reserve() and PdoReservationStore::reserve() names only the original
 * six columns and must keep working verbatim. The rolling-window COUNT(*) still rides
 * idx_scope_window(provider_scope, created_at) as a covering range scan — the limiter's hot
 * path is untouched.
 *
 * Variables injected by run.php: $pdo (PDO), $direction ('up'|'down').
 * Run:  php migrations/run.php 20260820120000_extend_endorse_refresh_rate_tokens_ledger.php
 * Down: php migrations/run.php 20260820120000_extend_endorse_refresh_rate_tokens_ledger.php down
 */

// Helper names are prefixed per-migration on purpose: run.php --pending `include`s every
// pending file into ONE process, so a generic hasColumn() here would silently execute (or be
// executed as) another migration's implementation. See tests/Unit/MigrationHelperIsolationTest.
if (!function_exists('rateTokenLedgerHasTable')) {
    function rateTokenLedgerHasTable(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('rateTokenLedgerHasColumn')) {
    function rateTokenLedgerHasColumn(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('rateTokenLedgerHasIndex')) {
    function rateTokenLedgerHasIndex(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

$table = 'endorse_refresh_rate_tokens';

if (!rateTokenLedgerHasTable($pdo, $table)) {
    echo "Table {$table} does not exist; run 20260803120000 first. Skipping.\n";

    return;
}

// leg is a widening of the existing implicit "provider_scope tells you which provider"
// contract: a scope is per provider+key, a leg is which position it ran in. Both are needed
// to compute (2 - pA) / (pA + (1 - pA) * pB).
$columns = [
    'leg'             => "VARCHAR(24) NULL COMMENT 'direct_scrape|rapidapi — which leg this request was'",
    'ok'              => "TINYINT(1) NULL COMMENT 'terminal outcome; NULL until the request completes'",
    'http_code'       => 'SMALLINT UNSIGNED NULL',
    'curl_errno'      => "SMALLINT UNSIGNED NULL COMMENT 'transport failure code; 28=timeout, 7=connect'",
    'total_time_ms'   => 'INT UNSIGNED NULL',
    'error_class'     => "VARCHAR(24) NULL COMMENT 'Endorse_sync error class, for per-leg failure mix'",
    'retry_after_sec' => "INT UNSIGNED NULL COMMENT 'honoured Retry-After, so 429 handling is auditable'",
    'worker_id'       => 'VARCHAR(64) NULL',
    'test_run_id'     => "CHAR(32) NULL COMMENT 'groups one load-test run; NULL in production'",
    'finished_at'     => "DATETIME(6) NULL COMMENT 'NULL + old created_at = started and never returned'",
];

$indexes = [
    // Retention deletes oldest-first in chunks; without this it is a full scan under load.
    'idx_prune'   => '(`created_at`)',
    // The census query: per-run, per-leg success rate.
    'idx_run_leg' => '(`test_run_id`, `leg`, `ok`)',
    // Per-post request history, for joining legs of the same queue row.
    'idx_queue'   => '(`queue_id`, `created_at`)',
];

if ($direction === 'down') {
    foreach (array_keys($indexes) as $index) {
        if (rateTokenLedgerHasIndex($pdo, $table, $index)) {
            $pdo->exec("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
            echo "Dropped index {$index}.\n";
        }
    }

    foreach (array_keys($columns) as $column) {
        if (rateTokenLedgerHasColumn($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
            echo "Dropped column {$column}.\n";
        }
    }

    // Deliberately does NOT drop idx_scope_window or any original column: rolling back the
    // ledger must never disarm the rate limiter that rides this table.
    echo "Reverted ledger columns on {$table}.\n";

    return;
}

$added = [];
foreach ($columns as $column => $definition) {
    if (!rateTokenLedgerHasColumn($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        $added[] = $column;
    }
}

foreach ($indexes as $index => $definition) {
    if (!rateTokenLedgerHasIndex($pdo, $table, $index)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` {$definition}");
        $added[] = $index;
    }
}

echo $added === []
    ? "Ledger columns already present on {$table}, skipping.\n"
    : ('Extended ' . $table . ' with: ' . implode(', ', $added) . ".\n");
