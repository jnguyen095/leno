<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lưu ảnh tải lên vào assets/<thư mục>/ (tên ngẫu nhiên) và thu nhỏ ảnh lớn hơn 2MB — dùng chung cho
 * Trang nội dung / Tin tức (controllers/Pages.php) và Banner trang chủ (controllers/Banners.php).
 * Trình duyệt đã tự thu nhỏ ảnh > 2MB trước khi gửi (assets/js/image-compress.js); đây là lớp dự phòng phía máy chủ.
 */
class Image_upload
{
    const MAX_KB = 5120;    // giới hạn tệp gửi lên
    const SHRINK_KB = 2048; // lớn hơn 2MB thì thu nhỏ (cần thư viện ảnh GD)
    const MAX_SIDE = 1920;
    const IMAGE_TYPES = 'jpg|jpeg|png|webp|gif';

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('upload');
    }

    /**
     * Lưu tệp ở trường $field (tên trong $_FILES) vào assets/$dir (vd 'uploads/banners/').
     * Trả về tên tệp đã lưu, hoặc NULL và $error là lý do.
     */
    public function save($field, $dir, &$error, $max_side = self::MAX_SIDE)
    {
        if (empty($_FILES[$field]['name']))
        {
            $error = 'Chưa chọn ảnh.';
            return NULL;
        }

        $path = FCPATH.'assets/'.$dir;
        if ( ! is_dir($path)) mkdir($path, 0755, TRUE);

        // initialize() mỗi lần: thư viện upload của CI giữ cấu hình của lần nạp đầu tiên.
        $this->CI->upload->initialize(array(
            'upload_path'      => $path,
            'allowed_types'    => self::IMAGE_TYPES,
            'max_size'         => self::MAX_KB,
            'encrypt_name'     => TRUE,
            'file_ext_tolower' => TRUE,
        ), TRUE);
        if ( ! $this->CI->upload->do_upload($field))
        {
            $error = $this->CI->upload->display_errors('', '');
            return NULL;
        }

        $file = $this->CI->upload->data();
        $this->shrink($file['full_path'], $file['file_ext'], $max_side);
        return $file['file_name'];
    }

    /**
     * Ảnh lớn hơn 2MB: thu nhỏ dần (cạnh dài $max_side → 1600 → 1280 → 1024px, chất lượng 82) tới khi dưới 2MB.
     * Cần GD; không có thì giữ nguyên. Ảnh GIF giữ nguyên (thu nhỏ làm mất ảnh động).
     */
    public function shrink($path, $ext, $max_side = self::MAX_SIDE)
    {
        clearstatcache(TRUE, $path);
        if ( ! extension_loaded('gd') || $ext === '.gif' || filesize($path) <= self::SHRINK_KB * 1024) return;

        $size = @getimagesize($path);
        if ( ! $size) return;
        $this->CI->load->library('image_lib');
        foreach (array_unique(array($max_side, 1600, 1280, 1024)) as $side)
        {
            // Lần đầu giữ kích thước nếu ảnh vốn nhỏ hơn (chỉ nén lại).
            $ratio = min(1, $side / max($size[0], $size[1]));
            $this->CI->image_lib->clear();
            $this->CI->image_lib->initialize(array(
                'source_image'   => $path,
                'maintain_ratio' => TRUE,
                'width'          => max(1, (int) round($size[0] * $ratio)),
                'height'         => max(1, (int) round($size[1] * $ratio)),
                'quality'        => 82,
            ));
            if ( ! $this->CI->image_lib->resize()) return;
            clearstatcache(TRUE, $path);
            if (filesize($path) <= self::SHRINK_KB * 1024) return;
            $size = @getimagesize($path) ?: $size;
        }
    }
}
