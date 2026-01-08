<?php
defined('BASEPATH') or exit('No direct script access allowed');

class LeaveCalculatorService
{
    public function calculate_days($startDate, $endDate)
    {
        try {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
        } catch (Exception $e) {
            return 0;
        }

        if ($start > $end) {
            return 0;
        }

        $diff = $start->diff($end);
        return (int) $diff->days + 1;
    }
}
