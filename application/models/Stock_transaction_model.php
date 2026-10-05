<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_transaction_model extends CI_Model
{
    protected $table = 'stock_transactions';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Inventory_item_model');
    }

    /** batch_id nhóm các dòng được tạo trong cùng 1 lần submit (nhiều sản phẩm) lại với nhau. */
    public function new_batch_id()
    {
        return uniqid('b');
    }

    public function create_in($item_id, $qty, $note, $created_by, $source = 'MANUAL', $batch_id = NULL)
    {
        $this->db->trans_start();
        $this->db->insert($this->table, array(
            'batch_id'          => $batch_id ?: $this->new_batch_id(),
            'item_id'           => $item_id,
            'type'              => 'IN',
            'qty'               => $qty,
            'dispense_point_id' => NULL,
            'source'            => $source,
            'note'              => $note,
            'created_by'        => $created_by,
            'created_at'        => date('Y-m-d H:i:s'),
        ));
        $this->Inventory_item_model->increase_qty($item_id, $qty);
        $this->db->trans_complete();

        return $this->db->trans_status() ? TRUE : 'Có lỗi xảy ra, vui lòng thử lại.';
    }

    /**
     * TRUE nếu xuất thành công, chuỗi thông báo lỗi nếu không đủ tồn kho.
     * Trừ tồn bằng 1 UPDATE ... WHERE qty_on_hand >= qty duy nhất (atomic ở
     * mức DB), tránh race condition khi 2 người xuất cùng lúc.
     */
    public function create_out($item_id, $qty, $dispense_point_id, $note, $created_by, $batch_id = NULL)
    {
        $item = $this->Inventory_item_model->get_by_id($item_id);
        if ( ! $item)
        {
            return 'Không tìm thấy sản phẩm.';
        }

        $this->db->trans_start();

        $this->db->where('id', $item_id);
        $this->db->where('qty_on_hand >=', $qty);
        $this->db->set('qty_on_hand', 'qty_on_hand - '.(float) $qty, FALSE);
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->update('inventory_items');
        $affected = $this->db->affected_rows();

        if ($affected < 1)
        {
            $this->db->trans_complete();
            $fresh = $this->Inventory_item_model->get_by_id($item_id);
            return 'Không đủ tồn kho (hiện có '.$fresh['qty_on_hand'].' '.$fresh['unit_name'].').';
        }

        $this->db->insert($this->table, array(
            'batch_id'          => $batch_id ?: $this->new_batch_id(),
            'item_id'           => $item_id,
            'type'              => 'OUT',
            'qty'               => $qty,
            'dispense_point_id' => $dispense_point_id,
            'source'            => 'MANUAL',
            'note'              => $note,
            'created_by'        => $created_by,
            'created_at'        => date('Y-m-d H:i:s'),
        ));

        $this->db->trans_complete();

        return $this->db->trans_status() ? TRUE : 'Có lỗi xảy ra, vui lòng thử lại.';
    }

    /**
     * Kiểm kho: đặt tồn kho về đúng số lượng đếm thực tế ($counted_qty).
     * Chênh lệch (có thể âm) được ghi lại làm số lượng của dòng ADJUST để
     * còn thấy trong lịch sử đã kiểm tăng/giảm bao nhiêu. Không tạo dòng gì
     * nếu số đếm bằng đúng số hệ thống (không có gì thay đổi).
     */
    public function create_adjust($item_id, $counted_qty, $note, $created_by, $batch_id = NULL)
    {
        $item = $this->Inventory_item_model->get_by_id($item_id);
        if ( ! $item)
        {
            return 'Không tìm thấy sản phẩm.';
        }

        $delta = round($counted_qty - $item['qty_on_hand'], 2);
        if ($delta == 0)
        {
            return 'NO_CHANGE';
        }

        $this->db->trans_start();

        $this->db->set('qty_on_hand', $counted_qty);
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('id', $item_id);
        $this->db->update('inventory_items');

        $this->db->insert($this->table, array(
            'batch_id'          => $batch_id ?: $this->new_batch_id(),
            'item_id'           => $item_id,
            'type'              => 'ADJUST',
            'qty'               => $delta,
            'dispense_point_id' => NULL,
            'source'            => 'MANUAL',
            'note'              => $note,
            'created_by'        => $created_by,
            'created_at'        => date('Y-m-d H:i:s'),
        ));

        $this->db->trans_complete();

        return $this->db->trans_status() ? TRUE : 'Có lỗi xảy ra, vui lòng thử lại.';
    }

    /** Áp bộ lọc ngày (theo ngày, không giờ) + người thực hiện lên stock_transactions trước khi nhóm/đếm. */
    private function _apply_filters($filters)
    {
        if ( ! empty($filters['date_from']))
        {
            $this->db->where('stock_transactions.created_at >=', $filters['date_from'].' 00:00:00');
        }
        if ( ! empty($filters['date_to']))
        {
            $this->db->where('stock_transactions.created_at <=', $filters['date_to'].' 23:59:59');
        }
        if ( ! empty($filters['created_by']))
        {
            $this->db->where('stock_transactions.created_by', $filters['created_by']);
        }
        if ( ! empty($filters['type']))
        {
            $this->db->where('stock_transactions.type', $filters['type']);
        }
    }

    /** Tổng số lô (batch) khớp bộ lọc — dùng để tính phân trang. */
    public function count_batches($filters = array())
    {
        $this->_apply_filters($filters);
        $row = $this->db->select('COUNT(DISTINCT stock_transactions.batch_id) as total', FALSE)
            ->from($this->table)
            ->get()->row_array();
        return (int) $row['total'];
    }

    /**
     * Lịch sử nhập/xuất kho, nhóm theo batch_id (1 lần submit nhiều sản
     * phẩm = 1 dòng), kèm chi tiết từng sản phẩm trong 'lines' để xổ ra khi
     * bấm vào (không cần gọi thêm request).
     */
    public function get_recent_batches($filters = array(), $limit = 20, $offset = 0)
    {
        $this->_apply_filters($filters);
        $batches = $this->db->select("
                stock_transactions.batch_id,
                MIN(stock_transactions.created_at) as created_at,
                MIN(stock_transactions.type) as type,
                MIN(stock_transactions.source) as source,
                MIN(stock_transactions.dispense_point_id) as dispense_point_id,
                MIN(dispense_points.name) as dispense_point_name,
                MIN(stock_transactions.note) as note,
                MIN(stock_transactions.created_by) as created_by,
                MIN(users.fullname) as created_by_name,
                COUNT(*) as item_count,
                SUM(stock_transactions.qty) as total_qty
            ", FALSE)
            ->from($this->table)
            ->join('dispense_points', 'dispense_points.id = stock_transactions.dispense_point_id', 'left')
            ->join('users', 'users.id = stock_transactions.created_by', 'left')
            ->group_by('stock_transactions.batch_id')
            ->order_by('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()->result_array();

        if (empty($batches))
        {
            return array();
        }

        $batch_ids = array_column($batches, 'batch_id');
        $lines = $this->db->select('stock_transactions.batch_id, stock_transactions.qty, inventory_items.sku, inventory_items.name as item_name, inventory_units.name as unit')
            ->from($this->table)
            ->join('inventory_items', 'inventory_items.id = stock_transactions.item_id', 'left')
            ->join('inventory_units', 'inventory_units.id = inventory_items.unit_id', 'left')
            ->where_in('stock_transactions.batch_id', $batch_ids)
            ->order_by('inventory_items.sku', 'ASC')
            ->get()->result_array();

        $lines_by_batch = array();
        foreach ($lines as $l)
        {
            $lines_by_batch[$l['batch_id']][] = $l;
        }

        foreach ($batches as &$b)
        {
            $b['lines'] = isset($lines_by_batch[$b['batch_id']]) ? $lines_by_batch[$b['batch_id']] : array();
        }

        return $batches;
    }
}
