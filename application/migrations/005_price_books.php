<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bảng giá (price book): giá bán riêng theo dịp — "Giá cuối tuần", "Ưu đãi phụ nữ"...
 * - price_books: tên, ngày bắt đầu / kết thúc (tuỳ chọn), áp dụng các thứ trong tuần, bật/tắt.
 * - price_book_items: giá mới của từng món (món không có dòng = giữ giá gốc).
 * - order_sessions.price_book_id / price_book_name: bảng giá người bán chọn cho đơn (lưu tên để in hóa đơn
 *   đúng cả khi bảng giá bị đổi tên / xoá sau này).
 * - order_items.original_price: giá gốc lúc bán, để hiện giá cũ gạch ngang cạnh giá mới (NULL = không giảm).
 */
class Migration_Price_books extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS price_books (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NULL,
            weekdays VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,7' COMMENT 'ISO: 1 = Thứ 2 ... 7 = Chủ nhật',
            status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_price_books_status (status, start_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS price_book_items (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            price_book_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            price DECIMAL(12,2) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_price_book_product (price_book_id, product_id),
            KEY idx_price_book_items_product (product_id),
            CONSTRAINT fk_pbi_book FOREIGN KEY (price_book_id) REFERENCES price_books (id) ON DELETE CASCADE,
            CONSTRAINT fk_pbi_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query('ALTER TABLE order_sessions
            ADD COLUMN price_book_id INT UNSIGNED NULL AFTER note,
            ADD COLUMN price_book_name VARCHAR(100) NULL AFTER price_book_id');
        $this->db->query('ALTER TABLE order_items
            ADD COLUMN original_price DECIMAL(12,2) NULL AFTER price');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE order_items DROP COLUMN original_price');
        $this->db->query('ALTER TABLE order_sessions DROP COLUMN price_book_name, DROP COLUMN price_book_id');
        $this->db->query('DROP TABLE IF EXISTS price_book_items');
        $this->db->query('DROP TABLE IF EXISTS price_books');
    }
}
