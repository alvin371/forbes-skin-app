<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';

/**
 * Locks the queue retry policy that fixes the 3-day endorse-refresh stall.
 *
 * Regression guard: recoverable transport/infra/config errors must NOT terminally
 * fail a queue row (that drained the whole queue into 'failed'). Only genuinely
 * unrecoverable classes — permanent, empty — may terminate.
 *
 * @internal
 */
final class EndorseRetryPolicyTest extends TestCase
{
    public function terminalClassProvider(): array
    {
        return [
            'permanent' => [Endorse_sync::ERR_PERMANENT],
            'empty'     => [Endorse_sync::ERR_EMPTY],
        ];
    }

    public function recoverableClassProvider(): array
    {
        return [
            'transient'     => [Endorse_sync::ERR_TRANSIENT],
            'infra'         => [Endorse_sync::ERR_INFRA],
            'infra_dns'     => [Endorse_sync::ERR_INFRA_DNS],
            'infra_connect' => [Endorse_sync::ERR_INFRA_CONNECT],
            'infra_tls'     => [Endorse_sync::ERR_INFRA_TLS],
            'infra_stall'   => [Endorse_sync::ERR_INFRA_STALL],
            'config'        => [Endorse_sync::ERR_CONFIG],
            'unknown'       => ['something_new'],
        ];
    }

    /**
     * @dataProvider terminalClassProvider
     */
    public function testTerminalClassesFailImmediately(string $class): void
    {
        $this->assertTrue(
            Endorse_sync::is_terminal_class($class),
            "$class must terminate the queue row"
        );
    }

    /**
     * @dataProvider recoverableClassProvider
     */
    public function testRecoverableClassesAreRetried(string $class): void
    {
        $this->assertFalse(
            Endorse_sync::is_terminal_class($class),
            "$class must be retried, not terminal-failed (queue-stall regression)"
        );
    }
}
