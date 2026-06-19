<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NotificationOutboxModel
 *
 * Single owner of the `notification_outbox` table (FCM Phase 3) — the push delivery
 * queue. Mirrors DeviceTokenModel / NotificationModel: the table name lives here once
 * and every write is parameterized. PushChannel enqueues; the Phase 5 cron worker
 * (Api_v2::cronjob_notification_dispatch) drives recoverStale -> claimBatch ->
 * markSent / markRetry.
 *
 * Claim model mirrors endorse_refresh_queue: an atomic UPDATE leases PENDING rows to a
 * worker_id, then a SELECT reads back exactly that worker's rows.
 */
class NotificationOutboxModel extends CI_Model
{
    const TABLE = 'notification_outbox';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Queue one push for a user. Routing IDs go in $data (json) — no PII.
     *
     * @param int    $userId
     * @param string|null $eventKey  e.g. 'leave.approved'
     * @param string $title
     * @param string $body
     * @param array  $data           String/scalar map written to data_json.
     * @return int|false Inserted id, or false on failure.
     */
    public function enqueue($userId, $eventKey, $title, $body, array $data = array())
    {
        $sql = "INSERT INTO `" . self::TABLE . "`
                (`user_id`, `event_key`, `title`, `body`, `data_json`, `status`, `next_attempt_at`, `created_at`)
                VALUES (?, ?, ?, ?, ?, 'PENDING', NOW(), NOW())";

        $ok = $this->db->query($sql, array(
            (int) $userId,
            $eventKey !== null ? (string) $eventKey : null,
            (string) $title,
            (string) $body,
            empty($data) ? null : json_encode($data),
        ));

        return $ok ? (int) $this->db->insert_id() : false;
    }

    /**
     * Return rows stuck in SENDING past the stale window to PENDING (crashed worker).
     * Mirrors the Step-1 recovery UPDATE in Api_v2::cronjob_endorse_refresh.
     *
     * @param int $staleMinutes
     * @return int Rows recovered.
     */
    public function recoverStale($staleMinutes = 5)
    {
        $staleMinutes = (int) $staleMinutes;
        $this->db->query("
            UPDATE `" . self::TABLE . "`
            SET `status` = 'PENDING', `worker_id` = NULL, `claimed_at` = NULL
            WHERE `status` = 'SENDING'
              AND `claimed_at` < (NOW() - INTERVAL {$staleMinutes} MINUTE)
        ");
        return $this->db->affected_rows();
    }

    /**
     * Atomic lease: claim up to $limit due PENDING rows for this worker, then read them
     * back. Mirrors the Step-2 claim in Api_v2::cronjob_endorse_refresh — the UPDATE is
     * serialized by MySQL so two workers never claim the same row.
     *
     * @param string $workerId
     * @param int    $limit
     * @return array Claimed rows.
     */
    public function claimBatch($workerId, $limit)
    {
        $limit = max(1, (int) $limit);

        $this->db->query("
            UPDATE `" . self::TABLE . "`
            SET `status` = 'SENDING', `worker_id` = ?, `claimed_at` = NOW()
            WHERE `status` = 'PENDING' AND `next_attempt_at` <= NOW()
            ORDER BY `id` ASC
            LIMIT {$limit}
        ", array((string) $workerId));

        if ($this->db->affected_rows() <= 0) {
            return array();
        }

        return $this->db
            ->query("SELECT * FROM `" . self::TABLE . "` WHERE `status` = 'SENDING' AND `worker_id` = ?", array((string) $workerId))
            ->result_array();
    }

    /**
     * Mark a row delivered.
     *
     * @param int $id
     * @return bool
     */
    public function markSent($id)
    {
        return (bool) $this->db->query("
            UPDATE `" . self::TABLE . "`
            SET `status` = 'SENT', `sent_at` = NOW(), `worker_id` = NULL
            WHERE `id` = ?
        ", array((int) $id));
    }

    /**
     * Terminally fail a row (non-retryable send error, e.g. FCM 400 INVALID_ARGUMENT).
     * Distinct from markRetry: no further attempts, surfaced as FAILED for observability.
     *
     * @param int    $id
     * @param string $err
     * @return bool
     */
    public function markFailed($id, $err)
    {
        return (bool) $this->db->query("
            UPDATE `" . self::TABLE . "`
            SET `status` = 'FAILED', `last_error` = ?, `worker_id` = NULL, `claimed_at` = NULL
            WHERE `id` = ?
        ", array((string) $err, (int) $id));
    }

    /**
     * Record a failed attempt: bump attempts, schedule an exponential-backoff retry, and
     * promote to DEAD once attempts >= max_attempts.
     *
     * @param int    $id
     * @param string $err
     * @return bool
     */
    public function markRetry($id, $err)
    {
        $sql = "
            UPDATE `" . self::TABLE . "`
            SET `attempts` = `attempts` + 1,
                `last_error` = ?,
                `worker_id` = NULL,
                `claimed_at` = NULL,
                `status` = IF(`attempts` + 1 >= `max_attempts`, 'DEAD', 'PENDING'),
                `next_attempt_at` = NOW() + INTERVAL POW(2, `attempts` + 1) MINUTE
            WHERE `id` = ?
        ";

        return (bool) $this->db->query($sql, array((string) $err, (int) $id));
    }
}
