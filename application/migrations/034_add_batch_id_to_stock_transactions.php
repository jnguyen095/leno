<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nhóm các dòng nhập/xuất kho được tạo cùng 1 lần submit (nhiều sản phẩm
 * cùng lúc) lại thành 1 "lô" — dùng cho màn Lịch sử nhập/xuất (thu gọn theo
 * lô, bấm vào mới xổ ra từng sản phẩm) thay vì hiện từng dòng rời rạc.
 */
class Migration_Add_batch_id_to_stock_transactions extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE stock_transactions ADD COLUMN batch_id VARCHAR(32) NULL AFTER id');
        $this->db->query('ALTER TABLE stock_transactions ADD KEY idx_st_batch (batch_id)');

        // Gán batch_id cho các dòng đã có sẵn (trước khi có cột này): các
        // dòng cùng thời điểm/loại/người tạo/điểm xuất coi như cùng 1 lần
        // submit, ghép chung 1 batch_id để không bị tách lẻ trên lịch sử cũ.
        $this->db->query("
            UPDATE stock_transactions st
            JOIN (
                SELECT MIN(id) AS first_id, created_at, type, created_by, dispense_point_id
                FROM stock_transactions
                GROUP BY created_at, type, created_by, dispense_point_id
            ) grp
              ON st.created_at = grp.created_at
             AND st.type = grp.type
             AND (st.created_by = grp.created_by OR (st.created_by IS NULL AND grp.created_by IS NULL))
             AND (st.dispense_point_id = grp.dispense_point_id OR (st.dispense_point_id IS NULL AND grp.dispense_point_id IS NULL))
            SET st.batch_id = CONCAT('legacy-', grp.first_id)
            WHERE st.batch_id IS NULL
        ");
    }

    public function down()
    {
        $this->db->query('ALTER TABLE stock_transactions DROP COLUMN batch_id');
    }
}
