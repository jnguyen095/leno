<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Token "Ghi nhớ đăng nhập" (cookie "selector:validator", DB chỉ lưu hash của validator).
 * Mỗi THIẾT BỊ một token: đăng nhập máy POS không làm mất ghi nhớ trên điện thoại và ngược lại.
 */
class User_remember_model extends CI_Model
{
    protected $table = 'user_remember_tokens';

    /** Số ngày cookie "ghi nhớ đăng nhập" tồn tại kể từ lúc tạo — cố định, không tự gia hạn thêm khi dùng. */
    const TTL_DAYS = 365;

    /** Xoay validator tối đa 1 lần / khoảng này (không xoay ở mỗi request). */
    const ROTATE_AFTER_SECONDS = 900;

    /**
     * Sau khi xoay, validator cũ vẫn được chấp nhận trong khoảng này — các request gửi song song
     * (poll 5 giây, AJAX, khung toàn màn hình...) còn mang cookie cũ không bị đá ra màn hình đăng nhập.
     */
    const GRACE_SECONDS = 300;

    /**
     * Tạo token mới cho user trên thiết bị hiện tại. Chỉ dọn token đã hết hạn của user này —
     * KHÔNG xoá token của thiết bị khác. Trả về chuỗi cookie "selector:validator".
     */
    public function create($user_id)
    {
        $this->db->where('user_id', $user_id)->where('expires_at <', date('Y-m-d H:i:s'))->delete($this->table);

        $selector = gen_token(16);
        $validator = gen_token(32);
        $now = date('Y-m-d H:i:s');

        $this->db->insert($this->table, array(
            'user_id'        => $user_id,
            'selector'       => $selector,
            'validator_hash' => hash('sha256', $validator),
            'rotated_at'     => $now,
            'last_used_at'   => $now,
            'expires_at'     => date('Y-m-d H:i:s', strtotime('+'.self::TTL_DAYS.' days')),
            'created_at'     => $now,
        ));

        return $selector.':'.$validator;
    }

    /**
     * Kiểm tra cookie "selector:validator". Hợp lệ khi khớp validator hiện tại, hoặc khớp validator
     * vừa bị thay trong vòng GRACE_SECONDS. Validator được xoay (giữ nguyên hạn gốc) nếu lần xoay trước
     * đã quá ROTATE_AFTER_SECONDS — cookie bị lộ cũng chỉ dùng được trong thời gian ngắn.
     * Trả về ['user_id'=>, 'cookie'=> chuỗi mới hoặc NULL nếu không đổi, 'expires_at'=>] hoặc FALSE.
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

        $hash = hash('sha256', $validator);
        $now = time();
        $rotated_at = $row['rotated_at'] ? strtotime($row['rotated_at']) : 0;

        $matches_current = hash_equals($row['validator_hash'], $hash);
        $matches_previous = ! $matches_current
            && $row['prev_validator_hash'] !== NULL
            && hash_equals($row['prev_validator_hash'], $hash)
            && ($now - $rotated_at) <= self::GRACE_SECONDS;

        if ( ! $matches_current && ! $matches_previous)
        {
            // Không xoá token: một request đến muộn mang cookie quá cũ không được làm mất đăng nhập
            // của chính thiết bị đó (cookie hiện tại của nó vẫn hợp lệ).
            return FALSE;
        }

        $update = array('last_used_at' => date('Y-m-d H:i:s', $now));
        $new_cookie = NULL;

        if ($matches_current && ($now - $rotated_at) >= self::ROTATE_AFTER_SECONDS)
        {
            $new_validator = gen_token(32);
            $update['prev_validator_hash'] = $row['validator_hash'];
            $update['validator_hash'] = hash('sha256', $new_validator);
            $update['rotated_at'] = date('Y-m-d H:i:s', $now);
            $new_cookie = $selector.':'.$new_validator;
        }

        $this->db->where('id', $row['id'])->update($this->table, $update);

        return array('user_id' => $row['user_id'], 'cookie' => $new_cookie, 'expires_at' => $row['expires_at']);
    }

    /** Đăng xuất: chỉ huỷ token của thiết bị đang dùng (theo selector trong cookie). */
    public function delete_by_cookie($cookie_value)
    {
        if ( ! $cookie_value || strpos($cookie_value, ':') === FALSE)
        {
            return FALSE;
        }
        list($selector) = explode(':', $cookie_value, 2);
        return $this->db->where('selector', $selector)->delete($this->table);
    }

    /** Huỷ ghi nhớ trên MỌI thiết bị của user (vd khi đổi mật khẩu). */
    public function delete_for_user($user_id)
    {
        return $this->db->where('user_id', $user_id)->delete($this->table);
    }
}
