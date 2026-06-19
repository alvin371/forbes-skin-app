<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeNotificationService Library (overtime domain facade)
 *
 * Thin facade over NotificationDispatcher, mirroring NotificationService for the
 * overtime domain. Keeps the original public method names so OvertimeWorkflowEngine
 * is untouched; all plumbing lives in the shared dispatcher / event registry.
 */
class OvertimeNotificationService
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
    }

    /**
     * Notify approver of a new pending overtime approval.
     */
    public function notifyApproverAssigned($approverId, $overtimeRequestId, $request, $stepId = null)
    {
        return $this->CI->notificationdispatcher->dispatch($approverId, 'overtime.approver_assigned', array(
            'overtime_request_id' => $overtimeRequestId,
            'approval_step_id'    => $stepId,
            'approver_id'         => $approverId,
            'requester_name'      => $request['requester_name'] ?? null,
            'overtime_type_name'  => $request['overtime_type_name'] ?? null,
            'overtime_date'       => $request['overtime_date'] ?? null,
            'start_time'          => $request['start_time'] ?? null,
            'end_time'            => $request['end_time'] ?? null,
            'duration_hours'      => $request['duration_hours'] ?? null,
        ));
    }

    /**
     * Notify requester their overtime was approved.
     */
    public function notifyRequesterApproved($overtimeRequestId, $request)
    {
        return $this->CI->notificationdispatcher->dispatch($request['user_id'] ?? null, 'overtime.approved', array(
            'overtime_request_id' => $overtimeRequestId,
            'overtime_type_name'  => $request['overtime_type_name'] ?? null,
            'overtime_date'       => $request['overtime_date'] ?? null,
            'start_time'          => $request['start_time'] ?? null,
            'end_time'            => $request['end_time'] ?? null,
            'duration_hours'      => $request['duration_hours'] ?? null,
        ));
    }

    /**
     * Notify requester their overtime was rejected.
     */
    public function notifyRequesterRejected($overtimeRequestId, $request, $stepName, $reason = null)
    {
        return $this->CI->notificationdispatcher->dispatch($request['user_id'] ?? null, 'overtime.rejected', array(
            'overtime_request_id' => $overtimeRequestId,
            'overtime_type_name'  => $request['overtime_type_name'] ?? null,
            'overtime_date'       => $request['overtime_date'] ?? null,
            'step_name'           => $stepName,
            'reason'              => $reason,
        ));
    }

    /**
     * Notify requester their request is pending at a specific step.
     */
    public function notifyRequesterPendingStep($overtimeRequestId, $request, $stepName, $approverName)
    {
        return $this->CI->notificationdispatcher->dispatch($request['user_id'] ?? null, 'overtime.pending_step', array(
            'overtime_request_id' => $overtimeRequestId,
            'overtime_type_name'  => $request['overtime_type_name'] ?? null,
            'approver_name'       => $approverName,
            'step_name'           => $stepName,
        ));
    }

    /**
     * Notify requester a step was approved (more steps remain).
     */
    public function notifyRequesterStepApproved($overtimeRequestId, $request, $stepNo, $totalSteps, $stepName)
    {
        return $this->CI->notificationdispatcher->dispatch($request['user_id'] ?? null, 'overtime.step_approved', array(
            'overtime_request_id' => $overtimeRequestId,
            'overtime_type_name'  => $request['overtime_type_name'] ?? null,
            'step_no'             => $stepNo,
            'total_steps'         => $totalSteps,
            'step_name'           => $stepName,
        ));
    }
}
