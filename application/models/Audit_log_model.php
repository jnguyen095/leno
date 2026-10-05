<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit_log_model extends CI_Model
{
    protected $table = 'audit_logs';

    public function log($module, $action, $old_data = NULL, $new_data = NULL, $user_id = NULL)
    {
        $this->db->insert($this->table, array(
            'module'     => $module,
            'action'     => $action,
            'old_data'   => $old_data !== NULL ? json_encode($old_data) : NULL,
            'new_data'   => $new_data !== NULL ? json_encode($new_data) : NULL,
            'user_id'    => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return $this->db->insert_id();
    }

    public function get_recent($limit = 100)
    {
        return $this->db->order_by('id', 'DESC')->limit($limit)->get($this->table)->result_array();
    }

    public function get_distinct_modules()
    {
        $rows = $this->db->select('DISTINCT module', FALSE)->order_by('module', 'ASC')->get($this->table)->result_array();
        return array_column($rows, 'module');
    }

    public function get_distinct_actions()
    {
        $rows = $this->db->select('DISTINCT action', FALSE)->order_by('action', 'ASC')->get($this->table)->result_array();
        return array_column($rows, 'action');
    }

    /** Áp bộ lọc (module, action, user_id, date_from, date_to) lên audit_logs trước khi đếm/liệt kê. */
    private function _apply_filters($filters)
    {
        if ( ! empty($filters['module']))
        {
            $this->db->where('audit_logs.module', $filters['module']);
        }
        if ( ! empty($filters['action']))
        {
            $this->db->where('audit_logs.action', $filters['action']);
        }
        if ( ! empty($filters['user_id']))
        {
            $this->db->where('audit_logs.user_id', $filters['user_id']);
        }
        if ( ! empty($filters['date_from']))
        {
            $this->db->where('audit_logs.created_at >=', $filters['date_from'].' 00:00:00');
        }
        if ( ! empty($filters['date_to']))
        {
            $this->db->where('audit_logs.created_at <=', $filters['date_to'].' 23:59:59');
        }
    }

    public function count_filtered($filters = array())
    {
        $this->_apply_filters($filters);
        return $this->db->count_all_results($this->table);
    }

    public function get_filtered($filters = array(), $limit = 30, $offset = 0)
    {
        $this->_apply_filters($filters);
        return $this->db->select('audit_logs.*, users.fullname as user_fullname, users.username as username')
            ->from($this->table)
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->order_by('audit_logs.id', 'DESC')
            ->limit($limit, $offset)
            ->get()->result_array();
    }
}
