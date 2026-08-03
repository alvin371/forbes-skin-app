<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/support/FakeCi.php';
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';

/**
 * Logical-ordering + crash-idempotency against the REAL Endorse_sync::apply() and the REAL
 * endorse/endorse_logs schema. Ordering authority is `stats_observation_seq` (the stable
 * queue-generation id), NOT any request/apply timestamp. Only the DB adapter is a thin shim.
 *
 * @group integration
 * @internal
 */
final class EndorseApplyIdempotencyTest extends TestCase
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
        $c = [];
        foreach (explode(';', $spec) as $p) {
            [$k, $v] = array_pad(explode('=', $p, 2), 2, '');
            $c[trim($k)] = trim($v);
        }
        self::$cfg = $c;
        $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], intval($c['port']));
        if ($m->connect_errno) {
            self::fail('connect: ' . $m->connect_error);
        }
        $m->query("SET SESSION sql_mode=''");
        $m->query("DROP TABLE IF EXISTS endorse");
        $m->query("DROP TABLE IF EXISTS endorse_logs");
        $ddl = file_get_contents(__DIR__ . '/schema/endorse_real_schema.sql');
        foreach (array_filter(array_map('trim', explode(";\n", $ddl))) as $stmt) {
            if ($stmt !== '' && $m->query($stmt) === false) {
                self::fail('schema load failed: ' . $m->error);
            }
        }
        // Apply the real migration (up) to add stats_observation_seq, then EXPLAIN-check unused.
        $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['db']}", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $direction = 'up';
        require __DIR__ . '/../../migrations/20260803130000_add_stats_observation_seq.php';
        self::$m = $m;
    }

    protected function setUp(): void
    {
        if (self::$m === null) {
            $this->markTestSkipped('FORBES_TEST_DB not set.');
        }
        self::$m->query("TRUNCATE endorse");
        self::$m->query("TRUNCATE endorse_logs");
        $GLOBALS['__fake_ci'] = new FakeCi(self::$m);
    }

    private function seedEndorse(int $id = 1): array
    {
        self::$m->query("INSERT INTO endorse
            (id, id_campaign, platform, link_upload, total_cost, status, status_campaign, brand, influencer,
             views, likes, comment, share_save, is_fyp, pengajuan_payment_logs, task, logs)
            VALUES ($id, 100, 'Tiktok', 'https://www.tiktok.com/@c/video/7500000000000000001', 0,
             'Aktif', 'Aktif', 'B1', '0', 0,0,0,0,0, '', '', '')");
        return self::$m->query("SELECT * FROM endorse WHERE id=$id")->fetch_assoc();
    }

    /** $seq = logical observation order; null = no order supplied (contract). */
    private function response(int $views, int $likes, ?int $seq): array
    {
        $r = [
            'status'       => true,
            'msg'          => '',
            'data'         => ['like' => $likes, 'comment' => 5, 'share' => 1, 'collect' => 1, 'view' => $views],
            'stats_fields' => ['like', 'comment', 'share', 'collect', 'view'],
            'observed_at'  => '2026-08-03 10:00:00.000000',
            'stats_source' => 'itest',
        ];
        if ($seq !== null) {
            $r['observation_seq'] = $seq;
        }
        return $r;
    }

    private function endorse(int $id = 1): array
    {
        return self::$m->query("SELECT * FROM endorse WHERE id=$id")->fetch_assoc();
    }

    private function logCount(int $id = 1): int
    {
        return intval(self::$m->query("SELECT COUNT(*) c FROM endorse_logs WHERE id_endorse=$id")->fetch_assoc()['c']);
    }

    // --- basic ordering by logical sequence -----------------------------------

    public function testForwardSequenceApplies(): void
    {
        $this->seedEndorse();
        $s = new Endorse_sync();
        $this->assertSame('applied_newer', $s->apply($this->endorse(), $this->response(500, 90, 100), 1)['outcome']);
        $this->assertSame('applied_newer', $s->apply($this->endorse(), $this->response(1000, 200, 105), 1)['outcome']);
        $this->assertSame(1000, intval($this->endorse()['views']));
    }

    public function testOlderSequenceDoesNotOverwrite(): void
    {
        $this->seedEndorse();
        $s = new Endorse_sync();
        $s->apply($this->endorse(), $this->response(1000, 200, 105), 1);
        $this->assertSame('stale', $s->apply($this->endorse(), $this->response(500, 90, 100), 1)['outcome']);
        $this->assertSame(1000, intval($this->endorse()['views']), 'older sequence must not regress newer stats');
    }

    public function testNewerLowerValueStillWinsWhenFresher(): void
    {
        $this->seedEndorse();
        $s = new Endorse_sync();
        $s->apply($this->endorse(), $this->response(1000, 200, 100), 1);
        $s->apply($this->endorse(), $this->response(700, 150, 105), 1);
        $this->assertSame(700, intval($this->endorse()['views']), 'fresher lower observation must apply (legitimate decrease)');
    }

    // --- Critical correction 1: retry must not outrank a newer job -------------

    public function testRetryDoesNotOutrankNewerJob(): void
    {
        $this->seedEndorse();
        $s = new Endorse_sync();
        // Job A (seq 100) applies; newer Job B (seq 105) applies; then A RETRIES (still seq 100).
        $s->apply($this->endorse(), $this->response(500, 90, 100), 1);
        $s->apply($this->endorse(), $this->response(1000, 200, 105), 1);
        $retry = $s->apply($this->endorse(), $this->response(500, 90, 100), 1); // A's late retry
        $this->assertSame('stale', $retry['outcome'], 'A retry (older seq) must be stale vs newer B');
        $this->assertSame(1000, intval($this->endorse()['views']), 'newer job B must remain');
    }

    public function testMultipleRetriesShareOneLogicalOrder(): void
    {
        $this->seedEndorse();
        $s = new Endorse_sync();
        $this->assertSame('applied_newer', $s->apply($this->endorse(), $this->response(500, 90, 100), 1)['outcome']);
        // same job retried twice → same seq → duplicates, not newer.
        $this->assertSame('duplicate', $s->apply($this->endorse(), $this->response(500, 90, 100), 1)['outcome']);
        $this->assertSame('duplicate', $s->apply($this->endorse(), $this->response(500, 90, 100), 1)['outcome']);
        $this->assertSame(1, $this->logCount());
    }

    // --- Critical correction 4: crash-before-log recovery ---------------------

    public function testCrashBeforeLogRecoversOnRetry(): void
    {
        $this->seedEndorse();
        $s = new Endorse_sync();
        $s->apply($this->endorse(), $this->response(1000, 200, 100), 1);
        $this->assertSame(1, $this->logCount());
        // Simulate a crash AFTER the endorse update but BEFORE the log write: remove the log.
        self::$m->query("DELETE FROM endorse_logs WHERE id_endorse=1");
        $this->assertSame(0, $this->logCount());
        // The queue retries with the SAME logical order (seq 100). Duplicate branch must repair
        // the missing log without rewriting the (already correct) endorse stats.
        $out = $s->apply($this->endorse(), $this->response(1000, 200, 100), 1);
        $this->assertSame('duplicate', $out['outcome']);
        $this->assertSame(1, $this->logCount(), 'missing log recovered exactly once');
        $this->assertSame(1000, intval($this->endorse()['views']));
    }

    public function testEqualSequenceConflictingPayloadKeepsFirst(): void
    {
        $this->seedEndorse();
        $s = new Endorse_sync();
        $s->apply($this->endorse(), $this->response(1000, 200, 100), 1);
        // Same seq, different payload (provider inconsistency) → duplicate, first values kept.
        $out = $s->apply($this->endorse(), $this->response(999, 199, 100), 1);
        $this->assertSame('duplicate', $out['outcome']);
        $this->assertSame(1000, intval($this->endorse()['views']), 'equal-seq duplicate must not overwrite');
        $this->assertSame(1, $this->logCount());
    }

    // --- Critical correction 3: null-order contract ---------------------------

    public function testNullOrderCannotOverwriteOrderedData(): void
    {
        $this->seedEndorse();
        $s = new Endorse_sync();
        $s->apply($this->endorse(), $this->response(1000, 200, 100), 1); // establishes seq=100
        $out = $s->apply($this->endorse(), $this->response(500, 90, null), 1); // no order supplied
        $this->assertSame('contract_error', $out['outcome'], 'unordered response must be rejected');
        $this->assertSame(1000, intval($this->endorse()['views']), 'ordered data must not be overwritten by unordered response');
    }

    public function testFirstObservationWithoutPriorOrderApplies(): void
    {
        $this->seedEndorse();
        $s = new Endorse_sync();
        // existing null + incoming valid → apply
        $this->assertSame('applied_newer', $s->apply($this->endorse(), $this->response(500, 90, 100), 1)['outcome']);
        $this->assertSame(500, intval($this->endorse()['views']));
    }

    // --- concurrency: 10 mixed jobs + retries race, newest logical job wins ----

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
            $err = stream_get_contents($pr['pipes'][2]);
            fclose($pr['pipes'][1]);
            fclose($pr['pipes'][2]);
            proc_close($pr['p']);
            $this->assertStringNotContainsString('Fatal error', $err, "worker stderr: $err");
        }
        return $out;
    }

    public function testConcurrentMixedJobsAndRetriesNewestWins(): void
    {
        $c = self::$cfg;
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['db']}";
        // Run several times to reduce timing luck.
        for ($round = 0; $round < 3; $round++) {
            self::$m->query("TRUNCATE endorse");
            self::$m->query("TRUNCATE endorse_logs");
            $this->seedEndorse();
            $barrier = microtime(true) + 1.0;
            // 10 observations: jobs seq 101..106 plus RETRIES of older jobs (101,102,103) that
            // physically start last. Newest logical job is seq 106 (views 6000).
            $specs = [
                [101, 1000], [102, 2000], [103, 3000], [104, 4000], [105, 5000], [106, 6000],
                [101, 1000], [102, 2000], [103, 3000], [104, 4000], // late retries of older jobs
            ];
            $args = [];
            foreach ($specs as $sp) {
                $args[] = [$dsn, $c['user'], $c['pass'], 1, $sp[1], intval($sp[1] / 10), $sp[0], $barrier];
            }
            $this->runConcurrent('apply_worker.php', $args);
            $e = $this->endorse();
            $this->assertSame(6000, intval($e['views']), "round $round: newest logical job (seq 106) must win");
            $this->assertSame(106, intval($e['stats_observation_seq']));
            $this->assertSame(1, $this->logCount(), "round $round: exactly one log row");
        }
    }
}
