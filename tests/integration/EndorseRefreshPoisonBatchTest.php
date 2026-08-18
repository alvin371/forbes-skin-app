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
