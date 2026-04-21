<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('hrms_attachment_url')) {
    function project_hrms_upload_type_map()
    {
        return array(
            'leave' => 'writable/uploads/leaves/',
            'overtime' => 'writable/uploads/overtime/',
            'attendance' => 'writable/uploads/attendance/',
            'profile' => 'writable/uploads/profile/',
        );
    }

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
        foreach (project_hrms_upload_type_map() as $type => $prefix) {
            if (strpos($normalizedPath, $prefix) !== 0) {
                continue;
            }

            $suffix = substr($normalizedPath, strlen($prefix));
            return 'api/hrms/files/' . rawurlencode($type) . '/' . str_replace('%2F', '/', rawurlencode($suffix));
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

        $publicPath = project_uploaded_file_path($path);
        if ($publicPath === '' || preg_match('/^https?:\/\//i', $publicPath) === 1) {
            return $publicPath;
        }

        return rtrim(base_url(), '/') . '/' . ltrim($publicPath, '/');
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

    function project_user_avatar_basename($value, $userId = 0)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $value) === 1) {
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

        $validate = static function ($filename) use ($userId) {
            $filename = trim((string) $filename);
            if ($filename === '' || $filename !== basename($filename)) {
                return '';
            }

            if (!preg_match('/^[A-Za-z0-9._-]+\.(jpg|jpeg|png)$/i', $filename)) {
                return '';
            }

            if ((int) $userId > 0 && !preg_match('/^' . preg_quote((string) $userId, '/') . '\.(jpg|jpeg|png)$/i', $filename)) {
                return '';
            }

            return $filename;
        };

        if ($value === basename($value)) {
            return $validate($value);
        }

        $markers = array(
            'writable/uploads/profile/',
            'api/hrms/files/profile/',
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

            return $validate($suffix);
        }

        return '';
    }

    function project_user_avatar_url($value, $version = '', $defaultUrl = '')
    {
        $value = trim((string) $value);
        if ($value === '') {
            return (string) $defaultUrl;
        }

        if (preg_match('/^https?:\/\//i', $value) === 1) {
            return $value;
        }

        $basename = project_user_avatar_basename($value);
        if ($basename === '') {
            return (string) $defaultUrl;
        }

        $version = trim((string) $version);
        $token = $version !== '' ? '?token=' . DATE('Ymdhis', strtotime($version)) : '';

        $writablePath = 'writable/uploads/profile/' . $basename;
        if (is_file(project_storage_path($writablePath))) {
            return project_uploaded_file_url($writablePath) . $token;
        }

        $legacyPath = 'assets/img/user/' . $basename;
        if (is_file(project_storage_path($legacyPath))) {
            return base_url($legacyPath) . $token;
        }

        return (string) $defaultUrl;
    }
}
