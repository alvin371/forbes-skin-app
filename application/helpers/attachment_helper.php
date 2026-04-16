<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('hrms_attachment_url')) {
    function project_root_path($path = '')
    {
        $projectRoot = rtrim(dirname(rtrim(APPPATH, '/\\')), '/\\') . DIRECTORY_SEPARATOR;
        $path = ltrim((string) $path, '/\\');
        if ($path === '') {
            return $projectRoot;
        }

        return $projectRoot . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
    }

    function project_storage_path($path)
    {
        $normalizedPath = ltrim((string) $path, '/\\');
        if ($normalizedPath === '') {
            return FCPATH;
        }

        if (strpos($normalizedPath, 'writable/') === 0) {
            return project_root_path($normalizedPath);
        }

        return FCPATH . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $normalizedPath);
    }

    function project_uploaded_file_path($path)
    {
        $path = trim((string) $path);
        if ($path === '' || preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        $normalizedPath = ltrim($path, '/');

        if (strpos($normalizedPath, 'writable/uploads/leaves/') === 0) {
            return 'api/hrms/files/leaves/' . substr($normalizedPath, strlen('writable/uploads/leaves/'));
        }

        if (strpos($normalizedPath, 'writable/uploads/overtime/') === 0) {
            return 'api/hrms/files/overtime/' . substr($normalizedPath, strlen('writable/uploads/overtime/'));
        }

        if (strpos($normalizedPath, 'writable/uploads/attendance/') === 0) {
            return 'api/hrms/files/attendance/' . substr($normalizedPath, strlen('writable/uploads/attendance/'));
        }

        return $normalizedPath;
    }

    function project_uploaded_file_url($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        return base_url(project_uploaded_file_path($path));
    }

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
        return project_uploaded_file_url($path);
    }
}
