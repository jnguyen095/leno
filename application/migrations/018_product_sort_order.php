<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Thứ tự món trong từng danh mục (products.sort_order) — sắp xếp ở Quản trị → Danh mục → "Sắp xếp món"
 * (/me/categories/{id}/products). POS web, ứng dụng POS (/api/v1/menu) và thực đơn website xếp món theo số này.
 * Điền sẵn theo thứ tự tên hiện tại trong mỗi danh mục (1, 2, 3...) nên giao diện không đổi cho tới khi sắp xếp lại.
 */
class Migration_Product_sort_order extends CI_Migration
{
    public function up()
    {
        if ( ! $this->db->field_exists('sort_order', 'products'))
        {
            $this->db->query("ALTER TABLE products
                ADD COLUMN sort_order INT NOT NULL DEFAULT 0 COMMENT 'Thứ tự trong danh mục (nhỏ trước)' AFTER category_id,
                ADD KEY idx_products_category_sort (category_id, sort_order)");
            $this->db->query("UPDATE products p
                JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY product_name, id) AS rn FROM products) t ON t.id = p.id
                SET p.sort_order = t.rn");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('sort_order', 'products'))
        {
            $this->db->query('ALTER TABLE products DROP KEY idx_products_category_sort, DROP COLUMN sort_order');
        }
    }
}
