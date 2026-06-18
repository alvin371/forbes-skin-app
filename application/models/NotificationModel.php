<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NotificationModel
 *
 * Single owner of the `notifications` table. All reads/writes go through here so the
 * table name lives in one place and every query is parameterized (CI query builder
 * binds values, eliminating the string-interpolation SQL injection that existed in
 * the Notifications controller).
 *
 * Used by NotificationDispatcher (writes), the Notifications controller (web inbox),
 * and any future channel that needs to read in-app notifications.
 */
class NotificationModel extends CI_Model
{
    const TABLE = 'notifications';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Insert a notification row.
     *
     * @param array $data Columns: user_id, title, message, type, related_table,
     *                    related_id, dedupe_key. is_read/created_at are defaulted.
     * @return int Inserted id (0 on failure).
     */
    public function insert(array $data)
    {
        $row = array(
            'user_id'       => (int) ($data['user_id'] ?? 0),
            'title'         => $data['title'] ?? null,
            'message'       => $data['message'] ?? '',
            'type'          => $data['type'] ?? 'info',
            'related_table' => $data['related_table'] ?? null,
            'related_id'    => isset($data['related_id']) ? (int) $data['related_id'] : null,
            'dedupe_key'    => $data['dedupe_key'] ?? null,
            'is_read'       => 0,
            'created_at'    => date('Y-m-d H:i:s'),
        );

        $this->db->insert(self::TABLE, $row);
        return (int) $this->db->insert_id();
    }

    /**
     * Cross-process deduplication: was a notification with this key written within
     * the recent window? Backed by the dedupe_key column + index (no session reliance,
     * so it works in web, API and cron/CLI contexts alike).
     *
     * @param string $key
     * @param int    $windowSeconds
     * @return bool
     */
    public function existsByDedupeKey($key, $windowSeconds)
    {
        if ($key === null || $key === '') {
            return false;
        }

        $this->db->from(self::TABLE);
        $this->db->where('dedupe_key', $key);
        $this->db->where('created_at >', date('Y-m-d H:i:s', time() - (int) $windowSeconds));
        $this->db->limit(1);

        return $this->db->count_all_results() > 0;
    }

    /**
     * Recent notifications for a user (newest first).
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getRecent($userId, $limit = 10, $offset = 0)
    {
        $this->db->from(self::TABLE);
        $this->db->where('user_id', (int) $userId);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit((int) $limit, (int) $offset);

        return $this->db->get()->result_array();
    }

    /**
     * Count notifications for a user, with optional keyword / read-state filters.
     *
     * @param int   $userId
     * @param array $filters ['keyword' => string, 'is_read' => '0'|'1']
     * @return int
     */
    public function countAll($userId, array $filters = array())
    {
        $this->applyListFilters($userId, $filters);
        return $this->db->count_all_results(self::TABLE);
    }

    /**
     * Paginated list for a user, with the same filters as countAll().
     *
     * @param int   $userId
     * @param array $filters
     * @param int   $limit
     * @param int   $offset
     * @return array
     */
    public function listForUser($userId, array $filters = array(), $limit = 20, $offset = 0)
    {
        $this->applyListFilters($userId, $filters);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit((int) $limit, (int) $offset);

        return $this->db->get(self::TABLE)->result_array();
    }

    /**
     * Unread count for a user.
     *
     * @param int $userId
     * @return int
     */
    public function unreadCount($userId)
    {
        $this->db->from(self::TABLE);
        $this->db->where('user_id', (int) $userId);
        $this->db->where('is_read', 0);

        return $this->db->count_all_results();
    }

    /**
     * Mark a single notification read (scoped to its owner).
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markRead($notificationId, $userId)
    {
        $this->db->where('id', (int) $notificationId);
        $this->db->where('user_id', (int) $userId);
        return $this->db->update(self::TABLE, array('is_read' => 1, 'updated_at' => date('Y-m-d H:i:s')));
    }

    /**
     * Mark all unread notifications read for a user.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllRead($userId)
    {
        $this->db->where('user_id', (int) $userId);
        $this->db->where('is_read', 0);
        return $this->db->update(self::TABLE, array('is_read' => 1, 'updated_at' => date('Y-m-d H:i:s')));
    }

    /**
     * Delete a single notification (scoped to its owner).
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function delete($notificationId, $userId)
    {
        $this->db->where('id', (int) $notificationId);
        $this->db->where('user_id', (int) $userId);
        return $this->db->delete(self::TABLE);
    }

    /**
     * Delete all read notifications for a user.
     *
     * @param int $userId
     * @return bool
     */
    public function clearRead($userId)
    {
        $this->db->where('user_id', (int) $userId);
        $this->db->where('is_read', 1);
        return $this->db->delete(self::TABLE);
    }

    /**
     * Shared WHERE builder for list/count (keyword + read-state).
     *
     * @param int   $userId
     * @param array $filters
     */
    private function applyListFilters($userId, array $filters)
    {
        $this->db->where('user_id', (int) $userId);

        $keyword = isset($filters['keyword']) ? trim((string) $filters['keyword']) : '';
        if ($keyword !== '') {
            $this->db->group_start();
            $this->db->like('title', $keyword);
            $this->db->or_like('message', $keyword);
            $this->db->group_end();
        }

        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            $this->db->where('is_read', (int) $filters['is_read']);
        }
    }
}
