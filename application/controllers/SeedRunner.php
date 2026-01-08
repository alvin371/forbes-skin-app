<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Seed.php';

class SeedRunner extends Seed
{
    private $allowed_ips = array('127.0.0.1', '::1');

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $results = array(
            'attendance_office' => $this->seed_attendance_office(),
            'leave_types' => $this->seed_leave_types(),
        );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'results' => $results,
            )));
    }

    public function attendance_office()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => $this->seed_attendance_office(),
            )));
    }

    public function leave_types()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => $this->seed_leave_types(),
            )));
    }

    private function ensure_allowed()
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            show_404();
            return false;
        }

        if (is_cli()) {
            return true;
        }

        $ip = $this->input->ip_address();
        if (!in_array($ip, $this->allowed_ips, true)) {
            show_404();
            return false;
        }

        return true;
    }
}
