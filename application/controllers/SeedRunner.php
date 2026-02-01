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
            'holidays' => $this->seed_holidays(),
            'performance_2026' => $this->seed_performance_2026_template(),
            'approval_modules' => $this->seed_approval_modules(),
            'approval_routes' => $this->seed_approval_routes(),
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

    public function holidays()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => $this->seed_holidays(),
            )));
    }

    public function performance_2026()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => $this->seed_performance_2026_template(),
            )));
    }

    public function approval_routes()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => $this->seed_approval_routes(),
            )));
    }

    public function approval_modules()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => $this->seed_approval_modules(),
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
