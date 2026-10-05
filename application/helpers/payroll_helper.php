<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('payroll_compute'))
{
    /**
     * Tính lương 1 nhân viên trong 1 tháng.
     * $settings: từ Payroll_setting_model (salary_type, fixed_salary, hourly_rate).
     * $record: từ Payroll_record_model (advance_amount, paid_status, note).
     * $total_hours: tổng giờ làm trong tháng (chỉ áp dụng khi salary_type = HOURLY).
     * $absence_days: số ngày nghỉ trong tháng, tính từ payroll_absences —
     * admin đánh dấu từng ngày cụ thể (chỉ áp dụng khi salary_type = FIXED).
     * $period: 'YYYY-MM'.
     * $bonus_total: tổng tiền thưởng trong tháng, từ payroll_bonuses (xem
     * Payroll_bonus_model::sum_by_user_period()) — cộng thẳng vào net_salary,
     * không qua gross_salary vì thưởng không phải lương cơ bản.
     *
     * Lương cố định: đơn giá/ngày = lương cố định / số ngày thực tế trong tháng đó.
     */
    function payroll_compute($settings, $record, $total_hours, $absence_days, $period, $bonus_total = 0)
    {
        $advance = (float) $record['advance_amount'];

        if ($settings['salary_type'] === 'HOURLY')
        {
            $rate = (float) $settings['hourly_rate'];
            $gross = $total_hours * $rate;
            $result = array(
                'salary_type'  => 'HOURLY',
                'hourly_rate'  => $rate,
                'total_hours'  => $total_hours,
                'gross_salary' => $gross,
            );
        }
        else
        {
            $days_in_month = (int) date('t', strtotime($period.'-01'));
            $fixed = (float) $settings['fixed_salary'];
            $daily_rate = $days_in_month > 0 ? $fixed / $days_in_month : 0;
            $gross = max(0, $fixed - ($daily_rate * $absence_days));
            $result = array(
                'salary_type'   => 'FIXED',
                'fixed_salary'  => $fixed,
                'days_in_month' => $days_in_month,
                'daily_rate'    => $daily_rate,
                'absence_days'  => $absence_days,
                'gross_salary'  => $gross,
            );
        }

        $result['advance_amount'] = $advance;
        $result['bonus_total'] = (float) $bonus_total;
        $result['net_salary'] = $result['gross_salary'] - $advance + $result['bonus_total'];
        $result['paid_status'] = $record['paid_status'];
        $result['note'] = isset($record['note']) ? $record['note'] : NULL;

        return $result;
    }
}

if ( ! function_exists('payroll_period_label'))
{
    /** 'YYYY-MM' -> "Tháng MM/YYYY" */
    function payroll_period_label($period)
    {
        $parts = explode('-', $period);
        return isset($parts[1]) ? 'Tháng '.$parts[1].'/'.$parts[0] : $period;
    }
}
