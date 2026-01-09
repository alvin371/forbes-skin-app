<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Leave Quotas</h3>
        <div>
            <a href="<?php echo site_url('admin/leave-quotas/bulk-set'); ?>" class="btn btn-secondary" style="background-color: #fff; border-color: #d9d9d9; color: rgba(0,0,0,0.65); height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; margin-right: 8px;">
                <i class="bi bi-people-fill"></i> Bulk Set
            </a>
            <a href="<?php echo site_url('admin/leave-quotas/users'); ?>" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                <i class="bi bi-plus"></i> Manage User Quota
            </a>
        </div>
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
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">User</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Leave Type</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Total Days</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Remaining Days</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Used Days</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Last Updated</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quotas)): ?>
                        <tr>
                            <td colspan="7" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No leave quotas configured.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($quotas as $quota): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <div style="color: rgba(0,0,0,0.85); font-weight: 500;"><?php echo htmlspecialchars($quota['user_name'] ?? 'N/A'); ?></div>
                                    <div style="color: rgba(0,0,0,0.45); font-size: 12px;"><?php echo htmlspecialchars($quota['user_email'] ?? ''); ?></div>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($quota['leave_type_name'] ?? 'N/A'); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;">
                                    <?php echo (int) $quota['total_days']; ?> days
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <?php
                                    $remaining = (int) $quota['remaining_days'];
                                    $total = (int) $quota['total_days'];
                                    $percentage = $total > 0 ? ($remaining / $total) * 100 : 0;
                                    $color = $percentage > 50 ? '#52c41a' : ($percentage > 20 ? '#faad14' : '#ff4d4f');
                                    ?>
                                    <span style="color: <?php echo $color; ?>; font-weight: 500;"><?php echo $remaining; ?> days</span>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo ((int) $quota['total_days'] - (int) $quota['remaining_days']); ?> days
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.45);">
                                    <?php echo !empty($quota['updated_at']) ? date('d M Y', strtotime($quota['updated_at'])) : '-'; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <a href="<?php echo site_url('admin/leave-quotas/manage/' . $quota['user_id']); ?>" style="color: #1890ff; margin-right: 12px; font-size: 16px;" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="<?php echo site_url('admin/leave-quotas/' . $quota['id'] . '/delete'); ?>" style="display:inline;" onsubmit="return confirm('Delete this quota?');">
                                        <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                            <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                        <?php endif; ?>
                                        <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: #ff4d4f; font-size: 16px;" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
