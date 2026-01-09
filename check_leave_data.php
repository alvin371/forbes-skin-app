<?php
define('BASEPATH', __DIR__);
require_once __DIR__ . '/application/config/database.php';

// Create database connection
$conn = new mysqli(
    $db['default']['hostname'],
    $db['default']['username'],
    $db['default']['password'],
    $db['default']['database']
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Leave Requests ===\n";
$result = $conn->query("SELECT id, request_no, user_id, status, current_step FROM leave_requests ORDER BY created_at DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Request: {$row['request_no']}, User: {$row['user_id']}, Status: {$row['status']}, Step: {$row['current_step']}\n";
    }
} else {
    echo "Query error: " . $conn->error . "\n";
}

echo "\n=== Leave Approvals ===\n";
$result = $conn->query("SELECT id, leave_request_id, approver_id, action, step_no FROM leave_approvals ORDER BY leave_request_id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Request: {$row['leave_request_id']}, Approver: {$row['approver_id']}, Action: {$row['action']}, Step: {$row['step_no']}\n";
    }
} else {
    echo "Query error: " . $conn->error . "\n";
}

echo "\n=== Approval Routes ===\n";
$result = $conn->query("SELECT user_id, approver_id, is_active FROM approval_routes");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "User: {$row['user_id']} -> Approver: {$row['approver_id']}, Active: {$row['is_active']}\n";
    }
} else {
    echo "Query error: " . $conn->error . "\n";
}

echo "\n=== Current User IDs ===\n";
$result = $conn->query("SELECT id, full_name, email FROM user WHERE status = 'Aktif' ORDER BY id LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Name: {$row['full_name']}, Email: {$row['email']}\n";
    }
}

$conn->close();
echo "\nDone!\n";
