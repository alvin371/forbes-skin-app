<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Endorse_sync::is_terminal_class() and friends are called statically below; declare the
// class outright instead of depending on a caller having loaded the library.
require_once __DIR__ . '/Endorse_sync.php';
require_once __DIR__ . '/EndorseRefreshClaimRepository.php';
require_once __DIR__ . '/EndorseRefreshRateLimiter.php';

class EndorseRefreshQueueService
{
    const DEFAULT_PRIORITY = 10;
    const DEFAULT_MAX_ATTEMPTS = 3;
    const INSERT_CHUNK_SIZE = 250;

    /**
     * When this process last ran stale recovery, for the claim-time cadence guard.
     *
     * Per-process, deliberately. The cron gets a fresh process per tick so it always reads
     * 0.0 and recovers exactly as before; only a long-lived worker accumulates state here.
     * Cross-process throttling is the GET_LOCK's job, not this field's.
     */
    private $lastRecoveryAt = 0.0;

    /**
     * Keep deployment configuration from turning one cron request into an
     * unbounded worker. The production scheduler may invoke this endpoint more
     * than once, so these are deliberately conservative hard ceilings.
     */
    public static function boundedWorkerSetting($value, int $default, int $max): int
    {
        $value = intval($value);
        if ($value <= 0) {
            $value = $default;
        }

        return max(1, min($max, $value));
    }

    /**
     * The HTTP claim endpoint is allowed to drain only when the Rust driver is
     * explicitly selected. Keep this pure so the ownership rule cannot drift
     * from its regression test.
     */
    public static function allowsRustClaims(string $driver): bool
    {
        return strtolower(trim($driver)) === 'rust';
    }

    /**
     * Drivers under which a long-lived external consumer owns draining, so the per-minute
     * cron must stand down.
     *
     * Deliberately a different question from allowsRustClaims(): that one gates the HTTP
     * claim endpoint used by the Rust pull-worker, whereas the PHP worker claims directly
     * against the database and never touches that endpoint. Sharing one predicate would
     * hand the PHP worker an HTTP claim path it does not need.
     *
     * Running cron and an external consumer together is *safe* — SKIP LOCKED prevents any
     * double-claim — but the cron re-enables the serial RapidAPI fallback in
     * Template::get_social_media_batch and spends provider budget outside the worker's
     * reservation accounting, so throughput and amplification measurements stop meaning
     * anything. Standing down is about measurability, not correctness.
     */
    const EXTERNAL_DRAIN_DRIVERS = array('rust', 'php_worker');

    public static function driverOwnsDraining(string $driver): bool
    {
        return in_array(strtolower(trim($driver)), self::EXTERNAL_DRAIN_DRIVERS, true);
    }

    /**
     * Whether stale recovery should run on this claim.
     *
     * Pure apart from the clock, so the cadence policy is testable without a database.
     * An interval of 0.0 means "every claim" — the historical cron behaviour, and the
     * default, so this can never silently change how production recovers.
     */
    public function recoveryIsDue(float $minIntervalSeconds, ?float $now = null): bool
    {
        if ($minIntervalSeconds <= 0.0) {
            return true;
        }

        $now = $now ?? microtime(true);

        return ($now - $this->lastRecoveryAt) >= $minIntervalSeconds;
    }

    /**
     * Queue-level exponential retry delay. Clamp the exponent because
     * max_attempts is configurable and malformed rows must not overflow it.
     */
    public static function retryDelaySeconds(int $attempts, int $baseSeconds = 60): int
    {
        $baseSeconds = max(1, min(3600, $baseSeconds));
        $exponent = max(0, min(10, $attempts - 1));

        return $baseSeconds * (2 ** $exponent);
    }

    /**
     * Stable jitter avoids a retry thundering herd while keeping tests and incident
     * reconstruction deterministic. Provider Retry-After is always a lower bound.
     */
    public static function retryDelayWithJitter(int $queueId, int $attempts, int $baseSeconds = 60, int $retryAfterSeconds = 0): int
    {
        $base = self::retryDelaySeconds($attempts, $baseSeconds);

        // Spread PROPORTIONALLY to the backoff, not by a fixed fraction of the base interval.
        // A flat window keeps later attempts tightly bunched: at attempt 3 the delay is 240s
        // but a baseSeconds/2 window spreads it over only 30s, so a cohort that failed
        // together retries together. Measured in the 30-minute run, attempt-2 and attempt-3
        // cohorts each arrived as a single-minute wave that consumed the entire request budget
        // (docs/loadtest/ISSUES.md#issue-19).
        $jitterWindow = max(1, intdiv($base, 2));
        $jitter = abs(crc32($queueId . ':' . $attempts)) % ($jitterWindow + 1);

        return max(0, $retryAfterSeconds, $base + $jitter);
    }

    // -------------------------------------------------------------------------
    // SCHEDULING-TIME CONTRACT (single authority).
    //
    // A column is a SCHEDULING column when some comparison against "now" decides
    // behaviour: lease_expires_at, claimed_at, started_at, next_attempt_at,
    // completed_at, finished_at on the queue tables, plus lease_expires_at on
    // endorse_refresh_fallback_calls and open_until on the two *_health tables.
    // Every one of those is written AND compared using the MySQL server clock via
    // NOW(6). None of them is written or compared from the PHP clock.
    //
    // The rule is about the comparison, not the write. These columns are DATETIME and
    // carry no offset, so reading one back into PHP and comparing it there re-parses it
    // in the process timezone (Asia/Jakarta) no matter which zone wrote it — which is
    // how a lease could read as hours expired the instant it was issued. Pure audit
    // timestamps that nothing compares against now (created_at/updated_at on the
    // control-plane tables) are exempt; they are records, not decisions.
    //
    // Why the database and not UTC-in-PHP: the cron driver (the only enabled driver)
    // has always written these columns with PHP's Asia/Jakarta clock and read them back
    // with NOW(6), so on a Jakarta-clocked server the existing rows already mean
    // "server local time". Rewriting them as UTC would reinterpret live data and needs
    // a reconciliation pass. NOW(6) keeps legacy rows meaningful and removes the
    // cross-path skew, because the V2 path stops writing gmdate()/UTC_TIMESTAMP into
    // the same columns.
    //
    // Deadlines are computed with database-side arithmetic rather than "read NOW(),
    // parse a zone-less string in PHP, add seconds, write it back later": that round
    // trip re-introduces a process clock and a parse ambiguity for no benefit.
    // -------------------------------------------------------------------------

    /** Close the still-open attempt as timed_out, then release the parent. */
    public const RECOVERY_CLOSE_OPEN_ATTEMPT = 'close_open_attempt';
    /** Parent owns no attempt row at all: record one reconciled timeout, then release. */
    public const RECOVERY_SYNTHESIZE_ATTEMPT = 'synthesize_attempt';
    /** Attempt is already closed (failed/timed_out/cancelled): release the parent only. */
    public const RECOVERY_RELEASE_ONLY = 'release_only';
    /** Ambiguous or already-successful state: observe it, never retry it. */
    public const RECOVERY_INCONSISTENT = 'inconsistent';

    /**
     * Prefix marking a row that automatic recovery has deliberately given up on.
     * Grep-able for operators and, more importantly, the predicate that keeps a
     * quarantined row out of the recovery window.
     */
    public const RECONCILIATION_MARKER = 'needs_reconciliation';

    /**
     * The diagnostic left on a quarantined row: what was observed, and which attempt
     * to look at. No secrets, and stable enough to alert on.
     */
    public static function reconciliationMessage(string $observedAttemptStatus, int $activeAttemptId): string
    {
        return self::RECONCILIATION_MARKER . ': active attempt ' . $activeAttemptId
            . ' is ' . $observedAttemptStatus . ' under a processing parent; recovery stopped, manual review required';
    }

    /**
     * Recovery policy for an expired lease, keyed on the ACTIVE ATTEMPT's state.
     *
     * The dangerous cases are 'completed' and a dangling pointer. A completed attempt is
     * written in the same transaction as its business write, so a completed attempt under a
     * still-processing parent cannot be re-run without risking a duplicate provider request
     * for work that already succeeded; and a pointer to a row that no longer exists carries
     * no evidence at all. Both are reported rather than normalised.
     *
     * @param string $attemptStatus  status of the parent's active attempt, '' when unjoined
     * @param int    $activeAttemptId parent's active_attempt_id (0 when NULL)
     * @param int    $attemptId       id of the joined attempt row (0 when it did not join)
     */
    public static function recoveryDecision(string $attemptStatus, int $activeAttemptId, int $attemptId): string
    {
        if ($activeAttemptId <= 0) {
            return self::RECOVERY_SYNTHESIZE_ATTEMPT;
        }
        if ($attemptId <= 0) {
            // Parent points at an attempt row that did not join: unexplainable history.
            return self::RECOVERY_INCONSISTENT;
        }

        switch ($attemptStatus) {
            case 'processing':
                return self::RECOVERY_CLOSE_OPEN_ATTEMPT;

            case 'failed':
            case 'timed_out':
            case 'cancelled':
            case 'retrying':
                return self::RECOVERY_RELEASE_ONLY;

            case 'completed':
            case 'submitted':
            default:
                // 'submitted' is an in-flight provider job whose outcome is still unknown,
                // 'completed' already succeeded, anything else violates the state machine.
                return self::RECOVERY_INCONSISTENT;
        }
    }

    /** The one clock every scheduling comparison and write uses. */
    public static function schedulingNowSql(): string
    {
        return 'NOW(6)';
    }

    /**
     * A future scheduling deadline, evaluated by the database. $seconds is clamped and
     * cast so the expression can never carry caller-controlled SQL.
     */
    public static function schedulingDeadlineSql(int $seconds, int $maxSeconds = 86400): string
    {
        $seconds = max(0, min($maxSeconds, $seconds));

        return 'DATE_ADD(NOW(6), INTERVAL ' . $seconds . ' SECOND)';
    }

    public static function retryAfterSeconds(array $response): int
    {
        $meta = is_array($response['error_meta'] ?? null) ? $response['error_meta'] : [];
        $raw = $response['retry_after'] ?? $meta['retry_after'] ?? $meta['retry_after_seconds'] ?? 0;
        if (is_numeric($raw)) {
            return max(0, min(86400, intval(ceil((float) $raw))));
        }

        $timestamp = strtotime((string) $raw);
        if ($timestamp === false) {
            return 0;
        }

        return max(0, min(86400, $timestamp - time()));
    }

    // ---------------------------------------------------------------------------
    // Provider-parity fix (fix/endorse-refresh-provider-parity).
    //
    // The healthy reference app (bhskin) drains sequentially: one item at a time,
    // direct scrape with unlimited timeout, RapidAPI fallback retried inline. It
    // never reserves more work than it can execute, so it reaches ~100% eventual
    // success. Forbes reimplemented this as a batch queue that can CLAIM up to 500
    // items while only ~PARALLEL_HTTP execute per run, and whose rolling rate limit
    // counts CLAIMS, not started requests. Overlarge claims → mass deferral + the
    // claim burst starves overlapping staggered runs.
    //
    // These pure helpers encode the corrected decisions. They are all default-off
    // (behaviour identical to today) and independently unit-tested so the semantics
    // cannot drift. Integration wiring reads them via env().
    // ---------------------------------------------------------------------------

    const LIMITER_CLAIM_RESERVATION = 'claim_reservation';
    const LIMITER_REQUEST_START     = 'request_start_reservation';

    /**
     * Selected rate-limiter mode. Default preserves current production semantics
     * (a token is reserved at CLAIM time). 'request_start_reservation' means a token
     * is consumed immediately before each outbound request, by every started request
     * regardless of outcome — and never by a claim that does not start.
     */
    public static function limiterMode(?string $raw = null): string
    {
        $raw = strtolower(trim((string) ($raw ?? env('ENDORSE_REFRESH_LIMITER_MODE', self::LIMITER_CLAIM_RESERVATION))));
        return $raw === self::LIMITER_REQUEST_START
            ? self::LIMITER_REQUEST_START
            : self::LIMITER_CLAIM_RESERVATION;
    }

    public static function reservesAtRequestStart(?string $rawMode = null): bool
    {
        return self::limiterMode($rawMode) === self::LIMITER_REQUEST_START;
    }

    /**
     * In request-start mode, EVERY outbound provider request consumes exactly one
     * token — success, 429, 5xx, timeout, invalid JSON, application error alike.
     * A claim that never starts a request (clean deferral) consumes nothing.
     * Encodes acceptance criteria 5 & 6. Pure and total so it cannot drift.
     *
     * @param string $outcome one of: success|http_429|http_5xx|timeout|invalid|app_error|deferred_unstarted
     */
    public static function consumesRequestToken(string $outcome): bool
    {
        return $outcome !== 'deferred_unstarted' && $outcome !== '';
    }

    /**
     * Incremental-claim sizing. Default off → returns the configured batch unchanged
     * (current behaviour). When ENDORSE_REFRESH_INCREMENTAL_CLAIM is on, a run claims
     * no more than it can actually start this pass: chunk = parallel_http * multiplier,
     * capped by the batch. This is what stops one run reserving hundreds of items for
     * ~20 execution slots and starving the staggered runs behind it.
     *
     * Initial safe rule: claim_chunk <= effective_parallel_http (multiplier 1). A larger
     * multiplier is allowed only with test evidence; clamped to [1,4] here.
     */
    public static function effectiveClaimLimit(int $configuredBatch, int $parallelHttp, bool $incremental, int $multiplier = 1): int
    {
        $configuredBatch = max(1, $configuredBatch);
        if (!$incremental) {
            return $configuredBatch;
        }
        $parallelHttp = max(1, $parallelHttp);
        $multiplier   = max(1, min(4, $multiplier));
        $chunk        = $parallelHttp * $multiplier;
        return max(1, min($configuredBatch, $chunk));
    }

    public static function incrementalClaimEnabled(?string $raw = null): bool
    {
        $raw = strtolower(trim((string) ($raw ?? env('ENDORSE_REFRESH_INCREMENTAL_CLAIM', 'false'))));
        return $raw === '1' || $raw === 'true' || $raw === 'on' || $raw === 'yes';
    }

    /**
     * Parity with bhskin's `has_valid_stats`: the direct scrape is only "usable" when
     * at least one engagement stat is strictly > 0. Forbes' isValidTiktokScrapeItem
     * accepts a row where the keys merely EXIST (all-zero), which both writes bogus
     * zeros and masks the need to fall back. Default off to preserve behaviour.
     *
     * @param array $stats e.g. ['diggCount'=>.., 'playCount'=>.., ...]
     */
    public static function scrapeStatsAreUsable(array $stats): bool
    {
        foreach (['diggCount', 'shareCount', 'commentCount', 'collectCount', 'playCount'] as $k) {
            if (intval($stats[$k] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    public static function strictScrapeStatsEnabled(?string $raw = null): bool
    {
        $raw = strtolower(trim((string) ($raw ?? env('ENDORSE_REFRESH_STRICT_SCRAPE_STATS', 'false'))));
        return $raw === '1' || $raw === 'true' || $raw === 'on' || $raw === 'yes';
    }

    /**
     * True incremental draining decision: given the run deadline and time already
     * spent, decide whether another slot-sized chunk may be claimed. Encodes rule 7
     * (never start work that cannot safely finish): only claim again if the remaining
     * budget exceeds one chunk's worst-case wall (perItemTimeout) plus a safety margin.
     * Pure so the loop's stop condition is regression-locked.
     */
    public static function mayClaimAnotherChunk(float $deadlineSec, float $elapsedSec, float $perItemTimeoutSec, float $safetyMarginSec = 2.0): bool
    {
        $remaining = $deadlineSec - $elapsedSec;
        return $remaining >= ($perItemTimeoutSec + $safetyMarginSec);
    }

    /**
     * True incremental drain loop. Repeatedly claims a slot-sized chunk, processes it,
     * and only claims again while the deadline safely allows another chunk — so ONE run
     * drains MULTIPLE bounded chunks instead of reserving one huge batch it cannot start.
     *
     * Fully injectable (clock + claim + process callables) so the multi-chunk behaviour,
     * deadline stop and clean release are deterministically testable without CI or a DB.
     *
     *   $claimChunk(int $chunkSize): array  → returns claimed items (possibly < chunkSize)
     *   $process(array $items): array       → ['started'=>int,'unique_completed'=>int,'deferred'=>int]
     *   $now(): float                       → monotonic seconds
     *
     * @return array run totals incl. chunk count.
     */
    public static function drainIncrementally(
        float $deadlineSec,
        int $chunkSize,
        float $perChunkTimeoutSec,
        callable $claimChunk,
        callable $process,
        callable $now,
        float $safetyMarginSec = 2.0
    ): array {
        $start = $now();
        $totals = ['chunks' => 0, 'claimed' => 0, 'requests_started' => 0, 'unique_completed' => 0, 'deferred_unstarted' => 0];
        while (true) {
            $elapsed = $now() - $start;
            if (!self::mayClaimAnotherChunk($deadlineSec, $elapsed, $perChunkTimeoutSec, $safetyMarginSec)) {
                break;
            }
            $items = $claimChunk(max(1, $chunkSize));
            $claimed = is_array($items) ? count($items) : 0;
            if ($claimed === 0) {
                break; // queue empty for now
            }
            $totals['chunks']++;
            $totals['claimed'] += $claimed;
            $r = $process($items);
            $totals['requests_started']   += intval($r['started'] ?? 0);
            $totals['unique_completed']   += intval($r['unique_completed'] ?? 0);
            $totals['deferred_unstarted'] += intval($r['deferred'] ?? 0);
        }
        return $totals;
    }

    public static function inlineFallbackRetryEnabled(?string $raw = null): bool
    {
        $raw = strtolower(trim((string) ($raw ?? env('ENDORSE_REFRESH_INLINE_FALLBACK_RETRY', 'false'))));
        return $raw === '1' || $raw === 'true' || $raw === 'on' || $raw === 'yes';
    }

    /**
     * Bounded inline-retry policy. Parity with bhskin's inline retry WITHOUT its
     * unlimited timeout: retry only proven-retryable classes, cap attempts, and never
     * start another retry unless enough run budget remains for a full attempt.
     *
     * @param string $errorClass Endorse_sync::ERR_* of the just-finished attempt
     * @return bool whether another inline retry should be started now
     */
    public static function shouldInlineRetry(string $errorClass, int $attemptNo, int $maxAttempts, float $remainingBudgetSec, float $perAttemptTimeoutSec): bool
    {
        if ($attemptNo >= $maxAttempts) {
            return false;
        }
        if ($remainingBudgetSec < $perAttemptTimeoutSec) {
            return false; // not enough time to complete another attempt safely
        }
        $retryable = [
            Endorse_sync::ERR_TRANSIENT,
            Endorse_sync::ERR_INFRA,
            Endorse_sync::ERR_INFRA_DNS,
            Endorse_sync::ERR_INFRA_CONNECT,
            Endorse_sync::ERR_INFRA_TLS,
            Endorse_sync::ERR_INFRA_STALL,
        ];
        return in_array($errorClass, $retryable, true);
    }

    /**
     * Structured per-run summary for observability. Distinguishes claims, actual
     * requests started, completions and UNIQUE successes (the business metric) so an
     * operator never again mistakes `processed` for throughput. Returns a compact JSON
     * string; contains no URLs, cookies, keys or bodies.
     */
    public static function buildRunSummary(array $m): string
    {
        $fields = [
            'run_id', 'worker_id', 'source_revision', 'fetch_mode', 'limiter_mode',
            'effective_batch', 'effective_concurrency', 'effective_rate',
            'claimed', 'requests_started', 'direct_attempts', 'direct_successes',
            'fallback_attempts', 'fallback_successes', 'transient', 'terminal',
            'deferred_unstarted', 'stale_recovered', 'unique_completed', 'wall_ms',
        ];
        $out = ['evt' => 'endorse_refresh_run'];
        foreach ($fields as $f) {
            $out[$f] = $m[$f] ?? null;
        }
        return json_encode($out, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Normalize supported social links stored without a scheme. Other absolute URLs
     * stay untouched so existing response classification remains authoritative.
     */
    public static function normalizeTiktokUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (preg_match('#^(?:[a-z0-9-]+\.)?(?:tiktok\.com|instagram\.com|threads\.(?:com|net))/#i', $url)) {
            return 'https://' . $url;
        }

        return $url;
    }

    protected $CI;
    protected $db;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('mymodel');
        $this->db = $this->CI->db;
        if (is_file(APPPATH . 'libraries/EndorseRefreshV2Coordinator.php')) {
            $this->CI->load->library('EndorseRefreshV2Coordinator');
        }
    }

    public function enqueueCampaign(int $id_campaign, int $user_id, array $ids = []): array
    {
        if ($id_campaign <= 0) {
            return [
                'status' => false,
                'msg' => 'Campaign tidak valid.',
                'enqueued' => 0,
                'skipped_duplicates' => 0,
                'id_campaign' => $id_campaign,
            ];
        }

        $extra = '';
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!empty($ids)) {
            $extra = ' AND id IN (' . implode(',', $ids) . ')';
        }

        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT id, id_campaign, platform, link_upload
            FROM endorse
            WHERE id_campaign = '" . intval($id_campaign) . "'
              AND status = 'Aktif' AND status_campaign = 'Aktif'
              AND link_upload != ''
              $extra
        ");

        if (empty($rows)) {
            return [
                'status' => false,
                'msg' => 'Tidak ada konten aktif yang bisa direfresh.',
                'enqueued' => 0,
                'skipped_duplicates' => 0,
                'id_campaign' => $id_campaign,
            ];
        }

        $runId = $this->startDiagnosticRun('manual_campaign', $user_id, $id_campaign, count($rows));
        $stats = $this->enqueueRows($rows, $user_id, $runId, 'manual_campaign');
        $this->finishDiagnosticRun($runId, array_merge($stats, array('candidate_count' => count($rows))));
        $msg = $this->buildEnqueueMessage($stats['enqueued'], $stats['skipped_duplicates'], $stats['excluded_known_url']);

        return [
            'status' => true,
            'msg' => $msg,
            'enqueued' => $stats['enqueued'],
            'skipped_duplicates' => $stats['skipped_duplicates'],
            'excluded_known_url' => $stats['excluded_known_url'],
            'count' => count($rows),
            'id_campaign' => $id_campaign,
        ];
    }

    public function enqueueAllActive(int $user_id): array
    {
        // Refresh Semua rule: sync only ACTIVE campaigns and, within them, only ACTIVE
        // posts. Never enqueue anything under a "Tidak Aktif" campaign.
        //   c.status = 'Aktif'          — the campaign's own status (source of truth)
        //   e.status = 'Aktif'          — the post's status
        //   e.status_campaign = 'Aktif' — denormalized campaign flag on the row (guards
        //                                 against any drift vs c.status)
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT e.id, e.id_campaign, e.platform, e.link_upload
            FROM endorse e
            INNER JOIN endorse_campaign c ON c.id = e.id_campaign
            WHERE c.status = 'Aktif'
              AND e.status = 'Aktif'
              AND e.status_campaign = 'Aktif'
              AND e.link_upload != ''
            ORDER BY e.id_campaign ASC, e.id ASC
        ");

        if (empty($rows)) {
            return [
                'status' => true,
                'msg' => 'Tidak ada konten aktif yang bisa direfresh.',
                'campaign_count' => 0,
                'candidate_count' => 0,
                'enqueued' => 0,
                'skipped_duplicates' => 0,
                'excluded_known_url' => 0,
            ];
        }

        $runId = $this->startDiagnosticRun('manual_all', $user_id, 0, count($rows));
        $stats = $this->enqueueRows($rows, $user_id, $runId, 'manual_all');
        $this->finishDiagnosticRun($runId, array_merge($stats, array('candidate_count' => count($rows))));

        return [
            'status' => true,
            'msg' => $this->buildEnqueueMessage($stats['enqueued'], $stats['skipped_duplicates'], $stats['excluded_known_url']),
            'campaign_count' => $stats['campaign_count'],
            'candidate_count' => count($rows),
            'enqueued' => $stats['enqueued'],
            'skipped_duplicates' => $stats['skipped_duplicates'],
            'excluded_known_url' => $stats['excluded_known_url'],
        ];
    }

    /**
     * Enqueue a single frozen-snapshot job (initial baseline or final) for one endorse.
     * High priority (default 50) so it jumps the daily backlog. Purpose-scoped dedup.
     * No-ops for placeholder platforms (metrics entered manually) and for an already
     * captured initial baseline.
     */
    public function enqueueSnapshot(int $id_endorse, string $purpose, int $user_id, int $priority = 50): array
    {
        $purpose = ($purpose === 'final') ? 'final' : 'initial';

        if ($id_endorse <= 0) {
            return ['status' => false, 'msg' => 'Endorse tidak valid.', 'enqueued' => 0];
        }

        $row = $this->CI->mymodel->selectDataOne('endorse', ['id' => $id_endorse]);
        if (empty($row)) {
            return ['status' => false, 'msg' => 'Endorse tidak ditemukan.', 'enqueued' => 0];
        }

        $platform = strval($row['platform'] ?? '');
        $link = trim((string) ($row['link_upload'] ?? ''));

        if ($link === '') {
            return ['status' => false, 'msg' => 'Link konten kosong, snapshot dilewati.', 'enqueued' => 0];
        }

        $this->CI->load->helper('social_platform');
        if (!is_auto_fetch_platform($platform)) {
            // Placeholder platform: metrics are entered manually, nothing to enqueue.
            return ['status' => false, 'msg' => 'Platform belum mendukung auto-fetch.', 'enqueued' => 0, 'placeholder' => true];
        }

        // Initial baseline must stay frozen — skip if already captured.
        if ($purpose === 'initial' && !empty($row['initial_fetched_at'])) {
            return ['status' => false, 'msg' => 'Baseline awal sudah diambil.', 'enqueued' => 0];
        }

        // Purpose-scoped dedup.
        $active = $this->loadActiveEndorseIds([$id_endorse], $purpose);
        if (isset($active[$id_endorse])) {
            return ['status' => false, 'msg' => 'Snapshot sudah ada di antrian.', 'enqueued' => 0];
        }

        $priority = $priority > 0 ? $priority : 50;
        $runId = $this->startDiagnosticRun('snapshot_' . $purpose, $user_id, intval($row['id_campaign'] ?? 0), 1);
        $inserted = $this->insertQueueRowsIgnoringDuplicates([[
            'id_endorse'   => $id_endorse,
            'id_campaign'  => intval($row['id_campaign'] ?? 0),
            'platform'     => $platform,
            'purpose'      => $purpose,
            'link_upload'  => $link,
            'status'       => 'pending',
            'priority'     => $priority,
            'attempts'     => 0,
            'max_attempts' => self::DEFAULT_MAX_ATTEMPTS,
            'enqueued_by'  => $user_id,
            'enqueue_run_id' => $runId,
            'enqueue_source' => 'snapshot_' . $purpose,
            'created_at'   => date('Y-m-d H:i:s'),
        ]]);
        $this->finishDiagnosticRun($runId, ['enqueued' => $inserted, 'skipped_duplicates' => $inserted === 0 ? 1 : 0, 'excluded_known_url' => 0]);

        if ($inserted === 0) {
            return ['status' => false, 'msg' => 'Snapshot sudah ada di antrian.', 'enqueued' => 0, 'purpose' => $purpose];
        }

        return ['status' => true, 'msg' => 'Snapshot ditambahkan ke antrian.', 'enqueued' => 1, 'purpose' => $purpose];
    }

    /**
     * Reconcile sweep: enqueue a 'final' snapshot for any auto-fetch endorse that is
     * Completed but has no final snapshot yet (covers enqueues lost after the row
     * update committed). Safe to run repeatedly — dedup + frozen guards prevent dupes.
     */
    public function enqueuePendingFinals(int $user_id, int $limit = 200): array
    {
        $limit = $limit > 0 ? $limit : 200;
        $this->CI->load->helper('social_platform');

        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT id, platform
            FROM endorse
            WHERE optimization_status = 'Completed'
              AND final_fetched_at IS NULL
              AND link_upload != ''
            ORDER BY id ASC
            LIMIT $limit
        ");

        $enqueued = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            if (!is_auto_fetch_platform($row['platform'])) {
                $skipped++;
                continue;
            }
            $res = $this->enqueueSnapshot(intval($row['id']), 'final', $user_id);
            if (!empty($res['enqueued'])) {
                $enqueued++;
            } else {
                $skipped++;
            }
        }

        return [
            'status'   => true,
            'msg'      => "$enqueued final snapshot dijadwalkan, $skipped dilewati.",
            'enqueued' => $enqueued,
            'skipped'  => $skipped,
        ];
    }

    public function cloneFailedRows(array $queueIds, int $user_id): array
    {
        $queueIds = array_values(array_unique(array_filter(array_map('intval', $queueIds))));
        if (empty($queueIds)) {
            return ['status' => false, 'msg' => 'Tidak ada baris dipilih.', 'updated' => 0];
        }

        $idList = implode(',', $queueIds);
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT id, id_endorse, id_campaign, platform, link_upload, priority, max_attempts
            FROM endorse_refresh_queue
            WHERE id IN ($idList) AND status = 'failed'
        ");

        if (empty($rows)) {
            return ['status' => false, 'msg' => 'Tidak ada baris gagal yang bisa dijadwalkan ulang.', 'updated' => 0];
        }

        $active = $this->loadActiveEndorseIds(array_map(function ($row) {
            return intval($row['id_endorse']);
        }, $rows));

        $now = date('Y-m-d H:i:s');
        $batch = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $id_endorse = intval($row['id_endorse']);
            if (isset($active[$id_endorse])) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'id_endorse' => $id_endorse,
                'id_campaign' => intval($row['id_campaign']),
                'platform' => strval($row['platform']),
                'link_upload' => strval($row['link_upload']),
                'status' => 'pending',
                'priority' => intval($row['priority']) > 0 ? intval($row['priority']) : self::DEFAULT_PRIORITY,
                'attempts' => 0,
                'max_attempts' => intval($row['max_attempts']) > 0 ? intval($row['max_attempts']) : self::DEFAULT_MAX_ATTEMPTS,
                'enqueued_by' => $user_id,
                'retry_source_id' => intval($row['id']),
                'created_at' => $now,
            ];
        }

        $runId = $this->startDiagnosticRun('manual_retry', $user_id, 0, count($rows));
        foreach ($batch as &$queued) { $queued['enqueue_run_id'] = $runId; $queued['enqueue_source'] = 'manual_retry'; }
        unset($queued);
        if (!empty($batch)) {
            $inserted = $this->insertQueueRowsIgnoringDuplicates($batch);
            $skipped += count($batch) - $inserted;
        } else {
            $inserted = 0;
        }
        $this->finishDiagnosticRun($runId, ['enqueued' => $inserted, 'skipped_duplicates' => $skipped, 'excluded_known_url' => 0]);

        return [
            'status' => true,
            'msg' => $inserted . ' baris dijadwalkan ulang.' . ($skipped > 0 ? " $skipped dilewati karena masih aktif di antrian." : ''),
            'updated' => $inserted,
            'skipped_duplicates' => $skipped,
        ];
    }

    public function clearAll(): array
    {
        $user = $_SESSION['user'] ?? array();
        $reason = isset($_POST['reason']) ? trim((string) $_POST['reason']) : 'manual_clear';
        if (is_file(APPPATH . 'libraries/EndorseRefreshDiagnostics.php')) {
            $this->CI->load->library('EndorseRefreshDiagnostics');
            $archived = $this->CI->endorserefreshdiagnostics->archiveAndClear(intval($user['id'] ?? 0), $reason);
            return ['status' => true, 'msg' => $archived['queue'] . ' data antrian dan ' . $archived['attempts'] . ' riwayat percobaan diarsipkan selama 30 hari.', 'deleted_queue' => $archived['queue'], 'deleted_attempts' => $archived['attempts'], 'archive_reason' => $archived['reason']];
        }
        $attemptRows = $this->CI->mymodel->selectWithQuery("SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts");
        $queueRows = $this->CI->mymodel->selectWithQuery("SELECT COUNT(*) AS c FROM endorse_refresh_queue");
        $attemptCount = !empty($attemptRows) ? intval($attemptRows[0]['c']) : 0;
        $queueCount = !empty($queueRows) ? intval($queueRows[0]['c']) : 0;

        $this->db->trans_start();
        $this->db->query("DELETE FROM endorse_refresh_queue_attempts");
        $this->db->query("DELETE FROM endorse_refresh_queue");
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return [
                'status' => false,
                'msg' => 'Gagal menghapus data antrian.',
                'deleted_queue' => 0,
                'deleted_attempts' => 0,
            ];
        }

        return [
            'status' => true,
            'msg' => $queueCount . ' data antrian dan ' . $attemptCount . ' riwayat percobaan dihapus.',
            'deleted_queue' => $queueCount,
            'deleted_attempts' => $attemptCount,
        ];
    }

    /**
     * Fence expired leases, close their active attempts as timed_out, then apply
     * backoff or max-attempt failure. The locked identity makes repeated recovery
     * idempotent and prevents a slow old worker from owning the new claim.
     */
    public function resetStuck(int $staleMinutes = 5): array
    {
        $staleMinutes = max(1, intval($staleMinutes));
        $now = date('Y-m-d H:i:s');
        $schedulingNow = self::schedulingNowSql();
        $retryBase = max(1, min(3600, intval(env('ENDORSE_REFRESH_RETRY_BASE_SEC', 60))));

        $this->db->trans_begin();
        try {
            $result = $this->db->query("
                SELECT q.*,
                       a.id AS current_attempt_id,
                       a.attempt_no AS current_attempt_no,
                       a.status AS current_attempt_status,
                       COALESCE((
                           SELECT MAX(history.attempt_no)
                           FROM endorse_refresh_queue_attempts history
                           WHERE history.queue_id = q.id
                       ), 0) AS history_attempt_sequence,
                       COALESCE((
                           SELECT SUM(CASE WHEN consumed.status <> 'cancelled' THEN 1 ELSE 0 END)
                           FROM endorse_refresh_queue_attempts consumed
                           WHERE consumed.queue_id = q.id
                       ), 0) AS history_consumed_attempts
                FROM endorse_refresh_queue q
                LEFT JOIN endorse_refresh_queue_attempts a
                  ON a.id = q.active_attempt_id
                 AND a.queue_id = q.id
                WHERE q.status = 'processing'
                  AND q.started_at IS NOT NULL
                  AND COALESCE(
                        q.lease_expires_at,
                        DATE_ADD(q.started_at, INTERVAL {$staleMinutes} MINUTE)
                      ) < NOW(6)
                  -- Already-quarantined rows are excluded, and this is load-bearing rather
                  -- than cosmetic. An inconsistent row is deliberately never mutated into a
                  -- recoverable state, so without this it stays in the window forever; 250
                  -- of them at low ids would permanently fill the LIMIT and ordinary stale
                  -- work behind them would silently stop being recovered altogether.
                  AND COALESCE(q.error_message, '') NOT LIKE '" . self::RECONCILIATION_MARKER . "%'
                ORDER BY q.id ASC
                LIMIT 250
                FOR UPDATE SKIP LOCKED
            ");
            $rows = $this->resultRowsOrThrow($result, 'select stale queue rows');

            $reset = 0;
            $failed = 0;
            $inconsistent = [];
            foreach ($rows as $row) {
                $queueId = intval($row['id']);
                $workerId = strval($row['worker_id'] ?? '');
                $attemptId = intval($row['current_attempt_id'] ?? 0);
                $attemptNo = intval($row['current_attempt_no'] ?? 0);
                $historySequence = intval($row['history_attempt_sequence'] ?? 0);
                $attemptStatus = strval($row['current_attempt_status'] ?? '');
                // The parent's own active_attempt_id is the fencing key for the release
                // below; it must be used verbatim, including when the attempt it points
                // at has already been closed by another writer.
                $activeAttemptId = intval($row['active_attempt_id'] ?? 0);
                // Consumption is DERIVED from history, never incremented blind, so running
                // recovery twice cannot charge the same attempt twice. 'cancelled' rows are
                // allocations that never started a provider request and never count.
                $consumedFromHistory = intval($row['history_consumed_attempts'] ?? 0);

                $decision = self::recoveryDecision($attemptStatus, $activeAttemptId, $attemptId);

                if ($decision === self::RECOVERY_INCONSISTENT) {
                    // Never silently retry an ambiguous state: a completed attempt means the
                    // provider work and its business write already committed, and a dangling
                    // active_attempt_id means history we cannot interpret.
                    //
                    // Quarantine rather than merely observe. Leaving the row untouched keeps
                    // it fail-closed but also keeps it in the recovery window forever, so it
                    // is re-read and re-logged every poll and crowds out rows that CAN be
                    // recovered. The marker persists the diagnostic, takes the row out of the
                    // window, and changes nothing else — status, attempts and history stay
                    // exactly as found, so a human still sees the original evidence.
                    $observedStatus = $attemptStatus !== '' ? $attemptStatus : 'missing';
                    $this->dbWriteOrThrow($this->db->update('endorse_refresh_queue', [
                        'error_message' => self::reconciliationMessage($observedStatus, $activeAttemptId),
                    ], [
                        'id' => $queueId,
                        'status' => 'processing',
                    ]), 'quarantine inconsistent queue row', 1);

                    $inconsistent[] = [
                        'queue_id' => $queueId,
                        'active_attempt_id' => $activeAttemptId,
                        'attempt_status' => $observedStatus,
                    ];

                    continue;
                }

                if ($decision === self::RECOVERY_CLOSE_OPEN_ATTEMPT) {
                    $this->db->set('finished_at', $schedulingNow, false);
                    $this->dbWriteOrThrow($this->db->update('endorse_refresh_queue_attempts', [
                        'status' => 'timed_out',
                        'error_class' => Endorse_sync::ERR_INFRA_STALL,
                        'error_message' => 'Claim lease expired; stale worker fenced',
                    ], [
                        'id' => $attemptId,
                        'queue_id' => $queueId,
                        'worker_id' => $workerId,
                        'status' => 'processing',
                    ]), 'close stale attempt', 1);
                    // The attempt was already non-cancelled, so it is already counted.
                } elseif ($decision === self::RECOVERY_SYNTHESIZE_ATTEMPT) {
                    // Legacy claim may have reached the provider after its attempt insert
                    // failed. Preserve that unknown request as an explicitly reconciled
                    // timed-out attempt so it is never mistaken for a real provider success.
                    // Bounded: the parent leaves 'processing' in the same transaction.
                    $attemptNo = max(intval($row['attempt_sequence'] ?? 0), $historySequence) + 1;
                    $this->db->set('finished_at', $schedulingNow, false);
                    $this->dbWriteOrThrow($this->db->insert('endorse_refresh_queue_attempts', [
                        'queue_id' => $queueId,
                        'attempt_no' => $attemptNo,
                        'worker_id' => $workerId !== '' ? $workerId : null,
                        'status' => 'timed_out',
                        'error_class' => Endorse_sync::ERR_INFRA_STALL,
                        'error_message' => 'internal_reconciled: recovered claim without active attempt history',
                        'started_at' => strval($row['started_at'] ?? $now),
                        'created_at' => strval($row['started_at'] ?? $now),
                    ]), 'insert recovered timed-out attempt');
                    $consumedFromHistory++;
                } else {
                    // RECOVERY_RELEASE_ONLY: the attempt is already closed (failed, timed_out
                    // or cancelled). Its history is authoritative; only the parent needs
                    // releasing, and no second timeout record is created.
                    $attemptNo = max($attemptNo, $historySequence);
                }

                $maxAttempts = max(1, intval($row['max_attempts'] ?? self::DEFAULT_MAX_ATTEMPTS));
                $consumedAttempts = max(intval($row['attempts'] ?? 0), $consumedFromHistory);
                $isExhausted = $consumedAttempts >= $maxAttempts;

                $queueUpdate = [
                    'status' => $isExhausted ? 'failed' : 'pending',
                    'attempts' => $consumedAttempts,
                    'attempt_sequence' => max(intval($row['attempt_sequence'] ?? 0), $historySequence, $attemptNo),
                    'active_attempt_id' => null,
                    'worker_id' => null,
                    'claim_owner' => null,
                    'started_at' => null,
                    'lease_expires_at' => null,
                    'error_message' => $isExhausted
                        ? 'Claim lease expired and maximum attempts were exhausted'
                        : 'Claim lease expired; scheduled for retry',
                ];
                $rawUpdate = ['claimed_at' => $schedulingNow];
                if ($isExhausted) {
                    $queueUpdate['next_attempt_at'] = null;
                    $rawUpdate['completed_at'] = $schedulingNow;
                } else {
                    $queueUpdate['completed_at'] = null;
                    $rawUpdate['next_attempt_at'] = self::schedulingDeadlineSql(
                        self::retryDelayWithJitter($queueId, $consumedAttempts, $retryBase)
                    );
                }

                // Fence on the parent's own ownership triple. active_attempt_id is taken
                // from the locked row, so it matches whether or not the attempt it points
                // at is still open; asserting one affected row turns a predicate that can
                // no longer match into a loud failure instead of a silent no-op.
                $where = [
                    'id' => $queueId,
                    'status' => 'processing',
                    'worker_id' => $workerId,
                    'active_attempt_id' => $activeAttemptId > 0 ? $activeAttemptId : null,
                ];

                foreach ($rawUpdate as $column => $expression) {
                    $this->db->set($column, $expression, false);
                }
                $this->dbWriteOrThrow($this->db->update('endorse_refresh_queue', $queueUpdate, $where), 'release stale queue', 1);
                $reset++;
                $failed += $isExhausted ? 1 : 0;
            }

            $this->commitOrThrow();

            if (! empty($inconsistent)) {
                // Observable, not silently normalised: these rows need reconciliation and
                // are deliberately left untouched by automatic recovery.
                log_message('error', 'endorse_refresh_recovery_inconsistent: ' . json_encode($inconsistent, JSON_UNESCAPED_SLASHES));
            }

            return [
                'status' => true,
                'reset_count' => $reset,
                'failed_count' => $failed,
                'inconsistent_count' => count($inconsistent),
                'inconsistent' => $inconsistent,
                'msg' => $reset > 0
                    ? "$reset item macet ditutup dengan fencing dan dijadwalkan ulang."
                    : 'Tidak ada claim kedaluwarsa untuk dipulihkan.',
            ];
        } catch (\Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'endorse_refresh_reset_stuck_failed: ' . $e->getMessage());

            return [
                'status' => false, 'reset_count' => 0, 'failed_count' => 0,
                'inconsistent_count' => 0, 'inconsistent' => [], 'msg' => 'Stale recovery gagal.',
            ];
        }
    }

    public function computeHealth(int $id_campaign = 0, int $staleMinutes = 5): array
    {
        $where = '';
        if ($id_campaign > 0) {
            $where = " AND id_campaign = '" . intval($id_campaign) . "'";
        }

        $summaryRows = $this->CI->mymodel->selectWithQuery("
            SELECT status, COUNT(*) AS c
            FROM endorse_refresh_queue
            WHERE status IN ('pending','processing','submitted')
            $where
            GROUP BY status
        ");

        $pending = 0;
        $processing = 0;
        $submitted = 0;
        foreach ($summaryRows as $row) {
            if ($row['status'] === 'pending') {
                $pending = intval($row['c']);
            }
            if ($row['status'] === 'processing') {
                $processing = intval($row['c']);
            }
            if ($row['status'] === 'submitted') {
                $submitted = intval($row['c']);
            }
        }

        $metaRows = $this->CI->mymodel->selectWithQuery("
            SELECT
                MIN(CASE WHEN status = 'pending' THEN created_at END) AS oldest_pending_at,
                MAX(CASE WHEN status IN ('completed','failed') THEN completed_at END) AS last_completed_at,
                MAX(CASE WHEN status = 'processing' THEN started_at END) AS last_started_at
            FROM endorse_refresh_queue
            WHERE 1 = 1
            $where
        ");
        $meta = !empty($metaRows) ? $metaRows[0] : [];

        $oldestPendingAt = $meta['oldest_pending_at'] ?? null;
        $lastCompletedAt = $meta['last_completed_at'] ?? null;
        $lastStartedAt = $meta['last_started_at'] ?? null;
        $lastActivityAt = $lastStartedAt ?: $lastCompletedAt;
        $isStalled = false;

        if ($pending > 0 && $processing === 0 && $submitted === 0) {
            if (empty($lastActivityAt) || strtotime($lastActivityAt) < strtotime('-' . intval($staleMinutes) . ' minutes')) {
                $isStalled = true;
            }
        }

        $stall = $isStalled ? $this->diagnoseStall() : null;

        // Rows automatic recovery has given up on. Both are invisible in the status
        // counts above — a quarantined row still reads as 'processing' and a terminal
        // poison row as 'failed' — so without these two they look like ordinary work and
        // nobody is told a human has to intervene.
        $attentionRows = $this->CI->mymodel->selectWithQuery("
            SELECT
                SUM(status = 'processing' AND error_message LIKE '" . self::RECONCILIATION_MARKER . "%') AS needs_reconciliation,
                SUM(status = 'failed' AND error_message LIKE 'poison:%') AS poison_terminal
            FROM endorse_refresh_queue
            WHERE 1 = 1
            $where
        ");
        $attention = !empty($attentionRows) ? $attentionRows[0] : [];

        return [
            'active_total' => $pending + $processing + $submitted,
            'pending_total' => $pending,
            'processing_total' => $processing,
            'submitted_total' => $submitted,
            'oldest_pending_at' => $oldestPendingAt,
            'last_completed_at' => $lastCompletedAt,
            'last_started_at' => $lastStartedAt,
            'is_stalled' => $isStalled,
            'stall_reason' => $stall['reason'] ?? null,
            'stall_label' => $stall['label'] ?? null,
            'needs_reconciliation_total' => intval($attention['needs_reconciliation'] ?? 0),
            'poison_terminal_total' => intval($attention['poison_terminal'] ?? 0),
        ];
    }

    /**
     * Explain WHY the queue is stalled, using the same DB signals the worker
     * (Api_v2::cronjob_endorse_refresh) checks — so the banner is accurate
     * without reading server logs. Mirrors the worker's daily/per-minute cap
     * queries and falls back to the most recent attempt error.
     */
    protected function diagnoseStall(): array
    {
        $dailyCap = intval(env('ENDORSE_REFRESH_DAILY_CAP', 0));
        $ratePerMin = intval(env('ENDORSE_REFRESH_RATE_PER_MIN', 0));

        if ($dailyCap > 0) {
            $startOfDay = date('Y-m-d') . ' 00:00:00';
            $row = $this->CI->mymodel->selectWithQuery("
                SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts
                WHERE started_at >= '$startOfDay'
            ");
            $usedToday = intval($row[0]['c'] ?? 0);
            if ($usedToday >= $dailyCap) {
                return ['reason' => 'daily_cap', 'label' => "batas harian tercapai ($usedToday/$dailyCap)"];
            }
        }

        if ($ratePerMin > 0) {
            $row = $this->CI->mymodel->selectWithQuery("
                SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts
                WHERE started_at >= (NOW() - INTERVAL 60 SECOND)
            ");
            $usedMinute = intval($row[0]['c'] ?? 0);
            if ($usedMinute >= $ratePerMin) {
                return ['reason' => 'rate_cap', 'label' => "batas per-menit tercapai ($usedMinute/$ratePerMin)"];
            }
        }

        $row = $this->CI->mymodel->selectWithQuery("
            SELECT error_class, COUNT(*) AS c
            FROM endorse_refresh_queue_attempts
            WHERE status IN ('failed','retrying')
              AND started_at >= (NOW() - INTERVAL 15 MINUTE)
            GROUP BY error_class
            ORDER BY c DESC
            LIMIT 1
        ");
        if (!empty($row)) {
            $cls = $row[0]['error_class'] ?: 'unknown';
            return ['reason' => 'upstream_error', 'label' => "error upstream: $cls"];
        }

        return ['reason' => 'idle_worker', 'label' => 'worker tidak berjalan (cek cron)'];
    }

    protected function loadActiveEndorseIds(array $endorseIds, string $purpose = 'daily'): array
    {
        $endorseIds = array_values(array_unique(array_filter(array_map('intval', $endorseIds))));
        if (empty($endorseIds)) {
            return [];
        }

        // Dedup is purpose-scoped: a daily, an initial and a final job for the same
        // endorse are distinct and must NOT swallow each other.
        $purpose = $this->db->escape($purpose);
        $idList = implode(',', $endorseIds);
        $existing = $this->CI->mymodel->selectWithQuery("
            SELECT id_endorse
            FROM endorse_refresh_queue
            WHERE id_endorse IN ($idList)
              AND purpose = $purpose
              AND status IN ('pending','processing','submitted')
        ");

        $active = [];
        foreach ($existing as $row) {
            $active[intval($row['id_endorse'])] = true;
        }

        return $active;
    }

    /**
     * Database-enforced idempotency for concurrent enqueue callers. The no-op
     * duplicate-key branch preserves the already-active queue row.
     */
    private function insertQueueRowsIgnoringDuplicates(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }
        $columns = array_keys($rows[0]);
        $columnSql = implode(', ', array_map(static function ($column) {
            return '`' . str_replace('`', '``', (string) $column) . '`';
        }, $columns));
        $values = [];
        foreach ($rows as $row) {
            $encoded = [];
            foreach ($columns as $column) {
                $encoded[] = $this->db->escape($row[$column] ?? null);
            }
            $values[] = '(' . implode(', ', $encoded) . ')';
        }
        $ok = $this->db->query(
            'INSERT INTO `endorse_refresh_queue` (' . $columnSql . ') VALUES ' . implode(', ', $values)
            . ' ON DUPLICATE KEY UPDATE `id` = `id`'
        );
        if ($ok === false) {
            throw new RuntimeException('enqueue insert failed');
        }

        return intval($this->db->affected_rows());
    }

    protected function enqueueRows(array $rows, int $user_id, string $runId = '', string $source = 'manual_campaign'): array
    {
        $candidateIds = array_map(function ($row) {
            return intval($row['id']);
        }, $rows);

        $already = $this->loadActiveEndorseIds($candidateIds);
        $knownUrlIssues = $this->loadKnownUrlIssueEndorseIds($candidateIds);
        $campaigns = [];
        $now = date('Y-m-d H:i:s');
        $batch = [];
        $enqueued = 0;
        $skipped = 0;
        $excludedKnownUrl = 0;

        foreach ($rows as $row) {
            $campaigns[intval($row['id_campaign'])] = true;
            $id_endorse = intval($row['id']);

            if (isset($already[$id_endorse])) {
                $skipped++;
                continue;
            }

            if (isset($knownUrlIssues[$id_endorse])) {
                $excludedKnownUrl++;
                continue;
            }

            if (!empty($this->CI->endorserefreshv2coordinator)
                && $this->CI->endorserefreshv2coordinator->isQuarantinedContent(
                    $id_endorse,
                    strval($row['platform']),
                    strval($row['link_upload'])
                )) {
                $excludedKnownUrl++;
                continue;
            }

            $batch[] = [
                'id_endorse' => $id_endorse,
                'id_campaign' => intval($row['id_campaign']),
                'platform' => strval($row['platform']),
                'link_upload' => strval($row['link_upload']),
                'status' => 'pending',
                'priority' => self::DEFAULT_PRIORITY,
                'attempts' => 0,
                'max_attempts' => self::DEFAULT_MAX_ATTEMPTS,
                'enqueued_by' => $user_id,
                'enqueue_run_id' => $runId !== '' ? $runId : null,
                'enqueue_source' => $source,
                'created_at' => $now,
            ];

            if (count($batch) >= self::INSERT_CHUNK_SIZE) {
                $inserted = $this->insertQueueRowsIgnoringDuplicates($batch);
                $enqueued += $inserted;
                $skipped += count($batch) - $inserted;
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $inserted = $this->insertQueueRowsIgnoringDuplicates($batch);
            $enqueued += $inserted;
            $skipped += count($batch) - $inserted;
        }

        return [
            'campaign_count' => count($campaigns),
            'enqueued' => $enqueued,
            'skipped_duplicates' => $skipped,
            'excluded_known_url' => $excludedKnownUrl,
        ];
    }

    protected function startDiagnosticRun(string $source, int $userId, int $campaignId, int $candidates): string
    {
        if (!is_file(APPPATH . 'libraries/EndorseRefreshDiagnostics.php')) return '';
        try {
            $this->CI->load->library('EndorseRefreshDiagnostics');
            return $this->CI->endorserefreshdiagnostics->startRun($source, $userId, $campaignId, array('driver' => env('ENDORSE_REFRESH_DRIVER', 'cron'), 'batch_size' => env('ENDORSE_REFRESH_BATCH_SIZE', 20), 'parallel_http' => env('ENDORSE_REFRESH_PARALLEL_HTTP', 10), 'rate_per_min' => env('ENDORSE_REFRESH_RATE_PER_MIN', 0), 'candidate_count' => $candidates));
        } catch (Throwable $e) { log_message('error', 'refresh diagnostic enqueue start failed: ' . get_class($e)); return ''; }
    }

    protected function finishDiagnosticRun(string $runId, array $stats): void
    {
        if ($runId === '' || !isset($this->CI->endorserefreshdiagnostics)) return;
        $this->CI->endorserefreshdiagnostics->finishRun($runId, array('candidate_count' => intval($stats['candidate_count'] ?? 0), 'enqueued_count' => intval($stats['enqueued'] ?? 0), 'skipped_duplicate_count' => intval($stats['skipped_duplicates'] ?? 0), 'excluded_count' => intval($stats['excluded_known_url'] ?? 0)));
    }

    protected function buildEnqueueMessage(int $enqueued, int $skipped, int $excludedKnownUrl): string
    {
        $msg = $enqueued . ' konten ditambahkan ke antrian.';
        if ($skipped > 0) {
            $msg .= " $skipped sudah ada di antrian.";
        }
        if ($excludedKnownUrl > 0) {
            $msg .= " $excludedKnownUrl dilewati karena URL TikTok bermasalah.";
        }

        return $msg;
    }

    protected function loadKnownUrlIssueEndorseIds(array $endorseIds): array
    {
        $endorseIds = array_values(array_unique(array_filter(array_map('intval', $endorseIds))));
        if (empty($endorseIds)) {
            return [];
        }

        $idList = implode(',', $endorseIds);
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT latest.id_endorse
            FROM endorse_refresh_queue latest
            INNER JOIN (
                SELECT id_endorse, MAX(id) AS max_id
                FROM endorse_refresh_queue
                WHERE id_endorse IN ($idList)
                GROUP BY id_endorse
            ) picked ON picked.max_id = latest.id
            WHERE latest.status = 'failed'
              AND (
                latest.error_message LIKE '%Stats data tidak ditemukan%'
                OR latest.error_message LIKE '%url tidak ditemukan%'
              )
        ");

        $blocked = [];
        foreach ($rows as $row) {
            $blocked[intval($row['id_endorse'])] = true;
        }

        if (!empty($this->CI->endorserefreshv2coordinator)) {
            $endorseRows = $this->CI->mymodel->selectWithQuery("
                SELECT id, platform, link_upload
                FROM endorse
                WHERE id IN ($idList)
            ");
            foreach ($endorseRows as $endorseRow) {
                $endorseId = intval($endorseRow['id'] ?? 0);
                if ($endorseId <= 0) {
                    continue;
                }
                if ($this->CI->endorserefreshv2coordinator->isQuarantinedContent(
                    $endorseId,
                    strval($endorseRow['platform'] ?? ''),
                    strval($endorseRow['link_upload'] ?? '')
                )) {
                    $blocked[$endorseId] = true;
                }
            }
        }

        return $blocked;
    }

    /**
     * Atomically claim a batch of pending rows and return them ready to fetch.
     *
     * Extracted from Api_v2::cronjob_endorse_refresh so the per-minute cron AND the
     * long-lived Rust consumer (POST /api/endorse-refresh/claim) share ONE hardened
     * claim path: stale recovery, daily + per-minute rate caps, atomic single-UPDATE
     * claim, and one 'processing' attempt row per claimed item (= one upstream request,
     * which is what the rate caps count).
     *
     * Returns:
     *   ['status'=>true, 'claimed'=>N, 'worker_id'=>.., 'items'=>[ {fetch-ready item}.. ]]
     *   or, when a cap blocks the run:
     *   ['status'=>true, 'claimed'=>0, 'items'=>[], 'skipped'=>['reason'=>.., 'msg'=>..]]
     *
     * Each item carries everything both the fetch step and applyResults() need, plus the
     * per-item fetch hints (rescue_lane widens the timeout and forces hd for photo posts).
     *
     * @param array $opts limit, force, daily_cap, rate_per_min, stale_minutes
     */
    public function claimBatch(array $opts = []): array
    {
        $this->CI->load->library('template');
        $this->CI->load->library('endorse_sync');

        $limit = intval($opts['limit'] ?? env('ENDORSE_REFRESH_BATCH_SIZE', 40));
        if ($limit <= 0) {
            $limit = 40;
        } elseif ($limit > 500) {
            $limit = 500;
        }

        // Incremental claim (default off). When enabled, never reserve more than one
        // run can actually start (chunk = parallel_http * multiplier), so a big batch
        // cannot strand rows or starve overlapping staggered runs. See effectiveClaimLimit().
        $incremental = array_key_exists('incremental_claim', $opts)
            ? !empty($opts['incremental_claim'])
            : self::incrementalClaimEnabled();
        if ($incremental) {
            $parallelHttp = intval($opts['parallel_http'] ?? env('ENDORSE_REFRESH_PARALLEL_HTTP', 10));
            $multiplier   = intval($opts['claim_chunk_multiplier'] ?? env('ENDORSE_REFRESH_CLAIM_CHUNK_MULTIPLIER', 1));
            $limit = self::effectiveClaimLimit($limit, $parallelHttp, true, $multiplier);
        }

        $force = !empty($opts['force']);
        $staleMinutes = intval($opts['stale_minutes'] ?? 5);
        if ($staleMinutes < 1) {
            $staleMinutes = 5;
        }

        // Recover stale rows from crashed/killed workers FIRST — before the caps below
        // can early-return. Otherwise orphaned 'processing' rows keep the per-minute
        // counter pinned, every run skips, and recovery never runs: the stall sustains
        // itself. Recovery is two cheap UPDATEs, safe to always run.
        //
        // "Safe to always run" holds for the cron, which claims once a minute. It does NOT
        // hold for a continuous worker: resetStuck() scans `processing` under
        // ORDER BY id LIMIT 250 FOR UPDATE SKIP LOCKED, and N replicas claiming every few
        // hundred ms would run it tens of times per second, taking X-locks across rows that
        // are legitimately in flight elsewhere. `recovery_min_interval_sec` throttles that;
        // the non-blocking GET_LOCK additionally ensures only one replica recovers at a time.
        //
        // Default 0.0 keeps the cron path byte-for-byte unchanged.
        $recoveryMinInterval = floatval($opts['recovery_min_interval_sec'] ?? 0.0);
        if ($this->recoveryIsDue($recoveryMinInterval)) {
            $recoveryLock = 'endorse-recovery:'
                . strtolower(trim((string) env('APP_ENV', 'prod')) ?: 'prod') . ':'
                . strtolower(trim((string) env('APP_NAME', 'forbes')) ?: 'forbes');
            $recoveryLockQ = $this->db->escape($recoveryLock);

            // Timeout 0: a replica that loses the race skips recovery this tick rather than
            // queueing behind it. Worst case a stale row waits one interval longer, against
            // a lease measured in minutes.
            $holdsLock = $recoveryMinInterval <= 0.0
                || intval($this->db->query("SELECT GET_LOCK($recoveryLockQ, 0) AS g")->row()->g ?? 0) === 1;

            if ($holdsLock) {
                try {
                    $recovery = $this->resetStuck($staleMinutes);
                    if (empty($recovery['status'])) {
                        return [
                            'status' => false,
                            'claimed' => 0,
                            'items' => [],
                            'worker_id' => '',
                            'error' => 'stale_recovery_failed',
                            'msg' => strval($recovery['msg'] ?? 'Stale recovery failed.'),
                        ];
                    }
                } finally {
                    if ($recoveryMinInterval > 0.0) {
                        $this->db->query("SELECT RELEASE_LOCK($recoveryLockQ)");
                    }
                    $this->lastRecoveryAt = microtime(true);
                }
            }
        }

        $worker_id = uniqid('w_', true);

        // Legacy cron safety cap. Counts consumed post attempts, excluding clean
        // cancellations. V2 fallback additionally reserves every actual RapidAPI start.
        $dailyCap = intval($opts['daily_cap'] ?? env('ENDORSE_REFRESH_DAILY_CAP', 0));
        if (!$force && $dailyCap > 0) {
            $startOfDay = date('Y-m-d') . ' 00:00:00';
            $usedRow = $this->CI->mymodel->selectWithQuery("
                SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts
                WHERE started_at >= '$startOfDay' AND status != 'cancelled'
            ");
            $usedToday = intval($usedRow[0]['c'] ?? 0);
            $remaining = $dailyCap - $usedToday;
            if ($remaining <= 0) {
                return [
                    'status' => true, 'claimed' => 0, 'items' => [], 'worker_id' => $worker_id,
                    'skipped' => [
                        'reason' => 'daily_cap', 'used' => $usedToday, 'cap' => $dailyCap,
                        'msg' => "Daily cap reached ($usedToday/$dailyCap) — skipping run",
                    ],
                ];
            }
            if ($remaining < $limit) {
                $limit = $remaining;
            }
        }

        // Legacy cron post-attempt cap. It is intentionally separate from HTTP
        // concurrency and from the V2 request-start reservation store. 0 = off.
        $ratePerMin = intval($opts['rate_per_min'] ?? env('ENDORSE_REFRESH_RATE_PER_MIN', 0));
        if (!$force && $ratePerMin > 0) {
            $usedRow = $this->CI->mymodel->selectWithQuery("
                SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts
                WHERE started_at >= (NOW() - INTERVAL 60 SECOND) AND status != 'cancelled'
            ");
            $usedMinute = intval($usedRow[0]['c'] ?? 0);
            $remainingMinute = $ratePerMin - $usedMinute;
            if ($remainingMinute <= 0) {
                return [
                    'status' => true, 'claimed' => 0, 'items' => [], 'worker_id' => $worker_id,
                    'skipped' => [
                        'reason' => 'rate_per_min', 'used' => $usedMinute, 'cap' => $ratePerMin,
                        'msg' => "Per-minute rate cap reached ($usedMinute/$ratePerMin) — skipping run",
                    ],
                ];
            }
            if ($remainingMinute < $limit) {
                $limit = $remainingMinute;
            }
        }

        if ($limit <= 0) {
            return ['status' => true, 'claimed' => 0, 'items' => [], 'worker_id' => $worker_id];
        }

        $now = date('Y-m-d H:i:s');

        // `claimed_at` stays NULL for never-attempted rows and deferrals. Failed and
        // stale attempts keep their finish time there so a brief upstream brownout
        // cannot burn every max_attempts slot in a few seconds.
        $retryBaseSeconds = intval($opts['retry_base_seconds'] ?? env('ENDORSE_REFRESH_RETRY_BASE_SEC', 60));
        $retryBaseSeconds = max(1, min(3600, $retryBaseSeconds));

        // Parent claim, attempt allocation and active-attempt identity are one transaction.
        // MAX(attempt_no) self-heals legacy rows whose attempt_sequence was not backfilled.
        $httpTimeoutForLease = max(1, intval(env('ENDORSE_REFRESH_HTTP_TIMEOUT', 30)));
        $leaseSeconds = intval(env('ENDORSE_REFRESH_LEASE_SEC', max(120, ($httpTimeoutForLease * 2) + 30)));
        $leaseSeconds = max(60, min(900, $leaseSeconds));
        $leaseExpiresAtSql = self::schedulingDeadlineSql($leaseSeconds);
        $schedulingNow = self::schedulingNowSql();
        $rows = [];
        $poison = [];
        $candidateIds = [];

        $this->db->trans_begin();
        try {
            $locked = $this->db->query(
                EndorseRefreshClaimRepository::buildSelectForUpdateSql(
                    $limit,
                    $retryBaseSeconds,
                    // Default 0 keeps the cron's claim ordering byte-for-byte unchanged.
                    intval($opts['retry_priority_demotion'] ?? 0)
                )
            );
            $candidates = $this->resultRowsOrThrow($locked, 'select claim candidates');
            $candidateIds = array_map(static function ($row) {
                return intval($row['id']);
            }, $candidates);

            foreach ($candidates as $row) {
                $rows[] = $this->activateClaimRow($row, $worker_id, $schedulingNow, $leaseExpiresAtSql, $now);
            }

            $this->commitOrThrow();
        } catch (\Throwable $e) {
            // One structurally inconsistent row must not cost the healthy rows. Roll the
            // batch back, then retry each candidate in its own transaction so the failure
            // is attributed to the row that actually caused it. Exactly one isolation pass
            // runs per call — no recursion, no unbounded retry.
            $this->db->trans_rollback();
            log_message('error', 'endorse_refresh_claim_batch_failed: ' . $e->getMessage());
            $rows = [];

            $isolation = $this->claimCandidatesIndividually(
                $candidateIds,
                $worker_id,
                $schedulingNow,
                $leaseExpiresAtSql,
                $now,
                $retryBaseSeconds
            );
            $rows = $isolation['rows'];
            $poison = $isolation['poison'];

            if (empty($rows) && empty($poison)) {
                return [
                    'status' => false,
                    'claimed' => 0,
                    'items' => [],
                    'worker_id' => $worker_id,
                    'error' => 'atomic_claim_failed',
                    'msg' => 'Queue claim failed before provider request.',
                ];
            }
        }

        $claimed = count($rows);
        if ($claimed === 0) {
            return [
                'status' => true, 'claimed' => 0, 'items' => [], 'worker_id' => $worker_id,
                'poison' => $poison, 'poison_count' => count($poison),
            ];
        }
        $endorseMeta = [];
        $endorseIds = array_values(array_unique(array_map(function ($row) {
            return intval($row['id_endorse'] ?? 0);
        }, $rows)));
        if (!empty($endorseIds)) {
            $threadsColumn = $this->db->field_exists('threads_media_id', 'endorse') ? ', threads_media_id' : '';
            $metaRows = $this->CI->mymodel->selectWithQuery(
                "SELECT id, influencer{$threadsColumn} FROM endorse WHERE id IN (" . implode(',', $endorseIds) . ")"
            );
            foreach ($metaRows as $meta) $endorseMeta[intval($meta['id'])] = $meta;
        }

        // Prior attempt classes → rescue-lane detection (infra-stall rows get more headroom).
        $priorAttemptMap = [];
        $queueIds = array_map(function ($r) { return intval($r['id']); }, $rows);
        if (!empty($queueIds)) {
            $queueIdList = implode(',', $queueIds);
            $priorAttempts = $this->CI->mymodel->selectWithQuery("
                SELECT queue_id, error_class
                FROM endorse_refresh_queue_attempts
                WHERE queue_id IN ($queueIdList)
                  AND status IN ('retrying', 'failed')
                ORDER BY id DESC
            ");
            foreach ($priorAttempts as $pa) {
                $qid = intval($pa['queue_id'] ?? 0);
                if ($qid > 0 && !array_key_exists($qid, $priorAttemptMap)) {
                    $priorAttemptMap[$qid] = strval($pa['error_class'] ?? '');
                }
            }
        }

        $httpTimeout = intval(env('ENDORSE_REFRESH_HTTP_TIMEOUT', 30));
        if ($httpTimeout < 1) {
            $httpTimeout = 30;
        }

        $items = [];
        foreach ($rows as $r) {
            $qid = intval($r['id']);
            $meta = $endorseMeta[intval($r['id_endorse'])] ?? [];
            $priorClass = strval($priorAttemptMap[$qid] ?? '');
            $isRescue = ($priorClass === Endorse_sync::ERR_INFRA_STALL);
            $url = self::normalizeTiktokUrl(strval($r['link_upload']));
            $hd = ($isRescue && $this->CI->template->detect_tiktok_media_type_from_url($url) === 'photo') ? 1 : 0;
            $items[] = [
                'queue_id'     => $qid,
                'id_endorse'   => intval($r['id_endorse']),
                'platform'     => $r['platform'],
                'url'          => $url,
                'purpose'      => strval($r['purpose'] ?? 'daily'),
                'enqueued_by'  => intval($r['enqueued_by'] ?: 0),
                'attempts'     => intval($r['attempts']),
                'attempt_no'   => intval($r['attempt_sequence']),
                'active_attempt_id' => intval($r['active_attempt_id']),
                'max_attempts' => intval($r['max_attempts']),
                'worker_id'    => $worker_id,
                'rescue_lane'  => $isRescue,
                'timeout_sec'  => $isRescue ? max(45, $httpTimeout + 15) : $httpTimeout,
                'hd'           => $hd,
                'influencer_id' => intval($meta['influencer'] ?? 0),
                'content_id'    => strval($meta['threads_media_id'] ?? ''),
            ];
        }

        return [
            'status' => true, 'claimed' => $claimed, 'items' => $items, 'worker_id' => $worker_id,
            'poison' => $poison, 'poison_count' => count($poison),
        ];
    }

    /**
     * Apply fetch outcomes to claimed items: mark completed/retrying/failed/deferred,
     * finalize the attempt row with the item's REAL error, and roll up touched campaigns.
     *
     * Extracted from Api_v2::cronjob_endorse_refresh (step 5+6). Shared by the cron and by
     * POST /api/endorse-refresh/result. `$responses` is indexed identically to `$items`;
     * each element is a Template::get_social_media-shaped array (or {deferred:true}).
     *
     * Committing per item here (not at end of a batch) is what fixes the "retry never
     * logged" bug: the true error is recorded as it happens, never masked by resetStuck's
     * generic stall label after a 60s guillotine.
     */
    /**
     * Allocate the attempt and activate the parent for ONE candidate. Caller owns the
     * transaction, so this is identical whether it runs inside the batch or the
     * per-row isolation pass — the two paths can never drift.
     */
    private function activateClaimRow(array $row, string $workerId, string $schedulingNow, string $leaseExpiresAtSql, string $now): array
    {
        $queueId = intval($row['id']);
        $attemptNo = max(
            intval($row['attempt_sequence'] ?? 0),
            intval($row['history_attempt_sequence'] ?? 0)
        ) + 1;

        // Attempt start time feeds the per-minute/daily provider caps, which compare
        // against the database clock, so it is stamped by the database too.
        $this->db->set('started_at', $schedulingNow, false);
        $this->db->set('created_at', $schedulingNow, false);
        $this->dbWriteOrThrow($this->db->insert('endorse_refresh_queue_attempts', [
            'queue_id' => $queueId,
            'attempt_no' => $attemptNo,
            'worker_id' => $workerId,
            'status' => 'processing',
        ]), 'insert active attempt');
        $attemptId = intval($this->db->insert_id());
        if ($attemptId <= 0) {
            throw new RuntimeException('insert active attempt returned no id');
        }

        $this->db->set('claimed_at', $schedulingNow, false);
        $this->db->set('started_at', $schedulingNow, false);
        $this->db->set('lease_expires_at', $leaseExpiresAtSql, false);
        $this->dbWriteOrThrow($this->db->update('endorse_refresh_queue', [
            'status' => 'processing',
            'worker_id' => $workerId,
            'claim_owner' => 'cron',
            'attempt_sequence' => $attemptNo,
            'active_attempt_id' => $attemptId,
            'next_attempt_at' => null,
        ], [
            'id' => $queueId,
            'status' => 'pending',
            'worker_id' => null,
        ]), 'activate queue claim', 1);

        $row['status'] = 'processing';
        $row['worker_id'] = $workerId;
        $row['claim_owner'] = 'cron';
        $row['attempt_sequence'] = $attemptNo;
        $row['active_attempt_id'] = $attemptId;
        $row['started_at'] = $now;

        return $row;
    }

    /**
     * Isolation pass: claim each candidate in its own transaction so a poison row is
     * attributed precisely and healthy rows still make progress.
     *
     * @param int[] $candidateIds
     *
     * @return array{rows: array<int,array>, poison: array<int,array>}
     */
    private function claimCandidatesIndividually(
        array $candidateIds,
        string $workerId,
        string $schedulingNow,
        string $leaseExpiresAtSql,
        string $now,
        int $retryBaseSeconds
    ): array {
        $rows = [];
        $poison = [];

        foreach ($candidateIds as $queueId) {
            $this->db->trans_begin();

            try {
                $locked = $this->db->query(
                    EndorseRefreshClaimRepository::buildSelectOneForUpdateSql($queueId, $retryBaseSeconds)
                );
                $candidate = $this->resultRowsOrThrow($locked, 'reselect claim candidate');
                if (empty($candidate)) {
                    // Another worker took it, or it stopped being eligible. Not poison.
                    $this->db->trans_rollback();

                    continue;
                }

                $rows[] = $this->activateClaimRow($candidate[0], $workerId, $schedulingNow, $leaseExpiresAtSql, $now);
                $this->commitOrThrow();
            } catch (\Throwable $e) {
                // Classify BEFORE rolling back: a successful ROLLBACK clears the driver's
                // last-error slot, which would mask the real cause as a generic failure.
                $reason = self::classifyClaimFailure($this->claimFailureCode($e));
                $this->db->trans_rollback();
                $poison[] = ['queue_id' => $queueId, 'reason' => $reason];
                log_message('error', 'endorse_refresh_claim_poison queue=' . $queueId . ' reason=' . $reason . ': ' . $e->getMessage());
                $this->deferPoisonRow($queueId, $reason, $retryBaseSeconds);
            }
        }

        return ['rows' => $rows, 'poison' => $poison];
    }

    /**
     * Isolations a row may accumulate before it is treated as permanently broken.
     * Reached only after the backoff has already grown to its one-hour cap, so a
     * transient cause has had hours to clear before anything becomes terminal.
     */
    public const POISON_MAX_ISOLATIONS = 8;

    /**
     * How many times this row has already been isolated, read back from its own
     * diagnostic. Losing the marker (another writer overwrites error_message) only
     * restarts the count — it can delay termination, never cause it early.
     */
    public static function isolationCount(string $errorMessage): int
    {
        return preg_match('/\bisolations=(\d+)/', $errorMessage, $m) ? intval($m[1]) : 0;
    }

    /**
     * Escalating, capped deferral. A flat window means a permanently broken row costs a
     * failed transaction and a log line on every single poll for the life of the queue;
     * doubling drops that to once an hour within a few cycles.
     */
    public static function isolationBackoffSeconds(int $isolations, int $baseSeconds): int
    {
        $base = max(60, $baseSeconds);
        $shift = max(0, min(16, $isolations - 1));

        return min(3600, $base * (2 ** $shift));
    }

    /**
     * Stable and secret-free: the classification, the count, and — once terminal —
     * an explicit statement that automatic recovery has stopped.
     */
    public static function poisonMessage(string $reason, int $isolations, bool $terminal): string
    {
        return $terminal
            ? 'poison: ' . $reason . ' (isolations=' . $isolations . ', manual requeue required)'
            : 'claim isolation deferred: ' . $reason . ' (isolations=' . $isolations . ')';
    }

    /**
     * Hold a structurally broken row off the queue, escalating each time, and give up on
     * it after POISON_MAX_ISOLATIONS.
     *
     * Deferral alone is not a lifecycle: a bounded backoff still means unbounded retries,
     * because nothing here consumes an attempt (no provider request ever started, so
     * charging one would both corrupt the consumption metric and be a lie). Without a
     * terminal state the row is re-isolated once per window forever and never becomes
     * visible as work that needs a human.
     *
     * Terminal is plain `failed` with a diagnostic, deliberately: it is an existing state
     * every dashboard already surfaces, and cloneFailedRows() is the operator's
     * already-built manual requeue path once the underlying data is reconciled.
     */
    private function deferPoisonRow(int $queueId, string $reason, int $retryBaseSeconds): void
    {
        $this->db->trans_begin();

        try {
            $locked = $this->resultRowsOrThrow($this->db->query(
                'SELECT error_message FROM endorse_refresh_queue WHERE id = ' . intval($queueId)
                . " AND status = 'pending' AND worker_id IS NULL FOR UPDATE",
            ), 'lock poison row');
            if (empty($locked)) {
                // Somebody else already moved it. Not ours to reclassify.
                $this->db->trans_rollback();

                return;
            }

            $isolations = self::isolationCount(strval($locked[0]['error_message'] ?? '')) + 1;
            $terminal = $isolations >= self::POISON_MAX_ISOLATIONS;
            $where = ['id' => $queueId, 'status' => 'pending', 'worker_id' => null];

            if ($terminal) {
                $this->db->set('completed_at', self::schedulingNowSql(), false);
                $this->dbWriteOrThrow($this->db->update('endorse_refresh_queue', [
                    'status' => 'failed',
                    // No future schedule: terminal must mean terminal.
                    'next_attempt_at' => null,
                    'error_message' => self::poisonMessage($reason, $isolations, true),
                ], $where), 'terminate poison row', 1);
                log_message('error', 'endorse_refresh_claim_poison_terminal queue=' . $queueId . ' reason=' . $reason
                    . ' isolations=' . $isolations);
            } else {
                $this->db->set(
                    'next_attempt_at',
                    self::schedulingDeadlineSql(self::isolationBackoffSeconds($isolations, $retryBaseSeconds), 3600),
                    false,
                );
                $this->dbWriteOrThrow($this->db->update('endorse_refresh_queue', [
                    'error_message' => self::poisonMessage($reason, $isolations, false),
                ], $where), 'defer poison row', 1);
            }

            $this->commitOrThrow();
        } catch (\Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'endorse_refresh_claim_defer_failed queue=' . $queueId . ': ' . $e->getMessage());
        }
    }

    /**
     * Stable, secret-free classification of a failed single-row claim. Only genuine
     * database invariant violations are poison; anything else stays generic so a
     * transient infrastructure fault is never mislabelled as a corrupt row.
     */
    public static function classifyClaimFailure(int $mysqlErrorCode): string
    {
        switch ($mysqlErrorCode) {
            case 1062: // ER_DUP_ENTRY
                return 'attempt_identity_conflict';

            case 1452: // ER_NO_REFERENCED_ROW_2
            case 1451: // ER_ROW_IS_REFERENCED_2
                return 'referential_conflict';

            case 1264: // ER_WARN_DATA_OUT_OF_RANGE
            case 1406: // ER_DATA_TOO_LONG
                return 'column_range_conflict';

            default:
                return 'claim_write_failed';
        }
    }

    /**
     * The MySQL error number behind a failed claim. The driver may surface it either on
     * the connection (query returned false) or on a thrown mysqli/PDO exception.
     */
    private function claimFailureCode(\Throwable $e): int
    {
        $code = intval($e->getCode());
        if ($code > 0) {
            return $code;
        }
        if (! method_exists($this->db, 'error')) {
            return 0;
        }
        $error = $this->db->error();

        return is_array($error) ? intval($error['code'] ?? 0) : 0;
    }

    /**
     * Return claimed-but-unstarted items without destroying audit history.
     * A cancelled allocation advances attempt_sequence but not consumed attempts.
     *
     * @param array $items claimBatch items
     */
    public function releaseUnstartedChunk(array $items): int
    {
        $released = 0;
        foreach ($items as $item) {
            $queue_id = intval($item['queue_id'] ?? 0);
            if ($queue_id <= 0) {
                continue;
            }
            $this->db->trans_begin();
            try {
                $claim = $this->lockActiveClaim($item);
                if ($claim === null) {
                    $this->db->trans_rollback();
                    continue;
                }
                $this->finalizeActiveAttemptOrThrow($claim, 'cancelled', 'unstarted', 'Claim released before request start');
                $this->updateActiveQueueOrThrow($claim, [
                    'status' => 'pending',
                    'worker_id' => null,
                    'claim_owner' => null,
                    'active_attempt_id' => null,
                    'started_at' => null,
                    'lease_expires_at' => null,
                    'claimed_at' => null,
                    'next_attempt_at' => null,
                    'error_message' => null,
                ]);
                $this->commitOrThrow();
                $released++;
            } catch (\Throwable $e) {
                $this->db->trans_rollback();
                log_message('error', 'endorse_refresh_release_failed queue=' . $queue_id . ': ' . $e->getMessage());
            }
        }

        return $released;
    }

    public function applyResults(array $items, array $responses, array $opts = []): array
    {
        if (empty($items)) {
            return ['completed' => 0, 'failed' => 0, 'retrying' => 0, 'deferred' => 0, 'processed' => 0, 'touched_campaigns' => []];
        }

        $this->CI->load->library('endorse_sync');

        $today = date('Y-m-d');
        $endorseIds = array_map(function ($it) { return intval($it['id_endorse']); }, $items);
        $endorseIdList = implode(',', array_map('intval', $endorseIds));
        $endorseRows = $this->CI->mymodel->selectWithQuery("
            SELECT * FROM endorse WHERE id IN ($endorseIdList)
        ");
        $endorseMap = [];
        foreach ($endorseRows as $er) {
            $endorseMap[intval($er['id'])] = $er;
        }
        $prevStatsMap = $this->CI->endorse_sync->load_prev_stats_batch($endorseIds, $today);

        $completed = 0; $failed = 0; $retrying = 0; $deferred = 0; $exceptioned = 0; $conflicts = 0;
        $touched = [];

        foreach ($items as $i => $item) {
            $queue_id    = intval($item['queue_id']);
            $id_endorse  = intval($item['id_endorse']);
            $endorse     = $endorseMap[$id_endorse] ?? null;
            $maxAttempts = intval($item['max_attempts']);
            $response    = $responses[$i] ?? ['status' => false, 'msg' => 'No response', 'data' => []];

            // A clean deferral preserves the cancelled allocation for audit but consumes
            // no request attempt and can never release a newer worker's claim.
            if (!empty($response['deferred'])) {
                $released = $this->releaseUnstartedChunk([$item]);
                $deferred += $released;
                $conflicts += $released === 0 ? 1 : 0;
                continue;
            }

            $this->logProviderRequestOutcome($item, $response);

            // Stamp the stable LOGICAL observation order for every path (legacy batch AND
            // incremental runner). The queue-row id is monotonic and constant across all
            // attempts/retries of this refresh generation, so a retry cannot outrank a newer
            // job. This is the freshness authority — not any request-start timestamp.
            $response['observation_seq'] = $queue_id;

            $purpose = strval($item['purpose'] ?? 'daily');

            // ONE consistency boundary per item: the business writes (endorse stats + endorse_logs,
            // inside apply()) AND the queue/attempt state transition commit together or not at all.
            // A crash/exception anywhere rolls the whole item back, leaving the queue row
            // 'processing' → stale recovery re-claims and retries it cleanly (the logical
            // observation sequence is unchanged, so the retry re-applies without regressing data).
            // The campaign rollup is a derived aggregate and stays OUTSIDE the transaction.
            $this->db->trans_begin();
            try {
                $claim = $this->lockActiveClaim($item);
                if ($claim === null) {
                    $this->db->trans_rollback();
                    $conflicts++;
                    continue;
                }
                $attemptNo = intval($claim['attempt_sequence']);
                $attempts = intval($claim['attempts']) + 1;
                $maxAttempts = intval($claim['max_attempts']);

                if (! $endorse) {
                    $this->failActiveClaimOrThrow(
                        $claim,
                        $attempts,
                        'Endorse row no longer exists',
                        Endorse_sync::ERR_PERMANENT
                    );
                    $this->commitOrThrow();
                    $failed++;
                    continue;
                }

                if ($purpose === 'daily') {
                    $result = $this->CI->endorse_sync->apply(
                        $endorse, $response, intval($item['enqueued_by'] ?: 0), $prevStatsMap[$id_endorse] ?? null
                    );
                } else {
                    $result = $this->CI->endorse_sync->apply_snapshot(
                        $endorse, $response, $purpose, intval($item['enqueued_by'] ?: 0)
                    );
                }

                if ($result['status']) {
                    $this->updateActiveQueueOrThrow($claim, [
                        'status' => 'completed', 'attempts' => $attempts, 'error_message' => null,
                        'worker_id' => null, 'claim_owner' => null, 'active_attempt_id' => null,
                        'lease_expires_at' => null, 'started_at' => null, 'next_attempt_at' => null,
                    ], ['completed_at' => self::schedulingNowSql()]);
                    $this->finalizeActiveAttemptOrThrow($claim, 'completed', null, null);
                    $this->commitOrThrow();
                    if ($purpose === 'daily') {
                        $touched[intval($endorse['id_campaign'])] = true;
                    }
                    $completed++;
                    continue;
                }

                $errorClass = $result['error_class'] ?? Endorse_sync::ERR_TRANSIENT;
                $msg = $result['msg'] ?: 'Gagal';

                // Only genuinely unrecoverable classes fail immediately; transport/infra classes
                // retry up to max_attempts (one upstream outage must not drain the queue to failed).
                if (Endorse_sync::is_terminal_class($errorClass)) {
                    $this->failActiveClaimOrThrow($claim, $attempts, $msg, $errorClass);
                    $this->commitOrThrow();
                    $failed++;
                    continue;
                }

                if ($attempts >= $maxAttempts) {
                    $this->failActiveClaimOrThrow($claim, $attempts, "$msg (after $attempts attempts)", $errorClass);
                    $this->commitOrThrow();
                    $failed++;
                } else {
                    $retryAfter = self::retryAfterSeconds($response);
                    $retryBase = intval(env('ENDORSE_REFRESH_RETRY_BASE_SEC', 60));
                    $delay = self::retryDelayWithJitter($queue_id, $attempts, $retryBase, $retryAfter);
                    $this->updateActiveQueueOrThrow($claim, [
                        'status' => 'pending', 'attempts' => $attempts, 'error_message' => $msg,
                        'worker_id' => null, 'claim_owner' => null, 'active_attempt_id' => null,
                        'started_at' => null, 'lease_expires_at' => null,
                    ], [
                        'claimed_at' => self::schedulingNowSql(),
                        'next_attempt_at' => self::schedulingDeadlineSql($delay),
                    ]);
                    $this->finalizeActiveAttemptOrThrow($claim, 'retrying', $errorClass, $msg);
                    $this->commitOrThrow();
                    $retrying++;
                }
            } catch (\Throwable $e) {
                // Crash between side effects (incl. simulated crashes, deadlocks, connection
                // loss): roll back so no partial business state is visible. The row stays
                // 'processing' and is safely recovered later. Never leaves duplicated effects.
                $this->db->trans_rollback();
                $this->log_apply_exception($queue_id, intval($item['attempt_no'] ?? 0), $e);
                $exceptioned++;
            }
        }

        // The campaign rollup is a pure recompute from current state, and it was ALREADY
        // outside the per-item transaction (see the boundary comment above), so deferring it
        // changes timing, never atomicity.
        //
        // Why it can be deferred at all: update_campaign_parent() runs ~11 statements
        // including four unbounded aggregates over `endorse` and `endorse_logs`. Batched over
        // 20 items touching ~5 campaigns that is ~55 statements per run — fine. Called
        // per-item at 400 completions/min it becomes ~4,400 aggregate statements/min, mostly
        // recomputing the same handful of campaigns over and over. That, not the provider,
        // is the next bottleneck at target throughput.
        //
        // A caller that opts in owns the flush: it must union `touched_campaigns` and call
        // update_campaign_parent() on a timer AND unconditionally at shutdown, or the
        // campaign totals silently stop converging.
        if (empty($opts['defer_campaign_rollup'])) {
            foreach (array_keys($touched) as $cid) {
                $this->CI->endorse_sync->update_campaign_parent($cid, 0);
            }
        }

        return [
            'completed' => $completed, 'failed' => $failed, 'retrying' => $retrying,
            'deferred' => $deferred, 'exceptioned' => $exceptioned, 'conflicts' => $conflicts,
            'processed' => count($items),
            'touched_campaigns' => array_values(array_map('intval', array_keys($touched))),
        ];
    }

    /** Commit the per-item transaction, converting a failed transaction into an exception
     *  so the caller's catch rolls it back (no partial business state ever commits). */
    private function commitOrThrow(): void
    {
        if ($this->db->trans_status() === false) {
            throw new RuntimeException('endorse apply transaction failed');
        }
        $this->db->trans_commit();
    }

    private function resultRowsOrThrow($result, string $context): array
    {
        if ($result === false || $this->db->trans_status() === false) {
            throw new RuntimeException($context . ' failed');
        }
        if (! is_object($result) || ! method_exists($result, 'result_array')) {
            throw new RuntimeException($context . ' returned no result set');
        }

        return $result->result_array();
    }

    private function dbWriteOrThrow($result, string $context, ?int $expectedAffected = null): void
    {
        if ($result === false || $this->db->trans_status() === false) {
            $detail = '';
            if (method_exists($this->db, 'error')) {
                $error = $this->db->error();
                $detail = is_array($error) && ! empty($error['message']) ? ': ' . $error['message'] : '';
            }
            throw new RuntimeException($context . ' failed' . $detail);
        }
        if ($expectedAffected !== null && intval($this->db->affected_rows()) !== $expectedAffected) {
            throw new RuntimeException($context . ' affected an unexpected number of rows');
        }
    }

    /**
     * Lock and validate the complete fencing identity before any business write.
     * A missing row is a normal stale-result conflict, not an internal error.
     */
    private function lockActiveClaim(array $item): ?array
    {
        $queueId = intval($item['queue_id'] ?? 0);
        $attemptNo = intval($item['attempt_no'] ?? 0);
        $attemptId = intval($item['active_attempt_id'] ?? 0);
        $workerId = trim((string) ($item['worker_id'] ?? ''));
        if ($queueId <= 0 || $attemptNo <= 0 || $attemptId <= 0 || $workerId === '') {
            return null;
        }

        $result = $this->db->query("
            SELECT q.*, a.id AS locked_attempt_id
            FROM endorse_refresh_queue q
            INNER JOIN endorse_refresh_queue_attempts a
              ON a.id = q.active_attempt_id
             AND a.queue_id = q.id
             AND a.attempt_no = " . intval($attemptNo) . "
             AND a.worker_id = " . $this->db->escape($workerId) . "
             AND a.status = 'processing'
            WHERE q.id = " . intval($queueId) . "
              AND q.status = 'processing'
              AND q.worker_id = " . $this->db->escape($workerId) . "
              AND q.attempt_sequence = " . intval($attemptNo) . "
              AND q.active_attempt_id = " . intval($attemptId) . "
            LIMIT 1
            FOR UPDATE
        ");
        $rows = $this->resultRowsOrThrow($result, 'lock active claim');

        return $rows[0] ?? null;
    }

    /**
     * @param array<string,string> $rawData columns written from a database expression
     *                                      (scheduling timestamps), never from PHP time
     */
    private function updateActiveQueueOrThrow(array $claim, array $data, array $rawData = []): void
    {
        foreach ($rawData as $column => $expression) {
            $this->db->set($column, $expression, false);
        }
        $this->dbWriteOrThrow($this->db->update('endorse_refresh_queue', $data, [
            'id' => intval($claim['id']),
            'status' => 'processing',
            'worker_id' => strval($claim['worker_id']),
            'attempt_sequence' => intval($claim['attempt_sequence']),
            'active_attempt_id' => intval($claim['active_attempt_id']),
        ]), 'fenced queue update', 1);
    }

    private function finalizeActiveAttemptOrThrow(array $claim, string $status, ?string $errorClass, ?string $msg): void
    {
        $this->db->set('finished_at', self::schedulingNowSql(), false);
        $this->dbWriteOrThrow($this->db->update('endorse_refresh_queue_attempts', [
            'status' => $status,
            'error_class' => $errorClass,
            'error_message' => $msg,
        ], [
            'id' => intval($claim['active_attempt_id']),
            'queue_id' => intval($claim['id']),
            'attempt_no' => intval($claim['attempt_sequence']),
            'worker_id' => strval($claim['worker_id']),
            'status' => 'processing',
        ]), 'fenced attempt update', 1);
    }

    private function failActiveClaimOrThrow(array $claim, int $attempts, string $msg, ?string $errorClass): void
    {
        $this->updateActiveQueueOrThrow($claim, [
            'status' => 'failed',
            'attempts' => $attempts,
            'error_message' => $msg,
            'worker_id' => null,
            'claim_owner' => null,
            'active_attempt_id' => null,
            'lease_expires_at' => null,
            'started_at' => null,
            'next_attempt_at' => null,
        ], ['completed_at' => self::schedulingNowSql()]);
        $this->finalizeActiveAttemptOrThrow($claim, 'failed', $errorClass, $msg);
    }

    private function log_apply_exception(int $queue_id, int $attempts, \Throwable $e): void
    {
        error_log(json_encode([
            'evt' => 'endorse_apply_exception',
            'queue_id' => $queue_id,
            'attempt' => $attempts,
            'error' => $e->getMessage(),
        ], JSON_UNESCAPED_SLASHES));
    }

    private function logProviderRequestOutcome(array $item, array $response): void
    {
        $meta = is_array($response['request_meta'] ?? null)
            ? $response['request_meta']
            : (is_array($response['error_meta'] ?? null) ? $response['error_meta'] : []);
        error_log(json_encode([
            'evt' => 'endorse_refresh_request',
            'queue_id' => intval($item['queue_id'] ?? 0),
            'attempt_no' => intval($item['attempt_no'] ?? 0),
            'provider' => strval($meta['provider'] ?? 'unknown'),
            'requests_started' => max(1, intval($meta['requests_started'] ?? 1)),
            'ok' => ! empty($response['status']),
            'http_code' => intval($meta['http_code'] ?? 0),
            'total_time_ms' => (int) round(doubleval($meta['total_time'] ?? 0) * 1000),
            'error_class' => strval($response['error_class'] ?? ''),
            'retry_after' => strval($meta['retry_after'] ?? ''),
            'rate_remaining' => strval($meta['rate_remaining'] ?? ''),
            'request_id' => strval($meta['request_id'] ?? ''),
        ], JSON_UNESCAPED_SLASHES));
    }

    /**
     * Fetch one authoritative processing row through PHP's proven isolated
     * HTTP/1.1 RapidAPI path. The worker supplies only queue_id; URL, platform
     * and credentials stay server-owned.
     */
    public function fetchFallback(int $queueId): array
    {
        $this->CI->load->library('endorse_sync');

        if ($queueId <= 0) {
            return [
                'status' => false,
                'msg' => 'Queue ID tidak valid.',
                'error_class' => Endorse_sync::ERR_PERMANENT,
                'data' => [],
            ];
        }

        $threadsColumn = $this->db->field_exists('threads_media_id', 'endorse') ? ', e.threads_media_id' : '';
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT q.id, q.platform, q.link_upload, e.influencer{$threadsColumn}
            FROM endorse_refresh_queue q
            INNER JOIN endorse e ON e.id = q.id_endorse
            WHERE q.id = '$queueId' AND q.status = 'processing'
            LIMIT 1
        ");
        if (empty($rows)) {
            return [
                'status' => false,
                'msg' => 'Queue item tidak lagi diproses.',
                'error_class' => Endorse_sync::ERR_TRANSIENT,
                'data' => [],
            ];
        }

        $this->CI->load->library('template');
        $row = $rows[0];
        $url = self::normalizeTiktokUrl(strval($row['link_upload'] ?? ''));

        // preferRapidApi=true makes this a single-item isolated HTTP/1.1
        // RapidAPI request first, reusing existing validation and mapping.
        return $this->CI->template->get_social_media(
            strval($row['platform'] ?? ''),
            $url,
            true,
            intval($row['influencer'] ?? 0) ?: null,
            true,
            strval($row['threads_media_id'] ?? '')
        );
    }

    // markQueueFailed()/finalizeQueueAttempt() were removed here. They wrote queue status,
    // attempt status and ownership with no fencing predicate and no affected-row check, so
    // a superseded worker could have overwritten a reassigned or completed row. A repo-wide
    // search found no caller and no subclass of this class; every failure path now goes
    // through failActiveClaimOrThrow(), which validates the full claim identity first.
}
