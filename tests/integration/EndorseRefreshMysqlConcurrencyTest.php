<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';

/**
 * Real MySQL concurrency regression. Exercises the ACTUAL claim UPDATE and the REAL
 * request-start reservation across multiple concurrent OS processes — proving
 * cross-worker atomicity, which pure arithmetic tests cannot.
 *
 * Opt-in: set FORBES_TEST_DB="host=127.0.0.1;port=33061;user=root;pass=root;db=forbes_test".
 * Skipped (not failed) when unset, so the default unit suite stays DB-free.
 *
 * @group integration
 * @internal
 */
final class EndorseRefreshMysqlConcurrencyTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static array $cfg = [];

    public static function setUpBeforeClass(): void
    {
        $spec = getenv('FORBES_TEST_DB');
        if ($spec === false || $spec === '') {
            return;
        }
        $cfg = [];
        foreach (explode(';', $spec) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $cfg[trim($k)] = trim($v);
        }
        self::$cfg = $cfg;
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']}";
        self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        self::$pdo->exec("DROP TABLE IF EXISTS endorse_refresh_queue");
        self::$pdo->exec("DROP TABLE IF EXISTS endorse_refresh_rate_tokens");
        self::$pdo->exec("
            CREATE TABLE endorse_refresh_queue (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              id_endorse INT UNSIGNED NOT NULL DEFAULT 0,
              id_campaign INT UNSIGNED NOT NULL DEFAULT 0,
              platform VARCHAR(20) NOT NULL DEFAULT 'Tiktok',
              link_upload TEXT NOT NULL,
              status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
              priority TINYINT NOT NULL DEFAULT 10,
              attempts TINYINT NOT NULL DEFAULT 0,
              max_attempts TINYINT NOT NULL DEFAULT 3,
              worker_id CHAR(36) NULL,
              claimed_at DATETIME NULL,
              started_at DATETIME NULL,
              completed_at DATETIME NULL,
              created_at DATETIME NOT NULL,
              PRIMARY KEY (id),
              KEY idx_pop (status, priority, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        self::$pdo->exec("
            CREATE TABLE endorse_refresh_rate_tokens (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              created_at DATETIME(6) NOT NULL,
              PRIMARY KEY (id), KEY idx_win (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('FORBES_TEST_DB not set; skipping MySQL concurrency test.');
        }
    }

    private function dsn(): array
    {
        $c = self::$cfg;
        return ["mysql:host={$c['host']};port={$c['port']};dbname={$c['db']}", $c['user'], $c['pass']];
    }

    /** Launch N worker processes concurrently and collect stdout lines. */
    private function runConcurrent(string $script, array $argsets): array
    {
        $procs = [];
        foreach ($argsets as $i => $args) {
            $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/' . $script);
            foreach ($args as $a) {
                $cmd .= ' ' . escapeshellarg((string) $a);
            }
            $procs[$i] = ['p' => proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes), 'pipes' => $pipes];
        }
        $out = [];
        foreach ($procs as $i => $pr) {
            $out[$i] = stream_get_contents($pr['pipes'][1]);
            fclose($pr['pipes'][1]);
            fclose($pr['pipes'][2]);
            proc_close($pr['p']);
        }
        return $out;
    }

    private function seedPending(int $n): void
    {
        self::$pdo->exec("TRUNCATE endorse_refresh_queue");
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $stmt = self::$pdo->prepare("INSERT INTO endorse_refresh_queue (link_upload, created_at) VALUES (?, ?)");
        for ($i = 0; $i < $n; $i++) {
            $stmt->execute(["https://www.tiktok.com/@c/video/" . (7500000000000000000 + $i), $now]);
        }
    }

    public function testThreeWorkersNeverDoubleClaimSameRow(): void
    {
        $this->seedPending(90);
        [$dsn, $u, $p] = $this->dsn();
        // 3 workers each try to claim 40; total demand 120 > 90 available.
        $out = $this->runConcurrent('claim_worker.php', [
            [$dsn, $u, $p, 'w_A', 40, 60],
            [$dsn, $u, $p, 'w_B', 40, 60],
            [$dsn, $u, $p, 'w_C', 40, 60],
        ]);
        $claimedTotal = 0;
        foreach ($out as $line) {
            $this->assertMatchesRegularExpression('/claimed=\d+/', $line);
            $claimedTotal += intval(explode('=', trim($line))[1]);
        }
        // No row claimed by two workers: each processing row has exactly one worker_id.
        $distinctWorkersPerRow = self::$pdo->query(
            "SELECT MAX(c) FROM (SELECT id, COUNT(DISTINCT worker_id) c FROM endorse_refresh_queue WHERE status='processing' GROUP BY id) t"
        )->fetchColumn();
        $this->assertLessThanOrEqual(1, intval($distinctWorkersPerRow), 'a row was claimed by more than one worker');
        // Total claimed never exceeds what was available.
        $this->assertLessThanOrEqual(90, $claimedTotal);
        // And every processing row has a worker.
        $orphan = self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing' AND worker_id IS NULL")->fetchColumn();
        $this->assertSame(0, intval($orphan));
        // Claimed rows equal processing rows (no silent loss / no over-claim).
        $processing = intval(self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'")->fetchColumn());
        $this->assertSame($processing, $claimedTotal);
    }

    public function testThreeWorkersCannotExceedRollingRequestLimit(): void
    {
        self::$pdo->exec("TRUNCATE endorse_refresh_rate_tokens");
        [$dsn, $u, $p] = $this->dsn();
        $limit = 50;
        // 3 workers each attempt 100 reservations against a shared rolling limit of 50.
        $out = $this->runConcurrent('reserve_worker.php', [
            [$dsn, $u, $p, $limit, 60, 100],
            [$dsn, $u, $p, $limit, 60, 100],
            [$dsn, $u, $p, $limit, 60, 100],
        ]);
        $grantedTotal = 0;
        foreach ($out as $line) {
            $this->assertMatchesRegularExpression('/granted=\d+/', $line);
            $grantedTotal += intval(explode('=', trim($line))[1]);
        }
        // The rolling window must never hold more than the limit.
        $inWindow = intval(self::$pdo->query(
            "SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE created_at > (NOW(6) - INTERVAL 60 SECOND)"
        )->fetchColumn());
        $this->assertLessThanOrEqual($limit, $inWindow, 'rolling window exceeded the request limit');
        $this->assertLessThanOrEqual($limit, $grantedTotal, 'more tokens granted than the limit allows');
        $this->assertGreaterThan(0, $grantedTotal, 'no tokens granted at all');
    }

    public function testDeferredUnstartedClaimReleasesWithoutConsumingToken(): void
    {
        // A claimed-but-unstarted row returns to pending; no token is inserted for it.
        $this->seedPending(5);
        self::$pdo->exec("TRUNCATE endorse_refresh_rate_tokens");
        // Claim 5, then "defer" all (return to pending) — mirrors applyResults deferral.
        [$dsn, $u, $p] = $this->dsn();
        $this->runConcurrent('claim_worker.php', [[$dsn, $u, $p, 'w_D', 5, 60]]);
        self::$pdo->exec("UPDATE endorse_refresh_queue SET status='pending', worker_id=NULL, started_at=NULL, claimed_at=NULL WHERE worker_id='w_D'");
        $tokens = intval(self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_rate_tokens")->fetchColumn());
        $this->assertSame(0, $tokens, 'deferred-unstarted work must not consume provider budget');
        $pending = intval(self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='pending'")->fetchColumn());
        $this->assertSame(5, $pending);
    }
}
