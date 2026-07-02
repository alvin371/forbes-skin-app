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
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';

final class TiktokRapidApiTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('ENDORSE_TIKTOK_SCRAPE_ENABLED=0');
        putenv('TIKTOK_METRICS_PREFER_RAPIDAPI=0');
        putenv('RAPIDAPI_HOST=test-rapidapi.example');
        putenv('RAPIDAPI_KEY=test-key');
    }

    public function testCurlRequestWithRetryStopsOnConfigFailure(): void
    {
        $template = new TemplateTransportProbe();
        $template->queuedResponses = [
            [
                'status' => false,
                'msg' => 'Konfigurasi RapidAPI tidak lengkap.',
                'data' => [],
                'error_class' => 'config',
                'error_meta' => [],
            ],
            [
                'code' => 0,
                'data' => ['user' => ['uniqueId' => 'should-not-be-used']],
            ],
        ];

        $result = $template->curlRequestWithRetry('https://example.test', [], function ($resp) {
            return false;
        }, 3, 0);

        $this->assertSame(1, $template->curlRequestCalls);
        $this->assertSame('config', $result['error_class']);
    }

    public function testGetSocialMediaAcceptsTikTokDetailPayloadWithoutDataId(): void
    {
        $template = new TemplateTransportProbe();
        $template->queuedResponses = [[
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'digg_count' => 11,
                'share_count' => 2,
                'comment_count' => 3,
                'collect_count' => 4,
                'play_count' => 25,
                'images' => ['https://cdn.example/image-1.jpg'],
                'cover' => 'https://cdn.example/cover.jpg',
            ],
        ]];

        $result = $template->get_social_media(
            'Tiktok',
            'https://www.tiktok.com/@espresso/photo/7653084884126272785?lang=en',
            true,
            null,
            true
        );

        $this->assertTrue($result['status']);
        $this->assertSame('7653084884126272785', $result['data']['content_id']);
        $this->assertSame(25, $result['data']['view']);
        $this->assertSame('photo', $result['data']['media_type']);
        $this->assertSame(['https://cdn.example/image-1.jpg'], $result['data']['images']);
    }

    public function testGetSocialMediaReturnsInfraFailureDetails(): void
    {
        $template = new TemplateTransportProbe();
        $template->queuedResponses = [[
            'status' => false,
            'msg' => 'RapidAPI host tidak dapat dijangkau: http=0 cURL#28=Resolving timed out after 5001 milliseconds',
            'data' => [],
            'error_class' => 'infra',
            'error_meta' => [
                'http_code' => 0,
                'curl_errno' => 28,
                'curl_error' => 'Resolving timed out after 5001 milliseconds',
                'rapidapi_code' => 'n/a',
                'rapidapi_msg' => 'n/a',
                'json_error' => '',
                'body_snippet' => '',
                'total_time' => 5.001,
                'multi_result' => 28,
            ],
        ]];

        $result = $template->get_social_media(
            'Tiktok',
            'https://www.tiktok.com/@espresso/photo/7653084884126272785?lang=en',
            true,
            null,
            true
        );

        $this->assertFalse($result['status']);
        $this->assertSame('infra', $result['error_class']);
        $this->assertStringContainsString('http=0', $result['msg']);
        $this->assertStringContainsString('cURL#28=Resolving timed out after 5001 milliseconds', $result['msg']);
    }

    public function testGetDataFromFirstEndpointPreservesTransportFailure(): void
    {
        $template = new TemplateTransportProbe();
        $template->queuedResponses = [[
            'status' => false,
            'msg' => 'RapidAPI credentials ditolak: http=403',
            'data' => [],
            'error_class' => 'config',
            'error_meta' => [
                'http_code' => 403,
                'curl_errno' => 0,
                'curl_error' => '',
                'rapidapi_code' => '403',
                'rapidapi_msg' => 'Forbidden',
                'json_error' => '',
                'body_snippet' => '',
                'total_time' => 0.2,
                'multi_result' => 0,
            ],
        ]];

        $result = $template->getDataFromFirstEndpoint('@espresso');

        $this->assertFalse($result['status']);
        $this->assertSame('config', $result['error_class']);
        $this->assertSame('first_endpoint', $result['source']);
        $this->assertStringNotContainsString('tidak ditemukan', strtolower($result['msg']));
    }

    public function testEndorseSyncHonorsMachineReadableInfraClass(): void
    {
        $sync = new EndorseSyncProbe();

        $result = $sync->classify_response([
            'status' => false,
            'msg' => 'RapidAPI host tidak dapat dijangkau: http=0 cURL#28=Resolving timed out after 5001 milliseconds',
            'data' => [],
            'error_class' => 'infra',
        ], 'Tiktok', 'https://www.tiktok.com/@espresso/photo/7653084884126272785?lang=en');

        $this->assertSame(Endorse_sync::ERR_INFRA, $result['class']);
    }
}

final class TemplateTransportProbe extends Template
{
    public $queuedResponses = [];
    public $curlRequestCalls = 0;

    public function curlRequest($url, $headers = [])
    {
        $this->curlRequestCalls++;
        return array_shift($this->queuedResponses);
    }

    protected function getRapidApiConfig(): array
    {
        return [
            'host' => 'test-rapidapi.example',
            'key' => 'test-key',
        ];
    }

    protected function isTiktokScrapeEnabled(): bool
    {
        return false;
    }

    protected function preferRapidApiForTiktok(bool $preferRapidApi): bool
    {
        return $preferRapidApi;
    }
}

final class EndorseSyncProbe extends Endorse_sync
{
    public function __construct()
    {
    }
}
