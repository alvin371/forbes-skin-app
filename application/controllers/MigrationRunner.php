<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MigrationRunner extends CI_Controller
{
    private $allowed_ips = array('127.0.0.1', '::1');

    public function __construct()
    {
        parent::__construct();
    }

    public function latest()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $this->config->set_item('migration_enabled', TRUE);
        $this->load->library('migration');

        if ($this->migration->latest() === FALSE) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => $this->migration->error_string(),
                )));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Migrations applied.',
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
