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

    /** Bật bán mang đi = hiện bàn "Mang đi" trên sơ đồ bàn. */
    public function is_takeaway_enabled()
    {
        return $this->get('takeaway_enabled', '0') === '1';
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

    // ---- Chuyển khoản / VietQR (in mã QR trên phiếu tạm tính của ứng dụng POS) ----

    /** Ngân hàng hỗ trợ VietQR: mã BIN NAPAS => tên hiển thị. */
    const VIETQR_BANKS = array(
        '970415' => 'VietinBank',
        '970436' => 'Vietcombank',
        '970418' => 'BIDV',
        '970405' => 'Agribank',
        '970407' => 'Techcombank',
        '970422' => 'MB Bank',
        '970416' => 'ACB',
        '970432' => 'VPBank',
        '970423' => 'TPBank',
        '970403' => 'Sacombank',
        '970441' => 'VIB',
        '970443' => 'SHB',
        '970437' => 'HDBank',
        '970448' => 'OCB',
        '970426' => 'MSB',
        '970440' => 'SeABank',
        '970431' => 'Eximbank',
        '970449' => 'LPBank',
        '970428' => 'Nam A Bank',
        '970409' => 'Bac A Bank',
        '970425' => 'ABBank',
    );

    /**
     * Thông tin nhận chuyển khoản cho mã VietQR. 'enabled' = bật in QR và đã có đủ ngân hàng + số tài khoản.
     * Trả về: enabled, bin, bank_name, account_no, account_name.
     */
    public function get_bank_qr()
    {
        $bin = (string) $this->get('bank_qr_bin', '');
        $account_no = (string) $this->get('bank_qr_account_no', '');
        return array(
            'enabled'      => $this->get('bank_qr_enabled', '0') === '1' && isset(self::VIETQR_BANKS[$bin]) && $account_no !== '',
            'bin'          => $bin,
            'bank_name'    => isset(self::VIETQR_BANKS[$bin]) ? self::VIETQR_BANKS[$bin] : '',
            'account_no'   => $account_no,
            'account_name' => (string) $this->get('bank_qr_account_name', ''),
        );
    }

    // ---- Màn hình khách (màn hình phụ máy POS) ----

    /** Giá trị mặc định của các tuỳ chọn màn hình khách (khóa settings = 'display_' + tên). */
    const DISPLAY_DEFAULTS = array(
        'slide_seconds'     => '8',
        'show_logo'         => '1',
        'welcome_text'      => 'Chào mừng quý khách đến với Leno',
        'thanks_text'       => 'Cảm ơn quý khách - Hẹn gặp lại!',
        'thanks_seconds'    => '6',
        'show_item_notes'   => '0',
        'show_qr'           => '1',
        'text_scale'        => '1',
    );

    /** Tuỳ chọn màn hình khách, đã ép kiểu, dùng cho trang quản trị và API ứng dụng POS. */
    public function get_display_config()
    {
        $v = array();
        foreach (self::DISPLAY_DEFAULTS as $key => $default)
        {
            $v[$key] = (string) $this->get('display_'.$key, $default);
        }
        return array(
            'slide_seconds'   => max(3, min(120, (int) $v['slide_seconds'])),
            'show_logo'       => $v['show_logo'] === '1',
            'welcome_text'    => $v['welcome_text'],
            'thanks_text'     => $v['thanks_text'],
            'thanks_seconds'  => max(2, min(60, (int) $v['thanks_seconds'])),
            'show_item_notes' => $v['show_item_notes'] === '1',
            'show_qr'         => $v['show_qr'] === '1',
            'text_scale'      => max(0.8, min(1.6, (float) $v['text_scale'])),
        );
    }

    /** Link nhúng Google Maps tùy chỉnh — rỗng thì site public tự build link tìm theo địa chỉ. */
    public function get_site_google_maps()
    {
        return $this->get('site_google_maps', '') ?: NULL;
    }
}
