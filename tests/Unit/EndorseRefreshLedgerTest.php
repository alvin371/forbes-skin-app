<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

if (! function_exists('log_message')) {
    function log_message($level, $message)
    {
    }
}

require_once __DIR__ . '/../../application/libraries/EndorseRefreshLedger.php';

/**
 * Captures the SQL the ledger emits, so the batching contract can be asserted without MySQL.
 *
 * @internal
 */
final class LedgerFakeDb
{
    public array $queries    = [];
    public int $affected     = 0;
    public ?Throwable $throw = null;

    public function query(string $sql)
    {
        $this->queries[] = $sql;
        if ($this->throw !== null) {
            throw $this->throw;
        }

        return true;
    }

    public function affected_rows(): int
    {
        return $this->affected;
    }

    public function escape($value): string
    {
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }
}

/**
 * The ledger is the measurement instrument for the 400/min target: requests-per-completion,
 * per-leg success rates and latency percentiles all come from it. Two properties matter.
 *
 * 1. It must never put a synchronous write on the hot path — record() does no I/O and one
 *    flush covers the whole buffer in a single statement.
 * 2. It must never be able to CREATE a rate-limit token. The table it writes is the limiter's
 *    table; an upsert that resurrected a pruned row would silently inflate the rolling window
 *    count and throttle the very run being measured.
 *
 * @internal
 */
final class EndorseRefreshLedgerTest extends TestCase
{
    private function outcome(array $overrides = []): array
    {
        return array_merge([
            'ok'            => 1, 'http_code' => 200, 'curl_errno' => 0,
            'total_time_ms' => 812, 'error_class' => null, 'retry_after_sec' => null,
        ], $overrides);
    }

    public function testRecordPerformsNoIo(): void
    {
        $db     = new LedgerFakeDb();
        $ledger = new EndorseRefreshLedger($db, 200, 60.0);

        for ($i = 1; $i <= 50; $i++) {
            $ledger->record($i, $this->outcome());
        }

        $this->assertSame([], $db->queries, 'record() must never touch the database');
        $this->assertSame(50, $ledger->pending());
    }

    public function testFlushWritesTheWholeBufferInOneStatement(): void
    {
        $db           = new LedgerFakeDb();
        $db->affected = 3;
        $ledger       = new EndorseRefreshLedger($db, 200, 60.0);

        $ledger->record(11, $this->outcome(['ok' => 1, 'http_code' => 200]));
        $ledger->record(12, $this->outcome(['ok' => 0, 'http_code' => 429, 'error_class' => 'rate_limited', 'retry_after_sec' => 7]));
        $ledger->record(13, $this->outcome(['ok' => 0, 'http_code' => 0, 'curl_errno' => 28, 'error_class' => 'infra_stall']));

        $this->assertSame(3, $ledger->flush(true));
        $this->assertCount(1, $db->queries, 'a flush must cost exactly one round-trip');

        $sql = $db->queries[0];
        $this->assertStringStartsWith('UPDATE `endorse_refresh_rate_tokens` SET', $sql);
        $this->assertStringContainsString('WHERE `id` IN (11,12,13)', $sql);
        $this->assertStringContainsString('`finished_at` = NOW(6)', $sql);
        $this->assertStringContainsString("WHEN 12 THEN 'rate_limited'", $sql);
        $this->assertStringContainsString('WHEN 13 THEN 28', $sql);
        $this->assertSame(0, $ledger->pending());
    }

    /**
     * The load-bearing safety property. INSERT ... ON DUPLICATE KEY UPDATE is the obvious
     * implementation and it can resurrect a pruned row as a live rate-limit token; an UPDATE
     * simply affects zero rows instead.
     */
    public function testFlushCanNeverInsertARow(): void
    {
        $db     = new LedgerFakeDb();
        $ledger = new EndorseRefreshLedger($db, 1, 0.01);
        $ledger->record(99, $this->outcome());
        $ledger->flush(true);

        $sql = $db->queries[0];
        $this->assertStringNotContainsStringIgnoringCase('INSERT', $sql);
        $this->assertStringNotContainsStringIgnoringCase('ON DUPLICATE KEY', $sql);
        $this->assertStringNotContainsStringIgnoringCase('REPLACE', $sql);
    }

    public function testUnsetColumnsLeaveExistingValuesUntouched(): void
    {
        $db     = new LedgerFakeDb();
        $ledger = new EndorseRefreshLedger($db, 1, 0.01);

        // Only ok is known for this token; everything else must fall through to ELSE.
        $ledger->record(5, ['ok' => 1]);
        $ledger->flush(true);

        $sql = $db->queries[0];
        $this->assertStringContainsString('`ok` = CASE `id` WHEN 5 THEN 1 ELSE `ok` END', $sql);
        $this->assertStringNotContainsString('`http_code` =', $sql, 'a column nobody reported must not be written at all');
    }

    public function testFlushIsDueOnSizeOrAge(): void
    {
        $db = new LedgerFakeDb();

        $bySize = new EndorseRefreshLedger($db, 3, 3600.0);
        $bySize->record(1, $this->outcome());
        $bySize->record(2, $this->outcome());
        $this->assertFalse($bySize->due(), 'under the row threshold and far from the time threshold');
        $bySize->record(3, $this->outcome());
        $this->assertTrue($bySize->due());

        $byAge = new EndorseRefreshLedger($db, 10000, 0.01);
        $byAge->record(1, $this->outcome());
        usleep(20000);
        $this->assertTrue($byAge->due(), 'an idle buffer must still drain on the time threshold');
    }

    public function testFlushIsANoOpWhenNothingIsBuffered(): void
    {
        $db     = new LedgerFakeDb();
        $ledger = new EndorseRefreshLedger($db, 1, 0.01);

        $this->assertSame(0, $ledger->flush(true));
        $this->assertSame([], $db->queries);
    }

    /**
     * A wedged loop fails the whole run; a lost ledger row only degrades the report. So a
     * failed flush drops its batch and keeps going — but says so, loudly, in stats().
     */
    public function testAFailedFlushDropsItsBatchRatherThanRetryingForever(): void
    {
        $db        = new LedgerFakeDb();
        $db->throw = new RuntimeException('deadlock');
        $ledger    = new EndorseRefreshLedger($db, 1, 0.01);

        $ledger->record(7, $this->outcome());
        $this->assertSame(0, $ledger->flush(true));
        $this->assertSame(0, $ledger->pending(), 'the failed batch must not be retried forever');
        $this->assertSame(1, $ledger->stats()['dropped']);
    }

    /**
     * No handle is created without a token, so a missing id is a bookkeeping bug worth surfacing.
     */
    public function testMissingTokenIdsAreCountedNotSilentlyIgnored(): void
    {
        $db     = new LedgerFakeDb();
        $ledger = new EndorseRefreshLedger($db, 10, 60.0);

        $ledger->record(null, $this->outcome());
        $ledger->record(0, $this->outcome());
        $ledger->record(-1, $this->outcome());

        $this->assertSame(3, $ledger->stats()['dropped']);
        $this->assertSame(0, $ledger->pending());
    }

    public function testPruneIsChunkedOrderedAndFloored(): void
    {
        $db           = new LedgerFakeDb();
        $db->affected = 0;
        $ledger       = new EndorseRefreshLedger($db, 10, 60.0);

        // First call is rate-limited away (constructor stamps lastPruneAt), so reflect the
        // clock back to prove the statement shape rather than the cadence.
        // setAccessible() is a no-op since PHP 8.1 and deprecated in 8.5 — see commit 527a2f04.
        (new ReflectionProperty(EndorseRefreshLedger::class, 'lastPruneAt'))
            ->setValue($ledger, microtime(true) - 120.0);

        $ledger->prune(30);   // below the floor

        $this->assertCount(1, $db->queries);
        $sql = $db->queries[0];
        $this->assertStringContainsString('DELETE FROM `endorse_refresh_rate_tokens`', $sql);
        $this->assertStringContainsString('INTERVAL 60 SECOND', $sql, 'retention must be floored at MIN_RETENTION_SEC');
        $this->assertStringContainsString('ORDER BY `created_at` LIMIT 2000', $sql, 'must be oldest-first and bounded');
    }

    /**
     * pruneExpired() on the limiter deletes everything older than ~120s. Running that during
     * a 30-minute load test would destroy the evidence the run exists to produce, so the
     * ledger prunes on its own, much slower, cadence.
     */
    public function testPruneRunsAtMostOncePerMinute(): void
    {
        $db     = new LedgerFakeDb();
        $ledger = new EndorseRefreshLedger($db, 10, 60.0);

        $ledger->prune(3600);
        $ledger->prune(3600);
        $ledger->prune(3600);

        $this->assertSame([], $db->queries, 'a freshly constructed ledger must not prune immediately');
    }
}
