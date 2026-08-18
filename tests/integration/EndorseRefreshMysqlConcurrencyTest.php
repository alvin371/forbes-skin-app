<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/support/QueueSchema.php';
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
 * @internal
 */
#[Group('integration')]
final class EndorseRefreshMysqlConcurrencyTest extends TestCase
{
    private static ?PDO $pdo  = null;
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
            [$k, $v]             = array_pad(explode('=', $pair, 2), 2, '');
            self::$cfg[trim($k)] = trim($v);
        }
        $c         = self::$cfg;
        self::$pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['db']}", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        QueueSchema::build(self::$pdo);
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

    /**
     * Connection parts in the order atomic_claim_worker.php expects them.
     */
    private function claimWorkerDsn(): array
    {
        $c = self::$cfg;

        return [$c['host'], $c['port'], $c['user'], $c['pass'], $c['db']];
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
            $err     = stream_get_contents($pr['pipes'][2]);
            fclose($pr['pipes'][1]);
            fclose($pr['pipes'][2]);
            proc_close($pr['p']);
            $this->assertStringNotContainsString('Fatal error', $err, "worker stderr: {$err}");
        }

        return $out;
    }

    private function seedPending(int $n): void
    {
        QueueSchema::reset(self::$pdo);
        $now = (new DateTime())->format('Y-m-d H:i:s');
        self::$pdo->exec("INSERT INTO endorse_campaign (id, status) VALUES (100, 'Aktif')");
        $stmt = self::$pdo->prepare(
            'INSERT INTO endorse_refresh_queue (id_endorse, id_campaign, platform, link_upload, created_at)
             VALUES (?, 100, ?, ?, ?)',
        );
        $endorse = self::$pdo->prepare(
            "INSERT INTO endorse (id, id_campaign, platform, link_upload, total_cost, status, status_campaign,
                                  brand, influencer, views, likes, comment, share_save, is_fyp,
                                  pengajuan_payment_logs, task, logs)
             VALUES (?, 100, 'Tiktok', ?, 0, 'Aktif', 'Aktif', 'B1', '0', 0, 0, 0, 0, 0, '', '', '')",
        );

        for ($i = 0; $i < $n; $i++) {
            $url = 'https://www.tiktok.com/@c/video/' . (7500000000000000000 + $i);
            $endorse->execute([$i + 1, $url]);
            $stmt->execute([$i + 1, 'Tiktok', $url, $now]);
        }
    }

    private function sumGranted(array $out): int
    {
        $t = 0;

        foreach ($out as $line) {
            $this->assertMatchesRegularExpression('/granted=\d+/', $line);
            $t += (int) (explode('=', trim($line))[1]);
        }

        return $t;
    }

    public function testThreeWorkersNeverDoubleClaimSameRowUsingProductionSql(): void
    {
        $this->seedPending(90);
        $conn    = $this->claimWorkerDsn();
        $barrier = microtime(true) + 0.5;
        $out     = $this->runConcurrent('atomic_claim_worker.php', [
            array_merge($conn, ['w_A', 40, 60, $barrier]),
            array_merge($conn, ['w_B', 40, 60, $barrier]),
            array_merge($conn, ['w_C', 40, 60, $barrier]),
        ]);
        $claimedTotal = 0;

        foreach ($out as $line) {
            $this->assertMatchesRegularExpression('/claimed=\d+/', $line);
            $claimedTotal += (int) (explode('=', trim($line))[1]);
        }
        // Grouping the parent by its own primary key can only ever yield one worker, so
        // the real double-claim signal is on the attempt table: two workers allocating
        // against the same queue row both leave an open attempt behind.
        $dup = (int) self::$pdo->query("
            SELECT COUNT(*) FROM (
                SELECT queue_id FROM endorse_refresh_queue_attempts
                WHERE status='processing' GROUP BY queue_id HAVING COUNT(*) > 1
            ) t
        ")->fetchColumn();
        $this->assertSame(0, $dup, 'a queue row was claimed by more than one worker');
        $distinctAttempts = (int) self::$pdo->query('SELECT COUNT(DISTINCT queue_id, attempt_no) FROM endorse_refresh_queue_attempts')->fetchColumn();
        $totalAttempts    = (int) self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_queue_attempts')->fetchColumn();
        $this->assertSame($totalAttempts, $distinctAttempts, 'an attempt number was allocated twice for the same queue row');
        $this->assertLessThanOrEqual(90, $claimedTotal);
        $processing = (int) (self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'")->fetchColumn());
        $this->assertSame($processing, $claimedTotal, 'claimed count must equal processing rows (no loss/over-claim)');
        $orphan = (int) (self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing' AND worker_id IS NULL")->fetchColumn());
        $this->assertSame(0, $orphan);
        $withoutAttempt = (int) (self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue q LEFT JOIN endorse_refresh_queue_attempts a ON a.id=q.active_attempt_id AND a.status='processing' WHERE q.status='processing' AND a.id IS NULL")->fetchColumn());
        $this->assertSame(0, $withoutAttempt, 'every committed claim must own one active attempt');
        $attempts = (int) (self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE status='processing'")->fetchColumn());
        $this->assertSame($processing, $attempts);
    }

    public function testLegacyCounterMismatchAllocatesNextUniqueAttempt(): void
    {
        $this->seedPending(1);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts (queue_id,attempt_no,worker_id,status,started_at,finished_at,created_at) VALUES (1,1,'old','retrying',NOW(),NOW(),NOW())");
        $out = $this->runConcurrent('atomic_claim_worker.php', [array_merge($this->claimWorkerDsn(), ['w_new', 1, 60])]);
        $this->assertStringContainsString('claimed=1', $out[0]);
        $this->assertSame(2, (int) self::$pdo->query('SELECT attempt_sequence FROM endorse_refresh_queue WHERE id=1')->fetchColumn());
        $this->assertSame('1,2', self::$pdo->query('SELECT GROUP_CONCAT(attempt_no ORDER BY attempt_no) FROM endorse_refresh_queue_attempts WHERE queue_id=1')->fetchColumn());
    }

    /**
     * A crash between the attempt insert and the parent activation must never leave partial
     * state. Since poison-batch isolation was added the batch no longer fails outright: the
     * transaction still rolls back, then the row is retried on its own. Either way the
     * durable invariant is the same — never an orphan attempt, never a half-claimed parent.
     */
    public function testCrashAfterAttemptInsertNeverLeavesPartialState(): void
    {
        $this->seedPending(1);
        $out = $this->runConcurrent('atomic_claim_worker.php', [array_merge($this->claimWorkerDsn(), ['w_crash', 1, 60, 0, 'after_attempt_insert'])]);

        $status   = (string) self::$pdo->query('SELECT status FROM endorse_refresh_queue WHERE id=1')->fetchColumn();
        $attempts = (int) self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_queue_attempts')->fetchColumn();

        if (str_contains($out[0], 'rolled_back=1')) {
            // Whole claim abandoned: nothing may survive the rollback.
            $this->assertSame('pending', $status);
            $this->assertSame(0, $attempts);

            return;
        }

        // Isolation recovered the row: exactly one open attempt, owned by a processing parent.
        $this->assertStringContainsString('claimed=1', $out[0]);
        $this->assertSame('processing', $status);
        $this->assertSame(1, $attempts);
        $this->assertSame(
            0,
            (int) self::$pdo->query("
                SELECT COUNT(*) FROM endorse_refresh_queue_attempts a
                LEFT JOIN endorse_refresh_queue q
                  ON q.id = a.queue_id AND q.active_attempt_id = a.id AND q.status = 'processing'
                WHERE a.status = 'processing' AND q.id IS NULL
            ")->fetchColumn(),
            'no orphan attempt may survive a crash',
        );
    }

    public function testThreeWorkersCannotExceedScopedRollingLimit(): void
    {
        self::$pdo->exec('TRUNCATE endorse_refresh_rate_tokens');
        [$dsn, $u, $p] = $this->dsn();
        $scope         = EndorseRefreshRateScope::scope('rapidapi', 'key-A');
        $limit         = 50;
        $out           = $this->runConcurrent('reserve_worker.php', [
            [$dsn, $u, $p, $scope, $limit, 60, 100],
            [$dsn, $u, $p, $scope, $limit, 60, 100],
            [$dsn, $u, $p, $scope, $limit, 60, 100],
        ]);
        $granted  = $this->sumGranted($out);
        $inWindow = (int) (self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE provider_scope=' . self::$pdo->quote($scope) . ' AND created_at > (NOW(6) - INTERVAL 60 SECOND)')->fetchColumn());
        $this->assertLessThanOrEqual($limit, $inWindow, 'scoped rolling window exceeded the limit');
        $this->assertLessThanOrEqual($limit, $granted);
        $this->assertGreaterThan(0, $granted);
    }

    public function testDifferentScopesDoNotContend(): void
    {
        self::$pdo->exec('TRUNCATE endorse_refresh_rate_tokens');
        [$dsn, $u, $p] = $this->dsn();
        $scopeForbes   = EndorseRefreshRateScope::scope('rapidapi', 'forbes-key');
        $scopeSec      = EndorseRefreshRateScope::scope('rapidapi', 'sec-forbes-key');
        $this->assertNotSame($scopeForbes, $scopeSec, 'separate keys must yield separate scopes');
        // Two apps with their OWN limit of 30 each, run concurrently. Each must get its full 30.
        $out = $this->runConcurrent('reserve_worker.php', [
            [$dsn, $u, $p, $scopeForbes, 30, 60, 60, 'prod', 'forbes'],
            [$dsn, $u, $p, $scopeSec, 30, 60, 60, 'prod', 'sec-forbes'],
        ]);
        $granted = $this->sumGranted($out);
        // Scopes are independent → up to 30+30 granted, and neither scope exceeds its own 30.
        $this->assertLessThanOrEqual(30, (int) (self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE provider_scope=' . self::$pdo->quote($scopeForbes))->fetchColumn()));
        $this->assertLessThanOrEqual(30, (int) (self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_rate_tokens WHERE provider_scope=' . self::$pdo->quote($scopeSec))->fetchColumn()));
        $this->assertGreaterThan(30, $granted, 'independent scopes should collectively exceed a single scope limit');
    }

    public function testDirectScrapeScopeIsSeparateFromRapidApi(): void
    {
        self::$pdo->exec('TRUNCATE endorse_refresh_rate_tokens');
        $direct = EndorseRefreshRateScope::scope(EndorseRefreshRateScope::PROVIDER_DIRECT);
        $rapid  = EndorseRefreshRateScope::scope('rapidapi', 'key-A');
        $this->assertSame('direct_scrape', $direct);
        $this->assertNotSame($direct, $rapid, 'direct scrape must not share the RapidAPI budget');
    }

    public function testRetentionPruneKeepsInWindowTokens(): void
    {
        self::$pdo->exec('TRUNCATE endorse_refresh_rate_tokens');
        $scope = 'rapidapi:keyA';
        // one fresh (in-window) token, one very old (expired) token
        self::$pdo->exec("INSERT INTO endorse_refresh_rate_tokens (provider_scope, created_at) VALUES ('{$scope}', NOW(6))");
        self::$pdo->exec("INSERT INTO endorse_refresh_rate_tokens (provider_scope, created_at) VALUES ('{$scope}', NOW(6) - INTERVAL 3600 SECOND)");
        [$dsn, $u, $p] = $this->dsn();
        $store         = new PdoReservationStore(new PDO($dsn, $u, $p, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]), 'test', 'forbes');
        $removed       = $store->pruneExpired(60, 60); // window 60s, grace 60s → cutoff 120s
        $this->assertSame(1, $removed, 'exactly the expired token is pruned');
        $remaining = (int) (self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_rate_tokens')->fetchColumn());
        $this->assertSame(1, $remaining, 'in-window token must survive prune');
    }
}
