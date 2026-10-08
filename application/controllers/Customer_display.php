<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản trị "Màn hình khách": ảnh trình chiếu lúc rảnh + tuỳ chọn hiển thị cho màn hình phụ của máy POS.
 * Ứng dụng POS đọc qua GET /api/v1/display/config (Api_pos::display_config).
 */
class Customer_display extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    const UPLOAD_DIR = 'uploads/display/';
    const MAX_KB = 4096;
    const MAX_SIDE = 1920; // ảnh lớn hơn được thu nhỏ (nếu máy chủ có thư viện ảnh GD)

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Display_slide_model', 'Setting_model'));
    }

    public function index()
    {
        $error = $this->session->flashdata('error');

        if ($this->input->method() === 'post')
        {
            $error = $this->_save_config();
            if ($error === NULL)
            {
                $this->session->set_flashdata('success', 'Đã lưu tuỳ chọn màn hình khách.');
                redirect('me/customer-display');
                return;
            }
        }

        $data = array(
            'page_title'   => 'Màn hình khách',
            'current_user' => $this->current_user,
            'slides'       => $this->Display_slide_model->get_all(),
            'config'       => $this->Setting_model->get_display_config(),
            'error'        => $error,
            'success'      => $this->session->flashdata('success'),
            'can_resize'   => extension_loaded('gd'),
            'max_kb'       => self::MAX_KB,
            'today'        => date('Y-m-d'),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('customer_display/index', $data);
        $this->load->view('layout/footer');
    }

    /** Thêm ảnh trình chiếu (POST multipart: image, title). */
    public function upload()
    {
        $this->_require_post();
        $error = NULL;
        $path = $this->_handle_upload($error);
        if ($path === NULL)
        {
            $this->session->set_flashdata('error', $error ?: 'Chưa chọn ảnh.');
            redirect('me/customer-display');
            return;
        }

        $id = $this->Display_slide_model->create(array(
            'image'  => $path,
            'title'  => $this->_clean_title($this->input->post('title', TRUE)),
            'status' => 'ACTIVE',
        ));
        $this->audit('customer_display', 'ADD_SLIDE', NULL, array('id' => $id, 'image' => $path));
        $this->session->set_flashdata('success', 'Đã thêm ảnh.');
        redirect('me/customer-display');
    }

    /** Sửa tiêu đề, thời gian hiển thị riêng, khoảng ngày hiển thị. */
    public function update($id)
    {
        $this->_require_post();
        $slide = $this->_slide_or_404($id);

        $duration = trim((string) $this->input->post('duration_seconds'));
        $start = trim((string) $this->input->post('start_date'));
        $end = trim((string) $this->input->post('end_date'));
        $valid_date = function ($d) { return $d === '' || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

        if ($duration !== '' && ( ! ctype_digit($duration) || (int) $duration < 3 || (int) $duration > 120))
        {
            $this->session->set_flashdata('error', 'Thời gian hiển thị phải từ 3 đến 120 giây (để trống = mặc định).');
        }
        elseif ( ! $valid_date($start) || ! $valid_date($end) || ($start !== '' && $end !== '' && $start > $end))
        {
            $this->session->set_flashdata('error', 'Khoảng ngày hiển thị không hợp lệ.');
        }
        else
        {
            $new = array(
                'title'            => $this->_clean_title($this->input->post('title', TRUE)),
                'duration_seconds' => $duration === '' ? NULL : (int) $duration,
                'start_date'       => $start === '' ? NULL : $start,
                'end_date'         => $end === '' ? NULL : $end,
            );
            $this->Display_slide_model->update($id, $new);
            $this->audit('customer_display', 'UPDATE_SLIDE', $slide, $new + array('id' => (int) $id));
            $this->session->set_flashdata('success', 'Đã lưu ảnh.');
        }
        redirect('me/customer-display');
    }

    public function toggle($id)
    {
        $this->_require_post();
        $slide = $this->_slide_or_404($id);
        $status = $slide['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $this->Display_slide_model->update($id, array('status' => $status));
        $this->audit('customer_display', 'TOGGLE_SLIDE', array('status' => $slide['status']), array('id' => (int) $id, 'status' => $status));
        redirect('me/customer-display');
    }

    public function move($id, $direction)
    {
        $this->_require_post();
        $this->_slide_or_404($id);
        $this->Display_slide_model->move($id, $direction === 'up' ? -1 : 1);
        redirect('me/customer-display');
    }

    public function delete($id)
    {
        $this->_require_post();
        $slide = $this->_slide_or_404($id);
        $this->Display_slide_model->delete($id);
        if (strpos($slide['image'], self::UPLOAD_DIR) === 0 && is_file(FCPATH.'assets/'.$slide['image']))
        {
            @unlink(FCPATH.'assets/'.$slide['image']);
        }
        $this->audit('customer_display', 'DELETE_SLIDE', $slide, NULL);
        $this->session->set_flashdata('success', 'Đã xoá ảnh.');
        redirect('me/customer-display');
    }

    // ---- Nội bộ ----

    /** Lưu tuỳ chọn hiển thị; trả về thông báo lỗi hoặc NULL nếu đã lưu. */
    private function _save_config()
    {
        $slide_seconds = (int) $this->input->post('slide_seconds');
        $thanks_seconds = (int) $this->input->post('thanks_seconds');
        $text_scale = (float) $this->input->post('text_scale');

        if ($slide_seconds < 3 || $slide_seconds > 120) return 'Thời gian mỗi ảnh phải từ 3 đến 120 giây.';
        if ($thanks_seconds < 2 || $thanks_seconds > 60) return 'Thời gian hiện lời cảm ơn phải từ 2 đến 60 giây.';
        if ($text_scale < 0.8 || $text_scale > 1.6) return 'Cỡ chữ không hợp lệ.';

        $old = $this->Setting_model->get_display_config();
        $text = function ($name) {
            return mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $this->input->post($name, TRUE))), 0, 150, 'UTF-8');
        };
        $values = array(
            'slide_seconds'   => (string) $slide_seconds,
            'show_logo'       => $this->input->post('show_logo') ? '1' : '0',
            'welcome_text'    => $text('welcome_text'),
            'thanks_text'     => $text('thanks_text'),
            'thanks_seconds'  => (string) $thanks_seconds,
            'show_item_notes' => $this->input->post('show_item_notes') ? '1' : '0',
            'show_qr'         => $this->input->post('show_qr') ? '1' : '0',
            'text_scale'      => (string) $text_scale,
        );
        foreach ($values as $key => $value)
        {
            $this->Setting_model->set('display_'.$key, $value);
        }
        $this->audit('customer_display', 'UPDATE_CONFIG', $old, $this->Setting_model->get_display_config());
        return NULL;
    }

    /** Lưu ảnh tải lên vào assets/uploads/display/, thu nhỏ nếu quá lớn. Trả về đường dẫn tương đối hoặc NULL. */
    private function _handle_upload(&$error)
    {
        if (empty($_FILES['image']['name']))
        {
            $error = 'Chưa chọn ảnh.';
            return NULL;
        }

        $dir = FCPATH.'assets/'.self::UPLOAD_DIR;
        if ( ! is_dir($dir))
        {
            mkdir($dir, 0755, TRUE);
        }

        $this->load->library('upload', array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size'      => self::MAX_KB,
            'encrypt_name'  => TRUE,
        ));
        if ( ! $this->upload->do_upload('image'))
        {
            $error = $this->upload->display_errors('', '');
            return NULL;
        }

        $file = $this->upload->data();
        // Thu nhỏ ảnh quá lớn để màn hình khách tải nhanh (cần GD; không có thì giữ nguyên ảnh gốc).
        if (extension_loaded('gd') && ($file['image_width'] > self::MAX_SIDE || $file['image_height'] > self::MAX_SIDE))
        {
            $this->load->library('image_lib', array(
                'source_image'   => $file['full_path'],
                'maintain_ratio' => TRUE,
                'width'          => self::MAX_SIDE,
                'height'         => self::MAX_SIDE,
                'quality'        => 85,
            ));
            $this->image_lib->resize();
        }
        return self::UPLOAD_DIR.$file['file_name'];
    }

    private function _clean_title($title)
    {
        $title = trim(preg_replace('/\s+/u', ' ', (string) $title));
        return $title === '' ? NULL : mb_substr($title, 0, 150, 'UTF-8');
    }

    private function _slide_or_404($id)
    {
        $slide = $this->Display_slide_model->get_by_id($id);
        if ( ! $slide) show_404();
        return $slide;
    }

    private function _require_post()
    {
        if ($this->input->method() !== 'post') show_404();
    }
}
