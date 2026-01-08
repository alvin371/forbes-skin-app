<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AttendanceEligibilityService
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Office_model');
        $this->CI->load->helper('attendance');
    }

    public function evaluate($userLat, $userLng, $accuracy, $ipAddress, $officeOverride = null)
    {
        $office = $officeOverride ?: $this->CI->Office_model->get_active_office();
        if (!$office) {
            return array('error' => 'NO_ACTIVE_OFFICE');
        }

        $distance = haversine_meters($userLat, $userLng, $office['lat'], $office['lng']);
        $accuracyOk = (float) $accuracy <= (int) $office['min_accuracy_m'];
        $insideRadius = (float) $distance <= (int) $office['radius_m'];

        $allowedCidrs = trim((string) $office['allowed_ip_cidrs']);
        $hasIpRule = $allowedCidrs !== '';
        $ipOk = true;
        if ($hasIpRule) {
            $ipOk = ip_in_cidrs($ipAddress, $allowedCidrs);
        }

        $reasons = array();
        if (!$insideRadius) {
            $reasons[] = 'OUTSIDE_RADIUS';
        }
        if (!$accuracyOk) {
            $reasons[] = 'ACCURACY_TOO_LOW';
        }
        if ($hasIpRule && !$ipOk) {
            $reasons[] = 'IP_NOT_ALLOWED';
        }

        $canConfirm = $insideRadius && $accuracyOk && (!$hasIpRule || $ipOk);

        return array(
            'office' => array(
                'id' => isset($office['id']) ? (int) $office['id'] : null,
                'name' => $office['name'],
                'lat' => (float) $office['lat'],
                'lng' => (float) $office['lng'],
                'radius_m' => (int) $office['radius_m'],
                'min_accuracy_m' => (int) $office['min_accuracy_m'],
                'has_ip_rule' => $hasIpRule,
            ),
            'user' => array(
                'lat' => (float) $userLat,
                'lng' => (float) $userLng,
                'accuracy' => (float) $accuracy,
                'ip' => $ipAddress,
            ),
            'computed' => array(
                'distance_m' => (float) $distance,
                'inside_radius' => $insideRadius,
                'accuracy_ok' => $accuracyOk,
                'ip_ok' => $ipOk,
                'can_confirm' => $canConfirm,
                'reasons' => $reasons,
            ),
        );
    }
}
