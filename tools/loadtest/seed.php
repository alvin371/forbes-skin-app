<?php
/**
 * Seed the disposable load-test database.
 *
 * Produces two things:
 *
 *  1. A bulk of VALID posts — the throughput corpus. Every post gets a UNIQUE content id, on
 *     purpose. Reusing one identical post would trigger application idempotency
 *     (stats_observation_seq), give the provider an unrealistic cache hit rate, flatten the
 *     latency distribution, and bypass the real business-key behaviour in endorse_logs. The
 *     measured throughput would then be a measurement of caching, not of the pipeline.
 *
 *  2. A small set of PATHOLOGICAL rows — poison, inconsistent parent/attempt states, stale
 *     results, duplicates, permanently-invalid URLs. These exist so a load run also answers
 *     "does the healthy queue keep draining while these are present?", which is the question
 *     that actually distinguishes a working recovery path from a stalled one.
 *
 * Usage (inside the worker container):
 *   php tools/loadtest/seed.php --count 13500 [--urls tests/fixtures/loadtest_urls.txt]
 *                              [--pathological 1] [--reset 1]
 */

$root = dirname(__DIR__, 2);

$opts = [
    'count' => 13500,
    'urls' => '',
    'pathological' => 1,
    'reset' => 1,
    'campaigns' => 30,
];

for ($i = 1; $i < $argc; $i++) {
    if (strpos($argv[$i], '--') !== 0) {
        continue;
    }
    $key = substr($argv[$i], 2);
    $value = $argv[$i + 1] ?? '';
    if (array_key_exists($key, $opts)) {
        $opts[$key] = is_numeric($value) ? (int) $value : $value;
        $i++;
    }
}

// -- connection ---------------------------------------------------------------
$cfg = ['host' => 'loadtest-mysql', 'port' => '3306', 'user' => 'root', 'pass' => '', 'db' => ''];
if (is_readable($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim(trim($v), "\"'");
        $map = ['DB_HOSTNAME' => 'host', 'DB_PORT' => 'port', 'DB_USERNAME' => 'user', 'DB_PASSWORD' => 'pass', 'DB_DATABASE' => 'db'];
        if (isset($map[trim($k)])) {
            $cfg[$map[trim($k)]] = $v;
        }
    }
}

// Independent restatement of the guard's rule: this script TRUNCATEs, and runs outside the
// CI bootstrap where EndorseRefreshLoadTestGuard lives.
if (substr($cfg['db'], -9) !== '_loadtest') {
    fwrite(STDERR, "REFUSING: DB_DATABASE must end with _loadtest before this script will touch it.\n");
    exit(1);
}

$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};charset=utf8mb4",
    $cfg['user'],
    $cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
// The real dumps carry legacy NOT NULL columns without defaults; relax the same way every
// integration suite does so seeding stays terse.
$pdo->exec("SET SESSION sql_mode=''");

if ($opts['reset']) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['endorse_refresh_queue_attempts', 'endorse_refresh_queue', 'endorse_refresh_rate_tokens',
              'endorse_logs', 'endorse', 'endorse_campaign_logs', 'endorse_campaign'] as $table) {
        $pdo->exec("TRUNCATE TABLE `{$table}`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "Reset data tables.\n";
}

// -- URL corpus ---------------------------------------------------------------
// A real corpus (read-only export of link_upload from production — public content
// identifiers only) gives realistic host/path diversity. Absent that, synthesise unique ids:
// the mock keys its deterministic outcome off the content id, so unique ids are what spread
// outcomes across the corpus instead of collapsing them onto one.
$realUrls = [];
if ($opts['urls'] !== '' && is_readable($root . '/' . $opts['urls'])) {
    foreach (file($root . '/' . $opts['urls'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line !== '' && strpos($line, 'http') === 0) {
            $realUrls[] = $line;
        }
    }
    echo 'Loaded ' . count($realUrls) . " URLs from corpus.\n";
}

$mockHost = 'https://tiktok-mock.local';
$urlFor = function (int $n) use ($realUrls, $mockHost): string {
    if ($realUrls !== []) {
        return $realUrls[$n % count($realUrls)];
    }

    // 19-digit ids in TikTok's real shape, unique per post.
    return $mockHost . '/@loadtest' . ($n % 500) . '/video/7' . str_pad((string) (300000000000000000 + $n), 18, '0', STR_PAD_LEFT);
};

// -- campaigns ----------------------------------------------------------------
$campaigns = max(1, (int) $opts['campaigns']);
$values = [];
for ($c = 1; $c <= $campaigns; $c++) {
    $values[] = "({$c}, 'loadtest campaign {$c}')";
}
$pdo->exec('INSERT INTO endorse_campaign (id, title) VALUES ' . implode(',', $values));
echo "Seeded {$campaigns} campaigns.\n";

// -- bulk valid corpus --------------------------------------------------------
$count = max(1, (int) $opts['count']);
$chunk = 500;
$seeded = 0;

$pdo->beginTransaction();
for ($offset = 0; $offset < $count; $offset += $chunk) {
    $endorseRows = [];
    $queueRows = [];
    $n = min($chunk, $count - $offset);

    for ($i = 0; $i < $n; $i++) {
        $id = $offset + $i + 1;
        $campaign = ($id % $campaigns) + 1;
        $url = $pdo->quote($urlFor($id));
        $endorseRows[] = "({$id}, {$campaign}, 'Tiktok', {$url}, 'loadtest_inf', 'active', '2026-08-19', 0, 0)";
        // priority spread so ORDER BY priority DESC, created_at ASC has something to order by
        // rather than degenerating into pure id order.
        $priority = 10 + ($id % 3);
        $queueRows[] = "({$id}, {$id}, {$campaign}, 'Tiktok', 'daily', {$url}, 'pending', {$priority}, 0, 0, 3, NOW(6))";
    }

    $pdo->exec('INSERT INTO endorse (id, id_campaign, platform, link_upload, influencer, status, posting_at, views, likes) VALUES ' . implode(',', $endorseRows));
    $pdo->exec('INSERT INTO endorse_refresh_queue (id, id_endorse, id_campaign, platform, purpose, link_upload, status, priority, attempts, attempt_sequence, max_attempts, created_at) VALUES ' . implode(',', $queueRows));
    $seeded += $n;
}
$pdo->commit();
echo "Seeded {$seeded} valid queue rows.\n";

// -- pathological fixtures ----------------------------------------------------
// Deliberately AFTER the bulk, at HIGH ids, except where a test needs a low id to prove
// head-of-queue starvation.
$path = [];
if ($opts['pathological']) {
    $base = $count + 1000;
    $mk = function (int $id, int $campaign, string $url, array $queue = [], ?array $attempt = null) use ($pdo, &$path) {
        $q = $pdo->quote($url);
        $pdo->exec("INSERT INTO endorse (id, id_campaign, platform, link_upload, influencer, status, posting_at, views, likes)
                    VALUES ({$id}, {$campaign}, 'Tiktok', {$q}, 'loadtest_inf', 'active', '2026-08-19', 0, 0)");

        $cols = array_merge([
            'id' => $id, 'id_endorse' => $id, 'id_campaign' => $campaign, 'platform' => "'Tiktok'",
            'purpose' => "'daily'", 'link_upload' => $q, 'status' => "'pending'", 'priority' => 10,
            'attempts' => 0, 'attempt_sequence' => 0, 'max_attempts' => 3, 'created_at' => 'NOW(6)',
        ], $queue);
        $pdo->exec('INSERT INTO endorse_refresh_queue (' . implode(',', array_keys($cols)) . ') VALUES (' . implode(',', $cols) . ')');

        if ($attempt !== null) {
            $acols = array_merge(['queue_id' => $id, 'attempt_no' => 1, 'worker_id' => "'dead_worker'",
                                  'status' => "'processing'", 'started_at' => 'NOW(6)', 'created_at' => 'NOW(6)'], $attempt);
            $pdo->exec('INSERT INTO endorse_refresh_queue_attempts (' . implode(',', array_keys($acols)) . ') VALUES (' . implode(',', $acols) . ')');
            $pdo->exec('UPDATE endorse_refresh_queue SET active_attempt_id = ' . (int) $pdo->lastInsertId() . " WHERE id = {$id}");
        }
    };

    // 1. Permanently invalid URL — must reach a TERMINAL classification, not retry forever.
    $mk($base + 1, 1, 'not-a-valid-url');
    $path[] = 'invalid_url';

    // 2. Empty URL — same, via a different code path (claimBatch normalises, fetch refuses).
    $mk($base + 2, 1, '');
    $path[] = 'empty_url';

    // 3. Private/deleted post — the mock serves HTTP 200 with no rehydration payload.
    $mk($base + 3, 1, $mockHost . '/@private/video/7999999999999999001');
    $path[] = 'private_post';

    // 4. Poison row: a ghost attempt already occupies uq_active_queue_attempt, so activating
    //    a new attempt violates the unique key. Must terminate after POISON_MAX_ISOLATIONS
    //    isolations, never retry unboundedly, and never block the healthy corpus.
    $mk($base + 4, 1, $mockHost . '/@poison/video/7999999999999999002', [], ['status' => "'processing'"]);
    $pdo->exec('UPDATE endorse_refresh_queue SET status = \'pending\', worker_id = NULL, active_attempt_id = NULL WHERE id = ' . ($base + 4));
    $path[] = 'poison_ghost_attempt';

    // 5. Inconsistent parent/attempt: parent says processing, attempt says completed.
    //    resetStuck must QUARANTINE this (needs_reconciliation) rather than recover it.
    $mk($base + 5, 1, $mockHost . '/@inconsistent/video/7999999999999999003',
        ['status' => "'processing'", 'worker_id' => "'dead_worker'", 'claimed_at' => 'NOW(6)',
         'started_at' => 'DATE_SUB(NOW(6), INTERVAL 600 SECOND)', 'lease_expires_at' => 'DATE_SUB(NOW(6), INTERVAL 300 SECOND)'],
        ['status' => "'completed'", 'finished_at' => 'NOW(6)']);
    $path[] = 'inconsistent_parent_attempt';

    // 6. Expired lease from a dead worker — the ordinary recovery case, must come back.
    $mk($base + 6, 1, $mockHost . '/@stale/video/7999999999999999004',
        ['status' => "'processing'", 'worker_id' => "'dead_worker'", 'claimed_at' => 'NOW(6)',
         'started_at' => 'DATE_SUB(NOW(6), INTERVAL 600 SECOND)', 'lease_expires_at' => 'DATE_SUB(NOW(6), INTERVAL 300 SECOND)'],
        ['status' => "'processing'"]);
    $path[] = 'expired_lease';

    // 7. Attempts already exhausted — must go terminal, never be claimed again.
    $mk($base + 7, 1, $mockHost . '/@exhausted/video/7999999999999999005',
        ['attempts' => 3, 'attempt_sequence' => 3]);
    $path[] = 'attempts_exhausted';

    // 8. A COMPLETED row for an endorse that also has an active row. The partial-unique
    //    uq_active_endorse_purpose permits this (completed rows have a NULL slot); it exists
    //    to prove the constraint does not reject legitimate history.
    $dupEndorse = $base + 8;
    $mk($dupEndorse, 1, $mockHost . '/@dup/video/7999999999999999006');
    $pdo->exec("INSERT INTO endorse_refresh_queue (id_endorse, id_campaign, platform, purpose, link_upload, status, priority, attempts, attempt_sequence, max_attempts, created_at, completed_at)
                VALUES ({$dupEndorse}, 1, 'Tiktok', 'daily', " . $pdo->quote($mockHost . '/@dup/video/7999999999999999006') . ", 'completed', 10, 1, 1, 3, NOW(6), NOW(6))");
    $path[] = 'completed_history_alongside_active';

    echo 'Seeded ' . count($path) . " pathological rows: " . implode(', ', $path) . ".\n";
}

// -- summary ------------------------------------------------------------------
$summary = $pdo->query("
    SELECT status, COUNT(*) AS n FROM endorse_refresh_queue GROUP BY status ORDER BY status
")->fetchAll(PDO::FETCH_ASSOC);

echo "\nQueue state:\n";
foreach ($summary as $row) {
    printf("  %-12s %d\n", $row['status'], $row['n']);
}

$pending = (int) $pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status = 'pending'")->fetchColumn();
printf("\nClaimable now: %d\n", $pending);
echo "Seed complete.\n";
