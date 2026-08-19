<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Continuous endorse-refresh fetch pipeline.
 *
 * WHAT THIS REPLACES, AND WHY
 *
 * The production path (Template::get_social_media_batch) slices claimed rows into chunks of
 * PARALLEL_HTTP, fires the direct scrape for a chunk in parallel via curl_multi, and then
 * walks that chunk in a SEQUENTIAL foreach calling a BLOCKING get_social_media() for every
 * item whose scrape missed. So the parallel phase covers one of up to three legs, and the
 * other two — up to 32s of timeout budget per item — run one after another. Effective
 * concurrency collapses toward 1, one slow item can consume a quarter of the run's 45s
 * budget, and ~70% of claimed rows get deferred and released every tick.
 *
 * Here, every leg of every item is an ordinary slot on ONE persistent curl_multi handle:
 *
 *   - claim only up to free capacity, so a row is never leased that cannot start;
 *   - the loop never calls a blocking curl_exec — a slot is bounded only by its own
 *     CURLOPT_TIMEOUT, so the slowest item delays nothing but itself;
 *   - each completion is applied THE MOMENT its handle finishes, not at a batch barrier;
 *   - a freed slot is refilled on the same iteration.
 *
 * Correctness is not reimplemented. Claiming, fencing, retry/backoff, terminal
 * classification, lease recovery and the per-item transaction all remain in
 * EndorseRefreshQueueService, reached through injected callables — the same collaborator
 * convention EndorseRefreshDrainRunner uses, which also makes this unit-testable with fakes
 * and no database.
 *
 * RATE BUDGET. A token is reserved after the leg is chosen and BEFORE the handle exists, so
 * "one token per started request" holds by construction for every outcome including 429,
 * timeout and malformed body. Both provider scopes participate — the existing wiring only
 * ever reserved for RapidAPI, leaving the direct scrape unmetered.
 */
final class EndorseRefreshPipeline
{
    const LEG_DIRECT = 'direct_scrape';
    const LEG_RAPIDAPI = 'rapidapi';

    const STARTED = 1;
    /** Shared rate budget refused the request: real backpressure, so claim less. */
    const DENIED = 2;
    const UNSUPPORTED = 3;
    /**
     * Local pacing says "not yet". This is NOT backpressure — it is the smooth-rate shaper
     * doing its job between starts, and it fires constantly by design. Conflating it with
     * DENIED collapsed the claim-capacity factor to its floor within seconds and deadlocked
     * the worker entirely (see docs/loadtest/ISSUES.md#issue-15).
     */
    const PACED = 4;

    /** @var array<string, mixed> */
    private array $c;
    /** @var array<string, mixed> */
    private array $cfg;

    private $mh = null;
    /** @var array<int, array<string, mixed>> in-flight slots, keyed by slot id */
    private array $slots = array();
    /** @var list<array{item: array, claimed_at: float}> claimed but not yet started, FIFO */
    private array $ready = array();
    private int $slotSeq = 0;

    private bool $stopping = false;
    private bool $hardStop = false;
    private float $drainDeadline = 0.0;
    private float $startedAt = 0.0;
    private float $nextClaimAt = 0.0;
    private float $nextFillAt = 0.0;

    /**
     * Fraction of nominal capacity we are currently willing to CLAIM for.
     *
     * Slot count says how much work can be in flight; the rate budget says how much work can
     * be STARTED. When concurrency is provisioned above the budget those disagree, and a
     * worker that claims against slots produces exactly the pathology this project set out to
     * remove: rows leased, never started, released, re-claimed. A 15-minute run at 60 slots
     * against a 600/min budget cancelled 8,031 attempts to complete 2,238 — 3.5 wasted
     * transactions per useful one.
     *
     * So claiming tracks the rate budget adaptively. Multiplicative decrease on denial,
     * additive recovery on success — the same shape as the brownout backoff in
     * Template::get_social_media_batch, for the same reason: react fast to pressure, return
     * slowly so it cannot oscillate.
     */
    private float $claimCapacityFactor = 1.0;

    /**
     * Rate-limiter scope keys, resolved once at construction.
     *
     * The RapidAPI scope is a fingerprint of the key, never the key itself, so it is safe to
     * hold and safe to log. Resolving it here rather than per leg start keeps the pipeline
     * free of any global env() lookup, which is both faster and what makes it testable.
     */
    private string $directScope = '';

    private string $rapidApiScope = '';

    /**
     * Per-scope token buckets that PACE request starts.
     *
     * The shared DB limiter counts starts in a rolling 60s window. That bounds the rate
     * correctly but says nothing about its shape: a worker able to burst faster than the
     * sustained rate will spend the whole minute's budget in the first ~20 seconds and then
     * block until the window ages out. Measured over 15 minutes that produced a sawtooth
     * between 109 and 389 completions/min for a 340/min average — the mean met the budget
     * while the worst rolling minute was less than a third of it.
     *
     * A burst is also worse than it looks upstream: the provider sees 400 requests in 20s,
     * not 400 over a minute, and rate-limit enforcement is usually shaped, not averaged.
     *
     * So starts are paced locally: refill at limit/60 per second, capped at a small burst.
     * The DB limiter stays as the distributed hard backstop — this only decides *when* to ask
     * it, so pacing can never admit more than the shared budget allows.
     *
     * @var array<string, array{tokens: float, updated: float}>
     */
    private array $paceBuckets = array();
    private float $lastRollupAt = 0.0;
    private bool $readyReleasedOnStop = false;

    /** @var array<int, bool> campaign ids whose rollup is pending */
    private array $dirtyCampaigns = array();

    /** @var array<string, int> */
    private array $totals = array(
        'claimed' => 0, 'started' => 0, 'completed' => 0, 'failed' => 0, 'retrying' => 0,
        'released' => 0, 'denied' => 0, 'conflicts' => 0, 'exceptioned' => 0,
        'leg1' => 0, 'leg2' => 0, 'watchdog_reaped' => 0, 'shutdown_stalled' => 0, 'paced' => 0,
    );

    public function __construct(array $collaborators, array $config)
    {
        foreach (array('claim', 'release', 'apply', 'reserve', 'rollup', 'ledger', 'kit', 'now', 'log') as $required) {
            if (!isset($collaborators[$required])) {
                throw new InvalidArgumentException('EndorseRefreshPipeline missing collaborator: ' . $required);
            }
        }

        $this->c = $collaborators;
        $this->cfg = $config + array(
            'max_in_flight'      => 10,
            'claim_chunk_max'    => 40,
            'claim_min_batch'    => 1,
            'ready_low_water'    => 5,
            'ready_max_age_sec'  => 2.0,
            // Seconds of paced starts to keep buffered ahead of the slots. Only needs to cover
            // one claim round-trip; larger values reintroduce claim/release churn.
            'ready_lead_sec'     => 1.5,
            'leg_order'          => array(self::LEG_DIRECT, self::LEG_RAPIDAPI),
            'max_legs'           => 2,
            'scrape_timeout_sec' => 20,
            'rapidapi_timeout_sec' => 12,
            'connect_timeout_sec'  => 5,
            'watchdog_grace_sec' => 10.0,
            'idle_sleep_us'      => 10000,
            'claim_backoff_sec'  => 0.25,
            'idle_claim_backoff_sec' => 1.0,
            // How long to stop attempting reservations after one is denied. Long enough that
            // denials cost little, short enough that freed budget is picked up promptly: the
            // rolling window frees capacity continuously, so 50ms recovers within one
            // request's latency while cutting denial traffic by ~95%.
            'denied_backoff_sec' => 0.05,
            // One smooth-rate tick at 10/s. Short: pacing is a schedule, not a penalty.
            'paced_backoff_sec' => 0.02,
            // Never stop claiming entirely: at the floor the worker still claims 10% of
            // nominal capacity, which is enough to keep the pipeline fed when budget frees.
            'claim_capacity_floor' => 0.1,
            'claim_capacity_recovery' => 0.02,
            // Approved local shape: 10 starts/second smooth, burst maximum 20.
            'pace_burst' => 20,
            'worker_replicas' => 1,
            'direct_rate_per_min' => 0,
            'rapidapi_rate_per_min' => 0,
            'rollup_flush_sec'   => 10.0,
            'max_runtime_sec'    => 3600,
            'drain_sec'          => 30.0,
            'token_retention_sec' => 3600,
            'max_body_bytes'     => EndorseRefreshFetchKit::DEFAULT_MAX_BODY_BYTES,
            'mock_base'          => '',
            'worker_id'          => '',
            'test_run_id'        => '',
            'run_id'             => '',
            // Fingerprint of the RapidAPI key, NOT the key. The caller resolves it, so the
            // pipeline never touches the environment.
            'rapidapi_scope'     => '',
        );

        $this->directScope = EndorseRefreshRateScope::scope(EndorseRefreshRateScope::PROVIDER_DIRECT);
        $this->rapidApiScope = (string) $this->cfg['rapidapi_scope'] !== ''
            ? (string) $this->cfg['rapidapi_scope']
            : EndorseRefreshRateScope::scope(EndorseRefreshRateScope::PROVIDER_RAPIDAPI);
    }

    /**
     * Signal-handler entry point. Sets two scalars and nothing else — no DB, no logging, no
     * curl. With pcntl_async_signals(true) this can interrupt between any two opcodes,
     * including mid-query, so anything non-trivial here risks corrupting the state it
     * interrupts. All real shutdown work happens on the next loop iteration.
     */
    public function requestStop(?float $drainSeconds = null): void
    {
        if ($this->stopping) {
            $this->hardStop = true;

            return;
        }

        $this->stopping = true;
        $this->drainDeadline = microtime(true) + ($drainSeconds ?? (float) $this->cfg['drain_sec']);
    }

    public function totals(): array
    {
        return $this->totals + array(
            'in_flight' => count($this->slots),
            'ready' => count($this->ready),
            // Settles near (sustainable start rate / nominal capacity). Far below 1.0 means
            // concurrency is provisioned well above what the rate budget can start.
            'claim_capacity_factor' => round($this->claimCapacityFactor, 3),
        );
    }

    // ---------------------------------------------------------------- main loop

    public function run(): array
    {
        $this->mh = curl_multi_init();
        $this->startedAt = $this->now();
        $this->lastRollupAt = $this->startedAt;

        if (defined('CURLMOPT_MAX_TOTAL_CONNECTIONS')) {
            curl_multi_setopt($this->mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, (int) $this->cfg['max_in_flight'] * 2);
        }

        try {
            while (!$this->stopping || $this->slots !== array()) {
                if (!$this->stopping && $this->runtimeExhausted()) {
                    // Self-recycle rather than leak: a long-lived PHP process accumulates
                    // fragmentation and driver-level state, and the supervisor restarting us
                    // is cheaper than diagnosing that at hour six.
                    $this->log('worker_max_runtime_reached', array('seconds' => (int) ($this->now() - $this->startedAt)));
                    $this->requestStop();
                }

                if ($this->stopping) {
                    $this->releaseReadyOnce();
                    if ($this->hardStop || $this->now() >= $this->drainDeadline) {
                        $this->forceFinishInFlight();
                        break;
                    }
                }

                $this->claimIfCapacity();
                $this->fillSlots();

                $running = 0;
                do {
                    $status = curl_multi_exec($this->mh, $running);
                } while ($status === CURLM_CALL_MULTI_PERFORM);

                $progressed = $this->harvestCompletions();
                $progressed = $this->reapOverdueSlots() || $progressed;

                $this->expireStaleReady();
                $this->maintenance();

                if (!$progressed) {
                    // Block only when there is genuinely nothing to do. curl_multi_select
                    // returns as soon as any socket is readable, so this is a wait on real
                    // I/O, not a poll interval.
                    if ($running > 0) {
                        if (curl_multi_select($this->mh, 0.05) === -1) {
                            usleep((int) $this->cfg['idle_sleep_us']);
                        }
                    } else {
                        usleep((int) $this->cfg['idle_sleep_us']);
                    }
                }
            }
        } finally {
            $this->shutdown();
        }

        return $this->totals();
    }

    private function runtimeExhausted(): bool
    {
        $max = (float) $this->cfg['max_runtime_sec'];

        return $max > 0 && ($this->now() - $this->startedAt) >= $max;
    }

    // ---------------------------------------------------------------- claim + fill

    /**
     * Claim only what can actually be started.
     *
     * The production cron claims BATCH_SIZE regardless of how many HTTP slots exist or how
     * much wall clock is left, which is why ~70% of its claims are deferred and released
     * every tick. Capacity here is in-flight headroom plus a small ready buffer, so the queue
     * row is leased at most a few hundred milliseconds before its request starts.
     */
    private function claimIfCapacity(): void
    {
        if ($this->stopping || $this->now() < $this->nextClaimAt) {
            return;
        }

        $minBatch = max(1, (int) $this->cfg['claim_min_batch']);
        $nominal = (int) $this->cfg['max_in_flight'] + (int) $this->cfg['ready_low_water'];

        // The ready buffer is sized from the START RATE, not from max_in_flight. Those are
        // different quantities and conflating them costs a claim/release cycle per surplus row:
        // sizing to nominal 35 while pacing only permits ~4 starts/second leaves items waiting
        // ~7s in a buffer whose expiry is 2s, so they are claimed, aged out, and released in a
        // loop. Measured at 2x30 with 480+240: 11,668 cancelled attempts against 4,286
        // completions, and MySQL at 237% CPU doing the churn
        // (docs/loadtest/ISSUES.md#issue-17).
        //
        // The buffer only has to cover one claim round-trip, so a lead of a second or two of
        // starts is sufficient to keep a freed slot from ever waiting on the database.
        $readyTarget = max($minBatch, (int) ceil($this->readyDepthTarget() * $this->claimCapacityFactor));

        // Two independent bounds: don't over-fill the buffer, and don't exceed overall nominal
        // capacity. Both must hold; the tighter one wins.
        $capacity = min(
            $readyTarget - count($this->ready),
            $nominal - count($this->slots) - count($this->ready)
        );
        if ($capacity < $minBatch) {
            return;
        }

        $items = ($this->c['claim'])(min($capacity, (int) $this->cfg['claim_chunk_max']));
        if ($items === array()) {
            $this->nextClaimAt = $this->now() + (float) $this->cfg['idle_claim_backoff_sec'];

            return;
        }

        $now = $this->now();
        foreach ($items as $item) {
            $this->ready[] = array('item' => $item, 'claimed_at' => $now);
        }
        $this->totals['claimed'] += count($items);
    }

    /**
     * How many claimed-but-unstarted items to keep buffered.
     *
     * Only the FIRST leg is fed from $ready — a fallback leg is started from a completed
     * leg-1 handle, never from the buffer — so the buffer drains at the first leg's paced
     * rate and must be sized from that rate alone.
     *
     * Returns the unpaced default when no budget is configured for the first leg: with no
     * pacing there is no rate to derive a depth from, and the shared limiter remains
     * authoritative either way.
     */
    private function readyDepthTarget(): int
    {
        $minBatch = max(1, (int) $this->cfg['claim_min_batch']);
        $legOrder = (array) $this->cfg['leg_order'];
        $firstLeg = $legOrder === array() ? self::LEG_DIRECT : (string) reset($legOrder);

        $limitPerMin = $firstLeg === self::LEG_DIRECT
            ? (int) $this->cfg['direct_rate_per_min']
            : (int) $this->cfg['rapidapi_rate_per_min'];

        if ($limitPerMin <= 0) {
            return max($minBatch, (int) $this->cfg['ready_low_water']);
        }

        $replicas = max(1, (int) $this->cfg['worker_replicas']);
        $perSecond = $limitPerMin / 60.0 / $replicas;
        $lead = max(0.1, (float) $this->cfg['ready_lead_sec']);

        // Never below claim_min_batch: capacity is compared against min_batch below, so a
        // smaller target would stop claiming permanently (ISSUES.md#issue-15).
        return max($minBatch, (int) ceil($perSecond * $lead));
    }

    private function fillSlots(): void
    {
        // A denied reservation means the rolling window is full; it will stay full for a
        // measurable period. Retrying on the very next iteration costs a GET_LOCK + COUNT +
        // RELEASE_LOCK every few milliseconds — the first smoke run burned ~3,600 queries on
        // 1,209 denials in 30 seconds, load that competes with the very requests it is
        // waiting for. Back off instead.
        if ($this->now() < $this->nextFillAt) {
            return;
        }

        while (!$this->stopping
            && count($this->slots) < (int) $this->cfg['max_in_flight']
            && $this->ready !== array()) {
            $entry = array_shift($this->ready);
            $legOrder = $this->cfg['leg_order'];
            $outcome = $this->startLeg($entry['item'], $legOrder[0], 1, null);

            if ($outcome === self::PACED) {
                // Put it back at the HEAD and wait for the next token. Deliberately does NOT
                // touch claimCapacityFactor: the item is fine, the queue is fine, and the
                // budget has room — we are simply between ticks of the smooth rate.
                array_unshift($this->ready, $entry);
                $this->nextFillAt = $this->now() + (float) $this->cfg['paced_backoff_sec'];

                return;
            }

            if ($outcome === self::DENIED) {
                // The SHARED budget refused us. That is real backpressure, so claim less.
                array_unshift($this->ready, $entry);
                $this->nextClaimAt = $this->now() + (float) $this->cfg['claim_backoff_sec'];
                $this->nextFillAt = $this->now() + (float) $this->cfg['denied_backoff_sec'];
                $this->claimCapacityFactor = max(
                    (float) $this->cfg['claim_capacity_floor'],
                    $this->claimCapacityFactor / 2.0
                );
                $this->totals['denied']++;

                return;
            }

            if ($outcome === self::UNSUPPORTED) {
                // Not a TikTok row (Threads drains through its own cron, and claimBatch
                // already excludes it). Hand it straight to applyResults so it gets an honest
                // terminal classification instead of silently occupying a slot.
                $this->finish($entry['item'], array(
                    'status' => false,
                    'msg'    => 'Platform belum tersedia',
                    'data'   => array(),
                    'error_class' => Endorse_sync::ERR_PERMANENT,
                ));
            }
        }
    }

    /**
     * Items sitting in $ready longer than ready_max_age_sec are released rather than held.
     *
     * Holding a claim burns lease time on a row nobody is working on AND hides it from every
     * other replica. releaseUnstartedChunk() marks the attempt 'cancelled' and resets the
     * parent to pending WITHOUT consuming an attempt, so this costs the row nothing.
     */
    private function expireStaleReady(): void
    {
        if ($this->ready === array()) {
            return;
        }

        $cutoff = $this->now() - (float) $this->cfg['ready_max_age_sec'];
        $stale = array();
        $keep = array();
        foreach ($this->ready as $entry) {
            if ($entry['claimed_at'] < $cutoff) {
                $stale[] = $entry['item'];
            } else {
                $keep[] = $entry;
            }
        }

        if ($stale === array()) {
            return;
        }

        $this->ready = $keep;
        $this->totals['released'] += (int) ($this->c['release'])($stale);
        $this->log('ready_expired', array('count' => count($stale)));
    }

    // ---------------------------------------------------------------- legs

    /**
     * Reserve budget, build the handle, add it to the multi handle.
     *
     * Order is the contract: the reservation happens after the leg is known and before the
     * handle exists, so a denied reservation costs exactly zero provider requests.
     */
    private function startLeg(array $item, string $leg, int $legNo, ?array $firstFailure): int
    {
        if (strval($item['platform'] ?? '') !== 'Tiktok') {
            return self::UNSUPPORTED;
        }

        // Both scopes are resolved once at construction. Reading env() here would re-read the
        // environment and re-hash the key on every single leg start — thousands of times a
        // minute — and it made the pipeline depend on whichever global env() helper happened
        // to be loaded first, which is exactly how this failed in CI but not locally.
        $scope = $leg === self::LEG_DIRECT ? $this->directScope : $this->rapidApiScope;

        // Pace first, and locally: a paced-out start costs nothing, whereas asking the shared
        // limiter costs a GET_LOCK + COUNT + RELEASE_LOCK round trip whose only possible
        // answer right now is "no".
        if (!$this->takePaceToken($leg, $scope)) {
            return self::PACED;
        }

        $tokenId = ($this->c['reserve'])($leg, $scope, array(
            'run_id'      => $this->cfg['run_id'],
            'queue_id'    => intval($item['queue_id'] ?? 0),
            'attempt_no'  => intval($item['attempt_no'] ?? 0),
            'leg'         => $leg,
            'worker_id'   => $this->cfg['worker_id'],
            'test_run_id' => $this->cfg['test_run_id'],
        ));

        if ($tokenId === null) {
            $this->log('reservation_denied', array('scope' => $scope, 'leg' => $leg, 'in_flight' => count($this->slots)));

            return self::DENIED;
        }

        $url = $this->effectiveUrl(strval($item['url'] ?? ''));
        $buffer = new EndorseRefreshResponseBuffer((int) $this->cfg['max_body_bytes']);
        $bag = new EndorseRefreshHeaderBag();

        if ($leg === self::LEG_DIRECT) {
            // rescue_lane rows carry a longer per-item timeout from claimBatch. In the batch
            // implementation that flag collapsed the whole chunk to size 1; here it is just a
            // longer timeout on one slot and costs the other slots nothing.
            $timeout = intval($item['timeout_sec'] ?? 0) ?: (int) $this->cfg['scrape_timeout_sec'];
            $curl = $this->kit()->newScrapeHandle($url, $timeout, (int) $this->cfg['connect_timeout_sec'], $buffer);
        } else {
            $timeout = (int) $this->cfg['rapidapi_timeout_sec'];
            $curl = $this->kit()->newRapidApiHandle($url, intval($item['hd'] ?? 0), $timeout, (int) $this->cfg['connect_timeout_sec'], $buffer, $bag);
        }

        $id = ++$this->slotSeq;
        curl_setopt($curl, CURLOPT_PRIVATE, (string) $id);
        curl_multi_add_handle($this->mh, $curl);

        $this->slots[$id] = array(
            'item' => $item, 'leg' => $leg, 'leg_no' => $legNo, 'ch' => $curl,
            'token_id' => $tokenId, 'scope' => $scope, 'buffer' => $buffer, 'headers' => $bag,
            'started_at' => $this->now(),
            'deadline' => $this->now() + $timeout + (float) $this->cfg['watchdog_grace_sec'],
            'requests_started' => $legNo,
            'first_failure' => $firstFailure,
            'url' => $url,
        );

        $this->totals['started']++;
        $this->totals[$legNo === 1 ? 'leg1' : 'leg2']++;

        // Additive recovery: budget freed up, so claim slightly more next time. Deliberately
        // much slower than the halving above so the factor settles near the sustainable rate
        // instead of oscillating between over- and under-claiming.
        $this->claimCapacityFactor = min(1.0, $this->claimCapacityFactor + (float) $this->cfg['claim_capacity_recovery']);

        return self::STARTED;
    }

    /**
     * Mock redirection lives HERE, in worker-only code, rather than in Template — so even a
     * misconfigured ENDORSE_REFRESH_MOCK_BASE cannot send production cron traffic anywhere
     * unexpected. Primary mock seam is still the seeded data; this covers shadow runs over
     * real production URLs.
     */
    private function effectiveUrl(string $url): string
    {
        $base = trim((string) $this->cfg['mock_base']);
        if ($base === '' || $url === '') {
            return $url;
        }

        return (string) preg_replace('#^https?://[^/]+#', rtrim($base, '/'), $url);
    }

    /**
     * Token bucket for one provider scope. Returns false when a start must wait.
     *
     * Refills continuously at limit/60 per second and is capped at `pace_burst`, so the
     * long-run rate equals the configured budget while the instantaneous rate cannot exceed
     * the burst. Approved shape: 10 starts/second smooth, burst 20.
     */
    private function takePaceToken(string $leg, string $scope): bool
    {
        $limitPerMin = $leg === self::LEG_DIRECT
            ? (int) $this->cfg['direct_rate_per_min']
            : (int) $this->cfg['rapidapi_rate_per_min'];

        // No configured budget for this scope: pacing has nothing to shape, and the shared
        // limiter remains authoritative (it fail-closes on a non-positive limit).
        if ($limitPerMin <= 0) {
            return true;
        }

        $now = $this->now();

        // Divide the budget by the replica count. The bucket is per PROCESS, but the budget is
        // per FLEET: two replicas each pacing at the full rate emit twice the intended smooth
        // rate, and the shared limiter then has to absorb the excess as a burst — which is the
        // exact shape pacing exists to remove. Measured with 2 replicas: 846 starts/min against
        // a 400/min budget before this divisor.
        //
        // Over-stating the replica count is safe (paces slower than necessary); under-stating
        // it degrades to the pre-pacing burst, with the shared limiter still enforcing the cap.
        $replicas = max(1, (int) $this->cfg['worker_replicas']);
        $burst = max(1.0, (float) $this->cfg['pace_burst'] / $replicas);
        $perSecond = $limitPerMin / 60.0 / $replicas;

        if (!isset($this->paceBuckets[$scope])) {
            // Start full: the first burst after an idle period is legitimate, and starting
            // empty would add a needless cold-start delay to every run.
            $this->paceBuckets[$scope] = array('tokens' => $burst, 'updated' => $now);
        }

        $bucket = $this->paceBuckets[$scope];
        $bucket['tokens'] = min($burst, $bucket['tokens'] + ($now - $bucket['updated']) * $perSecond);
        $bucket['updated'] = $now;

        if ($bucket['tokens'] < 1.0) {
            $this->paceBuckets[$scope] = $bucket;
            $this->totals['paced']++;

            return false;
        }

        $bucket['tokens'] -= 1.0;
        $this->paceBuckets[$scope] = $bucket;

        return true;
    }

    private function harvestCompletions(): bool
    {
        $progressed = false;
        while (($info = curl_multi_info_read($this->mh)) !== false) {
            $this->completeHandle($info['handle'], intval($info['result'] ?? 0));
            $progressed = true;
        }

        return $progressed;
    }

    private function completeHandle($curl, int $curlResult): void
    {
        $id = intval(curl_getinfo($curl, CURLINFO_PRIVATE));
        if (!isset($this->slots[$id])) {
            curl_multi_remove_handle($this->mh, $curl);
            curl_close($curl);

            return;
        }

        $slot = $this->slots[$id];
        unset($this->slots[$id]);

        $meta = $this->transportMeta($curl, $curlResult);
        curl_multi_remove_handle($this->mh, $curl);
        curl_close($curl);   // free the socket immediately; the pool is the scarce resource

        if ($slot['leg'] === self::LEG_DIRECT) {
            $this->completeDirectLeg($slot, $meta);

            return;
        }

        $this->completeRapidApiLeg($slot, $meta);
    }

    private function completeDirectLeg(array $slot, array $meta): void
    {
        $item = $this->kit()->parseScrapeHtml($slot['buffer']->body());
        $httpOk = intval($meta['http_code']) === 200 && intval($meta['curl_errno']) === 0;
        $usable = $httpOk && $item !== array() && $this->kit()->scrapeItemUsable($item);

        if ($usable) {
            $response = $this->kit()->scrapeToResponse($slot['url'], $item);
            $response['request_meta'] = array(
                'provider' => self::LEG_DIRECT,
                'requests_started' => $slot['requests_started'],
                'http_code' => intval($meta['http_code']),
                'total_time' => doubleval($meta['total_time']),
            );
            $this->ledgerRecord($slot, true, $meta, null, null);
            $this->finish($slot['item'], $response);

            return;
        }

        $failure = $this->directFailureResponse($slot, $meta, $item);
        $this->ledgerRecord($slot, false, $meta, strval($failure['error_class'] ?? ''), null);

        // Escalate to the next leg if the budget allows. Note the escalation is a NEW SLOT on
        // the same multi handle — not a blocking call, which is the entire point.
        if ($slot['leg_no'] < (int) $this->cfg['max_legs']) {
            $next = $this->cfg['leg_order'][1] ?? null;
            if ($next !== null && $this->startLeg($slot['item'], $next, $slot['leg_no'] + 1, $failure) === self::STARTED) {
                return;
            }
            // Leg 2 denied by the rate budget. Leg 1 already consumed an attempt, so the row
            // cannot be cleanly released — apply the honest leg-1 failure and let the queue's
            // backoff schedule the retry.
        }

        $this->finish($slot['item'], $failure);
    }

    private function completeRapidApiLeg(array $slot, array $meta): void
    {
        $configProblem = $this->kit()->rapidApiConfigProblem();
        if ($configProblem !== null) {
            $this->ledgerRecord($slot, false, $meta, strval($configProblem['error_class'] ?? 'config'), null);
            $this->finish($slot['item'], $configProblem);

            return;
        }

        $meta = array_merge($meta, $this->headerMeta($slot['headers']->headers));
        $response = $this->kit()->rapidApiToResponse($slot['url'], $slot['buffer']->body(), $meta);
        $ok = !empty($response['status']);

        $response['request_meta'] = array_merge(array(
            'provider' => $slot['leg_no'] > 1 ? self::LEG_DIRECT . '+' . self::LEG_RAPIDAPI : self::LEG_RAPIDAPI,
            'requests_started' => $slot['requests_started'],
        ), $meta);

        $this->ledgerRecord(
            $slot,
            $ok,
            $meta,
            $ok ? null : strval($response['error_class'] ?? ''),
            EndorseRefreshQueueService::retryAfterSeconds($response)
        );

        $this->finish($slot['item'], $response);
    }

    /**
     * Build a Template-shaped failure for a missed direct scrape, classified the same way
     * production classifies transport errors so infra_stall/infra_connect stay meaningful.
     */
    private function directFailureResponse(array $slot, array $meta, array $parsed): array
    {
        $errno = intval($meta['curl_errno']);
        $http = intval($meta['http_code']);

        if ($errno !== 0) {
            $class = $this->kit()->classifyTransport($meta);
            $msg = 'Direct scrape transport failure (errno ' . $errno . ')';
        } elseif ($http >= 400 && $http < 500) {
            // 404/403 on the page itself: the post is gone or gated. Not terminal on its own —
            // the fallback still gets a say, and Endorse_sync owns terminal classification.
            $class = Endorse_sync::ERR_TRANSIENT;
            $msg = 'Direct scrape HTTP ' . $http;
        } elseif ($http >= 500) {
            $class = Endorse_sync::ERR_TRANSIENT;
            $msg = 'Direct scrape HTTP ' . $http;
        } elseif ($parsed === array()) {
            $class = Endorse_sync::ERR_TRANSIENT;
            $msg = 'Stats data tidak ditemukan pada halaman';
        } else {
            $class = Endorse_sync::ERR_EMPTY;
            $msg = 'Stats data tidak ditemukan';
        }

        return array(
            'status' => false,
            'msg' => $msg,
            'data' => array(),
            'error_class' => $class,
            'error_meta' => $meta,
            'request_meta' => array(
                'provider' => self::LEG_DIRECT,
                'requests_started' => $slot['requests_started'],
                'http_code' => $http,
                'total_time' => doubleval($meta['total_time']),
            ),
        );
    }

    /** Same shape Template::executeRapidApiGet passes to finalizeRapidApiJsonResponse. */
    private function transportMeta($curl, int $curlResult): array
    {
        $errno = $curlResult !== 0 ? $curlResult : intval(curl_errno($curl));

        return array(
            'http_code'         => intval(curl_getinfo($curl, CURLINFO_HTTP_CODE)),
            'curl_errno'        => $errno,
            'curl_error'        => (string) curl_error($curl),
            'total_time'        => doubleval(curl_getinfo($curl, CURLINFO_TOTAL_TIME)),
            'time_namelookup'   => doubleval(curl_getinfo($curl, CURLINFO_NAMELOOKUP_TIME)),
            'time_connect'      => doubleval(curl_getinfo($curl, CURLINFO_CONNECT_TIME)),
            'time_appconnect'   => doubleval(curl_getinfo($curl, CURLINFO_APPCONNECT_TIME)),
            'time_starttransfer' => doubleval(curl_getinfo($curl, CURLINFO_STARTTRANSFER_TIME)),
        );
    }

    /** Provider quota / Retry-After headers, normalised the way Template does. */
    private function headerMeta(array $headers): array
    {
        $pick = function (string $name) use ($headers) {
            foreach ($headers as $k => $v) {
                if (strcasecmp((string) $k, $name) === 0) {
                    return is_array($v) ? reset($v) : $v;
                }
            }

            return null;
        };

        $meta = array();
        foreach (array(
            'retry_after' => 'retry-after',
            'rate_limit' => 'x-ratelimit-requests-limit',
            'rate_remaining' => 'x-ratelimit-requests-remaining',
            'rate_reset' => 'x-ratelimit-requests-reset',
            'request_id' => 'x-rapidapi-request-id',
        ) as $key => $header) {
            $value = $pick($header);
            if ($value !== null && $value !== '') {
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

    // ---------------------------------------------------------------- watchdog + apply

    /**
     * Close slots libcurl never reported.
     *
     * CURLOPT_TIMEOUT normally guarantees a completion, but DNS pathologies and a handful of
     * libcurl edge cases can leave a handle parked. Without this a single stuck slot would
     * permanently reduce concurrency, and the run would report a throughput drop with no
     * visible cause.
     */
    private function reapOverdueSlots(): bool
    {
        if ($this->slots === array()) {
            return false;
        }

        $now = $this->now();
        $reaped = false;
        foreach ($this->slots as $id => $slot) {
            if ($now < $slot['deadline']) {
                continue;
            }

            unset($this->slots[$id]);
            $meta = $this->transportMeta($slot['ch'], 28);
            curl_multi_remove_handle($this->mh, $slot['ch']);
            curl_close($slot['ch']);

            $this->ledgerRecord($slot, false, $meta, Endorse_sync::ERR_INFRA_STALL, null);
            $this->totals['watchdog_reaped']++;
            $this->log('slot_watchdog_reaped', array('queue_id' => intval($slot['item']['queue_id'] ?? 0), 'leg' => $slot['leg']));

            $this->finish($slot['item'], array(
                'status' => false,
                'msg' => 'Provider request exceeded its watchdog deadline',
                'data' => array(),
                'error_class' => Endorse_sync::ERR_INFRA_STALL,
                'error_meta' => $meta,
            ));
            $reaped = true;
        }

        return $reaped;
    }

    /**
     * The single place a completed request changes queue state.
     *
     * applyResults() is called with exactly one item, which it already supports (the
     * incremental branch does the same). Its per-item transaction, lockActiveClaim() identity
     * fencing and stats_observation_seq ordering guard all apply unchanged — this pipeline
     * gets the same correctness the cron has, because it is literally the same code.
     */
    private function finish(array $item, array $response): void
    {
        try {
            $summary = ($this->c['apply'])($item, $response);
        } catch (Throwable $e) {
            $this->totals['exceptioned']++;
            $this->log('apply_failed', array('queue_id' => intval($item['queue_id'] ?? 0), 'error' => $e->getMessage()));

            return;
        }

        $this->totals['completed'] += intval($summary['completed'] ?? 0);
        $this->totals['failed'] += intval($summary['failed'] ?? 0);
        $this->totals['retrying'] += intval($summary['retrying'] ?? 0);
        $this->totals['conflicts'] += intval($summary['conflicts'] ?? 0);
        $this->totals['exceptioned'] += intval($summary['exceptioned'] ?? 0);

        foreach ((array) ($summary['touched_campaigns'] ?? array()) as $cid) {
            $this->dirtyCampaigns[intval($cid)] = true;
        }
    }

    private function ledgerRecord(array $slot, bool $ok, array $meta, ?string $errorClass, ?int $retryAfter): void
    {
        $this->ledger()->record(intval($slot['token_id']), array(
            'ok'              => $ok ? 1 : 0,
            'http_code'       => intval($meta['http_code'] ?? 0),
            'curl_errno'      => intval($meta['curl_errno'] ?? 0),
            'total_time_ms'   => (int) round(doubleval($meta['total_time'] ?? 0) * 1000),
            'error_class'     => $errorClass !== null && $errorClass !== '' ? $errorClass : null,
            'retry_after_sec' => $retryAfter !== null && $retryAfter > 0 ? $retryAfter : null,
        ));
    }

    // ---------------------------------------------------------------- maintenance + shutdown

    private function maintenance(): void
    {
        $this->ledger()->flush();

        if ((float) $this->cfg['rollup_flush_sec'] > 0
            && ($this->now() - $this->lastRollupAt) >= (float) $this->cfg['rollup_flush_sec']) {
            $this->flushRollups();
        }

        $this->ledger()->prune((int) $this->cfg['token_retention_sec']);
    }

    /**
     * Recompute campaign parents for everything touched since the last flush.
     *
     * applyResults() is called with defer_campaign_rollup, because per-item it would run ~11
     * statements with four unbounded aggregates for EVERY completion. Deduplicating to one
     * recompute per campaign per interval turns ~400/min into ~30/min and converges to the
     * same values, since the rollup is a pure recompute rather than an increment.
     */
    private function flushRollups(): void
    {
        $this->lastRollupAt = $this->now();
        if ($this->dirtyCampaigns === array()) {
            return;
        }

        $ids = array_keys($this->dirtyCampaigns);
        $this->dirtyCampaigns = array();

        try {
            ($this->c['rollup'])($ids);
        } catch (Throwable $e) {
            // Re-dirty so the next flush (or shutdown) retries: dropping these would leave
            // campaign totals permanently stale.
            foreach ($ids as $cid) {
                $this->dirtyCampaigns[$cid] = true;
            }
            $this->log('rollup_failed', array('count' => count($ids), 'error' => $e->getMessage()));
        }
    }

    /** Release every claimed-but-unstarted row, once, as soon as shutdown begins. */
    private function releaseReadyOnce(): void
    {
        if ($this->readyReleasedOnStop || $this->ready === array()) {
            $this->readyReleasedOnStop = true;

            return;
        }

        $items = array_column($this->ready, 'item');
        $this->ready = array();
        $this->readyReleasedOnStop = true;
        $this->totals['released'] += (int) ($this->c['release'])($items);
        $this->log('shutdown_released_ready', array('count' => count($items)));
    }

    /**
     * Deadline reached with requests still in flight.
     *
     * These DID start a provider request, so the attempt must be consumed — releasing them
     * would claim no request was made, which is false and would let a row exceed its real
     * retry budget. Applying an honest infra_stall now (rather than abandoning them to lease
     * expiry) records the true cause and returns the row to pending under normal backoff
     * instead of waiting out a 120s lease.
     */
    private function forceFinishInFlight(): void
    {
        foreach ($this->slots as $id => $slot) {
            unset($this->slots[$id]);
            $elapsed = $this->now() - $slot['started_at'];

            @curl_multi_remove_handle($this->mh, $slot['ch']);
            @curl_close($slot['ch']);

            $meta = array('http_code' => 0, 'curl_errno' => 28, 'curl_error' => 'worker shutdown', 'total_time' => $elapsed);
            $this->ledgerRecord($slot, false, $meta, Endorse_sync::ERR_INFRA_STALL, null);
            $this->totals['shutdown_stalled']++;

            if ($this->hardStop) {
                // Second signal: do not touch the database. These rows fall to lease recovery,
                // which fences them correctly via resetStuck().
                continue;
            }

            $this->finish($slot['item'], array(
                'status' => false,
                'msg' => 'Worker shutdown during provider request',
                'data' => array(),
                'error_class' => Endorse_sync::ERR_INFRA_STALL,
                'error_meta' => $meta,
            ));
        }
    }

    private function shutdown(): void
    {
        $this->releaseReadyOnce();
        $this->forceFinishInFlight();

        $this->ledger()->flush(true);
        $this->flushRollups();

        if ($this->mh !== null) {
            @curl_multi_close($this->mh);
            $this->mh = null;
        }
    }

    // ---------------------------------------------------------------- helpers

    private function kit(): EndorseRefreshFetchKit
    {
        return $this->c['kit'];
    }

    private function ledger(): EndorseRefreshLedger
    {
        return $this->c['ledger'];
    }

    private function now(): float
    {
        return (float) ($this->c['now'])();
    }

    private function log(string $event, array $fields = array()): void
    {
        ($this->c['log'])($event, $fields);
    }
}
