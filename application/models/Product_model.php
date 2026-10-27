<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model
{
    protected $table = 'products';

    /**
     * Danh sách sản phẩm cho trang quản trị. $filters: category_id, q (tìm theo tên hoặc SKU),
     * status ('ACTIVE' | 'INACTIVE'; rỗng = tất cả).
     */
    public function get_all($filters = array())
    {
        $this->db->select('products.*, categories.name as category_name, inventory_categories.name as inventory_category_name')
            ->from($this->table)
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('inventory_categories', 'inventory_categories.id = products.inventory_category_id', 'left');

        if ( ! empty($filters['category_id']))
        {
            $this->db->where('products.category_id', (int) $filters['category_id']);
        }
        if (isset($filters['q']) && $filters['q'] !== '')
        {
            $this->db->group_start()
                ->like('products.product_name', $filters['q'])
                ->or_like('products.sku', $filters['q'])
                ->group_end();
        }
        if ( ! empty($filters['status']))
        {
            $this->db->where('products.status', $filters['status']);
        }

        // Theo thứ tự danh mục, rồi thứ tự món trong danh mục (Danh mục → Sắp xếp món) — ứng dụng POS (/api/v1/menu) dùng đúng thứ tự này.
        return $this->db->order_by('categories.sort_order', 'ASC')->order_by('categories.name', 'ASC')
            ->order_by('products.sort_order', 'ASC')->order_by('products.product_name', 'ASC')->get()->result_array();
    }

    /**
     * Thực đơn trên website (/thuc-don.html): danh mục đang bật có ít nhất 1 món đang bán, theo thứ tự danh mục
     * (Quản trị → Danh mục), món theo thứ tự trong danh mục (Sắp xếp món) — cùng thứ tự với màn hình gọi món POS.
     * Trả về [['id', 'name', 'products' => [['id', 'name', 'price', 'image', 'description'], ...]], ...].
     */
    public function get_public_menu()
    {
        $rows = $this->db->select('p.id, p.product_name, p.price, p.image, p.description, c.id AS category_id, c.name AS category_name')
            ->from($this->table.' p')
            ->join('categories c', 'c.id = p.category_id')
            ->where('p.status', 'ACTIVE')->where('c.status', 'ACTIVE')
            ->order_by('c.sort_order', 'ASC')->order_by('c.name', 'ASC')->order_by('p.sort_order', 'ASC')->order_by('p.product_name', 'ASC')
            ->get()->result_array();

        $menu = array();
        foreach ($rows as $r)
        {
            $cid = (int) $r['category_id'];
            if ( ! isset($menu[$cid])) $menu[$cid] = array('id' => $cid, 'name' => $r['category_name'], 'products' => array());
            $menu[$cid]['products'][] = array(
                'id' => (int) $r['id'], 'name' => $r['product_name'], 'price' => (float) $r['price'],
                'image' => $r['image'], 'description' => $r['description'],
            );
        }
        return array_values($menu);
    }

    public function get_active_grouped_by_category()
    {
        $products = $this->db->where('products.status', 'ACTIVE')
            ->select('products.*, categories.name as category_name, categories.sort_order as cat_sort')
            ->from($this->table)
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('categories.status', 'ACTIVE')
            ->order_by('categories.sort_order', 'ASC')
            ->order_by('categories.name', 'ASC')
            ->order_by('products.sort_order', 'ASC')
            ->order_by('products.product_name', 'ASC')
            ->get()->result_array();

        $grouped = array();
        foreach ($products as $p)
        {
            $grouped[$p['category_name']][] = $p;
        }
        return $grouped;
    }

    public function get_by_id($id)
    {
        return $this->db->select('products.*, inventory_categories.name as inventory_category_name')
            ->from($this->table)
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('inventory_categories', 'inventory_categories.id = products.inventory_category_id', 'left')
            ->where('products.id', $id)
            ->get()->row_array();
    }

    public function get_by_sku($sku)
    {
        return $this->db->where('sku', $sku)->get($this->table)->row_array();
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

    /** Tiền tố SKU của một danh mục sản phẩm (vd "Cà phê Espresso" -> CPE). */
    public function sku_prefix_for_category($category_id)
    {
        $this->load->model('Category_model');
        $category = $this->Category_model->get_by_id($category_id);
        return vn_initials_prefix($category ? $category['name'] : '');
    }

    /**
     * SKU kế tiếp cho danh mục: <tiền tố>-<số 2 chữ số>, số = số lớn nhất đang dùng với
     * tiền tố đó + 1 (CPE-01, CPE-02...; quá 99 thì thành CPE-100). Không dùng lại số của
     * sản phẩm đã xoá ở giữa dãy.
     */
    public function generate_sku($category_id)
    {
        $prefix = $this->sku_prefix_for_category($category_id);

        $max = 0;
        $rows = $this->db->select('sku')->like('sku', $prefix.'-', 'after')->get($this->table)->result_array();
        foreach ($rows as $r)
        {
            if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $r['sku'], $m))
            {
                $max = max($max, (int) $m[1]);
            }
        }

        $seq = $max + 1;
        do
        {
            $sku = $prefix.'-'.str_pad($seq++, 2, '0', STR_PAD_LEFT);
        }
        while ($this->sku_exists($sku));

        return $sku;
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

    public function top_products($from, $to, $limit = 10)
    {
        return $this->db->select('products.product_name, SUM(order_items.qty) as total_qty, SUM(order_items.amount) as total_amount')
            ->from('order_items')
            ->join('products', 'products.id = order_items.product_id')
            ->join('order_sessions', 'order_sessions.id = order_items.order_session_id')
            ->where('order_items.status', 'ACTIVE')
            ->where('order_sessions.status', 'PAID')
            ->where('order_sessions.paid_at >=', $from)
            ->where('order_sessions.paid_at <=', $to)
            ->group_by('products.id')
            ->order_by('total_qty', 'DESC')
            ->limit($limit)
            ->get()->result_array();
    }

    // ---- Thứ tự món trong danh mục (products.sort_order — migration 018) ----

    /** Mọi món của một danh mục (cả món đã ẩn), theo thứ tự hiện tại — trang Sắp xếp món. */
    public function get_by_category($category_id)
    {
        return $this->db->where('category_id', (int) $category_id)
            ->order_by('sort_order', 'ASC')->order_by('product_name', 'ASC')
            ->get($this->table)->result_array();
    }

    /** Số món theo danh mục: [category_id => ['total' => n, 'active' => n]]. */
    public function count_by_category()
    {
        $out = array();
        foreach ($this->db->select("category_id, COUNT(*) AS total, SUM(status = 'ACTIVE') AS active", FALSE)
                     ->group_by('category_id')->get($this->table)->result_array() as $r)
        {
            $out[(int) $r['category_id']] = array('total' => (int) $r['total'], 'active' => (int) $r['active']);
        }
        return $out;
    }

    /** Thứ tự cho món mới / món vừa chuyển sang danh mục này: xếp cuối danh mục. */
    public function next_sort_order($category_id)
    {
        return (int) $this->db->select_max('sort_order')->where('category_id', (int) $category_id)->get($this->table)->row('sort_order') + 1;
    }

    /**
     * Sắp xếp lại món trong một danh mục: sort_order = 1, 2, 3... theo đúng thứ tự $ids.
     * $ids phải gồm đủ mọi món của danh mục (không thiếu, không thừa, không trùng) — sai thì FALSE.
     */
    public function reorder($category_id, array $ids)
    {
        $ids = array_map('intval', $ids);
        $all = array_map('intval', array_column($this->db->select('id')->where('category_id', (int) $category_id)->get($this->table)->result_array(), 'id'));
        if ( ! $all || count($ids) !== count($all) || count(array_unique($ids)) !== count($ids) || array_diff($all, $ids))
        {
            return FALSE;
        }
        $this->db->trans_start();
        foreach ($ids as $i => $id)
        {
            $this->db->where('id', $id)->where('category_id', (int) $category_id)->update($this->table, array('sort_order' => $i + 1));
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
