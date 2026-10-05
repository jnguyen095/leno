<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kitchen_ticket_model extends CI_Model
{
    protected $table = 'kitchen_tickets';
    protected $items_table = 'kitchen_ticket_items';

    /**
     * Create a new kitchen ticket for a batch of newly-ordered items.
     * @param array $items array of ['product_id'=>, 'qty'=>, 'note'=>]
     */
    public function create_ticket($order_session_id, $table_id, array $items)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->insert($this->table, array(
            'order_session_id' => $order_session_id,
            'table_id'         => $table_id,
            'status'           => 'NEW',
            'created_at'       => $now,
            'updated_at'       => $now,
        ));
        $ticket_id = $this->db->insert_id();

        $rows = array();
        foreach ($items as $it)
        {
            $rows[] = array(
                'ticket_id'  => $ticket_id,
                'product_id' => $it['product_id'],
                'qty'        => $it['qty'],
                'note'       => isset($it['note']) ? $it['note'] : NULL,
                'status'     => 'NEW',
            );
        }
        if ($rows) $this->db->insert_batch($this->items_table, $rows);

        return $ticket_id;
    }

    /**
     * NEW/PREPARING are ordered oldest-first (FIFO prep queue); COMPLETED is
     * ordered by whichever ticket was most recently updated/finished first.
     */
    public function get_dashboard_tickets($statuses = array('NEW', 'PREPARING'))
    {
        $tickets = array();

        $active_statuses = array_intersect($statuses, array('NEW', 'PREPARING'));
        if ($active_statuses)
        {
            $tickets = array_merge($tickets, $this->_tickets_by_status($active_statuses, 'kitchen_tickets.created_at', 'ASC'));
        }

        if (in_array('COMPLETED', $statuses, TRUE))
        {
            // Chỉ lấy ticket hoàn thành TRONG NGÀY HÔM NAY — nếu không giới hạn, cột này phình to dần
            // theo thời gian (kéo toàn bộ lịch sử COMPLETED mỗi lần tải trang/poll), ảnh hưởng hiệu năng
            // trang KDS lẫn API /api/kitchen/tickets (được poll liên tục vài giây/lần).
            $tickets = array_merge($tickets, $this->_tickets_by_status(array('COMPLETED'), 'kitchen_tickets.updated_at', 'DESC', date('Y-m-d').' 00:00:00'));
        }

        $this->_attach_items($tickets);
        return $tickets;
    }

    private function _tickets_by_status($statuses, $order_col, $order_dir, $updated_since = NULL)
    {
        $this->db->select('kitchen_tickets.*, cafe_tables.table_name, cafe_tables.table_code, order_sessions.order_no, order_sessions.order_type')
            ->from($this->table)
            ->join('cafe_tables', 'cafe_tables.id = kitchen_tickets.table_id', 'left')
            ->join('order_sessions', 'order_sessions.id = kitchen_tickets.order_session_id')
            ->where_in('kitchen_tickets.status', $statuses);

        if ($updated_since)
        {
            $this->db->where('kitchen_tickets.updated_at >=', $updated_since);
        }

        return $this->db->order_by($order_col, $order_dir)->get()->result_array();
    }

    /** Gắn items cho danh sách ticket bằng 1 query duy nhất (thay vì 1 query riêng cho mỗi ticket). */
    private function _attach_items(&$tickets)
    {
        if ( ! $tickets)
        {
            return;
        }

        $items_by_ticket = array();
        $rows = $this->db->select('kitchen_ticket_items.*, products.product_name, products.image')
            ->from($this->items_table)
            ->join('products', 'products.id = kitchen_ticket_items.product_id')
            ->where_in('ticket_id', array_column($tickets, 'id'))
            ->get()->result_array();

        foreach ($rows as $row)
        {
            $items_by_ticket[$row['ticket_id']][] = $row;
        }

        foreach ($tickets as &$t)
        {
            $t['items'] = isset($items_by_ticket[$t['id']]) ? $items_by_ticket[$t['id']] : array();
        }
    }

    public function get_ticket($id)
    {
        $ticket = $this->db->select('kitchen_tickets.*, cafe_tables.table_name, cafe_tables.table_code, order_sessions.order_no, order_sessions.order_type')
            ->from($this->table)
            ->join('cafe_tables', 'cafe_tables.id = kitchen_tickets.table_id', 'left')
            ->join('order_sessions', 'order_sessions.id = kitchen_tickets.order_session_id')
            ->where('kitchen_tickets.id', $id)
            ->get()->row_array();

        if ($ticket)
        {
            $ticket['items'] = $this->db->select('kitchen_ticket_items.*, products.product_name, products.image')
                ->from($this->items_table)
                ->join('products', 'products.id = kitchen_ticket_items.product_id')
                ->where('ticket_id', $id)
                ->get()->result_array();
        }
        return $ticket;
    }

    public function update_ticket_status($id, $status)
    {
        $this->db->where('id', $id)->update($this->table, array('status' => $status, 'updated_at' => date('Y-m-d H:i:s')));
        $this->db->where('ticket_id', $id)->update($this->items_table, array('status' => $status));
        return TRUE;
    }

    public function update_item_status($item_id, $status)
    {
        $item = $this->db->where('id', $item_id)->get($this->items_table)->row_array();
        if ( ! $item) return FALSE;

        $this->db->where('id', $item_id)->update($this->items_table, array('status' => $status));

        // Re-derive parent ticket status from its items.
        $statuses = $this->db->select('status')->where('ticket_id', $item['ticket_id'])->get($this->items_table)->result_array();
        $all = array_column($statuses, 'status');

        if (in_array('NEW', $all, TRUE))
        {
            $ticket_status = 'NEW';
        }
        elseif (in_array('PREPARING', $all, TRUE))
        {
            $ticket_status = 'PREPARING';
        }
        else
        {
            $ticket_status = 'COMPLETED';
        }
        $this->db->where('id', $item['ticket_id'])->update($this->table, array('status' => $ticket_status, 'updated_at' => date('Y-m-d H:i:s')));

        return $ticket_status;
    }

    public function tickets_for_order($order_session_id)
    {
        return $this->db->where('order_session_id', $order_session_id)->order_by('id', 'ASC')->get($this->table)->result_array();
    }

    public function tickets_with_items_for_order($order_session_id)
    {
        $tickets = $this->tickets_for_order($order_session_id);
        foreach ($tickets as &$t)
        {
            $t['items'] = $this->db->select('kitchen_ticket_items.*, products.product_name, products.price, products.image')
                ->from($this->items_table)
                ->join('products', 'products.id = kitchen_ticket_items.product_id')
                ->where('ticket_id', $t['id'])
                ->get()->result_array();
        }
        return $tickets;
    }

    public function performance_by_day($from, $to)
    {
        return $this->db->select("DATE(created_at) as day, COUNT(*) as total, SUM(CASE WHEN status='COMPLETED' THEN 1 ELSE 0 END) as completed")
            ->where('created_at >=', $from.' 00:00:00')
            ->where('created_at <=', $to.' 23:59:59')
            ->group_by('DATE(created_at)')
            ->order_by('day', 'ASC')
            ->get($this->table)->result_array();
    }
}
