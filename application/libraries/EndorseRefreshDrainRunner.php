<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/EndorseRefreshQueueService.php';
require_once __DIR__ . '/EndorseRefreshRateLimiter.php';

/**
 * Orchestrates ONE endorse-refresh run as the healthy reference does in spirit — bounded,
 * observable, and correct — but under the queue architecture:
 *
 *   loop: claim a slot-sized chunk → for each item reserve-then-request (with bounded inline
 *   retry, each attempt = one scoped token) → apply → recompute deadline → next chunk.
 *
 * Every collaborator is injected (claim/reserve/fetch/apply/release/log/clock/sleep) so the
 * whole lifecycle — multi-chunk draining, one-token-per-request-and-retry, rate-exhaustion
 * stop without reclaim churn, deadline stop, clean release, and exception safety — is proven
 * end-to-end against real MySQL with a deterministic fake provider. Production wires the real
 * claimBatch/reservation-store/Template/applyResults into the same callables.
 *
 * Feature-flag gated by the caller; this class has no global side effects of its own.
 */
final class EndorseRefreshDrainRunner
{
    /** @var array<string,callable|object|float|int|string|bool> */
    private array $c;

    public function __construct(array $collaborators)
    {
        $this->c = $collaborators;
    }

    private function cfg(string $k, $default = null)
    {
        return $this->c[$k] ?? $default;
    }

    private function now(): float
    {
        return ($this->c['now'])();
    }

    private function log(string $type, array $data): void
    {
        if (isset($this->c['log']) && is_callable($this->c['log'])) {
            ($this->c['log'])($type, $data);
        }
    }

    /**
     * @return array run totals + stop_reason.
     */
    public function run(): array
    {
        $runId          = (string) $this->cfg('run_id', substr(md5(uniqid('', true)), 0, 32));
        $deadline       = (float) $this->cfg('deadline_sec', 45.0);
        $chunkSize      = (int) $this->cfg('chunk_size', 20);
        $perReqTimeout  = (float) $this->cfg('per_request_timeout_sec', 8.0);
        $applyMargin    = (float) $this->cfg('apply_margin_sec', 2.0);
        $safetyMargin   = (float) $this->cfg('safety_margin_sec', 2.0);
        $maxAttempts    = (int) $this->cfg('max_attempts', 3);
        $backoffBase    = (float) $this->cfg('backoff_base_sec', 0.5);
        $inlineRetry    = (bool) $this->cfg('inline_retry', false);
        $requestStart   = EndorseRefreshQueueService::limiterMode((string) $this->cfg('limiter_mode', 'claim_reservation')) === EndorseRefreshQueueService::LIMITER_REQUEST_START;

        $claim   = $this->c['claim'];
        $fetch   = $this->c['fetch'];
        $apply   = $this->c['apply'];
        $reserve = $this->c['reserve']  ?? null;   // fn(scope):bool
        $release = $this->c['release']  ?? null;   // fn(items):void
        $sleep   = $this->c['sleep']    ?? function ($s) {};

        $start   = $this->now();
        $t = [
            'run_id' => $runId, 'chunks' => 0, 'claimed' => 0, 'requests_started' => 0,
            'retries' => 0, 'unique_completed' => 0, 'deferred_unstarted' => 0,
            'reservation_denied' => 0, 'transient' => 0, 'terminal' => 0,
            'stop_reason' => 'queue_empty',
        ];

        // Per-chunk worst-case wall used for the next-chunk decision: one request + its retry
        // budget (backoff + timeout per extra attempt) + the apply/logging margin.
        $perChunkWall = $perReqTimeout
            + ($inlineRetry ? ($maxAttempts - 1) * ($backoffBase + $perReqTimeout) : 0.0)
            + $applyMargin;

        while (true) {
            $elapsed = $this->now() - $start;
            if (!EndorseRefreshQueueService::mayClaimAnotherChunk($deadline, $elapsed, $perChunkWall, $safetyMargin)) {
                $t['stop_reason'] = 'deadline';
                break;
            }

            $items = $claim(max(1, $chunkSize));
            $items = is_array($items) ? array_values($items) : [];
            if (count($items) === 0) {
                $t['stop_reason'] = 'queue_empty';
                break;
            }
            $t['chunks']++;
            $t['claimed'] += count($items);
            $chunkNo = $t['chunks'];

            $startedThisChunk = 0;
            $unstarted = [];

            foreach ($items as $item) {
                // Budget guard before any outbound request for this item.
                if (($this->now() - $start) + $perReqTimeout + $applyMargin > $deadline) {
                    $unstarted[] = $item;
                    $t['deferred_unstarted']++;
                    continue;
                }

                $outcome = $this->runItem(
                    $runId, $chunkNo, $item, $requestStart, $reserve, $fetch, $sleep,
                    $inlineRetry, $maxAttempts, $backoffBase, $perReqTimeout, $applyMargin,
                    $deadline, $start, $t
                );

                if ($outcome['status'] === 'reservation_denied' && $outcome['started'] === 0) {
                    // Could not start even the first request → provider budget exhausted.
                    $unstarted[] = $item;
                    $t['reservation_denied']++;
                    continue;
                }

                $startedThisChunk += $outcome['started'];
                // Apply the final result for this item (state transition + logs).
                try {
                    $apply($item, $outcome['response']);
                    if ($outcome['final'] === 'completed') {
                        $t['unique_completed']++;
                    } elseif ($outcome['final'] === 'terminal') {
                        $t['terminal']++;
                    } else {
                        $t['transient']++;
                    }
                } catch (\Throwable $e) {
                    // Never silently lose a claimed row: release it and record.
                    $unstarted[] = $item;
                    $this->log('apply_error', ['run_id' => $runId, 'queue_id' => $item['queue_id'] ?? null, 'err' => $e->getMessage()]);
                }
            }

            // Return any unstarted/failed-to-apply rows to pending immediately (no waiting
            // for stale recovery), and do not reclaim them within this same run.
            if (!empty($unstarted) && is_callable($release)) {
                $release($unstarted);
            }

            $this->log('chunk', [
                'run_id' => $runId, 'chunk_no' => $chunkNo, 'claimed' => count($items),
                'started' => $startedThisChunk, 'deferred' => count($unstarted),
            ]);

            // Rate exhaustion: a whole chunk started zero requests purely because the limiter
            // denied every reservation → stop cleanly, do NOT loop to reclaim the same items.
            if ($requestStart && $startedThisChunk === 0 && $t['reservation_denied'] > 0) {
                $t['stop_reason'] = 'rate_limit_exhausted';
                break;
            }
        }

        $t['wall_ms'] = (int) round(($this->now() - $start) * 1000);
        $this->log('run', $t);
        return $t;
    }

    /**
     * Drive one item: reserve-then-request with bounded inline retry. Each attempt is a
     * distinct outbound request and consumes exactly one scoped token.
     */
    private function runItem(
        string $runId,
        int $chunkNo,
        array $item,
        bool $requestStart,
        $reserve,
        callable $fetch,
        callable $sleep,
        bool $inlineRetry,
        int $maxAttempts,
        float $backoffBase,
        float $perReqTimeout,
        float $applyMargin,
        float $deadline,
        float $start,
        array &$t
    ): array {
        $scope = (string) ($item['scope'] ?? EndorseRefreshRateScope::PROVIDER_RAPIDAPI);
        $started = 0;
        $attempt = 0;
        $response = ['ok' => false, 'error_class' => Endorse_sync::ERR_TRANSIENT];
        $final = 'transient';

        while (true) {
            $remaining = $deadline - ($this->now() - $start);
            if ($remaining < $perReqTimeout + $applyMargin) {
                break; // not enough budget for another full attempt
            }
            if ($requestStart && is_callable($reserve)) {
                if (!$reserve($scope)) {
                    return ['status' => 'reservation_denied', 'started' => $started, 'response' => $response, 'final' => $final];
                }
            }
            $attempt++;
            $started++;
            $t['requests_started']++;
            if ($attempt > 1) {
                $t['retries']++;
            }
            $reqStart = $this->now();
            $response = $fetch($item, $attempt);
            $this->log('request', [
                'run_id' => $runId, 'chunk_no' => $chunkNo, 'queue_id' => $item['queue_id'] ?? null,
                'scope' => $scope, 'attempt' => $attempt, 'fetch_path' => $response['path'] ?? 'unknown',
                'http' => $response['http'] ?? null, 'started_at' => $reqStart, 'finished_at' => $this->now(),
                'outcome' => !empty($response['ok']) ? 'ok' : ($response['error_class'] ?? 'error'),
            ]);

            if (!empty($response['ok'])) {
                $final = 'completed';
                break;
            }
            $class = (string) ($response['error_class'] ?? Endorse_sync::ERR_TRANSIENT);
            if (Endorse_sync::is_terminal_class($class)) {
                $final = 'terminal';
                break;
            }
            $remainingAfter = $deadline - ($this->now() - $start);
            if (!$inlineRetry || !EndorseRefreshQueueService::shouldInlineRetry($class, $attempt, $maxAttempts, $remainingAfter, $perReqTimeout + $backoffBase)) {
                $final = 'transient';
                break;
            }
            $sleep($backoffBase * (2 ** ($attempt - 1)));
        }

        return ['status' => 'started', 'started' => $started, 'response' => $response, 'final' => $final];
    }
}
