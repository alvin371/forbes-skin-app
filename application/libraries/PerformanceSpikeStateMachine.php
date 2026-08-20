<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Small, deterministic state machine used by the host collector contract.
 * A spike opens on the first CPU sample at/above the threshold and closes only
 * after the configured number of consecutive recovery samples.
 */
class PerformanceSpikeStateMachine
{
    public static function transition(array $state, float $cpu, float $memory, string $at, int $threshold = 60, int $recoverySamples = 2): array
    {
        $open = !empty($state['incident_key']);
        $high = $cpu >= $threshold;
        $next = $state;
        $next['peak_cpu_percent'] = max((float) ($state['peak_cpu_percent'] ?? 0), $cpu);
        $next['peak_memory_percent'] = max((float) ($state['peak_memory_percent'] ?? 0), $memory);

        if (! $open && ! $high) {
            return ['event' => 'idle', 'state' => []];
        }

        if (! $open) {
            $next['started_at'] = $at;
            $next['recovery_count'] = 0;

            return ['event' => 'open', 'state' => $next];
        }

        if ($high) {
            $next['recovery_count'] = 0;

            return ['event' => 'sample', 'state' => $next];
        }

        $next['recovery_count'] = (int) ($state['recovery_count'] ?? 0) + 1;
        if ($next['recovery_count'] >= $recoverySamples) {
            return ['event' => 'close', 'state' => $next];
        }

        return ['event' => 'sample', 'state' => $next];
    }
}
