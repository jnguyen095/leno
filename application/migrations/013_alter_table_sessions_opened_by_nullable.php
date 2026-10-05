<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * A table_session can now be auto-opened by a customer scanning the QR code
 * (no staff account involved), so opened_by must allow NULL.
 */
class Migration_Alter_table_sessions_opened_by_nullable extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE table_sessions DROP FOREIGN KEY fk_ts_user');
        $this->db->query('ALTER TABLE table_sessions MODIFY opened_by INT(11) UNSIGNED NULL');
        $this->db->query('ALTER TABLE table_sessions ADD CONSTRAINT fk_ts_user FOREIGN KEY (opened_by) REFERENCES users(id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE table_sessions DROP FOREIGN KEY fk_ts_user');
        $this->db->query('ALTER TABLE table_sessions MODIFY opened_by INT(11) UNSIGNED NOT NULL');
        $this->db->query('ALTER TABLE table_sessions ADD CONSTRAINT fk_ts_user FOREIGN KEY (opened_by) REFERENCES users(id)');
    }
}
