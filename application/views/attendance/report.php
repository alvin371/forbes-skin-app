<?php
/**
 * Attendance Report — content area only.
 * Layout (sidebar/header/TemplateDashboard) is handled outside.
 *
 * Provided variables (from AttendanceReport::index):
 *   $month, $is_admin_hr, $report, $summaries, $matrix,
 *   $users, $target_user, $selected_user_id
 */
$bootstrap = array(
    'month'          => $month ?? date('Y-m'),
    'isAdminHr'      => !empty($is_admin_hr),
    'selectedUserId' => isset($selected_user_id) ? (int) $selected_user_id : 0,
    'users'          => $users ?? array(),
    'targetUser'     => $target_user ?? null,
    'matrix'         => $matrix ?? array(),
    'summaries'      => $summaries ?? array(),
    'singleReport'   => $report ?? null,
    'endpoints'      => array(
        'data'         => site_url('attendance/report/data'),
        'setSchedule'  => site_url('attendance/report/set-schedule'),
        'exportPdf'    => site_url('attendance/report/pdf'),
    ),
);

$assetCss  = FCPATH . 'assets/css/attendance-report.css';
$assetJs   = FCPATH . 'assets/js/attendance-report/index.js';
$cssVer    = is_file($assetCss) ? filemtime($assetCss) : time();
$jsVer     = is_file($assetJs)  ? filemtime($assetJs)  : time();
?>
<link rel="stylesheet" href="<?php echo base_url('assets/css/attendance-report.css'); ?>?v=<?php echo $cssVer; ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">

<?php if (!empty($report['error'])): ?>
    <div style="padding:12px 16px;background:#fff1f0;border:1px solid #ffa39e;color:#a8071a;border-radius:8px;font-size:13px;">
        <?php echo htmlspecialchars($report['error']); ?>
    </div>
<?php endif; ?>

<div id="attendance-app"
     data-can-manage="<?php echo !empty($is_admin_hr) ? '1' : '0'; ?>"
     style="min-height:600px;">
</div>

<script type="application/json" id="attendance-bootstrap"><?php
    echo json_encode($bootstrap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?></script>

<script type="module" src="<?php echo base_url('assets/js/attendance-report/index.js'); ?>?v=<?php echo $jsVer; ?>"></script>
