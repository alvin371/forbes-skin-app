<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Kehadiran</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #1f2937; margin: 0; }
        h1 { margin: 0 0 4px; font-size: 18px; }
        .meta { margin-bottom: 12px; color: #4b5563; }
        .meta div { margin-bottom: 2px; }
        .empty {
            padding: 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
            color: #6b7280;
        }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
        }
        tbody tr:nth-child(even) { background: #fafafa; }
        .col-name { width: 160px; text-align: left; }
        .col-role { width: 90px; text-align: left; }
        .col-schedule { width: 78px; text-align: center; }
        .col-day { width: 42px; text-align: center; font-size: 9px; }
        .col-total { width: 48px; text-align: center; }
        .print-note {
            margin-top: 10px;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>Laporan Kehadiran</h1>
    <div class="meta">
        <div>Bulan: <?php echo htmlspecialchars($month ?? ''); ?></div>
        <div>Scope: <?php echo htmlspecialchars($scope_label ?? ''); ?></div>
    </div>

    <?php if (empty($report_rows)): ?>
        <div class="empty">Tidak ada data kehadiran untuk diekspor.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th class="col-name">Karyawan</th>
                    <th class="col-role">Peran</th>
                    <th class="col-schedule">Jadwal</th>
                    <?php foreach (($day_headers ?? array()) as $header): ?>
                        <th class="col-day"><?php echo htmlspecialchars($header); ?></th>
                    <?php endforeach; ?>
                    <th class="col-total">Hadir</th>
                    <th class="col-total">Terlambat</th>
                    <th class="col-total">Tidak Hadir</th>
                    <th class="col-total">Cuti</th>
                    <th class="col-total">Pulang Cepat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_rows as $row): ?>
                    <tr>
                        <td class="col-name"><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                        <td class="col-role"><?php echo htmlspecialchars(($row['role_name'] ?? '') !== '' ? $row['role_name'] : '-'); ?></td>
                        <td class="col-schedule"><?php echo htmlspecialchars(($row['schedule'] ?? '') !== '' ? $row['schedule'] : '-'); ?></td>
                        <?php foreach (($row['days'] ?? array()) as $cell): ?>
                            <td class="col-day"><?php echo htmlspecialchars($cell); ?></td>
                        <?php endforeach; ?>
                        <td class="col-total"><?php echo (int) ($row['present_days'] ?? 0); ?></td>
                        <td class="col-total"><?php echo (int) ($row['late_count'] ?? 0); ?></td>
                        <td class="col-total"><?php echo (int) ($row['absent_count'] ?? 0); ?></td>
                        <td class="col-total"><?php echo (int) ($row['leave_days'] ?? 0); ?></td>
                        <td class="col-total"><?php echo (int) ($row['early_checkout_count'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="print-note">Kode: H = Hadir, TL = Terlambat, PC = Pulang Cepat, TH = Tidak Hadir, C = Cuti, Lb = Libur, WE = Weekend.</div>
    <?php endif; ?>
</body>
</html>
