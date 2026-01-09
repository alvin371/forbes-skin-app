<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Manage Leave Quotas for <?php echo htmlspecialchars($user['full_name']); ?></h3>
        <a href="<?php echo site_url('admin/leave-quotas'); ?>" class="btn btn-default" style="background-color: #fff; border-color: #d9d9d9; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('message')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div style="background-color: #fafafa; border: 1px solid #d9d9d9; border-radius: 2px; padding: 12px; margin-bottom: 16px;">
            <div style="font-size: 14px; color: rgba(0,0,0,0.85);">
                <strong>User:</strong> <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
            </div>
            <?php if (!empty($user['department'])): ?>
                <div style="font-size: 13px; color: rgba(0,0,0,0.65); margin-top: 4px;">
                    <strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?>
                </div>
            <?php endif; ?>
        </div>

        <form method="post" action="<?php echo site_url('admin/leave-quotas/manage/' . $user['id']); ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background-color: #fafafa;">
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px; width: 40%;">Leave Type</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px; width: 25%;">Total Days (Quota)</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px; width: 20%;">Currently Remaining</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px; width: 15%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leave_types)): ?>
                            <tr>
                                <td colspan="4" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">
                                    No active leave types configured. Please create leave types first.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leave_types as $leaveType): ?>
                                <?php
                                $existingQuota = isset($quotas_by_type[$leaveType['id']]) ? $quotas_by_type[$leaveType['id']] : null;
                                $currentTotal = $existingQuota ? (int) $existingQuota['total_days'] : 0;
                                $currentRemaining = $existingQuota ? (int) $existingQuota['remaining_days'] : 0;
                                ?>
                                <tr style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.85);">
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($leaveType['name']); ?></div>
                                        <div style="color: rgba(0,0,0,0.45); font-size: 12px;">
                                            Code: <?php echo htmlspecialchars($leaveType['code']); ?>
                                            <?php if ((int) $leaveType['is_paid'] === 1): ?>
                                                <span style="color: #52c41a;"> • Paid</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px;">
                                        <input
                                            type="number"
                                            name="quotas[<?php echo $leaveType['id']; ?>]"
                                            value="<?php echo $currentTotal; ?>"
                                            min="0"
                                            step="1"
                                            style="width: 100%; max-width: 120px; border: 1px solid #d9d9d9; border-radius: 2px; padding: 6px 11px; font-size: 14px;"
                                            placeholder="0"
                                        >
                                        <span style="color: rgba(0,0,0,0.45); font-size: 12px; margin-left: 6px;">days</span>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px;">
                                        <?php if ($existingQuota): ?>
                                            <?php
                                            $percentage = $currentTotal > 0 ? ($currentRemaining / $currentTotal) * 100 : 0;
                                            $color = $percentage > 50 ? '#52c41a' : ($percentage > 20 ? '#faad14' : '#ff4d4f');
                                            ?>
                                            <span style="color: <?php echo $color; ?>; font-weight: 500;">
                                                <?php echo $currentRemaining; ?> / <?php echo $currentTotal; ?> days
                                            </span>
                                        <?php else: ?>
                                            <span style="color: rgba(0,0,0,0.45);">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px;">
                                        <?php if ($existingQuota): ?>
                                            <span style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; padding: 2px 8px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span style="background-color: #f5f5f5; color: #8c8c8c; border: 1px solid #d9d9d9; padding: 2px 8px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                                Not Set
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($leave_types)): ?>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f0f0f0;">
                    <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 36px; padding: 6px 20px; border-radius: 2px; font-size: 14px; display: inline-flex; align-items: center;">
                        <i class="bi bi-check2"></i> Save Quotas
                    </button>
                    <a href="<?php echo site_url('admin/leave-quotas'); ?>" class="btn btn-default" style="background-color: #fff; border-color: #d9d9d9; color: rgba(0,0,0,0.65); height: 36px; padding: 6px 20px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; margin-left: 8px;">
                        Cancel
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
