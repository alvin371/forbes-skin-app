<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once __DIR__ . '/../../application/libraries/PerformanceSpikeStateMachine.php';

/**
 * @internal
 */
final class PerformanceSpikeStateMachineTest extends TestCase
{
    public function testItOpensAtSixtyAndClosesAfterTwoRecoverySamples(): void
    {
        $opened = PerformanceSpikeStateMachine::transition(['incident_key' => 'mysql-1'], 60.0, 20.0, '2026-08-20T00:00:00Z');
        $this->assertSame('sample', $opened['event']);

        $firstLow = PerformanceSpikeStateMachine::transition($opened['state'], 59.9, 20.0, '2026-08-20T00:00:15Z');
        $this->assertSame('sample', $firstLow['event']);
        $this->assertSame(1, $firstLow['state']['recovery_count']);

        $closed = PerformanceSpikeStateMachine::transition($firstLow['state'], 0.0, 20.0, '2026-08-20T00:00:30Z');
        $this->assertSame('close', $closed['event']);
    }

    public function testItOpensOnFirstHighSampleAndResetsRecoveryWhenCpuReturns(): void
    {
        $opened = PerformanceSpikeStateMachine::transition([], 60.1, 5.0, '2026-08-20T00:00:00Z');
        $this->assertSame('open', $opened['event']);

        $state = $opened['state'];
        // The host collector assigns the durable key immediately after open.
        $state['incident_key'] = 'forbes_app-1';
        $recovering            = PerformanceSpikeStateMachine::transition($state, 10.0, 5.0, '2026-08-20T00:00:15Z');
        $highAgain             = PerformanceSpikeStateMachine::transition($recovering['state'], 80.0, 8.0, '2026-08-20T00:00:30Z');
        $this->assertSame('sample', $highAgain['event']);
        $this->assertSame(0, $highAgain['state']['recovery_count']);
        $this->assertSame(80.0, $highAgain['state']['peak_cpu_percent']);
    }
}
