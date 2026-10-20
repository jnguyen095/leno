<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'controllers/Pages.php';

/**
 * Quản trị → Tin tức (chỉ ADMIN): tin / cập nhật của quán. Dùng chung mọi thứ với Trang nội dung (bảng pages,
 * trình soạn thảo, thư viện ảnh, banner, lượt xem) — chỉ khác loại NEWS và thêm tóm tắt, ngày đăng, ghim.
 * Khách xem ở /tin-tuc.html (danh sách), /tin-tuc/<code>.html (chi tiết) và khối "Tin mới" trên trang chủ.
 */
class News extends Pages
{
    protected $type = 'NEWS';
    protected $base = 'me/news';
    protected $labels = array(
        'title' => 'Tin tức', 'icon' => 'bi-newspaper', 'noun' => 'tin',
        'add' => 'Thêm tin', 'edit' => 'Sửa tin', 'public' => 'tin-tuc/',
    );
}
