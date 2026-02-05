<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SchemaBootstrap extends CI_Controller
{
    private $allowed_ips = array('127.0.0.1', '::1');

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->dbforge();
    }

    public function hr()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $results = array();

        if (!$this->db->table_exists('offices')) {
            $this->create_offices_table();
            $results[] = 'offices created';
        } else {
            $results[] = 'offices exists';
        }

        if (!$this->db->table_exists('attendance_logs')) {
            $this->create_attendance_logs_table();
            $results[] = 'attendance_logs created';
        } else {
            $results[] = 'attendance_logs exists';
        }

        if ($this->db->table_exists('attendance_logs')) {
            if (!$this->db->field_exists('notes', 'attendance_logs')) {
                $this->db->query("ALTER TABLE attendance_logs ADD COLUMN notes TEXT NULL");
                $results[] = 'attendance_logs.notes added';
            }

            if (!$this->db->field_exists('special_schedule', 'attendance_logs')) {
                $this->db->query("ALTER TABLE attendance_logs ADD COLUMN special_schedule TINYINT(1) NOT NULL DEFAULT 0");
                $results[] = 'attendance_logs.special_schedule added';
            }
        }

        if ($this->db->table_exists('offices')) {
            if (!$this->db->field_exists('is_active', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 0");
                $results[] = 'offices.is_active added';
            }

            if (!$this->db->field_exists('allowed_bssids', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN allowed_bssids TEXT NULL");
                $results[] = 'offices.allowed_bssids added';
            }

            if (!$this->db->field_exists('allowed_ssids', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN allowed_ssids TEXT NULL");
                $results[] = 'offices.allowed_ssids added';
            }

            if (!$this->db->field_exists('attendance_response_times', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN attendance_response_times TEXT NULL");
                $results[] = 'offices.attendance_response_times added';
            }

            if (!$this->db->field_exists('attendance_history_days', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN attendance_history_days INT NOT NULL DEFAULT 30");
                $results[] = 'offices.attendance_history_days added';
            }

            if (!$this->db->field_exists('attendance_recap_months', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN attendance_recap_months INT NOT NULL DEFAULT 6");
                $results[] = 'offices.attendance_recap_months added';
            }

            $index = $this->db->query("SHOW INDEX FROM offices WHERE Key_name = 'idx_offices_is_active'")->result_array();
            if (empty($index)) {
                $this->db->query("CREATE INDEX idx_offices_is_active ON offices (is_active)");
                $results[] = 'offices idx_offices_is_active added';
            }
        }

        if (!$this->db->table_exists('leave_types')) {
            $this->create_leave_types_table();
            $results[] = 'leave_types created';
        } else {
            $results[] = 'leave_types exists';
        }

        if ($this->db->table_exists('leave_types')) {
            if (!$this->db->field_exists('default_quota_days', 'leave_types')) {
                $this->db->query("ALTER TABLE leave_types ADD COLUMN default_quota_days INT NULL");
                $results[] = 'leave_types.default_quota_days added';
            }
        }

        if (!$this->db->table_exists('approval_routes')) {
            $this->create_approval_routes_table();
            $results[] = 'approval_routes created';
        } else {
            $results[] = 'approval_routes exists';
        }

        if (!$this->db->table_exists('leave_requests')) {
            $this->create_leave_requests_table();
            $results[] = 'leave_requests created';
        } else {
            $results[] = 'leave_requests exists';
        }

        if (!$this->db->table_exists('leave_approvals')) {
            $this->create_leave_approvals_table();
            $results[] = 'leave_approvals created';
        } else {
            $results[] = 'leave_approvals exists';
        }

        if (!$this->db->table_exists('holidays')) {
            $this->create_holidays_table();
            $results[] = 'holidays created';
        } else {
            $results[] = 'holidays exists';
        }

        if (!$this->db->table_exists('attendance_settings')) {
            $this->create_attendance_settings_table();
            $results[] = 'attendance_settings created';
        } else {
            $results[] = 'attendance_settings exists';
        }

        if (!$this->db->table_exists('api_refresh_tokens')) {
            $this->create_api_refresh_tokens_table();
            $results[] = 'api_refresh_tokens created';
        } else {
            $results[] = 'api_refresh_tokens exists';
        }

        if (!$this->db->table_exists('user_pins')) {
            $this->create_user_pins_table();
            $results[] = 'user_pins created';
        } else {
            $results[] = 'user_pins exists';
        }

        if (!$this->db->table_exists('leave_quotas')) {
            $this->create_leave_quotas_table();
            $results[] = 'leave_quotas created';
        } else {
            $results[] = 'leave_quotas exists';
        }

        if (!$this->db->table_exists('holidays')) {
            $this->create_holidays_table();
            $results[] = 'holidays created';
        } else {
            $results[] = 'holidays exists';
        }

        if (!$this->db->table_exists('attendance_settings')) {
            $this->create_attendance_settings_table();
            $results[] = 'attendance_settings created';
        } else {
            $results[] = 'attendance_settings exists';
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'results' => $results,
            )));
    }

    public function hrms_api()
    {
        if (!$this->ensure_allowed()) {
            return;
        }

        $results = array();

        if ($this->db->table_exists('offices')) {
            if (!$this->db->field_exists('allowed_ssids', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN allowed_ssids TEXT NULL");
                $results[] = 'offices.allowed_ssids added';
            }

            if (!$this->db->field_exists('attendance_response_times', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN attendance_response_times TEXT NULL");
                $results[] = 'offices.attendance_response_times added';
            }

            if (!$this->db->field_exists('attendance_history_days', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN attendance_history_days INT NOT NULL DEFAULT 30");
                $results[] = 'offices.attendance_history_days added';
            }

            if (!$this->db->field_exists('attendance_recap_months', 'offices')) {
                $this->db->query("ALTER TABLE offices ADD COLUMN attendance_recap_months INT NOT NULL DEFAULT 6");
                $results[] = 'offices.attendance_recap_months added';
            }
        }

        if (!$this->db->table_exists('api_refresh_tokens')) {
            $this->create_api_refresh_tokens_table();
            $results[] = 'api_refresh_tokens created';
        } else {
            $results[] = 'api_refresh_tokens exists';
        }

        if (!$this->db->table_exists('user_pins')) {
            $this->create_user_pins_table();
            $results[] = 'user_pins created';
        } else {
            $results[] = 'user_pins exists';
        }

        if (!$this->db->table_exists('leave_quotas')) {
            $this->create_leave_quotas_table();
            $results[] = 'leave_quotas created';
        } else {
            $results[] = 'leave_quotas exists';
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'results' => $results,
            )));
    }

    private function create_offices_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'lat' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,7',
            ),
            'lng' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,7',
            ),
            'radius_m' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 150,
            ),
            'min_accuracy_m' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 50,
            ),
            'allowed_ip_cidrs' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'allowed_ssids' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'attendance_response_times' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'attendance_history_days' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 30,
            ),
            'attendance_recap_months' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 6,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('offices', TRUE);
    }

    private function create_attendance_logs_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'office_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'type' => array(
                'type' => 'ENUM',
                'constraint' => array('IN', 'OUT'),
            ),
            'lat' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,7',
            ),
            'lng' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,7',
            ),
            'accuracy' => array(
                'type' => 'FLOAT',
            ),
            'distance_m' => array(
                'type' => 'FLOAT',
            ),
            'method' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
            ),
            'ip_address' => array(
                'type' => 'VARCHAR',
                'constraint' => 45,
            ),
            'user_agent' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'special_schedule' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('office_id');
        $this->dbforge->create_table('attendance_logs', TRUE);
    }

    private function create_leave_types_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'code' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'is_paid' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ),
            'requires_attachment' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ),
            'max_days_per_request' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
            ),
            'default_quota_days' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
            ),
            'is_active' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('code', TRUE);
        $this->dbforge->create_table('leave_types', TRUE);
    }

    private function create_approval_routes_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'approver_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'is_active' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('approver_id');
        $this->dbforge->add_key('is_active');
        $this->dbforge->create_table('approval_routes', TRUE);
    }

    private function create_leave_requests_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'request_no' => array(
                'type' => 'VARCHAR',
                'constraint' => 30,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'leave_type_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'start_date' => array(
                'type' => 'DATE',
            ),
            'end_date' => array(
                'type' => 'DATE',
            ),
            'days_count' => array(
                'type' => 'INT',
                'constraint' => 11,
            ),
            'reason' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'attachment_path' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
            'status' => array(
                'type' => 'ENUM',
                'constraint' => array('SUBMITTED', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'CANCELLED'),
                'default' => 'PENDING_APPROVAL',
            ),
            'current_step' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('request_no', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('leave_type_id');
        $this->dbforge->add_key('status');
        $this->dbforge->create_table('leave_requests', TRUE);
    }

    private function create_leave_approvals_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'leave_request_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'step_no' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ),
            'approver_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'action' => array(
                'type' => 'ENUM',
                'constraint' => array('PENDING', 'APPROVED', 'REJECTED'),
                'default' => 'PENDING',
            ),
            'action_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('leave_request_id');
        $this->dbforge->add_key('approver_id');
        $this->dbforge->add_key('action');
        $this->dbforge->create_table('leave_approvals', TRUE);
    }

    private function create_api_refresh_tokens_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'token_hash' => array(
                'type' => 'VARCHAR',
                'constraint' => 128,
            ),
            'expires_at' => array(
                'type' => 'DATETIME',
            ),
            'revoked_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'user_agent' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'ip' => array(
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('token_hash');
        $this->dbforge->create_table('api_refresh_tokens', TRUE);
    }

    private function create_user_pins_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'pin_hash' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
            'salt' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
            'failed_attempts' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ),
            'locked_until' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->create_table('user_pins', TRUE);
    }

    private function create_leave_quotas_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'leave_type_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
            ),
            'total_days' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ),
            'remaining_days' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('leave_type_id');
        $this->dbforge->create_table('leave_quotas', TRUE);
    }

    private function create_holidays_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'date' => array(
                'type' => 'DATE',
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
            ),
            'is_active' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('date', TRUE);
        $this->dbforge->create_table('holidays', TRUE);
    }

    private function create_attendance_settings_table()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'weekend_type' => array(
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'SATURDAY_SUNDAY',
            ),
            'allowed_role_ids' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('attendance_settings', TRUE);
    }

    private function ensure_allowed()
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            show_404();
            return false;
        }

        if (is_cli()) {
            return true;
        }

        $ip = $this->input->ip_address();
        if (!in_array($ip, $this->allowed_ips, true)) {
            show_404();
            return false;
        }

        return true;
    }
}
