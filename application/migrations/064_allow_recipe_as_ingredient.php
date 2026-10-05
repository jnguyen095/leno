<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cho phép 1 thành phần trong công thức là MỘT CÔNG THỨC KHÁC (sơ chế) thay
 * vì luôn phải là nguyên liệu kho — VD: "Trà Lài" (sơ chế) dùng làm thành
 * phần trong "Trà Đào". Mỗi dòng recipe_ingredients chỉ set 1 trong 2 cột
 * inventory_item_id / source_recipe_id (cột còn lại NULL), ràng buộc ở tầng
 * ứng dụng (Recipes controller), không dùng CHECK constraint.
 */
class Migration_Allow_recipe_as_ingredient extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE recipe_ingredients MODIFY COLUMN inventory_item_id INT(11) UNSIGNED NULL');
        $this->db->query('ALTER TABLE recipe_ingredients ADD COLUMN source_recipe_id INT(11) UNSIGNED NULL AFTER inventory_item_id');
        $this->db->query('ALTER TABLE recipe_ingredients ADD KEY idx_ri_source_recipe (source_recipe_id)');
        $this->db->query('ALTER TABLE recipe_ingredients ADD CONSTRAINT fk_ri_source_recipe FOREIGN KEY (source_recipe_id) REFERENCES recipes(id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE recipe_ingredients DROP FOREIGN KEY fk_ri_source_recipe');
        $this->db->query('ALTER TABLE recipe_ingredients DROP KEY idx_ri_source_recipe');
        $this->db->query('ALTER TABLE recipe_ingredients DROP COLUMN source_recipe_id');
        $this->db->query('ALTER TABLE recipe_ingredients MODIFY COLUMN inventory_item_id INT(11) UNSIGNED NOT NULL');
    }
}
