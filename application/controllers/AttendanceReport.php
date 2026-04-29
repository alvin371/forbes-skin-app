<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class AttendanceReport extends BaseController
{
    private $lateGraceMinutes = 15;
    private $earlyGraceMinutes = 15;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('attachment');
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
        $selectedUserId = (int) $this->input->get('user_id', TRUE);

        $canManageReports = $this->can_manage_reports($userId);
        $targetUserId = $userId;

        if ($selectedUserId > 0) {
            if (!$canManageReports && $selectedUserId !== $userId) {
                $this->permission->show_403_if_no_permission($userId, 'attendance_report', 'edit');
            }
            $targetUserId = $selectedUserId;
        }

        $office = $this->Office_model->get_active_office();
        $targetUser = $this->get_user($targetUserId);
        $report = $this->build_monthly_report($targetUserId, $month, $office, $targetUser);
        $summaries = array();
        $matrix = array();
        if ($canManageReports && $selectedUserId === 0) {
            foreach ($this->get_attendance_users() as $member) {
                $memberReport = $this->build_monthly_report((int) $member['id'], $month, $office, $member);
                $memberId = (int) $member['id'];
                if (!empty($memberReport['summary'])) {
                    $summaries[$memberId] = $memberReport['summary'];
                }
                if (!empty($memberReport['daily'])) {
                    $matrix[$memberId] = $memberReport['daily'];
                }
            }
        }

        $data['title'] = 'Laporan Kehadiran - ' . $this->template->title();
        $data['month'] = $month;
        $data['is_admin_hr'] = $canManageReports;
        $data['report'] = $report;
        $data['summaries'] = $summaries;
        $data['matrix'] = $matrix;
        $data['users'] = $canManageReports ? $this->get_attendance_users() : array();
        $data['target_user'] = $targetUser;
        $data['selected_user_id'] = $selectedUserId;
        $data['content'] = $this->load->view('attendance/report', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function data_json()
    {
        $month = $this->input->get('month', TRUE);
        $month = $month ?: date('Y-m');
        $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        $selectedUserId = (int) $this->input->get('user_id', TRUE);

        $canManageReports = $this->can_manage_reports($userId);
        $targetUserId = $userId;

        if ($selectedUserId > 0) {
            if (!$canManageReports && $selectedUserId !== $userId) {
                return $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('status' => 'error', 'message' => 'Access denied.')));
            }
            $targetUserId = $selectedUserId;
        }

        $office = $this->Office_model->get_active_office();
        $targetUser = $this->get_user($targetUserId);
        $report = $this->build_monthly_report($targetUserId, $month, $office, $targetUser);

        $summaries = array();
        $matrix = array();
        $users = array();
        if ($canManageReports && $selectedUserId === 0) {
            $users = $this->get_attendance_users();
            foreach ($users as $member) {
                $memberReport = $this->build_monthly_report((int) $member['id'], $month, $office, $member);
                $memberId = (int) $member['id'];
                if (!empty($memberReport['summary'])) {
                    $summaries[$memberId] = $memberReport['summary'];
                }
                if (!empty($memberReport['daily'])) {
                    $matrix[$memberId] = $memberReport['daily'];
                }
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status' => 'ok',
                'month' => $month,
                'isAdminHr' => $canManageReports,
                'selectedUserId' => $selectedUserId,
                'users' => $users,
                'targetUser' => $targetUser,
                'singleReport' => $report,
                'summaries' => $summaries,
                'matrix' => $matrix,
            ), JSON_UNESCAPED_SLASHES));
    }

    public function export_pdf()
    {
        $month = $this->input->get('month', TRUE);
        $month = $month ?: date('Y-m');
        $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        $selectedUserId = (int) $this->input->get('user_id', TRUE);

        $canManageReports = $this->can_manage_reports($userId);
        $targetUserId = $userId;

        if ($selectedUserId > 0) {
            if (!$canManageReports && $selectedUserId !== $userId) {
                $this->permission->show_403_if_no_permission($userId, 'attendance_report', 'edit');
            }
            $targetUserId = $selectedUserId;
        }

        $office = $this->Office_model->get_active_office();
        $user = $this->get_user($targetUserId);
        $report = $this->build_monthly_report($targetUserId, $month, $office, $user);

        $data['month'] = $month;
        $data['report'] = $report;
        $data['user'] = $user;

        $html = $this->load->view('attendance/report_pdf', $data, true);
        $this->output
            ->set_content_type('text/html')
            ->set_output($html);
    }

    public function set_user_schedule()
    {
        $currentUserId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        if (!$this->permission->check_permission($currentUserId, 'attendance_report', 'edit')) {
            return $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(array('status' => 'error', 'message' => 'Access denied.')));
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload)) {
            $payload = $this->input->post(NULL, TRUE) ?: array();
        }

        $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : 0;
        if (!$userId) {
            return $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(array('status' => 'error', 'message' => 'Invalid user_id.')));
        }

        $startTime = trim((string) ($payload['start_time'] ?? ''));
        $endTime   = trim((string) ($payload['end_time'] ?? ''));

        $isReset = ($startTime === '' && $endTime === '');

        if (!$isReset) {
            if ($startTime !== '' && !preg_match('/^\d{2}:\d{2}$/', $startTime)) {
                return $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('status' => 'error', 'message' => 'Invalid start_time format. Expected HH:MM.')));
            }
            if ($endTime !== '' && !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                return $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('status' => 'error', 'message' => 'Invalid end_time format. Expected HH:MM.')));
            }
        }

        $updateData = array(
            'attendance_start_time' => $isReset ? null : ($startTime !== '' ? $startTime : null),
            'attendance_end_time'   => $isReset ? null : ($endTime !== '' ? $endTime : null),
        );

        $this->db->where('id', $userId)->update('user', $updateData);

        $effectiveStart = $updateData['attendance_start_time'] ?? '08:00';
        $effectiveEnd   = $updateData['attendance_end_time']   ?? '17:00';

        return $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'     => 'ok',
                'start_time' => $effectiveStart,
                'end_time'   => $effectiveEnd,
                'is_special' => !$isReset && ($startTime !== '' || $endTime !== ''),
            )));
    }

    private function get_user($userId)
    {
        return $this->db->get_where('user', array('id' => (int) $userId))->row_array();
    }

    private function can_manage_reports($userId)
    {
        return $this->permission->check_permission((int) $userId, 'attendance_report', 'edit');
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

    private function build_monthly_report($userId, $month, $office, $user = null)
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

        $times = $this->resolve_attendance_times($office, $user);
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

            $notes = $this->build_daily_notes($firstIn, $lastOut, $startTime, $endTime, $isLate, $isEarlyCheckout, $dayLogs);
            $firstInCategory = $this->attendance_category_value($dayLogs['first_in_category'] ?? null);
            $lastOutCategory = $this->attendance_category_value($dayLogs['last_out_category'] ?? null);
            $firstInOutOfTown = $this->is_out_of_town_attendance($firstInCategory);
            $lastOutOutOfTown = $this->is_out_of_town_attendance($lastOutCategory);

            $daily[] = array(
                'date' => $day,
                'status' => $status,
                'first_in' => $firstIn,
                'last_out' => $lastOut,
                'first_in_category' => $firstInCategory,
                'last_out_category' => $lastOutCategory,
                'first_in_category_label' => $this->attendance_category_label($firstInCategory),
                'last_out_category_label' => $this->attendance_category_label($lastOutCategory),
                'first_in_is_out_of_town' => $firstInOutOfTown,
                'last_out_is_out_of_town' => $lastOutOutOfTown,
                'late' => $isLate,
                'early_checkout' => $isEarlyCheckout,
                'late_reason' => $isLate ? ($dayLogs['first_in_reason'] ?? null) : null,
                'late_attachment_path' => ($isLate && !$firstInOutOfTown) ? $this->public_attachment_url($dayLogs['first_in_attachment_path'] ?? null) : null,
                'early_checkout_reason' => $isEarlyCheckout ? ($dayLogs['last_out_reason'] ?? null) : null,
                'early_checkout_attachment_path' => ($isEarlyCheckout && !$lastOutOutOfTown) ? $this->public_attachment_url($dayLogs['last_out_attachment_path'] ?? null) : null,
                'first_in_proof_path' => $firstInOutOfTown ? $this->public_attachment_url($dayLogs['first_in_attachment_path'] ?? null) : null,
                'last_out_proof_path' => $lastOutOutOfTown ? $this->public_attachment_url($dayLogs['last_out_attachment_path'] ?? null) : null,
                'out_of_town' => $firstInOutOfTown || $lastOutOutOfTown,
                'holiday_name' => $holidayName,
                'notes' => $notes,
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
            'schedule_source' => $times['source'],
            'special_schedule' => $times['source'] === 'user',
        );

        return array(
            'summary' => $summary,
            'daily' => $daily,
        );
    }

    private function get_attendance_logs($userId, $startDate, $endDate)
    {
        $rows = $this->db->query("
            SELECT type, created_at, attendance_reason, attachment_path, attendance_category
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
                    'first_in_category' => 'REGULAR',
                    'last_out_category' => 'REGULAR',
                    'first_in_reason' => null,
                    'last_out_reason' => null,
                    'first_in_attachment_path' => null,
                    'last_out_attachment_path' => null,
                );
            }
            if (!$this->is_attendance_log_eligible($row)) {
                continue;
            }
            if ($row['type'] === 'IN') {
                if ($logs[$day]['first_in'] === null || $row['created_at'] < $logs[$day]['first_in']) {
                    $logs[$day]['first_in'] = $row['created_at'];
                    $logs[$day]['first_in_category'] = $this->attendance_category_value($row['attendance_category'] ?? null);
                    $logs[$day]['first_in_reason'] = $row['attendance_reason'] ?? null;
                    $logs[$day]['first_in_attachment_path'] = $row['attachment_path'] ?? null;
                }
            }
            if ($row['type'] === 'OUT') {
                if ($logs[$day]['last_out'] === null || $row['created_at'] > $logs[$day]['last_out']) {
                    $logs[$day]['last_out'] = $row['created_at'];
                    $logs[$day]['last_out_category'] = $this->attendance_category_value($row['attendance_category'] ?? null);
                    $logs[$day]['last_out_reason'] = $row['attendance_reason'] ?? null;
                    $logs[$day]['last_out_attachment_path'] = $row['attachment_path'] ?? null;
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

    private function resolve_attendance_times($office, $user = null)
    {
        $start = '08:00';
        $end = '17:00';
        $source = 'default';

        if ($office && !empty($office['attendance_response_times'])) {
            $times = $this->parse_response_times($office['attendance_response_times']);
            list($officeStart, $officeEnd) = $this->select_time_pair($times);
            if ($officeStart) {
                $start = $officeStart;
            }
            if ($officeEnd) {
                $end = $officeEnd;
            }
            $source = 'office';
        }

        $userTimes = $this->resolve_user_attendance_times($user);
        if ($userTimes) {
            if (!empty($userTimes['start'])) {
                $start = $userTimes['start'];
            }
            if (!empty($userTimes['end'])) {
                $end = $userTimes['end'];
            }
            $source = 'user';
        }

        return array('start' => $start, 'end' => $end, 'source' => $source);
    }

    private function resolve_user_attendance_times($user)
    {
        if (!$user || !is_array($user)) {
            return null;
        }

        $start = null;
        $end = null;

        $rawTimes = trim((string) ($user['attendance_response_times'] ?? ''));
        if ($rawTimes !== '') {
            $times = $this->parse_response_times($rawTimes);
            list($start, $end) = $this->select_time_pair($times);
        }

        $startOverride = trim((string) ($user['attendance_start_time'] ?? ''));
        if ($this->is_valid_time($startOverride)) {
            $start = $startOverride;
        }

        $endOverride = trim((string) ($user['attendance_end_time'] ?? ''));
        if ($this->is_valid_time($endOverride)) {
            $end = $endOverride;
        }

        if ($start === null && $end === null) {
            return null;
        }

        return array('start' => $start, 'end' => $end);
    }

    private function select_time_pair($times)
    {
        $start = null;
        $end = null;
        if (!is_array($times)) {
            return array($start, $end);
        }

        foreach ($times as $time) {
            $time = trim((string) $time);
            if ($this->is_valid_time($time)) {
                if ($start === null) {
                    $start = $time;
                } elseif ($end === null) {
                    $end = $time;
                    break;
                }
            }
        }

        return array($start, $end);
    }

    private function is_valid_time($time)
    {
        return $time !== '' && preg_match('/^\d{2}:\d{2}$/', $time);
    }

    private function minutes_after_start($timestamp, $startTime)
    {
        $start = strtotime(substr($timestamp, 0, 10) . ' ' . $startTime . ':00');
        if ($start === false) {
            return null;
        }
        $delta = strtotime($timestamp) - $start;
        return (int) floor($delta / 60);
    }

    private function minutes_before_end($timestamp, $endTime)
    {
        $end = strtotime(substr($timestamp, 0, 10) . ' ' . $endTime . ':00');
        if ($end === false) {
            return null;
        }
        $delta = $end - strtotime($timestamp);
        return (int) floor($delta / 60);
    }

    private function build_daily_notes($firstIn, $lastOut, $startTime, $endTime, $isLate, $isEarlyCheckout, $dayLogs = array())
    {
        $notes = array();
        $firstInCategory = $this->attendance_category_value($dayLogs['first_in_category'] ?? null);
        $lastOutCategory = $this->attendance_category_value($dayLogs['last_out_category'] ?? null);
        if ($firstIn && $this->is_out_of_town_attendance($firstInCategory)) {
            $notes[] = 'Masuk tercatat sebagai Dinas Luar Kota.';
        }
        if ($lastOut && $this->is_out_of_town_attendance($lastOutCategory)) {
            $notes[] = 'Keluar tercatat sebagai Dinas Luar Kota.';
        }
        if ($isLate && $firstIn) {
            $lateMinutes = $this->minutes_after_start($firstIn, $startTime);
            if ($lateMinutes !== null) {
                $notes[] = 'Terlambat masuk ' . $lateMinutes . ' menit.';
            }
        }
        if ($isEarlyCheckout && $lastOut) {
            $earlyMinutes = $this->minutes_before_end($lastOut, $endTime);
            if ($earlyMinutes !== null) {
                $notes[] = 'Pulang cepat ' . $earlyMinutes . ' menit.';
            }
        }

        return $notes;
    }

    private function attendance_category_value($value)
    {
        return strtoupper(trim((string) $value)) === 'OUT_OF_TOWN' ? 'OUT_OF_TOWN' : 'REGULAR';
    }

    private function attendance_category_label($value)
    {
        return $this->is_out_of_town_attendance($value) ? 'Dinas Luar Kota' : 'Kantor';
    }

    private function is_out_of_town_attendance($value)
    {
        return $this->attendance_category_value($value) === 'OUT_OF_TOWN';
    }

    private function is_attendance_log_eligible($row)
    {
        if (!$this->is_out_of_town_attendance($row['attendance_category'] ?? null)) {
            return true;
        }

        return trim((string) ($row['attachment_path'] ?? '')) !== '';
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
        $threshold = $start + ($this->lateGraceMinutes * 60);
        return strtotime($timestamp) > $threshold;
    }

    private function is_early_checkout($timestamp, $endTime)
    {
        $end = strtotime(substr($timestamp, 0, 10) . ' ' . $endTime . ':00');
        $threshold = $end - ($this->earlyGraceMinutes * 60);
        return strtotime($timestamp) < $threshold;
    }

    private function public_attachment_url($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        return project_uploaded_file_url($path);
    }
}
