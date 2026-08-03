<?php
/**
 * Migration: add a stable LOGICAL observation order to endorse/endorse_logs.
 *
 * `stats_observed_at` (a timestamp) proved unsafe as the freshness authority because a
 * retry's physical request-start time is later than a newer job's — an old job's retry could
 * outrank a newer job. `stats_observation_seq` is the queue row id (DB-generated, monotonic),
 * which is STABLE across all attempts/retries of one logical refresh generation and strictly
 * greater for a genuinely newer refresh. The atomic apply guard compares this sequence.
 *
 * stats_observed_at is retained for the log-date bucket and diagnostics only.
 *
 * Variables injected by run.php: $pdo (PDO), $direction ('up'|'down').
 */

$hasColumn = function (PDO $pdo, string $table, string $col): bool {
    $r = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col))->fetchAll();
    return !empty($r);
};

if ($direction === 'down') {
    foreach (['endorse', 'endorse_logs'] as $t) {
        if ($hasColumn($pdo, $t, 'stats_observation_seq')) {
            $pdo->exec("ALTER TABLE `$t` DROP COLUMN `stats_observation_seq`");
            echo "Dropped $t.stats_observation_seq.\n";
        }
    }
    return;
}

foreach (['endorse', 'endorse_logs'] as $t) {
    if (!$hasColumn($pdo, $t, 'stats_observation_seq')) {
        $pdo->exec("ALTER TABLE `$t` ADD COLUMN `stats_observation_seq` BIGINT UNSIGNED NULL AFTER `stats_observed_at`");
        echo "Added $t.stats_observation_seq.\n";
    } else {
        echo "$t.stats_observation_seq already exists, skipping.\n";
    }
}
