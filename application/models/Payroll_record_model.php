<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dữ liệu lương theo từng tháng: ứng lương, đã chi lương chưa. Số ngày nghỉ
 * (lương cố định) / giờ làm (lương theo giờ) nằm ở payroll_absences /
 * payroll_hours, tính theo từng ngày cụ thể — không lưu ở đây.
 */
class Payroll_record_model extends CI_Model
{
    protected $table = 'payroll_records';

    public function get_by_user_period($user_id, $period)
    {
        return $this->db->where('user_id', $user_id)->where('period', $period)->get($this->table)->row_array();
    }

    /** Trả về bản ghi mặc định (chưa ứng, chưa chi) nếu tháng đó chưa có dữ liệu. */
    public function get_by_user_period_or_default($user_id, $period)
    {
        $row = $this->get_by_user_period($user_id, $period);
        if ($row)
        {
            return $row;
        }
        return array(
            'user_id'        => $user_id,
            'period'         => $period,
            'advance_amount' => 0,
            'paid_status'    => 'UNPAID',
            'paid_at'        => NULL,
            'note'           => NULL,
        );
    }

    /** $data có thể gồm: advance_amount, paid_status, note. */
    public function upsert($user_id, $period, $data, $updated_by)
    {
        $data['updated_by'] = $updated_by;
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (isset($data['paid_status']) && $data['paid_status'] === 'PAID')
        {
            $data['paid_at'] = date('Y-m-d H:i:s');
        }
        elseif (isset($data['paid_status']) && $data['paid_status'] === 'UNPAID')
        {
            $data['paid_at'] = NULL;
        }

        $existing = $this->get_by_user_period($user_id, $period);
        if ($existing)
        {
            return $this->db->where('id', $existing['id'])->update($this->table, $data);
        }

        $data['user_id'] = $user_id;
        $data['period'] = $period;
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert($this->table, $data);
    }

    /** Mảng [user_id => record] cho toàn bộ nhân viên có dữ liệu trong 1 tháng — dùng cho màn tổng hợp admin. */
    public function get_all_by_period($period)
    {
        $rows = $this->db->where('period', $period)->get($this->table)->result_array();
        $by_user = array();
        foreach ($rows as $r)
        {
            $by_user[$r['user_id']] = $r;
        }
        return $by_user;
    }
}
