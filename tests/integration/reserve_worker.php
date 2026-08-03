<?php

/**
 * One concurrent request-start reservation worker. Calls the REAL scoped
 * PdoReservationStore::reserve() in a tight loop, simulating a worker that keeps starting
 * outbound requests against one provider scope. Prints how many tokens it was granted.
 *
 * argv: dsn user pass scope limit window attempts [env] [app]
 * prints: granted=<n>
 */
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';

[$_, $dsn, $user, $pass, $scope, $limit, $window, $attempts, $env, $app] = array_pad($argv, 10, null);
$pdo                                                                     = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$store                                                                   = new PdoReservationStore($pdo, $env ?: 'test', $app ?: 'forbes');

$granted = 0;

for ($i = 0; $i < (int) $attempts; $i++) {
    if ($store->reserve((string) $scope, (int) $limit, (int) $window, ['run_id' => 'itest'])) {
        $granted++;
    }
    usleep(1000);
}
echo "granted={$granted}\n";
