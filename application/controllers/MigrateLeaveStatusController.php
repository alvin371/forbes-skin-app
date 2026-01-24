<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MigrateLeaveStatusController extends CI_Controller
{
    public function submitted_to_pending()
    {
        $this->load->database();

        header('Content-Type: text/plain');

        echo "Migrating SUBMITTED leave requests to PENDING_APPROVAL...\n\n";

        $this->db->where('status', 'SUBMITTED');
        $submitted = $this->db->get('leave_requests')->result_array();

        if (empty($submitted)) {
            echo "No SUBMITTED requests found.\n";
            echo "\nDone!\n";
            return;
        }

        echo "Found " . count($submitted) . " SUBMITTED request(s):\n";
        foreach ($submitted as $req) {
            echo "- ID: {$req['id']}, Request: {$req['request_no']}, User: {$req['user_id']}\n";
        }

        echo "\nUpdating to PENDING_APPROVAL...\n";
        $this->db->where('status', 'SUBMITTED');
        $this->db->update('leave_requests', array(
            'status' => 'PENDING_APPROVAL',
            'current_step' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        echo "Updated " . $this->db->affected_rows() . " request(s).\n";
        echo "\nDone!\n";
    }
}
