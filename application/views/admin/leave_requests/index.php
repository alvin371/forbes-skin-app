<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">All Leave Requests</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('message')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background-color: #fafafa;">
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Request No</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Requester</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Leave Type</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Period</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Days</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Status</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Approver</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Approval</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="9" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No leave requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($request['request_no']); ?>
                                    <?php if (!empty($request['attachment_path'])): ?>
                                        <i class="bi bi-paperclip" style="color: #1890ff; margin-left: 4px;" title="Has attachment"></i>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <div style="color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['requester_name'] ?? 'N/A'); ?></div>
                                    <div style="color: rgba(0,0,0,0.45); font-size: 12px;"><?php echo htmlspecialchars($request['requester_email'] ?? ''); ?></div>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($request['leave_type_name'] ?? 'N/A'); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <div><?php echo date('d M Y', strtotime($request['start_date'])); ?></div>
                                    <div style="color: rgba(0,0,0,0.45); font-size: 12px;">to <?php echo date('d M Y', strtotime($request['end_date'])); ?></div>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo (int) $request['days_count']; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <?php
                                    $status = $request['status'];
                                    $statusColors = [
                                        'PENDING_APPROVAL' => ['bg' => '#fffbe6', 'color' => '#faad14', 'border' => '#ffe58f', 'text' => 'Pending'],
                                        'APPROVED' => ['bg' => '#f6ffed', 'color' => '#52c41a', 'border' => '#b7eb8f', 'text' => 'Approved'],
                                        'REJECTED' => ['bg' => '#fff2f0', 'color' => '#ff4d4f', 'border' => '#ffccc7', 'text' => 'Rejected'],
                                        'CANCELLED' => ['bg' => '#f5f5f5', 'color' => '#8c8c8c', 'border' => '#d9d9d9', 'text' => 'Cancelled'],
                                        'SUBMITTED' => ['bg' => '#e6f7ff', 'color' => '#1890ff', 'border' => '#91d5ff', 'text' => 'Submitted'],
                                    ];
                                    $statusStyle = $statusColors[$status] ?? ['bg' => '#f5f5f5', 'color' => '#595959', 'border' => '#d9d9d9', 'text' => $status];
                                    ?>
                                    <span style="background-color: <?php echo $statusStyle['bg']; ?>; color: <?php echo $statusStyle['color']; ?>; border: 1px solid <?php echo $statusStyle['border']; ?>; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                        <?php echo htmlspecialchars($statusStyle['text']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <?php if (!empty($request['approver_name'])): ?>
                                        <div style="color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['approver_name']); ?></div>
                                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;"><?php echo htmlspecialchars($request['approver_email'] ?? ''); ?></div>
                                    <?php else: ?>
                                        <span style="color: rgba(0,0,0,0.45);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <?php if (!empty($request['approval_action'])): ?>
                                        <?php
                                        $approvalColors = [
                                            'PENDING' => ['bg' => '#fffbe6', 'color' => '#faad14', 'border' => '#ffe58f'],
                                            'APPROVED' => ['bg' => '#f6ffed', 'color' => '#52c41a', 'border' => '#b7eb8f'],
                                            'REJECTED' => ['bg' => '#fff2f0', 'color' => '#ff4d4f', 'border' => '#ffccc7'],
                                        ];
                                        $approvalStyle = $approvalColors[$request['approval_action']] ?? ['bg' => '#f5f5f5', 'color' => '#595959', 'border' => '#d9d9d9'];
                                        ?>
                                        <span style="background-color: <?php echo $approvalStyle['bg']; ?>; color: <?php echo $approvalStyle['color']; ?>; border: 1px solid <?php echo $approvalStyle['border']; ?>; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            <?php echo htmlspecialchars(ucfirst(strtolower($request['approval_action']))); ?>
                                        </span>
                                        <?php if (!empty($request['approval_action_at'])): ?>
                                            <div style="color: rgba(0,0,0,0.45); font-size: 11px; margin-top: 2px;">
                                                <?php echo date('d M Y H:i', strtotime($request['approval_action_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: rgba(0,0,0,0.45);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <a href="<?php echo site_url('admin/leave-requests/' . $request['id']); ?>" style="color: #1890ff; font-size: 16px;" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
