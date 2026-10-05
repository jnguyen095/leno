<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'CASHIER', 'ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Order_model', 'Order_item_model', 'Product_model', 'Category_model', 'Kitchen_ticket_model', 'Table_model'));
    }

    const PER_PAGE = 20;

    public function index()
    {
        $status = $this->input->get('status');
        $table_id = $this->input->get('table_id');

        // Mặc định chỉ xem đơn hôm nay; nếu người dùng đã bấm lọc (kể cả bỏ trống
        // để xem tất cả ngày) thì tôn trọng giá trị họ chọn, không ép về hôm nay nữa.
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        if ($date_from === NULL && $date_to === NULL)
        {
            $date_from = $date_to = date('Y-m-d');
        }

        $filters = array();
        if ($status) $filters['status'] = $status;
        if ($date_from) $filters['date_from'] = $date_from;
        if ($date_to) $filters['date_to'] = $date_to;
        if ($table_id) $filters['table_id'] = $table_id;

        $total = $this->Order_model->count_list($filters);
        $total_pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($total_pages, (int) $this->input->get('page')));
        $offset = ($page - 1) * self::PER_PAGE;

        $orders = $this->Order_model->get_list($filters, self::PER_PAGE, $offset);

        $data = array(
            'page_title'   => 'Đơn hàng',
            'current_user' => $this->current_user,
            'orders'       => $orders,
            'status'       => $status,
            'date_from'    => $date_from,
            'date_to'      => $date_to,
            'table_id'     => $table_id,
            'tables'       => $this->Table_model->get_all(),
            'page'         => $page,
            'total_pages'  => $total_pages,
            'total'        => $total,
            'per_page'     => self::PER_PAGE,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('orders/index', $data);
        $this->load->view('layout/footer');
    }

    public function detail($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order)
        {
            show_404();
        }

        $items = $this->Order_item_model->get_by_order($id);
        $products_by_category = $this->Product_model->get_active_grouped_by_category();
        $tickets = $this->Kitchen_ticket_model->tickets_with_items_for_order($id);

        $data = array(
            'page_title'            => 'Đơn hàng '.$order['order_no'],
            'current_user'          => $this->current_user,
            'order'                 => $order,
            'items'                 => $items,
            'products_by_category'  => $products_by_category,
            'tickets'               => $tickets,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('orders/detail', $data);
        $this->load->view('layout/footer');
    }

    /** JSON poll dùng để cập nhật trạng thái pha chế theo thời gian thực trên trang chi tiết đơn. */
    public function ticket_status($id)
    {
        $tickets = $this->Kitchen_ticket_model->tickets_with_items_for_order($id);
        json_response(array('success' => TRUE, 'tickets' => $tickets));
    }

    public function add_item($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order || $order['status'] !== 'OPEN')
        {
            redirect('me/orders/'.$id);
            return;
        }

        $product_ids = $this->input->post('product_id');
        $qtys = $this->input->post('qty');
        $notes = $this->input->post('note');

        if (empty($product_ids))
        {
            redirect('me/orders/'.$id);
            return;
        }

        $added_items = array();
        foreach ($product_ids as $i => $pid)
        {
            $qty = max(1, (int) $qtys[$i]);
            $product = $this->Product_model->get_by_id($pid);
            if ( ! $product || $product['status'] !== 'ACTIVE') continue;

            $this->Order_item_model->add($id, $pid, $qty, $product['price'], $notes[$i] ?: NULL);
            $item = array('product_id' => $pid, 'qty' => $qty, 'note' => $notes[$i] ?: NULL);
            $added_items[] = $item;
        }

        if ($added_items)
        {
            $this->Kitchen_ticket_model->create_ticket($id, $order['table_id'], $added_items);
            $this->Order_model->recalc_totals($id);
            $this->audit('order', 'ADD_ITEM', NULL, array('order_id' => $id, 'items' => $added_items));
        }

        redirect('me/orders/'.$id);
    }

    public function update_item($order_id, $item_id)
    {
        $qty = max(1, (int) $this->input->post('qty'));
        $this->Order_item_model->update_qty($item_id, $qty);
        $this->Order_model->recalc_totals($order_id);
        $this->audit('order_item', 'UPDATE_QTY', NULL, array('item_id' => $item_id, 'qty' => $qty));
        redirect('me/orders/'.$order_id);
    }

    public function cancel_item($order_id, $item_id)
    {
        $this->Order_item_model->cancel($item_id);
        $this->Order_model->recalc_totals($order_id);
        $this->audit('order_item', 'CANCEL_ITEM', NULL, array('item_id' => $item_id));
        redirect('me/orders/'.$order_id);
    }

    /**
     * "Thanh toán" trên đơn mang đi: không có bàn để đóng bill qua Sơ đồ bàn,
     * nên chốt đơn (OPEN -> WAIT_PAYMENT) ngay tại đây rồi chuyển sang thu ngân.
     */
    public function checkout($order_id)
    {
        $order = $this->Order_model->get_by_id($order_id);
        if ($order && $order['status'] === 'OPEN')
        {
            $this->Order_model->mark_wait_payment($order_id);
            $this->audit('order', 'CHECKOUT', NULL, array('order_id' => $order_id));
        }
        redirect('me/cashier/'.$order_id);
    }
}
