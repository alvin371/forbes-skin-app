<?php

/** Run inside forbes_app: accepts one safe JSON snapshot on stdin and persists it. */
declare(strict_types=1);

$raw     = stream_get_contents(STDIN);
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
    [$k, $v]       = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}
$db = @new mysqli($env['DB_HOSTNAME'] ?? '', $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', $env['DB_DATABASE'] ?? '', (int) ($env['DB_PORT'] ?? 3306));
if ($db->connect_error) {
    fwrite(STDERR, "monitor database unavailable\n");

    exit(1);
}

function containerCpu(array $payload, string $needle): ?float
{
    foreach (($payload['containers'] ?? []) as $row) {
        if (str_starts_with((string) ($row['name'] ?? ''), $needle)) {
            return isset($row['cpu_percent']) ? (float) ($row['cpu_percent']) : null;
        }
    }

    return null;
}
function intField(string $text, string $name): ?int
{
    return preg_match('/' . preg_quote($name, '/') . '=([0-9]+)/', $text, $m) ? (int) ($m[1]) : null;
}
$at = str_replace('T', ' ', substr((string) $payload['ts'], 0, 26));
$at = rtrim($at, 'Z');
if (! str_contains($at, '.')) {
    $at .= '.000000';
}

if ($payload['type'] === 'resource_snapshot') {
    $load   = preg_split('/\s+/', (string) ($payload['host_load'] ?? ''));
    $memory = (string) ($payload['host_memory'] ?? '');
    $queues = $payload['queue'] ?? [];
    if (empty($queues)) {
        $res = $db->query('SELECT status, COUNT(*) c FROM endorse_refresh_queue GROUP BY status');

        while ($res && ($row = $res->fetch_assoc())) {
            $queues[(string) $row['status']] = (int) ($row['c']);
        }
    }
    $sql        = 'INSERT INTO endorse_refresh_resource_snapshots (captured_at,host_load_1,host_memory_used_bytes,host_memory_total_bytes,app_cpu_percent,mysql_cpu_percent,worker_cpu_percent,queue_pending,queue_processing,queue_completed,queue_failed,payload_json) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE payload_json=VALUES(payload_json),app_cpu_percent=VALUES(app_cpu_percent),mysql_cpu_percent=VALUES(mysql_cpu_percent),worker_cpu_percent=VALUES(worker_cpu_percent),queue_pending=VALUES(queue_pending),queue_processing=VALUES(queue_processing),queue_completed=VALUES(queue_completed),queue_failed=VALUES(queue_failed)';
    $stmt       = $db->prepare($sql);
    $json       = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hostLoad   = isset($load[0]) && is_numeric($load[0]) ? (float) ($load[0]) : null;
    $used       = intField($memory, 'used');
    $total      = intField($memory, 'total');
    $app        = containerCpu($payload, 'forbes_app');
    $mysql      = containerCpu($payload, 'mysql-8_mysql');
    $worker     = containerCpu($payload, 'forbes_endorse-refresh-worker');
    $pending    = isset($queues['pending']) ? (int) ($queues['pending']) : null;
    $processing = isset($queues['processing']) ? (int) ($queues['processing']) : null;
    $completed  = isset($queues['completed']) ? (int) ($queues['completed']) : null;
    $failed     = isset($queues['failed']) ? (int) ($queues['failed']) : null;
    $stmt->bind_param('sddddddiiiis', $at, $hostLoad, $used, $total, $app, $mysql, $worker, $pending, $processing, $completed, $failed, $json);
    $stmt->execute();
} elseif ($payload['type'] === 'resource_spike') {
    $stmt     = $db->prepare('INSERT INTO endorse_refresh_spikes (captured_at,source,severity,summary,evidence_json) VALUES (?,?,?,?,?)');
    $source   = (string) ($payload['source'] ?? 'host');
    $severity = (string) ($payload['severity'] ?? 'critical');
    $summary  = substr((string) ($payload['summary'] ?? 'Sustained resource threshold exceeded'), 0, 255);
    $json     = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $stmt->bind_param('sssss', $at, $source, $severity, $summary, $json);
    $stmt->execute();
}
// One lightweight daily retention pass. Tables are indexed by their timestamp.
if (gmdate('Hi') === '0000') {
    foreach (['endorse_refresh_queue_attempt_archive' => 'archived_at', 'endorse_refresh_queue_archive' => 'archived_at', 'endorse_refresh_resource_snapshots' => 'captured_at', 'endorse_refresh_spikes' => 'captured_at', 'endorse_refresh_runs' => 'started_at'] as $table => $column) {
        $db->query("DELETE FROM `{$table}` WHERE `{$column}` < (UTC_TIMESTAMP(6) - INTERVAL 30 DAY)");
    }
}
$db->close();
