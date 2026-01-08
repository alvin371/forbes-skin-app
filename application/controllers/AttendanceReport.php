<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class AttendanceReport extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('template');
        $this->load->model('AttendanceSettingsModel');
        $this->load->model('HolidayModel');
        $this->load->model('Attendance_log_model');
        $this->load->model('Office_model');
        $this->load->model('LeaveRequestModel');
    }

    public function index()
    {
        $month = $this->input->get('month', TRUE);
        $month = $month ?: date('Y-m');
        $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;

        $isAdminHr = $this->is_admin_hr_user($userId);
        $targetUserId = $userId;

        if ($isAdminHr && $this->input->get('user_id', TRUE)) {
            $targetUserId = (int) $this->input->get('user_id', TRUE);
        }

        $office = $this->Office_model->get_active_office();
        $report = $this->build_monthly_report($targetUserId, $month, $office);
        $summaries = array();
        if ($isAdminHr && !$this->input->get('user_id', TRUE)) {
            foreach ($this->get_attendance_users() as $member) {
                $memberReport = $this->build_monthly_report((int) $member['id'], $month, $office);
                if (!empty($memberReport['summary'])) {
                    $summaries[(int) $member['id']] = $memberReport['summary'];
                }
            }
        }

        $data['title'] = 'Attendance Report - ' . $this->template->title();
        $data['month'] = $month;
        $data['is_admin_hr'] = $isAdminHr;
        $data['report'] = $report;
        $data['summaries'] = $summaries;
        $data['users'] = $isAdminHr ? $this->get_attendance_users() : array();
        $data['target_user'] = $this->get_user($targetUserId);
        $data['selected_user_id'] = $this->input->get('user_id', TRUE) ? (int) $this->input->get('user_id', TRUE) : 0;
        $data['content'] = $this->load->view('attendance/report', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function export_pdf()
    {
        $month = $this->input->get('month', TRUE);
        $month = $month ?: date('Y-m');
        $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;

        $isAdminHr = $this->is_admin_hr_user($userId);
        $targetUserId = $userId;

        if ($isAdminHr && $this->input->get('user_id', TRUE)) {
            $targetUserId = (int) $this->input->get('user_id', TRUE);
        }

        $office = $this->Office_model->get_active_office();
        $report = $this->build_monthly_report($targetUserId, $month, $office);
        $user = $this->get_user($targetUserId);

        $data['month'] = $month;
        $data['report'] = $report;
        $data['user'] = $user;

        $html = $this->load->view('attendance/report_pdf', $data, true);
        $this->output
            ->set_content_type('text/html')
            ->set_output($html);
    }

    private function get_user($userId)
    {
        return $this->db->get_where('user', array('id' => (int) $userId))->row_array();
    }

    private function is_admin_hr_user($userId)
    {
        $userId = (int) $userId;
        try {
            $rolesTable = $this->db->query("SHOW TABLES LIKE 'roles'")->result_array();
            $userRolesTable = $this->db->query("SHOW TABLES LIKE 'user_roles'")->result_array();

            if (!empty($rolesTable) && !empty($userRolesTable)) {
                $roles = $this->db->query("
                    SELECT r.name
                    FROM user_roles ur
                    INNER JOIN roles r ON ur.role_id = r.id
                    WHERE ur.user_id = ? AND r.is_active = 1
                ", array($userId))->result_array();

                foreach ($roles as $role) {
                    $name = strtolower((string) $role['name']);
                    if (in_array($name, array('super_admin', 'admin', 'hr', 'human_resources'), true)) {
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            // fall through
        }

        $legacy = $this->db->query("SELECT role, role_text FROM user WHERE id = ? LIMIT 1", array($userId))->row_array();
        if ($legacy) {
            if (isset($legacy['role']) && in_array((string) $legacy['role'], array('1', '2', '7'), true)) {
                return true;
            }
            if (!empty($legacy['role_text'])) {
                $roleText = strtolower((string) $legacy['role_text']);
                if (strpos($roleText, 'admin') !== false || strpos($roleText, 'hr') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function get_attendance_users()
    {
        $allowed = $this->AttendanceSettingsModel->get_allowed_role_ids();
        $this->db->from('user');
        $this->db->where('status', 'Aktif');
        if (!empty($allowed)) {
            $this->db->where_in('role', $allowed);
        }
        $this->db->order_by('full_name', 'ASC');
        return $this->db->get()->result_array();
    }

    private function build_monthly_report($userId, $month, $office)
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return array('error' => 'Invalid month format.');
        }

        $startDate = $month . '-01';
        $startTs = strtotime($startDate);
        if ($startTs === false) {
            return array('error' => 'Invalid month.');
        }
        $endDate = date('Y-m-t', $startTs);

        $holidayRows = $this->HolidayModel->get_active_between($startDate, $endDate);
        $holidayLookup = array();
        foreach ($holidayRows as $holiday) {
            $holidayLookup[$holiday['date']] = $holiday['name'];
        }

        $leaveDays = $this->get_approved_leave_days($userId, $startDate, $endDate);
        $logs = $this->get_attendance_logs($userId, $startDate, $endDate);

        $times = $this->resolve_attendance_times($office);
        $startTime = $times['start'];
        $endTime = $times['end'];

        $weekendType = $this->AttendanceSettingsModel->get_settings()['weekend_type'] ?? 'SATURDAY_SUNDAY';

        $presentDays = 0;
        $lateCount = 0;
        $earlyCheckoutCount = 0;
        $absentCount = 0;
        $leaveCount = 0;
        $daily = array();

        $period = new DatePeriod(new DateTime($startDate), new DateInterval('P1D'), (new DateTime($endDate))->modify('+1 day'));
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $weekday = (int) $date->format('N');
            $isWeekend = $this->is_weekend_day($weekday, $weekendType);
            $holidayName = $holidayLookup[$day] ?? null;
            $isLeave = in_array($day, $leaveDays, true);

            $dayLogs = $logs[$day] ?? array();
            $firstIn = $dayLogs['first_in'] ?? null;
            $lastOut = $dayLogs['last_out'] ?? null;

            $hasIn = $firstIn !== null;
            $hasOut = $lastOut !== null;

            $status = 'Absent';
            $isLate = false;
            $isEarlyCheckout = false;

            if ($isWeekend) {
                $status = 'Weekend';
            } elseif ($holidayName) {
                $status = 'Holiday';
            } elseif ($isLeave) {
                $status = 'Leave';
                $leaveCount++;
            } elseif ($hasIn || $hasOut) {
                $status = 'Present';
                $presentDays++;

                if ($hasIn && $this->is_late($firstIn, $startTime)) {
                    $isLate = true;
                    $lateCount++;
                }
                if ($hasOut && $this->is_early_checkout($lastOut, $endTime)) {
                    $isEarlyCheckout = true;
                    $earlyCheckoutCount++;
                }
            } else {
                $absentCount++;
            }

            $daily[] = array(
                'date' => $day,
                'status' => $status,
                'first_in' => $firstIn,
                'last_out' => $lastOut,
                'late' => $isLate,
                'early_checkout' => $isEarlyCheckout,
                'holiday_name' => $holidayName,
            );
        }

        $summary = array(
            'month' => $month,
            'present_days' => $presentDays,
            'late_count' => $lateCount,
            'early_checkout_count' => $earlyCheckoutCount,
            'absent_count' => $absentCount,
            'leave_days' => $leaveCount,
            'start_time' => $startTime,
            'end_time' => $endTime,
        );

        return array(
            'summary' => $summary,
            'daily' => $daily,
        );
    }

    private function get_attendance_logs($userId, $startDate, $endDate)
    {
        $rows = $this->db->query("
            SELECT type, created_at
            FROM attendance_logs
            WHERE user_id = ? AND created_at >= ? AND created_at <= ?
            ORDER BY created_at ASC
        ", array($userId, $startDate . ' 00:00:00', $endDate . ' 23:59:59'))->result_array();

        $logs = array();
        foreach ($rows as $row) {
            $day = substr($row['created_at'], 0, 10);
            if (!isset($logs[$day])) {
                $logs[$day] = array(
                    'first_in' => null,
                    'last_out' => null,
                );
            }
            if ($row['type'] === 'IN') {
                if ($logs[$day]['first_in'] === null || $row['created_at'] < $logs[$day]['first_in']) {
                    $logs[$day]['first_in'] = $row['created_at'];
                }
            }
            if ($row['type'] === 'OUT') {
                if ($logs[$day]['last_out'] === null || $row['created_at'] > $logs[$day]['last_out']) {
                    $logs[$day]['last_out'] = $row['created_at'];
                }
            }
        }

        return $logs;
    }

    private function get_approved_leave_days($userId, $startDate, $endDate)
    {
        $rows = $this->db->query("
            SELECT start_date, end_date
            FROM leave_requests
            WHERE user_id = ? AND status = 'APPROVED'
            AND end_date >= ? AND start_date <= ?
        ", array($userId, $startDate, $endDate))->result_array();

        $days = array();
        foreach ($rows as $row) {
            $period = new DatePeriod(new DateTime($row['start_date']), new DateInterval('P1D'), (new DateTime($row['end_date']))->modify('+1 day'));
            foreach ($period as $date) {
                $day = $date->format('Y-m-d');
                if ($day >= $startDate && $day <= $endDate) {
                    $days[] = $day;
                }
            }
        }

        return array_values(array_unique($days));
    }

    private function resolve_attendance_times($office)
    {
        $start = '08:00';
        $end = '17:00';
        if ($office && !empty($office['attendance_response_times'])) {
            $times = $this->parse_response_times($office['attendance_response_times']);
            if (!empty($times[0])) {
                $start = $times[0];
            }
            if (!empty($times[1])) {
                $end = $times[1];
            }
        }

        return array('start' => $start, 'end' => $end);
    }

    private function parse_response_times($text)
    {
        $items = preg_split('/\r\n|\r|\n|,/', (string) $text);
        $list = array();
        foreach ($items as $item) {
            $item = trim($item);
            if ($item !== '') {
                $list[] = $item;
            }
        }
        return $list;
    }

    private function is_weekend_day($weekday, $weekendType)
    {
        if ($weekendType === 'SUNDAY_ONLY') {
            return $weekday === 7;
        }

        return $weekday === 6 || $weekday === 7;
    }

    private function is_late($timestamp, $startTime)
    {
        $start = strtotime(substr($timestamp, 0, 10) . ' ' . $startTime . ':00');
        $threshold = $start + (30 * 60);
        return strtotime($timestamp) > $threshold;
    }

    private function is_early_checkout($timestamp, $endTime)
    {
        $end = strtotime(substr($timestamp, 0, 10) . ' ' . $endTime . ':00');
        $threshold = $end - (30 * 60);
        return strtotime($timestamp) < $threshold;
    }
}
