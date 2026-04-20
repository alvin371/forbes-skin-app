<?php
defined('BASEPATH') or exit('No direct script access allowed');

class DiagnosticController extends CI_Controller
{
    public function sentry()
    {
        if (!defined('SENTRY_INITIALIZED') || !function_exists('sentry_capture_message')) {
            return $this->output
                ->set_status_header(503)
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ok' => false,
                    'message' => 'Sentry is not initialized for this environment.',
                )));
        }

        $this->load->helper('sentry');

        $requestPath = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : 'diagnostic/sentry';
        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        $timestamp = gmdate('c');

        $messageId = sentry_capture_message('Diagnostic Sentry verification message', array(
            'area' => 'diagnostic',
            'request_path' => $requestPath,
            'request_method' => $requestMethod,
            'verified_at' => $timestamp,
            'environment' => function_exists('env') ? env('SENTRY_ENVIRONMENT', env('CI_ENV', ENVIRONMENT)) : ENVIRONMENT,
        ));

        $traceAttempted = false;
        if (class_exists('\\Sentry\\Tracing\\TransactionContext') && function_exists('\\Sentry\\startTransaction')) {
            $traceAttempted = true;

            $transaction = \Sentry\startTransaction(
                \Sentry\Tracing\TransactionContext::make()
                    ->setName('GET /diagnostic/sentry verification')
                    ->setOp('diagnostic.http')
                    ->setSource(\Sentry\Tracing\TransactionSource::route())
            );

            \Sentry\configureScope(static function (\Sentry\State\Scope $scope) use ($transaction): void {
                $scope->setSpan($transaction);
            });

            $span = $transaction->startChild(
                \Sentry\Tracing\SpanContext::make()
                    ->setOp('diagnostic.step')
                    ->setDescription('Emit diagnostic Sentry trace')
            );

            usleep(50000);

            $span->setData(array(
                'diagnostic' => true,
                'timestamp' => $timestamp,
            ));
            $span->setHttpStatus(200);
            $span->finish();

            $transaction->setData(array(
                'request_path' => $requestPath,
                'request_method' => $requestMethod,
                'verification' => 'manual',
            ));
            $transaction->setHttpStatus(200);
            $transaction->finish();

            \Sentry\configureScope(static function (\Sentry\State\Scope $scope): void {
                $scope->setSpan(null);
            });
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => true,
                'message' => 'Diagnostic Sentry message and trace were attempted.',
                'message_id' => $messageId ? (string) $messageId : null,
                'trace_attempted' => $traceAttempted,
                'request_path' => $requestPath,
                'verified_at' => $timestamp,
            )));
    }

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
