<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model
{
    protected $table = 'products';

    public function get_all()
    {
        return $this->db->select('products.*, categories.name as category_name, inventory_categories.name as inventory_category_name')
            ->from($this->table)
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('inventory_categories', 'inventory_categories.id = products.inventory_category_id', 'left')
            ->order_by('products.product_name', 'ASC')
            ->get()->result_array();
    }

    public function get_active_grouped_by_category()
    {
        $products = $this->db->where('products.status', 'ACTIVE')
            ->select('products.*, categories.name as category_name, categories.sort_order as cat_sort')
            ->from($this->table)
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('categories.status', 'ACTIVE')
            ->order_by('categories.sort_order', 'ASC')
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
}
