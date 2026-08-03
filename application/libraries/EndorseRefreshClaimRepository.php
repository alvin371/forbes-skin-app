<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Single source of truth for the atomic endorse-refresh claim.
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
     * Build the verbatim atomic claim UPDATE. Contract (asserted by a regression test):
     *  - only `pending`, non-Threads, unowned rows;
     *  - honours the exponential cooldown on `claimed_at`;
     *  - orders by priority DESC, attempts ASC, created_at ASC;
     *  - bounded by LIMIT.
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
