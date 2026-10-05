<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Seed_data extends CI_Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $hash = password_hash('123456', PASSWORD_DEFAULT);

        $this->db->insert_batch('users', array(
            array('username' => 'admin', 'password' => $hash, 'fullname' => 'Quản trị viên', 'role' => 'ADMIN', 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now),
            array('username' => 'staff1', 'password' => $hash, 'fullname' => 'Nguyễn Văn Phục Vụ', 'role' => 'STAFF', 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now),
            array('username' => 'barista1', 'password' => $hash, 'fullname' => 'Trần Thị Pha Chế', 'role' => 'BARISTA', 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now),
            array('username' => 'cashier1', 'password' => $hash, 'fullname' => 'Lê Văn Thu Ngân', 'role' => 'CASHIER', 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now),
        ));

        $this->db->insert_batch('categories', array(
            array('name' => 'Cà phê', 'sort_order' => 1, 'status' => 'ACTIVE'),
            array('name' => 'Trà trái cây', 'sort_order' => 2, 'status' => 'ACTIVE'),
            array('name' => 'Sinh tố & Đá xay', 'sort_order' => 3, 'status' => 'ACTIVE'),
            array('name' => 'Bánh ngọt', 'sort_order' => 4, 'status' => 'ACTIVE'),
        ));

        $cat = array();
        foreach ($this->db->get('categories')->result_array() as $row)
        {
            $cat[$row['name']] = $row['id'];
        }

        $this->db->insert_batch('products', array(
            array('category_id' => $cat['Cà phê'], 'sku' => 'CF001', 'product_name' => 'Cà phê đen đá', 'price' => 25000, 'description' => 'Cà phê phin truyền thống', 'status' => 'ACTIVE'),
            array('category_id' => $cat['Cà phê'], 'sku' => 'CF002', 'product_name' => 'Cà phê sữa đá', 'price' => 29000, 'description' => 'Cà phê phin kèm sữa đặc', 'status' => 'ACTIVE'),
            array('category_id' => $cat['Cà phê'], 'sku' => 'CF003', 'product_name' => 'Bạc xỉu', 'price' => 32000, 'description' => 'Nhiều sữa, ít cà phê', 'status' => 'ACTIVE'),
            array('category_id' => $cat['Cà phê'], 'sku' => 'CF004', 'product_name' => 'Cappuccino', 'price' => 45000, 'description' => 'Espresso, sữa nóng, bọt sữa', 'status' => 'ACTIVE'),
            array('category_id' => $cat['Trà trái cây'], 'sku' => 'TR001', 'product_name' => 'Trà đào cam sả', 'price' => 39000, 'description' => 'Trà đào, cam, sả tươi', 'status' => 'ACTIVE'),
            array('category_id' => $cat['Trà trái cây'], 'sku' => 'TR002', 'product_name' => 'Trà vải', 'price' => 35000, 'description' => 'Trà vải thơm mát', 'status' => 'ACTIVE'),
            array('category_id' => $cat['Sinh tố & Đá xay'], 'sku' => 'SM001', 'product_name' => 'Sinh tố bơ', 'price' => 42000, 'description' => 'Bơ sáp, sữa tươi', 'status' => 'ACTIVE'),
            array('category_id' => $cat['Sinh tố & Đá xay'], 'sku' => 'SM002', 'product_name' => 'Matcha đá xay', 'price' => 45000, 'description' => 'Matcha Nhật Bản', 'status' => 'ACTIVE'),
            array('category_id' => $cat['Bánh ngọt'], 'sku' => 'CK001', 'product_name' => 'Bánh croissant', 'price' => 25000, 'description' => 'Bơ Pháp giòn xốp', 'status' => 'ACTIVE'),
            array('category_id' => $cat['Bánh ngọt'], 'sku' => 'CK002', 'product_name' => 'Bánh tiramisu', 'price' => 39000, 'description' => 'Vị cà phê, phô mai mascarpone', 'status' => 'ACTIVE'),
        ));

        $tables = array();
        for ($i = 1; $i <= 12; $i++)
        {
            $tables[] = array(
                'table_code' => 'T'.str_pad($i, 2, '0', STR_PAD_LEFT),
                'table_name' => 'Bàn '.$i,
                'capacity' => ($i % 3 === 0) ? 6 : 4,
                'qr_token' => gen_token(32),
                'status' => 'AVAILABLE',
                'created_at' => $now,
                'updated_at' => $now,
            );
        }
        $this->db->insert_batch('cafe_tables', $tables);
    }

    public function down()
    {
        $this->db->truncate('cafe_tables');
        $this->db->truncate('products');
        $this->db->truncate('categories');
        $this->db->truncate('users');
    }
}
