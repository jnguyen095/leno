<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Trung_thu_registration_model extends CI_Model
{
    protected $table = 'trung_thu_registrations';

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function phone_exists($phone, $except_id = NULL)
    {
        $this->db->where('phone', $phone);
        if ($except_id)
        {
            $this->db->where('id !=', $except_id);
        }
        return $this->db->get($this->table)->num_rows() > 0;
    }

    public function get_all()
    {
        return $this->db->order_by('created_at', 'DESC')->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function get_by_uuid($uuid)
    {
        return $this->db->where('uuid', $uuid)->get($this->table)->row_array();
    }

    public function update($id, $data)
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
    }

    public function count_total()
    {
        return (int) $this->db->count_all($this->table);
    }

    public function sum_kids()
    {
        $row = $this->db->select_sum('kid_count')->get($this->table)->row_array();
        return (int) $row['kid_count'];
    }
}
