<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản trị → Website → Banner trang chủ (chỉ ADMIN): nhiều ảnh banner, tự chạy dạng băng chuyền ở đầu trang chủ
 * (views/public/partials/hero.php). Tải nhiều ảnh một lần, sắp thứ tự, ẩn / hiện, thêm mô tả và link (không bắt buộc).
 */
class Banners extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    const UPLOAD_DIR = 'uploads/banners/';
    const MAX_FILES = 10;      // tối đa số ảnh mỗi lần tải
    const MAX_SIDE = 2400;     // ảnh banner tràn ngang màn hình lớn — cho phép to hơn ảnh trong bài

    public function __construct()
    {
        parent::__construct();
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            exit;
        }
        $this->load->model(array('Site_banner_model', 'Setting_model'));
        $this->load->library('image_upload');
    }

    public function index()
    {
        $data = array(
            'page_title'   => 'Banner trang chủ',
            'current_user' => $this->current_user,
            'banners'      => $this->Site_banner_model->get_all(),
            'seconds'      => $this->_seconds(),
            'shrink_kb'    => Image_upload::SHRINK_KB,
            'max_files'    => self::MAX_FILES,
            'error'        => $this->session->flashdata('error'),
            'success'      => $this->session->flashdata('success'),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('banners/index', $data);
        $this->load->view('layout/footer');
    }

    /** Thêm banner (POST multipart, nhiều tệp ở trường images[]). Ảnh lỗi bỏ qua, báo lại tên tệp. */
    public function upload()
    {
        $this->_require_post();
        $files = isset($_FILES['images']['name']) && is_array($_FILES['images']['name']) ? $_FILES['images'] : NULL;
        $count = $files ? count(array_filter($files['name'], 'strlen')) : 0;
        if ( ! $count)
        {
            $this->session->set_flashdata('error', 'Chưa chọn ảnh.');
            redirect('me/banners');
            return;
        }

        $added = 0;
        $errors = array();
        foreach ($files['name'] as $i => $name)
        {
            if ($name === '') continue;
            if ($added + count($errors) >= self::MAX_FILES)
            {
                $errors[] = 'Chỉ nhận tối đa '.self::MAX_FILES.' ảnh mỗi lần.';
                break;
            }
            // Thư viện upload của CI xử lý từng trường một: đưa tệp thứ $i ra trường riêng.
            $_FILES['banner_one'] = array(
                'name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i], 'size' => $files['size'][$i],
            );
            $error = NULL;
            $saved = $this->image_upload->save('banner_one', self::UPLOAD_DIR, $error, self::MAX_SIDE);
            if ($saved === NULL)
            {
                $errors[] = $name.': '.$error;
                continue;
            }
            $id = $this->Site_banner_model->create(array(
                'image' => self::UPLOAD_DIR.$saved, 'status' => 'ACTIVE', 'created_by' => $this->current_user['id'],
            ));
            $this->audit('banner', 'ADD', NULL, array('id' => $id, 'image' => self::UPLOAD_DIR.$saved));
            $added++;
        }

        if ($added) $this->session->set_flashdata('success', 'Đã thêm '.$added.' banner.');
        if ($errors) $this->session->set_flashdata('error', implode(' · ', $errors));
        redirect('me/banners');
    }

    /** Sửa mô tả + link của một banner. */
    public function update($id)
    {
        $this->_require_post();
        $banner = $this->_banner_or_404($id);
        $title = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('title', TRUE))), 0, 150, 'UTF-8');
        $link = trim((string) $this->input->post('link_url'));

        // Link: địa chỉ đầy đủ https://... hoặc đường dẫn trong website bắt đầu bằng / (vd /tin-tuc.html).
        if ($link !== '' && ! preg_match('#^(https?://[^\s<>"]+|/[^\s<>"]*)$#i', $link))
        {
            $this->session->set_flashdata('error', 'Link phải bắt đầu bằng https:// hoặc / (vd /tin-tuc.html).');
            redirect(site_url('me/banners').'#banner-'.$banner['id']);
            return;
        }
        $new = array('title' => $title !== '' ? $title : NULL, 'link_url' => $link !== '' ? mb_substr($link, 0, 300, 'UTF-8') : NULL);
        $this->Site_banner_model->update($banner['id'], $new);
        $this->audit('banner', 'UPDATE', array_intersect_key($banner, $new), array('id' => (int) $banner['id']) + $new);
        $this->session->set_flashdata('success', 'Đã lưu banner.');
        redirect(site_url('me/banners').'#banner-'.$banner['id']);
    }

    public function toggle($id)
    {
        $this->_require_post();
        $banner = $this->_banner_or_404($id);
        $status = $banner['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $this->Site_banner_model->update($banner['id'], array('status' => $status));
        $this->audit('banner', 'TOGGLE', array('status' => $banner['status']), array('id' => (int) $banner['id'], 'status' => $status));
        redirect(site_url('me/banners').'#banner-'.$banner['id']);
    }

    public function move($id, $direction)
    {
        $this->_require_post();
        $banner = $this->_banner_or_404($id);
        $this->Site_banner_model->move($banner['id'], $direction === 'up' ? -1 : 1);
        redirect(site_url('me/banners').'#banner-'.$banner['id']);
    }

    public function delete($id)
    {
        $this->_require_post();
        $banner = $this->_banner_or_404($id);
        $this->Site_banner_model->delete($banner['id']);
        if (strpos($banner['image'], self::UPLOAD_DIR) === 0 && is_file(FCPATH.'assets/'.$banner['image']))
        {
            @unlink(FCPATH.'assets/'.$banner['image']);
        }
        $this->audit('banner', 'DELETE', $banner, NULL);
        $this->session->set_flashdata('success', 'Đã xoá banner.');
        redirect('me/banners');
    }

    /** Thời gian mỗi ảnh (giây) của băng chuyền. */
    public function settings()
    {
        $this->_require_post();
        $seconds = (int) $this->input->post('seconds');
        if ($seconds < 3 || $seconds > 20)
        {
            $this->session->set_flashdata('error', 'Thời gian mỗi ảnh phải từ 3 đến 20 giây.');
        }
        else
        {
            $old = $this->_seconds();
            $this->Setting_model->set('site_banner_seconds', (string) $seconds);
            $this->audit('banner', 'UPDATE_SECONDS', array('seconds' => $old), array('seconds' => $seconds));
            $this->session->set_flashdata('success', 'Đã lưu: mỗi ảnh hiện '.$seconds.' giây.');
        }
        redirect('me/banners');
    }

    // ---- Nội bộ ----

    private function _seconds()
    {
        return (int) $this->Setting_model->get('site_banner_seconds', (string) Site_banner_model::DEFAULT_SECONDS);
    }

    private function _banner_or_404($id)
    {
        $banner = $this->Site_banner_model->get_by_id($id);
        if ( ! $banner) show_404();
        return $banner;
    }

    private function _require_post()
    {
        if ($this->input->method() !== 'post') show_404();
    }
}
