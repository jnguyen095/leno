<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Generic key-value app settings — bắt đầu với VAT (%), có thể mở rộng thêm
 * (tên quán, địa chỉ in hóa đơn...) mà không cần thêm migration mới mỗi lần.
 */
class Migration_Create_settings_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'setting_key' => array('type' => 'VARCHAR', 'constraint' => 100),
            'setting_value' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'updated_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('settings', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE settings ADD UNIQUE KEY uk_settings_key (setting_key)');

        // VAT mặc định 8% — khớp với hằng số VAT_RATE cũ đang dùng trong hệ thống.
        $this->db->insert('settings', array(
            'setting_key'   => 'vat_percent',
            'setting_value' => '8',
            'updated_at'    => date('Y-m-d H:i:s'),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_table('settings', TRUE);
    }
}
