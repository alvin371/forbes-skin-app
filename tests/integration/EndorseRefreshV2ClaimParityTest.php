<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/support/QueueSchema.php';
require_once __DIR__ . '/support/FakeCi.php';
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshClaimRepository.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshRateLimiter.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshQueueService.php';
require_once __DIR__ . '/../../application/libraries/EndorseRefreshV2Coordinator.php';

/**
 * PL-03 — the V2/Rust claim path must obey the same invariants as the cron path.
 *
 * The cron fix does not automatically protect V2: it is a separate allocator with its own
 * transaction, predicate and counter handling. These tests drive the REAL
 * EndorseRefreshV2Coordinator::claimBatchV2() from multiple OS processes on independent
 * connections, against the canonical migrated schema.
 *
 * @internal
 */
#[Group('integration')]
final class EndorseRefreshV2ClaimParityTest extends TestCase
{
    private const ROWS = 30;

    private static ?PDO $pdo  = null;
    private static array $cfg = [];

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
        $this->activateV2Contract();
        $this->seedPending(self::ROWS);
    }

    /**
     * The V2 claim endpoint only serves work when the contract hands ownership to Rust.
     */
    private function activateV2Contract(): void
    {
        self::$pdo->exec("
            INSERT INTO endorse_refresh_runtime_control (id, contract_state, owner_state, generation)
            VALUES (1, 'v2', 'rust', 1)
            ON DUPLICATE KEY UPDATE contract_state='v2', owner_state='rust'
        ");
        // Fallback lease invariant: lease >= timeout + margin.
        putenv('ENDORSE_REFRESH_FALLBACK_LEASE_SECONDS=90');
        putenv('ENDORSE_REFRESH_PROVIDER_TIMEOUT_SECONDS=60');
        putenv('ENDORSE_REFRESH_COMPLETION_MARGIN_SECONDS=15');
    }

    private function seedPending(int $n): void
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

        for ($id = 1; $id <= $n; $id++) {
            $url = 'https://www.tiktok.com/@c/video/' . (7500000000000000000 + $id);
            $endorse->execute([$id, $url]);
            $queue->execute([$id, $id, $url]);
        }
    }

    private function col(string $sql): string
    {
        return (string) (self::$pdo->query($sql)->fetch(PDO::FETCH_NUM)[0] ?? '');
    }

    /**
     * @return list<string> raw stdout per worker
     */
    private function runWorkers(array $argsets): array
    {
        $procs = [];

        foreach ($argsets as $i => $args) {
            $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/v2_claim_worker.php');

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
            $this->assertStringNotContainsString('Fatal error', $err, "v2 worker stderr: {$err}");
        }

        return $out;
    }

    private function connectionArgs(): array
    {
        $c = self::$cfg;

        return [$c['host'], $c['port'], $c['user'], $c['pass'], $c['db']];
    }

    public function testConcurrentV2WorkersNeverDoubleClaimAndKeepEveryInvariant(): void
    {
        $conn    = $this->connectionArgs();
        $barrier = microtime(true) + 0.5;
        $out     = $this->runWorkers([
            array_merge($conn, [EndorseRefreshV2Coordinator::generateWorkerUuid(), 12, $barrier]),
            array_merge($conn, [EndorseRefreshV2Coordinator::generateWorkerUuid(), 12, $barrier]),
            array_merge($conn, [EndorseRefreshV2Coordinator::generateWorkerUuid(), 12, $barrier]),
        ]);

        $returnedIds   = [];
        $returnedTotal = 0;

        foreach ($out as $line) {
            $this->assertMatchesRegularExpression('/^claimed=\d+:/', trim($line), "worker did not claim: {$line}");
            [$head, $ids] = explode(':', trim($line), 2);
            $returnedTotal += (int) explode('=', $head)[1];

            foreach (array_filter(explode(',', $ids)) as $id) {
                $returnedIds[] = (int) $id;
            }
        }

        // No queue row handed to more than one worker.
        $this->assertSame(
            count($returnedIds),
            count(array_unique($returnedIds)),
            'a queue row was returned to more than one V2 worker',
        );
        $this->assertCount($returnedTotal, $returnedIds);

        // Every returned claim has a matching processing parent AND open attempt.
        $processing = (int) $this->col("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'");
        $this->assertSame($returnedTotal, $processing, 'returned claims must equal processing parents');

        $this->assertSame(
            '0',
            $this->col("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing' AND (worker_id IS NULL OR active_attempt_id IS NULL)"),
            'no processing parent may be left without an owner and an active attempt',
        );

        // No orphan attempt: every open attempt belongs to a processing parent that points back.
        $this->assertSame(
            '0',
            $this->col("
                SELECT COUNT(*) FROM endorse_refresh_queue_attempts a
                LEFT JOIN endorse_refresh_queue q
                  ON q.id = a.queue_id AND q.active_attempt_id = a.id AND q.status = 'processing'
                WHERE a.status = 'processing' AND q.id IS NULL
            "),
            'an open attempt exists without a matching processing parent',
        );

        // At most one open attempt per queue row, and no duplicate attempt numbers.
        $this->assertSame(
            '0',
            $this->col("
                SELECT COUNT(*) FROM (
                    SELECT queue_id FROM endorse_refresh_queue_attempts
                    WHERE status='processing' GROUP BY queue_id HAVING COUNT(*) > 1
                ) t
            "),
        );
        $this->assertSame(
            $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts'),
            $this->col('SELECT COUNT(DISTINCT queue_id, attempt_no) FROM endorse_refresh_queue_attempts'),
            'an attempt number was allocated twice for the same queue row',
        );

        // No row returned as claimed is still pending.
        if (! empty($returnedIds)) {
            $idList = implode(',', array_map('intval', $returnedIds));
            $this->assertSame(
                '0',
                $this->col("SELECT COUNT(*) FROM endorse_refresh_queue WHERE id IN ({$idList}) AND status <> 'processing'"),
                'a row returned as claimed did not end up processing',
            );
        }
    }

    /**
     * Counter parity: V2 must allocate from the highest of the parent counter and real
     * attempt history, exactly like cron — never from the counter alone.
     */
    public function testV2AllocatesFromAttemptHistoryLikeCron(): void
    {
        // Legacy shape: history is ahead of the parent's attempt_sequence.
        self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, worker_id, status, started_at, finished_at, created_at)
            VALUES (1, 1, 'old', 'retrying', NOW(), NOW(), NOW()), (1, 2, 'old', 'failed', NOW(), NOW(), NOW())");
        self::$pdo->exec('UPDATE endorse_refresh_queue SET attempt_sequence = 0 WHERE id = 1');
        self::$pdo->exec('DELETE FROM endorse_refresh_queue WHERE id > 1');

        $out = $this->runWorkers([
            array_merge($this->connectionArgs(), [EndorseRefreshV2Coordinator::generateWorkerUuid(), 5]),
        ]);
        $this->assertStringStartsWith('claimed=1:', trim($out[0]));

        $this->assertSame('3', $this->col('SELECT attempt_sequence FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame(
            '1,2,3',
            $this->col('SELECT GROUP_CONCAT(attempt_no ORDER BY attempt_no) FROM endorse_refresh_queue_attempts WHERE queue_id=1'),
        );
    }

    /**
     * If the parent activation matches nothing — because a concurrent transaction changed
     * the row after it was selected — V2 must fail loudly, not hand the caller a claim it
     * does not actually own. Without the expected-affected-rows assertion this silently
     * returns an item whose parent is still pending, and the worker then calls the provider
     * for work it never held.
     */
    public function testZeroRowActivationIsAnExplicitFailureNotAClaim(): void
    {
        $c    = self::$cfg;
        $conn = new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], (int) $c['port']);
        $conn->query("SET SESSION sql_mode=''");
        $ci                   = new FakeCi($conn);
        $GLOBALS['__fake_ci'] = $ci;
        $ci->endorse_sync     = new Endorse_sync();

        $coordinator = new EndorseRefreshV2Coordinator();
        $ci->db->forceZeroAffectedOn('endorse_refresh_queue');

        $claim = $coordinator->claimBatchV2(
            EndorseRefreshV2Coordinator::OWNER_RUST,
            EndorseRefreshV2Coordinator::generateWorkerUuid(),
            5,
        );

        $this->assertNotSame(200, (int) ($claim['http_status']), 'a lost activation race must not return 2xx');
        $this->assertEmpty($claim['body']['items'] ?? [], 'no item may be handed out after a zero-row activation');
        $this->assertSame(
            '0',
            $this->col("SELECT COUNT(*) FROM endorse_refresh_queue WHERE status='processing'"),
            'a failed activation must leave no processing parent',
        );
        $this->assertSame(
            '0',
            $this->col('SELECT COUNT(*) FROM endorse_refresh_queue_attempts'),
            'a failed activation must roll back its attempt row',
        );
        $conn->close();
    }

    /**
     * A row already owned by another worker is never re-served by V2.
     */
    public function testV2SkipsRowsThatAreAlreadyOwned(): void
    {
        self::$pdo->exec("UPDATE endorse_refresh_queue SET status='processing', worker_id='someone-else' WHERE id <= 10");

        $out = $this->runWorkers([
            array_merge($this->connectionArgs(), [EndorseRefreshV2Coordinator::generateWorkerUuid(), 50]),
        ]);
        [$head, $ids] = explode(':', trim($out[0]), 2);
        $claimed      = array_map('intval', array_filter(explode(',', $ids)));

        $this->assertSame(self::ROWS - 10, (int) explode('=', $head)[1]);

        foreach ($claimed as $id) {
            $this->assertGreaterThan(10, $id, 'V2 re-served a row that another worker already owns');
        }
    }
}
