<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AttendanceController extends CI_Controller
{
    private $cooldownSeconds = 60;
    private $lateGraceMinutes = 15;
    private $earlyGraceMinutes = 15;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Office_model');
        $this->load->model('Attendance_log_model');
        $this->load->helper('attendance');
        $this->load->library('AttendanceEligibilityService');
    }

    public function status()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->respond(405, array('status' => 'error', 'message' => 'Method not allowed'));
        }

        $lat = $this->input->get('lat', TRUE);
        $lng = $this->input->get('lng', TRUE);
        $accuracy = $this->input->get('accuracy', TRUE);

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

        $officeOverride = $this->build_office_override();
        if (isset($officeOverride['error'])) {
            return $this->respond(400, array('status' => 'error', 'message' => $officeOverride['error']));
        }

        if (!empty($errors)) {
            return $this->respond(400, array('status' => 'error', 'message' => 'Validation failed', 'errors' => $errors));
        }

        $wifiProof = $this->extract_wifi_proof_from_query();
        $ipAddress = $this->input->ip_address();
        $result = $this->attendanceeligibilityservice->evaluate($lat, $lng, $accuracy, $ipAddress, $officeOverride ?: null, $wifiProof);
        if (isset($result['error'])) {
            return $this->respond(500, array('status' => 'error', 'message' => 'Active office is not configured.'));
        }

        $office = $result['office'];
        $officeRecord = $this->Office_model->get_by_id($office['id']);
        $officeForWifi = $officeRecord ?: $office;
        $hasWifiRules = $this->has_wifi_rules($officeForWifi);
        $wifiOk = $this->wifi_proof_ok($officeForWifi, $wifiProof);
        $canConfirm = $result['computed']['can_confirm'];
        $reasons = $result['computed']['reasons'];
        if ($hasWifiRules) {
            $canConfirm = $canConfirm && $wifiOk;
            if ($wifiProof === null) {
                $reasons[] = 'WIFI_REQUIRED';
            } elseif (!$wifiOk) {
                $reasons[] = 'WIFI_NOT_ALLOWED';
            }
            $reasons = array_values(array_unique($reasons));
        }
        $result['computed']['can_confirm'] = $canConfirm;
        $result['computed']['reasons'] = $reasons;

        return $this->respond(200, array(
            'status' => 'ok',
            'office' => $result['office'],
            'user' => $result['user'],
            'computed' => $result['computed'],
            'wifi' => array(
                'has_rules' => $hasWifiRules,
                'provided' => $wifiProof !== null,
                'ok' => $hasWifiRules ? $wifiOk : true,
            ),
        ));
    }

    public function confirm()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(405, array('status' => 'error', 'message' => 'Method not allowed'));
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload)) {
            $payload = $this->input->post(NULL, TRUE);
        }

        $type = isset($payload['type']) ? strtoupper(trim($payload['type'])) : '';
        $lat = isset($payload['lat']) ? $payload['lat'] : NULL;
        $lng = isset($payload['lng']) ? $payload['lng'] : NULL;
        $accuracy = isset($payload['accuracy']) ? $payload['accuracy'] : NULL;
        $wifiProof = $this->extract_wifi_proof_from_payload($payload);

        $errors = array();
        if (!in_array($type, array('IN', 'OUT'), TRUE)) {
            $errors['type'] = 'Type must be IN or OUT.';
        }
        if (!is_numeric($lat) || $lat < -90 || $lat > 90) {
            $errors['lat'] = 'Latitude must be between -90 and 90.';
        }
        if (!is_numeric($lng) || $lng < -180 || $lng > 180) {
            $errors['lng'] = 'Longitude must be between -180 and 180.';
        }
        if (!is_numeric($accuracy) || $accuracy < 0) {
            $errors['accuracy'] = 'Accuracy must be a positive number.';
        }

        $userId = NULL;
        if (isset($_SESSION['user']['id'])) {
            $userId = (int) $_SESSION['user']['id'];
        } elseif (isset($payload['user_id']) && is_numeric($payload['user_id'])) {
            $userId = (int) $payload['user_id'];
        }

        if (!$userId) {
            $errors['user_id'] = 'User is not authenticated.';
        }

        if (!empty($errors)) {
            return $this->respond(400, array('status' => 'error', 'message' => 'Validation failed', 'errors' => $errors));
        }

        if ($this->Attendance_log_model->has_recent_log($userId, $this->cooldownSeconds)) {
            return $this->respond(429, array('status' => 'error', 'message' => 'Please wait before confirming again.'));
        }

        $ipAddress = $this->input->ip_address();
        $result = $this->attendanceeligibilityservice->evaluate($lat, $lng, $accuracy, $ipAddress, null, $wifiProof);
        if (isset($result['error'])) {
            return $this->respond(500, array('status' => 'error', 'message' => 'Active office is not configured.'));
        }

        $office = $result['office'];
        $officeRecord = $this->Office_model->get_by_id($office['id']);
        $officeForWifi = $officeRecord ?: $office;
        $hasWifiRules = $this->has_wifi_rules($officeForWifi);
        $wifiOk = $this->wifi_proof_ok($officeForWifi, $wifiProof);
        $canConfirm = $result['computed']['can_confirm'];
        $reasons = $result['computed']['reasons'];
        if ($hasWifiRules) {
            $canConfirm = $canConfirm && $wifiOk;
            if ($wifiProof === null) {
                $reasons[] = 'WIFI_REQUIRED';
            } elseif (!$wifiOk) {
                $reasons[] = 'WIFI_NOT_ALLOWED';
            }
            $reasons = array_values(array_unique($reasons));
        }

        if (!$canConfirm) {
            return $this->respond(403, array(
                'status' => 'error',
                'message' => 'Attendance confirmation requirements not met.',
                'reasons' => $reasons,
                'computed' => array_merge($result['computed'], array(
                    'can_confirm' => false,
                    'reasons' => $reasons,
                )),
            ));
        }

        $distance = $result['computed']['distance_m'];
        $method = $office['has_ip_rule'] ? 'GEOFENCE+IP' : 'GEOFENCE';
        if ($hasWifiRules) {
            $method = $office['has_ip_rule'] ? 'GEOFENCE+IP+WIFI' : 'GEOFENCE+WIFI';
        }
        $now = date('Y-m-d H:i:s');
        $userRecord = $this->get_user_record($userId);
        $schedule = $this->resolve_attendance_times($officeRecord ?: $office, $userRecord);
        $noteData = $this->build_attendance_notes($type, $now, $schedule['start'], $schedule['end']);
        $flags = $noteData['flags'];
        $flags['special_schedule'] = $schedule['source'] === 'user';

        $insertData = array(
            'user_id' => $userId,
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
            'created_at' => $now,
        );

        if (!$this->Attendance_log_model->insert($insertData)) {
            return $this->respond(500, array('status' => 'error', 'message' => 'Failed to store attendance log.'));
        }

        return $this->respond(200, array(
            'status' => 'ok',
            'message' => 'Attendance confirmed',
            'distance_m' => round($distance, 2),
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
            'type' => $type,
        ));
    }

    private function get_user_record($userId)
    {
        $sessionUser = $_SESSION['user'] ?? null;
        if (is_array($sessionUser) && isset($sessionUser['id']) && (int) $sessionUser['id'] === (int) $userId) {
            if (isset($sessionUser['attendance_response_times']) || isset($sessionUser['attendance_start_time']) || isset($sessionUser['attendance_end_time'])) {
                return $sessionUser;
            }
        }

        return $this->db->get_where('user', array('id' => (int) $userId))->row_array();
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

    private function build_attendance_notes($type, $timestamp, $startTime, $endTime)
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

        return array(
            'notes' => $notes,
            'flags' => $flags,
            'minutes' => $minutes,
        );
    }

    private function build_office_override()
    {
        $officeLat = $this->input->get('office_lat', TRUE);
        $officeLng = $this->input->get('office_lng', TRUE);
        $radius = $this->input->get('office_radius_m', TRUE);
        $minAccuracy = $this->input->get('office_min_accuracy_m', TRUE);
        $officeName = $this->input->get('office_name', TRUE);
        $allowedCidrs = $this->input->get('allowed_ip_cidrs', FALSE);
        $allowedBssids = $this->input->get('allowed_bssids', FALSE);
        $allowedSsids = $this->input->get('allowed_ssids', FALSE);

        if ($officeLat === NULL && $officeLng === NULL && $radius === NULL && $minAccuracy === NULL && $officeName === NULL && $allowedCidrs === NULL && $allowedBssids === NULL && $allowedSsids === NULL) {
            return null;
        }

        if (!is_numeric($officeLat) || $officeLat < -90 || $officeLat > 90) {
            return array('error' => 'Invalid office latitude.');
        }
        if (!is_numeric($officeLng) || $officeLng < -180 || $officeLng > 180) {
            return array('error' => 'Invalid office longitude.');
        }
        if (!is_numeric($radius) || (int) $radius < 10 || (int) $radius > 5000) {
            return array('error' => 'Invalid office radius.');
        }
        if (!is_numeric($minAccuracy) || (int) $minAccuracy < 5 || (int) $minAccuracy > 500) {
            return array('error' => 'Invalid office minimum accuracy.');
        }

        $normalizedCidrs = '';
        if ($allowedCidrs !== NULL && trim($allowedCidrs) !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $allowedCidrs);
            $normalized = array();
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (!preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $line)) {
                    return array('error' => 'Invalid CIDR format in allowed IP list.');
                }
                $normalized[] = $line;
            }
            $normalizedCidrs = implode("\n", $normalized);
        }

        return array(
            'id' => null,
            'name' => $officeName !== '' ? $officeName : 'Preview Office',
            'lat' => (float) $officeLat,
            'lng' => (float) $officeLng,
            'radius_m' => (int) $radius,
            'min_accuracy_m' => (int) $minAccuracy,
            'allowed_ip_cidrs' => $normalizedCidrs,
            'allowed_bssids' => trim((string) $allowedBssids),
            'allowed_ssids' => trim((string) $allowedSsids),
        );
    }

    private function extract_wifi_proof_from_payload($payload)
    {
        if (!is_array($payload)) {
            return null;
        }

        if (array_key_exists('wifiProof', $payload)) {
            return $this->normalize_wifi_proof($payload['wifiProof']);
        }

        $proof = array();
        if (isset($payload['bssid'])) {
            $proof['bssid'] = $payload['bssid'];
        }
        if (isset($payload['ssid'])) {
            $proof['ssid'] = $payload['ssid'];
        }
        if (isset($payload['bssids'])) {
            $proof['bssids'] = is_array($payload['bssids']) ? $payload['bssids'] : $this->parse_csv_list($payload['bssids']);
        }
        if (isset($payload['ssids'])) {
            $proof['ssids'] = is_array($payload['ssids']) ? $payload['ssids'] : $this->parse_csv_list($payload['ssids']);
        }

        return empty($proof) ? null : $this->normalize_wifi_proof($proof);
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

    private function parse_allowed_bssids($text)
    {
        return $this->parse_csv_list($text);
    }

    private function parse_allowed_ssids($text)
    {
        return $this->parse_csv_list($text);
    }

    private function respond($statusCode, $payload)
    {
        return $this->output
            ->set_status_header($statusCode)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
