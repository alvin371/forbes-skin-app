<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$envFile = $root . '/.env';

if (!is_readable($envFile)) {
    fwrite(STDERR, ".env not found at {$envFile}\n");
    exit(1);
}

function loadEnvFile(string $path): array
{
    $vars = array();
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $vars;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $vars[trim($name)] = trim(trim($value), "\"'");
    }

    return $vars;
}

function metricLine(string $name, $value, array $labels = array()): string
{
    $labelText = '';
    if (!empty($labels)) {
        $pairs = array();
        foreach ($labels as $key => $labelValue) {
            $escaped = str_replace(array("\\", "\"", "\n"), array("\\\\", "\\\"", "\\n"), (string) $labelValue);
            $pairs[] = $key . '="' . $escaped . '"';
        }
        $labelText = '{' . implode(',', $pairs) . '}';
    }

    return $name . $labelText . ' ' . $value;
}

$env = loadEnvFile($envFile);
$required = array('DB_HOSTNAME', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE');
foreach ($required as $key) {
    if (!array_key_exists($key, $env)) {
        fwrite(STDERR, "Missing required env key: {$key}\n");
        exit(1);
    }
}

$mysqli = @new mysqli($env['DB_HOSTNAME'], $env['DB_USERNAME'], $env['DB_PASSWORD'], $env['DB_DATABASE']);
if ($mysqli->connect_error) {
    fwrite(STDERR, "MySQL connection failed: {$mysqli->connect_error}\n");
    exit(1);
}

$queries = array(
    'forbes_endorse_due_total' => "SELECT COUNT(*) AS c FROM endorse WHERE status = 'Aktif' AND status_campaign = 'Aktif' AND (sync_at < CURDATE() OR sync_at IS NULL) AND link_upload != ''",
    'forbes_endorse_campaign_active_total' => "SELECT COUNT(*) AS c FROM endorse_campaign WHERE status = 'Aktif'",
    'forbes_influencer_due_total' => "SELECT COUNT(*) AS c FROM influencer WHERE status = 'Aktif' AND (sync_at < (CURDATE() - INTERVAL 6 DAY) OR sync_at IS NULL) AND url != ''",
    'forbes_influencer_dummy_due_total' => "SELECT COUNT(*) AS c FROM influencer_dummy WHERE status = 'Aktif' AND (sync_at < (CURDATE() - INTERVAL 6 DAY) OR sync_at IS NULL) AND url != ''",
    'forbes_scraping_queue_ready_total' => "SELECT COUNT(*) AS c FROM scraping_queue WHERE status IN ('pending', 'submitted')",
    'forbes_notification_pending_total' => "SELECT COUNT(*) AS c FROM notification_outbox WHERE status = 'PENDING'",
);

$lines = array(
    '# HELP forbes_monitor_snapshot_timestamp_seconds Unix timestamp when the backlog snapshot ran.',
    '# TYPE forbes_monitor_snapshot_timestamp_seconds gauge',
    metricLine('forbes_monitor_snapshot_timestamp_seconds', time()),
);

foreach ($queries as $metricName => $sql) {
    $res = $mysqli->query($sql);
    if (!$res) {
        fwrite(STDERR, "Query failed for {$metricName}: {$mysqli->error}\n");
        exit(1);
    }

    $row = $res->fetch_assoc();
    $value = isset($row['c']) ? (int) $row['c'] : 0;
    $lines[] = '# TYPE ' . $metricName . ' gauge';
    $lines[] = metricLine($metricName, $value);
}

$metrics = implode(PHP_EOL, $lines) . PHP_EOL;

$outputPath = $argv[1] ?? '';
if ($outputPath !== '') {
    $dir = dirname($outputPath);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fwrite(STDERR, "Failed to create output directory: {$dir}\n");
        exit(1);
    }

    $tmpPath = $outputPath . '.tmp';
    if (file_put_contents($tmpPath, $metrics) === false || !rename($tmpPath, $outputPath)) {
        fwrite(STDERR, "Failed to write metrics file: {$outputPath}\n");
        exit(1);
    }
} else {
    echo $metrics;
}

$mysqli->close();
