<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lịch sử mức lương theo thời gian — cho phép đổi lương "áp dụng từ tháng
 * sau" mà không làm sai lệch lương các tháng trước đã tính.
 */
class Payroll_rate_history_model extends CI_Model
{
    protected $table = 'payroll_rate_history';

    /** Mức lương có hiệu lực tại 1 tháng cụ thể — dòng effective_from gần nhất mà <= tháng đó, hoặc NULL nếu chưa có lịch sử nào áp dụng được. */
    public function get_effective_for_period($user_id, $period)
    {
        return $this->db->where('user_id', $user_id)
            ->where('effective_from <=', $period)
            ->order_by('effective_from', 'DESC')
            ->limit(1)
            ->get($this->table)->row_array();
    }

    /** Toàn bộ lịch sử của 1 nhân viên, mới nhất trước — hiển thị tham khảo trên màn cấu hình lương. */
    public function get_all_for_user($user_id)
    {
        return $this->db->where('user_id', $user_id)->order_by('effective_from', 'DESC')->get($this->table)->result_array();
    }

    /** Ghi/ghi đè mức lương áp dụng từ 1 tháng cụ thể. */
    public function upsert($user_id, $salary_type, $fixed_salary, $hourly_rate, $effective_from, $created_by)
    {
        $existing = $this->db->where('user_id', $user_id)->where('effective_from', $effective_from)->get($this->table)->row_array();
        $data = array(
            'salary_type'  => $salary_type,
            'fixed_salary' => $fixed_salary,
            'hourly_rate'  => $hourly_rate,
        );

        if ($existing)
        {
            return $this->db->where('id', $existing['id'])->update($this->table, $data);
        }

        $data['user_id'] = $user_id;
        $data['effective_from'] = $effective_from;
        $data['created_by'] = $created_by;
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert($this->table, $data);
    }
}
