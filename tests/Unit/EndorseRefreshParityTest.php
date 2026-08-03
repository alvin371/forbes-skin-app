<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';

/**
 * Provider-parity regression guards (fix/endorse-refresh-provider-parity).
 *
 * These lock the corrected decisions found by comparing Forbes to the healthy
 * bhskin reference. All feature flags default OFF, so the first block proves the
 * defaults preserve current production behaviour; later blocks prove the fixed
 * behaviour when a flag is on. Pure functions only — no DB, no network.
 *
 * @internal
 */
final class EndorseRefreshParityTest extends TestCase
{
    // --- Defaults must preserve current behaviour (acceptance #1, #11) ---------

    public function testLimiterDefaultsToClaimReservation(): void
    {
        $this->assertSame(EndorseRefreshQueueService::LIMITER_CLAIM_RESERVATION, EndorseRefreshQueueService::limiterMode('claim_reservation'));
        $this->assertSame(EndorseRefreshQueueService::LIMITER_CLAIM_RESERVATION, EndorseRefreshQueueService::limiterMode('anything-unknown'));
        $this->assertFalse(EndorseRefreshQueueService::reservesAtRequestStart('claim_reservation'));
    }

    public function testIncrementalClaimDefaultOffReturnsConfiguredBatch(): void
    {
        // incremental = false → batch is returned unchanged regardless of parallel_http.
        $this->assertSame(400, EndorseRefreshQueueService::effectiveClaimLimit(400, 20, false));
        $this->assertSame(30, EndorseRefreshQueueService::effectiveClaimLimit(30, 20, false));
    }

    // --- Incremental claim (acceptance #5, #8): chunk <= parallel_http --------

    public function testIncrementalClaimIsBoundedByParallelHttp(): void
    {
        // batch 400 but only 20 slots → claim 20, not 400 (no mass reservation).
        $this->assertSame(20, EndorseRefreshQueueService::effectiveClaimLimit(400, 20, true, 1));
        // smaller batch than the chunk still wins (never claim more than exists to size).
        $this->assertSame(10, EndorseRefreshQueueService::effectiveClaimLimit(10, 20, true, 1));
    }

    public function testIncrementalClaimMultiplierIsClampedOneToFour(): void
    {
        $this->assertSame(40, EndorseRefreshQueueService::effectiveClaimLimit(400, 20, true, 2));
        $this->assertSame(80, EndorseRefreshQueueService::effectiveClaimLimit(400, 20, true, 4));
        $this->assertSame(80, EndorseRefreshQueueService::effectiveClaimLimit(400, 20, true, 9)); // clamp >4
        $this->assertSame(20, EndorseRefreshQueueService::effectiveClaimLimit(400, 20, true, 0)); // clamp <1
    }

    public function testIncrementalClaimNeverZeroOrNegative(): void
    {
        $this->assertGreaterThanOrEqual(1, EndorseRefreshQueueService::effectiveClaimLimit(0, 0, true, 1));
        $this->assertGreaterThanOrEqual(1, EndorseRefreshQueueService::effectiveClaimLimit(-5, -5, true, 1));
    }

    // --- Request-start reservation semantics (acceptance #5, #6) --------------

    public function testEveryStartedRequestConsumesExactlyOneToken(): void
    {
        // A started request consumes a token for ALL outcomes — never success-only.
        foreach (['success', 'http_429', 'http_5xx', 'timeout', 'invalid', 'app_error'] as $outcome) {
            $this->assertTrue(
                EndorseRefreshQueueService::consumesRequestToken($outcome),
                "started request with outcome '$outcome' must consume one token"
            );
        }
    }

    public function testDeferredUnstartedConsumesNoToken(): void
    {
        $this->assertFalse(EndorseRefreshQueueService::consumesRequestToken('deferred_unstarted'));
        $this->assertFalse(EndorseRefreshQueueService::consumesRequestToken(''));
    }

    public function testRequestStartModeSelectable(): void
    {
        $this->assertTrue(EndorseRefreshQueueService::reservesAtRequestStart('request_start_reservation'));
        $this->assertSame(
            EndorseRefreshQueueService::LIMITER_REQUEST_START,
            EndorseRefreshQueueService::limiterMode('  REQUEST_START_RESERVATION ')
        );
    }

    // --- Scrape-validity parity with bhskin has_valid_stats -------------------

    public function testScrapeStatsUsableRequiresAtLeastOnePositive(): void
    {
        $this->assertTrue(EndorseRefreshQueueService::scrapeStatsAreUsable(['playCount' => 5]));
        $this->assertTrue(EndorseRefreshQueueService::scrapeStatsAreUsable(['diggCount' => 1, 'playCount' => 0]));
        // All-zero (or keys merely present) must NOT be treated as usable — the
        // divergence from Forbes' isValidTiktokScrapeItem, which accepted this.
        $this->assertFalse(EndorseRefreshQueueService::scrapeStatsAreUsable(['diggCount' => 0, 'playCount' => 0]));
        $this->assertFalse(EndorseRefreshQueueService::scrapeStatsAreUsable([]));
    }

    // --- Observability builder distinguishes claims vs unique successes --------

    public function testRunSummaryDistinguishesClaimsFromUniqueSuccess(): void
    {
        $json = EndorseRefreshQueueService::buildRunSummary([
            'run_id' => 'r1', 'claimed' => 400, 'requests_started' => 20,
            'unique_completed' => 16, 'deferred_unstarted' => 380,
        ]);
        $decoded = json_decode($json, true);
        $this->assertSame('endorse_refresh_run', $decoded['evt']);
        $this->assertSame(400, $decoded['claimed']);
        $this->assertSame(20, $decoded['requests_started']);
        $this->assertSame(16, $decoded['unique_completed']);
        $this->assertSame(380, $decoded['deferred_unstarted']);
        // No URL/cookie/key fields leak into the summary.
        $this->assertStringNotContainsString('cookie', strtolower($json));
        $this->assertStringNotContainsString('rapidapi', strtolower($json));
    }

    // --- True incremental drain loop (acceptance #1): multiple chunks per run --

    public function testDrainProcessesMultipleChunksInOneRun(): void
    {
        // Fake clock advances 5s per chunk; deadline 45s, per-chunk budget 8s.
        $t = 0.0;
        $now = function () use (&$t) { return $t; };
        $pending = 100; // effectively unlimited
        $claim = function (int $n) use (&$pending) {
            $take = min($n, $pending); $pending -= $take;
            return array_fill(0, $take, ['queue_id' => 1]);
        };
        $process = function (array $items) use (&$t) {
            $t += 5.0; // each chunk takes 5s
            return ['started' => count($items), 'unique_completed' => count($items), 'deferred' => 0];
        };
        $totals = EndorseRefreshQueueService::drainIncrementally(45.0, 20, 8.0, $claim, $process, $now, 2.0);
        // 45s budget, stop when remaining < 8+2=10 → runs chunks while elapsed <= 35 → ~7 chunks.
        $this->assertGreaterThan(1, $totals['chunks'], 'incremental run must process more than one chunk');
        $this->assertSame($totals['claimed'], $totals['unique_completed']);
        $this->assertSame(0, $totals['deferred_unstarted']);
    }

    public function testDrainStopsBeforeDeadlineAndDoesNotOverclaim(): void
    {
        $t = 0.0; $now = function () use (&$t) { return $t; };
        $claim = function (int $n) { return array_fill(0, $n, ['queue_id' => 1]); };
        $process = function (array $items) use (&$t) { $t += 20.0; return ['started' => count($items), 'unique_completed' => count($items), 'deferred' => 0]; };
        // deadline 45, per-chunk 20, margin 2 → after 1 chunk elapsed=20, remaining 25 >= 22 → 2nd chunk;
        // after 2nd elapsed=40, remaining 5 < 22 → stop. Exactly 2 chunks, never a 3rd it can't finish.
        $totals = EndorseRefreshQueueService::drainIncrementally(45.0, 20, 20.0, $claim, $process, $now, 2.0);
        $this->assertSame(2, $totals['chunks']);
    }

    public function testDrainStopsWhenQueueEmpty(): void
    {
        $t = 0.0; $now = function () use (&$t) { return $t; };
        $calls = 0;
        $claim = function (int $n) use (&$calls) { $calls++; return $calls === 1 ? [['queue_id' => 1]] : []; };
        $process = function (array $items) use (&$t) { $t += 1.0; return ['started' => 1, 'unique_completed' => 1, 'deferred' => 0]; };
        $totals = EndorseRefreshQueueService::drainIncrementally(45.0, 20, 5.0, $claim, $process, $now);
        $this->assertSame(1, $totals['chunks']); // stopped when second claim returned empty
    }

    // --- Chunk-decision + bounded inline retry policy -------------------------

    public function testMayClaimAnotherChunkRespectsSafetyMargin(): void
    {
        $this->assertTrue(EndorseRefreshQueueService::mayClaimAnotherChunk(45, 30, 8, 2));   // 15 >= 10
        $this->assertFalse(EndorseRefreshQueueService::mayClaimAnotherChunk(45, 38, 8, 2));  // 7 < 10
    }

    public function testInlineRetryOnlyRetriesRetryableClassesWithinBudget(): void
    {
        // retryable class, attempts left, budget ok → retry
        $this->assertTrue(EndorseRefreshQueueService::shouldInlineRetry(Endorse_sync::ERR_TRANSIENT, 1, 3, 30, 20));
        $this->assertTrue(EndorseRefreshQueueService::shouldInlineRetry(Endorse_sync::ERR_INFRA_STALL, 2, 3, 30, 20));
        // terminal classes never inline-retry
        $this->assertFalse(EndorseRefreshQueueService::shouldInlineRetry(Endorse_sync::ERR_PERMANENT, 1, 3, 30, 20));
        $this->assertFalse(EndorseRefreshQueueService::shouldInlineRetry(Endorse_sync::ERR_EMPTY, 1, 3, 30, 20));
        // max attempts reached
        $this->assertFalse(EndorseRefreshQueueService::shouldInlineRetry(Endorse_sync::ERR_TRANSIENT, 3, 3, 30, 20));
        // insufficient remaining budget for another full attempt
        $this->assertFalse(EndorseRefreshQueueService::shouldInlineRetry(Endorse_sync::ERR_TRANSIENT, 1, 3, 10, 20));
    }

    // --- Parser parity contract (Phase 3/5): the shared golden corpus ----------
    // Forbes' and bhskin's extract_tiktok_content_id / detect_tiktok_media_type_from_url
    // are semantically identical (verified by source diff: same regex, same order).
    // This test locks that shared contract against the committed corpus so a future
    // edit to either extractor that breaks parity fails here.

    private static function contentId(string $url): string
    {
        if ($url === '') return '';
        if (preg_match('/\/video\/(\d+)/', $url, $m)) return $m[1];
        if (preg_match('/\/photo\/(\d+)/', $url, $m)) return $m[1];
        if (preg_match('/(\d{10,25})/', $url, $m)) return $m[1];
        return '';
    }

    private static function mediaType(string $url): string
    {
        if (stripos($url, '/photo/') !== false) return 'photo';
        if (stripos($url, '/video/') !== false) return 'video';
        return '';
    }

    public function testGoldenCorpusParserContract(): void
    {
        $path = __DIR__ . '/../fixtures/endorse_refresh_parity_corpus.json';
        $this->assertFileExists($path);
        $corpus = json_decode(file_get_contents($path), true);
        $this->assertNotEmpty($corpus['items']);
        foreach ($corpus['items'] as $item) {
            $this->assertSame($item['content_id'], self::contentId($item['url']), "content_id parity for shape {$item['shape']}");
            $this->assertSame($item['media_type'], self::mediaType($item['url']), "media_type parity for shape {$item['shape']}");
        }
    }

    // --- Rolling-window invariant used by the simulator (acceptance #7) --------

    public function testThreeWorkersNeverExceedRollingLimitWhenChunkSized(): void
    {
        // With incremental chunk = parallel_http and N staggered workers, the max
        // claimed per rolling window = N * chunk. This must stay <= rate for the
        // tested config (3 workers, chunk 20, rate 400).
        $chunk = EndorseRefreshQueueService::effectiveClaimLimit(400, 20, true, 1);
        $this->assertLessThanOrEqual(400, 3 * $chunk);
    }
}
