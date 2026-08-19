<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Buffered writer for the per-request ledger in `endorse_refresh_rate_tokens`.
 *
 * A request's ledger row is created by CiDbReservationStore::reserveToken() at the moment the
 * request STARTS — that INSERT is the rate-limit token, already being paid for. This class
 * only completes those rows with the outcome, and it must never put a synchronous round-trip
 * on the hot path: at 600 requests/min an extra blocking write per completion is 600 stalls
 * per minute in the middle of an event loop that is also servicing sockets.
 *
 * So: record() is a pure array append, and flush() writes the whole buffer in ONE statement
 * on the loop's slow path.
 *
 * That statement is a multi-row UPDATE ... CASE, not an INSERT ... ON DUPLICATE KEY UPDATE.
 * The upsert form is the more obvious choice and it is a trap: it requires supplying an
 * explicit `id` plus dummy values for the NOT NULL columns, so if retention ever deleted a
 * row between reserve and flush, the "update" would silently INSERT it back — resurrecting a
 * pruned row as a live rate-limit token and inflating the rolling window count. An UPDATE
 * simply affects zero rows in that case. The safety property is structural, not a matter of
 * configuring retention correctly.
 */
final class EndorseRefreshLedger
{
    /**
     * Never prune more aggressively than this, whatever the caller asks for. Retention must
     * stay far above the flush horizon so a completion can always find its own row.
     * Deliberately duplicated rather than imported from the load-test guard: the ledger is
     * production code and must not depend on load-test-only infrastructure.
     */
    const MIN_RETENTION_SEC = 60;

    /** Columns completed at request end. Order is fixed so the CASE arms stay aligned. */
    const TERMINAL_COLUMNS = array(
        'ok'              => 'int',
        'http_code'       => 'int',
        'curl_errno'      => 'int',
        'total_time_ms'   => 'int',
        'error_class'     => 'string',
        'retry_after_sec' => 'int',
    );

    private $db;
    private int $flushRows;
    private float $flushSeconds;
    private float $lastFlushAt = 0.0;
    private float $lastPruneAt = 0.0;

    /** @var array<int, array<string, mixed>> tokenId => terminal fields */
    private array $buffer = array();

    private int $written = 0;
    private int $dropped = 0;

    public function __construct($db, int $flushRows = 200, float $flushSeconds = 0.25)
    {
        $this->db = $db;
        $this->flushRows = max(1, $flushRows);
        $this->flushSeconds = max(0.01, $flushSeconds);
        $this->lastFlushAt = microtime(true);
        $this->lastPruneAt = microtime(true);
    }

    /**
     * Buffer one request outcome. No I/O.
     *
     * A null/absent $tokenId means the request was never reserved — which should be
     * impossible, since no handle is created without a token. Counted rather than thrown so a
     * bookkeeping bug degrades the report instead of killing a 30-minute run, and surfaced by
     * stats() so it cannot pass unnoticed.
     */
    public function record(?int $tokenId, array $fields): void
    {
        if ($tokenId === null || $tokenId <= 0) {
            $this->dropped++;

            return;
        }

        $this->buffer[$tokenId] = $fields;
    }

    public function due(): bool
    {
        return $this->buffer !== array()
            && (count($this->buffer) >= $this->flushRows || (microtime(true) - $this->lastFlushAt) >= $this->flushSeconds);
    }

    /**
     * Write buffered outcomes. Returns rows written. Safe to call on every loop iteration —
     * it no-ops unless the buffer is due (or $force).
     */
    public function flush(bool $force = false): int
    {
        if ($this->buffer === array() || (!$force && !$this->due())) {
            return 0;
        }

        $rows = $this->buffer;
        // Clear BEFORE the write: a failed flush must not re-attempt the same rows forever and
        // stall the loop. Losing a ledger row degrades a report; a wedged loop fails the run.
        $this->buffer = array();
        $this->lastFlushAt = microtime(true);

        $ids = array_keys($rows);
        $sets = array();

        foreach (self::TERMINAL_COLUMNS as $column => $type) {
            $arms = array();
            foreach ($ids as $id) {
                if (!array_key_exists($column, $rows[$id])) {
                    continue;
                }
                $arms[] = 'WHEN ' . (int) $id . ' THEN ' . $this->literal($rows[$id][$column], $type);
            }
            if ($arms === array()) {
                continue;
            }
            // ELSE `column` leaves rows this batch says nothing about untouched.
            $sets[] = '`' . $column . '` = CASE `id` ' . implode(' ', $arms) . ' ELSE `' . $column . '` END';
        }

        // finished_at is the completion marker, so it is always stamped — that is what makes
        // "started and never came back" a single predicate:
        //   finished_at IS NULL AND created_at < NOW(6) - INTERVAL 60 SECOND
        $sets[] = '`finished_at` = NOW(6)';

        $sql = 'UPDATE `endorse_refresh_rate_tokens` SET ' . implode(', ', $sets)
             . ' WHERE `id` IN (' . implode(',', array_map('intval', $ids)) . ')';

        try {
            $this->db->query($sql);
            $written = (int) $this->db->affected_rows();
            $this->written += $written;

            return $written;
        } catch (Throwable $e) {
            $this->dropped += count($ids);
            log_message('error', 'endorse_refresh_ledger_flush_failed: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Delete ledger rows older than $retentionSec, in bounded chunks, at most once per minute.
     *
     * Deliberately NOT EndorseRefreshRateLimiter::pruneExpired(), which deletes everything
     * older than window+grace (~120s) — running that mid-load-test would destroy the evidence
     * the run exists to produce.
     *
     * Chunked because one large DELETE holds locks long enough to stall a concurrent
     * reserve(), which would show up as a throughput cliff with no obvious cause.
     */
    public function prune(int $retentionSec, int $chunk = 2000, int $maxChunks = 5): int
    {
        $retentionSec = max(self::MIN_RETENTION_SEC, $retentionSec);
        if ((microtime(true) - $this->lastPruneAt) < 60.0) {
            return 0;
        }
        $this->lastPruneAt = microtime(true);

        $removed = 0;
        for ($i = 0; $i < $maxChunks; $i++) {
            $this->db->query(
                'DELETE FROM `endorse_refresh_rate_tokens` WHERE `created_at` < (NOW(6) - INTERVAL ' . (int) $retentionSec . ' SECOND)'
                . ' ORDER BY `created_at` LIMIT ' . (int) $chunk
            );
            $n = (int) $this->db->affected_rows();
            $removed += $n;
            if ($n < $chunk) {
                break;
            }
        }

        return $removed;
    }

    /** Buffered-but-unwritten count, for shutdown accounting. */
    public function pending(): int
    {
        return count($this->buffer);
    }

    /**
     * Written vs dropped. `dropped` must be 0 in a healthy run: a non-zero value means the
     * ledger has a hole, and the requests-per-completion metric computed from it understates
     * the true amplification.
     */
    public function stats(): array
    {
        return array('written' => $this->written, 'dropped' => $this->dropped, 'pending' => count($this->buffer));
    }

    /** Inline literal rather than a bound parameter: one statement, N rows, no placeholder blowup. */
    private function literal($value, string $type): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($type === 'int') {
            return (string) (int) $value;
        }

        return $this->db->escape((string) $value);
    }
}
