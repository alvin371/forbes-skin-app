<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DeviceTokenModel
 *
 * Single owner of the `device_tokens` table (FCM Phase 1). Mirrors NotificationModel:
 * the table name lives here once and every write is parameterized. The push channel
 * (Phase 4/5) reads activeForUser() at send time and calls revokeByToken() when FCM
 * reports a token dead.
 */
class DeviceTokenModel extends CI_Model
{
    const TABLE = 'device_tokens';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Register or refresh a device token.
     *
     * INSERT ... ON DUPLICATE KEY UPDATE keyed on the unique `token`: re-registering
     * an existing token reassigns it to the current user (shared device), refreshes
     * platform/app_version/last_seen_at, and clears revoked_at (token is live again).
     *
     * @param int    $userId
     * @param string $token
     * @param string $platform   One of: android, ios, web
     * @param string|null $appVersion
     * @return bool
     */
    public function upsert($userId, $token, $platform, $appVersion = null)
    {
        $now = date('Y-m-d H:i:s');

        $sql = "INSERT INTO `" . self::TABLE . "`
                (`user_id`, `token`, `platform`, `app_version`, `last_seen_at`, `revoked_at`, `created_at`)
                VALUES (?, ?, ?, ?, ?, NULL, ?)
                ON DUPLICATE KEY UPDATE
                    `user_id`      = VALUES(`user_id`),
                    `platform`     = VALUES(`platform`),
                    `app_version`  = VALUES(`app_version`),
                    `last_seen_at` = VALUES(`last_seen_at`),
                    `revoked_at`   = NULL,
                    `updated_at`   = VALUES(`last_seen_at`)";

        return (bool) $this->db->query($sql, array(
            (int) $userId,
            (string) $token,
            (string) $platform,
            $appVersion !== null ? (string) $appVersion : null,
            $now,
            $now,
        ));
    }

    /**
     * Live (non-revoked) tokens for a user. Uses the (user_id, revoked_at) index.
     *
     * @param int $userId
     * @return array
     */
    public function activeForUser($userId)
    {
        $this->db->from(self::TABLE);
        $this->db->where('user_id', (int) $userId);
        $this->db->where('revoked_at IS NULL', null, false);

        return $this->db->get()->result_array();
    }

    /**
     * Soft-revoke a token by its value (called when FCM reports it dead, or on logout).
     * Scoped to the token string — the token itself is the secret/identifier.
     *
     * @param string $token
     * @return int Rows affected.
     */
    public function revokeByToken($token)
    {
        $this->db->where('token', (string) $token);
        $this->db->where('revoked_at IS NULL', null, false);
        $this->db->update(self::TABLE, array(
            'revoked_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        return $this->db->affected_rows();
    }
}
