<?php
defined('BASEPATH') or exit('No direct script access allowed');

class RequestNoGenerator
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function generate_unique()
    {
        $date = date('Ymd');

        for ($i = 0; $i < 5; $i++) {
            $suffix = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $requestNo = 'LV-' . $date . '-' . $suffix;

            $exists = $this->CI->db->get_where('leave_requests', array(
                'request_no' => $requestNo,
            ))->row_array();

            if (!$exists) {
                return $requestNo;
            }
        }

        return 'LV-' . $date . '-' . uniqid();
    }
}
