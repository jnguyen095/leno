<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RBAC động — admin gán menu (màn hình) cho từng vai trò, và gán thêm cho
 * riêng 1 nhân viên ngoài vai trò của họ.
 *
 *  - menu_items: danh mục các mục menu có thể gán quyền — mỗi dòng khớp với
 *    1 (controller, danh sách method) cụ thể. methods = NULL nghĩa là toàn
 *    bộ method của controller đó đều thuộc mục menu này.
 *  - role_menu_permissions: (role, menu_item_id) — quyền mặc định theo vai trò.
 *  - user_menu_permissions: (user_id, menu_item_id) — quyền cấp thêm riêng
 *    cho 1 nhân viên, cộng thêm vào quyền theo vai trò (không thay thế).
 *
 * ADMIN luôn có toàn quyền, không bị 2 bảng permission chi phối (xử lý ở
 * MY_Controller, không cần lưu trong DB).
 *
 * Controller/method KHÔNG có trong menu_items vẫn tiếp tục dùng
 * $allowed_roles tĩnh như cũ (không đổi hành vi) — hệ thống này chỉ áp dụng
 * cho các mục đã được liệt kê ở đây.
 */
class Migration_Create_menu_permission_tables extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'          => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'group_label' => array('type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE), // nhóm hiển thị trên màn cấu hình quyền, vd "Kho hàng", "Quản trị"
            'menu_key'    => array('type' => 'VARCHAR', 'constraint' => 100),
            'label'       => array('type' => 'VARCHAR', 'constraint' => 150),
            'icon'        => array('type' => 'VARCHAR', 'constraint' => 50, 'null' => TRUE),
            'controller'  => array('type' => 'VARCHAR', 'constraint' => 100),
            'methods'     => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE), // danh sách method cách nhau dấu phẩy, NULL = toàn bộ
            'route'       => array('type' => 'VARCHAR', 'constraint' => 150, 'null' => TRUE),
            'sort_order'  => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('menu_items', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE menu_items ADD UNIQUE KEY uq_mi_key (menu_key)');
        $this->db->query('ALTER TABLE menu_items ADD KEY idx_mi_controller (controller)');

        $this->dbforge->add_field(array(
            'id'           => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'role'         => array('type' => 'VARCHAR', 'constraint' => 20),
            'menu_item_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('role_menu_permissions', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE role_menu_permissions ADD UNIQUE KEY uq_rmp (role, menu_item_id)');

        $this->dbforge->add_field(array(
            'id'           => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'user_id'      => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'menu_item_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('user_menu_permissions', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE user_menu_permissions ADD UNIQUE KEY uq_ump (user_id, menu_item_id)');
    }

    public function down()
    {
        $this->dbforge->drop_table('user_menu_permissions', TRUE);
        $this->dbforge->drop_table('role_menu_permissions', TRUE);
        $this->dbforge->drop_table('menu_items', TRUE);
    }
}
