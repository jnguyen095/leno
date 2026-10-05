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

    public function create()
    {
        if ($this->input->method() === 'post')
        {
            $id = $this->Category_model->create(array(
                'name'       => $this->input->post('name', TRUE),
                'sort_order' => (int) $this->input->post('sort_order'),
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
