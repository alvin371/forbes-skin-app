<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/support/FakeCi.php';
require_once __DIR__ . '/support/QueueSchema.php';
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
    private static ?PDO $pdo  = null;
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
        // Canonical schema: built by the real migration up() path, shared by every
        // integration class, so no test can prove an invariant against a schema
        // production does not run.
        self::$pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['db']}", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        QueueSchema::build(self::$pdo);

        $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], (int) ($c['port']));
        $m->query("SET SESSION sql_mode=''");
        self::$m = $m;
    }

    protected function setUp(): void
    {
        if (self::$m === null) {
            $this->markTestSkipped('FORBES_TEST_DB not set.');
        }
        QueueSchema::reset(self::$pdo);
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
        $now       = date('Y-m-d H:i:s');
        $attemptNo = $attempts + 1;
        self::$m->query("INSERT INTO endorse_refresh_queue (id, id_endorse, id_campaign, platform, link_upload, status, attempts, attempt_sequence, max_attempts, worker_id, claim_owner, started_at, claimed_at, lease_expires_at, created_at)
            VALUES ({$queueId}, 1, 100, 'Tiktok', 'https://x/video/1', 'processing', {$attempts}, {$attemptNo}, 3, 'w1', 'cron', '{$now}', '{$now}', DATE_ADD('{$now}', INTERVAL 3 MINUTE), '{$now}')");
        self::$m->query("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, worker_id, status, started_at, created_at)
            VALUES ({$queueId}, {$attemptNo}, 'w1', 'processing', '{$now}', '{$now}')");
        $attemptId = (int) self::$m->insert_id;
        self::$m->query("UPDATE endorse_refresh_queue SET active_attempt_id={$attemptId} WHERE id={$queueId}");
    }

    private function items(int $queueId = 1, int $attempts = 0): array
    {
        $attemptNo = $attempts + 1;
        $attemptId = (int) $this->col("SELECT active_attempt_id FROM endorse_refresh_queue WHERE id={$queueId}");

        return [[
            'queue_id'   => $queueId, 'id_endorse' => 1, 'attempts' => $attempts, 'max_attempts' => 3,
            'attempt_no' => $attemptNo, 'active_attempt_id' => $attemptId,
            'worker_id'  => 'w1', 'purpose' => 'daily', 'enqueued_by' => 0,
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

    public function testStuckRecoveryIsIdempotent(): void
    {
        $this->seedQueueRow();
        self::$m->query('UPDATE endorse_refresh_queue SET lease_expires_at=DATE_SUB(NOW(), INTERVAL 1 MINUTE), started_at=DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE id=1');
        $svc    = $this->makeService($this->conn());
        $first  = $svc->resetStuck(1);
        $second = $svc->resetStuck(1);

        $this->assertTrue($first['status']);
        $this->assertSame(1, $first['reset_count']);
        $this->assertSame(0, $second['reset_count']);
        $this->assertSame('pending', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('1', $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('timed_out', $this->col('SELECT status FROM endorse_refresh_queue_attempts WHERE queue_id=1 AND attempt_no=1'));
        $this->assertSame('1', $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=1'));
    }

    public function testStaleWorkerCannotCompleteAfterReassignment(): void
    {
        $this->seedQueueRow();
        $oldItem = $this->items()[0];
        self::$m->query('UPDATE endorse_refresh_queue SET lease_expires_at=DATE_SUB(NOW(), INTERVAL 1 MINUTE), started_at=DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE id=1');
        $svc = $this->makeService($this->conn());
        $svc->resetStuck(1);

        $now = date('Y-m-d H:i:s');
        self::$m->query("INSERT INTO endorse_refresh_queue_attempts (queue_id,attempt_no,worker_id,status,started_at,created_at) VALUES (1,2,'w2','processing','{$now}','{$now}')");
        $attemptId = (int) self::$m->insert_id;
        self::$m->query("UPDATE endorse_refresh_queue SET status='processing', worker_id='w2', claim_owner='cron', attempt_sequence=2, active_attempt_id={$attemptId}, started_at='{$now}', claimed_at='{$now}', lease_expires_at=DATE_ADD('{$now}', INTERVAL 3 MINUTE), next_attempt_at=NULL WHERE id=1");

        $stale = $svc->applyResults([$oldItem], [$this->response(500)]);
        $this->assertSame(1, $stale['conflicts']);
        $this->assertSame('0', $this->col('SELECT views FROM endorse WHERE id=1'));
        $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));

        $newItem                      = $oldItem;
        $newItem['attempts']          = 1;
        $newItem['attempt_no']        = 2;
        $newItem['active_attempt_id'] = $attemptId;
        $newItem['worker_id']         = 'w2';
        $fresh                        = $svc->applyResults([$newItem], [$this->response(1000)]);
        $this->assertSame(1, $fresh['completed']);
        $this->assertSame('1000', $this->col('SELECT views FROM endorse WHERE id=1'));
    }

    /**
     * A parent may point at an attempt that is no longer 'processing' (legacy rows, the
     * unfenced markQueueFailed/finalizeQueueAttempt writers, a partially applied repair).
     * Recovery must release that parent exactly once and must never manufacture a new
     * attempt row per invocation — otherwise the row is stuck forever, never exhausts,
     * and every cron minute and every worker poll appends another audit row.
     */
    public function testStuckRecoveryReleasesParentWhoseActiveAttemptIsNoLongerProcessing(): void
    {
        $now = date('Y-m-d H:i:s');
        self::$m->query("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, worker_id, status, started_at, finished_at, created_at)
            VALUES (1, 1, 'w1', 'retrying', '{$now}', '{$now}', '{$now}')");
        $attemptId = (int) self::$m->insert_id;
        self::$m->query("INSERT INTO endorse_refresh_queue (id, id_endorse, id_campaign, platform, link_upload, status, attempts, attempt_sequence, active_attempt_id, max_attempts, worker_id, claim_owner, started_at, claimed_at, lease_expires_at, created_at)
            VALUES (1, 1, 100, 'Tiktok', 'https://x/video/1', 'processing', 0, 1, {$attemptId}, 3, 'w1', 'cron', DATE_SUB(NOW(), INTERVAL 10 MINUTE), DATE_SUB(NOW(), INTERVAL 10 MINUTE), DATE_SUB(NOW(), INTERVAL 5 MINUTE), '{$now}')");

        $svc   = $this->makeService($this->conn());
        $first = $svc->resetStuck(1);

        $this->assertTrue($first['status']);
        $this->assertSame(1, $first['reset_count']);
        $this->assertSame('pending', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'), 'an expired claim must be released, not left processing');
        $this->assertSame('1', $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1'), 'the consumed attempt must be counted so the row can eventually exhaust');
        $this->assertSame('', $this->col('SELECT IFNULL(active_attempt_id, "") FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('retrying', $this->col('SELECT status FROM endorse_refresh_queue_attempts WHERE id=' . $attemptId), 'an already-closed attempt must not be relabelled');

        // Idempotent, and above all NOT a per-invocation attempt-row generator.
        $second = $svc->resetStuck(1);
        $this->assertSame(0, $second['reset_count']);
        $this->assertSame('1', $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=1'), 'recovery must not append an attempt row on every run');
    }

    /**
     * The lease deadline is compared against the database clock (resetStuck uses NOW(6)),
     * so it must be expressed in that clock. PHP runs in Asia/Jakarta (index.php) and the
     * V2 path used gmdate(); if the database clock differs from either, a claim that still
     * holds a full lease is fenced immediately and burns max_attempts within a few polls.
     */
    public function testFreshClaimSurvivesWhenDatabaseClockDiffersFromPhpClock(): void
    {
        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Jakarta');

        try {
            $now = date('Y-m-d H:i:s');
            self::$m->query("INSERT INTO endorse_refresh_queue (id, id_endorse, id_campaign, platform, link_upload, status, attempts, attempt_sequence, max_attempts, created_at)
                VALUES (1, 1, 100, 'Tiktok', 'https://www.tiktok.com/@c/video/7500000000000000001', 'pending', 0, 0, 3, '{$now}')");

            $conn = $this->conn();
            // A database whose clock is ahead of Asia/Jakarta. Any offset must be safe;
            // this direction is the one that silently expires a brand-new lease.
            $conn->query("SET SESSION time_zone='+13:00'");
            $svc = $this->makeService($conn);

            $claim = $svc->claimBatch(['limit' => 5, 'retry_base_seconds' => 60]);
            $this->assertTrue($claim['status'], 'claim must succeed');
            $this->assertSame(1, $claim['claimed']);
            $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));

            // The lease was just issued, so nothing is stale yet.
            $recovery = $svc->resetStuck(5);
            $this->assertTrue($recovery['status']);
            $this->assertSame(0, $recovery['reset_count'], 'a freshly issued lease must not be fenced by the database clock');
            $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
            $this->assertSame('0', $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1'), 'a live claim must not consume an attempt');
            $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue_attempts WHERE queue_id=1'));
        } finally {
            date_default_timezone_set($previousTimezone);
        }
    }
}
