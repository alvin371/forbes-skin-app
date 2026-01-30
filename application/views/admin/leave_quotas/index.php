<?php
$usersById = array();
foreach ($users as $user) {
    $usersById[(int) $user['id']] = $user;
}

$leaveTypesById = array();
foreach ($leave_types as $leaveType) {
    $leaveTypesById[(int) $leaveType['id']] = $leaveType;
}

$quotasByUser = array();
$quotasByUserType = array();
foreach ($quotas as $quota) {
    $userId = (int) $quota['user_id'];
    if (!isset($quotasByUser[$userId])) {
        $userInfo = $usersById[$userId] ?? array();
        $quotasByUser[$userId] = array(
            'user_id' => $userId,
            'user_name' => $quota['user_name'] ?? ($userInfo['full_name'] ?? 'N/A'),
            'user_email' => $quota['user_email'] ?? ($userInfo['email'] ?? ''),
            'user_department' => $userInfo['department'] ?? '',
            'quotas' => array(),
            'last_updated' => null,
            'leave_type_ids' => array(),
        );
    }

    $quotasByUser[$userId]['quotas'][] = $quota;
    $quotasByUser[$userId]['leave_type_ids'][(int) $quota['leave_type_id']] = true;
    $quotasByUserType[$userId][(int) $quota['leave_type_id']] = $quota;
    if (!empty($quota['updated_at'])) {
        $updated = strtotime($quota['updated_at']);
        $current = $quotasByUser[$userId]['last_updated'] ? strtotime($quotasByUser[$userId]['last_updated']) : 0;
        if ($updated > $current) {
            $quotasByUser[$userId]['last_updated'] = $quota['updated_at'];
        }
    }
}

$totalLeaveTypes = count($leave_types);
?>

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

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div style="background-color: #e6f7ff; border: 1px solid #91d5ff; border-radius: 2px; padding: 12px; margin-bottom: 16px;">
            <div style="color: #1890ff; font-size: 14px;">
                <i class="bi bi-info-circle"></i> Tip: Use <strong>By Leave Type</strong> to update all users for one leave type without opening each profile.
            </div>
        </div>

        <form method="post" action="<?php echo site_url('admin/leave-quotas/set-all'); ?>" style="border: 1px solid #f0f0f0; border-radius: 2px; padding: 12px; margin-bottom: 16px; background: #fafafa;">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <div style="min-width: 220px; flex: 1;">
                    <label style="display: block; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.85); margin-bottom: 6px;">Leave Type</label>
                    <select name="leave_type_id" required style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 6px 11px; font-size: 14px;">
                        <option value="">Select Leave Type</option>
                        <?php foreach ($leave_types as $leaveType): ?>
                            <option value="<?php echo $leaveType['id']; ?>">
                                <?php echo htmlspecialchars($leaveType['name']); ?> (<?php echo htmlspecialchars($leaveType['code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="min-width: 200px;">
                    <label style="display: block; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.85); margin-bottom: 6px;">Total Days</label>
                    <input type="number" name="total_days" required min="0" step="1" placeholder="e.g. 12" style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 6px 11px; font-size: 14px;">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 16px; border-radius: 2px; font-size: 13px; display: inline-flex; align-items: center;">
                        <i class="bi bi-people"></i> Set for All Users
                    </button>
                </div>
            </div>
        </form>

        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 16px;">
            <div style="min-width: 220px; flex: 1;">
                <input id="quota-search" type="text" placeholder="Search name or email" style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 6px 11px; font-size: 14px;">
            </div>
            <?php if (!empty($has_department)): ?>
                <div style="min-width: 180px;">
                    <select id="quota-department" style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 6px 11px; font-size: 14px;">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" id="quota-department" value="">
            <?php endif; ?>
            <div style="min-width: 180px;">
                <select id="quota-status" style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 6px 11px; font-size: 14px;">
                    <option value="">All Status</option>
                    <option value="ok">OK</option>
                    <option value="low">Low</option>
                    <option value="zero">Zero</option>
                    <option value="not-set">Not Set</option>
                </select>
            </div>
            <div style="min-width: 200px;">
                <select id="quota-leave-type" style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 6px 11px; font-size: 14px;">
                    <option value="">All Leave Types</option>
                    <?php foreach ($leave_types as $leaveType): ?>
                        <option value="<?php echo $leaveType['id']; ?>"><?php echo htmlspecialchars($leaveType['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" id="quota-clear" style="background: none; border: 1px solid #d9d9d9; color: rgba(0,0,0,0.65); padding: 6px 12px; border-radius: 2px; font-size: 13px;">Clear</button>
        </div>

        <div style="display: flex; gap: 8px; margin-bottom: 12px;">
            <button type="button" class="btn btn-primary" id="view-by-user-btn" style="background-color: #1890ff; border-color: #1890ff; height: 30px; padding: 2px 12px; border-radius: 2px; font-size: 12px;">By User</button>
            <button type="button" class="btn btn-default" id="view-by-type-btn" style="background-color: #fff; border-color: #d9d9d9; height: 30px; padding: 2px 12px; border-radius: 2px; font-size: 12px;">By Leave Type</button>
            <div id="user-view-actions" style="margin-left: auto; display: flex; gap: 8px;">
                <button type="button" id="expand-all" style="background: none; border: 1px solid #d9d9d9; color: rgba(0,0,0,0.65); padding: 4px 10px; border-radius: 2px; font-size: 12px;">Expand All</button>
                <button type="button" id="collapse-all" style="background: none; border: 1px solid #d9d9d9; color: rgba(0,0,0,0.65); padding: 4px 10px; border-radius: 2px; font-size: 12px;">Collapse All</button>
            </div>
        </div>

        <div id="view-by-user">
            <form method="post" action="<?php echo site_url('admin/leave-quotas/bulk-update'); ?>">
                <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                    <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                <?php endif; ?>
                <input type="hidden" name="only_user_id" value="">
                <input type="hidden" name="only_leave_type_id" value="">

                <div class="table-responsive">
                    <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr style="background-color: #fafafa;">
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px; width: 28%;">User</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px; width: 16%;">Department</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px; width: 18%;">Quota Summary</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px; width: 18%;">Last Updated</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px; width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $userId = (int) $user['id'];
                                    $userQuotas = $quotasByUser[$userId]['quotas'] ?? array();
                                    $userLeaveTypes = array_keys($quotasByUser[$userId]['leave_type_ids'] ?? array());
                                    $userUpdatedAt = $quotasByUser[$userId]['last_updated'] ?? null;
                                    $setCount = count($userLeaveTypes);
                                    $missingCount = max(0, $totalLeaveTypes - $setCount);
                                    ?>
                                    <tr class="user-summary-row" data-user-id="<?php echo $userId; ?>" data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>" data-user-email="<?php echo htmlspecialchars($user['email']); ?>" data-department="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" data-leave-types="<?php echo htmlspecialchars(implode(',', $userLeaveTypes)); ?>" style="border-bottom: 1px solid #f0f0f0; background: #fff;">
                                        <td style="padding: 12px 8px; font-size: 14px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <button type="button" class="toggle-user" data-user-id="<?php echo $userId; ?>" style="background: none; border: 1px solid #d9d9d9; border-radius: 2px; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; color: rgba(0,0,0,0.65);">+</button>
                                                <div>
                                                    <div style="color: rgba(0,0,0,0.85); font-weight: 500;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                    <div style="color: rgba(0,0,0,0.45); font-size: 12px;"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 12px 8px; font-size: 13px; color: rgba(0,0,0,0.65);">
                                            <?php echo htmlspecialchars($user['department'] ?? ''); ?>
                                        </td>
                                        <td style="padding: 12px 8px; font-size: 13px; color: rgba(0,0,0,0.85);">
                                            <strong><?php echo $setCount; ?></strong> / <?php echo $totalLeaveTypes; ?> set
                                            <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Missing: <?php echo $missingCount; ?></div>
                                        </td>
                                        <td style="padding: 12px 8px; font-size: 13px; color: rgba(0,0,0,0.45);">
                                            <?php echo $userUpdatedAt ? date('d M Y', strtotime($userUpdatedAt)) : '-'; ?>
                                        </td>
                                        <td style="padding: 12px 8px; font-size: 13px;">
                                            <a href="<?php echo site_url('admin/leave-quotas/manage/' . $userId); ?>" class="btn btn-primary btn-sm" style="background-color: #1890ff; border-color: #1890ff; height: 28px; padding: 2px 10px; border-radius: 2px; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center;">
                                                Manage
                                            </a>
                                        </td>
                                    </tr>
                                    <?php if (empty($userQuotas)): ?>
                                        <tr class="quota-detail-row" data-user-id="<?php echo $userId; ?>" data-status="not-set" style="display: none;">
                                            <td colspan="5" style="padding: 12px 8px; font-size: 13px; color: rgba(0,0,0,0.45);">
                                                No quotas set for this user.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr class="quota-detail-header" data-user-id="<?php echo $userId; ?>" style="display: none; background: #fafafa;">
                                            <td style="padding: 10px 8px; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.65);">Leave Type</td>
                                            <td style="padding: 10px 8px; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.65);">Total Days</td>
                                            <td style="padding: 10px 8px; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.65);">Remaining / Used</td>
                                            <td style="padding: 10px 8px; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.65);">Status</td>
                                            <td style="padding: 10px 8px; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.65);">Updated</td>
                                        </tr>
                                        <?php foreach ($userQuotas as $quota): ?>
                                            <?php
                                            $remaining = (int) $quota['remaining_days'];
                                            $total = (int) $quota['total_days'];
                                            $used = $total - $remaining;
                                            $status = 'ok';
                                            $statusLabel = 'OK';
                                            $statusColor = '#52c41a';
                                            if ($total === 0) {
                                                $status = 'zero';
                                                $statusLabel = 'Zero';
                                                $statusColor = '#8c8c8c';
                                            } elseif ($remaining === 0) {
                                                $status = 'low';
                                                $statusLabel = 'Low';
                                                $statusColor = '#ff4d4f';
                                            } elseif ($total > 0 && ($remaining / $total) <= 0.2) {
                                                $status = 'low';
                                                $statusLabel = 'Low';
                                                $statusColor = '#faad14';
                                            }
                                            ?>
                                            <tr class="quota-detail-row" data-user-id="<?php echo (int) $quota['user_id']; ?>" data-leave-type-id="<?php echo (int) $quota['leave_type_id']; ?>" data-status="<?php echo $status; ?>" data-user-name="<?php echo htmlspecialchars($quota['user_name'] ?? ''); ?>" data-user-email="<?php echo htmlspecialchars($quota['user_email'] ?? ''); ?>" data-department="<?php echo htmlspecialchars($usersById[(int) $quota['user_id']]['department'] ?? ''); ?>" style="display: none;">
                                                <td style="padding: 10px 8px; font-size: 13px; color: rgba(0,0,0,0.85);">
                                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($quota['leave_type_name'] ?? 'N/A'); ?></div>
                                                    <div style="color: rgba(0,0,0,0.45); font-size: 12px;">Code: <?php echo htmlspecialchars($quota['leave_type_code'] ?? ''); ?></div>
                                                </td>
                                                <td style="padding: 10px 8px; font-size: 13px;">
                                                    <div style="display: flex; align-items: center; gap: 6px;">
                                                        <input type="number" name="quota_updates[<?php echo (int) $quota['user_id']; ?>][<?php echo (int) $quota['leave_type_id']; ?>]" value="<?php echo (int) $quota['total_days']; ?>" min="0" step="1" style="width: 90px; border: 1px solid #d9d9d9; border-radius: 2px; padding: 4px 8px; font-size: 13px;">
                                                        <button type="button" class="save-row" data-user-id="<?php echo (int) $quota['user_id']; ?>" data-leave-type-id="<?php echo (int) $quota['leave_type_id']; ?>" style="background: none; border: 1px solid #d9d9d9; color: rgba(0,0,0,0.65); padding: 2px 8px; border-radius: 2px; font-size: 12px;">Save</button>
                                                    </div>
                                                </td>
                                                <td style="padding: 10px 8px; font-size: 13px; color: rgba(0,0,0,0.65);">
                                                    <?php echo $remaining; ?> / <?php echo $used; ?>
                                                </td>
                                                <td style="padding: 10px 8px; font-size: 12px;">
                                                    <span style="background-color: #f5f5f5; color: <?php echo $statusColor; ?>; border: 1px solid #d9d9d9; padding: 2px 8px; display: inline-flex; align-items: center; border-radius: 2px;">
                                                        <?php echo $statusLabel; ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 10px 8px; font-size: 12px; color: rgba(0,0,0,0.45);">
                                                    <?php echo !empty($quota['updated_at']) ? date('d M Y', strtotime($quota['updated_at'])) : '-'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 12px; color: rgba(0,0,0,0.45);">Save will update total days and reset remaining days to the same value.</div>
                    <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 34px; padding: 4px 18px; border-radius: 2px; font-size: 13px;">
                        Save All Changes
                    </button>
                </div>
            </form>
        </div>

        <div id="view-by-type" style="display: none;">
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.85); margin-bottom: 6px;">Leave Type View</label>
                <select id="leave-type-view-select" style="width: 100%; max-width: 360px; border: 1px solid #d9d9d9; border-radius: 2px; padding: 6px 11px; font-size: 14px;">
                    <?php foreach ($leave_types as $leaveType): ?>
                        <option value="<?php echo $leaveType['id']; ?>">
                            <?php echo htmlspecialchars($leaveType['name']); ?> (<?php echo htmlspecialchars($leaveType['code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach ($leave_types as $leaveType): ?>
                <div class="leave-type-panel" data-leave-type-id="<?php echo (int) $leaveType['id']; ?>" style="display: none;">
                    <form method="post" action="<?php echo site_url('admin/leave-quotas/bulk-update'); ?>">
                        <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                            <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                        <?php endif; ?>
                        <input type="hidden" name="only_user_id" value="">
                        <input type="hidden" name="only_leave_type_id" value="">

                        <div class="table-responsive">
                            <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                                <thead>
                                    <tr style="background-color: #fafafa;">
                                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">User</th>
                                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Department</th>
                                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Total Days</th>
                                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Remaining / Used</th>
                                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Status</th>
                                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="6" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No users found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <?php
                                            $userId = (int) $user['id'];
                                            $existingQuota = $quotasByUserType[$userId][(int) $leaveType['id']] ?? null;
                                            $total = $existingQuota ? (int) $existingQuota['total_days'] : 0;
                                            $remaining = $existingQuota ? (int) $existingQuota['remaining_days'] : 0;
                                            $used = $total - $remaining;
                                            $status = 'not-set';
                                            $statusLabel = 'Not Set';
                                            $statusColor = '#8c8c8c';
                                            if ($existingQuota) {
                                                $status = 'ok';
                                                $statusLabel = 'OK';
                                                $statusColor = '#52c41a';
                                                if ($total === 0) {
                                                    $status = 'zero';
                                                    $statusLabel = 'Zero';
                                                    $statusColor = '#8c8c8c';
                                                } elseif ($remaining === 0) {
                                                    $status = 'low';
                                                    $statusLabel = 'Low';
                                                    $statusColor = '#ff4d4f';
                                                } elseif ($total > 0 && ($remaining / $total) <= 0.2) {
                                                    $status = 'low';
                                                    $statusLabel = 'Low';
                                                    $statusColor = '#faad14';
                                                }
                                            }
                                            ?>
                                            <tr class="leave-type-row" data-user-id="<?php echo $userId; ?>" data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>" data-user-email="<?php echo htmlspecialchars($user['email']); ?>" data-department="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" data-status="<?php echo $status; ?>">
                                                <td style="padding: 12px 8px; font-size: 13px;">
                                                    <div style="color: rgba(0,0,0,0.85); font-weight: 500;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                    <div style="color: rgba(0,0,0,0.45); font-size: 12px;"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </td>
                                                <td style="padding: 12px 8px; font-size: 13px; color: rgba(0,0,0,0.65);">
                                                    <?php echo htmlspecialchars($user['department'] ?? ''); ?>
                                                </td>
                                                <td style="padding: 12px 8px; font-size: 13px;">
                                                    <div style="display: flex; align-items: center; gap: 6px;">
                                                        <input type="number" name="quota_updates[<?php echo $userId; ?>][<?php echo (int) $leaveType['id']; ?>]" value="<?php echo $existingQuota ? $total : ''; ?>" min="0" step="1" placeholder="0" style="width: 90px; border: 1px solid #d9d9d9; border-radius: 2px; padding: 4px 8px; font-size: 13px;">
                                                        <button type="button" class="save-row" data-user-id="<?php echo $userId; ?>" data-leave-type-id="<?php echo (int) $leaveType['id']; ?>" style="background: none; border: 1px solid #d9d9d9; color: rgba(0,0,0,0.65); padding: 2px 8px; border-radius: 2px; font-size: 12px;">Save</button>
                                                    </div>
                                                </td>
                                                <td style="padding: 12px 8px; font-size: 13px; color: rgba(0,0,0,0.65);">
                                                    <?php if ($existingQuota): ?>
                                                        <?php echo $remaining; ?> / <?php echo $used; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 12px 8px; font-size: 12px;">
                                                    <span style="background-color: #f5f5f5; color: <?php echo $statusColor; ?>; border: 1px solid #d9d9d9; padding: 2px 8px; display: inline-flex; align-items: center; border-radius: 2px;">
                                                        <?php echo $statusLabel; ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 12px 8px; font-size: 12px; color: rgba(0,0,0,0.45);">
                                                    <?php echo !empty($existingQuota['updated_at']) ? date('d M Y', strtotime($existingQuota['updated_at'])) : '-'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 12px; color: rgba(0,0,0,0.45);">Save will update total days and reset remaining days to the same value.</div>
                            <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 34px; padding: 4px 18px; border-radius: 2px; font-size: 13px;">
                                Save All for <?php echo htmlspecialchars($leaveType['name']); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
(function() {
    var viewByUserBtn = document.getElementById('view-by-user-btn');
    var viewByTypeBtn = document.getElementById('view-by-type-btn');
    var viewByUser = document.getElementById('view-by-user');
    var viewByType = document.getElementById('view-by-type');
    var userViewActions = document.getElementById('user-view-actions');
    var leaveTypeSelect = document.getElementById('leave-type-view-select');

    function setActiveView(view) {
        if (view === 'type') {
            viewByUser.style.display = 'none';
            viewByType.style.display = 'block';
            viewByUserBtn.classList.remove('btn-primary');
            viewByUserBtn.classList.add('btn-default');
            viewByUserBtn.style.backgroundColor = '#fff';
            viewByUserBtn.style.borderColor = '#d9d9d9';
            viewByTypeBtn.classList.add('btn-primary');
            viewByTypeBtn.style.backgroundColor = '#1890ff';
            viewByTypeBtn.style.borderColor = '#1890ff';
            userViewActions.style.display = 'none';
        } else {
            viewByUser.style.display = 'block';
            viewByType.style.display = 'none';
            viewByTypeBtn.classList.remove('btn-primary');
            viewByTypeBtn.classList.add('btn-default');
            viewByTypeBtn.style.backgroundColor = '#fff';
            viewByTypeBtn.style.borderColor = '#d9d9d9';
            viewByUserBtn.classList.add('btn-primary');
            viewByUserBtn.style.backgroundColor = '#1890ff';
            viewByUserBtn.style.borderColor = '#1890ff';
            userViewActions.style.display = 'flex';
        }
        applyFilters();
    }

    viewByUserBtn.addEventListener('click', function() { setActiveView('user'); });
    viewByTypeBtn.addEventListener('click', function() { setActiveView('type'); });

    var toggleButtons = document.querySelectorAll('.toggle-user');
    toggleButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var userId = btn.getAttribute('data-user-id');
            var rows = document.querySelectorAll('[data-user-id="' + userId + '"]');
            var expanded = btn.getAttribute('data-expanded') === 'true';
            rows.forEach(function(row) {
                if (row.classList.contains('quota-detail-row') || row.classList.contains('quota-detail-header')) {
                    row.style.display = expanded ? 'none' : 'table-row';
                }
            });
            btn.textContent = expanded ? '+' : '-';
            btn.setAttribute('data-expanded', expanded ? 'false' : 'true');
            applyFilters();
        });
    });

    var expandAllBtn = document.getElementById('expand-all');
    var collapseAllBtn = document.getElementById('collapse-all');

    function setAllExpanded(expand) {
        toggleButtons.forEach(function(btn) {
            var userId = btn.getAttribute('data-user-id');
            var rows = document.querySelectorAll('[data-user-id="' + userId + '"]');
            rows.forEach(function(row) {
                if (row.classList.contains('quota-detail-row') || row.classList.contains('quota-detail-header')) {
                    row.style.display = expand ? 'table-row' : 'none';
                }
            });
            btn.textContent = expand ? '-' : '+';
            btn.setAttribute('data-expanded', expand ? 'true' : 'false');
        });
        applyFilters();
    }

    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function() { setAllExpanded(true); });
    }
    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', function() { setAllExpanded(false); });
    }

    var clearBtn = document.getElementById('quota-clear');
    var searchInput = document.getElementById('quota-search');
    var departmentSelect = document.getElementById('quota-department');
    var statusSelect = document.getElementById('quota-status');
    var leaveTypeFilter = document.getElementById('quota-leave-type');

    function applyFilters() {
        var query = (searchInput.value || '').toLowerCase().trim();
        var dept = (departmentSelect.value || '').toLowerCase().trim();
        var status = (statusSelect.value || '').toLowerCase().trim();
        var leaveType = (leaveTypeFilter.value || '').trim();

        if (viewByUser.style.display !== 'none') {
            var userRows = document.querySelectorAll('.user-summary-row');
            userRows.forEach(function(row) {
                var userId = row.getAttribute('data-user-id');
                var name = (row.getAttribute('data-user-name') || '').toLowerCase();
                var email = (row.getAttribute('data-user-email') || '').toLowerCase();
                var rowDept = (row.getAttribute('data-department') || '').toLowerCase();
                var leaveTypes = (row.getAttribute('data-leave-types') || '').split(',').filter(Boolean);
                var toggleBtn = document.querySelector('.toggle-user[data-user-id="' + userId + '"]');
                var isExpanded = toggleBtn ? toggleBtn.getAttribute('data-expanded') === 'true' : false;

                var matchesQuery = !query || name.includes(query) || email.includes(query);
                var matchesDept = !dept || rowDept === dept;
                var matchesLeaveType = !leaveType || leaveTypes.indexOf(leaveType) !== -1;
                var matchesStatusAny = true;

                var detailRows = document.querySelectorAll('.quota-detail-row[data-user-id="' + userId + '"]');
                var headerRow = document.querySelector('.quota-detail-header[data-user-id="' + userId + '"]');
                var hasVisibleDetails = false;
                if (status) {
                    matchesStatusAny = false;
                    detailRows.forEach(function(detailRow) {
                        var detailStatus = (detailRow.getAttribute('data-status') || '').toLowerCase();
                        var detailLeaveType = detailRow.getAttribute('data-leave-type-id');
                        var matchesStatus = detailStatus === status;
                        var matchesLeaveTypeDetail = !leaveType || detailLeaveType === leaveType || !detailLeaveType;
                        if (matchesStatus && matchesLeaveTypeDetail) {
                            matchesStatusAny = true;
                        }
                    });
                }

                row.style.display = (matchesQuery && matchesDept && matchesLeaveType && matchesStatusAny) ? 'table-row' : 'none';

                detailRows.forEach(function(detailRow) {
                    var detailStatus = (detailRow.getAttribute('data-status') || '').toLowerCase();
                    var detailLeaveType = detailRow.getAttribute('data-leave-type-id');

                    var matchesStatus = !status || detailStatus === status;
                    var matchesLeaveTypeDetail = !leaveType || detailLeaveType === leaveType || !detailLeaveType;

                    if (row.style.display === 'none') {
                        detailRow.style.display = 'none';
                        return;
                    }

                    if (matchesStatus && matchesLeaveTypeDetail && isExpanded) {
                        detailRow.style.display = 'table-row';
                        hasVisibleDetails = true;
                    } else {
                        detailRow.style.display = 'none';
                    }
                });

                if (headerRow) {
                    if (!hasVisibleDetails || row.style.display === 'none') {
                        headerRow.style.display = 'none';
                    } else if (isExpanded) {
                        headerRow.style.display = 'table-row';
                    }
                }
            });
        } else {
            var activeLeaveTypeId = leaveTypeSelect.value;
            var panels = document.querySelectorAll('.leave-type-panel');
            panels.forEach(function(panel) {
                panel.style.display = panel.getAttribute('data-leave-type-id') === activeLeaveTypeId ? 'block' : 'none';
                if (panel.style.display === 'block') {
                    var rows = panel.querySelectorAll('.leave-type-row');
                    rows.forEach(function(row) {
                        var name = (row.getAttribute('data-user-name') || '').toLowerCase();
                        var email = (row.getAttribute('data-user-email') || '').toLowerCase();
                        var rowDept = (row.getAttribute('data-department') || '').toLowerCase();
                        var rowStatus = (row.getAttribute('data-status') || '').toLowerCase();

                        var matchesQuery = !query || name.includes(query) || email.includes(query);
                        var matchesDept = !dept || rowDept === dept;
                        var matchesStatus = !status || rowStatus === status;

                        row.style.display = (matchesQuery && matchesDept && matchesStatus) ? 'table-row' : 'none';
                    });
                }
            });
        }
    }

    [searchInput, departmentSelect, statusSelect, leaveTypeFilter].forEach(function(el) {
        if (!el) return;
        el.addEventListener('input', applyFilters);
        el.addEventListener('change', applyFilters);
    });

    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        departmentSelect.value = '';
        statusSelect.value = '';
        leaveTypeFilter.value = '';
        applyFilters();
    });

    leaveTypeSelect.addEventListener('change', applyFilters);

    var saveButtons = document.querySelectorAll('.save-row');
    saveButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var form = btn.closest('form');
            var onlyUser = form.querySelector('input[name="only_user_id"]');
            var onlyType = form.querySelector('input[name="only_leave_type_id"]');
            onlyUser.value = btn.getAttribute('data-user-id');
            onlyType.value = btn.getAttribute('data-leave-type-id');
            form.submit();
        });
    });

    setActiveView('user');
    applyFilters();
})();
</script>
