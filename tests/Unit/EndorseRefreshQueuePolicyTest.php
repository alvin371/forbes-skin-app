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
