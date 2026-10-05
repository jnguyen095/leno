<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Khuyến mãi/sự kiện hiển thị ở website public (section "Hôm nay có gì?" + trang /promotion). */
class Migration_Create_promotions_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'          => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'title'       => array('type' => 'VARCHAR', 'constraint' => 150),
            'description' => array('type' => 'VARCHAR', 'constraint' => 500, 'null' => TRUE),
            'image'       => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'link'        => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'start_date'  => array('type' => 'DATE', 'null' => TRUE),
            'end_date'    => array('type' => 'DATE', 'null' => TRUE),
            'status'      => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
            'sort_order'  => array('type' => 'INT', 'constraint' => 5, 'default' => 0),
            'created_at'  => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at'  => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('promotions', TRUE, array('ENGINE' => 'InnoDB'));
    }

    public function down()
    {
        $this->dbforge->drop_table('promotions', TRUE);
    }
}
