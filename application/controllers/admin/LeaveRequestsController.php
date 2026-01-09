<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class LeaveRequestsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('LeaveRequestModel');
        $this->load->database();
        $this->load->library('template');
        $this->load->library('AdminAuthFilter');
        $this->adminauthfilter->enforce();
    }

    public function index()
    {
        $data['title'] = 'All Leave Requests - ' . $this->template->title();
        $data['requests'] = $this->LeaveRequestModel->get_all_with_details();
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/leave_requests/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function detail($id)
    {
        $request = $this->LeaveRequestModel->get_by_id($id);
        if (!$request) {
            show_404();
            return;
        }

        $this->db->select('la.*, u.full_name as approver_name, u.email as approver_email');
        $this->db->from('leave_approvals la');
        $this->db->join('user u', 'u.id = la.approver_id', 'left');
        $this->db->where('la.leave_request_id', (int) $id);
        $this->db->order_by('la.step_no', 'ASC');
        $approvals = $this->db->get()->result_array();

        $data['title'] = 'Leave Request Detail - ' . $this->template->title();
        $data['request'] = $request;
        $data['approvals'] = $approvals;
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['content'] = $this->load->view('admin/leave_requests/detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }
}
