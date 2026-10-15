<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Trang nội dung website: /page/<code>.html (Giới thiệu, Khuyến mãi...), soạn ở Quản trị → Trang nội dung
 * (controllers/Pages.php). Khách chỉ xem được trang đang bật; ADMIN đã đăng nhập xem trước được cả trang đang tắt.
 * Không kế thừa MY_Controller (bắt buộc đăng nhập) — cùng cách làm với Public_site.php.
 */
class Site_page extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('kds');
        $this->load->model(array('Setting_model', 'Page_model'));
    }

    public function show($code = '')
    {
        $current_user = $this->session->userdata('user');
        if ( ! $current_user)
        {
            $current_user = attempt_remember_login();
        }

        $page = preg_match(Page_model::CODE_PATTERN, (string) $code) ? $this->Page_model->get_by_code($code) : NULL;
        $is_admin = $current_user && $current_user['role'] === 'ADMIN';
        if ( ! $page || ( ! (int) $page['status'] && ! $is_admin))
        {
            show_404();
        }

        if ((int) $page['status'] && ! $current_user)
        {
            $this->_count_view($page);
        }

        $content = Page_model::render($page['description']);
        // Mô tả SEO: đoạn chữ đầu của nội dung.
        $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8')));
        $site_name = $this->Setting_model->get_site_name();
        if (preg_match('/<img[^>]+src="([^"]+)"/i', $content, $m)) $og_image = $m[1];

        $data = array(
            'current_user'     => $current_user,
            'site_name'        => $site_name,
            'site_phone'       => $this->Setting_model->get_site_phone(),
            'site_address'     => $this->Setting_model->get_site_address(),
            'site_zalo'        => $this->Setting_model->get_site_zalo(),
            'site_facebook'    => $this->Setting_model->get_site_facebook(),
            'site_tiktok'      => $this->Setting_model->get_site_tiktok(),
            'site_google_maps' => $this->Setting_model->get_site_google_maps(),
            'page'             => $page,
            'content'          => $content,
            'is_preview'       => ! (int) $page['status'],
            'seo' => array(
                'title'       => $page['title'].' – '.$site_name,
                'description' => mb_substr($plain !== '' ? $plain : $page['title'], 0, 160, 'UTF-8'),
                'canonical'   => site_url('page/'.$page['code']),
                'image'       => isset($og_image) ? $og_image : NULL,
            ),
        );

        $this->load->view('public/layouts/header', $data);
        $this->load->view('public/page', $data);
        $this->load->view('public/layouts/footer', $data);
    }

    /**
     * Tính 1 lượt xem cho khách (nhân viên đã đăng nhập không tính — xử lý ở show()). Bỏ qua bot tìm kiếm / xem trước
     * link (Facebook, Zalo...) và lần tải lại của cùng trình duyệt trong 30 phút (cookie theo từng trang).
     */
    private function _count_view($page)
    {
        $ua = (string) $this->input->user_agent();
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|headless|curl|wget|python|httpclient|lighthouse/i', $ua))
        {
            return;
        }
        $cookie = 'pv_'.(int) $page['id'];
        if ($this->input->cookie($cookie))
        {
            return;
        }
        $this->input->set_cookie(array('name' => $cookie, 'value' => '1', 'expire' => 1800, 'httponly' => TRUE));
        $this->Page_model->add_view($page['id']);
    }
}
