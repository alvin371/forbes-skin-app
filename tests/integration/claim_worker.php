<?php
/**
 * One concurrent claim worker. Builds the claim UPDATE from the SAME shared repository
 * that production `claimBatch()` uses (EndorseRefreshClaimRepository::buildClaimSql), so
 * the concurrency test can never drift from shipped SQL.
 *
 * argv: dsn user pass worker_id limit retry_base
 * prints: claimed=<n>
 */
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';

[$_, $dsn, $user, $pass, $wid, $limit, $base] = array_pad($argv, 7, null);
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$now = (new DateTime())->format('Y-m-d H:i:s');

$sql = EndorseRefreshClaimRepository::buildClaimSql((string) $wid, $now, intval($limit), intval($base ?: 60));
$affected = $pdo->exec($sql);
echo "claimed=" . intval($affected) . "\n";
