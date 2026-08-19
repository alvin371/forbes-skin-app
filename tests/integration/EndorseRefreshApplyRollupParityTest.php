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
 * applyResults() is the most safety-critical function in the pipeline, and the continuous
 * worker calls it in a shape the cron never does: once per item, with the campaign rollup
 * deferred.
 *
 * Deferral is a throughput necessity — update_campaign_parent() runs ~11 statements including
 * four unbounded aggregates, so per-item at 400 completions/min it becomes ~4,400 aggregate
 * statements/min and displaces the provider as the bottleneck. But it is only SAFE because
 * the rollup is a pure recompute from current state, already outside the per-item transaction.
 *
 * These tests pin exactly that claim: batch-apply-with-rollup and per-item-apply-with-deferred
 * rollup must leave byte-identical queue, attempt, endorse, endorse_logs AND campaign state.
 * If deferral ever stops converging, this fails here rather than silently drifting campaign
 * totals in production.
 *
 * @internal
 */
#[Group('integration')]
final class EndorseRefreshApplyRollupParityTest extends TestCase
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
                self::fail('FORBES_REQUIRE_DB=1 but FORBES_TEST_DB unset.');
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
    }

    protected function tearDown(): void
    {
        foreach ($this->openConnections as $conn) {
            @$conn->close();
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

    /**
     * Two campaigns, three posts, so the rollup has something to deduplicate.
     */
    private function seed(): array
    {
        $pdo = self::$pdo;
        // sql_mode is relaxed by QueueSchema::build, so the legacy NOT NULL columns without
        // defaults (desc/budget/counts/aggregates) fill themselves — the same way every other
        // integration suite seeds this table.
        $pdo->exec("INSERT INTO endorse_campaign (id, title) VALUES (7, 'c7'), (8, 'c8')");

        $items = [];
        $specs = [
            [1, 7], [2, 7], [3, 8],
        ];

        foreach ($specs as $i => [$endorseId, $campaignId]) {
            $pdo->exec("INSERT INTO endorse (id, id_campaign, link_upload, posting_at)
                        VALUES ({$endorseId}, {$campaignId}, 'https://tiktok-mock.local/@u/video/730000000000000000{$endorseId}', '2026-08-19')");
            $pdo->exec("INSERT INTO endorse_refresh_queue
                        (id, id_endorse, id_campaign, platform, purpose, link_upload, status, worker_id, claim_owner,
                         attempts, attempt_sequence, max_attempts, claimed_at, started_at, lease_expires_at, created_at)
                        VALUES ({$endorseId}, {$endorseId}, {$campaignId}, 'Tiktok', 'daily',
                                'https://tiktok-mock.local/@u/video/730000000000000000{$endorseId}',
                                'processing', 'w1', 'cron', 0, 1, 3, NOW(6), NOW(6), DATE_ADD(NOW(6), INTERVAL 120 SECOND), NOW(6))");
            $pdo->exec("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, worker_id, status, started_at, created_at)
                        VALUES ({$endorseId}, 1, 'w1', 'processing', NOW(6), NOW(6))");
            $attemptId = (int) $pdo->lastInsertId();
            $pdo->exec("UPDATE endorse_refresh_queue SET active_attempt_id = {$attemptId} WHERE id = {$endorseId}");

            $items[] = [
                'queue_id'   => $endorseId, 'id_endorse' => $endorseId, 'attempts' => 0, 'max_attempts' => 3,
                'attempt_no' => 1, 'active_attempt_id' => $attemptId, 'worker_id' => 'w1',
                'purpose'    => 'daily', 'enqueued_by' => 0, 'platform' => 'Tiktok',
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function responses(): array
    {
        $make = static fn (int $n): array => [
            'status'       => true, 'msg' => '',
            'data'         => ['like' => 100 * $n, 'comment' => 5 * $n, 'share' => $n, 'collect' => $n, 'view' => 1000 * $n],
            'stats_fields' => ['like', 'comment', 'share', 'collect', 'view'],
            'observed_at'  => '2026-08-19 10:00:00.000000',
        ];

        return [$make(1), $make(2), $make(3)];
    }

    /**
     * Everything the two paths must agree on, as a comparable snapshot.
     */
    private function snapshot(): array
    {
        $pdo  = self::$pdo;
        $rows = static fn (string $sql): array => $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'queue'    => $rows('SELECT id, status, attempts, attempt_sequence, worker_id, claim_owner, active_attempt_id, error_message FROM endorse_refresh_queue ORDER BY id'),
            'attempts' => $rows('SELECT queue_id, attempt_no, status, error_class, worker_id FROM endorse_refresh_queue_attempts ORDER BY queue_id, attempt_no'),
            'endorse'  => $rows('SELECT id, likes, comment, share_save, views, stats_observation_seq, stats_completeness FROM endorse ORDER BY id'),
            'logs'     => $rows('SELECT id_endorse, id_campaign, date, views, likes, comment, share_save, cpm,
                                    views_before, likes_before, views_after, likes_after,
                                    stats_completeness, stats_observation_seq
                             FROM endorse_logs ORDER BY id_endorse, date'),
            // These are precisely the columns update_campaign_parent() recomputes. Comparing
            // them is what proves deferral CONVERGES rather than merely not crashing.
            'campaigns' => $rows('SELECT id, views, likes, comment, share_save, cpm, count_endorse, count_influencer FROM endorse_campaign ORDER BY id'),
        ];
    }

    /**
     * The load-bearing test. The two call shapes must be indistinguishable in the database.
     */
    public function testPerItemApplyWithDeferredRollupMatchesBatchApplyExactly(): void
    {
        // --- path A: one batch call, rollup inline (what the cron does)
        $items        = $this->seed();
        $batchSummary = $this->service($this->conn())->applyResults($items, $this->responses());
        $batch        = $this->snapshot();

        $this->assertSame(3, $batchSummary['completed']);

        // --- path B: three per-item calls, rollup deferred then flushed (what the worker does)
        QueueSchema::reset(self::$pdo);
        $items     = $this->seed();
        $responses = $this->responses();

        $svc              = $this->service($this->conn());
        $touched          = [];
        $perItemCompleted = 0;

        foreach ($items as $i => $item) {
            $summary = $svc->applyResults([$item], [$responses[$i]], ['defer_campaign_rollup' => true]);
            $perItemCompleted += (int) $summary['completed'];

            foreach ($summary['touched_campaigns'] as $cid) {
                $touched[(int) $cid] = true;
            }
        }

        // The worker's flush, on its timer / at shutdown.
        $ci = $GLOBALS['__fake_ci'];

        foreach (array_keys($touched) as $cid) {
            $ci->endorse_sync->update_campaign_parent((int) $cid, 0);
        }

        $perItem = $this->snapshot();

        $this->assertSame(3, $perItemCompleted);
        $this->assertSame($batch, $perItem, 'deferred-rollup per-item apply must converge to the batch result');
    }

    /**
     * The deferral is only safe if the caller knows WHICH campaigns to flush. A caller that
     * gets an empty list would silently never recompute, and campaign totals would drift with
     * no error anywhere.
     */
    public function testTouchedCampaignsAreReportedForTheCallerToFlush(): void
    {
        $items     = $this->seed();
        $responses = $this->responses();
        $svc       = $this->service($this->conn());

        $touched = [];

        foreach ($items as $i => $item) {
            $summary = $svc->applyResults([$item], [$responses[$i]], ['defer_campaign_rollup' => true]);
            $this->assertArrayHasKey('touched_campaigns', $summary);

            foreach ($summary['touched_campaigns'] as $cid) {
                $touched[(int) $cid] = true;
            }
        }

        $ids = array_keys($touched);
        sort($ids);
        $this->assertSame([7, 8], $ids, 'both campaigns must be reported exactly once each');
    }

    /**
     * Default behaviour must be byte-identical to before the $opts parameter existed, or the
     * cron silently changes. An empty $opts and no $opts at all must be the same call.
     */
    public function testOmittingOptsKeepsTheInlineRollupBehaviour(): void
    {
        $items       = $this->seed();
        $withoutOpts = $this->service($this->conn())->applyResults($items, $this->responses());
        $implicit    = $this->snapshot();

        QueueSchema::reset(self::$pdo);
        $items         = $this->seed();
        $withEmptyOpts = $this->service($this->conn())->applyResults($items, $this->responses(), []);
        $explicit      = $this->snapshot();

        $this->assertSame($implicit, $explicit);
        $this->assertSame($withoutOpts['completed'], $withEmptyOpts['completed']);
        $this->assertSame([7, 8], $withoutOpts['touched_campaigns']);
    }

    /**
     * An empty batch must still return the key, so callers can union it unconditionally.
     */
    public function testEmptyBatchStillReportsTouchedCampaigns(): void
    {
        $summary = $this->service($this->conn())->applyResults([], []);

        $this->assertArrayHasKey('touched_campaigns', $summary);
        $this->assertSame([], $summary['touched_campaigns']);
    }
}
