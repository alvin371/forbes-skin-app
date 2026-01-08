<?php
defined('BASEPATH') or exit('No direct script access allowed');

class LeaveOverlapService
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function has_overlap($userId, $startDate, $endDate, $ignoreRequestId = null)
    {
        $this->CI->db->from('leave_requests');
        $this->CI->db->where('user_id', (int) $userId);
        $this->CI->db->where_in('status', array('PENDING_APPROVAL', 'APPROVED'));
        $this->CI->db->where('start_date <=', $endDate);
        $this->CI->db->where('end_date >=', $startDate);

        if (!empty($ignoreRequestId)) {
            $this->CI->db->where('id !=', (int) $ignoreRequestId);
        }

        return $this->CI->db->count_all_results() > 0;
    }
}
