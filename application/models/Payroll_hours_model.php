<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Số giờ làm mỗi ngày của nhân viên lương theo giờ — admin nhập tay. */
class Payroll_hours_model extends CI_Model
{
    protected $table = 'payroll_hours';

    /** Mảng ['YYYY-MM-DD' => hours] cho 1 nhân viên trong 1 tháng. */
    public function get_by_user_period($user_id, $period)
    {
        $rows = $this->db->where('user_id', $user_id)
            ->where('work_date >=', $period.'-01')
            ->where('work_date <=', date('Y-m-t', strtotime($period.'-01')))
            ->get($this->table)->result_array();

        $by_date = array();
        foreach ($rows as $r)
        {
            $by_date[$r['work_date']] = $r['hours'];
        }
        return $by_date;
    }

    public function sum_hours($user_id, $period)
    {
        $row = $this->db->select_sum('hours')
            ->where('user_id', $user_id)
            ->where('work_date >=', $period.'-01')
            ->where('work_date <=', date('Y-m-t', strtotime($period.'-01')))
            ->get($this->table)->row_array();
        return $row['hours'] ? (float) $row['hours'] : 0;
    }

    /** Mảng [user_id => tổng giờ] cho tất cả nhân viên trong 1 tháng — dùng cho màn tổng hợp admin. */
    public function get_totals_by_period($period)
    {
        $rows = $this->db->select('user_id, SUM(hours) as total_hours', FALSE)
            ->where('work_date >=', $period.'-01')
            ->where('work_date <=', date('Y-m-t', strtotime($period.'-01')))
            ->group_by('user_id')
            ->get($this->table)->result_array();

        $totals = array();
        foreach ($rows as $r)
        {
            $totals[$r['user_id']] = (float) $r['total_hours'];
        }
        return $totals;
    }

    /**
     * Lưu cả tháng cùng lúc. $entries: ['YYYY-MM-DD' => giờ làm, ...].
     * Ngày để trống/0 sẽ bị xoá khỏi bảng (không lưu dòng rác).
     */
    public function save_batch($user_id, $entries, $created_by)
    {
        foreach ($entries as $work_date => $hours)
        {
            $hours = (float) $hours;
            $existing = $this->db->where('user_id', $user_id)->where('work_date', $work_date)->get($this->table)->row_array();

            if ($hours <= 0)
            {
                if ($existing)
                {
                    $this->db->where('id', $existing['id'])->delete($this->table);
                }
                continue;
            }

            if ($existing)
            {
                $this->db->where('id', $existing['id'])->update($this->table, array(
                    'hours'      => $hours,
                    'updated_at' => date('Y-m-d H:i:s'),
                ));
            }
            else
            {
                $this->db->insert($this->table, array(
                    'user_id'    => $user_id,
                    'work_date'  => $work_date,
                    'hours'      => $hours,
                    'created_by' => $created_by,
                    'created_at' => date('Y-m-d H:i:s'),
                ));
            }
        }
    }
}
