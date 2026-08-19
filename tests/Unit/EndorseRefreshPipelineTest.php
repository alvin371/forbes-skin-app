<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
if (! defined('APPPATH')) {
    define('APPPATH', __DIR__ . '/../../application/');
}
if (! defined('FCPATH')) {
    define('FCPATH', __DIR__ . '/../../');
}

require_once __DIR__ . '/../../application/helpers/env_helper.php';
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshLedger.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshFetchKit.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshPipeline.php';

/**
 * Pipeline behaviour under a rate budget, with no network and no database.
 *
 * The trick that makes this possible: when the injected `reserve` collaborator always denies,
 * NO curl handle is ever created — reservation happens before handle construction, by design.
 * So the entire claim -> deny -> back off -> release path runs in-process.
 *
 * What these pin is the lesson from a real 15-minute run: at 60 in-flight slots against a
 * 600/min budget the worker cancelled 22,122 attempts to complete 5,382 — 4.1 wasted
 * transactions per useful one, and completions oscillating between 109/min and 389/min.
 * Slot count says how much can be IN FLIGHT; the rate budget says how much can be STARTED.
 * A worker that claims against the former reproduces exactly the over-claiming defect this
 * project set out to remove from the cron.
 *
 * @internal
 */
final class EndorseRefreshPipelineTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $log = [];

    private function collaborators(array $overrides = []): array
    {
        $claimSeq = 0;

        $base = [
            'claim' => function (int $limit) use (&$claimSeq): array {
                $this->log['claim_limits'][] = $limit;
                $items                       = [];

                for ($i = 0; $i < $limit; $i++) {
                    $items[] = [
                        'queue_id'     => ++$claimSeq, 'id_endorse' => $claimSeq,
                        'platform'     => 'Tiktok', 'url' => 'https://tiktok-mock.local/@u/video/700000000000000' . $claimSeq,
                        'attempt_no'   => 1, 'active_attempt_id' => $claimSeq, 'attempts' => 0,
                        'max_attempts' => 3, 'worker_id' => 'w1', 'purpose' => 'daily',
                        'timeout_sec'  => 20, 'hd' => 0,
                    ];
                }

                return $items;
            },
            'release' => function (array $items): int {
                $this->log['released'][] = count($items);

                return count($items);
            },
            'apply' => function (array $item, array $response): array {
                $this->log['applied'][] = [$item['queue_id'], $response['error_class'] ?? null];

                return ['completed' => 0, 'failed' => 1, 'retrying' => 0, 'touched_campaigns' => []];
            },
            // Always deny: no handle is ever built, so the whole loop runs without a socket.
            'reserve' => function (string $leg, string $scope, array $ctx) {
                $this->log['reserve_attempts'][] = $scope;

                return null;
            },
            'rollup' => function (array $ids): void {
                $this->log['rollups'][] = $ids;
            },
            'ledger' => new EndorseRefreshLedger(new PipelineNullDb(), 1000, 3600.0),
            'kit'    => new EndorseRefreshFetchKit(),
            'now'    => static fn (): float => microtime(true),
            'log'    => function (string $event, array $fields = []): void {
                $this->log['events'][] = $event;
            },
        ];

        return array_merge($base, $overrides);
    }

    private function config(array $overrides = []): array
    {
        return array_merge([
            'max_in_flight'       => 20,
            'ready_low_water'     => 5,
            'claim_chunk_max'     => 40,
            'claim_min_batch'     => 1,
            'ready_max_age_sec'   => 0.05,
            'max_runtime_sec'     => 1,
            'drain_sec'           => 1.0,
            'idle_sleep_us'       => 1000,
            'rollup_flush_sec'    => 3600.0,
            'token_retention_sec' => 3600,
            'worker_id'           => 'test',
            'run_id'              => 'testrun',
            'test_run_id'         => 'testrun',
        ], $overrides);
    }

    protected function setUp(): void
    {
        $this->log = [];
    }

    /**
     * The load-bearing test. Under sustained denial the claim capacity must collapse toward
     * the floor, so the worker stops leasing rows it cannot start.
     */
    public function testSustainedDenialCollapsesClaimCapacity(): void
    {
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config());
        $totals   = $pipeline->run();

        $this->assertGreaterThan(0, $totals['denied'], 'the run must actually have been rate-denied');
        $this->assertLessThan(
            1.0,
            $totals['claim_capacity_factor'],
            'claim capacity must fall when reservations are denied',
        );
        $this->assertLessThanOrEqual(0.25, $totals['claim_capacity_factor'], 'halving must reach the low end quickly');
        $this->assertGreaterThanOrEqual(0.1, $totals['claim_capacity_factor'], 'it must never fall below the floor');
    }

    /**
     * Claim sizes must SHRINK as capacity collapses. A worker that keeps asking for full
     * batches while being denied is the over-claiming defect, whatever it does afterwards.
     */
    public function testClaimSizesShrinkUnderDenial(): void
    {
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config());
        $pipeline->run();

        $limits = $this->log['claim_limits'] ?? [];
        $this->assertNotEmpty($limits, 'the pipeline must have attempted to claim');
        $this->assertLessThan(
            $limits[0],
            end($limits),
            'later claims must be smaller than the first once capacity has collapsed',
        );
    }

    /**
     * A denied item must go back to the queue, not be dropped and not be counted as failed.
     * releaseUnstartedChunk() marks the attempt cancelled WITHOUT consuming a retry, so this
     * costs the row nothing — which is what makes backing off safe.
     */
    public function testDeniedItemsAreReleasedRatherThanLostOrFailed(): void
    {
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config());
        $totals   = $pipeline->run();

        $this->assertGreaterThan(0, array_sum($this->log['released'] ?? []), 'unstarted claims must be released');
        $this->assertSame(0, $totals['completed']);
        $this->assertContains('reservation_denied', $this->log['events'] ?? []);
    }

    /**
     * Every claimed row must end up either started, released, or still held — never dropped.
     */
    public function testNoClaimedRowIsSilentlyDropped(): void
    {
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config());
        $totals   = $pipeline->run();

        $accounted = $totals['started'] + $totals['released'] + $totals['ready'] + $totals['in_flight'];
        $this->assertSame(
            $totals['claimed'],
            $accounted,
            'claimed must equal started + released + still-ready + in-flight',
        );
    }

    /**
     * A non-TikTok row cannot be fetched by either leg. It must be handed to apply() with a
     * terminal classification rather than occupying a slot or being silently skipped —
     * otherwise it would be reclaimed forever.
     */
    public function testUnsupportedPlatformGetsATerminalClassificationImmediately(): void
    {
        $collaborators = $this->collaborators([
            'claim' => static function (int $limit): array {
                static $done = false;
                if ($done) {
                    return [];
                }
                $done = true;

                return [[
                    'queue_id'          => 99, 'id_endorse' => 99, 'platform' => 'Instagram',
                    'url'               => 'https://instagram.com/p/abc', 'attempt_no' => 1,
                    'active_attempt_id' => 99, 'attempts' => 0, 'max_attempts' => 3,
                    'worker_id'         => 'w1', 'purpose' => 'daily', 'timeout_sec' => 20, 'hd' => 0,
                ]];
            },
        ]);

        $pipeline = new EndorseRefreshPipeline($collaborators, $this->config());
        $pipeline->run();

        $applied = $this->log['applied'] ?? [];
        $this->assertNotEmpty($applied, 'an unsupported platform must still reach applyResults');
        $this->assertSame(99, $applied[0][0]);
        $this->assertSame(Endorse_sync::ERR_PERMANENT, $applied[0][1], 'it must be terminal, not retried forever');
        $this->assertSame([], $this->log['reserve_attempts'] ?? [], 'it must not spend rate budget');
    }

    /**
     * Regression for the deadlock in ISSUES.md#issue-15.
     *
     * The claim target is `ceil(nominal * factor)`. With nominal 35 and the factor collapsed
     * to its 0.1 floor that is 4 — below a claim_min_batch of 5 — so claimIfCapacity()
     * returned early on every iteration and the worker never claimed again. Two workers sat
     * idle for minutes against 12,000 pending rows.
     *
     * Starting the factor already at the floor reproduces the exact state.
     */
    public function testWorkerKeepsClaimingWhenCapacityCollapsesBelowMinBatch(): void
    {
        $pipeline = new EndorseRefreshPipeline(
            $this->collaborators(),
            $this->config(['max_in_flight' => 30, 'ready_low_water' => 5, 'claim_min_batch' => 5]),
        );

        (new ReflectionProperty(EndorseRefreshPipeline::class, 'claimCapacityFactor'))
            ->setValue($pipeline, 0.1);

        $totals = $pipeline->run();

        $this->assertGreaterThan(
            0,
            $totals['claimed'],
            'a collapsed capacity factor must never stop claiming entirely',
        );

        foreach ($this->log['claim_limits'] ?? [] as $limit) {
            $this->assertGreaterThanOrEqual(5, $limit, 'every claim must be at least claim_min_batch');
        }
    }

    /**
     * Pacing is a schedule, not backpressure. It fires constantly by design, so treating it
     * as a denial collapses the claim factor within seconds — which is precisely how the
     * deadlock above was reached.
     */
    public function testPacingDoesNotReduceClaimCapacity(): void
    {
        // A limit of 60/min = 1 start/second with burst 1: the bucket empties immediately and
        // every subsequent start is paced out, never budget-denied.
        $collaborators = $this->collaborators([
            'reserve' => function (string $leg, string $scope, array $ctx) {
                $this->log['reserve_attempts'][] = $scope;

                return 12345;   // the shared budget always says yes
            },
        ]);

        $pipeline = new EndorseRefreshPipeline($collaborators, $this->config([
            'direct_rate_per_min' => 60,
            'pace_burst'          => 1,
            'max_runtime_sec'     => 1,
        ]));
        $totals = $pipeline->run();

        $this->assertGreaterThan(0, $totals['paced'], 'the pacer must have throttled starts');
        $this->assertSame(0, $totals['denied'], 'pacing must never be counted as a budget denial');
        $this->assertSame(
            1.0,
            $totals['claim_capacity_factor'],
            'pacing must leave claim capacity untouched',
        );
    }

    /**
     * The ready buffer is sized from the paced START RATE, not from max_in_flight. Sizing it
     * from concurrency claims rows far faster than pacing can start them; they age out and are
     * released, costing a claim/release round-trip each (ISSUES.md#issue-17).
     */
    public function testReadyBufferIsSizedFromRateNotConcurrency(): void
    {
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config([
            'max_in_flight'       => 30,
            'ready_low_water'     => 5,
            'claim_min_batch'     => 1,
            'direct_rate_per_min' => 240,   // 4 starts/second at 1 replica
            'worker_replicas'     => 1,
            'ready_lead_sec'      => 1.5,   // -> ceil(4 * 1.5) = 6, NOT 35
        ]));

        $depth = (new ReflectionMethod(EndorseRefreshPipeline::class, 'readyDepthTarget'))
            ->invoke($pipeline);

        $this->assertSame(6, $depth, 'buffer depth must follow the first leg rate x lead');
    }

    public function testReadyBufferHalvesWithReplicasAndFollowsLegOrder(): void
    {
        // Same fleet budget spread over two replicas: each buffers half as deep.
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config([
            'claim_min_batch'     => 1,
            'direct_rate_per_min' => 240,
            'worker_replicas'     => 2,
            'ready_lead_sec'      => 1.5,
        ]));
        $this->assertSame(3, (new ReflectionMethod(EndorseRefreshPipeline::class, 'readyDepthTarget'))
            ->invoke($pipeline));

        // With rapidapi first, the RapidAPI budget is what drains the buffer — reading the
        // direct budget here would size it from a rate that never applies.
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config([
            'claim_min_batch'       => 1,
            'leg_order'             => [EndorseRefreshPipeline::LEG_RAPIDAPI, EndorseRefreshPipeline::LEG_DIRECT],
            'direct_rate_per_min'   => 6000,
            'rapidapi_rate_per_min' => 120,   // 2/second
            'worker_replicas'       => 1,
            'ready_lead_sec'        => 2.0,
        ]));
        $this->assertSame(4, (new ReflectionMethod(EndorseRefreshPipeline::class, 'readyDepthTarget'))
            ->invoke($pipeline));
    }

    public function testUnpacedWorkerFallsBackToTheLowWaterDepth(): void
    {
        // No configured budget: there is no rate to derive a depth from.
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config([
            'claim_min_batch'     => 1,
            'direct_rate_per_min' => 0,
            'ready_low_water'     => 5,
        ]));

        $this->assertSame(5, (new ReflectionMethod(EndorseRefreshPipeline::class, 'readyDepthTarget'))
            ->invoke($pipeline));
    }

    public function testReadyDepthNeverFallsBelowMinBatch(): void
    {
        // A very slow rate would compute a depth of 1; min_batch must still win, or capacity
        // can never reach the claim threshold and the worker deadlocks (ISSUES.md#issue-15).
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config([
            'claim_min_batch'     => 5,
            'direct_rate_per_min' => 6,     // 0.1/second
            'worker_replicas'     => 1,
            'ready_lead_sec'      => 1.5,
        ]));

        $this->assertSame(5, (new ReflectionMethod(EndorseRefreshPipeline::class, 'readyDepthTarget'))
            ->invoke($pipeline));
    }

    /**
     * The pipeline must resolve its limiter scopes from injected config, never from a global
     * env() helper.
     *
     * This is not hypothetical tidiness. Calling env() inside startLeg() made the class depend
     * on whichever env() implementation happened to be autoloaded first: locally an earlier
     * test defined a plain one and this passed, while in CI the Laravel helper won and blew up
     * with "Class PhpOption\Option not found" — a green local suite and a red pipeline for the
     * same commit. It also re-read the environment and re-hashed the key on every leg start.
     */
    public function testScopesComeFromConfigAndNeverFromTheEnvironment(): void
    {
        // Token-based, so a mention of env() in a comment or docblock cannot trip it and,
        // more importantly, cannot hide a real call either.
        $tokens = token_get_all(file_get_contents(APPPATH . 'libraries/EndorseRefreshPipeline.php'));
        $calls  = 0;

        foreach ($tokens as $i => $token) {
            if (! is_array($token) || $token[0] !== T_STRING || $token[1] !== 'env') {
                continue;
            }

            // Skip method calls / static access such as $x->env(...) or Foo::env(...).
            $prev = $tokens[$i - 1] ?? null;
            if (is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
                continue;
            }

            for ($j = $i + 1; $j < count($tokens); $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                    continue;
                }
                if ($tokens[$j] === '(') {
                    $calls++;
                }

                break;
            }
        }

        $this->assertSame(
            0,
            $calls,
            'EndorseRefreshPipeline must not call the global env() helper; the worker injects config',
        );
    }

    public function testInjectedRapidApiScopeIsUsedForReservations(): void
    {
        $collaborators = $this->collaborators([
            'reserve' => function (string $leg, string $scope, array $ctx) {
                $this->log['scopes'][] = $scope;

                return null;
            },
        ]);

        $pipeline = new EndorseRefreshPipeline($collaborators, $this->config([
            'rapidapi_scope' => 'rapidapi:deadbeef',
            'leg_order'      => [EndorseRefreshPipeline::LEG_RAPIDAPI, EndorseRefreshPipeline::LEG_DIRECT],
        ]));
        $pipeline->run();

        $this->assertNotEmpty($this->log['scopes'] ?? [], 'the pipeline must have attempted a reservation');
        $this->assertContains('rapidapi:deadbeef', $this->log['scopes'], 'the injected scope must be the one reserved against');
    }

    public function testMissingCollaboratorIsRejectedAtConstruction(): void
    {
        $collaborators = $this->collaborators();
        unset($collaborators['reserve']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reserve');
        new EndorseRefreshPipeline($collaborators, $this->config());
    }

    /**
     * The signal handler must only flip scalars — safe to call at any point.
     */
    public function testRequestStopIsSafeBeforeRunAndIsIdempotent(): void
    {
        $pipeline = new EndorseRefreshPipeline($this->collaborators(), $this->config());

        $pipeline->requestStop(1.0);
        $pipeline->requestStop(1.0);   // second signal = hard stop

        $totals = $pipeline->run();
        $this->assertSame(0, $totals['claimed'], 'a stopped pipeline must never claim');
        $this->assertSame(0, $totals['started']);
    }
}

/**
 * Swallows the ledger's writes. The pipeline records outcomes unconditionally, and these
 * tests are about claim/deny/release behaviour, not persistence.
 *
 * @internal
 */
final class PipelineNullDb
{
    public function query(string $sql)
    {
        return true;
    }

    public function affected_rows(): int
    {
        return 0;
    }

    public function escape($value): string
    {
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }
}
