<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Categories extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Category_model');
    }

    public function index()
    {
        $data = array(
            'page_title'   => 'Danh mục',
            'current_user' => $this->current_user,
            'categories'   => $this->Category_model->get_all(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('categories/index', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Lưu thứ tự mới từ trang danh sách (kéo thả / nhập số / nút lên-xuống): POST ids[] theo thứ tự hiển thị,
     * đánh số lại 1, 2, 3... — POS, thực đơn website và báo cáo đều xếp danh mục theo số này.
     */
    public function reorder()
    {
        if ($this->input->method() !== 'post') show_404();
        $ids = (array) $this->input->post('ids');
        $old = array_column($this->Category_model->get_all(), 'name', 'id');
        if ( ! $this->Category_model->reorder($ids))
        {
            $this->session->set_flashdata('error', 'Danh sách đã thay đổi trong lúc sắp xếp (có danh mục mới thêm / bớt). Tải lại trang rồi sắp xếp lại.');
            redirect('me/categories');
            return;
        }
        $this->audit('category', 'REORDER', array('order' => array_values($old)),
            array('order' => array_map(function ($id) use ($old) { return isset($old[$id]) ? $old[$id] : $id; }, array_map('intval', $ids))));
        $this->session->set_flashdata('success', 'Đã lưu thứ tự danh mục.');
        redirect('me/categories');
    }

    public function create()
    {
        if ($this->input->method() === 'post')
        {
            // Để trống thứ tự -> xếp cuối danh sách (không còn bị nhảy lên đầu vì 0).
            $sort = trim((string) $this->input->post('sort_order'));
            $id = $this->Category_model->create(array(
                'name'       => $this->input->post('name', TRUE),
                'sort_order' => $sort === '' ? $this->Category_model->next_sort_order() : (int) $sort,
                'status'     => 'ACTIVE',
            ));
            $this->audit('category', 'CREATE', NULL, array('id' => $id));
            redirect('me/categories');
            return;
        }

        $data = array('page_title' => 'Thêm danh mục', 'current_user' => $this->current_user, 'category' => NULL);
        $this->load->view('layout/header', $data);
        $this->load->view('categories/form', $data);
        $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        $category = $this->Category_model->get_by_id($id);
        if ( ! $category) show_404();

        if ($this->input->method() === 'post')
        {
            $this->Category_model->update($id, array(
                'name'       => $this->input->post('name', TRUE),
                'sort_order' => (int) $this->input->post('sort_order'),
                'status'     => $this->input->post('status'),
            ));
            $this->audit('category', 'UPDATE', $category, array('id' => $id));
            redirect('me/categories');
            return;
        }

        $data = array('page_title' => 'Sửa danh mục', 'current_user' => $this->current_user, 'category' => $category);
        $this->load->view('layout/header', $data);
        $this->load->view('categories/form', $data);
        $this->load->view('layout/footer');
    }

    public function delete($id)
    {
        $category = $this->Category_model->get_by_id($id);
        $this->Category_model->delete($id);
        $this->audit('category', 'DELETE', $category, NULL);
        redirect('me/categories');
    }
}
