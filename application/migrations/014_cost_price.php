<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Giá vốn (không bắt buộc) để biết lãi:
 *  - products.cost_price: giá vốn hiện tại của món (NULL = chưa nhập).
 *  - order_items.cost_price: giá vốn chụp lại lúc bán (giống price) — sửa giá vốn món về sau không làm đổi lãi của đơn cũ.
 * Lãi gộp trên Tổng quan chỉ tính các món có giá vốn (xem Dashboard_model::profit).
 */
class Migration_Cost_price extends CI_Migration
{
    public function up()
    {
        if ( ! $this->db->field_exists('cost_price', 'products'))
        {
            $this->db->query("ALTER TABLE products ADD COLUMN cost_price DECIMAL(12,2) NULL COMMENT 'Giá vốn (không bắt buộc)' AFTER price");
        }
        if ( ! $this->db->field_exists('cost_price', 'order_items'))
        {
            $this->db->query("ALTER TABLE order_items ADD COLUMN cost_price DECIMAL(12,2) NULL COMMENT 'Giá vốn lúc bán (chụp từ products.cost_price)' AFTER original_price");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('cost_price', 'order_items')) $this->db->query('ALTER TABLE order_items DROP COLUMN cost_price');
        if ($this->db->field_exists('cost_price', 'products')) $this->db->query('ALTER TABLE products DROP COLUMN cost_price');
    }
}
