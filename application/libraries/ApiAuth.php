<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ApiAuth
{
    protected $CI;
    protected $jwtSecret;
    protected $jwtTtlMin;
    protected $refreshTtlDays;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->jwtSecret = (string) env('API_JWT_SECRET', 'change-me');
        $this->jwtTtlMin = (int) env('API_JWT_TTL_MIN', 30);
        $this->refreshTtlDays = (int) env('API_REFRESH_TTL_DAYS', 30);
    }

    public function issue_tokens($user, $userAgent, $ipAddress)
    {
        $accessToken = $this->generate_access_token($user);
        $refreshToken = $this->generate_refresh_token();
        $this->store_refresh_token($user['id'], $refreshToken, $userAgent, $ipAddress);

        return array(
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        );
    }

    public function refresh_tokens($refreshToken, $userAgent, $ipAddress)
    {
        $tokenData = $this->get_refresh_token_record($refreshToken);
        if (!$tokenData) {
            return null;
        }

        $user = $this->CI->db->get_where('user', array('id' => (int) $tokenData['user_id']))->row_array();
        if (!$user) {
            return null;
        }

        $this->revoke_refresh_token($tokenData['id']);
        return $this->issue_tokens($user, $userAgent, $ipAddress);
    }

    public function authenticate()
    {
        $header = $this->CI->input->get_request_header('Authorization', true);
        if (!$header) {
            return $this->authenticate_from_session();
        }

        if (stripos($header, 'Bearer ') !== 0) {
            return $this->authenticate_from_session();
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return null;
        }

        $payload = $this->decode_jwt($token);
        if (!$payload || empty($payload['sub'])) {
            return null;
        }

        $user = $this->CI->db->get_where('user', array('id' => (int) $payload['sub']))->row_array();
        if (!$user) {
            return null;
        }

        return $user;
    }

    private function authenticate_from_session()
    {
        $sessionUser = $_SESSION['user'] ?? null;
        if (!is_array($sessionUser) || empty($sessionUser['id'])) {
            return null;
        }

        return $this->CI->db->get_where('user', array('id' => (int) $sessionUser['id']))->row_array();
    }

    public function generate_access_token($user)
    {
        $now = time();

        // Get user's primary role_id from user_roles table
        $role_id = $this->get_user_primary_role_id($user['id']);

        $payload = array(
            'sub' => (int) $user['id'],
            'name' => $user['full_name'] ?? null,
            'email' => $user['email'] ?? null,
            'role' => $user['role_text'] ?? ($user['role'] ?? null),
            'role_id' => $role_id,
            'iat' => $now,
            'exp' => $now + ($this->jwtTtlMin * 60),
        );

        return $this->encode_jwt($payload);
    }

    /**
     * Decode JWT payload without returning user (for getting role_id from token)
     */
    public function decode_jwt_payload($token)
    {
        return $this->decode_jwt($token);
    }

    /**
     * Get user's primary role_id from user_roles table
     */
    private function get_user_primary_role_id($user_id)
    {
        $this->CI->db->select('ur.role_id');
        $this->CI->db->from('user_roles ur');
        $this->CI->db->join('roles r', 'ur.role_id = r.id');
        $this->CI->db->where('ur.user_id', $user_id);
        $this->CI->db->where('r.is_active', 1);
        $this->CI->db->limit(1);

        $result = $this->CI->db->get()->row_array();
        return $result ? (int) $result['role_id'] : null;
    }

    public function generate_refresh_token()
    {
        return $this->base64url_encode(random_bytes(48));
    }

    private function encode_jwt($payload)
    {
        $header = array('alg' => 'HS256', 'typ' => 'JWT');
        $segments = array(
            $this->base64url_encode(json_encode($header)),
            $this->base64url_encode(json_encode($payload)),
        );
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $this->jwtSecret, true);
        $segments[] = $this->base64url_encode($signature);
        return implode('.', $segments);
    }

    private function decode_jwt($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($headerB64, $payloadB64, $signatureB64) = $parts;
        $payload = json_decode($this->base64url_decode($payloadB64), true);
        if (!is_array($payload)) {
            return null;
        }

        $signature = $this->base64url_decode($signatureB64);
        $signingInput = $headerB64 . '.' . $payloadB64;
        $expected = hash_hmac('sha256', $signingInput, $this->jwtSecret, true);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function store_refresh_token($userId, $refreshToken, $userAgent, $ipAddress)
    {
        $expiresAt = date('Y-m-d H:i:s', time() + ($this->refreshTtlDays * 86400));
        $this->CI->db->insert('api_refresh_tokens', array(
            'user_id' => (int) $userId,
            'token_hash' => hash('sha256', $refreshToken),
            'expires_at' => $expiresAt,
            'revoked_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'user_agent' => $userAgent,
            'ip' => $ipAddress,
        ));
    }

    private function get_refresh_token_record($refreshToken)
    {
        $hash = hash('sha256', $refreshToken);
        $this->CI->db->where('token_hash', $hash);
        $this->CI->db->where('revoked_at IS NULL', null, false);
        $this->CI->db->where('expires_at >', date('Y-m-d H:i:s'));
        return $this->CI->db->get('api_refresh_tokens')->row_array();
    }

    private function revoke_refresh_token($id)
    {
        $this->CI->db->where('id', (int) $id);
        $this->CI->db->update('api_refresh_tokens', array(
            'revoked_at' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Revoke every still-active refresh token for a user. Used after a password
     * reset so the change forces re-login on all devices.
     */
    public function revoke_all_for_user($userId)
    {
        $this->CI->db->where('user_id', (int) $userId);
        $this->CI->db->where('revoked_at IS NULL', null, false);
        $this->CI->db->update('api_refresh_tokens', array(
            'revoked_at' => date('Y-m-d H:i:s'),
        ));
    }

    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64url_decode($data)
    {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
