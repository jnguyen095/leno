<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Leno chỉ là quán cà phê — gỡ toàn bộ phần pickleball kế thừa từ Pick Angel Park:
 *  - lịch đặt sân (court_bookings, court_booking_notes), khung giờ & giá sân (court_time_slots)
 *  - "bàn" loại sân (cafe_tables.table_type = COURT) cùng phiên/đơn/phiếu bếp/gọi hỗ trợ của chúng,
 *    rồi bỏ hẳn cột table_type (mọi bàn đều là bàn cafe)
 *  - vai trò BOOKING (lễ tân đặt sân) -> chuyển về STAFF; menu "Lịch sân"; cài đặt giờ nhận đặt sân
 *  - danh mục sản phẩm: chỉ giữ "Cà phê & Nước uống" và "Thức ăn chế biến"; bỏ cờ court_only
 *  - danh mục doanh thu PICKLEBALL, ảnh gallery pickleball
 *
 * Không rollback được dữ liệu đã xoá — muốn quay lại thì khôi phục từ bản sao lưu DB.
 */
class Migration_Remove_pickleball extends CI_Migration
{
    private $keep_categories = array('Cà phê & Nước uống', 'Thức ăn chế biến');

    public function up()
    {
        $this->db->trans_start();

        // Lịch đặt sân + khung giờ giá sân
        $this->dbforge->drop_table('court_booking_notes', TRUE);
        $this->dbforge->drop_table('court_bookings', TRUE);
        $this->dbforge->drop_table('court_time_slots', TRUE);

        // Sân (cafe_tables loại COURT) và mọi thứ treo vào chúng
        $court_ids = array_column($this->db->select('id')->where('table_type', 'COURT')->get('cafe_tables')->result_array(), 'id');
        if ($court_ids)
        {
            $session_ids = array_column($this->db->select('id')->where_in('table_id', $court_ids)->get('table_sessions')->result_array(), 'id');
            $order_ids = $session_ids
                ? array_column($this->db->select('id')->where_in('table_session_id', $session_ids)->get('order_sessions')->result_array(), 'id')
                : array();

            $ticket_ids = array_column($this->db->select('id')->group_start()->where_in('table_id', $court_ids)
                ->or_where_in('order_session_id', $order_ids ?: array(0))->group_end()->get('kitchen_tickets')->result_array(), 'id');
            if ($ticket_ids)
            {
                $this->db->where_in('ticket_id', $ticket_ids)->delete('kitchen_ticket_items');
                $this->db->where_in('id', $ticket_ids)->delete('kitchen_tickets');
            }
            if ($order_ids)
            {
                $this->db->where_in('order_session_id', $order_ids)->delete('order_items');
                $this->db->where_in('order_session_id', $order_ids)->delete('payments');
                $this->db->where_in('id', $order_ids)->delete('order_sessions');
            }
            if ($session_ids)
            {
                $this->db->where_in('id', $session_ids)->delete('table_sessions');
            }
            $this->db->where_in('table_id', $court_ids)->delete('assistance_calls');
            $this->db->where_in('id', $court_ids)->delete('cafe_tables');
        }
        $this->db->query('ALTER TABLE cafe_tables DROP COLUMN table_type');

        // Danh mục sản phẩm: chỉ giữ 2 danh mục đồ uống/đồ ăn
        $drop_cat_ids = array_column($this->db->select('id')->where_not_in('name', $this->keep_categories)->get('categories')->result_array(), 'id');
        if ($drop_cat_ids)
        {
            $this->db->where_in('category_id', $drop_cat_ids)->delete('products');
            $this->db->where_in('id', $drop_cat_ids)->delete('categories');
        }
        $this->db->where('sku', 'COURT_FEE')->delete('products');
        $this->db->query('ALTER TABLE categories DROP COLUMN court_only');

        // Vai trò BOOKING
        $this->db->where('role', 'BOOKING')->update('users', array('role' => 'STAFF'));
        $this->db->where('role', 'BOOKING')->delete('role_menu_permissions');
        $this->db->query("ALTER TABLE users MODIFY role ENUM('STAFF','BARISTA','CASHIER','ADMIN','STOCKTAKER') NOT NULL DEFAULT 'STAFF'");

        // Menu "Lịch sân"
        $menu_ids = array_column($this->db->select('id')->where_in('controller', array('bookings', 'court_time_slots'))->get('menu_items')->result_array(), 'id');
        if ($menu_ids)
        {
            $this->db->where_in('menu_item_id', $menu_ids)->delete('role_menu_permissions');
            $this->db->where_in('menu_item_id', $menu_ids)->delete('user_menu_permissions');
            $this->db->where_in('id', $menu_ids)->delete('menu_items');
        }

        // Cài đặt giờ nhận đặt sân
        $this->db->where_in('setting_key', array('booking_start_time', 'booking_end_time'))->delete('settings');

        // Doanh thu nhập tay + gallery
        $this->db->where('category', 'PICKLEBALL')->delete('monthly_revenue');
        $this->db->query("ALTER TABLE monthly_revenue MODIFY category ENUM('KHU_VUI_CHOI','NUOC_DO_AN','PHOTOBOOTH','GRABFOOD') NOT NULL");
        $this->db->where('category', 'pickleball')->delete('gallery');
        $this->db->query("ALTER TABLE gallery MODIFY category ENUM('kids','cafe','photobooth') NOT NULL");

        $this->db->trans_complete();
    }

    public function down()
    {
        show_error('Migration 069 (gỡ pickleball) không rollback được — khôi phục từ bản sao lưu DB.');
    }
}
