<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Leave Request Detail</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('message')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <div style="background-color: #fafafa; border: 1px solid #d9d9d9; border-radius: 2px; padding: 16px; margin-bottom: 16px;">
            <div class="row g-3">
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Request No</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;"><?php echo htmlspecialchars($request['request_no']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Leave Type</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['leave_type_name']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Start Date</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['start_date']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">End Date</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['end_date']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Days</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;"><?php echo (int) $request['days_count']; ?></div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Status</div>
                        <div>
                            <?php if ($request['status'] === 'PENDING_APPROVAL'): ?>
                                <span style="background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                    Pending Approval
                                </span>
                            <?php elseif ($request['status'] === 'APPROVED'): ?>
                                <span style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                    Approved
                                </span>
                            <?php elseif ($request['status'] === 'DENIED'): ?>
                                <span style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                    Denied
                                </span>
                            <?php else: ?>
                                <span style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                    <?php echo htmlspecialchars($request['status']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Reason</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.65); line-height: 1.6;"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div>
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Attachment</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.65);">
                            <?php if (!empty($request['attachment_path'])): ?>
                                <a href="<?php echo base_url($request['attachment_path']); ?>" target="_blank" style="color: #1890ff; text-decoration: none;">
                                    <i class="bi bi-paperclip"></i> View Attachment
                                </a>
                            <?php else: ?>
                                <span style="color: rgba(0,0,0,0.45);">No attachment</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Progress Section -->
        <?php if (!empty($approval_steps)): ?>
            <div style="background-color: #fff; border: 1px solid #d9d9d9; border-radius: 2px; padding: 16px; margin-bottom: 16px;">
                <h5 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                    <i class="bi bi-diagram-3"></i> Approval Progress
                </h5>
                <div class="approval-timeline">
                    <?php foreach ($approval_steps as $idx => $step): ?>
                        <?php
                        $isCurrentStep = isset($progress['current_step']) && $step['step_no'] == $progress['current_step'] && $step['action'] === 'PENDING';
                        $isCompleted = $step['action'] === 'APPROVED';
                        $isRejected = $step['action'] === 'REJECTED';
                        $isPending = $step['action'] === 'PENDING';

                        if ($isCompleted) {
                            $stepColor = '#52c41a';
                            $stepBg = '#f6ffed';
                            $icon = 'bi-check-circle-fill';
                            $statusText = 'Approved';
                        } elseif ($isRejected) {
                            $stepColor = '#ff4d4f';
                            $stepBg = '#fff2f0';
                            $icon = 'bi-x-circle-fill';
                            $statusText = 'Rejected';
                        } elseif ($isCurrentStep) {
                            $stepColor = '#1890ff';
                            $stepBg = '#e6f7ff';
                            $icon = 'bi-arrow-right-circle-fill';
                            $statusText = 'Waiting for approval';
                        } else {
                            $stepColor = '#d9d9d9';
                            $stepBg = '#fafafa';
                            $icon = 'bi-circle';
                            $statusText = 'Pending';
                        }

                        $isLastStep = $idx === count($approval_steps) - 1;
                        ?>
                        <div style="display: flex; position: relative; padding-bottom: <?php echo $isLastStep ? '0' : '20px'; ?>;">
                            <!-- Vertical Line -->
                            <?php if (!$isLastStep): ?>
                                <div style="position: absolute; left: 11px; top: 24px; bottom: 0; width: 2px; background-color: <?php echo $isCompleted ? '#52c41a' : '#e8e8e8'; ?>;"></div>
                            <?php endif; ?>

                            <!-- Icon -->
                            <div style="width: 24px; z-index: 1;">
                                <i class="bi <?php echo $icon; ?>" style="font-size: 22px; color: <?php echo $stepColor; ?>;"></i>
                            </div>

                            <!-- Content -->
                            <div style="flex: 1; margin-left: 12px; padding-bottom: 4px;">
                                <div style="font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                                    Step <?php echo $step['step_no']; ?>: <?php echo htmlspecialchars($step['step_name'] ?? 'Approval'); ?>
                                </div>
                                <div style="font-size: 13px; color: rgba(0,0,0,0.65); margin-top: 4px;">
                                    <?php if (!empty($step['assigned_approver_name'])): ?>
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($step['assigned_approver_name']); ?>
                                        <?php if (!empty($step['assigned_approver_role'])): ?>
                                            <span style="color: rgba(0,0,0,0.45);">(<?php echo htmlspecialchars($step['assigned_approver_role']); ?>)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Status Badge -->
                                <div style="margin-top: 8px;">
                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; background-color: <?php echo $stepBg; ?>; color: <?php echo $stepColor; ?>; border-radius: 4px; font-size: 12px;">
                                        <i class="bi <?php echo $icon; ?>" style="font-size: 12px; margin-right: 4px;"></i>
                                        <?php echo $statusText; ?>
                                    </span>
                                </div>

                                <?php if ($step['action'] !== 'PENDING' && !empty($step['action_at'])): ?>
                                    <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-top: 6px;">
                                        <i class="bi bi-clock"></i> <?php echo date('d M Y H:i', strtotime($step['action_at'])); ?>
                                        <?php if (!empty($step['actual_approver_name']) && $step['actual_approver_name'] !== $step['assigned_approver_name']): ?>
                                            by <?php echo htmlspecialchars($step['actual_approver_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($step['notes'])): ?>
                                    <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-top: 6px; padding: 8px; background-color: <?php echo $stepBg; ?>; border-radius: 4px; border-left: 3px solid <?php echo $stepColor; ?>;">
                                        <i class="bi bi-chat-left-quote"></i> "<?php echo htmlspecialchars($step['notes']); ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($request['status'] === 'SUBMITTED' || $request['status'] === 'NEEDS_ROUTE'): ?>
            <div style="background-color: #fffbe6; border: 1px solid #ffe58f; border-radius: 2px; padding: 16px; margin-bottom: 16px;">
                <div style="font-size: 14px; color: #ad8b00;">
                    <i class="bi bi-hourglass-split"></i> Approval route is being determined. Please wait.
                </div>
            </div>
        <?php endif; ?>

        <div>
            <a href="<?php echo site_url('leave'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <?php if ($request['status'] === 'PENDING_APPROVAL'): ?>
                <form method="post" action="<?php echo site_url('leave/' . $request['id'] . '/cancel'); ?>" style="display:inline;" onsubmit="return confirm('Cancel this request?');">
                    <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                        <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-outline-danger" style="height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-left: 8px;">
                        Cancel Request
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
