<?php

/** Run inside forbes_app: accepts one already-sanitized JSON event on stdin. */
declare(strict_types=1);

$raw = stream_get_contents(STDIN);
$payload = json_decode($raw, true);
if (! is_array($payload) || empty($payload['type']) || empty($payload['ts'])) {
    fwrite(STDERR, "invalid monitoring payload\n");

    exit(1);
}

$env = [];
foreach (@file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    if (str_starts_with(ltrim($line), '#') || ! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim(trim($value), "\"'");
}
$db = @new mysqli($env['DB_HOSTNAME'] ?? '', $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', $env['DB_DATABASE'] ?? '', (int) ($env['DB_PORT'] ?? 3306));
if ($db->connect_error) {
    fwrite(STDERR, "monitor database unavailable\n");

    exit(1);
}

function monitoringAt(string $timestamp): string
{
    $at = rtrim(str_replace('T', ' ', substr($timestamp, 0, 26)), 'Z');

    return str_contains($at, '.') ? $at : $at . '.000000';
}

function monitoringJson($value): string
{
    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}';
}

function monitoringLoadOne(string $load): ?float
{
    $part = preg_split('/\s+/', trim($load))[0] ?? null;

    return is_numeric($part) ? (float) $part : null;
}

function monitoringRequestSummary(array $evidence): array
{
    $users = [];
    $requests = 0;
    $anonymous = 0;
    $endpoints = [];
    foreach (($evidence['request_performance'] ?? []) as $record) {
        if (! is_array($record) || ($record['type'] ?? '') !== 'request_performance') {
            continue;
        }
        ++$requests;
        $userId = $record['user_id'] ?? null;
        if ($userId === null || $userId === '') {
            ++$anonymous;
        } else {
            $users[(string) $userId] = true;
        }
        $service = substr((string) ($record['service'] ?? 'unknown'), 0, 64);
        $route = substr((string) ($record['route'] ?? '/'), 0, 255);
        $key = $service . '|' . $route;
        if (! isset($endpoints[$key])) {
            $endpoints[$key] = ['service' => $service, 'route' => $route, 'requests' => 0, 'active_users' => [], 'total_ms' => 0.0, 'max_ms' => 0.0, 'db_ms' => 0.0, 'db_queries' => 0, 'statuses' => []];
        }
        $duration = (float) ($record['duration_ms'] ?? 0);
        $endpoint = &$endpoints[$key];
        ++$endpoint['requests'];
        $endpoint['total_ms'] += $duration;
        $endpoint['max_ms'] = max($endpoint['max_ms'], $duration);
        $endpoint['db_ms'] += (float) ($record['db']['time_ms'] ?? 0);
        $endpoint['db_queries'] += (int) ($record['db']['count'] ?? 0);
        $status = (string) ($record['http_status'] ?? 0);
        $endpoint['statuses'][$status] = ($endpoint['statuses'][$status] ?? 0) + 1;
        if ($userId !== null && $userId !== '') {
            $endpoint['active_users'][(string) $userId] = true;
        }
        unset($endpoint);
    }
    foreach ($endpoints as &$endpoint) {
        $endpoint['active_users'] = count($endpoint['active_users']);
        $endpoint['total_ms'] = round($endpoint['total_ms'], 2);
        $endpoint['max_ms'] = round($endpoint['max_ms'], 2);
        $endpoint['db_ms'] = round($endpoint['db_ms'], 2);
    }
    unset($endpoint);
    $endpoints = array_values($endpoints);
    usort($endpoints, static fn (array $a, array $b): int => $b['total_ms'] <=> $a['total_ms']);

    return [
        'request_count' => $requests,
        'active_user_count' => count($users),
        'anonymous_request_count' => $anonymous,
        'top_endpoints' => array_slice($endpoints, 0, 20),
    ];
}

function monitoringMysqlCounters(string $diagnostics): array
{
    $allowed = [
        'Threads_connected', 'Threads_running', 'Questions', 'Slow_queries',
        'Created_tmp_disk_tables', 'Created_tmp_tables', 'Handler_read_rnd_next',
        'Innodb_buffer_pool_reads', 'Innodb_row_lock_time', 'Innodb_row_lock_waits',
    ];
    $counters = [];
    foreach (preg_split('/\R/', $diagnostics) as $line) {
        $parts = preg_split('/\t+/', trim($line));
        if (count($parts) !== 2 || ! in_array($parts[0], $allowed, true) || ! is_numeric($parts[1])) {
            continue;
        }
        $counters[$parts[0]] = (float) $parts[1];
    }

    return $counters;
}

function monitoringMysqlCounterDelta(mysqli $db, string $key, array $closeEvidence): array
{
    $current = monitoringMysqlCounters((string) ($closeEvidence['mysql_diagnostics'] ?? ''));
    if (empty($current)) {
        return [];
    }
    $stmt = $db->prepare("SELECT evidence_json FROM performance_spike_evidence WHERE incident_key=? AND phase='open' ORDER BY captured_at DESC LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $encoded = null;
    $stmt->bind_result($encoded);
    $stmt->fetch();
    $open = is_string($encoded) ? json_decode($encoded, true) : [];
    $baseline = is_array($open) ? monitoringMysqlCounters((string) ($open['mysql_diagnostics'] ?? '')) : [];
    $delta = [];
    foreach ($current as $name => $value) {
        if (array_key_exists($name, $baseline)) {
            $delta[$name] = $value - $baseline[$name];
        }
    }

    return $delta;
}

function persistIncident(mysqli $db, array $payload): void
{
    $type = (string) $payload['type'];
    $key = substr((string) ($payload['incident_key'] ?? ''), 0, 100);
    $service = substr((string) ($payload['service_name'] ?? ''), 0, 64);
    if ($key === '' || $service === '') {
        throw new InvalidArgumentException('missing incident identity');
    }
    $at = monitoringAt((string) $payload['ts']);
    $startedAt = monitoringAt((string) ($payload['started_at'] ?? $payload['ts']));
    $cpu = (float) ($payload['cpu_percent'] ?? 0);
    $memory = (float) ($payload['memory_percent'] ?? 0);
    $pids = max(0, (int) ($payload['pids'] ?? 0));
    $peakCpu = (float) ($payload['peak_cpu_percent'] ?? $cpu);
    $peakMemory = (float) ($payload['peak_memory_percent'] ?? $memory);
    $load = monitoringLoadOne((string) ($payload['host_load'] ?? ''));
    $json = monitoringJson($payload);

    if ($type === 'performance_spike_open') {
        $stmt = $db->prepare('INSERT IGNORE INTO performance_spike_incidents (incident_key,service_name,status,started_at,peak_cpu_percent,peak_memory_percent,summary_json) VALUES (?, ?, \'open\', ?, ?, ?, ?)');
        $summary = monitoringJson(['threshold_cpu_percent' => 60, 'opened_by' => 'host collector']);
        $stmt->bind_param('sssdds', $key, $service, $startedAt, $peakCpu, $peakMemory, $summary);
        $stmt->execute();
    }

    if (in_array($type, ['performance_spike_open', 'performance_spike_sample', 'performance_spike_close'], true)) {
        $stmt = $db->prepare('INSERT INTO performance_spike_samples (incident_key,captured_at,service_name,cpu_percent,memory_percent,pids,host_load_1,payload_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssddids', $key, $at, $service, $cpu, $memory, $pids, $load, $json);
        $stmt->execute();
        $stmt = $db->prepare('UPDATE performance_spike_incidents SET peak_cpu_percent=GREATEST(peak_cpu_percent, ?), peak_memory_percent=GREATEST(peak_memory_percent, ?), sample_count=sample_count+1 WHERE incident_key=?');
        $stmt->bind_param('dds', $peakCpu, $peakMemory, $key);
        $stmt->execute();
    }

    if (isset($payload['evidence']) && is_array($payload['evidence'])) {
        $phase = $type === 'performance_spike_close' ? 'close' : 'open';
        $evidence = monitoringJson($payload['evidence']);
        $source = substr((string) ($payload['source'] ?? 'host'), 0, 32);
        $stmt = $db->prepare('INSERT INTO performance_spike_evidence (incident_key,captured_at,source,phase,evidence_json) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $key, $at, $source, $phase, $evidence);
        $stmt->execute();
    }

    if ($type === 'performance_spike_close') {
        $evidencePayload = is_array($payload['evidence'] ?? null) ? $payload['evidence'] : [];
        $summary = monitoringRequestSummary($evidencePayload);
        $summary['mysql_counter_delta'] = monitoringMysqlCounterDelta($db, $key, $evidencePayload);
        $ended = $at;
        $startedEpoch = strtotime($startedAt . ' UTC');
        $endedEpoch = strtotime($ended . ' UTC');
        $duration = ($startedEpoch === false || $endedEpoch === false) ? null : max(0, $endedEpoch - $startedEpoch);
        $endpointJson = monitoringJson($summary['top_endpoints']);
        $summaryJson = monitoringJson($summary);
        $stmt = $db->prepare("UPDATE performance_spike_incidents SET status='closed', ended_at=?, duration_seconds=?, peak_cpu_percent=GREATEST(peak_cpu_percent, ?), peak_memory_percent=GREATEST(peak_memory_percent, ?), request_count=?, active_user_count=?, anonymous_request_count=?, endpoint_summary_json=?, summary_json=? WHERE incident_key=?");
        $stmt->bind_param('siddiiisss', $ended, $duration, $peakCpu, $peakMemory, $summary['request_count'], $summary['active_user_count'], $summary['anonymous_request_count'], $endpointJson, $summaryJson, $key);
        $stmt->execute();
    }
}

try {
    if (str_starts_with((string) $payload['type'], 'performance_spike_')) {
        persistIncident($db, $payload);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'monitor persistence failed: ' . get_class($e) . "\n");
    $db->close();

    exit(1);
}

// Keep the existing queue diagnostics retention bounded as well as the new data.
if (gmdate('Hi') === '0000') {
    foreach ([
        'endorse_refresh_queue_attempt_archive' => 'archived_at',
        'endorse_refresh_queue_archive' => 'archived_at',
        'endorse_refresh_resource_snapshots' => 'captured_at',
        'endorse_refresh_spikes' => 'captured_at',
        'endorse_refresh_runs' => 'started_at',
        'performance_spike_evidence' => 'captured_at',
        'performance_spike_samples' => 'captured_at',
        'performance_spike_incidents' => 'started_at',
    ] as $table => $column) {
        $db->query("DELETE FROM `{$table}` WHERE `{$column}` < (UTC_TIMESTAMP(6) - INTERVAL 30 DAY)");
    }
}
$db->close();
