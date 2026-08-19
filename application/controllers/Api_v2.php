<?php

// Load custom env helper BEFORE Composer autoloader to prevent illuminate/support conflicts
require_once __DIR__ . '/../../application/helpers/env_helper.php';

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

use Lazada\LazopClient;
use Lazada\LazopRequest;

defined('BASEPATH') or exit('No direct script access allowed');
class Api_v2 extends CI_Controller
{
    protected $app_id_threads;
    protected $app_secret_threads;
    protected $threads_redirect_uri;

    function __construct()
    {
        parent::__construct();
        $this->load->library('Template');
        $this->load->helper('env');
        $this->load->helper('monitoring');
        $this->load->helper('sentry');

        // TikTok Shop API credentials
        $this->app_key_tiktok = env('TIKTOK_APP_KEY', '');
        $this->app_secret_tiktok = env('TIKTOK_APP_SECRET', '');

        // Lazada API credentials
        $this->app_key_lazada = env('LAZADA_APP_KEY', '');
        $this->app_secret_lazada = env('LAZADA_APP_SECRET', '');

        // Shopee API credentials
        $this->partner_id_shopee = env('SHOPEE_PARTNER_ID', '');
        $this->partner_key_shopee = env('SHOPEE_PARTNER_KEY', '');

        // Meta/Facebook API credentials
        $this->app_id_meta = env('META_APP_ID', '');
        $this->app_secret_meta = env('META_APP_SECRET', '');
        $this->app_id_threads = env('THREADS_APP_ID', '');
        $this->app_secret_threads = env('THREADS_APP_SECRET', '');
        $this->threads_redirect_uri = env('THREADS_REDIRECT_URI', rtrim(base_url(), '/') . '/api_v2/threads_callback');
        $config = $this->mymodel->selectWithQuery("SELECT * FROM endorse_config");
        $config_map = array();
        if (is_array($config)) {
            foreach ($config as $row) {
                if (isset($row['title'])) {
                    $config_map[$row['title']] = isset($row['value']) ? $row['value'] : null;
                }
            }
        }
        $this->fyp_views = isset($config_map['fyp_views']) ? intval($config_map['fyp_views']) : 0;
        $this->fyp_percentage = isset($config_map['fyp_persentase']) ? intval($config_map['fyp_persentase']) : 0;
    }

    public function index()
    {
        $dt = $_GET;
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $dt;
        $html['msg'] = "Acneno System REST API access has been successful!";
        echo json_encode($html, true);
    }

    function update_cronjob()
    {
        $platform = 'Tiktok';
        // $data = $this->mymodel->selectWithQuery("SELECT link_upload FROM `endorse` WHERE DATE(created_at) BETWEEN '2025-05-01' AND '2025-05-25' AND platform = 'Tiktok' AND link_upload != '' AND id_campaign = 54 LIMIT 10");

        // foreach ($data as $row) {
        //     $link_upload = $row['link_upload'];
        //     $response = $this->template->get_social_media($platform, $link_upload);

        //     // Cetak response jika perlu
        //     print_r($response);
        // }
        $response = $this->template->get_account_id($platform, 'https://www.tiktok.com/@xxmivaxx4');
        print_r($response);
    }

    function objKeySort($obj)
    {
        $newKey = array_keys($obj);
        sort($newKey);
        $newObj = [];
        foreach ($newKey as $key) {
            $newObj[$key] = $obj[$key];
        }
        return $newObj;
    }

    function getEnvVar($k)
    {
        $v = $_ENV[$k] ?? null;
        if ($v !== null) {
            return $v;
        }
        $v = $_SERVER[$k] ?? null;
        if ($v !== null) {
            return $v;
        }
        return null;
    }

    private function json_response(int $httpStatus, array $body)
    {
        http_response_code($httpStatus);
        echo json_encode($body);
        die;
    }

    private function worker_auth_guard()
    {
        $ip_allowlist = env('WORKER_IP_ALLOWLIST', '');
        if ($ip_allowlist) {
            $allowed = array_map('trim', explode(',', $ip_allowlist));
            $remote = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($remote === '' || !in_array($remote, $allowed, true)) {
                $this->json_response(403, array('status' => false, 'reason' => 'worker_ip_denied', 'msg' => 'Unauthorized'));
            }
        }

        $secret = env('WORKER_SHARED_SECRET', '');
        if ($secret) {
            $header = $_SERVER['HTTP_X_WORKER_SECRET'] ?? '';
            if ($header === '') {
                $this->json_response(401, array('status' => false, 'reason' => 'worker_auth_missing', 'msg' => 'Unauthorized'));
            }
            if (!hash_equals($secret, $header)) {
                $this->json_response(401, array('status' => false, 'reason' => 'worker_auth_invalid', 'msg' => 'Unauthorized'));
            }
        }

        return true;
    }

    public function threads_authorize()
    {
        if (empty($_SESSION['user']['id'])) {
            redirect(base_url('auth/login'));
            return;
        }
        $influencerId = intval($this->input->get('influencer_id'));
        $influencer = $this->mymodel->selectDataOne('influencer', ['id' => $influencerId, 'type' => 'Threads']);
        if (empty($influencer)) {
            redirect(base_url('influencer?msg=threads_not_found'));
            return;
        }
        if ($this->app_id_threads === '' || $this->app_secret_threads === '') {
            redirect(base_url('influencer?msg=threads_config_missing'));
            return;
        }

        $state = bin2hex(random_bytes(32));
        $_SESSION['threads_oauth_state'] = [
            'hash' => hash('sha256', $state),
            'influencer_id' => $influencerId,
            'user_id' => intval($_SESSION['user']['id']),
            'expires_at' => time() + 600,
        ];
        $url = 'https://threads.net/oauth/authorize?' . http_build_query([
            'client_id' => $this->app_id_threads,
            'redirect_uri' => $this->threads_redirect_uri,
            'scope' => 'threads_basic,threads_manage_insights',
            'response_type' => 'code',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
        redirect($url);
    }

    public function threads_callback()
    {
        $stored = $_SESSION['threads_oauth_state'] ?? null;
        unset($_SESSION['threads_oauth_state']);
        $state = strval($this->input->get('state'));
        $code = strval($this->input->get('code'));
        if (!is_array($stored) || $state === '' || $code === ''
            || intval($stored['expires_at'] ?? 0) < time()
            || intval($stored['user_id'] ?? 0) !== intval($_SESSION['user']['id'] ?? 0)
            || !hash_equals(strval($stored['hash'] ?? ''), hash('sha256', $state))) {
            redirect(base_url('influencer?msg=threads_oauth_invalid'));
            return;
        }

        $influencerId = intval($stored['influencer_id'] ?? 0);
        $influencer = $this->mymodel->selectDataOne('influencer', ['id' => $influencerId, 'type' => 'Threads']);
        if (empty($influencer)) {
            redirect(base_url('influencer?msg=threads_not_found'));
            return;
        }

        $this->load->library('Threads_api');
        $short = $this->threads_api->exchangeCode($code, $this->threads_redirect_uri);
        $shortToken = strval($short['data']['access_token'] ?? '');
        if (empty($short['status']) || $shortToken === '') {
            log_message('error', 'threads_oauth_code_exchange_failed: ' . strval($short['msg'] ?? 'unknown'));
            redirect(base_url('influencer?msg=threads_connect_failed'));
            return;
        }
        $long = $this->threads_api->exchangeLongLived($shortToken);
        $longToken = strval($long['data']['access_token'] ?? '');
        if (empty($long['status']) || $longToken === '') {
            log_message('error', 'threads_oauth_long_token_failed: ' . strval($long['msg'] ?? 'unknown'));
            redirect(base_url('influencer?msg=threads_connect_failed'));
            return;
        }

        $expiresIn = max(1, intval($long['data']['expires_in'] ?? 5184000));
        $this->db->update('influencer', [
            'threads_user_id' => strval($short['data']['user_id'] ?? ''),
            'threads_access_token' => $longToken,
            'threads_token_expires_at' => date('Y-m-d H:i:s', time() + $expiresIn),
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => strval($_SESSION['user']['id']),
        ], ['id' => $influencerId]);
        $sync = $this->template->syncSocialProfile('influencer', $influencerId, 'Threads', strval($influencer['url'] ?? ''));
        if (empty($sync['status'])) {
            log_message('error', 'threads_profile_sync_after_oauth_failed influencer=' . $influencerId . ' msg=' . strval($sync['msg'] ?? 'unknown'));
        }
        redirect(base_url('influencer?msg=threads_connected&influencer_id=' . $influencerId));
    }

    public function threads_refresh_token()
    {
        $this->worker_auth_guard();
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->db->field_exists('threads_access_token', 'influencer')) {
            echo json_encode(['status' => false, 'refreshed' => 0, 'failed' => 0, 'msg' => 'Migration Threads belum dijalankan.']);
            return;
        }
        $deadline = date('Y-m-d H:i:s', strtotime('+7 days'));
        $rows = $this->mymodel->selectWithQuery(
            "SELECT id, threads_access_token FROM influencer WHERE type = 'Threads' AND threads_access_token IS NOT NULL AND threads_access_token != '' AND threads_token_expires_at > NOW() AND threads_token_expires_at <= " . $this->db->escape($deadline) . " LIMIT 100"
        );
        $this->load->library('Threads_api');
        $refreshed = 0; $failed = 0;
        foreach ($rows as $row) {
            $result = $this->threads_api->refreshToken(strval($row['threads_access_token']));
            if (!empty($result['status']) && !empty($result['data']['access_token'])) {
                $this->db->update('influencer', [
                    'threads_access_token' => strval($result['data']['access_token']),
                    'threads_token_expires_at' => date('Y-m-d H:i:s', time() + max(1, intval($result['data']['expires_in'] ?? 5184000))),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => '1',
                ], ['id' => intval($row['id'])]);
                $refreshed++;
            } else {
                $failed++;
                log_message('error', 'threads_token_refresh_failed influencer=' . intval($row['id']) . ' msg=' . strval($result['msg'] ?? 'unknown'));
            }
        }
        echo json_encode(['status' => true, 'refreshed' => $refreshed, 'failed' => $failed]);
    }

    public function threads_deauthorize()
    {
        $payload = $this->parse_threads_signed_request(strval($this->input->post('signed_request')));
        if ($payload === null) $this->json_response(400, ['status' => false, 'msg' => 'signed_request tidak valid.']);
        $this->clear_threads_connection(strval($payload['user_id'] ?? ''));
        $this->json_response(200, ['url' => base_url(), 'confirmation_code' => $this->threads_confirmation_code(strval($payload['user_id'] ?? ''))]);
    }

    public function threads_delete_data()
    {
        $payload = $this->parse_threads_signed_request(strval($this->input->post('signed_request')));
        if ($payload === null) $this->json_response(400, ['status' => false, 'msg' => 'signed_request tidak valid.']);
        $userId = strval($payload['user_id'] ?? '');
        $this->clear_threads_connection($userId);
        $code = $this->threads_confirmation_code($userId);
        $this->json_response(200, [
            'url' => base_url('api_v2/threads_delete_status?code=' . rawurlencode($code)),
            'confirmation_code' => $code,
        ]);
    }

    public function threads_delete_status()
    {
        $valid = $this->verify_threads_confirmation_code(strval($this->input->get('code')));
        $this->json_response($valid ? 200 : 404, [
            'status' => $valid,
            'msg' => $valid ? 'Data Threads telah dihapus.' : 'Kode konfirmasi tidak valid.',
        ]);
    }

    private function parse_threads_signed_request(string $signedRequest)
    {
        $parts = explode('.', $signedRequest, 2);
        if (count($parts) !== 2 || $this->app_secret_threads === '') return null;
        $signature = $this->base64url_decode($parts[0]);
        $payloadJson = $this->base64url_decode($parts[1]);
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload) || strtoupper(strval($payload['algorithm'] ?? '')) !== 'HMAC-SHA256') return null;
        $expected = hash_hmac('sha256', $parts[1], $this->app_secret_threads, true);
        return hash_equals($expected, $signature) ? $payload : null;
    }

    private function clear_threads_connection(string $threadsUserId): void
    {
        if ($threadsUserId === '' || !$this->db->field_exists('threads_user_id', 'influencer')) return;
        $this->db->update('influencer', [
            'threads_access_token' => null,
            'threads_user_id' => null,
            'threads_token_expires_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['threads_user_id' => $threadsUserId]);
    }

    private function threads_confirmation_code(string $threadsUserId): string
    {
        $payload = $this->base64url_encode(json_encode(['sub' => hash('sha256', $threadsUserId), 'exp' => time() + 86400]));
        return $payload . '.' . $this->base64url_encode(hash_hmac('sha256', $payload, $this->app_secret_threads, true));
    }

    private function verify_threads_confirmation_code(string $code): bool
    {
        $parts = explode('.', $code, 2);
        if (count($parts) !== 2 || $this->app_secret_threads === '') return false;
        $expected = $this->base64url_encode(hash_hmac('sha256', $parts[0], $this->app_secret_threads, true));
        $payload = json_decode($this->base64url_decode($parts[0]), true);
        return hash_equals($expected, $parts[1]) && is_array($payload) && intval($payload['exp'] ?? 0) >= time();
    }

    private function base64url_encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64url_decode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding) $value .= str_repeat('=', 4 - $padding);
        return strval(base64_decode(strtr($value, '-_', '+/'), true));
    }

    private function cron_monitor_start($job, array $context = array())
    {
        if (!function_exists('monitoring_start_job')) {
            return null;
        }

        $context['controller'] = 'Api_v2';
        return monitoring_start_job($job, $context);
    }

    private function cron_monitor_finish($monitor, array $payload = array())
    {
        if ($monitor === null || !function_exists('monitoring_finish_job')) {
            return;
        }

        monitoring_finish_job($monitor, $payload);
    }

    private function cron_monitor_fail($monitor, Throwable $exception, array $payload = array())
    {
        if ($monitor === null || !function_exists('monitoring_fail_job')) {
            return;
        }

        monitoring_fail_job($monitor, $exception, $payload);
    }

    /**
     * MySQL advisory locks are connection-scoped, so they are released even if
     * a legacy endpoint ends with die(). This protects against duplicate HTTP
     * cron invocations as well as overlapping host scheduler entries.
     */
    private function cron_try_lock(string $name): bool
    {
        try {
            $row = $this->db->query('SELECT GET_LOCK(?, 0) AS acquired', array($name))->row_array();
            return intval($row['acquired'] ?? 0) === 1;
        } catch (Throwable $e) {
            log_message('error', 'cron_lock_failed name=' . $name . ' msg=' . $e->getMessage());
            return false;
        }
    }
    function interpolateVar($value, $env)
    {
        foreach ($env as $key => $val) {
            $value = str_replace("{{" . $key . "}}", $val, $value);
        }
        return $value;
    }

    function tiktok_signature_generator($dt)
    {
        $secret = $dt['secret'] ?? '';
        $ts = $dt['timest'] ?? null;
        $queryParam = $dt['get'] ?? [];

        $param = [];
        foreach ($queryParam as $key => $value) {
            if ($key === 'sign' || $key === 'access_token') {
                continue;
            }
            if ($key === 'timestamp' && $ts !== null) {
                $value = $ts;
            } else if ($value === null || $value === '{{' . $key . '}}') {
                $value = $this->getEnvVar($key);
            }
            $param[$key] = $value;
        }

        ksort($param);

        $path = parse_url($dt['url'], PHP_URL_PATH);
        $input = $path;
        foreach ($param as $key => $value) {
            $input .= $key . $value;
        }

        $body = $dt['post'] ?? '';
        if (is_array($body) || is_object($body)) {
            $body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if ($body !== null && $body !== '') {
            $input .= $body;
        }

        $input = $secret . $input . $secret;

        return hash_hmac('sha256', $input, $secret);
    }

    public function marketplace_callback_shopee()
    {
        $marketplace = "SHOPEE";
        $dt = $_GET;

        $host = 'https://partner.shopeemobile.com';
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $code = $dt['code'];
        $shop_id = $dt['shop_id'];
        $path = "/api/v2/auth/token/get";
        $timest = time();
        $body = array("code" => $code,  "shop_id" => intval($shop_id), "partner_id" => intval($partner_id));
        $baseString = sprintf("%s%s%s", $partner_id, $path, $timest);
        $sign = hash_hmac('sha256', $baseString, $partner_key);
        $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $host, $path, $partner_id, $timest, $sign);

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($c);
        $response = json_decode($response, true);

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $expired_at = time() + $response['expire_in'];
        if (empty($access_token)) {
            echo 'Koneksi shopee tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $path = "/api/v2/shop/get_profile";

        $timest = time();
        $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
        $sign = hash_hmac('sha256', $baseString, $partner_key);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        $response = json_decode($response, true);



        $shop_id = $shop_id;
        $shop_name = $response['response']['shop_name'];
        $shop = $response['response'];
        $img_url = $response['response']['shop_logo'];

        if (empty($shop_id)) {
            echo 'Toko shopee tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        if ($img_url) {
            $file_name = $shop_id . '.jpg';
            $img_dir = './assets/img/marketplace_account/' . $file_name;
            file_put_contents($img_dir, file_get_contents($img_url));
            $dt['img'] = $file_name;
        }
        $config = array();
        $config['partner_id'] = $partner_id;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;
        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'marketplace-account');
    }


    public function marketplace_callback_lazada()
    {
        $marketplace = "LAZADA";
        $dt = $_GET;
        $code = $dt['code'];
        $app_key = $this->app_key_lazada;
        $app_secret = $this->app_secret_lazada;

        $url = 'https://api.lazada.co.id/rest';

        $c = new LazopClient($url, $app_key, $app_secret);
        $request = new LazopRequest('/auth/token/create');
        $request->addApiParam('code', $code);

        $response = $c->execute($request);
        $response = json_decode($response, true);

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $expired_at = time() + $response['expires_in'];

        // print_r($response);

        if (empty($access_token)) {
            echo 'Koneksi lazada tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $c = new LazopClient($url, $app_key, $app_secret);
        $request = new LazopRequest('/seller/get', 'GET');
        $response = $c->execute($request, $access_token);
        $response = json_decode($response, true);


        $shop_id = $response['data']['seller_id'];
        $shop_name = $response['data']['name'];
        $shop = $response['data'];
        $img_url = $response['data']['logo_url'];


        if (empty($shop_id)) {
            echo 'Toko lazada tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        if ($img_url) {
            $file_name = $check['id'] . '.jpg';
            $img_dir = './assets/img/marketplace_account/' . $file_name;
            file_put_contents($img_dir, file_get_contents($img_url));
            $dt['img'] = $file_name;
        }
        $config = array();
        $config['app_key'] = $app_key;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;
        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'marketplace-account');
    }

    public function marketplace_callback_tiktok()
    {
        $marketplace = "TIKTOK";
        $dt = $_GET;
        $app_key = $dt['app_key'];
        $code = $dt['code'];
        $app_secret = $this->app_secret_tiktok;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'auth.tiktok-shops.com/api/v2/token/get?app_key=' . $app_key . '&app_secret=' . $app_secret . '&auth_code=' . $code . '&grant_type=authorized_code',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        $access_token = $response['data']['access_token'];
        $refresh_token = $response['data']['refresh_token'];
        $expired_at = $response['data']['access_token_expire_in'];
        if (empty($access_token)) {
            echo 'Koneksi tiktok tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $url = 'https://open-api.tiktokglobalshop.com/authorization/202309/shops?app_key=' . $app_key . '&sign={{sign}}&timestamp={{timestamp}}';
        $urlParts = parse_url($url);
        $paramGET = [];
        parse_str($urlParts['query'], $paramGET);
        $secret = $this->app_secret_tiktok;
        $timest = strtotime('now');
        $pr = array();
        $pr['secret'] = $secret;
        $pr['timest'] = $timest;
        $pr['get'] = $paramGET;
        $pr['url'] = $url;
        $sign = $this->tiktok_signature_generator($pr);

        $url = str_replace('{{sign}}', $sign, $url);
        $url = str_replace('{{timestamp}}', $timest, $url);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-tts-access-token: ' . $access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        $shop_id = $response['data']['shops'][0]['id'];
        $shop_name = $response['data']['shops'][0]['name'];
        $shop = $response['data']['shops'][0];

        if (empty($shop_id)) {
            echo 'Toko tiktok tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        $config = array();
        $config['app_key'] = $app_key;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;

        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'marketplace-account');
    }

    function marketplace_token_refresh()
    {
        header('Content-Type: application/json; charset=utf-8');
        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $marketplace = strtoupper($marketplace);
        $shop_id = $dt['shop_id'];
        $qry = "";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        $is_error = false;
        $text = '';

        foreach ($data as $k => $v) {
            if ($v['opt'] == "TIKTOK") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_tiktok;
                $refresh_token = $config['refresh_token'];
                $app_secret = $this->app_secret_tiktok;

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'auth.tiktok-shops.com/api/v2/token/refresh?app_key=' . $app_key . '&app_secret=' . $app_secret . '&refresh_token=' . $refresh_token . '&grant_type=refresh_token',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);
                curl_close($curl);

                $response = json_decode($response, true);

                if ($response['data']['access_token']) {

                    $access_token = $response['data']['access_token'];
                    $refresh_token = $response['data']['refresh_token'];
                    $expired_at = $response['data']['access_token_expire_in'];

                    $url = 'https://open-api.tiktokglobalshop.com/authorization/202309/shops?app_key=' . $app_key . '&sign={{sign}}&timestamp={{timestamp}}';
                    $urlParts = parse_url($url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
                    $secret = $this->app_secret_tiktok;
                    $timest = strtotime('now');
                    $pr = array();
                    $pr['secret'] = $secret;
                    $pr['timest'] = $timest;
                    $pr['get'] = $paramGET;
                    $pr['url'] = $url;
                    $sign = $this->tiktok_signature_generator($pr);

                    $url = str_replace('{{sign}}', $sign, $url);
                    $url = str_replace('{{timestamp}}', $timest, $url);

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => array(
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);

                    $response = json_decode($response, true);

                    $shop_id = $response['data']['shops'][0]['id'];
                    $shop_name = $response['data']['shops'][0]['name'];
                    $shop = $response['data']['shops'][0];

                    $dt = array();
                    $config = array();
                    $config['app_key'] = $app_key;
                    $config['access_token'] = $access_token;
                    $config['refresh_token'] = $refresh_token;
                    $config['shop'] = $shop;
                    $dt['val'] = json_encode($config, true);
                    $dt['opt'] = $marketplace;
                    $dt['status'] = "Aktif";
                    $dt['shop_id'] = $shop_id;
                    $dt['shop_name'] = $shop_name;
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = $_SESSION['user']['id'];
                    $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                    if ($shop_id) {
                        $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                    } else {
                        $is_error = true;
                        $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                    }
                }
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $refresh_token = $config['refresh_token'];
                $shop_id = $v['shop_id'];

                $host = 'https://partner.shopeemobile.com';
                $path = "/api/v2/auth/access_token/get";
                $timest = time();
                $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
                $baseString = sprintf("%s%s%s", $partner_id, $path, $timest);
                $sign = hash_hmac('sha256', $baseString, $partner_key);
                $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $host, $path, $partner_id, $timest, $sign);

                $c = curl_init($url);
                curl_setopt($c, CURLOPT_POST, 1);
                curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
                curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

                $response = curl_exec($c);
                $response = json_decode($response, true);

                $access_token = $response['access_token'];
                $refresh_token = $response['refresh_token'];
                $expired_at = time() + $response['expire_in'];

                if ($access_token) {
                    $path = "/api/v2/shop/get_profile";

                    $timest = time();
                    $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
                    $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                    $sign = hash_hmac('sha256', $baseString, $partner_key);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json'
                        ),
                    ));
                    $response = curl_exec($curl);
                    $response = json_decode($response, true);


                    $shop_id = $shop_id;
                    $shop_name = $response['response']['shop_name'];
                    $shop = $response['response'];
                    $img_url = $response['response']['shop_logo'];
                    if ($shop_id) {
                        $dt = array();
                        if ($img_url) {
                            $file_name = $shop_id . '.jpg';
                            $img_dir = './assets/img/marketplace_account/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        $config = array();
                        $config['partner_id'] = $partner_id;
                        $config['access_token'] = $access_token;
                        $config['refresh_token'] = $refresh_token;
                        $config['shop'] = $shop;
                        $dt['val'] = json_encode($config, true);
                        $dt['opt'] = $marketplace;
                        $dt['status'] = "Aktif";
                        $dt['shop_id'] = $shop_id;
                        $dt['shop_name'] = $shop_name;
                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                        $dt['updated_by'] = $_SESSION['user']['id'];
                        $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                        $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                    } else {
                        $is_error = true;
                        $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                    }
                } else {
                    $is_error = true;
                    $text .= 'Refresh token shopee id : ' . $v['shop_id'] . ' tidak valid!<br>';
                }
            } else if ($v['opt'] == "LAZADA") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $refresh_token = $config['refresh_token'];
                $url = 'https://api.lazada.co.id/rest';

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/auth/token/refresh');
                $request->addApiParam('refresh_token', $refresh_token);
                $response = $c->execute($request);
                $response = json_decode($response, true);

                $access_token = $response['access_token'];
                $refresh_token = $response['refresh_token'];
                $expired_at = time() + $response['expires_in'];


                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/seller/get', 'GET');
                $response = $c->execute($request, $access_token);
                $response = json_decode($response, true);

                $shop_id = $response['data']['seller_id'];
                $shop_name = $response['data']['name'];
                $shop = $response['data'];
                $img_url = $response['data']['logo_url'];

                $dt = array();
                if ($img_url) {
                    $file_name = $shop_id . '.jpg';
                    $img_dir = './assets/img/marketplace_account/' . $file_name;
                    file_put_contents($img_dir, file_get_contents($img_url));
                    $dt['img'] = $file_name;
                }

                $config = array();
                $config['app_key'] = $app_key;
                $config['access_token'] = $access_token;
                $config['refresh_token'] = $refresh_token;
                $config['shop'] = $shop;
                $dt['val'] = json_encode($config, true);
                $dt['opt'] = $marketplace;
                $dt['status'] = "Aktif";
                $dt['shop_id'] = $shop_id;
                $dt['shop_name'] = $shop_name;
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = $_SESSION['user']['id'];
                $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                if ($shop_id) {
                    $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                } else {
                    $is_error = true;
                    $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                }
            } else if ($v['opt'] == "META") {
                $marketplace = $v['opt'];
                $app_id = $this->app_id_meta;
                $app_secret = $this->app_secret_meta;
                $config = json_decode($v['val'], true);
                $refresh_token = $config['access_token'];

                $url = "https://graph.facebook.com/v21.0/oauth/access_token";

                $params = array(
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => $app_id,
                    'client_secret' => $app_secret,
                    'fb_exchange_token' => $refresh_token
                );

                $url_with_params = $url . '?' . http_build_query($params);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url_with_params);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);

                $response_data = json_decode($response, true);

                $config = array();
                $config['app_id'] = $app_id;
                $config['access_token'] = $refresh_token;
                $config['refresh_token'] = $refresh_token;
                $dt['val'] = json_encode($config, true);
                $dt['opt'] = $marketplace;
                $dt['status'] = "Aktif";
                $dt['shop_id'] = $app_id;
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = $_SESSION['user']['id'];
                $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                if ($app_id) {
                    $sql = "UPDATE marketplace_config 
                            SET val = ? 
                            WHERE shop_id = ?";
                    $this->db->query($sql, array($dt['val'], $app_id));
                } else {
                    $is_error = true;
                    $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                }
            }
        }
        if ($is_error) {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = $text;
            echo json_encode($html, true);
            die;
        } else {
            $html['status'] = true;
            $html['data'] = array();
            $html['msg'] = 'Refresh token berhasil!';
            echo json_encode($html, true);
            die;
        }
    }

    private function report_marketplace_curl_error($endpoint, $error, array $context = array())
    {
        $context['controller'] = 'Api_v2';
        $context['endpoint'] = $endpoint;
        $context['curl_error'] = $error;

        sentry_capture_message('Marketplace integration CURL error', $context);
    }

    function customer_summary()
    {
        $id_customer = $_GET['id'];
        $this->buyer_summary($id_customer);

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Pembaharuan customer order berhasil!';
        echo json_encode($html, true);
        die;
    }

    function buyer_summary($id_customer)
    {

        $dtt = array();
        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count, 
        SUM(omset_kotor) as omset_kotor,
        SUM(komisi_afiliasi) as komisi_afiliasi,
        SUM(diskon_penjual) as diskon_penjual,
        SUM(omset_bersih) as omset_bersih,
        SUM(dana_pencairan) as dana_pencairan,
        SUM(price_total) as price_total,
        SUM(marketplace_fee) as marketplace_fee,
        SUM(customer_price) as customer_price,
        SUM(transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'
        AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
        ORDER BY transaction.date ASC
        ");

        $dtt['count_order'] = strval($query[0]['count']);
        $dtt['return_trx'] = strval($query[0]['returnn']);
        $dtt['omset_kotor'] = strval($query[0]['omset_kotor']);
        $dtt['komisi_afiliasi'] = strval($query[0]['komisi_afiliasi']);
        $dtt['diskon_penjual'] = strval($query[0]['diskon_penjual']);
        $dtt['omset_bersih'] = strval($query[0]['omset_bersih']);
        $dtt['dana_pencairan'] = strval($query[0]['dana_pencairan']);
        $dtt['price_total'] = strval($query[0]['price_total']);
        $dtt['marketplace_fee'] = strval($query[0]['marketplace_fee']);
        $dtt['customer_price'] = strval($query[0]['customer_price']);

        $query = $this->mymodel->selectWithQuery("SELECT
        transaction.date,
        order_id,
        order_status,
        pesanan,
        transaction.json,
        is_manual,
        (omset_kotor) as omset_kotor,
        (komisi_afiliasi) as komisi_afiliasi,
        (diskon_penjual) as diskon_penjual,
        (omset_bersih) as omset_bersih,
        (dana_pencairan) as dana_pencairan,
        (price_total) as price_total,
        (marketplace_fee) as marketplace_fee,
        (customer_price) as customer_price,
        (transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'
        AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
        ORDER BY transaction.date ASC");

        $dtt['first_order'] = strval($query[0]['date']);

        $last_query = end($query);

        $dtt['last_order'] = "";
        if ($last_query['date'] && $last_query['date'] != $dtt['first_order']) {
            $dtt['last_order'] = strval($last_query['date']);
        }


        $dt = array();
        if ($query) {
            if (count($query) > 1) {
                $dt['cb_cl'] = "CL";
            } else {
                $dt['cb_cl'] = "CB";
            }
            $this->db->update('transaction', $dt, array('customer' => $id_customer));
        }


        $json = array();
        $price_total_hpp = 0;
        foreach ($query as $kk => $vv) {
            $json[$kk]['date'] = $vv['date'];
            $json[$kk]['id'] = $vv['id'];
            $json[$kk]['order_id'] = $vv['order_id'];
            $json[$kk]['order_status'] = $vv['order_status'];
            $json[$kk]['is_manual'] = $vv['is_manual'];
            if ($vv['is_manual'] == 1) {
                $json[$kk]['data'] = json_decode($vv['pesanan'], true);
            } else {
                $vv['pesanan'] = array();
                foreach (json_decode($vv['json'], true) as $kkk => $vvv) {
                    $vv['pesanan'][$kkk]['item_name'] = $vvv['product_text'];
                    $vv['pesanan'][$kkk]['item_sku'] = $vvv['sku'];
                    $vv['pesanan'][$kkk]['item_id'] = $vvv['product'];
                    $vv['pesanan'][$kkk]['qty'] = $vvv['qty'];
                }
                $json[$kk]['data'] = $vv['pesanan'];
            }
        }

        $dtt['pesanan'] = json_encode($json, true);
        if (empty($query)) {
            $this->db->delete('customer', array('id' => $id_customer));
        } else {
            if (count($query) > 1) {
                $dt['cb_cl'] = "CL";
            } else {
                $dt['cb_cl'] = "CB";
            }
            $this->db->update('customer', $dtt, array('id' => $id_customer));
        }

        return $dt;
    }

    function calculate_buyer($dt)
    {
        $user = $_SESSION['user'];

        if (is_string($dt['pesanan'])) {
            $dt['pesanan'] = json_decode($dt['pesanan'], true);
        }

        $sku_list = [];
        foreach ($dt['pesanan'] as $item) {
            $sku_cleaned = preg_replace('/^\d+-/', '', $item['sku']);
            $sku_list[] = "'" . $sku_cleaned . "'";
        }

        if (!empty($sku_list)) {
            $sku_values = implode(',', $sku_list);
            $query = "SELECT DISTINCT p.sku, p.brand FROM product p WHERE p.is_varian = 0 AND p.sku IN ($sku_values)";
            $result = $this->mymodel->selectWithQuery($query);
        } else {
            $result = [];
        }

        $brand_list = array_column($result, 'brand');

        $dt['brand'] = implode(', ', array_unique($brand_list));

        $data = $this->mymodel->selectDataOne('customer', ['id_buyer' => $dt['id_buyer'], 'marketplace' => $dt['marketplace']]);


        if (empty($data)) {
            $dtt = [
                'akun_type' => $dt['c_type'],
                'brand' => $dt['brand'],
                'marketplace' => $dt['marketplace'],
                'id_buyer' => strval($dt['id_buyer']),
                'status' => "Aktif",
                'created_at' => date("Y-m-d H:i:s"),
                'created_by' => strval($user['id']),
                'full_name' => $dt['customer_text'],
                'phone' => $dt['phone'],
                'username' => strval($dt['c_username']),
                'count_order' => 1,
                'first_order' => strval($dt['date']),
                'last_order' => strval($dt['date']),
                'address' => $dt['address'],
                'province_text' => $dt['province_text'],
                'city_text' => $dt['city_text'],
                'subdistrict_text' => $dt['subdistrict_text'],
                'shop_id' => $dt['shop_id'],
                'shop_name' => $dt['shop_name']
            ];
            $this->db->insert('customer', $dtt);
            $id_buyer = $this->db->insert_id();
        } else {
            $dtt = [
                'updated_at' => date("Y-m-d H:i:s"),
                'shop_id' => $dt['shop_id'],
                'shop_name' => $dt['shop_name'],
                'brand' => $dt['brand'],
                'akun_type' => $dt['c_type'],
            ];
            $this->db->update('customer', $dtt, ['id' => $data['id']]);
            $id_buyer = $data['id'];
        }

        $this->db->update('transaction', ['customer' => $id_buyer], ['id' => $dt['id']]);

        $this->buyer_summary($id_buyer);
    }


    public function update_stock($id_product)
    {
        $dtp = array();
        $id_product = $id_product;
        $query = $this->mymodel->selectWithQuery("SELECT SUM(qty_in) as qty_in,SUM(qty_in_pos) as qty_in_pos,SUM(qty_out) as qty_out,SUM(qty_out_pos) as qty_out_pos,SUM(qty) as qty FROM stock WHERE product = '$id_product'");

        $dtp['stock_in'] = strval($query[0]['qty_in']);
        $dtp['stock_in_pos'] = strval($query[0]['qty_in_pos']);
        $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
        $dtp['stock_out_pos'] = strval(abs($query[0]['qty_out_pos']) * -1);
        $dtp['stock'] = strval(doubleval($query[0]['qty']));
        $this->db->update('product', $dtp, array('id' => $id_product));
    }

    function calculate_stock_product()
    {
        $product = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
        ORDER BY sku ASC
        ");
        $product_arr = array();
        foreach ($product as $k => $v) {
            $product_arr[$v['id']] = $v;
        }

        foreach ($product_arr as $k => $v) {
            $id_product = $v['id'];
            $this->update_stock($id_product);
        }
    }
    function calculate_stock_product_by_id($id)
    {
        $product = $this->mymodel->selectWithQuery("SELECT * FROM product 
        WHERE id = '$id'
        ORDER BY sku ASC
        ");
        $product_arr = array();
        foreach ($product as $k => $v) {
            $product_arr[$v['id']] = $v;
        }

        foreach ($product_arr as $k => $v) {
            $id_product = $v['id'];
            $this->update_stock($id_product);
        }
    }

    function calculate_stock($dt)
    {
        $order_id = $dt['order_id'];
        $marketplace = $dt['marketplace'];
        $shop_id = $dt['shop_id'];
        $user = $_SESSION['user'];
        $id_trx = $dt['id'];
        if ($order_id) {
            $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
            $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
        }
        // if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL'))) {
        //     $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        //     $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        // }
        // if (!in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL', 'RETURN', 'REFUND'))) {
        foreach ($dt['stock_product_3rd'] as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);
            $dts['shop_id'] = strval($dt['shop_id']);
            $dts['shop_name'] = strval($dt['shop_name']);
            $dts['brand'] = strval($dt['brand']);
            $dts['id_trx'] = strval($dt['id']);
            $dts['qty'] = 0 - abs($v2['qty']);
            // $dts['qty_out'] = abs($v2['qty']);
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';

            $dts['original_price'] = doubleval($v2['original_price']);
            $dts['price'] = doubleval($v2['price']);
            $dts['discount'] = doubleval($v2['discount']);
            $dts['price_total'] = doubleval($v2['price_total']);



            $dts['product_id'] = strval($v2['id_product_parent']);
            $dts['product_sku'] = strval($v2['sku_parent']);
            $dts['product_text'] = strval($v2['name_parent']);
            $dts['varian_id'] = strval($v2['id_product']);
            $dts['varian_sku'] = strval($v2['sku']);
            $dts['varian_text'] = strval($v2['name']);
            $dts['order_id'] = $dt['order_id'];
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";
            $dts['desc'] = "Penjualan";

            if ($dts['id_trx']) {
                $this->db->insert('stock_product_3rd', $dts);
            }

            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL'))) {
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "In";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  abs($v2['qty']);
                $dts['desc'] = "Return";

                if ($dts['id_trx']) {
                    $this->db->insert('stock_product_3rd', $dts);
                }
            }

            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL')) && $dt['is_shipped'] == 1) {
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "Ongoing";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  '0';
                $dts['qty_retur'] =  abs($v2['qty']);
                $dts['desc'] = "Return";

                if ($dts['id_trx']) {
                    $this->db->insert('stock_product_3rd', $dts);
                }
            }
        }

        // if ($dt['order_status'] == 'CANCELLED' && $dt['is_shipped'] == 0) {
        //     $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        // }

        foreach ($dt['stock'] as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);
            $dts['shop_id'] = strval($dt['shop_id']);
            $dts['shop_name'] = strval($dt['shop_name']);
            $dts['id_trx'] = strval($dt['id']);
            $dts['qty'] = 0 - abs($v2['qty']);
            // $dts['qty_out'] = abs($v2['qty']);
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';
            $dts['price'] = doubleval($v2['price']);
            $dts['discount'] = doubleval($v2['discount']);
            $dts['hpp'] = doubleval($v2['hpp']);
            $dts['price_total'] = doubleval($v2['price_total']);
            $dts['product'] = $v2['product'];
            $dts['product_text'] = $v2['product_text'];
            $dts['sku'] = $v2['sku'];
            $dts['order_id'] = $dt['order_id'];
            $dts['brand'] = strval($dt['brand']);
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";

            if ($dts['id_trx']) {
                $this->db->insert('stock', $dts);
            }

            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL'))) {
                if (empty($dt['return_at'])) {
                    $dt['return_at'] = $dt['cancel_at'];
                }
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "In";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  abs($v2['qty']);
                if ($dts['id_trx']) {
                    $this->db->insert('stock', $dts);
                }
            }

            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL')) && $dt['is_shipped'] == 1) {
                if (empty($dt['return_at'])) {
                    $dt['return_at'] = $dt['cancel_at'];
                }
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "Ongoing";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  '0';
                $dts['qty_retur'] =  abs($v2['qty']);
                if ($dts['id_trx']) {
                    $this->db->insert('stock', $dts);
                }
            }
        }

        // if ($dt['order_status'] == 'CANCELLED' && $dt['is_shipped'] == 0) {
        //     $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        // }

        foreach ($dt['stock'] as $k2 => $v2) {
            $this->calculate_stock_product_by_id($v2['product']);
        }
        // }
        $this->update_stock_marketplace($dt);
    }


    function update_stock_marketplace($dt)
    {
        $id_product = array_keys($dt['stock']);
        $id_products = implode("','", $id_product);

        $products = $this->mymodel->selectWithQuery("SELECT sku, stock as stock_akhir 
                                FROM product
                                WHERE id IN ('$id_products') GROUP BY sku");
        $stock_map = [];
        $sku_arr = [];
        foreach ($products as $sku) {
            $uppercase_sku = strtoupper($sku['sku']);
            $sku_arr[] = $uppercase_sku;
            $stock_map[$uppercase_sku] = $sku['stock_akhir'];

            if (preg_match('/^(\d+)-(.+)/', $sku['sku'], $matches)) {
                $base_sku = strtoupper($matches[2]);
                if (!isset($stock_map[$base_sku])) {
                    $stock_map[$base_sku] = $sku['stock_akhir'];
                }
            }
        }

        $like_clauses = array_map(function ($sku) {
            return "json_varian LIKE '%$sku%'";
        }, $sku_arr);
        $like_sql = implode(' OR ', $like_clauses);

        $products_3rd = $this->mymodel->selectWithQuery("SELECT id_product, marketplace, shop_id, shop_name, json_varian FROM product_3rd WHERE $like_sql");

        foreach ($products_3rd as &$p3) {
            $json_varian = json_decode($p3['json_varian'], true);
            $p3_variants = [];

            if (is_array($json_varian)) {
                foreach ($json_varian as $varian) {
                    $sku = isset($varian['model_sku']) ? $varian['model_sku'] : ($varian['sku'] ?? '');
                    $sku_parent = $varian['sku_parent'] ?? '';
                    $sku_used = '';
                    $stock_total = null;


                    $stock_map_upper = array_change_key_case($stock_map, CASE_UPPER);

                    $bundle_parts = [];
                    if ($sku !== '') {
                        $bundle_parts = preg_split('/[\s&+\/,]+/', strtoupper(trim($sku)));
                    } elseif ($sku_parent !== '') {
                        $bundle_parts = preg_split('/\s*[&+,\/]\s*/', strtoupper(trim($sku_parent)));
                        $bundle_parts = array_map('trim', $bundle_parts);
                        $bundle_parts = array_filter($bundle_parts);
                    }

                    if (preg_match('/^(\d+)-(.+)/', $sku, $matches)) {
                        $quantity = (int)$matches[1];
                        $base_sku = strtoupper($matches[2]);

                        if (isset($stock_map_upper[$base_sku])) {
                            $stock_total = floor($stock_map_upper[$base_sku] / $quantity);
                            $sku_used = $sku;
                        }
                    } elseif (count($bundle_parts) > 1) {
                        $stock_candidates = [];
                        $sku_in = implode("','", array_map('addslashes', $bundle_parts));
                        $query = "SELECT sku, stock as stock_akhir 
                                  FROM product 
                                  WHERE UPPER(sku) IN ('$sku_in') 
                                  GROUP BY sku";

                        $result = $this->mymodel->selectWithQuery($query);

                        $stock_lookup = [];
                        foreach ($result as $row) {
                            $stock_lookup[strtoupper($row['sku'])] = $row['stock_akhir'];
                        }

                        foreach ($bundle_parts as $part) {
                            $part_upper = strtoupper($part);
                            if (isset($stock_lookup[$part_upper])) {
                                $stock_candidates[] = $stock_lookup[$part_upper];
                            }
                        }

                        if (!empty($stock_candidates)) {
                            $stock_total = min($stock_candidates);
                            $sku_used = implode(' + ', $bundle_parts);
                        }
                    } else {
                        $sku_candidate = !empty($bundle_parts) ? $bundle_parts[0] : strtoupper(trim($sku));

                        if (isset($stock_map_upper[$sku_candidate])) {
                            $stock_total = $stock_map_upper[$sku_candidate];
                            $sku_used = $sku_candidate;
                        }
                    }

                    if ($stock_total !== null) {
                        $variant_data = [
                            'id_product' => $p3['id_product'],
                            'marketplace' => $p3['marketplace'],
                            'shop_id' => $p3['shop_id'],
                            'shop_name' => $p3['shop_name'],
                            'json_varian' => $p3['json_varian'],
                            'sku' => $sku_used,
                            'stock' => $stock_total,
                            'variant_details' => $varian
                        ];

                        $p3_variants[] = $variant_data;
                    }
                }
            }

            if (empty($p3_variants)) {
                $p3_variants[] = [
                    'id_product' => $p3['id_product'],
                    'marketplace' => $p3['marketplace'],
                    'shop_id' => $p3['shop_id'],
                    'shop_name' => $p3['shop_name'],
                    'json_varian' => $p3['json_varian'],
                    'sku' => null,
                    'stock' => 0
                ];
            }

            $p3 = $p3_variants;
        }
        unset($p3);

        $flattened_products = [];
        foreach ($products_3rd as $product_variants) {
            foreach ($product_variants as $variant) {
                $flattened_products[] = $variant;
            }
        }

        $arr_product = [
            'SHOPEE' => [],
            'LAZADA' => [],
            'TIKTOK' => [],
        ];

        foreach ($flattened_products as $product) {
            $marketplace = strtoupper($product['marketplace']);
            if (isset($arr_product[$marketplace])) {
                $arr_product[$marketplace][] = $product;
            }
        }

        foreach ($arr_product as $marketplace => $products) {
            foreach ($products as $product) {
                $stock = $product['stock'];

                switch ($marketplace) {
                    case 'SHOPEE':
                        $this->updateShopeeStock($product, $stock);
                        break;
                    case 'LAZADA':
                        $this->updateLazadaStock($product, $stock);
                        break;
                    case 'TIKTOK':
                        $this->updateTiktokStock($product, $stock);
                        break;
                }
            }
        }
    }

    protected function updateShopeeStock($product, $stock)
    {
        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $product['shop_id']));
        if (!$config) return false;

        $config = json_decode($config['val'], true);
        $access_token = $config['access_token'];
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $host = 'https://partner.shopeemobile.com';

        $path = "/api/v2/product/update_stock";
        $timest = time();
        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $product['shop_id']);
        $sign = hash_hmac('sha256', $baseString, $partner_key);

        $variants = json_decode($product['json_varian'], true) ?? [];

        $stock_list = [];
        $target_sku = $product['sku'];

        foreach ($variants as $variant) {
            if (isset($variant['sku']) && strtoupper(trim($variant['sku'])) === strtoupper(trim($target_sku))) {
                if (!empty($variant['id_product']) && $variant['id_product'] != '0') {
                    $stock_list[] = [
                        'model_id' => (int)$variant['id_product'],
                        'seller_stock' => [
                            [
                                'stock' => (int)$stock
                            ]
                        ]
                    ];
                    break;
                }
            }
        }

        if (empty($stock_list)) {
            $stock_list[] = [
                'seller_stock' => [
                    [
                        'stock' => (int)$stock
                    ]
                ]
            ];
        }

        $item_id = is_numeric($product['id_product']) ? (int)$product['id_product'] : 0;
        if ($item_id <= 0) {
            echo "Invalid product ID: " . $product['id_product'];
            return false;
        }

        $post_data = [
            'item_id' => $item_id,
            'stock_list' => $stock_list
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&partner_id=' . $partner_id . '&shop_id=' . $product['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data, JSON_NUMERIC_CHECK), // Gunakan JSON_NUMERIC_CHECK
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            echo 'Curl error: ' . curl_error($curl);
        }

        curl_close($curl);

        return $response;
    }

    protected function updateTiktokStock($product, $stock)
    {

        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $product['shop_id']));
        if (!$config) return false;

        $config = json_decode($config['val'], true);
        $marketplace = "TIKTOK";
        $app_key = $config['app_key'];
        $access_token = $config['access_token'];
        $shop_cipher = $config['shop']['cipher'];
        $shop_id = $product['shop_id'];
        $app_secret = $this->app_secret_tiktok;

        $skus = [];
        $target_sku = $product['sku'];
        $variants = json_decode($product['json_varian'], true) ?? [];

        foreach ($variants as $variant) {
            if (isset($variant['sku']) && strtoupper(trim($variant['sku'])) === strtoupper(trim($target_sku))) {
                if (!empty($variant['id_product']) && $variant['id_product'] != '0') {
                    $skus[] = [
                        'id' => (string)$variant['id_product'],
                        'inventory' => [
                            [
                                'quantity' => (int)$stock
                            ]
                        ]
                    ];
                    break;
                }
            }
        }

        if (empty($skus)) {
            $skus[] = [
                'id' => (string)$product['id_product'],
                'inventory' => [
                    [
                        'quantity' => (int)$stock
                    ]
                ]
            ];
        }

        $post_data = [
            'skus' => $skus
        ];

        $base_url = 'https://open-api.tiktokglobalshop.com/product/202309/products/'
            . $product['id_product'] . '/inventory/update';

        $timest = time();

        $body = json_encode($post_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $queryParams = [
            'app_key'     => $app_key,
            'timestamp'   => $timest,
            'shop_cipher' => $shop_cipher,
        ];

        $sign = $this->tiktok_signature_generator([
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $queryParams,
            'url'    => $base_url,
            'post'   => $body,
        ]);

        $queryParams['sign'] = $sign;

        $url = $base_url . '?' . http_build_query($queryParams);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token,
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        return $response;
    }


    protected function updateLazadaStock($product, $stock)
    {
        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $product['shop_id']));
        if (!$config) return false;

        $config = json_decode($config['val'], true);
        $access_token = $config['access_token'];
        $app_key = $this->app_key_lazada;
        $app_secret = $this->app_secret_lazada;
        $url = 'https://api.lazada.co.id/rest';

        $variants = json_decode($product['json_varian'], true) ?? [];

        $sku_payload = '';
        $target_sku = $product['sku'];

        foreach ($variants as $variant) {
            if (isset($variant['sku']) && strtoupper(trim($variant['sku'])) === strtoupper(trim($target_sku))) {
                $sku_id = !empty($variant['id_product']) ? $variant['id_product'] : $product['id_product'];

                $sku_payload = '
                <Sku>
                    <ItemId>' . $product['id_product'] . '</ItemId>
                    <SkuId>' . $sku_id . '</SkuId>
                    <SellerSku>' . htmlspecialchars($variant['sku']) . '</SellerSku>
                    <SellableQuantity>' . (int)$stock . '</SellableQuantity>
                </Sku>';
                break;
            }
        }

        if (empty($sku_payload)) {
            $sku_payload = '
            <Sku>
                <ItemId>' . $product['id_product'] . '</ItemId>
                <SkuId>' . $product['id_product'] . '</SkuId>
                <SellerSku>' . htmlspecialchars($product['sku']) . '</SellerSku>
                <SellableQuantity>' . (int)$stock . '</SellableQuantity>
            </Sku>';
        }

        $xml_payload = '<Request>
            <Product>
                <Skus>
                    ' . $sku_payload . '
                </Skus>
            </Product>
        </Request>';

        echo htmlspecialchars($xml_payload) . "\n";

        $c = new LazopClient($url, $app_key, $app_secret);
        $request = new LazopRequest('/product/stock/sellable/update');
        $request->addApiParam('payload', $xml_payload);

        $response = $c->execute($request, $access_token);
        $response = json_decode($response, true);
        return $response;
    }

    function marketplace_order_detail()
    {
        header('Content-Type: application/json; charset=utf-8');

        $is_configurated = 1;

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $order_id = $dt['order_id'];
        $shop_id = $dt['shop_id'];
        $mode = $dt['mode'];

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }

        if ($marketplace) {
            $this->db->where('marketplace', $marketplace);
        }

        $this->db->select('id,marketplace,order_id,shop_id,shop_name');
        $trx_existing = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id));

        $is_error = '';
        $msg = '';

        $trx = array();
        $trx['marketplace'] = $dt['marketplace'];
        $trx['order_id'] = $dt['order_id'];
        $trx['shop_id'] = $dt['shop_id'];

        if (empty($shop_id)) {
            $trx = $trx_existing;
        }

        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
        $shop_name = $config['shop_name'];
        $shop_id = $config['shop_id'];

        if ($trx['marketplace'] == "TIKTOK") {
            $marketplace = $trx['marketplace'];
            $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
            $config = json_decode($config['val'], true);
            $app_key = $config['app_key'];
            $access_token = $config['access_token'];
            $shop_cipher = $config['shop']['cipher'];
            $app_secret = $this->app_secret_tiktok;

            $url = 'https://open-api.tiktokglobalshop.com/order/202309/orders?access_token=' . $access_token . '&app_key=' . $app_key . '&ids=' . $order_id . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

            $urlParts = parse_url($url);
            $paramGET = [];
            parse_str($urlParts['query'], $paramGET);
            $timest = strtotime('now');
            $pr = array();
            $pr['secret'] = $app_secret;
            $pr['timest'] = $timest;
            $pr['get'] = $paramGET;
            $pr['url'] = $url;
            $sign = $this->tiktok_signature_generator($pr);

            $url = str_replace('{{sign}}', $sign, $url);
            $url = str_replace('{{timestamp}}', $timest, $url);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'x-tts-access-token: ' . $access_token
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);


            if (empty($response['data'])) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            $v2 = $response['data']['orders'][0];

            $price_total_hpp = 0;

            $dt = array();
            $dt['type'] = "Out";
            $dt['type_sub'] = "POS";
            $dt['order_id'] = $order_id;
            $dt['shop_id'] = $shop_id;
            $dt['shop_name'] = $shop_name;
            $dt['marketplace'] = $marketplace;
            $dt['date'] = DATE("Y-m-d H:i:s", $v2['create_time']);
            $dt['shipping'] = strval($v2['shipping_provider']);
            $dt['awb_number'] = strval($v2['tracking_number']);

            if ($v2['is_sample_order'] == true) {
                $dt['c_type'] = "Affiliate";
            } else {
                $dt['c_type'] = "Pelanggan";
            }

            $js = array();
            foreach ($v2['line_items'] as $k4 => $v4) {
                $js[$k4]['id_product'] = $v4['sku_id'];
                $js[$k4]['sku'] = $v4['seller_sku'];
                $name = $v4['sku_name'];
                if ($name == "Default") {
                    $name = "";
                }
                $js[$k4]['name'] = $name;
                $js[$k4]['id_product_parent'] = $v4['product_id'];
                $js[$k4]['sku_parent'] = "";
                $js[$k4]['name_parent'] = $v4['product_name'];
                $js[$k4]['qty'] = '1';
                $js[$k4]['price'] = $v4['sale_price'];
                $js[$k4]['original_price'] = $v4['original_price'];
                $js[$k4]['discount'] = $v4['seller_discount'];
            }



            $c_type['akun_type'] = "Pelanggan";

            $brand = array();
            $json = array();
            foreach ($js as $k4 => $v4) {
                $id_product = $v4['id_product'];
                $id_product_parent = $v4['id_product_parent'];
                $this->db->select('json');
                $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));

                if (empty($conf) && $v4['sku']) {
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                }

                $conf = json_decode($conf['json'], true);
                if (empty($conf)) {
                    $js[$k4]['is_empty'] = true;
                    $is_configurated = 0;
                }
                foreach ($conf as $k5 => $v5) {
                    $product = $arr_product[$v5['product']];
                    $brand[$product['brand']] += 1;
                    $price = 0;
                    if ($dt['c_type'] == "Pelanggan") {
                        $price = $product['price_normal'];
                    } else if ($dt['c_type'] == "Distributor") {
                        $price = $product['price_distributor'];
                    } else if ($dt['c_type'] == "Reseller") {
                        $price = $product['price_reseller'];
                    } else {
                        $price = $product['price_normal'];
                    }
                    $json[$product['id']]['sku'] = $product['sku'];
                    $json[$product['id']]['hpp'] = $product['price_buy'];
                    $json[$product['id']]['product'] = $product['id'];
                    $json[$product['id']]['product_text'] = $product['name'];
                    $json[$product['id']]['product_sub'] = $product['sub_name'];
                    $json[$product['id']]['brand'] = $product['brand'];
                    $json[$product['id']]['price'] = $price;
                    $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                    $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                    $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                    $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                }
            }

            $brand_selected = "MG";
            $arr_brand = array();
            foreach ($json as $k4 => $v4) {
                $arr_brand[$v4['brand']] += 1;
            }

            $max = 0;
            foreach ($arr_brand as $k => $v) {
                if ($v >= $max) {
                    $max = $v;
                    $brand_selected = $k;
                }
            }

            $dt['brand'] = $brand_selected;

            $dt['pesanan'] = json_encode($js, true);
            $dt['pesanan_count'] = count($js);

            $dt['hpp'] = doubleval($price_total_hpp);
            $dt['json'] = json_encode($json, true);

            if ($v2['is_cod'] == true) {
                $dt['payment_type'] = "COD";
            } else {
                $dt['payment_type'] = "TF";
            }
            if ($v2['rts_time']) {
                $dt['rts_at'] = strval(DATE("Y-m-d H:i:s", $v2['rts_time']));
            }
            if ($v2['paid_time']) {
                $dt['payment_status'] = "Paid";
                $dt['pay_at'] = strval(DATE("Y-m-d H:i:s", $v2['paid_time']));
            } else {
                $dt['payment_status'] = "Unpaid";
            }

            $order_status = "PENDING";
            $dt['is_shipped'] = 0;
            if (in_array($v2['status'], array('UNPAID'))) {
                $order_status = 'UNPAID';
            } else if (in_array($v2['status'], array('AWAITING_COLLECTION', 'ON_HOLD'))) {
                $order_status = 'PROCESSED';
            } else if (in_array($v2['status'], array('returned'))) {
                $order_status = 'RETURN';
            } else if (in_array($v2['status'], array('CANCELLED'))) {
                $order_status = 'CANCELLED';
            } else if (in_array($v2['status'], array('COMPLETED'))) {
                $order_status = 'COMPLETED';
            } else if (in_array($v2['status'], array('DELIVERED'))) {
                $order_status = 'DELIVERED';
            } else if (in_array($v2['status'], array('IN_TRANSIT', 'PARTIALLY_SHIPPING'))) {
                $order_status = 'SHIPPED';
                $dt['is_shipped'] = 1;
            } else if (in_array($v2['status'], array('AWAITING_SHIPMENT'))) {
                $order_status = 'READY_TO_SHIP';
            }

            $dt['order_status'] = $order_status;
            $dt['return_at'] = DATE("Y-m-d H:i:s", $v2['cancel_time']);

            if (in_array($dt['order_status'], array('DELIVERED', 'COMPLETED', 'CANCELLED'))) {

                $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/returns/search?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                $urlParts = parse_url($url);
                $paramGET = [];
                parse_str($urlParts['query'], $paramGET);
                $timest = strtotime('now');
                $pr = array();
                $pr['secret'] = $app_secret;
                $pr['timest'] = $timest;
                $pr['get'] = $paramGET;
                $pr['post'] = '{"order_ids":["' . $order_id . '"]}';
                $pr['url'] = $url;
                $sign = $this->tiktok_signature_generator($pr);

                $url = str_replace('{{sign}}', $sign, $url);
                $url = str_replace('{{timestamp}}', $timest, $url);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $pr['post'],
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'x-tts-access-token: ' . $access_token
                    ),
                ));


                $response = curl_exec($curl);
                $response = json_decode($response, true);

                // if ($response['data']['return_orders']) {
                //     $v3 = $response['data']['return_orders'][0];
                //     $dt['return_at'] = DATE("Y-m-d H:i:s", $v3['create_time']);
                //     $dt['order_status'] = "RETURN";
                // }

                $url = 'https://open-api.tiktokglobalshop.com/finance/202501/orders/' . $order_id . '/statement_transactions?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}';

                $urlParts = parse_url($url);
                $paramGET = [];
                parse_str($urlParts['query'], $paramGET);
                $timest = strtotime('now');
                $pr = array();
                $pr['secret'] = $app_secret;
                $pr['timest'] = $timest;
                $pr['get'] = $paramGET;
                $pr['url'] = $url;
                $sign = $this->tiktok_signature_generator($pr);

                $url = str_replace('{{sign}}', $sign, $url);
                $url = str_replace('{{timestamp}}', $timest, $url);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'x-tts-access-token: ' . $access_token
                    ),
                ));

                $response = curl_exec($curl);
                $response = json_decode($response, true);
                if ($response['message'] != 'Success') {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = $response['message'];
                    echo json_encode($html, true);
                    die;
                }

                $payment = $response['data'];

                if ($payment['settlement_amount'] > 0) {
                    $total_komisi_afiliasi = 0;
                    $total_omset_bersih = 0;
                    $total_platform_commission = 0;
                    $total_sfp_service_fee = 0;

                    foreach ($payment['sku_transactions'] as $sku) {
                        $fee_breakdown = $sku['fee_tax_breakdown']['fee'];
                        $total_komisi_afiliasi += abs(doubleval($fee_breakdown['affiliate_commission_amount']));
                        $total_omset_bersih += doubleval($sku['revenue_breakdown']['subtotal_before_discount_amount'])
                            + doubleval($sku['revenue_breakdown']['seller_discount_amount']);

                        $total_platform_commission += abs(doubleval($fee_breakdown['platform_commission_amount']));
                        $total_sfp_service_fee += abs(doubleval($fee_breakdown['sfp_service_fee_amount']));
                    }

                    $dt['komisi_afiliasi'] = $total_komisi_afiliasi;
                    $dt['omset_bersih'] = $total_omset_bersih;
                    $dt['marketplace_fee'] = $total_platform_commission + $total_sfp_service_fee;
                    $dt['dana_pencairan'] = doubleval($payment['settlement_amount']);
                    $dt['pencairan_status'] = '';
                    $dt['pencairan_at'] = '';

                    if ($payment['settlement_time']) {
                        $dt['pencairan_status'] = 'Settlement';
                        $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($payment['settlement_time']));
                    }
                }
            }


            $this->db->select('id');
            $customer = $this->mymodel->selectDataOne('customer', array('id_buyer' => $dt['id_buyer'], 'marketplace' => $marketplace));
            if (empty($customer)) {
                $dt['customer_text'] = strval($v2['recipient_address']['name']);
                $dt['phone'] = strval($v2['recipient_address']['phone_number']);
                $dt['address'] = strval($v2['recipient_address']['full_address']);
                $dt['address_2'] = strval($v2['recipient_address']['full_address']);
                $dt['postal_code'] = strval($v2['recipient_address']['postal_code']);
                $dt['province_text'] = strval($v2['recipient_address']['district_info'][1]['address_name']);
                $dt['city_text'] = strval($v2['recipient_address']['district_info'][2]['address_name']);
                $dt['subdistrict_text'] = strval($v2['recipient_address']['district_info'][3]['address_name']);
            }

            $dt['id_buyer'] = $v2['user_id'];
            // $id_buyer = $dt['id_buyer'];
            // $customer = $this->mymodel->selectDataOne("customer", array('id_buyer' => $id_buyer, 'marketplace' => $marketplace));
            // print_r($customer);
            // print_r($dt);
            // die;



            $dt['customer_price'] = doubleval($v2['payment']['total_amount']);
            $dt['omset_kotor'] = doubleval($v2['payment']['original_total_product_price']);
            $dt['omset_bersih'] = doubleval($v2['payment']['original_total_product_price'] - $v2['payment']['seller_discount']);
            $dt['diskon_penjual'] = doubleval($v2['payment']['seller_discount']);

            if ($dt['marketplace_fee'] == 0) {
                $channel = $this->mymodel->selectDataOne('marketplace', array('name' => $marketplace));
                $fee_json = json_decode($channel['configuration'], true);
                $fee = array();
                foreach ($fee_json as $kk => $vv) {
                    if (DATE("Y-m-d", strtotime($dt['date'])) >= $vv['date']) {
                        $fee = $vv;
                    } else {
                        break;
                    }
                }
                $marketplace_fee = 0;
                if ($fee['type'] == "Persentase") {
                    if ($fee['fee'] > 0) {
                        $marketplace_fee = doubleval($dt['omset_bersih']) * $fee['fee'] / 100;
                    }
                } else {
                    $marketplace_fee = $fee['fee'];
                }
                $dt['marketplace_fee'] = $marketplace_fee;
            }

            // print_r($dt);
            // print_r($v2);
            // echo ' --- ';
            // print_r($payment);

            // die;

            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['is_webhook'] = 1;

            if ($mode == "webhook") {
                $dtt = array();
                $dtt['order_date'] = $dt['date'];
                $this->db->update('webhook', $dtt, array('order_id' => $order_id));
            }
        } else if ($trx['marketplace'] == "SHOPEE") {
            $marketplace = $trx['marketplace'];
            $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
            $config = json_decode($config['val'], true);
            $app_key = $config['app_key'];
            $access_token = $config['access_token'];
            $shop_cipher = $config['shop']['cipher'];
            $partner_id = $this->partner_id_shopee;
            $partner_key = $this->partner_key_shopee;
            $host = 'https://partner.shopeemobile.com';

            $path = "/api/v2/order/get_order_detail";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn_list=' . $order_id . '&response_optional_fields=buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,no_plastic_packing,order_chargeable_weight_gram,edt,return_due_date&request_order_status_pending=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($response, true);
            if (empty($response['response']['order_list'][0])) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            $v2 = $response['response']['order_list'][0];

            $price_total_hpp = 0;

            $dt = array();
            $dt['type'] = "Out";
            $dt['type_sub'] = "POS";
            $dt['order_id'] = $order_id;
            $dt['shop_id'] = $shop_id;
            $dt['shop_name'] = $shop_name;
            $dt['marketplace'] = $marketplace;
            $dt['date'] = DATE("Y-m-d H:i:s", $v2['create_time']);
            $dt['shipping'] = strval($v2['shipping_carrier']);
            $js = array();
            foreach ($v2['item_list'] as $k4 => $v4) {
                $js[$k4]['id_product'] = $v4['model_id'];
                $js[$k4]['sku'] = $v4['model_sku'];
                $js[$k4]['name'] = $v4['model_name'];
                $js[$k4]['id_product_parent'] = $v4['item_id'];
                $js[$k4]['sku_parent'] = $v4['item_sku'];
                $js[$k4]['name_parent'] = $v4['item_name'];
                $js[$k4]['qty'] = intval($v4['model_quantity_purchased']);
                $js[$k4]['price'] = intval($v4['model_discounted_price']);
                $js[$k4]['original_price'] = intval($v4['model_original_price']);
                $js[$k4]['discount'] = intval($v4['model_original_price'] - $v4['model_discounted_price']);
            }




            $c_type['akun_type'] = "Pelanggan";
            $dt['c_type'] = "Pelanggan";

            $brand = array();
            $json = array();
            foreach ($js as $k4 => $v4) {
                $id_product = $v4['id_product'];
                $id_product_parent = $v4['id_product_parent'];
                $this->db->select('json');
                $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));

                if (empty($conf) && $v4['sku']) {
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                }

                $conf = json_decode($conf['json'], true);
                if (empty($conf)) {
                    $js[$k4]['is_empty'] = true;
                    $is_configurated = 0;
                }
                foreach ($conf as $k5 => $v5) {
                    $product = $arr_product[$v5['product']];
                    $price = 0;
                    if ($dt['c_type'] == "Pelanggan") {
                        $price = $product['price_normal'];
                    } else if ($dt['c_type'] == "Distributor") {
                        $price = $product['price_distributor'];
                    } else if ($dt['c_type'] == "Reseller") {
                        $price = $product['price_reseller'];
                    } else {
                        $price = $product['price_normal'];
                    }
                    $json[$product['id']]['sku'] = $product['sku'];
                    $json[$product['id']]['hpp'] = $product['price_buy'];
                    $json[$product['id']]['product'] = $product['id'];
                    $json[$product['id']]['product_text'] = $product['name'];
                    $json[$product['id']]['product_sub'] = $product['sub_name'];
                    $json[$product['id']]['brand'] = $product['brand'];
                    $json[$product['id']]['price'] = $price;
                    $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                    $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                    $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                    $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                }
            }


            $brand_selected = "MG";
            $arr_brand = array();
            foreach ($json as $k4 => $v4) {
                $arr_brand[$v4['brand']] += 1;
            }

            $max = 0;
            foreach ($arr_brand as $k => $v) {
                if ($v >= $max) {
                    $max = $v;
                    $brand_selected = $k;
                }
            }

            $dt['brand'] = $brand_selected;

            $dt['pesanan'] = json_encode($js, true);
            $dt['pesanan_count'] = count($js);

            $dt['hpp'] = doubleval($price_total_hpp);
            $dt['json'] = json_encode($json, true);

            if ($v2['cod'] == true) {
                $dt['payment_type'] = "COD";
            } else {
                $dt['payment_type'] = "TF";
            }

            $path = "/api/v2/logistics/get_tracking_info";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&order_sn=' . $order_id . '&access_token=' . $access_token . '&timestamp=' . $timest . '&sign=' . $sign . '&shop_id=' . $shop_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response_shipping = curl_exec($curl);

            $response_shipping = json_decode($response_shipping, true);

            curl_close($curl);

            $dt['rts_at'] = "";
            $dt['is_shipped'] = 0;
            foreach ($response_shipping['response']['tracking_info'] as $k3 => $v3) {
                if ($v3['logistics_status'] == "ORDER_CREATED") {
                    $dt['rts_at'] = DATE("Y-m-d H:i:s", $v3['update_time']);
                } else if ($v3['logistics_status'] == "PICKED_UP") {
                    $dt['is_shipped'] = 1;
                }
            }

            if ($v2['pay_time']) {
                $dt['payment_status'] = "Paid";
                $dt['pay_at'] = strval(DATE("Y-m-d H:i:s", $v2['pay_time']));
            } else {
                $dt['payment_status'] = "Unpaid";
            }

            $dt['order_status'] = $v2['order_status'];
            $dt['id_buyer'] = strval($v2['buyer_user_id']);


            $this->db->select('id');
            $customer = $this->mymodel->selectDataOne('customer', array('id_buyer' => $dt['id_buyer'], 'marketplace' => $marketplace));
            if (empty($customer)) {
                $dt['c_username'] = strval($v2['buyer_username']);
                $dt['customer_text'] = strval($v2['recipient_address']['name']);
                $dt['phone'] = strval($v2['recipient_address']['phone']);
                $dt['address'] = strval($v2['recipient_address']['full_address']);
                $dt['address_2'] = strval($v2['recipient_address']['full_address']);
                $dt['postal_code'] = strval($v2['recipient_address']['zipcode']);
                $dt['province_text'] = strval($v2['recipient_address']['state']);
                $dt['city_text'] = strval($v2['recipient_address']['city']);
                $dt['subdistrict_text'] = strval($v2['recipient_address']['district']);
            }



            $order_status = $v2['order_status'];
            if ($v2['order_status'] == "TO_CONFIRM_RECEIVE") {
                $order_status = "DELIVERED";
            } else if ($v2['order_status'] == "TO_RETURN") {
                $order_status = "RETURN";
            } else if ($v2['order_status'] == "RETRY_SHIP") {
                $order_status = "PROCESSED";
            }

            // if (in_array($v2['order_status'], array('CANCELLED')) && $v2['pickup_done_time']) {
            //     foreach ($response_shipping['response']['tracking_info'] as $k3 => $v3) {
            //         if (in_array($v3['logistics_status'], array('RETURNED', 'RETURN'))) {
            //             $dt['return_at'] = DATE("Y-m-d H:i:s", $v3['update_time']);
            //             break;
            //         }
            //     }
            //     $dt['order_status'] = "RETURN";
            // }


            $curl = curl_init();

            $path = "/api/v2/logistics/get_tracking_number";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&order_sn=' . $order_id . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response_awb = curl_exec($curl);
            $response_awb = json_decode($response_awb, true);
            curl_close($curl);

            $dt['awb_number'] = strval($response_awb['response']['tracking_number']);


            $path = "/api/v2/payment/get_escrow_detail";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn=' . $order_id . '&response_optional_fields=buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,no_plastic_packing,order_chargeable_weight_gram,edt,return_due_date&request_order_status_pending=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);
            // print_r($response);

            curl_close($curl);
            $response = json_decode($response, true);


            $omset_kotor = 0;
            foreach ($v2['item_list'] as $kk => $vv) {
                $omset_kotor += $vv['model_quantity_purchased'] * $vv['model_original_price'];
            }
            $dt['omset_kotor'] = doubleval($omset_kotor);
            $detail = $response['response']['order_income'];
            if ($detail['escrow_amount']) {
                $dt['komisi_afiliasi'] = doubleval($detail['order_ams_commission_fee']);
                $dt['omset_kotor'] = doubleval($dt['omset_kotor']);
                $dt['diskon_penjual'] = doubleval($detail['seller_discount']);
                $dt['omset_bersih'] = doubleval($dt['omset_kotor']) - doubleval($detail['seller_discount']);
                $dt['marketplace_fee'] = doubleval($detail['commission_fee']) + doubleval($detail['service_fee']);

                $dt['pencairan_status'] = '';
                $dt['pencairan_at'] = '';
                $dt['dana_pencairan'] = '';
                if (in_array($dt['order_status'], array('COMPLETED'))) {
                    $dt['dana_pencairan'] = doubleval($detail['escrow_amount']);
                    if ($dt['dana_pencairan']) {
                        $dt['pencairan_status'] = 'Settlement';
                        // $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                    }
                }
            }

            if (in_array($dt['order_status'], array("CANCELLED"))) {
                $dt['cancel_at'] = DATE("Y-m-d H:i:s", $v2['update_time']);
            }

            $dt['customer_price'] = $v2['total_amount'];
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['is_webhook'] = 1;

            if ($mode == "webhook") {
                $dtt = array();
                $dtt['order_date'] = $dt['date'];
                $this->db->update('webhook', $dtt, array('order_id' => $order_id));
            }
        } else if ($trx['marketplace'] == "LAZADA") {
            $marketplace = $trx['marketplace'];
            $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
            $config = json_decode($config['val'], true);
            $access_token = $config['access_token'];
            $shop_cipher = $config['shop']['cipher'];

            $app_key = $this->app_key_lazada;
            $app_secret = $this->app_secret_lazada;
            $url = 'https://api.lazada.co.id/rest';
            $page_size = 100;
            $cursor = '';


            $c = new LazopClient($url, $app_key, $app_secret);
            $request = new LazopRequest('/order/get', 'GET');
            $request->addApiParam('order_id', $order_id);

            $response = $c->execute($request, $access_token);
            $response = json_decode($response, true);

            if ($response['data']) {
                $v2 = $response['data'];

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/order/items/get', 'GET');
                $request->addApiParam('order_id', $order_id);
                $response_item = $c->execute($request, $access_token);
                $response_item = json_decode($response_item, true);

                $price_total_hpp = 0;

                $dt = array();
                $dt['type'] = "Out";
                $dt['type_sub'] = "POS";
                $dt['order_id'] = $order_id;
                $dt['shop_id'] = $shop_id;
                $dt['shop_name'] = $shop_name;
                $dt['marketplace'] = $marketplace;
                $dt['date'] = DATE("Y-m-d H:i:s", strtotime(strval(substr($v2['created_at'], 0, 19))));
                $dt['id_buyer'] = strval($response_item['data'][0]['buyer_id']);

                $this->db->select('id');
                $customer = $this->mymodel->selectDataOne('customer', array('id_buyer' => $dt['id_buyer'], 'marketplace' => $marketplace));
                if (empty($customer)) {
                    $dt['customer_text'] = strval($v2['customer_first_name']);
                    $dt['phone'] = strval($v2['address_shipping']['phone']);
                    $dt['address'] = strval($v2['address_shipping']['address1']);
                    $dt['address_2'] = strval($v2['address_shipping']['address1']);
                    $dt['postal_code'] = strval($v2['address_shipping']['post_code']);
                    $dt['province_text'] = strval($v2['address_shipping']['address3']);
                    $dt['city_text'] = strval($v2['address_shipping']['city']);
                    $dt['subdistrict_text'] = strval($v2['address_shipping']['address2']);
                }
                if ($v2['payment_method'] == "COD") {
                    $dt['payment_type'] = "COD";
                } else {
                    $dt['payment_type'] = "TF";
                }
                $dt['customer_price'] = $v2['price'] + $v2['shipping_fee'] - $v2['voucher_seller'] - $v2['voucher_platform'];
                $segments = explode(',', $response_item['data'][0]['shipment_provider']);
                $segments = explode(': ', $segments[0]);
                $shipment_provider = $segments[1];
                if (empty($shipment_provider)) {
                    $shipment_provider = $segments[0];
                }
                $dt['shipping'] = strval($shipment_provider);
                $dt['awb_number'] = strval($response_item['data'][0]['tracking_code']);

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/logistic/order/trace');
                $request->addApiParam('order_id', $order_id);
                $response_shipping = $c->execute($request, $access_token);
                $response_shipping = json_decode($response_shipping, true);

                $dt['rts_at'] = "";
                foreach ($response_shipping['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'] as $k3 => $v3) {
                    if ($v3['detail_type'] == "ready_to") {
                        $dt['rts_at'] = DATE("Y-m-d H:i:s", (substr($v3['event_time'], 0, 10)));
                    }
                }
                if ($response_item['data'][0]['payment_time']) {
                    $dt['payment_status'] = strval("Paid");
                    $dt['pay_at'] = DATE("Y-m-d H:i:s", (substr($response_item['data'][0]['payment_time'], 0, 10)));
                } else {
                    $dt['payment_status'] = strval("Unpaid");
                }
                $js = array();
                // print_r($response_item['data']);
                foreach ($response_item['data'] as $k4 => $v4) {
                    $js[$k4]['id_product'] = $v4['sku_id'];
                    $js[$k4]['sku'] = $v4['sku'];
                    $parts = explode(":", $v4['variation']);
                    $v4['variation'] = $parts[1];
                    if (empty($v4['variation'])) {
                        $v4['variation'] = $parts[0];
                    }
                    if (empty($v4['variation'])) {
                        $v4['variation'] = $v4['name'];
                    }
                    $js[$k4]['name'] = strval($v4['variation']);
                    $js[$k4]['id_product_parent'] = $v4['product_id'];
                    $js[$k4]['sku_parent'] = "";
                    $js[$k4]['name_parent'] = $v4['name'];
                    $js[$k4]['qty'] = '1';
                    $js[$k4]['price'] = intval($v4['item_price']);
                    $js[$k4]['original_price'] = intval($v4['item_price']);
                    $js[$k4]['discount'] = intval($v4['model_original_price'] - $v4['model_discounted_price']);
                    // print_r($v4);
                }


                $c_type['akun_type'] = "Pelanggan";

                $brand = array();
                $json = array();
                foreach ($js as $k4 => $v4) {
                    $id_product = $v4['id_product'];
                    $id_product_parent = $v4['id_product_parent'];
                    $this->db->select('json');
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));

                    if (empty($conf) && $v4['sku']) {
                        $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                    }

                    $conf = json_decode($conf['json'], true);
                    if (empty($conf)) {
                        $js[$k4]['is_empty'] = true;
                        $is_configurated = 0;
                    }
                    foreach ($conf as $k5 => $v5) {
                        $product = $arr_product[$v5['product']];
                        $price = 0;
                        if ($dt['c_type'] == "Pelanggan") {
                            $price = $product['price_normal'];
                        } else if ($dt['c_type'] == "Distributor") {
                            $price = $product['price_distributor'];
                        } else if ($dt['c_type'] == "Reseller") {
                            $price = $product['price_reseller'];
                        } else {
                            $price = $product['price_normal'];
                        }
                        $json[$product['id']]['sku'] = $product['sku'];
                        $json[$product['id']]['hpp'] = $product['price_buy'];
                        $json[$product['id']]['product'] = $product['id'];
                        $json[$product['id']]['product_text'] = $product['name'];
                        $json[$product['id']]['product_sub'] = $product['sub_name'];
                        $json[$product['id']]['brand'] = $product['brand'];
                        $json[$product['id']]['price'] = $price;
                        $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                        $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                        $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                        $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                    }
                }

                $brand_selected = "MG";
                $arr_brand = array();
                foreach ($json as $k4 => $v4) {
                    $arr_brand[$v4['brand']] += 1;
                }

                $max = 0;
                foreach ($arr_brand as $k => $v) {
                    if ($v >= $max) {
                        $max = $v;
                        $brand_selected = $k;
                    }
                }

                $dt['brand'] = $brand_selected;

                $dt['pesanan'] = json_encode($js, true);
                $dt['pesanan_count'] = count($js);

                $dt['hpp'] = doubleval($price_total_hpp);
                $dt['json'] = json_encode($json, true);
                $order_status = "COMPLETED";
                if (in_array($v2['statuses'][0], array('unpaid'))) {
                    $order_status = 'UNPAID';
                } else if (in_array($v2['statuses'][0], array('topack', 'pending'))) {
                    $order_status = 'PROCESSED';
                } else if (in_array($v2['statuses'][0], array('returned', 'shipped_back_success'))) {
                    $order_status = 'RETURN';
                } else if (in_array($v2['statuses'][0], array('canceled', 'failed', 'lost'))) {
                    $order_status = 'CANCELLED';
                } else if (in_array($v2['statuses'][0], array('confirmed'))) {
                    $order_status = 'COMPLETED';
                    $dt['disbursement_at'] = '';
                    $dt['is_disbursement'] = '1';
                } else if (in_array($v2['statuses'][0], array('delivered'))) {
                    $order_status = 'DELIVERED';
                } else if (in_array($v2['statuses'][0], array('shipped'))) {
                    $order_status = 'SHIPPED';
                    $dt['is_shipped'] = 1;
                } else if (in_array($v2['statuses'][0], array('ready_to_ship', 'toship', 'shipping'))) {
                    $order_status = 'READY_TO_SHIP';
                }

                $dt['order_status'] = $order_status;


                // foreach ($response_shipping['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'] as $k3 => $v3) {
                //     if ($v3['status_code'] == '1420') {
                //         $dt['order_status']  = "RETURN";
                //         $dt['return_at'] = DATE("Y-m-d H:i:s", substr($v2['event_time'], 0, 10));
                //     }
                // }


                // if (in_array($dt['order_status'], array('COMPLETED'))) {
                $start_date = date("Y-m-01", strtotime($dt['date']));
                $until_date = date("Y-m-t", strtotime($start_date . " +1 months"));
                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/finance/transaction/details/get', 'GET');
                $request->addApiParam('offset', '0');
                $request->addApiParam('trade_order_id', $order_id);
                $request->addApiParam('limit', '100');
                $request->addApiParam('start_time', $start_date);
                $request->addApiParam('end_time', $until_date);
                $response_vat = $c->execute($request, $config['access_token']);
                // print_r($response_vat);

                $response_vat = json_decode($response_vat, true);
                $price_admin = 0;
                $price_total = 0;
                $diskon_penjual = 0;
                foreach ($response_vat['data'] as $kk => $vv) {
                    if (in_array($vv['fee_type'], array('118', '306'))) {
                        $diskon_penjual += doubleval(str_replace('-', '', str_replace(',', '', $vv['amount'])));
                    }
                    if (in_array($vv['transaction_type'], array('Orders-Sales'))) {
                        $price_total += doubleval(str_replace('-', '', str_replace(',', '', $vv['amount'])));
                    }
                    if (in_array($vv['fee_type'], array('298', '16', '3'))) {
                        $price_admin += doubleval(str_replace('-', '', str_replace(',', '', $vv['amount'])));
                    }
                    if ($vv['transaction_date']) {
                        $dt['pencairan_at'] = DATE("Y-m-d 00:00:01", strtotime($vv['transaction_date']));
                    }
                }
                // print_r($v2);
                if (empty($price_total)) {
                    $price_total = $v2['price'];
                    $dt['customer_price'] = $v2['price'];
                }
                if (doubleval($price_total - $price_admin - $diskon_penjual) > 0) {
                    $dt['komisi_afiliasi'] = doubleval(0);
                    $dt['omset_kotor'] = doubleval($price_total);
                    $dt['diskon_penjual'] = doubleval($diskon_penjual);
                    $dt['omset_bersih'] = doubleval($price_total) - doubleval($diskon_penjual);
                    $dt['marketplace_fee'] = doubleval($price_admin);
                    $dt['dana_pencairan'] = doubleval($price_total - $price_admin - $diskon_penjual);
                    $dt['pencairan_status'] = '';
                    // $dt['pencairan_at'] = '';
                    if ($dt['dana_pencairan']) {
                        $dt['pencairan_status'] = 'Settlement';
                        // $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                    }
                }
                // }

                if ($dt['marketplace_fee'] == 0) {
                    $channel = $this->mymodel->selectDataOne('marketplace', array('name' => $marketplace));
                    $fee_json = json_decode($channel['configuration'], true);
                    $fee = array();
                    foreach ($fee_json as $kk => $vv) {
                        if (DATE("Y-m-d", strtotime($dt['date'])) >= $vv['date']) {
                            $fee = $vv;
                        } else {
                            break;
                        }
                    }
                    $marketplace_fee = 0;
                    if ($fee['type'] == "Persentase") {
                        if ($fee['fee'] > 0) {
                            $marketplace_fee = doubleval($dt['customer_price']) * $fee['fee'] / 100;
                        }
                    } else {
                        $marketplace_fee = $fee['fee'];
                    }
                    $dt['marketplace_fee'] = $marketplace_fee;
                }

                // print_r($dt);
                // print_r($v2);
                // print_r($response_vat['data']);

                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['is_webhook'] = 1;
                if ($mode == "webhook") {
                    $dtt = array();
                    $dtt['order_date'] = $dt['date'];
                    $this->db->update('webhook', $dtt, array('order_id' => $order_id));
                }
            } else {
                $is_error = true;
            }
        }
        if ($v2) {
            if ($trx_existing) {
                $dt['is_configurated'] = $is_configurated;
                // if ($dt['order_status'] == 'CANCELLED' && $dt['is_shipped'] == 0) {
                //     $this->db->delete('transaction', array('id' => $trx_existing['id']));
                // }
                $this->db->update('transaction', $dt, array('id' => $trx_existing['id']));
                $dt['id'] = $trx_existing['id'];
                $dt['stock'] = $json;
                $dt['stock_product_3rd'] = $js;
                $this->calculate_stock($dt);
                $this->calculate_buyer($dt);
                $html['status'] = true;
                $html['data'] = array();
                $html['msg'] = 'Data ' . $order_id . ' berhasil diperbarui!';
                echo json_encode($html, true);
                die;
            } else {
                $dt['is_configurated'] = $is_configurated;
                $this->db->insert('transaction', $dt);
                $dt['id'] = $this->db->insert_id();
                $dt['stock'] = $json;
                $dt['stock_product_3rd'] = $js;
                $this->calculate_stock($dt);
                $this->calculate_buyer($dt);
                $html['status'] = true;
                $html['data'] = array();
                $html['msg'] = 'Data ' . $order_id . ' berhasil ditambahkan!';
                echo json_encode($html, true);
                die;
            }
        } else if (empty($trx)) {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Data tidak ditemukan!';
            echo json_encode($html, true);
            die;
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Koneksi marketplace bermasalah!';
            echo json_encode($html, true);
            die;
        }
    }

    function marketplace_order_tracking()
    {
        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $order_id = $dt['order_id'];
        $mode = $dt['mode'];

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }

        if ($marketplace) {
            $this->db->where('marketplace', $marketplace);
        }
        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'is_manual' => '0'));
        $is_error = '';
        $msg = '';
        if ($trx) {
            if ($trx['marketplace'] == "TIKTOK") {
                $marketplace = $trx['marketplace'];
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
                $config = json_decode($config['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $trx['shop_id'];
                $shop_name = $trx['shop_name'];
                $app_secret = $this->app_secret_tiktok;

                $url = 'https://open-api.tiktokglobalshop.com/fulfillment/202309/orders/' . $order_id . '/tracking?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                $urlParts = parse_url($url);
                $paramGET = [];
                parse_str($urlParts['query'], $paramGET);
                $timest = strtotime('now');
                $pr = array();
                $pr['secret'] = $app_secret;
                $pr['timest'] = $timest;
                $pr['get'] = $paramGET;
                $pr['url'] = $url;
                $sign = $this->tiktok_signature_generator($pr);

                $url = str_replace('{{sign}}', $sign, $url);
                $url = str_replace('{{timestamp}}', $timest, $url);

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'x-tts-access-token: ' . $access_token
                    ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                $response = json_decode($response, true);
                if (empty($response['data']['tracking'])) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = 'Data belum tersedia!';
                    echo json_encode($html, true);
                    die;
                }
                $arr = array();
                foreach ($response['data']['tracking'] as $k2 => $v2) {
                    $arr[$k2]['title'] = $v2['title'];
                    $arr[$k2]['description'] = $v2['description'];
                    $arr[$k2]['datetime'] = DATE("Y-m-d H:i:s", substr($v2['update_time_millis'], 0, 10));
                }
            } else if ($trx['marketplace'] == "SHOPEE") {
                $marketplace = $trx['marketplace'];
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
                $config = json_decode($config['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $trx['shop_id'];
                $shop_name = $trx['shop_name'];
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $host = 'https://partner.shopeemobile.com';
                $path = "/api/v2/logistics/get_tracking_info";
                $timest = time();
                $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                $sign = hash_hmac('sha256', $baseString, $partner_key);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&order_sn=' . $order_id . '&access_token=' . $access_token . '&timestamp=' . $timest . '&sign=' . $sign . '&shop_id=' . $shop_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                $response = json_decode($response, true);
                if (empty($response['response']['tracking_info'])) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = 'Data belum tersedia!';
                    echo json_encode($html, true);
                    die;
                }
                $arr = array();
                foreach ($response['response']['tracking_info'] as $k2 => $v2) {
                    $arr[$k2]['title'] = $v2['logistics_status'];
                    $arr[$k2]['description'] = $v2['description'];
                    $arr[$k2]['datetime'] = DATE("Y-m-d H:i:s", $v2['update_time']);
                }
            } else if ($trx['marketplace'] == "LAZADA") {
                $marketplace = $trx['marketplace'];
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
                $config = json_decode($config['val'], true);
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $trx['shop_id'];
                $shop_name = $trx['shop_name'];
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $url = 'https://api.lazada.co.id/rest';
                $page_size = 100;
                $cursor = '';


                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/logistic/order/trace');
                $request->addApiParam('order_id', $order_id);
                $response = $c->execute($request, $access_token);
                $response = json_decode($response, true);
                if (empty($response['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'])) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = 'Data belum tersedia!';
                    echo json_encode($html, true);
                    die;
                }
                $arr = array();
                foreach ($response['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'] as $k2 => $v2) {
                    $arr[$k2]['title'] = $v2['title'];
                    $arr[$k2]['description'] = $v2['description'];
                    $arr[$k2]['datetime'] = DATE("Y-m-d H:i:s", substr($v2['event_time'], 0, 10));
                }
            }
            if (empty($arr)) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = 'Data belum tersedia!';
                echo json_encode($html, true);
                die;
            } else {
                $html['status'] = true;
                $html['data'] = $arr;
                $html['msg'] = 'Data ' . $order_id . ' ditemukan!';
                echo json_encode($html, true);
                die;
            }
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Data tidak ditemukan!';
            echo json_encode($html, true);
            die;
        }
    }

    function marketplace_webhook_reset()
    {
        $this->db->delete('webhook', " order_id = '' ");
        $this->db->delete('webhook', " order_date != '' ");

        // $this->db->delete('webhook', " order_date != '' AND order_id != '' ");

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Reset webhook success!';
        echo json_encode($html, true);
        die;
    }

    function marketplace_webhook_refresh()
    {
        $mode = "";
        $data = array();
        $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,order_id,shop_id
            FROM transaction
            WHERE is_webhook = 0 AND is_manual = 0
            ORDER BY updated_at DESC
            LIMIT 30
            ");
        if (empty($data)) {
            $data = $this->mymodel->selectWithQuery("SELECT MIN(id) as id, marketplace, order_id, shop_id
            FROM webhook
            WHERE order_id != '' AND order_date = ''
            GROUP BY order_id, marketplace, shop_id
            ORDER BY MIN(id) DESC
            LIMIT 30
            ");
            $mode = "webhook";
        }

        foreach ($data as $k => $v) {

            if ($mode == "webhook") {
                $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '&mode=webhook';
            } else {
                $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '';
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 1,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $data;
        $html['mode'] = $mode;
        $html['msg'] = 'Refresh order data success!';
        echo json_encode($html, true);
        die;
    }

    function marketplace_webhook_update()
    {
        $mode = "";
        $data = array();
        $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,order_id,shop_id FROM transaction WHERE DATE(date) >= '2025-04-01' AND DATE(date) <= '2025-06-10' AND order_status = 'COMPLETED' AND dana_pencairan = 0 AND order_status IN ('SETTLEMENT','COMPLETED') AND customer_price > 0 AND type_sub = 'POS' AND DATE(updated_at) != CURDATE()
            ");

        print_r($data);
        die;

        // foreach ($data as $k => $v) {

        //     if ($mode == "webhook") {
        //         $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '&mode=webhook';
        //     } else {
        //         $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '';
        //     }
        //     $curl = curl_init();
        //     curl_setopt_array($curl, array(
        //         CURLOPT_URL => $url,
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_ENCODING => '',
        //         CURLOPT_MAXREDIRS => 10,
        //         CURLOPT_TIMEOUT => 1,
        //         CURLOPT_FOLLOWLOCATION => true,
        //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST => 'GET',
        //         CURLOPT_HTTPHEADER => array(
        //             'Content-Type: application/json'
        //         ),
        //     ));
        //     $response = curl_exec($curl);
        //     curl_close($curl);
        // }

        // header('Content-Type: application/json; charset=utf-8');
        // $html = array();
        // $html['status'] = true;
        // $html['data'] = $data;
        // $html['mode'] = $mode;
        // $html['msg'] = 'Refresh order data success!';
        // echo json_encode($html, true);
        // die;
    }

    function marketplace_config()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();

        $dt = $_GET;
        $marketplace = strtoupper($dt['marketplace'] ?? '');
        $shop_id = $dt['shop_id'] ?? '';

        $qry = "status = 'Aktif'";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }

        $data = $this->mymodel->selectWithQuery("SELECT shop_id, shop_name, opt, val
        FROM marketplace_config
        WHERE $qry");

        $result = array();
        foreach ($data as $row) {
            $config = json_decode($row['val'] ?? '', true);
            if (empty($config)) {
                continue;
            }
            $result[] = array(
                'shop_id' => strval($row['shop_id']),
                'shop_name' => strval($row['shop_name']),
                'marketplace' => strval($row['opt']),
                'app_key' => $config['app_key'] ?? '',
                'access_token' => $config['access_token'] ?? '',
                'shop_cipher' => $config['shop']['cipher'] ?? '',
            );
        }

        echo json_encode(array(
            'status' => true,
            'data' => $result,
            'msg' => 'OK',
        ), true);
        die;
    }

    function marketplace_order_ingest()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            echo json_encode(array('status' => false, 'msg' => 'Payload tidak valid'), true);
            die;
        }

        $marketplace = strtoupper($payload['marketplace'] ?? '');
        if ($marketplace !== 'TIKTOK') {
            echo json_encode(array('status' => false, 'msg' => 'Marketplace tidak didukung'), true);
            die;
        }

        $orders = $payload['orders'] ?? array();
        if (!is_array($orders) || empty($orders)) {
            echo json_encode(array('status' => true, 'msg' => 'Tidak ada order untuk diproses'), true);
            die;
        }

        $shop_id = $payload['shop_id'] ?? '';
        $shop_name = $payload['shop_name'] ?? '';
        $start_date = $payload['start_date'] ?? DATE("Y-m-01");
        $start_date = DATE("Y-m-d", strtotime($start_date)) . ' 00:00:00';

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }

        foreach ($orders as $k2 => $v2) {
            $order_id = $v2['order_id'] ?? ($v2['id'] ?? '');
            if ($order_id === '') {
                continue;
            }
            $this->db->select('id');
            $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'marketplace' => $marketplace));

            $dt = array();
            $dt['type'] = "Out";
            $dt['type_sub'] = "POS";
            $dt['marketplace'] = strval($marketplace);
            $dt['shop_id'] = strval($shop_id);
            $dt['shop_name'] = strval($shop_name);
            $dt['marketplace'] = $marketplace;
            $dt['order_id'] = $order_id;
            $dt['is_manual'] = 0;

            $create_time = isset($v2['create_time']) ? intval($v2['create_time']) : 0;
            if ($create_time > 0) {
                $dt['date'] = DATE("Y-m-d H:i:s", $create_time);
            }

            if (isset($v2['is_sample_order'])) {
                $dt['c_type'] = $v2['is_sample_order'] ? "Affiliate" : "Pelanggan";
            }

            if (!empty($v2['shipping_provider'])) {
                $dt['shipping'] = strval($v2['shipping_provider']);
            } else if (!empty($v2['delivery_option_name'])) {
                $dt['shipping'] = strval($v2['delivery_option_name']);
            }
            if (!empty($v2['tracking_number'])) {
                $dt['awb_number'] = strval($v2['tracking_number']);
            }

            if (isset($v2['is_cod'])) {
                $dt['payment_type'] = $v2['is_cod'] ? "COD" : "TF";
            }
            if (!empty($v2['paid_time'])) {
                $dt['payment_status'] = "Paid";
                $dt['pay_at'] = DATE("Y-m-d H:i:s", intval($v2['paid_time']));
            } else if (isset($v2['paid_time'])) {
                $dt['payment_status'] = "Unpaid";
            }
            if (!empty($v2['rts_time'])) {
                $dt['rts_at'] = DATE("Y-m-d H:i:s", intval($v2['rts_time']));
            } else if (!empty($v2['rts_sla_time'])) {
                $dt['rts_at'] = DATE("Y-m-d H:i:s", intval($v2['rts_sla_time']));
            }
            if (!empty($v2['cancel_time'])) {
                $dt['return_at'] = DATE("Y-m-d H:i:s", intval($v2['cancel_time']));
            }

            $buyer_id = $v2['buyer_user_id'] ?? ($v2['user_id'] ?? '');
            if ($buyer_id !== '') {
                $dt['id_buyer'] = strval($buyer_id);
            }
            $buyer_username = $v2['buyer_nickname'] ?? ($v2['buyer_email'] ?? '');
            if ($buyer_username !== '') {
                $dt['c_username'] = strval($buyer_username);
            }

            if (!empty($v2['recipient_address']) && is_array($v2['recipient_address'])) {
                $recipient = $v2['recipient_address'];
                if (!empty($recipient['name'])) {
                    $dt['customer_text'] = strval($recipient['name']);
                }
                if (!empty($recipient['phone_number'])) {
                    $dt['phone'] = strval($recipient['phone_number']);
                }
                if (!empty($recipient['full_address'])) {
                    $dt['address'] = strval($recipient['full_address']);
                    $dt['address_2'] = strval($recipient['full_address']);
                }
                if (!empty($recipient['postal_code'])) {
                    $dt['postal_code'] = strval($recipient['postal_code']);
                }
                if (!empty($recipient['district_info']) && is_array($recipient['district_info'])) {
                    $dt['province_text'] = strval($recipient['district_info'][1]['address_name'] ?? '');
                    $dt['city_text'] = strval($recipient['district_info'][2]['address_name'] ?? '');
                    $dt['subdistrict_text'] = strval($recipient['district_info'][3]['address_name'] ?? '');
                }
            }

            if (!empty($v2['payment']) && is_array($v2['payment'])) {
                $payment = $v2['payment'];
                if (isset($payment['total_amount'])) {
                    $dt['customer_price'] = doubleval($payment['total_amount']);
                }
                if (isset($payment['original_total_product_price'])) {
                    $dt['omset_kotor'] = doubleval($payment['original_total_product_price']);
                }
                if (isset($payment['seller_discount']) && isset($payment['original_total_product_price'])) {
                    $dt['diskon_penjual'] = doubleval($payment['seller_discount']);
                    $dt['omset_bersih'] = doubleval($payment['original_total_product_price'] - $payment['seller_discount']);
                }
            }

            if (!empty($v2['line_items']) && is_array($v2['line_items'])) {
                $js = array();
                foreach ($v2['line_items'] as $k4 => $v4) {
                    $js[$k4]['id_product'] = $v4['sku_id'] ?? '';
                    $js[$k4]['sku'] = $v4['seller_sku'] ?? '';
                    $name = $v4['sku_name'] ?? '';
                    if ($name === "Default") {
                        $name = "";
                    }
                    $js[$k4]['name'] = $name;
                    $js[$k4]['id_product_parent'] = $v4['product_id'] ?? '';
                    $js[$k4]['sku_parent'] = "";
                    $js[$k4]['name_parent'] = $v4['product_name'] ?? '';
                    $js[$k4]['qty'] = isset($v4['quantity']) ? strval($v4['quantity']) : '1';
                    $js[$k4]['price'] = $v4['sale_price'] ?? '';
                    $js[$k4]['original_price'] = $v4['original_price'] ?? '';
                    $js[$k4]['discount'] = $v4['seller_discount'] ?? '';
                }

                $price_total_hpp = 0;
                $json = array();
                $brand_selected = "MG";
                $arr_brand = array();
                foreach ($js as $k4 => $v4) {
                    $id_product = $v4['id_product'];
                    $id_product_parent = $v4['id_product_parent'];
                    $this->db->select('json');
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));
                    if (empty($conf) && $v4['sku']) {
                        $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                    }
                    $conf = json_decode($conf['json'] ?? '', true);
                    if (empty($conf)) {
                        $js[$k4]['is_empty'] = true;
                        continue;
                    }
                    foreach ($conf as $k5 => $v5) {
                        if (empty($arr_product[$v5['product']])) {
                            continue;
                        }
                        $product = $arr_product[$v5['product']];
                        $arr_brand[$product['brand']] += 1;
                        $price = 0;
                        if (($dt['c_type'] ?? '') == "Pelanggan") {
                            $price = $product['price_normal'];
                        } else if (($dt['c_type'] ?? '') == "Distributor") {
                            $price = $product['price_distributor'];
                        } else if (($dt['c_type'] ?? '') == "Reseller") {
                            $price = $product['price_reseller'];
                        } else {
                            $price = $product['price_normal'];
                        }
                        $json[$product['id']]['sku'] = $product['sku'];
                        $json[$product['id']]['hpp'] = $product['price_buy'];
                        $json[$product['id']]['product'] = $product['id'];
                        $json[$product['id']]['product_text'] = $product['name'];
                        $json[$product['id']]['product_sub'] = $product['sub_name'];
                        $json[$product['id']]['brand'] = $product['brand'];
                        $json[$product['id']]['price'] = $price;
                        $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                        $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                        $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                        $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                    }
                }

                if (!empty($arr_brand)) {
                    $max = 0;
                    foreach ($arr_brand as $k5 => $v5) {
                        if ($v5 >= $max) {
                            $max = $v5;
                            $brand_selected = $k5;
                        }
                    }
                    $dt['brand'] = $brand_selected;
                }

                $dt['pesanan'] = json_encode($js, true);
                $dt['pesanan_count'] = count($js);
                $dt['json'] = json_encode($json, true);
                $dt['hpp'] = doubleval($price_total_hpp);
            }

            $status_raw = $v2['order_status'] ?? ($v2['status'] ?? '');
            $status_upper = strtoupper(strval($status_raw));
            if (in_array($status_upper, array('UNPAID'))) {
                $order_status = 'UNPAID';
            } else if (in_array($status_upper, array('AWAITING_COLLECTION', 'ON_HOLD'))) {
                $order_status = 'PROCESSED';
            } else if (in_array($status_upper, array('RETURNED'))) {
                $order_status = 'RETURN';
            } else if (in_array($status_upper, array('CANCELLED'))) {
                $order_status = 'CANCELLED';
            } else if (in_array($status_upper, array('COMPLETED'))) {
                $order_status = 'COMPLETED';
            } else if (in_array($status_upper, array('DELIVERED'))) {
                $order_status = 'DELIVERED';
            } else if (in_array($status_upper, array('IN_TRANSIT', 'PARTIALLY_SHIPPING'))) {
                $order_status = 'SHIPPED';
                $dt['is_shipped'] = 1;
            } else if (in_array($status_upper, array('AWAITING_SHIPMENT'))) {
                $order_status = 'READY_TO_SHIP';
            } else if (in_array($status_raw, array('100'))) {
                $order_status = 'UNPAID';
            } else if (in_array($status_raw, array('112', '105'))) {
                $order_status = 'PROCESSED';
            } else if (in_array($status_raw, array('returned'))) {
                $order_status = 'RETURN';
            } else if (in_array($status_raw, array('140'))) {
                $order_status = 'CANCELLED';
            } else if (in_array($status_raw, array('130',))) {
                $order_status = 'COMPLETED';
            } else if (in_array($status_raw, array('122'))) {
                $order_status = 'DELIVERED';
            } else if (in_array($status_raw, array('121', '114'))) {
                $order_status = 'SHIPPED';
                $dt['is_shipped'] = 1;
            } else if (in_array($status_raw, array('111'))) {
                $order_status = 'READY_TO_SHIP';
            }
            $dt['order_status'] = strval($order_status);
            if ($trx) {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $this->db->update('transaction', $dt, array('id' => $trx['id']));
            } else if ($dt['order_status'] !== 'CANCELLED') {
                if (empty($dt['date'])) {
                    $dt['date'] = DATE("Y-m-d 23:00:00", strtotime($start_date));
                }
                $dt['created_at'] = DATE("Y-m-d H:i:s");
                $this->db->insert('transaction', $dt);
            }
        }

        echo json_encode(array(
            'status' => true,
            'msg' => 'Sync data order berhasil!',
        ), true);
        die;
    }

    function marketplace_product_ingest()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            echo json_encode(array('status' => false, 'msg' => 'Payload tidak valid'), true);
            die;
        }

        $marketplace = strtoupper($payload['marketplace'] ?? '');
        if ($marketplace !== 'TIKTOK') {
            echo json_encode(array('status' => false, 'msg' => 'Marketplace tidak didukung'), true);
            die;
        }

        $products = $payload['products'] ?? array();
        if (!is_array($products) || empty($products)) {
            echo json_encode(array('status' => true, 'msg' => 'Tidak ada produk untuk diproses'), true);
            die;
        }

        $shop_id = $payload['shop_id'] ?? '';
        $shop_name = $payload['shop_name'] ?? '';
        $created_by = $_SESSION['user']['id'] ?? '0';

        foreach ($products as $v2) {
            $id_product = $v2['id'] ?? '';
            if ($id_product === '') {
                continue;
            }
            $this->db->select('id');
            $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

            $dt = array();
            $dt['marketplace'] = $marketplace;
            $dt['id_product'] = $id_product;
            $dt['name'] = strval($v2['title'] ?? '');
            $dt['desc'] = strval($v2['description'] ?? '');
            $dt['sku'] = strval($v2['sku'] ?? '');
            $dt['shop_name'] = $shop_name;
            $dt['shop_id'] = $shop_id;

            $img_url = $v2['main_images'][0]['thumb_urls'][0] ?? '';
            if ($img_url) {
                $file_name = $id_product . '.jpg';
                $img_dir = './assets/img/product_3rd/' . $file_name;
                file_put_contents($img_dir, file_get_contents($img_url));
                $dt['img'] = $file_name;
            }

            if ($product) {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $this->db->update('product_3rd', $dt, array('id' => $product['id']));
            } else {
                $dt['created_by'] = strval($created_by);
                $dt['created_at'] = DATE("Y-m-d H:i:s");
                $this->db->insert('product_3rd', $dt);
                $product['id'] = $this->db->insert_id();
            }

            $item = $v2['skus'] ?? array();
            $item_list = array();
            if (empty($item)) {
                $varian['sku'] = '';
                $varian['name'] = '';
                $varian['id_product'] = '0';
                $varian['sku_parent'] = $dt['sku'];
                $varian['parent_name'] = $dt['name'];
                $varian['id_product_parent'] = $dt['id_product'];
                $varian['id_parent'] = $product['id'];
                $varian['img'] = $dt['img'] ?? '';
                $item_list[] = $varian;
            } else {
                foreach ($item as $k3 => $v3) {
                    $varian['sku'] = $v3['seller_sku'] ?? '';
                    $varian['name'] = strval($v3['sales_attributes'][0]['value_name'] ?? '');
                    $varian['id_product'] = $v3['id'] ?? '';
                    $varian['sku_parent'] = $dt['sku'];
                    $varian['parent_name'] = $dt['name'];
                    $varian['id_product_parent'] = $dt['id_product'];
                    $varian['id_parent'] = $product['id'];
                    $img_url = $v3['sales_attributes'][0]['sku_img']['thumb_urls'][0] ?? '';
                    if ($img_url) {
                        $file_name = $varian['id_product'] . '.jpg';
                        $img_dir = './assets/img/product_3rd/' . $file_name;
                        file_put_contents($img_dir, file_get_contents($img_url));
                        $varian['img'] = $file_name;
                    }
                    $item_list[] = $varian;
                }
            }

            $dt['json_varian'] = json_encode($item_list, true);
            $dt['count_varian'] = count($item_list);
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $this->db->update('product_3rd', $dt, array('id' => $product['id']));

            foreach ($item_list as $k4 => $v4) {
                $id_product = $v4['id_product'];
                $id_product_parent = $v4['id_product_parent'];
                $this->db->select('id');
                $product_variant = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                $dtt = array();
                foreach ($v4 as $k5 => $v5) {
                    $dtt[$k5] = strval($v5);
                }
                $dtt['marketplace'] = $marketplace;
                $dtt['shop_name'] = $shop_name;
                $dtt['shop_id'] = $shop_id;

                if ($dtt['sku']) {
                    $dat = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $dtt['sku']));
                    if ($dat) {
                        $dtt['json'] = strval($dat['json']);
                    }
                }

                if ($product_variant) {
                    $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                    $this->db->update('product_variant_3rd', $dtt, array('id' => $product_variant['id']));
                } else {
                    $dtt['created_by'] = strval($created_by);
                    $dtt['created_at'] = DATE("Y-m-d H:i:s");
                    $this->db->insert('product_variant_3rd', $dtt);
                }
            }
        }

        echo json_encode(array(
            'status' => true,
            'msg' => 'Sync data produk berhasil!',
        ), true);
        die;
    }

    function marketplace_order()
    {


        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;

        $marketplace = $dt['marketplace'];
        $marketplace = strtoupper($marketplace);
        $shop_id = $dt['shop_id'];
        $qry = "";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }

        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");

        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }



        $start_date = $_GET['start_date'] ?? '';
        $until_date = $_GET['until_date'] ?? '';
        if (empty($start_date)) {
            $start_date = DATE("Y-m-01");
        }
        if (empty($until_date)) {
            $until_date = DATE("Y-m-d");
        }
        $start_date .= ' 00:00:00';
        $until_date .= ' 00:00:00';

        $start_time = strtotime($start_date);
        $until_time = $until_date . '';
        $until_time = DATE('Y-m-d 00:00:00', strtotime($until_time . " +1 days"));
        $until_time = strtotime($until_time);

        foreach ($data as $k => $v) {
            if ($v['opt'] == "TIKTOK") {
                $marketplace = "TIKTOK";
                $config = json_decode($v['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_secret = $this->app_secret_tiktok;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];

                $page_size = 100;
                $page_token = "";

                for ($i = 0; $i <= 100; $i++) {

                    $endpoint_path = '/order/202309/orders/search';
                    $timest = time();

                    $queryParams = array(
                        'app_key' => $app_key,
                        'shop_cipher' => $shop_cipher,
                        'sort_field' => 'create_time',
                        'sort_order' => 'ASC',
                        'timestamp' => $timest,
                        'page_size' => $page_size,
                    );
                    if ($page_token !== '') {
                        $queryParams['page_token'] = $page_token;
                    }

                    $now_time = time();
                    $body_payload = array(
                        'create_time_ge' => $start_time,
                        'create_time_lt' => $until_time ?: $now_time,
                        'update_time_ge' => $start_time,
                        'update_time_lt' => $until_time ?: $now_time,
                    );
                    $body_json = json_encode($body_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    $pr = array(
                        'secret' => $app_secret,
                        'timest' => $timest,
                        'get' => $queryParams,
                        'post' => $body_json,
                        'url' => 'https://open-api.tiktokglobalshop.com' . $endpoint_path,
                    );
                    $sign = $this->tiktok_signature_generator($pr);

                    $queryParams['sign'] = $sign;
                    $url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path . '?' . http_build_query($queryParams);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $body_json,
                        CURLOPT_HTTPHEADER => array(
                            'content-type: application/json',
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));

                    $response_raw = curl_exec($curl);
                    $response = json_decode($response_raw, true);

                    $orders = array();
                    if (isset($response['data']['orders']) && is_array($response['data']['orders'])) {
                        $orders = $response['data']['orders'];
                    } else if (isset($response['data']['order_list']) && is_array($response['data']['order_list'])) {
                        $orders = $response['data']['order_list'];
                    }

                    if (empty($orders)) {
                        break;
                    }

                    foreach ($orders as $k2 => $v2) {
                        $order_id = $v2['order_id'] ?? ($v2['id'] ?? '');
                        if ($order_id === '') {
                            continue;
                        }
                        $this->db->select('id');
                        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['marketplace'] = strval($marketplace);
                        $dt['shop_id'] = strval($shop_id);
                        $dt['shop_name'] = strval($shop_name);
                        $dt['marketplace'] = $marketplace;
                        $dt['order_id'] = $order_id;
                        $dt['is_manual'] = 0;

                        $create_time = isset($v2['create_time']) ? intval($v2['create_time']) : 0;
                        if ($create_time > 0) {
                            $dt['date'] = DATE("Y-m-d H:i:s", $create_time);
                        }

                        if (isset($v2['is_sample_order'])) {
                            $dt['c_type'] = $v2['is_sample_order'] ? "Affiliate" : "Pelanggan";
                        }

                        if (!empty($v2['shipping_provider'])) {
                            $dt['shipping'] = strval($v2['shipping_provider']);
                        } else if (!empty($v2['delivery_option_name'])) {
                            $dt['shipping'] = strval($v2['delivery_option_name']);
                        }
                        if (!empty($v2['tracking_number'])) {
                            $dt['awb_number'] = strval($v2['tracking_number']);
                        }

                        if (isset($v2['is_cod'])) {
                            $dt['payment_type'] = $v2['is_cod'] ? "COD" : "TF";
                        }
                        if (!empty($v2['paid_time'])) {
                            $dt['payment_status'] = "Paid";
                            $dt['pay_at'] = DATE("Y-m-d H:i:s", intval($v2['paid_time']));
                        } else if (isset($v2['paid_time'])) {
                            $dt['payment_status'] = "Unpaid";
                        }
                        if (!empty($v2['rts_time'])) {
                            $dt['rts_at'] = DATE("Y-m-d H:i:s", intval($v2['rts_time']));
                        } else if (!empty($v2['rts_sla_time'])) {
                            $dt['rts_at'] = DATE("Y-m-d H:i:s", intval($v2['rts_sla_time']));
                        }
                        if (!empty($v2['cancel_time'])) {
                            $dt['return_at'] = DATE("Y-m-d H:i:s", intval($v2['cancel_time']));
                        }

                        $buyer_id = $v2['buyer_user_id'] ?? ($v2['user_id'] ?? '');
                        if ($buyer_id !== '') {
                            $dt['id_buyer'] = strval($buyer_id);
                        }
                        $buyer_username = $v2['buyer_nickname'] ?? ($v2['buyer_email'] ?? '');
                        if ($buyer_username !== '') {
                            $dt['c_username'] = strval($buyer_username);
                        }

                        if (!empty($v2['recipient_address']) && is_array($v2['recipient_address'])) {
                            $recipient = $v2['recipient_address'];
                            if (!empty($recipient['name'])) {
                                $dt['customer_text'] = strval($recipient['name']);
                            }
                            if (!empty($recipient['phone_number'])) {
                                $dt['phone'] = strval($recipient['phone_number']);
                            }
                            if (!empty($recipient['full_address'])) {
                                $dt['address'] = strval($recipient['full_address']);
                                $dt['address_2'] = strval($recipient['full_address']);
                            }
                            if (!empty($recipient['postal_code'])) {
                                $dt['postal_code'] = strval($recipient['postal_code']);
                            }
                            if (!empty($recipient['district_info']) && is_array($recipient['district_info'])) {
                                $dt['province_text'] = strval($recipient['district_info'][1]['address_name'] ?? '');
                                $dt['city_text'] = strval($recipient['district_info'][2]['address_name'] ?? '');
                                $dt['subdistrict_text'] = strval($recipient['district_info'][3]['address_name'] ?? '');
                            }
                        }

                        if (!empty($v2['payment']) && is_array($v2['payment'])) {
                            $payment = $v2['payment'];
                            if (isset($payment['total_amount'])) {
                                $dt['customer_price'] = doubleval($payment['total_amount']);
                            }
                            if (isset($payment['original_total_product_price'])) {
                                $dt['omset_kotor'] = doubleval($payment['original_total_product_price']);
                            }
                            if (isset($payment['seller_discount']) && isset($payment['original_total_product_price'])) {
                                $dt['diskon_penjual'] = doubleval($payment['seller_discount']);
                                $dt['omset_bersih'] = doubleval($payment['original_total_product_price'] - $payment['seller_discount']);
                            }
                        }

                        if (!empty($v2['line_items']) && is_array($v2['line_items'])) {
                            $js = array();
                            foreach ($v2['line_items'] as $k4 => $v4) {
                                $js[$k4]['id_product'] = $v4['sku_id'] ?? '';
                                $js[$k4]['sku'] = $v4['seller_sku'] ?? '';
                                $name = $v4['sku_name'] ?? '';
                                if ($name === "Default") {
                                    $name = "";
                                }
                                $js[$k4]['name'] = $name;
                                $js[$k4]['id_product_parent'] = $v4['product_id'] ?? '';
                                $js[$k4]['sku_parent'] = "";
                                $js[$k4]['name_parent'] = $v4['product_name'] ?? '';
                                $js[$k4]['qty'] = isset($v4['quantity']) ? strval($v4['quantity']) : '1';
                                $js[$k4]['price'] = $v4['sale_price'] ?? '';
                                $js[$k4]['original_price'] = $v4['original_price'] ?? '';
                                $js[$k4]['discount'] = $v4['seller_discount'] ?? '';
                            }

                            $price_total_hpp = 0;
                            $json = array();
                            $brand_selected = "MG";
                            $arr_brand = array();
                            foreach ($js as $k4 => $v4) {
                                $id_product = $v4['id_product'];
                                $id_product_parent = $v4['id_product_parent'];
                                $this->db->select('json');
                                $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));
                                if (empty($conf) && $v4['sku']) {
                                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                                }
                                $conf = json_decode($conf['json'] ?? '', true);
                                if (empty($conf)) {
                                    $js[$k4]['is_empty'] = true;
                                    continue;
                                }
                                foreach ($conf as $k5 => $v5) {
                                    if (empty($arr_product[$v5['product']])) {
                                        continue;
                                    }
                                    $product = $arr_product[$v5['product']];
                                    $arr_brand[$product['brand']] += 1;
                                    $price = 0;
                                    if (($dt['c_type'] ?? '') == "Pelanggan") {
                                        $price = $product['price_normal'];
                                    } else if (($dt['c_type'] ?? '') == "Distributor") {
                                        $price = $product['price_distributor'];
                                    } else if (($dt['c_type'] ?? '') == "Reseller") {
                                        $price = $product['price_reseller'];
                                    } else {
                                        $price = $product['price_normal'];
                                    }
                                    $json[$product['id']]['sku'] = $product['sku'];
                                    $json[$product['id']]['hpp'] = $product['price_buy'];
                                    $json[$product['id']]['product'] = $product['id'];
                                    $json[$product['id']]['product_text'] = $product['name'];
                                    $json[$product['id']]['product_sub'] = $product['sub_name'];
                                    $json[$product['id']]['brand'] = $product['brand'];
                                    $json[$product['id']]['price'] = $price;
                                    $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                                    $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                                    $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                                    $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                                }
                            }

                            if (!empty($arr_brand)) {
                                $max = 0;
                                foreach ($arr_brand as $k5 => $v5) {
                                    if ($v5 >= $max) {
                                        $max = $v5;
                                        $brand_selected = $k5;
                                    }
                                }
                                $dt['brand'] = $brand_selected;
                            }

                            $dt['pesanan'] = json_encode($js, true);
                            $dt['pesanan_count'] = count($js);
                            $dt['json'] = json_encode($json, true);
                            $dt['hpp'] = doubleval($price_total_hpp);
                        }

                        $status_raw = $v2['order_status'] ?? ($v2['status'] ?? '');
                        $status_upper = strtoupper(strval($status_raw));
                        if (in_array($status_upper, array('UNPAID'))) {
                            $order_status = 'UNPAID';
                        } else if (in_array($status_upper, array('AWAITING_COLLECTION', 'ON_HOLD'))) {
                            $order_status = 'PROCESSED';
                        } else if (in_array($status_upper, array('RETURNED'))) {
                            $order_status = 'RETURN';
                        } else if (in_array($status_upper, array('CANCELLED'))) {
                            $order_status = 'CANCELLED';
                        } else if (in_array($status_upper, array('COMPLETED'))) {
                            $order_status = 'COMPLETED';
                        } else if (in_array($status_upper, array('DELIVERED'))) {
                            $order_status = 'DELIVERED';
                        } else if (in_array($status_upper, array('IN_TRANSIT', 'PARTIALLY_SHIPPING'))) {
                            $order_status = 'SHIPPED';
                            $dt['is_shipped'] = 1;
                        } else if (in_array($status_upper, array('AWAITING_SHIPMENT'))) {
                            $order_status = 'READY_TO_SHIP';
                        } else if (in_array($status_raw, array('100'))) {
                            $order_status = 'UNPAID';
                        } else if (in_array($status_raw, array('112', '105'))) {
                            $order_status = 'PROCESSED';
                        } else if (in_array($status_raw, array('returned'))) {
                            $order_status = 'RETURN';
                        } else if (in_array($status_raw, array('140'))) {
                            $order_status = 'CANCELLED';
                        } else if (in_array($status_raw, array('130',))) {
                            $order_status = 'COMPLETED';
                        } else if (in_array($status_raw, array('122'))) {
                            $order_status = 'DELIVERED';
                        } else if (in_array($status_raw, array('121', '114'))) {
                            $order_status = 'SHIPPED';
                            $dt['is_shipped'] = 1;
                        } else if (in_array($status_raw, array('111'))) {
                            $order_status = 'READY_TO_SHIP';
                        }
                        $dt['order_status'] = strval($order_status);
                        if ($trx) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('transaction', $dt, array('id' => $trx['id']));
                        } else if ($dt['order_status'] !== 'CANCELLED') {
                            if (empty($dt['date'])) {
                                $dt['date'] = DATE("Y-m-d 23:00:00", strtotime($start_date));
                            }
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('transaction', $dt);
                        }
                    }

                    $next_page_token = $response['data']['next_page_token'] ?? '';
                    if ($next_page_token !== '') {
                        $page_token = $next_page_token;
                        continue;
                    }
                    break;
                }
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = "SHOPEE";
                $config = json_decode($v['val'], true);
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $host = 'https://partner.shopeemobile.com';
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];

                $page_size = 100;
                $cursor = '';

                for ($i = 0; $i <= 100; $i++) {
                    $path = "/api/v2/order/get_order_list";
                    $timest = time();
                    $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                    $sign = hash_hmac('sha256', $baseString, $partner_key);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&timestamp=' . $timest . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign=' . $sign
                            . '&time_range_field=create_time&time_from=' . $start_time . '&time_to=' . $until_time . '&page_size=' . $page_size . '&cursor=' . $cursor . '&response_optional_fields=order_status',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    $response = json_decode($response, true);

                    $cursor = $response['response']['next_cursor'];

                    foreach ($response['response']['order_list'] as $k2 => $v2) {
                        $order_id = $v2['order_sn'];
                        $this->db->select('id');
                        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['marketplace'] = strval($marketplace);
                        $dt['shop_id'] = strval($shop_id);
                        $dt['shop_name'] = strval($shop_name);
                        $dt['marketplace'] = $marketplace;
                        $dt['order_id'] = $order_id;
                        $dt['order_status'] = $v2['order_status'];
                        if ($trx) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('transaction', $dt, array('id' => $trx['id']));
                        } else if ($dt['order_status'] !== 'CANCELLED') {
                            $dt['date'] = DATE("Y-m-d 23:00:00", strtotime($start_date));
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('transaction', $dt);
                        }
                    }
                    if (empty($cursor)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "LAZADA") {
                $marketplace = "LAZADA";
                $config = json_decode($v['val'], true);
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];
                $url = 'https://api.lazada.co.id/rest';
                $page_size = 100;
                $cursor = '';

                $nomor = 0;
                $offset = 0;
                $limit = 100;

                for ($i = 0; $i <= 100; $i++) {
                    $c = new LazopClient($url, $app_key, $app_secret);
                    $request = new LazopRequest('/orders/get', 'GET');
                    $request->addApiParam('sort_direction', 'ASC');
                    $request->addApiParam('offset', $offset);
                    $request->addApiParam('limit', $limit);
                    $request->addApiParam('sort_by', 'created_at');
                    $start_date = DATE("Y-m-d", strtotime($start_date));
                    $until_date = DATE("Y-m-d", strtotime($until_date));
                    $until_date = DATE('Y-m-d', strtotime($until_date . " +1 days"));

                    $request->addApiParam('created_after', $start_date . 'T00:00:00+07:00');
                    $request->addApiParam('created_before', $until_date . 'T00:00:00+07:00');

                    $response = $c->execute($request, $access_token);
                    $response = json_decode($response, true);

                    $total_data = $response['data']['countTotal'];

                    foreach ($response['data']['orders'] as $k2 => $v2) {
                        $nomor++;
                        $order_id = $v2['order_id'];
                        $this->db->select('id');
                        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['marketplace'] = strval($marketplace);
                        $dt['shop_id'] = strval($shop_id);
                        $dt['shop_name'] = strval($shop_name);
                        $dt['marketplace'] = $marketplace;
                        $dt['order_id'] = $order_id;

                        $order_status = "COMPLETED";
                        $dt['is_shipped'] = 0;
                        if (in_array($v2['statuses'][0], array('unpaid'))) {
                            $order_status = 'UNPAID';
                        } else if (in_array($v2['statuses'][0], array('topack', 'pending'))) {
                            $order_status = 'PROCESSED';
                        } else if (in_array($v2['statuses'][0], array('returned', 'shipped_back_success'))) {
                            $order_status = 'RETURN';
                        } else if (in_array($v2['statuses'][0], array('canceled', 'failed', 'lost'))) {
                            $order_status = 'CANCELLED';
                        } else if (in_array($v2['statuses'][0], array('confirmed'))) {
                            $order_status = 'COMPLETED';
                            $dt['disbursement_at'] = '';
                            $dt['is_disbursement'] = '1';
                        } else if (in_array($v2['statuses'][0], array('delivered'))) {
                            $order_status = 'DELIVERED';
                        } else if (in_array($v2['statuses'][0], array('shipped'))) {
                            $order_status = 'SHIPPED';
                            $dt['is_shipped'] = 1;
                        } else if (in_array($v2['statuses'][0], array('ready_to_ship', 'toship', 'shipping'))) {
                            $order_status = 'READY_TO_SHIP';
                        }

                        $dt['order_status'] = $order_status;

                        $dt['customer_price'] = $v2['price'];

                        print_r($dt);

                        if ($trx) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('transaction', $dt, array('id' => $trx['id']));
                        } else if ($dt['order_status'] !== 'CANCELLED') {
                            $dt['date'] = DATE("Y-m-d 23:00:00", strtotime($start_date));
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('transaction', $dt);
                        }
                    }
                    $offset += $limit;
                    if ($nomor >= intval($total_data)) {
                        break;
                    }
                }
            }
        }

        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Sync data order berhasil!';
        echo json_encode($html, true);
        die;
    }

    function marketplace_product()
    {

        $msg = "Sync data produk berhasil!";
        $status = true;
        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $marketplace = strtoupper($marketplace);
        $shop_id = $dt['shop_id'];
        $qry = "";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }

        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        foreach ($data as $k => $v) {
            $shop_name = $v['shop_name'];
            $shop_id = $v['shop_id'];
            if ($v['opt'] == "TIKTOK") {
                $marketplace = "TIKTOK";
                $config = json_decode($v['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_secret = $this->app_secret_tiktok;

                $page_size = 100;
                $next_page_token = "";

                for ($i = 0; $i <= 100; $i++) {
                    $url = 'https://open-api.tiktokglobalshop.com/product/202312/products/search?access_token=' . $access_token . '&app_key=' . $app_key . '&page_size=' . $page_size . '&page_token=' . $next_page_token . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202312';
                    $urlParts = parse_url($url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
                    $timest = strtotime('now');
                    $pr = array();
                    $pr['secret'] = $app_secret;
                    $pr['timest'] = $timest;
                    $pr['get'] = $paramGET;
                    $pr['post'] = '{"status":"ACTIVATE"}';
                    $pr['url'] = $url;
                    $sign = $this->tiktok_signature_generator($pr);

                    $url = str_replace('{{sign}}', $sign, $url);
                    $url = str_replace('{{timestamp}}', $timest, $url);
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $pr['post'],
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));

                    $response = curl_exec($curl);

                    $response = json_decode($response, true);

                    if ($response['code']) {
                        $msg = $marketplace . ' ' . $shop_name . ' : ' . $response['message'];
                        $status = false;
                    }

                    $next_page_token = $response['data']['next_page_token'];

                    foreach ($response['data']['products'] as $k2 => $v2) {

                        $id_product = $v2['id'];
                        $this->db->select('id');
                        $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

                        $url = 'https://open-api.tiktokglobalshop.com/product/202309/products/' . $id_product . '?app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                        $urlParts = parse_url($url);
                        $paramGET = [];
                        parse_str($urlParts['query'], $paramGET);
                        $timest = strtotime('now');
                        $pr = array();
                        $pr['secret'] = $app_secret;
                        $pr['timest'] = $timest;
                        $pr['get'] = $paramGET;
                        $pr['post'] = '';
                        $pr['url'] = $url;
                        $sign = $this->tiktok_signature_generator($pr);

                        $url = str_replace('{{sign}}', $sign, $url);
                        $url = str_replace('{{timestamp}}', $timest, $url);
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                            CURLOPT_POSTFIELDS => $pr['post'],
                            CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/json',
                                'x-tts-access-token: ' . $access_token
                            ),
                        ));

                        $response_detail = curl_exec($curl);

                        $response_detail = json_decode($response_detail, true);

                        $v2 = $response_detail['data'];
                        $dt = array();
                        $dt['marketplace'] = $marketplace;
                        $dt['id_product'] = $id_product;
                        $dt['name'] = strval($v2['title']);
                        $dt['desc'] = strval($v2['description']);
                        $dt['sku'] = strval($v2['sku']);

                        $dt['shop_name'] = $shop_name;
                        $dt['shop_id'] = $shop_id;
                        // $img_url = $v2['main_images'][0]['urls'][0];
                        $img_url = $v2['main_images'][0]['thumb_urls'][0];
                        if ($img_url) {
                            $file_name = $id_product . '.jpg';
                            // $img_dir = '/public_html/app/assets/img/product_3rd/' . $file_name;
                            $img_dir = './assets/img/product_3rd/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        if ($product) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('product_3rd', $dt, array('id' => $product['id']));
                        } else {
                            $dt['created_by'] = strval($_SESSION['user']['id']);
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('product_3rd', $dt);
                            $product['id'] = $this->db->insert_id();
                        }

                        $item = $v2['skus'];
                        $item_list = array();
                        if (empty($item)) {
                            $varian['sku'] = '';
                            $varian['name'] = '';
                            $varian['id_product'] = '0';
                            $varian['sku_parent'] = $dt['sku'];
                            $varian['parent_name'] = $dt['name'];
                            $varian['id_product_parent'] = $dt['id_product'];
                            $varian['id_parent'] = $product['id'];
                            $varian['img'] = $dt['img'];
                            $item_list[] = $varian;
                        } else {
                            foreach ($item as $k3 => $v3) {
                                $varian['sku'] = $v3['seller_sku'];
                                $varian['name'] = strval($v3['sales_attributes'][0]['value_name']);
                                $varian['id_product'] = $v3['id'];
                                $varian['sku_parent'] = $dt['sku'];
                                $varian['parent_name'] = $dt['name'];
                                $varian['id_product_parent'] = $dt['id_product'];
                                $varian['id_parent'] = $product['id'];
                                // $img_url = $v3['sales_attributes'][0]['sku_img']['urls'][0];
                                $img_url = $v3['sales_attributes'][0]['sku_img']['thumb_urls'][0];
                                if ($img_url) {
                                    $file_name = $varian['id_product'] . '.jpg';
                                    $img_dir = './assets/img/product_3rd/' . $file_name;
                                    file_put_contents($img_dir, file_get_contents($img_url));
                                    $varian['img'] = $file_name;
                                }
                                $item_list[] = $varian;
                            }
                        }



                        $dt['json_varian'] = json_encode($item_list, true);
                        $dt['count_varian'] = count($item_list);

                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('product_3rd', $dt, array('id' => $product['id']));

                        $ids = '';
                        foreach ($item_list as $k4 => $v4) {
                            $id_product = $v4['id_product'];
                            $id_product_parent = $v4['id_product_parent'];
                            $this->db->select('id');
                            $product = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                            $dtt = array();
                            foreach ($v4 as $k5 => $v5) {
                                $dtt[$k5] = strval($v5);
                            }
                            $dtt['marketplace'] = $marketplace;
                            $dtt['shop_name'] = $shop_name;
                            $dtt['shop_id'] = $shop_id;

                            if ($dtt['sku']) {
                                $dat = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $dtt['sku']));
                                if ($dat) {
                                    $dtt['json'] = strval($dat['json']);
                                }
                            }

                            if ($product) {
                                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                                $this->db->update('product_variant_3rd', $dtt, array('id' => $product['id']));
                            } else {

                                $dtt['created_by'] = strval($_SESSION['user']['id']);
                                $dtt['created_at'] = DATE("Y-m-d H:i:s");
                                $this->db->insert('product_variant_3rd', $dtt);
                                $product['id'] = $this->db->insert_id();
                            }
                            $ids .= $id_product . ',';
                        }
                        // print_r($v2);
                        // die;
                        // $id_parent = $dt['id_product'];
                        // $ids = substr($ids, 0, -1);
                        // $qry = "";
                        // if ($ids != "") {
                        //     $qry = " AND id_product NOT IN ($ids) ";
                        // }
                        // $this->db->query("DELETE FROM product_variant_3rd
                        //         WHERE id_product_parent = '$id_parent' $qry");
                    }
                    if (empty($next_page_token)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_lazada;
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $access_token = $config['access_token'];
                $host = 'https://partner.shopeemobile.com';
                $shop_id = intval($shop_id);

                $offset = 0;
                for ($i = 0; $i <= 3; $i++) {
                    $path = "/api/v2/product/get_item_list";
                    $timest = time();
                    $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                    $sign = hash_hmac('sha256', $baseString, $partner_key);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?partner_id=' . $config['partner_id'] . '&timestamp=' . $timest . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign=' . $sign
                            . '&offset=' . $offset . '&page_size=50&item_status=NORMAL',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                    ));
                    $response = curl_exec($curl);
                    curl_close($curl);
                    $response = json_decode($response, true);

                    if ($response['response']['next_offset']) {
                        $offset = $response['response']['next_offset'];
                    } else {
                        $offset = $response['response']['next_offset'];
                    }

                    $list_id = '';
                    foreach ($response['response']['item'] as $k => $v) {
                        $list_id .= $v['item_id'] . ',';
                    }
                    $list_id = substr($list_id, 0, -1);

                    if ($list_id) {
                        $path = "/api/v2/product/get_item_base_info";
                        $timest = time();
                        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                        $sign = hash_hmac('sha256', $baseString, $partner_key);

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&item_id_list=' . $list_id . '&need_complaint_policy=true&need_tax_info=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                        ));

                        $response = curl_exec($curl);
                        curl_close($curl);
                        $response = json_decode($response, true);
                    }
                    foreach ($response['response']['item_list']  as $k2 => $v2) {
                        $id_product = $v2['item_id'];
                        $this->db->select('id');
                        $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['marketplace'] = $marketplace;
                        $dt['id_product'] = $id_product;
                        $dt['name'] = strval($v2['item_name']);
                        $dt['desc'] = strval($v2['description']);
                        $dt['sku'] = strval($v2['item_sku']);
                        $dt['shop_name'] = $shop_name;
                        $dt['shop_id'] = $shop_id;
                        $img_url = $v2['image']['image_url_list'][0];
                        if ($img_url) {
                            $file_name = $id_product . '.jpg';
                            $img_dir = './assets/img/product_3rd/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        if ($product) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('product_3rd', $dt, array('id' => $product['id']));
                        } else {
                            $dt['created_by'] = strval($_SESSION['user']['id']);
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('product_3rd', $dt);
                            $product['id'] = $this->db->insert_id();
                        }

                        $item = array();
                        if (intval($v2['has_model']) > 0) {
                            $path = "/api/v2/product/get_model_list";
                            $timest = time();
                            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                            $sign = hash_hmac('sha256', $baseString, $partner_key);

                            // $dt['id_product'] = '3609877442';
                            $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&item_id=' . $dt['id_product'] . '&need_complaint_policy=true&need_tax_info=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'GET',
                            ));

                            $response = curl_exec($curl);
                            curl_close($curl);
                            $response = json_decode($response, true);

                            $item = $response['response']['model'];
                        }


                        // print_r($v2);
                        // print_r($item);
                        // echo ' --- ';

                        $item_list = array();
                        if (empty($item)) {
                            $varian['sku'] = '';
                            $varian['name'] = '';
                            $varian['id_product'] = '0';
                            $varian['sku_parent'] = $dt['sku'];
                            $varian['parent_name'] = $dt['name'];
                            $varian['id_product_parent'] = $dt['id_product'];
                            $varian['id_parent'] = $product['id'];
                            $varian['img'] = $dt['img'];
                            $item_list[] = $varian;
                        } else {
                            foreach ($item as $k3 => $v3) {
                                $varian['sku'] = $v3['model_sku'];
                                $name = $v3['model_name'];
                                if (empty($name)) {
                                    $name = $dt['name'];
                                }
                                $varian['name'] = strval($name);
                                $varian['id_product'] = $v3['model_id'];
                                $varian['sku_parent'] = $dt['sku'];
                                $varian['parent_name'] = $dt['name'];
                                $varian['id_product_parent'] = $dt['id_product'];
                                $varian['id_parent'] = $product['id'];
                                $img_url = $response['response']['tier_variation'][$k3]['option_list'][0]['image']['image_url'];
                                if ($img_url) {
                                    $file_name = $varian['id_product'] . '.jpg';
                                    $img_dir = './assets/img/product_3rd/' . $file_name;
                                    file_put_contents($img_dir, file_get_contents($img_url));
                                    $varian['img'] = $file_name;
                                }
                                $item_list[] = $varian;
                            }
                        }

                        $dt['json_varian'] = json_encode($item_list, true);
                        $dt['count_varian'] = count($item_list);



                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('product_3rd', $dt, array('id' => $product['id']));

                        $ids = '';
                        foreach ($item_list as $k4 => $v4) {
                            $id_product = $v4['id_product'];
                            $id_product_parent = $v4['id_product_parent'];
                            $this->db->select('id');
                            $product = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                            $dtt = array();
                            foreach ($v4 as $k5 => $v5) {
                                $dtt[$k5] = strval($v5);
                            }
                            $dtt['marketplace'] = $marketplace;
                            $dtt['shop_name'] = $shop_name;
                            $dtt['shop_id'] = $shop_id;
                            if ($product) {
                                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                                $this->db->update('product_variant_3rd', $dtt, array('id' => $product['id']));
                            } else {

                                $dtt['created_by'] = strval($_SESSION['user']['id']);
                                $dtt['created_at'] = DATE("Y-m-d H:i:s");
                                $this->db->insert('product_variant_3rd', $dtt);
                                $product['id'] = $this->db->insert_id();
                            }
                            $ids .= $id_product . ',';
                        }
                        // $id_parent = $dt['id_product'];
                        // $ids = substr($ids, 0, -1);
                        // $qry = "";
                        // if ($ids != "") {
                        //     $qry = " AND id_product NOT IN ($ids) ";
                        // }
                        // $this->db->query("DELETE FROM product_variant_3rd
                        //         WHERE id_product_parent = '$id_parent' $qry");
                    }

                    if (empty($offset)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "LAZADA") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $refresh_token = $config['refresh_token'];
                $url = 'https://api.lazada.co.id/rest';

                $nomor = 0;
                $offset = 0;
                $limit = 50;
                for ($i = 0; $i <= 3; $i++) {
                    $nomor++;
                    $c = new LazopClient($url, $app_key, $app_secret);
                    $request = new LazopRequest('/products/get', 'GET');
                    $request->addApiParam('filter', 'all');
                    $request->addApiParam('offset', $offset);
                    $request->addApiParam('limit', $limit);
                    $request->addApiParam('options', '1');
                    $response = $c->execute($request, $config['access_token']);
                    $response = json_decode($response, true);

                    foreach ($response['data']['products'] as $k2 => $v2) {
                        $id_product = $v2['item_id'];
                        $this->db->select('id');
                        $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['marketplace'] = $marketplace;
                        $dt['id_product'] = $id_product;
                        $dt['name'] = strval($v2['attributes']['name']);
                        $dt['desc'] = strval($v2['attributes']['description']);
                        $dt['sku'] = "";

                        $dt['shop_name'] = $shop_name;
                        $dt['shop_id'] = $shop_id;
                        $img_url = $v2['images'][0];
                        if ($img_url) {
                            $file_name = $id_product . '.jpg';
                            $img_dir = './assets/img/product_3rd/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        if ($product) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('product_3rd', $dt, array('id' => $product['id']));
                        } else {
                            $dt['created_by'] = strval($_SESSION['user']['id']);
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('product_3rd', $dt);
                            $product['id'] = $this->db->insert_id();
                        }


                        $item = $v2['skus'];
                        $item_list = array();
                        if (empty($item)) {
                            $varian['sku'] = '';
                            $varian['name'] = '';
                            $varian['id_product'] = '0';
                            $varian['sku_parent'] = $dt['sku'];
                            $varian['parent_name'] = $dt['name'];
                            $varian['id_product_parent'] = $dt['id_product'];
                            $varian['id_parent'] = $product['id'];
                            $varian['img'] = $dt['img'];
                            $item_list[] = $varian;
                        } else {
                            foreach ($item as $k3 => $v3) {
                                $varian['sku'] = $v3['SellerSku'];

                                $name = "";
                                if ($v3['saleProp']) {
                                    foreach ($v3['saleProp'] as $k4 => $v4) {
                                        $name = $v4;
                                    }
                                }
                                if (empty($name)) {
                                    $name = $v3['fragrance_family'];
                                }

                                $varian['name'] = strval($name);
                                $varian['id_product'] = $v3['SkuId'];
                                $varian['sku_parent'] = $dt['sku'];
                                $varian['parent_name'] = $dt['name'];
                                $varian['id_product_parent'] = $dt['id_product'];
                                $varian['id_parent'] = $product['id'];
                                $img_url = $v3['Images'][0];
                                if ($img_url) {
                                    $file_name = $varian['id_product'] . '.jpg';
                                    $img_dir = './assets/img/product_3rd/' . $file_name;
                                    file_put_contents($img_dir, file_get_contents($img_url));
                                    $varian['img'] = $file_name;
                                }
                                $item_list[] = $varian;
                            }
                        }

                        $dt['json_varian'] = json_encode($item_list, true);
                        $dt['count_varian'] = count($item_list);

                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('product_3rd', $dt, array('id' => $product['id']));

                        $ids = '';
                        foreach ($item_list as $k4 => $v4) {
                            $id_product = $v4['id_product'];
                            $id_product_parent = $v4['id_product_parent'];
                            $this->db->select('id');
                            $product = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                            $dtt = array();
                            foreach ($v4 as $k5 => $v5) {
                                $dtt[$k5] = strval($v5);
                            }
                            $dtt['marketplace'] = $marketplace;
                            $dtt['shop_name'] = $shop_name;
                            $dtt['shop_id'] = $shop_id;
                            if ($product) {
                                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                                $this->db->update('product_variant_3rd', $dtt, array('id' => $product['id']));
                            } else {

                                $dtt['created_by'] = strval($_SESSION['user']['id']);
                                $dtt['created_at'] = DATE("Y-m-d H:i:s");
                                $this->db->insert('product_variant_3rd', $dtt);
                                $product['id'] = $this->db->insert_id();
                            }
                            $ids .= $id_product . ',';
                        }
                        // $id_parent = $dt['id_product'];
                        // $ids = substr($ids, 0, -1);
                        // $qry = "";
                        // if ($ids != "") {
                        //     $qry = " AND id_product NOT IN ($ids) ";
                        // }
                        // $this->db->query("DELETE FROM product_variant_3rd
                        //         WHERE id_product_parent = '$id_parent' $qry");
                    }

                    $offset += $limit;

                    if ($nomor >= intval($response['data']['total_products'])) {
                        break;
                    }
                }
            }
        }

        $html['status'] = $status;
        $html['data'] = array();
        $html['msg'] = $msg;
        echo json_encode($html, true);
        die;
    }

    function cronjob_influencer()
    {
        $monitor = $this->cron_monitor_start('cronjob_influencer', array(
            'mode' => isset($_GET['mode']) ? strval($_GET['mode']) : '',
        ));
        $mode = isset($_GET['mode']) ? strval($_GET['mode']) : '';

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now < $target) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => false,
                    'data' => [],
                    'msg' => "Acneno System influencer cronjob will be processed at " . $target . "!"
                ]);
                $this->cron_monitor_finish($monitor, array(
                    'status' => 'skipped',
                    'processed_count' => 0,
                    'queue_count' => 0,
                    'target_time' => $target,
                    'reason' => 'before_target_time',
                ));
                die;
            }
        }

        // Synchronously refresh supported social profiles through their adapters.
        $today = DATE("Y-m-d");
        $sync_date = DATE('Y-m-d', strtotime($today . " -7 days"));

        $list = $this->mymodel->selectWithQuery("
            SELECT id, type, url FROM influencer
            WHERE status = 'Aktif'
            AND (sync_at < ('$sync_date' + INTERVAL 1 DAY) OR sync_at IS NULL)
            AND url != ''
            LIMIT 10
        ");

        $enqueued = 0;
        foreach ($list as $vl) {
            // Update endorse aggregation (this is local DB work, no API needed)
            $id = $vl['id'];
            $endorse = $this->mymodel->selectWithQuery("SELECT COUNT(id) as frequency, SUM(total_cost) as total_cost, SUM(views) as views,
            AVG(views) as avg_views,
            AVG(likes+comment+share_save) as avg_interaksi,
            SUM(likes) as likes,
            SUM(share_save) as share,
            SUM(comment) as comment
            FROM endorse WHERE influencer = '$id'
            AND link_upload != ''
            ");
            $endorse = $endorse[0];

            $dt = array();
            $dt['frequency'] = $endorse['frequency'];
            $dt['total_cost'] = $endorse['total_cost'];
            $dt['avg_view'] = $endorse['avg_views'];
            $dt['avg_interaksi'] = $endorse['avg_interaksi'];
            if ($endorse['total_cost'] > 0 && $endorse['views'] > 0) {
                $dt['cpm'] = $endorse['total_cost'] / $endorse['views'] * 1000;
            } else {
                $dt['cpm'] = 0;
            }
            $this->db->update('influencer', $dt, array('id' => $id));

            $result = in_array($vl['type'], ['Tiktok', 'Instagram', 'Threads'], true)
                ? $this->template->syncSocialProfile('influencer', $vl['id'], $vl['type'], $vl['url'])
                : ['status' => false];
            if ($result['status']) $enqueued++;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => true,
            'data' => [],
            'msg' => $enqueued . " of " . count($list) . " influencer records synced (sync_at <= $sync_date)"
        ]);
        $this->cron_monitor_finish($monitor, array(
            'status' => 'ok',
            'processed_count' => count($list),
            'queue_count' => $enqueued,
            'sync_date' => $sync_date,
        ));
        die;
    }

    function cronjob_influencer_dummy()
    {
        $monitor = $this->cron_monitor_start('cronjob_influencer_dummy', array(
            'mode' => isset($_GET['mode']) ? strval($_GET['mode']) : '',
        ));
        $mode = isset($_GET['mode']) ? strval($_GET['mode']) : '';

        $target = DATE("Y-m-d 01:00:00");
        $now = DATE("Y-m-d H:i:s");

        if ($mode != 'true') {
            if ($now < $target) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => false,
                    'data' => [],
                    'msg' => "Influencer dummy cronjob will be processed at " . $target . "!"
                ]);
                $this->cron_monitor_finish($monitor, array(
                    'status' => 'skipped',
                    'processed_count' => 0,
                    'queue_count' => 0,
                    'target_time' => $target,
                    'reason' => 'before_target_time',
                ));
                die;
            }
        }

        $today = DATE("Y-m-d");
        $sync_date = DATE('Y-m-d', strtotime($today . " -7 days"));

        // Dummy profiles support direct TikTok and Instagram refresh.
        $list = $this->mymodel->selectWithQuery("
            SELECT id, type, url FROM influencer_dummy
            WHERE status = 'Aktif'
            AND (sync_at < ('$sync_date' + INTERVAL 1 DAY) OR sync_at IS NULL)
            AND url != ''
            LIMIT 10
        ");

        $enqueued = 0;
        foreach ($list as $vl) {
            $type = $vl['type'] ? $vl['type'] : 'Tiktok';
            $result = in_array($type, ['Tiktok', 'Instagram'], true)
                ? $this->template->syncSocialProfile('influencer_dummy', $vl['id'], $type, $vl['url'])
                : ['status' => false];
            if ($result['status']) $enqueued++;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => true,
            'data' => [],
            'msg' => $enqueued . " of " . count($list) . " influencer dummy records synced (sync_at <= $sync_date)"
        ]);
        $this->cron_monitor_finish($monitor, array(
            'status' => 'ok',
            'processed_count' => count($list),
            'queue_count' => $enqueued,
            'sync_date' => $sync_date,
        ));
        die;
    }

    function maintenance()
    {
        $logs = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_logs
        WHERE views_after = 0
        AND views < 0
        -- AND id_endorse = '5320'
        ");
        foreach ($logs as $k2 => $v2) {
            $dtt = array();
            $id = $v2['id'];
            $id_endorse = $v2['id_endorse'];
            $dt_before = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_logs WHERE id_endorse = '$id_endorse'
            AND id < '$id'
            ORDER BY id DESC
            LIMIT 1
            ");
            $dt_before = $dt_before[0];
            if ($dt_before) {
                $dtt['views_after'] = strval($dt_before['views_after']);
                $dtt['likes_after'] = strval($dt_before['likes_after']);
                $dtt['comment_after'] = strval($dt_before['comment_after']);
                $dtt['share_save_after'] = strval($dt_before['share_save_after']);
                $dtt['cpm_after'] = strval($dt_before['cpm_after']);

                $dtt['views'] = 0;
                $dtt['likes'] = 0;
                $dtt['comment'] = 0;
                $dtt['share_save'] = 0;
                $dtt['cpm'] = 0;

                $dtt['views_before'] = strval($dt_before['views_after']);
                $dtt['likes_before'] = strval($dt_before['likes_after']);
                $dtt['comment_before'] = strval($dt_before['comment_after']);
                $dtt['share_save_before'] = strval($dt_before['share_save_after']);
                $dtt['cpm_before'] = strval($dt_before['cpm_after']);

                print_r($dtt);
                $this->db->update('endorse_logs', $dtt, array('id' => $v2['id']));
            }
        }
    }

    function maintenance_2()
    {
        $today = DATE("Y-m-d");
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse
        -- WHERE id = 5101
        WHERE DATE(updated_at) != '$today'
        -- LIMIT 100
        ");
        foreach ($data as $k2 => $v2) {
            $id = $v2['id'];
            $logs = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_logs
            WHERE id_endorse = '$id'
            AND views_after > 0
            ORDER BY id DESC
            LIMIT 1");
            $logs = $logs[0];
            $dtt = array();
            $dtt['views'] = strval($logs['views_after']);
            $dtt['likes'] = strval($logs['likes_after']);
            $dtt['comment'] = strval($logs['comment_after']);
            $dtt['share_save'] = strval($logs['share_save_after']);
            $dtt['cpm'] = strval($logs['cpm_after']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            print_r($dtt);
            $this->db->update('endorse', $dtt, array('id' => $v2['id']));
        }
    }

    function maintenance_3()
    {
        $today = DATE("Y-m-04 H:i:s");
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse
        WHERE link_upload LIKE '%vt.%'
        AND platform = 'Tiktok' AND DATE(updated_at) != '$today'
        AND status = 'Aktif' AND status_campaign = 'Aktif'
        ORDER BY created_at DESC
        LIMIT 1000
       ");

        foreach ($data as $k => $v) {
            echo $v['id'];
            echo '<br>';
            $dt = array();
            $url = $v['link_upload'];
            echo $url;
            echo '<br>';
            $new_url = $this->getFinalUrl($url);
            if (strpos($new_url, "tiktok.com") !== false) {
                echo $new_url;
                echo '<br>';
                $dt['link_upload'] = $new_url;
            }
            echo '----';
            echo '<br>';
            echo '<br>';
            $dt['updated_at'] = $today;
            $dt['platform'] = 'Tiktok';
            $this->db->update('endorse', $dt, array('id' => $v['id']));
        }
    }

    function getFinalUrl($url)
    {
        // Get headers for the URL, including any redirect headers
        $headers = get_headers($url, 1);

        // Check if there is a 'Location' header, which indicates a redirect
        if (isset($headers['Location'])) {
            // If 'Location' is an array (in case of multiple redirects), get the last one
            $finalUrl = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
            $parsedUrl = parse_url($finalUrl);

            // Reconstruct the URL without query parameters
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
            return $baseUrl;
        }

        // Return the original URL if there is no redirect
        return $url;
    }

    function cronjob_endorse()
    {
        $monitor = $this->cron_monitor_start('cronjob_endorse', array(
            'mode' => isset($_GET['mode']) ? strval($_GET['mode']) : '',
        ));


        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Acneno System influencer cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                $this->cron_monitor_finish($monitor, array(
                    'status' => 'skipped',
                    'processed_count' => 0,
                    'queue_count' => 0,
                    'target_time' => $target,
                    'reason' => 'before_target_time',
                ));
                die;
            }
        }
        $today = DATE("Y-m-d");
        // $today = DATE('Y-m-d', strtotime($today . " -1 days"));
        $todayy = $today;

        $list = $this->mymodel->selectWithQuery("SELECT * FROM endorse WHERE status = 'Aktif' AND status_campaign = 'Aktif' AND (sync_at < '$today' OR sync_at IS NULL) AND link_upload != '' LIMIT 10");

        // Track only the campaigns whose endorse_logs actually changed this run, so we
        // re-aggregate just those instead of re-SUMming every active campaign's full history.
        $touched = array();

        foreach ($list as $kl => $vl) {

            $id_endorse = $vl['id'];
            $v = $vl;
            $touched[strval($vl['id_campaign'])] = true;
            $today = DATE("Y-m-d");
            $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));

            $query = $this->mymodel->selectWithQuery("SELECT id
            FROM endorse_logs
            WHERE id_endorse = '$id_endorse' AND date = '$today'
            ORDER BY id DESC
            LIMIT 1");
            $query = !empty($query) ? $query[0] : null;
            $query_yesterday = $this->mymodel->selectWithQuery("SELECT * 
            FROM endorse_logs
            WHERE id_endorse = '$id_endorse' AND date < '$today' AND views_after > 0 ORDER BY date DESC LIMIT 1 ");
            $query_yesterday = !empty($query_yesterday) ? $query_yesterday[0] : array();

            $prev_likes = intval($query_yesterday['likes_after'] ?? 0);
            $prev_comment = intval($query_yesterday['comment_after'] ?? 0);
            $prev_share_save = intval($query_yesterday['share_save_after'] ?? 0);
            $prev_views = intval($query_yesterday['views_after'] ?? 0);


            $dt = array();
            $dt['status'] = strval($v['status']);
            $dt['status_campaign'] = strval($v['status_campaign']);
            $dt['id_endorse'] = strval($v['id']);
            $dt['id_campaign'] = strval($v['id_campaign']);
            $dt['influencer'] = strval($v['influencer']);
            $dt['date'] = $today;


            $response = $this->template->get_social_media($v['platform'], $v['link_upload']);

            $dts = array();
            $dts['sync_at'] = DATE("Y-m-d H:i:s");
            if ($response['data']['created_at']) {
                $dts['posting_at'] = $response['data']['created_at'];
            }

            $this->db->update('endorse', $dts, array('id' => $v['id']));

            // if ($response['data']['view'] > 0) {

            $dt['likes'] = $prev_likes;
            $dt['comment'] = $prev_comment;
            $dt['share_save'] = $prev_share_save;
            $dt['views'] = $prev_views;

            if ($response['data']['view'] > 0) {
                $dt['likes'] = $response['data']['like'];
                $dt['comment'] = $response['data']['comment'];
                $dt['share_save'] = doubleval($response['data']['share']) + doubleval($response['data']['collect']);
                $dt['views'] = $response['data']['view'];
            }

            // Keep views cumulative and non-decreasing per content.
            if (intval($dt['views']) < $prev_views) {
                $dt['views'] = $prev_views;
            }

            if ($dt['views'] >= 50000) {
                $id_influencer = $vl['influencer'];
                $creator = $this->mymodel->selectWithQuery("SELECT follower
                    FROM influencer WHERE id = '$id_influencer'");
                $creator = $creator[0];
                $percentage = 0;
                $follower = intval($creator['follower']);
                if ($follower > 0) {
                    $batas = intval($follower * 30 / 100);
                    if ($dt['views'] >= $batas) {
                        $dt['is_fyp'] = "1";
                    }
                } else {
                    $dt['is_fyp'] = "1";
                }
            }



            $dtt = $dt;
            unset($dt['is_fyp']);
            unset($dtt['id_endorse']);
            unset($dtt['id_campaign']);
            unset($dtt['date']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");

            $this->db->update('endorse', $dtt, array('id' => $id_endorse));


            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $dtt = array();
            $dtt['likes'] = doubleval($dt['likes']);
            $dtt['comment'] = doubleval($dt['comment']);
            $dtt['share_save'] = doubleval($dt['share_save']);
            $dtt['views'] = doubleval($dt['views']);
            $dtt['cpm'] = doubleval($dt['cpm']);

            $dt['total_cost'] = doubleval($v['total_cost']);

            $dt['link_upload'] = strval($v['link_upload']);
            $dt['platform'] = strval($v['platform']);

            $dt['likes_after'] = intval($dt['likes']);
            $dt['comment_after'] = intval($dt['comment']);
            $dt['share_save_after'] = intval($dt['share_save']);
            $dt['views_after'] = intval($dt['views']);

            if ($v['total_cost'] > 0 && $dt['views_after'] > 0) {
                $dt['cpm_after'] = doubleval($v['total_cost']) / doubleval($dt['views_after']) * 1000;
            } else {
                $dt['cpm_after'] = 0;
            }

            $dt['likes'] -= $prev_likes;
            $dt['comment'] -= $prev_comment;
            $dt['share_save'] -= $prev_share_save;
            $dt['views'] = max(0, intval($dt['views_after']) - $prev_views);

            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $dt['likes_before'] = $prev_likes;
            $dt['comment_before'] = $prev_comment;
            $dt['share_save_before'] = $prev_share_save;
            $dt['views_before'] = $prev_views;

            if ($v['total_cost'] > 0 && $dt['views_before'] > 0) {
                $dt['cpm_before'] = doubleval($v['total_cost']) / doubleval($dt['views_before']) * 1000;
            } else {
                $dt['cpm_before'] = 0;
            }
            // }

            // $dt['is_cron'] = '1';
            // print_r($dt);die;
            $dt['brand'] = strval($vl['brand']);

            $dt_tmp = array();
            foreach ($dt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dt = $dt_tmp;

            if ($query) {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $this->db->update('endorse_logs', $dt, array('id_endorse' => $id_endorse, 'date' => $today));
                $id_parent = $query['id'];
            } else {
                $dt['created_at'] = DATE("Y-m-d H:i:s");
                $dt['created_by'] = strval($user['id']);
                if ($this->db->insert('endorse_logs', $dt)) {
                    $id_parent = $this->db->insert_id();
                } else {
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $this->db->update('endorse_logs', $dt, array('id_endorse' => $id_endorse, 'date' => $today));
                    $id_parent = 0;
                }
            }

            $dt_tmp = array();
            foreach ($dtt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dtt = $dt_tmp;

            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = strval($user['id']);
            $this->db->update('endorse', $dtt, array('id' => $v['id']));
        }

        // Only roll up campaigns that received new/updated logs this run. The cumulative
        // SUM over endorse_logs is unchanged for untouched campaigns, so re-aggregating all
        // active campaigns every minute was the dominant CPU cost (~697k rows scanned/call).
        foreach (array_keys($touched) as $id_parent) {
            $this->update_endorse_parent($id_parent, array('status' => 'Aktif'));
        }

        // Hourly full-sweep backstop: re-aggregate every active campaign at most once per
        // hour. Catches campaigns changed outside the sync path (e.g. bulk delete/status
        // edits) that don't self-roll, without paying the full scan every minute. Gated by a
        // file marker so it piggybacks the existing per-minute cron (no extra crontab entry).
        $sweep_marker = APPPATH . 'cache/endorse_rollup_sweep.txt';
        $last_sweep = is_file($sweep_marker) ? (int) filemtime($sweep_marker) : 0;
        if (time() - $last_sweep >= 3600) {
            $all_active = $this->mymodel->selectWithQuery("SELECT id FROM endorse_campaign WHERE status = 'Aktif'");
            foreach ($all_active as $c) {
                $this->update_endorse_parent($c['id'], array('status' => 'Aktif'));
            }
            @touch($sweep_marker);
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data endorse yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        $this->cron_monitor_finish($monitor, array(
            'status' => 'ok',
            'processed_count' => count($list),
            'queue_count' => count($touched),
            'sync_date' => $todayy,
        ));
        die;
    }

    function update_endorse_parent($id_parent, $detail)
    {
        $v['id'] = $id_parent;
        $today = DATE("Y-m-d");
        $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));
        $query = $this->mymodel->selectWithQuery("SELECT id
        FROM endorse_campaign_logs
        WHERE id_campaign = '$id_parent' AND date = '$today' ");
        $query = $query[0];

        $query_yesterday = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_campaign_logs
        WHERE id_campaign = '$id_parent' AND date < '$today' ORDER BY date DESC LIMIT 1 ");
        $query_yesterday = $query_yesterday[0];

        $item_detail = $this->mymodel->selectWithQuery("SELECT SUM(total_cost) as total_cost, COUNT(id) as count_endorse,
        SUM(likes) as likes, SUM(comment) as comment, SUM(share_save) as share_save, SUM(views) as views, AVG(cpm) as cpm
        FROM endorse
        WHERE id_campaign = '$id_parent'  AND link_upload != ''
        AND status = 'Aktif' ");
        $item_detail = $item_detail[0];

        $item = $this->mymodel->selectWithQuery("SELECT SUM(likes) as likes, SUM(comment) as comment, SUM(share_save) as share_save, SUM(views) as views, AVG(cpm) as cpm,
        SUM(likes_after) as likes_after, SUM(comment_after) as comment_after, SUM(share_save_after) as share_save_after, SUM(views_after) as views_after, AVG(cpm_after) as cpm_after,
        SUM(likes_before) as likes_before, SUM(comment_before) as comment_before, SUM(share_save_before) as share_save_before, SUM(views_before) as views_before, AVG(cpm_before) as cpm_before
        FROM endorse_logs
        WHERE id_campaign = '$id_parent';");
        $dt = array();
        $dt['id_campaign'] = $v['id'];
        $dt['total_cost'] = doubleval($item_detail['total_cost']);
        $dt['date'] = $today;
        foreach ($item[0] as $k2 => $v2) {
            $dt[$k2] = doubleval($v2);
        }


        $dtt = array();
        foreach ($item_detail as $k3 => $v3) {
            $dtt[$k3] = doubleval($v3);
        }

        $id_parent = $v['id'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
        FROM endorse WHERE id_campaign = '$id_parent'  ");
        $summary = $summary[0];
        $dtt['count_endorse'] = intval($summary['count']);
        $dt['ce_now'] = $dtt['count_endorse'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif'  ");
        $summary = $summary[0];
        $dtt['count_endorse_active'] = intval($summary['count']);
        $dt['ce_active_now'] = $dtt['count_endorse_active'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif' 
        AND link_upload != '' ");
        $summary = $summary[0];
        $dtt['count_endorse_processed'] = intval($summary['count']);
        $dt['ce_processed_now'] = $dtt['count_endorse_processed'];


        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(DISTINCT influencer) as count
        FROM endorse WHERE id_campaign = '$id_parent'  ");
        $summary = $summary[0];
        $dtt['count_influencer'] = intval($summary['count']);
        $dt['ci_now'] = $dtt['count_influencer'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(DISTINCT influencer) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif'  ");
        $summary = $summary[0];
        $dtt['count_influencer_active'] = intval($summary['count']);
        $dt['ci_active_now'] = $dtt['count_influencer_active'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(DISTINCT influencer) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif'  
        AND link_upload != '' ");
        $summary = $summary[0];
        $dtt['count_influencer_processed'] = intval($summary['count']);
        $dt['ci_processed_now'] = $dtt['count_influencer_processed'];

        // print_r($query_yesterday);die;
        $dt['ci_before'] =  $query_yesterday['ci_now'];
        $dt['ci_active_before'] = $query_yesterday['ci_active_now'];
        $dt['ci_processed_before'] = $query_yesterday['ci_processed_now'];

        $dt['ce_before'] =  $query_yesterday['ce_now'];
        $dt['ce_active_before'] = $query_yesterday['ce_active_now'];
        $dt['ce_processed_before'] = $query_yesterday['ce_processed_now'];

        $dt['ci_before'] =  $query_yesterday['ci_now'];
        $dt['ci_active_before'] = $query_yesterday['ci_active_now'];
        $dt['ci_processed_before'] = $query_yesterday['ci_processed_now'];
        $dt['ce_before'] =  $query_yesterday['ce_now'];
        $dt['ce_active_before'] = $query_yesterday['ce_active_now'];
        $dt['ce_processed_before'] = $query_yesterday['ce_processed_now'];

        $dt['ci_after'] =  $dt['ci_now'];
        $dt['ci_active_after'] = $dt['ci_active_now'];
        $dt['ci_processed_after'] = $dt['ci_processed_now'];
        $dt['ce_after'] =  $dt['ce_now'];
        $dt['ce_active_after'] = $dt['ce_active_now'];
        $dt['ce_processed_after'] = $dt['ce_processed_now'];

        $dt['ci_now'] =  $dt['ci_after'] - $dt['now_before'];
        $dt['ci_active_now'] = $dt['ci_active_after'] - $dt['ci_active_before'];
        $dt['ci_processed_now'] = $dt['ci_processed_after'] - $dt['ci_processed_before'];
        $dt['ce_now'] =  $dt['ce_after'] - $dt['ce_before'];
        $dt['ce_active_now'] = $dt['ce_active_after'] - $dt['ce_active_before'];
        $dt['ce_processed_now'] = $dt['ce_processed_after'] - $dt['ce_processed_before'];

        // $dt['is_cron'] = '1';
        $dt_tmp = array();
        foreach ($dt as $kt => $vt) {
            $dt_tmp[$kt] = strval($vt);
        }
        $dt = $dt_tmp;
        $dt['status'] = strval($detail['status']);

        $campaign = $this->mymodel->selectDataOne('endorse_campaign', array('id' => $id_parent));
        $dt['brand'] = strval($campaign['brand']);


        if ($query) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);
            $this->db->update('endorse_campaign_logs', $dt, array('id' => $query['id']));
            // $id_parent = $query['id'];
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = strval($user['id']);
            $this->db->insert('endorse_campaign_logs', $dt);
            // $id_parent = $this->db->insert_id();
        }

        $dt_tmp = array();
        foreach ($dtt as $kt => $vt) {
            $dt_tmp[$kt] = strval($vt);
        }
        $dtt = $dt_tmp;

        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
        $dtt['updated_by'] = strval($user['id']);
        $this->db->update('endorse_campaign', $dtt, array('id' => $v['id']));
    }

    function cronjob_endorse_campaign()
    {
        $monitor = $this->cron_monitor_start('cronjob_endorse_campaign', array(
            'mode' => isset($_GET['mode']) ? strval($_GET['mode']) : '',
        ));


        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Acneno System endorse campaign cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                $this->cron_monitor_finish($monitor, array(
                    'status' => 'skipped',
                    'processed_count' => 0,
                    'queue_count' => 0,
                    'target_time' => $target,
                    'reason' => 'before_target_time',
                ));
                die;
            }
        }
        $today = DATE("Y-m-d");
        $today = DATE('Y-m-d', strtotime($today . " -1 days"));
        $todayy = $today;

        $list = $this->mymodel->selectWithQuery("SELECT * FROM endorse_campaign WHERE status = 'Aktif' LIMIT 10");

        foreach ($list as $kl => $vl) {
            $id_campaign = $vl['id'];

            $id_parent = $vl['id'];
            $this->update_endorse_parent($id_parent, $vl);
        }



        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data endorse campaign yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        $this->cron_monitor_finish($monitor, array(
            'status' => 'ok',
            'processed_count' => count($list),
            'queue_count' => count($list),
            'sync_date' => $todayy,
        ));
        die;
    }
    public function webhook()
    {
        date_default_timezone_set('Asia/Jakarta');
        header('Content-Type: application/json; charset=utf-8');
        $dt['marketplace'] = strval($_GET['marketplace']);
        $dt['get'] = json_encode($_GET, true);
        $dt['post'] = json_encode($_POST, true);
        $dt['input'] = file_get_contents("php://input");
        $dt['key'] = strval($_SERVER['HTTP_X_API_KEY']);
        $dt['method'] = strval($_SERVER['REQUEST_METHOD']);
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['is_live'] = 'true';

        $json = json_decode($dt['input'], true);

        $order_id = $json['data']['order_id'];

        if ($order_id == "") {
            $order_id = $json['data']['ordersn'];
        }
        if ($order_id == "") {
            $order_id = $json['data']['content']['content']['source_content'];
        }
        if ($order_id == "") {
            $order_id = $json['data']['trade_order_id'];
        }

        $shop_id = $json['shop_id'];
        if ($shop_id == "") {
            $shop_id = $json['seller_id'];
        }

        $dt['order_id'] = strval($order_id);
        $dt['shop_id'] = strval($shop_id);
        $json = array();
        if ($dt['order_id']) {
            $this->db->insert('webhook', $dt);

            // Step 2: Automatically trigger detail sync to populate full order data
            // Call webhook refresh to populate order details (customer, products, payment info)
            // Return full JSON response with sync data
            $marketplace = strval($dt['marketplace']);
            if ($order_id && $shop_id) {
                try {
                    // Temporarily set $_GET parameters for marketplace_order_detail function
                    $_GET['marketplace'] = $marketplace;
                    $_GET['order_id'] = $order_id;
                    $_GET['shop_id'] = $shop_id;
                    $_GET['mode'] = 'webhook';

                    // MARKETPLACE TAKEDOWN (2026-06-22): marketplace features are
                    // currently unused. The inline marketplace_order_detail() made an
                    // external marketplace API call (CURLOPT_TIMEOUT=0) on the request
                    // path, blocking an Apache worker per webhook and spiking CPU /
                    // exhausting the worker pool during inbound webhook bursts. The raw
                    // payload is still stored above (audit), so processing can be
                    // re-enabled later — ideally async via a drain cron
                    // (see docs/2026-06-22-webhook-and-cron-audit.md). For now, fall
                    // through to the standard 200 ACK below.
                    // $this->marketplace_order_detail();
                } catch (Exception $e) {
                    // Log error but still return success webhook response
                    error_log("Webhook order sync error: " . $e->getMessage());

                    // Return standard webhook response
                    $dtt = array();
                    $dtt['order_id'] = strval($order_id);
                    $dtt['shop_id'] = strval($shop_id);
                    $dtt['marketplace'] = strval($dt['marketplace']);
                    $html = array();
                    $html['status'] = true;
                    $html['data'] = $dtt;
                    $html['msg'] = "Acneno System webhook live access has been successful!";
                    echo json_encode($html, true);
                    die;
                }
            }

            $dtt = array();
            $dtt['order_id'] = strval($order_id);
            $dtt['shop_id'] = strval($shop_id);
            $dtt['marketplace'] = strval($dt['marketplace']);
            $html = array();
            $html['status'] = true;
            $html['data'] = $dtt;
            $html['msg'] = "Acneno System webhook live access has been successful!";
            echo json_encode($html, true);
            die;
        } else {
            $html = array();
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = "Acneno System webhook live access has been unsuccessful!";
            echo json_encode($html, true);
            die;
        }
    }

    function marketplace_order_download()
    {


        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-d');
            $start_date = DATE('Y-m-d', strtotime($start_date . " -31 days"));
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];
        $keyword_category = $_GET['keyword_category'];

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role = '3' 
        ORDER BY full_name ASC
        ");

        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif'
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");

        $data['brands'] = $query;

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        if ($id) {
            $qry .= " AND customer = '$id' ";
        }

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }



        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }
        $ekspedisi = $_GET['ekspedisi'];
        if ($ekspedisi) {
            $qry .= " AND shipping = '$ekspedisi' ";
        }
        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND cs = '$cs' ";
        }

        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') ";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND dana_pencairan > 0 AND is_disbursement > 0 ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else {
                $qry .= " AND order_status = '$order_status' ";
            }
        }

        if ($keyword) {
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND c_username LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Pelanggan") {
                $qry .= " AND customer_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Pelanggan") {
                $qry .= " AND phone LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Resi") {
                $qry .= " AND awb_number LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Produk") {
                $qry .= " AND pesanan LIKE '%$keyword%' ";
            }
        }

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        }


        $filename = 'ORDER.';
        if ($marketplace) {
            $filename .= $marketplace . '.';
        }
        $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);


        $user = $_SESSION['user'];
        $dt = array();
        $dt['title'] = $filename;
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = strval($user['id']);
        $dt['param'] = $this->template->get_param();
        // print_r($dtc);die;
        $this->db->insert('download_file', $dt);
        $id = $this->db->insert_id();

        // $title .= $filename . '.' . $id . '';
        $filename .= '.' . $id . '.xlsx';

        // Set the file path where the spreadsheet will be saved
        $file_path = str_replace('public/', '', FCPATH . 'assets/webfile/excel/') . $filename;


        $dt = array();
        $dt['title'] = $filename;
        $dt['file'] = $file_path;

        $this->db->update('download_file', $dt, array('id' => $id));

        $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction
    WHERE $qry 
    -- AND order_status NOT IN ('CANCELLED','IN_CANCEL') 
    AND type_sub = 'POS' 
    ORDER BY date DESC, id DESC
    ");
        $data['data'] = $query;
        $this->spreadsheet = new Spreadsheet();

        $this->spreadsheet->getProperties()
            ->setCreator('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setLastModifiedBy('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setTitle('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setSubject('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setDescription('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setKeywords('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date));



        $style_col = array(
            'font' => array('bold' => true),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            ),
            'fill' => array(
                'type' => Fill::FILL_SOLID,
                'color' => array('rgb' => 'aeb5bc')
            ),
        );

        $style_row = array(
            'alignment' => array(
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            )
        );


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product
    ORDER BY brand ASC, sub_name ASC
    ");

        $data['product'] = $query;


        $data['header'] = array();

        $header_1 = array(
            "ID",
            "TGL ORDER",
            "TGL RTS",
            "ORDER ID",
            "BRAND",
            "KET",
            "KODE CS",
            "CB/CL",
            "NAMA",
            "NO HP",
            "USERNAME",
            "ALAMAT",
            "KAB",
            "PROV",
            "PESANAN",
        );

        $header_2 = array();

        foreach ($data['product'] as $k => $v) {
            $header_2[] = strtoupper($v['sub_name']);
        }

        $header_3 = array(
            "OMSET KOTOR",
            "DISKON & VOUCHER PENJUAL",
            "BIAYA LAINNYA",
            "OMSET BERSIH",
            "MARKETPLACE FEE",
            "AFFILIATE FEE",
            "TOTAL PENCAIRAN DANA",
            "IS_CAIR",
            "RETURN",
            "JENIS PEMBAYARAN",
            "JUMLAH",
            "TANGGAL TF",
            "TANGGAL CEK",
            "ACC",
            "EKSPEDISI",
            "NO RESI",
            "ALAMAT",
            "PROV",
            "KAB",
            "KEC",
            "CATATAN",
            "STATUS ORDER",
            // "IS_MANUAL",
        );

        // $data['header'] = array_merge($header_1, $header_2, $header_3);


        $body_1 = array(
            "id",
            "date",
            "rts_at",
            "order_id",
            "brand",
            "marketplace",
            "cs",
            "cb_cl",
            "customer_text",
            "phone",
            "c_username",
            "address",
            "city_text",
            "province_text",
            "pesanan",

        );

        $body_2 = array();

        foreach ($data['product'] as $k => $v) {
            $body_2[] = $v['id'];
        }

        $body_3  = array(
            "omset_kotor",
            "diskon_penjual",
            "biaya_lainnya",
            "omset_bersih",
            "marketplace_fee",
            "komisi_afiliasi",
            "dana_pencairan",
            "is_disbursement",
            "return",
            "payment_type",
            "dibayar",
            "pay_at",
            "check_at",
            "acc",
            "shipping",
            "awb_number",
            "address",
            "province_text",
            "city_text",
            "subdistrict_text",
            "desc",
            "order_status",
            // "is_manual",
        );

        $data['body'] = array_merge($body_1, $body_2, $body_3);



        $i = 0;
        foreach ($header_1 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $this->spreadsheet
                ->getActiveSheet()
                ->getStyle($code)
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN);
            $i++;
        }

        foreach ($header_2 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('ffff00');
            $this->spreadsheet
                ->getActiveSheet()
                ->getStyle($code)
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN);
            $i++;
        }

        foreach ($header_3 as $k => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $this->spreadsheet
                ->getActiveSheet()
                ->getStyle($code)
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN);
            $i++;
        }

        $column = 2;
        foreach ($data['data'] as $k => $v) {
            //             $index = 2;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['date']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['order_id']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['brand']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['marketplace']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['cs']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['cb_cl']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['customer_text']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['phone']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['c_username']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['address']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['city_text']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['province_text']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $list = json_decode($v['pesanan'],true) ;
            //             $v['pesanan'] = '';
            //                     foreach($list as $kk=>$vv){
            //                         $v['pesanan'] .= $vv['qty']."x ".$vv['item_name']."
            // ";
            //                     }
            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['pesanan']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;
            // break;
            $v['id'] = '';
            $index = 1;
            foreach ($body_1 as $k2 => $v2) {
                if ($v2 == "order_id") {
                    $v[$v2] = " " . $v[$v2];
                }
                if ($v2 == "phone") {
                    $v[$v2] = " " . $v[$v2];
                }
                if ($v2 == "pesanan") {
                    $list = json_decode($v[$v2], true);
                    $v[$v2] = '';
                    foreach ($list as $kk => $vv) {
                        $v[$v2] .= $vv['qty'] . "x " . $vv['item_name'] . "
";
                    }
                }
                $index_alpha = $this->template->get_name_from_number($index);
                $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                $this->spreadsheet->getActiveSheet()
                    ->getStyle($index_alpha . $column)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $index++;
            }

            $json = json_decode($v['json'], true);

            foreach ($body_2 as $k2 => $v2) {
                $val = $json[$v2]['qty'];
                $index_alpha = $this->template->get_name_from_number($index);
                $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $val);
                $this->spreadsheet->getActiveSheet()
                    ->getStyle($index_alpha . $column)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $index++;
            }
            foreach ($body_3 as $k2 => $v2) {
                $index_alpha = $this->template->get_name_from_number($index);
                $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                $this->spreadsheet->getActiveSheet()
                    ->getStyle($index_alpha . $column)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $index++;
            }

            $column++;
        }

        $sheet = $this->spreadsheet->getActiveSheet();
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $writer = new Xlsx($this->spreadsheet);

        $writer->save($file_path);

        if (file_exists($file_path)) {
            echo "File saved successfully.";
            $dt = array();
            $dt['updated_at'] = date("Y-m-d H:i:s");
            $this->db->update('download_file', $dt, array('id' => $id));
        } else {
            echo "Error saving the file.";
        }
    }

    public function get_handover_time_slots_by_package()
    {
        header('Content-Type: application/json');

        $transaction_id = $_POST['transaction_id'] ?? '';
        $shop_id = $_POST['shop_id'] ?? '';

        $package_id = '';
        if ($transaction_id) {
            $transaction_row = $this->mymodel->selectDataOne('transaction', ['order_id' => $transaction_id]);
            if ($transaction_row) {
                $package_id = $transaction_row['package_id'];
            }
        }
        if (!$package_id || !$shop_id) {
            echo json_encode(['status' => false, 'message' => 'package_id dan shop_id wajib diisi']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $config['app_key'] ?? '';
        $access_token = $config['access_token'] ?? '';
        $shop_cipher  = $config['shop']['cipher'] ?? '';
        $app_secret   = $this->app_secret_tiktok;
        $shop_id_val  = $config['shop']['id'] ?? $shop_id;

        if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
            echo json_encode(['status' => false, 'message' => 'Config tidak lengkap']);
            return;
        }

        $endpoint_path = '/fulfillment/202309/packages/' . $package_id . '/handover_time_slots';

        $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path
            . '?access_token=' . rawurlencode($access_token)
            . '&app_key='      . rawurlencode($app_key)
            . '&shop_cipher='  . rawurlencode($shop_cipher)
            . '&shop_id='      . rawurlencode($shop_id_val)
            . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

        $urlParts  = parse_url($request_url);
        $paramGET  = [];
        parse_str($urlParts['query'], $paramGET);

        $timest = time();
        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $paramGET,
            'post'   => '',
            'url'    => $request_url
        ];

        $sign = $this->tiktok_signature_generator($pr);

        $request_url_signed = str_replace('{{sign}}', $sign, $request_url);
        $request_url_signed = str_replace('{{timestamp}}', $timest, $request_url_signed);

        // Eksekusi request
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $request_url_signed,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            $this->report_marketplace_curl_error('tts_get_shipping_providers', $curl_error, array(
                'shop_id' => $shop_id ?? null,
            ));
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $response_json = json_decode($response_body, true);

        if (isset($response_json['code']) && $response_json['code'] == 0) {
            echo json_encode([
                'status' => true,
                'data' => $response_json['data'] ?? [],
                'message' => 'Berhasil mengambil time slots'
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mengambil time slots: ' . ($response_json['message'] ?? 'Unknown error'),
                'raw' => $response_json
            ]);
        }
    }



    public function tts_ship_packages_bulk()
    {
        header('Content-Type: application/json');

        $transaction_ids_input = $_POST['transaction_ids'] ?? [];
        if (is_string($transaction_ids_input)) {
            $transaction_ids = array_filter(array_map('trim', explode(',', $transaction_ids_input)));
        } else {
            $transaction_ids = (array) $transaction_ids_input;
        }

        if (empty($transaction_ids)) {
            echo json_encode(['status' => false, 'message' => 'transaction_ids wajib (array atau CSV)']);
            return;
        }

        $handover_type = 'PICKUP';
        $pickup_start_time = null;
        $pickup_end_time = null;

        if ($handover_type === 'PICKUP') {
            $pickup_start_time_input = $_POST['pickup_start_time'] ?? '';
            $pickup_end_time_input = $_POST['pickup_end_time'] ?? '';

            if ($pickup_start_time_input === '' || $pickup_end_time_input === '') {
                echo json_encode(['status' => false, 'message' => 'pickup_start_time dan pickup_end_time wajib diisi']);
                return;
            }

            if (!ctype_digit((string)$pickup_start_time_input) || !ctype_digit((string)$pickup_end_time_input)) {
                echo json_encode(['status' => false, 'message' => 'pickup_start_time dan pickup_end_time harus berupa angka detik UNIX']);
                return;
            }

            $pickup_start_time = (int)$pickup_start_time_input;
            $pickup_end_time = (int)$pickup_end_time_input;

            if ($pickup_start_time >= $pickup_end_time) {
                echo json_encode(['status' => false, 'message' => 'pickup_end_time harus lebih besar dari pickup_start_time']);
                return;
            }
        }

        // 1. AMBIL SEMUA DATA TRANSAKSI SEKALIGUS DENGAN JOIN
        $placeholders = implode(',', array_fill(0, count($transaction_ids), '?'));
        $sql = "SELECT t.order_id, t.package_id, t.shop_id, mc.val as config_val 
                FROM transaction t 
                LEFT JOIN marketplace_config mc ON t.shop_id = mc.shop_id 
                WHERE t.order_id IN ($placeholders)";

        $transactions = $this->db->query($sql, $transaction_ids)->result_array();

        // Kelompokkan transaksi berdasarkan shop_id dan buat lookup
        $transactions_by_shop = [];
        $transaction_lookup = [];
        $missing_transactions = [];

        foreach ($transaction_ids as $tx_id) {
            $found = false;
            foreach ($transactions as $tx) {
                if ($tx['order_id'] === $tx_id) {
                    $found = true;
                    $shop_id = $tx['shop_id'];
                    if (!isset($transactions_by_shop[$shop_id])) {
                        $transactions_by_shop[$shop_id] = [
                            'transactions' => [],
                            'config_val' => $tx['config_val']
                        ];
                    }
                    $transactions_by_shop[$shop_id]['transactions'][] = $tx;
                    $transaction_lookup[$tx_id] = $tx;
                    break;
                }
            }
            if (!$found) {
                $missing_transactions[] = $tx_id;
            }
        }

        $results = [];

        // 2. PROSES SETIAP SHOP DENGAN BATCHING (MAKSIMAL 50 PER REQUEST)
        $successful_updates = [];

        foreach ($transactions_by_shop as $shop_id => $shop_data) {
            $config_val = json_decode($shop_data['config_val'] ?? '{}', true);

            if (empty($config_val)) {
                foreach ($shop_data['transactions'] as $tx) {
                    $results[] = [
                        'transaction_id' => $tx['order_id'],
                        'status' => false,
                        'message' => 'Config toko tidak ditemukan'
                    ];
                }
                continue;
            }

            $app_key = $config_val['app_key'] ?? '';
            $access_token = $config_val['access_token'] ?? '';
            $shop_cipher = $config_val['shop']['cipher'] ?? '';
            $app_secret = $this->app_secret_tiktok;
            $shop_id_val = $config_val['shop']['id'] ?? $shop_id;

            if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
                foreach ($shop_data['transactions'] as $tx) {
                    $results[] = [
                        'transaction_id' => $tx['order_id'],
                        'status' => false,
                        'message' => 'Config tidak lengkap'
                    ];
                }
                continue;
            }

            // Bagi transactions menjadi chunk maksimal 50
            $transaction_chunks = array_chunk($shop_data['transactions'], 50);

            foreach ($transaction_chunks as $chunk_index => $transaction_chunk) {
                // Siapkan packages data untuk bulk request (maksimal 50)
                $packages_data = [];
                $package_to_transaction = [];

                foreach ($transaction_chunk as $tx) {
                    $package_id = $tx['package_id'] ?? '';
                    if (!$package_id) {
                        $results[] = [
                            'transaction_id' => $tx['order_id'],
                            'status' => false,
                            'message' => 'package_id kosong'
                        ];
                        continue;
                    }

                    $package_data = [
                        'id' => $package_id,
                        'handover_method' => $handover_type,
                    ];

                    if ($handover_type === 'PICKUP') {
                        $package_data['pickup_slot'] = [
                            'start_time' => (int)$pickup_start_time,
                            'end_time' => (int)$pickup_end_time
                        ];
                    }

                    $packages_data[] = $package_data;
                    $package_to_transaction[$package_id] = $tx['order_id'];
                }

                if (empty($packages_data)) {
                    continue;
                }

                // Persiapkan request untuk chunk ini
                $endpoint_path = '/fulfillment/202309/packages/ship';
                $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path
                    . '?access_token=' . rawurlencode($access_token)
                    . '&app_key='      . rawurlencode($app_key)
                    . '&shop_cipher='  . rawurlencode($shop_cipher)
                    . '&shop_id='      . rawurlencode($shop_id_val)
                    . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                $request_body_json = json_encode([
                    'packages' => $packages_data
                ], JSON_UNESCAPED_SLASHES);

                $urlParts  = parse_url($request_url);
                $paramGET  = [];
                parse_str($urlParts['query'], $paramGET);

                $timest = time();
                $pr = [
                    'secret' => $app_secret,
                    'timest' => $timest,
                    'get'    => $paramGET,
                    'post'   => $request_body_json,
                    'url'    => $request_url
                ];

                $sign = $this->tiktok_signature_generator($pr);

                $request_url_signed = str_replace(['{{sign}}', '{{timestamp}}'], [$sign, $timest], $request_url);

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL            => $request_url_signed,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => '',
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_TIMEOUT        => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => 'POST',
                    CURLOPT_POSTFIELDS     => $request_body_json,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'x-tts-access-token: ' . $access_token
                    ],
                ]);

                $response_body = curl_exec($curl);
                $curl_error = curl_error($curl);
                $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if ($curl_error) {
                    $this->report_marketplace_curl_error('tts_ship_packages_bulk', $curl_error, array(
                        'shop_id' => $shop_id,
                        'chunk_size' => count($transaction_chunk),
                    ));
                    foreach ($transaction_chunk as $tx) {
                        $results[] = [
                            'transaction_id' => $tx['order_id'],
                            'status' => false,
                            'message' => 'CURL Error: ' . $curl_error
                        ];
                    }
                    continue;
                }

                $response_json = json_decode($response_body, true);

                if (isset($response_json['code']) && $response_json['code'] == 0) {
                    // Semua package dalam chunk ini berhasil
                    foreach ($transaction_chunk as $tx) {
                        $successful_updates[] = $tx['order_id'];
                        $results[] = [
                            'transaction_id' => $tx['order_id'],
                            'status' => true,
                            'message' => 'Ship Package sukses',
                            'package_id' => $tx['package_id'],
                            'raw' => $response_json
                        ];
                    }
                } else {
                    $error_message = $response_json['message'] ?? 'Unknown error';

                    if (isset($response_json['data']['failed_packages'])) {
                        $failed_packages = $response_json['data']['failed_packages'];
                        $success_packages = $response_json['data']['success_packages'] ?? [];

                        // Process failed packages
                        foreach ($failed_packages as $failed_pkg) {
                            $package_id = $failed_pkg['package_id'] ?? '';
                            if (isset($package_to_transaction[$package_id])) {
                                $transaction_id = $package_to_transaction[$package_id];
                                $results[] = [
                                    'transaction_id' => $transaction_id,
                                    'status' => false,
                                    'message' => 'Ship Package gagal: ' . ($failed_pkg['message'] ?? $error_message),
                                    'package_id' => $package_id,
                                    'raw' => $response_json
                                ];
                            }
                        }

                        // Process success packages
                        foreach ($success_packages as $success_pkg) {
                            $package_id = $success_pkg['package_id'] ?? '';
                            if (isset($package_to_transaction[$package_id])) {
                                $transaction_id = $package_to_transaction[$package_id];
                                $successful_updates[] = $transaction_id;
                                $results[] = [
                                    'transaction_id' => $transaction_id,
                                    'status' => true,
                                    'message' => 'Ship Package sukses',
                                    'package_id' => $package_id,
                                    'raw' => $response_json
                                ];
                            }
                        }
                    } else {
                        // Jika tidak ada detail per package, anggap semua dalam chunk gagal
                        foreach ($transaction_chunk as $tx) {
                            $results[] = [
                                'transaction_id' => $tx['order_id'],
                                'status' => false,
                                'message' => 'Ship Package gagal: ' . $error_message,
                                'package_id' => $tx['package_id'],
                                'raw' => $response_json
                            ];
                        }
                    }
                }

                // Tambahkan delay kecil antara request untuk menghindari rate limiting
                if (count($transaction_chunks) > 1 && $chunk_index < count($transaction_chunks) - 1) {
                    usleep(500000); // 0.5 detik delay
                }
            }
        }

        // 3. BATCH UPDATE UNTUK SEMUA TRANSAKSI YANG BERHASIL
        if (!empty($successful_updates)) {
            $placeholders = implode(',', array_fill(0, count($successful_updates), '?'));
            $update_sql = "UPDATE transaction SET rts_at = ?, order_status = ? WHERE order_id IN ($placeholders)";
            $params = array_merge([date('Y-m-d H:i:s'), 'PROCESSED'], $successful_updates);
            $this->db->query($update_sql, $params);
        }

        // 4. HANDLE TRANSAKSI YANG TIDAK DITEMUKAN
        foreach ($missing_transactions as $tx_id) {
            $results[] = [
                'transaction_id' => $tx_id,
                'status' => false,
                'message' => 'Transaksi tidak ditemukan'
            ];
        }

        echo json_encode(['status' => true, 'results' => $results]);
    }



    public function tts_get_shipping_documents_bulk()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Content-Type: application/json');

        // Handle preflight request
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }

        $transaction_ids_input = $_POST['transaction_ids'] ?? [];
        if (is_string($transaction_ids_input)) {
            $transaction_ids = array_filter(array_map('trim', explode(',', $transaction_ids_input)));
        } else {
            $transaction_ids = (array) $transaction_ids_input;
        }

        if (empty($transaction_ids)) {
            echo json_encode(['status' => false, 'message' => 'transaction_ids wajib (array atau CSV)']);
            return;
        }

        // 1. AMBIL SEMUA DATA TRANSAKSI SEKALIGUS
        $placeholders = implode(',', array_fill(0, count($transaction_ids), '?'));
        $sql = "SELECT t.order_id, t.package_id, t.shop_id, mc.val as config_val 
                FROM transaction t 
                LEFT JOIN marketplace_config mc ON t.shop_id = mc.shop_id 
                WHERE t.order_id IN ($placeholders)";

        $transactions = $this->db->query($sql, $transaction_ids)->result_array();

        // Group by shop_id untuk optimasi lebih lanjut
        $transactions_by_shop = [];
        $transaction_lookup = [];

        foreach ($transactions as $tx) {
            $transactions_by_shop[$tx['shop_id']][] = $tx;
            $transaction_lookup[$tx['order_id']] = $tx;
        }

        $results = [];
        $doc_type = strtoupper('SHIPPING_LABEL_PICTURE');
        $label_size = 'A6';

        foreach ($transactions_by_shop as $shop_id => $shop_transactions) {
            $first_tx = $shop_transactions[0];
            $config_val = json_decode($first_tx['config_val'] ?? '{}', true);

            if (empty($config_val)) {
                foreach ($shop_transactions as $tx) {
                    $results[] = [
                        'transaction_id' => $tx['order_id'],
                        'status' => false,
                        'message' => 'Config toko tidak ditemukan'
                    ];
                }
                continue;
            }

            $app_key = $config_val['app_key'] ?? '';
            $access_token = $config_val['access_token'] ?? '';
            $shop_cipher = $config_val['shop']['cipher'] ?? '';
            $app_secret = $this->app_secret_tiktok;
            $shop_id_val = $config_val['shop']['id'] ?? $shop_id;

            if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
                foreach ($shop_transactions as $tx) {
                    $results[] = [
                        'transaction_id' => $tx['order_id'],
                        'status' => false,
                        'message' => 'Config tidak lengkap'
                    ];
                }
                continue;
            }

            // OPTIMASI: Gunakan batch size lebih besar dengan connection limit
            $batch_size = 40; // Increased batch size
            $transaction_batches = array_chunk($shop_transactions, $batch_size);

            $successful_updates = [];

            foreach ($transaction_batches as $batch_index => $batch_transactions) {
                $multi_curl = curl_multi_init();
                $curl_handlers = [];
                $package_to_tx = [];

                // OPTIMASI: Set konfigurasi multi curl untuk performa lebih baik
                curl_multi_setopt($multi_curl, CURLMOPT_MAXCONNECTS, 30);
                curl_multi_setopt($multi_curl, CURLMOPT_MAX_HOST_CONNECTIONS, 10);

                foreach ($batch_transactions as $tx) {
                    $package_id = $tx['package_id'] ?? '';
                    if (!$package_id) {
                        $results[] = [
                            'transaction_id' => $tx['order_id'],
                            'status' => false,
                            'message' => 'package_id kosong'
                        ];
                        continue;
                    }

                    $endpoint_path = '/fulfillment/202309/packages/' . $package_id . '/shipping_documents';
                    $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path
                        . '?app_key=' . rawurlencode($app_key)
                        . '&shop_cipher=' . rawurlencode($shop_cipher)
                        . '&document_type=' . rawurlencode($doc_type)
                        . '&document_size=' . rawurlencode($label_size)
                        . '&sign={{sign}}'
                        . '&timestamp={{timestamp}}'
                        . '&version=202309';

                    $urlParts = parse_url($request_url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);

                    $timest = time();
                    $pr = [
                        'secret' => $app_secret,
                        'timest' => $timest,
                        'get' => $paramGET,
                        'post' => '',
                        'url' => $request_url
                    ];

                    $sign = $this->tiktok_signature_generator($pr);
                    $request_url_signed = str_replace(['{{sign}}', '{{timestamp}}'], [$sign, $timest], $request_url);

                    $curl = curl_init();

                    // OPTIMASI: Kurang timeout dan optimasi curl options
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $request_url_signed,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 5, // Reduced
                        CURLOPT_TIMEOUT => 30,  // Reduced from 60 to 30
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json',
                            'x-tts-access-token: ' . $access_token
                        ],
                        CURLOPT_SSL_VERIFYPEER => false, // OPTIONAL: untuk percepatan
                        CURLOPT_SSL_VERIFYHOST => false, // OPTIONAL: untuk percepatan
                    ]);

                    $curl_handlers[$package_id] = $curl;
                    $package_to_tx[$package_id] = $tx['order_id'];
                    curl_multi_add_handle($multi_curl, $curl);
                }

                // OPTIMASI: Eksekusi multi curl dengan timeout lebih agresif
                $running = null;
                $start_time = microtime(true);

                do {
                    $status = curl_multi_exec($multi_curl, $running);
                    if ($running) {
                        // Kurangi timeout untuk respons lebih cepat
                        curl_multi_select($multi_curl, 0.05); // Reduced from 0.1 to 0.05
                    }

                    // Timeout safety: maksimal 15 detik per batch
                    if ((microtime(true) - $start_time) > 15) {
                        break;
                    }
                } while ($running > 0);

                // Process responses untuk batch saat ini
                foreach ($curl_handlers as $package_id => $curl) {
                    $tx_id = $package_to_tx[$package_id];
                    $response_body = curl_multi_getcontent($curl);
                    $curl_error = curl_error($curl);
                    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                    if ($curl_error) {
                        $this->report_marketplace_curl_error('tts_get_shipping_documents_bulk', $curl_error, array(
                            'transaction_id' => $tx_id,
                            'package_id' => $package_id,
                        ));
                        $results[] = ['transaction_id' => $tx_id, 'status' => false, 'message' => $curl_error];
                        continue;
                    }

                    $response_json = json_decode($response_body, true);

                    // Handle rate limiting
                    if ($http_code === 429 || (isset($response_json['code']) && $response_json['code'] == 36009002)) {
                        $results[] = [
                            'transaction_id' => $tx_id,
                            'status' => false,
                            'message' => 'Rate limit exceeded',
                        ];
                        continue;
                    }

                    if (isset($response_json['code']) && $response_json['code'] != 0) {
                        $results[] = [
                            'transaction_id' => $tx_id,
                            'status' => false,
                            'message' => 'Get Shipping Document gagal: ' . ($response_json['message'] ?? 'Unknown'),
                            'raw' => $response_json
                        ];
                        continue;
                    }

                    $document_urls = $response_json['data']['document_urls'] ?? $response_json['data'] ?? [];

                    $successful_updates[] = $tx_id;

                    $results[] = [
                        'transaction_id' => $tx_id,
                        'status' => true,
                        'message' => 'OK',
                        'package_id' => $package_id,
                        'document_urls' => $document_urls
                    ];

                    curl_multi_remove_handle($multi_curl, $curl);
                    curl_close($curl);
                }

                curl_multi_close($multi_curl);

                // OPTIMASI: Kurangi delay antara batch
                if (count($transaction_batches) > 1 && $batch_index < count($transaction_batches) - 1) {
                    // Delay minimal antara batch
                    usleep(100000); // Hanya 0.1 detik delay antara batch
                }
            }

            $today = date('Y-m-d H:i:s');

            if (!empty($successful_updates)) {
                $data = array(
                    'print_at' => $today
                );

                $this->db->where_in('order_id', $successful_updates);
                $this->db->update('transaction', $data);
            }
        }

        foreach ($transaction_ids as $tx_id) {
            if (!isset($transaction_lookup[$tx_id])) {
                $results[] = ['transaction_id' => $tx_id, 'status' => false, 'message' => 'Transaksi tidak ditemukan'];
            }
        }

        echo json_encode(['status' => true, 'results' => $results]);
    }



    public function get_shop_products_performance()
    {
        header('Content-Type: application/json');

        $shop_id = $_GET['shop_id'] ?? '';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';

        if (!$shop_id || !$start_date || !$end_date) {
            echo json_encode(['status' => false, 'message' => 'shop_id, start_date, dan end_date wajib diisi']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $config['app_key'] ?? '';
        $access_token = $config['access_token'] ?? '';
        $shop_cipher  = $config['shop']['cipher'] ?? '';
        $app_secret   = $this->app_secret_tiktok;
        $shop_id_val  = $config['shop']['id'] ?? $shop_id;

        if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
            echo json_encode(['status' => false, 'message' => 'Config tidak lengkap']);
            return;
        }

        $timest = time();

        $endpoint_path = '/analytics/202405/shop/performance';

        $params = [
            'sort_order' => 'DESC',
            'sort_field' => 'gmv',
            'currency' => 'LOCAL',
            'page_size' => 10,
            'start_date_ge' => $start_date,
            'end_date_lt' => $end_date,
            'app_key' => $app_key,
            'shop_cipher' => $shop_cipher,
            'shop_id' => $shop_id_val,
            'timestamp' => $timest,
        ];

        if (!empty($_GET['page_token'])) {
            $params['page_token'] = $_GET['page_token'];
        }

        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $params,
            'post'   => '',
            'url'    => 'https://open-api.tiktokglobalshop.com' . $endpoint_path
        ];

        $sign = $this->tiktok_signature_generator($pr);

        $params['sign'] = $sign;

        $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path . '?' . http_build_query($params);

        // Eksekusi request
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            $this->report_marketplace_curl_error('get_shop_products_performance', $curl_error, array(
                'shop_id' => $shop_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
            ));
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $response_json = json_decode($response_body, true);

        if (isset($response_json['code']) && $response_json['code'] == 0) {
            echo json_encode([
                'status' => true,
                'data' => $response_json['data'] ?? [],
                'message' => 'Berhasil mengambil data performa produk'
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mengambil data performa produk: ' . ($response_json['message'] ?? 'Unknown error'),
                'raw' => $response_json
            ]);
        }
    }



    public function get_affiliate_performance()
    {
        header('Content-Type: application/json');

        $shop_id = $_GET['shop_id'] ?? '';
        $creator_id = $_GET['creator_id'] ?? '';

        if (!$shop_id || !$creator_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id dan creator_id wajib diisi']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $config['app_key'] ?? '';
        $access_token = $config['access_token'] ?? '';
        $shop_cipher  = $config['shop']['cipher'] ?? '';
        $app_secret   = $this->app_secret_tiktok;

        if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
            echo json_encode(['status' => false, 'message' => 'Config tidak lengkap']);
            return;
        }

        $timest = time();
        $endpoint_path = '/affiliate_seller/202508/marketplace_creators/' . rawurlencode($creator_id);

        $params = [
            'app_key' => $app_key,
            'shop_cipher' => $shop_cipher,
            'timestamp' => $timest,
        ];

        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $params,
            'post'   => '',
            'url'    => 'https://open-api.tiktokglobalshop.com' . $endpoint_path
        ];

        $sign = $this->tiktok_signature_generator($pr);
        $params['sign'] = $sign;

        $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path . '?' . http_build_query($params);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            $this->report_marketplace_curl_error('get_affiliate_performance', $curl_error, array(
                'shop_id' => $shop_id,
                'creator_id' => $creator_id,
            ));
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $response_json = json_decode($response_body, true);

        if (isset($response_json['code']) && $response_json['code'] == 0) {
            echo json_encode([
                'status' => true,
                'data' => $response_json['data'] ?? [],
                'message' => 'Berhasil mengambil performa affiliate'
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mengambil performa affiliate: ' . ($response_json['message'] ?? 'Unknown error'),
                'raw' => $response_json
            ]);
        }
    }



    public function search_marketplace_creators()
    {
        header('Content-Type: application/json');

        $shop_id = $_GET['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib diisi']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $config['app_key'] ?? '';
        $access_token = $config['access_token'] ?? '';
        $shop_cipher  = $config['shop']['cipher'] ?? '';
        $app_secret   = $this->app_secret_tiktok;

        if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
            echo json_encode(['status' => false, 'message' => 'Config tidak lengkap']);
            return;
        }

        $raw_input = file_get_contents('php://input');
        $payload = json_decode($raw_input, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $allowed_filters = [
            'search_key',
            'keyword',
            'follower_demographics',
            'gmv_ranges',
            'units_sold_ranges',
            'category',
            'content_performance',
            'affiliate_data',
            'advanced_filters',
        ];

        $filters = [];
        foreach ($allowed_filters as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if ($value === '' || $value === null) {
                continue;
            }
            if (is_array($value) && empty($value)) {
                continue;
            }
            $filters[$key] = $value;
        }

        $timest = time();
        $endpoint_path = '/affiliate_seller/202508/marketplace_creators/search';

        $params = [
            'app_key' => $app_key,
            'shop_cipher' => $shop_cipher,
            'timestamp' => $timest,
        ];

        if (!empty($_GET['page_token'])) {
            $params['page_token'] = $_GET['page_token'];
        }
        if (isset($_GET['page_size']) && $_GET['page_size'] !== '') {
            $params['page_size'] = (int)$_GET['page_size'];
        } else {
            $params['page_size'] = 20;
        }

        $body = $filters
            ? json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '{}';

        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $params,
            'post'   => $body,
            'url'    => 'https://open-api.tiktokglobalshop.com' . $endpoint_path
        ];

        $sign = $this->tiktok_signature_generator($pr);
        $params['sign'] = $sign;

        $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path . '?' . http_build_query($params);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            $this->report_marketplace_curl_error('search_marketplace_creators', $curl_error, array(
                'shop_id' => $shop_id,
            ));
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $response_json = json_decode($response_body, true);

        if (isset($response_json['code']) && $response_json['code'] == 0) {
            echo json_encode([
                'status' => true,
                'data' => $response_json['data'] ?? [],
                'message' => 'Berhasil mengambil data marketplace creators'
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mengambil data marketplace creators: ' . ($response_json['message'] ?? 'Unknown error'),
                'raw' => $response_json,
                'http_code' => $http_code
            ]);
        }
    }



    public function get_shop_product_performance_detail()
    {
        header('Content-Type: application/json');

        $shop_id = $_GET['shop_id'] ?? '';
        $product_id = $_GET['product_id'] ?? '';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $granularity = $_GET['granularity'] ?? 'ALL';
        $currency = $_GET['currency'] ?? 'LOCAL';

        if (!$shop_id || !$product_id || !$start_date || !$end_date) {
            echo json_encode(['status' => false, 'message' => 'shop_id, product_id, start_date, dan end_date wajib diisi']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $config['app_key'] ?? '';
        $access_token = $config['access_token'] ?? '';
        $shop_cipher  = $config['shop']['cipher'] ?? '';
        $app_secret   = $this->app_secret_tiktok;
        $shop_id_val  = $config['shop']['id'] ?? $shop_id;

        if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
            echo json_encode(['status' => false, 'message' => 'Config tidak lengkap']);
            return;
        }

        $timest = time();
        $endpoint_path = '/analytics/202509/shop_products/' . $product_id . '/performance';

        $params = [
            'granularity' => $granularity,
            'currency' => $currency,
            'start_date_ge' => $start_date,
            'end_date_lt' => $end_date,
            'app_key' => $app_key,
            'shop_cipher' => $shop_cipher,
            'shop_id' => $shop_id_val,
            'timestamp' => $timest,
        ];

        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $params,
            'post'   => '',
            'url'    => 'https://open-api.tiktokglobalshop.com' . $endpoint_path
        ];

        $sign = $this->tiktok_signature_generator($pr);
        $params['sign'] = $sign;

        $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path . '?' . http_build_query($params);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            $this->report_marketplace_curl_error('get_shop_product_performance_detail', $curl_error, array(
                'shop_id' => $shop_id,
                'product_id' => $product_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
            ));
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $response_json = json_decode($response_body, true);

        if (isset($response_json['code']) && $response_json['code'] == 0) {
            echo json_encode([
                'status' => true,
                'data' => $response_json['data'] ?? [],
                'message' => 'Berhasil mengambil detail performa produk'
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mengambil detail performa produk: ' . ($response_json['message'] ?? 'Unknown error'),
                'raw' => $response_json
            ]);
        }
    }

    // =====================================================================
    // ScrapingBot Queue Cronjobs
    // =====================================================================

    /**
     * Cronjob A - Submit pending scrape jobs to ScrapingBot
     * Runs every 5 minutes
     * Picks pending queue items, POSTs to ScrapingBot, stores responseId
     */
    function cronjob_scraping_submit()
    {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->library('scrapingbot');
        $this->load->library('Threads_scraper_api');
        $this->load->model('mymodel');

        // Pick pending items ordered by priority
        $items = $this->mymodel->selectWithQuery("
            SELECT * FROM scraping_queue
            WHERE status = 'pending'
            ORDER BY priority DESC, created_at ASC
            LIMIT 20
        ");

        $submitted = 0;
        $errors = [];

        foreach ($items as $item) {
            $params = json_decode($item['scrape_url'], true);
            if (!$params) {
                $this->db->update('scraping_queue', [
                    'status'        => 'failed',
                    'error_message' => 'Invalid scrape_url JSON',
                    'completed_at'  => date('Y-m-d H:i:s'),
                ], ['id' => $item['id']]);
                $errors[] = "Item #{$item['id']}: invalid params";
                continue;
            }

            if ($item['scraper'] === 'threadsProfile') {
                $result = $this->threads_scraper_api->createAccount(strval($params['link'] ?? ''));
                $remote = is_array($result['data'] ?? null) ? $result['data'] : [];
                if (!empty($result['status']) && !empty($remote['job_id']) && !empty($remote['account']['id'])) {
                    $params['account_id'] = strval($remote['account']['id']);
                    $this->db->update('scraping_queue', [
                        'status'       => 'submitted',
                        'response_id'  => strval($remote['job_id']),
                        'scrape_url'   => json_encode($params),
                        'submitted_at' => date('Y-m-d H:i:s'),
                    ], ['id' => $item['id']]);
                    $submitted++;
                    continue;
                }
            } else {
                $result = $this->scrapingbot->startScrape($item['scraper'], $params);
            }

            if ($result['status'] && !empty($result['responseId'])) {
                $this->db->update('scraping_queue', [
                    'status'       => 'submitted',
                    'response_id'  => $result['responseId'],
                    'submitted_at' => date('Y-m-d H:i:s'),
                ], ['id' => $item['id']]);
                $submitted++;
            } else {
                $attempts = intval($item['attempts']) + 1;
                $newStatus = ($attempts >= intval($item['max_attempts'])) ? 'failed' : 'pending';

                $this->db->update('scraping_queue', [
                    'attempts'      => $attempts,
                    'status'        => $newStatus,
                    'error_message' => $result['msg'],
                    'completed_at'  => ($newStatus === 'failed') ? date('Y-m-d H:i:s') : null,
                ], ['id' => $item['id']]);
                $errors[] = "Item #{$item['id']}: " . $result['msg'];
            }
        }

        echo json_encode([
            'status'    => true,
            'submitted' => $submitted,
            'total'     => count($items),
            'errors'    => $errors,
            'msg'       => "$submitted of " . count($items) . " items submitted to ScrapingBot",
        ]);
        die;
    }

    /**
     * Cronjob B - Poll submitted scrape jobs for results
     * Runs every 1 minute
     * Checks submitted items, parses results, updates entities
     */
    function cronjob_scraping_poll()
    {
        $monitor = $this->cron_monitor_start('cronjob_scraping_poll');
        header('Content-Type: application/json; charset=utf-8');

        $this->load->library('scrapingbot');
        $this->load->library('Threads_scraper_api');
        $this->load->model('mymodel');

        // Pick submitted items that haven't exceeded max attempts
        $items = $this->mymodel->selectWithQuery("
            SELECT * FROM scraping_queue
            WHERE status = 'submitted'
            AND attempts < max_attempts
            ORDER BY submitted_at ASC
            LIMIT 25
        ");

        $completed = 0;
        $pending = 0;
        $failed = 0;
        $jobTimeout = max(60, intval(env('SOCIAL_SCRAPER_JOB_TIMEOUT_SEC', 900)));

        foreach ($items as $item) {
            if ($item['scraper'] === 'threadsProfile') {
                $params = json_decode($item['scrape_url'], true);
                $job = $this->threads_scraper_api->job(strval($item['response_id']));
                if (empty($job['status'])) {
                    $attempts = intval($item['attempts']) + 1;
                    $newStatus = ($attempts >= intval($item['max_attempts'])) ? 'failed' : 'pending';
                    $this->db->update('scraping_queue', [
                        'attempts' => $attempts, 'status' => $newStatus,
                        'error_message' => strval($job['msg'] ?? 'Gagal memeriksa job Threads.'),
                        'response_id' => $newStatus === 'pending' ? null : $item['response_id'],
                        'completed_at' => $newStatus === 'failed' ? date('Y-m-d H:i:s') : null,
                    ], ['id' => $item['id']]);
                    $failed++;
                    continue;
                }
                $remote = $job['data'];
                $remoteStatus = strval($remote['status'] ?? '');
                if (in_array($remoteStatus, ['pending', 'running'], true)) {
                    $submittedAt = strtotime(strval($item['submitted_at'] ?? '')) ?: time();
                    if ((time() - $submittedAt) < $jobTimeout) {
                        $pending++;
                        continue;
                    }
                    $attempts = intval($item['attempts']) + 1;
                    $newStatus = ($attempts >= intval($item['max_attempts'])) ? 'failed' : 'pending';
                    $this->db->update('scraping_queue', [
                        'attempts' => $attempts, 'status' => $newStatus,
                        'response_id' => $newStatus === 'pending' ? null : $item['response_id'],
                        'error_message' => 'Job Threads melewati batas waktu.',
                        'completed_at' => $newStatus === 'failed' ? date('Y-m-d H:i:s') : null,
                    ], ['id' => $item['id']]);
                    $failed++;
                    continue;
                }
                if ($remoteStatus === 'completed' && !empty($params['account_id'])) {
                    $account = $this->threads_scraper_api->account(strval($params['account_id']));
                    $posts = $this->threads_scraper_api->posts(strval($params['account_id']), 10);
                    if (!empty($account['status']) && !empty($posts['status'])) {
                        $data = ['account' => $account['data'], 'posts' => $posts['data']];
                        $this->db->update('scraping_queue', [
                            'status' => 'completed', 'result_data' => json_encode($data),
                            'completed_at' => date('Y-m-d H:i:s'), 'attempts' => intval($item['attempts']) + 1,
                        ], ['id' => $item['id']]);
                        $item['result_data'] = json_encode($data);
                        $this->template->process_scrape_result($item, $data);
                        $completed++;
                        continue;
                    }
                    $job = !empty($account['status']) ? $posts : $account;
                }

                $attempts = intval($item['attempts']) + 1;
                $newStatus = ($attempts >= intval($item['max_attempts'])) ? 'failed' : 'pending';
                $this->db->update('scraping_queue', [
                    'attempts' => $attempts, 'status' => $newStatus,
                    'response_id' => $newStatus === 'pending' ? null : $item['response_id'],
                    'error_message' => strval($remote['error'] ?? ($job['msg'] ?? 'Job Threads gagal.')),
                    'completed_at' => $newStatus === 'failed' ? date('Y-m-d H:i:s') : null,
                ], ['id' => $item['id']]);
                $failed++;
                continue;
            }

            $result = $this->scrapingbot->pollResult($item['scraper'], $item['response_id']);

            if ($result['status'] === 'success') {
                // Store result data
                $this->db->update('scraping_queue', [
                    'status'       => 'completed',
                    'result_data'  => json_encode($result['data']),
                    'completed_at' => date('Y-m-d H:i:s'),
                    'attempts'     => intval($item['attempts']) + 1,
                ], ['id' => $item['id']]);

                // Process the result and update the entity
                $item['result_data'] = json_encode($result['data']);
                $this->template->process_scrape_result($item, $result['data']);

                $completed++;

            } elseif ($result['status'] === 'pending') {
                // Still processing, increment attempts
                $this->db->update('scraping_queue', [
                    'attempts' => intval($item['attempts']) + 1,
                ], ['id' => $item['id']]);
                $pending++;

            } else {
                // Error
                $attempts = intval($item['attempts']) + 1;
                $newStatus = ($attempts >= intval($item['max_attempts'])) ? 'failed' : 'submitted';

                $this->db->update('scraping_queue', [
                    'attempts'      => $attempts,
                    'status'        => $newStatus,
                    'error_message' => $result['msg'],
                    'completed_at'  => ($newStatus === 'failed') ? date('Y-m-d H:i:s') : null,
                ], ['id' => $item['id']]);
                $failed++;
            }
        }

        echo json_encode([
            'status'    => true,
            'completed' => $completed,
            'pending'   => $pending,
            'failed'    => $failed,
            'total'     => count($items),
            'msg'       => "Poll results: $completed completed, $pending pending, $failed failed",
        ]);
        $this->cron_monitor_finish($monitor, array(
            'status' => 'ok',
            'processed_count' => count($items),
            'queue_count' => $pending,
            'completed_count' => $completed,
            'failed_count' => $failed,
        ));
        die;
    }

    /**
     * Cronjob C - Enqueue influencers/dummies needing sync
     * Runs every 30 minutes
     * Finds records where sync_at is older than threshold, inserts into queue
     */
    function cronjob_scraping_enqueue()
    {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->model('mymodel');

        $enqueued = 0;
        $skipped = 0;

        // Tier 1 (Hot): sync_at IS NULL or new records - priority 10
        // TikTok excluded — handled by cronjob_tiktok_sync
        $tier1_influencer = $this->mymodel->selectWithQuery("
            SELECT id, type, url FROM influencer
            WHERE status = 'Aktif' AND url != ''
            AND type NOT IN ('Tiktok', 'Instagram')
            AND sync_at IS NULL
            LIMIT 20
        ");
        foreach ($tier1_influencer as $row) {
            $result = $this->template->enqueue_scrape('influencer', $row['id'], $row['type'], $row['url'], 10);
            if ($result['status']) $enqueued++; else $skipped++;
        }

        $tier1_dummy = $this->mymodel->selectWithQuery("
            SELECT id, type, url FROM influencer_dummy
            WHERE status = 'Aktif' AND url != ''
            AND type NOT IN ('Tiktok', 'Instagram')
            AND sync_at IS NULL
            LIMIT 20
        ");
        foreach ($tier1_dummy as $row) {
            $result = $this->template->enqueue_scrape('influencer_dummy', $row['id'], $row['type'], $row['url'], 10);
            if ($result['status']) $enqueued++; else $skipped++;
        }

        // Tier 2 (Active): Has active endorsement campaign - every 3 days, priority 7
        $three_days_ago = date('Y-m-d', strtotime('-3 days'));
        $tier2 = $this->mymodel->selectWithQuery("
            SELECT DISTINCT i.id, i.type, i.url FROM influencer i
            INNER JOIN endorse e ON e.influencer = i.id
            INNER JOIN endorse_campaign ec ON e.id_campaign = ec.id
            WHERE i.status = 'Aktif' AND i.url != ''
            AND i.type NOT IN ('Tiktok', 'Instagram')
            AND ec.status = 'Aktif'
            AND (i.sync_at < ('$three_days_ago' + INTERVAL 1 DAY) OR i.sync_at IS NULL)
            LIMIT 20
        ");
        foreach ($tier2 as $row) {
            $result = $this->template->enqueue_scrape('influencer', $row['id'], $row['type'], $row['url'], 7);
            if ($result['status']) $enqueued++; else $skipped++;
        }

        // Tier 3 (Regular): Active influencer, synced > 7 days ago - priority 5
        $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
        $tier3_influencer = $this->mymodel->selectWithQuery("
            SELECT id, type, url FROM influencer
            WHERE status = 'Aktif' AND url != ''
            AND type NOT IN ('Tiktok', 'Instagram')
            AND sync_at < ('$seven_days_ago' + INTERVAL 1 DAY)
            LIMIT 10
        ");
        foreach ($tier3_influencer as $row) {
            $result = $this->template->enqueue_scrape('influencer', $row['id'], $row['type'], $row['url'], 5);
            if ($result['status']) $enqueued++; else $skipped++;
        }

        $tier3_dummy = $this->mymodel->selectWithQuery("
            SELECT id, type, url FROM influencer_dummy
            WHERE status = 'Aktif' AND url != ''
            AND type NOT IN ('Tiktok', 'Instagram')
            AND sync_at < ('$seven_days_ago' + INTERVAL 1 DAY)
            LIMIT 10
        ");
        foreach ($tier3_dummy as $row) {
            $result = $this->template->enqueue_scrape('influencer_dummy', $row['id'], $row['type'], $row['url'], 5);
            if ($result['status']) $enqueued++; else $skipped++;
        }

        // Tier 4 (Cold): Active but synced > 14 days ago - priority 3
        $fourteen_days_ago = date('Y-m-d', strtotime('-14 days'));
        $tier4 = $this->mymodel->selectWithQuery("
            SELECT id, type, url FROM influencer
            WHERE status = 'Aktif' AND url != ''
            AND type NOT IN ('Tiktok', 'Instagram')
            AND sync_at < ('$fourteen_days_ago' + INTERVAL 1 DAY)
            LIMIT 5
        ");
        foreach ($tier4 as $row) {
            $result = $this->template->enqueue_scrape('influencer', $row['id'], $row['type'], $row['url'], 3);
            if ($result['status']) $enqueued++; else $skipped++;
        }

        echo json_encode([
            'status'   => true,
            'enqueued' => $enqueued,
            'skipped'  => $skipped,
            'msg'      => "$enqueued records enqueued, $skipped skipped (already in queue or invalid)",
        ]);
        die;
    }

    /**
     * Compatibility cron route for synchronous supported social-profile sync.
     * Replaces ScrapingBot for TikTok and Instagram. Threads uses scraping_queue.
     * Same 4-tier priority logic, processes max 20 per run with 300ms delay
     */
    function cronjob_tiktok_sync()
    {
        header('Content-Type: application/json; charset=utf-8');

        $this->load->model('mymodel');
        $this->load->library('template');

        $synced = 0;
        $failed = 0;
        $maxPerRun = 20;
        $processed = 0;

        // Tier 1 (Hot): sync_at IS NULL or new records
        $tier1_influencer = $this->mymodel->selectWithQuery("
            SELECT id, type, url FROM influencer
            WHERE status = 'Aktif' AND url != '' AND type IN ('Tiktok', 'Instagram')
            AND sync_at IS NULL
            LIMIT 10
        ");
        foreach ($tier1_influencer as $row) {
            if ($processed >= $maxPerRun) break;
            $result = $this->template->syncSocialProfile('influencer', $row['id'], $row['type'], $row['url']);
            if ($result['status']) $synced++; else $failed++;
            $processed++;
            usleep(300000);
        }

        $tier1_dummy = $this->mymodel->selectWithQuery("
            SELECT id, type, url FROM influencer_dummy
            WHERE status = 'Aktif' AND url != '' AND type IN ('Tiktok', 'Instagram')
            AND sync_at IS NULL
            LIMIT 10
        ");
        foreach ($tier1_dummy as $row) {
            if ($processed >= $maxPerRun) break;
            $result = $this->template->syncSocialProfile('influencer_dummy', $row['id'], $row['type'], $row['url']);
            if ($result['status']) $synced++; else $failed++;
            $processed++;
            usleep(300000);
        }

        // Tier 2 (Active): Has active endorsement campaign - every 3 days
        if ($processed < $maxPerRun) {
            $three_days_ago = date('Y-m-d', strtotime('-3 days'));
            $tier2 = $this->mymodel->selectWithQuery("
                SELECT DISTINCT i.id, i.type, i.url FROM influencer i
                INNER JOIN endorse e ON e.influencer = i.id
                INNER JOIN endorse_campaign ec ON e.id_campaign = ec.id
                WHERE i.status = 'Aktif' AND i.url != '' AND i.type IN ('Tiktok', 'Instagram')
                AND ec.status = 'Aktif'
                AND (i.sync_at < ('$three_days_ago' + INTERVAL 1 DAY) OR i.sync_at IS NULL)
                LIMIT 10
            ");
            foreach ($tier2 as $row) {
                if ($processed >= $maxPerRun) break;
                $result = $this->template->syncSocialProfile('influencer', $row['id'], $row['type'], $row['url']);
                if ($result['status']) $synced++; else $failed++;
                $processed++;
                usleep(300000);
            }
        }

        // Tier 3 (Regular): Active influencer, synced > 7 days ago
        if ($processed < $maxPerRun) {
            $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
            $tier3_influencer = $this->mymodel->selectWithQuery("
                SELECT id, type, url FROM influencer
                WHERE status = 'Aktif' AND url != '' AND type IN ('Tiktok', 'Instagram')
                AND sync_at < ('$seven_days_ago' + INTERVAL 1 DAY)
                LIMIT 5
            ");
            foreach ($tier3_influencer as $row) {
                if ($processed >= $maxPerRun) break;
                $result = $this->template->syncSocialProfile('influencer', $row['id'], $row['type'], $row['url']);
                if ($result['status']) $synced++; else $failed++;
                $processed++;
                usleep(300000);
            }

            $tier3_dummy = $this->mymodel->selectWithQuery("
                SELECT id, type, url FROM influencer_dummy
                WHERE status = 'Aktif' AND url != '' AND type IN ('Tiktok', 'Instagram')
                AND sync_at < ('$seven_days_ago' + INTERVAL 1 DAY)
                LIMIT 5
            ");
            foreach ($tier3_dummy as $row) {
                if ($processed >= $maxPerRun) break;
                $result = $this->template->syncSocialProfile('influencer_dummy', $row['id'], $row['type'], $row['url']);
                if ($result['status']) $synced++; else $failed++;
                $processed++;
                usleep(300000);
            }
        }

        // Tier 4 (Cold): Active but synced > 14 days ago
        if ($processed < $maxPerRun) {
            $fourteen_days_ago = date('Y-m-d', strtotime('-14 days'));
            $tier4 = $this->mymodel->selectWithQuery("
                SELECT id, type, url FROM influencer
                WHERE status = 'Aktif' AND url != '' AND type IN ('Tiktok', 'Instagram')
                AND sync_at < ('$fourteen_days_ago' + INTERVAL 1 DAY)
                LIMIT 5
            ");
            foreach ($tier4 as $row) {
                if ($processed >= $maxPerRun) break;
                $result = $this->template->syncSocialProfile('influencer', $row['id'], $row['type'], $row['url']);
                if ($result['status']) $synced++; else $failed++;
                $processed++;
                usleep(300000);
            }
        }

        echo json_encode([
            'status'    => true,
            'synced'    => $synced,
            'failed'    => $failed,
            'processed' => $processed,
            'msg'       => "$synced synced, $failed failed out of $processed processed",
        ]);
        die;
    }

    /**
     * Cronjob: sync endorse stats for a specific campaign or for all campaigns
     * that have a pending refresh request. Accepts ?id_campaign=X to target one
     * campaign, or runs all pending refresh_requested_at campaigns.
     * No time-gate — can be triggered on-demand via the "Refresh" button.
     */
    function cronjob_endorse_by_campaign()
    {
        $user = $_SESSION['user'];
        $id_campaign = $this->db->escape_str($_GET['id_campaign'] ?? '');
        $today = DATE("Y-m-d");

        if ($id_campaign) {
            // Sync specific campaign
            $campaigns = $this->mymodel->selectWithQuery("
                SELECT id FROM endorse_campaign WHERE id = '$id_campaign'
            ");
        } else {
            // Sync all campaigns with a pending refresh request
            $campaigns = $this->mymodel->selectWithQuery("
                SELECT id FROM endorse_campaign
                WHERE refresh_requested_at IS NOT NULL
                ORDER BY refresh_requested_at ASC
            ");
        }

        if (empty($campaigns)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => false, 'msg' => 'No campaigns to sync']);
            die;
        }

        $total_synced = 0;

        foreach ($campaigns as $campaign) {
            $cid = $campaign['id'];

            // Fetch up to 10 unsynced active endorses for this campaign
            $list = $this->mymodel->selectWithQuery("
                SELECT * FROM endorse
                WHERE id_campaign = '$cid'
                  AND status = 'Aktif'
                  AND status_campaign = 'Aktif'
                  AND link_upload != ''
                  AND (sync_at < '$today' OR sync_at IS NULL)
                LIMIT 10
            ");

            foreach ($list as $vl) {
                $id_endorse = $vl['id'];
                $v = $vl;
                $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));

                $query = $this->mymodel->selectWithQuery("SELECT id
                FROM endorse_logs
                WHERE id_endorse = '$id_endorse' AND date = '$today'
                ORDER BY id DESC LIMIT 1");
                $query = !empty($query) ? $query[0] : null;

                $query_yesterday = $this->mymodel->selectWithQuery("SELECT *
                FROM endorse_logs
                WHERE id_endorse = '$id_endorse' AND date < '$today' AND views_after > 0
                ORDER BY date DESC LIMIT 1");
                $query_yesterday = !empty($query_yesterday) ? $query_yesterday[0] : [];

                $prev_likes     = intval($query_yesterday['likes_after'] ?? 0);
                $prev_comment   = intval($query_yesterday['comment_after'] ?? 0);
                $prev_share_save = intval($query_yesterday['share_save_after'] ?? 0);
                $prev_views     = intval($query_yesterday['views_after'] ?? 0);

                $dt = [];
                $dt['status']          = strval($v['status']);
                $dt['status_campaign'] = strval($v['status_campaign']);
                $dt['id_endorse']      = strval($v['id']);
                $dt['id_campaign']     = strval($v['id_campaign']);
                $dt['influencer']      = strval($v['influencer']);
                $dt['date']            = $today;

                $response = $this->template->get_social_media($v['platform'], $v['link_upload']);

                $dts = [];
                $dts['sync_at'] = DATE("Y-m-d H:i:s");
                if (!empty($response['data']['created_at'])) {
                    $dts['posting_at'] = $response['data']['created_at'];
                }
                $this->db->update('endorse', $dts, ['id' => $v['id']]);

                $dt['likes']      = $prev_likes;
                $dt['comment']    = $prev_comment;
                $dt['share_save'] = $prev_share_save;
                $dt['views']      = $prev_views;

                if (!empty($response['data']['view']) && $response['data']['view'] > 0) {
                    $dt['likes']      = $response['data']['like'];
                    $dt['comment']    = $response['data']['comment'];
                    $dt['share_save'] = doubleval($response['data']['share']) + doubleval($response['data']['collect']);
                    $dt['views']      = $response['data']['view'];
                }

                if (intval($dt['views']) < $prev_views) {
                    $dt['views'] = $prev_views;
                }

                $dt['likes_after']      = intval($dt['likes']);
                $dt['comment_after']    = intval($dt['comment']);
                $dt['share_save_after'] = intval($dt['share_save']);
                $dt['views_after']      = intval($dt['views']);

                $dt['total_cost'] = doubleval($v['total_cost']);
                $dt['link_upload'] = strval($v['link_upload']);
                $dt['platform']    = strval($v['platform']);

                if ($v['total_cost'] > 0 && $dt['views_after'] > 0) {
                    $dt['cpm_after'] = doubleval($v['total_cost']) / doubleval($dt['views_after']) * 1000;
                } else {
                    $dt['cpm_after'] = 0;
                }

                $dt['likes']      -= $prev_likes;
                $dt['comment']    -= $prev_comment;
                $dt['share_save'] -= $prev_share_save;
                $dt['views']       = max(0, intval($dt['views_after']) - $prev_views);

                $dt['likes_before']      = $prev_likes;
                $dt['comment_before']    = $prev_comment;
                $dt['share_save_before'] = $prev_share_save;
                $dt['views_before']      = $prev_views;

                if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                    $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
                } else {
                    $dt['cpm'] = 0;
                }
                if ($v['total_cost'] > 0 && $dt['views_before'] > 0) {
                    $dt['cpm_before'] = doubleval($v['total_cost']) / doubleval($dt['views_before']) * 1000;
                } else {
                    $dt['cpm_before'] = 0;
                }

                $dt['brand'] = strval($vl['brand']);

                $dt_tmp = [];
                foreach ($dt as $kt => $vt) {
                    $dt_tmp[$kt] = strval($vt);
                }
                $dt = $dt_tmp;

                if ($query) {
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $this->db->update('endorse_logs', $dt, ['id_endorse' => $id_endorse, 'date' => $today]);
                } else {
                    $dt['created_at'] = DATE("Y-m-d H:i:s");
                    $dt['created_by'] = strval($user['id']);
                    if (!$this->db->insert('endorse_logs', $dt)) {
                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $dt['updated_by'] = strval($user['id']);
                        $this->db->update('endorse_logs', $dt, ['id_endorse' => $id_endorse, 'date' => $today]);
                    }
                }
                $total_synced++;
            }

            // Update campaign aggregate stats
            $this->update_endorse_parent($cid, ['status' => 'Aktif']);

            // Clear refresh request flag
            $this->db->update('endorse_campaign',
                ['refresh_requested_at' => null],
                ['id' => $cid]
            );
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => true,
            'synced' => $total_synced,
            'msg'    => "$total_synced endorse berhasil di-sync untuk " . count($campaigns) . " campaign"
        ]);
        die;
    }

    function cronjob_endorse_refresh_enqueue_all()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();

        $this->load->library('EndorseRefreshQueueService');
        $result = $this->endorserefreshqueueservice->enqueueAllActive(0);

        echo json_encode($result);
        die;
    }

    /**
     * Reconcile sweep for content-optimization finals. Enqueues a 'final' snapshot for
     * any auto-fetch endorse that is Completed but has no final snapshot yet — covering
     * enqueues lost after the row update committed. Safe to run repeatedly (dedup +
     * frozen guards prevent duplicates).
     */
    function cronjob_endorse_final_reconcile()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();

        $this->load->library('EndorseRefreshQueueService');
        $result = $this->endorserefreshqueueservice->enqueuePendingFinals(0);

        echo json_encode($result);
        die;
    }

    /**
     * Push ALL content-optimization rows to the configured Google Sheet tab (full-replace).
     * Mirror of the on-demand button but with no campaign/list filters.
     */
    function cronjob_endorse_optimization_sheet()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();

        $this->load->library('EndorseOptimizationSheet');
        $result = $this->endorseoptimizationsheet->sync([]);

        echo json_encode($result);
        die;
    }

    /**
     * Worker for endorse_refresh_queue. A global advisory lock makes repeated
     * scheduler/manual requests safe; Threads work is handled by its dedicated
     * endpoint so this request only owns refresh-queue work.
     *
     * Per tick:
     *   1. Reset stale rows (processing > 5 min) — handles crashed previous workers
     *   2. Atomic claim up to BATCH_SIZE pending rows with unique worker_id
     *   3. Pre-fetch endorse rows + previous stats in batch
     *   4. Fire RapidAPI requests in parallel via curl_multi (PARALLEL_HTTP at a time)
     *   5. Apply each result via Endorse_sync::apply
     *   6. Update queue rows and roll up touched campaigns once
     *
     * Keep one host schedule; the lock is the second line of defence.
     */
    function cronjob_endorse_refresh()
    {
        $monitor = $this->cron_monitor_start('cronjob_endorse_refresh');
        header('Content-Type: application/json; charset=utf-8');
        @set_time_limit(55);

        if (!$this->cron_try_lock('forbes:cronjob_endorse_refresh')) {
            echo json_encode([
                'status'  => true,
                'skipped' => true,
                'reason'  => 'already_running',
                'msg'     => 'Endorse refresh is already running',
            ]);
            $this->cron_monitor_finish($monitor, array('status' => 'ok', 'skipped' => true, 'note' => 'already_running'));
            die;
        }

        $this->load->model('mymodel');
        $this->load->library('template');
        $this->load->library('endorse_sync');
        $this->load->library('EndorseRefreshQueueService');
        $this->load->library('EndorseRefreshDiagnostics');
        $diagnosticRunId = $this->endorserefreshdiagnostics->startRun('cron_worker', 0, 0, array('driver' => env('ENDORSE_REFRESH_DRIVER', 'cron'), 'batch_size' => env('ENDORSE_REFRESH_BATCH_SIZE', 20), 'parallel_http' => env('ENDORSE_REFRESH_PARALLEL_HTTP', 10)));

        // Driver gate: when the long-lived Rust consumer owns draining
        // (ENDORSE_REFRESH_DRIVER=rust) the per-minute cron stands down; only the manual
        // "Proses Sekarang" button (force=1) still runs inline. Flip the env back to
        // 'cron' for rollback to the bounded web path. Both paths preserve the same
        // business-write semantics, while V2 adds pull-worker fencing and request limiting.
        $force = ($this->input->get_post('force') === '1');
        $driver = strtolower(trim((string) env('ENDORSE_REFRESH_DRIVER', 'cron')));
        if ($driver === 'rust' && !$force) {
            echo json_encode([
                'status'    => true,
                'processed' => 0,
                'driver'    => 'rust',
                'msg'       => 'Rust consumer owns draining — cron standing down',
            ]);
            $this->cron_monitor_finish($monitor, array(
                'status'          => 'ok',
                'processed_count' => 0,
                'queue_count'     => 0,
                'note'            => 'driver_rust_standby',
            ));
            die;
        }

        // Concurrent-fetch knobs stay on the cron (the fetch happens here); the claim,
        // rate caps and apply logic are shared with the Rust path via the queue service.
        // Ceiling raised from 10 to 30 so ENDORSE_REFRESH_PARALLEL_HTTP is actually tunable:
        // the old max equalled the default, so setting the env var could only ever LOWER it.
        // The default stays 10, so this is inert until an operator opts in.
        //
        // Safe to raise only because the fallback leg is now parallel too
        // (Template::get_social_media_batch). While it was a blocking per-item call, extra
        // leg-1 concurrency just piled more work onto a serialised second leg.
        $PARALLEL_HTTP = EndorseRefreshQueueService::boundedWorkerSetting(
            env('ENDORSE_REFRESH_PARALLEL_HTTP', 10), 10, 30
        );
        // Wall-clock budget so the run always returns before the cron curl --max-time /
        // nginx 60s timeout. Leftover items are deferred back to the queue by applyResults.
        $DEADLINE_SEC = floatval(env('ENDORSE_REFRESH_DEADLINE_SEC', 45));

        // Manual force run claims a larger batch and bypasses the daily + per-minute caps
        // (claimBatch honours the 'force' flag). Cron uses the normal batch size.
        $limit = EndorseRefreshQueueService::boundedWorkerSetting(
            $force ? env('ENDORSE_REFRESH_FORCE_BATCH', 50) : env('ENDORSE_REFRESH_BATCH_SIZE', 20),
            $force ? 50 : 20,
            $force ? 100 : 50
        );

        // Incremental drain path (default OFF). When enabled, one run drains multiple
        // slot-sized chunks (claim→reserve-per-request→fetch→apply→next chunk) via the
        // e2e-tested EndorseRefreshDrainRunner instead of one oversized claim. Legacy path
        // below is byte-for-byte unchanged when the flag is off, so rollback is instant.
        if (EndorseRefreshQueueService::incrementalClaimEnabled() && !$force) {
            require_once APPPATH . 'libraries/EndorseRefreshDrainRunner.php';
            $svc = $this->endorserefreshqueueservice;
            $tpl = $this->template;
            $requestStart = EndorseRefreshQueueService::reservesAtRequestStart();
            $env = strtolower((string) env('APP_ENV', 'prod'));
            $app = strtolower((string) env('APP_NAME', 'forbes'));
            $rapidKey = (string) env('RAPIDAPI_KEY', '');
            $rate = intval(env('ENDORSE_REFRESH_RATE_PER_MIN', 0));
            $store = new CiDbReservationStore($this->db, $env, $app);
            $store->pruneExpired(60, 60);
            $logger = new EndorseRefreshRunLogger();
            $chunk = EndorseRefreshQueueService::effectiveClaimLimit($limit, $PARALLEL_HTTP, true);

            $runner = new EndorseRefreshDrainRunner([
                'run_id'                  => $worker_id ?? substr(md5(uniqid('', true)), 0, 32),
                'deadline_sec'            => $DEADLINE_SEC,
                'chunk_size'              => $chunk,
                'per_request_timeout_sec' => floatval(env('ENDORSE_REFRESH_HTTP_TIMEOUT', 30)),
                'inline_retry'            => EndorseRefreshQueueService::inlineFallbackRetryEnabled(),
                'max_attempts'            => 3,
                'limiter_mode'            => EndorseRefreshQueueService::limiterMode(),
                'log'                     => $logger,
                'now'                     => function () { return microtime(true); },
                'sleep'                   => function ($s) { usleep((int) ($s * 1000000)); },
                'claim' => function (int $n) use ($svc, $rapidKey) {
                    $c = $svc->claimBatch(['limit' => $n, 'stale_minutes' => 5]);
                    if (empty($c['status'])) {
                        throw new RuntimeException(strval($c['error'] ?? 'atomic_claim_failed'));
                    }
                    if (!empty($c['skipped']) || empty($c['items'])) {
                        return [];
                    }
                    $scope = EndorseRefreshRateScope::scope(EndorseRefreshRateScope::PROVIDER_RAPIDAPI, $rapidKey);
                    return array_map(function ($it) use ($scope) {
                        return ['queue_id' => $it['queue_id'], 'scope' => $scope, 'orig' => $it];
                    }, $c['items']);
                },
                'reserve' => $requestStart ? function (string $scope) use ($store, $rate) {
                    return $store->reserve($scope, $rate > 0 ? $rate : PHP_INT_MAX, 60, ['run_id' => 'cron']);
                } : null,
                'fetch' => function (array $item, int $attempt) use ($tpl, $svc) {
                    $o = $item['orig'];
                    // Stamp the observation time at REQUEST START (UTC, microseconds), not at
                    // apply time — this is the ordering signal the atomic guard compares, so a
                    // late older response cannot regress newer stats regardless of apply order.
                    $mt = microtime(true);
                    $observedAt = gmdate('Y-m-d H:i:s', (int) $mt) . '.' . sprintf('%06d', (int) round(($mt - floor($mt)) * 1e6));
                    $resp = $tpl->get_social_media($o['platform'], $o['url'], true, intval($o['influencer_id'] ?? 0) ?: null);
                    if (is_array($resp)) {
                        $resp['observed_at'] = $observedAt;
                    }
                    // applyResults() owns the canonical response classification. This
                    // callback only tells the drain runner whether it can proceed; do
                    // not call a non-existent service classifier and abort the worker.
                    return ['ok' => !empty($resp['status']), 'error_class' => !empty($resp['status']) ? Endorse_sync::ERR_OK : Endorse_sync::ERR_TRANSIENT, '_resp' => $resp, 'path' => 'rapidapi'];
                },
                'apply' => function (array $item, array $resp) use ($svc) {
                    $svc->applyResults([$item['orig']], [$resp['_resp'] ?? ['status' => false, 'msg' => 'No response', 'data' => []]]);
                },
                'release' => function (array $items) use ($svc) {
                    $svc->releaseUnstartedChunk(array_map(function ($it) { return $it['orig']; }, $items));
                },
            ]);
            $summary = $runner->run();
            $this->endorserefreshdiagnostics->finishRun($diagnosticRunId, array('claimed_count' => intval($summary['requests_started'] ?? 0), 'completed_count' => intval($summary['unique_completed'] ?? 0), 'note' => strval($summary['stop_reason'] ?? '')));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => true, 'mode' => 'incremental', 'summary' => $summary]);
            $this->cron_monitor_finish($monitor, array(
                'status' => 'ok', 'processed_count' => $summary['requests_started'] ?? 0,
                'completed_count' => $summary['unique_completed'] ?? 0, 'note' => $summary['stop_reason'] ?? '',
            ));
            die;
        }

        // Claim (stale recovery + caps + atomic claim + attempt-insert all inside).
        $claim = $this->endorserefreshqueueservice->claimBatch([
            'limit'         => $limit,
            'force'         => $force,
            'stale_minutes' => 5,
        ]);

        if (empty($claim['status'])) {
            http_response_code(500);
            $reason = strval($claim['error'] ?? 'atomic_claim_failed');
            echo json_encode([
                'status' => false,
                'processed' => 0,
                'reason' => $reason,
                'msg' => strval($claim['msg'] ?? 'Queue claim failed before provider request.'),
            ]);
            $this->cron_monitor_finish($monitor, array(
                'status' => 'error', 'processed_count' => 0, 'queue_count' => 0, 'note' => $reason,
            ));
            $this->endorserefreshdiagnostics->finishRun($diagnosticRunId, array('note' => $reason));
            die;
        }

        // A cap blocked the run — report why and stop.
        if (!empty($claim['skipped'])) {
            $skip = $claim['skipped'];
            echo json_encode([
                'status'    => true,
                'processed' => 0,
                'used'      => $skip['used'] ?? null,
                'cap'       => $skip['cap'] ?? null,
                'msg'       => $skip['msg'] ?? 'Skipped',
            ]);
            $this->cron_monitor_finish($monitor, array(
                'status'          => 'ok',
                'processed_count' => 0,
                'queue_count'     => 0,
                'note'            => $skip['reason'] ?? 'capped',
            ));
            $this->endorserefreshdiagnostics->finishRun($diagnosticRunId, array('note' => $skip['reason'] ?? 'capped'));
            die;
        }

        $items     = $claim['items'];
        $worker_id = $claim['worker_id'];

        if (empty($items)) {
            echo json_encode([
                'status'    => true,
                'worker'    => $worker_id,
                'processed' => 0,
                'msg'       => 'No pending endorse refresh items',
            ]);
            $this->cron_monitor_finish($monitor, array(
                'status'          => 'ok',
                'processed_count' => 0,
                'queue_count'     => 0,
                'worker'          => $worker_id,
            ));
            $this->endorserefreshdiagnostics->finishRun($diagnosticRunId, array('note' => 'empty_queue'));
            die;
        }

        // Build fetch tasks from the claimed items — the per-item hints (rescue lane,
        // timeout, hd) were computed in claimBatch so the Rust path gets the same shape.
        $tasks = [];
        foreach ($items as $i => $item) {
            $tasks[$i] = [
                'platform'    => $item['platform'],
                'url'         => $item['url'],
                'rescue_lane' => !empty($item['rescue_lane']),
                'timeout_sec' => intval($item['timeout_sec']),
                'hd'          => intval($item['hd']),
                'influencer_id' => intval($item['influencer_id'] ?? 0),
                'content_id'  => strval($item['content_id'] ?? ''),
            ];
        }
        $responses = $this->template->get_social_media_batch($tasks, $PARALLEL_HTTP, $DEADLINE_SEC);

        // Step 5+6 — apply outcomes (mark completed/retrying/failed, finalize each attempt
        // with its REAL error, roll up touched campaigns) via the shared service.
        $summary = $this->endorserefreshqueueservice->applyResults($items, $responses);
        $this->endorserefreshdiagnostics->finishRun($diagnosticRunId, array('claimed_count' => intval($claim['claimed'] ?? count($items)), 'completed_count' => intval($summary['completed'] ?? 0), 'failed_count' => intval($summary['failed'] ?? 0), 'retrying_count' => intval($summary['retrying'] ?? 0), 'deferred_count' => intval($summary['deferred'] ?? 0)));

        echo json_encode([
            'status'    => true,
            'worker'    => $worker_id,
            'processed' => $summary['processed'],
            'completed' => $summary['completed'],
            'failed'    => $summary['failed'],
            'retrying'  => $summary['retrying'],
            'deferred'  => $summary['deferred'],
            'msg'       => $summary['processed'] . " items: {$summary['completed']} ok, {$summary['failed']} failed, {$summary['retrying']} retrying, {$summary['deferred']} deferred",
        ]);
        $this->cron_monitor_finish($monitor, array(
            'status'          => 'ok',
            'processed_count' => $summary['processed'],
            'queue_count'     => $claim['claimed'],
            'completed_count' => $summary['completed'],
            'failed_count'    => $summary['failed'],
            'retrying_count'  => $summary['retrying'],
            'deferred_count'  => $summary['deferred'],
            'worker'          => $worker_id,
        ));
        die;
    }

    /**
     * Async Threads slice of endorse_refresh_queue. This runs independently from
     * ENDORSE_REFRESH_DRIVER because the Rust worker deliberately skips Threads.
     */
    function cronjob_threads_scraper()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->cron_try_lock('forbes:cronjob_threads_scraper')) {
            echo json_encode(['status' => true, 'skipped' => true, 'reason' => 'already_running']);
            return;
        }
        $this->load->library('EndorseRefreshQueueService');
        $this->load->library('ThreadsEndorseScraperService');
        $limit = EndorseRefreshQueueService::boundedWorkerSetting(env('SOCIAL_SCRAPER_BATCH_SIZE', 10), 10, 50);
        $result = $this->threadsendorsescraperservice->run($limit);
        echo json_encode($result);
        die;
    }

    /**
     * Worker endpoint: claim a batch of pending endorse-refresh rows for the long-lived
     * Rust consumer (POST /api/endorse-refresh/claim). Returns fetch-ready items; the Rust
     * worker fetches each URL over ISOLATED HTTP/1.1 and posts outcomes back to
     * endorse_refresh_result. Shares the exact claim + rate-cap logic the cron uses.
     *
     * Auth: WORKER_SHARED_SECRET header + optional WORKER_IP_ALLOWLIST (worker_auth_guard).
     * RapidAPI fallback reserves a distributed token at request start. Claim size and
     * HTTP concurrency remain independent controls.
     */
    function endorse_refresh_claim()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();
        $this->load->library('EndorseRefreshV2Coordinator');

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $error = $this->endorserefreshv2coordinator->validateV2Request($payload, true);
        if ($error !== null) {
            $this->json_response($error['http_status'], $error['body']);
        }

        // V2 has no cron request to run stale recovery while Rust owns draining.
        // Use the same lease-aware, fenced recovery before allocating more work.
        $this->load->library('EndorseRefreshQueueService');
        $recovery = $this->endorserefreshqueueservice->resetStuck(
            intval(env('ENDORSE_REFRESH_STALE_MINUTES', 5))
        );
        if (empty($recovery['status'])) {
            $this->json_response(503, [
                'status' => false,
                'reason' => 'stale_recovery_failed',
                'msg' => strval($recovery['msg'] ?? 'Stale recovery failed.'),
            ]);
        }

        $claim = $this->endorserefreshv2coordinator->claimBatchV2(
            EndorseRefreshV2Coordinator::OWNER_RUST,
            trim((string) $payload['worker_id']),
            intval($payload['limit'] ?? env('ENDORSE_REFRESH_BATCH_SIZE', 40)),
            strval($payload['task_identity'] ?? '')
        );

        $this->json_response($claim['http_status'], $claim['body']);
    }

    /**
     * Authenticated per-item fallback for the Rust consumer. Only queue_id is
     * accepted; PHP reloads the authoritative row and owns URL, credentials,
     * RapidAPI validation and response mapping.
     */
    function endorse_refresh_fetch_fallback()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();
        $this->load->library('EndorseRefreshV2Coordinator');

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $response = $this->endorserefreshv2coordinator->fetchFallbackV2($payload);

        $this->json_response($response['http_status'], $response['body']);
    }

    function endorse_refresh_release()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();
        $this->load->library('EndorseRefreshV2Coordinator');

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $response = $this->endorserefreshv2coordinator->releaseClaimsV2($payload, EndorseRefreshV2Coordinator::OWNER_RUST);

        $this->json_response($response['http_status'], $response['body']);
    }

    /**
     * Worker endpoint: apply fetch outcomes posted back by the Rust consumer
     * (POST /api/endorse-refresh/result).
     *
     * Body: { "results": [ { "queue_id": N, "response": {status,msg,data} }, ... ] }
     * where `response` is a Template::get_social_media-shaped array (the worker parses the
     * raw TikTok payload into that shape). Authoritative item fields are re-read from the
     * still-`processing` queue rows here — the worker only supplies queue_id + response —
     * so a compromised/confused worker cannot forge attempt counts or campaign links.
     * apply()/is_terminal_class in PHP still own every retry decision.
     */
    function endorse_refresh_result()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->worker_auth_guard();
        $this->load->library('EndorseRefreshV2Coordinator');

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $response = $this->endorserefreshv2coordinator->applyResultsV2($payload);

        $this->json_response($response['http_status'], $response['body']);
    }

    /**
     * Precompute endorse_logs_daily_rollup — one row per (id_endorse, log_date).
     *
     * Powers the /overview?t=kol GRAFIK CAMPAIGN chart so it reads pre-aggregated daily
     * rows instead of scanning raw endorse_logs on every request. Upsert-only, so old
     * rows persist; the dashboard baseline (latest log before a range) stays correct as
     * long as the window has been backfilled at least once.
     *
     * Usage:
     *   - Backfill ALL history once:  GET api/cronjob/endorse-rollup?full=1
     *   - Schedule rolling refresh:   GET api/cronjob/endorse-rollup   (last N days only)
     *
     * Window size: env ENDORSE_ROLLUP_WINDOW_DAYS (default 60). Schedule alongside the
     * endorse sync/refresh crons so the rollup trails fresh logs.
     *
     * Route: GET api/cronjob/endorse-rollup
     */
    function cronjob_endorse_rollup()
    {
        $monitor = $this->cron_monitor_start('cronjob_endorse_rollup');
        header('Content-Type: application/json; charset=utf-8');

        $full = ($this->input->get('full') === '1');
        @set_time_limit($full ? 0 : 55);

        $window_days = intval(env('ENDORSE_ROLLUP_WINDOW_DAYS', 60));
        if ($window_days <= 0) {
            $window_days = 60;
        }

        // Full backfill walks all history; rolling refresh only re-aggregates recent days
        // (cumulative *_after columns for those days may still be changing).
        $since = $full
            ? '1970-01-01'
            : date('Y-m-d', strtotime('-' . $window_days . ' days'));

        $sql = "
            INSERT INTO endorse_logs_daily_rollup
                (id_endorse, log_date, likes_delta, comment_delta, share_save_delta, views_delta,
                 likes_after, comment_after, share_save_after, views_after, total_cost, last_updated)
            SELECT
                el.id_endorse,
                el.log_date,
                SUM(GREATEST(COALESCE(el.likes, 0), 0))        AS likes_delta,
                SUM(GREATEST(COALESCE(el.comment, 0), 0))      AS comment_delta,
                SUM(GREATEST(COALESCE(el.share_save, 0), 0))   AS share_save_delta,
                SUM(GREATEST(COALESCE(el.views, 0), 0))        AS views_delta,
                MAX(COALESCE(el.likes_after, 0))               AS likes_after,
                MAX(COALESCE(el.comment_after, 0))             AS comment_after,
                MAX(COALESCE(el.share_save_after, 0))          AS share_save_after,
                MAX(COALESCE(el.views_after, 0))               AS views_after,
                MAX(COALESCE(el.total_cost, 0))                AS total_cost,
                MAX(COALESCE(el.updated_at, el.created_at, CONCAT(el.date, ' 00:00:00'))) AS last_updated
            FROM endorse_logs el
            WHERE el.log_date IS NOT NULL
              AND el.log_date >= " . $this->db->escape($since) . "
            GROUP BY el.id_endorse, el.log_date
            ON DUPLICATE KEY UPDATE
                likes_delta      = VALUES(likes_delta),
                comment_delta    = VALUES(comment_delta),
                share_save_delta = VALUES(share_save_delta),
                views_delta      = VALUES(views_delta),
                likes_after      = VALUES(likes_after),
                comment_after    = VALUES(comment_after),
                share_save_after = VALUES(share_save_after),
                views_after      = VALUES(views_after),
                total_cost       = VALUES(total_cost),
                last_updated     = VALUES(last_updated)
        ";

        try {
            $this->db->query($sql);
            $affected = $this->db->affected_rows();
        } catch (Exception $e) {
            if (function_exists('sentry_capture_exception')) {
                sentry_capture_exception($e, array('controller' => 'Api_v2', 'method' => 'cronjob_endorse_rollup'));
            }
            $this->cron_monitor_fail($monitor, $e, array('since' => $since, 'full' => $full));
            echo json_encode(array('status' => false, 'msg' => $e->getMessage()));
            die;
        }

        echo json_encode(array(
            'status'        => true,
            'full'          => $full,
            'since'         => $since,
            'affected_rows' => $affected,
        ));

        $this->cron_monitor_finish($monitor, array(
            'status'          => 'ok',
            'processed_count' => $affected,
            'since'           => $since,
            'full'            => $full ? 1 : 0,
        ));
        die;
    }

    /**
     * FCM Phase 5 — push delivery worker. Recommend cron every 1 minute.
     *
     * Drains notification_outbox: recover stale leases -> claim a batch -> for each row,
     * expand to the user's live device tokens and send via FCM. Dead tokens are revoked;
     * a row succeeds (markSent) when every token sent (or the user has no tokens), else it
     * retries with backoff (markRetry, -> DEAD after max_attempts). A single token's
     * non-retryable hard error does not block the other tokens.
     *
     * Route: GET api/cronjob/notification-dispatch
     */
    function cronjob_notification_dispatch()
    {
        $monitor = $this->cron_monitor_start('cronjob_notification_dispatch');
        header('Content-Type: application/json; charset=utf-8');
        @set_time_limit(55);

        $BATCH_SIZE = intval(env('NOTIFICATION_DISPATCH_BATCH', 20));
        if ($BATCH_SIZE <= 0) {
            $BATCH_SIZE = 20;
        } elseif ($BATCH_SIZE > 200) {
            $BATCH_SIZE = 200;
        }

        $this->load->library('fcm');
        $this->load->model('DeviceTokenModel');
        $this->load->model('NotificationOutboxModel');

        $worker_id = uniqid('nw_', true);

        // Step 1 — recover rows stranded in SENDING by a crashed worker.
        $this->NotificationOutboxModel->recoverStale(5);

        // Step 2 — atomic claim.
        $rows = $this->NotificationOutboxModel->claimBatch($worker_id, $BATCH_SIZE);

        if (empty($rows)) {
            echo json_encode([
                'status'    => true,
                'worker'    => $worker_id,
                'processed' => 0,
                'msg'       => 'No pending notification outbox rows',
            ]);
            $this->cron_monitor_finish($monitor, array(
                'status' => 'ok',
                'processed_count' => 0,
                'queue_count' => 0,
                'worker' => $worker_id,
            ));
            die;
        }

        $sent = 0;
        $retried = 0;
        $failed = 0;
        $revoked = 0;
        $authFailed = false;

        foreach ($rows as $row) {
            $tokens = $this->DeviceTokenModel->activeForUser($row['user_id']);

            // No devices -> in-app only; nothing to push.
            if (empty($tokens)) {
                $this->NotificationOutboxModel->markSent($row['id']);
                $sent++;
                continue;
            }

            $data = !empty($row['data_json']) ? (json_decode($row['data_json'], true) ?: array()) : array();

            // Classify the row by the best outcome across its tokens.
            $anySent = false;   // at least one delivery
            $anyRetry = false;  // a transient failure -> revisit the whole row
            $anyHard = false;   // a permanent send error (e.g. 400) -> surface, don't retry
            $lastErr = '';
            foreach ($tokens as $t) {
                $r = $this->fcm->send($t['token'], $row['title'], $row['body'], $data);

                if (!empty($r['revoke'])) {
                    $this->DeviceTokenModel->revokeByToken($t['token']);
                    $revoked++;
                }
                if (!empty($r['auth'])) {
                    $authFailed = true; // FCM auth broken, not just this token
                }

                if ($r['status'] === 'sent') {
                    $anySent = true;
                } elseif (!empty($r['retryable'])) {
                    $anyRetry = true;
                    $lastErr = $r['msg'];
                } elseif (empty($r['revoke'])) {
                    // hard, non-retryable, not a dead-token cleanup
                    $anyHard = true;
                    $lastErr = $r['msg'];
                }
            }

            // Precedence: retry (give transient failures another pass) > delivered > hard fail.
            // The else covers "only dead tokens" — nothing deliverable, but no real error.
            if ($anyRetry) {
                $this->NotificationOutboxModel->markRetry($row['id'], $lastErr);
                $retried++;
            } elseif ($anySent) {
                $this->NotificationOutboxModel->markSent($row['id']);
                $sent++;
            } elseif ($anyHard) {
                $this->NotificationOutboxModel->markFailed($row['id'], $lastErr);
                $failed++;
            } else {
                $this->NotificationOutboxModel->markSent($row['id']);
                $sent++;
            }
        }

        // FCM auth is down -> alert admins (in-app, throttled). Rows stay retried and drain
        // on their own once the service account is fixed.
        if ($authFailed) {
            $this->alert_fcm_unavailable($lastErr ?? '');
        }

        echo json_encode([
            'status'    => true,
            'worker'    => $worker_id,
            'processed' => count($rows),
            'sent'      => $sent,
            'retried'   => $retried,
            'failed'    => $failed,
            'revoked'   => $revoked,
            'auth_failed' => $authFailed,
            'msg'       => count($rows) . " rows: $sent sent, $retried retried, $failed failed, $revoked tokens revoked",
        ]);
        $this->cron_monitor_finish($monitor, array(
            'status' => 'ok',
            'processed_count' => count($rows),
            'queue_count' => count($rows),
            'sent_count' => $sent,
            'retried_count' => $retried,
            'failed_count' => $failed,
            'revoked_count' => $revoked,
            'auth_failed' => $authFailed,
            'worker' => $worker_id,
        ));
        die;
    }

    /**
     * Notify configured admins that FCM push is failing. In-app only (push is what's broken)
     * and throttled to once per hour so a sustained outage can't spam. Recipients come from
     * env FCM_ALERT_USER_IDS (comma-separated user ids); with none set it just logs.
     *
     * @param string $lastErr
     */
    private function alert_fcm_unavailable($lastErr = '')
    {
        log_message('error', 'FCM push unavailable; auth failing in notification dispatch worker. ' . $lastErr);

        $this->load->model('NotificationModel');

        // Hourly throttle, independent of the dispatcher's own 60s dedupe window.
        if ($this->NotificationModel->existsByDedupeKey('fcm_unavailable', 3600)) {
            return;
        }

        $ids = array_filter(array_map('intval', explode(',', (string) env('FCM_ALERT_USER_IDS', ''))));
        if (empty($ids)) {
            return; // no recipients configured -> log-only (above)
        }

        $this->load->library('NotificationDispatcher');
        $this->notificationdispatcher->dispatchMany($ids, 'system.fcm_unavailable', array());
    }

    /**
     * FCM health check — GET api/fcm/health. Verifies the service account loads and a real
     * OAuth2 token can be minted. Internal, unauthenticated; leaks no secrets (project_id is
     * not sensitive). The "is FCM configured right?" one-curl check.
     */
    function fcm_health()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->load->library('fcm');
            $info = $this->fcm->describe();
            $this->fcm->getAccessToken(true); // force a fresh mint — a cached token can pass while the SA is broken

            echo json_encode(array(
                'ok'           => true,
                'project_id'   => $info['project_id'],
                'source'       => $info['source'],
                'token_cached' => $info['token_cached'],
                'client_email' => $info['client_email'],
                'key_id'       => $info['key_id'],
                'msg'          => 'FCM auth OK',
            ));
        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode(array(
                'ok'  => false,
                'msg' => $e->getMessage(),
            ));
        }
        die;
    }
}
