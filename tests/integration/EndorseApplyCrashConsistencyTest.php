<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/support/FakeCi.php';
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';

/**
 * Crash/transaction consistency through the REAL EndorseRefreshQueueService::applyResults()
 * and the REAL Endorse_sync::apply(), against the REAL endorse schema. A simulated crash is
 * injected between side effects; after retry we assert the newest stats, exactly one log, a
 * completed-once queue row, correct attempt state, and no duplicated business effect.
 *
 * @internal
 */
#[Group('integration')]
final class EndorseApplyCrashConsistencyTest extends TestCase
{
    private static ?mysqli $m = null;
    private static array $cfg = [];

    public static function setUpBeforeClass(): void
    {
        $spec = getenv('FORBES_TEST_DB');
        if ($spec === false || $spec === '') {
            if (getenv('FORBES_REQUIRE_DB') === '1') {
                self::fail('FORBES_REQUIRE_DB=1 but FORBES_TEST_DB unset.');
            }

            return;
        }

        foreach (explode(';', $spec) as $p) {
            [$k, $v]             = array_pad(explode('=', $p, 2), 2, '');
            self::$cfg[trim($k)] = trim($v);
        }
        $c = self::$cfg;
        $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], (int) ($c['port']));
        $m->query("SET SESSION sql_mode=''");

        foreach (['endorse', 'endorse_logs', 'endorse_campaign', 'endorse_campaign_logs', 'endorse_refresh_queue', 'endorse_refresh_queue_attempts'] as $t) {
            $m->query("DROP TABLE IF EXISTS `{$t}`");
        }
        self::loadSql($m, file_get_contents(__DIR__ . '/schema/endorse_real_schema.sql'));
        self::loadSql($m, file_get_contents(__DIR__ . '/schema/campaign_real_schema.sql'));
        $pdo       = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['db']}", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $direction = 'up';
        require __DIR__ . '/../../migrations/20260803130000_add_stats_observation_seq.php';
        // Queue table with the full column set applyResults/finalize use (base migration
        // predates worker_id/claimed_at/purpose; create it complete here).
        $m->query("CREATE TABLE endorse_refresh_queue (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, id_endorse INT UNSIGNED NOT NULL,
            id_campaign INT UNSIGNED NOT NULL, platform VARCHAR(20) NOT NULL, link_upload TEXT NOT NULL,
            purpose VARCHAR(20) NOT NULL DEFAULT 'daily',
            status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
            priority TINYINT NOT NULL DEFAULT 10, attempts TINYINT NOT NULL DEFAULT 0,
            max_attempts TINYINT NOT NULL DEFAULT 3, error_message TEXT NULL, enqueued_by INT UNSIGNED NULL,
            worker_id CHAR(36) NULL, claimed_at DATETIME NULL, started_at DATETIME NULL,
            completed_at DATETIME NULL, created_at DATETIME NOT NULL,
            PRIMARY KEY(id), KEY idx_pop (status, priority, created_at)) ENGINE=InnoDB");
        // Minimal attempts table (columns applyResults/finalizeQueueAttempt use).
        $m->query("CREATE TABLE endorse_refresh_queue_attempts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, queue_id INT UNSIGNED NOT NULL,
            attempt_no TINYINT NOT NULL, worker_id CHAR(36) NULL,
            status ENUM('processing','submitted','retrying','completed','failed','cancelled') NOT NULL DEFAULT 'processing',
            error_class VARCHAR(32) NULL, error_message TEXT NULL,
            started_at DATETIME NULL, finished_at DATETIME NULL, created_at DATETIME NOT NULL,
            PRIMARY KEY(id), KEY idx_q (queue_id, attempt_no)) ENGINE=InnoDB");
        self::$m = $m;
    }

    private static function loadSql(mysqli $m, string $ddl): void
    {
        foreach (array_filter(array_map('trim', explode(";\n", $ddl))) as $stmt) {
            if ($stmt !== '' && $m->query($stmt) === false) {
                self::fail('schema load failed: ' . $m->error . ' :: ' . substr($stmt, 0, 60));
            }
        }
    }

    protected function setUp(): void
    {
        if (self::$m === null) {
            $this->markTestSkipped('FORBES_TEST_DB not set.');
        }

        foreach (['endorse', 'endorse_logs', 'endorse_campaign', 'endorse_campaign_logs', 'endorse_refresh_queue', 'endorse_refresh_queue_attempts'] as $t) {
            self::$m->query("TRUNCATE `{$t}`");
        }
        self::$m->query("INSERT INTO endorse (id, id_campaign, platform, link_upload, total_cost, status, status_campaign, brand, influencer, views, likes, comment, share_save, is_fyp, pengajuan_payment_logs, task, logs)
            VALUES (1, 100, 'Tiktok', 'https://www.tiktok.com/@c/video/7500000000000000001', 0, 'Aktif', 'Aktif', 'B1', '0', 0,0,0,0,0,'','','')");
        self::$m->query("INSERT INTO endorse_campaign (id, status) VALUES (100, 'Aktif')");
    }

    /**
     * Build the real service on a FakeCi bound to a fresh connection, with a live Endorse_sync.
     */
    private function makeService(mysqli $conn): EndorseRefreshQueueService
    {
        $ci                   = new FakeCi($conn);
        $GLOBALS['__fake_ci'] = $ci;
        $ci->endorse_sync     = new Endorse_sync();

        return new EndorseRefreshQueueService();
    }

    private function conn(): mysqli
    {
        $c = self::$cfg;
        $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], (int) ($c['port']));
        $m->query("SET SESSION sql_mode=''");

        return $m;
    }

    private function seedQueueRow(int $queueId = 1, int $attempts = 0): void
    {
        $now = date('Y-m-d H:i:s');
        self::$m->query("INSERT INTO endorse_refresh_queue (id, id_endorse, id_campaign, platform, link_upload, status, attempts, max_attempts, worker_id, started_at, created_at)
            VALUES ({$queueId}, 1, 100, 'Tiktok', 'https://x/video/1', 'processing', {$attempts}, 3, 'w1', '{$now}', '{$now}')");
        self::$m->query("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, worker_id, status, started_at, created_at)
            VALUES ({$queueId}, " . ($attempts + 1) . ", 'w1', 'processing', '{$now}', '{$now}')");
    }

    private function items(int $queueId = 1, int $attempts = 0): array
    {
        return [[
            'queue_id'  => $queueId, 'id_endorse' => 1, 'attempts' => $attempts, 'max_attempts' => 3,
            'worker_id' => 'w1', 'purpose' => 'daily', 'enqueued_by' => 0,
        ]];
    }

    private function response(int $views): array
    {
        return [
            'status'       => true, 'msg' => '',
            'data'         => ['like' => 100, 'comment' => 5, 'share' => 1, 'collect' => 1, 'view' => $views],
            'stats_fields' => ['like', 'comment', 'share', 'collect', 'view'],
            'observed_at'  => '2026-08-03 10:00:00.000000',
        ];
    }

    private function col(string $sql): string
    {
        return (string) (self::$m->query($sql)->fetch_row()[0] ?? '');
    }

    public function testAllTransactionalWritesUseOneConnection(): void
    {
        // The transaction is only valid if the service's queue writes AND Endorse_sync's
        // endorse/log writes travel on the SAME CI db connection. Both resolve to
        // $this->CI->db (the service sets $this->db = $this->CI->db), so a single connection
        // id must serve every write in the item.
        $conn       = $this->conn();
        $svc        = $this->makeService($conn);
        $ci         = $GLOBALS['__fake_ci'];
        $viaService = (int) ($ci->db->m->query('SELECT CONNECTION_ID()')->fetch_row()[0]);
        // Endorse_sync uses get_instance()->db === $ci->db (same mysqli) for all its writes.
        $syncCi  = (fn () => $this->CI)->call($ci->endorse_sync);
        $viaSync = (int) ($syncCi->db->m->query('SELECT CONNECTION_ID()')->fetch_row()[0]);
        $this->assertSame($viaService, $viaSync, 'service and Endorse_sync must share one connection');
        $this->assertSame($ci->db, $syncCi->db, 'both must reference the same CI db object');
    }

    public function testHappyPathCompletesOnce(): void
    {
        $this->seedQueueRow();
        $svc = $this->makeService($this->conn());
        $out = $svc->applyResults($this->items(), [$this->response(1000)]);
        $this->assertSame(1, $out['completed']);
        $this->assertSame('completed', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('1000', $this->col('SELECT views FROM endorse WHERE id=1'));
        $this->assertSame('1', $this->col('SELECT COUNT(*) FROM endorse_logs WHERE id_endorse=1'));
        $this->assertSame('completed', $this->col('SELECT status FROM endorse_refresh_queue_attempts WHERE queue_id=1 AND attempt_no=1'));
    }

    public function testCrashBeforeEndorseUpdateRollsBackThenRecovers(): void
    {
        $this->assertCrashRollsBackThenRetryRecovers('endorse');
    }

    public function testCrashBeforeLogInsertRollsBackThenRecovers(): void
    {
        $this->assertCrashRollsBackThenRetryRecovers('endorse_logs');
    }

    public function testCrashBeforeQueueCompletionRollsBackThenRecovers(): void
    {
        $this->assertCrashRollsBackThenRetryRecovers('endorse_refresh_queue');
    }

    private function assertCrashRollsBackThenRetryRecovers(string $crashTable): void
    {
        $this->seedQueueRow();

        // Attempt 1: inject a crash before writing $crashTable → the whole item rolls back.
        $ciConn               = $this->conn();
        $ci                   = new FakeCi($ciConn);
        $GLOBALS['__fake_ci'] = $ci;
        $ci->endorse_sync     = new Endorse_sync();
        $ci->db->crashBeforeWriteTo($crashTable);
        $svc = new EndorseRefreshQueueService();
        $out = $svc->applyResults($this->items(), [$this->response(1000)]);
        $this->assertSame(1, $out['exceptioned'], "crash before {$crashTable} must be caught and rolled back");

        // Rollback consistency: NO partial business state, queue still processing (recoverable).
        $this->assertSame('0', $this->col('SELECT COUNT(*) FROM endorse_logs WHERE id_endorse=1'), "no log after rollback ({$crashTable})");
        $this->assertSame('0', $this->col('SELECT views FROM endorse WHERE id=1'), "endorse not written after rollback ({$crashTable})");
        $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'), "queue stays processing for recovery ({$crashTable})");
        $this->assertNull(self::$m->query('SELECT completed_at FROM endorse_refresh_queue WHERE id=1')->fetch_assoc()['completed_at'], "not completed ({$crashTable})");

        // Retry (fresh worker, no crash): must recover to a correct, completed-once state.
        $svc2 = $this->makeService($this->conn());
        $out2 = $svc2->applyResults($this->items(1, 0), [$this->response(1000)]);
        $this->assertSame(1, $out2['completed'], "retry completes ({$crashTable})");
        $this->assertSame('1000', $this->col('SELECT views FROM endorse WHERE id=1'), "newest stats after recovery ({$crashTable})");
        $this->assertSame('1', $this->col('SELECT COUNT(*) FROM endorse_logs WHERE id_endorse=1'), "exactly one log after recovery ({$crashTable})");
        $this->assertSame('completed', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'), "queue completed once ({$crashTable})");
        $this->assertSame('0', $this->col("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'"), "no stuck processing row ({$crashTable})");
    }

    public function testStaleRecoveryDoesNotDuplicateWhenApplyAlreadyCommitted(): void
    {
        // Apply commits fully (queue completed). A racing stale-recovery pass then runs. It must
        // not resurrect a completed row or create a second log / second completion.
        $this->seedQueueRow();
        $svc = $this->makeService($this->conn());
        $svc->applyResults($this->items(), [$this->response(1000)]);
        $this->assertSame('completed', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));

        $svc2 = $this->makeService($this->conn());
        $svc2->resetStuck(0); // aggressive: treat everything as stale
        $this->assertSame('completed', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'), 'stale recovery must not reopen a completed row');
        $this->assertSame('1', $this->col('SELECT COUNT(*) FROM endorse_logs WHERE id_endorse=1'), 'no duplicate log from stale recovery');
    }
}
