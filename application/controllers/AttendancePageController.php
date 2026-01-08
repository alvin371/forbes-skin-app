<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class AttendancePageController extends BaseController
{
    protected $public_methods = ['index'];

    public function __construct()
    {
        parent::__construct();
        $this->load->library('template');
    }

    public function index()
    {
        $data['title'] = 'Attendance Confirmation - ' . $this->template->title();
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('attendance/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }
}
