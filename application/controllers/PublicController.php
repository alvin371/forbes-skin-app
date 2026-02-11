<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PublicController extends CI_Controller
{
    public function privacy_policy()
    {
        $this->load->view('public/privacy_policy');
    }

    public function support()
    {
        $this->load->view('public/support');
    }
}
