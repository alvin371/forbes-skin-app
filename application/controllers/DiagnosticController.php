<?php
defined('BASEPATH') or exit('No direct script access allowed');

class DiagnosticController extends CI_Controller
{
    public function check_leave_data()
    {
        $this->load->database();

        header('Content-Type: text/plain');

        echo "=== Leave Requests ===\n";
        $requests = $this->db->select('id, request_no, user_id, status, current_step, created_at')
            ->order_by('created_at', 'DESC')
            ->limit(10)
            ->get('leave_requests')
            ->result_array();

        if (empty($requests)) {
            echo "No leave requests found.\n";
        } else {
            foreach ($requests as $req) {
                echo "ID: {$req['id']}, Request: {$req['request_no']}, User: {$req['user_id']}, Status: {$req['status']}, Step: {$req['current_step']}, Created: {$req['created_at']}\n";
            }
        }

        echo "\n=== Leave Approvals ===\n";
        $approvals = $this->db->select('id, leave_request_id, approver_id, action, step_no')
            ->order_by('leave_request_id', 'DESC')
            ->get('leave_approvals')
            ->result_array();

        if (empty($approvals)) {
            echo "No leave approvals found.\n";
        } else {
            foreach ($approvals as $app) {
                echo "ID: {$app['id']}, Request: {$app['leave_request_id']}, Approver: {$app['approver_id']}, Action: {$app['action']}, Step: {$app['step_no']}\n";
            }
        }

        echo "\n=== Approval Routes ===\n";
        $routes = $this->db->select('user_id, approver_id, is_active')
            ->get('approval_routes')
            ->result_array();

        if (empty($routes)) {
            echo "No approval routes configured.\n";
        } else {
            foreach ($routes as $route) {
                echo "User: {$route['user_id']} -> Approver: {$route['approver_id']}, Active: {$route['is_active']}\n";
            }
        }

        echo "\n=== Active Users ===\n";
        $users = $this->db->select('id, full_name, email')
            ->where('status', 'Aktif')
            ->order_by('id')
            ->limit(10)
            ->get('user')
            ->result_array();

        foreach ($users as $user) {
            echo "ID: {$user['id']}, Name: {$user['full_name']}, Email: {$user['email']}\n";
        }

        echo "\n=== Testing get_pending_for_approver for each user ===\n";
        $this->load->model('LeaveRequestModel');
        foreach ($users as $user) {
            $pending = $this->LeaveRequestModel->get_pending_for_approver($user['id']);
            echo "Approver ID {$user['id']} ({$user['full_name']}): " . count($pending) . " pending requests\n";
        }

        echo "\nDone!\n";
    }
}
