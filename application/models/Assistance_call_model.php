<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assistance_call_model extends CI_Model
{
    protected $table = 'assistance_calls';

    /**
     * Creates a new call, unless an identical PENDING call for the same table
     * already exists (avoids spamming staff if the customer taps repeatedly).
     * Returns the call id, or FALSE if one was already pending.
     */
    public function create($table_id, $type)
    {
        $existing = $this->db->where('table_id', $table_id)->where('type', $type)->where('status', 'PENDING')
            ->get($this->table)->row_array();

        if ($existing)
        {
            return FALSE;
        }

        $this->db->insert($this->table, array(
            'table_id'   => $table_id,
            'type'       => $type,
            'status'     => 'PENDING',
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return $this->db->insert_id();
    }

    public function get_pending()
    {
        return $this->db->select('assistance_calls.*, cafe_tables.table_name, cafe_tables.table_code')
            ->from($this->table)
            ->join('cafe_tables', 'cafe_tables.id = assistance_calls.table_id')
            ->where('assistance_calls.status', 'PENDING')
            ->order_by('assistance_calls.created_at', 'ASC')
            ->get()->result_array();
    }

    public function count_pending()
    {
        return $this->db->where('status', 'PENDING')->get($this->table)->num_rows();
    }

    /** @return array table_id => list of pending call types (e.g. ['HELP','PAYMENT']) */
    public function get_pending_by_table()
    {
        $rows = $this->db->select('table_id, type')->where('status', 'PENDING')->get($this->table)->result_array();
        $map = array();
        foreach ($rows as $r)
        {
            $map[$r['table_id']][] = $r['type'];
        }
        return $map;
    }

    public function resolve($id, $user_id)
    {
        return $this->db->where('id', $id)->where('status', 'PENDING')->update($this->table, array(
            'status'      => 'RESOLVED',
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolved_by' => $user_id,
        ));
    }
}
