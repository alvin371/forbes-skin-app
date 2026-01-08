<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ApproverAuthFilter
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function enforce()
    {
        $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        if (!$userId) {
            redirect(base_url('auth/login'));
            return;
        }

        $exists = $this->CI->db->get_where('approval_routes', array(
            'approver_id' => $userId,
            'is_active' => 1,
        ))->row_array();

        if ($exists) {
            return;
        }

        $this->CI->output->set_status_header(403);
        $data = array(
            'heading' => 'Access Forbidden',
            'message' => 'You do not have permission to access this resource.',
        );
        $this->CI->load->view('errors/html/error_403', $data);
        exit;
    }
}
