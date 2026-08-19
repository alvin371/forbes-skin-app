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
require_once __DIR__ . '/../../application/libraries/EndorseRefreshDrainRunner.php';

/**
 * End-to-end drain test against real MySQL with a deterministic fake provider. Drives the
 * REAL claim SQL, the REAL scoped reservation store, and the drain runner's loop; asserts
 * final DB state, token accounting and stop reasons — not helper return values alone.
 *
 * @internal
 */
#[Group('integration')]
final class EndorseRefreshDrainE2ETest extends TestCase
{
    private static ?PDO $pdo  = null;
    private static array $cfg = [];

    public static function setUpBeforeClass(): void
    {
        $spec = getenv('FORBES_TEST_DB');
        if ($spec === false || $spec === '') {
            if (getenv('FORBES_REQUIRE_DB') === '1') {
                self::fail('FORBES_REQUIRE_DB=1 but FORBES_TEST_DB unset: e2e suite must not skip in CI.');
            }

            return;
        }

        foreach (explode(';', $spec) as $pair) {
            [$k, $v]             = array_pad(explode('=', $pair, 2), 2, '');
            self::$cfg[trim($k)] = trim($v);
        }
        $c = self::$cfg;
        // This suite drives a behaviour SIMULATOR table (it carries a `behavior` column the
        // production schema has no concept of), so it runs in its own database rather than
        // replacing the canonical queue table other suites depend on.
        $database  = QueueSchema::isolatedDatabase($c, 'drain');
        self::$pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$database}", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        self::$pdo->exec('DROP TABLE IF EXISTS endorse_refresh_queue');
        self::$pdo->exec("
            CREATE TABLE endorse_refresh_queue (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT, id_endorse INT UNSIGNED NOT NULL DEFAULT 0,
              platform VARCHAR(20) NOT NULL DEFAULT 'Tiktok', link_upload TEXT NOT NULL,
              behavior VARCHAR(32) NOT NULL DEFAULT 'success',
              status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
              priority TINYINT NOT NULL DEFAULT 10, attempts TINYINT NOT NULL DEFAULT 0,
              max_attempts TINYINT NOT NULL DEFAULT 3, worker_id CHAR(36) NULL,
              claimed_at DATETIME NULL, started_at DATETIME NULL, completed_at DATETIME NULL,
              created_at DATETIME NOT NULL, PRIMARY KEY (id), KEY idx_pop (status, priority, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $pdo       = self::$pdo;
        $direction = 'up';
        // The rate-token table is built from the REAL migrations, in order, so this suite
        // exercises the same reservation/ledger schema production runs. Requiring only the
        // CREATE would leave the ledger columns absent and let reserveToken() silently take
        // its degraded path — the suite would then pass while testing the wrong thing.
        require __DIR__ . '/../../migrations/20260803120000_create_endorse_refresh_rate_tokens.php';
        require __DIR__ . '/../../migrations/20260820120000_extend_endorse_refresh_rate_tokens_ledger.php';
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('FORBES_TEST_DB not set.');
        }
        self::$pdo->exec('TRUNCATE endorse_refresh_queue');
        self::$pdo->exec('TRUNCATE endorse_refresh_rate_tokens');
    }

    /**
     * @param array<string,int> $byBehavior behavior => count
     */
    private function seed(array $byBehavior): void
    {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $st  = self::$pdo->prepare('INSERT INTO endorse_refresh_queue (link_upload, behavior, created_at) VALUES (?, ?, ?)');
        $i   = 0;

        foreach ($byBehavior as $behavior => $count) {
            for ($k = 0; $k < $count; $k++) {
                $st->execute(['https://www.tiktok.com/@c/video/' . (7500000000000000000 + $i++), $behavior, $now]);
            }
        }
    }

    /**
     * Build runner collaborators wired to real MySQL + a deterministic fake provider.
     */
    private function makeRunner(array $cfg, string $worker = 'w_e2e'): EndorseRefreshDrainRunner
    {
        $pdo         = self::$pdo;
        $store       = new PdoReservationStore($pdo, 'test', 'forbes');
        $attemptSeen = []; // queue_id => attempts so far (for retry-then-success behavior)
        $clock       = ['t' => 0.0];

        $claim = static function (int $n) use ($pdo, $worker) {
            // apply()/release() always null worker_id, so processing-for-worker rows are exactly
            // the ones this UPDATE just claimed — no dedup bookkeeping needed.
            $now = (new DateTime())->format('Y-m-d H:i:s');
            $pdo->exec(EndorseRefreshClaimRepository::buildClaimSql($worker, $now, $n, 60));
            $rows = $pdo->query('SELECT id, behavior FROM endorse_refresh_queue WHERE worker_id=' . $pdo->quote($worker) . " AND status='processing'")->fetchAll(PDO::FETCH_ASSOC);
            $out  = [];

            foreach ($rows as $r) {
                $out[] = ['queue_id' => (int) ($r['id']), 'behavior' => $r['behavior'], 'scope' => EndorseRefreshRateScope::scope('rapidapi', 'key-A')];
            }

            return $out;
        };

        $fetch = static function (array $item, int $attempt) use (&$attemptSeen, &$clock, $cfg) {
            $clock['t'] += (float) ($cfg['latency'] ?? 1.0);
            $b = $item['behavior'];
            if ($b === 'success') {
                return ['ok' => true, 'http' => 200, 'path' => 'rapidapi'];
            }
            if ($b === 'terminal') {
                return ['ok' => false, 'error_class' => Endorse_sync::ERR_PERMANENT, 'http' => 400];
            }
            if ($b === 'retry_then_success') {
                return $attempt >= 2
                    ? ['ok' => true, 'http' => 200, 'path' => 'rapidapi']
                    : ['ok' => false, 'error_class' => Endorse_sync::ERR_TRANSIENT, 'http' => 500];
            }
            if ($b === 'always_transient') {
                return ['ok' => false, 'error_class' => Endorse_sync::ERR_TRANSIENT, 'http' => 429];
            }

            return ['ok' => true, 'http' => 200, 'path' => 'rapidapi'];
        };

        $apply = static function (array $item, array $resp) use ($pdo) {
            if (! empty($resp['ok'])) {
                $pdo->prepare("UPDATE endorse_refresh_queue SET status='completed', worker_id=NULL, completed_at=NOW() WHERE id=?")->execute([$item['queue_id']]);
            } elseif (Endorse_sync::is_terminal_class($resp['error_class'] ?? '')) {
                $pdo->prepare("UPDATE endorse_refresh_queue SET status='failed', worker_id=NULL, completed_at=NOW() WHERE id=?")->execute([$item['queue_id']]);
            } else {
                $pdo->prepare("UPDATE endorse_refresh_queue SET status='pending', worker_id=NULL, started_at=NULL, attempts=attempts+1 WHERE id=?")->execute([$item['queue_id']]);
            }
        };

        $release = static function (array $items) use ($pdo) {
            foreach ($items as $it) {
                $pdo->prepare("UPDATE endorse_refresh_queue SET status='pending', worker_id=NULL, started_at=NULL, claimed_at=NULL WHERE id=?")->execute([$it['queue_id']]);
            }
        };

        $now   = static function () use (&$clock) { return $clock['t']; };
        $sleep = static function ($s) use (&$clock) { $clock['t'] += $s; };

        return new EndorseRefreshDrainRunner(array_merge([
            'claim'   => $claim, 'fetch' => $fetch, 'apply' => $apply, 'release' => $release,
            'reserve' => static fn (string $scope) => $store->reserve($scope, (int) ($cfg['rate'] ?? 1000), 60, ['run_id' => 'e2e']),
            'now'     => $now, 'sleep' => $sleep,
        ], $cfg['runner'] ?? []));
    }

    public function testMultiChunkDrainCompletesAllSuccesses(): void
    {
        $this->seed(['success' => 45]);
        $runner = $this->makeRunner([
            'latency' => 1.0, 'rate' => 1000,
            'runner'  => ['deadline_sec' => 1000.0, 'chunk_size' => 10, 'per_request_timeout_sec' => 2.0, 'limiter_mode' => 'request_start_reservation'],
        ]);
        $t = $runner->run();
        $this->assertGreaterThan(1, $t['chunks'], 'must drain multiple chunks in one run');
        $completed = (int) (self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='completed'")->fetchColumn());
        $this->assertSame(45, $completed, 'all rows completed in DB');
        $this->assertSame(45, $t['unique_completed']);
        // one success = one token; no retries.
        $tokens = (int) (self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_rate_tokens')->fetchColumn());
        $this->assertSame(45, $tokens, 'one started request = one token');
        $stuck = (int) (self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'")->fetchColumn());
        $this->assertSame(0, $stuck, 'no row left processing');
    }

    public function testRetryConsumesAnAdditionalToken(): void
    {
        $this->seed(['retry_then_success' => 3]);
        $runner = $this->makeRunner([
            'latency' => 1.0, 'rate' => 1000,
            'runner'  => ['deadline_sec' => 1000.0, 'chunk_size' => 10, 'per_request_timeout_sec' => 2.0, 'inline_retry' => true, 'max_attempts' => 3, 'limiter_mode' => 'request_start_reservation'],
        ]);
        $t = $runner->run();
        $this->assertSame(3, $t['unique_completed']);
        $this->assertSame(3, $t['retries'], 'each item retried exactly once');
        // 3 items x 2 attempts = 6 tokens (one per request AND per retry).
        $tokens = (int) (self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_rate_tokens')->fetchColumn());
        $this->assertSame(6, $tokens);
    }

    public function testRateExhaustionStopsWithoutReclaimChurn(): void
    {
        $this->seed(['success' => 20]);
        $runner = $this->makeRunner([
            'latency' => 1.0, 'rate' => 5, // only 5 tokens available in the window
            'runner'  => ['deadline_sec' => 1000.0, 'chunk_size' => 10, 'per_request_timeout_sec' => 2.0, 'limiter_mode' => 'request_start_reservation'],
        ]);
        $t = $runner->run();
        $this->assertSame('rate_limit_exhausted', $t['stop_reason']);
        $this->assertLessThanOrEqual(5, $t['requests_started'], 'never exceed the rate budget');
        $tokens = (int) (self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_rate_tokens')->fetchColumn());
        $this->assertLessThanOrEqual(5, $tokens);
        // Unstarted rows returned to pending (no permanent processing, no in-run reclaim loop).
        $stuck = (int) (self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'")->fetchColumn());
        $this->assertSame(0, $stuck);
        $completed = (int) (self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='completed'")->fetchColumn());
        $this->assertLessThanOrEqual(5, $completed);
    }

    public function testDeadlineStopReleasesUnstartedToPending(): void
    {
        $this->seed(['success' => 30]);
        $runner = $this->makeRunner([
            'latency' => 5.0, 'rate' => 1000,
            // deadline 12s, latency 5s, per-request 6s → only ~1-2 requests before deadline.
            'runner' => ['deadline_sec' => 12.0, 'chunk_size' => 10, 'per_request_timeout_sec' => 6.0, 'limiter_mode' => 'request_start_reservation'],
        ]);
        $t = $runner->run();
        $this->assertContains($t['stop_reason'], ['deadline', 'queue_empty']);
        $stuck = (int) (self::$pdo->query("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'")->fetchColumn());
        $this->assertSame(0, $stuck, 'no row stranded in processing at deadline');
        $tokens = (int) (self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_rate_tokens')->fetchColumn());
        $this->assertSame($t['requests_started'], $tokens, 'tokens equal actual started requests');
    }

    public function testEmptyQueueStopsImmediately(): void
    {
        $runner = $this->makeRunner(['latency' => 1.0, 'rate' => 1000, 'runner' => ['deadline_sec' => 100.0, 'chunk_size' => 10]]);
        $t      = $runner->run();
        $this->assertSame('queue_empty', $t['stop_reason']);
        $this->assertSame(0, $t['chunks']);
        $this->assertSame(0, $t['requests_started']);
    }
}
