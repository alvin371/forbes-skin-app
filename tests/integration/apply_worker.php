<?php

/**
 * One concurrent apply worker: runs the REAL Endorse_sync::apply() for a single observation
 * against the shared endorse row, carrying a stable LOGICAL observation sequence. Blocks on a
 * wall-clock barrier so workers hit the atomic UPDATE simultaneously (creating the race).
 *
 * argv: dsn user pass id_endorse views likes observation_seq barrier_epoch_micros
 * prints: outcome=<applied_newer|duplicate|stale|contract_error|...>
 */
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
if (! function_exists('env')) {
    function env($k, $d = null)
    {
        $v = getenv($k);

        return $v !== false ? $v : $d;
    }
}
require_once __DIR__ . '/support/FakeCi.php';
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';

[$_, $dsn, $user, $pass, $id, $views, $likes, $seq, $barrier] = array_pad($argv, 9, null);
preg_match('/host=([^;]+)/', $dsn, $h);
preg_match('/port=([^;]+)/', $dsn, $p);
preg_match('/dbname=([^;]+)/', $dsn, $d);
$m = new mysqli($h[1], $user, $pass, $d[1], (int) ($p[1]));
$m->query("SET SESSION sql_mode=''");
$GLOBALS['__fake_ci'] = new FakeCi($m);

$endorse  = $m->query('SELECT * FROM endorse WHERE id=' . (int) $id)->fetch_assoc();
$response = [
    'status'          => true,
    'msg'             => '',
    'data'            => ['like' => (int) $likes, 'comment' => 5, 'share' => 1, 'collect' => 1, 'view' => (int) $views],
    'stats_fields'    => ['like', 'comment', 'share', 'collect', 'view'],
    'observed_at'     => '2026-08-03 10:00:00.000000',
    'observation_seq' => (int) $seq,
    'stats_source'    => 'race',
];

$target = (float) $barrier;

while (microtime(true) < $target) {
    usleep(200);
}

$sync = new Endorse_sync();
$r    = $sync->apply($endorse, $response, 1);
echo 'outcome=' . ($r['outcome'] ?? 'none') . "\n";
