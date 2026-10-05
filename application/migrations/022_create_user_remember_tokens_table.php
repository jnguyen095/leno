<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Ghi nhớ đăng nhập" — cookie dài hạn (1 năm) để tự đăng nhập lại khi phiên
 * CI session (2 giờ) đã hết hạn. Dùng mẫu selector/validator: selector để tra
 * cứu nhanh trong DB, validator chỉ lưu dạng hash nên lộ DB cũng không dùng
 * được cookie cũ (giống cách WordPress/Laravel làm "remember me").
 */
class Migration_Create_user_remember_tokens_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'             => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'user_id'        => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'selector'       => array('type' => 'VARCHAR', 'constraint' => 32),
            'validator_hash' => array('type' => 'VARCHAR', 'constraint' => 64),
            'expires_at'     => array('type' => 'DATETIME'),
            'created_at'     => array('type' => 'DATETIME'),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('user_remember_tokens', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE user_remember_tokens ADD UNIQUE KEY uq_urt_selector (selector)');
        $this->db->query('ALTER TABLE user_remember_tokens ADD KEY idx_urt_user (user_id)');
        $this->db->query('ALTER TABLE user_remember_tokens ADD CONSTRAINT fk_urt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    public function down()
    {
        $this->dbforge->drop_table('user_remember_tokens', TRUE);
    }
}
