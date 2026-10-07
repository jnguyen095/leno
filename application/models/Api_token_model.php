<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Bearer token của ứng dụng di động (xem migration 002_api_tokens). */
class Api_token_model extends CI_Model
{
    protected $table = 'api_tokens';

    /** Token sống 30 ngày, tự gia hạn mỗi lần dùng (máy POS dùng hằng ngày thì không phải đăng nhập lại). */
    const TTL_DAYS = 30;

    /** Mỗi user giữ tối đa chừng này thiết bị đăng nhập cùng lúc; vượt thì bỏ token cũ nhất. */
    const MAX_PER_USER = 5;

    /** Tạo token mới, trả về chuỗi token gốc (chỉ lần này). */
    public function create($user_id, $device_name = NULL)
    {
        $token = gen_token(64);
        $now = date('Y-m-d H:i:s');

        $this->db->insert($this->table, array(
            'user_id'      => $user_id,
            'token_hash'   => hash('sha256', $token),
            'device_name'  => $device_name !== NULL ? mb_substr($device_name, 0, 100, 'UTF-8') : NULL,
            'last_used_at' => $now,
            'expires_at'   => date('Y-m-d H:i:s', strtotime('+'.self::TTL_DAYS.' days')),
            'created_at'   => $now,
        ));

        $ids = array_column($this->db->select('id')->where('user_id', $user_id)
            ->order_by('last_used_at', 'DESC')->get($this->table)->result_array(), 'id');
        if (count($ids) > self::MAX_PER_USER)
        {
            $this->db->where_in('id', array_slice($ids, self::MAX_PER_USER))->delete($this->table);
        }

        return $token;
    }

    /** Token hợp lệ -> trả về dòng token và gia hạn; sai/hết hạn -> NULL. */
    public function verify($token)
    {
        if ( ! is_string($token) || $token === '')
        {
            return NULL;
        }

        $row = $this->db->where('token_hash', hash('sha256', $token))->get($this->table)->row_array();
        if ( ! $row)
        {
            return NULL;
        }
        if (strtotime($row['expires_at']) < time())
        {
            $this->db->where('id', $row['id'])->delete($this->table);
            return NULL;
        }

        // Gia hạn tối đa 1 lần/phút để không ghi DB ở mọi request.
        if (strtotime($row['last_used_at']) < time() - 60)
        {
            $this->db->where('id', $row['id'])->update($this->table, array(
                'last_used_at' => date('Y-m-d H:i:s'),
                'expires_at'   => date('Y-m-d H:i:s', strtotime('+'.self::TTL_DAYS.' days')),
            ));
        }
        return $row;
    }

    public function delete_by_token($token)
    {
        return $this->db->where('token_hash', hash('sha256', (string) $token))->delete($this->table);
    }

    public function delete_for_user($user_id)
    {
        return $this->db->where('user_id', $user_id)->delete($this->table);
    }
}
