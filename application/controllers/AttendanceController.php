<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AttendanceController extends CI_Controller
{
    private $cooldownSeconds = 60;

    public function __construct()
    {
        parent::__construct();
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

        $ipAddress = $this->input->ip_address();
        $result = $this->attendanceeligibilityservice->evaluate($lat, $lng, $accuracy, $ipAddress, $officeOverride ?: null);
        if (isset($result['error'])) {
            return $this->respond(500, array('status' => 'error', 'message' => 'Active office is not configured.'));
        }

        return $this->respond(200, array(
            'status' => 'ok',
            'office' => $result['office'],
            'user' => $result['user'],
            'computed' => $result['computed'],
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
        $result = $this->attendanceeligibilityservice->evaluate($lat, $lng, $accuracy, $ipAddress);
        if (isset($result['error'])) {
            return $this->respond(500, array('status' => 'error', 'message' => 'Active office is not configured.'));
        }

        if (!$result['computed']['can_confirm']) {
            return $this->respond(403, array(
                'status' => 'error',
                'message' => 'Attendance confirmation requirements not met.',
                'reasons' => $result['computed']['reasons'],
                'computed' => $result['computed'],
            ));
        }

        $office = $result['office'];
        $distance = $result['computed']['distance_m'];
        $method = $office['has_ip_rule'] ? 'GEOFENCE+IP' : 'GEOFENCE';

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
            'created_at' => date('Y-m-d H:i:s'),
        );

        if (!$this->Attendance_log_model->insert($insertData)) {
            return $this->respond(500, array('status' => 'error', 'message' => 'Failed to store attendance log.'));
        }

        return $this->respond(200, array(
            'status' => 'ok',
            'message' => 'Attendance confirmed',
            'distance_m' => round($distance, 2),
            'office' => array(
                'id' => (int) $office['id'],
                'name' => $office['name'],
            ),
            'type' => $type,
        ));
    }

    private function build_office_override()
    {
        $officeLat = $this->input->get('office_lat', TRUE);
        $officeLng = $this->input->get('office_lng', TRUE);
        $radius = $this->input->get('office_radius_m', TRUE);
        $minAccuracy = $this->input->get('office_min_accuracy_m', TRUE);
        $officeName = $this->input->get('office_name', TRUE);
        $allowedCidrs = $this->input->get('allowed_ip_cidrs', FALSE);

        if ($officeLat === NULL && $officeLng === NULL && $radius === NULL && $minAccuracy === NULL && $officeName === NULL && $allowedCidrs === NULL) {
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
        );
    }

    private function respond($statusCode, $payload)
    {
        return $this->output
            ->set_status_header($statusCode)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
