<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_units extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Inventory_unit_model');
    }

    public function index()
    {
        $data = array(
            'page_title'   => 'Đơn vị tính',
            'current_user' => $this->current_user,
            'units'        => $this->Inventory_unit_model->get_all(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_units/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $name = $this->input->post('name', TRUE);
            if ($this->Inventory_unit_model->get_by_name($name))
            {
                $error = 'Đơn vị tính này đã tồn tại.';
            }
            else
            {
                $id = $this->Inventory_unit_model->create(array('name' => $name, 'status' => 'ACTIVE'));
                $this->audit('inventory_unit', 'CREATE', NULL, array('id' => $id));
                redirect('me/inventory/units');
                return;
            }
        }

        $data = array('page_title' => 'Thêm đơn vị tính', 'current_user' => $this->current_user, 'unit' => NULL, 'error' => $error);
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_units/form', $data);
        $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        $unit = $this->Inventory_unit_model->get_by_id($id);
        if ( ! $unit) show_404();
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $name = $this->input->post('name', TRUE);
            $existing = $this->Inventory_unit_model->get_by_name($name);
            if ($existing && (int) $existing['id'] !== (int) $id)
            {
                $error = 'Đơn vị tính này đã tồn tại.';
            }
            else
            {
                $this->Inventory_unit_model->update($id, array(
                    'name'   => $name,
                    'status' => $this->input->post('status'),
                ));
                $this->audit('inventory_unit', 'UPDATE', $unit, array('id' => $id));
                redirect('me/inventory/units');
                return;
            }
        }

        $data = array('page_title' => 'Sửa đơn vị tính', 'current_user' => $this->current_user, 'unit' => $unit, 'error' => $error);
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_units/form', $data);
        $this->load->view('layout/footer');
    }

    public function delete($id)
    {
        $unit = $this->Inventory_unit_model->get_by_id($id);
        $this->Inventory_unit_model->delete($id);
        $this->audit('inventory_unit', 'DELETE', $unit, NULL);
        redirect('me/inventory/units');
    }
}
