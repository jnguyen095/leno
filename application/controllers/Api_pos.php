<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'core/MY_Api_Controller.php';

/**
 * API POS cho ứng dụng di động/tablet (/api/v1/*, xem config/routes.php).
 * Xác thực bằng bearer token (Api_auth::login). Nghiệp vụ dùng chung Pos_service với
 * màn hình web, quyền theo "Gán quyền menu": sơ đồ bàn = 'tables', đơn/gọi món = 'orders'.
 * Mọi thao tác trên đơn trả lại toàn bộ đơn (xem _order_response) để ứng dụng vẽ lại ngay.
 */
class Api_pos extends MY_Api_Controller
{
    const POS_ROLES = array('STAFF', 'CASHIER', 'ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('api');
        $this->load->library('pos_service');
        $this->load->model(array('Order_model', 'Order_item_model', 'Product_model', 'Category_model', 'Payment_model', 'Setting_model', 'Api_token_model'));
    }

    // ---- Tài khoản ------------------------------------------------------------

    /** GET /api/v1/me */
    public function me()
    {
        $this->_method('get');
        json_response(array(
            'success'  => TRUE,
            'user'     => api_user($this->current_user, api_pos_permissions($this->current_user)),
            'settings' => array(
                'site_name'        => $this->Setting_model->get_site_name(),
                'vat_percent'      => $this->Setting_model->get_vat_percent(),
                'takeaway_enabled' => $this->Setting_model->is_takeaway_enabled(),
                // Tài khoản nhận chuyển khoản — ứng dụng tự tạo mã VietQR in trên phiếu tạm tính.
                'bank_qr'          => $this->Setting_model->get_bank_qr(),
            ),
        ));
    }

    /** POST /api/v1/auth/logout — huỷ token của thiết bị này. */
    public function logout()
    {
        $this->_method('post');
        if ($this->api_token)
        {
            $this->Api_token_model->delete_by_token($this->api_token);
            $this->load->model('Audit_log_model');
            $this->Audit_log_model->log('auth', 'LOGOUT', NULL, array('via' => 'app'), $this->current_user['id']);
        }
        json_response(array('success' => TRUE));
    }

    // ---- Sơ đồ bàn --------------------------------------------------------------

    /** GET /api/v1/tables */
    public function tables()
    {
        $this->_method('get');
        $this->require_menu('tables', self::POS_ROLES);
        $this->_tables_response();
    }

    /** POST /api/v1/tables/{id}/open — mở bàn trống (hoặc lấy lại đơn rỗng đang mở), trả về đơn. */
    public function open_table($table_id)
    {
        $this->_method('post');
        $this->require_menu('tables', self::POS_ROLES);

        $order_id = $this->pos_service->open_table($table_id, $this->current_user['id']);
        if ( ! $order_id)
        {
            $this->fail(409, 'Bàn này không còn trống, vui lòng tải lại sơ đồ bàn.');
        }
        $this->_order_response($order_id);
    }

    /** POST /api/v1/tables/{id}/transfer {"target_table_id": n} — chuyển khách sang bàn trống. */
    public function transfer($table_id)
    {
        $this->_method('post');
        $this->require_menu('tables', self::POS_ROLES);

        $body = $this->input_json();
        $target = isset($body['target_table_id']) ? (int) $body['target_table_id'] : 0;
        if ( ! $this->pos_service->transfer_table($table_id, $target, $this->current_user['id']))
        {
            $this->fail(409, 'Không chuyển được bàn — bàn đích phải đang trống.');
        }
        $this->_tables_response();
    }

    /** POST /api/v1/tables/{id}/merge {"target_table_id": n} — gộp vào bàn đang phục vụ, trả về đơn đích. */
    public function merge($table_id)
    {
        $this->_method('post');
        $this->require_menu('tables', self::POS_ROLES);

        $body = $this->input_json();
        $target = isset($body['target_table_id']) ? (int) $body['target_table_id'] : 0;
        $order_id = $this->pos_service->merge_table($table_id, $target, $this->current_user['id']);
        if ( ! $order_id)
        {
            $this->fail(409, 'Không gộp được bàn — cả hai bàn phải đang phục vụ.');
        }
        $this->_order_response($order_id);
    }

    // ---- Thực đơn & đơn hàng ---------------------------------------------------

    /** GET /api/v1/menu — danh mục đang bán, mỗi danh mục kèm món đang bán. */
    public function menu()
    {
        $this->_method('get');
        $this->require_menu('orders', self::POS_ROLES);

        $by_category = array();
        foreach ($this->Product_model->get_all(array('status' => 'ACTIVE')) as $p)
        {
            $by_category[(int) $p['category_id']][] = api_product($p);
        }

        $categories = array();
        foreach ($this->Category_model->get_active() as $c)
        {
            if (empty($by_category[(int) $c['id']])) continue;
            $categories[] = array(
                'id'       => (int) $c['id'],
                'name'     => $c['name'],
                'products' => $by_category[(int) $c['id']],
            );
        }

        json_response(array('success' => TRUE, 'categories' => $categories));
    }

    /** GET /api/v1/orders/active — các đơn đang phục vụ (chuyển nhanh giữa các khách). */
    public function active_orders()
    {
        $this->_method('get');
        $this->require_menu('orders', self::POS_ROLES);

        $orders = array();
        foreach ($this->Order_model->get_active_orders() as $o)
        {
            $orders[] = array_merge(api_order_summary($o), array(
                'table_id'   => $o['table_id'] !== NULL ? (int) $o['table_id'] : NULL,
                'table_name' => $o['table_name'] ?: 'Mang đi',
            ));
        }
        json_response(array('success' => TRUE, 'orders' => $orders));
    }

    /**
     * GET /api/v1/orders/history?date=YYYY-MM-DD — đơn do người đang đăng nhập tạo trong ngày
     * (mặc định hôm nay), mới nhất trước, kèm tổng kết.
     */
    public function order_history()
    {
        $this->_method('get');
        $this->require_menu('orders', self::POS_ROLES);

        $date = (string) $this->input->get('date');
        if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! strtotime($date))
        {
            $date = date('Y-m-d');
        }

        $orders = array();
        $summary = array('count' => 0, 'paid_count' => 0, 'paid_total' => 0.0, 'open_count' => 0, 'open_total' => 0.0, 'cancelled_count' => 0);
        // Tiền đã thu theo hình thức thanh toán (chỉ tính đơn đã thanh toán).
        $by_method = array();
        foreach (Pos_service::PAYMENT_METHODS as $m)
        {
            $by_method[$m] = array('method' => $m, 'label' => payment_method_label($m), 'count' => 0, 'total' => 0.0);
        }
        foreach ($this->Order_model->get_history_for_user($this->current_user['id'], $date) as $o)
        {
            $total = api_money($o['total_amount']);
            $summary['count']++;
            if ($o['status'] === 'PAID')
            {
                $summary['paid_count']++;
                $summary['paid_total'] += $total;
                if (isset($by_method[$o['payment_method']]))
                {
                    $by_method[$o['payment_method']]['count']++;
                    $by_method[$o['payment_method']]['total'] += $total;
                }
            }
            elseif ($o['status'] === 'CANCELLED') { $summary['cancelled_count']++; }
            else { $summary['open_count']++; $summary['open_total'] += $total; }

            $orders[] = array(
                'id'             => (int) $o['id'],
                'order_no'       => $o['order_no'],
                'order_type'     => $o['order_type'],
                'status'         => $o['status'],
                'table_id'       => $o['table_id'] !== NULL ? (int) $o['table_id'] : NULL,
                'table_name'     => $o['table_name'] ?: 'Mang đi',
                'total_amount'   => $total,
                'item_count'     => (int) $o['item_count'],
                'note'           => $o['note'],
                'payment_method' => $o['payment_method'],
                'method_label'   => $o['payment_method'] ? payment_method_label($o['payment_method']) : NULL,
                'created_at'     => $o['created_at'],
                'paid_at'        => $o['paid_at'],
            );
        }

        $summary['by_method'] = array_values($by_method);
        json_response(array('success' => TRUE, 'date' => $date, 'summary' => $summary, 'orders' => $orders));
    }

    /** GET /api/v1/orders/{id} */
    public function order($order_id)
    {
        $this->_method('get');
        $this->require_menu('orders', self::POS_ROLES);
        $this->_order_response($order_id);
    }

    /** PATCH /api/v1/orders/{id} {"note": "..."} — ghi chú cả đơn. */
    public function update_order($order_id)
    {
        $this->_method('patch');
        $this->require_menu('orders', self::POS_ROLES);
        $this->_active_order($order_id);

        $body = $this->input_json();
        if (array_key_exists('note', $body))
        {
            $this->pos_service->set_order_note($order_id, $body['note'], $this->current_user['id']);
        }
        $this->_order_response($order_id);
    }

    /**
     * POST /api/v1/orders/{id}/items {"items": [{"product_id": 1, "qty": 2, "note": "Ít đá"}]}
     * Thêm món — CHƯA báo bếp (chờ /notify). Cùng món không ghi chú thì cộng dồn.
     */
    public function add_items($order_id)
    {
        $this->_method('post');
        $this->require_menu('orders', self::POS_ROLES);
        $this->_active_order($order_id);

        $body = $this->input_json();
        $items = isset($body['items']) && is_array($body['items']) ? $body['items'] : array();
        if ( ! $this->pos_service->add_items($order_id, $items, $this->current_user['id']))
        {
            $this->fail(422, 'Không có món hợp lệ để thêm (món có thể đã ngừng bán).');
        }
        $this->Order_model->sync_table_status($order_id);
        $this->_order_response($order_id);
    }

    /** PATCH /api/v1/orders/{id}/items/{item_id} {"qty": 3, "note": "..."} — một hoặc cả hai. */
    public function update_item($order_id, $item_id)
    {
        $this->_method('patch');
        $this->require_menu('orders', self::POS_ROLES);
        $item = $this->_active_item($order_id, $item_id);

        $body = $this->input_json();
        if (isset($body['qty']))
        {
            if ((int) $body['qty'] < 1)
            {
                $this->fail(422, 'Số lượng tối thiểu là 1 — muốn bỏ món hãy dùng Hủy món.');
            }
            $this->pos_service->update_item_qty($item, $body['qty'], $this->current_user['id']);
        }
        if (array_key_exists('note', $body))
        {
            $this->pos_service->set_item_note($item, $body['note'], $this->current_user['id']);
        }
        $this->_order_response($order_id);
    }

    /** DELETE /api/v1/orders/{id}/items/{item_id} — hủy món (đã báo bếp thì in mục HỦY lần báo sau). */
    public function delete_item($order_id, $item_id)
    {
        $this->_method('delete');
        $this->require_menu('orders', self::POS_ROLES);
        $item = $this->_active_item($order_id, $item_id);

        $this->pos_service->remove_item($item, $this->current_user['id']);
        $this->Order_model->sync_table_status($order_id);
        $this->_order_response($order_id);
    }

    /** POST /api/v1/orders/{id}/notify — báo bếp phần chưa báo; trả về đơn + phiếu bếp ('kitchen_slip'). */
    public function notify($order_id)
    {
        $this->_method('post');
        $this->require_menu('orders', self::POS_ROLES);
        $order = $this->_active_order($order_id);

        $slip = $this->pos_service->notify_kitchen($order, $this->current_user);
        if ( ! $slip)
        {
            $this->fail(409, 'Không có món mới cần báo bếp.');
        }
        $this->Order_model->sync_table_status($order_id);

        $lines = function ($rows) {
            return array_map(function ($r) {
                return array_merge($r, array('product_id' => (int) $r['product_id'], 'qty' => (int) $r['qty']));
            }, $rows);
        };
        $slip['send'] = $lines($slip['send']);
        $slip['cancel'] = $lines($slip['cancel']);
        $slip['changed'] = $lines($slip['changed']);
        $this->_order_response($order_id, array('kitchen_slip' => $slip));
    }

    /** GET /api/v1/orders/{id}/kitchen-history — các lần báo bếp của đơn (web + ứng dụng), mới nhất trước. */
    public function kitchen_history($order_id)
    {
        $this->_method('get');
        $this->require_menu('orders', self::POS_ROLES);
        $order = $this->Order_model->get_detail($order_id);
        if ( ! $order)
        {
            $this->fail(404, 'Không tìm thấy đơn.');
        }

        $this->load->model('Audit_log_model');
        $lines = function ($rows) {
            return array_map(function ($r) {
                return array(
                    'product_id'   => (int) $r['product_id'],
                    'product_name' => $r['product_name'],
                    'qty'          => (int) $r['qty'],
                    'note'         => isset($r['note']) ? $r['note'] : NULL,
                    'old_note'     => isset($r['old_note']) ? $r['old_note'] : NULL,
                );
            }, is_array($rows) ? $rows : array());
        };

        $history = array();
        foreach ($this->Audit_log_model->get_kitchen_notifications($order_id) as $row)
        {
            $data = json_decode($row['new_data'], TRUE);
            if ( ! is_array($data) || (int) $data['order_id'] !== (int) $order_id) continue;
            $history[] = array(
                'id'         => (int) $row['id'],
                'send'       => $lines(isset($data['send']) ? $data['send'] : NULL),
                'cancel'     => $lines(isset($data['cancel']) ? $data['cancel'] : NULL),
                'changed'    => $lines(isset($data['changed']) ? $data['changed'] : NULL),
                'order_note' => NULL,
                'created_at' => $row['created_at'],
                'staff'      => $row['staff'],
            );
        }

        json_response(array('success' => TRUE, 'history' => $history));
    }

    /**
     * POST /api/v1/orders/{id}/pay {"payment_method": "CASH|CARD|TRANSFER|QR", "received_amount": 100000}
     * Chốt đơn, đóng phiên bàn, bàn về Trống. Trả về đơn đã thanh toán kèm 'payment'.
     */
    public function pay($order_id)
    {
        $this->_method('post');
        $this->require_menu('orders', self::POS_ROLES);
        $this->_active_order($order_id);

        $body = $this->input_json();
        $method = isset($body['payment_method']) ? (string) $body['payment_method'] : 'CASH';
        if ( ! in_array($method, Pos_service::PAYMENT_METHODS, TRUE))
        {
            $this->fail(422, 'Phương thức thanh toán không hợp lệ.');
        }
        $received = isset($body['received_amount']) ? (float) $body['received_amount'] : 0;

        $result = $this->pos_service->pay($order_id, $method, $received, $this->current_user['id']);
        if (isset($result['error']))
        {
            $this->fail(422, $result['error']);
        }
        $this->_order_response($order_id);
    }

    // ---- Nội bộ -------------------------------------------------------------------

    private function _method($expected)
    {
        if ($this->input->method() !== $expected)
        {
            $this->fail(405, 'Method not allowed');
        }
    }

    private function _active_order($order_id)
    {
        $order = $this->Order_model->get_detail($order_id);
        if ( ! $order)
        {
            $this->fail(404, 'Không tìm thấy đơn.');
        }
        if ( ! $this->pos_service->is_active($order))
        {
            $this->fail(409, 'Đơn đã đóng, không thể thay đổi.');
        }
        return $order;
    }

    private function _active_item($order_id, $item_id)
    {
        $this->_active_order($order_id);
        $item = $this->pos_service->get_active_item($order_id, $item_id);
        if ( ! $item)
        {
            $this->fail(404, 'Món này không còn trong đơn, vui lòng tải lại.');
        }
        return $item;
    }

    private function _tables_response()
    {
        json_response(array(
            'success' => TRUE,
            'tables'  => array_map('api_table', $this->pos_service->table_map()),
        ));
    }

    /** Toàn bộ đơn: thông tin đơn, món đang hiện, số dòng chưa báo bếp, thanh toán (nếu đã trả). */
    private function _order_response($order_id, array $extra = array())
    {
        $order = $this->Order_model->get_detail($order_id);
        if ( ! $order)
        {
            $this->fail(404, 'Không tìm thấy đơn.');
        }
        $panel = $this->pos_service->panel_data($order);

        json_response(array_merge(array(
            'success'       => TRUE,
            'order'         => api_order($order),
            'is_active'     => $panel['is_active'],
            'items'         => array_map('api_order_item', $panel['visible_items']),
            'pending_count' => $panel['pending_count'],
            'payment'       => $order['status'] === 'PAID' ? api_payment($this->Payment_model->get_by_order($order_id)) : NULL,
        ), $extra));
    }
}
