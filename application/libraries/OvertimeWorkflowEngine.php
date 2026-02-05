<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeWorkflowEngine Library
 *
 * Manages the approval workflow state machine for overtime requests.
 * Handles workflow initialization, approval processing, step advancement,
 * and final approval with ledger creation.
 *
 * Workflow States:
 * - IN_PROGRESS: Active workflow with pending steps
 * - COMPLETED: All steps approved, overtime request approved
 * - REJECTED: Request rejected at any step
 * - CANCELLED: Request cancelled by requester
 */
class OvertimeWorkflowEngine
{
    protected $CI;
    protected $db;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        // Load required models and libraries
        $this->CI->load->model('OvertimeRequestModel');
        $this->CI->load->model('OvertimeApprovalInstanceModel');
        $this->CI->load->model('OvertimeApprovalStepModel');
        $this->CI->load->model('OvertimeLedgerModel');
        $this->CI->load->library('OvertimeRouteResolver');
        $this->CI->load->library('OvertimeNotificationService');
    }

    /**
     * Initialize a new approval workflow for an overtime request
     *
     * @param int $overtimeRequestId
     * @return array Result with success status and message
     */
    public function initializeWorkflow($overtimeRequestId)
    {
        // Get overtime request details
        $request = $this->CI->OvertimeRequestModel->get_by_id($overtimeRequestId);
        if (!$request) {
            return array(
                'success' => false,
                'message' => 'Overtime request not found',
            );
        }

        // Check if workflow already exists
        $existing = $this->CI->OvertimeApprovalInstanceModel->get_by_overtime_request($overtimeRequestId);
        if ($existing) {
            return array(
                'success' => false,
                'message' => 'Workflow already exists for this request',
                'instance_id' => $existing['id'],
            );
        }

        // Resolve the overtime route
        $route = $this->CI->overtimerouteresolver->resolve(
            $request['user_id'],
            $request['overtime_type_id'],
            $request['duration_hours'],
            date('Y-m-d')
        );

        if (!$route) {
            log_message('error', 'OvertimeWorkflowEngine: No matching route found for overtime request ' . $overtimeRequestId);
            return array(
                'success' => false,
                'code' => 'NO_ROUTE',
                'message' => 'No approval route configured. Please contact HR.',
            );
        }

        if (empty($route['steps'])) {
            log_message('error', 'OvertimeWorkflowEngine: Route has no steps for overtime request ' . $overtimeRequestId);
            return array(
                'success' => false,
                'code' => 'NO_STEPS',
                'message' => 'Approval route has no steps. Please contact HR.',
            );
        }

        // Create approval instance and steps
        $this->db->trans_start();

        // Create approval instance with route snapshot
        $instanceData = array(
            'overtime_request_id' => $overtimeRequestId,
            'route_version_id' => $route['route_id'],
            'route_id' => $route['route_id'],
            'route_snapshot' => json_encode($route),
            'total_steps' => $route['total_steps'],
            'current_step' => 1,
            'status' => 'IN_PROGRESS',
        );
        $instanceId = $this->CI->OvertimeApprovalInstanceModel->insert($instanceData);

        // Create approval steps
        foreach ($route['steps'] as $step) {
            $stepData = array(
                'approval_instance_id' => $instanceId,
                'overtime_request_id' => $overtimeRequestId,
                'step_no' => $step['step_no'],
                'step_name' => $step['step_name'],
                'assigned_approver_id' => $step['assigned_approver_id'],
                'action' => 'PENDING',
                'version' => 1,
            );
            $this->CI->OvertimeApprovalStepModel->insert($stepData);
        }

        // Update overtime request
        $this->CI->OvertimeRequestModel->update($overtimeRequestId, array(
            'status' => 'IN_REVIEW',
            'approval_instance_id' => $instanceId,
            'submitted_at' => date('Y-m-d H:i:s'),
            'current_step' => 1,
        ));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return array(
                'success' => false,
                'message' => 'Failed to initialize workflow',
            );
        }

        // Notify first approver
        $firstStep = $route['steps'][0];
        if ($firstStep['assigned_approver_id']) {
            $this->CI->overtimenotificationservice->notifyApproverAssigned(
                $firstStep['assigned_approver_id'],
                $overtimeRequestId,
                $request
            );
        }

        return array(
            'success' => true,
            'message' => 'Workflow initialized successfully',
            'instance_id' => $instanceId,
            'route_code' => $route['route_code'],
            'total_steps' => $route['total_steps'],
        );
    }

    /**
     * Process an approval action (approve or reject)
     *
     * @param int $stepId The approval step ID
     * @param int $approverId The user performing the action
     * @param string $action 'APPROVED' or 'REJECTED'
     * @param string|null $notes Optional notes
     * @return array Result with success status and message
     */
    public function processApproval($stepId, $approverId, $action, $notes = null)
    {
        // Validate action
        if (!in_array($action, array('APPROVED', 'REJECTED'))) {
            return array(
                'success' => false,
                'message' => 'Invalid action. Must be APPROVED or REJECTED.',
            );
        }

        // Get step details
        $step = $this->CI->OvertimeApprovalStepModel->get_by_id($stepId);
        if (!$step) {
            return array(
                'success' => false,
                'message' => 'Approval step not found',
            );
        }

        // Check if user can approve this step
        if (!$this->canUserApprove($approverId, $step)) {
            return array(
                'success' => false,
                'message' => 'You are not authorized to approve this step',
            );
        }

        // Check if step is still pending
        if ($step['action'] !== 'PENDING') {
            return array(
                'success' => false,
                'message' => 'This step has already been processed',
            );
        }

        // Get approval instance
        $instance = $this->CI->OvertimeApprovalInstanceModel->get_by_id($step['approval_instance_id']);
        if (!$instance || $instance['status'] !== 'IN_PROGRESS') {
            return array(
                'success' => false,
                'message' => 'Workflow is not in progress',
            );
        }

        // Check if this is the current step
        if ($step['step_no'] != $instance['current_step']) {
            return array(
                'success' => false,
                'message' => 'This is not the current approval step',
            );
        }

        // Use optimistic locking to prevent double approval
        $this->db->trans_start();

        $updateResult = $this->db->query("
            UPDATE overtime_approval_steps
            SET action = ?,
                action_at = ?,
                actual_approver_id = ?,
                notes = ?,
                version = version + 1,
                updated_at = ?
            WHERE id = ? AND version = ? AND action = 'PENDING'
        ", array(
            $action,
            date('Y-m-d H:i:s'),
            $approverId,
            $notes,
            date('Y-m-d H:i:s'),
            $stepId,
            $step['version']
        ));

        if ($this->db->affected_rows() === 0) {
            $this->db->trans_rollback();
            return array(
                'success' => false,
                'message' => 'Step was already processed by another user',
            );
        }

        // Handle based on action
        if ($action === 'REJECTED') {
            $result = $this->handleRejection($instance, $step, $approverId, $notes);
        } else {
            $result = $this->handleApproval($instance, $step, $approverId);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return array(
                'success' => false,
                'message' => 'Failed to process approval',
            );
        }

        return $result;
    }

    /**
     * Handle an approval action
     *
     * @param array $instance
     * @param array $step
     * @param int $approverId
     * @return array
     */
    protected function handleApproval($instance, $step, $approverId)
    {
        $overtimeRequestId = $instance['overtime_request_id'];
        $request = $this->CI->OvertimeRequestModel->get_by_id($overtimeRequestId);

        // Check if this is the final step
        if ($step['step_no'] >= $instance['total_steps']) {
            // Final approval - complete workflow
            return $this->handleFinalApproval($overtimeRequestId, $approverId, $instance);
        }

        // Advance to next step
        $nextStepNo = $step['step_no'] + 1;

        $this->CI->OvertimeApprovalInstanceModel->update($instance['id'], array(
            'current_step' => $nextStepNo,
        ));

        $this->CI->OvertimeRequestModel->update($overtimeRequestId, array(
            'current_step' => $nextStepNo,
        ));

        // Get next step approver and notify
        $nextStep = $this->db->query("
            SELECT * FROM overtime_approval_steps
            WHERE approval_instance_id = ? AND step_no = ?
        ", array($instance['id'], $nextStepNo))->row_array();

        if ($nextStep && $nextStep['assigned_approver_id']) {
            $this->CI->overtimenotificationservice->notifyApproverAssigned(
                $nextStep['assigned_approver_id'],
                $overtimeRequestId,
                $request
            );
        }

        return array(
            'success' => true,
            'message' => 'Step approved. Advanced to step ' . $nextStepNo,
            'is_final' => false,
            'next_step' => $nextStepNo,
        );
    }

    /**
     * Handle final approval
     *
     * @param int $overtimeRequestId
     * @param int $approverId
     * @param array|null $instance
     * @return array
     */
    public function handleFinalApproval($overtimeRequestId, $approverId, $instance = null)
    {
        if (!$instance) {
            $instance = $this->CI->OvertimeApprovalInstanceModel->get_by_overtime_request($overtimeRequestId);
        }

        $request = $this->CI->OvertimeRequestModel->get_by_id($overtimeRequestId);
        if (!$request) {
            return array(
                'success' => false,
                'message' => 'Overtime request not found',
            );
        }

        // Create ledger entry
        $ledgerId = $this->CI->OvertimeLedgerModel->create_from_request($request, $approverId);
        if (!$ledgerId) {
            log_message('error', 'OvertimeWorkflowEngine: Failed to create ledger entry for request ' . $overtimeRequestId);
        }

        // Update instance status
        $this->CI->OvertimeApprovalInstanceModel->update($instance['id'], array(
            'status' => 'COMPLETED',
        ));

        // Update overtime request
        $this->CI->OvertimeRequestModel->update($overtimeRequestId, array(
            'status' => 'APPROVED',
            'final_approved_by' => $approverId,
            'final_approved_at' => date('Y-m-d H:i:s'),
        ));

        // Notify requester
        $this->CI->overtimenotificationservice->notifyRequesterApproved($overtimeRequestId, $request);

        return array(
            'success' => true,
            'message' => 'Overtime request fully approved',
            'is_final' => true,
            'ledger_id' => $ledgerId,
        );
    }

    /**
     * Handle rejection
     *
     * @param array $instance
     * @param array $step
     * @param int $approverId
     * @param string|null $notes
     * @return array
     */
    protected function handleRejection($instance, $step, $approverId, $notes)
    {
        $overtimeRequestId = $instance['overtime_request_id'];
        $request = $this->CI->OvertimeRequestModel->get_by_id($overtimeRequestId);

        // Update instance status
        $this->CI->OvertimeApprovalInstanceModel->update($instance['id'], array(
            'status' => 'REJECTED',
        ));

        // Update overtime request
        $this->CI->OvertimeRequestModel->update($overtimeRequestId, array(
            'status' => 'REJECTED',
        ));

        // Notify requester
        $this->CI->overtimenotificationservice->notifyRequesterRejected(
            $overtimeRequestId,
            $request,
            $step['step_name'],
            $notes
        );

        return array(
            'success' => true,
            'message' => 'Overtime request rejected at step ' . $step['step_no'],
            'is_final' => true,
            'rejected_at_step' => $step['step_no'],
        );
    }

    /**
     * Cancel a workflow (called when requester cancels)
     *
     * @param int $overtimeRequestId
     * @param int $userId
     * @return array
     */
    public function cancelWorkflow($overtimeRequestId, $userId)
    {
        $request = $this->CI->OvertimeRequestModel->get_by_id($overtimeRequestId);
        if (!$request) {
            return array(
                'success' => false,
                'message' => 'Overtime request not found',
            );
        }

        // Only requester can cancel
        if ($request['user_id'] != $userId) {
            return array(
                'success' => false,
                'message' => 'Only the requester can cancel this request',
            );
        }

        // Check if can be cancelled (SUBMITTED or IN_REVIEW, before any approval)
        if (!in_array($request['status'], array('SUBMITTED', 'IN_REVIEW'))) {
            return array(
                'success' => false,
                'message' => 'Request cannot be cancelled in current status',
            );
        }

        // Check if any step has been approved
        $approvedSteps = $this->db->query("
            SELECT COUNT(*) as count
            FROM overtime_approval_steps
            WHERE overtime_request_id = ? AND action = 'APPROVED'
        ", array($overtimeRequestId))->row_array();

        if ($approvedSteps && (int) $approvedSteps['count'] > 0) {
            return array(
                'success' => false,
                'message' => 'Cannot cancel request that has already received approvals',
            );
        }

        $this->db->trans_start();

        $instance = $this->CI->OvertimeApprovalInstanceModel->get_by_overtime_request($overtimeRequestId);
        if ($instance) {
            $this->CI->OvertimeApprovalInstanceModel->update($instance['id'], array(
                'status' => 'CANCELLED',
            ));
        }

        $this->CI->OvertimeRequestModel->update($overtimeRequestId, array(
            'status' => 'CANCELLED',
        ));

        $this->db->trans_complete();

        return array(
            'success' => true,
            'message' => 'Overtime request cancelled successfully',
        );
    }

    /**
     * Check if a user can approve a step
     *
     * @param int $userId
     * @param array $step
     * @return bool
     */
    public function canUserApprove($userId, $step)
    {
        // Direct assignment check
        if ($step['assigned_approver_id'] == $userId) {
            return true;
        }

        // Check if user has same role as the assigned approver
        if ($step['assigned_approver_id']) {
            $assignedUser = $this->db->query("
                SELECT role_text FROM user WHERE id = ?
            ", array($step['assigned_approver_id']))->row_array();

            $currentUser = $this->db->query("
                SELECT role_text FROM user WHERE id = ?
            ", array($userId))->row_array();

            if ($assignedUser && $currentUser &&
                $assignedUser['role_text'] == $currentUser['role_text']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get workflow progress for an overtime request
     *
     * @param int $overtimeRequestId
     * @return array|null
     */
    public function getWorkflowProgress($overtimeRequestId)
    {
        $instance = $this->CI->OvertimeApprovalInstanceModel->get_by_overtime_request($overtimeRequestId);
        if (!$instance) {
            return null;
        }

        $steps = $this->CI->OvertimeApprovalStepModel->get_by_overtime_request($overtimeRequestId);

        return array(
            'instance' => $instance,
            'steps' => $steps,
            'current_step' => $instance['current_step'],
            'total_steps' => $instance['total_steps'],
            'status' => $instance['status'],
        );
    }
}
