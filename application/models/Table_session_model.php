<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Table_session_model extends CI_Model
{
    protected $table = 'table_sessions';

    /** How long a customer's per-visit link stays valid without any staff action. */
    const SESSION_TTL_HOURS = 3;

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
            'session_secret' => gen_token(32),
            'opened_by'      => $opened_by,
            'opened_at'      => date('Y-m-d H:i:s'),
            'status'         => 'OPEN',
        );
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Validates a customer's per-visit link against the table's CURRENT open
     * session. Fails closed on any mismatch — wrong secret (a newer visit has
     * since taken over the table) or the session has been open too long
     * without staff processing payment (likely means the customer left).
     */
    public function validate_session($table_id, $secret)
    {

        $tbSession = $this->db->where('table_id', $table_id)->where('session_secret', $secret)
            ->order_by('id', 'DESC')->limit(1)->get($this->table)->row_array();
        if($tbSession['status'] == 'CLOSED'){
            return array('valid' => FALSE, 'reason' => 'CLOSED');
        }

        $session = $this->get_open_by_table($table_id);
        if ( ! $session)
        {
            return array('valid' => FALSE, 'reason' => 'NOT_OPEN');
        }

        if ( ! $secret || ! hash_equals($session['session_secret'], $secret))
        {
            return array('valid' => FALSE, 'reason' => 'SUPERSEDED');
        }

        $age_hours = (time() - strtotime($session['opened_at'])) / 3600;
        if ($age_hours > self::SESSION_TTL_HOURS)
        {
            return array('valid' => FALSE, 'reason' => 'EXPIRED', 'session' => $session);
        }

        return array('valid' => TRUE, 'session' => $session);
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

    public function get_with_table($id)
    {
        return $this->db->select('table_sessions.*, cafe_tables.table_name, cafe_tables.table_code, cafe_tables.qr_token')
            ->from($this->table)
            ->join('cafe_tables', 'cafe_tables.id = table_sessions.table_id')
            ->where('table_sessions.id', $id)
            ->get()->row_array();
    }
}
