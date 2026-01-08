<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Attendance Report</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <form method="get" action="<?php echo site_url('attendance/report'); ?>" style="margin-bottom: 16px;">
            <div class="row g-2">
                <div class="col-md-3">
                    <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Month</label>
                    <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($month); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                </div>
                <?php if (!empty($is_admin_hr) && !empty($target_user)): ?>
                <div class="col-md-3">
                    <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">User</label>
                    <select name="user_id" class="form-select" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo (int) $user['id']; ?>" <?php echo ($selected_user_id ?? 0) == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                        <i class="bi bi-search"></i> Apply
                    </button>
                </div>
            </div>
        </form>

        <?php if (!empty($report['error'])): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f;">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($report['error']); ?>
            </div>
        <?php else: ?>
            <?php if (!empty($is_admin_hr) && empty($_GET['user_id'])): ?>
                <div class="table-responsive">
                    <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr style="background-color: #fafafa;">
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">User</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Present</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Late</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Early Checkout</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Absent</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Leave</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php $summaryData = $summaries[$user['id']] ?? null; ?>
                                <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['present_days'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['late_count'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['early_checkout_count'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['absent_count'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['leave_days'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px;">
                                        <a href="<?php echo site_url('attendance/report?month=' . urlencode($month) . '&user_id=' . (int) $user['id']); ?>" style="color: #1890ff; font-size: 16px;" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php if (!empty($target_user)): ?>
                    <div style="margin-bottom: 16px; padding: 12px; background-color: #fafafa; border-radius: 2px; border: 1px solid #d9d9d9;">
                        <strong style="font-size: 14px; color: rgba(0,0,0,0.85);">User:</strong> <span style="font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($target_user['full_name'] ?? ''); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($report['summary'])): ?>
                    <div class="row g-2" style="margin-bottom: 16px;">
                        <div class="col-md-2">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Present</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['present_days']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Late</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['late_count']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Early Checkout</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['early_checkout_count']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Absent</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['absent_count']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Leave</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['leave_days']; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 16px;">
                    <a href="<?php echo site_url('attendance/report/pdf?month=' . urlencode($month) . (!empty($target_user['id']) ? '&user_id=' . (int) $target_user['id'] : '')); ?>" target="_blank" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr style="background-color: #fafafa;">
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Date</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Status</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">First In</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Last Out</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Late</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Early Checkout</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['daily'] as $row): ?>
                                <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($row['date']); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                        <?php if (!empty($row['holiday_name'])): ?>
                                            <span style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px; margin-left: 4px;">
                                                <?php echo htmlspecialchars($row['holiday_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($row['first_in'] ?? '-'); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($row['last_out'] ?? '-'); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php if ($row['late']): ?>
                                            <span style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">Yes</span>
                                        <?php else: ?>
                                            <span style="color: rgba(0,0,0,0.45);">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php if ($row['early_checkout']): ?>
                                            <span style="background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">Yes</span>
                                        <?php else: ?>
                                            <span style="color: rgba(0,0,0,0.45);">No</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
