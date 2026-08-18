<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once __DIR__ . '/support/QueueSchema.php';

/**
 * PL-05 — pin the schema contract every other integration suite now runs against.
 *
 * These assertions read the DDL MySQL actually materialised from the production migration
 * up() path, so a migration that silently changes a type, enum, generated column or unique
 * key fails here instead of in production.
 *
 * @internal
 */
#[Group('integration')]
final class EndorseRefreshSchemaContractTest extends TestCase
{
    private static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        $spec = getenv('FORBES_TEST_DB');
        if ($spec === false || $spec === '') {
            if (getenv('FORBES_REQUIRE_DB') === '1') {
                self::fail('FORBES_REQUIRE_DB=1 but FORBES_TEST_DB is not set: the MySQL suite must fail, never skip.');
            }

            return;
        }
        $cfg = [];

        foreach (explode(';', $spec) as $pair) {
            [$k, $v]       = array_pad(explode('=', $pair, 2), 2, '');
            $cfg[trim($k)] = trim($v);
        }
        self::$pdo = new PDO(
            "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']}",
            $cfg['user'],
            $cfg['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        QueueSchema::build(self::$pdo);
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('FORBES_TEST_DB not set.');
        }
    }

    public function testQueueTableCarriesTheClaimAndLeaseContract(): void
    {
        $ddl = QueueSchema::showCreate(self::$pdo, 'endorse_refresh_queue');

        // Scheduling columns, with the precision the code compares against.
        $this->assertMatchesRegularExpression('/`lease_expires_at` datetime\(6\)/i', $ddl);
        $this->assertMatchesRegularExpression('/`next_attempt_at` datetime\(6\)/i', $ddl);
        // claimed_at is deliberately second-precision in production; the code must not
        // assume microseconds here.
        $this->assertMatchesRegularExpression('/`claimed_at` datetime /i', $ddl);

        // Ownership and attempt identity.
        $this->assertMatchesRegularExpression("/`claim_owner` enum\\('cron','rust'\\)/i", $ddl);
        $this->assertMatchesRegularExpression('/`attempt_sequence` int unsigned/i', $ddl);
        $this->assertMatchesRegularExpression('/`active_attempt_id` bigint unsigned/i', $ddl);

        // Queue status values the recovery and claim predicates rely on.
        $this->assertMatchesRegularExpression(
            "/`status` enum\\('pending','processing','submitted','completed','failed'\\)/i",
            $ddl,
        );

        // Active business-scope uniqueness, via its stored generated column.
        $this->assertMatchesRegularExpression('/`active_business_slot` tinyint unsigned GENERATED ALWAYS AS .* STORED/i', $ddl);
        $this->assertMatchesRegularExpression(
            '/UNIQUE KEY `uq_active_endorse_purpose` \(`id_endorse`,`purpose`,`active_business_slot`\)/i',
            $ddl,
        );
    }

    public function testAttemptTableCarriesTheAttemptIdentityContract(): void
    {
        $ddl = QueueSchema::showCreate(self::$pdo, 'endorse_refresh_queue_attempts');

        // Widened attempt number: TINYINT would cap a long-lived row at 127.
        $this->assertMatchesRegularExpression('/`attempt_no` int unsigned/i', $ddl);

        // Every state the recovery policy switches on must exist.
        foreach (['processing', 'submitted', 'retrying', 'completed', 'failed', 'cancelled', 'timed_out'] as $state) {
            $this->assertStringContainsString("'{$state}'", $ddl, "attempt status '{$state}' missing from the enum");
        }

        // One attempt number per queue row, and at most one OPEN attempt per queue row.
        $this->assertMatchesRegularExpression('/UNIQUE KEY `uq_queue_attempt` \(`queue_id`,`attempt_no`\)/i', $ddl);
        $this->assertMatchesRegularExpression('/`active_queue_id` int unsigned GENERATED ALWAYS AS .* STORED/i', $ddl);
        $this->assertMatchesRegularExpression('/UNIQUE KEY `uq_active_queue_attempt` \(`active_queue_id`\)/i', $ddl);
    }

    public function testReservationTableCarriesTheProviderLedgerContract(): void
    {
        $ddl = QueueSchema::showCreate(self::$pdo, 'endorse_refresh_rate_tokens');
        $this->assertMatchesRegularExpression('/`provider_scope`/i', $ddl);
        // Microsecond precision: the rolling window is compared with NOW(6).
        $this->assertMatchesRegularExpression('/`created_at` datetime\(6\)/i', $ddl);
    }

    /**
     * The unique keys are enforced, not merely declared.
     */
    public function testOnlyOneOpenAttemptPerQueueIsAccepted(): void
    {
        QueueSchema::reset(self::$pdo);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, status, started_at, created_at)
            VALUES (77, 1, 'processing', NOW(), NOW())");

        $this->expectException(PDOException::class);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, status, started_at, created_at)
            VALUES (77, 2, 'processing', NOW(), NOW())");
    }

    public function testAttemptNumberCannotBeReusedForTheSameQueue(): void
    {
        QueueSchema::reset(self::$pdo);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, status, started_at, created_at)
            VALUES (78, 1, 'failed', NOW(), NOW())");

        $this->expectException(PDOException::class);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts (queue_id, attempt_no, status, started_at, created_at)
            VALUES (78, 1, 'timed_out', NOW(), NOW())");
    }

    /**
     * Enqueue idempotency depends on this: one active queue row per (endorse, purpose).
     */
    public function testOnlyOneActiveQueueRowPerBusinessScope(): void
    {
        QueueSchema::reset(self::$pdo);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue (id_endorse, id_campaign, platform, link_upload, purpose, status, created_at)
            VALUES (5, 100, 'Tiktok', 'https://x/1', 'daily', 'pending', NOW())");

        $this->expectException(PDOException::class);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue (id_endorse, id_campaign, platform, link_upload, purpose, status, created_at)
            VALUES (5, 100, 'Tiktok', 'https://x/1', 'daily', 'processing', NOW())");
    }

    /**
     * Terminal rows are historical and must not consume the active slot.
     */
    public function testTerminalRowsDoNotOccupyTheActiveBusinessSlot(): void
    {
        QueueSchema::reset(self::$pdo);

        foreach (['completed', 'failed', 'completed'] as $status) {
            self::$pdo->exec("INSERT INTO endorse_refresh_queue (id_endorse, id_campaign, platform, link_upload, purpose, status, created_at)
                VALUES (6, 100, 'Tiktok', 'https://x/1', 'daily', '{$status}', NOW())");
        }
        self::$pdo->exec("INSERT INTO endorse_refresh_queue (id_endorse, id_campaign, platform, link_upload, purpose, status, created_at)
            VALUES (6, 100, 'Tiktok', 'https://x/1', 'daily', 'pending', NOW())");

        $this->assertSame(
            '4',
            (string) self::$pdo->query('SELECT COUNT(*) FROM endorse_refresh_queue WHERE id_endorse=6')->fetch(PDO::FETCH_NUM)[0],
            'history plus one active row must coexist',
        );
    }
}
