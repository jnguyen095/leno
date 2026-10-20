<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản trị → Trang nội dung (chỉ ADMIN): soạn trang cho website (Giới thiệu, Khuyến mãi...) bằng CKEditor.
 * Khách xem ở /page/<code>.html (controllers/public/Site_page.php).
 * Ảnh chèn vào nội dung: tải lên assets/uploads/pages/, quản lý (xem, chép link, xoá) ở /me/pages/images.
 * Tin tức (controllers/News.php) kế thừa controller này với $type = NEWS.
 */
class Pages extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    /** Loại nội dung quản lý ở controller này (News kế thừa và đổi thành NEWS) + đường dẫn quản trị + chữ hiển thị. */
    protected $type = 'PAGE';
    protected $base = 'me/pages';
    protected $labels = array(
        'title' => 'Trang nội dung', 'icon' => 'bi-file-earmark-richtext', 'noun' => 'trang',
        'add' => 'Thêm trang', 'edit' => 'Sửa trang', 'public' => 'page/',
    );

    const UPLOAD_DIR = 'uploads/pages/';
    const MAX_KB = 5120;   // giới hạn tệp gửi lên (trình duyệt đã tự thu nhỏ ảnh > SHRINK_KB trước khi gửi)
    const SHRINK_KB = 2048; // ảnh lớn hơn 2MB được thu nhỏ (nếu máy chủ có thư viện ảnh GD)
    const MAX_SIDE = 1920;
    const IMAGE_TYPES = 'jpg|jpeg|png|webp|gif';

    public function __construct()
    {
        parent::__construct();
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            exit;
        }
        $this->load->model('Page_model');
    }

    public function index()
    {
        $data = array(
            'page_title'   => $this->labels['title'],
            'current_user' => $this->current_user,
            'pages'        => $this->Page_model->get_all($this->type),
        ) + $this->_view_vars();
        $this->load->view('layout/header', $data);
        $this->load->view('pages/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        $this->_form(NULL);
    }

    public function edit($id)
    {
        $page = $this->Page_model->get_by_id($id);
        if ( ! $page || $page['type'] !== $this->type) show_404();
        $this->_form($page);
    }

    public function toggle($id)
    {
        $page = $this->_post_page($id);
        $status = (int) $page['status'] ? 0 : 1;
        $this->Page_model->set_status($page['id'], $status, $this->current_user['id']);
        $this->audit('page', 'TOGGLE', array('status' => (int) $page['status']), array('id' => (int) $page['id'], 'status' => $status));
        $this->session->set_flashdata('success', ($status ? 'Đã bật' : 'Đã tắt').' '.$this->labels['noun'].' "'.$page['title'].'".');
        redirect($this->base);
    }

    public function delete($id)
    {
        $page = $this->_post_page($id);
        $this->Page_model->delete($page['id']);
        $this->audit('page', 'DELETE', $page, NULL);
        $this->session->set_flashdata('success', 'Đã xoá '.$this->labels['noun'].' "'.$page['title'].'". Ảnh đã tải lên vẫn còn trong Thư viện ảnh.');
        redirect($this->base);
    }

    /** Biến chung cho view pages/index, pages/form, pages/_actions. */
    protected function _view_vars()
    {
        return array('type' => $this->type, 'base' => $this->base, 'labels' => $this->labels);
    }

    // ---- Thư viện ảnh ----

    /** Trang quản lý ảnh: xem, tải lên, chép link, xoá. */
    public function images()
    {
        $data = array(
            'page_title'   => 'Thư viện ảnh',
            'current_user' => $this->current_user,
            'images'       => $this->_list_images(TRUE),
            'max_kb'       => self::MAX_KB,
            'shrink_kb'    => self::SHRINK_KB,
            'error'        => $this->session->flashdata('error'),
            'success'      => $this->session->flashdata('success'),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('pages/images', $data);
        $this->load->view('layout/footer');
    }

    /** JSON danh sách ảnh cho hộp chọn ảnh trong trình soạn thảo. */
    public function image_list()
    {
        $this->_json(array('images' => $this->_list_images(FALSE)));
    }

    /**
     * Tải ảnh lên (POST multipart, tệp ở trường "upload" — trình soạn thảo, hoặc "image" — trang Thư viện ảnh).
     * Gọi bằng AJAX trả JSON {url, name}; từ form trang Thư viện ảnh thì quay lại trang đó.
     */
    public function upload_image()
    {
        if ($this->input->method() !== 'post') show_404();
        $field = ! empty($_FILES['upload']['name']) ? 'upload' : 'image';
        $error = NULL;
        $name = $this->_handle_upload($field, $error);
        if ($name !== NULL)
        {
            $this->audit('page', 'UPLOAD_IMAGE', NULL, array('file' => self::UPLOAD_DIR.$name));
        }

        if ($this->input->is_ajax_request())
        {
            if ($name === NULL)
            {
                $this->_json(array('error' => array('message' => $error)), 400);
                return;
            }
            $this->_json(array('url' => base_url('assets/'.self::UPLOAD_DIR.$name), 'name' => $name));
            return;
        }

        $this->session->set_flashdata($name === NULL ? 'error' : 'success', $name === NULL ? $error : 'Đã tải ảnh lên.');
        redirect('me/pages/images');
    }

    public function image_delete()
    {
        if ($this->input->method() !== 'post') show_404();
        $name = basename((string) $this->input->post('name'));
        $path = FCPATH.'assets/'.self::UPLOAD_DIR.$name;
        if ( ! preg_match('/^[A-Za-z0-9_.-]+$/', $name) || ! is_file($path)) show_404();

        @unlink($path);
        $this->audit('page', 'DELETE_IMAGE', array('file' => self::UPLOAD_DIR.$name), NULL);
        $this->session->set_flashdata('success', 'Đã xoá ảnh '.$name.'.');
        redirect('me/pages/images');
    }

    // ---- Nội bộ ----

    protected function _form($page)
    {
        $error = NULL;
        $is_news = $this->type === 'NEWS';
        $values = array(
            'code'         => $page ? $page['code'] : '',
            'title'        => $page ? $page['title'] : '',
            'description'  => $page ? Page_model::render($page['description']) : '',
            'status'       => $page ? (int) $page['status'] : 1,
            'banner'       => $page ? (string) $page['banner'] : '',
            // Tin tức: tóm tắt, ngày đăng (ô datetime-local: Y-m-d\TH:i), ghim.
            'summary'      => $page ? (string) $page['summary'] : '',
            'published_at' => $page && $page['published_at'] ? date('Y-m-d\TH:i', strtotime($page['published_at'])) : date('Y-m-d\TH:i'),
            'is_pinned'    => $page ? (int) $page['is_pinned'] : 0,
        );

        if ($this->input->method() === 'post')
        {
            // Banner: ảnh đang chọn (ảnh cũ / chọn từ thư viện, rỗng = không có banner) — chỉ nhận ảnh trong thư mục của Thư viện ảnh.
            $banner = trim((string) $this->input->post('banner'));
            if ($banner !== '' && ( ! preg_match('#^'.preg_quote(self::UPLOAD_DIR, '#').'[A-Za-z0-9_.-]+$#', $banner) || ! is_file(FCPATH.'assets/'.$banner)))
            {
                $banner = $page ? (string) $page['banner'] : '';
            }
            $values = array(
                'code'        => strtolower(trim((string) $this->input->post('code'))),
                'title'       => trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('title', TRUE))),
                'description' => (string) $this->input->post('description'),
                'status'      => $this->input->post('status') ? 1 : 0,
                'banner'      => $banner,
                'summary'      => trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('summary', TRUE))),
                'published_at' => trim((string) $this->input->post('published_at')),
                'is_pinned'    => $this->input->post('is_pinned') ? 1 : 0,
            );
            $published = DateTime::createFromFormat('Y-m-d\TH:i', $values['published_at']);

            // Ảnh banner mới tải lên (thay ảnh đang chọn).
            if ( ! empty($_FILES['banner_file']['name']))
            {
                $upload_error = NULL;
                $name = $this->_handle_upload('banner_file', $upload_error);
                if ($name === NULL)
                {
                    $error = 'Ảnh banner: '.$upload_error;
                }
                else
                {
                    $values['banner'] = self::UPLOAD_DIR.$name;
                    $this->audit('page', 'UPLOAD_IMAGE', NULL, array('file' => $values['banner']));
                }
            }

            if ($error !== NULL)                                            { /* lỗi tải ảnh banner, đã có thông báo */ }
            elseif ($values['code'] === '')                                 $error = 'Nhập mã (đường dẫn).';
            elseif (strlen($values['code']) > 100)                          $error = 'Mã tối đa 100 ký tự.';
            elseif ( ! preg_match(Page_model::CODE_PATTERN, $values['code'])) $error = 'Mã chỉ gồm chữ thường không dấu, số và dấu gạch ngang (vd: gioi-thieu).';
            elseif ($this->Page_model->code_exists($values['code'], $page ? $page['id'] : NULL)) $error = 'Mã "'.$values['code'].'" đã có trang hoặc tin khác dùng.';
            elseif ($values['title'] === '')                                $error = 'Nhập tiêu đề.';
            elseif (mb_strlen($values['title'], 'UTF-8') > 200)             $error = 'Tiêu đề tối đa 200 ký tự.';
            elseif ($is_news && mb_strlen($values['summary'], 'UTF-8') > 300) $error = 'Tóm tắt tối đa 300 ký tự.';
            elseif ($is_news && ( ! $published || $published->format('Y-m-d\TH:i') !== $values['published_at'])) $error = 'Ngày đăng không hợp lệ.';
            else
            {
                $row = array(
                    'type'        => $this->type,
                    'code'        => $values['code'],
                    'title'       => $values['title'],
                    'description' => $values['description'],
                    'status'      => $values['status'],
                    'banner'      => $values['banner'] !== '' ? $values['banner'] : NULL,
                );
                if ($is_news)
                {
                    $row['summary'] = $values['summary'] !== '' ? $values['summary'] : NULL;
                    $row['published_at'] = $published->format('Y-m-d H:i:00');
                    $row['is_pinned'] = $values['is_pinned'];
                }
                $id = $this->Page_model->save($page ? $page['id'] : NULL, $row, $this->current_user['id']);
                $this->audit('page', $page ? 'UPDATE' : 'CREATE',
                    $page ? array_diff_key($page, array('description' => 1)) : NULL,
                    array('id' => $id) + array_diff_key($row, array('description' => 1)));
                $this->session->set_flashdata('success', 'Đã lưu '.$this->labels['noun'].' "'.$values['title'].'".');
                redirect($this->input->post('stay') ? $this->base.'/'.$id.'/edit' : $this->base);
                return;
            }
        }

        $data = array(
            'page_title'   => $page ? $this->labels['edit'] : $this->labels['add'],
            'current_user' => $this->current_user,
            'page'         => $page ? $page + $this->_audit_names($page) : NULL,
            'values'       => $values,
            'error'        => $error,
            'success'      => $this->session->flashdata('success'),
            'max_kb'       => self::MAX_KB,
            'shrink_kb'    => self::SHRINK_KB,
        ) + $this->_view_vars();
        $this->load->view('layout/header', $data);
        $this->load->view('pages/form', $data);
        $this->load->view('layout/footer');
    }

    /** Tên người tạo / sửa (để hiện trên form sửa). */
    protected function _audit_names($page)
    {
        $ids = array_filter(array((int) $page['created_by'], (int) $page['updated_by']));
        $names = $ids ? array_column($this->db->select('id, fullname, username')->where_in('id', $ids)->get('users')->result_array(), NULL, 'id') : array();
        $name = function ($id) use ($names) { return isset($names[$id]) ? $names[$id]['fullname'].' ('.$names[$id]['username'].')' : '—'; };
        return array('created_by_name' => $name((int) $page['created_by']), 'updated_by_name' => $name((int) $page['updated_by']));
    }

    /** Lưu ảnh tải lên vào assets/uploads/pages/, thu nhỏ nếu quá lớn. Trả về tên tệp hoặc NULL. */
    protected function _handle_upload($field, &$error)
    {
        if (empty($_FILES[$field]['name']))
        {
            $error = 'Chưa chọn ảnh.';
            return NULL;
        }

        $dir = FCPATH.'assets/'.self::UPLOAD_DIR;
        if ( ! is_dir($dir)) mkdir($dir, 0755, TRUE);

        $this->load->library('upload', array(
            'upload_path'   => $dir,
            'allowed_types' => self::IMAGE_TYPES,
            'max_size'      => self::MAX_KB,
            'encrypt_name'  => TRUE,
            'file_ext_tolower' => TRUE,
        ));
        if ( ! $this->upload->do_upload($field))
        {
            $error = $this->upload->display_errors('', '');
            return NULL;
        }

        $file = $this->upload->data();
        $this->_shrink($file['full_path'], $file['file_ext']);
        return $file['file_name'];
    }

    /**
     * Ảnh lớn hơn 2MB: thu nhỏ dần (cạnh dài 1920 → 1600 → 1280 → 1024px, chất lượng 82) tới khi dưới 2MB.
     * Cần GD; không có thì giữ nguyên (trình duyệt đã thu nhỏ trước khi gửi). Ảnh GIF giữ nguyên (mất ảnh động).
     */
    protected function _shrink($path, $ext)
    {
        clearstatcache(TRUE, $path);
        if ( ! extension_loaded('gd') || $ext === '.gif' || filesize($path) <= self::SHRINK_KB * 1024) return;

        $size = @getimagesize($path);
        if ( ! $size) return;
        $this->load->library('image_lib');
        foreach (array(self::MAX_SIDE, 1600, 1280, 1024) as $side)
        {
            // Lần đầu giữ kích thước nếu ảnh vốn nhỏ hơn 1920px (chỉ nén lại).
            $ratio = min(1, $side / max($size[0], $size[1]));
            $this->image_lib->clear();
            $this->image_lib->initialize(array(
                'source_image'   => $path,
                'maintain_ratio' => TRUE,
                'width'          => max(1, (int) round($size[0] * $ratio)),
                'height'         => max(1, (int) round($size[1] * $ratio)),
                'quality'        => 82,
            ));
            if ( ! $this->image_lib->resize()) return;
            clearstatcache(TRUE, $path);
            if (filesize($path) <= self::SHRINK_KB * 1024) return;
            $size = @getimagesize($path) ?: $size;
        }
    }

    /** Ảnh trong thư mục, mới nhất trước. $with_usage: kèm danh sách trang đang dùng ảnh. */
    protected function _list_images($with_usage)
    {
        $dir = FCPATH.'assets/'.self::UPLOAD_DIR;
        $files = is_dir($dir) ? glob($dir.'*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) : array();
        $out = array();
        foreach ($files as $path)
        {
            $name = basename($path);
            $size = @getimagesize($path);
            $img = array(
                'name'   => $name,
                'url'    => base_url('assets/'.self::UPLOAD_DIR.$name),
                'size'   => filesize($path),
                'time'   => filemtime($path),
                'width'  => $size ? $size[0] : NULL,
                'height' => $size ? $size[1] : NULL,
            );
            if ($with_usage) $img['used_in'] = $this->Page_model->pages_using($name);
            $out[] = $img;
        }
        usort($out, function ($a, $b) { return $b['time'] - $a['time']; });
        return $out;
    }

    protected function _post_page($id)
    {
        if ($this->input->method() !== 'post') show_404();
        $page = $this->Page_model->get_by_id($id);
        if ( ! $page || $page['type'] !== $this->type) show_404();
        return $page;
    }

    protected function _json($data, $status = 200)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
