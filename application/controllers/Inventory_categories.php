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
        );
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_categories/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        if ($this->input->method() === 'post')
        {
            $id = $this->Inventory_category_model->create(array(
                'name'       => $this->input->post('name', TRUE),
                'sort_order' => (int) $this->input->post('sort_order'),
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
