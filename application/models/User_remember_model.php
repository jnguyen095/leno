<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_remember_model extends CI_Model
{
    protected $table = 'user_remember_tokens';

    /** Số ngày cookie "ghi nhớ đăng nhập" tồn tại kể từ lúc tạo — cố định, không tự gia hạn thêm khi dùng. */
    const TTL_DAYS = 365;

    /**
     * Tạo token mới cho user (xoá các token cũ của user này trước — mỗi user chỉ
     * giữ 1 "remember me" hiệu lực tại một thời điểm). Trả về chuỗi cookie dạng
     * "selector:validator" để lưu vào cookie trình duyệt.
     */
    public function create($user_id)
    {
        $this->db->where('user_id', $user_id)->delete($this->table);

        $selector = gen_token(16);
        $validator = gen_token(32);

        $this->db->insert($this->table, array(
            'user_id'        => $user_id,
            'selector'       => $selector,
            'validator_hash' => hash('sha256', $validator),
            'expires_at'     => date('Y-m-d H:i:s', strtotime('+'.self::TTL_DAYS.' days')),
            'created_at'     => date('Y-m-d H:i:s'),
        ));

        return $selector.':'.$validator;
    }

    /**
     * Kiểm tra cookie "selector:validator" còn hợp lệ không. Nếu hợp lệ, xoay
     * (rotate) validator mới — giữ nguyên hạn dùng gốc — để phòng trường hợp
     * cookie cũ bị lộ vẫn đăng nhập lại được (đánh cắp 1 lần là mất tác dụng).
     * Trả về ['user_id'=>, 'cookie'=>'selector:validator_mới'] hoặc FALSE.
     */
    public function verify_and_rotate($cookie_value)
    {
        if ( ! $cookie_value || strpos($cookie_value, ':') === FALSE)
        {
            return FALSE;
        }

        list($selector, $validator) = explode(':', $cookie_value, 2);

        $row = $this->db->where('selector', $selector)->get($this->table)->row_array();

        if ( ! $row || strtotime($row['expires_at']) < time())
        {
            return FALSE;
        }

        if ( ! hash_equals($row['validator_hash'], hash('sha256', $validator)))
        {
            // Selector khớp nhưng validator sai — dấu hiệu cookie bị đánh cắp/hỏng, huỷ luôn token này.
            $this->db->where('id', $row['id'])->delete($this->table);
            return FALSE;
        }

        $new_validator = gen_token(32);
        $this->db->where('id', $row['id'])->update($this->table, array('validator_hash' => hash('sha256', $new_validator)));

        return array('user_id' => $row['user_id'], 'cookie' => $selector.':'.$new_validator, 'expires_at' => $row['expires_at']);
    }

    public function delete_for_user($user_id)
    {
        return $this->db->where('user_id', $user_id)->delete($this->table);
    }
}
