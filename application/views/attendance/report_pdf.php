<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        h2 { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
        .summary { margin-top: 12px; }
        .summary span { margin-right: 16px; }
        .flagged { background: #fff1f0; }
        .special { margin-top: 8px; display: inline-block; background: #fff1f0; border: 1px solid #ffa39e; color: #a8071a; padding: 4px 8px; }
    </style>
</head>
<body>
    <h2>Attendance Report</h2>
    <div>Month: <?php echo htmlspecialchars($month); ?></div>
    <?php if (!empty($user)): ?>
        <div>User: <?php echo htmlspecialchars($user['full_name'] ?? ''); ?></div>
    <?php endif; ?>

    <?php if (!empty($report['summary'])): ?>
        <div class="summary">
            <span>Present: <?php echo $report['summary']['present_days']; ?></span>
            <span>Late: <?php echo $report['summary']['late_count']; ?></span>
            <span>Early Checkout: <?php echo $report['summary']['early_checkout_count']; ?></span>
            <span>Absent: <?php echo $report['summary']['absent_count']; ?></span>
            <span>Leave: <?php echo $report['summary']['leave_days']; ?></span>
        </div>
        <?php if (!empty($report['summary']['special_schedule'])): ?>
            <div class="special">
                Special Schedule: <?php echo htmlspecialchars(($report['summary']['start_time'] ?? '-') . ' - ' . ($report['summary']['end_time'] ?? '-')); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Status</th>
                <th>First In</th>
                <th>Last Out</th>
                <th>Late</th>
                <th>Early Checkout</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report['daily'] as $row): ?>
                <?php
                    $isFlagged = !empty($row['late']) || !empty($row['early_checkout']);
                    $notesValue = $row['notes'] ?? array();
                    if (is_array($notesValue)) {
                        $notesText = implode(' ', $notesValue);
                    } else {
                        $notesText = (string) $notesValue;
                    }
                ?>
                <tr class="<?php echo $isFlagged ? 'flagged' : ''; ?>">
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
                    <td><?php echo $notesText !== '' ? htmlspecialchars($notesText) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
