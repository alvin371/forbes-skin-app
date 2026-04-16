<?php
/**
 * Migration: Add attendance_category column to attendance_logs table.
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260416000200_add_attendance_category_to_attendance_logs.php
 * Down: php migrations/run.php 20260416000200_add_attendance_category_to_attendance_logs.php down
 */

if ($direction === 'down') {
    $pdo->exec("ALTER TABLE `attendance_logs` DROP COLUMN IF EXISTS `attendance_category`");
    echo "Rolled back: removed attendance_category from attendance_logs.\n";
    return;
}

// up
$existing = $pdo->query("SHOW COLUMNS FROM `attendance_logs` LIKE 'attendance_category'")->fetchAll();
if (empty($existing)) {
    $pdo->exec("ALTER TABLE `attendance_logs`
        ADD COLUMN `attendance_category` VARCHAR(32) NOT NULL DEFAULT 'REGULAR'");
    echo "Added column: attendance_category\n";
} else {
    echo "Column attendance_category already exists, skipping.\n";
}

$pdo->exec("UPDATE `attendance_logs` SET `attendance_category` = 'REGULAR'
    WHERE `attendance_category` IS NULL OR `attendance_category` = ''");
echo "Backfilled attendance_category = REGULAR for existing rows.\n";
