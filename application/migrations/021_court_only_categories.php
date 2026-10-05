<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cho phép đánh dấu một danh mục là "chỉ dành cho sân pickleball" (thuê vợt,
 * thuê trang phục, nhặt bóng, ...) — các sản phẩm trong danh mục này chỉ hiện
 * trong menu gọi món khi bàn đang mở là sân (table_type = COURT), không hiện
 * ở bàn cafe thường. Đánh dấu luôn danh mục "Dịch vụ sân" sẵn có (nơi chứa
 * COURT_FEE) và seed thêm vài sản phẩm dịch vụ mẫu, admin có thể sửa giá/thêm
 * bớt sau tại trang Sản phẩm.
 */
class Migration_Court_only_categories extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE categories ADD COLUMN court_only TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order');

        $this->db->where('name', 'Dịch vụ sân')->update('categories', array('court_only' => 1));

        $cat = $this->db->where('name', 'Dịch vụ sân')->get('categories')->row_array();
        if ($cat)
        {
            $this->db->insert_batch('products', array(
                array('category_id' => $cat['id'], 'sku' => 'RENT_RACKET', 'product_name' => 'Thuê vợt', 'price' => 30000, 'description' => 'Thuê vợt pickleball theo buổi', 'status' => 'ACTIVE'),
                array('category_id' => $cat['id'], 'sku' => 'RENT_OUTFIT', 'product_name' => 'Thuê trang phục', 'price' => 20000, 'description' => 'Thuê trang phục thi đấu theo buổi', 'status' => 'ACTIVE'),
                array('category_id' => $cat['id'], 'sku' => 'BALL_PICKER', 'product_name' => 'Nhặt bóng', 'price' => 50000, 'description' => 'Dịch vụ nhặt bóng theo buổi', 'status' => 'ACTIVE'),
            ));
        }
    }

    public function down()
    {
        $this->db->where_in('sku', array('RENT_RACKET', 'RENT_OUTFIT', 'BALL_PICKER'))->delete('products');
        $this->db->query('ALTER TABLE categories DROP COLUMN court_only');
    }
}
