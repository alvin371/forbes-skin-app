<?php
/**
 * Slow-request report — aggregates the monitoring request logs into a human
 * answer to: who is using the app, when it slows, which endpoints and queries
 * are responsible. Reads the JSON-line logs written by monitoring_write_log():
 *   application/logs/monitor-YYYY-MM-DD.log  (one line per request)
 *   application/logs/slow-YYYY-MM-DD.log     (per-query dump for slow requests)
 *
 * Usage:
 *   php application/monitoring/slow_report.php [YYYY-MM-DD] [top_n] [slow_ms]
 *   (defaults: today, top 15, slow>=1000ms)
 *
 * No framework bootstrap — safe to run on prod against the live log files.
 */

date_default_timezone_set('Asia/Jakarta');
$date = $argv[1] ?? date('Y-m-d');
$topN = isset($argv[2]) ? max(1, (int) $argv[2]) : 15;
$slowMs = isset($argv[3]) ? (int) $argv[3] : 1000;

$logDir = __DIR__ . '/../logs';
$monitorLog = "$logDir/monitor-$date.log";
$slowLog = "$logDir/slow-$date.log";

if (!is_readable($monitorLog)) {
    fwrite(STDERR, "No monitor log for $date at $monitorLog\n");
    exit(1);
}

/** Group an endpoint: drop query string, collapse numeric path segments. */
function endpoint_key(array $r): string
{
    $uri = (string) ($r['uri'] ?? ($r['route'] ?? '/'));
    $uri = explode('?', $uri, 2)[0];
    $uri = preg_replace('#/\d+#', '/{id}', $uri);
    return (($r['method'] ?? 'GET') . ' ' . $uri);
}
function norm_sql(string $sql): string
{
    $sql = preg_replace('/\s+/', ' ', trim($sql));
    $sql = preg_replace("/'[^']*'/", '?', $sql);
    $sql = preg_replace('/\b\d+\b/', '?', $sql);
    $sql = preg_replace('/IN \([^)]*\)/i', 'IN (?)', $sql);
    return substr($sql, 0, 120);
}

$total = 0;
$byEndpoint = [];   // key => [count, sum_ms, max_ms, sum_q, sum_qms, statuses]
$byUser = [];       // username => [count, sum_ms, max_ms]
$slowReqs = [];     // individual slow requests
$statusCounts = [];
$hourly = [];       // hour => [count, sum_ms]

$fh = fopen($monitorLog, 'r');
while (($line = fgets($fh)) !== false) {
    $r = json_decode($line, true);
    if (!is_array($r) || ($r['type'] ?? '') !== 'request') {
        continue;
    }
    $total++;
    $dur = (int) ($r['duration_ms'] ?? 0);
    $db = is_array($r['db'] ?? null) ? $r['db'] : [];
    $qc = (int) ($db['count'] ?? 0);
    $qms = (float) ($db['time_ms'] ?? 0);
    $user = $r['user']['username'] ?? '(anon)';
    $status = (int) ($r['status_code'] ?? 0);
    $hour = substr((string) ($r['ts'] ?? ''), 11, 2);

    $k = endpoint_key($r);
    $e = $byEndpoint[$k] ?? [0, 0, 0, 0, 0.0];
    $e[0]++; $e[1] += $dur; $e[2] = max($e[2], $dur); $e[3] += $qc; $e[4] += $qms;
    $byEndpoint[$k] = $e;

    $u = $byUser[$user] ?? [0, 0, 0];
    $u[0]++; $u[1] += $dur; $u[2] = max($u[2], $dur);
    $byUser[$user] = $u;

    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    if ($hour !== '') {
        $h = $hourly[$hour] ?? [0, 0];
        $h[0]++; $h[1] += $dur; $hourly[$hour] = $h;
    }

    if ($dur >= $slowMs) {
        $slowReqs[] = [
            'ts' => $r['ts'] ?? '', 'dur' => $dur, 'user' => $user,
            'ep' => $k, 'qc' => $qc, 'qms' => $qms,
            'slow_sql' => $db['slowest_sql'] ?? null, 'slow_ms' => $db['slowest_ms'] ?? 0,
            'status' => $status,
        ];
    }
}
fclose($fh);

// slow-channel: aggregate worst queries across slow requests
$byQuery = []; // norm_sql => [count, sum_ms, max_ms, sample]
if (is_readable($slowLog)) {
    $sh = fopen($slowLog, 'r');
    while (($line = fgets($sh)) !== false) {
        $r = json_decode($line, true);
        if (!is_array($r) || empty($r['queries'])) {
            continue;
        }
        foreach ($r['queries'] as $q) {
            $sql = (string) ($q['sql'] ?? '');
            if ($sql === '') { continue; }
            $ms = (float) ($q['ms'] ?? 0);
            $nk = norm_sql($sql);
            $g = $byQuery[$nk] ?? [0, 0.0, 0.0, $sql];
            $g[0]++; $g[1] += $ms; $g[2] = max($g[2], $ms);
            $byQuery[$nk] = $g;
        }
    }
    fclose($sh);
}

// ---- output ----
$line = str_repeat('=', 78);
echo "$line\nSLOW-REQUEST REPORT  $date   (total requests: $total, slow>= {$slowMs}ms)\n$line\n";

echo "\n## STATUS CODES\n";
krsort($statusCounts);
foreach ($statusCounts as $s => $c) { printf("  %-5s %d\n", $s, $c); }

echo "\n## SLOWEST ENDPOINTS (by total time = real load)\n";
printf("  %-46s %6s %8s %7s %7s %6s\n", 'endpoint', 'count', 'tot_s', 'avg_ms', 'max_ms', 'q/req');
uasort($byEndpoint, fn($a, $b) => $b[1] <=> $a[1]);
foreach (array_slice($byEndpoint, 0, $topN, true) as $k => $e) {
    printf("  %-46s %6d %8.1f %7d %7d %6.1f\n",
        substr($k, 0, 46), $e[0], $e[1] / 1000, $e[1] / max(1, $e[0]), $e[2], $e[3] / max(1, $e[0]));
}

echo "\n## TOP USERS (who generates the load)\n";
printf("  %-24s %6s %8s %7s\n", 'user', 'reqs', 'tot_s', 'max_ms');
uasort($byUser, fn($a, $b) => $b[1] <=> $a[1]);
foreach (array_slice($byUser, 0, $topN, true) as $u => $v) {
    printf("  %-24s %6d %8.1f %7d\n", substr($u, 0, 24), $v[0], $v[1] / 1000, $v[2]);
}

echo "\n## REQUESTS PER HOUR (when it's busy)\n";
ksort($hourly);
foreach ($hourly as $h => $v) {
    printf("  %s:00  %5d reqs  avg %5dms\n", $h, $v[0], $v[1] / max(1, $v[0]));
}

echo "\n## SLOWEST INDIVIDUAL REQUESTS (who + what + which query)\n";
usort($slowReqs, fn($a, $b) => $b['dur'] <=> $a['dur']);
foreach (array_slice($slowReqs, 0, $topN) as $s) {
    printf("  %s  %6dms  %-16s %-40s  %dq/%.0fms\n",
        substr($s['ts'], 11, 8), $s['dur'], substr($s['user'], 0, 16),
        substr($s['ep'], 0, 40), $s['qc'], $s['qms']);
    if (!empty($s['slow_sql'])) {
        printf("        slowest query (%.0fms): %s\n", $s['slow_ms'], substr(preg_replace('/\s+/', ' ', $s['slow_sql']), 0, 110));
    }
}

if ($byQuery) {
    echo "\n## WORST QUERIES (aggregated from slow requests)\n";
    printf("  %-60s %6s %8s %7s\n", 'normalized query', 'count', 'tot_ms', 'max_ms');
    uasort($byQuery, fn($a, $b) => $b[1] <=> $a[1]);
    foreach (array_slice($byQuery, 0, $topN, true) as $nk => $g) {
        printf("  %-60s %6d %8.0f %7.0f\n", substr($nk, 0, 60), $g[0], $g[1], $g[2]);
    }
}
echo "\n$line\n";
