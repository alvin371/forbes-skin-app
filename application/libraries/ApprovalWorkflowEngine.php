<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ApprovalWorkflowEngine Library
 *
 * Manages the approval workflow state machine for leave requests.
 * Handles workflow initialization, approval processing, step advancement,
 * and final approval with quota deduction.
 *
 * Workflow States:
 * - IN_PROGRESS: Active workflow with pending steps
 * - COMPLETED: All steps approved, leave request approved
 * - REJECTED: Request rejected at any step
 * - CANCELLED: Request cancelled by requester
 * - NEEDS_ROUTE: No matching route found, requires HR intervention
 */
class ApprovalWorkflowEngine
{
    protected $CI;
    protected $db;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        // Load required libraries
        $this->CI->load->library('ApprovalRouteResolver');
        $this->CI->load->library('NotificationService');
        $this->CI->load->helper('sentry');
    }

    /**
     * Initialize a new approval workflow for a leave request
     *
     * @param int $leaveRequestId
     * @return array Result with success status and message
     */
    public function initializeWorkflow($leaveRequestId)
    {
        // Get leave request details
        $request = $this->getLeaveRequest($leaveRequestId);
        if (!$request) {
            sentry_capture_message('Leave workflow initialization failed: request not found', array(
                'library' => 'ApprovalWorkflowEngine',
                'method' => 'initializeWorkflow',
                'leave_request_id' => (int) $leaveRequestId,
            ));
            return array(
                'success' => false,
                'message' => 'Leave request not found',
            );
        }

        // Check if workflow already exists
        $existing = $this->getApprovalInstance($leaveRequestId);
        if ($existing) {
            sentry_capture_message('Leave workflow already exists', array(
                'library' => 'ApprovalWorkflowEngine',
                'method' => 'initializeWorkflow',
                'leave_request_id' => (int) $leaveRequestId,
                'instance_id' => $existing['id'] ?? null,
            ));
            return array(
                'success' => false,
                'message' => 'Workflow already exists for this request',
                'instance_id' => $existing['id'],
            );
        }

        // Resolve the best matching route
        $route = $this->CI->approvalrouteresolver->resolve(
            $request['user_id'],
            array(
                'leave_type_id' => $request['leave_type_id'],
                'days_count' => $request['days_count'],
            ),
            date('Y-m-d')
        );

        if (!$route) {
            // No matching route found - set status to NEEDS_ROUTE
            $this->db->trans_start();

            // Create a placeholder instance
            $instanceData = array(
                'leave_request_id' => $leaveRequestId,
                'route_version_id' => 0,
                'route_snapshot' => json_encode(array('error' => 'No matching route found')),
                'total_steps' => 0,
                'current_step' => 0,
                'status' => 'NEEDS_ROUTE',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            );
            $this->db->insert('approval_instances', $instanceData);
            $instanceId = $this->db->insert_id();

            // Update leave request status
            $this->db->update('leave_requests', array(
                'status' => 'NEEDS_ROUTE',
                'approval_instance_id' => $instanceId,
                'submitted_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ), array('id' => $leaveRequestId));

            $this->db->trans_complete();

            // Notify HR admins
            $this->notifyHrAdmins($leaveRequestId, $request);
            sentry_capture_message('Leave workflow needs manual route', array(
                'library' => 'ApprovalWorkflowEngine',
                'method' => 'initializeWorkflow',
                'leave_request_id' => (int) $leaveRequestId,
                'user_id' => $request['user_id'] ?? null,
            ));

            return array(
                'success' => true,
                'needs_route' => true,
                'message' => 'No matching approval route found. HR has been notified.',
                'instance_id' => $instanceId,
            );
        }

        // Create approval instance and steps
        $this->db->trans_start();

        // Create approval instance with route snapshot
        $instanceData = array(
            'leave_request_id' => $leaveRequestId,
            'route_version_id' => $route['route_version_id'],
            'route_snapshot' => json_encode($route),
            'total_steps' => $route['total_steps'],
            'current_step' => 1,
            'status' => 'IN_PROGRESS',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $this->db->insert('approval_instances', $instanceData);
        $instanceId = $this->db->insert_id();

        // Create approval steps
        foreach ($route['steps'] as $step) {
            $stepData = array(
                'approval_instance_id' => $instanceId,
                'leave_request_id' => $leaveRequestId,
                'step_no' => $step['step_no'],
                'step_name' => $step['step_name'],
                'assigned_approver_id' => $step['assigned_approver_id'],
                'action' => 'PENDING',
                'version' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            );
            $this->db->insert('approval_steps', $stepData);
        }

        // Update leave request
        $this->db->update('leave_requests', array(
            'status' => 'IN_REVIEW',
            'approval_instance_id' => $instanceId,
            'submitted_at' => date('Y-m-d H:i:s'),
            'current_step' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $leaveRequestId));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            sentry_capture_message('Leave workflow transaction failed', array(
                'library' => 'ApprovalWorkflowEngine',
                'method' => 'initializeWorkflow',
                'leave_request_id' => (int) $leaveRequestId,
                'route_code' => $route['route_code'] ?? null,
            ));
            return array(
                'success' => false,
                'message' => 'Failed to initialize workflow',
            );
        }

        // Notify first approver
        $firstStep = $route['steps'][0];
        if ($firstStep['assigned_approver_id']) {
            $this->CI->notificationservice->notifyApproverAssigned(
                $firstStep['assigned_approver_id'],
                $leaveRequestId,
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
        $step = $this->getApprovalStep($stepId);
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
        $instance = $this->db->query("
            SELECT * FROM approval_instances WHERE id = ?
        ", array($step['approval_instance_id']))->row_array();

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
            UPDATE approval_steps
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
        $leaveRequestId = $instance['leave_request_id'];
        $request = $this->getLeaveRequest($leaveRequestId);

        // Check if this is the final step
        if ($step['step_no'] >= $instance['total_steps']) {
            // Final approval - complete workflow
            return $this->handleFinalApproval($leaveRequestId, $approverId, $instance);
        }

        // Advance to next step
        $nextStepNo = $step['step_no'] + 1;

        $this->db->update('approval_instances', array(
            'current_step' => $nextStepNo,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $instance['id']));

        $this->db->update('leave_requests', array(
            'current_step' => $nextStepNo,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $leaveRequestId));

        // Get next step approver
        $nextStep = $this->db->query("
            SELECT * FROM approval_steps
            WHERE approval_instance_id = ? AND step_no = ?
        ", array($instance['id'], $nextStepNo))->row_array();

        if ($nextStep && $nextStep['assigned_approver_id']) {
            $this->CI->notificationservice->notifyApproverAssigned(
                $nextStep['assigned_approver_id'],
                $leaveRequestId,
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
     * @param int $leaveRequestId
     * @param int $approverId
     * @param array $instance
     * @return array
     */
    public function handleFinalApproval($leaveRequestId, $approverId, $instance = null)
    {
        if (!$instance) {
            $instance = $this->getApprovalInstance($leaveRequestId);
        }

        $request = $this->getLeaveRequest($leaveRequestId);
        if (!$request) {
            sentry_capture_message('Leave workflow cancel failed: request not found', array(
                'library' => 'ApprovalWorkflowEngine',
                'method' => 'cancelWorkflow',
                'leave_request_id' => (int) $leaveRequestId,
                'user_id' => (int) $userId,
            ));
            return array(
                'success' => false,
                'message' => 'Leave request not found',
            );
        }

        // Load LeaveQuotaService for quota deduction
        $this->CI->load->library('LeaveQuotaService');

        // Deduct quota
        $quotaResult = $this->CI->leavequotaservice->deductQuota(
            $request['user_id'],
            $request['leave_type_id'],
            $request['days_count'],
            $leaveRequestId,
            $approverId
        );

        if (!$quotaResult['success']) {
            // Quota insufficient - but still mark as approved with warning
            log_message('warning', 'Leave approved but quota issue: ' . $quotaResult['message']);
        }

        // Create ledger entry
        $this->CI->leavequotaservice->createLedgerEntry($request, $approverId);

        // Update instance status
        $this->db->update('approval_instances', array(
            'status' => 'COMPLETED',
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $instance['id']));

        // Update leave request
        $this->db->update('leave_requests', array(
            'status' => 'APPROVED',
            'final_approved_by' => $approverId,
            'final_approved_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $leaveRequestId));

        // Notify requester
        $this->CI->notificationservice->notifyRequesterApproved($leaveRequestId, $request);

        return array(
            'success' => true,
            'message' => 'Leave request fully approved',
            'is_final' => true,
            'quota_warning' => isset($quotaResult['warning']) ? $quotaResult['warning'] : null,
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
        $leaveRequestId = $instance['leave_request_id'];
        $request = $this->getLeaveRequest($leaveRequestId);

        // Update instance status
        $this->db->update('approval_instances', array(
            'status' => 'REJECTED',
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $instance['id']));

        // Update leave request
        $this->db->update('leave_requests', array(
            'status' => 'REJECTED',
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $leaveRequestId));

        // Notify requester
        $this->CI->notificationservice->notifyRequesterRejected(
            $leaveRequestId,
            $request,
            $step['step_name'],
            $notes
        );

        return array(
            'success' => true,
            'message' => 'Leave request rejected at step ' . $step['step_no'],
            'is_final' => true,
            'rejected_at_step' => $step['step_no'],
        );
    }

    /**
     * Cancel a workflow (called when requester cancels)
     *
     * @param int $leaveRequestId
     * @param int $userId
     * @return array
     */
    public function cancelWorkflow($leaveRequestId, $userId)
    {
        $request = $this->getLeaveRequest($leaveRequestId);
        if (!$request) {
            return array(
                'success' => false,
                'message' => 'Leave request not found',
            );
        }

        // Only requester can cancel
        if ($request['user_id'] != $userId) {
            sentry_capture_message('Leave workflow cancel denied', array(
                'library' => 'ApprovalWorkflowEngine',
                'method' => 'cancelWorkflow',
                'leave_request_id' => (int) $leaveRequestId,
                'user_id' => (int) $userId,
                'request_owner_id' => $request['user_id'] ?? null,
            ));
            return array(
                'success' => false,
                'message' => 'Only the requester can cancel this request',
            );
        }

        // Check if can be cancelled
        if (in_array($request['status'], array('APPROVED', 'REJECTED', 'CANCELLED'))) {
            sentry_capture_message('Leave workflow cancel rejected due to status', array(
                'library' => 'ApprovalWorkflowEngine',
                'method' => 'cancelWorkflow',
                'leave_request_id' => (int) $leaveRequestId,
                'user_id' => (int) $userId,
                'status' => $request['status'],
            ));
            return array(
                'success' => false,
                'message' => 'Request cannot be cancelled in current status',
            );
        }

        $this->db->trans_start();

        $instance = $this->getApprovalInstance($leaveRequestId);
        if ($instance) {
            $this->db->update('approval_instances', array(
                'status' => 'CANCELLED',
                'updated_at' => date('Y-m-d H:i:s'),
            ), array('id' => $instance['id']));
        }

        $this->db->update('leave_requests', array(
            'status' => 'CANCELLED',
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $leaveRequestId));

        $this->db->trans_complete();

        return array(
            'success' => true,
            'message' => 'Leave request cancelled successfully',
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
        return isset($step['assigned_approver_id']) && (int) $step['assigned_approver_id'] === (int) $userId;
    }

    /**
     * Get pending steps for an approver
     *
     * @param int $approverId
     * @return array
     */
    public function getPendingStepsForApprover($approverId)
    {
        $query = $this->db->query("
            SELECT
                s.*,
                lr.request_no,
                lr.user_id as requester_id,
                lr.start_date,
                lr.end_date,
                lr.days_count,
                lr.reason,
                lt.name as leave_type_name,
                u.full_name as requester_name,
                ai.total_steps,
                ai.current_step
            FROM approval_steps s
            INNER JOIN approval_instances ai ON s.approval_instance_id = ai.id
            INNER JOIN leave_requests lr ON s.leave_request_id = lr.id
            INNER JOIN leave_types lt ON lr.leave_type_id = lt.id
            INNER JOIN user u ON lr.user_id = u.id
            WHERE s.action = 'PENDING'
              AND ai.status = 'IN_PROGRESS'
              AND s.step_no = ai.current_step
              AND s.assigned_approver_id = ?
            ORDER BY lr.created_at ASC
        ", array($approverId));

        return $query->result_array();
    }

    /**
     * Get approval history for an approver
     *
     * @param int $approverId
     * @param int $limit
     * @return array
     */
    public function getApprovalHistory($approverId, $limit = 50)
    {
        $query = $this->db->query("
            SELECT
                s.*,
                lr.request_no,
                lr.user_id as requester_id,
                lr.start_date,
                lr.end_date,
                lr.days_count,
                lt.name as leave_type_name,
                u.full_name as requester_name
            FROM approval_steps s
            INNER JOIN leave_requests lr ON s.leave_request_id = lr.id
            INNER JOIN leave_types lt ON lr.leave_type_id = lt.id
            INNER JOIN user u ON lr.user_id = u.id
            WHERE s.actual_approver_id = ?
              AND s.action IN ('APPROVED', 'REJECTED')
            ORDER BY s.action_at DESC
            LIMIT ?
        ", array($approverId, $limit));

        return $query->result_array();
    }

    /**
     * Get workflow progress for a leave request
     *
     * @param int $leaveRequestId
     * @return array|null
     */
    public function getWorkflowProgress($leaveRequestId)
    {
        $instance = $this->getApprovalInstance($leaveRequestId);
        if (!$instance) {
            return null;
        }

        $steps = $this->db->query("
            SELECT
                s.*,
                assigned.full_name as assigned_approver_name,
                assigned.role_text as assigned_approver_role,
                actual.full_name as actual_approver_name
            FROM approval_steps s
            LEFT JOIN user assigned ON s.assigned_approver_id = assigned.id
            LEFT JOIN user actual ON s.actual_approver_id = actual.id
            WHERE s.approval_instance_id = ?
            ORDER BY s.step_no ASC
        ", array($instance['id']))->result_array();

        return array(
            'instance' => $instance,
            'steps' => $steps,
            'current_step' => $instance['current_step'],
            'total_steps' => $instance['total_steps'],
            'status' => $instance['status'],
        );
    }

    /**
     * Manually assign a route to a request that needs one
     *
     * @param int $leaveRequestId
     * @param int $routeVersionId
     * @param int $assignedBy
     * @return array
     */
    public function assignRoute($leaveRequestId, $routeVersionId, $assignedBy)
    {
        $request = $this->getLeaveRequest($leaveRequestId);
        if (!$request) {
            return array(
                'success' => false,
                'message' => 'Leave request not found',
            );
        }

        if ($request['status'] !== 'NEEDS_ROUTE') {
            return array(
                'success' => false,
                'message' => 'Request does not need route assignment',
            );
        }

        // Get route details
        $route = $this->CI->approvalrouteresolver->getRouteDetails($routeVersionId);
        if (!$route) {
            return array(
                'success' => false,
                'message' => 'Route not found',
            );
        }

        // Resolve approvers
        $steps = $route['steps'];
        $resolvedSteps = $this->CI->approvalrouteresolver->resolveApprovers($steps, $request['user_id']);

        $this->db->trans_start();

        // Delete old instance
        $oldInstance = $this->getApprovalInstance($leaveRequestId);
        if ($oldInstance) {
            $this->db->delete('approval_steps', array('approval_instance_id' => $oldInstance['id']));
            $this->db->delete('approval_instances', array('id' => $oldInstance['id']));
        }

        // Create new instance
        $routeSnapshot = array(
            'route_version_id' => $routeVersionId,
            'route_code' => $route['route_code'],
            'route_name' => $route['name'],
            'version' => $route['version'],
            'steps' => $resolvedSteps,
            'total_steps' => count($resolvedSteps),
            'manually_assigned' => true,
            'assigned_by' => $assignedBy,
        );

        $instanceData = array(
            'leave_request_id' => $leaveRequestId,
            'route_version_id' => $routeVersionId,
            'route_snapshot' => json_encode($routeSnapshot),
            'total_steps' => count($resolvedSteps),
            'current_step' => 1,
            'status' => 'IN_PROGRESS',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $this->db->insert('approval_instances', $instanceData);
        $instanceId = $this->db->insert_id();

        // Create steps
        foreach ($resolvedSteps as $step) {
            $stepData = array(
                'approval_instance_id' => $instanceId,
                'leave_request_id' => $leaveRequestId,
                'step_no' => $step['step_no'],
                'step_name' => $step['step_name'],
                'assigned_approver_id' => $step['assigned_approver_id'],
                'action' => 'PENDING',
                'version' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            );
            $this->db->insert('approval_steps', $stepData);
        }

        // Update leave request
        $this->db->update('leave_requests', array(
            'status' => 'IN_REVIEW',
            'approval_instance_id' => $instanceId,
            'current_step' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $leaveRequestId));

        $this->db->trans_complete();

        // Notify first approver
        if (!empty($resolvedSteps[0]['assigned_approver_id'])) {
            $this->CI->notificationservice->notifyApproverAssigned(
                $resolvedSteps[0]['assigned_approver_id'],
                $leaveRequestId,
                $request
            );
        }

        return array(
            'success' => true,
            'message' => 'Route assigned successfully',
            'instance_id' => $instanceId,
        );
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Get leave request details
     *
     * @param int $leaveRequestId
     * @return array|null
     */
    protected function getLeaveRequest($leaveRequestId)
    {
        $query = $this->db->query("
            SELECT lr.*, lt.name as leave_type_name, u.full_name as requester_name
            FROM leave_requests lr
            INNER JOIN leave_types lt ON lr.leave_type_id = lt.id
            INNER JOIN user u ON lr.user_id = u.id
            WHERE lr.id = ?
        ", array($leaveRequestId));

        return $query->row_array();
    }

    /**
     * Get approval instance for a leave request
     *
     * @param int $leaveRequestId
     * @return array|null
     */
    protected function getApprovalInstance($leaveRequestId)
    {
        $query = $this->db->query("
            SELECT * FROM approval_instances WHERE leave_request_id = ?
        ", array($leaveRequestId));

        return $query->row_array();
    }

    /**
     * Get approval step by ID
     *
     * @param int $stepId
     * @return array|null
     */
    protected function getApprovalStep($stepId)
    {
        $query = $this->db->query("
            SELECT * FROM approval_steps WHERE id = ?
        ", array($stepId));

        return $query->row_array();
    }

    /**
     * Notify HR admins about missing route
     *
     * @param int $leaveRequestId
     * @param array $request
     */
    protected function notifyHrAdmins($leaveRequestId, $request)
    {
        // Notify users who can maintain approval routes instead of relying on role names.
        if (!$this->db->table_exists('user_module_permissions')) {
            return;
        }

        $hrAdmins = $this->db->query("
            SELECT DISTINCT u.id
            FROM user u
            INNER JOIN user_module_permissions ump ON u.id = ump.user_id
            WHERE ump.module_name = 'approval_routes'
              AND ump.can_edit = 1
              AND u.status = 'Aktif'
        ")->result_array();

        foreach ($hrAdmins as $admin) {
            $this->CI->notificationservice->notifyNeedsRoute(
                $admin['id'],
                $leaveRequestId,
                $request
            );
        }
    }
}
