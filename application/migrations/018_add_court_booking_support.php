<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Integrates pickleball court management: courts are just cafe_tables with
 * table_type=COURT and an hourly_rate (so ordering/KDS/cashier all keep
 * working unchanged), plus a court_bookings table for advance reservations
 * (single or recurring) that get "checked in" into a normal table_session.
 */
class Migration_Add_court_booking_support extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE cafe_tables ADD COLUMN table_type ENUM('CAFE','COURT') NOT NULL DEFAULT 'CAFE' AFTER capacity");
        $this->db->query("ALTER TABLE cafe_tables ADD COLUMN hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER table_type");

        $this->dbforge->add_field(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'table_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'customer_name' => array('type' => 'VARCHAR', 'constraint' => 150),
            'customer_phone' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => TRUE),
            'booking_date' => array('type' => 'DATE'),
            'start_time' => array('type' => 'TIME'),
            'end_time' => array('type' => 'TIME'),
            'status' => array('type' => 'ENUM', 'constraint' => array('BOOKED', 'CHECKED_IN', 'COMPLETED', 'CANCELLED', 'NO_SHOW'), 'default' => 'BOOKED'),
            'booking_group_id' => array('type' => 'CHAR', 'constraint' => 32, 'null' => TRUE),
            'table_session_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'notes' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('court_bookings', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE court_bookings ADD KEY idx_cb_table_date (table_id, booking_date)');
        $this->db->query('ALTER TABLE court_bookings ADD KEY idx_cb_group (booking_group_id)');
        $this->db->query('ALTER TABLE court_bookings ADD KEY idx_cb_status (status)');
        $this->db->query('ALTER TABLE court_bookings ADD CONSTRAINT fk_cb_table FOREIGN KEY (table_id) REFERENCES cafe_tables(id)');
        $this->db->query('ALTER TABLE court_bookings ADD CONSTRAINT fk_cb_session FOREIGN KEY (table_session_id) REFERENCES table_sessions(id)');
        $this->db->query('ALTER TABLE court_bookings ADD CONSTRAINT fk_cb_user FOREIGN KEY (created_by) REFERENCES users(id)');

        // Sản phẩm "Tiền sân" dùng chung cho mọi sân — giá thực tế được tính theo
        // (giờ đặt x hourly_rate của sân) rồi ghi đè khi thêm vào order_items.
        $this->db->insert('categories', array('name' => 'Dịch vụ sân', 'sort_order' => 99, 'status' => 'ACTIVE'));
        $category_id = $this->db->insert_id();
        $this->db->insert('products', array(
            'category_id'  => $category_id,
            'sku'          => 'COURT_FEE',
            'product_name' => 'Tiền sân',
            'price'        => 0,
            'description'  => 'Phí thuê sân theo giờ, tính tự động khi check-in lịch đặt',
            'status'       => 'INACTIVE', // ẩn khỏi menu khách, chỉ hệ thống dùng khi check-in
        ));
    }

    public function down()
    {
        $this->db->query("DELETE FROM products WHERE sku = 'COURT_FEE'");
        $this->db->query("DELETE FROM categories WHERE name = 'Dịch vụ sân'");
        $this->dbforge->drop_table('court_bookings', TRUE);
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN hourly_rate');
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN table_type');
    }
}
