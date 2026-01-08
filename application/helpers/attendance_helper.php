<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('haversine_meters')) {
    function haversine_meters($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000;
        $lat1 = deg2rad((float) $lat1);
        $lng1 = deg2rad((float) $lng1);
        $lat2 = deg2rad((float) $lat2);
        $lng2 = deg2rad((float) $lng2);

        $latDelta = $lat2 - $lat1;
        $lngDelta = $lng2 - $lng1;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($lat1) * cos($lat2) * sin($lngDelta / 2) * sin($lngDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

if (!function_exists('parse_cidrs')) {
    function parse_cidrs($text)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text);
        $cidrs = array();
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $cidrs[] = $trimmed;
            }
        }

        return $cidrs;
    }
}

if (!function_exists('ip_in_cidrs')) {
    function ip_in_cidrs($ip, $cidrListText)
    {
        $ipLong = ip2long($ip);
        if ($ipLong === FALSE) {
            return FALSE;
        }

        $cidrs = parse_cidrs($cidrListText);
        $hasValid = FALSE;

        foreach ($cidrs as $cidr) {
            if (strpos($cidr, '/') === FALSE) {
                $cidr .= '/32';
            }

            list($subnet, $mask) = explode('/', $cidr, 2);
            $subnetLong = ip2long($subnet);
            $mask = (int) $mask;

            if ($subnetLong === FALSE || $mask < 0 || $mask > 32) {
                continue;
            }

            $hasValid = TRUE;
            $maskLong = -1 << (32 - $mask);
            $subnetNetwork = $subnetLong & $maskLong;
            $ipNetwork = $ipLong & $maskLong;

            if ($subnetNetwork === $ipNetwork) {
                return TRUE;
            }
        }

        return FALSE;
    }
}
