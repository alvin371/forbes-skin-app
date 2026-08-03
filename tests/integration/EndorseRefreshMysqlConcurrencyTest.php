<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';

/**
 * Real MySQL concurrency regression. Exercises the ACTUAL shared claim SQL and the REAL
 * scoped reservation store across multiple concurrent OS processes — proving cross-worker
 * atomicity, scope isolation and bounded retention, which pure unit tests cannot.
 *
 * Opt-in locally via FORBES_TEST_DB; MANDATORY (not skipped) in CI when FORBES_REQUIRE_DB=1.
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
            if (getenv('FORBES_REQUIRE_DB') === '1') {
                self::fail('FORBES_REQUIRE_DB=1 but FORBES_TEST_DB is not set: MySQL integration suite must not be skipped in CI.');
            }
            return;
        }
        foreach (explode(';', $spec) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            self::$cfg[trim($k)] = trim($v);
        }
        $c = self::$cfg;
        self::$pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['db']}", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        self::runMigrations(self::$pdo);
    }

    /** Apply the real migrations against the disposable DB (queue + rate tokens). */
    private static function runMigrations(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS endorse_refresh_queue");
        $pdo->exec("
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
              worker_id CHAR(36) NULL, claimed_at DATETIME NULL, started_at DATETIME NULL,
              completed_at DATETIME NULL, created_at DATETIME NOT NULL,
              PRIMARY KEY (id), KEY idx_pop (status, priority, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        // The real reservation migration.
        $direction = 'down';
        require __DIR__ . '/../../migrations/20260803120000_create_endorse_refresh_rate_tokens.php';
        $direction = 'up';
        require __DIR__ . '/../../migrations/20260803120000_create_endorse_refresh_rate_tokens.php';
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
            $err = stream_get_contents($pr['pipes'][2]);
            fclose($pr['pipes'][1]);
            fclose($pr['pipes'][2]);
            proc_close($pr['p']);
            $this->assertStringNotContainsString('Fatal error', $err, "worker stderr: $err");
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

    private function sumGranted(array $out): int
    {
        $t = 0;
        foreach ($out as $line) {
            $this->assertMatchesRegularExpression('/granted=\d+/', $line);
            $t += intval(explode('=', trim($line))[1]);
        }
        return $t;
    }

    public function testThreeWorkersNeverDoubleClaimSameRowUsingProductionSql(): void
    {
        $this->seedPending(90);
        [$dsn, $u, $p] = $this->dsn();
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
        $dup = self::$pdo->query("SELECT MAX(c) FROM (SELECT id, COUNT(DISTINCT worker_id) c FROM endorse_refresh_queue WHERE status='processing' GROUP BY id) t")->fetchColumn();
        $this->assertLessThanOrEqual(1, intval($dup), 'a row was claimed by more than one worker');
        $this->assertLessThanOrEqual(90, $claimedTotal);
        $processing = intval(self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'")->fetchColumn());
        $this->assertSame($processing, $claimedTotal, 'claimed count must equal processing rows (no loss/over-claim)');
        $orphan = intval(self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing' AND worker_id IS NULL")->fetchColumn());
        $this->assertSame(0, $orphan);
    }

    public function testThreeWorkersCannotExceedScopedRollingLimit(): void
    {
        self::$pdo->exec("TRUNCATE endorse_refresh_rate_tokens");
        [$dsn, $u, $p] = $this->dsn();
        $scope = EndorseRefreshRateScope::scope('rapidapi', 'key-A');
        $limit = 50;
        $out = $this->runConcurrent('reserve_worker.php', [
            [$dsn, $u, $p, $scope, $limit, 60, 100],
            [$dsn, $u, $p, $scope, $limit, 60, 100],
            [$dsn, $u, $p, $scope, $limit, 60, 100],
        ]);
        $granted = $this->sumGranted($out);
        $inWindow = intval(self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE provider_scope=" . self::$pdo->quote($scope) . " AND created_at > (NOW(6) - INTERVAL 60 SECOND)")->fetchColumn());
        $this->assertLessThanOrEqual($limit, $inWindow, 'scoped rolling window exceeded the limit');
        $this->assertLessThanOrEqual($limit, $granted);
        $this->assertGreaterThan(0, $granted);
    }

    public function testDifferentScopesDoNotContend(): void
    {
        self::$pdo->exec("TRUNCATE endorse_refresh_rate_tokens");
        [$dsn, $u, $p] = $this->dsn();
        $scopeForbes = EndorseRefreshRateScope::scope('rapidapi', 'forbes-key');
        $scopeSec    = EndorseRefreshRateScope::scope('rapidapi', 'sec-forbes-key');
        $this->assertNotSame($scopeForbes, $scopeSec, 'separate keys must yield separate scopes');
        // Two apps with their OWN limit of 30 each, run concurrently. Each must get its full 30.
        $out = $this->runConcurrent('reserve_worker.php', [
            [$dsn, $u, $p, $scopeForbes, 30, 60, 60, 'prod', 'forbes'],
            [$dsn, $u, $p, $scopeSec,    30, 60, 60, 'prod', 'sec-forbes'],
        ]);
        $granted = $this->sumGranted($out);
        // Scopes are independent → up to 30+30 granted, and neither scope exceeds its own 30.
        $this->assertLessThanOrEqual(30, intval(self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE provider_scope=" . self::$pdo->quote($scopeForbes))->fetchColumn()));
        $this->assertLessThanOrEqual(30, intval(self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE provider_scope=" . self::$pdo->quote($scopeSec))->fetchColumn()));
        $this->assertGreaterThan(30, $granted, 'independent scopes should collectively exceed a single scope limit');
    }

    public function testDirectScrapeScopeIsSeparateFromRapidApi(): void
    {
        self::$pdo->exec("TRUNCATE endorse_refresh_rate_tokens");
        $direct = EndorseRefreshRateScope::scope(EndorseRefreshRateScope::PROVIDER_DIRECT);
        $rapid  = EndorseRefreshRateScope::scope('rapidapi', 'key-A');
        $this->assertSame('direct_scrape', $direct);
        $this->assertNotSame($direct, $rapid, 'direct scrape must not share the RapidAPI budget');
    }

    public function testRetentionPruneKeepsInWindowTokens(): void
    {
        self::$pdo->exec("TRUNCATE endorse_refresh_rate_tokens");
        $scope = 'rapidapi:keyA';
        // one fresh (in-window) token, one very old (expired) token
        self::$pdo->exec("INSERT INTO endorse_refresh_rate_tokens (provider_scope, created_at) VALUES ('$scope', NOW(6))");
        self::$pdo->exec("INSERT INTO endorse_refresh_rate_tokens (provider_scope, created_at) VALUES ('$scope', NOW(6) - INTERVAL 3600 SECOND)");
        [$dsn, $u, $p] = $this->dsn();
        $store = new PdoReservationStore(new PDO($dsn, $u, $p, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]), 'test', 'forbes');
        $removed = $store->pruneExpired(60, 60); // window 60s, grace 60s → cutoff 120s
        $this->assertSame(1, $removed, 'exactly the expired token is pruned');
        $remaining = intval(self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_rate_tokens")->fetchColumn());
        $this->assertSame(1, $remaining, 'in-window token must survive prune');
    }
}
