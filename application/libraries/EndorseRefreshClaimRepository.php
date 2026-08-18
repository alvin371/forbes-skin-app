<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Single source of truth for endorse-refresh claim selection.
 *
 * The claim is one serialized UPDATE that flips `pending` rows to `processing`, stamps the
 * worker id and respects the exponential retry cooldown. Production (`claimBatch`) and the
 * MySQL concurrency tests both build the statement here so the tested SQL can never drift
 * from the shipped SQL.
 *
 * Inputs are controlled (worker id is uniqid(), timestamps are formatted, limit/base are
 * ints) so single-quote interpolation is safe; there is no user input in this statement.
 */
final class EndorseRefreshClaimRepository
{
    /**
     * Select and lock eligible parent rows. Attempt allocation and the parent
     * transition are performed in the same transaction by QueueService.
     */
    public static function buildSelectForUpdateSql(int $limit, int $retryBaseSeconds): string
    {
        $limit = max(1, min(500, $limit));
        $retryBaseSeconds = max(1, min(3600, $retryBaseSeconds));

        return "
            SELECT q.*,
                   COALESCE((
                       SELECT MAX(a.attempt_no)
                       FROM endorse_refresh_queue_attempts a
                       WHERE a.queue_id = q.id
                   ), 0) AS history_attempt_sequence
            FROM endorse_refresh_queue q
            WHERE q.status = 'pending'
              AND q.platform != 'Threads'
              AND q.worker_id IS NULL
              AND q.attempts < q.max_attempts
              AND (
                    (q.next_attempt_at IS NOT NULL AND q.next_attempt_at <= NOW(6))
                    OR (
                        q.next_attempt_at IS NULL
                        AND (
                            q.claimed_at IS NULL
                            OR TIMESTAMPDIFF(SECOND, q.claimed_at, NOW(6)) >=
                               ($retryBaseSeconds * POW(2, LEAST(10, GREATEST(q.attempts - 1, 0))))
                        )
                    )
              )
            ORDER BY q.priority DESC, q.attempts ASC, q.created_at ASC, q.id ASC
            LIMIT $limit
            FOR UPDATE SKIP LOCKED
        ";
    }

    /**
     * Re-select ONE still-eligible candidate under its own lock, for the isolation pass
     * that runs after a batch claim was rolled back. Same eligibility predicate as the
     * batch select, so isolation can never claim a row the batch would have rejected.
     */
    public static function buildSelectOneForUpdateSql(int $queueId, int $retryBaseSeconds): string
    {
        $queueId = max(0, $queueId);
        $retryBaseSeconds = max(1, min(3600, $retryBaseSeconds));

        return "
            SELECT q.*,
                   COALESCE((
                       SELECT MAX(a.attempt_no)
                       FROM endorse_refresh_queue_attempts a
                       WHERE a.queue_id = q.id
                   ), 0) AS history_attempt_sequence
            FROM endorse_refresh_queue q
            WHERE q.id = $queueId
              AND q.status = 'pending'
              AND q.platform != 'Threads'
              AND q.worker_id IS NULL
              AND q.attempts < q.max_attempts
              AND (
                    (q.next_attempt_at IS NOT NULL AND q.next_attempt_at <= NOW(6))
                    OR (
                        q.next_attempt_at IS NULL
                        AND (
                            q.claimed_at IS NULL
                            OR TIMESTAMPDIFF(SECOND, q.claimed_at, NOW(6)) >=
                               ($retryBaseSeconds * POW(2, LEAST(10, GREATEST(q.attempts - 1, 0))))
                        )
                    )
              )
            LIMIT 1
            FOR UPDATE SKIP LOCKED
        ";
    }

    /**
     * DEPRECATED — NOT the production claim path.
     *
     * This single-UPDATE claim predates attempt allocation: it flips rows to `processing`
     * without creating an attempt row, without a lease and without an affected-row check.
     * Production claims go through EndorseRefreshQueueService::claimBatch(), which uses
     * buildSelectForUpdateSql() and allocates the attempt in the same transaction.
     *
     * It is retained only for the drain E2E behaviour simulator, which runs against its own
     * disposable database. Do not call it from application code.
     */
    public static function buildClaimSql(string $workerId, string $now, int $limit, int $retryBaseSeconds): string
    {
        $limit = max(1, $limit);
        $retryBaseSeconds = max(1, $retryBaseSeconds);

        return "
            UPDATE endorse_refresh_queue
            SET status = 'processing', worker_id = '$workerId', claimed_at = '$now', started_at = '$now'
            WHERE status = 'pending' AND platform != 'Threads' AND worker_id IS NULL
              AND (
                    claimed_at IS NULL
                    OR TIMESTAMPDIFF(SECOND, claimed_at, NOW()) >=
                       ($retryBaseSeconds * POW(2, LEAST(10, GREATEST(attempts - 1, 0))))
              )
            ORDER BY priority DESC, attempts ASC, created_at ASC
            LIMIT $limit
        ";
    }
}
