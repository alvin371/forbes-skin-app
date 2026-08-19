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
 * A post proven gone must be recorded once and never claimed again.
 *
 * `endorse_refresh_quarantine`, its unique key and FOUR readers shipped with contract v2, but
 * the WRITER was missing — so a deleted or private post was failed, then re-enqueued by the
 * next `enqueueAllActive` and re-attempted on every pass. forbes carried 5,394 failed rows and
 * sec-forbes 10,828 against a 12,548-post corpus, all of them re-attempted daily against a
 * shared, quota-limited provider.
 *
 * These tests drive the REAL applyResults() against the REAL migrated schema, and pin the
 * write and the claim-time exclusion as ONE mechanism: a quarantine row that the claim does
 * not honour would be pure bookkeeping.
 *
 * @internal
 */
#[Group('integration')]
final class EndorseRefreshQuarantineTest extends TestCase
{
    private const ENDORSE_URL = 'https://www.tiktok.com/@creator/video/7500000000000000001';

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
        $c         = self::$cfg;
        self::$pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['db']}",
            $c['user'],
            $c['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
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
        self::$m->query('DELETE FROM endorse_refresh_quarantine');
        self::$m->query("INSERT INTO endorse (id, id_campaign, platform, link_upload, total_cost, status, status_campaign, brand, influencer, views, likes, comment, share_save, is_fyp, pengajuan_payment_logs, task, logs)
            VALUES (1, 100, 'Tiktok', '" . self::ENDORSE_URL . "', 0, 'Aktif', 'Aktif', 'B1', '0', 0,0,0,0,0,'','','')");
        self::$m->query("INSERT INTO endorse_campaign (id, status) VALUES (100, 'Aktif')");
    }

    // ------------------------------------------------------------------ helpers

    private function conn(): mysqli
    {
        $c = self::$cfg;
        $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], (int) ($c['port']));
        $m->query("SET SESSION sql_mode=''");

        return $m;
    }

    private function makeService(mysqli $conn): EndorseRefreshQueueService
    {
        $ci                   = new FakeCi($conn);
        $GLOBALS['__fake_ci'] = $ci;
        $ci->endorse_sync     = new Endorse_sync();

        return new EndorseRefreshQueueService();
    }

    private function seedQueueRow(int $queueId = 1, string $url = self::ENDORSE_URL): void
    {
        $now = date('Y-m-d H:i:s');
        self::$m->query("INSERT INTO endorse_refresh_queue (id, id_endorse, id_campaign, platform, link_upload, status, attempts, attempt_sequence, max_attempts, worker_id, claim_owner, started_at, claimed_at, lease_expires_at, created_at)
            VALUES ({$queueId}, 1, 100, 'Tiktok', '{$url}', 'processing', 0, 1, 3, 'w1', 'cron', '{$now}', '{$now}', DATE_ADD('{$now}', INTERVAL 3 MINUTE), '{$now}')");
        self::$m->query("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, worker_id, status, started_at, created_at)
            VALUES ({$queueId}, 1, 'w1', 'processing', '{$now}', '{$now}')");
        $attemptId = (int) self::$m->insert_id;
        self::$m->query("UPDATE endorse_refresh_queue SET active_attempt_id={$attemptId} WHERE id={$queueId}");
    }

    private function items(int $queueId = 1): array
    {
        $attemptId = (int) $this->col("SELECT active_attempt_id FROM endorse_refresh_queue WHERE id={$queueId}");

        return [[
            'queue_id'   => $queueId, 'id_endorse' => 1, 'attempts' => 0, 'max_attempts' => 3,
            'attempt_no' => 1, 'active_attempt_id' => $attemptId,
            'worker_id'  => 'w1', 'purpose' => 'daily', 'enqueued_by' => 0,
        ]];
    }

    /**
     * What the provider actually returns for a post that no longer resolves.
     */
    private function permanentResponse(): array
    {
        return [
            'status'      => false,
            'error_class' => Endorse_sync::ERR_PERMANENT,
            'msg'         => 'RapidAPI cannot resolve this post (deleted, private, or invalid URL)',
        ];
    }

    /**
     * Terminal for the queue, but NOT proof the post is gone.
     */
    private function emptyResponse(): array
    {
        return [
            'status'      => false,
            'error_class' => Endorse_sync::ERR_EMPTY,
            'msg'         => 'Stats data tidak ditemukan',
        ];
    }

    private function col(string $sql): string
    {
        return (string) (self::$m->query($sql)->fetch_row()[0] ?? '');
    }

    private function quarantineCount(): int
    {
        return (int) $this->col('SELECT COUNT(*) FROM endorse_refresh_quarantine');
    }

    /**
     * Seed one claimable pending row and return the ids the claim SELECT yields.
     */
    private function claimableIds(string $url = self::ENDORSE_URL, bool $excludeQuarantined = true): array
    {
        self::$m->query('DELETE FROM endorse_refresh_queue');
        $now = date('Y-m-d H:i:s');
        self::$m->query("INSERT INTO endorse_refresh_queue (id, id_endorse, id_campaign, platform, link_upload, status, attempts, attempt_sequence, max_attempts, priority, created_at)
            VALUES (900, 1, 100, 'Tiktok', '{$url}', 'pending', 0, 0, 3, 10, '{$now}')");

        $sql = EndorseRefreshClaimRepository::buildSelectForUpdateSql(10, 60, 0, $excludeQuarantined);
        // FOR UPDATE outside a transaction is a no-op lock; the predicate is what is under test.
        $res = self::$m->query(str_replace('FOR UPDATE SKIP LOCKED', '', $sql));
        $ids = [];

        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }

    // ------------------------------------------------------------------- writer

    public function testPermanentFailureQuarantinesTheContentOnTheFirstAttempt(): void
    {
        $this->seedQueueRow();
        $out = $this->makeService($this->conn())->applyResults($this->items(), [$this->permanentResponse()]);

        $this->assertSame(1, $out['failed'] ?? 0);
        $this->assertSame(1, $this->quarantineCount());

        // Terminal classification is checked BEFORE the attempts counter, so a dead post costs
        // one attempt, not three. That is the whole point: 3x fewer wasted provider requests.
        $this->assertSame('failed', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame('1', $this->col('SELECT attempts FROM endorse_refresh_queue WHERE id=1'));

        $row = self::$m->query('SELECT * FROM endorse_refresh_quarantine LIMIT 1')->fetch_assoc();
        $this->assertSame('1', (string) $row['id_endorse']);
        $this->assertSame('provider_permanent_item', $row['source']);
        $this->assertSame('rapidapi_unresolvable', $row['reason_code']);
        $this->assertSame('1', (string) $row['source_queue_id']);
        $this->assertNull($row['cleared_at']);
        $this->assertNotSame('', (string) $row['canonical_url_hash']);
    }

    /**
     * The URL stored here and the URL the claim compares must be the SAME column. Writing the
     * endorse's current `link_upload` while the claim reads the queue row's would leave the
     * exclusion looking implemented and matching nothing whenever the two have drifted.
     */
    public function testQuarantineSnapshotsTheQueueRowsUrlNotTheEndorsesUrl(): void
    {
        $queueUrl = 'https://vt.tiktok.com/ZSdrifted/';
        $this->seedQueueRow(1, $queueUrl);
        $this->makeService($this->conn())->applyResults($this->items(), [$this->permanentResponse()]);

        $this->assertSame(
            $queueUrl,
            $this->col('SELECT url_snapshot FROM endorse_refresh_quarantine LIMIT 1'),
            'url_snapshot must come from the queue row, which is what the claim predicate reads',
        );
        $this->assertNotSame(self::ENDORSE_URL, $this->col('SELECT url_snapshot FROM endorse_refresh_quarantine LIMIT 1'));
    }

    /**
     * `enqueueAllActive` re-queues everything each pass, so the same dead post arrives again.
     * The unique (id_endorse, content_key) must absorb that rather than erroring the item —
     * an INSERT failure here would roll back the transaction and strand the row in 'processing'.
     */
    public function testRepeatedPermanentFailuresStayOneRow(): void
    {
        foreach ([1, 2, 3] as $queueId) {
            self::$m->query('DELETE FROM endorse_refresh_queue');
            self::$m->query('DELETE FROM endorse_refresh_queue_attempts');
            $this->seedQueueRow($queueId);
            $out = $this->makeService($this->conn())->applyResults($this->items($queueId), [$this->permanentResponse()]);
            $this->assertSame(1, $out['failed'] ?? 0, "pass {$queueId} must still fail cleanly");
        }

        $this->assertSame(1, $this->quarantineCount(), 're-observing the same dead post must not add rows');
        $this->assertSame('3', $this->col('SELECT source_queue_id FROM endorse_refresh_quarantine LIMIT 1'), 'the row should carry the LATEST evidence');
    }

    /**
     * ERR_EMPTY is terminal for the queue but is NOT proof the post is gone — a provider
     * hiccup can return a payload with no stats. Quarantine has no automatic expiry, so
     * quarantining on it would permanently retire live posts.
     */
    public function testEmptyStatsIsTerminalButNeverQuarantines(): void
    {
        $this->seedQueueRow();
        $out = $this->makeService($this->conn())->applyResults($this->items(), [$this->emptyResponse()]);

        $this->assertSame(1, $out['failed'] ?? 0);
        $this->assertSame('failed', $this->col('SELECT status FROM endorse_refresh_queue WHERE id=1'));
        $this->assertSame(0, $this->quarantineCount(), 'empty stats must not retire a post permanently');
    }

    /**
     * The second terminal path — the endorse row itself is gone — also classifies PERMANENT,
     * but there is no content to quarantine and no endorse to key on.
     */
    public function testMissingEndorseRowFailsWithoutQuarantining(): void
    {
        $this->seedQueueRow();
        self::$m->query('DELETE FROM endorse WHERE id=1');

        $out = $this->makeService($this->conn())->applyResults($this->items(), [$this->permanentResponse()]);

        $this->assertSame(1, $out['failed'] ?? 0);
        $this->assertSame(0, $this->quarantineCount());
    }

    // ---------------------------------------------------------- claim exclusion

    public function testQuarantinedContentIsNotClaimedAgain(): void
    {
        $this->seedQueueRow();
        $this->makeService($this->conn())->applyResults($this->items(), [$this->permanentResponse()]);

        $this->assertSame([], $this->claimableIds(), 'a proven-dead post must not be claimable');

        // ...and the exclusion is genuinely doing the work, not the row being ineligible.
        $this->assertSame([900], $this->claimableIds(self::ENDORSE_URL, false));
    }

    /**
     * Replacing the post is the operator's normal remedy, and it must work with no extra
     * action: a different URL is different content, so the quarantine simply stops matching.
     */
    public function testRepointingTheEndorseAtANewPostReopensTheRow(): void
    {
        $this->seedQueueRow();
        $this->makeService($this->conn())->applyResults($this->items(), [$this->permanentResponse()]);

        $this->assertSame(
            [900],
            $this->claimableIds('https://www.tiktok.com/@creator/video/7599999999999999999'),
            'a replacement post must be claimable without clearing the quarantine',
        );
    }

    /**
     * Clearing a quarantine row must take effect on the very next claim.
     */
    public function testClearingAQuarantineRowReopensTheRow(): void
    {
        $this->seedQueueRow();
        $this->makeService($this->conn())->applyResults($this->items(), [$this->permanentResponse()]);
        $this->assertSame([], $this->claimableIds());

        self::$m->query("UPDATE endorse_refresh_quarantine SET cleared_at=NOW(6), cleared_by='ops', clear_reason='verified live'");
        $this->assertSame([900], $this->claimableIds());
    }
}
