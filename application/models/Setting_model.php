<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends CI_Model
{
    protected $table = 'settings';

    public function get($key, $default = NULL)
    {
        $row = $this->db->where('setting_key', $key)->get($this->table)->row_array();
        return $row ? $row['setting_value'] : $default;
    }

    public function set($key, $value)
    {
        $exists = $this->db->where('setting_key', $key)->get($this->table)->row_array();
        $data = array('setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s'));

        if ($exists)
        {
            return $this->db->where('setting_key', $key)->update($this->table, $data);
        }

        $data['setting_key'] = $key;
        return $this->db->insert($this->table, $data);
    }

    /** Tỷ lệ VAT dạng thập phân (vd 0.08), dùng trực tiếp trong tính toán hóa đơn. */
    public function get_vat_rate()
    {
        return ((float) $this->get('vat_percent', 8)) / 100;
    }

    public function get_vat_percent()
    {
        return (float) $this->get('vat_percent', 8);
    }

    /** Thời điểm mở đăng ký quà Trung Thu, định dạng 'Y-m-d H:i:s'. Rỗng/NULL = không giới hạn (mở sẵn). */
    public function get_trung_thu_open_at()
    {
        return $this->get('trung_thu_open_at') ?: NULL;
    }

    /** Thời điểm đóng đăng ký quà Trung Thu, định dạng 'Y-m-d H:i:s'. Rỗng/NULL = không giới hạn (không tự đóng). */
    public function get_trung_thu_close_at()
    {
        return $this->get('trung_thu_close_at') ?: NULL;
    }

    // ---- Thông tin website public (site/xem application/controllers/Public.php) ----

    public function get_site_name()
    {
        return $this->get('site_name', 'Leno');
    }

    public function get_site_phone()
    {
        return $this->get('site_phone', '0974749277');
    }

    public function get_site_address()
    {
        return $this->get('site_address', '82 Võ Văn Kiệt, Buôn Ma Thuột, Đắk Lắk');
    }

    /** Link Zalo (vd https://zalo.me/...) — rỗng nghĩa là chưa cấu hình, ẩn nút Zalo trên site public. */
    public function get_site_zalo()
    {
        return $this->get('site_zalo', '') ?: NULL;
    }

    public function get_site_facebook()
    {
        return $this->get('site_facebook', '') ?: NULL;
    }

    public function get_site_tiktok()
    {
        return $this->get('site_tiktok', '') ?: NULL;
    }

    /** Link nhúng Google Maps tùy chỉnh — rỗng thì site public tự build link tìm theo địa chỉ. */
    public function get_site_google_maps()
    {
        return $this->get('site_google_maps', '') ?: NULL;
    }
}
