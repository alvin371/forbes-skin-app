<?php
/**
 * Build the load-test schema.
 *
 * Deliberately reuses tests/integration/support/QueueSchema — the SAME canonical builder the
 * integration suite uses, which layers the real endorse/campaign schema dumps and then
 * executes the real production migration up() paths in filename order.
 *
 * The alternative (`migrations/run.php --pending`) does not work here and should not be made
 * to: this repo's migration set assumes a pre-existing business schema it does not itself
 * create, so a bare database fails on the first unrelated migration. More importantly, a
 * load test that measured a hand-written schema would be measuring query plans production
 * never runs — the indexes are the whole point.
 *
 * Usage (inside the load-test worker container):
 *   php tools/loadtest/schema.php [--reset]
 */

$root = dirname(__DIR__, 2);

require_once $root . '/tests/integration/support/QueueSchema.php';

$host = getenv('DB_HOSTNAME') ?: 'loadtest-mysql';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$name = getenv('DB_DATABASE') ?: '';

// Read .env when the process environment does not carry the values — env_helper.php prefers
// the file, and this script must agree with what the worker will actually use.
if ($name === '' && is_readable($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim(trim($v), "\"'");
        switch (trim($k)) {
            case 'DB_HOSTNAME': $host = $v ?: $host; break;
            case 'DB_PORT':     $port = $v ?: $port; break;
            case 'DB_USERNAME': $user = $v ?: $user; break;
            case 'DB_PASSWORD': $pass = $v; break;
            case 'DB_DATABASE': $name = $v ?: $name; break;
        }
    }
}

// Independent restatement of the guard's rule. This script runs OUTSIDE the worker (no
// CI bootstrap, so no EndorseRefreshLoadTestGuard), and it issues DROP TABLE — the one place
// where being wrong about which database this is would be unrecoverable.
if (substr($name, -9) !== '_loadtest') {
    fwrite(STDERR, "REFUSING: DB_DATABASE must end with _loadtest before this script will touch it.\n");
    exit(1);
}

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$reset = in_array('--reset', $argv, true);
if ($reset) {
    // Force a full rebuild rather than the intact-check fast path.
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "Dropped all tables in {$name}.\n";
}

QueueSchema::build($pdo);

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'Schema ready: ' . count($tables) . " tables in {$name}.\n";

// Fail loudly if the ledger columns are missing: every throughput and amplification number
// the run produces is read out of them, so a silent absence would invalidate the whole test.
$ledgerColumns = $pdo->query('SHOW COLUMNS FROM `endorse_refresh_rate_tokens`')->fetchAll(PDO::FETCH_COLUMN);
foreach (['leg', 'ok', 'http_code', 'total_time_ms', 'error_class', 'finished_at', 'test_run_id'] as $column) {
    if (!in_array($column, $ledgerColumns, true)) {
        fwrite(STDERR, "Ledger column missing: {$column}. Migration 20260820120000 did not apply.\n");
        exit(1);
    }
}
echo "Ledger columns verified.\n";
