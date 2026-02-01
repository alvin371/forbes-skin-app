<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NotificationService Library
 *
 * Handles in-app workflow notifications with deduplication.
 * Phase 1: In-app notifications only
 * Phase 2 (future): Add email integration
 *
 * Notification Triggers:
 * - On submit: Notify first approver
 * - On step approval: Notify next approver
 * - On final approval: Notify requester
 * - On rejection: Notify requester with rejection reason
 * - On NEEDS_ROUTE: Notify HR admins
 *
 * Deduplication:
 * - Uses unique notification keys per event
 * - Prevents duplicate notifications within a time window
 */
class NotificationService
{
    protected $CI;
    protected $db;

    /**
     * Notification types
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';

    /**
     * Deduplication window in seconds
     */
    protected $dedupeWindow = 60;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    /**
     * Notify approver that they have a new pending approval
     *
     * @param int $approverId
     * @param int $leaveRequestId
     * @param array $request Leave request details
     * @return bool
     */
    public function notifyApproverAssigned($approverId, $leaveRequestId, $request)
    {
        $title = 'Pengajuan Cuti Baru Perlu Disetujui';
        $message = sprintf(
            '%s mengajukan %s (%s hari) dari %s sampai %s. Silakan review dan berikan persetujuan.',
            $request['requester_name'] ?? 'Karyawan',
            $request['leave_type_name'] ?? 'Cuti',
            $request['days_count'],
            date('d M Y', strtotime($request['start_date'])),
            date('d M Y', strtotime($request['end_date']))
        );

        $notificationKey = "approver_assigned_{$leaveRequestId}_{$approverId}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $approverId,
            $title,
            $message,
            self::TYPE_INFO,
            'leave_requests',
            $leaveRequestId,
            $notificationKey
        );
    }

    /**
     * Notify requester that their leave has been approved
     *
     * @param int $leaveRequestId
     * @param array $request
     * @return bool
     */
    public function notifyRequesterApproved($leaveRequestId, $request)
    {
        $title = 'Pengajuan Cuti Disetujui';
        $message = sprintf(
            'Pengajuan %s Anda dari %s sampai %s (%s hari) telah disetujui.',
            $request['leave_type_name'] ?? 'Cuti',
            date('d M Y', strtotime($request['start_date'])),
            date('d M Y', strtotime($request['end_date'])),
            $request['days_count']
        );

        $notificationKey = "request_approved_{$leaveRequestId}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $request['user_id'],
            $title,
            $message,
            self::TYPE_SUCCESS,
            'leave_requests',
            $leaveRequestId,
            $notificationKey
        );
    }

    /**
     * Notify requester that their leave has been rejected
     *
     * @param int $leaveRequestId
     * @param array $request
     * @param string $stepName Which step rejected
     * @param string|null $reason Rejection reason
     * @return bool
     */
    public function notifyRequesterRejected($leaveRequestId, $request, $stepName, $reason = null)
    {
        $title = 'Pengajuan Cuti Ditolak';
        $message = sprintf(
            'Pengajuan %s Anda dari %s sampai %s telah ditolak oleh %s.',
            $request['leave_type_name'] ?? 'Cuti',
            date('d M Y', strtotime($request['start_date'])),
            date('d M Y', strtotime($request['end_date'])),
            $stepName
        );

        if ($reason) {
            $message .= ' Alasan: ' . $reason;
        }

        $notificationKey = "request_rejected_{$leaveRequestId}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $request['user_id'],
            $title,
            $message,
            self::TYPE_ERROR,
            'leave_requests',
            $leaveRequestId,
            $notificationKey
        );
    }

    /**
     * Notify HR admins about a request that needs route assignment
     *
     * @param int $adminId
     * @param int $leaveRequestId
     * @param array $request
     * @return bool
     */
    public function notifyNeedsRoute($adminId, $leaveRequestId, $request)
    {
        $title = 'Pengajuan Cuti Memerlukan Penentuan Rute';
        $message = sprintf(
            'Pengajuan cuti dari %s tidak memiliki rute approval yang sesuai. Silakan tentukan rute approval secara manual.',
            $request['requester_name'] ?? 'Karyawan'
        );

        $notificationKey = "needs_route_{$leaveRequestId}_{$adminId}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $adminId,
            $title,
            $message,
            self::TYPE_WARNING,
            'leave_requests',
            $leaveRequestId,
            $notificationKey
        );
    }

    /**
     * Notify requester that their request is pending at a specific step
     *
     * @param int $leaveRequestId
     * @param array $request
     * @param string $stepName
     * @param string $approverName
     * @return bool
     */
    public function notifyRequesterPendingStep($leaveRequestId, $request, $stepName, $approverName)
    {
        $title = 'Pengajuan Cuti Sedang Diproses';
        $message = sprintf(
            'Pengajuan %s Anda sedang menunggu persetujuan dari %s (%s).',
            $request['leave_type_name'] ?? 'Cuti',
            $approverName,
            $stepName
        );

        $notificationKey = "pending_step_{$leaveRequestId}_{$stepName}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $request['user_id'],
            $title,
            $message,
            self::TYPE_INFO,
            'leave_requests',
            $leaveRequestId,
            $notificationKey
        );
    }

    /**
     * Notify requester that a step has been approved (but more steps remain)
     *
     * @param int $leaveRequestId
     * @param array $request
     * @param int $stepNo
     * @param int $totalSteps
     * @param string $stepName
     * @return bool
     */
    public function notifyRequesterStepApproved($leaveRequestId, $request, $stepNo, $totalSteps, $stepName)
    {
        $title = 'Tahap Persetujuan Cuti Berhasil';
        $message = sprintf(
            'Pengajuan %s Anda telah disetujui oleh %s (Tahap %d dari %d).',
            $request['leave_type_name'] ?? 'Cuti',
            $stepName,
            $stepNo,
            $totalSteps
        );

        $notificationKey = "step_approved_{$leaveRequestId}_{$stepNo}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $request['user_id'],
            $title,
            $message,
            self::TYPE_INFO,
            'leave_requests',
            $leaveRequestId,
            $notificationKey
        );
    }

    /**
     * Notify user about quota change
     *
     * @param int $userId
     * @param string $leaveTypeName
     * @param float $changeAmount
     * @param float $newRemaining
     * @param string $reason
     * @return bool
     */
    public function notifyQuotaChange($userId, $leaveTypeName, $changeAmount, $newRemaining, $reason)
    {
        $changeText = $changeAmount > 0 ? '+' . $changeAmount : $changeAmount;
        $title = 'Perubahan Kuota Cuti';
        $message = sprintf(
            'Kuota %s Anda berubah %s hari. Sisa kuota: %s hari. %s',
            $leaveTypeName,
            $changeText,
            $newRemaining,
            $reason
        );

        // Don't deduplicate quota notifications
        return $this->notify(
            $userId,
            $title,
            $message,
            $changeAmount > 0 ? self::TYPE_SUCCESS : self::TYPE_INFO,
            'leave_quotas',
            null,
            null
        );
    }

    /**
     * Send a generic notification
     *
     * @param int $userId
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $relatedTable
     * @param int|null $relatedId
     * @param string|null $notificationKey For deduplication
     * @return bool
     */
    public function notify($userId, $title, $message, $type = self::TYPE_INFO, $relatedTable = null, $relatedId = null, $notificationKey = null)
    {
        if (!$userId) {
            log_message('warning', 'NotificationService: No user ID provided');
            return false;
        }

        // Check for duplicate if key provided
        if ($notificationKey && $this->isDuplicate($notificationKey)) {
            log_message('debug', 'NotificationService: Duplicate notification skipped: ' . $notificationKey);
            return false;
        }

        $data = array(
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_table' => $relatedTable,
            'related_id' => $relatedId,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        );

        $result = $this->db->insert('notifications', $data);

        if ($result && $notificationKey) {
            $this->recordNotificationKey($notificationKey);
        }

        return $result;
    }

    /**
     * Check if a notification with this key was already sent recently
     *
     * @param string $key
     * @return bool
     */
    public function isDuplicate($key)
    {
        // Check in cache/memory first (session-based for simplicity)
        if (!isset($this->CI->session)) {
            $this->CI->load->library('session');
        }

        $sentNotifications = $this->CI->session->userdata('sent_notifications') ?: array();

        if (isset($sentNotifications[$key])) {
            $sentTime = $sentNotifications[$key];
            if (time() - $sentTime < $this->dedupeWindow) {
                return true;
            }
        }

        // Also check database for more reliable deduplication
        $query = $this->db->query("
            SELECT id FROM notifications
            WHERE related_table = 'notification_keys'
              AND message = ?
              AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            LIMIT 1
        ", array($key, $this->dedupeWindow));

        return $query->num_rows() > 0;
    }

    /**
     * Record a notification key for deduplication
     *
     * @param string $key
     */
    protected function recordNotificationKey($key)
    {
        // Store in session
        if (!isset($this->CI->session)) {
            $this->CI->load->library('session');
        }

        $sentNotifications = $this->CI->session->userdata('sent_notifications') ?: array();
        $sentNotifications[$key] = time();

        // Clean old entries
        $sentNotifications = array_filter($sentNotifications, function($time) {
            return time() - $time < $this->dedupeWindow * 2;
        });

        $this->CI->session->set_userdata('sent_notifications', $sentNotifications);
    }

    /**
     * Get unread count for a user
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount($userId)
    {
        $query = $this->db->query("
            SELECT COUNT(id) as count
            FROM notifications
            WHERE user_id = ? AND is_read = 0
        ", array($userId));

        $result = $query->row_array();
        return intval($result['count']);
    }

    /**
     * Get recent notifications for a user
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentNotifications($userId, $limit = 10)
    {
        $query = $this->db->query("
            SELECT *
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ", array($userId, $limit));

        return $query->result_array();
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead($notificationId, $userId)
    {
        return $this->db->update('notifications', array(
            'is_read' => 1,
        ), array(
            'id' => $notificationId,
            'user_id' => $userId,
        ));
    }

    /**
     * Mark all notifications as read for a user
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead($userId)
    {
        return $this->db->update('notifications', array(
            'is_read' => 1,
        ), array(
            'user_id' => $userId,
            'is_read' => 0,
        ));
    }

    /**
     * Bulk send notifications to multiple users
     *
     * @param array $userIds
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $relatedTable
     * @param int|null $relatedId
     * @return int Number of notifications sent
     */
    public function notifyMany($userIds, $title, $message, $type = self::TYPE_INFO, $relatedTable = null, $relatedId = null)
    {
        $count = 0;
        foreach ($userIds as $userId) {
            if ($this->notify($userId, $title, $message, $type, $relatedTable, $relatedId)) {
                $count++;
            }
        }
        return $count;
    }
}
