<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Khuyến mãi / giảm giá trên tổng đơn (khác bảng `promotions` = banner của trang web public):
 * - discounts: giảm theo % (có thể giới hạn số tiền giảm tối đa) hoặc số tiền cố định, áp cho đơn từ một giá trị
 *   tối thiểu (tuỳ chọn); hiệu lực theo khoảng ngày + thứ trong tuần, bật/tắt — giống bảng giá.
 * - order_sessions.discount_*: khuyến mãi người bán chọn cho đơn, lưu nguyên điều kiện tại lúc chọn để đơn không đổi
 *   khi khuyến mãi bị sửa / xoá. Số tiền giảm (discount_amount, đã có) được tính lại mỗi khi tổng đơn thay đổi.
 */
class Migration_Discounts extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS discounts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            discount_type ENUM('PERCENT','AMOUNT') NOT NULL DEFAULT 'PERCENT',
            value DECIMAL(12,2) NOT NULL COMMENT 'PERCENT: 0-100, AMOUNT: số tiền',
            max_discount DECIMAL(12,2) NULL COMMENT 'PERCENT: giảm tối đa (NULL = không giới hạn)',
            min_order DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Áp dụng cho đơn có tạm tính từ giá trị này',
            start_date DATE NOT NULL,
            end_date DATE NULL,
            weekdays VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,7' COMMENT 'ISO: 1 = Thứ 2 ... 7 = Chủ nhật',
            status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_discounts_status (status, start_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("ALTER TABLE order_sessions
            ADD COLUMN discount_id INT UNSIGNED NULL AFTER price_book_name,
            ADD COLUMN discount_name VARCHAR(100) NULL AFTER discount_id,
            ADD COLUMN discount_type ENUM('PERCENT','AMOUNT') NULL AFTER discount_name,
            ADD COLUMN discount_value DECIMAL(12,2) NULL AFTER discount_type,
            ADD COLUMN discount_max DECIMAL(12,2) NULL AFTER discount_value,
            ADD COLUMN discount_min_order DECIMAL(12,2) NULL AFTER discount_max");
    }

    public function down()
    {
        $this->db->query('ALTER TABLE order_sessions DROP COLUMN discount_min_order, DROP COLUMN discount_max,
            DROP COLUMN discount_value, DROP COLUMN discount_type, DROP COLUMN discount_name, DROP COLUMN discount_id');
        $this->db->query('DROP TABLE IF EXISTS discounts');
    }
}
