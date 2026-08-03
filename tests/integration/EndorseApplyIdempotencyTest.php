<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/support/FakeCi.php';
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';

/**
 * Idempotency + out-of-order safety against the REAL Endorse_sync::apply() and the REAL
 * endorse/endorse_logs schema (loaded from production DDL). Only the DB adapter is a thin
 * mysqli shim; the apply logic under test is production code.
 *
 * @group integration
 * @internal
 */
final class EndorseApplyIdempotencyTest extends TestCase
{
    private static ?mysqli $m = null;

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
        $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], intval($c['port']));
        if ($m->connect_errno) {
            self::fail('connect: ' . $m->connect_error);
        }
        // Legacy tables have NOT NULL columns without defaults; relax strict mode for seeds
        // so missing columns take their zero-value (matches how the legacy app inserts).
        $m->query("SET SESSION sql_mode=''");
        $m->query("DROP TABLE IF EXISTS endorse");
        $m->query("DROP TABLE IF EXISTS endorse_logs");
        $ddl = file_get_contents(__DIR__ . '/schema/endorse_real_schema.sql');
        foreach (array_filter(array_map('trim', explode(";\n", $ddl))) as $stmt) {
            if ($stmt === '') {
                continue;
            }
            if ($m->query($stmt) === false) {
                self::fail('schema load failed: ' . $m->error);
            }
        }
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
             views, likes, comment, share_save, is_fyp,
             pengajuan_payment_logs, task, logs)
            VALUES ($id, 100, 'Tiktok', 'https://www.tiktok.com/@c/video/7500000000000000001', 0,
             'Aktif', 'Aktif', 'B1', '0', 0,0,0,0,0, '', '', '')");
        $row = self::$m->query("SELECT * FROM endorse WHERE id=$id")->fetch_assoc();
        return $row;
    }

    private function response(int $views, int $likes, string $observedAt): array
    {
        return [
            'status'      => true,
            'msg'         => '',
            'data'        => ['like' => $likes, 'comment' => 5, 'share' => 1, 'collect' => 1, 'view' => $views],
            'stats_fields' => ['like', 'comment', 'share', 'collect', 'view'],
            'observed_at' => $observedAt,
            'stats_source' => 'itest',
        ];
    }

    private function endorse(int $id = 1): array
    {
        return self::$m->query("SELECT * FROM endorse WHERE id=$id")->fetch_assoc();
    }

    public function testDuplicateProcessingProducesOneLogRowAndConsistentStats(): void
    {
        $endorse = $this->seedEndorse();
        $sync = new Endorse_sync();
        $resp = $this->response(1000, 200, '2026-08-03 10:00:00.000000');

        $r1 = $sync->apply($endorse, $resp, 1);
        $this->assertTrue($r1['status'], $r1['msg'] ?? '');
        // Apply the SAME observation again (duplicate delivery of one queue item).
        $r2 = $sync->apply($this->endorse(), $resp, 1);
        $this->assertTrue($r2['status']);

        $logRows = intval(self::$m->query("SELECT COUNT(*) c FROM endorse_logs WHERE id_endorse=1")->fetch_assoc()['c']);
        $this->assertSame(1, $logRows, 'exactly one log row per (id_endorse,date) — no duplicate logs');
        $e = $this->endorse();
        $this->assertSame(1000, intval($e['views']));
        $this->assertSame(200, intval($e['likes']));
    }

    public function testOlderObservationDoesNotOverwriteNewerStats(): void
    {
        $endorse = $this->seedEndorse();
        $sync = new Endorse_sync();

        // Newer observation lands first (T2), then an older one (T1 < T2) arrives late.
        $newer = $this->response(1000, 200, '2026-08-03 10:05:00.000000');
        $older = $this->response(500, 90, '2026-08-03 10:00:00.000000');

        $this->assertTrue($sync->apply($this->endorse(), $newer, 1)['status']);
        $sync->apply($this->endorse(), $older, 1); // late, stale delivery

        $e = $this->endorse();
        $this->assertSame(1000, intval($e['views']), 'older response must not regress newer views');
        $this->assertSame(200, intval($e['likes']), 'older response must not regress newer likes');
        // The stored observation timestamp must remain the newer one.
        $this->assertStringStartsWith('2026-08-03 10:05:00', (string) $e['stats_observed_at']);
    }

    public function testForwardObservationsStillApply(): void
    {
        $endorse = $this->seedEndorse();
        $sync = new Endorse_sync();
        $this->assertTrue($sync->apply($this->endorse(), $this->response(500, 90, '2026-08-03 10:00:00.000000'), 1)['status']);
        $this->assertTrue($sync->apply($this->endorse(), $this->response(1000, 200, '2026-08-03 10:05:00.000000'), 1)['status']);
        $e = $this->endorse();
        $this->assertSame(1000, intval($e['views']), 'a genuinely newer observation must apply');
        $this->assertSame(200, intval($e['likes']));
    }
}
