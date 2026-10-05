<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Takeaway orders (bán mang đi) have no physical table: order_sessions is no
 * longer required to belong to a table_session, and kitchen_tickets no longer
 * requires a table_id. order_type distinguishes DINE_IN vs TAKEAWAY for
 * reporting/UI without having to infer it from a NULL check everywhere.
 */
class Migration_Add_takeaway_support extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE order_sessions ADD COLUMN order_type ENUM('DINE_IN','TAKEAWAY') NOT NULL DEFAULT 'DINE_IN' AFTER order_no");

        $this->db->query('ALTER TABLE order_sessions DROP FOREIGN KEY fk_os_table_session');
        $this->db->query('ALTER TABLE order_sessions MODIFY table_session_id INT(11) UNSIGNED NULL');
        $this->db->query('ALTER TABLE order_sessions ADD CONSTRAINT fk_os_table_session FOREIGN KEY (table_session_id) REFERENCES table_sessions(id)');

        $this->db->query('ALTER TABLE kitchen_tickets DROP FOREIGN KEY fk_kt_table');
        $this->db->query('ALTER TABLE kitchen_tickets MODIFY table_id INT(11) UNSIGNED NULL');
        $this->db->query('ALTER TABLE kitchen_tickets ADD CONSTRAINT fk_kt_table FOREIGN KEY (table_id) REFERENCES cafe_tables(id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE kitchen_tickets DROP FOREIGN KEY fk_kt_table');
        $this->db->query('ALTER TABLE kitchen_tickets MODIFY table_id INT(11) UNSIGNED NOT NULL');
        $this->db->query('ALTER TABLE kitchen_tickets ADD CONSTRAINT fk_kt_table FOREIGN KEY (table_id) REFERENCES cafe_tables(id)');

        $this->db->query('ALTER TABLE order_sessions DROP FOREIGN KEY fk_os_table_session');
        $this->db->query('ALTER TABLE order_sessions MODIFY table_session_id INT(11) UNSIGNED NOT NULL');
        $this->db->query('ALTER TABLE order_sessions ADD CONSTRAINT fk_os_table_session FOREIGN KEY (table_session_id) REFERENCES table_sessions(id)');

        $this->db->query('ALTER TABLE order_sessions DROP COLUMN order_type');
    }
}
