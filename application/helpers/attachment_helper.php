<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('hrms_attachment_url')) {
    /**
     * Convert stored attachment paths into browser-safe public URLs.
     *
     * Stored paths under writable/uploads are served through routed endpoints
     * instead of relying on direct web-server access to /writable.
     *
     * @param string|null $path
     * @return string
     */
    function hrms_attachment_url($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        $normalizedPath = ltrim($path, '/');

        if (strpos($normalizedPath, 'writable/uploads/leaves/') === 0) {
            return base_url('api/hrms/files/leaves/' . substr($normalizedPath, strlen('writable/uploads/leaves/')));
        }

        if (strpos($normalizedPath, 'writable/uploads/overtime/') === 0) {
            return base_url('api/hrms/files/overtime/' . substr($normalizedPath, strlen('writable/uploads/overtime/')));
        }

        return base_url($normalizedPath);
    }
}
