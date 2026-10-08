<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Ghi nhớ đăng nhập" bị mất liên tục: validator được xoay (rotate) ở MỖI lần dùng, nên khi phiên
 * hết hạn mà trang POS gửi nhiều request cùng lúc (poll 5 giây, AJAX...), request thứ 2 trở đi mang
 * validator cũ -> bị coi là cookie đánh cắp -> xoá token -> buộc nhập lại mật khẩu.
 *
 * Thêm prev_validator_hash + rotated_at để validator cũ vẫn hợp lệ thêm một khoảng ngắn sau khi xoay
 * (xem User_remember_model::verify_and_rotate()), và last_used_at để biết thiết bị nào còn dùng.
 */
class Migration_Remember_token_grace extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE user_remember_tokens
            ADD COLUMN prev_validator_hash VARCHAR(64) NULL AFTER validator_hash,
            ADD COLUMN rotated_at DATETIME NULL AFTER prev_validator_hash,
            ADD COLUMN last_used_at DATETIME NULL AFTER rotated_at');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE user_remember_tokens
            DROP COLUMN last_used_at, DROP COLUMN rotated_at, DROP COLUMN prev_validator_hash');
    }
}
