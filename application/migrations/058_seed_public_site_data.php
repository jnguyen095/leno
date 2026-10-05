<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dữ liệu khởi tạo cho website public: thông tin liên hệ/mạng xã hội (bảng
 * settings dùng chung) + vài khuyến mãi/ảnh mẫu để trang không trống khi mới
 * bật tính năng — admin có thể sửa/xóa/thêm sau qua DB (chưa có màn quản trị riêng).
 */
class Migration_Seed_public_site_data extends CI_Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $settings = array(
            'site_name'         => 'Leno',
            'site_phone'        => '0974749277',
            'site_address'      => '82 Võ Văn Kiệt, Buôn Ma Thuột, Đắk Lắk',
            'site_zalo'         => '',
            'site_facebook'     => '',
            'site_tiktok'       => '',
            'site_google_maps'  => '',
        );
        foreach ($settings as $key => $value)
        {
            $this->db->insert('settings', array('setting_key' => $key, 'setting_value' => $value, 'updated_at' => $now));
        }

        $this->db->insert_batch('promotions', array(
            array(
                'title'       => 'Pickleball cùng HLV',
                'description' => 'HLV có mặt Thứ 2 – Thứ 6, 10:00 sáng, hỗ trợ người mới bắt đầu.',
                'image'       => 'pickleball/pickleball-01.svg',
                'link'        => 'pickleball',
                'start_date'  => NULL,
                'end_date'    => NULL,
                'status'      => 'ACTIVE',
                'sort_order'  => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ),
            array(
                'title'       => 'Giảm 10% vé khu vui chơi',
                'description' => 'Áp dụng cho tất cả các bé, từ 31/08 đến 02/09.',
                'image'       => 'kids/kids-01.svg',
                'link'        => 'kids',
                'start_date'  => '2026-08-31',
                'end_date'    => '2026-09-02',
                'status'      => 'ACTIVE',
                'sort_order'  => 2,
                'created_at'  => $now,
                'updated_at'  => $now,
            ),
            array(
                'title'       => 'Photobooth check-in',
                'description' => '50.000đ/lần chụp, in thêm ảnh chỉ 20.000đ.',
                'image'       => 'photobooth/photobooth-01.svg',
                'link'        => 'photobooth',
                'start_date'  => NULL,
                'end_date'    => NULL,
                'status'      => 'ACTIVE',
                'sort_order'  => 3,
                'created_at'  => $now,
                'updated_at'  => $now,
            ),
        ));

        $gallery_rows = array();
        $items = array(
            array('kids/kids-01.svg', 'kids', 'Khu vui chơi trẻ em'),
            array('kids/kids-02.svg', 'kids', 'Bé vui chơi cùng bạn bè'),
            array('kids/kids-03.svg', 'kids', 'Không gian vui chơi an toàn'),
            array('pickleball/pickleball-01.svg', 'pickleball', 'Sân pickleball'),
            array('pickleball/pickleball-02.svg', 'pickleball', 'Giờ chơi pickleball'),
            array('pickleball/pickleball-03.svg', 'pickleball', 'Huấn luyện viên hướng dẫn'),
            array('cafe/cafe-01.svg', 'cafe', 'Góc cà phê'),
            array('cafe/cafe-02.svg', 'cafe', 'Đồ uống tại Leno'),
            array('photobooth/photobooth-01.svg', 'photobooth', 'Góc photobooth'),
            array('photobooth/photobooth-02.svg', 'photobooth', 'Check-in cùng bạn bè'),
        );
        foreach ($items as $i => $item)
        {
            $gallery_rows[] = array(
                'title'      => $item[2],
                'image'      => $item[0],
                'category'   => $item[1],
                'status'     => 'ACTIVE',
                'sort_order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            );
        }
        $this->db->insert_batch('gallery', $gallery_rows);
    }

    public function down()
    {
        $this->db->where_in('setting_key', array('site_name', 'site_phone', 'site_address', 'site_zalo', 'site_facebook', 'site_tiktok', 'site_google_maps'))->delete('settings');
        $this->db->empty_table('promotions');
        $this->db->empty_table('gallery');
    }
}
