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
            'tables'       => $this->Table_model->get_all(TRUE),
            'page'         => $page,
            'total_pages'  => $total_pages,
            'total'        => $total,
            'per_page'     => self::PER_PAGE,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('orders/index', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Tab "Thực đơn" của POS: món đã gọi + thực đơn để thêm món, và 3 nút
     * Thông báo (báo bếp + in phiếu bếp) / In tạm tính / Thanh toán.
     * Đơn đang mở được ghi nhớ trong session của người dùng để tab "Thực đơn"
     * quay lại đúng bàn đang phục vụ, mỗi thiết bị/nhân viên độc lập.
     */
    public function detail($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order)
        {
            show_404();
        }

        $panel = $this->_panel_data($order);
        if ($panel['is_active'])
        {
            $this->session->set_userdata('pos_order_id', (int) $id);
        }

        $data = array_merge($panel, array(
            'page_title'            => $panel['table_label'].' — '.$order['order_no'],
            'current_user'          => $this->current_user,
            'products_by_category'  => $panel['is_active'] ? $this->Product_model->get_active_grouped_by_category() : array(),
            'kitchen_slip'          => $this->session->flashdata('kitchen_slip'),
        ));
        $data = array_merge($data, $this->pos_tabs_data('menu'));

        $this->load->view('layout/header', $data);
        $this->load->view('orders/detail', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Thêm món vào đơn — CHƯA báo bếp (chờ bấm "Thông báo"). Bấm vào món ở thực đơn gửi
     * AJAX (product_id[]=X, qty[]=1); bấm lại cùng món thì cộng dồn số lượng.
     */
    public function add_item($id)
    {
        if ( ! $this->_active_order_or_respond($id)) return;

        $added = $this->_add_posted_items($id);
        if ($added)
        {
            $this->Order_model->recalc_totals($id);
            $this->audit('order', 'ADD_ITEM', NULL, array('order_id' => $id, 'items' => $added));
        }
        $this->_respond($id);
    }

    /**
     * "Thông báo": thêm các món đang chọn (nếu có) rồi gửi phần chưa báo bếp — món mới/
     * tăng số lượng thành phiếu bếp (KDS), món bị bớt/hủy in thành mục "HỦY" trên phiếu.
     * Đơn vẫn mở, chưa thanh toán. Phiếu được in tự động khi trang đơn tải lại.
     */
    public function notify($id)
    {
        $order = $this->_active_order_or_redirect($id);
        if ( ! $order) return;

        $added = $this->_add_posted_items($id);
        if ($added)
        {
            $this->Order_model->recalc_totals($id);
            $this->Order_model->sync_table_status($id);
            $this->audit('order', 'ADD_ITEM', NULL, array('order_id' => $id, 'items' => $added));
        }

        $send = array();
        $cancel = array();
        foreach ($this->Order_item_model->get_by_order($id) as $it)
        {
            $target = $it['status'] === 'ACTIVE' ? (int) $it['qty'] : 0;
            $delta = $target - (int) $it['notified_qty'];
            if ($delta === 0) continue;

            $line = array('product_id' => $it['product_id'], 'product_name' => $it['product_name'], 'qty' => abs($delta), 'note' => $it['note']);
            if ($delta > 0) $send[] = $line; else $cancel[] = $line;
            $this->Order_item_model->set_notified_qty($it['id'], $target);
        }

        if ( ! $send && ! $cancel)
        {
            $this->session->set_flashdata('error', 'Không có món mới cần báo bếp.');
            redirect('me/orders/'.$id);
            return;
        }

        if ($send)
        {
            $this->Kitchen_ticket_model->create_ticket($id, $order['table_id'], $send);
        }
        $this->audit('order', 'NOTIFY_KITCHEN', NULL, array('order_id' => $id, 'send' => $send, 'cancel' => $cancel));

        $this->session->set_flashdata('kitchen_slip', array(
            'send'       => $send,
            'cancel'     => $cancel,
            'created_at' => date('Y-m-d H:i:s'),
            'staff'      => $this->current_user['fullname'],
        ));
        redirect('me/orders/'.$id);
    }

    /** Nút −/+ ở "Món đã gọi". Số lượng tối thiểu 1 — bỏ món phải bấm "Hủy món" (cancel_item). */
    public function update_item($order_id, $item_id)
    {
        $item = $this->_item_of_active_order($order_id, $item_id);
        $qty = (int) $this->input->post('qty');
        if ($item && $qty >= 1 && $qty !== (int) $item['qty'])
        {
            $this->Order_item_model->update_qty($item_id, $qty);
            $this->audit('order_item', 'UPDATE_QTY', NULL, array('item_id' => $item_id, 'qty' => $qty));
            $this->Order_model->recalc_totals($order_id);
        }
        $this->_respond($order_id);
    }

    public function cancel_item($order_id, $item_id)
    {
        $item = $this->_item_of_active_order($order_id, $item_id);
        if ($item)
        {
            $this->_remove_item($item);
            $this->Order_model->recalc_totals($order_id);
        }
        $this->_respond($order_id);
    }

    /**
     * Bếp chưa biết món này -> xoá hẳn khỏi đơn. Đã báo bếp -> giữ dòng ở trạng thái
     * CANCELLED (ẩn khỏi danh sách) để lần "Thông báo" sau in mục HỦY cho bếp.
     */
    private function _remove_item($item)
    {
        if ((int) $item['notified_qty'] === 0)
        {
            $this->Order_item_model->delete($item['id']);
            $this->audit('order_item', 'DELETE_ITEM', $item, NULL);
        }
        else
        {
            $this->Order_item_model->cancel($item['id']);
            $this->audit('order_item', 'CANCEL_ITEM', NULL, array('item_id' => $item['id']));
        }
    }

    /** Dữ liệu dùng chung cho trang đơn và các phản hồi AJAX (khối "Món đã gọi" + phiếu tạm tính). */
    private function _panel_data($order)
    {
        $is_active = in_array($order['status'], array('OPEN', 'WAIT_PAYMENT'), TRUE);
        $items = $this->Order_item_model->get_by_order($order['id']);
        $active_items = array_values(array_filter($items, function ($it) { return $it['status'] === 'ACTIVE'; }));

        $pending_count = 0;
        foreach ($items as $it)
        {
            $target = $it['status'] === 'ACTIVE' ? (int) $it['qty'] : 0;
            if ($target !== (int) $it['notified_qty']) $pending_count++;
        }

        return array(
            'order'                     => $order,
            'is_active'                 => $is_active,
            'table_label'               => $order['table_name'] ?: 'Mang đi',
            'items'                     => $items,
            'active_items'              => $active_items,
            // Đơn đang phục vụ ẩn món đã hủy; đơn đã đóng hiện đủ để xem lại lịch sử.
            'visible_items'             => $is_active ? $active_items : $items,
            'pending_count'             => $pending_count,
        );
    }

    /**
     * Gọi sau mỗi lần thêm/đổi số lượng/hủy món: cập nhật trạng thái bàn theo món (có món ->
     * "Đang phục vụ", hết món -> "Trống"), rồi AJAX trả khối "Món đã gọi" + phiếu tạm tính
     * đã render lại; không phải AJAX thì quay lại trang đơn.
     */
    private function _respond($order_id, $error = NULL)
    {
        $this->Order_model->sync_table_status($order_id);

        if ( ! $this->input->is_ajax_request())
        {
            if ($error) $this->session->set_flashdata('error', $error);
            redirect('me/orders/'.$order_id);
            return;
        }

        $panel = $this->_panel_data($this->Order_model->get_detail($order_id));
        json_response(array(
            'success'       => $error === NULL,
            'message'       => $error,
            'panel_html'    => $this->load->view('orders/_order_panel', $panel, TRUE),
            'slip_html'     => $this->load->view('orders/_provisional_slip', $panel, TRUE),
            'total'         => (float) $panel['order']['total_amount'],
            'total_text'    => money_format_vnd($panel['order']['total_amount']),
            'pending_count' => $panel['pending_count'],
        ));
    }

    private function _active_order_or_respond($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order || ! in_array($order['status'], array('OPEN', 'WAIT_PAYMENT'), TRUE))
        {
            if ($this->input->is_ajax_request())
            {
                json_response(array('success' => FALSE, 'message' => 'Đơn đã đóng, không thể thay đổi món.'), 409);
            }
            else
            {
                redirect('me/orders/'.$id);
            }
            return NULL;
        }
        return $order;
    }

    /** "Thanh toán": chốt đơn ngay trên tab Thực đơn — ghi nhận thanh toán, đóng phiên bàn, bàn về Trống. */
    public function pay($id)
    {
        $order = $this->_active_order_or_redirect($id);
        if ( ! $order) return;

        $this->Order_model->recalc_totals($id);
        $order = $this->Order_model->get_detail($id);

        if ( ! $this->Order_item_model->get_active_by_order($id))
        {
            $this->session->set_flashdata('error', 'Đơn chưa có món nào để thanh toán.');
            redirect('me/orders/'.$id);
            return;
        }

        $method = $this->input->post('payment_method');
        if ( ! in_array($method, array('CASH', 'CARD', 'TRANSFER', 'QR'), TRUE))
        {
            $method = 'CASH';
        }
        $total = (float) $order['total_amount'];
        $received = $method === 'CASH' ? (float) $this->input->post('received_amount') : $total;
        if ($received < $total)
        {
            $this->session->set_flashdata('error', 'Số tiền khách đưa chưa đủ.');
            redirect('me/orders/'.$id);
            return;
        }

        $this->load->model(array('Payment_model', 'Table_session_model'));
        $payment_id = $this->Payment_model->create($id, $method, $total, $received, $this->current_user['id']);
        $this->Order_model->mark_paid($id);

        if ($order['table_session_id'])
        {
            $this->Table_session_model->close($order['table_session_id']);
            $this->Table_model->set_status($order['table_id'], 'AVAILABLE');
        }

        if ((int) $this->session->userdata('pos_order_id') === (int) $id)
        {
            $this->session->unset_userdata('pos_order_id');
        }

        $this->audit('payment', 'PAY', NULL, array('order_id' => $id, 'payment_id' => $payment_id, 'method' => $method));
        $this->session->set_flashdata('paid_order_id', (int) $id);
        redirect('me/tables');
    }

    /** ADMIN xoá hẳn một đơn (kể cả đã thanh toán) từ danh sách Đơn hàng. Chỉ nhận POST. */
    public function delete($id)
    {
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            exit;
        }
        if ($this->input->method() !== 'post')
        {
            show_404();
        }

        $order = $this->Order_model->get_detail($id);
        if ($order)
        {
            $snapshot = array(
                'order'   => $order,
                'items'   => $this->Order_item_model->get_by_order($id),
                'payment' => $this->db->where('order_session_id', $id)->get('payments')->row_array(),
            );
            if ($this->Order_model->delete_order($id))
            {
                if ((int) $this->session->userdata('pos_order_id') === (int) $id)
                {
                    $this->session->unset_userdata('pos_order_id');
                }
                $this->audit('order', 'DELETE_ORDER', $snapshot, NULL);
                $this->session->set_flashdata('success', 'Đã xoá đơn '.$order['order_no'].'.');
            }
            else
            {
                $this->session->set_flashdata('error', 'Không xoá được đơn '.$order['order_no'].'.');
            }
        }

        // Quay lại đúng trang/bộ lọc đang xem (chỉ nhận query string, không nhận URL ngoài).
        $back = preg_replace('/[^A-Za-z0-9_=&%.\-]/', '', (string) $this->input->post('back_qs'));
        redirect('me/orders'.($back !== '' ? '?'.$back : ''));
    }

    /** In hóa đơn sau thanh toán (dùng lại mẫu K80 của thu ngân). */
    public function invoice($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order || $order['status'] !== 'PAID')
        {
            show_404();
        }
        $this->load->model('Payment_model');
        $this->load->view('orders/invoice', array(
            'order'   => $order,
            'items'   => $this->Order_item_model->get_active_by_order($id),
            'payment' => $this->Payment_model->get_by_order($id),
        ));
    }

    private function _active_order_or_redirect($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order || ! in_array($order['status'], array('OPEN', 'WAIT_PAYMENT'), TRUE))
        {
            redirect('me/orders/'.$id);
            return NULL;
        }
        return $order;
    }

    private function _item_of_active_order($order_id, $item_id)
    {
        $order = $this->Order_model->get_by_id($order_id);
        $item = $this->Order_item_model->get_by_id($item_id);
        if ( ! $order || ! $item || (int) $item['order_session_id'] !== (int) $order_id
            || $item['status'] !== 'ACTIVE' || ! in_array($order['status'], array('OPEN', 'WAIT_PAYMENT'), TRUE))
        {
            return NULL;
        }
        return $item;
    }

    /** Thêm các món product_id[]/qty[]/note[] trong POST vào đơn; trả về danh sách đã thêm. */
    private function _add_posted_items($order_id)
    {
        $product_ids = (array) $this->input->post('product_id');
        $qtys = (array) $this->input->post('qty');
        $notes = (array) $this->input->post('note');

        $added = array();
        foreach ($product_ids as $i => $pid)
        {
            $product = $this->Product_model->get_by_id($pid);
            if ( ! $product || $product['status'] !== 'ACTIVE') continue;

            $qty = max(1, (int) (isset($qtys[$i]) ? $qtys[$i] : 1));
            $note = isset($notes[$i]) && $notes[$i] !== '' ? $notes[$i] : NULL;
            $this->Order_item_model->add_or_merge($order_id, $product['id'], $qty, $product['price'], $note);
            $added[] = array('product_id' => $product['id'], 'qty' => $qty, 'note' => $note);
        }
        return $added;
    }
}
