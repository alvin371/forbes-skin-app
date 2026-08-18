<?php

/**
 * Concurrent MySQL worker driving the REAL production claim path.
 *
 * This deliberately calls EndorseRefreshQueueService::claimBatch() rather than
 * re-implementing the protocol: a hand-written copy of the SQL can stay green while the
 * shipped allocator regresses, which is exactly the gap that let a broken attempt counter
 * and a missing affected-row assertion through.
 *
 * argv: host port user pass db worker_id limit retry_base [barrier_unix] [crash_point]
 */
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/support/FakeCi.php';
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';

[$script, $host, $port, $user, $pass, $db, $workerId, $limit, $retryBase, $barrier, $crashPoint] = array_pad($argv, 11, null);
unset($script, $workerId);

$conn = new mysqli($host, $user, $pass, $db, (int) $port);
$conn->query("SET SESSION sql_mode=''");

$ci                   = new FakeCi($conn);
$GLOBALS['__fake_ci'] = $ci;
$ci->endorse_sync     = new Endorse_sync();
$svc                  = new EndorseRefreshQueueService();

if ($crashPoint === 'after_attempt_insert') {
    // The first write to endorse_refresh_queue in claimBatch() is the parent activation,
    // i.e. immediately after the attempt row is inserted in the same transaction.
    $ci->db->crashBeforeWriteTo('endorse_refresh_queue');
}

// Release all workers into the same instant so they genuinely contend for the same rows.
if ((float) $barrier > 0) {
    while (microtime(true) < (float) $barrier) {
        usleep(1000);
    }
}

$claim = $svc->claimBatch([
    'limit'              => (int) $limit,
    'retry_base_seconds' => (int) $retryBase,
    'incremental_claim'  => false,
]);

if (empty($claim['status'])) {
    echo "rolled_back=1\n";

    exit(0);
}

echo 'claimed=' . (int) $claim['claimed'] . "\n";
