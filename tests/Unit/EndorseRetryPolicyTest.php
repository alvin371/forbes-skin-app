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
    public function testOnlyPermanentAndEmptyTerminate(): void
    {
        $terminal = [
            Endorse_sync::ERR_PERMANENT,
            Endorse_sync::ERR_EMPTY,
        ];

        foreach ($terminal as $class) {
            $this->assertTrue(
                Endorse_sync::is_terminal_class($class),
                "{$class} must terminate the queue row",
            );
        }
    }

    public function testTransportAndTransientClassesAreRetried(): void
    {
        $recoverable = [
            Endorse_sync::ERR_TRANSIENT,
            Endorse_sync::ERR_INFRA,
            Endorse_sync::ERR_INFRA_DNS,
            Endorse_sync::ERR_INFRA_CONNECT,
            Endorse_sync::ERR_INFRA_TLS,
            Endorse_sync::ERR_INFRA_STALL,
            Endorse_sync::ERR_CONFIG,
            'something_new',
        ];

        foreach ($recoverable as $class) {
            $this->assertFalse(
                Endorse_sync::is_terminal_class($class),
                "{$class} must be retried, not terminal-failed (queue-stall regression)",
            );
        }
    }
}
