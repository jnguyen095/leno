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
}
