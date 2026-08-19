<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Long-lived CLI endorse-refresh worker.
 *
 *   php -d memory_limit=512M index.php EndorseRefreshWorker run
 *
 * CLI-only, by is_cli() guard, following Seed.php / MigrationRunner.php. CodeIgniter's CLI
 * SAPI routes argv[1]/argv[2] straight to controller/method, so no routes.php entry exists
 * and this can never be reached over HTTP.
 *
 * This process claims queue rows, writes business rows and spends provider quota, so it
 * refuses to start unless EndorseRefreshLoadTestGuard can PROVE it is isolated from
 * production. That check runs before the database is touched.
 *
 * It does NOT replace the cron. The cron path is untouched; this is a second, opt-in driver
 * whose activation is a deliberate, separately reviewed decision — the same guardrail
 * services/endorse-refresh-worker/DECISION.md established for the Rust worker.
 */
class EndorseRefreshWorker extends CI_Controller
{
    public function __construct()
    {
        // BOTH checks run BEFORE parent::__construct(), and that ordering is the point.
        // config/autoload.php autoloads the 'database' library, so CI_Controller's constructor
        // opens a connection to whatever DB_HOSTNAME/DB_DATABASE happen to be configured. A
        // guard that ran after it would be validating an environment it had already connected
        // to — which is exactly the mistake this class exists to prevent.
        if (!is_cli()) {
            show_404();
        }

        require_once APPPATH . 'helpers/env_helper.php';
        require_once APPPATH . 'libraries/EndorseRefreshLoadTestGuard.php';

        $env = EndorseRefreshLoadTestGuard::readEnv(function ($key, $default) {
            return env($key, $default);
        });

        $violations = EndorseRefreshLoadTestGuard::violations($env);
        if ($violations !== array()) {
            fwrite(STDERR, "REFUSING TO START: production isolation is not proven.\n");
            foreach ($violations as $violation) {
                fwrite(STDERR, '  [' . $violation['code'] . '] ' . $violation['hint'] . "\n");
            }
            fwrite(STDERR, "Fix the load-test environment; never relax this guard to make a run start.\n");
            exit(1);
        }

        parent::__construct();
    }

    /**
     * Isolation verdict only — never claims a row or opens a provider socket.
     *
     * Reaching this method already proves isolation: the constructor exits non-zero before
     * CI_Controller can autoload the database otherwise. So there is nothing left to check
     * here, which is the intended shape.
     */
    public function check()
    {
        fwrite(STDOUT, "isolation proven\n");
        exit(0);
    }

    public function run()
    {
        $this->load->model('mymodel');
        $this->load->library('template');
        $this->load->library('endorse_sync');
        $this->load->library('EndorseRefreshQueueService');
        $this->load->library('EndorseRefreshDiagnostics');
        $this->load->database();

        // MANDATORY for a long-lived process. config/database.php sets save_queries => TRUE,
        // and CI_DB_driver appends every query string plus its timing to $this->queries
        // forever. Over 30 minutes at 400 completions/min that is an unbounded array and a
        // guaranteed OOM — not a correctness bug, but a run that dies at minute 20 proves
        // nothing.
        $this->db->save_queries = false;

        require_once APPPATH . 'libraries/EndorseRefreshRateLimiter.php';
        require_once APPPATH . 'libraries/EndorseRefreshLedger.php';
        require_once APPPATH . 'libraries/EndorseRefreshFetchKit.php';
        require_once APPPATH . 'libraries/EndorseRefreshPipeline.php';

        $cfg = $this->workerConfig();
        $svc = $this->endorserefreshqueueservice;
        $kit = new EndorseRefreshFetchKit();
        $ledger = new EndorseRefreshLedger($this->db, (int) $cfg['ledger_flush_rows'], (float) $cfg['ledger_flush_sec']);
        $store = new CiDbReservationStore($this->db, $cfg['app_env'], $cfg['app_name']);

        $pipeline = new EndorseRefreshPipeline($this->collaborators($svc, $kit, $ledger, $store, $cfg), $cfg);

        $this->installSignalHandlers($pipeline, (float) $cfg['drain_sec']);

        $runId = $this->endorserefreshdiagnostics->startRun('php_worker', 0, 0, $this->reportableConfig($cfg));
        $startedAt = microtime(true);

        $totals = $pipeline->run();

        $elapsed = max(0.001, microtime(true) - $startedAt);
        $totals['elapsed_sec'] = round($elapsed, 2);
        $totals['completions_per_min'] = round($totals['completed'] / ($elapsed / 60), 2);
        $totals['requests_per_completion'] = $totals['completed'] > 0
            ? round($totals['started'] / $totals['completed'], 3)
            : null;
        $totals['ledger'] = $ledger->stats();

        $this->endorserefreshdiagnostics->finishRun($runId, array(
            'claimed_count' => intval($totals['claimed']),
            'completed_count' => intval($totals['completed']),
            'failed_count' => intval($totals['failed']),
            'retrying_count' => intval($totals['retrying']),
            'deferred_count' => intval($totals['released']),
        ));

        fwrite(STDOUT, json_encode($totals, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");
        exit(0);
    }

    // ---------------------------------------------------------------- wiring

    private function collaborators($svc, EndorseRefreshFetchKit $kit, EndorseRefreshLedger $ledger, CiDbReservationStore $store, array $cfg): array
    {
        $CI = $this;

        return array(
            /**
             * rate_per_min and daily_cap are explicitly 0 — NOT because the worker is
             * unlimited, but because its budget lives at REQUEST grain in the reservation
             * store. The claim-time counter meters posts attempted; the reservation store
             * meters HTTP requests started. At ~1.3 requests per post those are different
             * numbers, and only the second is what the provider actually rate-limits.
             *
             * Passing them via $opts (rather than changing env) leaves the cron's own caps
             * exactly as they are. 'force' is deliberately NOT set: force means "operator
             * override", not "worker".
             */
            'claim' => function (int $limit) use ($svc, $cfg) {
                $claim = $svc->claimBatch(array(
                    'limit' => $limit,
                    'rate_per_min' => 0,
                    'daily_cap' => 0,
                    'stale_minutes' => 5,
                    'recovery_min_interval_sec' => (float) $cfg['recovery_min_interval_sec'],
                    'retry_priority_demotion' => (int) $cfg['retry_priority_demotion'],
                ));

                if (empty($claim['status'])) {
                    log_message('error', 'endorse_refresh_worker_claim_failed: ' . strval($claim['error'] ?? 'unknown'));

                    return array();
                }

                return (!empty($claim['skipped']) || empty($claim['items'])) ? array() : $claim['items'];
            },

            'release' => function (array $items) use ($svc) {
                return $items === array() ? 0 : (int) $svc->releaseUnstartedChunk($items);
            },

            /**
             * One item at a time, with the campaign rollup deferred to the pipeline's timer.
             * Everything else — per-item transaction, lockActiveClaim() fencing,
             * stats_observation_seq ordering, retry/backoff, terminal classification — is the
             * production implementation, unchanged.
             */
            'apply' => function (array $item, array $response) use ($svc) {
                return $svc->applyResults(array($item), array($response), array('defer_campaign_rollup' => true));
            },

            'reserve' => function (string $leg, string $scope, array $ctx) use ($store, $cfg) {
                $limit = $leg === EndorseRefreshPipeline::LEG_DIRECT
                    ? (int) $cfg['direct_rate_per_min']
                    : (int) $cfg['rapidapi_rate_per_min'];

                $tokenId = $store->reserveToken($scope, $limit, 60, $ctx);

                // A denial has three very different causes and they demand opposite responses:
                // "window full" is the budget working correctly and the worker should wait;
                // "limit <= 0" is a misconfiguration that will never resolve; "lock" is
                // contention between replicas. Logging only "denied" makes a starved worker
                // indistinguishable from a correctly throttled one — which cost real
                // debugging time on the first multi-replica run.
                if ($tokenId === null) {
                    $used = $store->countInWindow($scope, 60);
                    error_log(json_encode(array(
                        'evt' => 'endorse_refresh_reserve_denied',
                        'scope' => $scope, 'limit' => $limit, 'used_in_window' => $used,
                        'reason' => $limit <= 0 ? 'limit_not_positive' : ($used >= $limit ? 'window_full' : 'lock_unavailable'),
                    )));
                }

                return $tokenId;
            },

            'rollup' => function (array $campaignIds) use ($CI) {
                foreach ($campaignIds as $cid) {
                    $CI->endorse_sync->update_campaign_parent(intval($cid), 0);
                }
            },

            'ledger' => $ledger,
            'kit' => $kit,
            'now' => function () {
                return microtime(true);
            },
            'log' => function (string $event, array $fields = array()) use ($cfg) {
                error_log(json_encode(array_merge(
                    array('evt' => 'endorse_refresh_worker_' . $event, 'worker_id' => $cfg['worker_id']),
                    $fields
                ), JSON_UNESCAPED_SLASHES));
            },
        );
    }

    /**
     * pcntl_async_signals rather than declare(ticks): the handler runs between opcodes with
     * no per-tick overhead. The handler itself only flips two scalars — see
     * EndorseRefreshPipeline::requestStop().
     */
    private function installSignalHandlers(EndorseRefreshPipeline $pipeline, float $drainSeconds): void
    {
        if (!function_exists('pcntl_async_signals')) {
            fwrite(STDERR, "WARNING: pcntl unavailable; SIGTERM will not drain gracefully\n");

            return;
        }

        pcntl_async_signals(true);
        foreach (array(SIGTERM, SIGINT, SIGQUIT) as $signal) {
            pcntl_signal($signal, function () use ($pipeline, $drainSeconds) {
                $pipeline->requestStop($drainSeconds);
            });
        }
    }

    private function workerConfig(): array
    {
        $legOrder = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ENDORSE_REFRESH_LEG_ORDER', 'direct_scrape,rapidapi'))
        )));
        if (count($legOrder) < 2) {
            $legOrder = array(EndorseRefreshPipeline::LEG_DIRECT, EndorseRefreshPipeline::LEG_RAPIDAPI);
        }

        return array(
            'worker_id'   => 'phpw_' . substr(bin2hex(random_bytes(8)), 0, 12),
            'run_id'      => substr(md5(uniqid('run', true)), 0, 32),
            'test_run_id' => (string) env('ENDORSE_REFRESH_TEST_RUN_ID', ''),
            // Resolve the limiter scope HERE, where reading the environment is this class's
            // job, so the pipeline stays free of global helpers. The value is a fingerprint
            // of the key, never the key, so it is safe to hold in config and to log.
            'rapidapi_scope' => EndorseRefreshRateScope::scope(
                EndorseRefreshRateScope::PROVIDER_RAPIDAPI,
                (string) env('RAPIDAPI_KEY', '')
            ),
            'app_env'     => strtolower((string) env('APP_ENV', 'loadtest')),
            'app_name'    => strtolower((string) env('APP_NAME', 'forbes')),

            'max_in_flight'   => self::clamp(env('ENDORSE_REFRESH_MAX_IN_FLIGHT', 10), 1, 200),
            'claim_chunk_max' => self::clamp(env('ENDORSE_REFRESH_CLAIM_CHUNK_MAX', 40), 1, 500),
            'claim_min_batch' => self::clamp(env('ENDORSE_REFRESH_CLAIM_MIN_BATCH', 1), 1, 100),
            'ready_low_water' => self::clamp(env('ENDORSE_REFRESH_READY_LOW_WATER', 5), 0, 100),

            'leg_order' => $legOrder,
            'max_legs'  => self::clamp(env('ENDORSE_REFRESH_MAX_LEGS', 2), 1, 2),

            'scrape_timeout_sec'   => self::clamp(env('ENDORSE_REFRESH_HTTP_TIMEOUT', 20), 1, 120),
            'rapidapi_timeout_sec' => self::clamp(env('ENDORSE_REFRESH_RAPIDAPI_TIMEOUT', 12), 1, 120),
            'connect_timeout_sec'  => self::clamp(env('ENDORSE_REFRESH_CONNECT_TIMEOUT', 5), 1, 60),
            'watchdog_grace_sec'   => (float) self::clamp(env('ENDORSE_REFRESH_WATCHDOG_GRACE_SEC', 10), 1, 120),

            // These two ARE the global brake: the worker disables the claim-time caps above.
            // The guard refuses to boot on a non-positive or implausible value, because
            // reserve() fail-closes on <= 0 but fail-OPENS on a huge limit.
            'direct_rate_per_min'   => self::clamp(env('ENDORSE_REFRESH_DIRECT_RATE_PER_MIN', 0), 0, 800),
            'rapidapi_rate_per_min' => self::clamp(env('ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN', 0), 0, 800),

            'recovery_min_interval_sec' => (float) self::clamp(env('ENDORSE_REFRESH_RECOVERY_MIN_INTERVAL_SEC', 5), 0, 300),
            // One priority band per consumed attempt. Bounds how long a deterministically
            // failing high-priority cohort can hold the head of the queue ahead of fresh
            // lower-priority work. 0 restores the historical absolute-priority ordering.
            'retry_priority_demotion' => self::clamp(env('ENDORSE_REFRESH_RETRY_PRIORITY_DEMOTION', 1), 0, 100),
            'rollup_flush_sec'    => (float) self::clamp(env('ENDORSE_REFRESH_ROLLUP_FLUSH_SEC', 10), 1, 300),
            'token_retention_sec' => self::clamp(env('ENDORSE_REFRESH_TOKEN_RETENTION_SEC', 3600), 60, 86400),
            'drain_sec'           => (float) self::clamp(env('ENDORSE_REFRESH_DRAIN_SEC', 30), 1, 300),
            'max_runtime_sec'     => self::clamp(env('ENDORSE_REFRESH_MAX_RUNTIME_SEC', 3600), 0, 86400),

            'ledger_flush_rows' => self::clamp(env('ENDORSE_REFRESH_LEDGER_FLUSH_ROWS', 200), 1, 5000),
            'ledger_flush_sec'  => 0.25,

            'ready_max_age_sec'      => 2.0,
            // Kept comfortably under ready_max_age_sec so a buffered item is started well
            // before it can age out and be released.
            'ready_lead_sec'         => 1.5,
            'idle_sleep_us'          => self::clamp(env('ENDORSE_REFRESH_IDLE_SLEEP_US', 10000), 1000, 1000000),
            'claim_backoff_sec'      => 0.25,
            'pace_burst'             => self::clamp(env('ENDORSE_REFRESH_PACE_BURST', 20), 1, 200),
            'worker_replicas'        => self::clamp(env('ENDORSE_REFRESH_WORKER_REPLICAS', 1), 1, 64),
            'claim_capacity_floor'   => 0.1,
            'claim_capacity_recovery' => 0.02,
            'denied_backoff_sec'     => 0.05,
            'idle_claim_backoff_sec' => 1.0,
            'max_body_bytes'         => self::clamp(env('ENDORSE_REFRESH_MAX_BODY_BYTES', EndorseRefreshFetchKit::DEFAULT_MAX_BODY_BYTES), 65536, 33554432),
            'mock_base'              => (string) env('ENDORSE_REFRESH_MOCK_BASE', ''),
        );
    }

    /** Config echoed into endorse_refresh_runs. Tuning knobs only — never a credential. */
    private function reportableConfig(array $cfg): array
    {
        return array(
            'driver' => 'php_worker',
            'worker_id' => $cfg['worker_id'],
            'test_run_id' => $cfg['test_run_id'],
            'max_in_flight' => $cfg['max_in_flight'],
            'leg_order' => implode(',', $cfg['leg_order']),
            'max_legs' => $cfg['max_legs'],
            'direct_rate_per_min' => $cfg['direct_rate_per_min'],
            'rapidapi_rate_per_min' => $cfg['rapidapi_rate_per_min'],
            'scrape_timeout_sec' => $cfg['scrape_timeout_sec'],
            'rapidapi_timeout_sec' => $cfg['rapidapi_timeout_sec'],
        );
    }

    private static function clamp($value, int $min, int $max): int
    {
        return max($min, min($max, intval($value)));
    }
}
