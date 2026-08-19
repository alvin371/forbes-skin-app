<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/Template.php';

/**
 * Bounded response buffer for a curl handle.
 *
 * CURLOPT_ENCODING => '' auto-inflates gzip, and a real TikTok detail page is 0.5-1 MB
 * inflated. At 40 concurrent scrape slots that is tens of megabytes of live buffers plus
 * PHP's string copies — in a process that must stay up for 30 minutes.
 *
 * Past the cap, bytes are counted and discarded rather than aborting the transfer: returning
 * short from a write callback makes libcurl fail the handle with CURLE_WRITE_ERROR (23),
 * which would be recorded as a transport failure and quietly corrupt the per-leg success
 * rates the whole capacity model is derived from. A truncated body is a parse miss —
 * honest, and visible via truncated().
 */
final class EndorseRefreshResponseBuffer
{
    private string $body = '';
    private int $maxBytes;
    private int $seen = 0;
    private bool $truncated = false;

    public function __construct(int $maxBytes)
    {
        $this->maxBytes = max(4096, $maxBytes);
    }

    /** curl write callback. MUST return the full chunk length or the transfer is failed. */
    public function write($curl, string $chunk): int
    {
        $len = strlen($chunk);
        $this->seen += $len;

        $room = $this->maxBytes - strlen($this->body);
        if ($room > 0) {
            $this->body .= $len <= $room ? $chunk : substr($chunk, 0, $room);
        }
        if ($this->seen > $this->maxBytes) {
            $this->truncated = true;
        }

        return $len;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function truncated(): bool
    {
        return $this->truncated;
    }

    public function bytes(): int
    {
        return $this->seen;
    }
}

/** Holder so Template::buildRapidApiHeaderCollector(array &$headers) has a real reference target. */
final class EndorseRefreshHeaderBag
{
    public array $headers = array();
}

/**
 * The fetch seam for the continuous worker.
 *
 * Template's TikTok parsers and mappers are exactly the behaviour we need and exactly the
 * behaviour production already trusts — but they are `protected`, and the only public entry
 * points (get_social_media, get_social_media_batch) are the blocking, chunk-barriered
 * implementations we are replacing.
 *
 * Template is a plain non-final class with no constructor, so a subclass reaches all of it.
 * That is the whole trick: this file adds handle construction and re-exports the parsers,
 * and Template.php is not edited at all. The production cron path therefore cannot be
 * affected by anything the load-test worker does — including a bug in it.
 *
 * The two handle builders copy their option arrays from Template verbatim
 * (fetchTiktokDetailPagesBatch and executeRapidApiGet), differing only by:
 *   - CURLOPT_WRITEFUNCTION into a bounded buffer instead of CURLOPT_RETURNTRANSFER,
 *   - CURLOPT_PRIVATE carrying the slot id so curl_multi_info_read results map home.
 * If those arrays ever drift, the load test stops measuring the code production runs.
 */
final class EndorseRefreshFetchKit extends Template
{
    /** Same UA the production scrape sends. Changing it changes what TikTok returns. */
    const SCRAPE_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:100.0) Gecko/20100101 Firefox/100.0';

    /** Same cookie the production scrape sends. Not a credential — a public consent/CSRF blob. */
    const SCRAPE_COOKIE = 'Cookie: tt_chain_token=+O8Mw9RH4nKrX/ACdOBhXw==; tt_csrf_token=27TtpaB8-Wftkj0rFR_w6LdtcAp4tdDCFfBY; ttwid=1%7CdJI7LAdiTNKwSISqHad9wDTJ6G_70WU_PGro2isx-ac%7C1705385087%7C518efef116162148489d7f25fa3c7b06633a23c824590f04ea0226d5c2b6f092';

    const DEFAULT_MAX_BODY_BYTES = 2097152; // 2 MB — comfortably above a real detail page

    /** @var array{host: string, key: string}|null resolved by the caller, never from env */
    private ?array $rapidApiConfig = null;

    /**
     * @param array{host?: string, key?: string} $rapidApiConfig
     *
     * Template::getRapidApiConfig() reads env('RAPIDAPI_HOST') and env('RAPIDAPI_KEY') on every
     * call. That is wrong for a long-lived worker twice over: it re-reads the environment
     * hundreds of times a second to produce a constant, and it makes the class depend on
     * whichever global env() helper is loaded — illuminate/support ships one whose Env::get()
     * needs phpoption/phpoption, a package this project does not install, so it fatals outright.
     *
     * Passing the config in keeps the worker free of that dependency. Omitting it preserves the
     * inherited env-reading behaviour exactly, so every existing caller is unaffected.
     */
    public function __construct(array $rapidApiConfig = array())
    {
        if (isset($rapidApiConfig['host']) || isset($rapidApiConfig['key'])) {
            $this->rapidApiConfig = array(
                'host' => trim((string) ($rapidApiConfig['host'] ?? '')),
                'key' => trim((string) ($rapidApiConfig['key'] ?? '')),
            );
        }
    }

    protected function getRapidApiConfig(): array
    {
        return $this->rapidApiConfig ?? parent::getRapidApiConfig();
    }

    /** Leg 1: direct tiktok.com page scrape. Mirrors Template::fetchTiktokDetailPagesBatch. */
    public function newScrapeHandle(string $url, int $timeoutSec, int $connectTimeoutSec, EndorseRefreshResponseBuffer $buffer)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_CONNECTTIMEOUT => max(1, $connectTimeoutSec),
            CURLOPT_TIMEOUT        => max(1, $timeoutSec),
            CURLOPT_USERAGENT      => self::SCRAPE_USER_AGENT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(self::SCRAPE_COOKIE),
            CURLOPT_WRITEFUNCTION  => array($buffer, 'write'),
        ));

        return $curl;
    }

    /** Leg 2: RapidAPI getVideoInfo. Mirrors Template::executeRapidApiGet. */
    public function newRapidApiHandle(string $url, int $hd, int $timeoutSec, int $connectTimeoutSec, EndorseRefreshResponseBuffer $buffer, EndorseRefreshHeaderBag $bag)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $this->buildTiktokDetailUrl($url, $hd),
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_CONNECTTIMEOUT => max(1, $connectTimeoutSec),
            CURLOPT_TIMEOUT        => max(1, $timeoutSec),
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => $this->getRapidApiHeaders(),
            CURLOPT_HEADERFUNCTION => $this->buildRapidApiHeaderCollector($bag->headers),
            CURLOPT_WRITEFUNCTION  => array($buffer, 'write'),
        ));

        return $curl;
    }

    // -- re-exports of Template's protected logic (never reimplementations) --------------

    public function parseScrapeHtml(string $html): array
    {
        return $this->extractTiktokItemStructFromHtml($html);
    }

    public function scrapeItemUsable(array $item): bool
    {
        return $this->isValidTiktokScrapeItem($item);
    }

    public function baseResponse(string $url): array
    {
        return $this->buildTiktokBaseResponse($url);
    }

    public function contentId(string $url): string
    {
        return (string) $this->extract_tiktok_content_id($url);
    }

    public function mediaType(string $url): string
    {
        return (string) $this->detect_tiktok_media_type_from_url($url);
    }

    /** RapidAPI config problem (missing key/host) — a `config` class error, never retried blindly. */
    public function rapidApiConfigProblem(): ?array
    {
        return $this->rapidApiConfigError();
    }

    /**
     * Build the success response for a completed direct scrape.
     * Mirrors the literal Template::get_social_media_batch constructs before mapping.
     */
    public function scrapeToResponse(string $url, array $itemStruct): array
    {
        $response = array(
            'status' => true,
            'msg'    => '',
            'data'   => array(
                'like' => 0, 'share' => 0, 'comment' => 0, 'collect' => 0, 'view' => 0,
                'created_at' => '',
                'content_id' => $this->extract_tiktok_content_id($url),
                'media_type' => $this->detect_tiktok_media_type_from_url($url),
                'video_link' => '', 'cover' => '', 'images' => array(),
            ),
        );

        return $this->mapDirectTiktokItemToResponse($response, $itemStruct, true);
    }

    /**
     * Turn a completed RapidAPI transfer into a Template-shaped response.
     *
     * Runs the SAME finalize -> validate -> map|failure sequence as
     * Template::get_social_media, so error classification (429 -> rate_limited,
     * 401/403 -> config, >=500 -> transient, >=400 -> permanent) and the Retry-After
     * capture in error_meta are the production ones, not a second opinion.
     */
    public function rapidApiToResponse(string $url, string $body, array $transportMeta): array
    {
        $finalized = $this->finalizeRapidApiJsonResponse($body, $transportMeta);

        if (!$this->isValidRapidApiTiktokDetailResponse($finalized)) {
            return $this->buildTiktokRapidApiFailureResponse($this->extract_tiktok_content_id($url), $finalized);
        }

        $mapped = $this->mapRapidApiTiktokDetailToResponse(
            $this->buildTiktokBaseResponse($url),
            $finalized['data'] ?? array(),
            true
        );
        $mapped['data']['content_id'] = $mapped['data']['content_id'] ?: $this->extract_tiktok_content_id($url);

        return $mapped;
    }

    /**
     * Classify a transport-level failure using the production heuristics
     * (Template::classifyRapidApiTransportFailure): curl timing phases split
     * infra_dns / infra_connect / infra_tls / infra_stall so a stalled socket is
     * distinguishable from a refused one in the ledger.
     */
    public function classifyTransport(array $meta): string
    {
        return $this->classifyRapidApiTransportFailure($meta);
    }
}
