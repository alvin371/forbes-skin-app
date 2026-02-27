<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Docs extends CI_Controller
{
    public function hrms()
    {
        $this->load->view('public/hrms_swagger');
    }

    public function hrms_openapi()
    {
        $path = FCPATH . 'docs/openapi/hrms.yaml';
        if (!is_file($path)) {
            show_404();
            return;
        }

        $this->output
            ->set_content_type('application/yaml')
            ->set_output(file_get_contents($path));
    }
}
