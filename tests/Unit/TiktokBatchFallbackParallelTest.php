<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

if (! function_exists('env')) {
    function env($key, $default = null)
    {
        $value = getenv($key);

        return $value !== false ? $value : $default;
    }
}

require_once __DIR__ . '/../../application/libraries/Template.php';

/**
 * Guards the scrape-enabled batch path in Template::get_social_media_batch.
 *
 * Production ran ~8 completions/minute because this path handled leg-1 failures with a
 * BLOCKING get_social_media() call per item, which additionally issued a third leg — a
 * byte-identical re-request of the scrape that had just failed in the same tick. Worst case
 * ~32s of wall clock for one item against a 45s tick budget.
 *
 * These tests pin the two properties that fix depends on: the fallback is issued as ONE
 * batched call, and the redundant third leg is never issued.
 *
 * @internal
 */
final class TiktokBatchFallbackParallelTest extends TestCase
{
    private function tasks(int $n): array
    {
        $tasks = array();
        for ($i = 1; $i <= $n; $i++) {
            $tasks[$i] = array('platform' => 'Tiktok', 'url' => 'https://www.tiktok.com/@a/video/' . $i);
        }

        return $tasks;
    }

    public function testFallbackIsIssuedAsOneBatchedCallNotOnePerItem(): void
    {
        $probe = new BatchFallbackProbe();
        // Items 2 and 4 fail leg 1; 1, 3, 5 succeed.
        $probe->scrapeFailures = array(2, 4);

        $probe->get_social_media_batch($this->tasks(5), 10, 45.0);

        $this->assertSame(
            1,
            $probe->fallbackBatchCalls,
            'the fallback must be ONE parallel batch per chunk, not one blocking call per item'
        );
        $this->assertSame(
            array(2, 4),
            $probe->fallbackBatchTaskKeys,
            'only the items whose leg 1 failed may be sent to the fallback'
        );
    }

    public function testRedundantThirdScrapeLegIsNeverIssued(): void
    {
        $probe = new BatchFallbackProbe();
        $probe->scrapeFailures = array(1, 2, 3);

        $probe->get_social_media_batch($this->tasks(3), 10, 45.0);

        // scrapeTiktokDetailFromPage re-requests the SAME url the leg-1 batch just tried,
        // with the same UA, cookie and timeouts. Retrying a just-failed scrape is the queue's
        // job — it does it with a real attempt row and Retry-After support.
        $this->assertSame(0, $probe->thirdLegScrapeCalls, 'the redundant third leg must be gone');
        $this->assertSame(0, $probe->getSocialMediaCalls, 'the batch path must not fall back to the per-item call');
    }

    public function testSuccessfulScrapesNeverReachTheFallback(): void
    {
        $probe = new BatchFallbackProbe();
        $probe->scrapeFailures = array();

        $results = $probe->get_social_media_batch($this->tasks(4), 10, 45.0);

        $this->assertSame(0, $probe->fallbackBatchCalls, 'no fallback when every scrape succeeds');
        $this->assertCount(4, $results);
        foreach (array(1, 2, 3, 4) as $idx) {
            $this->assertTrue($results[$idx]['status'], "item {$idx} should be satisfied by the scrape");
            $this->assertSame('direct_scrape', $results[$idx]['request_meta']['provider'] ?? null);
        }
    }

    public function testResultsStayKeyedToTheirTaskAcrossBothLegs(): void
    {
        // A mis-keyed result would attribute one post's metrics to another — silent data
        // corruption that no throughput measurement would reveal.
        $probe = new BatchFallbackProbe();
        $probe->scrapeFailures = array(2, 3);

        $results = $probe->get_social_media_batch($this->tasks(4), 10, 45.0);

        $this->assertSame('direct_scrape', $results[1]['request_meta']['provider']);
        $this->assertSame('direct_scrape+rapidapi', $results[2]['request_meta']['provider']);
        $this->assertSame('direct_scrape+rapidapi', $results[3]['request_meta']['provider']);
        $this->assertSame('direct_scrape', $results[4]['request_meta']['provider']);

        $this->assertSame('video/2', $results[2]['fallback_echo']);
        $this->assertSame('video/3', $results[3]['fallback_echo']);
    }

    public function testChunkingIssuesOneFallbackBatchPerChunk(): void
    {
        $probe = new BatchFallbackProbe();
        $probe->scrapeFailures = range(1, 6);

        // Concurrency 3 over 6 tasks = 2 chunks, so 2 fallback batches — never 6 calls.
        $probe->get_social_media_batch($this->tasks(6), 3, 45.0);

        $this->assertSame(2, $probe->fallbackBatchCalls);
    }
}

/**
 * @internal
 */
final class BatchFallbackProbe extends Template
{
    /** @var int[] task keys whose leg-1 scrape should fail */
    public $scrapeFailures = array();

    public $fallbackBatchCalls = 0;
    public $fallbackBatchTaskKeys = array();
    public $thirdLegScrapeCalls = 0;
    public $getSocialMediaCalls = 0;

    public function __construct()
    {
    }

    protected function isTiktokScrapeEnabled(): bool
    {
        return true;
    }

    protected function fetchTiktokDetailPagesBatch(array $tasks): array
    {
        $out = array();
        foreach ($tasks as $idx => $task) {
            $out[$idx] = in_array($idx, $this->scrapeFailures, true)
                ? array()
                : array(
                    'stats' => array('diggCount' => 1),
                    '_request_meta' => array('provider' => 'direct_scrape', 'requests_started' => 1, 'total_time' => 0.5),
                );
        }

        return $out;
    }

    protected function isValidTiktokScrapeItem($item)
    {
        return is_array($item) && $item !== array();
    }

    protected function mapDirectTiktokItemToResponse(array $response, array $item, bool $fetch_media_assets): array
    {
        $response['status'] = true;

        return $response;
    }

    protected function fetchRapidApiTiktokBatch(array $tasks, array $options = array()): array
    {
        $this->fallbackBatchCalls++;
        foreach (array_keys($tasks) as $key) {
            $this->fallbackBatchTaskKeys[] = $key;
        }

        $results = array();
        foreach ($tasks as $idx => $task) {
            $results[$idx] = array(
                'status' => true,
                'msg' => '',
                'data' => array(),
                // Echoes which url this result was built from, so a mis-keyed merge is visible.
                'fallback_echo' => substr(strval($task['url']), -7),
            );
        }

        return $results;
    }

    protected function scrapeTiktokDetailFromPage($url)
    {
        $this->thirdLegScrapeCalls++;

        return array();
    }

    public function get_social_media($type, $url, $fetch_media_assets = true, $influencer_id = null, $preferRapidApi = false, $known_content_id = null, int $maxProviderRequests = 2)
    {
        $this->getSocialMediaCalls++;

        return array('status' => false, 'msg' => 'should not be called from the batch path', 'data' => array());
    }
}
