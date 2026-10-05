<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bán mang đi: khách mua tại chỗ, không gắn với bàn nào. Tạo xong sẽ dùng lại
 * Orders (thêm/sửa/hủy món) và Cashier (thanh toán) như một đơn bình thường,
 * chỉ khác là table_session_id/table_id đều NULL.
 */
class Takeaway extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'CASHIER', 'ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Order_model', 'Order_item_model', 'Product_model', 'Kitchen_ticket_model'));
    }

    public function create()
    {
        if ($this->input->method() === 'post')
        {
            $product_ids = $this->input->post('product_id');
            $qtys = $this->input->post('qty');
            $notes = $this->input->post('note');

            if (empty($product_ids))
            {
                redirect('me/takeaway/create');
                return;
            }

            $order_id = $this->Order_model->create_takeaway();

            $ticket_items = array();
            foreach ($product_ids as $i => $pid)
            {
                $product = $this->Product_model->get_by_id($pid);
                if ( ! $product || $product['status'] !== 'ACTIVE') continue;

                $qty = max(1, (int) $qtys[$i]);
                $note = isset($notes[$i]) && $notes[$i] !== '' ? $notes[$i] : NULL;

                $this->Order_item_model->add($order_id, $pid, $qty, $product['price'], $note);
                $ticket_items[] = array('product_id' => $pid, 'qty' => $qty, 'note' => $note);
            }

            if (empty($ticket_items))
            {
                redirect('me/takeaway/create');
                return;
            }

            $this->Kitchen_ticket_model->create_ticket($order_id, NULL, $ticket_items);
            $this->Order_model->recalc_totals($order_id);
            $this->audit('order', 'CREATE_TAKEAWAY', NULL, array('order_id' => $order_id, 'items' => $ticket_items));

            redirect('me/orders/'.$order_id);
            return;
        }

        $data = array(
            'page_title'            => 'Bán mang đi',
            'current_user'          => $this->current_user,
            'products_by_category'  => $this->Product_model->get_active_grouped_by_category(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('takeaway/create', $data);
        $this->load->view('layout/footer');
    }
}
