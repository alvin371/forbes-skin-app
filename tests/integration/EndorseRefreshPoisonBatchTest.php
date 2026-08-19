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
 * PL-04 — one structurally inconsistent row must not cost the whole batch.
 *
 * The poison row here is a legacy inconsistency that really occurs: the parent is pending
 * and unowned, but an orphaned `processing` attempt row still exists for it. Allocating a
 * new attempt collides with `uq_active_queue_attempt`, which under a single-transaction
 * batch claim rolls back every healthy row with it.
 *
 * @internal
 */
#[Group('integration')]
final class EndorseRefreshPoisonBatchTest extends TestCase
{
    private const HEALTHY_ROWS    = 19;
    private const POISON_QUEUE_ID = 20;

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
        $this->seedBatchWithOnePoisonRow();
    }

    protected function tearDown(): void
    {
        foreach ($this->openConnections as $conn) {
            $conn->close();
        }
        $this->openConnections = [];
    }

    private function conn(): mysqli
    {
        $c = self::$cfg;
        $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], (int) $c['port']);
        $m->query("SET SESSION sql_mode=''");
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

    private function seedBatchWithOnePoisonRow(): void
    {
        self::$pdo->exec("INSERT INTO endorse_campaign (id, status) VALUES (100, 'Aktif')");
        $endorse = self::$pdo->prepare(
            "INSERT INTO endorse (id, id_campaign, platform, link_upload, total_cost, status, status_campaign,
                                  brand, influencer, views, likes, comment, share_save, is_fyp,
                                  pengajuan_payment_logs, task, logs)
             VALUES (?, 100, 'Tiktok', ?, 0, 'Aktif', 'Aktif', 'B1', '0', 0, 0, 0, 0, 0, '', '', '')",
        );
        $queue = self::$pdo->prepare(
            "INSERT INTO endorse_refresh_queue (id, id_endorse, id_campaign, platform, link_upload, status, created_at)
             VALUES (?, ?, 100, 'Tiktok', ?, 'pending', NOW())",
        );

        for ($id = 1; $id <= self::POISON_QUEUE_ID; $id++) {
            $url = 'https://www.tiktok.com/@c/video/' . (7500000000000000000 + $id);
            $endorse->execute([$id, $url]);
            $queue->execute([$id, $id, $url]);
        }

        // The poison: an orphaned OPEN attempt for a parent that is pending and unowned.
        // Any new allocation for this queue violates uq_active_queue_attempt.
        self::$pdo->exec('INSERT INTO endorse_refresh_queue_attempts
            (queue_id, attempt_no, worker_id, status, started_at, created_at)
            VALUES (' . self::POISON_QUEUE_ID . ", 1, 'ghost', 'processing', NOW(), NOW())");
    }

    public function testHealthyRowsAreClaimedDespiteOnePoisonRow(): void
    {
        $claim = $this->service($this->conn())->claimBatch(['limit' => 50, 'retry_base_seconds' => 60]);

        $this->assertTrue($claim['status'], 'a single poison row must not fail the whole claim');
        $this->assertSame(self::HEALTHY_ROWS, $claim['claimed'], 'every healthy row must still be claimed');
        $this->assertCount(self::HEALTHY_ROWS, $claim['items']);

        $claimedIds = array_map(static fn (array $item) => (int) $item['queue_id'], $claim['items']);
        $this->assertNotContains(self::POISON_QUEUE_ID, $claimedIds, 'the poison row must never be handed to a worker');

        $processing = (int) $this->col("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'");
        $this->assertSame(self::HEALTHY_ROWS, $processing, 'claimed count must equal processing parents');
    }

    public function testPoisonRowIsIdentifiedAndDiagnosable(): void
    {
        $claim = $this->service($this->conn())->claimBatch(['limit' => 50, 'retry_base_seconds' => 60]);

        $this->assertSame(1, $claim['poison_count'] ?? 0, 'the poison row must be reported, not silently dropped');
        $poison = $claim['poison'][0];
        $this->assertSame(self::POISON_QUEUE_ID, (int) $poison['queue_id']);
        // Stable, secret-free classification.
        $this->assertSame('attempt_identity_conflict', $poison['reason']);

        $errorMessage = $this->col('SELECT error_message FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID);
        $this->assertStringContainsString('attempt_identity_conflict', $errorMessage);
    }

    /**
     * The poison row is deferred, never completed, failed-by-fiat, or given fake history.
     */
    public function testPoisonRowIsDeferredWithoutFabricatedHistory(): void
    {
        $this->service($this->conn())->claimBatch(['limit' => 50, 'retry_base_seconds' => 60]);

        $this->assertSame(
            'pending',
            $this->col('SELECT status FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID),
            'a structurally broken row must not be reported as completed or failed',
        );
        $this->assertSame(
            '0',
            $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID),
            'isolation must not consume a provider attempt',
        );
        // Only the pre-existing ghost attempt: isolation fabricates nothing.
        $this->assertSame(
            '1',
            $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=' . self::POISON_QUEUE_ID),
        );
        $this->assertSame(
            'ghost',
            $this->col('SELECT worker_id FROM endorse_refresh_queue_attempts WHERE queue_id=' . self::POISON_QUEUE_ID),
        );
    }

    /**
     * A second poll must neither spin on the poison row nor let it block the queue: it is
     * held off by its own backoff while healthy work keeps flowing.
     */
    public function testRepeatPollDoesNotSpinOnThePoisonRow(): void
    {
        $first = $this->service($this->conn())->claimBatch(['limit' => 50, 'retry_base_seconds' => 60]);
        $this->assertSame(self::HEALTHY_ROWS, $first['claimed']);

        $second = $this->service($this->conn())->claimBatch(['limit' => 50, 'retry_base_seconds' => 60]);
        $this->assertTrue($second['status']);
        $this->assertSame(0, $second['claimed'], 'nothing is eligible yet');
        $this->assertSame(0, $second['poison_count'] ?? 0, 'the deferred row must not be re-attempted immediately');
        $this->assertSame(
            '1',
            $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=' . self::POISON_QUEUE_ID),
            'repeated polls must not grow attempt history for the poison row',
        );

        // The deferral is bounded — the row becomes eligible again later, so a transient
        // cause self-heals instead of quarantining the row forever.
        $backoffSeconds = (int) $this->col(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(6), next_attempt_at) FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID,
        );
        $this->assertGreaterThan(0, $backoffSeconds);
        $this->assertLessThanOrEqual(3600, $backoffSeconds, 'deferral must be bounded, not permanent');
    }

    /**
     * Drive the queue the way a real deployment does: poll, let the deferral window
     * elapse, poll again. A bounded BACKOFF is not a bounded RETRY COUNT — without a
     * terminal condition this loop runs for the life of the row, once per window,
     * forever.
     */
    private function pollPastBackoff(int $polls): array
    {
        $observed = [];

        for ($i = 0; $i < $polls; $i++) {
            $claim      = $this->service($this->conn())->claimBatch(['limit' => 50, 'retry_base_seconds' => 60]);
            $observed[] = [
                'poison'  => $claim['poison_count'] ?? 0,
                'status'  => $this->col('SELECT status FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID),
                'backoff' => (int) $this->col(
                    'SELECT IFNULL(TIMESTAMPDIFF(SECOND, NOW(6), next_attempt_at), -1) FROM endorse_refresh_queue WHERE id='
                    . self::POISON_QUEUE_ID,
                ),
            ];
            // Simulate the deferral window elapsing, so the next poll sees the row
            // as eligible again exactly as production would after the backoff.
            self::$pdo->exec(
                'UPDATE endorse_refresh_queue SET next_attempt_at = NULL WHERE id = ' . self::POISON_QUEUE_ID
                . " AND status = 'pending'",
            );
        }

        return $observed;
    }

    public function testPoisonRowReachesATerminalStateInsteadOfRetryingForever(): void
    {
        $observed = $this->pollPastBackoff(12);

        $finalStatus = $this->col('SELECT status FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID);
        $this->assertSame(
            'failed',
            $finalStatus,
            'a deterministically poisoned row must stop being retried and become terminal',
        );

        // Terminal means terminal: it is no longer eligible for any future poll.
        $this->assertSame(
            '',
            $this->col('SELECT IFNULL(next_attempt_at, "") FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID),
            'a terminal row must not carry a future retry schedule',
        );
        $after = $this->service($this->conn())->claimBatch(['limit' => 50, 'retry_base_seconds' => 60]);
        $this->assertSame(0, $after['poison_count'] ?? 0, 'a terminal row must never be isolated again');

        // The whole point of terminating is to stop the spin: isolation must have
        // happened a bounded number of times, not once per poll.
        $isolations = array_sum(array_column($observed, 'poison'));
        $this->assertLessThan(12, $isolations, 'isolation must be bounded, not once per poll');
    }

    /**
     * Termination must not be bought with fabricated provider history.
     */
    public function testTerminalPoisonRowStillHasNoFabricatedAttempts(): void
    {
        $this->pollPastBackoff(12);

        $this->assertSame(
            '0',
            $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID),
            'no provider request ever started, so no attempt may be consumed',
        );
        $this->assertSame(
            '1',
            $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts WHERE queue_id=' . self::POISON_QUEUE_ID),
            'only the pre-existing ghost attempt may exist',
        );
        // Stable, secret-free, and explicit that a human has to act.
        $message = $this->col('SELECT error_message FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID);
        $this->assertStringContainsString('attempt_identity_conflict', $message);
        $this->assertStringContainsString('manual requeue required', $message);
    }

    /**
     * A terminal poison row reads as an ordinary 'failed' row, which is exactly how a
     * permanently broken item disappears into the noise. Health must call it out.
     */
    public function testTerminalPoisonRowIsVisibleInHealth(): void
    {
        $service = $this->service($this->conn());
        $this->assertSame(0, $service->computeHealth()['poison_terminal_total']);

        $this->pollPastBackoff(12);

        $health = $this->service($this->conn())->computeHealth();
        $this->assertSame(1, $health['poison_terminal_total'], 'a terminal poison row must be reported to operators');
        $this->assertSame(0, $health['needs_reconciliation_total']);
    }

    /**
     * Backoff must grow between isolations, so a row that cannot be claimed stops
     * costing a failed transaction every single poll while it walks to terminal.
     */
    public function testIsolationBackoffEscalates(): void
    {
        $observed = $this->pollPastBackoff(4);
        $backoffs = array_values(array_filter(array_column($observed, 'backoff'), static fn (int $b) => $b > 0));

        $this->assertGreaterThanOrEqual(3, count($backoffs), 'expected several deferrals to compare');
        $this->assertGreaterThan($backoffs[0], $backoffs[2], 'the deferral window must escalate');

        foreach ($backoffs as $backoff) {
            $this->assertLessThanOrEqual(3600, $backoff, 'escalation must stay capped at one hour');
        }
    }

    /**
     * Healthy work must keep draining the entire time a poison row walks to terminal.
     */
    public function testHealthyRowsAreUnaffectedWhileThePoisonRowTerminates(): void
    {
        $this->pollPastBackoff(12);

        $this->assertSame(
            (string) self::HEALTHY_ROWS,
            $this->col("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'"),
            'every healthy row must still have been claimed',
        );
    }

    /**
     * Two workers polling at once must not both try to isolate the same row.
     */
    public function testSecondWorkerCannotClaimAHalfIsolatedRow(): void
    {
        $this->service($this->conn())->claimBatch(['limit' => 50, 'retry_base_seconds' => 60]);

        $second = $this->service($this->conn())->claimBatch(['limit' => 50, 'retry_base_seconds' => 60]);
        $this->assertSame(0, $second['claimed']);
        $this->assertSame(
            '',
            $this->col('SELECT IFNULL(worker_id, "") FROM endorse_refresh_queue WHERE id=' . self::POISON_QUEUE_ID),
            'an isolated row must never be left owned',
        );
    }
}
