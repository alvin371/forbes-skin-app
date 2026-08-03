<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';

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
            EndorseRefreshRateScope::scope('rapidapi', 'k')
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
        $fp = EndorseRefreshRateScope::fingerprint($key);
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
            EndorseRefreshRateScope::lockNameFor('prod', 'sec-forbes', $scope)
        );
        $this->assertNotSame(
            EndorseRefreshRateScope::lockNameFor('prod', 'forbes', $scope),
            EndorseRefreshRateScope::lockNameFor('staging', 'forbes', $scope)
        );
    }

    public function testLockNameStaysWithinMysqlLimit(): void
    {
        $scope = EndorseRefreshRateScope::scope('rapidapi', str_repeat('x', 200));
        $lock = EndorseRefreshRateScope::lockNameFor('production-environment', 'a-very-long-application-name', $scope);
        $this->assertLessThanOrEqual(64, strlen($lock));
    }

    // --- shared claim SQL contract (fails if production claim shape changes) ---

    public function testClaimSqlContract(): void
    {
        $sql = EndorseRefreshClaimRepository::buildClaimSql('w_test', '2026-08-03 10:00:00', 20, 60);
        $this->assertStringContainsString("UPDATE endorse_refresh_queue", $sql);
        $this->assertStringContainsString("SET status = 'processing'", $sql);
        $this->assertStringContainsString("worker_id = 'w_test'", $sql);
        $this->assertStringContainsString("status = 'pending'", $sql);
        $this->assertStringContainsString("platform != 'Threads'", $sql);
        $this->assertStringContainsString("worker_id IS NULL", $sql);
        $this->assertStringContainsString("ORDER BY priority DESC, attempts ASC, created_at ASC", $sql);
        $this->assertStringContainsString("LIMIT 20", $sql);
        // cooldown expression present
        $this->assertStringContainsString("TIMESTAMPDIFF(SECOND, claimed_at, NOW())", $sql);
    }

    public function testClaimSqlClampsLimitAndBase(): void
    {
        $sql = EndorseRefreshClaimRepository::buildClaimSql('w', 'now', 0, 0);
        $this->assertStringContainsString("LIMIT 1", $sql); // limit clamped to >=1
        $this->assertStringContainsString("(1 * POW(2", $sql); // base clamped to >=1
    }
}
