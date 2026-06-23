<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('monitoring_is_http_request')) {
    function monitoring_is_http_request()
    {
        return PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg';
    }
}

if (!function_exists('monitoring_request_id')) {
    function monitoring_request_id()
    {
        if (!empty($_SERVER['APP_REQUEST_ID'])) {
            return (string) $_SERVER['APP_REQUEST_ID'];
        }

        $incoming = '';
        if (!empty($_SERVER['HTTP_X_REQUEST_ID'])) {
            $incoming = (string) $_SERVER['HTTP_X_REQUEST_ID'];
        } elseif (!empty($_SERVER['UNIQUE_ID'])) {
            $incoming = (string) $_SERVER['UNIQUE_ID'];
        }

        if ($incoming !== '') {
            $_SERVER['APP_REQUEST_ID'] = $incoming;
            return $incoming;
        }

        try {
            $generated = bin2hex(random_bytes(8));
        } catch (Throwable $e) {
            $generated = str_replace('.', '', uniqid('', true));
        }

        $_SERVER['APP_REQUEST_ID'] = $generated;
        return $generated;
    }
}

if (!function_exists('monitoring_log_dir')) {
    function monitoring_log_dir()
    {
        return defined('APPPATH') ? APPPATH . 'logs/' : __DIR__ . '/../logs/';
    }
}

if (!function_exists('monitoring_log_path')) {
    function monitoring_log_path($channel = 'monitor')
    {
        $date = date('Y-m-d');
        return rtrim(monitoring_log_dir(), '/\\') . DIRECTORY_SEPARATOR . $channel . '-' . $date . '.log';
    }
}

if (!function_exists('monitoring_write_log')) {
    function monitoring_write_log(array $payload, $channel = 'monitor')
    {
        $dir = monitoring_log_dir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        $payload['ts'] = $payload['ts'] ?? date('c');
        $payload['request_id'] = $payload['request_id'] ?? monitoring_request_id();

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return false;
        }

        return @file_put_contents(monitoring_log_path($channel), $encoded . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
    }
}

if (!function_exists('monitoring_start_job')) {
    function monitoring_start_job($job, array $context = array())
    {
        $state = array(
            'job' => (string) $job,
            'started_at' => microtime(true),
            'request_id' => monitoring_request_id(),
            'context' => $context,
        );

        monitoring_write_log(array(
            'type' => 'job_start',
            'job' => $state['job'],
            'context' => $context,
        ), 'monitor');

        return $state;
    }
}

if (!function_exists('monitoring_finish_job')) {
    function monitoring_finish_job(array $state, array $payload = array())
    {
        $startedAt = isset($state['started_at']) ? (float) $state['started_at'] : microtime(true);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $record = array(
            'type' => 'job_finish',
            'job' => $state['job'] ?? 'unknown',
            'duration_ms' => $durationMs,
            'context' => $state['context'] ?? array(),
        );

        foreach ($payload as $key => $value) {
            $record[$key] = $value;
        }

        monitoring_write_log($record, 'monitor');
        return $record;
    }
}

if (!function_exists('monitoring_fail_job')) {
    function monitoring_fail_job(array $state, Throwable $exception, array $payload = array())
    {
        $payload['status'] = $payload['status'] ?? 'error';
        $payload['error_message'] = $payload['error_message'] ?? $exception->getMessage();
        $payload['error_class'] = $payload['error_class'] ?? get_class($exception);

        return monitoring_finish_job($state, $payload);
    }
}
