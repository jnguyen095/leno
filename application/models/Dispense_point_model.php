<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dispense_point_model extends CI_Model
{
    protected $table = 'dispense_points';

    public function get_all()
    {
        return $this->db->order_by('name', 'ASC')->get($this->table)->result_array();
    }

    public function get_active()
    {
        return $this->db->where('status', 'ACTIVE')->order_by('name', 'ASC')->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->update($this->table, array('status' => 'INACTIVE'));
    }
}
