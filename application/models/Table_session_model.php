<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Table_session_model extends CI_Model
{
    protected $table = 'table_sessions';

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function get_open_by_table($table_id)
    {
        return $this->db->where('table_id', $table_id)->where('status', 'OPEN')
            ->order_by('id', 'DESC')->limit(1)->get($this->table)->row_array();
    }

    public function open($table_id, $opened_by = NULL)
    {
        $data = array(
            'table_id'       => $table_id,
            'session_no'     => gen_no('SS'),
            'opened_by'      => $opened_by,
            'opened_at'      => date('Y-m-d H:i:s'),
            'status'         => 'OPEN',
        );
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function close($id)
    {
        return $this->db->where('id', $id)->update($this->table, array(
            'status'    => 'CLOSED',
            'closed_at' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Defensive cleanup: a table should never have more than one OPEN session,
     * but close out any stragglers before opening a fresh one (e.g. left behind
     * by a manual data fix or an interrupted close-session step).
     */
    public function close_stray_open_sessions($table_id)
    {
        return $this->db->where('table_id', $table_id)->where('status', 'OPEN')->update($this->table, array(
            'status'    => 'CLOSED',
            'closed_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function usage_by_table($from, $to)
    {
        return $this->db->select("cafe_tables.table_name, COUNT(table_sessions.id) as sessions_count,
                AVG(TIMESTAMPDIFF(MINUTE, table_sessions.opened_at, COALESCE(table_sessions.closed_at, NOW()))) as avg_minutes")
            ->from($this->table)
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id')
            ->where('table_sessions.opened_at >=', $from.' 00:00:00')
            ->where('table_sessions.opened_at <=', $to.' 23:59:59')
            ->group_by('cafe_tables.id')
            ->order_by('sessions_count', 'DESC')
            ->get()->result_array();
    }

}
