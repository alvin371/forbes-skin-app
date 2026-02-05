<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OvertimeCalculatorService Library
 *
 * Handles overtime duration calculation from start and end times.
 */
class OvertimeCalculatorService
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Calculate duration in hours from start and end time
     *
     * @param string $startTime Format: HH:MM or HH:MM:SS
     * @param string $endTime Format: HH:MM or HH:MM:SS
     * @return float Duration in hours (rounded to 2 decimal places)
     */
    public function calculate_duration($startTime, $endTime)
    {
        // Normalize time format
        $startTime = $this->normalize_time($startTime);
        $endTime = $this->normalize_time($endTime);

        $start = strtotime("2000-01-01 $startTime");
        $end = strtotime("2000-01-01 $endTime");

        // Handle overnight overtime (end time is next day)
        if ($end <= $start) {
            $end = strtotime("2000-01-02 $endTime");
        }

        $diffSeconds = $end - $start;
        $diffHours = $diffSeconds / 3600;

        return round($diffHours, 2);
    }

    /**
     * Normalize time format to HH:MM:SS
     *
     * @param string $time
     * @return string
     */
    protected function normalize_time($time)
    {
        $time = trim($time);

        // Handle HH:MM format
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time .= ':00';
        }

        return $time;
    }

    /**
     * Validate time format
     *
     * @param string $time
     * @return bool
     */
    public function is_valid_time($time)
    {
        $time = trim($time);

        // Accept HH:MM or HH:MM:SS
        if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
            return false;
        }

        // Parse and validate
        $parts = explode(':', $time);
        $hour = (int) $parts[0];
        $minute = (int) $parts[1];
        $second = isset($parts[2]) ? (int) $parts[2] : 0;

        return $hour >= 0 && $hour <= 23 &&
               $minute >= 0 && $minute <= 59 &&
               $second >= 0 && $second <= 59;
    }

    /**
     * Check if overtime spans overnight
     *
     * @param string $startTime
     * @param string $endTime
     * @return bool
     */
    public function is_overnight($startTime, $endTime)
    {
        $startTime = $this->normalize_time($startTime);
        $endTime = $this->normalize_time($endTime);

        $start = strtotime("2000-01-01 $startTime");
        $end = strtotime("2000-01-01 $endTime");

        return $end <= $start;
    }

    /**
     * Format duration hours for display
     *
     * @param float $hours
     * @return string
     */
    public function format_duration($hours)
    {
        $wholeHours = floor($hours);
        $minutes = round(($hours - $wholeHours) * 60);

        if ($minutes > 0) {
            return sprintf('%d jam %d menit', $wholeHours, $minutes);
        }

        return sprintf('%d jam', $wholeHours);
    }

    /**
     * Get overtime period boundaries based on a date
     *
     * @param string $date
     * @return array ['start' => datetime, 'end' => datetime]
     */
    public function get_overtime_period($date)
    {
        // Overtime can span from the given date's evening to next morning
        $startDate = date('Y-m-d', strtotime($date));
        $endDate = date('Y-m-d', strtotime($date . ' +1 day'));

        return array(
            'start' => $startDate . ' 00:00:00',
            'end' => $endDate . ' 23:59:59',
        );
    }
}
