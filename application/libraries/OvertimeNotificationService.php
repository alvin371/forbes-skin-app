<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeNotificationService Library
 *
 * Handles in-app workflow notifications for overtime requests with deduplication.
 *
 * Notification Triggers:
 * - On submit: Notify first approver
 * - On step approval: Notify next approver
 * - On final approval: Notify requester
 * - On rejection: Notify requester with rejection reason
 */
class OvertimeNotificationService
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
     * Notify approver that they have a new pending overtime approval
     *
     * @param int $approverId
     * @param int $overtimeRequestId
     * @param array $request Overtime request details
     * @return bool
     */
    public function notifyApproverAssigned($approverId, $overtimeRequestId, $request)
    {
        $title = 'Pengajuan Lembur Baru Perlu Disetujui';
        $message = sprintf(
            '%s mengajukan lembur %s pada tanggal %s (%s - %s, %s jam). Silakan review dan berikan persetujuan.',
            $request['requester_name'] ?? 'Karyawan',
            $request['overtime_type_name'] ?? 'Lembur',
            date('d M Y', strtotime($request['overtime_date'])),
            $request['start_time'],
            $request['end_time'],
            $request['duration_hours']
        );

        $notificationKey = "overtime_approver_assigned_{$overtimeRequestId}_{$approverId}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $approverId,
            $title,
            $message,
            self::TYPE_INFO,
            'overtime_requests',
            $overtimeRequestId,
            $notificationKey
        );
    }

    /**
     * Notify requester that their overtime has been approved
     *
     * @param int $overtimeRequestId
     * @param array $request
     * @return bool
     */
    public function notifyRequesterApproved($overtimeRequestId, $request)
    {
        $title = 'Pengajuan Lembur Disetujui';
        $message = sprintf(
            'Pengajuan lembur %s Anda pada tanggal %s (%s - %s, %s jam) telah disetujui.',
            $request['overtime_type_name'] ?? 'Lembur',
            date('d M Y', strtotime($request['overtime_date'])),
            $request['start_time'],
            $request['end_time'],
            $request['duration_hours']
        );

        $notificationKey = "overtime_request_approved_{$overtimeRequestId}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $request['user_id'],
            $title,
            $message,
            self::TYPE_SUCCESS,
            'overtime_requests',
            $overtimeRequestId,
            $notificationKey
        );
    }

    /**
     * Notify requester that their overtime has been rejected
     *
     * @param int $overtimeRequestId
     * @param array $request
     * @param string $stepName Which step rejected
     * @param string|null $reason Rejection reason
     * @return bool
     */
    public function notifyRequesterRejected($overtimeRequestId, $request, $stepName, $reason = null)
    {
        $title = 'Pengajuan Lembur Ditolak';
        $message = sprintf(
            'Pengajuan lembur %s Anda pada tanggal %s telah ditolak oleh %s.',
            $request['overtime_type_name'] ?? 'Lembur',
            date('d M Y', strtotime($request['overtime_date'])),
            $stepName
        );

        if ($reason) {
            $message .= ' Alasan: ' . $reason;
        }

        $notificationKey = "overtime_request_rejected_{$overtimeRequestId}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $request['user_id'],
            $title,
            $message,
            self::TYPE_ERROR,
            'overtime_requests',
            $overtimeRequestId,
            $notificationKey
        );
    }

    /**
     * Notify requester that their request is pending at a specific step
     *
     * @param int $overtimeRequestId
     * @param array $request
     * @param string $stepName
     * @param string $approverName
     * @return bool
     */
    public function notifyRequesterPendingStep($overtimeRequestId, $request, $stepName, $approverName)
    {
        $title = 'Pengajuan Lembur Sedang Diproses';
        $message = sprintf(
            'Pengajuan lembur %s Anda sedang menunggu persetujuan dari %s (%s).',
            $request['overtime_type_name'] ?? 'Lembur',
            $approverName,
            $stepName
        );

        $notificationKey = "overtime_pending_step_{$overtimeRequestId}_{$stepName}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $request['user_id'],
            $title,
            $message,
            self::TYPE_INFO,
            'overtime_requests',
            $overtimeRequestId,
            $notificationKey
        );
    }

    /**
     * Notify requester that a step has been approved (but more steps remain)
     *
     * @param int $overtimeRequestId
     * @param array $request
     * @param int $stepNo
     * @param int $totalSteps
     * @param string $stepName
     * @return bool
     */
    public function notifyRequesterStepApproved($overtimeRequestId, $request, $stepNo, $totalSteps, $stepName)
    {
        $title = 'Tahap Persetujuan Lembur Berhasil';
        $message = sprintf(
            'Pengajuan lembur %s Anda telah disetujui oleh %s (Tahap %d dari %d).',
            $request['overtime_type_name'] ?? 'Lembur',
            $stepName,
            $stepNo,
            $totalSteps
        );

        $notificationKey = "overtime_step_approved_{$overtimeRequestId}_{$stepNo}";

        if ($this->isDuplicate($notificationKey)) {
            return false;
        }

        return $this->notify(
            $request['user_id'],
            $title,
            $message,
            self::TYPE_INFO,
            'overtime_requests',
            $overtimeRequestId,
            $notificationKey
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
            log_message('warning', 'OvertimeNotificationService: No user ID provided');
            return false;
        }

        // Check for duplicate if key provided
        if ($notificationKey && $this->isDuplicate($notificationKey)) {
            log_message('debug', 'OvertimeNotificationService: Duplicate notification skipped: ' . $notificationKey);
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
        if (isset($this->CI->session)) {
            $sentNotifications = $this->CI->session->userdata('sent_overtime_notifications') ?: array();

            if (isset($sentNotifications[$key])) {
                $sentTime = $sentNotifications[$key];
                if (time() - $sentTime < $this->dedupeWindow) {
                    return true;
                }
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
        // Store in session if available
        if (isset($this->CI->session)) {
            $sentNotifications = $this->CI->session->userdata('sent_overtime_notifications') ?: array();
            $sentNotifications[$key] = time();

            // Clean old entries
            $sentNotifications = array_filter($sentNotifications, function($time) {
                return time() - $time < $this->dedupeWindow * 2;
            });

            $this->CI->session->set_userdata('sent_overtime_notifications', $sentNotifications);
        }
    }
}
