<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshV2Coordinator.php';

/**
 * Provider circuit routing: only auth failures scoped to the failing provider may
 * open a systemic circuit. Item-level 403s on TikTok RapidAPI must stay local.
 *
 * @internal
 */
final class EndorseRefreshProviderTransitionTest extends TestCase
{
    public function testTiktokItemLevel403DoesNotOpenSystemicCircuit(): void
    {
        $transition = $this->transition(
            ['status' => false, 'msg' => 'Video is private', 'http_status' => 403, 'error_class' => Endorse_sync::ERR_PERMANENT],
            EndorseRefreshV2Coordinator::PROVIDER_KEY_RAPIDAPI,
        );

        $this->assertFalse($transition['systemic']);
        $this->assertSame('closed', $transition['state']);
    }

    public function testInstagram403OpensSystemicCircuit(): void
    {
        $transition = $this->transition(
            ['status' => false, 'msg' => 'Bad key', 'http_status' => 403, 'error_class' => Endorse_sync::ERR_TRANSIENT],
            EndorseRefreshV2Coordinator::PROVIDER_KEY_INSTAGRAM,
        );

        $this->assertTrue($transition['systemic']);
        $this->assertSame('provider_auth_failed', $transition['reason_code']);
    }

    public function testInstagramConfigClassOpensSystemicCircuit(): void
    {
        $transition = $this->transition(
            ['status' => false, 'msg' => 'Konfigurasi Instagram RapidAPI belum lengkap.', 'error_class' => Endorse_sync::ERR_CONFIG],
            EndorseRefreshV2Coordinator::PROVIDER_KEY_INSTAGRAM,
        );

        $this->assertTrue($transition['systemic']);
    }

    public function testThreadsConfigClassIsPerInfluencerNotSystemic(): void
    {
        $transition = $this->transition(
            ['status' => false, 'msg' => 'Akun Threads belum terhubung.', 'http_status' => 401, 'error_class' => Endorse_sync::ERR_CONFIG],
            EndorseRefreshV2Coordinator::PROVIDER_KEY_THREADS,
        );

        $this->assertFalse($transition['systemic']);
        $this->assertSame('threads_token_invalid', $transition['reason_code']);
    }

    public function testWorkerAuthReasonStaysSystemicForAnyProvider(): void
    {
        $transition = $this->transition(
            ['status' => false, 'msg' => 'Unauthorized', 'reason_code' => 'worker_auth_invalid'],
            EndorseRefreshV2Coordinator::PROVIDER_KEY_RAPIDAPI,
        );

        $this->assertTrue($transition['systemic']);
        $this->assertSame('provider_auth_failed', $transition['reason_code']);
    }

    private function transition(array $response, string $providerKey): array
    {
        $coordinator = (new ReflectionClass(EndorseRefreshV2Coordinator::class))->newInstanceWithoutConstructor();
        $method      = new ReflectionMethod(EndorseRefreshV2Coordinator::class, 'providerTransitionForResponse');

        return $method->invoke($coordinator, $response, $providerKey);
    }
}
