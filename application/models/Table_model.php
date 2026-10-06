<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Table_model extends CI_Model
{
    protected $table = 'cafe_tables';

    /** Bàn thường. $include_takeaway = TRUE để lấy kèm bàn "Mang đi" (is_takeaway = 1). */
    public function get_all($include_takeaway = FALSE)
    {
        if ( ! $include_takeaway)
        {
            $this->db->where('is_takeaway', 0);
        }
        return $this->db->order_by('is_takeaway', 'DESC')->order_by('sort_order', 'ASC')->order_by('table_name', 'ASC')->get($this->table)->result_array();
    }

    public function get_takeaway()
    {
        return $this->db->where('is_takeaway', 1)->get($this->table)->row_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function get_by_code($code)
    {
        return $this->db->where('table_code', $code)->get($this->table)->row_array();
    }

    public function create($data)
    {
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

    public function set_note($id, $note)
    {
        return $this->update($id, array('note' => $note));
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
