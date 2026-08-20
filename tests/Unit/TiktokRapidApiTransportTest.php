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

/**
 * @internal
 */
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

    /**
     * The bug this pins: curl reports a CUMULATIVE time of ~0 for a phase it completed
     * instantly, and a resolver with the answer cached returns in ~0.000 s. The classifier
     * read `time_namelookup <= 0` as "DNS never finished" and returned infra_dns for read
     * timeouts that had plainly resolved, connected and negotiated TLS first.
     *
     * Measured on production 2026-08-20: DNS from inside the worker container took 1-5 ms, six
     * for six, while 454 of 631 transport failures in six hours were filed as infra_dns.
     */
    public function testCachedDnsTimeoutIsAStallNotADnsFailure(): void
    {
        $probe = new TemplateTransportProbe();

        $this->assertSame('infra_stall', $probe->classifyTransport([
            'curl_errno'         => 28,      // CURLE_OPERATION_TIMEDOUT
            'time_namelookup'    => 0.0,     // cached resolver answer
            'time_connect'       => 0.031,
            'time_appconnect'    => 0.084,
            'time_starttransfer' => 0.0,     // the response never began
            'total_time'         => 8.0,
        ]));
    }

    public function testTimeoutAfterBytesStartedFlowingIsAStall(): void
    {
        $probe = new TemplateTransportProbe();

        $this->assertSame('infra_stall', $probe->classifyTransport([
            'curl_errno'         => 28,
            'time_namelookup'    => 0.004,
            'time_connect'       => 0.031,
            'time_appconnect'    => 0.084,
            'time_starttransfer' => 1.2,
            'total_time'         => 8.0,
        ]));
    }

    /**
     * The reordering must not cost us the classifications that were already correct: an
     * explicit CURLE_COULDNT_RESOLVE_HOST is direct evidence and still outranks every timing.
     */
    public function testGenuineResolveFailureIsStillDns(): void
    {
        $probe = new TemplateTransportProbe();

        $this->assertSame('infra_dns', $probe->classifyTransport([
            'curl_errno'         => 6,       // CURLE_COULDNT_RESOLVE_HOST
            'time_namelookup'    => 0.0,
            'time_connect'       => 0.0,
            'time_appconnect'    => 0.0,
            'time_starttransfer' => 0.0,
            'total_time'         => 5.0,
        ]));
    }

    public function testExplicitConnectAndTlsErrnosOutrankTimings(): void
    {
        $probe = new TemplateTransportProbe();

        $this->assertSame('infra_connect', $probe->classifyTransport([
            'curl_errno'      => 7, 'time_namelookup' => 0.0, 'time_connect' => 0.0,
            'time_appconnect' => 0.0, 'time_starttransfer' => 0.0, 'total_time' => 2.0,
        ]));

        $this->assertSame('infra_tls', $probe->classifyTransport([
            'curl_errno'      => 35, 'time_namelookup' => 0.004, 'time_connect' => 0.03,
            'time_appconnect' => 0.0, 'time_starttransfer' => 0.0, 'total_time' => 2.0,
        ]));
    }

    /**
     * A timeout that never opened a socket is genuinely a connect failure, and one that
     * completed no phase at all remains DNS — the classifier still names the earliest phases
     * when the evidence actually supports them.
     */
    public function testTimeoutIsAttributedToThePhaseAfterTheLastCompletedOne(): void
    {
        $probe = new TemplateTransportProbe();

        // Resolved, but the socket never opened.
        $this->assertSame('infra_connect', $probe->classifyTransport([
            'curl_errno'      => 28, 'time_namelookup' => 0.004, 'time_connect' => 0.0,
            'time_appconnect' => 0.0, 'time_starttransfer' => 0.0, 'total_time' => 8.0,
        ]));

        // Connected, but the TLS handshake never finished.
        $this->assertSame('infra_tls', $probe->classifyTransport([
            'curl_errno'      => 28, 'time_namelookup' => 0.004, 'time_connect' => 0.03,
            'time_appconnect' => 0.0, 'time_starttransfer' => 0.0, 'total_time' => 8.0,
        ]));

        // Nothing completed at all.
        $this->assertSame('infra_dns', $probe->classifyTransport([
            'curl_errno'      => 28, 'time_namelookup' => 0.0, 'time_connect' => 0.0,
            'time_appconnect' => 0.0, 'time_starttransfer' => 0.0, 'total_time' => 8.0,
        ]));
    }

    public function testCurlRequestWithRetryStopsOnConfigFailure(): void
    {
        $template                  = new TemplateTransportProbe();
        $template->queuedResponses = [
            [
                'status'      => false,
                'msg'         => 'Konfigurasi RapidAPI tidak lengkap.',
                'data'        => [],
                'error_class' => 'config',
                'error_meta'  => [],
            ],
            [
                'code' => 0,
                'data' => ['user' => ['uniqueId' => 'should-not-be-used']],
            ],
        ];

        $result = $template->curlRequestWithRetry('https://example.test', [], static fn ($resp) => false, 3, 0);

        $this->assertSame(1, $template->curlRequestCalls);
        $this->assertSame('config', $result['error_class']);
    }

    public function testGetSocialMediaAcceptsTikTokDetailPayloadWithoutDataId(): void
    {
        $template                  = new TemplateTransportProbe();
        $template->queuedResponses = [[
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'digg_count'    => 11,
                'share_count'   => 2,
                'comment_count' => 3,
                'collect_count' => 4,
                'play_count'    => 25,
                'images'        => ['https://cdn.example/image-1.jpg'],
                'cover'         => 'https://cdn.example/cover.jpg',
            ],
        ]];

        $result = $template->get_social_media(
            'Tiktok',
            'https://www.tiktok.com/@espresso/photo/7653084884126272785?lang=en',
            true,
            null,
            true,
        );

        $this->assertTrue($result['status']);
        $this->assertSame('7653084884126272785', $result['data']['content_id']);
        $this->assertSame(25, $result['data']['view']);
        $this->assertSame('photo', $result['data']['media_type']);
        $this->assertSame(['https://cdn.example/image-1.jpg'], $result['data']['images']);
    }

    public function testGetSocialMediaReturnsInfraFailureDetails(): void
    {
        $template                  = new TemplateTransportProbe();
        $template->queuedResponses = [[
            'status'      => false,
            'msg'         => 'RapidAPI host tidak dapat dijangkau: http=0 cURL#28=Resolving timed out after 5001 milliseconds',
            'data'        => [],
            'error_class' => 'infra',
            'error_meta'  => [
                'http_code'     => 0,
                'curl_errno'    => 28,
                'curl_error'    => 'Resolving timed out after 5001 milliseconds',
                'rapidapi_code' => 'n/a',
                'rapidapi_msg'  => 'n/a',
                'json_error'    => '',
                'body_snippet'  => '',
                'total_time'    => 5.001,
                'multi_result'  => 28,
            ],
        ]];

        $result = $template->get_social_media(
            'Tiktok',
            'https://www.tiktok.com/@espresso/photo/7653084884126272785?lang=en',
            true,
            null,
            true,
        );

        $this->assertFalse($result['status']);
        $this->assertSame('infra', $result['error_class']);
        $this->assertStringContainsString('http=0', $result['msg']);
        $this->assertStringContainsString('cURL#28=Resolving timed out after 5001 milliseconds', $result['msg']);
    }

    public function testCurlRequestWithRetryRetriesInfraStall(): void
    {
        $template                  = new TemplateTransportProbe();
        $template->queuedResponses = [
            [
                'status'      => false,
                'msg'         => 'RapidAPI host tidak dapat dijangkau: http=0 cURL#28=Operation timed out after 12003 milliseconds with 0 bytes received',
                'data'        => [],
                'error_class' => 'infra_stall',
                'error_meta'  => [],
            ],
            [
                'code' => 0,
                'data' => ['user' => ['uniqueId' => 'espresso']],
            ],
        ];

        $result = $template->curlRequestWithRetry(
            'https://example.test',
            [],
            static fn ($resp) => (int) ($resp['code'] ?? -1) === 0,
            2,
            0,
        );

        $this->assertSame(2, $template->curlRequestCalls);
        $this->assertSame('espresso', $result['data']['user']['uniqueId']);
    }

    public function testCurlRequestWithRetryStopsOnInfraDns(): void
    {
        $template                  = new TemplateTransportProbe();
        $template->queuedResponses = [
            [
                'status'      => false,
                'msg'         => 'RapidAPI host tidak dapat dijangkau: http=0 cURL#6=Could not resolve host',
                'data'        => [],
                'error_class' => 'infra_dns',
                'error_meta'  => [],
            ],
            [
                'code' => 0,
                'data' => ['user' => ['uniqueId' => 'should-not-run']],
            ],
        ];

        $result = $template->curlRequestWithRetry('https://example.test', [], static fn ($resp) => false, 2, 0);

        $this->assertSame(1, $template->curlRequestCalls);
        $this->assertSame('infra_dns', $result['error_class']);
    }

    public function testGetDataFromFirstEndpointPreservesTransportFailure(): void
    {
        $template                  = new TemplateTransportProbe();
        $template->queuedResponses = [[
            'status'      => false,
            'msg'         => 'RapidAPI credentials ditolak: http=403',
            'data'        => [],
            'error_class' => 'config',
            'error_meta'  => [
                'http_code'     => 403,
                'curl_errno'    => 0,
                'curl_error'    => '',
                'rapidapi_code' => '403',
                'rapidapi_msg'  => 'Forbidden',
                'json_error'    => '',
                'body_snippet'  => '',
                'total_time'    => 0.2,
                'multi_result'  => 0,
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
            'status'      => false,
            'msg'         => 'RapidAPI host tidak dapat dijangkau: http=0 cURL#28=Resolving timed out after 5001 milliseconds',
            'data'        => [],
            'error_class' => 'infra',
        ], 'Tiktok', 'https://www.tiktok.com/@espresso/photo/7653084884126272785?lang=en');

        $this->assertSame(Endorse_sync::ERR_INFRA, $result['class']);
    }

    public function testEndorseSyncTreatsInfraStallAsRetryableMachineClass(): void
    {
        $sync = new EndorseSyncProbe();

        $result = $sync->classify_response([
            'status'      => false,
            'msg'         => 'RapidAPI host tidak dapat dijangkau: http=0 cURL#28=Operation timed out after 12003 milliseconds with 0 bytes received',
            'data'        => [],
            'error_class' => 'infra_stall',
        ], 'Tiktok', 'https://www.tiktok.com/@espresso/photo/7653084884126272785?lang=en');

        $this->assertSame(Endorse_sync::ERR_INFRA_STALL, $result['class']);
    }

    public function testBatchRetriesInfraStallOnceAndReturnsRecoveredPayload(): void
    {
        $template                         = new TemplateTransportProbe();
        $template->queuedExecuteResponses = [
            [
                'code' => 0,
                'msg'  => 'ok',
                'data' => [
                    'digg_count'    => 11,
                    'share_count'   => 2,
                    'comment_count' => 3,
                    'collect_count' => 4,
                    'play_count'    => 25,
                    'cover'         => 'https://cdn.example/cover.jpg',
                ],
            ],
        ];
        $template->queuedBatchResponses = [[
            'status'      => false,
            'msg'         => 'RapidAPI host tidak dapat dijangkau: http=0 cURL#28=Operation timed out after 12003 milliseconds with 0 bytes received',
            'data'        => [],
            'error_class' => 'infra_stall',
            'error_meta'  => [
                'http_code'          => 0,
                'curl_errno'         => 28,
                'curl_error'         => 'Operation timed out after 12003 milliseconds with 0 bytes received',
                'rapidapi_code'      => 'n/a',
                'rapidapi_msg'       => 'n/a',
                'json_error'         => '',
                'body_snippet'       => '',
                'total_time'         => 12.003,
                'time_namelookup'    => 0.01,
                'time_connect'       => 0.03,
                'time_appconnect'    => 0.08,
                'time_starttransfer' => 0.0,
                'multi_result'       => 28,
            ],
        ]];

        $result = $template->runRapidApiBatch([
            [
                'platform' => 'Tiktok',
                'url'      => 'https://www.tiktok.com/@espresso/photo/7653084884126272785?lang=en',
            ],
        ], [
            'inline_retry_limit'       => 1,
            'inline_retry_delay_ms'    => 0,
            'remaining_budget_seconds' => 30,
            'min_retry_budget_seconds' => 15,
        ]);

        $this->assertSame(1, $template->executeRapidApiGetCalls);
        $this->assertTrue($result[0]['status']);
        $this->assertSame(25, $result[0]['data']['view']);
    }

    public function testBatchBrownoutMetaTurnsOnAfterMajorityInfraStall(): void
    {
        $template                       = new TemplateTransportProbe();
        $template->queuedBatchResponses = [
            [
                'status'      => false,
                'msg'         => 'timeout 1',
                'data'        => [],
                'error_class' => 'infra_stall',
                'error_meta'  => ['curl_errno' => 28, 'curl_error' => 'timeout'],
            ],
            [
                'status'      => false,
                'msg'         => 'timeout 2',
                'data'        => [],
                'error_class' => 'infra_stall',
                'error_meta'  => ['curl_errno' => 28, 'curl_error' => 'timeout'],
            ],
            [
                'status'      => false,
                'msg'         => 'timeout 3',
                'data'        => [],
                'error_class' => 'infra_stall',
                'error_meta'  => ['curl_errno' => 28, 'curl_error' => 'timeout'],
            ],
        ];

        $result = $template->runRapidApiBatch([
            ['platform' => 'Tiktok', 'url' => 'https://www.tiktok.com/@a/video/1'],
            ['platform' => 'Tiktok', 'url' => 'https://www.tiktok.com/@a/video/2'],
            ['platform' => 'Tiktok', 'url' => 'https://www.tiktok.com/@a/video/3'],
        ]);

        $this->assertTrue($result['_meta']['brownout']);
    }
}

final class TemplateTransportProbe extends Template
{
    public $queuedResponses         = [];
    public $queuedExecuteResponses  = [];
    public $queuedBatchResponses    = [];
    public $curlRequestCalls        = 0;
    public $executeRapidApiGetCalls = 0;

    public function curlRequest($url, $headers = [])
    {
        $this->curlRequestCalls++;

        return array_shift($this->queuedResponses);
    }

    protected function executeRapidApiGet(string $url, array $headers = [], int $timeoutSec = 12): array
    {
        $this->executeRapidApiGetCalls++;

        return array_shift($this->queuedExecuteResponses);
    }

    /**
     * Exposes the protected transport classifier so its ordering can be pinned directly.
     */
    public function classifyTransport(array $meta): string
    {
        return $this->classifyRapidApiTransportFailure($meta);
    }

    protected function fetchRapidApiTiktokBatch(array $tasks, array $options = []): array
    {
        if ($this->queuedBatchResponses === []) {
            return parent::fetchRapidApiTiktokBatch($tasks, $options);
        }

        $results                = [];
        $inlineRetryLimit       = max(0, (int) ($options['inline_retry_limit'] ?? 0));
        $remainingBudgetSeconds = (float) ($options['remaining_budget_seconds'] ?? 0.0);
        $minRetryBudgetSeconds  = (float) ($options['min_retry_budget_seconds'] ?? 0.0);

        foreach ($tasks as $idx => $task) {
            $response  = array_shift($this->queuedBatchResponses);
            $contentId = $this->extract_tiktok_content_id($task['url']);
            $result    = $this->buildTiktokRapidApiFailureResponse($contentId, $response);

            if (($result['error_class'] ?? '') === 'infra_stall'
                && empty($task['rescue_lane'])
                && $inlineRetryLimit > 0
                && $remainingBudgetSeconds >= $minRetryBudgetSeconds) {
                $inlineRetryLimit--;
                $retryResp = $this->executeRapidApiGet(
                    $this->buildTiktokDetailUrl($task['url'], (int) ($task['hd'] ?? 0)),
                    $this->getRapidApiHeaders(),
                    (int) ($task['timeout_sec'] ?? 12),
                );

                if ($this->isValidRapidApiTiktokDetailResponse($retryResp)) {
                    $result = $this->mapRapidApiTiktokDetailToResponse(
                        $this->buildTiktokBaseResponse($task['url']),
                        $retryResp['data'],
                        true,
                    );
                } else {
                    $result = $this->buildTiktokRapidApiFailureResponse($contentId, $retryResp);
                }
            }

            $results[$idx] = $result;
        }

        $stallFailures = 0;

        foreach ($results as $result) {
            if (($result['error_class'] ?? '') === 'infra_stall') {
                $stallFailures++;
            }
        }
        $results['_meta'] = [
            'brownout' => count($results) >= 3 && $stallFailures >= 3 && ($stallFailures / count($results)) >= 0.5,
        ];

        return $results;
    }

    public function runRapidApiBatch(array $tasks, array $options = []): array
    {
        return $this->fetchRapidApiTiktokBatch($tasks, $options);
    }

    protected function getRapidApiConfig(): array
    {
        return [
            'host' => 'test-rapidapi.example',
            'key'  => 'test-key',
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
