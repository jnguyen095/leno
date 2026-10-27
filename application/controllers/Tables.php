<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tables extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'ADMIN', 'CASHIER');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Table_model', 'Table_session_model', 'Order_model', 'Order_item_model', 'Kitchen_ticket_model', 'Setting_model', 'Price_book_model', 'Discount_model'));
        $this->load->library('pos_service');
    }

    public function index()
    {
        $tables = $this->pos_service->table_map();

        // Vừa bấm "Xác nhận thanh toán" -> in hóa đơn ngay trên trang này (không mở tab mới,
        // không mất toàn màn hình).
        $paid_order_id = $this->session->flashdata('paid_order_id');
        $paid_invoice = NULL;
        if ($paid_order_id)
        {
            $paid_order = $this->Order_model->get_detail($paid_order_id);
            if ($paid_order && $paid_order['status'] === 'PAID')
            {
                $this->load->model('Payment_model');
                $paid_invoice = array(
                    'order'   => $paid_order,
                    'items'   => $this->Order_item_model->get_active_by_order($paid_order_id),
                    'payment' => $this->Payment_model->get_by_order($paid_order_id),
                );
            }
        }

        $data = array(
            'page_title'   => 'Sơ đồ bàn',
            'current_user' => $this->current_user,
            'tables'       => $tables,
            'paid_order_id' => $paid_order_id,
            'paid_invoice' => $paid_invoice,
            // Bảng giá + khuyến mãi chọn ở đây áp cho mọi đơn đang mở và bàn mở mới.
            'shop_offers'  => $this->pos_service->shop_offers(),
            'price_books'  => $this->Price_book_model->get_applicable(),
            'discounts'    => $this->Discount_model->get_applicable(),
        );
        $data = array_merge($data, $this->pos_tabs_data('tables'));
        $this->load->view('layout/header', $data);
        $this->load->view('tables/index', $data);
        $this->load->view('layout/footer');
    }

    /**
     * POST (AJAX) — chọn bảng giá + khuyến mãi ở Sơ đồ bàn (price_book_id / discount_id, 0 = bỏ): áp ngay cho mọi
     * đơn đang mở (chưa thanh toán) và cho bàn mở sau này. Trang đơn chỉ bỏ được cho riêng đơn đó.
     */
    public function offers()
    {
        if ($this->input->method() !== 'post') show_404();
        $result = $this->pos_service->set_shop_offers($this->input->post('price_book_id'), $this->input->post('discount_id'), $this->current_user['id']);
        if (isset($result['error']))
        {
            json_response(array('success' => FALSE, 'message' => $result['error']), 422);
            return;
        }
        json_response(array('success' => TRUE, 'count' => $result['count'],
            'message' => $result['count'] ? 'Đã áp dụng cho '.$result['count'].' đơn đang mở.' : 'Đã lưu — áp dụng cho bàn mở sau.'));
    }

    public function open($id)
    {
        $order_id = $this->pos_service->open_table($id, $this->current_user['id']);
        redirect($order_id ? 'me/orders/'.$order_id : 'me/tables');
    }

    public function detail($id)
    {
        $table = $this->Table_model->get_by_id($id);
        if ( ! $table)
        {
            show_404();
        }
        $session = $this->Table_session_model->get_open_by_table($id);
        if ( ! $session)
        {
            redirect('me/tables');
            return;
        }
        $order = $this->Order_model->get_active_by_table_session($session['id']);
        redirect('me/orders/'.$order['id']);
    }

    /** Ghi chú cố định của bàn (AJAX từ trang gọi món). */
    public function note($id)
    {
        $table = $this->Table_model->get_by_id($id);
        if ( ! $table || $this->input->method() !== 'post')
        {
            json_response(array('success' => FALSE, 'message' => 'Không tìm thấy bàn.'), 404);
            return;
        }
        $note = clean_note($this->input->post('note', TRUE));
        $this->Table_model->set_note($id, $note);
        $this->audit('table', 'UPDATE_NOTE', array('note' => $table['note']), array('table_id' => (int) $id, 'note' => $note));
        json_response(array('success' => TRUE, 'note' => $note));
    }

    public function transfer($id)
    {
        $table = $this->Table_model->get_by_id($id);
        $session = $this->Table_session_model->get_open_by_table($id);

        if ($this->input->method() === 'post')
        {
            $this->pos_service->transfer_table($id, (int) $this->input->post('target_table_id'), $this->current_user['id']);
            redirect('me/tables');
            return;
        }

        $available = array_filter($this->Table_model->get_all(), function ($t) { return $t['status'] === 'AVAILABLE'; });
        $data = array(
            'page_title'   => 'Chuyển bàn',
            'current_user' => $this->current_user,
            'table'        => $table,
            'available'    => $available,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('tables/transfer', $data);
        $this->load->view('layout/footer');
    }

    public function merge($id)
    {
        $table = $this->Table_model->get_by_id($id);
        $session = $this->Table_session_model->get_open_by_table($id);

        if ($this->input->method() === 'post')
        {
            $target_order_id = $this->pos_service->merge_table($id, (int) $this->input->post('target_table_id'), $this->current_user['id']);
            redirect($target_order_id ? 'me/orders/'.$target_order_id : 'me/tables');
            return;
        }

        $others = array_filter($this->Table_model->get_all(), function ($t) use ($id) {
            return $t['status'] === 'OPEN' && (int) $t['id'] !== (int) $id;
        });
        $data = array(
            'page_title'   => 'Gộp bàn',
            'current_user' => $this->current_user,
            'table'        => $table,
            'others'       => $others,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('tables/merge', $data);
        $this->load->view('layout/footer');
    }

    public function manage()
    {
        $this->_require_admin();

        $data = array(
            'page_title'   => 'Quản lý bàn',
            'current_user' => $this->current_user,
            'tables'       => $this->Table_model->get_all(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('tables/manage', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Lưu thứ tự bàn từ trang Quản lý bàn (kéo thả / nhập số / nút lên-xuống): POST ids[] theo thứ tự hiển thị,
     * đánh số lại 1, 2, 3... — màn hình Bàn (POS web + ứng dụng) xếp bàn theo số này.
     */
    public function manage_reorder()
    {
        $this->_require_admin();
        if ($this->input->method() !== 'post') show_404();
        $ids = (array) $this->input->post('ids');
        $old = array_column($this->Table_model->get_all(), 'table_name', 'id');
        if ( ! $this->Table_model->reorder($ids))
        {
            $this->session->set_flashdata('error', 'Danh sách bàn đã thay đổi trong lúc sắp xếp (có bàn mới thêm / xoá). Tải lại trang rồi sắp xếp lại.');
            redirect('me/tables/manage');
            return;
        }
        $this->audit('table', 'REORDER', array('order' => array_values($old)),
            array('order' => array_map(function ($id) use ($old) { return isset($old[$id]) ? $old[$id] : $id; }, array_map('intval', $ids))));
        $this->session->set_flashdata('success', 'Đã lưu thứ tự bàn.');
        redirect('me/tables/manage');
    }

    public function manage_create()
    {
        $this->_require_admin();
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $code = $this->input->post('table_code', TRUE);

            if ($this->Table_model->code_exists($code))
            {
                $error = 'Mã bàn đã tồn tại.';
            }
            else
            {
                $id = $this->Table_model->create(array(
                    'table_code'     => $code,
                    'table_name'     => $this->input->post('table_name', TRUE),
                    'note'           => clean_note($this->input->post('note', TRUE)),
                    'capacity'       => (int) $this->input->post('capacity'),
                    // Để trống thứ tự -> xếp cuối danh sách bàn.
                    'sort_order'     => trim((string) $this->input->post('sort_order')) === '' ? $this->Table_model->next_sort_order() : (int) $this->input->post('sort_order'),
                    'status'         => 'AVAILABLE',
                ));
                $this->audit('table', 'CREATE', NULL, array('id' => $id));
                redirect('me/tables/manage');
                return;
            }
        }

        $data = array('page_title' => 'Thêm bàn', 'current_user' => $this->current_user, 'table' => NULL, 'error' => $error);
        $this->load->view('layout/header', $data);
        $this->load->view('tables/manage_form', $data);
        $this->load->view('layout/footer');
    }

    public function manage_edit($id)
    {
        $this->_require_admin();
        $table = $this->Table_model->get_by_id($id);
        if ( ! $table || $table['is_takeaway']) show_404();
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $code = $this->input->post('table_code', TRUE);

            if ($this->Table_model->code_exists($code, $id))
            {
                $error = 'Mã bàn đã tồn tại.';
            }
            else
            {
                $this->Table_model->update($id, array(
                    'table_code'     => $code,
                    'table_name'     => $this->input->post('table_name', TRUE),
                    'note'           => clean_note($this->input->post('note', TRUE)),
                    'capacity'       => (int) $this->input->post('capacity'),
                    'sort_order'     => (int) $this->input->post('sort_order'),
                ));
                $this->audit('table', 'UPDATE', $table, array('id' => $id));
                redirect('me/tables/manage');
                return;
            }
        }

        $data = array('page_title' => 'Sửa bàn', 'current_user' => $this->current_user, 'table' => $table, 'error' => $error);
        $this->load->view('layout/header', $data);
        $this->load->view('tables/manage_form', $data);
        $this->load->view('layout/footer');
    }

    public function manage_delete($id)
    {
        $this->_require_admin();
        $table = $this->Table_model->get_by_id($id);

        if ($table && ! $table['is_takeaway'] && $table['status'] === 'AVAILABLE')
        {
            $has_history = $this->db->where('table_id', $id)->get('table_sessions')->num_rows() > 0;

            if ($has_history)
            {
                $this->session->set_flashdata('error', 'Không thể xóa bàn đã có lịch sử sử dụng.');
            }
            else
            {
                $this->Table_model->delete($id);
                $this->audit('table', 'DELETE', $table, NULL);
            }
        }
        else
        {
            $this->session->set_flashdata('error', 'Chỉ có thể xóa bàn đang trống.');
        }

        redirect('me/tables/manage');
    }

    public function manage_reset_status($id)
    {
        $this->_require_admin();
        $table = $this->Table_model->get_by_id($id);

        if ($table && $table['status'] !== 'AVAILABLE')
        {
            $session = $this->Table_session_model->get_open_by_table($id);
            if ($session)
            {
                $order = $this->Order_model->get_active_by_table_session($session['id']);
                if ($order) $this->Order_model->cancel($order['id']);
                $this->Table_session_model->close($session['id']);
            }
            $this->Table_model->set_status($id, 'AVAILABLE');
            $this->audit('table', 'FORCE_RESET', $table, array('id' => $id));
        }

        redirect('me/tables/manage');
    }

    private function _require_admin()
    {
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            $this->load->view('errors/forbidden', array('current_user' => $this->current_user));
            exit;
        }
    }
}
