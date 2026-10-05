<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Thành phần (nguyên liệu + định lượng) của 1 công thức — xem [[061_create_recipes_table]]. */
class Migration_Create_recipe_ingredients_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'                => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'recipe_id'         => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'inventory_item_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'quantity'          => array('type' => 'DECIMAL', 'constraint' => '12,3'),
            'sort_order'        => array('type' => 'INT', 'constraint' => 5, 'default' => 0),
            'created_at'        => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('recipe_ingredients', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE recipe_ingredients ADD KEY idx_ri_recipe (recipe_id)');
        $this->db->query('ALTER TABLE recipe_ingredients ADD CONSTRAINT fk_ri_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE recipe_ingredients ADD CONSTRAINT fk_ri_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('recipe_ingredients', TRUE);
    }
}
