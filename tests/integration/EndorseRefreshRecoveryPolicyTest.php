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
 * Scheduling-clock contract (PL-01) and state-aware stuck recovery (PL-02), against the
 * canonical migrated schema and across INDEPENDENT database connections — a claim and its
 * recovery never share a session in production.
 *
 * @internal
 */
#[Group('integration')]
final class EndorseRefreshRecoveryPolicyTest extends TestCase
{
    private static ?PDO $pdo  = null;
    private static array $cfg = [];

    /**
     * @var list<mysqli>
     */
    private array $openConnections = [];

    public static function setUpBeforeClass(): void
    {
        $spec = getenv('FORBES_TEST_DB');
        if ($spec === false || $spec === '') {
            if (getenv('FORBES_REQUIRE_DB') === '1') {
                self::fail('FORBES_REQUIRE_DB=1 but FORBES_TEST_DB is not set: the MySQL suite must fail, never skip.');
            }

            return;
        }

        foreach (explode(';', $spec) as $pair) {
            [$k, $v]             = array_pad(explode('=', $pair, 2), 2, '');
            self::$cfg[trim($k)] = trim($v);
        }
        $c         = self::$cfg;
        self::$pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['db']}",
            $c['user'],
            $c['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        QueueSchema::build(self::$pdo);
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('FORBES_TEST_DB not set.');
        }
        QueueSchema::reset(self::$pdo);
        self::$pdo->exec("INSERT INTO endorse_campaign (id, status) VALUES (100, 'Aktif')");
        self::$pdo->exec("INSERT INTO endorse (id, id_campaign, platform, link_upload, total_cost, status, status_campaign, brand, influencer, views, likes, comment, share_save, is_fyp, pengajuan_payment_logs, task, logs)
            VALUES (1, 100, 'Tiktok', 'https://www.tiktok.com/@c/video/7500000000000000001', 0, 'Aktif', 'Aktif', 'B1', '0', 0,0,0,0,0,'','','')");
    }

    protected function tearDown(): void
    {
        foreach ($this->openConnections as $conn) {
            $conn->close();
        }
        $this->openConnections = [];
    }

    /**
     * An independent connection, optionally pinned to a specific session timezone.
     */
    private function conn(?string $timezone = null): mysqli
    {
        $c = self::$cfg;
        $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], (int) $c['port']);
        $m->query("SET SESSION sql_mode=''");
        if ($timezone !== null) {
            $m->query("SET SESSION time_zone='{$timezone}'");
        }
        $this->openConnections[] = $m;

        return $m;
    }

    private function service(mysqli $conn): EndorseRefreshQueueService
    {
        $ci                   = new FakeCi($conn);
        $GLOBALS['__fake_ci'] = $ci;
        $ci->endorse_sync     = new Endorse_sync();

        return new EndorseRefreshQueueService();
    }

    private function col(string $sql): string
    {
        return (string) (self::$pdo->query($sql)->fetch(PDO::FETCH_NUM)[0] ?? '');
    }

    /**
     * Read a scalar through a specific connection. DATETIME columns carry no timezone, so
     * any comparison against NOW(6) is only meaningful on a session using the same zone as
     * the session that wrote the value.
     */
    private function colOn(mysqli $conn, string $sql): string
    {
        $result = $conn->query($sql);

        return (string) ($result ? ($result->fetch_row()[0] ?? '') : '');
    }

    private function seedPending(): void
    {
        self::$pdo->exec("INSERT INTO endorse_refresh_queue
            (id, id_endorse, id_campaign, platform, link_upload, status, attempts, attempt_sequence, max_attempts, created_at)
            VALUES (1, 1, 100, 'Tiktok', 'https://www.tiktok.com/@c/video/7500000000000000001', 'pending', 0, 0, 3, NOW())");
    }

    /**
     * Seed a processing parent whose active attempt is in $attemptStatus.
     * $leaseOffsetSeconds < 0 makes the lease already expired.
     */
    private function seedProcessing(string $attemptStatus, int $leaseOffsetSeconds, int $attempts = 0): int
    {
        // Real history, not just a counter: `attempts` prior consumed attempts actually
        // exist as closed rows, which is the only state the production writers can produce.
        for ($i = 1; $i <= $attempts; $i++) {
            self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts
                (queue_id, attempt_no, worker_id, status, started_at, finished_at, created_at)
                VALUES (1, {$i}, 'w_old', 'failed', NOW(), NOW(), NOW())");
        }
        $activeAttemptNo = $attempts + 1;
        self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts
            (queue_id, attempt_no, worker_id, status, started_at, created_at)
            VALUES (1, {$activeAttemptNo}, 'w1', " . self::$pdo->quote($attemptStatus) . ', NOW(), NOW())');
        $attemptId = (int) self::$pdo->lastInsertId();
        $sign      = $leaseOffsetSeconds < 0 ? 'DATE_SUB' : 'DATE_ADD';
        $abs       = abs($leaseOffsetSeconds);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue
            (id, id_endorse, id_campaign, platform, link_upload, status, attempts, attempt_sequence,
             active_attempt_id, max_attempts, worker_id, claim_owner, started_at, claimed_at, lease_expires_at, created_at)
            VALUES (1, 1, 100, 'Tiktok', 'https://www.tiktok.com/@c/video/7500000000000000001', 'processing',
             {$attempts}, {$activeAttemptNo}, {$attemptId}, 3, 'w1', 'cron', DATE_SUB(NOW(), INTERVAL 10 MINUTE),
             DATE_SUB(NOW(), INTERVAL 10 MINUTE), {$sign}(NOW(6), INTERVAL {$abs} SECOND), NOW())");

        return $attemptId;
    }

    // ---------------------------------------------------------------------
    // PL-01 — one scheduling clock, proven across independent connections.
    // ---------------------------------------------------------------------

    /**
     * Production topology: the claim and the recovery run in different PHP processes on
     * different connections. A lease issued moments ago must survive recovery.
     */
    public function testFreshClaimSurvivesRecoveryOnAnIndependentConnection(): void
    {
        $this->seedPending();
        $claim = $this->service($this->conn())->claimBatch(['limit' => 5, 'retry_base_seconds' => 60]);
        $this->assertTrue($claim['status']);
        $this->assertSame(1, $claim['claimed']);

        // A DIFFERENT connection, and a different service instance, runs recovery.
        $recovery = $this->service($this->conn())->resetStuck(5);
        $this->assertTrue($recovery['status']);
        $this->assertSame(0, $recovery['reset_count'], 'a live lease must never be fenced');
        $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('0', $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1'));
    }

    /**
     * The contract must hold for ANY uniform session timezone, not just the server default:
     * a deployment that pins its connections to +07:00 or -05:00 behaves identically.
     */
    public function testSchedulingIsCorrectUnderUtcSession(): void
    {
        $this->assertSchedulingIsCorrectUnderSessionTimezone('+00:00');
    }

    public function testSchedulingIsCorrectUnderJakartaSession(): void
    {
        $this->assertSchedulingIsCorrectUnderSessionTimezone('+07:00');
    }

    public function testSchedulingIsCorrectUnderFarEastSession(): void
    {
        $this->assertSchedulingIsCorrectUnderSessionTimezone('+13:00');
    }

    public function testSchedulingIsCorrectUnderFarWestSession(): void
    {
        $this->assertSchedulingIsCorrectUnderSessionTimezone('-05:00');
    }

    private function assertSchedulingIsCorrectUnderSessionTimezone(string $timezone): void
    {
        $previousPhpTimezone = date_default_timezone_get();
        // PHP's own clock deliberately differs from the database session in every case.
        date_default_timezone_set('Asia/Jakarta');

        try {
            $this->seedPending();
            $claimConnection = $this->conn($timezone);
            $claim           = $this->service($claimConnection)->claimBatch(['limit' => 5, 'retry_base_seconds' => 60]);
            $this->assertTrue($claim['status']);
            $this->assertSame(1, $claim['claimed']);

            // A DIFFERENT connection, same pinned zone — the production topology.
            $recovery = $this->service($this->conn($timezone))->resetStuck(5);
            $this->assertSame(0, $recovery['reset_count'], "fresh lease fenced under session tz {$timezone}");
            $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));

            // Measured on a session using the deployment's zone, because DATETIME stores no
            // offset. The lease must be in the future by roughly the configured window and
            // never hours out — hours is the signature of disagreeing clock authorities.
            $skewSeconds = (int) $this->colOn(
                $this->conn($timezone),
                'SELECT TIMESTAMPDIFF(SECOND, NOW(6), lease_expires_at) FROM endorse_refresh_queue WHERE id=1',
            );
            $this->assertGreaterThan(0, $skewSeconds, 'lease must be in the future');
            $this->assertLessThanOrEqual(900, $skewSeconds, 'lease must not be hours away');
        } finally {
            date_default_timezone_set($previousPhpTimezone);
        }
    }

    /**
     * Cron parity with the V2 case: an activation that matches nothing must never be
     * reported as a claim. The item would otherwise reach a provider request for a row the
     * worker does not own.
     */
    public function testCronZeroRowActivationIsNeverReportedAsAClaim(): void
    {
        $this->seedPending();
        $conn = $this->conn();
        $svc  = $this->service($conn);
        // Persistent, so the batch attempt AND the per-row isolation retry both lose the
        // race — otherwise isolation legitimately recovers a one-off failure.
        $GLOBALS['__fake_ci']->db->forceZeroAffectedOn('endorse_refresh_queue', 10);

        $claim = $svc->claimBatch(['limit' => 5, 'retry_base_seconds' => 60]);

        $this->assertSame(0, $claim['claimed'], 'a lost activation race must not count as a claim');
        $this->assertSame([], $claim['items']);
        $this->assertSame('pending', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame(
            '0',
            $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE status="processing"'),
            'a failed activation must not leave an open attempt behind',
        );
    }

    /**
     * An actually expired lease must still be recovered — the fix must not disable fencing.
     */
    public function testExpiredClaimIsRecoveredOnAnIndependentConnection(): void
    {
        $this->seedProcessing('processing', -60);
        $recovery = $this->service($this->conn())->resetStuck(1);

        $this->assertSame(1, $recovery['reset_count']);
        $this->assertSame('pending', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('timed_out', $this->col('SELECT status FROM endorse_refresh_queue_attempts WHERE queue_id=1'));
    }

    /**
     * The retry deadline is computed by the database, so it must land near the intended
     * backoff — never seven hours early or late, which is the signature of a clock mismatch.
     */
    public function testRetryDeadlineIsNeverHoursAwayFromTheDatabaseClock(): void
    {
        $this->seedProcessing('processing', -60);
        $this->service($this->conn('+07:00'))->resetStuck(1);

        $delta = (int) $this->colOn(
            $this->conn('+07:00'),
            'SELECT TIMESTAMPDIFF(SECOND, NOW(6), next_attempt_at) FROM endorse_refresh_queue WHERE id=1',
        );
        $this->assertGreaterThan(0, $delta, 'retry must be scheduled in the future');
        $this->assertLessThan(3600, $delta, 'retry deadline drifted by hours — clock authorities disagree');
    }

    /**
     * Documents the boundary this batch does NOT fix, so it cannot be mistaken for solved.
     *
     * MySQL DATETIME stores no offset and NOW(6) is session-relative, so a deployment whose
     * connections disagree about `time_zone` writes and compares different literals. The
     * scheduling contract is therefore "one uniform session zone", and enforcing it needs a
     * pinned connection zone — which would reinterpret legacy rows and belongs to the
     * migration/reconciliation batch.
     */
    public function testMixedSessionTimezonesRemainUnsafeAndAreNotSilentlyMasked(): void
    {
        $this->seedPending();
        $this->service($this->conn('+07:00'))->claimBatch(['limit' => 5, 'retry_base_seconds' => 60]);

        $skewOnWriterZone = (int) $this->colOn(
            $this->conn('+07:00'),
            'SELECT TIMESTAMPDIFF(SECOND, NOW(6), lease_expires_at) FROM endorse_refresh_queue WHERE id=1',
        );
        $skewOnOtherZone = (int) $this->colOn(
            $this->conn('+00:00'),
            'SELECT TIMESTAMPDIFF(SECOND, NOW(6), lease_expires_at) FROM endorse_refresh_queue WHERE id=1',
        );

        $this->assertGreaterThan(0, $skewOnWriterZone, 'the lease is valid in the zone that wrote it');
        // Reading the same literal from a session 7h behind makes the lease look 7h longer.
        $this->assertSame(
            -7 * 3600,
            $skewOnWriterZone - $skewOnOtherZone,
            'a session-zone difference shifts DATETIME comparisons by exactly that offset — '
            . 'connections must share one zone until the reconciliation batch pins it',
        );
    }

    /**
     * next_attempt_at eligibility must mean the same thing to the cron claim SELECT and the
     * V2 claim SELECT. Both are executed here against the same row and must agree.
     */
    public function testCronAndV2AgreeOnNextAttemptEligibility(): void
    {
        $this->seedPending();
        // Scheduled 60s into the future by the database clock: neither path may claim it.
        self::$pdo->exec('UPDATE endorse_refresh_queue SET next_attempt_at = DATE_ADD(NOW(6), INTERVAL 60 SECOND) WHERE id=1');

        $cronEligible = (int) $this->col(
            'SELECT COUNT(*) FROM (' . str_replace(
                'FOR UPDATE SKIP LOCKED',
                '',
                EndorseRefreshClaimRepository::buildSelectForUpdateSql(10, 60),
            ) . ') eligible',
        );
        $v2Eligible = (int) $this->col("
            SELECT COUNT(*) FROM endorse_refresh_queue
            WHERE status='pending' AND platform!='Threads' AND worker_id IS NULL
              AND attempts < max_attempts
              AND (next_attempt_at IS NULL OR next_attempt_at <= NOW(6))
        ");
        $this->assertSame(0, $cronEligible, 'cron claimed a row whose backoff has not elapsed');
        $this->assertSame($cronEligible, $v2Eligible, 'cron and V2 disagree on next_attempt_at eligibility');

        // Once the backoff has elapsed, both must agree it is claimable.
        self::$pdo->exec('UPDATE endorse_refresh_queue SET next_attempt_at = DATE_SUB(NOW(6), INTERVAL 1 SECOND) WHERE id=1');
        $cronEligible = (int) $this->col(
            'SELECT COUNT(*) FROM (' . str_replace(
                'FOR UPDATE SKIP LOCKED',
                '',
                EndorseRefreshClaimRepository::buildSelectForUpdateSql(10, 60),
            ) . ') eligible',
        );
        $v2Eligible = (int) $this->col("
            SELECT COUNT(*) FROM endorse_refresh_queue
            WHERE status='pending' AND platform!='Threads' AND worker_id IS NULL
              AND attempts < max_attempts
              AND (next_attempt_at IS NULL OR next_attempt_at <= NOW(6))
        ");
        $this->assertSame(1, $cronEligible);
        $this->assertSame($cronEligible, $v2Eligible);
    }

    // ---------------------------------------------------------------------
    // PL-02 — recovery decides per active-attempt state.
    // ---------------------------------------------------------------------

    public function testValidLeaseIsLeftAlone(): void
    {
        $this->seedProcessing('processing', 300);
        $recovery = $this->service($this->conn())->resetStuck(1);

        $this->assertSame(0, $recovery['reset_count']);
        $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue_attempts WHERE queue_id=1'));
    }

    /**
     * A completed attempt means the provider call succeeded AND its business write committed
     * in the same transaction. Recovery must never re-queue that for another provider request.
     */
    public function testCompletedAttemptIsReportedInconsistentAndNeverRetried(): void
    {
        $this->seedProcessing('completed', -60);
        $recovery = $this->service($this->conn())->resetStuck(1);

        $this->assertTrue($recovery['status']);
        $this->assertSame(0, $recovery['reset_count'], 'a completed attempt must not be recovered');
        $this->assertSame(1, $recovery['inconsistent_count'], 'the ambiguous row must be observable');
        $this->assertSame('completed', $recovery['inconsistent'][0]['attempt_status']);
        $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('1', $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=1'));
    }

    public function testDanglingActiveAttemptPointerIsReportedInconsistent(): void
    {
        $this->seedProcessing('processing', -60);
        self::$pdo->exec('UPDATE endorse_refresh_queue SET active_attempt_id = 987654 WHERE id=1');

        $recovery = $this->service($this->conn())->resetStuck(1);
        $this->assertSame(0, $recovery['reset_count']);
        $this->assertSame(1, $recovery['inconsistent_count']);
        $this->assertSame('missing', $recovery['inconsistent'][0]['attempt_status']);
        $this->assertSame('processing', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
    }

    /**
     * A cancelled allocation never started a provider request, so it costs no attempt.
     */
    public function testCancelledAttemptReleasesWithoutCountingProviderConsumption(): void
    {
        $this->seedProcessing('cancelled', -60);
        $recovery = $this->service($this->conn())->resetStuck(1);

        $this->assertSame(1, $recovery['reset_count']);
        $this->assertSame('pending', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('0', $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1'), 'cancelled work must not consume an attempt');
        $this->assertSame('1', $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=1'));
    }

    public function testFailedAttemptReleasesParentWithoutCreatingAnotherTimeout(): void
    {
        $this->assertClosedAttemptReleasesParent('failed');
    }

    public function testTimedOutAttemptReleasesParentWithoutCreatingAnotherTimeout(): void
    {
        $this->assertClosedAttemptReleasesParent('timed_out');
    }

    public function testRetryingAttemptReleasesParentWithoutCreatingAnotherTimeout(): void
    {
        $this->assertClosedAttemptReleasesParent('retrying');
    }

    private function assertClosedAttemptReleasesParent(string $attemptStatus): void
    {
        $this->seedProcessing($attemptStatus, -60);
        $recovery = $this->service($this->conn())->resetStuck(1);

        $this->assertSame(1, $recovery['reset_count']);
        $this->assertSame('pending', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('1', $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1'), 'the closed attempt counts exactly once');
        $this->assertSame('1', $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=1'), 'no second timeout row');
        $this->assertSame($attemptStatus, $this->col('SELECT status FROM endorse_refresh_queue_attempts WHERE queue_id=1'), 'a closed attempt must not be relabelled');
    }

    /**
     * A parent with no attempt row at all gets exactly one explicitly reconciled record.
     */
    public function testMissingActiveAttemptSynthesizesOneReconciledTimeout(): void
    {
        self::$pdo->exec("INSERT INTO endorse_refresh_queue
            (id, id_endorse, id_campaign, platform, link_upload, status, attempts, attempt_sequence,
             active_attempt_id, max_attempts, worker_id, claim_owner, started_at, claimed_at, lease_expires_at, created_at)
            VALUES (1, 1, 100, 'Tiktok', 'https://x/video/1', 'processing', 0, 0, NULL, 3, 'w1', 'cron',
             DATE_SUB(NOW(), INTERVAL 10 MINUTE), DATE_SUB(NOW(), INTERVAL 10 MINUTE), DATE_SUB(NOW(6), INTERVAL 60 SECOND), NOW())");

        $recovery = $this->service($this->conn())->resetStuck(1);
        $this->assertSame(1, $recovery['reset_count']);
        $this->assertSame('1', $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=1'));
        $this->assertSame('timed_out', $this->col('SELECT status FROM endorse_refresh_queue_attempts WHERE queue_id=1'));
        // Provenance: never presented as a normal provider attempt.
        $this->assertStringContainsString(
            'internal_reconciled',
            $this->col('SELECT error_message FROM endorse_refresh_queue_attempts WHERE queue_id=1'),
        );
        $this->assertSame('1', $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1'));
    }

    /**
     * Repeated recovery must not charge the same attempt twice or grow the audit table.
     */
    public function testRepeatedRecoveryIsIdempotent(): void
    {
        $this->seedProcessing('processing', -60);
        $svc = $this->service($this->conn());

        $first = $svc->resetStuck(1);
        $this->assertSame(1, $first['reset_count']);
        $attemptsAfterFirst = $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1');
        $rowsAfterFirst     = $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=1');

        $second = $svc->resetStuck(1);
        $this->assertSame(0, $second['reset_count']);
        $this->assertSame($attemptsAfterFirst, $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame($rowsAfterFirst, $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=1'));
    }

    /**
     * Recovery on the last permitted attempt moves the row to the terminal state.
     */
    public function testExhaustedRetriesBecomeTerminalFailure(): void
    {
        $this->seedProcessing('processing', -60, 2);
        $recovery = $this->service($this->conn())->resetStuck(1);

        $this->assertSame(1, $recovery['reset_count']);
        $this->assertSame(1, $recovery['failed_count']);
        $this->assertSame('failed', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('', $this->col('SELECT IFNULL(next_attempt_at, "") FROM endorse_refresh_queue WHERE id=1'));
    }

    /**
     * Recovery and a concurrent result submission both target the same claim. Exactly one
     * may win, and the loser must not produce a second business effect.
     */
    public function testRecoveryAndResultSubmissionHaveOneWinner(): void
    {
        $attemptId = $this->seedProcessing('processing', -60);
        $item      = [
            'queue_id'   => 1, 'id_endorse' => 1, 'attempts' => 0, 'max_attempts' => 3,
            'attempt_no' => 1, 'active_attempt_id' => $attemptId,
            'worker_id'  => 'w1', 'purpose' => 'daily', 'enqueued_by' => 0,
        ];
        $response = [
            'status'       => true, 'msg' => '',
            'data'         => ['like' => 100, 'comment' => 5, 'share' => 1, 'collect' => 1, 'view' => 1000],
            'stats_fields' => ['like', 'comment', 'share', 'collect', 'view'],
            'observed_at'  => '2026-08-19 10:00:00.000000',
        ];

        // Recovery wins first; the late result must then be fenced out entirely.
        $this->service($this->conn())->resetStuck(1);
        $applied = $this->service($this->conn())->applyResults([$item], [$response]);

        $this->assertSame(1, $applied['conflicts'], 'the superseded worker must lose');
        $this->assertSame(0, $applied['completed']);
        $this->assertSame('0', $this->col('SELECT views FROM endorse WHERE id=1'), 'a fenced worker must not write business data');
        $this->assertSame('pending', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
    }
}
