<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public, unauthenticated ordering API used by the customer QR menu.
 *
 * Authorization is two-layered:
 *  - qr_token identifies the table (permanent, printed on the physical QR).
 *  - session_secret identifies THIS specific visit — issued fresh by
 *    Menu::_auto_open_table() every time the table turns over. Every write
 *    (and the read of "my current order") re-validates both, so a customer's
 *    stale bookmarked link stops working the moment the table is reused or
 *    the visit has gone on too long unpaid (Table_session_model::SESSION_TTL_HOURS).
 */
class Api_order extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('kds');
        $this->load->model(array('Table_model', 'Table_session_model', 'Order_model', 'Order_item_model', 'Product_model', 'Kitchen_ticket_model'));
    }

    public function create()
    {
        $this->_add_items();
    }

    public function add_item()
    {
        $this->_add_items();
    }

    private function _add_items()
    {
        $body = $this->input_json();
        $token = isset($body['token']) ? $body['token'] : NULL;
        $secret = isset($body['secret']) ? $body['secret'] : NULL;
        $items = isset($body['items']) ? $body['items'] : array();

        $table = $token ? $this->Table_model->get_by_token($token) : NULL;
        if ( ! $table)
        {
            json_response(array('success' => FALSE, 'message' => 'Bàn không hợp lệ'), 404);
            return;
        }

        $check = $this->_check_session($table, $secret);
        if ( ! $check['valid'])
        {
            json_response(array('success' => FALSE, 'expired' => TRUE, 'message' => $check['message']), 409);
            return;
        }
        $session = $check['session'];

        $order = $this->Order_model->get_active_by_table_session($session['id']);
        if ( ! $order || $order['status'] !== 'OPEN')
        {
            json_response(array('success' => FALSE, 'message' => 'Đơn đã chuyển thu ngân, không thể đặt thêm món'), 409);
            return;
        }

        if (empty($items) || ! is_array($items))
        {
            json_response(array('success' => FALSE, 'message' => 'Vui lòng chọn ít nhất 1 món'), 400);
            return;
        }

        $added_items = array();
        foreach ($items as $it)
        {
            $product = $this->Product_model->get_by_id((int) $it['product_id']);
            if ( ! $product || $product['status'] !== 'ACTIVE') continue;

            $qty = max(1, (int) $it['qty']);
            $note = isset($it['note']) ? substr($it['note'], 0, 255) : NULL;

            $this->Order_item_model->add($order['id'], $product['id'], $qty, $product['price'], $note);
            $item = array('product_id' => $product['id'], 'qty' => $qty, 'note' => $note);
            $added_items[] = $item;
        }

        if (empty($added_items))
        {
            json_response(array('success' => FALSE, 'message' => 'Món không hợp lệ'), 400);
            return;
        }

        $this->Kitchen_ticket_model->create_ticket($order['id'], $table['id'], $added_items);
        $this->Order_model->recalc_totals($order['id']);

        $this->load->model('Audit_log_model');
        $this->Audit_log_model->log('order', 'CUSTOMER_ADD_ITEM', NULL, array('order_id' => $order['id'], 'items' => $added_items));

        json_response(array('success' => TRUE, 'order_id' => $order['id']));
    }

    public function remove_item()
    {
        $body = $this->input_json();
        $token = isset($body['token']) ? $body['token'] : NULL;
        $secret = isset($body['secret']) ? $body['secret'] : NULL;
        $item_id = isset($body['item_id']) ? (int) $body['item_id'] : 0;

        $table = $token ? $this->Table_model->get_by_token($token) : NULL;
        if ( ! $table)
        {
            json_response(array('success' => FALSE, 'message' => 'Bàn không hợp lệ'), 404);
            return;
        }

        $check = $this->_check_session($table, $secret);
        if ( ! $check['valid'])
        {
            json_response(array('success' => FALSE, 'expired' => TRUE, 'message' => $check['message']), 409);
            return;
        }
        $session = $check['session'];

        $item = $this->Order_item_model->get_by_id($item_id);
        $order = $this->Order_model->get_active_by_table_session($session['id']);

        if ( ! $item || ! $order || (int) $item['order_session_id'] !== (int) $order['id'] || $order['status'] !== 'OPEN')
        {
            json_response(array('success' => FALSE, 'message' => 'Không thể hủy món này'), 409);
            return;
        }

        $this->Order_item_model->cancel($item_id);
        $this->Order_model->recalc_totals($order['id']);

        json_response(array('success' => TRUE));
    }

    public function current_by_token($token, $secret = NULL)
    {
        $table = $this->Table_model->get_by_token($token);
        if ( ! $table)
        {
            json_response(array('success' => FALSE, 'message' => 'Bàn không hợp lệ'), 404);
            return;
        }

        $check = $this->_check_session($table, $secret);
        if ( ! $check['valid'])
        {
            json_response(array('success' => FALSE, 'expired' => TRUE, 'message' => $check['message']));
            return;
        }
        $session = $check['session'];

        $order = $this->Order_model->get_active_by_table_session($session['id']);
        if ( ! $order)
        {
            json_response(array('success' => TRUE, 'order' => NULL));
            return;
        }

        $order['items'] = $this->Order_item_model->get_active_by_order($order['id']);
        $order['tickets'] = $this->Kitchen_ticket_model->tickets_with_items_for_order($order['id']);

        json_response(array('success' => TRUE, 'order' => $order));
    }

    /**
     * Shared secret+TTL check used by every write and by the "my order" poll.
     * Returns ['valid'=>true,'session'=>...] or ['valid'=>false,'message'=>...].
     */
    private function _check_session($table, $secret)
    {
        $check = $this->Table_session_model->validate_session($table['id'], $secret);

        if ($check['valid'])
        {
            return $check;
        }

        $messages = array(
            'NOT_OPEN'   => 'Bàn hiện chưa mở, vui lòng quét lại mã QR trên bàn.',
            'SUPERSEDED' => 'Phiên đặt món này đã kết thúc. Vui lòng quét lại mã QR trên bàn.',
            'EXPIRED'    => 'Phiên đặt món đã hết hạn. Vui lòng gọi nhân viên hoặc quét lại mã QR trên bàn.',
            'CLOSED'     => 'Bàn đã thanh toán, cảm ơn quý khách và sớm gặp lại'
        );

        return array('valid' => FALSE, 'message' => $messages[$check['reason']]);
    }

    protected function input_json(): array
    {
        $raw = $this->input->raw_input_stream;
        if (empty($raw))
        {
            return $this->input->post() ?: array();
        }
        $decoded = json_decode($raw, TRUE);
        return is_array($decoded) ? $decoded : array();
    }
}
