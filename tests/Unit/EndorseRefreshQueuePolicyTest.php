<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';

/**
 * Pure policy guards for driver ownership, retry cooldown and URL normalization.
 *
 * @internal
 */
final class EndorseRefreshQueuePolicyTest extends TestCase
{
    public function testOnlyRustDriverAllowsPullClaims(): void
    {
        $this->assertTrue(EndorseRefreshQueueService::allowsRustClaims('rust'));
        $this->assertTrue(EndorseRefreshQueueService::allowsRustClaims(' RUST '));
        $this->assertFalse(EndorseRefreshQueueService::allowsRustClaims('cron'));
        $this->assertFalse(EndorseRefreshQueueService::allowsRustClaims(''));
    }

    public function testRetryDelayUsesExponentialCooldown(): void
    {
        $this->assertSame(60, EndorseRefreshQueueService::retryDelaySeconds(1, 60));
        $this->assertSame(120, EndorseRefreshQueueService::retryDelaySeconds(2, 60));
        $this->assertSame(240, EndorseRefreshQueueService::retryDelaySeconds(3, 60));
        $this->assertSame(1, EndorseRefreshQueueService::retryDelaySeconds(0, 0));
    }

    public function testRetryJitterIsDeterministicAndHonorsProviderDelay(): void
    {
        $first = EndorseRefreshQueueService::retryDelayWithJitter(42, 2, 60);
        $this->assertSame($first, EndorseRefreshQueueService::retryDelayWithJitter(42, 2, 60));
        $this->assertGreaterThanOrEqual(120, $first);
        $this->assertSame(300, EndorseRefreshQueueService::retryDelayWithJitter(42, 2, 60, 300));
    }

    public function testSchedulingDeadlinesAreDatabaseSideAndBounded(): void
    {
        // No PHP clock anywhere in a scheduling deadline: the expression is evaluated by
        // MySQL, so the process timezone cannot shift it.
        $this->assertSame('NOW(6)', EndorseRefreshQueueService::schedulingNowSql());
        $this->assertSame(
            'DATE_ADD(NOW(6), INTERVAL 120 SECOND)',
            EndorseRefreshQueueService::schedulingDeadlineSql(120),
        );
        // Bounded and integer-cast, so no caller value can reach SQL as text.
        $this->assertSame(
            'DATE_ADD(NOW(6), INTERVAL 0 SECOND)',
            EndorseRefreshQueueService::schedulingDeadlineSql(-5),
        );
        $this->assertSame(
            'DATE_ADD(NOW(6), INTERVAL 86400 SECOND)',
            EndorseRefreshQueueService::schedulingDeadlineSql(999999),
        );
        $this->assertSame(
            'DATE_ADD(NOW(6), INTERVAL 900 SECOND)',
            EndorseRefreshQueueService::schedulingDeadlineSql(1200, 900),
        );
    }

    /**
     * The recovery policy must never turn an already-successful or unexplainable attempt
     * into another provider request.
     */
    public function testRecoveryDecisionNeverRetriesSuccessfulOrAmbiguousAttempts(): void
    {
        $this->assertSame(
            EndorseRefreshQueueService::RECOVERY_CLOSE_OPEN_ATTEMPT,
            EndorseRefreshQueueService::recoveryDecision('processing', 5, 5),
        );
        $this->assertSame(
            EndorseRefreshQueueService::RECOVERY_SYNTHESIZE_ATTEMPT,
            EndorseRefreshQueueService::recoveryDecision('', 0, 0),
        );

        foreach (['failed', 'timed_out', 'cancelled', 'retrying'] as $closed) {
            $this->assertSame(
                EndorseRefreshQueueService::RECOVERY_RELEASE_ONLY,
                EndorseRefreshQueueService::recoveryDecision($closed, 5, 5),
                "{$closed} attempts are already closed and only release the parent",
            );
        }

        foreach (['completed', 'submitted', 'something_new'] as $unsafe) {
            $this->assertSame(
                EndorseRefreshQueueService::RECOVERY_INCONSISTENT,
                EndorseRefreshQueueService::recoveryDecision($unsafe, 5, 5),
                "{$unsafe} must never be silently retried",
            );
        }

        // Parent points at an attempt row that did not join: no evidence, no retry.
        $this->assertSame(
            EndorseRefreshQueueService::RECOVERY_INCONSISTENT,
            EndorseRefreshQueueService::recoveryDecision('', 7, 0),
        );
    }

    public function testRetryAfterSupportsSecondsAndHttpDate(): void
    {
        $this->assertSame(90, EndorseRefreshQueueService::retryAfterSeconds([
            'error_meta' => ['retry_after' => '90'],
        ]));
        $future = gmdate('D, d M Y H:i:s \G\M\T', time() + 120);
        $parsed = EndorseRefreshQueueService::retryAfterSeconds(['retry_after' => $future]);
        $this->assertGreaterThanOrEqual(118, $parsed);
        $this->assertLessThanOrEqual(120, $parsed);
    }

    public function testWorkerSettingsAreConservativelyBounded(): void
    {
        $this->assertSame(20, EndorseRefreshQueueService::boundedWorkerSetting(0, 20, 50));
        $this->assertSame(50, EndorseRefreshQueueService::boundedWorkerSetting(400, 20, 50));
        $this->assertSame(5, EndorseRefreshQueueService::boundedWorkerSetting(5, 20, 50));
    }

    public function testTikTokUrlNormalizationAddsMissingScheme(): void
    {
        $this->assertSame(
            'https://www.tiktok.com/@creator/photo/1234567890',
            EndorseRefreshQueueService::normalizeTiktokUrl('www.tiktok.com/@creator/photo/1234567890'),
        );
        $this->assertSame(
            'https://m.tiktok.com/v/1234567890',
            EndorseRefreshQueueService::normalizeTiktokUrl('//m.tiktok.com/v/1234567890'),
        );
        $this->assertSame(
            'https://www.tiktok.com/@creator/video/1234567890',
            EndorseRefreshQueueService::normalizeTiktokUrl('https://www.tiktok.com/@creator/video/1234567890'),
        );
    }
}
