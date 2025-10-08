<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Home extends BaseController
{
    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        // Set public methods (no permission required)
        $this->set_public_methods(['index']);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([]);
    }

    public function index()
    {
        return redirect(base_url() . 'auth/login');
    }
}
