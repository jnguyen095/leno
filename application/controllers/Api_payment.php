<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'core/MY_Api_Controller.php';

/**
 * JSON REST endpoint per SDS section 12 (POST /api/payment). The browser-based
 * Cashier screen posts a normal form to Cashier::pay() instead; this endpoint
 * exists for future integrations (e.g. a native POS terminal / VietQR webhook).
 */
class Api_payment extends MY_Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_role(array('CASHIER', 'ADMIN'));
        $this->load->model(array('Order_model', 'Payment_model', 'Table_model'));
    }

    public function create()
    {
        $body = $this->input_json();
        $order_id = isset($body['order_session_id']) ? (int) $body['order_session_id'] : 0;
        $method = isset($body['payment_method']) ? $body['payment_method'] : 'CASH';
        $received = isset($body['received_amount']) ? (float) $body['received_amount'] : 0;

        $order = $this->Order_model->get_detail($order_id);
        if ( ! $order || $order['status'] !== 'WAIT_PAYMENT')
        {
            json_response(array('success' => FALSE, 'message' => 'Đơn không ở trạng thái chờ thanh toán'), 409);
            return;
        }

        if ( ! in_array($method, array('CASH', 'CARD', 'TRANSFER', 'QR'), TRUE))
        {
            json_response(array('success' => FALSE, 'message' => 'Phương thức thanh toán không hợp lệ'), 400);
            return;
        }

        if ($method === 'CASH' && $received < (float) $order['total_amount'])
        {
            json_response(array('success' => FALSE, 'message' => 'Số tiền nhận chưa đủ'), 400);
            return;
        }

        $payment_id = $this->Payment_model->create($order_id, $method, $order['total_amount'], $received, $this->current_user['id']);
        $this->Order_model->mark_paid($order_id);
        $this->Table_model->set_status($order['table_id'], 'PAID');

        $this->load->model('Audit_log_model');
        $this->Audit_log_model->log('payment', 'PAY', NULL, array('order_id' => $order_id, 'payment_id' => $payment_id), $this->current_user['id']);

        json_response(array('success' => TRUE, 'payment_id' => $payment_id));
    }
}
