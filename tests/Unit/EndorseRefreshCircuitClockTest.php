<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshV2Coordinator.php';

/**
 * The provider/worker circuit breaker decides "is this circuit still open?" from a
 * DATETIME that carries no timezone.
 *
 * That decision has to be made by the database, next to the clock that wrote the value.
 * Made in PHP — `strtotime($row['open_until']) > time()` — the zone-less string is
 * re-parsed in the process timezone (index.php pins Asia/Jakarta), so a value written in
 * any other zone reads hours off. On this deployment that meant every open breaker looked
 * already-expired and never held a provider closed.
 *
 * These tests pin the decision to the database-supplied flag, so restoring the PHP
 * comparison fails here rather than silently disabling the breaker in production.
 *
 * @internal
 */
final class EndorseRefreshCircuitClockTest extends TestCase
{
    /**
     * @param array<string, mixed> $row
     */
    private function isCircuitActive(array $row): bool
    {
        // The constructor wires CI superglobals; the method under test needs none of them.
        $coordinator = (new ReflectionClass(EndorseRefreshV2Coordinator::class))->newInstanceWithoutConstructor();
        $method      = new ReflectionMethod(EndorseRefreshV2Coordinator::class, 'isCircuitActive');

        return (bool) $method->invoke($coordinator, $row);
    }

    /**
     * The regression itself: a breaker the DATABASE says is still open must stay open,
     * even though the stored string looks long past to PHP.
     */
    public function testOpenCircuitStaysActiveWhenTheDatabaseSaysTheWindowIsStillFuture(): void
    {
        // Reproduce the production skew exactly: the value is written in UTC, the process
        // clock is Asia/Jakarta (index.php:2), so PHP reads it as ~7 hours in the past
        // while the database — which wrote it — reports it as 300 s in the future.
        $writtenInAnotherZone = gmdate('Y-m-d H:i:s', time() + 300) . '.000000';
        $previous             = date_default_timezone_get();
        date_default_timezone_set('Asia/Jakarta');

        try {
            $this->assertLessThan(
                time(),
                strtotime($writtenInAnotherZone),
                'precondition: PHP must misread this live deadline as already past',
            );
            $this->assertTrue(
                $this->isCircuitActive([
                    'state'                => 'open',
                    'open_until'           => $writtenInAnotherZone,
                    'open_until_is_future' => 1,
                ]),
                'the database is the authority on whether the open window has passed',
            );
        } finally {
            date_default_timezone_set($previous);
        }
    }

    public function testOpenCircuitClosesOnceTheDatabaseSaysTheWindowHasPassed(): void
    {
        $this->assertFalse(
            $this->isCircuitActive([
                'state'                => 'open',
                'open_until'           => '2020-01-01 00:00:00.000000',
                'open_until_is_future' => 0,
            ]),
            'an elapsed window must actually release the circuit',
        );
    }

    /**
     * Fail closed: if the flag is missing the circuit must not be treated as live purely
     * because a string happens to parse into the future.
     */
    public function testMissingDatabaseFlagIsNotInferredFromTheStoredString(): void
    {
        $this->assertFalse(
            $this->isCircuitActive([
                'state'      => 'open',
                'open_until' => gmdate('Y-m-d H:i:s', time() + 3600) . '.000000',
            ]),
            'liveness must come from the database flag, never from re-parsing the DATETIME',
        );
    }

    public function testHalfOpenIsAlwaysActiveAndClosedIsNever(): void
    {
        $this->assertTrue($this->isCircuitActive(['state' => 'half_open']));
        $this->assertFalse($this->isCircuitActive(['state' => 'closed']));
        $this->assertFalse($this->isCircuitActive([]));
    }

    /**
     * An open breaker with no deadline at all is indefinite, not expired.
     */
    public function testOpenCircuitWithoutADeadlineStaysActive(): void
    {
        $this->assertTrue($this->isCircuitActive(['state' => 'open', 'open_until' => null]));
    }
}
