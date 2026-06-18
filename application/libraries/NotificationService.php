<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NotificationService Library (leave domain facade)
 *
 * Thin facade over NotificationDispatcher. Keeps the original public method names so
 * existing callers (ApprovalWorkflowEngine) are untouched, while all plumbing
 * (dedupe, persistence, templating) now lives in the shared dispatcher / event
 * registry. The previous copy-pasted notify()/isDuplicate()/recordNotificationKey()
 * implementation is gone.
 *
 * Notification triggers (unchanged):
 * - On submit: notify first approver
 * - On step approval: notify next approver
 * - On final approval / rejection: notify requester
 * - On NEEDS_ROUTE: notify HR admins
 */
class NotificationService
{
    const TYPE_INFO    = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR   = 'error';

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('NotificationDispatcher');
        $this->CI->load->model('NotificationModel');
    }

    /**
     * Notify approver of a new pending approval.
     */
    public function notifyApproverAssigned($approverId, $leaveRequestId, $request)
    {
        return $this->CI->notificationdispatcher->dispatch($approverId, 'leave.approver_assigned', array(
            'leave_request_id' => $leaveRequestId,
            'approver_id'      => $approverId,
            'requester_name'   => $request['requester_name'] ?? null,
            'leave_type_name'  => $request['leave_type_name'] ?? null,
            'days_count'       => $request['days_count'] ?? null,
            'start_date'       => $request['start_date'] ?? null,
            'end_date'         => $request['end_date'] ?? null,
        ));
    }

    /**
     * Notify requester their leave was approved.
     */
    public function notifyRequesterApproved($leaveRequestId, $request)
    {
        return $this->CI->notificationdispatcher->dispatch($request['user_id'] ?? null, 'leave.approved', array(
            'leave_request_id' => $leaveRequestId,
            'leave_type_name'  => $request['leave_type_name'] ?? null,
            'days_count'       => $request['days_count'] ?? null,
            'start_date'       => $request['start_date'] ?? null,
            'end_date'         => $request['end_date'] ?? null,
        ));
    }

    /**
     * Notify requester their leave was rejected.
     */
    public function notifyRequesterRejected($leaveRequestId, $request, $stepName, $reason = null)
    {
        return $this->CI->notificationdispatcher->dispatch($request['user_id'] ?? null, 'leave.rejected', array(
            'leave_request_id' => $leaveRequestId,
            'leave_type_name'  => $request['leave_type_name'] ?? null,
            'start_date'       => $request['start_date'] ?? null,
            'end_date'         => $request['end_date'] ?? null,
            'step_name'        => $stepName,
            'reason'           => $reason,
        ));
    }

    /**
     * Notify an HR admin that a request needs manual route assignment.
     */
    public function notifyNeedsRoute($adminId, $leaveRequestId, $request)
    {
        return $this->CI->notificationdispatcher->dispatch($adminId, 'leave.needs_route', array(
            'leave_request_id' => $leaveRequestId,
            'admin_id'         => $adminId,
            'requester_name'   => $request['requester_name'] ?? null,
        ));
    }

    /**
     * Notify requester their request is pending at a specific step.
     */
    public function notifyRequesterPendingStep($leaveRequestId, $request, $stepName, $approverName)
    {
        return $this->CI->notificationdispatcher->dispatch($request['user_id'] ?? null, 'leave.pending_step', array(
            'leave_request_id' => $leaveRequestId,
            'leave_type_name'  => $request['leave_type_name'] ?? null,
            'approver_name'    => $approverName,
            'step_name'        => $stepName,
        ));
    }

    /**
     * Notify requester a step was approved (more steps remain).
     */
    public function notifyRequesterStepApproved($leaveRequestId, $request, $stepNo, $totalSteps, $stepName)
    {
        return $this->CI->notificationdispatcher->dispatch($request['user_id'] ?? null, 'leave.step_approved', array(
            'leave_request_id' => $leaveRequestId,
            'leave_type_name'  => $request['leave_type_name'] ?? null,
            'step_no'          => $stepNo,
            'total_steps'      => $totalSteps,
            'step_name'        => $stepName,
        ));
    }

    /**
     * Notify a user about a leave quota change.
     */
    public function notifyQuotaChange($userId, $leaveTypeName, $changeAmount, $newRemaining, $reason)
    {
        return $this->CI->notificationdispatcher->dispatch($userId, 'leave.quota_change', array(
            'leave_type_name' => $leaveTypeName,
            'change_amount'   => $changeAmount,
            'new_remaining'   => $newRemaining,
            'reason'          => $reason,
        ));
    }

    /**
     * Generic notification (no registry template). Retained for compatibility.
     */
    public function notify($userId, $title, $message, $type = self::TYPE_INFO, $relatedTable = null, $relatedId = null, $notificationKey = null)
    {
        return $this->CI->notificationdispatcher->emit($userId, array(
            'title'         => $title,
            'message'       => $message,
            'type'          => $type,
            'related_table' => $relatedTable,
            'related_id'    => $relatedId,
            'dedupe_key'    => $notificationKey,
        ));
    }

    /**
     * Bulk send a generic notification to many users.
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

    // --- Read helpers (delegate to NotificationModel) ---

    public function getUnreadCount($userId)
    {
        return $this->CI->NotificationModel->unreadCount($userId);
    }

    public function getRecentNotifications($userId, $limit = 10)
    {
        return $this->CI->NotificationModel->getRecent($userId, $limit);
    }

    public function markAsRead($notificationId, $userId)
    {
        return $this->CI->NotificationModel->markRead($notificationId, $userId);
    }

    public function markAllAsRead($userId)
    {
        return $this->CI->NotificationModel->markAllRead($userId);
    }
}
