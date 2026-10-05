<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Trang tổng quan — tạm để trống. */
class Dashboard extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'CASHIER', 'ADMIN');

    public function index()
    {
        $data = array(
            'page_title'   => 'Tổng quan',
            'current_user' => $this->current_user,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('layout/footer');
    }
}
