<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Trang nội dung và tin tức trên website (soạn ở Quản trị → Trang nội dung / Tin tức — controllers/Pages.php, News.php):
 *  - /page/<code>.html     trang nội dung (Giới thiệu, Khuyến mãi...)
 *  - /tin-tuc.html         danh sách tin (?trang=2 ...)
 *  - /tin-tuc/<code>.html  một tin
 * Khách chỉ xem được bài đang bật (tin: đã tới ngày đăng); ADMIN đã đăng nhập xem trước được bài đang tắt / hẹn giờ.
 * Không kế thừa MY_Controller (bắt buộc đăng nhập) — cùng cách làm với Public_site.php.
 */
class Site_page extends CI_Controller
{
    const NEWS_PER_PAGE = 12;

    protected $current_user;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('kds');
        $this->load->model(array('Setting_model', 'Page_model'));
        $this->current_user = $this->session->userdata('user') ?: attempt_remember_login();
    }

    public function show($code = '')
    {
        $page = $this->_find($code);
        if ($page['type'] === 'NEWS')
        {
            redirect(Page_model::public_path($page), 'location', 301);
        }
        $this->_render_item($page, array('nav_current' => 'page/'.$page['code']));
    }

    public function news($code = '')
    {
        $page = $this->_find($code);
        if ($page['type'] !== 'NEWS')
        {
            redirect(Page_model::public_path($page), 'location', 301);
        }
        $this->_render_item($page, array(
            'nav_current' => 'tin-tuc',
            'more_news'   => $this->Page_model->get_news_published(3, 0, $page['id']),
        ));
    }

    public function news_list()
    {
        $total = $this->Page_model->count_news_published();
        $pages = max(1, (int) ceil($total / self::NEWS_PER_PAGE));
        $current = min($pages, max(1, (int) $this->input->get('trang')));
        $site = $this->Setting_model->get_public_site_data();

        $data = $site + array(
            'current_user' => $this->current_user,
            'nav_current'  => 'tin-tuc',
            'news'         => $this->Page_model->get_news_published(self::NEWS_PER_PAGE, ($current - 1) * self::NEWS_PER_PAGE),
            'paging'       => array('current' => $current, 'pages' => $pages),
            'seo' => array(
                'title'       => 'Tin tức – '.$site['site_name'],
                'description' => 'Tin mới, sự kiện và ưu đãi từ '.$site['site_name'].'.',
                'canonical'   => site_url('tin-tuc').($current > 1 ? '?trang='.$current : ''),
            ),
        );
        $this->load->view('public/layouts/header', $data);
        $this->load->view('public/news_list', $data);
        $this->load->view('public/layouts/footer', $data);
    }

    // ---- Nội bộ ----

    /** Bài theo mã; 404 nếu không có, hoặc khách chưa được xem (đang tắt / tin hẹn giờ — ADMIN vẫn xem trước được). */
    private function _find($code)
    {
        $page = preg_match(Page_model::CODE_PATTERN, (string) $code) ? $this->Page_model->get_by_code($code) : NULL;
        $is_admin = $this->current_user && $this->current_user['role'] === 'ADMIN';
        if ( ! $page || ( ! $this->_is_live($page) && ! $is_admin))
        {
            show_404();
        }
        return $page;
    }

    private function _is_live($page)
    {
        return (int) $page['status'] && ! Page_model::is_scheduled($page);
    }

    /** Hiện một trang nội dung / tin (views/public/page.php). */
    private function _render_item($page, array $extra)
    {
        if ($this->_is_live($page) && ! $this->current_user)
        {
            $this->_count_view($page);
        }

        $content = Page_model::render($page['description']);
        $site = $this->Setting_model->get_public_site_data();
        // Ảnh chia sẻ (Facebook, Zalo...): banner, không có thì ảnh đầu tiên trong nội dung.
        $banner = $page['banner'] ? base_url('assets/'.$page['banner']) : NULL;

        $data = $site + $extra + array(
            'current_user' => $this->current_user,
            'page'         => $page,
            'content'      => $content,
            'banner'       => $banner,
            'gallery'      => Page_model::gallery($page),
            'is_preview'   => ! $this->_is_live($page),
            'more_news'    => array(),
            'seo' => array(
                'title'       => $page['title'].' – '.$site['site_name'],
                'description' => Page_model::excerpt($page, 160) ?: $page['title'],
                'canonical'   => site_url(Page_model::public_path($page)),
                'image'       => Page_model::thumbnail($page),
            ),
        );

        $this->load->view('public/layouts/header', $data);
        $this->load->view('public/page', $data);
        $this->load->view('public/layouts/footer', $data);
    }

    /**
     * Tính 1 lượt xem cho khách (nhân viên đã đăng nhập không tính). Bỏ qua bot tìm kiếm / xem trước
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
