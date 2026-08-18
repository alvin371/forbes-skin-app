<?php

/**
 * Concurrent worker driving the REAL production V2 claim path
 * (EndorseRefreshV2Coordinator::claimBatchV2), not a reimplementation of it.
 *
 * argv: host port user pass db worker_uuid limit [barrier_unix]
 * prints: claimed=<n>:<queue_id,queue_id,...>   or   failed=<reason>
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
require_once __DIR__ . '/../../application/libraries/EndorseRefreshV2Coordinator.php';

[$script, $host, $port, $user, $pass, $db, $workerId, $limit, $barrier] = array_pad($argv, 9, null);
unset($script);

$conn = new mysqli($host, $user, $pass, $db, (int) $port);
$conn->query("SET SESSION sql_mode=''");

$ci                   = new FakeCi($conn);
$GLOBALS['__fake_ci'] = $ci;
$ci->endorse_sync     = new Endorse_sync();

$coordinator = new EndorseRefreshV2Coordinator();

// Release every worker into the same instant so they genuinely contend.
if ((float) $barrier > 0) {
    while (microtime(true) < (float) $barrier) {
        usleep(1000);
    }
}

$claim = $coordinator->claimBatchV2(
    EndorseRefreshV2Coordinator::OWNER_RUST,
    (string) $workerId,
    (int) $limit,
);

$body = is_array($claim['body'] ?? null) ? $claim['body'] : [];
if ((int) ($claim['http_status'] ?? 0) !== 200 || empty($body['status'])) {
    echo 'failed=' . (string) ($body['reason'] ?? 'unknown') . "\n";

    exit(0);
}

$ids = array_map(
    static fn (array $item) => (int) ($item['queue_id']),
    is_array($body['items'] ?? null) ? $body['items'] : [],
);
echo 'claimed=' . count($ids) . ':' . implode(',', $ids) . "\n";
