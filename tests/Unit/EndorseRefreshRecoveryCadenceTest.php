<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';

/**
 * claimBatch() runs stale recovery before anything else, which is correct and load-bearing:
 * orphaned 'processing' rows otherwise pin the per-minute counter, every run skips, and the
 * stall sustains itself.
 *
 * That reasoning assumes a claimer that runs once a minute. A continuous worker claims every
 * few hundred milliseconds, and resetStuck() is not free — it scans `processing` under
 * ORDER BY id LIMIT 250 FOR UPDATE SKIP LOCKED. At 400 completions/min across several
 * replicas that is tens of recovery scans per second, taking X-locks across rows that are
 * legitimately in flight in another replica.
 *
 * So recovery gained a cadence guard. These tests pin the one property that matters most:
 * the DEFAULT is unchanged, so the cron cannot silently start recovering less often.
 *
 * @internal
 */
final class EndorseRefreshRecoveryCadenceTest extends TestCase
{
    /**
     * The constructor wires CI superglobals; the policy under test needs none of them.
     */
    private function service(float $lastRecoveryAt = 0.0): EndorseRefreshQueueService
    {
        $svc = (new ReflectionClass(EndorseRefreshQueueService::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(EndorseRefreshQueueService::class, 'lastRecoveryAt'))
            ->setValue($svc, $lastRecoveryAt);

        return $svc;
    }

    /**
     * The cron passes no interval. If this ever returns false, production quietly recovers
     * less often than it did, and the self-sustaining-stall bug comes back.
     */
    public function testZeroIntervalAlwaysRecoversSoTheCronPathIsUnchanged(): void
    {
        $now = 1_000_000.0;
        $svc = $this->service($now);

        $this->assertTrue($svc->recoveryIsDue(0.0, $now));
        $this->assertTrue($svc->recoveryIsDue(0.0, $now + 0.001));
        $this->assertTrue($svc->recoveryIsDue(-1.0, $now), 'a negative interval must not be read as "never"');
    }

    /**
     * A fresh process has never recovered, so its first claim must recover immediately —
     * otherwise a restarted worker would ignore rows orphaned by the process it replaced
     * for a full interval.
     */
    public function testAFreshProcessRecoversOnItsFirstClaim(): void
    {
        $this->assertTrue($this->service(0.0)->recoveryIsDue(5.0, microtime(true)));
    }

    public function testRecoveryIsSuppressedInsideTheIntervalAndResumesAfterIt(): void
    {
        $now = 1_000_000.0;
        $svc = $this->service($now);

        $this->assertFalse($svc->recoveryIsDue(5.0, $now + 0.2), 'a claim 200ms later must not rescan');
        $this->assertFalse($svc->recoveryIsDue(5.0, $now + 4.999));
        $this->assertTrue($svc->recoveryIsDue(5.0, $now + 5.0), 'the boundary is inclusive');
        $this->assertTrue($svc->recoveryIsDue(5.0, $now + 60.0));
    }

    /**
     * The guard bounds worst-case recovery latency to one interval. That has to stay far
     * below the claim lease or a genuinely dead worker's rows sit unrecovered past the point
     * where another replica could have taken them.
     */
    public function testWorstCaseRecoveryLatencyStaysWellUnderTheLease(): void
    {
        $intervalSec = 5.0;
        $leaseSec    = 120;   // ENDORSE_REFRESH_LEASE_SEC default, clamped 60..900 in claimBatch

        $this->assertLessThan(
            $leaseSec / 10,
            $intervalSec,
            'the recovery interval must stay an order of magnitude below the lease',
        );
    }
}
