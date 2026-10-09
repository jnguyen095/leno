<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'CASHIER', 'ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Order_model', 'Order_item_model', 'Product_model', 'Category_model', 'Kitchen_ticket_model', 'Table_model'));
        $this->load->library('pos_service');
    }

    const PER_PAGE = 20;
    const EXPORT_LIMIT = 20000;

    /**
     * Đọc bộ lọc danh sách Đơn hàng từ query string — dùng chung cho trang danh sách và
     * Xuất Excel để file xuất ra đúng những đơn đang thấy. Trả về [$filters, $values].
     */
    private function _list_filters()
    {
        $status = $this->input->get('status');
        // "Chờ TT" (WAIT_PAYMENT) không còn dùng — link cũ có status lạ thì xem tất cả.
        if ( ! in_array($status, array('OPEN', 'PAID', 'CANCELLED'), TRUE))
        {
            $status = '';
        }
        $table_id = $this->input->get('table_id');
        $created_by = (int) $this->input->get('created_by');
        // Chỉ ADMIN xem được đơn của mọi người; vai trò khác luôn chỉ thấy đơn do chính mình tạo
        // (áp cho danh sách, số liệu tổng quan và xuất Excel).
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $created_by = (int) $this->current_user['id'];
        }
        $payment_method = $this->input->get('payment_method');
        if ( ! in_array($payment_method, array('CASH', 'TRANSFER', 'CARD', 'QR', 'NONE'), TRUE))
        {
            $payment_method = '';
        }

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
        if ($created_by) $filters['created_by'] = $created_by;
        if ($payment_method) $filters['payment_method'] = $payment_method;

        return array($filters, compact('status', 'table_id', 'created_by', 'payment_method', 'date_from', 'date_to'));
    }

    public function index()
    {
        list($filters, $values) = $this->_list_filters();
        extract($values);

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
            'created_by'   => $created_by,
            'creators'     => $this->Order_model->get_creators(),
            'payment_method' => $payment_method,
            'summary'      => $this->Order_model->summary_by_status($filters),
            'page'         => $page,
            'total_pages'  => $total_pages,
            'total'        => $total,
            'per_page'     => self::PER_PAGE,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('orders/index', $data);
        $this->load->view('layout/footer');
    }

    /** Xuất danh sách Đơn hàng (đúng bộ lọc đang xem, không phân trang) ra file Excel .xlsx. */
    public function export()
    {
        list($filters, $values) = $this->_list_filters();
        $orders = $this->Order_model->get_list($filters, self::EXPORT_LIMIT, 0);

        $status_labels = array('OPEN' => 'Đang mở', 'WAIT_PAYMENT' => 'Chờ thanh toán', 'PAID' => 'Đã thanh toán', 'CANCELLED' => 'Đã hủy');

        $this->load->library('xlsx_writer');
        $x = $this->xlsx_writer;
        $x->set_sheet_name('Đơn hàng');
        $x->set_columns(array(
            'Mã đơn' => 20, 'Bàn' => 12, 'Loại' => 10, 'Trạng thái' => 16, 'Người tạo' => 18, 'Thanh toán' => 15,
            'Tạm tính' => 13, 'Giảm giá' => 11, 'VAT' => 10, 'Tổng tiền' => 14, 'Ghi chú' => 30,
            'Thời gian tạo' => 17, 'Thời gian thanh toán' => 19,
        ));
        foreach ($orders as $o)
        {
            $x->add_row(array(
                $o['order_no'],
                $o['table_name'] ?: 'Mang đi',
                $o['order_type'] === 'TAKEAWAY' ? 'Mang đi' : 'Tại bàn',
                isset($status_labels[$o['status']]) ? $status_labels[$o['status']] : $o['status'],
                $o['created_by_name'],
                $o['payment_method'] ? payment_method_label($o['payment_method']) : '',
                array('n' => $o['subtotal']),
                array('n' => $o['discount_amount']),
                array('n' => $o['vat_amount']),
                array('n' => $o['total_amount']),
                $o['note'],
                array('d' => $o['created_at']),
                array('d' => $o['paid_at']),
            ));
        }

        // Dòng tổng cộng: SUBTOTAL(109) chỉ cộng các dòng đang hiện, nên lọc trong Excel vẫn ra tổng đúng.
        // end_data() để bộ lọc không phủ dòng tổng này.
        if ($orders)
        {
            $x->end_data();
            $last = $x->row_count() + 1;
            $sum = function ($col) use ($last) { return array('f' => 'SUBTOTAL(109,'.$col.'2:'.$col.$last.')', 'b' => TRUE); };
            $x->add_row(array(array('t' => 'Tổng cộng ('.count($orders).' đơn)', 'b' => TRUE), '', '', '', '', '',
                $sum('G'), $sum('H'), $sum('I'), $sum('J'), '', '', ''));
        }

        $range = $values['date_from'] || $values['date_to']
            ? ($values['date_from'] ?: 'dau').'_'.($values['date_to'] ?: 'nay')
            : 'tat_ca';
        $this->audit('order', 'EXPORT_EXCEL', NULL, array('filters' => $filters, 'rows' => count($orders)));
        $x->download('don_hang_'.str_replace('-', '', $range).'.xlsx');
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

        $this->pos_service->add_items($id, $this->_posted_items(), $this->current_user['id']);
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

        if ($this->pos_service->add_items($id, $this->_posted_items(), $this->current_user['id']))
        {
            $this->Order_model->sync_table_status($id);
        }

        $slip = $this->pos_service->notify_kitchen($order, $this->current_user);
        if ( ! $slip)
        {
            $this->session->set_flashdata('error', 'Không có món mới cần báo bếp.');
            redirect('me/orders/'.$id);
            return;
        }

        $this->session->set_flashdata('kitchen_slip', $slip);
        redirect('me/orders/'.$id);
    }

    /** Nút −/+ ở "Món đã gọi". Số lượng tối thiểu 1 — bỏ món phải bấm "Hủy món" (cancel_item). */
    public function update_item($order_id, $item_id)
    {
        $item = $this->pos_service->get_active_item($order_id, $item_id);
        if ($item)
        {
            $this->pos_service->update_item_qty($item, $this->input->post('qty'), $this->current_user['id']);
        }
        $this->_respond($order_id);
    }

    public function cancel_item($order_id, $item_id)
    {
        $item = $this->pos_service->get_active_item($order_id, $item_id);
        if ($item)
        {
            $this->pos_service->remove_item($item, $this->current_user['id']);
        }
        $this->_respond($order_id);
    }

    /** Ghi chú cho cả đơn (AJAX từ tab Thực đơn). */
    public function note($id)
    {
        if ( ! $this->_active_order_or_respond($id)) return;

        $this->pos_service->set_order_note($id, $this->input->post('note', TRUE), $this->current_user['id']);
        $this->_respond($id);
    }

    /**
     * Ghi chú cho một dòng món (vd "Ít đá"). Món đã báo bếp mà đổi ghi chú thì hiện "Chưa báo"
     * và lần "Thông báo" sau in mục ĐỔI GHI CHÚ cho bếp.
     */
    public function item_note($order_id, $item_id)
    {
        $item = $this->pos_service->get_active_item($order_id, $item_id);
        if ($item)
        {
            $this->pos_service->set_item_note($item, $this->input->post('note', TRUE), $this->current_user['id']);
        }
        $this->_respond($order_id);
    }

    /** Dữ liệu dùng chung cho trang đơn và các phản hồi AJAX (khối "Món đã gọi" + phiếu tạm tính). */
    private function _panel_data($order)
    {
        return $this->pos_service->panel_data($order);
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

        $result = $this->pos_service->pay($id, $this->input->post('payment_method'), $this->input->post('received_amount'), $this->current_user['id']);
        if (isset($result['error']))
        {
            $this->session->set_flashdata('error', $result['error']);
            redirect('me/orders/'.$id);
            return;
        }

        if ((int) $this->session->userdata('pos_order_id') === (int) $id)
        {
            $this->session->unset_userdata('pos_order_id');
        }

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

    /** GET (AJAX) — Lịch sử "Thông báo" của đơn: HTML danh sách các lần báo bếp + phiếu bếp ẩn để "In lại". */
    public function kitchen_history($id)
    {
        $order = $this->Order_model->get_detail($id);
        if ( ! $order)
        {
            json_response(array('success' => FALSE, 'message' => 'Không tìm thấy đơn.'), 404);
            return;
        }
        json_response(array(
            'success' => TRUE,
            'html'    => $this->load->view('orders/_kitchen_history', array(
                'history'     => $this->pos_service->kitchen_history($id),
                'order'       => $order,
                'table_label' => $order['table_name'] ?: 'Mang đi',
            ), TRUE),
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

    /** Các món product_id[]/qty[]/note[] trong POST -> [['product_id'=>, 'qty'=>, 'note'=>], ...]. */
    private function _posted_items()
    {
        $product_ids = (array) $this->input->post('product_id');
        $qtys = (array) $this->input->post('qty');
        $notes = (array) $this->input->post('note');

        $items = array();
        foreach ($product_ids as $i => $pid)
        {
            $items[] = array(
                'product_id' => $pid,
                'qty'        => isset($qtys[$i]) ? $qtys[$i] : 1,
                'note'       => isset($notes[$i]) ? $notes[$i] : NULL,
            );
        }
        return $items;
    }
}
