<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Cấu hình lương của từng nhân viên — loại lương, mức lương, thông tin ngân hàng. */
class Payroll_setting_model extends CI_Model
{
    protected $table = 'payroll_settings';

    public function get_by_user($user_id)
    {
        return $this->db->where('user_id', $user_id)->get($this->table)->row_array();
    }

    /** Trả về cấu hình mặc định nếu nhân viên chưa được admin thiết lập lương. */
    public function get_by_user_or_default($user_id)
    {
        $row = $this->get_by_user($user_id);
        if ($row)
        {
            return $row;
        }
        return array(
            'user_id'             => $user_id,
            'salary_type'         => 'FIXED',
            'fixed_salary'        => 0,
            'hourly_rate'         => 0,
            'bank_name'           => NULL,
            'bank_account_number' => NULL,
            'bank_account_name'   => NULL,
        );
    }

    public function upsert($user_id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $existing = $this->get_by_user($user_id);
        if ($existing)
        {
            return $this->db->where('user_id', $user_id)->update($this->table, $data);
        }
        $data['user_id'] = $user_id;
        return $this->db->insert($this->table, $data);
    }
}
