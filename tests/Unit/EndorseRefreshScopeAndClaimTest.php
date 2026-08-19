<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';

/**
 * Pure guards for the scoped limiter identity and the shared claim SQL contract.
 *
 * @internal
 */
final class EndorseRefreshScopeAndClaimTest extends TestCase
{
    // --- provider scope isolation ---------------------------------------------

    public function testDirectScrapeAndRapidApiAreSeparateScopes(): void
    {
        $this->assertSame('direct_scrape', EndorseRefreshRateScope::scope('direct_scrape'));
        $this->assertNotSame(
            EndorseRefreshRateScope::scope('direct_scrape'),
            EndorseRefreshRateScope::scope('rapidapi', 'k'),
        );
    }

    public function testSeparateKeysYieldSeparateScopes(): void
    {
        $a = EndorseRefreshRateScope::scope('rapidapi', 'forbes-key');
        $b = EndorseRefreshRateScope::scope('rapidapi', 'sec-forbes-key');
        $this->assertNotSame($a, $b, 'Forbes and Sec-Forbes must not share a scope');
    }

    public function testFingerprintNeverLeaksTheKey(): void
    {
        $key = 'super-secret-rapidapi-key-abcdef';
        $fp  = EndorseRefreshRateScope::fingerprint($key);
        $this->assertSame(8, strlen($fp));
        $this->assertStringNotContainsString('secret', $fp);
        $this->assertStringNotContainsString($key, EndorseRefreshRateScope::scope('rapidapi', $key));
    }

    // --- lock namespace safety ------------------------------------------------

    public function testLockNameIsNamespacedNotGlobal(): void
    {
        $lock = EndorseRefreshRateScope::lockNameFor('prod', 'forbes', EndorseRefreshRateScope::scope('rapidapi', 'k1'));
        $this->assertStringStartsWith('endorse-rate:prod:forbes:', $lock);
        $this->assertNotSame('erq_rate', $lock);
    }

    public function testLockNameDiffersPerAppAndEnv(): void
    {
        $scope = EndorseRefreshRateScope::scope('rapidapi', 'k');
        $this->assertNotSame(
            EndorseRefreshRateScope::lockNameFor('prod', 'forbes', $scope),
            EndorseRefreshRateScope::lockNameFor('prod', 'sec-forbes', $scope),
        );
        $this->assertNotSame(
            EndorseRefreshRateScope::lockNameFor('prod', 'forbes', $scope),
            EndorseRefreshRateScope::lockNameFor('staging', 'forbes', $scope),
        );
    }

    public function testLockNameStaysWithinMysqlLimit(): void
    {
        $scope = EndorseRefreshRateScope::scope('rapidapi', str_repeat('x', 200));
        $lock  = EndorseRefreshRateScope::lockNameFor('production-environment', 'a-very-long-application-name', $scope);
        $this->assertLessThanOrEqual(64, strlen($lock));
    }

    // --- shared claim SQL contract (fails if production claim shape changes) ---

    public function testClaimSqlContract(): void
    {
        $sql = EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60);
        $this->assertStringContainsString('SELECT q.*', $sql);
        $this->assertStringContainsString('MAX(a.attempt_no)', $sql);
        $this->assertStringContainsString("q.status = 'pending'", $sql);
        $this->assertStringContainsString("q.platform != 'Threads'", $sql);
        $this->assertStringContainsString('q.worker_id IS NULL', $sql);
        $this->assertStringContainsString('q.attempts < q.max_attempts', $sql);
        $this->assertStringContainsString('ORDER BY q.priority DESC, q.attempts ASC, q.created_at ASC, q.id ASC', $sql);
        $this->assertStringContainsString('LIMIT 20', $sql);
        $this->assertStringContainsString('FOR UPDATE SKIP LOCKED', $sql);
        $this->assertStringContainsString('TIMESTAMPDIFF(SECOND, q.claimed_at, NOW(6))', $sql);
    }

    /**
     * Absolute `ORDER BY priority DESC` has no starvation guard: a deterministically failing
     * high-priority cohort outranks every fresh row beneath it until it exhausts max_attempts.
     * Demotion bounds that. Default 0 must leave the cron's SQL untouched.
     */
    public function testRetryDemotionIsOptInAndDefaultsToUnchangedSql(): void
    {
        $this->assertSame(
            EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60),
            EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60, 0),
            'demotion 0 must produce byte-identical SQL to the historical claim',
        );

        $demoted = EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60, 1);
        $this->assertStringContainsString(
            'ORDER BY (q.priority - q.attempts * 1) DESC, q.attempts ASC, q.created_at ASC, q.id ASC',
            $demoted,
        );

        // Still ordered by attempts within a band, so fresh work leads its own priority level.
        $this->assertStringContainsString('q.attempts ASC', $demoted);
    }

    public function testRetryDemotionIsClamped(): void
    {
        $this->assertStringContainsString(
            'ORDER BY q.priority DESC',
            EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60, -5),
            'a negative demotion must not promote retries above fresh work',
        );
        $this->assertStringContainsString(
            '(q.priority - q.attempts * 100)',
            EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60, 9999),
        );
    }

    public function testClaimSqlClampsLimitAndBase(): void
    {
        $sql = EndorseRefreshClaimRepository::buildSelectForUpdateSql(0, 0);
        $this->assertStringContainsString('LIMIT 1', $sql); // limit clamped to >=1
        $this->assertStringContainsString('(1 * POW(2', $sql); // base clamped to >=1
    }

    // --- quarantine exclusion at claim time -----------------------------------

    /**
     * The exclusion must be OFF by default. Tenants sit at different migration levels — one
     * worker binary serves both — and naming `endorse_refresh_quarantine` on a schema that
     * lacks it turns every claim into a SQL error instead of degrading to the old behaviour.
     */
    public function testQuarantineExclusionIsOptInAndDefaultsToUnchangedSql(): void
    {
        $this->assertSame(
            EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60, 0),
            EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60, 0, false),
            'the quarantine flag off must produce byte-identical SQL to the historical claim',
        );

        $this->assertStringNotContainsString(
            'endorse_refresh_quarantine',
            EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60),
        );
    }

    /**
     * Matching is (id_endorse, url_snapshot) and only while `cleared_at IS NULL`, so that
     * re-pointing an endorse at a fresh post re-opens the row with no operator action, and an
     * operator clearing a quarantine row takes effect on the next claim.
     */
    public function testQuarantineExclusionMatchesEndorseAndExactUrl(): void
    {
        $sql = EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60, 0, true);

        $this->assertStringContainsString('NOT EXISTS', $sql);
        $this->assertStringContainsString('endorse_refresh_quarantine z', $sql);
        $this->assertStringContainsString('z.id_endorse = q.id_endorse', $sql);
        $this->assertStringContainsString('z.cleared_at IS NULL', $sql);
        $this->assertStringContainsString('z.url_snapshot = q.link_upload', $sql);
    }

    /**
     * The exclusion narrows the WHERE; it must never disturb ordering or locking.
     */
    public function testQuarantineExclusionComposesWithDemotionAndKeepsClaimShape(): void
    {
        $sql = EndorseRefreshClaimRepository::buildSelectForUpdateSql(20, 60, 1, true);

        $this->assertStringContainsString('endorse_refresh_quarantine z', $sql);
        $this->assertStringContainsString(
            'ORDER BY (q.priority - q.attempts * 1) DESC, q.attempts ASC, q.created_at ASC, q.id ASC',
            $sql,
        );
        $this->assertStringContainsString('FOR UPDATE SKIP LOCKED', $sql);
        $this->assertStringContainsString("q.status = 'pending'", $sql);

        // The clause sits inside the WHERE, ahead of ORDER BY — not appended after LIMIT.
        $this->assertLessThan(
            strpos($sql, 'ORDER BY'),
            strpos($sql, 'NOT EXISTS'),
            'the exclusion must be part of the WHERE predicate',
        );
    }

    /**
     * The single-row isolation re-select deliberately does NOT carry the exclusion: it only
     * ever re-checks a row the batch already selected, and adding a second predicate there
     * would let the two paths disagree about eligibility.
     */
    public function testSingleRowReselectIsUnaffectedByQuarantine(): void
    {
        $this->assertStringNotContainsString(
            'endorse_refresh_quarantine',
            EndorseRefreshClaimRepository::buildSelectOneForUpdateSql(42, 60),
        );
    }

    // --- run logger sanitization ----------------------------------------------

    public function testRunLoggerEmitsStructuredJsonWithoutSecrets(): void
    {
        $captured = [];
        $logger   = new EndorseRefreshRunLogger(static function (string $line) use (&$captured) { $captured[] = $line; }, true);
        $logger('request', [
            'run_id' => 'r1', 'queue_id' => 7, 'scope' => 'rapidapi:ab12', 'attempt' => 1,
            'url'    => 'https://secret', 'cookie' => 'x', 'key' => 'topsecret', 'body' => '...',
        ]);
        $this->assertCount(1, $captured);
        $decoded = json_decode($captured[0], true);
        $this->assertSame('endorse_refresh_request', $decoded['evt']);
        $this->assertSame('r1', $decoded['run_id']);
        $this->assertSame(7, $decoded['queue_id']);

        foreach (['url', 'cookie', 'key', 'body'] as $secret) {
            $this->assertArrayNotHasKey($secret, $decoded, "{$secret} must never be logged");
        }
        $this->assertStringNotContainsString('topsecret', $captured[0]);
    }

    public function testRunLoggerCanDisableItemEvents(): void
    {
        $captured = [];
        $logger   = new EndorseRefreshRunLogger(static function (string $line) use (&$captured) { $captured[] = $line; }, false);
        $logger('request', ['run_id' => 'r1']); // item-level → suppressed
        $logger('run', ['run_id' => 'r1']);     // run-level → kept
        $this->assertCount(1, $captured);
        $this->assertStringContainsString('endorse_refresh_run', $captured[0]);
    }

    // --- out-of-order apply guard (pure) --------------------------------------

    public function testStaleObservationGuard(): void
    {
        // first observation (no existing) → always applies
        $this->assertFalse(Endorse_sync::isStaleObservation('', '2026-08-03 10:00:00.000000'));
        // strictly newer → applies
        $this->assertFalse(Endorse_sync::isStaleObservation('2026-08-03 10:00:00.000000', '2026-08-03 10:05:00.000000'));
        // strictly older → stale (skip)
        $this->assertTrue(Endorse_sync::isStaleObservation('2026-08-03 10:05:00.000000', '2026-08-03 10:00:00.000000'));
        // equal → stale (idempotent duplicate no-op)
        $this->assertTrue(Endorse_sync::isStaleObservation('2026-08-03 10:00:00.000000', '2026-08-03 10:00:00.000000'));
        // missing incoming → never stale (preserve legacy forward progress)
        $this->assertFalse(Endorse_sync::isStaleObservation('2026-08-03 10:00:00.000000', ''));
    }
}
