<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/support/QueueSchema.php';

/**
 * @internal
 */
#[Group('integration')]
final class EndorseRefreshSchemaMigrationTest extends TestCase
{
    private static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        $spec = getenv('FORBES_TEST_DB');
        if ($spec === false || $spec === '') {
            if (getenv('FORBES_REQUIRE_DB') === '1') {
                self::fail('FORBES_REQUIRE_DB=1 but FORBES_TEST_DB is unset.');
            }

            return;
        }
        $cfg = [];

        foreach (explode(';', $spec) as $pair) {
            [$key, $value]   = array_pad(explode('=', $pair, 2), 2, '');
            $cfg[trim($key)] = trim($value);
        }
        // This suite deliberately starts from a PRE-migration schema, so it runs in its own
        // database and can never disturb the canonical one other suites share.
        $database = QueueSchema::isolatedDatabase($cfg, 'migration');
        $pdo      = new PDO(
            "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$database}",
            $cfg['user'],
            $cfg['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $pdo->exec('DROP TABLE IF EXISTS endorse_refresh_queue_attempts');
        $pdo->exec('DROP TABLE IF EXISTS endorse_refresh_queue');
        $pdo->exec("
            CREATE TABLE endorse_refresh_queue (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_endorse INT UNSIGNED NOT NULL,
                purpose VARCHAR(20) NOT NULL DEFAULT 'daily',
                status ENUM('pending','processing','submitted','completed','failed') NOT NULL DEFAULT 'pending',
                attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                attempt_sequence INT UNSIGNED NOT NULL DEFAULT 0,
                active_attempt_id BIGINT UNSIGNED NULL,
                claimed_at DATETIME(6) NULL
            ) ENGINE=InnoDB
        ");
        $pdo->exec("
            CREATE TABLE endorse_refresh_queue_attempts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                queue_id INT UNSIGNED NOT NULL,
                attempt_no TINYINT UNSIGNED NOT NULL,
                worker_id CHAR(36) NULL,
                status ENUM('processing','submitted','retrying','completed','failed','cancelled') NOT NULL DEFAULT 'processing'
            ) ENGINE=InnoDB
        ");
        $direction = 'up';
        require __DIR__ . '/../../migrations/20260818170000_harden_endorse_refresh_claims.php';
        self::$pdo = $pdo;
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('FORBES_TEST_DB not set.');
        }
        self::$pdo->exec('DELETE FROM endorse_refresh_queue_attempts');
        self::$pdo->exec('DELETE FROM endorse_refresh_queue');
    }

    public function testMigrationAddsLeaseAndActiveUniqueness(): void
    {
        $lease = self::$pdo->query("SHOW COLUMNS FROM endorse_refresh_queue LIKE 'lease_expires_at'")->fetchAll();
        $this->assertCount(1, $lease);
        $attemptNo = self::$pdo->query("SHOW COLUMNS FROM endorse_refresh_queue_attempts LIKE 'attempt_no'")->fetch(PDO::FETCH_ASSOC);
        $this->assertStringContainsString('int unsigned', strtolower($attemptNo['Type']));

        self::$pdo->exec("INSERT INTO endorse_refresh_queue (id_endorse,purpose,status) VALUES (7,'daily','pending')");
        $this->expectException(PDOException::class);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue (id_endorse,purpose,status) VALUES (7,'daily','processing')");
    }

    public function testOnlyOneProcessingAttemptIsAllowedPerQueue(): void
    {
        self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts (queue_id,attempt_no,status) VALUES (99,1,'processing')");
        $this->expectException(PDOException::class);
        self::$pdo->exec("INSERT INTO endorse_refresh_queue_attempts (queue_id,attempt_no,status) VALUES (99,2,'processing')");
    }
}
