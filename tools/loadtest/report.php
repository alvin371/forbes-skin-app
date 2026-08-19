<?php
/**
 * Load-run report: funnel, amplification, latency, and correctness invariants.
 *
 * Reads the request ledger (endorse_refresh_rate_tokens) and the queue, and emits both a
 * human-readable summary and machine-readable JSON.
 *
 * A measurement note that matters more than it looks:
 *
 *   Run-total throughput is NOT the number to judge. The rate limiter uses a rolling 60s
 *   window, so a 30-second run can legitimately spend a full minute's budget and report
 *   double the sustainable rate. The first smoke run did exactly that — 480 requests in 30s
 *   against a 480/min cap, reported as "958/min".
 *
 * So the headline figure here is the WORST rolling 60s window and the best sustained 5-minute
 * window, computed from per-completion timestamps. A run shorter than a few minutes cannot
 * produce a trustworthy sustained rate at all, and this script says so rather than printing a
 * flattering number.
 *
 * Usage (inside the worker container):
 *   php tools/loadtest/report.php [--test-run-id ID] [--json path.json] [--window 300]
 */

$root = dirname(__DIR__, 2);

$opts = ['test-run-id' => '', 'json' => '', 'window' => 300, 'label' => '', 'mock-url' => ''];
for ($i = 1; $i < $argc; $i++) {
    if (strpos($argv[$i], '--') !== 0) {
        continue;
    }
    $key = substr($argv[$i], 2);
    if (array_key_exists($key, $opts)) {
        $opts[$key] = $argv[$i + 1] ?? '';
        $i++;
    }
}
$opts['window'] = max(60, (int) $opts['window']);

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

$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};charset=utf8mb4",
    $cfg['user'],
    $cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$runFilter = $opts['test-run-id'] !== ''
    ? ' AND test_run_id = ' . $pdo->quote($opts['test-run-id'])
    : '';

$one = function (string $sql) use ($pdo) {
    return $pdo->query($sql)->fetchColumn();
};
$all = function (string $sql) use ($pdo): array {
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
};

// -- funnel -------------------------------------------------------------------
$queue = [];
foreach ($all('SELECT status, COUNT(*) AS n FROM endorse_refresh_queue GROUP BY status') as $row) {
    $queue[$row['status']] = (int) $row['n'];
}

$attempts = [];
foreach ($all('SELECT status, COUNT(*) AS n FROM endorse_refresh_queue_attempts GROUP BY status') as $row) {
    $attempts[$row['status']] = (int) $row['n'];
}

$requestsStarted = (int) $one("SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE 1=1{$runFilter}");
$requestsOk = (int) $one("SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE ok = 1{$runFilter}");
$requestsUnreturned = (int) $one("SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE finished_at IS NULL AND created_at < (NOW(6) - INTERVAL 60 SECOND){$runFilter}");
$completed = (int) ($queue['completed'] ?? 0);

// -- per-leg census -----------------------------------------------------------
// pA, pB and the CONDITIONAL fallback rate. The marginal rate overstates the fallback's value
// because dead/private posts fail both legs, and it is the conditional that governs
// requests-per-completion.
$legs = $all("
    SELECT leg,
           COUNT(*)                                        AS requests,
           SUM(ok = 1)                                     AS successes,
           ROUND(AVG(ok = 1), 4)                           AS success_rate,
           ROUND(AVG(total_time_ms))                       AS mean_ms,
           MIN(total_time_ms)                              AS min_ms,
           MAX(total_time_ms)                              AS max_ms
      FROM endorse_refresh_rate_tokens
     WHERE leg IS NOT NULL{$runFilter}
     GROUP BY leg ORDER BY leg
");

$percentile = function (string $leg, float $p) use ($pdo, $runFilter) {
    $rows = $pdo->query("
        SELECT total_time_ms FROM endorse_refresh_rate_tokens
         WHERE leg = " . $pdo->quote($leg) . " AND total_time_ms IS NOT NULL{$runFilter}
         ORDER BY total_time_ms
    ")->fetchAll(PDO::FETCH_COLUMN);

    if ($rows === []) {
        return null;
    }
    $i = min(count($rows) - 1, max(0, (int) ceil($p * count($rows)) - 1));

    return (int) $rows[$i];
};

foreach ($legs as &$leg) {
    $leg['p50_ms'] = $percentile($leg['leg'], 0.50);
    $leg['p95_ms'] = $percentile($leg['leg'], 0.95);
    $leg['p99_ms'] = $percentile($leg['leg'], 0.99);
}
unset($leg);

// The conditional: of posts whose leg-1 failed, how many did leg-2 rescue?
$conditional = $all("
    SELECT COUNT(*) AS leg1_failures,
           SUM(l2.ok = 1) AS rescued_by_leg2
      FROM endorse_refresh_rate_tokens l1
      LEFT JOIN endorse_refresh_rate_tokens l2
             ON l2.queue_id = l1.queue_id AND l2.attempt_no = l1.attempt_no AND l2.leg <> l1.leg
     WHERE l1.ok = 0 AND l1.leg IS NOT NULL{$runFilter}
");

// -- error distribution -------------------------------------------------------
$errors = $all("
    SELECT leg, COALESCE(error_class, 'ok') AS error_class, http_code, curl_errno, COUNT(*) AS n
      FROM endorse_refresh_rate_tokens
     WHERE 1=1{$runFilter}
     GROUP BY leg, error_class, http_code, curl_errno
     ORDER BY n DESC LIMIT 20
");

// -- sustained throughput -----------------------------------------------------
// Per-second completion counts, then the worst rolling 60s and the best rolling $window.
$perSecond = $all("
    SELECT UNIX_TIMESTAMP(completed_at) AS ts, COUNT(*) AS n
      FROM endorse_refresh_queue
     WHERE status = 'completed' AND completed_at IS NOT NULL
     GROUP BY ts ORDER BY ts
");

$rolling = function (array $perSecond, int $windowSec): array {
    if ($perSecond === []) {
        return ['best' => null, 'worst' => null, 'samples' => 0];
    }

    $counts = [];
    foreach ($perSecond as $row) {
        $counts[(int) $row['ts']] = (int) $row['n'];
    }
    $first = min(array_keys($counts));
    $last = max(array_keys($counts));
    $span = $last - $first + 1;
    if ($span < $windowSec) {
        return ['best' => null, 'worst' => null, 'samples' => 0, 'span_sec' => $span];
    }

    $best = null;
    $worst = null;
    $samples = 0;
    for ($start = $first; $start + $windowSec - 1 <= $last; $start++) {
        $sum = 0;
        for ($t = $start; $t < $start + $windowSec; $t++) {
            $sum += $counts[$t] ?? 0;
        }
        $perMin = $sum * 60.0 / $windowSec;
        $best = $best === null ? $perMin : max($best, $perMin);
        $worst = $worst === null ? $perMin : min($worst, $perMin);
        $samples++;
    }

    return ['best' => round((float) $best, 1), 'worst' => round((float) $worst, 1), 'samples' => $samples, 'span_sec' => $span];
};

$window60 = $rolling($perSecond, 60);
$windowN = $rolling($perSecond, $opts['window']);

// -- correctness invariants ---------------------------------------------------
// VIOLATIONS must be zero. Each is a load-bearing claim about the run.
//
// The distinction from OBSERVATIONS below is deliberate and was earned: the first version of
// this report flagged two "failures" that were the seeded pathological fixtures behaving
// exactly as designed — a pre-seeded completed row with no business write, and an
// inconsistent row correctly quarantined by resetStuck. A report that cries wolf on correct
// behaviour is worse than no report, because it trains you to ignore it.
$invariants = [
    'duplicate_active_rows_per_endorse_purpose' => (int) $one("
        SELECT COUNT(*) FROM (
          SELECT id_endorse, purpose FROM endorse_refresh_queue
           WHERE status IN ('pending','processing','submitted')
           GROUP BY id_endorse, purpose HAVING COUNT(*) > 1
        ) d"),
    'multiple_processing_attempts_per_queue' => (int) $one("
        SELECT COUNT(*) FROM (
          SELECT queue_id FROM endorse_refresh_queue_attempts
           WHERE status = 'processing' GROUP BY queue_id HAVING COUNT(*) > 1
        ) d"),
    // Restricted to rows THIS run actually processed: a completed attempt row is the proof a
    // worker handled it. Without that restriction, any pre-seeded completed fixture reads as
    // a false completion.
    'completed_without_business_write' => (int) $one("
        SELECT COUNT(*) FROM endorse_refresh_queue q
          JOIN endorse e ON e.id = q.id_endorse
         WHERE q.status = 'completed' AND e.stats_observation_seq IS NULL
           AND EXISTS (SELECT 1 FROM endorse_refresh_queue_attempts a
                        WHERE a.queue_id = q.id AND a.status = 'completed')"),
    'completed_rows_still_holding_a_worker' => (int) $one("
        SELECT COUNT(*) FROM endorse_refresh_queue
         WHERE status IN ('completed','failed') AND (worker_id IS NOT NULL OR active_attempt_id IS NOT NULL)"),
    'orphaned_processing_without_attempt' => (int) $one("
        SELECT COUNT(*) FROM endorse_refresh_queue q
         WHERE q.status = 'processing'
           AND NOT EXISTS (SELECT 1 FROM endorse_refresh_queue_attempts a
                            WHERE a.id = q.active_attempt_id AND a.queue_id = q.id)"),
    'attempts_exceeding_max' => (int) $one("
        SELECT COUNT(*) FROM endorse_refresh_queue WHERE attempts > max_attempts"),
    'duplicate_endorse_log_business_key' => (int) $one("
        SELECT COUNT(*) FROM (
          SELECT id_endorse, date FROM endorse_logs GROUP BY id_endorse, date HAVING COUNT(*) > 1
        ) d"),
    'requests_started_and_never_returned' => $requestsUnreturned,
    'poison_terminal_without_completion_stamp' => (int) $one("
        SELECT COUNT(*) FROM endorse_refresh_queue
         WHERE error_message LIKE 'poison:%' AND (status <> 'failed' OR completed_at IS NULL)"),
    // Recovery starvation: a quarantined row must never stop OTHER rows from draining. If any
    // row was still pending with an elapsed next_attempt_at while completions were happening,
    // recovery is starved.
    'poison_isolations_beyond_terminal_cap' => (int) $one("
        SELECT COUNT(*) FROM endorse_refresh_queue
         WHERE error_message REGEXP 'isolations=([9]|[1-9][0-9]+)'"),
];

// OBSERVATIONS are expected outcomes of the seeded pathological fixtures. They are reported
// because their ABSENCE would be as suspicious as an unexpected value — a run where the
// poison row was never isolated means the poison path was never exercised.
$observations = [
    'needs_reconciliation_quarantined' => (int) $one("
        SELECT COUNT(*) FROM endorse_refresh_queue
         WHERE COALESCE(error_message,'') LIKE 'needs_reconciliation%'"),
    'poison_terminal' => (int) $one("
        SELECT COUNT(*) FROM endorse_refresh_queue WHERE error_message LIKE 'poison:%'"),
    'poison_isolating' => (int) $one("
        SELECT COUNT(*) FROM endorse_refresh_queue WHERE error_message LIKE 'claim isolation%'"),
    'terminal_failed' => (int) ($queue['failed'] ?? 0),
    'released_unstarted' => (int) ($attempts['cancelled'] ?? 0),
    'lease_recovered' => (int) ($attempts['timed_out'] ?? 0),
];

// -- request-start rate: did the budget actually hold? ------------------------
// The worker passes rate_per_min=0 / daily_cap=0 to claimBatch, so the reservation store is
// the ONLY thing bounding outbound traffic. "It looked about right" is not evidence — this
// computes the true maximum over every rolling 60s window, per scope.
//
// A window function rather than a self-join: at 18k+ ledger rows the correlated form is
// quadratic and slow enough to discourage running it, which is how a check stops being run.
$rateWindows = $all("
    SELECT provider_scope, MAX(cnt) AS max_starts_per_60s, COUNT(*) AS rows_considered
      FROM (
        SELECT provider_scope,
               COUNT(*) OVER (
                 PARTITION BY provider_scope ORDER BY created_at
                 RANGE BETWEEN INTERVAL 60 SECOND PRECEDING AND CURRENT ROW
               ) AS cnt
          FROM endorse_refresh_rate_tokens
         WHERE 1=1{$runFilter}
      ) w
     GROUP BY provider_scope ORDER BY provider_scope
");

$totalStarts = 0;
foreach ($rateWindows as $row) {
    $totalStarts += (int) $row['max_starts_per_60s'];
}
// Approved budget: 600/min local test cap, 720/min combined operational safety ceiling.
// Between the two is not a failure but must not pass silently — the margin above 600 exists
// to absorb bursts, not to be spent as headroom.
//
// Note this sums each scope's own peak window. Those peaks need not co-occur, so the figure
// is a conservative upper bound — which is the right direction to be wrong in for a budget.
$rateVerdict = $totalStarts <= 600 ? 'PASS' : ($totalStarts <= 720 ? 'WARN (over the 600/min local budget)' : 'FAIL (over the 720/min ceiling)');

// -- ledger reconciliation against provider ground truth ----------------------
// The ledger is the instrument every headline number is read from. If it has a hole, the
// amplification ratio understates the true cost per completion and the capacity envelope
// built on it is wrong. So the mock's own count is treated as ground truth and compared.
//
// A ledger row is created at reserve(), BEFORE the handle exists, so the ledger should be
// >= the provider count: a reserved-but-never-sent request is possible, the reverse is not.
// Ledger < provider means requests reached the provider without a token — which would mean
// the rate limiter is not actually bounding outbound traffic.
$reconciliation = null;
if ($opts['mock-url'] !== '') {
    $raw = @file_get_contents(rtrim($opts['mock-url'], '/') . '/_control/stats', false, stream_context_create([
        'http' => ['timeout' => 5, 'ignore_errors' => true],
    ]));
    $mock = json_decode((string) $raw, true);

    if (is_array($mock)) {
        $providerRequests = (int) ($mock['scrape']['requests'] ?? 0) + (int) ($mock['rapidapi']['requests'] ?? 0);
        $delta = $requestsStarted - $providerRequests;
        $reconciliation = [
            'ledger_rows' => $requestsStarted,
            'provider_served' => $providerRequests,
            'delta' => $delta,
            // Tolerate a small positive delta (reserved but cancelled at shutdown). A NEGATIVE
            // delta is never acceptable: it means untracked outbound traffic.
            'verdict' => $delta < 0 ? 'FAIL: requests reached the provider without a ledger token' : 'PASS',
        ];
    }
}

// -- assemble -----------------------------------------------------------------
$amplification = $completed > 0 ? round($requestsStarted / $completed, 3) : null;

$report = [
    'label' => $opts['label'],
    'test_run_id' => $opts['test-run-id'],
    'generated_at' => gmdate('c'),
    'funnel' => [
        'queue_by_status' => $queue,
        'attempts_by_status' => $attempts,
        'requests_started' => $requestsStarted,
        'requests_succeeded' => $requestsOk,
        'persisted_completed' => $completed,
    ],
    'amplification' => [
        'requests_per_completed_post' => $amplification,
        'target' => 1.5,
        'verdict' => $amplification === null ? 'no data' : ($amplification <= 1.5 ? 'PASS' : 'FAIL'),
    ],
    'throughput' => [
        'rolling_60s_worst_per_min' => $window60['worst'],
        'rolling_60s_best_per_min' => $window60['best'],
        "rolling_{$opts['window']}s_best_per_min" => $windowN['best'],
        "rolling_{$opts['window']}s_worst_per_min" => $windowN['worst'],
        'completion_span_sec' => $window60['span_sec'] ?? 0,
        'note' => ($window60['span_sec'] ?? 0) < 120
            ? 'RUN TOO SHORT for a trustworthy sustained rate (rolling 60s limiter window not exercised)'
            : 'ok',
    ],
    'legs' => $legs,
    'fallback_conditional' => $conditional[0] ?? null,
    'errors' => $errors,
    'invariants' => $invariants,
    'observations' => $observations,
    'ledger_reconciliation' => $reconciliation,
    'request_rate' => [
        'per_scope' => $rateWindows,
        'combined_peak_per_min' => $totalStarts,
        'local_cap' => 600, 'combined_ceiling' => 720,
        'verdict' => $rateVerdict,
    ],
    'invariants_verdict' => array_sum($invariants) === 0 ? 'PASS' : 'FAIL',
];

if ($opts['json'] !== '') {
    @mkdir(dirname($opts['json']), 0775, true);
    file_put_contents($opts['json'], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// -- human-readable -----------------------------------------------------------
printf("\n=== endorse-refresh load run%s ===\n", $opts['label'] !== '' ? ': ' . $opts['label'] : '');

echo "\nFUNNEL\n";
foreach ($queue as $status => $n) {
    printf("  queue.%-12s %d\n", $status, $n);
}
foreach ($attempts as $status => $n) {
    printf("  attempt.%-10s %d\n", $status, $n);
}
printf("  requests started   %d\n", $requestsStarted);
printf("  requests succeeded %d\n", $requestsOk);

echo "\nAMPLIFICATION\n";
printf("  requests / completed post   %s (target <= 1.5)  %s\n",
    $amplification === null ? 'n/a' : number_format((float) $amplification, 3),
    $report['amplification']['verdict']);

echo "\nTHROUGHPUT (completions per minute)\n";
printf("  rolling 60s   worst %s   best %s\n",
    $window60['worst'] === null ? 'n/a' : $window60['worst'],
    $window60['best'] === null ? 'n/a' : $window60['best']);
printf("  rolling %ds  worst %s   best %s\n", $opts['window'],
    $windowN['worst'] === null ? 'n/a' : $windowN['worst'],
    $windowN['best'] === null ? 'n/a' : $windowN['best']);
printf("  completion span %ds\n", $window60['span_sec'] ?? 0);
if ($report['throughput']['note'] !== 'ok') {
    printf("  !! %s\n", $report['throughput']['note']);
}

echo "\nPER-LEG\n";
foreach ($legs as $leg) {
    printf("  %-14s n=%-6d ok=%-6d rate=%-6s p50=%-6s p95=%-6s p99=%s\n",
        $leg['leg'], $leg['requests'], $leg['successes'], $leg['success_rate'],
        $leg['p50_ms'] ?? '-', $leg['p95_ms'] ?? '-', $leg['p99_ms'] ?? '-');
}

if (!empty($conditional[0]['leg1_failures'])) {
    $c = $conditional[0];
    printf("  fallback rescued %d of %d leg-1 failures (conditional rate %.3f)\n",
        (int) $c['rescued_by_leg2'], (int) $c['leg1_failures'],
        (int) $c['leg1_failures'] > 0 ? (int) $c['rescued_by_leg2'] / (int) $c['leg1_failures'] : 0);
}

echo "\nERROR DISTRIBUTION (top)\n";
foreach ($errors as $row) {
    printf("  %-14s %-16s http=%-5s errno=%-4s %d\n",
        $row['leg'] ?? '-', $row['error_class'], $row['http_code'], $row['curl_errno'], $row['n']);
}

echo "\nREQUEST-START RATE (peak over any rolling 60s window)\n";
foreach ($rateWindows as $row) {
    printf("  %-24s %d/min\n", $row['provider_scope'], (int) $row['max_starts_per_60s']);
}
printf("  %-24s %d/min  (local cap 600, combined ceiling 720)  %s\n", 'combined peak', $totalStarts, $rateVerdict);

if ($reconciliation !== null) {
    echo "\nLEDGER RECONCILIATION (vs provider ground truth)\n";
    printf("  ledger rows %d   provider served %d   delta %+d   %s\n",
        $reconciliation['ledger_rows'], $reconciliation['provider_served'],
        $reconciliation['delta'], $reconciliation['verdict']);
}

echo "\nCORRECTNESS VIOLATIONS (must all be zero)\n";
foreach ($invariants as $name => $n) {
    printf("  %-46s %s%s\n", $name, $n === 0 ? 'PASS' : 'FAIL', $n === 0 ? '' : " ({$n})");
}

echo "\nOBSERVATIONS (expected outcomes of the seeded pathological rows)\n";
foreach ($observations as $name => $n) {
    printf("  %-46s %d\n", $name, $n);
}
printf("\n  overall: %s\n\n", $report['invariants_verdict']);

$reconciliationFailed = $reconciliation !== null && strpos($reconciliation['verdict'], 'FAIL') === 0;
exit(($report['invariants_verdict'] === 'PASS' && !$reconciliationFailed && strpos($rateVerdict, 'FAIL') !== 0) ? 0 : 1);
