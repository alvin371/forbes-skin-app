<?php
defined('BASEPATH') or exit('No direct script access allowed');

class UploadService
{
    const DEFAULT_MAX_SIZE_KB = 5120;

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('attachment');
        $this->CI->load->library('upload');
    }

    public function upload($scope, $fieldName, $options = array())
    {
        $scopeConfig = $this->scope_config($scope, $options);
        if (isset($scopeConfig['error'])) {
            return array('error' => $scopeConfig['error']);
        }

        $relativeDir = trim($scopeConfig['relative_dir'], '/');
        $uploadDir = rtrim(project_storage_path($relativeDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return array('error' => 'Failed to create upload directory.');
        }

        $config = array(
            'upload_path' => $uploadDir,
            'allowed_types' => $scopeConfig['allowed_types'],
            'max_size' => $scopeConfig['max_size'],
            'encrypt_name' => !empty($scopeConfig['encrypt_name']),
            'overwrite' => !empty($scopeConfig['overwrite']),
        );

        if (!empty($scopeConfig['file_name'])) {
            $config['file_name'] = $scopeConfig['file_name'];
        }

        $this->CI->upload->initialize($config, true);
        if (!$this->CI->upload->do_upload($fieldName)) {
            return array('error' => strip_tags($this->CI->upload->display_errors('', '')));
        }

        $file = $this->CI->upload->data();
        $relativePath = $relativeDir . '/' . $file['file_name'];
        $sizeBytes = (int) round(((float) $file['file_size']) * 1024);

        return array(
            'path' => $relativePath,
            'public_path' => project_uploaded_file_path($relativePath),
            'url' => project_uploaded_file_url($relativePath),
            'filename' => $file['file_name'],
            'original_name' => $file['client_name'],
            'size_bytes' => $sizeBytes,
            'file' => $file,
        );
    }

    protected function scope_config($scope, $options)
    {
        $subdir = trim((string) ($options['subdir'] ?? ''), '/');
        $fileName = isset($options['file_name']) ? (string) $options['file_name'] : '';

        switch ($scope) {
            case 'leave':
                if ($subdir === '') {
                    return array('error' => 'Leave upload subdirectory is required.');
                }

                return array(
                    'relative_dir' => 'writable/uploads/leaves/' . $subdir,
                    'allowed_types' => 'pdf|jpg|jpeg|png',
                    'max_size' => self::DEFAULT_MAX_SIZE_KB,
                    'encrypt_name' => true,
                    'overwrite' => false,
                );

            case 'overtime':
                if ($subdir === '') {
                    return array('error' => 'Overtime upload subdirectory is required.');
                }

                return array(
                    'relative_dir' => 'writable/uploads/overtime/' . $subdir,
                    'allowed_types' => 'pdf|jpg|jpeg|png',
                    'max_size' => self::DEFAULT_MAX_SIZE_KB,
                    'encrypt_name' => true,
                    'overwrite' => false,
                );

            case 'hrms_profile':
                return array(
                    'relative_dir' => 'assets/uploads/profile',
                    'allowed_types' => 'pdf|jpg|jpeg|png',
                    'max_size' => self::DEFAULT_MAX_SIZE_KB,
                    'encrypt_name' => true,
                    'overwrite' => false,
                );

            case 'user_avatar':
                return array(
                    'relative_dir' => 'assets/img/user',
                    'allowed_types' => 'jpg|jpeg|png',
                    'max_size' => self::DEFAULT_MAX_SIZE_KB,
                    'encrypt_name' => false,
                    'overwrite' => true,
                    'file_name' => $fileName,
                );

            case 'user_ktp':
                return array(
                    'relative_dir' => 'assets/img/ktp',
                    'allowed_types' => 'jpg|jpeg|png',
                    'max_size' => self::DEFAULT_MAX_SIZE_KB,
                    'encrypt_name' => false,
                    'overwrite' => true,
                    'file_name' => $fileName,
                );
        }

        return array('error' => 'Upload scope is invalid.');
    }
}
