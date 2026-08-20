<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('monitoring_is_http_request')) {
    function monitoring_is_http_request()
    {
        // Detect HTTP via REQUEST_METHOD. The previous `!isset($_SERVER['argv'])` check
        // disabled monitoring under apache whenever php has register_argc_argv=On (the
        // common default), because $_SERVER['argv'] is then populated for web requests too.
        return PHP_SAPI !== 'cli'
            && PHP_SAPI !== 'phpdbg'
            && isset($_SERVER['REQUEST_METHOD']);
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

if (!function_exists('monitoring_current_user')) {
    /**
     * Identify the authenticated user for the current request from the session.
     * Only the opaque ID and role are sent to the incident collector; username,
     * request parameters and IP address are intentionally excluded.
     */
    function monitoring_current_user()
    {
        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            return null;
        }

        $u = $_SESSION['user'];

        return array(
            'id' => isset($u['id']) ? (int) $u['id'] : null,
            'role' => isset($u['role_text']) ? (string) $u['role_text']
                : (isset($u['role']) ? (string) $u['role'] : null),
        );
    }
}

if (!function_exists('monitoring_db')) {
    /**
     * The default CI3 database object if it is loaded, else null.
     * (database.php has save_queries=TRUE, so $db->queries / $db->query_times
     * already hold every executed query + its time for this request.)
     */
    function monitoring_db()
    {
        if (!function_exists('get_instance')) {
            return null;
        }

        $ci = @get_instance();
        if (!$ci || !isset($ci->db) || !is_object($ci->db)) {
            return null;
        }

        return $ci->db;
    }
}

if (!function_exists('monitoring_db_stats')) {
    /**
     * Per-request DB summary so logs answer "WHY" a request was slow:
     * how many queries, total query time, and the single slowest statement.
     */
    function monitoring_db_stats()
    {
        $db = monitoring_db();
        if ($db === null) {
            return null;
        }

        $queries = (isset($db->queries) && is_array($db->queries)) ? $db->queries : array();
        $times = (isset($db->query_times) && is_array($db->query_times)) ? $db->query_times : array();

        $count = count($queries);
        if ($count === 0) {
            return array('count' => 0, 'time_ms' => 0.0, 'slowest_ms' => 0.0, 'slowest_sql' => null);
        }

        $total = 0.0;
        $maxIdx = 0;
        $max = -1.0;
        foreach ($times as $i => $t) {
            $t = (float) $t;
            $total += $t;
            if ($t > $max) {
                $max = $t;
                $maxIdx = $i;
            }
        }

        $slowSql = isset($queries[$maxIdx]) ? (string) $queries[$maxIdx] : null;
        if ($slowSql !== null && strlen($slowSql) > 600) {
            $slowSql = substr($slowSql, 0, 600);
        }

        return array(
            'count' => $count,
            'time_ms' => round($total * 1000, 2),
            'slowest_ms' => round(max($max, 0.0) * 1000, 2),
            'slowest_sql' => $slowSql,
        );
    }
}

if (!function_exists('monitoring_slow_threshold_ms')) {
    /**
     * Requests at/above this duration get a full query dump (env MONITOR_SLOW_MS,
     * default 1000ms). Set higher to reduce noise once the worst offenders are fixed.
     */
    function monitoring_slow_threshold_ms()
    {
        $v = function_exists('env') ? env('MONITOR_SLOW_MS', 1000) : 1000;

        return is_numeric($v) ? (int) $v : 1000;
    }
}

if (!function_exists('monitoring_db_query_list')) {
    /**
     * Every query of the request (sql + ms), sorted slowest-first, capped to $limit.
     * Used to dump the full breakdown for a slow request so it is reproducible.
     */
    function monitoring_db_query_list($limit = 50)
    {
        $db = monitoring_db();
        if ($db === null) {
            return array();
        }

        $queries = (isset($db->queries) && is_array($db->queries)) ? $db->queries : array();
        $times = (isset($db->query_times) && is_array($db->query_times)) ? $db->query_times : array();

        $rows = array();
        foreach ($queries as $i => $sql) {
            $sql = (string) $sql;
            $rows[] = array(
                'ms' => isset($times[$i]) ? round((float) $times[$i] * 1000, 2) : null,
                'sql' => strlen($sql) > 400 ? substr($sql, 0, 400) : $sql,
            );
        }

        usort($rows, static function ($a, $b) {
            return ($b['ms'] ?? 0) <=> ($a['ms'] ?? 0);
        });

        return array_slice($rows, 0, max(1, (int) $limit));
    }
}

if (!function_exists('monitoring_sql_fingerprint')) {
    /** Preserve query shape without retaining request-specific literals. */
    function monitoring_sql_fingerprint($sql)
    {
        $sql = (string) $sql;
        $sql = preg_replace("/'(?:''|\\\\.|[^'])*'/s", '?', $sql);
        $sql = preg_replace('/"(?:""|\\\\.|[^"])*"/s', '?', $sql);
        $sql = preg_replace('/\\b\\d+(?:\\.\\d+)?\\b/', '?', $sql);
        $sql = preg_replace('/\\s+/', ' ', trim($sql));

        return strlen($sql) > 600 ? substr($sql, 0, 600) : $sql;
    }
}

if (!function_exists('monitoring_redacted_query_list')) {
    function monitoring_redacted_query_list($limit = 50)
    {
        $rows = monitoring_db_query_list($limit);
        foreach ($rows as &$row) {
            $row['sql'] = monitoring_sql_fingerprint($row['sql'] ?? '');
        }
        unset($row);

        return $rows;
    }
}
