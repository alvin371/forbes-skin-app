<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api_hrms extends CI_Controller
{
    private $cooldownSeconds = 60;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('ApiAuth');
        $this->load->model('Office_model');
        $this->load->model('Attendance_log_model');
        $this->load->model('AttendanceSettingsModel');
        $this->load->model('HolidayModel');
        $this->load->model('LeaveTypeModel');
        $this->load->model('LeaveRequestModel');
        $this->load->model('LeaveApprovalModel');
        $this->load->model('ApprovalRouteModel');
        $this->load->model('Performance_model');
        $this->load->library('AttendanceEligibilityService');
        $this->load->library('LeaveCalculatorService');
        $this->load->library('LeaveOverlapService');
        $this->load->library('RequestNoGenerator');
        $this->load->helper('attendance');
    }

    public function auth_login()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $payload = $this->json_input();
        $identifier = trim((string) ($payload['email'] ?? $payload['username'] ?? $payload['identifier'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($identifier === '' || $password === '') {
            return $this->respond(400, array('message' => 'Email/username and password are required.'));
        }

        $this->db->from('user');
        $this->db->group_start();
        $this->db->where('email', $identifier);
        $this->db->or_where('username', $identifier);
        $this->db->group_end();
        $user = $this->db->get()->row_array();

        if (!$user) {
            return $this->respond(401, array('message' => 'Invalid credentials.'));
        }

        $isValid = false;
        if (password_verify($password, $user['password'])) {
            $isValid = true;
        } elseif ($user['password'] === md5($password)) {
            $isValid = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $this->db->update('user', array('password' => $newHash), array('id' => $user['id']));
            $user['password'] = $newHash;
        }

        if (!$isValid) {
            return $this->respond(401, array('message' => 'Invalid credentials.'));
        }

        if (isset($user['status']) && $user['status'] !== 'Aktif') {
            return $this->respond(403, array('message' => 'Account is inactive.'));
        }

        $tokens = $this->apiauth->issue_tokens($user, $this->input->user_agent(), $this->input->ip_address());
        return $this->respond(200, array(
            'accessToken' => $tokens['accessToken'],
            'refreshToken' => $tokens['refreshToken'],
            'user' => $this->user_response($user),
        ));
    }

    public function auth_refresh()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $payload = $this->json_input();
        $refreshToken = trim((string) ($payload['refreshToken'] ?? $payload['refresh_token'] ?? ''));
        if ($refreshToken === '') {
            return $this->respond(400, array('message' => 'Refresh token is required.'));
        }

        $tokens = $this->apiauth->refresh_tokens($refreshToken, $this->input->user_agent(), $this->input->ip_address());
        if (!$tokens) {
            return $this->respond(401, array('message' => 'Invalid refresh token.'));
        }

        return $this->respond(200, array(
            'accessToken' => $tokens['accessToken'],
            'refreshToken' => $tokens['refreshToken'],
        ));
    }

    public function profile()
    {
        $method = $this->input->method(TRUE);
        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->user_requires_attendance((int) $user['id'])) {
            return $this->respond(403, array('message' => 'Attendance is not required for this account.'));
        }

        if ($method === 'GET') {
            return $this->respond(200, $this->user_response($user));
        }

        if (!in_array($method, array('PATCH', 'POST', 'PUT'), true)) {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $payload = $this->json_input();
        $updates = array();
        $errors = array();

        if (isset($payload['name'])) {
            $name = trim((string) $payload['name']);
            if ($name === '') {
                $errors['name'] = 'Name cannot be empty.';
            } else {
                $updates['full_name'] = $name;
            }
        }

        if (isset($payload['email'])) {
            $email = trim((string) $payload['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email address.';
            } else {
                $existing = $this->db->get_where('user', array('email' => $email, 'id !=' => $user['id']))->row_array();
                if ($existing) {
                    $errors['email'] = 'Email is already in use.';
                } else {
                    $updates['email'] = $email;
                }
            }
        }

        $phoneNumber = null;
        if (isset($payload['phone']) || isset($payload['phone_number'])) {
            $phoneNumber = trim((string) ($payload['phone_number'] ?? $payload['phone']));
        }

        if (!empty($errors)) {
            return $this->respond(422, array('message' => 'Validation failed.', 'errors' => $errors));
        }

        $this->db->trans_start();
        if (!empty($updates)) {
            $this->db->update('user', $updates, array('id' => $user['id']));
        }

        if ($phoneNumber !== null) {
            $profile = $this->db->get_where('user_profile', array('user_id' => $user['id']))->row_array();
            if ($profile) {
                $this->db->update('user_profile', array('phone_number' => $phoneNumber), array('user_id' => $user['id']));
            } else {
                $this->db->insert('user_profile', array(
                    'user_id' => $user['id'],
                    'phone_number' => $phoneNumber,
                ));
            }
        }
        $this->db->trans_complete();

        $user = $this->db->get_where('user', array('id' => $user['id']))->row_array();
        return $this->respond(200, $this->user_response($user));
    }

    public function pin_setup()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $payload = $this->json_input();
        $pin = trim((string) ($payload['pin'] ?? ''));
        if (!preg_match('/^\d{6}$/', $pin)) {
            return $this->respond(422, array('message' => 'PIN must be 6 digits.'));
        }

        $hash = password_hash($pin, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->get_where('user_pins', array('user_id' => $user['id']))->row_array();
        if ($existing) {
            $this->db->update('user_pins', array(
                'pin_hash' => $hash,
                'failed_attempts' => 0,
                'locked_until' => null,
                'updated_at' => $now,
            ), array('user_id' => $user['id']));
        } else {
            $this->db->insert('user_pins', array(
                'user_id' => $user['id'],
                'pin_hash' => $hash,
                'failed_attempts' => 0,
                'locked_until' => null,
                'updated_at' => $now,
            ));
        }

        return $this->respond(200, array('ok' => true));
    }

    public function pin_verify()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $payload = $this->json_input();
        $pin = trim((string) ($payload['pin'] ?? ''));
        if ($pin === '') {
            return $this->respond(422, array('message' => 'PIN is required.'));
        }

        $pinData = $this->db->get_where('user_pins', array('user_id' => $user['id']))->row_array();
        if (!$pinData || empty($pinData['pin_hash'])) {
            return $this->respond(404, array('message' => 'PIN is not set.'));
        }

        $now = time();
        $lockedUntil = $pinData['locked_until'] ? strtotime($pinData['locked_until']) : null;
        if ($lockedUntil && $lockedUntil > $now) {
            return $this->respond(200, array(
                'ok' => false,
                'locked' => true,
                'lockedUntil' => $pinData['locked_until'],
            ));
        }

        if (password_verify($pin, $pinData['pin_hash'])) {
            $this->db->update('user_pins', array(
                'failed_attempts' => 0,
                'locked_until' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ), array('user_id' => $user['id']));
            return $this->respond(200, array('ok' => true));
        }

        $maxAttempts = (int) env('PIN_MAX_ATTEMPTS', 5);
        $lockoutMinutes = (int) env('PIN_LOCKOUT_MINUTES', 15);
        $failed = (int) $pinData['failed_attempts'] + 1;
        $update = array(
            'failed_attempts' => $failed,
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $lockedUntilValue = null;
        if ($failed >= $maxAttempts) {
            $lockedUntilValue = date('Y-m-d H:i:s', $now + ($lockoutMinutes * 60));
            $update['locked_until'] = $lockedUntilValue;
        }
        $this->db->update('user_pins', $update, array('user_id' => $user['id']));

        $response = array('ok' => false);
        if ($lockedUntilValue) {
            $response['locked'] = true;
            $response['lockedUntil'] = $lockedUntilValue;
        }

        return $this->respond(200, $response);
    }

    public function pin_reset()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $payload = $this->json_input();
        $password = (string) ($payload['password'] ?? '');

        $canReset = false;
        if ($password !== '') {
            if (password_verify($password, $user['password']) || $user['password'] === md5($password)) {
                $canReset = true;
            }
        }

        if (!$canReset && $this->is_admin($user)) {
            $canReset = true;
        }

        if (!$canReset) {
            return $this->respond(403, array('message' => 'Not authorized to reset PIN.'));
        }

        $this->db->update('user_pins', array(
            'pin_hash' => null,
            'failed_attempts' => 0,
            'locked_until' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ), array('user_id' => $user['id']));

        return $this->respond(200, array('ok' => true));
    }

    public function config()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }
        $office = $this->Office_model->get_active_office();
        if (!$office) {
            return $this->respond(404, array('message' => 'Active office is not configured.'));
        }

        $allowedBssids = $this->parse_allowed_bssids($office['allowed_bssids'] ?? '');
        $allowedSsids = $this->parse_allowed_ssids($office['allowed_ssids'] ?? '');
        $responseTimes = $this->parse_response_times($office['attendance_response_times'] ?? '');
        $historyDays = isset($office['attendance_history_days']) ? (int) $office['attendance_history_days'] : 30;
        $recapMonths = isset($office['attendance_recap_months']) ? (int) $office['attendance_recap_months'] : 6;

        return $this->respond(200, array(
            'office' => array(
                'id' => (int) $office['id'],
                'name' => $office['name'],
                'lat' => (float) $office['lat'],
                'lng' => (float) $office['lng'],
                'radius_m' => (int) $office['radius_m'],
                'min_accuracy_m' => (int) $office['min_accuracy_m'],
                'allowed_ip_cidrs' => $office['allowed_ip_cidrs'] ?? '',
            ),
            'attendance' => array(
                'min_accuracy_m' => (int) $office['min_accuracy_m'],
                'radius_m' => (int) $office['radius_m'],
                'requires_ip' => !empty(trim((string) ($office['allowed_ip_cidrs'] ?? ''))),
                'response_times' => $responseTimes,
                'history_days' => $historyDays,
                'recap_months' => $recapMonths,
            ),
            'wifi' => array(
                'allowed_bssids' => $allowedBssids,
                'allowed_ssids' => $allowedSsids,
            ),
        ));
    }

    public function attendance_office_proof()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }
        $office = $this->Office_model->get_active_office();
        if (!$office) {
            return $this->respond(404, array('message' => 'Active office is not configured.'));
        }

        $payload = $this->json_input();
        $wifiProof = $payload['wifiProof'] ?? null;

        $ok = $this->wifi_proof_ok($office, $wifiProof);
        return $this->respond(200, array('ok' => $ok));
    }

    public function attendance_check_in()
    {
        return $this->attendance_check('IN');
    }

    public function attendance_check_out()
    {
        return $this->attendance_check('OUT');
    }

    public function attendance_history()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $office = $this->Office_model->get_active_office();
        $items = null;
        if ($office && !empty($office['attendance_history_days'])) {
            $startDate = date('Y-m-d H:i:s', strtotime('-' . (int) $office['attendance_history_days'] . ' days'));
            $items = $this->Attendance_log_model->get_by_user_since($user['id'], $startDate);
        } else {
            $items = $this->Attendance_log_model->get_by_user($user['id']);
        }
        return $this->respond(200, array('data' => $items));
    }

    public function attendance_recap()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $month = $this->input->get('month', TRUE);
        $month = $month ?: date('Y-m');

        $office = $this->Office_model->get_active_office();
        $report = $this->build_monthly_report((int) $user['id'], $month, $office);
        if (isset($report['error'])) {
            return $this->respond(400, array('message' => $report['error']));
        }

        return $this->respond(200, $report['summary']);
    }

    public function attendance_recap_all()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->is_admin_hr_user($user['id'])) {
            return $this->respond(403, array('message' => 'Not authorized.'));
        }

        $month = $this->input->get('month', TRUE);
        $month = $month ?: date('Y-m');
        $office = $this->Office_model->get_active_office();

        $users = $this->get_attendance_users();
        $items = array();
        foreach ($users as $member) {
            $report = $this->build_monthly_report((int) $member['id'], $month, $office);
            if (!isset($report['summary'])) {
                continue;
            }
            $summary = $report['summary'];
            $summary['user'] = array(
                'id' => (int) $member['id'],
                'name' => $member['full_name'] ?? null,
                'email' => $member['email'] ?? null,
                'role' => $member['role_text'] ?? ($member['role'] ?? null),
            );
            $items[] = $summary;
        }

        return $this->respond(200, array(
            'month' => $month,
            'data' => $items,
        ));
    }

    public function attendance_report()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $month = $this->input->get('month', TRUE);
        $month = $month ?: date('Y-m');
        $office = $this->Office_model->get_active_office();

        $report = $this->build_monthly_report((int) $user['id'], $month, $office);
        if (isset($report['error'])) {
            return $this->respond(400, array('message' => $report['error']));
        }

        return $this->respond(200, $report);
    }

    public function holidays()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $startDate = trim((string) $this->input->get('start', TRUE));
        $endDate = trim((string) $this->input->get('end', TRUE));
        $errors = array();

        if ($startDate !== '' && !$this->is_valid_date($startDate)) {
            $errors['start'] = 'Invalid date format.';
        }
        if ($endDate !== '' && !$this->is_valid_date($endDate)) {
            $errors['end'] = 'Invalid date format.';
        }
        if (empty($errors) && $startDate !== '' && $endDate !== '' && strtotime($startDate) > strtotime($endDate)) {
            $errors['start'] = 'Start date must be before or equal to end date.';
        }

        if (!empty($errors)) {
            return $this->respond(422, array('message' => 'Validation failed.', 'errors' => $errors));
        }

        $rows = $this->HolidayModel->get_active_filtered(
            $startDate !== '' ? $startDate : null,
            $endDate !== '' ? $endDate : null
        );
        $items = array();
        foreach ($rows as $holiday) {
            $date = $holiday['date'];
            $items[] = array(
                'id' => (int) $holiday['id'],
                'date' => $date,
                'name' => $holiday['name'],
                'dayName' => date('l', strtotime($date)),
                'isHoliday' => true,
            );
        }

        return $this->respond(200, array('data' => $items));
    }

    public function leave()
    {
        $method = $this->input->method(TRUE);
        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->user_requires_attendance((int) $user['id'])) {
            return $this->respond(403, array('message' => 'Attendance is not required for this account.'));
        }

        if ($method === 'GET') {
            $requests = $this->LeaveRequestModel->get_by_user($user['id']);
            $items = array();
            foreach ($requests as $request) {
                $items[] = array(
                    'id' => (int) $request['id'],
                    'requestNo' => $request['request_no'],
                    'leaveTypeId' => (int) $request['leave_type_id'],
                    'leaveTypeName' => $request['leave_type_name'] ?? null,
                    'leaveTypeCode' => $request['leave_type_code'] ?? null,
                    'startDate' => $request['start_date'],
                    'endDate' => $request['end_date'],
                    'daysCount' => (int) $request['days_count'],
                    'reason' => $request['reason'],
                    'status' => $this->map_leave_status($request['status']),
                    'attachmentPath' => $request['attachment_path'],
                );
            }

            return $this->respond(200, array('data' => $items));
        }

        if ($method !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $input = $this->json_input();
        list($errors, $clean, $leaveType) = $this->validate_leave_request($input, $user['id']);
        if (!empty($errors)) {
            return $this->respond(422, array('message' => 'Validation failed.', 'errors' => $errors));
        }

        $requestNo = $this->requestnogenerator->generate_unique();
        $attachmentPath = null;

        if ($leaveType && (int) $leaveType['requires_attachment'] === 1) {
            $upload = $this->handle_attachment_upload($requestNo, 'attachment');
            if (isset($upload['error'])) {
                return $this->respond(422, array('message' => $upload['error']));
            }
            $attachmentPath = $upload['path'];
        } elseif (!empty($_FILES['attachment']['name'])) {
            $upload = $this->handle_attachment_upload($requestNo, 'attachment');
            if (isset($upload['error'])) {
                return $this->respond(422, array('message' => $upload['error']));
            }
            $attachmentPath = $upload['path'];
        }

        $now = date('Y-m-d H:i:s');
        $route = $this->ApprovalRouteModel->get_active_by_user($user['id']);
        $hasApprover = $route && !empty($route['approver_id']);
        $this->db->trans_start();
        $requestId = $this->LeaveRequestModel->insert(array(
            'request_no' => $requestNo,
            'user_id' => $user['id'],
            'leave_type_id' => $clean['leave_type_id'],
            'start_date' => $clean['start_date'],
            'end_date' => $clean['end_date'],
            'days_count' => $clean['days_count'],
            'reason' => $clean['reason'],
            'attachment_path' => $attachmentPath,
            'status' => $hasApprover ? 'PENDING_APPROVAL' : 'SUBMITTED',
            'current_step' => $hasApprover ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        if ($hasApprover) {
            $this->LeaveApprovalModel->insert(array(
                'leave_request_id' => $requestId,
                'step_no' => 1,
                'approver_id' => (int) $route['approver_id'],
                'action' => 'PENDING',
            ));
        }
        $this->db->trans_complete();

        return $this->respond(201, array(
            'id' => (int) $requestId,
            'requestNo' => $requestNo,
            'status' => $hasApprover ? 'Pending' : 'Submitted',
        ));
    }

    public function leave_quota()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->user_requires_attendance((int) $user['id'])) {
            return $this->respond(403, array('message' => 'Attendance is not required for this account.'));
        }

        $this->load->model('LeaveQuotaModel');

        $quotas = $this->LeaveQuotaModel->get_by_user($user['id']);

        $items = array();
        $totalSum = 0;
        $remainingSum = 0;

        foreach ($quotas as $quota) {
            $total = (int) $quota['total_days'];
            $remaining = (int) $quota['remaining_days'];
            $used = $total - $remaining;
            $percentage = $total > 0 ? round(($remaining / $total) * 100, 1) : 0;

            $items[] = array(
                'id' => (int) $quota['id'],
                'leaveTypeId' => (int) $quota['leave_type_id'],
                'leaveTypeName' => $quota['leave_type_name'] ?? null,
                'leaveTypeCode' => $quota['leave_type_code'] ?? null,
                'totalDays' => $total,
                'remainingDays' => $remaining,
                'usedDays' => $used,
                'percentageRemaining' => $percentage,
                'status' => $this->get_quota_status($percentage),
                'updatedAt' => $quota['updated_at'],
            );

            $totalSum += $total;
            $remainingSum += $remaining;
        }

        return $this->respond(200, array(
            'summary' => array(
                'totalDays' => $totalSum,
                'remainingDays' => $remainingSum,
                'usedDays' => $totalSum - $remainingSum,
            ),
            'quotas' => $items,
        ));
    }

    public function leave_quota_detail()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->user_requires_attendance((int) $user['id'])) {
            return $this->respond(403, array('message' => 'Attendance is not required for this account.'));
        }

        $leaveTypeId = (int) $this->input->get('leave_type_id', TRUE);
        if ($leaveTypeId <= 0) {
            return $this->respond(400, array('message' => 'leave_type_id is required.'));
        }

        $this->load->model('LeaveQuotaModel');

        $quota = $this->LeaveQuotaModel->get_by_user_and_type($user['id'], $leaveTypeId);

        if (!$quota) {
            return $this->respond(404, array('message' => 'Quota not found for this leave type.'));
        }

        $this->db->select('id, request_no, start_date, end_date, days_count, status, created_at');
        $this->db->from('leave_requests');
        $this->db->where('user_id', (int) $user['id']);
        $this->db->where('leave_type_id', $leaveTypeId);
        $this->db->where_in('status', array('APPROVED', 'PENDING_APPROVAL'));
        $this->db->order_by('created_at', 'DESC');
        $requests = $this->db->get()->result_array();

        $usageHistory = array();
        foreach ($requests as $req) {
            $usageHistory[] = array(
                'requestNo' => $req['request_no'],
                'startDate' => $req['start_date'],
                'endDate' => $req['end_date'],
                'daysCount' => (int) $req['days_count'],
                'status' => $this->map_leave_status($req['status']),
                'createdAt' => $req['created_at'],
            );
        }

        $total = (int) $quota['total_days'];
        $remaining = (int) $quota['remaining_days'];
        $used = $total - $remaining;

        return $this->respond(200, array(
            'quota' => array(
                'id' => (int) $quota['id'],
                'leaveTypeId' => (int) $quota['leave_type_id'],
                'leaveTypeName' => $quota['leave_type_name'] ?? null,
                'leaveTypeCode' => $quota['leave_type_code'] ?? null,
                'totalDays' => $total,
                'remainingDays' => $remaining,
                'usedDays' => $used,
                'percentageRemaining' => $total > 0 ? round(($remaining / $total) * 100, 1) : 0,
                'updatedAt' => $quota['updated_at'],
            ),
            'usageHistory' => $usageHistory,
        ));
    }

    private function get_quota_status($percentage)
    {
        if ($percentage > 50) {
            return 'healthy';
        } elseif ($percentage > 20) {
            return 'low';
        } else {
            return 'critical';
        }
    }

    public function performance_templates_active()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $period_year = $this->input->get('period_year');
        if (!$period_year) {
            return $this->respond(400, array('message' => 'period_year is required.'));
        }

        $userRow = $this->db->get_where('user', array('id' => $user['id']))->row_array();
        $department = $userRow['department'] ?? null;

        $template = $this->Performance_model->get_active_template_for_employee($period_year, $department);
        if (!$template) {
            return $this->respond(404, array('message' => 'No active template found.'));
        }

        return $this->respond(200, array('data' => $template));
    }

    public function performance_submissions()
    {
        $method = $this->input->method(TRUE);
        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if ($method === 'GET') {
            $filters = array('employee_id' => $user['id']);
            if ($this->input->get('period_year')) {
                $filters['period_year'] = $this->input->get('period_year');
            }
            $submissions = $this->Performance_model->get_submissions($filters);
            foreach ($submissions as &$sub) {
                $sub['total_score'] = round($sub['total_score'], 2);
            }
            unset($sub);
            return $this->respond(200, array('data' => $submissions));
        }

        if ($method !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $input = $this->json_input();
        if (empty($input['template_id']) || empty($input['items'])) {
            return $this->respond(400, array('message' => 'template_id and items are required.'));
        }

        $template = $this->Performance_model->get_template_by_id($input['template_id'], false);
        if (!$template) {
            return $this->respond(404, array('message' => 'Template not found.'));
        }

        $userRow = $this->db->get_where('user', array('id' => $user['id']))->row_array();
        $input['employee_id'] = $user['id'];
        $input['employee_snapshot'] = array(
            'name' => $userRow['full_name'] ?? null,
            'nik' => $userRow['nik'] ?? null,
            'department' => $userRow['department'] ?? null,
            'position' => $userRow['position'] ?? null,
        );
        $input['period_year'] = $template['period_year'];

        $result = $this->Performance_model->create_submission($input);
        if (!$result['success']) {
            return $this->respond(400, array('message' => $result['error']));
        }

        return $this->respond(201, array(
            'id' => $result['id'],
            'total_score' => round($result['total_score'], 2),
            'message' => 'Submission created.'
        ));
    }

    public function performance_submission_detail($id = null)
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$id) {
            return $this->respond(400, array('message' => 'Submission ID is required.'));
        }

        $submission = $this->Performance_model->get_submission_by_id($id, true);
        if (!$submission) {
            return $this->respond(404, array('message' => 'Submission not found.'));
        }

        if ((int) $submission['employee_id'] !== (int) $user['id']) {
            return $this->respond(403, array('message' => 'Access denied.'));
        }

        $submission['total_score'] = round($submission['total_score'], 2);
        foreach ($submission['items'] as &$item) {
            $item['score_ratio'] = round($item['score_ratio'], 4);
            $item['final_score'] = round($item['final_score'], 2);
        }
        unset($item);

        return $this->respond(200, array('data' => $submission));
    }

    private function attendance_check($type)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $payload = $this->json_input();
        $lat = $payload['lat'] ?? null;
        $lng = $payload['lng'] ?? null;
        $accuracy = $payload['gpsAccuracy'] ?? $payload['accuracy'] ?? null;
        $wifiProof = $payload['wifiProof'] ?? null;

        $errors = array();
        if (!is_numeric($lat) || $lat < -90 || $lat > 90) {
            $errors['lat'] = 'Latitude must be between -90 and 90.';
        }
        if (!is_numeric($lng) || $lng < -180 || $lng > 180) {
            $errors['lng'] = 'Longitude must be between -180 and 180.';
        }
        if (!is_numeric($accuracy) || $accuracy < 0) {
            $errors['gpsAccuracy'] = 'Accuracy must be a positive number.';
        }
        if (!empty($errors)) {
            return $this->respond(422, array('message' => 'Validation failed.', 'errors' => $errors));
        }

        if ($this->Attendance_log_model->has_recent_log($user['id'], $this->cooldownSeconds)) {
            return $this->respond(429, array('message' => 'Please wait before confirming again.'));
        }

        $ipAddress = $this->input->ip_address();
        $result = $this->attendanceeligibilityservice->evaluate($lat, $lng, $accuracy, $ipAddress);
        if (isset($result['error'])) {
            return $this->respond(500, array('message' => 'Active office is not configured.'));
        }

        $office = $result['office'];
        $officeRecord = $this->Office_model->get_by_id($office['id']);
        if ($officeRecord) {
            $office['allowed_bssids'] = $officeRecord['allowed_bssids'] ?? '';
            $office['allowed_ip_cidrs'] = $officeRecord['allowed_ip_cidrs'] ?? '';
            $office['allowed_ssids'] = $officeRecord['allowed_ssids'] ?? '';
        }
        $hasWifiRules = $this->has_wifi_rules($office);
        $wifiOk = $this->wifi_proof_ok($office, $wifiProof);
        $ipOk = $result['computed']['ip_ok'] ?? true;
        if ($hasWifiRules) {
            $canConfirm = $wifiOk;
        } else {
            $canConfirm = $result['computed']['can_confirm'];
        }
        if (!$canConfirm) {
            $reasons = $hasWifiRules ? array() : $result['computed']['reasons'];
            if ($hasWifiRules && $wifiProof === null) {
                $reasons[] = 'WIFI_REQUIRED';
            } elseif ($hasWifiRules && !$wifiOk) {
                $reasons[] = 'WIFI_NOT_ALLOWED';
            }
            return $this->respond(403, array(
                'message' => 'Attendance confirmation requirements not met.',
                'reasons' => $reasons,
            ));
        }

        $method = $office['has_ip_rule'] ? 'GEOFENCE+IP' : 'GEOFENCE';
        if ($this->has_wifi_rules($office)) {
            $method = $office['has_ip_rule'] ? 'GEOFENCE+IP+WIFI' : 'GEOFENCE+WIFI';
        }

        $distance = $result['computed']['distance_m'];
        $insertData = array(
            'user_id' => (int) $user['id'],
            'office_id' => (int) $office['id'],
            'type' => $type,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'accuracy' => $accuracy,
            'distance_m' => (float) $distance,
            'method' => $method,
            'ip_address' => $ipAddress,
            'user_agent' => $this->input->user_agent(),
            'created_at' => date('Y-m-d H:i:s'),
        );

        if (!$this->Attendance_log_model->insert($insertData)) {
            return $this->respond(500, array('message' => 'Failed to store attendance log.'));
        }

        return $this->respond(200, array(
            'ok' => true,
            'type' => $type,
            'distanceMeters' => round($distance, 2),
            'office' => array(
                'id' => (int) $office['id'],
                'name' => $office['name'],
            ),
        ));
    }

    private function validate_leave_request($input, $userId)
    {
        $errors = array();
        $clean = array();
        $leaveType = null;

        $clean['leave_type_id'] = (int) ($input['leave_type_id'] ?? 0);
        if ($clean['leave_type_id'] <= 0) {
            $errors['leave_type_id'] = 'Leave type is required.';
        } else {
            $leaveType = $this->LeaveTypeModel->get_by_id($clean['leave_type_id']);
            if (!$leaveType || (int) $leaveType['is_active'] !== 1) {
                $errors['leave_type_id'] = 'Leave type is invalid.';
            }
        }

        $clean['start_date'] = trim((string) ($input['start_date'] ?? ''));
        $clean['end_date'] = trim((string) ($input['end_date'] ?? ''));
        if ($clean['start_date'] === '' || $clean['end_date'] === '') {
            $errors['start_date'] = 'Start date and end date are required.';
        } elseif (!$this->is_valid_date($clean['start_date']) || !$this->is_valid_date($clean['end_date'])) {
            $errors['start_date'] = 'Invalid date format.';
        } elseif (strtotime($clean['start_date']) > strtotime($clean['end_date'])) {
            $errors['start_date'] = 'Start date must be before or equal to end date.';
        }

        $clean['reason'] = trim((string) ($input['reason'] ?? ''));
        $clean['days_count'] = $this->leavecalculatorservice->calculate_days($clean['start_date'], $clean['end_date']);
        if ($clean['days_count'] <= 0) {
            $errors['days_count'] = 'Unable to calculate leave days.';
        }

        if ($leaveType && !empty($leaveType['max_days_per_request'])) {
            if ((int) $leaveType['max_days_per_request'] < $clean['days_count']) {
                $errors['days_count'] = 'Requested days exceed the leave type limit.';
            }
        }

        if (empty($errors) && $this->leaveoverlapservice->has_overlap($userId, $clean['start_date'], $clean['end_date'])) {
            $errors['start_date'] = 'Leave dates overlap with an existing request.';
        }

        if ($leaveType && (int) $leaveType['requires_attachment'] === 1) {
            if (empty($_FILES['attachment']['name'])) {
                $errors['attachment'] = 'Attachment is required for this leave type.';
            }
        }

        return array($errors, $clean, $leaveType);
    }

    private function handle_attachment_upload($requestNo, $fieldName)
    {
        $uploadDir = FCPATH . 'writable/uploads/leaves/' . $requestNo . '/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return array('error' => 'Failed to create attachment directory.');
            }
        }

        $config['upload_path'] = $uploadDir;
        $config['allowed_types'] = 'pdf|jpg|jpeg|png';
        $config['max_size'] = 2048;
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload($fieldName)) {
            return array('error' => strip_tags($this->upload->display_errors('', '')));
        }

        $file = $this->upload->data();
        $relativePath = 'writable/uploads/leaves/' . $requestNo . '/' . $file['file_name'];

        return array('path' => $relativePath);
    }

    private function json_input()
    {
        $payload = json_decode($this->input->raw_input_stream, true);
        if (!is_array($payload)) {
            $payload = $this->input->post(NULL, true);
        }
        return is_array($payload) ? $payload : array();
    }

    private function respond($statusCode, $payload)
    {
        return $this->output
            ->set_status_header($statusCode)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function require_user()
    {
        $user = $this->apiauth->authenticate();
        if (!$user) {
            $this->respond(401, array('message' => 'Unauthorized'));
            return null;
        }
        return $user;
    }

    private function user_response($user)
    {
        return array(
            'id' => (int) $user['id'],
            'name' => $user['full_name'] ?? null,
            'email' => $user['email'] ?? null,
            'role' => $user['role_text'] ?? ($user['role'] ?? null),
        );
    }

    private function map_leave_status($status)
    {
        switch ($status) {
            case 'PENDING_APPROVAL':
                return 'Pending';
            case 'APPROVED':
                return 'Approved';
            case 'REJECTED':
            case 'CANCELLED':
                return 'Rejected';
            default:
                return $status;
        }
    }

    private function is_valid_date($date)
    {
        $format = 'Y-m-d';
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    private function is_admin($user)
    {
        $roleText = strtolower((string) ($user['role_text'] ?? ''));
        $role = (string) ($user['role'] ?? '');
        return in_array($role, array('1', '2'), true)
            || strpos($roleText, 'admin') !== false
            || strpos($roleText, 'super') !== false;
    }

    private function parse_allowed_bssids($text)
    {
        $items = preg_split('/\r\n|\r|\n|,/', (string) $text);
        $list = array();
        foreach ($items as $item) {
            $item = trim($item);
            if ($item !== '') {
                $list[] = $item;
            }
        }
        return $list;
    }

    private function has_wifi_rules($office)
    {
        $allowedBssids = $office['allowed_bssids'] ?? '';
        $allowedSsids = $office['allowed_ssids'] ?? '';
        return trim((string) $allowedBssids) !== '' || trim((string) $allowedSsids) !== '';
    }

    private function wifi_proof_ok($office, $wifiProof)
    {
        $allowed = $this->parse_allowed_bssids($office['allowed_bssids'] ?? '');
        $allowedSsids = $this->parse_allowed_ssids($office['allowed_ssids'] ?? '');
        if (empty($allowed) && empty($allowedSsids)) {
            return true;
        }

        if ($wifiProof === null) {
            return false;
        }

        $proofBssids = array();
        $proofSsids = array();
        if (is_string($wifiProof)) {
            $proofBssids[] = $wifiProof;
        } elseif (is_array($wifiProof)) {
            if (isset($wifiProof['bssid'])) {
                $proofBssids[] = $wifiProof['bssid'];
            }
            if (isset($wifiProof['ssid'])) {
                $proofSsids[] = $wifiProof['ssid'];
            }
            if (isset($wifiProof['bssids']) && is_array($wifiProof['bssids'])) {
                $proofBssids = array_merge($proofBssids, $wifiProof['bssids']);
            }
            if (isset($wifiProof['ssids']) && is_array($wifiProof['ssids'])) {
                $proofSsids = array_merge($proofSsids, $wifiProof['ssids']);
            }
        }

        $allowedLookup = array();
        foreach ($allowed as $bssid) {
            $allowedLookup[strtolower($bssid)] = true;
        }
        $allowedSsidLookup = array();
        foreach ($allowedSsids as $ssid) {
            $allowedSsidLookup[strtolower($ssid)] = true;
        }

        $hasAllowedBssid = !empty($allowedLookup);
        $hasAllowedSsid = !empty($allowedSsidLookup);

        if ($hasAllowedBssid && $hasAllowedSsid) {
            return $this->match_any($proofBssids, $allowedLookup) && $this->match_any($proofSsids, $allowedSsidLookup);
        }

        if ($hasAllowedBssid) {
            return $this->match_any($proofBssids, $allowedLookup);
        }

        if ($hasAllowedSsid) {
            return $this->match_any($proofSsids, $allowedSsidLookup);
        }

        return true;
    }

    private function match_any($values, $lookup)
    {
        foreach ($values as $value) {
            $key = strtolower(trim((string) $value));
            if ($key !== '' && isset($lookup[$key])) {
                return true;
            }
        }
        return false;
    }

    private function is_admin_hr_user($userId)
    {
        $userId = (int) $userId;
        try {
            $rolesTable = $this->db->query("SHOW TABLES LIKE 'roles'")->result_array();
            $userRolesTable = $this->db->query("SHOW TABLES LIKE 'user_roles'")->result_array();

            if (!empty($rolesTable) && !empty($userRolesTable)) {
                $roles = $this->db->query("
                    SELECT r.name
                    FROM user_roles ur
                    INNER JOIN roles r ON ur.role_id = r.id
                    WHERE ur.user_id = ? AND r.is_active = 1
                ", array($userId))->result_array();

                foreach ($roles as $role) {
                    $name = strtolower((string) $role['name']);
                    if (in_array($name, array('super_admin', 'admin', 'hr', 'human_resources'), true)) {
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            // fall through
        }

        $legacy = $this->db->query("SELECT role, role_text FROM user WHERE id = ? LIMIT 1", array($userId))->row_array();
        if ($legacy) {
            if (isset($legacy['role']) && in_array((string) $legacy['role'], array('1', '2', '7'), true)) {
                return true;
            }
            if (!empty($legacy['role_text'])) {
                $roleText = strtolower((string) $legacy['role_text']);
                if (strpos($roleText, 'admin') !== false || strpos($roleText, 'hr') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function user_requires_attendance($userId)
    {
        $allowed = $this->AttendanceSettingsModel->get_allowed_role_ids();
        if (empty($allowed)) {
            return true;
        }

        $userRoleIds = $this->get_user_role_ids($userId);
        foreach ($userRoleIds as $roleId) {
            if (in_array($roleId, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    private function get_user_role_ids($userId)
    {
        $userId = (int) $userId;
        $rolesTable = $this->db->query("SHOW TABLES LIKE 'user_roles'")->result_array();
        if (!empty($rolesTable)) {
            $rows = $this->db->get_where('user_roles', array('user_id' => $userId))->result_array();
            $roleIds = array();
            foreach ($rows as $row) {
                if (isset($row['role_id'])) {
                    $roleIds[] = (int) $row['role_id'];
                }
            }
            if (!empty($roleIds)) {
                return array_values(array_unique($roleIds));
            }
        }

        $legacy = $this->db->query("SELECT role FROM user WHERE id = ? LIMIT 1", array($userId))->row_array();
        if ($legacy && isset($legacy['role']) && is_numeric($legacy['role'])) {
            return array((int) $legacy['role']);
        }

        return array();
    }

    private function get_attendance_users()
    {
        $allowed = $this->AttendanceSettingsModel->get_allowed_role_ids();
        $this->db->from('user');
        $this->db->where('status', 'Aktif');
        if (!empty($allowed)) {
            $this->db->where_in('role', $allowed);
        }
        $this->db->order_by('full_name', 'ASC');
        return $this->db->get()->result_array();
    }

    private function build_monthly_report($userId, $month, $office)
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return array('error' => 'Invalid month format.');
        }

        $startDate = $month . '-01';
        $startTs = strtotime($startDate);
        if ($startTs === false) {
            return array('error' => 'Invalid month.');
        }
        $endDate = date('Y-m-t', $startTs);

        $holidayRows = $this->HolidayModel->get_active_between($startDate, $endDate);
        $holidayLookup = array();
        foreach ($holidayRows as $holiday) {
            $holidayLookup[$holiday['date']] = $holiday['name'];
        }

        $leaveDays = $this->get_approved_leave_days($userId, $startDate, $endDate);
        $logs = $this->get_attendance_logs($userId, $startDate, $endDate);

        $times = $this->resolve_attendance_times($office);
        $startTime = $times['start'];
        $endTime = $times['end'];

        $weekendType = $this->AttendanceSettingsModel->get_settings()['weekend_type'] ?? 'SATURDAY_SUNDAY';

        $presentDays = 0;
        $lateCount = 0;
        $earlyCheckoutCount = 0;
        $absentCount = 0;
        $leaveCount = 0;
        $daily = array();

        $period = new DatePeriod(new DateTime($startDate), new DateInterval('P1D'), (new DateTime($endDate))->modify('+1 day'));
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $weekday = (int) $date->format('N'); // 1-7 (Mon-Sun)
            $isWeekend = $this->is_weekend_day($weekday, $weekendType);
            $holidayName = $holidayLookup[$day] ?? null;
            $isLeave = in_array($day, $leaveDays, true);

            $dayLogs = $logs[$day] ?? array();
            $firstIn = $dayLogs['first_in'] ?? null;
            $lastOut = $dayLogs['last_out'] ?? null;

            $hasIn = $firstIn !== null;
            $hasOut = $lastOut !== null;

            $status = 'Absent';
            $isLate = false;
            $isEarlyCheckout = false;

            if ($isWeekend) {
                $status = 'Weekend';
            } elseif ($holidayName) {
                $status = 'Holiday';
            } elseif ($isLeave) {
                $status = 'Leave';
                $leaveCount++;
            } elseif ($hasIn || $hasOut) {
                $status = 'Present';
                $presentDays++;

                if ($hasIn && $this->is_late($firstIn, $startTime)) {
                    $isLate = true;
                    $lateCount++;
                }
                if ($hasOut && $this->is_early_checkout($lastOut, $endTime)) {
                    $isEarlyCheckout = true;
                    $earlyCheckoutCount++;
                }
            } else {
                $absentCount++;
            }

            $daily[] = array(
                'date' => $day,
                'status' => $status,
                'first_in' => $firstIn,
                'last_out' => $lastOut,
                'late' => $isLate,
                'early_checkout' => $isEarlyCheckout,
                'holiday_name' => $holidayName,
            );
        }

        $summary = array(
            'month' => $month,
            'present_days' => $presentDays,
            'late_count' => $lateCount,
            'early_checkout_count' => $earlyCheckoutCount,
            'absent_count' => $absentCount,
            'leave_days' => $leaveCount,
            'start_time' => $startTime,
            'end_time' => $endTime,
        );

        return array(
            'summary' => $summary,
            'daily' => $daily,
        );
    }

    private function get_attendance_logs($userId, $startDate, $endDate)
    {
        $rows = $this->db->query("
            SELECT type, created_at
            FROM attendance_logs
            WHERE user_id = ? AND created_at >= ? AND created_at <= ?
            ORDER BY created_at ASC
        ", array($userId, $startDate . ' 00:00:00', $endDate . ' 23:59:59'))->result_array();

        $logs = array();
        foreach ($rows as $row) {
            $day = substr($row['created_at'], 0, 10);
            if (!isset($logs[$day])) {
                $logs[$day] = array(
                    'first_in' => null,
                    'last_out' => null,
                );
            }
            if ($row['type'] === 'IN') {
                if ($logs[$day]['first_in'] === null || $row['created_at'] < $logs[$day]['first_in']) {
                    $logs[$day]['first_in'] = $row['created_at'];
                }
            }
            if ($row['type'] === 'OUT') {
                if ($logs[$day]['last_out'] === null || $row['created_at'] > $logs[$day]['last_out']) {
                    $logs[$day]['last_out'] = $row['created_at'];
                }
            }
        }

        return $logs;
    }

    private function get_approved_leave_days($userId, $startDate, $endDate)
    {
        $rows = $this->db->query("
            SELECT start_date, end_date
            FROM leave_requests
            WHERE user_id = ? AND status = 'APPROVED'
            AND end_date >= ? AND start_date <= ?
        ", array($userId, $startDate, $endDate))->result_array();

        $days = array();
        foreach ($rows as $row) {
            $period = new DatePeriod(new DateTime($row['start_date']), new DateInterval('P1D'), (new DateTime($row['end_date']))->modify('+1 day'));
            foreach ($period as $date) {
                $day = $date->format('Y-m-d');
                if ($day >= $startDate && $day <= $endDate) {
                    $days[] = $day;
                }
            }
        }

        return array_values(array_unique($days));
    }

    private function resolve_attendance_times($office)
    {
        $start = '08:00';
        $end = '17:00';
        if ($office && !empty($office['attendance_response_times'])) {
            $times = $this->parse_response_times($office['attendance_response_times']);
            if (!empty($times[0])) {
                $start = $times[0];
            }
            if (!empty($times[1])) {
                $end = $times[1];
            }
        }

        return array('start' => $start, 'end' => $end);
    }

    private function is_weekend_day($weekday, $weekendType)
    {
        if ($weekendType === 'SUNDAY_ONLY') {
            return $weekday === 7;
        }

        return $weekday === 6 || $weekday === 7;
    }

    private function is_late($timestamp, $startTime)
    {
        $start = strtotime(substr($timestamp, 0, 10) . ' ' . $startTime . ':00');
        $threshold = $start + (30 * 60);
        return strtotime($timestamp) > $threshold;
    }

    private function is_early_checkout($timestamp, $endTime)
    {
        $end = strtotime(substr($timestamp, 0, 10) . ' ' . $endTime . ':00');
        $threshold = $end - (30 * 60);
        return strtotime($timestamp) < $threshold;
    }

    private function parse_allowed_ssids($text)
    {
        $items = preg_split('/\r\n|\r|\n|,/', (string) $text);
        $list = array();
        foreach ($items as $item) {
            $item = trim($item);
            if ($item !== '') {
                $list[] = $item;
            }
        }
        return $list;
    }

    private function parse_response_times($text)
    {
        $items = preg_split('/\r\n|\r|\n|,/', (string) $text);
        $list = array();
        foreach ($items as $item) {
            $item = trim($item);
            if ($item !== '') {
                $list[] = $item;
            }
        }
        return $list;
    }
}
