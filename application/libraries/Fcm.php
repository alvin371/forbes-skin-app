<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Firebase\JWT\JWT;

/**
 * Fcm — Firebase Cloud Messaging HTTP v1 transport (FCM Phase 2).
 *
 * Sends a single push to a single device token via the OAuth2 service-account flow
 * (legacy server keys are rejected by FCM v1). This library is pure transport: it
 * knows nothing about the outbox/queue (Phase 3) or which users get notified
 * (Phase 4). The Phase 5 cron worker is the intended caller.
 *
 * Auth: a short-lived RS256 JWT is minted from the service-account key
 * (firebase/php-jwt) and exchanged at Google's token endpoint for a bearer access
 * token, which is cached on disk ~55 min to avoid an OAuth round-trip per send.
 *
 * Config (.env), in precedence order for the credentials:
 *   FCM_SERVICE_ACCOUNT_B64   base64 of the service-account JSON (kept out of git), OR
 *   FCM_SERVICE_ACCOUNT_FILE  path to the raw service-account JSON (absolute or
 *                             APPPATH-relative); easier ops than the base64 blob
 *   FCM_PROJECT_ID            optional; defaults to project_id in the SA JSON
 *
 * Loaded lowercase per CI3 convention: $this->fcm.
 */
class Fcm
{
    const TOKEN_SCOPE    = 'https://www.googleapis.com/auth/firebase.messaging';
    const TOKEN_AUDIENCE = 'https://oauth2.googleapis.com/token';
    const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    const TOKEN_TTL      = 3300; // 55 min; Google tokens live 60 min.

    /** @var array Decoded service-account credentials. */
    private $sa;

    /** @var string Firebase project id. */
    private $projectId;

    /** @var string Cached-token file path. */
    private $tokenCachePath;

    /** @var string Where the SA was loaded from: 'b64' | 'file'. */
    private $source;

    public function __construct()
    {
        $this->sa = $this->loadServiceAccount();
        $this->projectId = (string) (env('FCM_PROJECT_ID', '') ?: ($this->sa['project_id'] ?? ''));
        if ($this->projectId === '') {
            throw new RuntimeException('FCM project id missing: set FCM_PROJECT_ID or include project_id in the service account JSON.');
        }
        $this->tokenCachePath = APPPATH . 'cache/fcm_token.json';
    }

    /**
     * Load the service-account credentials.
     *
     * Precedence: FCM_SERVICE_ACCOUNT_B64 (base64 blob, back-compat) → else
     * FCM_SERVICE_ACCOUNT_FILE (path to the raw JSON — easier ops). Either way the JSON is
     * validated the same. Records the source ('b64'|'file') for the health check.
     *
     * @return array
     */
    private function loadServiceAccount()
    {
        $b64  = (string) env('FCM_SERVICE_ACCOUNT_B64', '');
        $file = (string) env('FCM_SERVICE_ACCOUNT_FILE', '');

        if ($b64 !== '') {
            $this->source = 'b64';
            // Tolerant decode: strip whitespace / stray shell artifacts like a trailing '%'.
            $b64 = preg_replace('#[^A-Za-z0-9+/=]#', '', trim($b64));
            $json = base64_decode($b64, true);
            if ($json === false) {
                throw new RuntimeException('FCM_SERVICE_ACCOUNT_B64 is not valid base64.');
            }
            return $this->validateServiceAccount($json, 'FCM_SERVICE_ACCOUNT_B64');
        }

        if ($file !== '') {
            $this->source = 'file';
            $path = $this->resolvePath($file);
            if (!is_file($path) || !is_readable($path)) {
                throw new RuntimeException('FCM_SERVICE_ACCOUNT_FILE is not a readable file: ' . $path);
            }
            $json = file_get_contents($path);
            if ($json === false) {
                throw new RuntimeException('FCM_SERVICE_ACCOUNT_FILE could not be read: ' . $path);
            }
            return $this->validateServiceAccount($json, 'FCM_SERVICE_ACCOUNT_FILE');
        }

        throw new RuntimeException('No FCM credentials: set FCM_SERVICE_ACCOUNT_B64 or FCM_SERVICE_ACCOUNT_FILE in .env.');
    }

    /**
     * Decode + validate a service-account JSON string.
     *
     * @param string $json
     * @param string $sourceKey  env key name, for clearer error messages
     * @return array
     */
    private function validateServiceAccount($json, $sourceKey)
    {
        $sa = json_decode($json, true);
        if (!is_array($sa) || empty($sa['client_email']) || empty($sa['private_key'])) {
            throw new RuntimeException($sourceKey . ' did not decode to a valid service account JSON.');
        }
        return $sa;
    }

    /**
     * Resolve a configured path: absolute as-is, otherwise relative to APPPATH.
     *
     * @param string $file
     * @return string
     */
    private function resolvePath($file)
    {
        $file = trim($file);
        $isAbsolute = ($file !== '' && ($file[0] === '/' || preg_match('#^[A-Za-z]:[\\\\/]#', $file)));
        return $isAbsolute ? $file : rtrim(APPPATH, '/\\') . DIRECTORY_SEPARATOR . ltrim($file, '/\\');
    }

    /**
     * Non-network snapshot of the FCM config, for the health endpoint.
     *
     * @return array ['project_id'=>string, 'source'=>string, 'token_cached'=>bool]
     */
    public function describe()
    {
        return array(
            'project_id'   => $this->projectId,
            'source'       => $this->source,
            'token_cached' => $this->readCachedToken() !== null,
        );
    }

    /**
     * Return a valid OAuth2 access token, minting + caching a new one when needed.
     *
     * @param bool $forceRefresh Skip the disk cache and mint a fresh token (used by the
     *                           health check and the stale-token recovery in send()).
     * @return string
     */
    public function getAccessToken($forceRefresh = false)
    {
        if (!$forceRefresh) {
            $cached = $this->readCachedToken();
            if ($cached !== null) {
                return $cached;
            }
        }

        $now = time();
        $assertion = JWT::encode(array(
            'iss'   => $this->sa['client_email'],
            'scope' => self::TOKEN_SCOPE,
            'aud'   => self::TOKEN_AUDIENCE,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ), $this->sa['private_key'], 'RS256');

        $post = http_build_query(array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $assertion,
        ));

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => self::TOKEN_ENDPOINT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_HTTPHEADER     => array('Content-Type: application/x-www-form-urlencoded'),
        ));
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new RuntimeException('FCM token request failed: cURL Error: ' . $err);
        }

        $data = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300 || empty($data['access_token'])) {
            $msg = is_array($data) ? ($data['error_description'] ?? ($data['error'] ?? $response)) : $response;
            throw new RuntimeException('FCM token request rejected (HTTP ' . $httpCode . '): ' . $msg);
        }

        $this->writeCachedToken($data['access_token']);
        return $data['access_token'];
    }

    /**
     * Send a push to one device token.
     *
     * Generic body text only; IDs / routing data go in $data so no PII rides in the
     * push payload.
     *
     * @param string $token Device registration token.
     * @param string $title
     * @param string $body
     * @param array  $data  String map (FCM v1 requires string values).
     * @return array ['status'=>'sent'|'error', 'retryable'=>bool, 'revoke'=>bool,
     *               'auth'=>bool, 'http_code'=>int, 'msg'=>string]
     *               (auth=true means FCM auth itself is broken, not just this token)
     */
    public function send($token, $title, $body, array $data = array())
    {
        // Note whether we're about to send on a cached token: if a cached token is rejected
        // (401/403) it may simply be stale, so we get one shot to bust it and mint fresh.
        $usedCache = ($this->readCachedToken() !== null);

        try {
            $accessToken = $this->getAccessToken();
        } catch (Exception $e) {
            // Token mint failure = FCM auth is broken (bad/expired/revoked SA, or network).
            // Transient at the row level -> worker retries; flagged auth so it can alert.
            log_message('error', 'FCM auth failure (token mint): ' . $e->getMessage());
            return $this->result('error', true, false, 0, $e->getMessage(), true);
        }

        // FCM v1 data values must be strings.
        $stringData = array();
        foreach ($data as $k => $v) {
            $stringData[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        $message = array(
            'message' => array(
                'token'        => $token,
                'notification' => array('title' => $title, 'body' => $body),
            ),
        );
        if (!empty($stringData)) {
            $message['message']['data'] = $stringData;
        }

        list($response, $httpCode, $err) = $this->postMessage($accessToken, $message);

        // Stale cached token recovery: a cached bearer can be rejected as expired even though
        // the SA is fine. Bust the cache, mint a genuinely fresh token, and retry once. Only
        // when the rejected token came from cache, so a truly-bad SA still fails fast (below).
        if (($httpCode === 401 || $httpCode === 403) && $usedCache) {
            $this->clearCachedToken();
            try {
                $accessToken = $this->getAccessToken(true);
            } catch (Exception $e) {
                log_message('error', 'FCM auth failure (forced re-mint): ' . $e->getMessage());
                return $this->result('error', true, false, 0, $e->getMessage(), true);
            }
            list($response, $httpCode, $err) = $this->postMessage($accessToken, $message);
        }

        if ($err) {
            return $this->result('error', true, false, 0, 'cURL Error: ' . $err);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return $this->result('sent', false, false, $httpCode, 'Push delivered');
        }

        $decoded = json_decode($response, true);
        $fcmError = $decoded['error']['status'] ?? ($decoded['error']['message'] ?? '');

        // Dead token -> caller should revoke it.
        if ($httpCode === 404 || in_array($fcmError, array('UNREGISTERED', 'NOT_FOUND'), true)) {
            return $this->result('error', false, true, $httpCode, 'Token unregistered: ' . $fcmError);
        }

        // 401/403 = FCM auth rejected the credentials (SA disabled / wrong project / missing
        // scope), and a fresh-minted token didn't help. Drop any cache so the next attempt
        // re-mints, mark retryable in case it's transient, and flag auth so admins are alerted.
        if ($httpCode === 401 || $httpCode === 403) {
            $this->clearCachedToken();
            log_message('error', 'FCM auth failure (HTTP ' . $httpCode . '): ' . ($fcmError ?: $response));
            return $this->result('error', true, false, $httpCode, 'FCM auth rejected: ' . ($fcmError ?: $response), true);
        }

        // 5xx / 429 are transient -> retryable.
        if ($httpCode >= 500 || $httpCode === 429) {
            return $this->result('error', true, false, $httpCode, 'FCM transient error: ' . $fcmError);
        }

        // Other 4xx (bad request) -> not retryable, do not revoke.
        return $this->result('error', false, false, $httpCode, 'FCM error (HTTP ' . $httpCode . '): ' . ($fcmError ?: $response));
    }

    /**
     * POST one built message to the FCM v1 endpoint with the given bearer token.
     * Pure transport, no classification — returns the raw [body, http_code, curl_error]
     * so send() can decide (and retry) once on a stale token.
     *
     * @param string $accessToken
     * @param array  $message
     * @return array [string|false $response, int $httpCode, string $err]
     */
    private function postMessage($accessToken, array $message)
    {
        $url = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($message),
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ),
        ));
        $response = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        return array($response, $httpCode, $err);
    }

    private function result($status, $retryable, $revoke, $httpCode, $msg, $auth = false)
    {
        return array(
            'status'    => $status,
            'retryable' => $retryable,
            'revoke'    => $revoke,
            'auth'      => $auth,
            'http_code' => $httpCode,
            'msg'       => $msg,
        );
    }

    /**
     * @return string|null Cached token if present and unexpired.
     */
    private function readCachedToken()
    {
        if (!is_file($this->tokenCachePath)) {
            return null;
        }
        $raw = @file_get_contents($this->tokenCachePath);
        if ($raw === false) {
            return null;
        }
        $cached = json_decode($raw, true);
        if (!is_array($cached) || empty($cached['access_token']) || empty($cached['expires_at'])) {
            return null;
        }
        if (time() >= (int) $cached['expires_at']) {
            return null;
        }
        return $cached['access_token'];
    }

    private function writeCachedToken($accessToken)
    {
        $payload = json_encode(array(
            'access_token' => $accessToken,
            'expires_at'   => time() + self::TOKEN_TTL,
        ));
        // Restrictive perms: the cache holds a live bearer token.
        if (@file_put_contents($this->tokenCachePath, $payload, LOCK_EX) !== false) {
            @chmod($this->tokenCachePath, 0600);
        }
    }

    /**
     * Drop the cached bearer token so the next getAccessToken() mints a fresh one.
     * Called when FCM rejects a cached token (401/403) — recovers from a stale cache
     * without any manual file deletion on the server.
     */
    private function clearCachedToken()
    {
        if (is_file($this->tokenCachePath)) {
            @unlink($this->tokenCachePath);
        }
    }
}
