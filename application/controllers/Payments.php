<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only payment history listing (payment collection itself happens
 * via Cashier::pay(), which owns the payment screen UI).
 */
class Payments extends MY_Controller
{
    protected $allowed_roles = array('CASHIER', 'ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Payment_model');
    }

    public function index()
    {
        $from = $this->input->get('from') ?: date('Y-m-d');
        $to = $this->input->get('to') ?: date('Y-m-d');

        $data = array(
            'page_title'   => 'Lịch sử thanh toán',
            'current_user' => $this->current_user,
            'from'         => $from,
            'to'           => $to,
            'summary'      => $this->Payment_model->summary_by_method($from, $to),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('payments/index', $data);
        $this->load->view('layout/footer');
    }
}
