<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ngày nghỉ của nhân viên lương cố định — đánh dấu từng ngày cụ thể thay vì
 * gõ tổng số. Mỗi ngày có thể nghỉ cả ngày (fraction = 1) hoặc nửa ngày
 * (fraction = 0.5).
 */
class Payroll_absence_model extends CI_Model
{
    protected $table = 'payroll_absences';

    /** Mảng ['YYYY-MM-DD' => fraction] cho các ngày nghỉ của 1 nhân viên trong 1 tháng. */
    public function get_by_user_period($user_id, $period)
    {
        $rows = $this->db->where('user_id', $user_id)
            ->where('absence_date >=', $period.'-01')
            ->where('absence_date <=', date('Y-m-t', strtotime($period.'-01')))
            ->get($this->table)->result_array();

        $by_date = array();
        foreach ($rows as $r)
        {
            $by_date[$r['absence_date']] = (float) $r['fraction'];
        }
        return $by_date;
    }

    /** Tổng số ngày nghỉ (có tính nửa ngày) của 1 nhân viên trong 1 tháng. */
    public function sum_by_user_period($user_id, $period)
    {
        $row = $this->db->select_sum('fraction')
            ->where('user_id', $user_id)
            ->where('absence_date >=', $period.'-01')
            ->where('absence_date <=', date('Y-m-t', strtotime($period.'-01')))
            ->get($this->table)->row_array();
        return $row['fraction'] ? (float) $row['fraction'] : 0;
    }

    /**
     * Lưu cả tháng cùng lúc. $entries: ['YYYY-MM-DD' => fraction, ...] —
     * fraction là 1 (cả ngày), 0.5 (nửa ngày), hoặc 0/rỗng (không nghỉ, xoá
     * nếu đã có).
     */
    public function save_batch($user_id, $entries, $created_by)
    {
        foreach ($entries as $date => $fraction)
        {
            $fraction = (float) $fraction;
            $existing = $this->db->where('user_id', $user_id)->where('absence_date', $date)->get($this->table)->row_array();

            if ($fraction <= 0)
            {
                if ($existing)
                {
                    $this->db->where('id', $existing['id'])->delete($this->table);
                }
                continue;
            }

            if ($existing)
            {
                $this->db->where('id', $existing['id'])->update($this->table, array('fraction' => $fraction));
            }
            else
            {
                $this->db->insert($this->table, array(
                    'user_id'      => $user_id,
                    'absence_date' => $date,
                    'fraction'     => $fraction,
                    'created_by'   => $created_by,
                    'created_at'   => date('Y-m-d H:i:s'),
                ));
            }
        }
    }
}
