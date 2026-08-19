<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../application/libraries/Template.php';

/**
 * The provider mock is only useful if the REAL parsers accept what it serves.
 *
 * If the rehydration <script> tag drifts by one character, extractTiktokItemStructFromHtml
 * returns [] for every post, every scrape reads as a miss, and the whole load test silently
 * measures the fallback path while reporting a scrape success rate of zero. That failure is
 * invisible in the throughput numbers and would invalidate the leg census, the amplification
 * ratio, and the capacity envelope built on them.
 *
 * So the contract is pinned here, against the production Template methods, not against a
 * copy of them.
 *
 * Requires a running mock:
 *   FORBES_MOCK_PROVIDER_URL=http://127.0.0.1:18080 php vendor/bin/phpunit -c phpunit-integration.xml
 *
 * @internal
 */
final class ProviderMockContractTest extends TestCase
{
    private string $base = '';

    protected function setUp(): void
    {
        $url = trim((string) getenv('FORBES_MOCK_PROVIDER_URL'));
        if ($url === '') {
            if (getenv('FORBES_REQUIRE_MOCK') === '1') {
                $this->fail('FORBES_MOCK_PROVIDER_URL is required when FORBES_REQUIRE_MOCK=1');
            }
            $this->markTestSkipped('FORBES_MOCK_PROVIDER_URL not set; start tools/loadtest stack first');
        }

        $this->base = rtrim($url, '/');
        $this->pinProfile(1.0, 1.0, 0.0);
    }

    /**
     * Reset to a known profile: the mock retains whatever the previous run configured.
     */
    private function pinProfile(float $scrapeRate, float $rapidRate, float $correlation, array $extra = []): void
    {
        $latency = ['p50_ms' => 1, 'p95_ms' => 2, 'p99_ms' => 3];
        $payload = array_merge([
            'seed'        => 'contract',
            'correlation' => $correlation,
            'scrape'      => array_merge(['success_rate' => $scrapeRate, 'latency' => $latency], $extra['scrape'] ?? []),
            'rapidapi'    => array_merge(['success_rate' => $rapidRate, 'latency' => $latency], $extra['rapidapi'] ?? []),
        ], []);

        $this->post('/_control/profile', json_encode($payload));
        $this->post('/_control/reset');
    }

    private function post(string $path, string $body = ''): string
    {
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST', 'header' => "Content-Type: application/json\r\n",
            'content' => $body, 'ignore_errors' => true, 'timeout' => 10,
        ]]);

        return (string) @file_get_contents($this->base . $path, false, $ctx);
    }

    private function get(string $path): array
    {
        $ctx     = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 40]]);
        $body    = (string) @file_get_contents($this->base . $path, false, $ctx);
        $headers = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?: [])
            : [];

        return ['body' => $body, 'headers' => implode("\n", $headers)];
    }

    private function probe(): ProviderMockContractProbe
    {
        return new ProviderMockContractProbe(parse_url($this->base, PHP_URL_HOST) . ':' . parse_url($this->base, PHP_URL_PORT));
    }

    public function testScrapeHtmlSatisfiesTheRealRehydrationParser(): void
    {
        $probe = $this->probe();
        $html  = $this->get('/@mockuser/video/7300000000000000001')['body'];

        $item = $probe->parseHtml($html);
        $this->assertNotSame([], $item, 'the <script id="__UNIVERSAL_DATA_FOR_REHYDRATION__"> tag must match byte for byte');
        $this->assertTrue($probe->scrapeUsable($item), 'isValidTiktokScrapeItem must accept the mock stats block');
        $this->assertSame('7300000000000000001', (string) ($item['id'] ?? ''), 'content id must round-trip from the URL');
        $this->assertGreaterThan(0, (int) ($item['stats']['playCount'] ?? 0));
    }

    public function testRapidApiBodySatisfiesTheRealValidatorViaTheRealUrlBuilder(): void
    {
        $probe = $this->probe();

        // Built by buildTiktokDetailUrl so the path and query shape are proven too, not just
        // the response body.
        $url = $probe->detailUrl('https://tiktok-mock.local/@u/video/7300000000000000001', 0);
        $this->assertStringContainsString('/index/Tiktok/getVideoInfo?', $url);

        $body = $this->get('/index/Tiktok/getVideoInfo?url=' . rawurlencode('https://tiktok-mock.local/@u/video/7300000000000000001') . '&hd=0')['body'];
        $json = json_decode($body, true);

        $this->assertSame(0, (int) ($json['code'] ?? -1));
        $this->assertTrue($probe->rapidValid($json), 'isValidRapidApiTiktokDetailResponse must accept the mock payload');
    }

    /**
     * HTTP 200 with no rehydration payload is how a private/removed post actually presents.
     */
    public function testNoScriptTagFailureYieldsAnEmptyItemStruct(): void
    {
        $this->pinProfile(0.0, 1.0, 0.0, ['scrape' => ['failures' => ['no_script_tag' => 1.0]]]);

        $html = $this->get('/@mockuser/video/7300000000000000002')['body'];
        $this->assertSame([], $this->probe()->parseHtml($html));
    }

    /**
     * The 429 path drives retryAfterSeconds() and retryDelayWithJitter()'s
     * max(0, retry_after, base + jitter) lower bound, so the header must actually be there.
     */
    public function testRateLimitedResponseCarriesRetryAfterAndQuotaHeaders(): void
    {
        $this->pinProfile(1.0, 0.0, 0.0, ['rapidapi' => [
            'failures' => ['http_429' => 1.0], 'retry_after_sec' => 7,
        ]]);

        $res = $this->get('/index/Tiktok/getVideoInfo?url=' . rawurlencode('https://t/video/7300000000000000003') . '&hd=0');

        $this->assertStringContainsString('429', $res['headers']);
        $this->assertMatchesRegularExpression('/retry-after:\s*7/i', $res['headers']);
        $this->assertMatchesRegularExpression('/x-ratelimit-requests-remaining:/i', $res['headers']);
        $this->assertIsArray(json_decode($res['body'], true), 'a 429 body must still be parseable JSON');
    }

    /**
     * The leg census compares run A (scrape only) with run B (rapidapi only) per queue_id.
     * That comparison is meaningless unless a given post gets the same outcome every time.
     */
    public function testOutcomesAreDeterministicPerPost(): void
    {
        $first = $this->get('/@u/video/7300000000000000009')['body'];
        $this->post('/_control/reset');
        $second = $this->get('/@u/video/7300000000000000009')['body'];

        $this->assertSame($first, $second);
    }

    /**
     * Dead/private/removed videos fail BOTH legs. Without this the fallback's measured
     * success rate is optimistic and the requests-per-completion prediction comes out wrong
     * in the direction that matters.
     */
    public function testCorrelationMakesFallbackFailForPostsWhoseScrapeFailed(): void
    {
        $this->pinProfile(0.0, 1.0, 1.0, ['scrape' => ['failures' => ['no_script_tag' => 1.0]]]);

        $id = '7300000000000000123';
        $this->get('/@u/video/' . $id);                       // leg 1 fails, mock remembers
        $body = $this->get('/index/Tiktok/getVideoInfo?url=' . rawurlencode('https://t/video/' . $id) . '&hd=0')['body'];

        $json = json_decode($body, true);
        $this->assertNotSame(0, (int) ($json['code'] ?? -1), 'correlation=1.0 must deny the fallback for a post whose scrape failed');
    }

    public function testStatsReportGroundTruthPerLeg(): void
    {
        $this->pinProfile(1.0, 1.0, 0.0);

        $this->get('/@u/video/7300000000000000010');
        $this->get('/@u/video/7300000000000000011');
        $this->get('/index/Tiktok/getVideoInfo?url=' . rawurlencode('https://t/video/7300000000000000010') . '&hd=0');

        $stats = json_decode($this->get('/_control/stats')['body'], true);

        $this->assertSame(2, (int) ($stats['scrape']['requests'] ?? 0));
        $this->assertSame(2, (int) ($stats['scrape']['successes'] ?? 0));
        $this->assertSame(1, (int) ($stats['rapidapi']['requests'] ?? 0));
    }
}

/**
 * Exposes the protected Template parsers. Subclassing is the same seam the load-test fetch
 * path uses, so this test also proves that seam keeps working.
 *
 * @internal
 */
final class ProviderMockContractProbe extends Template
{
    private string $host;

    public function __construct(string $host)
    {
        $this->host = $host;
    }

    public function parseHtml(string $html): array
    {
        return $this->extractTiktokItemStructFromHtml($html);
    }

    public function scrapeUsable(array $item): bool
    {
        return $this->isValidTiktokScrapeItem($item);
    }

    public function rapidValid($response): bool
    {
        return $this->isValidRapidApiTiktokDetailResponse($response);
    }

    public function detailUrl(string $url, int $hd = 0): string
    {
        return $this->buildTiktokDetailUrl($url, $hd);
    }

    /**
     * env() reads FCPATH.'.env' and PREFERS it over getenv(), so putenv() cannot redirect the
     * host in-process. Overriding the accessor keeps buildTiktokDetailUrl itself under test.
     */
    protected function getRapidApiConfig(): array
    {
        return ['host' => $this->host, 'key' => 'mock'];
    }
}
