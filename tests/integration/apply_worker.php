<?php
/**
 * One concurrent apply worker: runs the REAL Endorse_sync::apply() for a single observation
 * against the shared endorse row. Blocks on a wall-clock barrier so multiple workers hit the
 * atomic UPDATE simultaneously, reliably creating the out-of-order race.
 *
 * argv: dsn user pass id_endorse views likes observed_at barrier_epoch_micros
 * prints: applied=1|0 (affected-row outcome of the atomic guard)
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

[$_, $dsn, $user, $pass, $id, $views, $likes, $observedAt, $barrier] = array_pad($argv, 9, null);
// dsn = mysql:host=..;port=..;dbname=.. → parse host/port/db for mysqli
preg_match('/host=([^;]+)/', $dsn, $h);
preg_match('/port=([^;]+)/', $dsn, $p);
preg_match('/dbname=([^;]+)/', $dsn, $d);
$m = new mysqli($h[1], $user, $pass, $d[1], intval($p[1]));
$m->query("SET SESSION sql_mode=''");
$GLOBALS['__fake_ci'] = new FakeCi($m);

$endorse = $m->query("SELECT * FROM endorse WHERE id=" . intval($id))->fetch_assoc();
$response = [
    'status'       => true,
    'msg'          => '',
    'data'         => ['like' => intval($likes), 'comment' => 5, 'share' => 1, 'collect' => 1, 'view' => intval($views)],
    'stats_fields' => ['like', 'comment', 'share', 'collect', 'view'],
    'observed_at'  => (string) $observedAt,
    'stats_source' => 'race',
];

// Spin-wait to the shared barrier so all workers fire the UPDATE together.
$target = floatval($barrier);
while (microtime(true) < $target) {
    usleep(200);
}

$sync = new Endorse_sync();
$r = $sync->apply($endorse, $response, 1);
echo 'applied=' . (($r['msg'] ?? '') === 'OK' ? '1' : '0') . "\n";
