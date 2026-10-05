<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Liên kết sản phẩm bán hàng (products) với danh mục kho — để biết món này
 * thuộc nhóm vật tư nào trong kho và có cần theo dõi tồn kho hay không.
 * Chưa trừ kho tự động khi bán, chỉ là gắn nhãn/lọc.
 */
class Migration_Add_inventory_link_to_products extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE products ADD COLUMN inventory_category_id INT(11) UNSIGNED NULL AFTER category_id');
        $this->db->query("ALTER TABLE products ADD COLUMN track_inventory TINYINT(1) NOT NULL DEFAULT 0 AFTER inventory_category_id");
        $this->db->query('ALTER TABLE products ADD KEY idx_products_inventory_category (inventory_category_id)');
        $this->db->query('ALTER TABLE products ADD CONSTRAINT fk_products_inventory_category FOREIGN KEY (inventory_category_id) REFERENCES inventory_categories(id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE products DROP FOREIGN KEY fk_products_inventory_category');
        $this->db->query('ALTER TABLE products DROP COLUMN track_inventory');
        $this->db->query('ALTER TABLE products DROP COLUMN inventory_category_id');
    }
}
