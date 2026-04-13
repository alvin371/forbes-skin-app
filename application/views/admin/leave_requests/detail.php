<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Leave Request Detail</h3>
        <a href="<?php echo site_url('admin/leave-requests'); ?>" class="btn btn-default" style="background-color: #fff; border-color: #d9d9d9; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('message')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div style="background: #fafafa; padding: 16px; border-radius: 2px; margin-bottom: 16px;">
                    <h5 style="font-size: 14px; font-weight: 600; color: rgba(0,0,0,0.85); margin-bottom: 12px;">Request Information</h5>

                    <div style="margin-bottom: 12px;">
                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Request Number</div>
                        <div style="color: rgba(0,0,0,0.85); font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($request['request_no']); ?></div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Leave Type</div>
                        <div style="color: rgba(0,0,0,0.85); font-size: 14px;">
                            <?php echo htmlspecialchars($request['leave_type_name'] ?? 'N/A'); ?>
                            <?php if (!empty($request['leave_type_code'])): ?>
                                <span style="color: rgba(0,0,0,0.45); font-size: 12px;">(<?php echo htmlspecialchars($request['leave_type_code']); ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Status</div>
                        <div>
                            <?php
                            $status = $request['status'];
                            $statusColors = [
                                'PENDING_APPROVAL' => ['bg' => '#fffbe6', 'color' => '#faad14', 'border' => '#ffe58f', 'text' => 'Pending Approval'],
                                'APPROVED' => ['bg' => '#f6ffed', 'color' => '#52c41a', 'border' => '#b7eb8f', 'text' => 'Approved'],
                                'REJECTED' => ['bg' => '#fff2f0', 'color' => '#ff4d4f', 'border' => '#ffccc7', 'text' => 'Rejected'],
                                'CANCELLED' => ['bg' => '#f5f5f5', 'color' => '#8c8c8c', 'border' => '#d9d9d9', 'text' => 'Cancelled'],
                                'SUBMITTED' => ['bg' => '#e6f7ff', 'color' => '#1890ff', 'border' => '#91d5ff', 'text' => 'Submitted'],
                            ];
                            $statusStyle = $statusColors[$status] ?? ['bg' => '#f5f5f5', 'color' => '#595959', 'border' => '#d9d9d9', 'text' => $status];
                            ?>
                            <span style="background-color: <?php echo $statusStyle['bg']; ?>; color: <?php echo $statusStyle['color']; ?>; border: 1px solid <?php echo $statusStyle['border']; ?>; padding: 4px 12px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px; margin-top: 4px;">
                                <?php echo htmlspecialchars($statusStyle['text']); ?>
                            </span>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Leave Period</div>
                        <div style="color: rgba(0,0,0,0.85); font-size: 14px;">
                            <?php echo date('d M Y', strtotime($request['start_date'])); ?> - <?php echo date('d M Y', strtotime($request['end_date'])); ?>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Number of Days</div>
                        <div style="color: rgba(0,0,0,0.85); font-size: 14px; font-weight: 500;"><?php echo (int) $request['days_count']; ?> days</div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Submitted On</div>
                        <div style="color: rgba(0,0,0,0.85); font-size: 14px;"><?php echo date('d M Y, H:i', strtotime($request['created_at'])); ?></div>
                    </div>

                    <?php if (!empty($request['reason'])): ?>
                        <div style="margin-bottom: 0;">
                            <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Reason</div>
                            <div style="color: rgba(0,0,0,0.85); font-size: 14px; white-space: pre-wrap;"><?php echo htmlspecialchars($request['reason']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($request['attachment_path'])): ?>
                    <div style="background: #fafafa; padding: 16px; border-radius: 2px; margin-bottom: 16px;">
                        <h5 style="font-size: 14px; font-weight: 600; color: rgba(0,0,0,0.85); margin-bottom: 12px;">Attachment</h5>
                        <?php $attachmentUrl = hrms_attachment_url($request['attachment_path']); ?>
                        <div style="display: flex; align-items: center; padding: 8px 12px; background: #fff; border: 1px solid #d9d9d9; border-radius: 2px;">
                            <i class="bi bi-file-earmark-pdf" style="font-size: 24px; color: #ff4d4f; margin-right: 12px;"></i>
                            <div style="flex: 1;">
                                <div style="color: rgba(0,0,0,0.85); font-size: 14px;"><?php echo basename($request['attachment_path']); ?></div>
                                <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Supporting document</div>
                            </div>
                            <a href="<?php echo $attachmentUrl; ?>" target="_blank" style="color: #1890ff; text-decoration: none; padding: 4px 12px; border: 1px solid #1890ff; border-radius: 2px; font-size: 12px;">
                                <i class="bi bi-download"></i> Download
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <div style="background: #fafafa; padding: 16px; border-radius: 2px; margin-bottom: 16px;">
                    <h5 style="font-size: 14px; font-weight: 600; color: rgba(0,0,0,0.85); margin-bottom: 12px;">Requester Information</h5>

                    <div style="margin-bottom: 12px;">
                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Name</div>
                        <div style="color: rgba(0,0,0,0.85); font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($request['requester_name'] ?? 'N/A'); ?></div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Email</div>
                        <div style="color: rgba(0,0,0,0.85); font-size: 14px;"><?php echo htmlspecialchars($request['requester_email'] ?? 'N/A'); ?></div>
                    </div>

                    <?php if (!empty($request['requester_position'])): ?>
                        <div style="margin-bottom: 0;">
                            <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Position</div>
                            <div style="color: rgba(0,0,0,0.85); font-size: 14px;"><?php echo htmlspecialchars($request['requester_position']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($approvals)): ?>
                    <div style="background: #fafafa; padding: 16px; border-radius: 2px; margin-bottom: 16px;">
                        <h5 style="font-size: 14px; font-weight: 600; color: rgba(0,0,0,0.85); margin-bottom: 12px;">Approval History</h5>

                        <?php foreach ($approvals as $approval): ?>
                            <div style="background: #fff; padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; margin-bottom: 8px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                    <div>
                                        <div style="color: rgba(0,0,0,0.85); font-size: 14px; font-weight: 500;">
                                            <?php echo htmlspecialchars($approval['approver_name'] ?? 'N/A'); ?>
                                        </div>
                                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">
                                            <?php echo htmlspecialchars($approval['approver_email'] ?? ''); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <?php
                                        $action = $approval['action'];
                                        $approvalColors = [
                                            'PENDING' => ['bg' => '#fffbe6', 'color' => '#faad14', 'border' => '#ffe58f'],
                                            'APPROVED' => ['bg' => '#f6ffed', 'color' => '#52c41a', 'border' => '#b7eb8f'],
                                            'REJECTED' => ['bg' => '#fff2f0', 'color' => '#ff4d4f', 'border' => '#ffccc7'],
                                        ];
                                        $approvalStyle = $approvalColors[$action] ?? ['bg' => '#f5f5f5', 'color' => '#595959', 'border' => '#d9d9d9'];
                                        ?>
                                        <span style="background-color: <?php echo $approvalStyle['bg']; ?>; color: <?php echo $approvalStyle['color']; ?>; border: 1px solid <?php echo $approvalStyle['border']; ?>; padding: 2px 8px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 11px;">
                                            <?php echo htmlspecialchars(ucfirst(strtolower($action))); ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if (!empty($approval['action_at'])): ?>
                                    <div style="color: rgba(0,0,0,0.45); font-size: 12px; margin-bottom: 4px;">
                                        <i class="bi bi-clock"></i> <?php echo date('d M Y, H:i', strtotime($approval['action_at'])); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($approval['notes'])): ?>
                                    <div style="border-top: 1px solid #f0f0f0; padding-top: 8px; margin-top: 8px;">
                                        <div style="color: rgba(0,0,0,0.45); font-size: 11px; margin-bottom: 4px;">Notes:</div>
                                        <div style="color: rgba(0,0,0,0.85); font-size: 13px; white-space: pre-wrap;"><?php echo htmlspecialchars($approval['notes']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
