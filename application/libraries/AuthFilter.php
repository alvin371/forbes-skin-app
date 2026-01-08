<?php
defined('BASEPATH') or exit('No direct script access allowed');

class AuthFilter
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function enforce()
    {
        if ($this->is_logged_in()) {
            return;
        }

        redirect(base_url('auth/login'));
        exit;
    }

    private function is_logged_in()
    {
        if (isset($_SESSION['is_login']) && $_SESSION['is_login']) {
            return true;
        }

        if (!empty($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            return true;
        }

        return false;
    }
}
