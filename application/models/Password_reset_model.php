<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * password_reset_tokens data access for the Forgot Password flow.
 *
 * Tokens are random 32-byte values; only their sha256 hash is stored. Tokens are
 * single-use (used_at) and short-lived (PASSWORD_RESET_TTL_MIN, default 30 min).
 * Requesting a new token invalidates any prior unused token for the same user.
 */
class Password_reset_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function ttl_minutes()
    {
        $ttl = (int) env('PASSWORD_RESET_TTL_MIN', 30);
        return $ttl > 0 ? $ttl : 30;
    }

    private function hash_token($rawToken)
    {
        return hash('sha256', (string) $rawToken);
    }

    /**
     * Invalidate prior unused tokens, then issue a fresh one.
     *
     * @param int    $userId
     * @param string $channel 'web'|'api'
     * @param string $ip
     * @return string the raw token (only returned here, never stored)
     */
    public function create_token($userId, $channel, $ip)
    {
        $this->invalidate_user_tokens($userId);

        $rawToken = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + ($this->ttl_minutes() * 60));

        $this->db->insert('password_reset_tokens', array(
            'user_id' => (int) $userId,
            'token_hash' => $this->hash_token($rawToken),
            'channel' => in_array($channel, array('web', 'api'), true) ? $channel : 'web',
            'expires_at' => $expiresAt,
            'used_at' => null,
            'requested_ip' => $ip !== '' ? $ip : null,
            'created_at' => $now,
        ));

        return $rawToken;
    }

    /**
     * Fetch a token row that is unused and unexpired, or null.
     *
     * @param string $rawToken
     * @return array|null
     */
    public function find_valid($rawToken)
    {
        $rawToken = (string) $rawToken;
        if ($rawToken === '') {
            return null;
        }

        $this->db->from('password_reset_tokens');
        $this->db->where('token_hash', $this->hash_token($rawToken));
        $this->db->where('used_at IS NULL', null, false);
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        $row = $this->db->get()->row_array();

        return $row ?: null;
    }

    /**
     * Mark a token consumed (single-use).
     */
    public function consume($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->update('password_reset_tokens', array('used_at' => date('Y-m-d H:i:s')));
    }

    /**
     * Invalidate all of a user's still-unused tokens.
     */
    public function invalidate_user_tokens($userId)
    {
        $this->db->where('user_id', (int) $userId);
        $this->db->where('used_at IS NULL', null, false);
        $this->db->update('password_reset_tokens', array('used_at' => date('Y-m-d H:i:s')));
    }

    /**
     * Count reset requests in the last $seconds for either this user or this IP.
     * Drives a simple anti-abuse throttle (per-user + per-IP).
     *
     * @return int
     */
    public function recent_request_count($userId, $ip, $seconds)
    {
        $seconds = (int) $seconds;
        $since = date('Y-m-d H:i:s', time() - ($seconds > 0 ? $seconds : 60));

        $this->db->from('password_reset_tokens');
        $this->db->where('created_at >=', $since);
        $this->db->group_start();
        $this->db->where('user_id', (int) $userId);
        if ($ip !== '') {
            $this->db->or_where('requested_ip', $ip);
        }
        $this->db->group_end();

        return (int) $this->db->count_all_results();
    }
}
