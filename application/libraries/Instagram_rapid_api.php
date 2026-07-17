<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Instagram_rapid_api
{
    protected $host;
    protected $key;
    protected $timeout;

    public function __construct(array $config = [])
    {
        $this->host = trim((string) ($config['host'] ?? env('INSTAGRAM_RAPIDAPI_HOST', 'instagram-looter2.p.rapidapi.com')));
        $this->key = trim((string) ($config['key'] ?? env('INSTAGRAM_RAPIDAPI_KEY', '')));
        $this->timeout = max(5, intval($config['timeout'] ?? env('INSTAGRAM_RAPIDAPI_TIMEOUT', 30)));
    }

    public function profile(string $username): array
    {
        $username = ltrim(trim($username), '@');
        if ($username === '') return $this->failure('Username Instagram tidak ditemukan.', 'permanent');
        $response = $this->request('/profile2', ['username' => $username]);
        if (empty($response['status'])) return $response;
        $raw = $this->unwrap($response['data']);
        $id = strval($raw['pk'] ?? ($raw['id'] ?? ''));
        if ($id === '') return $this->failure('Profil Instagram tidak ditemukan.', 'empty', intval($response['http_status'] ?? 200));
        return $this->success([
            'account_id' => $id,
            'username' => strval($raw['username'] ?? $username),
            'full_name' => strval($raw['full_name'] ?? ($raw['username'] ?? $username)),
            'img' => strval($raw['profile_pic_url_hd'] ?? ($raw['profile_pic_url'] ?? '')),
            'follower' => intval($raw['follower_count'] ?? ($raw['edge_followed_by']['count'] ?? 0)),
            'media_count' => intval($raw['media_count'] ?? ($raw['edge_owner_to_timeline_media']['count'] ?? 0)),
        ], 'Data profil Instagram ditemukan.');
    }

    public function posts(string $accountId, int $count = 10): array
    {
        if (trim($accountId) === '') return $this->failure('Account ID Instagram tidak ditemukan.', 'permanent');
        $response = $this->request('/user-feeds2', ['id' => $accountId, 'count' => max(1, min(50, $count))]);
        if (empty($response['status'])) return $response;
        $raw = $response['data'];
        $edges = $raw['data']['user']['edge_owner_to_timeline_media']['edges']
            ?? ($raw['user']['edge_owner_to_timeline_media']['edges'] ?? ($raw['edges'] ?? ($raw['items'] ?? [])));
        $posts = [];
        foreach (array_slice(is_array($edges) ? $edges : [], 0, $count) as $edge) {
            $posts[] = $this->normalizePost(is_array($edge['node'] ?? null) ? $edge['node'] : $edge);
        }
        return empty($posts)
            ? $this->failure('Posting Instagram tidak ditemukan.', 'empty', intval($response['http_status'] ?? 200))
            : $this->success($posts, 'Data posting Instagram ditemukan.');
    }

    public function post(string $url): array
    {
        if (trim($url) === '') return $this->failure('URL Instagram tidak ditemukan.', 'permanent');
        $response = $this->request('/post', ['url' => $url]);
        if (empty($response['status'])) return $response;
        $raw = $this->unwrap($response['data']);
        $node = $raw['graphql']['shortcode_media'] ?? ($raw['shortcode_media'] ?? ($raw['item'] ?? $raw));
        if (!is_array($node) || empty($node)) return $this->failure('Statistik posting Instagram tidak ditemukan.', 'empty');
        $post = $this->normalizePost($node);
        $fields = ['like', 'comment'];
        if ($post['view'] !== null) $fields[] = 'view';
        return ['status' => true, 'msg' => 'Statistik Instagram ditemukan.', 'data' => $post,
            'stats_fields' => $fields, 'stats_source' => 'instagram_rapidapi', 'http_status' => 200];
    }

    protected function normalizePost(array $node): array
    {
        $id = strval($node['id'] ?? ($node['pk'] ?? ''));
        $shortcode = strval($node['shortcode'] ?? ($node['code'] ?? ''));
        $view = null;
        foreach (['video_play_count', 'video_view_count', 'view_count', 'play_count'] as $field) {
            if (array_key_exists($field, $node) && $node[$field] !== null && $node[$field] !== '') { $view = intval($node[$field]); break; }
        }
        $timestamp = intval($node['taken_at_timestamp'] ?? ($node['taken_at'] ?? 0));
        $isVideo = !empty($node['is_video']) || strval($node['__typename'] ?? '') === 'GraphVideo' || intval($node['media_type'] ?? 0) === 2;
        return [
            'content_id' => $id !== '' ? $id : $shortcode,
            'shortcode' => $shortcode,
            'like' => intval($node['edge_media_preview_like']['count'] ?? ($node['edge_liked_by']['count'] ?? ($node['like_count'] ?? 0))),
            'comment' => intval($node['edge_media_to_parent_comment']['count'] ?? ($node['edge_media_to_comment']['count'] ?? ($node['comment_count'] ?? 0))),
            'share' => null, 'collect' => null, 'view' => $view,
            'media_type' => $isVideo ? 'video' : (strval($node['__typename'] ?? '') === 'GraphSidecar' ? 'carousel' : 'image'),
            'cover' => strval($node['display_url'] ?? ($node['thumbnail_src'] ?? ($node['image_versions2']['candidates'][0]['url'] ?? ''))),
            'video_link' => strval($node['video_url'] ?? ''),
            'created_at' => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : null,
            'url' => $shortcode !== '' ? 'https://www.instagram.com/p/' . rawurlencode($shortcode) . '/' : '',
        ];
    }

    protected function request(string $path, array $query = []): array
    {
        if ($this->key === '' || $this->host === '') return $this->failure('Konfigurasi Instagram RapidAPI belum lengkap.', 'config');
        $url = 'https://' . $this->host . $path . (empty($query) ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout), CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-RapidAPI-Host: ' . $this->host, 'X-RapidAPI-Key: ' . $this->key]]);
        $body = curl_exec($curl); $errno = curl_errno($curl); $error = curl_error($curl);
        $status = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE)); curl_close($curl);
        if ($errno !== 0) return $this->failure('Instagram RapidAPI transport error: ' . $error, $this->curlErrorClass($errno), $status);
        $decoded = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300) {
            $class = in_array($status, [401, 403], true) ? 'config' : ($status === 404 ? 'permanent' : 'transient');
            return $this->failure('Instagram RapidAPI gagal (HTTP ' . $status . ').', $class, $status);
        }
        return is_array($decoded) ? ['status' => true, 'msg' => 'OK', 'data' => $decoded, 'http_status' => $status]
            : $this->failure('Respons Instagram RapidAPI tidak valid.', 'transient', $status);
    }

    protected function unwrap(array $data): array
    {
        foreach (['data', 'user'] as $key) if (is_array($data[$key] ?? null)) $data = $data[$key];
        return $data;
    }
    protected function success(array $data, string $msg): array { return ['status' => true, 'msg' => $msg, 'data' => $data, 'http_status' => 200]; }
    protected function failure(string $msg, string $class, int $status = 0): array { return ['status' => false, 'msg' => $msg, 'data' => [], 'error_class' => $class, 'http_status' => $status]; }
    protected function curlErrorClass(int $errno): string
    {
        if ($errno === CURLE_OPERATION_TIMEDOUT) return 'infra_stall';
        if ($errno === CURLE_COULDNT_RESOLVE_HOST) return 'infra_dns';
        if ($errno === CURLE_COULDNT_CONNECT) return 'infra_connect';
        return 'infra';
    }
}
