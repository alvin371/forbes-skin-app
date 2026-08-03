<?php
/**
 * One concurrent claim worker. Runs the EXACT atomic claim UPDATE from
 * EndorseRefreshQueueService::claimBatch (verbatim SQL) so the concurrency test
 * exercises real production behaviour, not a reimplementation.
 *
 * argv: dsn user pass worker_id limit retry_base
 * prints: claimed=<n>
 */
[$_, $dsn, $user, $pass, $wid, $limit, $base] = array_pad($argv, 7, null);
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$now = (new DateTime())->format('Y-m-d H:i:s');
$wid = $pdo->quote($wid);
$limit = intval($limit);
$base = intval($base ?: 60);

// --- verbatim from claimBatch() lines ~794-805 ---
$sql = "
    UPDATE endorse_refresh_queue
    SET status = 'processing', worker_id = $wid, claimed_at = " . $pdo->quote($now) . ", started_at = " . $pdo->quote($now) . "
    WHERE status = 'pending' AND platform != 'Threads' AND worker_id IS NULL
      AND (
            claimed_at IS NULL
            OR TIMESTAMPDIFF(SECOND, claimed_at, NOW()) >=
               ($base * POW(2, LEAST(10, GREATEST(attempts - 1, 0))))
      )
    ORDER BY priority DESC, attempts ASC, created_at ASC
    LIMIT $limit
";
$affected = $pdo->exec($sql);
echo "claimed=" . intval($affected) . "\n";
