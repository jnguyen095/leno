<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dispense_points extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Dispense_point_model');
    }

    public function index()
    {
        $data = array(
            'page_title'      => 'Điểm xuất kho',
            'current_user'    => $this->current_user,
            'dispense_points' => $this->Dispense_point_model->get_all(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('dispense_points/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        if ($this->input->method() === 'post')
        {
            $id = $this->Dispense_point_model->create(array(
                'name'   => $this->input->post('name', TRUE),
                'status' => 'ACTIVE',
            ));
            $this->audit('dispense_point', 'CREATE', NULL, array('id' => $id));
            redirect('me/inventory/dispense-points');
            return;
        }

        $data = array('page_title' => 'Thêm điểm xuất kho', 'current_user' => $this->current_user, 'dispense_point' => NULL);
        $this->load->view('layout/header', $data);
        $this->load->view('dispense_points/form', $data);
        $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        $dispense_point = $this->Dispense_point_model->get_by_id($id);
        if ( ! $dispense_point) show_404();

        if ($this->input->method() === 'post')
        {
            $this->Dispense_point_model->update($id, array(
                'name'   => $this->input->post('name', TRUE),
                'status' => $this->input->post('status'),
            ));
            $this->audit('dispense_point', 'UPDATE', $dispense_point, array('id' => $id));
            redirect('me/inventory/dispense-points');
            return;
        }

        $data = array('page_title' => 'Sửa điểm xuất kho', 'current_user' => $this->current_user, 'dispense_point' => $dispense_point);
        $this->load->view('layout/header', $data);
        $this->load->view('dispense_points/form', $data);
        $this->load->view('layout/footer');
    }

    public function delete($id)
    {
        $dispense_point = $this->Dispense_point_model->get_by_id($id);
        $this->Dispense_point_model->delete($id);
        $this->audit('dispense_point', 'DELETE', $dispense_point, NULL);
        redirect('me/inventory/dispense-points');
    }
}
