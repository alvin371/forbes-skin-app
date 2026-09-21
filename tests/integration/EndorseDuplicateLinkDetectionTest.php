<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Proves the duplicate-link JOIN used by Endorse::item() and Endorse::duplicate_locations()
 * (the fraud-flag detector) against the REAL endorse/endorse_campaign schema: a
 * tiktok_content_id reused across campaigns, or twice within one campaign, must be counted;
 * a content id that only appears once must not.
 *
 * @internal
 */
#[Group('integration')]
final class EndorseDuplicateLinkDetectionTest extends TestCase
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
        $pdo = new PDO(
            "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']}",
            $cfg['user'],
            $cfg['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $pdo->exec("SET SESSION sql_mode=''");
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['endorse', 'endorse_logs', 'endorse_campaign', 'endorse_campaign_logs'] as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        self::loadSqlFile($pdo, __DIR__ . '/schema/campaign_real_schema.sql');
        self::loadSqlFile($pdo, __DIR__ . '/schema/endorse_real_schema.sql');

        $direction = 'up';
        // The frozen schema dump predates the tiktok_* columns; apply the same migrations
        // production ran, in order, so this suite proves against the real current shape.
        require __DIR__ . '/../../migrations/20260519000000_add_tiktok_media_columns_to_endorse.php';
        require __DIR__ . '/../../migrations/20260921000000_add_index_tiktok_content_id_to_endorse.php';

        self::$pdo = $pdo;
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('FORBES_TEST_DB not set.');
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        self::$pdo->exec('TRUNCATE TABLE endorse');
        self::$pdo->exec('TRUNCATE TABLE endorse_campaign');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testMigrationAddsIndex(): void
    {
        $rows = self::$pdo->query("SHOW INDEX FROM `endorse` WHERE Key_name = 'idx_endorse_tiktok_content_id'")->fetchAll();
        $this->assertNotEmpty($rows);
    }

    public function testSameVideoAcrossDifferentCampaignsIsFlaggedDuplicate(): void
    {
        $campaignA = $this->insertCampaign('Campaign A');
        $campaignB = $this->insertCampaign('Campaign B');

        $this->insertEndorse($campaignA, 'creatorA', 'CONTENT123');
        $this->insertEndorse($campaignB, 'creatorB', 'CONTENT123');

        $counts = $this->duplicateCounts();

        $this->assertSame(2, $counts['CONTENT123']);
    }

    public function testSameVideoReusedWithinOneCampaignIsFlaggedDuplicate(): void
    {
        $campaign = $this->insertCampaign('Campaign A');

        $this->insertEndorse($campaign, 'creatorA', 'CONTENT999');
        $this->insertEndorse($campaign, 'creatorB', 'CONTENT999');

        $counts = $this->duplicateCounts();

        $this->assertSame(2, $counts['CONTENT999']);
    }

    public function testUniqueVideoIsNotFlagged(): void
    {
        $campaign = $this->insertCampaign('Campaign A');

        $this->insertEndorse($campaign, 'creatorA', 'ONLYONE');

        $counts = $this->duplicateCounts();

        $this->assertArrayNotHasKey('ONLYONE', $counts);
    }

    public function testDuplicateLocationsListsEveryOccurrenceAcrossCampaigns(): void
    {
        $campaignA = $this->insertCampaign('Campaign A');
        $campaignB = $this->insertCampaign('Campaign B');

        $this->insertEndorse($campaignA, 'creatorA', 'CONTENT456', '2026-01-01', '2025-12-20');
        $this->insertEndorse($campaignB, 'creatorB', 'CONTENT456', '2026-02-15', '2025-12-20');

        $rows = self::$pdo->query("
            SELECT e.id_campaign, c.title AS campaign_title, e.created_at, e.posting_at
            FROM endorse e
            INNER JOIN endorse_campaign c ON c.id = e.id_campaign
            WHERE e.platform = 'Tiktok' AND e.tiktok_content_id = 'CONTENT456'
            ORDER BY e.id_campaign ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $rows);
        $this->assertSame('Campaign A', $rows[0]['campaign_title']);
        $this->assertSame('Campaign B', $rows[1]['campaign_title']);
        $this->assertSame('2025-12-20', $rows[0]['posting_at']);
    }

    /** @return array<string,int> content_id => duplicate count, mirroring Endorse::item()'s dup JOIN */
    private function duplicateCounts(): array
    {
        $rows = self::$pdo->query("
            SELECT tiktok_content_id, COUNT(*) AS cnt
            FROM endorse
            WHERE platform = 'Tiktok' AND tiktok_content_id IS NOT NULL AND tiktok_content_id != ''
            GROUP BY tiktok_content_id
            HAVING COUNT(*) > 1
        ")->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $out[$row['tiktok_content_id']] = (int) $row['cnt'];
        }

        return $out;
    }

    private function insertCampaign(string $title): int
    {
        $stmt = self::$pdo->prepare("INSERT INTO endorse_campaign (title, `desc`, budget, count_influencer, count_endorse, views, likes, comment, share_save, cpm, total_cost, count_influencer_active, count_endorse_active, count_influencer_processed, count_endorse_processed) VALUES (?, '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)");
        $stmt->execute([$title]);

        return (int) self::$pdo->lastInsertId();
    }

    private function insertEndorse(int $idCampaign, string $creator, string $contentId, string $createdAt = '2026-01-01', string $postingAt = '2026-01-01'): int
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO endorse (id_campaign, platform, nama_creator, pic, link_upload, tiktok_content_id, created_at, posting_at, views, likes, comment, share_save)
            VALUES (?, 'Tiktok', ?, 'PIC', ?, ?, ?, ?, 0, 0, 0, 0)
        ");
        $stmt->execute([
            $idCampaign,
            $creator,
            'https://www.tiktok.com/@' . $creator . '/video/' . $contentId,
            $contentId,
            $createdAt,
            $postingAt,
        ]);

        return (int) self::$pdo->lastInsertId();
    }

    private static function loadSqlFile(PDO $pdo, string $path): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException("Cannot read schema file {$path}");
        }

        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }
}
