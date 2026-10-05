<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Đổi inventory_items.unit (text tự do) sang unit_id (FK tới inventory_units
 * quản lý được). Bảng inventory_items hiện chưa có dữ liệu thật nên không
 * cần bước chuyển đổi dữ liệu.
 */
class Migration_Alter_inventory_items_unit_to_fk extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE inventory_items ADD COLUMN unit_id INT(11) UNSIGNED NOT NULL AFTER category_id');
        $this->db->query('ALTER TABLE inventory_items DROP COLUMN unit');
        $this->db->query('ALTER TABLE inventory_items ADD KEY idx_inventory_items_unit (unit_id)');
        $this->db->query('ALTER TABLE inventory_items ADD CONSTRAINT fk_inventory_items_unit FOREIGN KEY (unit_id) REFERENCES inventory_units(id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE inventory_items DROP FOREIGN KEY fk_inventory_items_unit');
        $this->db->query('ALTER TABLE inventory_items DROP COLUMN unit_id');
        $this->db->query("ALTER TABLE inventory_items ADD COLUMN unit VARCHAR(30) NOT NULL AFTER category_id");
    }
}
