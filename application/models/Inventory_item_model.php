<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_item_model extends CI_Model
{
    protected $table = 'inventory_items';

    /** Join dùng chung cho các query cần tên danh mục + tên đơn vị tính. */
    private function _with_joins()
    {
        return $this->db->select('inventory_items.*, inventory_categories.name as category_name, inventory_units.name as unit_name')
            ->from($this->table)
            ->join('inventory_categories', 'inventory_categories.id = inventory_items.category_id', 'left')
            ->join('inventory_units', 'inventory_units.id = inventory_items.unit_id', 'left');
    }

    /**
     * $stock_status: 'LOW' (sắp hết hàng — tồn < ngưỡng), 'OK' (đủ hàng —
     * tồn >= ngưỡng), hoặc NULL (không lọc theo tồn kho).
     * $status: 'ACTIVE' (mặc định) | 'INACTIVE' | 'ALL' — dùng cho màn ADMIN
     * xem lại/khôi phục sản phẩm đã xoá (xoá chỉ ẩn status=INACTIVE, không
     * xoá hẳn dòng — xem delete()).
     */
    public function get_all($category_id = NULL, $stock_status = NULL, $keyword = NULL, $status = 'ACTIVE')
    {
        $this->_with_joins();
        if ($status !== 'ALL')
        {
            $this->db->where('inventory_items.status', $status === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE');
        }

        if ($category_id)
        {
            $this->db->where('inventory_items.category_id', $category_id);
        }
        if ($stock_status === 'LOW')
        {
            $this->db->where('inventory_items.qty_on_hand <', 'inventory_items.low_stock_threshold', FALSE);
        }
        elseif ($stock_status === 'OK')
        {
            $this->db->where('inventory_items.qty_on_hand >=', 'inventory_items.low_stock_threshold', FALSE);
        }
        if ($keyword)
        {
            $this->db->group_start()
                ->like('inventory_items.sku', $keyword)
                ->or_like('inventory_items.name', $keyword)
            ->group_end();
        }

        return $this->db->order_by('inventory_items.sku', 'ASC')
            ->get()->result_array();
    }

    /**
     * Danh sách sản phẩm còn hoạt động — dùng cho màn Nhập/Xuất kho. $category_id
     * để trống/0 nghĩa là tất cả danh mục; $keyword lọc theo SKU/tên.
     */
    public function get_by_category($category_id = NULL, $keyword = NULL)
    {
        $this->_with_joins()->where('inventory_items.status', 'ACTIVE');

        if ($category_id)
        {
            $this->db->where('inventory_items.category_id', $category_id);
        }
        if ($keyword)
        {
            $this->db->group_start()
                ->like('inventory_items.sku', $keyword)
                ->or_like('inventory_items.name', $keyword)
            ->group_end();
        }

        return $this->db->order_by('inventory_items.sku', 'ASC')->get()->result_array();
    }

    public function get_by_id($id)
    {
        return $this->_with_joins()->where('inventory_items.id', $id)->get()->row_array();
    }

    public function get_by_sku($sku)
    {
        return $this->_with_joins()->where('inventory_items.sku', $sku)->get()->row_array();
    }

    public function sku_exists($sku, $except_id = NULL)
    {
        $this->db->where('sku', $sku);
        if ($except_id)
        {
            $this->db->where('id !=', $except_id);
        }
        return $this->db->get($this->table)->num_rows() > 0;
    }

    /** Tìm nhanh theo SKU/tên — dùng cho ô chọn sản phẩm ở màn Nhập/Xuất kho. */
    public function search($keyword, $limit = 15)
    {
        return $this->_with_joins()
            ->where('inventory_items.status', 'ACTIVE')
            ->group_start()
                ->like('inventory_items.sku', $keyword)
                ->or_like('inventory_items.name', $keyword)
            ->group_end()
            ->order_by('inventory_items.name', 'ASC')
            ->limit($limit)
            ->get()->result_array();
    }

    public function get_low_stock()
    {
        return $this->_with_joins()
            ->where('inventory_items.status', 'ACTIVE')
            ->where('inventory_items.qty_on_hand <', 'inventory_items.low_stock_threshold', FALSE)
            ->order_by('inventory_items.name', 'ASC')
            ->get()->result_array();
    }

    public function count_low_stock()
    {
        return $this->db->where('status', 'ACTIVE')
            ->where('qty_on_hand <', 'low_stock_threshold', FALSE)
            ->count_all_results($this->table);
    }

    public function count_active()
    {
        return $this->db->where('status', 'ACTIVE')->count_all_results($this->table);
    }

    /**
     * Số sản phẩm sắp hết hàng / tổng số sản phẩm theo từng danh mục — dùng
     * cho dashboard kho hàng (vd "Pha Chế: 2/200").
     */
    public function get_category_stock_summary()
    {
        return $this->db->select("
                inventory_categories.id as category_id,
                inventory_categories.name as category_name,
                COUNT(*) as total,
                SUM(CASE WHEN inventory_items.qty_on_hand < inventory_items.low_stock_threshold THEN 1 ELSE 0 END) as low
            ", FALSE)
            ->from($this->table)
            ->join('inventory_categories', 'inventory_categories.id = inventory_items.category_id', 'left')
            ->where('inventory_items.status', 'ACTIVE')
            ->group_by('inventory_categories.id')
            ->order_by('inventory_categories.sort_order', 'ASC')
            ->get()->result_array();
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

    public function delete($id)
    {
        return $this->db->where('id', $id)->update($this->table, array('status' => 'INACTIVE'));
    }

    public function increase_qty($id, $qty)
    {
        $this->db->set('qty_on_hand', 'qty_on_hand + '.(float) $qty, FALSE);
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('id', $id);
        return $this->db->update($this->table);
    }

    public function decrease_qty($id, $qty)
    {
        $this->db->set('qty_on_hand', 'qty_on_hand - '.(float) $qty, FALSE);
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('id', $id);
        return $this->db->update($this->table);
    }

    /**
     * Sinh SKU tự động dạng "<2 chữ cái đầu tên danh mục>-<số thứ tự>", vd
     * danh mục "Pha Chế" -> "PH-001". Chỉ dùng làm gợi ý ban đầu — người
     * dùng có thể sửa tay trước khi lưu (sku_exists() vẫn kiểm tra lại khi lưu).
     *
     * $exclude: các SKU coi như đã dùng dù chưa có trong DB — cần khi sinh
     * nhiều SKU liên tiếp trong cùng 1 lần xử lý (vd nhiều dòng để trống SKU
     * cùng danh mục trong 1 file import), tránh sinh trùng nhau.
     */
    public function generate_sku($category_id, $exclude = array())
    {
        $this->load->model('Inventory_category_model');
        $category = $this->Inventory_category_model->get_by_id($category_id);
        $prefix = $category ? vn_sku_prefix($category['name']) : 'SP';

        $seq = 1;
        do
        {
            $sku = $prefix.'-'.str_pad($seq, 3, '0', STR_PAD_LEFT);
            $seq++;
        }
        while ($this->sku_exists($sku) || in_array($sku, $exclude, TRUE));

        return $sku;
    }
}
