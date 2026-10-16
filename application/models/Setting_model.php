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

    // ---- Thông tin in trên phiếu (tạm tính / hóa đơn) — Cài đặt → Thông tin in phiếu ----

    const RECEIPT_DEFAULTS = array(
        'shop_name' => 'Leno',
        'address'   => '28 Võ Văn Kiệt, BMT',
        'phone'     => '',
        'footer'    => 'Cảm ơn quý khách - Hẹn gặp lại!',
    );

    /** Trả về: shop_name, address, phone, footer (chưa cấu hình thì dùng mặc định ở trên). */
    public function get_receipt_info()
    {
        $info = array();
        foreach (self::RECEIPT_DEFAULTS as $key => $default)
        {
            $info[$key] = (string) $this->get('receipt_'.$key, $default);
        }
        return $info;
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

    /** Giờ mở cửa hiện trên website (vd "7:00 – 22:00 hằng ngày") — rỗng thì ẩn. */
    public function get_site_hours()
    {
        return $this->get('site_hours', '') ?: NULL;
    }

    /** Mọi thông tin website (Cài đặt → Website), giá trị thô — rỗng là chưa đặt. */
    public function get_site_info()
    {
        $info = array();
        foreach (self::SITE_FIELDS as $key)
        {
            $info[$key] = (string) $this->get('site_'.$key, '');
        }
        return $info;
    }

    /** Các khóa site_* sửa được ở Cài đặt → Website. */
    const SITE_FIELDS = array('name', 'phone', 'address', 'hours', 'zalo', 'facebook', 'tiktok', 'google_maps', 'ga4_id');

    /**
     * Dữ liệu chung cho mọi trang website public (header / footer / liên hệ) — dùng ở Public_site, Site_page.
     * zalo: nhập số điện thoại cũng được, tự đổi thành link https://zalo.me/<số>.
     */
    public function get_public_site_data()
    {
        $zalo = $this->get_site_zalo();
        if ($zalo && preg_match('/^[0-9+ .]+$/', $zalo))
        {
            $zalo = 'https://zalo.me/'.preg_replace('/[^0-9]/', '', $zalo);
        }
        $address = $this->get_site_address();
        $maps = $this->get_site_google_maps();
        return array(
            'site_name'        => $this->get_site_name(),
            'site_phone'       => $this->get_site_phone(),
            'site_phone_link'  => 'tel:'.preg_replace('/[^0-9+]/', '', $this->get_site_phone()),
            'site_address'     => $address,
            'site_hours'       => $this->get_site_hours(),
            'site_zalo'        => $zalo,
            'site_facebook'    => $this->get_site_facebook(),
            'site_tiktok'      => $this->get_site_tiktok(),
            'site_google_maps' => $maps,
            // "Chỉ đường": link Google Maps của quán nếu có, không thì tìm theo địa chỉ. Bản đồ nhúng luôn tìm theo địa chỉ
            // (link chia sẻ maps.app.goo.gl không nhúng được vào iframe).
            'site_directions_url' => $maps ?: 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address),
            'site_map_embed_url'  => 'https://www.google.com/maps?q='.rawurlencode($address).'&output=embed',
            // Mã đo lường Google Analytics 4 (G-XXXXXXX) — rỗng thì không gắn GA (xem views/public/layouts/header.php).
            'site_ga4_id'         => $this->get('site_ga4_id', '') ?: NULL,
        );
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

    /** Link Google Maps của quán (nút "Chỉ đường") — rỗng thì tự tìm theo địa chỉ. Bản đồ nhúng luôn tìm theo địa chỉ. */
    public function get_site_google_maps()
    {
        return $this->get('site_google_maps', '') ?: NULL;
    }
}
