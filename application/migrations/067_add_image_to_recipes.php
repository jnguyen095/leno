<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Ảnh công thức — hiển thị trên /recipes/{id}/edit và thẻ công thức ở /pha-che. */
class Migration_Add_image_to_recipes extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('recipes', array(
            'image' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE, 'after' => 'name'),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_column('recipes', 'image');
    }
}
