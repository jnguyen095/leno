<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    protected $table = 'users';

    public function get_all($role = NULL, $status = NULL, $keyword = NULL)
    {
        if ($role)
        {
            $this->db->where('role', $role);
        }
        if ($status)
        {
            $this->db->where('status', $status);
        }
        if ($keyword)
        {
            $this->db->group_start()
                ->like('username', $keyword)
                ->or_like('fullname', $keyword)
            ->group_end();
        }

        return $this->db->order_by('fullname', 'ASC')->get($this->table)->result_array();
    }

    public function get_by_roles($roles)
    {
        return $this->db->where_in('role', $roles)->order_by('fullname', 'ASC')->get($this->table)->result_array();
    }

    /**
     * Danh sách nhân viên cho màn lương của 1 tháng ($period 'YYYY-MM') —
     * khác get_all(): nhân viên đã nghỉ việc (status=INACTIVE) vẫn hiện ra
     * cho tháng họ còn làm việc (dựa vào end_date, hoặc updated_at nếu
     * không có end_date — thời điểm tài khoản bị vô hiệu hóa), chỉ biến
     * mất kể từ tháng SAU tháng họ nghỉ, để vẫn tính/chi được lương tháng
     * cuối của họ.
     */
    public function get_for_payroll($period, $role = NULL, $keyword = NULL)
    {
        $period_start = $period.'-01';

        $this->db->group_start()
            ->where('status', 'ACTIVE')
            ->or_group_start()
                ->where('status', 'INACTIVE')
                ->group_start()
                    ->where('end_date >=', $period_start)
                    ->or_group_start()
                        ->where('end_date', NULL)
                        ->where('updated_at >=', $period_start)
                    ->group_end()
                ->group_end()
            ->group_end()
        ->group_end();

        if ($role)
        {
            $this->db->where('role', $role);
        }
        if ($keyword)
        {
            $this->db->group_start()
                ->like('username', $keyword)
                ->or_like('fullname', $keyword)
            ->group_end();
        }

        return $this->db->order_by('fullname', 'ASC')->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function get_by_username($username)
    {
        return $this->db->where('username', $username)->get($this->table)->row_array();
    }

    public function verify_login($username, $password)
    {
        $user = $this->get_by_username($username);

        if ( ! $user || $user['status'] !== 'ACTIVE')
        {
            return FALSE;
        }

        return password_verify($password, $user['password']) ? $user : FALSE;
    }

    public function create($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        if ( ! empty($data['password']))
        {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        else
        {
            unset($data['password']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->update($this->table, array('status' => 'INACTIVE', 'updated_at' => date('Y-m-d H:i:s')));
    }

    public function username_exists($username, $except_id = NULL)
    {
        $this->db->where('username', $username);
        if ($except_id)
        {
            $this->db->where('id !=', $except_id);
        }
        return $this->db->get($this->table)->num_rows() > 0;
    }
}
