<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_categories extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Inventory_category_model');
    }

    public function index()
    {
        $data = array(
            'page_title'   => 'Danh mục kho',
            'current_user' => $this->current_user,
            'categories'   => $this->Inventory_category_model->get_all(),
            'counts'       => $this->Inventory_category_model->count_items(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_categories/index', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Lưu thứ tự mới từ trang danh sách (kéo thả / nhập số / nút lên-xuống, dùng chung assets/js/sortable-list.js):
     * POST ids[] theo thứ tự hiển thị, đánh số lại 1, 2, 3... — các trang kho (nhập / xuất / kiểm kho, hàng trong kho) xếp theo số này.
     */
    public function reorder()
    {
        if ($this->input->method() !== 'post') show_404();
        $ids = (array) $this->input->post('ids');
        $old = array_column($this->Inventory_category_model->get_all(), 'name', 'id');
        if ( ! $this->Inventory_category_model->reorder($ids))
        {
            $this->session->set_flashdata('error', 'Danh sách đã thay đổi trong lúc sắp xếp (có danh mục kho mới thêm / bớt). Tải lại trang rồi sắp xếp lại.');
            redirect('me/inventory/categories');
            return;
        }
        $this->audit('inventory_category', 'REORDER', array('order' => array_values($old)),
            array('order' => array_map(function ($id) use ($old) { return isset($old[$id]) ? $old[$id] : $id; }, array_map('intval', $ids))));
        $this->session->set_flashdata('success', 'Đã lưu thứ tự danh mục kho.');
        redirect('me/inventory/categories');
    }

    public function create()
    {
        if ($this->input->method() === 'post')
        {
            // Để trống thứ tự -> xếp cuối danh sách (không còn bị nhảy lên đầu vì 0).
            $sort = trim((string) $this->input->post('sort_order'));
            $id = $this->Inventory_category_model->create(array(
                'name'       => $this->input->post('name', TRUE),
                'sort_order' => $sort === '' ? $this->Inventory_category_model->next_sort_order() : (int) $sort,
                'status'     => 'ACTIVE',
            ));
            $this->audit('inventory_category', 'CREATE', NULL, array('id' => $id));
            redirect('me/inventory/categories');
            return;
        }

        $data = array('page_title' => 'Thêm danh mục kho', 'current_user' => $this->current_user, 'category' => NULL);
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_categories/form', $data);
        $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        $category = $this->Inventory_category_model->get_by_id($id);
        if ( ! $category) show_404();

        if ($this->input->method() === 'post')
        {
            $this->Inventory_category_model->update($id, array(
                'name'       => $this->input->post('name', TRUE),
                'sort_order' => (int) $this->input->post('sort_order'),
                'status'     => $this->input->post('status'),
            ));
            $this->audit('inventory_category', 'UPDATE', $category, array('id' => $id));
            redirect('me/inventory/categories');
            return;
        }

        $data = array('page_title' => 'Sửa danh mục kho', 'current_user' => $this->current_user, 'category' => $category);
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_categories/form', $data);
        $this->load->view('layout/footer');
    }

    public function delete($id)
    {
        $category = $this->Inventory_category_model->get_by_id($id);
        $this->Inventory_category_model->delete($id);
        $this->audit('inventory_category', 'DELETE', $category, NULL);
        redirect('me/inventory/categories');
    }
}
