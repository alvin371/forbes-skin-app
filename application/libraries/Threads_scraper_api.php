<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Client for the Acneno async social scraper. This is deliberately Threads-only:
 * Instagram remains on Instagram_rapid_api and TikTok keeps its existing worker.
 */
class Threads_scraper_api
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeout;

    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim(trim((string) ($config['base_url'] ?? env('SOCIAL_SCRAPER_BASE_URL', ''))), '/');
        $this->apiKey = trim((string) ($config['api_key'] ?? env('SOCIAL_SCRAPER_API_KEY', '')));
        $this->timeout = max(5, intval($config['timeout'] ?? env('SOCIAL_SCRAPER_TIMEOUT', 20)));
    }

    public function createAccount(string $link): array
    {
        return $this->request('POST', '/api/v1/accounts', ['link' => trim($link)]);
    }

    public function scrapePost(string $link): array
    {
        return $this->request('POST', '/api/v1/posts/scrape', ['link' => trim($link)]);
    }

    public function job(string $jobId): array
    {
        return $this->request('GET', '/api/v1/jobs/' . rawurlencode(trim($jobId)));
    }

    public function account(string $accountId): array
    {
        return $this->request('GET', '/api/v1/accounts/' . rawurlencode(trim($accountId)));
    }

    public function posts(string $accountId, int $limit = 10): array
    {
        return $this->request('GET', '/api/v1/posts?account_id=' . rawurlencode(trim($accountId)) . '&platform=threads&limit=' . max(1, min(200, $limit)));
    }

    /** Map a documented PostResponse into Template::get_social_media's contract. */
    public static function normalizePostResult(array $post, string $expectedUrl = ''): array
    {
        $platform = strtolower(trim((string) ($post['platform'] ?? '')));
        $permalink = trim((string) ($post['permalink'] ?? ''));
        if ($platform !== 'threads') {
            return self::failure('Hasil scraper bukan post Threads.', 'permanent');
        }
        if (trim((string) ($post['post_id'] ?? '')) === '' || $permalink === '') {
            return self::failure('Hasil job Threads tidak memenuhi kontrak PostResponse.', 'transient');
        }
        if ($expectedUrl !== '' && !self::samePostUrl($expectedUrl, $permalink)) {
            return self::failure('Permalink hasil job Threads tidak cocok dengan konten yang diminta.', 'permanent');
        }

        return [
            'status' => true,
            'msg' => 'Statistik Threads ditemukan.',
            'data' => [
                'content_id' => strval($post['post_id']),
                'like' => intval($post['likes'] ?? 0),
                'comment' => intval($post['comments'] ?? 0),
                'share' => intval($post['shares'] ?? 0),
                'collect' => null,
                'view' => intval($post['views'] ?? 0),
                'media_type' => strval($post['media_type'] ?? ''),
                'cover' => strval($post['media_url'] ?? ''),
                'video_link' => '',
                'created_at' => strval($post['posted_at'] ?? ''),
                'url' => $permalink,
            ],
            'stats_fields' => ['like', 'comment', 'share', 'view'],
            'stats_source' => 'threads_scraper',
            'http_status' => 200,
        ];
    }

    protected function request(string $method, string $path, ?array $body = null): array
    {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            return self::failure('Konfigurasi Social Scraper belum lengkap.', 'config');
        }
        if (stripos($this->baseUrl, 'https://') !== 0) {
            return self::failure('Social Scraper harus memakai HTTPS.', 'config');
        }

        $curl = curl_init($this->baseUrl . $path);
        $headers = ['Accept: application/json', 'X-API-Key: ' . $this->apiKey];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $httpStatus = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        $retryAfter = defined('CURLINFO_RETRY_AFTER') ? intval(curl_getinfo($curl, CURLINFO_RETRY_AFTER) ?: 0) : 0;
        curl_close($curl);

        if ($errno !== 0) {
            $class = $errno === CURLE_OPERATION_TIMEDOUT ? 'infra_stall'
                : ($errno === CURLE_COULDNT_RESOLVE_HOST ? 'infra_dns' : 'infra_connect');
            return self::failure('Social Scraper transport error: ' . $error, $class, 0);
        }
        $data = json_decode((string) $response, true);
        if ($httpStatus < 200 || $httpStatus >= 300 || !is_array($data)) {
            $class = in_array($httpStatus, [401, 403], true) ? 'config'
                : ($httpStatus === 400 || $httpStatus === 404 ? 'permanent' : 'transient');
            $result = self::failure(strval($data['detail'] ?? ('Social Scraper gagal (HTTP ' . $httpStatus . ').')), $class, $httpStatus);
            if ($httpStatus === 429 && $retryAfter > 0) $result['retry_after'] = $retryAfter;
            return $result;
        }

        return ['status' => true, 'msg' => 'OK', 'data' => $data, 'http_status' => $httpStatus];
    }

    protected static function samePostUrl(string $left, string $right): bool
    {
        $left = self::canonicalPostUrl($left);
        $right = self::canonicalPostUrl($right);
        return $left !== '' && $left === $right;
    }

    protected static function canonicalPostUrl(string $url): string
    {
        $parts = parse_url(trim($url));
        if (!is_array($parts)) return '';
        $host = strtolower(preg_replace('#^www\\.#', '', strval($parts['host'] ?? '')));
        $path = rtrim(strval($parts['path'] ?? ''), '/');
        return $host . $path;
    }

    protected static function failure(string $msg, string $class, int $httpStatus = 0): array
    {
        return ['status' => false, 'msg' => $msg, 'data' => [], 'error_class' => $class, 'http_status' => $httpStatus];
    }
}
