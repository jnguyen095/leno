<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Thư viện ảnh hiển thị ở website public (section Gallery, lọc theo category). */
class Migration_Create_gallery_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'         => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'title'      => array('type' => 'VARCHAR', 'constraint' => 150, 'null' => TRUE),
            'image'      => array('type' => 'VARCHAR', 'constraint' => 255),
            'category'   => array('type' => 'ENUM', 'constraint' => array('kids', 'pickleball', 'cafe', 'photobooth')),
            'status'     => array('type' => 'ENUM', 'constraint' => array('ACTIVE', 'INACTIVE'), 'default' => 'ACTIVE'),
            'sort_order' => array('type' => 'INT', 'constraint' => 5, 'default' => 0),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('gallery', TRUE, array('ENGINE' => 'InnoDB'));
    }

    public function down()
    {
        $this->dbforge->drop_table('gallery', TRUE);
    }
}
