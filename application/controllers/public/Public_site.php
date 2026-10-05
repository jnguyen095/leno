<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Website public giới thiệu Leno (khách hàng, không cần đăng
 * nhập) — tách biệt hoàn toàn với hệ thống quản lý nội bộ. Không kế thừa
 * MY_Controller vì controller đó bắt buộc đăng nhập (xem
 * application/core/MY_Controller.php) — cùng cách làm với Menu.php/Trung_thu.php.
 *
 * Chỉ còn 1 trang duy nhất (landing page one-page) — các mục Khu vui chơi/
 * Cà phê/Photobooth/Khuyến mãi/Liên hệ đều là section trong cùng
 * trang chủ, điều hướng bằng anchor (#kids, #cafe...) thay vì route
 * riêng. Xem application/views/public/home.php.
 *
 * Lưu ý đặt tên: spec gốc đề xuất tên "Public", nhưng PHP không cho phép đặt
 * tên class là "Public" (từ khóa dành riêng cho visibility). Dùng
 * "Public_site" thay thế, route qua application/config/routes.php.
 */
class Public_site extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('kds');
        $this->load->model(array('Setting_model', 'Promotion_model', 'Gallery_model'));
    }

    public function index()
    {
        // Nhân viên đã đăng nhập (session còn hạn, hoặc tự đăng nhập lại qua
        // cookie "ghi nhớ") ghé trang public thì header hiện tên thay vì nút
        // Đăng nhập — xem application/views/public/layouts/header.php.
        $current_user = $this->session->userdata('user');
        if ( ! $current_user)
        {
            $current_user = attempt_remember_login();
        }

        $data = array_merge(
            array(
                'current_user' => $current_user,
            ),
            array(
                'site_name'        => $this->Setting_model->get_site_name(),
                'site_phone'       => $this->Setting_model->get_site_phone(),
                'site_address'     => $this->Setting_model->get_site_address(),
                'site_zalo'        => $this->Setting_model->get_site_zalo(),
                'site_facebook'    => $this->Setting_model->get_site_facebook(),
                'site_tiktok'      => $this->Setting_model->get_site_tiktok(),
                'site_google_maps' => $this->Setting_model->get_site_google_maps(),
            ),
            array(
                'promotions' => $this->Promotion_model->get_active(),
                'gallery'    => $this->Gallery_model->get_active(),
            ),
            array(
                'seo' => array(
                    'title'       => 'Leno – Khu vui chơi, Cà phê & Photobooth tại Buôn Ma Thuột',
                    'description' => 'Leno – điểm đến vui chơi, cà phê và photobooth dành cho gia đình và bạn bè tại Buôn Ma Thuột.',
                    'canonical'   => current_url(),
                ),
            )
        );

        $this->load->view('public/layouts/header', $data);
        $this->load->view('public/home', $data);
        $this->load->view('public/layouts/footer', $data);
    }
}
