<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Công thức pha chế / sơ chế nguyên liệu (VD: Kem muối, Kem trứng, Cốt trà trái cây...). */
class Migration_Create_recipes_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'         => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'name'       => array('type' => 'VARCHAR', 'constraint' => 150),
            'status'     => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
            'sort_order' => array('type' => 'INT', 'constraint' => 5, 'default' => 0),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('recipes', TRUE, array('ENGINE' => 'InnoDB'));
    }

    public function down()
    {
        $this->dbforge->drop_table('recipes', TRUE);
    }
}
