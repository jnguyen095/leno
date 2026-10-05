<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cashier extends MY_Controller
{
    protected $allowed_roles = array('CASHIER', 'ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Order_model', 'Order_item_model', 'Payment_model', 'Table_model', 'Table_session_model'));
    }

    public function index()
    {
        $data = array(
            'page_title'   => 'Thu ngân',
            'current_user' => $this->current_user,
            'open_orders'  => $this->Order_model->get_list(array('status' => 'OPEN')),
            'wait_orders'  => $this->Order_model->get_list(array('status' => 'WAIT_PAYMENT')),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('cashier/index', $data);
        $this->load->view('layout/footer');
    }

    public function detail($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order)
        {
            show_404();
        }
        $items = $this->Order_item_model->get_active_by_order($id);

        $data = array(
            'page_title'   => 'Thanh toán '.$order['order_no'],
            'current_user' => $this->current_user,
            'order'        => $order,
            'items'        => $items,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('cashier/detail', $data);
        $this->load->view('layout/footer');
    }

    public function close_bill($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ($order && $order['status'] === 'OPEN')
        {
            $this->Order_model->mark_wait_payment($id);
            if ($order['table_id']) $this->Table_model->set_status($order['table_id'], 'WAIT_PAYMENT');
            $this->audit('order', 'CLOSE_BILL', NULL, array('order_id' => $id));
        }
        redirect('me/cashier/'.$id);
    }

    public function pay($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order || $order['status'] !== 'WAIT_PAYMENT')
        {
            redirect('me/cashier');
            return;
        }

        $method = $this->input->post('payment_method');
        $received = (float) $this->input->post('received_amount');

        if ($received < (float) $order['total_amount'] && $method === 'CASH')
        {
            redirect('me/cashier/'.$id);
            return;
        }

        $payment_id = $this->Payment_model->create($id, $method, $order['total_amount'], $received, $this->current_user['id']);
        $this->Order_model->mark_paid($id);

        // Bàn tự động về trống ngay sau khi thanh toán — không cần nhân viên bấm "Đóng bàn".
        // Đơn mang đi không có bàn/table_session nên bỏ qua bước này.
        if ($order['table_session_id'])
        {
            $this->Table_session_model->close($order['table_session_id']);
            $this->Table_model->set_status($order['table_id'], 'AVAILABLE');        }

        $this->audit('payment', 'PAY', NULL, array('order_id' => $id, 'payment_id' => $payment_id, 'method' => $method));

        redirect('me/cashier/'.$id);
    }

    public function invoice($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order || $order['status'] !== 'PAID')
        {
            redirect('me/cashier/'.$id);
            return;
        }
        $items = $this->Order_item_model->get_active_by_order($id);
        $payment = $this->Payment_model->get_by_order($id);

        $data = array(
            'order'   => $order,
            'items'   => $items,
            'payment' => $payment,
        );
        $this->load->view('cashier/invoice', $data);
    }
}
