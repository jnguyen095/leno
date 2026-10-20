<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Trang nội dung website (xem migration 009). Nội dung lưu link nội bộ dạng {{base_url}}... để chuyển
 * dữ liệu giữa máy (localhost/leno/) và máy chủ thật (/) mà ảnh vẫn hiện đúng.
 */
class Page_model extends CI_Model
{
    protected $table = 'pages';

    const BASE_TOKEN = '{{base_url}}';
    const CODE_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Google Fonts có thể chọn trong trình soạn thảo (bảng phông ở views/pages/form.php) — nạp ở cả trang soạn và trang website. */
    const FONTS_URL = 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,700;1,400&family=Roboto:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,400;0,700;1,400&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Dancing+Script:wght@400;700&display=swap&subset=vietnamese';

    const TYPES = array('PAGE', 'NEWS');

    /** Danh sách quản trị theo loại: trang nội dung mới sửa trước; tin tức ghim trước rồi mới đăng trước. */
    public function get_all($type = 'PAGE')
    {
        $this->db->select('p.*, cu.fullname AS created_by_name, uu.fullname AS updated_by_name')
            ->from($this->table.' p')
            ->join('users cu', 'cu.id = p.created_by', 'left')
            ->join('users uu', 'uu.id = p.updated_by', 'left')
            ->where('p.type', $type);
        if ($type === 'NEWS') $this->db->order_by('p.is_pinned', 'DESC')->order_by('p.published_at', 'DESC');
        return $this->db->order_by('p.updated_at', 'DESC')->order_by('p.id', 'DESC')->get()->result_array();
    }

    /** Tin khách xem được: đang bật, đã tới ngày đăng. Ghim trước, mới trước. $except_id: bỏ tin đang xem. */
    public function get_news_published($limit, $offset = 0, $except_id = NULL)
    {
        $this->_where_news_published();
        if ($except_id) $this->db->where('id !=', (int) $except_id);
        return $this->db->order_by('is_pinned', 'DESC')->order_by('published_at', 'DESC')->order_by('id', 'DESC')
            ->limit((int) $limit, (int) $offset)->get($this->table)->result_array();
    }

    public function count_news_published()
    {
        $this->_where_news_published();
        return $this->db->count_all_results($this->table);
    }

    private function _where_news_published()
    {
        $this->db->where('type', 'NEWS')->where('status', 1)
            ->group_start()->where('published_at IS NULL', NULL, FALSE)->or_where('published_at <=', date('Y-m-d H:i:s'))->group_end();
    }

    /** Đường dẫn trên website: tin tức /tin-tuc/<code>, trang nội dung /page/<code> (dùng với site_url). */
    public static function public_path($page)
    {
        return (isset($page['type']) && $page['type'] === 'NEWS' ? 'tin-tuc/' : 'page/').$page['code'];
    }

    /** Tin hẹn giờ (ngày đăng trong tương lai). */
    public static function is_scheduled($page)
    {
        return ! empty($page['published_at']) && $page['published_at'] > date('Y-m-d H:i:s');
    }

    /** Tóm tắt cho thẻ tin: ô Tóm tắt, rỗng thì lấy đoạn đầu nội dung. */
    public static function excerpt($page, $max = 160)
    {
        $text = trim((string) $page['summary']);
        if ($text === '')
        {
            $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', (string) $page['description'])), ENT_QUOTES, 'UTF-8')));
        }
        return mb_strlen($text, 'UTF-8') > $max ? rtrim(mb_substr($text, 0, $max - 1, 'UTF-8')).'…' : $text;
    }

    /** Ảnh đại diện cho thẻ tin: banner, không có thì ảnh đầu tiên trong nội dung; NULL nếu không có ảnh. */
    public static function thumbnail($page)
    {
        if ( ! empty($page['banner'])) return base_url('assets/'.$page['banner']);
        if (preg_match('/<img[^>]+src="([^"]+)"/i', self::render($page['description']), $m)) return $m[1];
        return NULL;
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', (int) $id)->get($this->table)->row_array();
    }

    public function get_by_code($code)
    {
        return $this->db->where('code', (string) $code)->get($this->table)->row_array();
    }

    public function code_exists($code, $except_id = NULL)
    {
        $this->db->where('code', $code);
        if ($except_id) $this->db->where('id !=', (int) $except_id);
        return $this->db->count_all_results($this->table) > 0;
    }

    /** Thêm ($id NULL) hoặc sửa trang; trả về id. */
    public function save($id, array $row, $user_id)
    {
        $now = date('Y-m-d H:i:s');
        $row['description'] = self::to_storage($row['description']);
        $row['updated_at'] = $now;
        $row['updated_by'] = $user_id;
        if ($id)
        {
            $this->db->where('id', (int) $id)->update($this->table, $row);
            return (int) $id;
        }
        $row['created_at'] = $now;
        $row['created_by'] = $user_id;
        $this->db->insert($this->table, $row);
        return (int) $this->db->insert_id();
    }

    public function set_status($id, $status, $user_id)
    {
        return $this->db->where('id', (int) $id)->update($this->table, array(
            'status' => $status ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $user_id,
        ));
    }

    /** +1 lượt xem (không đổi ngày sửa / người sửa). */
    public function add_view($id)
    {
        $this->db->set('view_count', 'view_count + 1', FALSE)
            ->set('last_viewed_at', date('Y-m-d H:i:s'))
            ->where('id', (int) $id)->update($this->table);
    }

    public function delete($id)
    {
        return $this->db->where('id', (int) $id)->delete($this->table);
    }

    /** Trang / tin nào đang dùng tệp ảnh này (trong nội dung hoặc làm banner): [[code, type, title], ...]. */
    public function pages_using($file_name)
    {
        return $this->db->select('code, type, title')
            ->group_start()->like('description', $file_name)->or_like('banner', $file_name)->group_end()
            ->get($this->table)->result_array();
    }

    /** Nội dung để hiện (website / trình soạn thảo): đổi {{base_url}} về địa chỉ thật. */
    public static function render($html)
    {
        return str_replace(self::BASE_TOKEN, base_url(), (string) $html);
    }

    /** Nội dung để lưu: lọc mã nguy hiểm, đổi địa chỉ trang web thành {{base_url}}. */
    public static function to_storage($html)
    {
        $html = self::sanitize($html);
        return str_replace(base_url(), self::BASE_TOKEN, $html);
    }

    /**
     * Lọc HTML từ trình soạn thảo: bỏ thẻ chạy mã (script, style, iframe ngoài YouTube...), thuộc tính on*,
     * link javascript:. Chỉ quản trị viên soạn, đây là lớp bảo vệ thêm khi dán mã HTML.
     */
    public static function sanitize($html)
    {
        $html = (string) $html;
        if (trim($html) === '') return '';
        $html = preg_replace('#<(script|style|object|embed|applet|form|meta|link|base)\b[^>]*>.*?</\1\s*>#is', '', $html);
        $html = preg_replace('#<(script|style|object|embed|applet|form|input|button|textarea|select|meta|link|base)\b[^>]*/?>#is', '', $html);
        // iframe: chỉ giữ video YouTube / bản đồ Google.
        $html = preg_replace_callback('#<iframe\b[^>]*>.*?</iframe\s*>#is', function ($m) {
            return preg_match('#src\s*=\s*["\']https://(www\.)?(youtube\.com|youtube-nocookie\.com|google\.com/maps)/#i', $m[0]) ? $m[0] : '';
        }, $html);
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\'])\s*(javascript|vbscript|data):(?!image/)[^"\']*\2#i', '$1=$2#$2', $html);
        return $html;
    }
}
