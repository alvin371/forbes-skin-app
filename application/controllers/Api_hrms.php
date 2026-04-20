<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api_hrms extends CI_Controller
{
    private $cooldownSeconds = 60;
    private $lateGraceMinutes = 15;
    private $earlyGraceMinutes = 15;

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
        $this->load->model('ApprovalStepModel');
        $this->load->model('LeaveLedgerModel');
        $this->load->model('Performance_model');
        $this->load->library('AttendanceEligibilityService');
        $this->load->library('LeaveCalculatorService');
        $this->load->library('LeaveOverlapService');
        $this->load->library('RequestNoGenerator');
        $this->load->library('ApprovalWorkflowEngine');
        $this->load->library('LeaveQuotaService');
        $this->load->library('permission');
        $this->load->library('UploadService');
        $this->load->helper('attendance');
    }

    public function auth_login()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $payload = $this->json_input();
        $identifier = '';
        foreach (array('email', 'username', 'identifier', 'login') as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            $value = trim((string) $payload[$field]);
            if ($value !== '') {
                $identifier = $value;
                break;
            }
        }
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

        if (isset($payload['keterangan']) || isset($payload['description'])) {
            $updates['desc'] = trim((string) ($payload['keterangan'] ?? $payload['description']));
        }

        if (
            isset($payload['profilePicture']) ||
            isset($payload['profilePicturePath']) ||
            isset($payload['profile_picture']) ||
            isset($payload['profile_picture_path'])
        ) {
            $profilePicture = trim((string) (
                $payload['profilePicture']
                ?? $payload['profilePicturePath']
                ?? $payload['profile_picture']
                ?? $payload['profile_picture_path']
            ));

            if ($profilePicture === '') {
                $updates['img'] = null;
            } else {
                $normalizedProfileImage = $this->normalize_profile_image_input($profilePicture, (int) $user['id']);
                if ($normalizedProfileImage === '') {
                    $errors['profilePicture'] = 'Profile picture must come from the HRMS profile upload endpoint.';
                } else {
                    $updates['img'] = $normalizedProfileImage;
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

    public function profile_password()
    {
        if (!in_array($this->input->method(TRUE), array('POST', 'PATCH'), true)) {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $payload = $this->json_input();
        $oldPassword = (string) ($payload['oldPassword'] ?? $payload['old_password'] ?? '');
        $newPassword = (string) ($payload['newPassword'] ?? $payload['new_password'] ?? '');
        $passwordConfirmation = (string) ($payload['passwordConfirmation'] ?? $payload['password_confirmation'] ?? '');

        $errors = array();
        if ($oldPassword === '') {
            $errors['oldPassword'] = 'Old password is required.';
        }
        if ($newPassword === '') {
            $errors['newPassword'] = 'New password is required.';
        }
        if ($passwordConfirmation === '') {
            $errors['passwordConfirmation'] = 'Password confirmation is required.';
        }
        if (!empty($errors)) {
            return $this->respond(422, array('message' => 'Validation failed.', 'errors' => $errors));
        }

        if (!$this->verify_user_password($user, $oldPassword)) {
            return $this->respond(422, array(
                'message' => 'Validation failed.',
                'errors' => array('oldPassword' => 'Old password is incorrect.'),
            ));
        }

        if ($newPassword !== $passwordConfirmation) {
            return $this->respond(422, array(
                'message' => 'Validation failed.',
                'errors' => array('passwordConfirmation' => 'Password confirmation does not match.'),
            ));
        }

        if ($this->verify_user_password($user, $newPassword)) {
            return $this->respond(422, array(
                'message' => 'Validation failed.',
                'errors' => array('newPassword' => 'New password must be different from the current password.'),
            ));
        }

        $this->db->update('user', array(
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ), array('id' => (int) $user['id']));

        return $this->respond(200, array('ok' => true));
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
        $offices = $this->Office_model->get_active_offices();
        if (empty($offices)) {
            return $this->respond(404, array('message' => 'Active office is not configured.'));
        }

        $baseline = $offices[0];
        $officeConfigs = array();
        foreach ($offices as $office) {
            $officeConfigs[] = $this->build_office_config($office, $baseline);
        }

        return $this->respond(200, array(
            'offices' => $officeConfigs,
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
        $offices = $this->Office_model->get_active_offices();
        if (empty($offices)) {
            return $this->respond(404, array('message' => 'Active office is not configured.'));
        }

        $payload = $this->json_input();
        $wifiProof = $payload['wifiProof'] ?? null;

        $hasWifiRules = false;
        $ok = false;
        foreach ($offices as $office) {
            if ($this->has_wifi_rules($office)) {
                $hasWifiRules = true;
                if ($this->wifi_proof_ok($office, $wifiProof)) {
                    $ok = true;
                    break;
                }
            }
        }
        if (!$hasWifiRules) {
            $ok = true;
        }
        return $this->respond(200, array('ok' => $ok));
    }

    public function attendance_check_in()
    {
        return $this->attendance_check('IN');
    }

    public function attendance_out_of_town_check_in()
    {
        return $this->attendance_out_of_town_check('IN');
    }

    public function attendance_out_of_town_check_out()
    {
        return $this->attendance_out_of_town_check('OUT');
    }

    public function attendance_status()
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

        $lat = $this->input->get('lat', TRUE);
        $lng = $this->input->get('lng', TRUE);
        $accuracy = $this->input->get('accuracy', TRUE);
        $wifiProof = $this->extract_wifi_proof_from_query();

        $errors = array();
        if (!is_numeric($lat) || $lat < -90 || $lat > 90) {
            $errors['lat'] = 'Latitude must be between -90 and 90.';
        }
        if (!is_numeric($lng) || $lng < -180 || $lng > 180) {
            $errors['lng'] = 'Longitude must be between -180 and 180.';
        }
        if (!is_numeric($accuracy) || $accuracy < 0) {
            $errors['accuracy'] = 'Accuracy must be a positive number.';
        }
        if (!empty($errors)) {
            return $this->respond(422, array('message' => 'Validation failed.', 'errors' => $errors));
        }

        $ipAddress = $this->input->ip_address();
        $result = $this->attendanceeligibilityservice->evaluate($lat, $lng, $accuracy, $ipAddress, null, $wifiProof);
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
        $computed = $result['computed'];
        $reasons = $computed['reasons'] ?? array();
        if ($hasWifiRules) {
            $computed['can_confirm'] = !empty($computed['can_confirm']) && $wifiOk;
            if ($wifiProof === null) {
                $reasons[] = 'WIFI_REQUIRED';
            } elseif (!$wifiOk) {
                $reasons[] = 'WIFI_NOT_ALLOWED';
            }
        }
        $computed['reasons'] = array_values(array_unique($reasons));

        $schedule = $this->resolve_attendance_times($officeRecord ?: $office, $user);

        return $this->respond(200, array(
            'office' => $result['office'],
            'user' => $result['user'],
            'computed' => $computed,
            'wifi' => array(
                'has_rules' => $hasWifiRules,
                'provided' => $wifiProof !== null,
                'ok' => $hasWifiRules ? $wifiOk : true,
            ),
            'schedule' => array(
                'start_time' => $schedule['start'],
                'end_time' => $schedule['end'],
                'late_threshold' => date('H:i', strtotime('2000-01-01 ' . $schedule['start'] . ':00') + 15 * 60),
                'early_threshold' => date('H:i', strtotime('2000-01-01 ' . $schedule['end'] . ':00') - 15 * 60),
                'source' => $schedule['source'],
                'is_special' => $schedule['source'] === 'user',
            ),
        ));
    }

    public function attendance_check_out()
    {
        return $this->attendance_check('OUT');
    }

    private function attendance_out_of_town_check($type)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->user_requires_attendance((int) $user['id'])) {
            return $this->respond(403, array('message' => 'Attendance is not required for this account.'));
        }

        $payload = $this->json_input();
        $attachmentPathInput = trim((string) ($payload['attachmentPath'] ?? $payload['photoPath'] ?? $payload['attachment_path'] ?? ''));
        $dinasLocation = trim((string) ($payload['dinasLocation'] ?? $payload['lokasiDinas'] ?? ''));
        $dinasNotes = trim((string) ($payload['notes'] ?? $payload['catatan'] ?? ''));

        $errors = array();
        $proof = $this->validate_out_of_town_proof_path($attachmentPathInput, (int) $user['id']);
        if (isset($proof['error'])) {
            $errors['attachmentPath'] = $proof['error'];
        }

        if (!empty($errors)) {
            return $this->respond(422, array('message' => 'Validation failed.', 'errors' => $errors));
        }

        if ($this->Attendance_log_model->has_recent_log($user['id'], $this->cooldownSeconds)) {
            return $this->respond(429, array('message' => 'Please wait before confirming again.'));
        }

        $ipAddress = $this->input->ip_address();
        $office = $this->Office_model->get_active_office();
        if (!$office) {
            return $this->respond(500, array('message' => 'Active office is not configured.'));
        }

        $now = date('Y-m-d H:i:s');
        $schedule = $this->resolve_attendance_times($office, $user);
        $notesMeta = array();
        if ($dinasLocation !== '') {
            $notesMeta['dinasLocation'] = $dinasLocation;
        }
        if ($dinasNotes !== '') {
            $notesMeta['dinasNotes'] = $dinasNotes;
        }

        $noteData = $this->build_attendance_notes($type, $now, $schedule['start'], $schedule['end'], $notesMeta);
        $flags = $noteData['flags'];
        $flags['special_schedule'] = $schedule['source'] === 'user';
        $insertData = array(
            'user_id' => (int) $user['id'],
            'office_id' => (int) $office['id'],
            'type' => $type,
            'lat' => null,
            'lng' => null,
            'accuracy' => null,
            'distance_m' => null,
            'method' => 'OUT_OF_TOWN+PHOTO',
            'ip_address' => $ipAddress,
            'user_agent' => $this->input->user_agent(),
            'notes' => $noteData['notes'],
            'special_schedule' => $flags['special_schedule'],
            'attendance_category' => 'OUT_OF_TOWN',
            'attachment_path' => $proof['path'],
            'created_at' => $now,
        );

        if (!$this->Attendance_log_model->insert($insertData)) {
            return $this->respond(500, array('message' => 'Failed to store attendance log.'));
        }

        $attendanceLogId = (int) $this->db->insert_id();

        return $this->respond(200, array(
            'ok' => true,
            'attendance_log_id' => $attendanceLogId,
            'type' => $type,
            'attendanceCategory' => 'OUT_OF_TOWN',
            'attendanceCategoryLabel' => $this->attendance_category_label('OUT_OF_TOWN'),
            'dinasLocation' => $dinasLocation !== '' ? $dinasLocation : null,
            'notesText' => $dinasNotes !== '' ? $dinasNotes : null,
            'attachmentPath' => $this->absolute_attachment_url($proof['path']),
            'distanceMeters' => null,
            'notes' => $noteData['notes'],
            'flags' => $flags,
            'minutes' => $noteData['minutes'],
            'schedule' => array(
                'start_time' => $schedule['start'],
                'end_time' => $schedule['end'],
                'source' => $schedule['source'],
            ),
            'office' => array(
                'id' => (int) $office['id'],
                'name' => $office['name'],
            ),
        ));
    }

    public function attendance_reason($id = null)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $attendanceLogId = (int) $id;
        if ($attendanceLogId <= 0) {
            return $this->respond(400, array('message' => 'Attendance log ID is required.'));
        }

        $log = $this->db->get_where('attendance_logs', array(
            'id' => $attendanceLogId,
            'user_id' => (int) $user['id'],
        ))->row_array();
        if (!$log) {
            return $this->respond(404, array('message' => 'Attendance log not found.'));
        }

        $context = $this->attendance_reason_context($log, $user);
        if (empty($context['flags']['late']) && empty($context['flags']['early_checkout'])) {
            return $this->respond(409, array('message' => 'Reason can only be submitted for late check-in or early checkout.'));
        }

        $payload = $this->json_input();
        $formPayload = $this->input->post(NULL, TRUE);
        if (is_array($formPayload) && !empty($formPayload)) {
            $payload = array_merge($formPayload, is_array($payload) ? $payload : array());
        }
        $reasonProvided = array_key_exists('reason', $payload);
        $reason = trim((string) ($payload['reason'] ?? ''));
        $attachmentPath = trim((string) ($payload['attachment_path'] ?? $payload['attachment'] ?? ''));
        $isOutOfTown = $this->is_out_of_town_attendance($log['attendance_category'] ?? null);

        if (!empty($_FILES['attachment']['name'])) {
            if ($isOutOfTown && trim((string) ($log['attachment_path'] ?? '')) !== '') {
                return $this->respond(422, array(
                    'message' => 'Out-of-town attendance proof photo cannot be replaced.',
                    'errors' => array('attachment' => 'Out-of-town attendance proof photo cannot be replaced.'),
                ));
            }
            $upload = $this->handle_attendance_attachment_upload($attendanceLogId, 'attachment');
            if (isset($upload['error'])) {
                return $this->respond(422, array(
                    'message' => 'Attachment upload failed.',
                    'errors' => array('attachment' => $upload['error']),
                ));
            }
            $attachmentPath = $upload['path'];
        } elseif ($attachmentPath === '') {
            $attachmentPath = $log['attachment_path'] ?? null;
        }

        if ($isOutOfTown && trim((string) ($log['attachment_path'] ?? '')) !== '' && $attachmentPath !== '') {
            $normalizedExisting = $this->normalize_attendance_upload_path($log['attachment_path']);
            $normalizedIncoming = $this->normalize_attendance_upload_path($attachmentPath);
            if ($normalizedExisting !== $normalizedIncoming) {
                return $this->respond(422, array(
                    'message' => 'Out-of-town attendance proof photo cannot be replaced.',
                    'errors' => array('attachment_path' => 'Out-of-town attendance proof photo cannot be replaced.'),
                ));
            }
        }

        $updateData = array(
            'attendance_reason' => $reasonProvided ? ($reason !== '' ? $reason : null) : ($log['attendance_reason'] ?? null),
            'attachment_path' => $attachmentPath !== '' ? $attachmentPath : null,
        );

        $this->db->where('id', $attendanceLogId)->update('attendance_logs', $updateData);

        return $this->respond(200, array(
            'ok' => true,
            'id' => $attendanceLogId,
            'type' => $log['type'],
            'reason' => $updateData['attendance_reason'],
            'attachmentPath' => $this->absolute_attachment_url($updateData['attachment_path']),
            'flags' => $context['flags'],
            'hasReasonOrAttachment' => $this->attendance_has_reason_or_attachment($updateData),
        ));
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

        $month = trim((string) $this->input->get('month', TRUE));
        $items = null;
        if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $items = $this->Attendance_log_model->get_by_user_month($user['id'], $month);
        } else {
            $office = $this->Office_model->get_active_office();
            if ($office && !empty($office['attendance_history_days'])) {
                $startDate = date('Y-m-d H:i:s', strtotime('-' . (int) $office['attendance_history_days'] . ' days'));
                $items = $this->Attendance_log_model->get_by_user_since($user['id'], $startDate);
            } else {
                $items = $this->Attendance_log_model->get_by_user($user['id']);
            }
        }

        foreach ($items as &$item) {
            $item = $this->map_attendance_history_item($item, $user);
        }
        unset($item);

        return $this->respond(200, array(
            'month' => $month !== '' ? $month : null,
            'data' => $items,
        ));
    }

    private function map_attendance_history_item($item, $user)
    {
        $context = $this->attendance_reason_context($item, $user);
        $notes = $this->decode_attendance_notes($item['notes'] ?? null);

        $item['notes'] = $notes;
        $item['flags'] = $context['flags'];
        $item['attendance_reason'] = isset($item['attendance_reason']) && trim((string) $item['attendance_reason']) !== ''
            ? trim((string) $item['attendance_reason'])
            : null;
        $item['attendanceCategory'] = $this->attendance_category_value($item['attendance_category'] ?? null);
        $item['attendanceCategoryLabel'] = $this->attendance_category_label($item['attendanceCategory']);
        $item['isOutOfTown'] = $this->is_out_of_town_attendance($item['attendanceCategory']);
        $item['dinasLocation'] = $this->attendance_note_meta_value($item['notes'] ?? null, 'dinasLocation');
        $item['notesText'] = $this->attendance_note_meta_value($item['notes'] ?? null, 'dinasNotes');
        $item['attachmentPath'] = $this->absolute_attachment_url($item['attachment_path'] ?? null);
        $item['hasReasonOrAttachment'] = $this->attendance_has_reason_or_attachment($item);
        $item['reasonEligible'] = !empty($context['flags']['late']) || !empty($context['flags']['early_checkout']);

        return $item;
    }

    private function attendance_has_reason_or_attachment($item)
    {
        $reason = trim((string) ($item['attendance_reason'] ?? ''));
        $attachmentPath = trim((string) ($item['attachment_path'] ?? ''));
        return $reason !== '' || $attachmentPath !== '';
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
        $report = $this->build_monthly_report((int) $user['id'], $month, $office, $user);
        if (isset($report['error'])) {
            return $this->respond(400, array('message' => $report['error']));
        }

        return $this->respond(200, $report['summary']);
    }

    public function attendance_dashboard()
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
        $report = $this->build_monthly_report((int) $user['id'], $month, $office, $user);
        if (isset($report['error'])) {
            return $this->respond(400, array('message' => $report['error']));
        }

        return $this->respond(200, array(
            'month' => $report['summary']['month'],
            'statistics' => $report['summary']['dashboard_statistics'],
        ));
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

        if (!$this->require_module_permission($user['id'], 'attendance_report', 'edit')) {
            return null;
        }

        $month = $this->input->get('month', TRUE);
        $month = $month ?: date('Y-m');
        $office = $this->Office_model->get_active_office();

        $users = $this->get_attendance_users();
        $items = array();
        foreach ($users as $member) {
            $report = $this->build_monthly_report((int) $member['id'], $month, $office, $member);
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

        $report = $this->build_monthly_report((int) $user['id'], $month, $office, $user);
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

    public function upload()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $type = trim((string) $this->input->post('type', TRUE));
        if ($type === '') {
            $type = trim((string) $this->input->get('type', TRUE));
        }

        $allowedTypes = array('attendance', 'leave', 'profile');
        if ($type === '') {
            return $this->respond(400, array('message' => 'type is required.'));
        }
        if (!in_array($type, $allowedTypes, true)) {
            return $this->respond(422, array('message' => 'type is invalid.'));
        }

        if (empty($_FILES['file']['name'])) {
            return $this->respond(400, array('message' => 'file is required.'));
        }

        $scope = $type === 'leave' ? 'leave' : ($type === 'attendance' ? 'attendance' : 'user_avatar');
        $options = array();
        if ($scope === 'leave' || $scope === 'attendance') {
            $options['subdir'] = 'api-upload';
        } elseif ($scope === 'user_avatar') {
            $options['file_name'] = (string) ((int) $user['id']);
        }

        $upload = $this->uploadservice->upload($scope, 'file', $options);
        if (!empty($upload['error'])) {
            return $this->respond(422, array('message' => $upload['error']));
        }

        return $this->respond(200, array(
            'type' => $type,
            'path' => $upload['public_path'],
            'storedPath' => $upload['path'],
            'url' => $upload['url'],
            'filename' => $upload['filename'],
            'value' => $type === 'profile' ? $upload['filename'] : $upload['path'],
            'originalName' => $upload['original_name'],
            'sizeBytes' => $upload['size_bytes'],
        ));
    }

    public function uploaded_file($scope = null)
    {
        $allowedScopes = array('leaves', 'overtime', 'attendance');
        if (!in_array($scope, $allowedScopes, true)) {
            show_404();
            return;
        }

        $segments = array_values($this->uri->segment_array());
        $scopeIndex = array_search($scope, $segments, true);
        if ($scopeIndex === false) {
            show_404();
            return;
        }

        $parts = array_slice($segments, $scopeIndex + 1);
        if (empty($parts)) {
            show_404();
            return;
        }

        foreach ($parts as $part) {
            $part = urldecode((string) $part);
            if ($part === '' || $part !== basename($part)) {
                show_404();
                return;
            }
        }

        $relativePath = 'writable/uploads/' . $scope . '/' . implode('/', $parts);
        $fullPath = project_storage_path($relativePath);

        if (!is_file($fullPath) || !is_readable($fullPath)) {
            show_404();
            return;
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type($this->detect_uploaded_file_mime_type($fullPath))
            ->set_header('Content-Length: ' . filesize($fullPath))
            ->set_header('Content-Disposition: inline; filename="' . basename($fullPath) . '"')
            ->set_output(file_get_contents($fullPath));
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
                    'attachmentPath' => $this->absolute_attachment_url($request['attachment_path']),
                );
            }

            return $this->respond(200, array('data' => $items));
        }

        if ($method !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $input = $this->json_input();
        $submitNowRaw = $input['submit_now'] ?? $input['submitNow'] ?? null;
        $submitNow = true;
        if ($submitNowRaw !== null) {
            if (is_bool($submitNowRaw)) {
                $submitNow = $submitNowRaw;
            } else {
                $submitNow = !in_array((string) $submitNowRaw, array('0', 'false', 'FALSE'), true);
            }
        }

        list($errors, $clean, $leaveType) = $this->validate_leave_request($input, $user['id']);
        if (!empty($errors)) {
            return $this->respond(422, array('message' => 'Validation failed.', 'errors' => $errors));
        }

        $requestNo = $this->requestnogenerator->generate_unique();
        $attachmentPath = $clean['attachment_path'] !== '' ? $clean['attachment_path'] : null;

        if (!empty($_FILES['attachment']['name'])) {
            $upload = $this->handle_attachment_upload($requestNo, 'attachment');
            if (isset($upload['error'])) {
                return $this->respond(422, array('message' => $upload['error']));
            }
            $attachmentPath = $upload['path'];
        }

        $now = date('Y-m-d H:i:s');
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
            'status' => $submitNow ? 'SUBMITTED' : 'DRAFT',
            'current_step' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $this->db->trans_complete();

        $workflow = null;
        if ($submitNow && $requestId) {
            $workflow = $this->approvalworkflowengine->initializeWorkflow($requestId);
        }

        $request = $this->LeaveRequestModel->get_by_id((int) $requestId);
        $statusRaw = $request['status'] ?? ($submitNow ? 'SUBMITTED' : 'DRAFT');

        $payload = array(
            'id' => (int) $requestId,
            'requestNo' => $requestNo,
            'status' => $this->map_leave_status($statusRaw),
            'statusRaw' => $statusRaw,
        );

        if ($workflow) {
            $payload['workflow'] = $workflow;
        }

        return $this->respond(201, $payload);
    }

    public function leave_detail($id)
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

        $request = $this->LeaveRequestModel->get_by_id((int) $id);
        if (!$request) {
            return $this->respond(404, array('message' => 'Leave request not found.'));
        }

        if ((int) $request['user_id'] !== (int) $user['id']) {
            return $this->respond(403, array('message' => 'You do not have access to this leave request.'));
        }

        $approvalSteps = $this->ApprovalStepModel->get_by_leave_request((int) $id);
        $approvalsPayload = array();
        foreach ($approvalSteps as $step) {
            $approvalsPayload[] = array(
                'id' => (int) $step['id'],
                'leaveRequestId' => (int) $step['leave_request_id'],
                'stepNo' => (int) $step['step_no'],
                'approverId' => (int) $step['assigned_approver_id'],
                'approverName' => $step['assigned_approver_name'] ?? null,
                'approverRole' => $step['assigned_approver_role'] ?? null,
                'action' => $step['action'],
                'actionAt' => $step['action_at'],
                'notes' => $step['notes'],
                'actualApproverId' => isset($step['actual_approver_id']) ? (int) $step['actual_approver_id'] : null,
                'actualApproverName' => $step['actual_approver_name'] ?? null,
            );
        }

        $requester = $this->db->select('id, full_name, email')
            ->get_where('user', array('id' => (int) $request['user_id']))
            ->row_array();

        return $this->respond(200, array(
            'id' => (int) $request['id'],
            'requestNo' => $request['request_no'],
            'requester' => array(
                'id' => (int) ($requester['id'] ?? 0),
                'name' => $requester['full_name'] ?? null,
                'email' => $requester['email'] ?? null,
            ),
            'leaveTypeId' => (int) $request['leave_type_id'],
            'leaveTypeName' => $request['leave_type_name'] ?? null,
            'leaveTypeCode' => $request['leave_type_code'] ?? null,
            'requiresAttachment' => (int) ($request['requires_attachment'] ?? 0),
            'maxDaysPerRequest' => $request['max_days_per_request'] !== null ? (int) $request['max_days_per_request'] : null,
            'startDate' => $request['start_date'],
            'endDate' => $request['end_date'],
            'daysCount' => (int) $request['days_count'],
            'reason' => $request['reason'],
            'status' => $this->map_leave_status($request['status']),
            'statusRaw' => $request['status'],
            'currentStep' => (int) $request['current_step'],
            'attachmentPath' => $this->absolute_attachment_url($request['attachment_path']),
            'createdAt' => $request['created_at'],
            'updatedAt' => $request['updated_at'],
            'approvals' => $approvalsPayload,
        ));
    }

    public function leave_submit($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->user_requires_attendance((int) $user['id'])) {
            return $this->respond(403, array('message' => 'Attendance is not required for this account.'));
        }

        $request = $this->LeaveRequestModel->get_by_id((int) $id);
        if (!$request || (int) $request['user_id'] !== (int) $user['id']) {
            return $this->respond(404, array('message' => 'Leave request not found.'));
        }

        if (!in_array($request['status'], array('DRAFT', 'NEEDS_ROUTE'), true)) {
            return $this->respond(409, array('message' => 'This request cannot be submitted.'));
        }

        $quotaCheck = $this->leavequotaservice->validateQuota(
            (int) $user['id'],
            (int) $request['leave_type_id'],
            (float) $request['days_count'],
            (int) $request['id']
        );

        if (!$quotaCheck['valid']) {
            return $this->respond(409, array(
                'message' => 'Kuota cuti tidak mencukupi: ' . ($quotaCheck['message'] ?? 'Insufficient quota.'),
                'quota' => $quotaCheck,
            ));
        }

        $result = $this->approvalworkflowengine->initializeWorkflow((int) $request['id']);
        if (!$result['success']) {
            return $this->respond(400, array(
                'message' => 'Failed to submit leave request: ' . ($result['message'] ?? 'Unknown error.'),
                'workflow' => $result,
            ));
        }

        $request = $this->LeaveRequestModel->get_by_id((int) $request['id']);
        $statusRaw = $request['status'] ?? 'SUBMITTED';

        return $this->respond(200, array(
            'message' => $result['message'] ?? 'Leave request submitted.',
            'id' => (int) $request['id'],
            'status' => $this->map_leave_status($statusRaw),
            'statusRaw' => $statusRaw,
            'workflow' => $result,
        ));
    }

    public function leave_cancel($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->user_requires_attendance((int) $user['id'])) {
            return $this->respond(403, array('message' => 'Attendance is not required for this account.'));
        }

        $request = $this->LeaveRequestModel->get_by_id((int) $id);
        if (!$request || (int) $request['user_id'] !== (int) $user['id']) {
            return $this->respond(404, array('message' => 'Leave request not found.'));
        }

        $cancellableStatuses = array('DRAFT', 'SUBMITTED', 'PENDING_APPROVAL', 'IN_REVIEW', 'NEEDS_ROUTE', 'APPROVED');
        if (!in_array($request['status'], $cancellableStatuses, true)) {
            return $this->respond(409, array('message' => 'This request cannot be cancelled.'));
        }

        $wasApproved = $request['status'] === 'APPROVED';

        $result = $this->approvalworkflowengine->cancelWorkflow((int) $request['id'], (int) $user['id']);
        if (!$result['success']) {
            $now = date('Y-m-d H:i:s');
            $this->db->trans_start();

            $this->LeaveRequestModel->update($request['id'], array(
                'status' => 'CANCELLED',
                'updated_at' => $now,
            ));

            $this->db->where('leave_request_id', (int) $request['id']);
            $this->db->where('action', 'PENDING');
            $this->db->update('approval_steps', array(
                'action' => 'REJECTED',
                'action_at' => $now,
                'notes' => 'Cancelled by requester.',
            ));

            $this->db->trans_complete();
        }

        if ($wasApproved) {
            $this->leavequotaservice->restoreQuota(
                (int) $request['user_id'],
                (int) $request['leave_type_id'],
                (int) $request['days_count'],
                (int) $request['id'],
                (int) $user['id'],
                'Pengajuan dibatalkan oleh pemohon'
            );

            $this->LeaveLedgerModel->delete_by_leave_request((int) $request['id']);
        }

        $message = $wasApproved ? 'Pengajuan cuti dibatalkan dan kuota dikembalikan.' : 'Pengajuan cuti dibatalkan.';

        return $this->respond(200, array(
            'message' => $message,
            'id' => (int) $request['id'],
            'status' => 'CANCELLED',
        ));
    }

    public function leave_progress($id)
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

        $request = $this->LeaveRequestModel->get_by_id((int) $id);
        if (!$request || (int) $request['user_id'] !== (int) $user['id']) {
            return $this->respond(404, array('message' => 'Leave request not found.'));
        }

        $progress = $this->approvalworkflowengine->getWorkflowProgress((int) $id);
        if (!$progress) {
            return $this->respond(404, array('message' => 'Approval workflow not found.'));
        }

        $stepsPayload = array();
        foreach ($progress['steps'] as $step) {
            $stepsPayload[] = array(
                'id' => (int) $step['id'],
                'stepNo' => (int) $step['step_no'],
                'stepName' => $step['step_name'] ?? null,
                'assignedApproverId' => isset($step['assigned_approver_id']) ? (int) $step['assigned_approver_id'] : null,
                'assignedApproverName' => $step['assigned_approver_name'] ?? null,
                'assignedApproverRole' => $step['assigned_approver_role'] ?? null,
                'actualApproverId' => isset($step['actual_approver_id']) ? (int) $step['actual_approver_id'] : null,
                'actualApproverName' => $step['actual_approver_name'] ?? null,
                'action' => $step['action'],
                'actionAt' => $step['action_at'],
                'notes' => $step['notes'],
                'version' => isset($step['version']) ? (int) $step['version'] : null,
            );
        }

        return $this->respond(200, array(
            'requestId' => (int) $request['id'],
            'status' => $this->map_leave_status($request['status']),
            'statusRaw' => $request['status'],
            'instance' => $progress['instance'],
            'currentStep' => (int) $progress['current_step'],
            'totalSteps' => (int) $progress['total_steps'],
            'workflowStatus' => $progress['status'],
            'steps' => $stepsPayload,
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

        // Get user AND JWT payload with role_id
        $auth_data = $this->require_user_with_payload();
        if (!$auth_data) {
            return null;
        }

        $user = $auth_data['user'];
        $jwt_payload = $auth_data['payload'];

        $period_year = $this->input->get('period_year');
        if (!$period_year) {
            return $this->respond(400, array('message' => 'period_year is required.'));
        }

        // Use role_id from JWT directly - no need to query user_roles again
        $role_id = isset($jwt_payload['role_id']) ? (int) $jwt_payload['role_id'] : null;
        $role_name = $jwt_payload['role'] ?? null;

        $template = $this->Performance_model->get_active_template_for_employee($period_year, $role_id);
        if (!$template) {
            return $this->respond(404, array('message' => 'No active template found for this period and role.'));
        }

        $template['employee_role_id'] = $role_id;
        $template['employee_role_name'] = $role_name;

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
        $employee_role = $this->Performance_model->get_employee_primary_role($user['id']);
        $input['employee_id'] = $user['id'];
        $input['employee_snapshot'] = array(
            'name' => $userRow['full_name'] ?? null,
            'nik' => $userRow['nik'] ?? null,
            'department' => $userRow['department'] ?? null,
            'position' => $userRow['position'] ?? null,
            'role_id' => $employee_role ? $employee_role['id'] : null,
            'role_name' => $employee_role ? $employee_role['display_name'] : null,
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

    public function performance_submission_cancel($id = null)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$id) {
            return $this->respond(400, array('message' => 'Submission ID is required.'));
        }

        $submission = $this->Performance_model->get_submission_by_id($id, false);
        if (!$submission || (int) $submission['employee_id'] !== (int) $user['id']) {
            return $this->respond(404, array('message' => 'Submission not found.'));
        }

        $cancellableStatuses = array('SUBMITTED', 'DRAFT');
        if (!in_array($submission['status'], $cancellableStatuses, true)) {
            return $this->respond(409, array('message' => 'This submission cannot be cancelled.'));
        }

        $now = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $submission['id']);
        $this->db->update('performance_submissions', array(
            'status' => 'CANCELLED',
            'updated_at' => $now,
        ));

        return $this->respond(200, array(
            'message' => 'Submission cancelled.',
            'id' => (int) $submission['id'],
            'status' => 'CANCELLED',
        ));
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
        $result = $this->attendanceeligibilityservice->evaluate($lat, $lng, $accuracy, $ipAddress, null, $wifiProof);
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
        $now = date('Y-m-d H:i:s');
        $schedule = $this->resolve_attendance_times($officeRecord ?: $office, $user);
        $noteData = $this->build_attendance_notes($type, $now, $schedule['start'], $schedule['end']);
        $flags = $noteData['flags'];
        $flags['special_schedule'] = $schedule['source'] === 'user';
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
            'notes' => $noteData['notes'],
            'special_schedule' => $flags['special_schedule'],
            'attendance_category' => 'REGULAR',
            'created_at' => $now,
        );

        if (!$this->Attendance_log_model->insert($insertData)) {
            return $this->respond(500, array('message' => 'Failed to store attendance log.'));
        }

        $attendanceLogId = (int) $this->db->insert_id();

        return $this->respond(200, array(
            'ok' => true,
            'attendance_log_id' => $attendanceLogId,
            'type' => $type,
            'attendanceCategory' => 'REGULAR',
            'attendanceCategoryLabel' => $this->attendance_category_label('REGULAR'),
            'distanceMeters' => round($distance, 2),
            'notes' => $noteData['notes'],
            'flags' => $flags,
            'minutes' => $noteData['minutes'],
            'schedule' => array(
                'start_time' => $schedule['start'],
                'end_time' => $schedule['end'],
                'source' => $schedule['source'],
            ),
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
        $clean['attachment_path'] = trim((string) ($input['attachment_path'] ?? $input['attachment'] ?? ''));
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
            $errors['start_date'] = 'A leave application already exists for the selected date range.';
        }

        if ($this->leave_type_requires_attachment($leaveType)) {
            if (empty($_FILES['attachment']['name']) && $clean['attachment_path'] === '') {
                $errors['attachment'] = 'Attachment is required for this leave type.';
            }
        }

        return array($errors, $clean, $leaveType);
    }

    private function leave_type_requires_attachment($leaveType)
    {
        return is_array($leaveType) && (int) ($leaveType['requires_attachment'] ?? 0) === 1;
    }

    private function handle_attachment_upload($requestNo, $fieldName)
    {
        return $this->uploadservice->upload('leave', $fieldName, array('subdir' => $requestNo));
    }

    private function handle_attendance_attachment_upload($attendanceLogId, $fieldName)
    {
        return $this->uploadservice->upload('attendance', $fieldName, array('subdir' => (string) ((int) $attendanceLogId)));
    }

    private function handle_hrms_upload($uploadDir, $relativeBase, $fieldName)
    {
        $scope = strpos((string) $relativeBase, 'writable/uploads/leaves/') === 0 ? 'leave' : 'hrms_profile';
        $options = array();
        if ($scope === 'leave') {
            $trimmedBase = trim((string) $relativeBase, '/');
            $options['subdir'] = trim(substr($trimmedBase, strlen('writable/uploads/leaves')), '/');
            if ($options['subdir'] === '') {
                $options['subdir'] = 'api-upload';
            }
        }

        return $this->uploadservice->upload($scope, $fieldName, $options);
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
        if (!is_array($payload)) {
            $payload = array('message' => (string) $payload);
        }

        if ($statusCode >= 400) {
            $message = isset($payload['message']) ? (string) $payload['message'] : 'Request failed.';
            $normalized = array(
                'status' => 'error',
                'message' => $message,
                'code' => isset($payload['code']) ? (string) $payload['code'] : $this->default_error_code($statusCode, $message),
            );

            if (isset($payload['errors'])) {
                $normalized['errors'] = $payload['errors'];
            }

            $payload = array_merge($payload, $normalized);
        }

        return $this->output
            ->set_status_header($statusCode)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function default_error_code($statusCode, $message)
    {
        switch ($statusCode) {
            case 400:
                return 'BAD_REQUEST';
            case 401:
                return 'UNAUTHORIZED';
            case 403:
                return 'FORBIDDEN';
            case 404:
                return 'NOT_FOUND';
            case 405:
                return 'METHOD_NOT_ALLOWED';
            case 409:
                return 'CONFLICT';
            case 422:
                return 'VALIDATION_FAILED';
            case 429:
                return 'TOO_MANY_REQUESTS';
            case 500:
                return 'INTERNAL_SERVER_ERROR';
            default:
                return 'ERROR';
        }
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

    private function require_module_permission($userId, $moduleName, $action = 'view')
    {
        if ($this->permission->check_permission((int) $userId, $moduleName, $action)) {
            return true;
        }

        $this->respond(403, array('message' => 'Not authorized.'));
        return false;
    }

    /**
     * Get user AND JWT payload with role_id (for performance API)
     */
    private function require_user_with_payload()
    {
        $header = $this->input->get_request_header('Authorization', true);
        if (!$header || stripos($header, 'Bearer ') !== 0) {
            $this->respond(401, array('message' => 'Unauthorized'));
            return null;
        }

        $token = trim(substr($header, 7));
        $payload = $this->apiauth->decode_jwt_payload($token);

        if (!$payload || empty($payload['sub'])) {
            $this->respond(401, array('message' => 'Unauthorized'));
            return null;
        }

        $user = $this->db->get_where('user', array('id' => (int) $payload['sub']))->row_array();
        if (!$user) {
            $this->respond(401, array('message' => 'Unauthorized'));
            return null;
        }

        return array(
            'user' => $user,
            'payload' => $payload
        );
    }

    private function user_response($user)
    {
        $employeeRole = $this->Performance_model->get_employee_primary_role($user['id']);
        $position = $this->db
            ->select('up.position_id, p.name as position_name')
            ->from('user_profile up')
            ->join('positions p', 'p.id = up.position_id', 'left')
            ->where('up.user_id', (int) $user['id'])
            ->limit(1)
            ->get()
            ->row_array();
        $office = $this->Office_model->get_active_office();
        $schedule = $this->resolve_attendance_times($office, $user);

        return array(
            'id' => (int) $user['id'],
            'name' => $user['full_name'] ?? null,
            'email' => $user['email'] ?? null,
            'role' => $user['role_text'] ?? ($user['role'] ?? null),
            'role_id' => $employeeRole ? (int) $employeeRole['id'] : null,
            'role_name' => $employeeRole['display_name'] ?? null,
            'roleInfo' => array(
                'id' => $employeeRole ? (int) $employeeRole['id'] : null,
                'name' => $employeeRole['display_name'] ?? ($user['role_text'] ?? ($user['role'] ?? null)),
            ),
            'profilePicture' => $this->profile_picture_url($user),
            'keterangan' => $user['desc'] ?? null,
            'position_id' => isset($position['position_id']) ? (int) $position['position_id'] : null,
            'position_name' => $position['position_name'] ?? null,
            'schedule' => array(
                'start_time' => $schedule['start'],
                'end_time' => $schedule['end'],
                'source' => $schedule['source'],
                'special_schedule' => $schedule['source'] === 'user',
            ),
        );
    }

    private function map_leave_status($status)
    {
        switch ($status) {
            case 'DRAFT':
                return 'Draft';
            case 'SUBMITTED':
            case 'PENDING_APPROVAL':
            case 'IN_REVIEW':
                return 'Pending';
            case 'NEEDS_ROUTE':
                return 'Needs Route';
            case 'APPROVED':
                return 'Approved';
            case 'REJECTED':
                return 'Rejected';
            case 'CANCELLED':
                return 'Cancelled';
            default:
                return $status;
        }
    }

    private function normalize_attendance_upload_path($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        if (preg_match('/^https?:\\/\\//i', $path)) {
            $parsedPath = parse_url($path, PHP_URL_PATH);
            if (is_string($parsedPath) && $parsedPath !== '') {
                $path = ltrim($parsedPath, '/');
            }
        } else {
            $path = ltrim($path, '/');
        }

        $path = preg_replace('#/+#', '/', $path);
        $markers = array(
            'writable/uploads/attendance/',
            'api/hrms/files/attendance/',
        );
        foreach ($markers as $marker) {
            $position = strpos($path, $marker);
            if ($position === false) {
                continue;
            }

            $suffix = ltrim(substr($path, $position + strlen($marker)), '/');
            if ($suffix === '' || strpos($suffix, '..') !== false) {
                return '';
            }

            return 'writable/uploads/attendance/' . $suffix;
        }

        return '';
    }

    private function validate_out_of_town_proof_path($path, $userId)
    {
        $normalizedPath = $this->normalize_attendance_upload_path($path);
        if ($normalizedPath === '') {
            return array('error' => 'Attendance photo is required and must come from the attendance upload endpoint.');
        }

        $extension = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
        if (!in_array($extension, array('jpg', 'jpeg', 'png'), true)) {
            return array('error' => 'Attendance photo must be JPG, JPEG, or PNG.');
        }

        $fullPath = project_storage_path($normalizedPath);
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            return array('error' => 'Attendance photo upload could not be found.');
        }

        $existing = $this->db
            ->select('id')
            ->from('attendance_logs')
            ->where('user_id', (int) $userId)
            ->where('attachment_path', $normalizedPath)
            ->limit(1)
            ->get()
            ->row_array();
        if ($existing) {
            return array('error' => 'A new photo is required for each attendance action.');
        }

        return array('path' => $normalizedPath);
    }

    private function absolute_attachment_url($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (preg_match('/^https?:\\/\\//i', $path)) {
            return $path;
        }

        return project_uploaded_file_url($path);
    }

    private function profile_picture_url($user)
    {
        $image = trim((string) ($user['img'] ?? ''));
        if ($image === '') {
            return null;
        }

        if (preg_match('/^https?:\\/\\//i', $image)) {
            return $image;
        }

        $image = str_replace('\\', '/', $image);
        if (strpos($image, '/') !== false) {
            return base_url(ltrim($image, '/'));
        }

        return base_url('assets/img/user/' . $image);
    }

    private function normalize_profile_image_input($value, $userId)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^https?:\\/\\//i', $value)) {
            $parsedPath = parse_url($value, PHP_URL_PATH);
            if (is_string($parsedPath) && $parsedPath !== '') {
                $value = ltrim($parsedPath, '/');
            }
        } else {
            $value = ltrim(str_replace('\\', '/', $value), '/');
        }

        $value = preg_replace('#/+#', '/', $value);
        if ($value === '') {
            return '';
        }

        if ($value === basename($value)) {
            if (preg_match('/^' . preg_quote((string) $userId, '/') . '\.(jpg|jpeg|png)$/i', $value)) {
                return $value;
            }

            return '';
        }

        $markers = array(
            'assets/img/user/',
        );
        foreach ($markers as $marker) {
            $position = strpos($value, $marker);
            if ($position === false) {
                continue;
            }

            $suffix = ltrim(substr($value, $position + strlen($marker)), '/');
            if ($suffix === '' || strpos($suffix, '/') !== false || strpos($suffix, '..') !== false) {
                return '';
            }

            if (!preg_match('/^' . preg_quote((string) $userId, '/') . '\.(jpg|jpeg|png)$/i', $suffix)) {
                return '';
            }

            return $suffix;
        }

        return '';
    }

    private function verify_user_password($user, $password)
    {
        $password = (string) $password;
        if ($password === '') {
            return false;
        }

        $currentHash = (string) ($user['password'] ?? '');
        if ($currentHash === '') {
            return false;
        }

        if (password_verify($password, $currentHash)) {
            return true;
        }

        return $currentHash === md5($password);
    }

    private function detect_uploaded_file_mime_type($fullPath)
    {
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($fullPath);
            if (is_string($mimeType) && $mimeType !== '') {
                return $mimeType;
            }
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'pdf':
                return 'application/pdf';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            default:
                return 'application/octet-stream';
        }
    }

    private function public_uploaded_file_url($path)
    {
        return project_uploaded_file_url($path);
    }

    private function public_uploaded_file_path($path)
    {
        return project_uploaded_file_path($path);
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

    private function build_office_config($office, $baseline = null)
    {
        if ($baseline === null) {
            $baseline = $office;
        }

        $allowedCidrs = $this->parse_allowed_cidrs($office['allowed_ip_cidrs'] ?? '');
        $allowedBssids = $this->parse_allowed_bssids($office['allowed_bssids'] ?? '');
        $allowedSsids = $this->parse_allowed_ssids($office['allowed_ssids'] ?? '');
        $baselineRadius = isset($baseline['radius_m']) ? (int) $baseline['radius_m'] : null;
        $baselineMinAccuracy = isset($baseline['min_accuracy_m']) ? (int) $baseline['min_accuracy_m'] : null;

        $attendanceOverrides = array(
            'requires_ip' => !empty($allowedCidrs),
        );

        $radius = isset($office['radius_m']) ? (int) $office['radius_m'] : null;
        if ($radius === null || $radius <= 0) {
            $radius = $baselineRadius !== null ? $baselineRadius : 0;
        }
        $attendanceOverrides['radius_m'] = $radius;

        $minAccuracy = isset($office['min_accuracy_m']) ? (int) $office['min_accuracy_m'] : null;
        if ($minAccuracy === null || $minAccuracy <= 0) {
            $minAccuracy = $baselineMinAccuracy !== null ? $baselineMinAccuracy : 0;
        }
        $attendanceOverrides['min_accuracy_m'] = $minAccuracy;

        $result = array(
            'id' => (int) $office['id'],
            'name' => $office['name'],
            'location' => array(
                'lat' => (float) $office['lat'],
                'lng' => (float) $office['lng'],
            ),
            'overrides' => array(
                'attendance' => $attendanceOverrides,
            ),
        );

        if (!empty($allowedCidrs)) {
            $result['ip'] = array(
                'allowed_cidrs' => $allowedCidrs,
            );
        }

        if (!empty($allowedBssids) || !empty($allowedSsids)) {
            $wifi = array();
            if (!empty($allowedBssids)) {
                $wifi['allowed_bssids'] = $allowedBssids;
            }
            if (!empty($allowedSsids)) {
                $wifi['allowed_ssids'] = $allowedSsids;
            }
            $result['overrides']['wifi'] = $wifi;
        }

        return $result;
    }

    private function parse_allowed_cidrs($text)
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

    private function extract_wifi_proof_from_query()
    {
        $wifiProofRaw = $this->input->get('wifi_proof', FALSE);
        if ($wifiProofRaw !== null && trim((string) $wifiProofRaw) !== '') {
            $decoded = json_decode((string) $wifiProofRaw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalize_wifi_proof($decoded);
            }
            return $this->normalize_wifi_proof($wifiProofRaw);
        }

        $proof = array();
        $bssid = $this->input->get('bssid', FALSE);
        $ssid = $this->input->get('ssid', FALSE);
        $bssids = $this->input->get('bssids', FALSE);
        $ssids = $this->input->get('ssids', FALSE);

        if ($bssid !== null && trim((string) $bssid) !== '') {
            $proof['bssid'] = $bssid;
        }
        if ($ssid !== null && trim((string) $ssid) !== '') {
            $proof['ssid'] = $ssid;
        }
        if ($bssids !== null && trim((string) $bssids) !== '') {
            $proof['bssids'] = $this->parse_csv_list($bssids);
        }
        if ($ssids !== null && trim((string) $ssids) !== '') {
            $proof['ssids'] = $this->parse_csv_list($ssids);
        }

        return empty($proof) ? null : $this->normalize_wifi_proof($proof);
    }

    private function normalize_wifi_proof($wifiProof)
    {
        if ($wifiProof === null) {
            return null;
        }

        if (is_string($wifiProof)) {
            $wifiProof = trim($wifiProof);
            return $wifiProof === '' ? null : $wifiProof;
        }

        if (!is_array($wifiProof)) {
            return null;
        }

        $normalized = array();
        if (isset($wifiProof['bssid']) && trim((string) $wifiProof['bssid']) !== '') {
            $normalized['bssid'] = trim((string) $wifiProof['bssid']);
        }
        if (isset($wifiProof['ssid']) && trim((string) $wifiProof['ssid']) !== '') {
            $normalized['ssid'] = trim((string) $wifiProof['ssid']);
        }
        if (isset($wifiProof['bssids']) && is_array($wifiProof['bssids'])) {
            $values = array();
            foreach ($wifiProof['bssids'] as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $values[] = $value;
                }
            }
            if (!empty($values)) {
                $normalized['bssids'] = $values;
            }
        }
        if (isset($wifiProof['ssids']) && is_array($wifiProof['ssids'])) {
            $values = array();
            foreach ($wifiProof['ssids'] as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $values[] = $value;
                }
            }
            if (!empty($values)) {
                $normalized['ssids'] = $values;
            }
        }

        return empty($normalized) ? null : $normalized;
    }

    private function parse_csv_list($text)
    {
        if (is_array($text)) {
            $items = $text;
        } else {
            $items = preg_split('/\r\n|\r|\n|,/', (string) $text);
        }
        $result = array();
        foreach ($items as $item) {
            $item = trim($item);
            if ($item !== '') {
                $result[] = $item;
            }
        }
        return $result;
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

    private function build_monthly_report($userId, $month, $office, $user = null)
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

        $times = $this->resolve_attendance_times($office, $user);
        $startTime = $times['start'];
        $endTime = $times['end'];

        $weekendType = $this->AttendanceSettingsModel->get_settings()['weekend_type'] ?? 'SATURDAY_SUNDAY';

        $presentDays = 0;
        $onTimeCount = 0;
        $lateCount = 0;
        $earlyCheckoutCount = 0;
        $absentCount = 0;
        $leaveCount = 0;
        $notAbsentOutCount = 0;
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
                if ($hasIn && !$hasOut) {
                    $notAbsentOutCount++;
                }
                if ($hasIn && $hasOut && !$isLate && !$isEarlyCheckout) {
                    $onTimeCount++;
                }
            } else {
                $absentCount++;
            }

            $notes = $this->build_daily_notes($firstIn, $lastOut, $startTime, $endTime, $isLate, $isEarlyCheckout, $dayLogs);
            $firstInCategory = $this->attendance_category_value($dayLogs['first_in_category'] ?? null);
            $lastOutCategory = $this->attendance_category_value($dayLogs['last_out_category'] ?? null);
            $firstInOutOfTown = $this->is_out_of_town_attendance($firstInCategory);
            $lastOutOutOfTown = $this->is_out_of_town_attendance($lastOutCategory);

            $daily[] = array(
                'date' => $day,
                'status' => $status,
                'first_in' => $firstIn,
                'last_out' => $lastOut,
                'first_in_category' => $firstInCategory,
                'last_out_category' => $lastOutCategory,
                'first_in_category_label' => $this->attendance_category_label($firstInCategory),
                'last_out_category_label' => $this->attendance_category_label($lastOutCategory),
                'first_in_is_out_of_town' => $firstInOutOfTown,
                'last_out_is_out_of_town' => $lastOutOutOfTown,
                'late' => $isLate,
                'early_checkout' => $isEarlyCheckout,
                'late_reason' => $isLate ? ($dayLogs['first_in_reason'] ?? null) : null,
                'late_attachment_path' => ($isLate && !$firstInOutOfTown) ? $this->absolute_attachment_url($dayLogs['first_in_attachment_path'] ?? null) : null,
                'early_checkout_reason' => $isEarlyCheckout ? ($dayLogs['last_out_reason'] ?? null) : null,
                'early_checkout_attachment_path' => ($isEarlyCheckout && !$lastOutOutOfTown) ? $this->absolute_attachment_url($dayLogs['last_out_attachment_path'] ?? null) : null,
                'first_in_proof_path' => $firstInOutOfTown ? $this->absolute_attachment_url($dayLogs['first_in_attachment_path'] ?? null) : null,
                'last_out_proof_path' => $lastOutOutOfTown ? $this->absolute_attachment_url($dayLogs['last_out_attachment_path'] ?? null) : null,
                'out_of_town' => $firstInOutOfTown || $lastOutOutOfTown,
                'holiday_name' => $holidayName,
                'notes' => $notes,
            );
        }

        $summary = array(
            'month' => $month,
            'present_days' => $presentDays,
            'late_count' => $lateCount,
            'early_checkout_count' => $earlyCheckoutCount,
            'absent_count' => $absentCount,
            'leave_days' => $leaveCount,
            'dashboard_statistics' => array(
                'on_time' => $onTimeCount,
                'late' => $lateCount,
                'absent' => $absentCount,
                'leave' => $leaveCount,
                'not_absent_out' => $notAbsentOutCount,
                'early_leave' => $earlyCheckoutCount,
            ),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'schedule_source' => $times['source'],
            'special_schedule' => $times['source'] === 'user',
        );

        return array(
            'summary' => $summary,
            'daily' => $daily,
        );
    }

    private function get_attendance_logs($userId, $startDate, $endDate)
    {
        $rows = $this->db->query("
            SELECT type, created_at, attendance_reason, attachment_path, attendance_category
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
                    'first_in_category' => 'REGULAR',
                    'last_out_category' => 'REGULAR',
                    'first_in_reason' => null,
                    'last_out_reason' => null,
                    'first_in_attachment_path' => null,
                    'last_out_attachment_path' => null,
                );
            }
            if (!$this->is_attendance_log_eligible($row)) {
                continue;
            }
            if ($row['type'] === 'IN') {
                if ($logs[$day]['first_in'] === null || $row['created_at'] < $logs[$day]['first_in']) {
                    $logs[$day]['first_in'] = $row['created_at'];
                    $logs[$day]['first_in_category'] = $this->attendance_category_value($row['attendance_category'] ?? null);
                    $logs[$day]['first_in_reason'] = $row['attendance_reason'] ?? null;
                    $logs[$day]['first_in_attachment_path'] = $row['attachment_path'] ?? null;
                }
            }
            if ($row['type'] === 'OUT') {
                if ($logs[$day]['last_out'] === null || $row['created_at'] > $logs[$day]['last_out']) {
                    $logs[$day]['last_out'] = $row['created_at'];
                    $logs[$day]['last_out_category'] = $this->attendance_category_value($row['attendance_category'] ?? null);
                    $logs[$day]['last_out_reason'] = $row['attendance_reason'] ?? null;
                    $logs[$day]['last_out_attachment_path'] = $row['attachment_path'] ?? null;
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

    private function resolve_attendance_times($office, $user = null)
    {
        $start = '08:00';
        $end = '17:00';
        $source = 'default';

        if ($office && !empty($office['attendance_response_times'])) {
            $times = $this->parse_response_times($office['attendance_response_times']);
            list($officeStart, $officeEnd) = $this->select_time_pair($times);
            if ($officeStart) {
                $start = $officeStart;
            }
            if ($officeEnd) {
                $end = $officeEnd;
            }
            $source = 'office';
        }

        $userTimes = $this->resolve_user_attendance_times($user);
        if ($userTimes) {
            if (!empty($userTimes['start'])) {
                $start = $userTimes['start'];
            }
            if (!empty($userTimes['end'])) {
                $end = $userTimes['end'];
            }
            $source = 'user';
        }

        return array('start' => $start, 'end' => $end, 'source' => $source);
    }

    private function resolve_user_attendance_times($user)
    {
        if (!$user || !is_array($user)) {
            return null;
        }

        $start = null;
        $end = null;

        $rawTimes = trim((string) ($user['attendance_response_times'] ?? ''));
        if ($rawTimes !== '') {
            $times = $this->parse_response_times($rawTimes);
            list($start, $end) = $this->select_time_pair($times);
        }

        $startOverride = trim((string) ($user['attendance_start_time'] ?? ''));
        if ($this->is_valid_time($startOverride)) {
            $start = $startOverride;
        }

        $endOverride = trim((string) ($user['attendance_end_time'] ?? ''));
        if ($this->is_valid_time($endOverride)) {
            $end = $endOverride;
        }

        if ($start === null && $end === null) {
            return null;
        }

        return array('start' => $start, 'end' => $end);
    }

    private function select_time_pair($times)
    {
        $start = null;
        $end = null;
        if (!is_array($times)) {
            return array($start, $end);
        }

        foreach ($times as $time) {
            $time = trim((string) $time);
            if ($this->is_valid_time($time)) {
                if ($start === null) {
                    $start = $time;
                } elseif ($end === null) {
                    $end = $time;
                    break;
                }
            }
        }

        return array($start, $end);
    }

    private function is_valid_time($time)
    {
        return $time !== '' && preg_match('/^\d{2}:\d{2}$/', $time);
    }

    private function minutes_after_start($timestamp, $startTime)
    {
        $start = strtotime(substr($timestamp, 0, 10) . ' ' . $startTime . ':00');
        if ($start === false) {
            return null;
        }
        $delta = strtotime($timestamp) - $start;
        return (int) floor($delta / 60);
    }

    private function minutes_before_end($timestamp, $endTime)
    {
        $end = strtotime(substr($timestamp, 0, 10) . ' ' . $endTime . ':00');
        if ($end === false) {
            return null;
        }
        $delta = $end - strtotime($timestamp);
        return (int) floor($delta / 60);
    }

    private function build_attendance_notes($type, $timestamp, $startTime, $endTime, $meta = array())
    {
        $notes = array();
        $flags = array(
            'late' => false,
            'early_checkout' => false,
        );
        $minutes = array(
            'late' => null,
            'early_checkout' => null,
        );

        if ($type === 'IN') {
            $lateMinutes = $this->minutes_after_start($timestamp, $startTime);
            if ($lateMinutes !== null && $lateMinutes > $this->lateGraceMinutes) {
                $flags['late'] = true;
                $minutes['late'] = $lateMinutes;
                $notes[] = 'Late check-in by ' . $lateMinutes . ' minutes.';
            }
        } elseif ($type === 'OUT') {
            $earlyMinutes = $this->minutes_before_end($timestamp, $endTime);
            if ($earlyMinutes !== null && $earlyMinutes > $this->earlyGraceMinutes) {
                $flags['early_checkout'] = true;
                $minutes['early_checkout'] = $earlyMinutes;
                $notes[] = 'Early checkout by ' . $earlyMinutes . ' minutes.';
            }
        }

        $notesPayload = $notes;
        if (!empty($meta)) {
            $notesPayload = array(
                'items' => $notes,
                'meta' => $meta,
            );
        }

        return array(
            'notes' => $notesPayload,
            'flags' => $flags,
            'minutes' => $minutes,
        );
    }

    private function attendance_reason_context($log, $user)
    {
        $notes = $this->decode_attendance_notes($log['notes'] ?? null);
        $flags = array(
            'late' => false,
            'early_checkout' => false,
        );

        foreach ($notes as $note) {
            $note = strtolower(trim((string) $note));
            if (strpos($note, 'late check-in by') === 0) {
                $flags['late'] = true;
            }
            if (strpos($note, 'early checkout by') === 0) {
                $flags['early_checkout'] = true;
            }
        }

        if (!$flags['late'] && !$flags['early_checkout']) {
            $office = !empty($log['office_id']) ? $this->Office_model->get_by_id((int) $log['office_id']) : null;
            $computed = $this->build_attendance_notes($log['type'], $log['created_at'], ($this->resolve_attendance_times($office, $user))['start'], ($this->resolve_attendance_times($office, $user))['end']);
            $flags = $computed['flags'];
        }

        return array(
            'notes' => $notes,
            'flags' => $flags,
        );
    }

    private function decode_attendance_notes($notesValue)
    {
        if (is_array($notesValue)) {
            if (isset($notesValue['items']) && is_array($notesValue['items'])) {
                return array_values($notesValue['items']);
            }
            return array_values($notesValue);
        }

        $notesValue = trim((string) $notesValue);
        if ($notesValue === '') {
            return array();
        }

        $decoded = json_decode($notesValue, true);
        if (is_array($decoded)) {
            if (isset($decoded['items']) && is_array($decoded['items'])) {
                return array_values($decoded['items']);
            }
            return array_values($decoded);
        }

        return array($notesValue);
    }

    private function decode_attendance_notes_meta($notesValue)
    {
        if (is_array($notesValue)) {
            return isset($notesValue['meta']) && is_array($notesValue['meta']) ? $notesValue['meta'] : array();
        }

        $notesValue = trim((string) $notesValue);
        if ($notesValue === '') {
            return array();
        }

        $decoded = json_decode($notesValue, true);
        if (is_array($decoded) && isset($decoded['meta']) && is_array($decoded['meta'])) {
            return $decoded['meta'];
        }

        return array();
    }

    private function attendance_note_meta_value($notesValue, $key)
    {
        $meta = $this->decode_attendance_notes_meta($notesValue);
        return isset($meta[$key]) && trim((string) $meta[$key]) !== '' ? trim((string) $meta[$key]) : null;
    }

    private function build_daily_notes($firstIn, $lastOut, $startTime, $endTime, $isLate, $isEarlyCheckout, $dayLogs = array())
    {
        $notes = array();
        $firstInCategory = $this->attendance_category_value($dayLogs['first_in_category'] ?? null);
        $lastOutCategory = $this->attendance_category_value($dayLogs['last_out_category'] ?? null);
        if ($firstIn && $this->is_out_of_town_attendance($firstInCategory)) {
            $notes[] = 'Check-in recorded as Dinas Luar Kota.';
        }
        if ($lastOut && $this->is_out_of_town_attendance($lastOutCategory)) {
            $notes[] = 'Check-out recorded as Dinas Luar Kota.';
        }
        if ($isLate && $firstIn) {
            $lateMinutes = $this->minutes_after_start($firstIn, $startTime);
            if ($lateMinutes !== null) {
                $notes[] = 'Late check-in by ' . $lateMinutes . ' minutes.';
            }
        }
        if ($isEarlyCheckout && $lastOut) {
            $earlyMinutes = $this->minutes_before_end($lastOut, $endTime);
            if ($earlyMinutes !== null) {
                $notes[] = 'Early checkout by ' . $earlyMinutes . ' minutes.';
            }
        }

        return $notes;
    }

    private function attendance_category_value($value)
    {
        return strtoupper(trim((string) $value)) === 'OUT_OF_TOWN' ? 'OUT_OF_TOWN' : 'REGULAR';
    }

    private function attendance_category_label($value)
    {
        return $this->is_out_of_town_attendance($value) ? 'Dinas Luar Kota' : 'Kantor';
    }

    private function is_out_of_town_attendance($value)
    {
        return $this->attendance_category_value($value) === 'OUT_OF_TOWN';
    }

    private function is_attendance_log_eligible($row)
    {
        if (!$this->is_out_of_town_attendance($row['attendance_category'] ?? null)) {
            return true;
        }

        return trim((string) ($row['attachment_path'] ?? '')) !== '';
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
        $threshold = $start + ($this->lateGraceMinutes * 60);
        return strtotime($timestamp) > $threshold;
    }

    private function is_early_checkout($timestamp, $endTime)
    {
        $end = strtotime(substr($timestamp, 0, 10) . ' ' . $endTime . ':00');
        $threshold = $end - ($this->earlyGraceMinutes * 60);
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

    // =========================================
    // OVERTIME API METHODS
    // =========================================

    /**
     * Get active overtime types
     * GET /api/hrms/overtime/types
     */
    public function overtime_types()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        $this->load->model('OvertimeTypeModel');
        $types = $this->OvertimeTypeModel->get_active();

        $items = array();
        foreach ($types as $type) {
            $items[] = array(
                'id' => (int) $type['id'],
                'code' => $type['code'],
                'name' => $type['name'],
                'description' => $type['description'],
                'requiresAttachment' => (int) $type['requires_attachment'] === 1,
            );
        }

        return $this->respond(200, array('data' => $items));
    }

    /**
     * Get overtime requests or create new one
     * GET /api/hrms/overtime - List requests
     * POST /api/hrms/overtime - Create request
     */
    public function overtime()
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
            return $this->overtime_list($user);
        }

        if ($method !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        return $this->overtime_create($user);
    }

    /**
     * List overtime requests for user
     */
    private function overtime_list($user)
    {
        $this->load->model('OvertimeRequestModel');

        $filters = array();
        $status = $this->input->get('status', TRUE);
        $month = $this->input->get('month', TRUE);

        if ($status) {
            $filters['status'] = $status;
        }
        if ($month) {
            $filters['month'] = $month;
        }

        $requests = $this->OvertimeRequestModel->get_by_user($user['id'], $filters);

        $items = array();
        foreach ($requests as $request) {
            $items[] = array(
                'id' => (int) $request['id'],
                'requestNo' => $request['request_no'],
                'overtimeTypeId' => (int) $request['overtime_type_id'],
                'overtimeTypeName' => $request['overtime_type_name'] ?? null,
                'overtimeDate' => $request['overtime_date'],
                'startTime' => $request['start_time'],
                'endTime' => $request['end_time'],
                'durationHours' => (float) $request['duration_hours'],
                'reason' => $request['reason'],
                'status' => $this->map_overtime_status($request['status']),
                'statusRaw' => $request['status'],
                'attachmentPath' => $this->absolute_attachment_url($request['attachment_path']),
            );
        }

        return $this->respond(200, array('data' => $items));
    }

    /**
     * Create overtime request
     */
    private function overtime_create($user)
    {
        $this->load->model('OvertimeTypeModel');
        $this->load->model('OvertimeRequestModel');
        $this->load->library('OvertimeCalculatorService');
        $this->load->library('OvertimeWorkflowEngine');

        $input = $this->json_input();
        $errors = array();

        // Validate overtime_type_id
        $overtimeTypeId = (int) ($input['overtime_type_id'] ?? 0);
        $overtimeType = null;
        if ($overtimeTypeId <= 0) {
            $errors['overtime_type_id'] = 'Overtime type is required.';
        } else {
            $overtimeType = $this->OvertimeTypeModel->get_by_id($overtimeTypeId);
            if (!$overtimeType || (int) $overtimeType['is_active'] !== 1) {
                $errors['overtime_type_id'] = 'Overtime type is invalid.';
            }
        }

        // Validate overtime_date
        $overtimeDate = trim((string) ($input['overtime_date'] ?? ''));
        if ($overtimeDate === '' || !$this->is_valid_date($overtimeDate)) {
            $errors['overtime_date'] = 'Valid overtime date is required.';
        }

        // Validate times
        $startTime = trim((string) ($input['start_time'] ?? ''));
        $endTime = trim((string) ($input['end_time'] ?? ''));
        if ($startTime === '' || !$this->overtimecalculatorservice->is_valid_time($startTime)) {
            $errors['start_time'] = 'Valid start time is required.';
        }
        if ($endTime === '' || !$this->overtimecalculatorservice->is_valid_time($endTime)) {
            $errors['end_time'] = 'Valid end time is required.';
        }

        // Calculate duration
        $durationHours = 0;
        if (empty($errors['start_time']) && empty($errors['end_time'])) {
            $durationHours = $this->overtimecalculatorservice->calculate_duration($startTime, $endTime);
            if ($durationHours <= 0) {
                $errors['end_time'] = 'End time must be after start time.';
            }
        }

        // Validate reason
        $reason = trim((string) ($input['reason'] ?? ''));
        if ($reason === '') {
            $errors['reason'] = 'Reason is required.';
        }

        // Check overlap
        if (empty($errors) && $this->OvertimeRequestModel->has_overlap($user['id'], $overtimeDate, $startTime, $endTime)) {
            $errors['overtime_date'] = 'An overtime request already exists for this time period.';
        }

        // Validate attachment if required
        $attachmentPath = trim((string) ($input['attachment_path'] ?? $input['attachment'] ?? ''));
        if ($overtimeType && (int) $overtimeType['requires_attachment'] === 1) {
            if (empty($_FILES['attachment']['name']) && $attachmentPath === '') {
                $errors['attachment'] = 'Attachment is required for this overtime type.';
            }
        }

        // WiFi BSSID validation for mobile
        $bssid = trim((string) ($input['bssid'] ?? ''));
        if ($bssid !== '') {
            $office = $this->Office_model->get_active_office();
            if ($office && !empty($office['allowed_bssids'])) {
                $allowedBssids = array_map('trim', preg_split('/\r\n|\r|\n|,/', $office['allowed_bssids']));
                $allowedBssids = array_map('strtoupper', array_filter($allowedBssids));
                if (!in_array(strtoupper($bssid), $allowedBssids)) {
                    $errors['bssid'] = 'You must be connected to office WiFi to submit overtime request.';
                }
            }
        }

        if (!empty($errors)) {
            return $this->respond(422, array('message' => 'Validation failed.', 'errors' => $errors));
        }

        // Generate request number
        $requestNo = $this->OvertimeRequestModel->generate_request_no();

        // Handle attachment upload
        if (!empty($_FILES['attachment']['name'])) {
            $upload = $this->handle_overtime_attachment_upload($requestNo, 'attachment');
            if (isset($upload['error'])) {
                return $this->respond(422, array('message' => $upload['error']));
            }
            $attachmentPath = $upload['path'];
        }

        // Get active office
        $office = $this->Office_model->get_active_office();

        $now = date('Y-m-d H:i:s');

        // Insert overtime request
        $this->db->trans_start();

        $requestId = $this->OvertimeRequestModel->insert(array(
            'request_no' => $requestNo,
            'user_id' => $user['id'],
            'overtime_type_id' => $overtimeTypeId,
            'office_id' => $office ? $office['id'] : null,
            'overtime_date' => $overtimeDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_hours' => $durationHours,
            'reason' => $reason,
            'attachment_path' => $attachmentPath !== '' ? $attachmentPath : null,
            'status' => 'SUBMITTED',
            'submitted_at' => $now,
            'created_by' => $user['id'],
        ));

        $this->db->trans_complete();

        if (!$requestId) {
            return $this->respond(500, array('message' => 'Failed to create overtime request.'));
        }

        // Initialize workflow
        $workflowResult = $this->overtimeworkflowengine->initializeWorkflow($requestId);

        if (!$workflowResult['success'] && isset($workflowResult['code']) && in_array($workflowResult['code'], array('NO_ROUTE', 'NO_STEPS'), true)) {
            $this->OvertimeRequestModel->delete($requestId);
            return $this->respond(422, array('message' => $workflowResult['message']));
        }

        $status = 'SUBMITTED';
        if ($workflowResult['success']) {
            $status = 'IN_REVIEW';
        }

        return $this->respond(201, array(
            'id' => (int) $requestId,
            'requestNo' => $requestNo,
            'status' => $this->map_overtime_status($status),
            'statusRaw' => $status,
            'durationHours' => $durationHours,
        ));
    }

    /**
     * Get overtime request detail with approval progress
     * GET /api/hrms/overtime/{id}
     */
    public function overtime_detail($id)
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

        $this->load->model('OvertimeRequestModel');
        $this->load->model('OvertimeApprovalStepModel');
        $this->load->model('OvertimeApprovalInstanceModel');

        $request = $this->OvertimeRequestModel->get_by_id((int) $id);
        if (!$request) {
            return $this->respond(404, array('message' => 'Overtime request not found.'));
        }

        if ((int) $request['user_id'] !== (int) $user['id']) {
            return $this->respond(403, array('message' => 'You do not have access to this overtime request.'));
        }

        // Get approvals
        $approvals = $this->OvertimeApprovalStepModel->get_by_overtime_request((int) $id);
        $approvalsPayload = array();
        foreach ($approvals as $approval) {
            $approvalsPayload[] = array(
                'id' => (int) $approval['id'],
                'stepNo' => (int) $approval['step_no'],
                'stepName' => $approval['step_name'],
                'approverId' => $approval['assigned_approver_id'] ? (int) $approval['assigned_approver_id'] : null,
                'approverName' => $approval['approver_name'] ?? null,
                'action' => $approval['action'],
                'actionAt' => $approval['action_at'],
                'notes' => $approval['notes'],
            );
        }

        // Get instance for total steps
        $instance = $this->OvertimeApprovalInstanceModel->get_by_overtime_request((int) $id);

        $requester = $this->db->select('id, full_name, email')
            ->get_where('user', array('id' => (int) $request['user_id']))
            ->row_array();

        return $this->respond(200, array(
            'id' => (int) $request['id'],
            'requestNo' => $request['request_no'],
            'requester' => array(
                'id' => (int) ($requester['id'] ?? 0),
                'name' => $requester['full_name'] ?? null,
                'email' => $requester['email'] ?? null,
            ),
            'overtimeTypeId' => (int) $request['overtime_type_id'],
            'overtimeTypeName' => $request['overtime_type_name'] ?? null,
            'overtimeTypeCode' => $request['overtime_type_code'] ?? null,
            'requiresAttachment' => (int) ($request['requires_attachment'] ?? 0),
            'overtimeDate' => $request['overtime_date'],
            'startTime' => $request['start_time'],
            'endTime' => $request['end_time'],
            'durationHours' => (float) $request['duration_hours'],
            'reason' => $request['reason'],
            'status' => $this->map_overtime_status($request['status']),
            'statusRaw' => $request['status'],
            'currentStep' => (int) $request['current_step'],
            'totalSteps' => $instance ? (int) $instance['total_steps'] : 1,
            'attachmentPath' => $this->absolute_attachment_url($request['attachment_path']),
            'createdAt' => $request['created_at'],
            'updatedAt' => $request['updated_at'],
            'approvals' => $approvalsPayload,
        ));
    }

    /**
     * Cancel overtime request
     * POST /api/hrms/overtime/{id}/cancel
     */
    public function overtime_cancel($id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->user_requires_attendance((int) $user['id'])) {
            return $this->respond(403, array('message' => 'Attendance is not required for this account.'));
        }

        $this->load->library('OvertimeWorkflowEngine');

        $result = $this->overtimeworkflowengine->cancelWorkflow((int) $id, (int) $user['id']);

        if (!$result['success']) {
            return $this->respond(409, array('message' => $result['message']));
        }

        return $this->respond(200, array(
            'message' => $result['message'],
            'id' => (int) $id,
            'status' => 'CANCELLED',
        ));
    }

    /**
     * Get user's overtime summary
     * GET /api/hrms/overtime/summary?month=YYYY-MM
     */
    public function overtime_summary()
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

        $this->load->model('OvertimeRequestModel');
        $this->load->model('OvertimeLedgerModel');

        $month = $this->input->get('month', TRUE);
        $month = $month ?: date('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->respond(422, array('message' => 'Invalid month format. Use YYYY-MM.'));
        }

        $year = (int) substr($month, 0, 4);
        $monthNum = (int) substr($month, 5, 2);

        // Get summary from requests
        $requestSummary = $this->OvertimeRequestModel->get_user_monthly_summary($user['id'], $month);

        // Get approved hours from ledger
        $ledgerSummary = $this->OvertimeLedgerModel->get_monthly_summary($user['id'], $year, $monthNum);

        // Get requests for the month
        $requests = $this->OvertimeRequestModel->get_by_user($user['id'], array('month' => $month));

        $items = array();
        foreach ($requests as $request) {
            $items[] = array(
                'id' => (int) $request['id'],
                'requestNo' => $request['request_no'],
                'overtimeTypeName' => $request['overtime_type_name'] ?? null,
                'overtimeDate' => $request['overtime_date'],
                'durationHours' => (float) $request['duration_hours'],
                'status' => $this->map_overtime_status($request['status']),
                'statusRaw' => $request['status'],
            );
        }

        return $this->respond(200, array(
            'summary' => array(
                'month' => $month,
                'totalHours' => (float) ($requestSummary['total_hours'] ?? 0),
                'approvedHours' => (float) ($ledgerSummary['total_hours'] ?? 0),
                'pendingHours' => (float) ($requestSummary['pending_hours'] ?? 0),
                'requestCount' => (int) ($requestSummary['request_count'] ?? 0),
            ),
            'requests' => $items,
        ));
    }

    public function leave_approvals()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'leave_approvals', 'view')) {
            return null;
        }

        $statusFilter = $this->normalize_approval_status_filter();
        if (isset($statusFilter['error'])) {
            return $this->respond(422, array(
                'message' => 'Validation failed.',
                'errors' => array('status' => $statusFilter['error']),
            ));
        }

        $limit = $this->normalize_approval_list_limit();
        $steps = $this->get_leave_approval_steps_for_filter((int) $user['id'], $statusFilter);

        $items = array();
        foreach (array_slice($steps, 0, $limit) as $step) {
            $items[] = $this->map_leave_approval_step_item($step, (int) $user['id']);
        }

        return $this->respond(200, array(
            'status' => $statusFilter,
            'limit' => $limit,
            'counts' => array(
                'pending' => $this->ApprovalStepModel->get_pending_count((int) $user['id']),
                'returned' => count($items),
                'total' => count($steps),
            ),
            'data' => $items,
        ));
    }

    public function leave_approval_detail($stepId)
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'leave_approvals', 'view')) {
            return null;
        }

        $payload = $this->build_leave_approval_detail_payload((int) $stepId, (int) $user['id']);
        if (isset($payload['status_code'])) {
            return $this->respond($payload['status_code'], array('message' => $payload['message']));
        }

        return $this->respond(200, $payload);
    }

    public function leave_approval_approve($stepId)
    {
        return $this->handle_leave_approval_action($stepId, 'APPROVED');
    }

    public function leave_approval_reject($stepId)
    {
        return $this->handle_leave_approval_action($stepId, 'REJECTED');
    }

    public function leave_approvals_history()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'leave_approvals', 'view')) {
            return null;
        }

        $limit = $this->normalize_approval_list_limit();
        $steps = $this->ApprovalStepModel->get_history_for_approver((int) $user['id'], $limit);
        $items = array();
        foreach ($steps as $step) {
            $items[] = $this->map_leave_approval_step_item($step, (int) $user['id']);
        }

        return $this->respond(200, array(
            'status' => 'history',
            'limit' => $limit,
            'counts' => array(
                'pending' => $this->ApprovalStepModel->get_pending_count((int) $user['id']),
                'returned' => count($items),
                'total' => count($items),
            ),
            'data' => $items,
        ));
    }

    /**
     * Get pending overtime approvals inbox
     * GET /api/hrms/overtime/approvals/inbox
     */
    public function overtime_approvals_inbox()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'overtime_approvals', 'view')) {
            return null;
        }

        return $this->respond(200, $this->build_overtime_approvals_list_response((int) $user['id'], 'pending'));
    }

    /**
     * Get count of pending overtime approvals
     * GET /api/hrms/overtime/approvals/inbox/count
     */
    public function overtime_approvals_inbox_count()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'overtime_approvals', 'view')) {
            return null;
        }

        $this->load->model('OvertimeApprovalStepModel');

        $count = $this->OvertimeApprovalStepModel->count_pending_for_approver((int) $user['id']);

        return $this->respond(200, array('count' => $count));
    }

    /**
     * Approve overtime approval step
     * POST /api/hrms/overtime/approvals/{stepId}/approve
     */
    public function overtime_approval_approve($stepId)
    {
        return $this->handle_overtime_approval_action($stepId, 'APPROVED');
    }

    /**
     * Reject overtime approval step
     * POST /api/hrms/overtime/approvals/{stepId}/reject
     */
    public function overtime_approval_reject($stepId)
    {
        return $this->handle_overtime_approval_action($stepId, 'REJECTED');
    }

    /**
     * Get overtime approval history for approver
     * GET /api/hrms/overtime/approvals/history
     */
    public function overtime_approvals_history()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'overtime_approvals', 'view')) {
            return null;
        }

        return $this->respond(200, $this->build_overtime_approvals_list_response((int) $user['id'], 'history'));
    }

    public function overtime_approvals()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'overtime_approvals', 'view')) {
            return null;
        }

        $statusFilter = $this->normalize_approval_status_filter();
        if (isset($statusFilter['error'])) {
            return $this->respond(422, array(
                'message' => 'Validation failed.',
                'errors' => array('status' => $statusFilter['error']),
            ));
        }

        return $this->respond(200, $this->build_overtime_approvals_list_response((int) $user['id'], $statusFilter));
    }

    public function overtime_approval_detail($stepId)
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'overtime_approvals', 'view')) {
            return null;
        }

        $payload = $this->build_overtime_approval_detail_payload((int) $stepId, (int) $user['id']);
        if (isset($payload['status_code'])) {
            return $this->respond($payload['status_code'], array('message' => $payload['message']));
        }

        return $this->respond(200, $payload);
    }

    private function normalize_approval_status_filter()
    {
        $status = strtolower(trim((string) $this->input->get('status', TRUE)));
        if ($status === '') {
            return 'pending';
        }

        if (!in_array($status, array('pending', 'history', 'all'), true)) {
            return array('error' => 'Status must be one of: pending, history, all.');
        }

        return $status;
    }

    private function normalize_approval_list_limit()
    {
        $limit = (int) $this->input->get('limit', TRUE);
        return $limit > 0 ? min($limit, 100) : 50;
    }

    private function get_leave_approval_steps_for_filter($userId, $statusFilter)
    {
        if ($statusFilter === 'pending') {
            return $this->ApprovalStepModel->get_pending_for_approver($userId);
        }

        if ($statusFilter === 'history') {
            return $this->ApprovalStepModel->get_history_for_approver($userId, $this->normalize_approval_list_limit());
        }

        $pending = $this->ApprovalStepModel->get_pending_for_approver($userId);
        $history = $this->ApprovalStepModel->get_history_for_approver($userId, $this->normalize_approval_list_limit());
        return array_merge($pending, $history);
    }

    private function get_overtime_approval_steps_for_filter($userId, $statusFilter)
    {
        $this->load->model('OvertimeApprovalStepModel');

        if ($statusFilter === 'pending') {
            return $this->OvertimeApprovalStepModel->get_pending_for_approver($userId);
        }

        if ($statusFilter === 'history') {
            return $this->OvertimeApprovalStepModel->get_history_for_approver($userId, $this->normalize_approval_list_limit());
        }

        $pending = $this->OvertimeApprovalStepModel->get_pending_for_approver($userId);
        $history = $this->OvertimeApprovalStepModel->get_history_for_approver($userId, $this->normalize_approval_list_limit());
        return array_merge($pending, $history);
    }

    private function build_overtime_approvals_list_response($userId, $statusFilter)
    {
        $steps = $this->get_overtime_approval_steps_for_filter($userId, $statusFilter);
        $limit = $this->normalize_approval_list_limit();
        $items = array();
        foreach (array_slice($steps, 0, $limit) as $step) {
            $items[] = $this->map_overtime_approval_step_item($step, $userId);
        }

        $this->load->model('OvertimeApprovalStepModel');

        return array(
            'status' => $statusFilter,
            'limit' => $limit,
            'counts' => array(
                'pending' => $this->OvertimeApprovalStepModel->count_pending_for_approver($userId),
                'returned' => count($items),
                'total' => count($steps),
            ),
            'data' => $items,
        );
    }

    private function map_leave_approval_step_item($step, $userId)
    {
        $isProcessed = in_array((string) ($step['action'] ?? ''), array('APPROVED', 'REJECTED'), true);

        return array(
            'stepId' => (int) $step['id'],
            'requestId' => isset($step['leave_request_id']) ? (int) $step['leave_request_id'] : null,
            'requestNo' => $step['request_no'] ?? null,
            'module' => 'leave',
            'requesterId' => isset($step['requester_id']) ? (int) $step['requester_id'] : null,
            'requesterName' => $step['requester_name'] ?? null,
            'requesterRole' => $step['requester_role'] ?? null,
            'leaveTypeName' => $step['leave_type_name'] ?? null,
            'startDate' => $step['start_date'] ?? null,
            'endDate' => $step['end_date'] ?? null,
            'daysCount' => isset($step['days_count']) ? (int) $step['days_count'] : null,
            'reason' => $step['reason'] ?? null,
            'stepNo' => isset($step['step_no']) ? (int) $step['step_no'] : null,
            'stepName' => $step['step_name'] ?? null,
            'totalSteps' => isset($step['total_steps']) ? (int) $step['total_steps'] : null,
            'currentStep' => isset($step['current_step']) ? (int) $step['current_step'] : null,
            'action' => $step['action'] ?? null,
            'actionAt' => $step['action_at'] ?? null,
            'notes' => $step['notes'] ?? null,
            'canTakeAction' => !$isProcessed && isset($step['assigned_approver_id']) && (int) $step['assigned_approver_id'] === $userId,
        );
    }

    private function map_overtime_approval_step_item($step, $userId)
    {
        $isProcessed = in_array((string) ($step['action'] ?? ''), array('APPROVED', 'REJECTED'), true);

        return array(
            'stepId' => (int) $step['id'],
            'requestId' => isset($step['overtime_request_id']) ? (int) $step['overtime_request_id'] : null,
            'requestNo' => $step['request_no'] ?? null,
            'module' => 'overtime',
            'requesterId' => isset($step['requester_id']) ? (int) $step['requester_id'] : null,
            'requesterName' => $step['requester_name'] ?? null,
            'overtimeTypeName' => $step['overtime_type_name'] ?? null,
            'overtimeDate' => $step['overtime_date'] ?? null,
            'startTime' => $step['start_time'] ?? null,
            'endTime' => $step['end_time'] ?? null,
            'durationHours' => isset($step['duration_hours']) ? (float) $step['duration_hours'] : null,
            'reason' => $step['reason'] ?? null,
            'stepNo' => isset($step['step_no']) ? (int) $step['step_no'] : null,
            'stepName' => $step['step_name'] ?? null,
            'totalSteps' => isset($step['total_steps']) ? (int) $step['total_steps'] : null,
            'currentStep' => isset($step['current_step']) ? (int) $step['current_step'] : null,
            'action' => $step['action'] ?? null,
            'actionAt' => $step['action_at'] ?? null,
            'notes' => $step['notes'] ?? null,
            'canTakeAction' => !$isProcessed && isset($step['assigned_approver_id']) && (int) $step['assigned_approver_id'] === $userId,
        );
    }

    private function build_leave_approval_detail_payload($stepId, $userId)
    {
        $this->load->model('ApprovalInstanceModel');

        $step = $this->ApprovalStepModel->get_by_id($stepId);
        if (!$step) {
            return array('status_code' => 404, 'message' => 'Approval step not found.');
        }

        $instance = $this->ApprovalInstanceModel->get_by_id((int) $step['approval_instance_id']);
        $request = $this->LeaveRequestModel->get_by_id((int) $step['leave_request_id']);
        if (!$request) {
            return array('status_code' => 404, 'message' => 'Leave request not found.');
        }

        if (!$this->can_access_leave_approval_step($step, $instance, $userId)) {
            return array('status_code' => 403, 'message' => 'You do not have access to this approval step.');
        }

        $progress = $this->approvalworkflowengine->getWorkflowProgress((int) $step['leave_request_id']);
        $requester = $this->db->query("
            SELECT u.id, u.full_name, u.email, p.name as position_name
            FROM user u
            LEFT JOIN user_profile up ON u.id = up.user_id
            LEFT JOIN positions p ON up.position_id = p.id
            WHERE u.id = ?
            LIMIT 1
        ", array((int) $request['user_id']))->row_array();

        $stepsPayload = array();
        foreach ($this->ApprovalStepModel->get_by_leave_request((int) $step['leave_request_id']) as $approvalStep) {
            $stepsPayload[] = array(
                'id' => (int) $approvalStep['id'],
                'stepNo' => (int) $approvalStep['step_no'],
                'stepName' => $approvalStep['step_name'] ?? null,
                'assignedApproverId' => isset($approvalStep['assigned_approver_id']) ? (int) $approvalStep['assigned_approver_id'] : null,
                'assignedApproverName' => $approvalStep['assigned_approver_name'] ?? null,
                'assignedApproverRole' => $approvalStep['assigned_approver_role'] ?? null,
                'actualApproverId' => isset($approvalStep['actual_approver_id']) ? (int) $approvalStep['actual_approver_id'] : null,
                'actualApproverName' => $approvalStep['actual_approver_name'] ?? null,
                'action' => $approvalStep['action'],
                'actionAt' => $approvalStep['action_at'],
                'notes' => $approvalStep['notes'],
            );
        }

        return array(
            'step' => array(
                'id' => (int) $step['id'],
                'stepNo' => (int) $step['step_no'],
                'stepName' => $step['step_name'] ?? null,
                'action' => $step['action'],
                'actionAt' => $step['action_at'],
                'notes' => $step['notes'],
                'canTakeAction' => $this->is_current_pending_step_for_user($step, $instance, $userId),
            ),
            'request' => array(
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
                'statusRaw' => $request['status'],
                'attachmentPath' => $this->absolute_attachment_url($request['attachment_path']),
                'createdAt' => $request['created_at'],
                'updatedAt' => $request['updated_at'],
            ),
            'requester' => array(
                'id' => (int) ($requester['id'] ?? 0),
                'name' => $requester['full_name'] ?? null,
                'email' => $requester['email'] ?? null,
                'positionName' => $requester['position_name'] ?? null,
            ),
            'workflow' => array(
                'currentStep' => isset($progress['current_step']) ? (int) $progress['current_step'] : (isset($instance['current_step']) ? (int) $instance['current_step'] : null),
                'totalSteps' => isset($progress['total_steps']) ? (int) $progress['total_steps'] : (isset($instance['total_steps']) ? (int) $instance['total_steps'] : null),
                'status' => $progress['status'] ?? ($instance['status'] ?? null),
                'steps' => $stepsPayload,
            ),
        );
    }

    private function build_overtime_approval_detail_payload($stepId, $userId)
    {
        $this->load->model('OvertimeApprovalStepModel');
        $this->load->model('OvertimeApprovalInstanceModel');
        $this->load->model('OvertimeRequestModel');

        $step = $this->OvertimeApprovalStepModel->get_by_id($stepId);
        if (!$step) {
            return array('status_code' => 404, 'message' => 'Approval step not found.');
        }

        $instance = $this->OvertimeApprovalInstanceModel->get_by_id((int) $step['approval_instance_id']);
        $request = $this->OvertimeRequestModel->get_by_id((int) $step['overtime_request_id']);
        if (!$request) {
            return array('status_code' => 404, 'message' => 'Overtime request not found.');
        }

        if (!$this->can_access_overtime_approval_step($step, $instance, $userId)) {
            return array('status_code' => 403, 'message' => 'You do not have access to this approval step.');
        }

        $this->load->library('OvertimeWorkflowEngine');
        $progress = $this->overtimeworkflowengine->getWorkflowProgress((int) $step['overtime_request_id']);
        $requester = $this->db->query("
            SELECT u.id, u.full_name, u.email, p.name as position_name
            FROM user u
            LEFT JOIN user_profile up ON u.id = up.user_id
            LEFT JOIN positions p ON up.position_id = p.id
            WHERE u.id = ?
            LIMIT 1
        ", array((int) $request['user_id']))->row_array();

        $stepsPayload = array();
        foreach ($this->OvertimeApprovalStepModel->get_by_overtime_request((int) $step['overtime_request_id']) as $approvalStep) {
            $stepsPayload[] = array(
                'id' => (int) $approvalStep['id'],
                'stepNo' => (int) $approvalStep['step_no'],
                'stepName' => $approvalStep['step_name'] ?? null,
                'assignedApproverId' => isset($approvalStep['assigned_approver_id']) ? (int) $approvalStep['assigned_approver_id'] : null,
                'assignedApproverName' => $approvalStep['approver_name'] ?? null,
                'actualApproverId' => isset($approvalStep['actual_approver_id']) ? (int) $approvalStep['actual_approver_id'] : null,
                'actualApproverName' => $approvalStep['actual_approver_name'] ?? null,
                'action' => $approvalStep['action'],
                'actionAt' => $approvalStep['action_at'],
                'notes' => $approvalStep['notes'],
            );
        }

        return array(
            'step' => array(
                'id' => (int) $step['id'],
                'stepNo' => (int) $step['step_no'],
                'stepName' => $step['step_name'] ?? null,
                'action' => $step['action'],
                'actionAt' => $step['action_at'],
                'notes' => $step['notes'],
                'canTakeAction' => $this->is_current_pending_step_for_user($step, $instance, $userId),
            ),
            'request' => array(
                'id' => (int) $request['id'],
                'requestNo' => $request['request_no'],
                'overtimeTypeId' => (int) $request['overtime_type_id'],
                'overtimeTypeName' => $request['overtime_type_name'] ?? null,
                'overtimeTypeCode' => $request['overtime_type_code'] ?? null,
                'overtimeDate' => $request['overtime_date'],
                'startTime' => $request['start_time'],
                'endTime' => $request['end_time'],
                'durationHours' => (float) $request['duration_hours'],
                'reason' => $request['reason'],
                'status' => $this->map_overtime_status($request['status']),
                'statusRaw' => $request['status'],
                'attachmentPath' => $this->absolute_attachment_url($request['attachment_path']),
                'createdAt' => $request['created_at'],
                'updatedAt' => $request['updated_at'],
            ),
            'requester' => array(
                'id' => (int) ($requester['id'] ?? 0),
                'name' => $requester['full_name'] ?? null,
                'email' => $requester['email'] ?? null,
                'positionName' => $requester['position_name'] ?? null,
            ),
            'workflow' => array(
                'currentStep' => isset($progress['current_step']) ? (int) $progress['current_step'] : (isset($instance['current_step']) ? (int) $instance['current_step'] : null),
                'totalSteps' => isset($progress['total_steps']) ? (int) $progress['total_steps'] : (isset($instance['total_steps']) ? (int) $instance['total_steps'] : null),
                'status' => $progress['status'] ?? ($instance['status'] ?? null),
                'steps' => $stepsPayload,
            ),
        );
    }

    private function can_access_leave_approval_step($step, $instance, $userId)
    {
        if ($this->is_current_pending_step_for_user($step, $instance, $userId)) {
            return true;
        }

        return isset($step['actual_approver_id']) && (int) $step['actual_approver_id'] === $userId;
    }

    private function can_access_overtime_approval_step($step, $instance, $userId)
    {
        if ($this->is_current_pending_step_for_user($step, $instance, $userId)) {
            return true;
        }

        return isset($step['actual_approver_id']) && (int) $step['actual_approver_id'] === $userId;
    }

    private function is_current_pending_step_for_user($step, $instance, $userId)
    {
        return
            isset($step['assigned_approver_id']) &&
            (int) $step['assigned_approver_id'] === $userId &&
            isset($step['action']) &&
            $step['action'] === 'PENDING' &&
            !empty($instance) &&
            (($instance['status'] ?? null) === 'IN_PROGRESS') &&
            isset($instance['current_step']) &&
            (int) $instance['current_step'] === (int) ($step['step_no'] ?? 0);
    }

    private function handle_leave_approval_action($stepId, $action)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'leave_approvals', 'approve')) {
            return null;
        }

        $input = $this->json_input();
        $notes = trim((string) ($input['notes'] ?? ''));
        if ($action === 'REJECTED' && $notes === '') {
            return $this->respond(422, array(
                'message' => 'Validation failed.',
                'errors' => array('notes' => 'Rejection reason is required.'),
            ));
        }

        $step = $this->ApprovalStepModel->get_by_id((int) $stepId);
        if (!$step) {
            return $this->respond(404, array('message' => 'Approval step not found.'));
        }

        $request = $this->LeaveRequestModel->get_by_id((int) $step['leave_request_id']);
        $result = $this->approvalworkflowengine->processApproval((int) $stepId, (int) $user['id'], $action, $notes !== '' ? $notes : null);
        if (!$result['success']) {
            return $this->respond(409, array('message' => $result['message']));
        }

        $request = $request ? $this->LeaveRequestModel->get_by_id((int) $request['id']) : null;

        return $this->respond(200, array(
            'success' => true,
            'message' => $result['message'],
            'action' => $action,
            'isFinal' => !empty($result['is_final']),
            'nextStep' => isset($result['next_step']) ? (int) $result['next_step'] : null,
            'requestStatus' => $request ? $this->map_leave_status($request['status']) : null,
            'requestStatusRaw' => $request['status'] ?? null,
            'quotaWarning' => $result['quota_warning'] ?? null,
        ));
    }

    private function handle_overtime_approval_action($stepId, $action)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('message' => 'Method not allowed'));
        }

        $user = $this->require_user();
        if (!$user) {
            return null;
        }

        if (!$this->require_module_permission($user['id'], 'overtime_approvals', 'approve')) {
            return null;
        }

        $this->load->library('OvertimeWorkflowEngine');
        $this->load->model('OvertimeApprovalStepModel');
        $this->load->model('OvertimeRequestModel');

        $input = $this->json_input();
        $notes = trim((string) ($input['notes'] ?? ''));
        if ($action === 'REJECTED' && $notes === '') {
            return $this->respond(422, array(
                'message' => 'Validation failed.',
                'errors' => array('notes' => 'Rejection reason is required.'),
            ));
        }

        $step = $this->OvertimeApprovalStepModel->get_by_id((int) $stepId);
        if (!$step) {
            return $this->respond(404, array('message' => 'Approval step not found.'));
        }

        $request = $this->OvertimeRequestModel->get_by_id((int) $step['overtime_request_id']);
        $result = $this->overtimeworkflowengine->processApproval((int) $stepId, (int) $user['id'], $action, $notes !== '' ? $notes : null);
        if (!$result['success']) {
            return $this->respond(409, array('message' => $result['message']));
        }

        $request = $request ? $this->OvertimeRequestModel->get_by_id((int) $request['id']) : null;

        return $this->respond(200, array(
            'success' => true,
            'message' => $result['message'],
            'action' => $action,
            'isFinal' => !empty($result['is_final']),
            'nextStep' => isset($result['next_step']) ? (int) $result['next_step'] : null,
            'requestStatus' => $request ? $this->map_overtime_status($request['status']) : null,
            'requestStatusRaw' => $request['status'] ?? null,
        ));
    }

    /**
     * Handle overtime attachment upload
     */
    private function handle_overtime_attachment_upload($requestNo, $fieldName)
    {
        return $this->uploadservice->upload('overtime', $fieldName, array('subdir' => $requestNo));
    }

    /**
     * Map overtime status for display
     */
    private function map_overtime_status($status)
    {
        switch ($status) {
            case 'SUBMITTED':
            case 'IN_REVIEW':
                return 'Pending';
            case 'APPROVED':
                return 'Approved';
            case 'REJECTED':
                return 'Rejected';
            case 'CANCELLED':
                return 'Cancelled';
            default:
                return $status;
        }
    }
}
