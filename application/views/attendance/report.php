<div class="card">
    <div class="card-body">
        <h3>Attendance Report</h3>

        <form method="get" action="<?php echo site_url('attendance/report'); ?>" style="margin-bottom: 16px;">
            <label>Month</label>
            <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>">
            <?php if (!empty($is_admin_hr) && !empty($target_user)): ?>
                <label style="margin-left: 12px;">User</label>
                <select name="user_id">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int) $user['id']; ?>" <?php echo ($selected_user_id ?? 0) == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button type="submit" style="margin-left: 8px;">Apply</button>
        </form>

        <?php if (!empty($report['error'])): ?>
            <div style="color: #b00020;"><?php echo htmlspecialchars($report['error']); ?></div>
        <?php else: ?>
            <?php if (!empty($is_admin_hr) && empty($_GET['user_id'])): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Early Checkout</th>
                            <th>Absent</th>
                            <th>Leave</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php $summaryData = $summaries[$user['id']] ?? null; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo $summaryData['present_days'] ?? '-'; ?></td>
                                <td><?php echo $summaryData['late_count'] ?? '-'; ?></td>
                                <td><?php echo $summaryData['early_checkout_count'] ?? '-'; ?></td>
                                <td><?php echo $summaryData['absent_count'] ?? '-'; ?></td>
                                <td><?php echo $summaryData['leave_days'] ?? '-'; ?></td>
                                <td>
                                    <a href="<?php echo site_url('attendance/report?month=' . urlencode($month) . '&user_id=' . (int) $user['id']); ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <?php if (!empty($target_user)): ?>
                    <div style="margin-bottom: 12px;">
                        <strong>User:</strong> <?php echo htmlspecialchars($target_user['full_name'] ?? ''); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($report['summary'])): ?>
                    <div class="row" style="margin-bottom: 12px;">
                        <div class="col-md-2"><strong>Present:</strong> <?php echo $report['summary']['present_days']; ?></div>
                        <div class="col-md-2"><strong>Late:</strong> <?php echo $report['summary']['late_count']; ?></div>
                        <div class="col-md-3"><strong>Early Checkout:</strong> <?php echo $report['summary']['early_checkout_count']; ?></div>
                        <div class="col-md-2"><strong>Absent:</strong> <?php echo $report['summary']['absent_count']; ?></div>
                        <div class="col-md-2"><strong>Leave:</strong> <?php echo $report['summary']['leave_days']; ?></div>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 12px;">
                    <a href="<?php echo site_url('attendance/report/pdf?month=' . urlencode($month) . (!empty($target_user['id']) ? '&user_id=' . (int) $target_user['id'] : '')); ?>" target="_blank">Export PDF</a>
                </div>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>First In</th>
                            <th>Last Out</th>
                            <th>Late</th>
                            <th>Early Checkout</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['daily'] as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['status']); ?>
                                    <?php if (!empty($row['holiday_name'])): ?>
                                        (<?php echo htmlspecialchars($row['holiday_name']); ?>)
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['first_in'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['last_out'] ?? '-'); ?></td>
                                <td><?php echo $row['late'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['early_checkout'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
