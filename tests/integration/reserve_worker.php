<?php
/**
 * One concurrent request-start reservation worker. Calls the REAL
 * EndorseRefreshQueueService::tryReserveToken() in a tight loop, simulating a worker
 * that keeps starting outbound requests. Prints how many tokens it was granted.
 *
 * argv: dsn user pass limit window attempts
 * prints: granted=<n>
 */
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';

[$_, $dsn, $user, $pass, $limit, $window, $attempts] = array_pad($argv, 7, null);
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$granted = 0;
for ($i = 0; $i < intval($attempts); $i++) {
    if (EndorseRefreshQueueService::tryReserveToken($pdo, intval($limit), intval($window))) {
        $granted++;
    }
    usleep(1000); // 1ms between attempts
}
echo "granted=$granted\n";
