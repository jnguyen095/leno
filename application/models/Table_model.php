<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Table_model extends CI_Model
{
    protected $table = 'cafe_tables';

    public function get_all()
    {
        return $this->db->order_by('sort_order', 'ASC')->order_by('table_name', 'ASC')->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function get_by_code($code)
    {
        return $this->db->where('table_code', $code)->get($this->table)->row_array();
    }

    public function get_by_token($token)
    {
        return $this->db->where('qr_token', $token)->get($this->table)->row_array();
    }

    public function create($data)
    {
        $data['qr_token'] = gen_token(32);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function set_status($id, $status)
    {
        return $this->update($id, array('status' => $status));
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
    }

    public function code_exists($code, $except_id = NULL)
    {
        $this->db->where('table_code', $code);
        if ($except_id)
        {
            $this->db->where('id !=', $except_id);
        }
        return $this->db->get($this->table)->num_rows() > 0;
    }
}
