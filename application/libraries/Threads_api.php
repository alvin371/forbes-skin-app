<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Threads_api
{
    protected $token;
    protected $appId;
    protected $appSecret;
    protected $timeout;
    protected $budget;

    public function __construct(array $config = [])
    {
        $this->token = trim((string) ($config['access_token'] ?? ''));
        $this->appId = trim((string) ($config['app_id'] ?? env('THREADS_APP_ID', '')));
        $this->appSecret = trim((string) ($config['app_secret'] ?? env('THREADS_APP_SECRET', '')));
        $this->timeout = max(5, intval($config['timeout'] ?? env('THREADS_API_TIMEOUT', 30)));
        // Total wall-clock budget per operation chain. Must stay below the Rust
        // worker's fetch-fallback HTTP timeout (>= 35s) so multi-call operations
        // (post resolve + detail + insights) fail fast here instead of stalling there.
        $this->budget = max(10, intval($config['budget'] ?? env('THREADS_API_BUDGET_SEC', 25)));
    }

    public function withToken(string $token): self
    {
        $clone = clone $this;
        $clone->token = trim($token);
        return $clone;
    }

    public function profile(): array
    {
        $deadline = $this->operationDeadline();
        $profile = $this->graph('/v1.0/me', ['fields' => 'id,username,name,threads_profile_picture_url'], $deadline);
        if (empty($profile['status'])) return $profile;
        $followers = $this->graph('/v1.0/me/threads_insights', ['metric' => 'followers_count'], $deadline);
        if (empty($followers['status'])) return $followers;
        $raw = $profile['data'];
        return $this->success([
            'account_id' => strval($raw['id'] ?? ''),
            'username' => strval($raw['username'] ?? ''),
            'full_name' => strval($raw['name'] ?? ($raw['username'] ?? '')),
            'img' => strval($raw['threads_profile_picture_url'] ?? ''),
            'follower' => $this->insightValue($followers['data'], 'followers_count'),
            'media_count' => null,
        ], 'Data profil Threads ditemukan.');
    }

    public function recentPosts(int $limit = 10): array
    {
        $deadline = $this->operationDeadline();
        $response = $this->graph('/v1.0/me/threads', [
            'fields' => 'id,shortcode,text,media_type,media_url,thumbnail_url,permalink,timestamp',
            'limit' => max(1, min(100, $limit)),
        ], $deadline);
        if (empty($response['status'])) return $response;
        $posts = [];
        $lastFailure = null;
        foreach (array_slice($response['data']['data'] ?? [], 0, $limit) as $post) {
            $insights = $this->postInsights(strval($post['id'] ?? ''), $deadline);
            if (empty($insights['status'])) {
                // Token/permission problems affect every post — bail out. Anything
                // else (deleted post, insight unavailable) only skips this item.
                if (strval($insights['error_class'] ?? '') === 'config') return $insights;
                $lastFailure = $insights;
                continue;
            }
            $posts[] = $this->normalizePost($post, $insights['data']);
        }
        if (empty($posts)) {
            return $lastFailure ?: $this->failure('Posting Threads tidak ditemukan.', 'empty');
        }
        return $this->success($posts, 'Data posting Threads ditemukan.');
    }

    public function post(string $url, string $knownMediaId = ''): array
    {
        $deadline = $this->operationDeadline();
        $mediaId = trim($knownMediaId) ?: $this->resolveMediaId($url, $deadline);
        if ($mediaId === '') return $this->failure('Media Threads tidak ditemukan dari URL.', 'permanent');
        $detail = $this->graph('/v1.0/' . rawurlencode($mediaId), [
            'fields' => 'id,shortcode,text,media_type,media_url,thumbnail_url,permalink,timestamp',
        ], $deadline);
        if (empty($detail['status'])) return $detail;
        $insights = $this->postInsights($mediaId, $deadline);
        if (empty($insights['status'])) return $insights;
        return ['status' => true, 'msg' => 'Statistik Threads ditemukan.',
            'data' => $this->normalizePost($detail['data'], $insights['data']),
            'stats_fields' => ['like', 'comment', 'share', 'view'], 'stats_source' => 'threads_graph', 'http_status' => 200];
    }

    public function postInsights(string $mediaId, float $deadline = 0.0): array
    {
        $response = $this->graph('/v1.0/' . rawurlencode($mediaId) . '/insights', ['metric' => 'views,likes,replies,reposts,quotes'], $deadline);
        if (empty($response['status'])) return $response;
        $metrics = [];
        foreach (['views', 'likes', 'replies', 'reposts', 'quotes'] as $metric) $metrics[$metric] = $this->insightValue($response['data'], $metric);
        return $this->success($metrics, 'Insight Threads ditemukan.');
    }

    public function resolveMediaId(string $url, float $deadline = 0.0): string
    {
        $shortcode = $this->extractShortcode($url);
        if ($shortcode === '') return '';
        $next = '/v1.0/me/threads'; $query = ['fields' => 'id,shortcode,permalink', 'limit' => 100];
        for ($page = 0; $page < 5 && $next !== ''; $page++) {
            $response = $this->graph($next, $query, $deadline);
            if (empty($response['status'])) return '';
            foreach ($response['data']['data'] ?? [] as $post) {
                if (strval($post['shortcode'] ?? '') === $shortcode || $this->extractShortcode(strval($post['permalink'] ?? '')) === $shortcode) return strval($post['id'] ?? '');
            }
            $nextUrl = strval($response['data']['paging']['next'] ?? '');
            if ($nextUrl === '') break;
            $parts = parse_url($nextUrl); $next = strval($parts['path'] ?? ''); parse_str(strval($parts['query'] ?? ''), $query);
        }
        return '';
    }

    public function exchangeCode(string $code, string $redirectUri): array
    {
        if ($this->appId === '' || $this->appSecret === '') return $this->failure('Konfigurasi OAuth Threads belum lengkap.', 'config');
        return $this->request('https://graph.threads.net/oauth/access_token', 'POST', [], [
            'client_id' => $this->appId, 'client_secret' => $this->appSecret, 'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri, 'code' => $code,
        ]);
    }

    public function exchangeLongLived(string $shortToken): array
    {
        return $this->request('https://graph.threads.net/access_token', 'GET', [
            'grant_type' => 'th_exchange_token', 'client_secret' => $this->appSecret, 'access_token' => $shortToken,
        ]);
    }

    public function refreshToken(string $token): array
    {
        return $this->request('https://graph.threads.net/refresh_access_token', 'GET', [
            'grant_type' => 'th_refresh_token', 'access_token' => $token,
        ]);
    }

    protected function normalizePost(array $post, array $metrics): array
    {
        $type = strtolower(strval($post['media_type'] ?? 'text'));
        return [
            'content_id' => strval($post['id'] ?? ''), 'shortcode' => strval($post['shortcode'] ?? ''),
            'like' => intval($metrics['likes'] ?? 0), 'comment' => intval($metrics['replies'] ?? 0),
            'share' => intval($metrics['reposts'] ?? 0) + intval($metrics['quotes'] ?? 0), 'collect' => null,
            'view' => intval($metrics['views'] ?? 0), 'media_type' => $type,
            'cover' => strval($post['thumbnail_url'] ?? ($post['media_url'] ?? '')),
            'video_link' => $type === 'video' ? strval($post['media_url'] ?? '') : '',
            'created_at' => !empty($post['timestamp']) ? date('Y-m-d H:i:s', strtotime($post['timestamp'])) : null,
            'url' => strval($post['permalink'] ?? ''), 'title' => strval($post['text'] ?? ''),
        ];
    }

    protected function insightValue(array $payload, string $name): int
    {
        foreach ($payload['data'] ?? [] as $metric) if (strval($metric['name'] ?? '') === $name) return intval($metric['values'][0]['value'] ?? ($metric['total_value']['value'] ?? ($metric['value'] ?? 0)));
        return 0;
    }

    protected function extractShortcode(string $url): string
    {
        $parts = array_values(array_filter(explode('/', trim(strval(parse_url($url, PHP_URL_PATH)), '/'))));
        $i = array_search('post', $parts, true);
        return $i !== false ? strval($parts[$i + 1] ?? '') : strval(end($parts) ?: '');
    }

    protected function graph(string $path, array $query, float $deadline = 0.0): array
    {
        if ($this->token === '') return $this->failure('Akun Threads belum terhubung.', 'config');
        $query['access_token'] = $this->token;
        return $this->request(preg_match('#^https?://#i', $path) ? $path : 'https://graph.threads.net' . $path, 'GET', $query, [], $deadline);
    }

    protected function operationDeadline(): float
    {
        return microtime(true) + $this->budget;
    }

    protected function request(string $url, string $method = 'GET', array $query = [], array $form = [], float $deadline = 0.0): array
    {
        $timeout = $this->timeout;
        if ($deadline > 0) {
            $remaining = intval(floor($deadline - microtime(true)));
            if ($remaining < 2) return $this->failure('Batas waktu operasi Threads API terlampaui.', 'infra_stall', 0);
            $timeout = min($timeout, $remaining);
        }
        if ($query) $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $curl = curl_init($url);
        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout), CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json']];
        if ($method === 'POST') { $options[CURLOPT_POST] = true; $options[CURLOPT_POSTFIELDS] = http_build_query($form, '', '&', PHP_QUERY_RFC3986); $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded'; }
        curl_setopt_array($curl, $options); $body = curl_exec($curl); $errno = curl_errno($curl); $error = curl_error($curl);
        $status = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE)); curl_close($curl);
        if ($errno !== 0) return $this->failure('Threads API transport error: ' . $error, $errno === CURLE_OPERATION_TIMEDOUT ? 'infra_stall' : ($errno === CURLE_COULDNT_RESOLVE_HOST ? 'infra_dns' : 'infra_connect'), $status);
        $decoded = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded)) {
            $msg = is_array($decoded) ? strval($decoded['error']['message'] ?? '') : '';
            $class = in_array($status, [400, 404], true) ? 'permanent' : (in_array($status, [401, 403], true) ? 'config' : 'transient');
            return $this->failure($msg ?: 'Threads API gagal (HTTP ' . $status . ').', $class, $status);
        }
        return ['status' => true, 'msg' => 'OK', 'data' => $decoded, 'http_status' => $status];
    }

    protected function success(array $data, string $msg): array { return ['status' => true, 'msg' => $msg, 'data' => $data, 'http_status' => 200]; }
    protected function failure(string $msg, string $class, int $status = 0): array { return ['status' => false, 'msg' => $msg, 'data' => [], 'error_class' => $class, 'http_status' => $status]; }
}
