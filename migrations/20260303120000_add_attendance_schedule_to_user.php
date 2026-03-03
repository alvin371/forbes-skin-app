<?php
/**
 * Migration: Add per-user attendance schedule columns to `user` table.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260303120000_add_attendance_schedule_to_user.php
 * Down: php migrations/run.php 20260303120000_add_attendance_schedule_to_user.php down
 */

if ($direction === 'down') {
    $pdo->exec("ALTER TABLE `user` DROP COLUMN IF EXISTS `attendance_start_time`");
    $pdo->exec("ALTER TABLE `user` DROP COLUMN IF EXISTS `attendance_end_time`");
    echo "Rolled back: removed attendance_start_time, attendance_end_time from user.\n";
    return;
}

// up
$existing = $pdo->query("SHOW COLUMNS FROM `user` LIKE 'attendance_start_time'")->fetchAll();
if (empty($existing)) {
    $pdo->exec("ALTER TABLE `user`
        ADD COLUMN `attendance_start_time` VARCHAR(5) NULL DEFAULT NULL
        COMMENT 'Custom clock-in time HH:MM; NULL = use default 08:00'");
    echo "Added column: attendance_start_time\n";
} else {
    echo "Column attendance_start_time already exists, skipping.\n";
}

$existing = $pdo->query("SHOW COLUMNS FROM `user` LIKE 'attendance_end_time'")->fetchAll();
if (empty($existing)) {
    $pdo->exec("ALTER TABLE `user`
        ADD COLUMN `attendance_end_time` VARCHAR(5) NULL DEFAULT NULL
        COMMENT 'Custom clock-out time HH:MM; NULL = use default 17:00'");
    echo "Added column: attendance_end_time\n";
} else {
    echo "Column attendance_end_time already exists, skipping.\n";
}
