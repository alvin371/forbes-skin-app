<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * LeaveQuotaService Library
 *
 * Handles leave quota operations with full audit logging:
 * - Quota validation before approval
 * - Quota deduction on final approval
 * - Quota restoration on cancellation
 * - Ledger entry creation for approved leave
 * - Audit trail via leave_quota_logs
 *
 * Insufficient Quota Policies:
 * - BLOCK (default): Return error, prevent approval
 * - CONVERT_UNPAID: Mark all days as unpaid leave
 * - PARTIAL: Deduct available quota, remainder as unpaid
 */
class LeaveQuotaService
{
    protected $CI;
    protected $db;

    /**
     * Quota policy constants
     */
    const POLICY_BLOCK = 'BLOCK';
    const POLICY_CONVERT_UNPAID = 'CONVERT_UNPAID';
    const POLICY_PARTIAL = 'PARTIAL';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    /**
     * Validate if user has sufficient quota for the requested leave
     *
     * @param int $userId
     * @param int $leaveTypeId
     * @param float $daysRequested
     * @param int|null $excludeRequestId Exclude this request from consideration (for edits)
     * @return array
     */
    public function validateQuota($userId, $leaveTypeId, $daysRequested, $excludeRequestId = null)
    {
        $quota = $this->getQuota($userId, $leaveTypeId);

        if (!$quota) {
            return array(
                'valid' => false,
                'message' => 'No quota found for this leave type',
                'remaining' => 0,
                'requested' => $daysRequested,
            );
        }

        $remaining = floatval($quota['remaining_days']);

        // Check for pending requests that may affect quota
        $pendingDays = $this->getPendingDays($userId, $leaveTypeId, $excludeRequestId);
        $effectiveRemaining = $remaining - $pendingDays;

        if ($effectiveRemaining < $daysRequested) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    'Insufficient quota. Requested: %s days, Available: %s days (including %s pending days)',
                    $daysRequested,
                    $effectiveRemaining,
                    $pendingDays
                ),
                'remaining' => $remaining,
                'effective_remaining' => $effectiveRemaining,
                'pending_days' => $pendingDays,
                'requested' => $daysRequested,
            );
        }

        return array(
            'valid' => true,
            'remaining' => $remaining,
            'effective_remaining' => $effectiveRemaining,
            'pending_days' => $pendingDays,
            'requested' => $daysRequested,
            'after_deduction' => $effectiveRemaining - $daysRequested,
        );
    }

    /**
     * Deduct quota for an approved leave request
     *
     * @param int $userId
     * @param int $leaveTypeId
     * @param float $days
     * @param int $leaveRequestId
     * @param int $performedBy
     * @param string $policy How to handle insufficient quota
     * @return array
     */
    public function deductQuota($userId, $leaveTypeId, $days, $leaveRequestId, $performedBy, $policy = self::POLICY_BLOCK)
    {
        $quota = $this->getQuota($userId, $leaveTypeId);

        if (!$quota) {
            // Create quota record if it doesn't exist
            $quota = $this->createQuota($userId, $leaveTypeId, 0, $performedBy);
            if (!$quota) {
                return array(
                    'success' => false,
                    'message' => 'Failed to create quota record',
                );
            }
        }

        $oldRemaining = floatval($quota['remaining_days']);
        $oldTotal = floatval($quota['total_days']);

        // Check if sufficient quota
        if ($oldRemaining < $days) {
            switch ($policy) {
                case self::POLICY_CONVERT_UNPAID:
                    // Mark entire leave as unpaid, no quota deduction
                    $this->logQuotaChange(
                        $userId,
                        $leaveTypeId,
                        $leaveRequestId,
                        'DEDUCT',
                        $oldTotal,
                        $oldTotal,
                        $oldRemaining,
                        $oldRemaining,
                        0,
                        'Leave marked as unpaid due to insufficient quota',
                        $performedBy
                    );
                    return array(
                        'success' => true,
                        'unpaid' => true,
                        'message' => 'Leave approved as unpaid due to insufficient quota',
                        'deducted' => 0,
                        'remaining' => $oldRemaining,
                    );

                case self::POLICY_PARTIAL:
                    // Deduct what's available, mark remainder as unpaid
                    $deductable = $oldRemaining;
                    $unpaidDays = $days - $deductable;
                    $newRemaining = 0;

                    $this->db->update('leave_quotas', array(
                        'remaining_days' => $newRemaining,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ), array('id' => $quota['id']));

                    $this->logQuotaChange(
                        $userId,
                        $leaveTypeId,
                        $leaveRequestId,
                        'DEDUCT',
                        $oldTotal,
                        $oldTotal,
                        $oldRemaining,
                        $newRemaining,
                        -$deductable,
                        sprintf('Partial deduction: %s days from quota, %s days unpaid', $deductable, $unpaidDays),
                        $performedBy
                    );

                    return array(
                        'success' => true,
                        'partial' => true,
                        'message' => sprintf('%s days deducted from quota, %s days marked as unpaid', $deductable, $unpaidDays),
                        'deducted' => $deductable,
                        'unpaid_days' => $unpaidDays,
                        'remaining' => $newRemaining,
                    );

                case self::POLICY_BLOCK:
                default:
                    return array(
                        'success' => false,
                        'message' => sprintf('Insufficient quota. Available: %s days, Requested: %s days', $oldRemaining, $days),
                        'remaining' => $oldRemaining,
                        'requested' => $days,
                    );
            }
        }

        // Normal deduction
        $newRemaining = $oldRemaining - $days;

        $this->db->update('leave_quotas', array(
            'remaining_days' => $newRemaining,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $quota['id']));

        $this->logQuotaChange(
            $userId,
            $leaveTypeId,
            $leaveRequestId,
            'DEDUCT',
            $oldTotal,
            $oldTotal,
            $oldRemaining,
            $newRemaining,
            -$days,
            'Leave request approved',
            $performedBy
        );

        return array(
            'success' => true,
            'message' => sprintf('Quota deducted: %s days', $days),
            'deducted' => $days,
            'old_remaining' => $oldRemaining,
            'remaining' => $newRemaining,
        );
    }

    /**
     * Restore quota when a leave request is cancelled or rejected after approval
     *
     * @param int $userId
     * @param int $leaveTypeId
     * @param float $days
     * @param int $leaveRequestId
     * @param int $performedBy
     * @param string $reason
     * @return array
     */
    public function restoreQuota($userId, $leaveTypeId, $days, $leaveRequestId, $performedBy, $reason = 'Leave request cancelled')
    {
        $quota = $this->getQuota($userId, $leaveTypeId);

        if (!$quota) {
            return array(
                'success' => false,
                'message' => 'Quota record not found',
            );
        }

        $oldRemaining = floatval($quota['remaining_days']);
        $oldTotal = floatval($quota['total_days']);
        $newRemaining = $oldRemaining + $days;

        // Cap at total days
        if ($newRemaining > $oldTotal) {
            $newRemaining = $oldTotal;
        }

        $this->db->update('leave_quotas', array(
            'remaining_days' => $newRemaining,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('id' => $quota['id']));

        $this->logQuotaChange(
            $userId,
            $leaveTypeId,
            $leaveRequestId,
            'RESTORE',
            $oldTotal,
            $oldTotal,
            $oldRemaining,
            $newRemaining,
            $days,
            $reason,
            $performedBy
        );

        return array(
            'success' => true,
            'message' => sprintf('Quota restored: %s days', $days),
            'restored' => $days,
            'old_remaining' => $oldRemaining,
            'remaining' => $newRemaining,
        );
    }

    /**
     * Adjust quota (for admin corrections)
     *
     * @param int $userId
     * @param int $leaveTypeId
     * @param float $newTotalDays
     * @param float $newRemainingDays
     * @param int $performedBy
     * @param string $reason
     * @return array
     */
    public function adjustQuota($userId, $leaveTypeId, $newTotalDays, $newRemainingDays, $performedBy, $reason = 'Manual adjustment')
    {
        $quota = $this->getQuota($userId, $leaveTypeId);

        $oldTotal = $quota ? floatval($quota['total_days']) : 0;
        $oldRemaining = $quota ? floatval($quota['remaining_days']) : 0;

        if ($quota) {
            $this->db->update('leave_quotas', array(
                'total_days' => $newTotalDays,
                'remaining_days' => $newRemainingDays,
                'updated_at' => date('Y-m-d H:i:s'),
            ), array('id' => $quota['id']));
        } else {
            $quota = $this->createQuota($userId, $leaveTypeId, $newTotalDays, $performedBy);
            if (!$quota) {
                return array(
                    'success' => false,
                    'message' => 'Failed to create quota record',
                );
            }

            $this->db->update('leave_quotas', array(
                'remaining_days' => $newRemainingDays,
            ), array('id' => $quota['id']));
        }

        $changeAmount = $newRemainingDays - $oldRemaining;

        $this->logQuotaChange(
            $userId,
            $leaveTypeId,
            null,
            'ADJUST',
            $oldTotal,
            $newTotalDays,
            $oldRemaining,
            $newRemainingDays,
            $changeAmount,
            $reason,
            $performedBy
        );

        return array(
            'success' => true,
            'message' => 'Quota adjusted successfully',
            'old_total' => $oldTotal,
            'new_total' => $newTotalDays,
            'old_remaining' => $oldRemaining,
            'new_remaining' => $newRemainingDays,
        );
    }

    /**
     * Create ledger entry for approved leave
     *
     * @param array $leaveRequest
     * @param int $approvedBy
     * @return array
     */
    public function createLedgerEntry($leaveRequest, $approvedBy)
    {
        // Check if entry already exists
        $existing = $this->db->query("
            SELECT id FROM leave_ledger WHERE leave_request_id = ?
        ", array($leaveRequest['id']))->row_array();

        if ($existing) {
            return array(
                'success' => false,
                'message' => 'Ledger entry already exists',
                'ledger_id' => $existing['id'],
            );
        }

        // Get leave type info
        $leaveType = $this->db->query("
            SELECT is_paid FROM leave_types WHERE id = ?
        ", array($leaveRequest['leave_type_id']))->row_array();

        $isPaid = $leaveType ? $leaveType['is_paid'] : 1;
        $year = date('Y', strtotime($leaveRequest['start_date']));

        $ledgerData = array(
            'leave_request_id' => $leaveRequest['id'],
            'user_id' => $leaveRequest['user_id'],
            'leave_type_id' => $leaveRequest['leave_type_id'],
            'start_date' => $leaveRequest['start_date'],
            'end_date' => $leaveRequest['end_date'],
            'days_used' => $leaveRequest['days_count'],
            'is_paid' => $isPaid,
            'year' => $year,
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        );

        $this->db->insert('leave_ledger', $ledgerData);
        $ledgerId = $this->db->insert_id();

        return array(
            'success' => true,
            'ledger_id' => $ledgerId,
        );
    }

    /**
     * Delete ledger entry (for cancelled approved leave)
     *
     * @param int $leaveRequestId
     * @return bool
     */
    public function deleteLedgerEntry($leaveRequestId)
    {
        return $this->db->delete('leave_ledger', array('leave_request_id' => $leaveRequestId));
    }

    /**
     * Get quota usage summary for a user
     *
     * @param int $userId
     * @param int|null $year
     * @return array
     */
    public function getQuotaSummary($userId, $year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $query = $this->db->query("
            SELECT
                lq.id,
                lq.leave_type_id,
                lt.name as leave_type_name,
                lt.code as leave_type_code,
                lt.is_paid,
                lq.total_days,
                lq.remaining_days,
                lq.carried_over_days,
                (lq.total_days - lq.remaining_days) as used_days,
                lq.year,
                lq.updated_at
            FROM leave_quotas lq
            INNER JOIN leave_types lt ON lq.leave_type_id = lt.id
            WHERE lq.user_id = ?
              AND lq.year = ?
            ORDER BY lt.name
        ", array($userId, $year));

        return $query->result_array();
    }

    /**
     * Get quota logs for a user
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getQuotaLogs($userId, $limit = 50)
    {
        $query = $this->db->query("
            SELECT
                lql.*,
                lt.name as leave_type_name,
                lr.request_no,
                p.full_name as performed_by_name
            FROM leave_quota_logs lql
            INNER JOIN leave_types lt ON lql.leave_type_id = lt.id
            LEFT JOIN leave_requests lr ON lql.leave_request_id = lr.id
            LEFT JOIN user p ON lql.performed_by = p.id
            WHERE lql.user_id = ?
            ORDER BY lql.created_at DESC
            LIMIT ?
        ", array($userId, $limit));

        return $query->result_array();
    }

    /**
     * Get leave ledger entries for a user
     *
     * @param int $userId
     * @param int|null $year
     * @return array
     */
    public function getLedgerEntries($userId, $year = null)
    {
        $where = "ll.user_id = ?";
        $params = array($userId);

        if ($year) {
            $where .= " AND ll.year = ?";
            $params[] = $year;
        }

        $query = $this->db->query("
            SELECT
                ll.*,
                lt.name as leave_type_name,
                lr.request_no,
                a.full_name as approved_by_name
            FROM leave_ledger ll
            INNER JOIN leave_types lt ON ll.leave_type_id = lt.id
            INNER JOIN leave_requests lr ON ll.leave_request_id = lr.id
            LEFT JOIN user a ON ll.approved_by = a.id
            WHERE $where
            ORDER BY ll.start_date DESC
        ", $params);

        return $query->result_array();
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Get quota for user and leave type
     *
     * @param int $userId
     * @param int $leaveTypeId
     * @param int|null $year
     * @return array|null
     */
    protected function getQuota($userId, $leaveTypeId, $year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $query = $this->db->query("
            SELECT * FROM leave_quotas
            WHERE user_id = ?
              AND leave_type_id = ?
              AND year = ?
        ", array($userId, $leaveTypeId, $year));

        return $query->row_array();
    }

    /**
     * Create a quota record
     *
     * @param int $userId
     * @param int $leaveTypeId
     * @param float $totalDays
     * @param int $createdBy
     * @return array|null
     */
    protected function createQuota($userId, $leaveTypeId, $totalDays, $createdBy)
    {
        $year = date('Y');

        $data = array(
            'user_id' => $userId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
            'total_days' => $totalDays,
            'remaining_days' => $totalDays,
            'carried_over_days' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $this->db->insert('leave_quotas', $data);
        $quotaId = $this->db->insert_id();

        if ($quotaId) {
            $this->logQuotaChange(
                $userId,
                $leaveTypeId,
                null,
                'INITIALIZE',
                0,
                $totalDays,
                0,
                $totalDays,
                $totalDays,
                'Quota initialized',
                $createdBy
            );

            return array_merge($data, array('id' => $quotaId));
        }

        return null;
    }

    /**
     * Get pending days for user and leave type
     * (days in requests that are submitted but not yet approved)
     *
     * @param int $userId
     * @param int $leaveTypeId
     * @param int|null $excludeRequestId
     * @return float
     */
    protected function getPendingDays($userId, $leaveTypeId, $excludeRequestId = null)
    {
        $where = "user_id = ? AND leave_type_id = ? AND status IN ('SUBMITTED', 'PENDING_APPROVAL', 'IN_REVIEW')";
        $params = array($userId, $leaveTypeId);

        if ($excludeRequestId) {
            $where .= " AND id != ?";
            $params[] = $excludeRequestId;
        }

        $query = $this->db->query("
            SELECT COALESCE(SUM(days_count), 0) as pending_days
            FROM leave_requests
            WHERE $where
        ", $params);

        $result = $query->row_array();
        return floatval($result['pending_days']);
    }

    /**
     * Log quota change for audit trail
     *
     * @param int $userId
     * @param int $leaveTypeId
     * @param int|null $leaveRequestId
     * @param string $actionType
     * @param float $oldTotal
     * @param float $newTotal
     * @param float $oldRemaining
     * @param float $newRemaining
     * @param float $changeAmount
     * @param string $reason
     * @param int $performedBy
     */
    protected function logQuotaChange($userId, $leaveTypeId, $leaveRequestId, $actionType, $oldTotal, $newTotal, $oldRemaining, $newRemaining, $changeAmount, $reason, $performedBy)
    {
        $this->db->insert('leave_quota_logs', array(
            'user_id' => $userId,
            'leave_type_id' => $leaveTypeId,
            'leave_request_id' => $leaveRequestId,
            'action_type' => $actionType,
            'old_total_days' => $oldTotal,
            'new_total_days' => $newTotal,
            'old_remaining_days' => $oldRemaining,
            'new_remaining_days' => $newRemaining,
            'change_amount' => $changeAmount,
            'reason' => $reason,
            'performed_by' => $performedBy,
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }
}
