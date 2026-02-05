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

    public function evaluate($userLat, $userLng, $accuracy, $ipAddress, $officeOverride = null, $wifiProof = null)
    {
        if ($officeOverride) {
            $office = $officeOverride;
        } else {
            $offices = $this->CI->Office_model->get_active_offices();
            $office = $this->select_office($offices, $userLat, $userLng, $wifiProof);
        }
        if (!$office) {
            return array('error' => 'NO_ACTIVE_OFFICE');
        }

        $distance = isset($office['_distance_m'])
            ? (float) $office['_distance_m']
            : haversine_meters($userLat, $userLng, $office['lat'], $office['lng']);
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

    private function select_office($offices, $userLat, $userLng, $wifiProof = null)
    {
        if (!is_array($offices) || empty($offices)) {
            return null;
        }

        $candidates = $offices;
        if ($wifiProof !== null) {
            $wifiCandidates = array();
            foreach ($offices as $office) {
                if ($this->has_wifi_rules($office) && $this->wifi_proof_ok($office, $wifiProof)) {
                    $wifiCandidates[] = $office;
                }
            }
            if (!empty($wifiCandidates)) {
                $candidates = $wifiCandidates;
            }
        }

        $nearest = null;
        $nearestDistance = null;
        foreach ($candidates as $office) {
            $distance = haversine_meters($userLat, $userLng, $office['lat'], $office['lng']);
            if ($nearest === null || $distance < $nearestDistance) {
                $nearest = $office;
                $nearestDistance = $distance;
            }
        }

        if ($nearest !== null) {
            $nearest['_distance_m'] = $nearestDistance;
        }

        return $nearest;
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
}
