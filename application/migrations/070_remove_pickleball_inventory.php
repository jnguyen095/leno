<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tiếp theo 069: gỡ nốt dữ liệu pickleball còn sót — danh mục kho "Pickleball"
 * (banh, vớ, vợt cho thuê...) cùng hàng hoá + lịch sử nhập/xuất của chúng, và
 * nhật ký hệ thống của 2 module đã xoá (court_booking, court_time_slot).
 * Không rollback được — khôi phục từ bản sao lưu DB nếu cần.
 */
class Migration_Remove_pickleball_inventory extends CI_Migration
{
    public function up()
    {
        $this->db->trans_start();

        $cat_ids = array_column($this->db->select('id')->where('name', 'Pickleball')->get('inventory_categories')->result_array(), 'id');
        if ($cat_ids)
        {
            $item_ids = array_column($this->db->select('id')->where_in('category_id', $cat_ids)->get('inventory_items')->result_array(), 'id');
            if ($item_ids)
            {
                $this->db->where_in('item_id', $item_ids)->delete('stock_transactions');
                $this->db->where_in('inventory_item_id', $item_ids)->delete('recipe_ingredients');
                $this->db->where_in('id', $item_ids)->delete('inventory_items');
            }
            $this->db->where_in('inventory_category_id', $cat_ids)->update('products', array('inventory_category_id' => NULL, 'track_inventory' => 0));
            $this->db->where_in('id', $cat_ids)->delete('inventory_categories');
        }

        $this->db->where_in('module', array('court_booking', 'court_time_slot'))->delete('audit_logs');

        $this->db->trans_complete();
    }

    public function down()
    {
        show_error('Migration 070 (gỡ kho pickleball) không rollback được — khôi phục từ bản sao lưu DB.');
    }
}
