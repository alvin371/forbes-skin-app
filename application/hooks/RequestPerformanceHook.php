<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Low-overhead request timing hook. JSONL records correlate HTTP requests with
 * cron_probe request IDs and Docker/Dozzle stderr logs.
 */
class RequestPerformanceHook
{
    const START_KEY = 'FORBES_REQUEST_MONITOR_STARTED_AT';

    public function start()
    {
        if (!$this->isHttpRequest()) {
            return;
        }

        $this->loadHelper();
        $_SERVER[self::START_KEY] = microtime(true);
        monitoring_request_id();
    }

    public function finish()
    {
        if (!$this->isHttpRequest() || !isset($_SERVER[self::START_KEY])) {
            return;
        }

        try {
            $this->loadHelper();
            $durationMs = round((microtime(true) - (float) $_SERVER[self::START_KEY]) * 1000, 2);
            $db = monitoring_db_stats();
            $slow = $durationMs >= monitoring_slow_threshold_ms();
            $currentUser = monitoring_current_user();
            $output = get_instance()->output;
            $status = method_exists($output, 'get_status_header_code')
                ? (int) $output->get_status_header_code()
                : (int) http_response_code();

            $record = array(
                'type' => 'request_performance',
                // Used by the host incident collector to keep the two apps separate.
                'service' => $this->serviceName(),
                'route' => $this->route(),
                'method' => strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
                'http_status' => $status > 0 ? $status : 200,
                'duration_ms' => $durationMs,
                'slow' => $slow,
                'request_id' => monitoring_request_id(),
                'user_id' => $currentUser['id'] ?? null,
                'user_role' => $currentUser['role'] ?? null,
                'db' => $db === null ? null : array(
                    'count' => $db['count'],
                    'time_ms' => $db['time_ms'],
                    'slowest_ms' => $db['slowest_ms'],
                    'slowest_sql' => monitoring_sql_fingerprint($db['slowest_sql'] ?? ''),
                ),
            );

            if ($slow && $record['db'] !== null) {
                $record['db']['queries'] = monitoring_redacted_query_list(50);
            }

            monitoring_write_log($record, 'performance');
            if ($this->boolEnv('MONITOR_REQUEST_LOG_STDERR', true)) {
                error_log('PERF ' . json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        } catch (Throwable $e) {
            // Monitoring must never turn an otherwise successful request into a failure.
            error_log('PERF monitoring_failed ' . get_class($e));
        }
    }

    private function isHttpRequest()
    {
        return PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' && isset($_SERVER['REQUEST_METHOD']);
    }

    private function loadHelper()
    {
        if (!function_exists('monitoring_write_log')) {
            require_once APPPATH . 'helpers/monitoring_helper.php';
        }
        if (!function_exists('env')) {
            require_once APPPATH . 'helpers/env_helper.php';
        }
    }

    private function route()
    {
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        return $uri === null || $uri === '' ? '/' : $uri;
    }

    private function serviceName()
    {
        $service = function_exists('env') ? (string) env('MONITOR_SERVICE_NAME', '') : '';

        return $service !== '' ? $service : 'forbes_app';
    }

    private function boolEnv($key, $default)
    {
        $value = function_exists('env') ? env($key, $default ? 'true' : 'false') : ($default ? 'true' : 'false');
        return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
    }
}
