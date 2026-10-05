<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Thưởng ngoài lương — nhiều dòng/nhân viên/tháng, xem
 * [[065_create_payroll_bonuses_table]]. Cộng vào net_salary qua
 * payroll_helper::payroll_compute(), tham số $bonus_total.
 */
class Payroll_bonus_model extends CI_Model
{
    protected $table = 'payroll_bonuses';

    /** Danh sách khoản thưởng của 1 nhân viên trong 1 tháng, mới nhất trước. */
    public function get_by_user_period($user_id, $period)
    {
        return $this->db->where('user_id', $user_id)->where('period', $period)
            ->order_by('id', 'DESC')
            ->get($this->table)->result_array();
    }

    /** Tổng tiền thưởng của 1 nhân viên trong 1 tháng — cộng vào net_salary. */
    public function sum_by_user_period($user_id, $period)
    {
        $row = $this->db->select_sum('amount')
            ->where('user_id', $user_id)->where('period', $period)
            ->get($this->table)->row_array();
        return $row['amount'] ? (float) $row['amount'] : 0;
    }

    /** $data: bonus_type ('AMOUNT'|'HOURLY'), hours (chỉ HOURLY), rate (chỉ HOURLY), amount, note. */
    public function create($user_id, $period, $data, $created_by)
    {
        $data['user_id'] = $user_id;
        $data['period'] = $period;
        $data['created_by'] = $created_by;
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert($this->table, $data);
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function delete($id, $user_id)
    {
        return $this->db->where('id', $id)->where('user_id', $user_id)->delete($this->table);
    }
}
