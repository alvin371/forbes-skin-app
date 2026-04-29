<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Kehadiran</title>
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
    <?php
    $statusMap = array(
        'Present' => 'Hadir',
        'Absent'  => 'Tidak Hadir',
        'Weekend' => 'Akhir Pekan',
        'Holiday' => 'Libur',
        'Leave'   => 'Cuti',
    );
    ?>
    <h2>Laporan Kehadiran</h2>
    <div>Bulan: <?php echo htmlspecialchars($month); ?></div>
    <?php if (!empty($user)): ?>
        <div>Karyawan: <?php echo htmlspecialchars($user['full_name'] ?? ''); ?></div>
    <?php endif; ?>

    <?php if (!empty($report['summary'])): ?>
        <div class="summary">
            <span>Hadir: <?php echo $report['summary']['present_days']; ?></span>
            <span>Terlambat: <?php echo $report['summary']['late_count']; ?></span>
            <span>Pulang Cepat: <?php echo $report['summary']['early_checkout_count']; ?></span>
            <span>Tidak Hadir: <?php echo $report['summary']['absent_count']; ?></span>
            <span>Cuti: <?php echo $report['summary']['leave_days']; ?></span>
        </div>
        <?php if (!empty($report['summary']['special_schedule'])): ?>
            <div class="special">
                Jadwal Khusus: <?php echo htmlspecialchars(($report['summary']['start_time'] ?? '-') . ' - ' . ($report['summary']['end_time'] ?? '-')); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Terlambat</th>
                <th>Pulang Cepat</th>
                <th>Catatan</th>
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
                    $statusLabel = $statusMap[$row['status']] ?? $row['status'];
                ?>
                <tr class="<?php echo $isFlagged ? 'flagged' : ''; ?>">
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($statusLabel); ?>
                        <?php if (!empty($row['holiday_name'])): ?>
                            (<?php echo htmlspecialchars($row['holiday_name']); ?>)
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['first_in'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['last_out'] ?? '-'); ?></td>
                    <td><?php echo $row['late'] ? 'Ya' : 'Tidak'; ?></td>
                    <td><?php echo $row['early_checkout'] ? 'Ya' : 'Tidak'; ?></td>
                    <td><?php echo $notesText !== '' ? htmlspecialchars($notesText) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
