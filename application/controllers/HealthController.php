<?php

defined('BASEPATH') || exit('No direct script access allowed');

class HealthController extends CI_Controller
{
    public function index()
    {
        return $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'ok' => true,
            ]));
    }
}
