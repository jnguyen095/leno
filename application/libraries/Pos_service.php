<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nghiệp vụ POS dùng chung cho màn hình web (Tables, Orders) và API ứng dụng
 * di động (Api_v1): mở bàn, thêm/hủy món, báo bếp, thanh toán, chuyển/gộp bàn.
 * Controller lo phần nhận dữ liệu và trả về (redirect/HTML/JSON); mọi quy tắc
 * về trạng thái đơn/bàn nằm ở đây để hai nơi luôn hành xử giống nhau.
 */
class Pos_service
{
    const ACTIVE_STATUSES = array('OPEN', 'WAIT_PAYMENT');
    const PAYMENT_METHODS = array('CASH', 'CARD', 'TRANSFER', 'QR');

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('kds');
        $this->CI->load->model(array(
            'Table_model', 'Table_session_model', 'Order_model', 'Order_item_model',
            'Product_model', 'Kitchen_ticket_model', 'Payment_model', 'Setting_model', 'Audit_log_model',
            'Price_book_model', 'Discount_model',
        ));
    }

    /**
     * Chọn / đổi / bỏ khuyến mãi cho đơn (người bán tự chọn trên POS, chỉ khuyến mãi có hiệu lực hôm nay).
     * Đơn chưa đủ giá trị tối thiểu vẫn chọn được — giảm 0đ cho tới khi đủ. Trả về NULL nếu thành công, hoặc chuỗi lỗi.
     */
    public function set_discount($order, $discount_id, $user_id)
    {
        $discount = NULL;
        if ($discount_id)
        {
            $discount = $this->CI->Discount_model->find_applicable($discount_id);
            if ( ! $discount)
            {
                return 'Khuyến mãi không tồn tại hoặc không áp dụng hôm nay.';
            }
        }
        $this->CI->Order_model->set_discount($order['id'], $discount);
        $this->CI->Audit_log_model->log('order', 'SET_DISCOUNT', array('discount_id' => $order['discount_id']),
            array('order_id' => $order['id'], 'discount_id' => $discount ? (int) $discount['id'] : NULL), $user_id);
        return NULL;
    }

    /**
     * Giá bán theo bảng giá: array(giá bán, giá gốc khi khác giá bán | NULL).
     * Món không có trong bảng giá (hoặc chưa chọn bảng giá) -> bán giá gốc, original = NULL.
     */
    public static function book_price($base_price, array $book_prices, $product_id)
    {
        $base = (float) $base_price;
        if ( ! isset($book_prices[(int) $product_id])) return array($base, NULL);
        $price = (float) $book_prices[(int) $product_id];
        return array($price, abs($price - $base) < 0.005 ? NULL : $base);
    }

    /**
     * Chọn / đổi / bỏ bảng giá cho đơn (người bán tự chọn trên POS). Mọi món đang có trong đơn được tính
     * lại theo bảng giá mới (bỏ chọn = về giá gốc). Chỉ chọn được bảng giá đang có hiệu lực hôm nay.
     * Trả về NULL nếu thành công, hoặc chuỗi lỗi.
     */
    public function set_price_book($order, $book_id, $user_id)
    {
        $book = NULL;
        if ($book_id)
        {
            $book = $this->CI->Price_book_model->get_by_id($book_id);
            if ( ! $book || ! $this->CI->Price_book_model->is_applicable($book_id))
            {
                return 'Bảng giá không tồn tại hoặc không áp dụng hôm nay.';
            }
        }

        $this->CI->Order_model->set_price_book($order['id'], $book ? (int) $book['id'] : NULL, $book ? $book['name'] : NULL);

        $book_prices = $book ? $this->CI->Price_book_model->get_prices($book['id']) : array();
        foreach ($this->CI->Order_item_model->get_active_by_order($order['id']) as $it)
        {
            // Giá gốc = giá gốc đã lưu lúc bán (nếu từng áp bảng giá), không thì chính giá hiện tại của dòng.
            $base = $it['original_price'] !== NULL ? $it['original_price'] : $it['price'];
            list($price, $original) = self::book_price($base, $book_prices, $it['product_id']);
            $this->CI->Order_item_model->set_price($it['id'], $price, $original, (int) $it['qty']);
        }
        $this->CI->Order_model->recalc_totals($order['id']);
        $this->CI->Audit_log_model->log('order', 'SET_PRICE_BOOK', array('price_book_id' => $order['price_book_id']),
            array('order_id' => $order['id'], 'price_book_id' => $book ? (int) $book['id'] : NULL), $user_id);
        return NULL;
    }

    public function is_active($order)
    {
        return $order && in_array($order['status'], self::ACTIVE_STATUSES, TRUE);
    }

    /** Đơn đang phục vụ theo id, hoặc NULL nếu không có / đã đóng. */
    public function get_active_order($order_id)
    {
        $order = $this->CI->Order_model->get_detail($order_id);
        return $this->is_active($order) ? $order : NULL;
    }

    /** Món ACTIVE thuộc đơn đang phục vụ, hoặc NULL. */
    public function get_active_item($order_id, $item_id)
    {
        $order = $this->CI->Order_model->get_by_id($order_id);
        $item = $this->CI->Order_item_model->get_by_id($item_id);
        if ( ! $this->is_active($order) || ! $item || (int) $item['order_session_id'] !== (int) $order_id
            || $item['status'] !== 'ACTIVE')
        {
            return NULL;
        }
        return $item;
    }

    /**
     * Sơ đồ bàn: bàn thường + bàn "Mang đi" đứng đầu khi bật bán mang đi (đã tắt nhưng
     * còn đơn dở thì vẫn hiện để xử lý cho xong). Bàn có khách kèm 'order' và 'session_id'.
     */
    public function table_map()
    {
        $tables = $this->CI->Table_model->get_all();

        $takeaway = $this->CI->Table_model->get_takeaway();
        if ($takeaway && ($this->CI->Setting_model->is_takeaway_enabled() || $takeaway['status'] !== 'AVAILABLE'))
        {
            array_unshift($tables, $takeaway);
        }

        foreach ($tables as &$t)
        {
            $t['order'] = NULL;
            if ($t['status'] !== 'AVAILABLE')
            {
                $session = $this->CI->Table_session_model->get_open_by_table($t['id']);
                if ($session)
                {
                    $t['order'] = $this->CI->Order_model->get_active_by_table_session($session['id']);
                    $t['session_id'] = $session['id'];
                }
            }
        }
        return $tables;
    }

    /**
     * Mở bàn trống. Bàn "Trống" nhưng còn đơn rỗng đang mở (đã chọn bàn mà chưa thêm món)
     * thì dùng lại đơn đó. Trả về order_id, hoặc NULL nếu bàn không mở được.
     */
    public function open_table($table_id, $user_id)
    {
        $table = $this->CI->Table_model->get_by_id($table_id);
        if ( ! $table || $table['status'] !== 'AVAILABLE'
            || ($table['is_takeaway'] && ! $this->CI->Setting_model->is_takeaway_enabled()))
        {
            return NULL;
        }

        $session = $this->CI->Table_session_model->get_open_by_table($table_id);
        $existing = $session ? $this->CI->Order_model->get_active_by_table_session($session['id']) : NULL;
        if ($existing)
        {
            return (int) $existing['id'];
        }

        $result = $this->CI->Order_model->open_table_with_order($table_id, $user_id);
        $this->CI->Audit_log_model->log('table', 'OPEN_TABLE', NULL,
            array('table_id' => $table_id, 'session_id' => $result['session_id'], 'order_id' => $result['order_id']), $user_id);

        // Bàn mở mới nhận luôn bảng giá + khuyến mãi đang chọn ở Sơ đồ bàn.
        $this->apply_shop_offers_to_order((int) $result['order_id'], $user_id);

        return (int) $result['order_id'];
    }

    // ---- Bảng giá + khuyến mãi chọn ở Sơ đồ bàn (dùng chung mọi máy) ----

    /**
     * Bảng giá / khuyến mãi đang chọn ở Sơ đồ bàn: array('price_book' => dòng|NULL, 'discount' => dòng|NULL).
     * Lựa chọn hết hiệu lực (sang ngày / thứ khác, bị tắt, bị xoá) coi như không chọn.
     */
    public function shop_offers()
    {
        $book_id = (int) $this->CI->Setting_model->get('pos_price_book_id', 0);
        $discount_id = (int) $this->CI->Setting_model->get('pos_discount_id', 0);
        $book = NULL;
        if ($book_id)
        {
            foreach ($this->CI->Price_book_model->get_applicable() as $b)
            {
                if ((int) $b['id'] === $book_id) { $book = $b; break; }
            }
        }
        return array(
            'price_book' => $book,
            'discount'   => $discount_id ? $this->CI->Discount_model->find_applicable($discount_id) : NULL,
        );
    }

    /**
     * Chọn bảng giá + khuyến mãi ở Sơ đồ bàn (0 = bỏ): lưu làm lựa chọn chung và áp ngay cho MỌI đơn đang mở
     * (chưa thanh toán), kể cả đơn đã từng bỏ ở trang đơn. Trả về array('count' => số đơn) hoặc array('error' => ...).
     */
    public function set_shop_offers($book_id, $discount_id, $user_id)
    {
        $book_id = (int) $book_id;
        $discount_id = (int) $discount_id;
        if ($book_id && ! $this->CI->Price_book_model->is_applicable($book_id))
        {
            return array('error' => 'Bảng giá không tồn tại hoặc không áp dụng hôm nay.');
        }
        if ($discount_id && ! $this->CI->Discount_model->find_applicable($discount_id))
        {
            return array('error' => 'Khuyến mãi không tồn tại hoặc không áp dụng hôm nay.');
        }

        $old = array('price_book_id' => (int) $this->CI->Setting_model->get('pos_price_book_id', 0), 'discount_id' => (int) $this->CI->Setting_model->get('pos_discount_id', 0));
        $this->CI->Setting_model->set('pos_price_book_id', (string) $book_id);
        $this->CI->Setting_model->set('pos_discount_id', (string) $discount_id);

        $orders = $this->CI->Order_model->get_active_orders();
        foreach ($orders as $o)
        {
            $this->apply_shop_offers_to_order((int) $o['id'], $user_id);
        }
        $this->CI->Audit_log_model->log('pos', 'SET_SHOP_OFFERS', $old,
            array('price_book_id' => $book_id, 'discount_id' => $discount_id, 'orders' => count($orders)), $user_id);
        return array('count' => count($orders));
    }

    /** Áp bảng giá + khuyến mãi đang chọn ở Sơ đồ bàn cho một đơn (không chọn = về giá gốc / bỏ khuyến mãi). */
    public function apply_shop_offers_to_order($order_id, $user_id)
    {
        $offers = $this->shop_offers();
        $order = $this->CI->Order_model->get_by_id($order_id);
        if ( ! $order) return;

        $book_id = $offers['price_book'] ? (int) $offers['price_book']['id'] : 0;
        if ((int) $order['price_book_id'] !== $book_id)
        {
            $this->set_price_book($order, $book_id, $user_id);
            $order = $this->CI->Order_model->get_by_id($order_id);
        }
        $discount_id = $offers['discount'] ? (int) $offers['discount']['id'] : 0;
        if ((int) $order['discount_id'] !== $discount_id)
        {
            $this->set_discount($order, $discount_id, $user_id);
        }
    }

    /**
     * Thêm món vào đơn — CHƯA báo bếp. $items: [['product_id'=>, 'qty'=>, 'note'=>], ...].
     * Cùng món không ghi chú thì cộng dồn số lượng. Trả về danh sách đã thêm (bỏ món ngừng bán).
     */
    public function add_items($order_id, array $items, $user_id)
    {
        $added = array();
        // Đơn đã chọn bảng giá -> món mới cũng bán theo bảng giá đó (món không có trong bảng giá = giá gốc).
        $order = $this->CI->Order_model->get_by_id($order_id);
        $book_prices = $order && $order['price_book_id'] ? $this->CI->Price_book_model->get_prices($order['price_book_id']) : array();
        foreach ($items as $it)
        {
            $product = isset($it['product_id']) ? $this->CI->Product_model->get_by_id($it['product_id']) : NULL;
            if ( ! $product || $product['status'] !== 'ACTIVE') continue;

            $qty = max(1, (int) (isset($it['qty']) ? $it['qty'] : 1));
            $note = clean_note(isset($it['note']) ? $it['note'] : NULL);
            list($price, $original) = self::book_price($product['price'], $book_prices, $product['id']);
            $this->CI->Order_item_model->add_or_merge($order_id, $product['id'], $qty, $price, $note, $original);
            $added[] = array('product_id' => $product['id'], 'qty' => $qty, 'note' => $note);
        }

        if ($added)
        {
            $this->CI->Order_model->recalc_totals($order_id);
            $this->CI->Audit_log_model->log('order', 'ADD_ITEM', NULL, array('order_id' => $order_id, 'items' => $added), $user_id);
        }
        return $added;
    }

    /** Đổi số lượng (tối thiểu 1 — bỏ món dùng remove_item). Trả về TRUE nếu có thay đổi. */
    public function update_item_qty($item, $qty, $user_id)
    {
        $qty = (int) $qty;
        if ($qty < 1 || $qty === (int) $item['qty'])
        {
            return FALSE;
        }
        $this->CI->Order_item_model->update_qty($item['id'], $qty);
        $this->CI->Audit_log_model->log('order_item', 'UPDATE_QTY', NULL, array('item_id' => $item['id'], 'qty' => $qty), $user_id);
        $this->CI->Order_model->recalc_totals($item['order_session_id']);
        return TRUE;
    }

    /**
     * Ghi chú cho một dòng món (vd "Ít đá"). Món đã báo bếp mà đổi ghi chú thì lần
     * "Thông báo" sau in mục ĐỔI GHI CHÚ cho bếp.
     */
    public function set_item_note($item, $note, $user_id)
    {
        $note = clean_note($note);
        $this->CI->Order_item_model->set_note($item['id'], $note);
        $this->CI->Audit_log_model->log('order_item', 'UPDATE_NOTE', NULL, array('item_id' => $item['id'], 'note' => $note), $user_id);
        return $note;
    }

    /**
     * Bếp chưa biết món này -> xoá hẳn khỏi đơn. Đã báo bếp -> giữ dòng ở trạng thái
     * CANCELLED (ẩn khỏi danh sách) để lần "Thông báo" sau in mục HỦY cho bếp.
     */
    public function remove_item($item, $user_id)
    {
        if ((int) $item['notified_qty'] === 0)
        {
            $this->CI->Order_item_model->delete($item['id']);
            $this->CI->Audit_log_model->log('order_item', 'DELETE_ITEM', $item, NULL, $user_id);
        }
        else
        {
            $this->CI->Order_item_model->cancel($item['id']);
            $this->CI->Audit_log_model->log('order_item', 'CANCEL_ITEM', NULL, array('item_id' => $item['id']), $user_id);
        }
        $this->CI->Order_model->recalc_totals($item['order_session_id']);
    }

    public function set_order_note($order_id, $note, $user_id)
    {
        $note = clean_note($note);
        $this->CI->Order_model->set_note($order_id, $note);
        $this->CI->Audit_log_model->log('order', 'UPDATE_NOTE', NULL, array('order_id' => $order_id, 'note' => $note), $user_id);
        return $note;
    }

    /**
     * "Thông báo": gửi phần chưa báo bếp — món mới/tăng số lượng thành phiếu bếp (KDS), món
     * bị bớt/hủy thành mục "HỦY", món đổi ghi chú thành mục "ĐỔI GHI CHÚ". Đơn vẫn mở.
     * Trả về dữ liệu phiếu bếp để in, hoặc NULL nếu không có gì cần báo.
     */
    public function notify_kitchen($order, $user)
    {
        $send = array();
        $cancel = array();
        $changed = array();
        foreach ($this->CI->Order_item_model->get_by_order($order['id']) as $it)
        {
            $target = $it['status'] === 'ACTIVE' ? (int) $it['qty'] : 0;
            $delta = $target - (int) $it['notified_qty'];
            $note_changed = Order_item_model::note_changed($it);
            if ($delta === 0 && ! $note_changed) continue;

            $line = array('product_id' => $it['product_id'], 'product_name' => $it['product_name'], 'note' => $it['note']);
            if ($note_changed)
            {
                $changed[] = $line + array('qty' => min($target, (int) $it['notified_qty']), 'old_note' => $it['notified_note']);
            }
            if ($delta > 0) $send[] = $line + array('qty' => $delta);
            elseif ($delta < 0) $cancel[] = $line + array('qty' => -$delta);

            $this->CI->Order_item_model->set_notified($it['id'], $target, $target > 0 ? $it['note'] : $it['notified_note']);
        }

        if ( ! $send && ! $cancel && ! $changed)
        {
            return NULL;
        }

        if ($send)
        {
            $this->CI->Kitchen_ticket_model->create_ticket($order['id'], $order['table_id'], $send);
        }
        $this->CI->Audit_log_model->log('order', 'NOTIFY_KITCHEN', NULL,
            array('order_id' => $order['id'], 'send' => $send, 'cancel' => $cancel, 'changed' => $changed), $user['id']);

        return array(
            'send'       => $send,
            'cancel'     => $cancel,
            'changed'    => $changed,
            'order_note' => $order['note'],
            'created_at' => date('Y-m-d H:i:s'),
            'staff'      => $user['fullname'],
        );
    }

    /**
     * Lịch sử "Thông báo" (báo bếp) của đơn — web + ứng dụng, mới nhất trước. Mỗi lần: id, send/cancel/changed
     * (product_id, product_name, qty, note, old_note), created_at, staff. Lấy từ nhật ký NOTIFY_KITCHEN.
     */
    public function kitchen_history($order_id)
    {
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
        foreach ($this->CI->Audit_log_model->get_kitchen_notifications($order_id) as $row)
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
        return $history;
    }

    /**
     * "Thanh toán": ghi nhận thanh toán, đóng phiên bàn, bàn về Trống.
     * Trả về array('payment_id' => ...) hoặc array('error' => 'thông báo lỗi').
     */
    public function pay($order_id, $method, $received_amount, $user_id)
    {
        $order = $this->get_active_order($order_id);
        if ( ! $order)
        {
            return array('error' => 'Đơn đã đóng, không thể thanh toán.');
        }

        $this->CI->Order_model->recalc_totals($order_id);
        $order = $this->CI->Order_model->get_detail($order_id);

        if ( ! $this->CI->Order_item_model->get_active_by_order($order_id))
        {
            return array('error' => 'Đơn chưa có món nào để thanh toán.');
        }

        if ( ! in_array($method, self::PAYMENT_METHODS, TRUE))
        {
            $method = 'CASH';
        }
        $total = (float) $order['total_amount'];
        $received = $method === 'CASH' ? (float) $received_amount : $total;
        if ($received < $total)
        {
            return array('error' => 'Số tiền khách đưa chưa đủ.');
        }

        $payment_id = $this->CI->Payment_model->create($order_id, $method, $total, $received, $user_id);
        $this->CI->Order_model->mark_paid($order_id);

        if ($order['table_session_id'])
        {
            $this->CI->Table_session_model->close($order['table_session_id']);
            $this->CI->Table_model->set_status($order['table_id'], 'AVAILABLE');
        }

        $this->CI->Audit_log_model->log('payment', 'PAY', NULL,
            array('order_id' => $order_id, 'payment_id' => $payment_id, 'method' => $method), $user_id);

        return array('payment_id' => $payment_id);
    }

    /** Chuyển khách sang bàn trống. Trả về TRUE nếu đã chuyển. */
    public function transfer_table($table_id, $target_id, $user_id)
    {
        $session = $this->CI->Table_session_model->get_open_by_table($table_id);
        $target = $this->CI->Table_model->get_by_id($target_id);
        if ( ! $session || ! $target || $target['status'] !== 'AVAILABLE' || (int) $target_id === (int) $table_id)
        {
            return FALSE;
        }

        // Bàn đích "Trống" có thể còn phiên + đơn rỗng (đã chọn bàn mà chưa thêm món) -> bỏ đi
        // trước khi chuyển, để mỗi bàn chỉ có một phiên đang mở.
        $target_session = $this->CI->Table_session_model->get_open_by_table($target_id);
        if ($target_session)
        {
            $target_order = $this->CI->Order_model->get_active_by_table_session($target_session['id']);
            if ($target_order) $this->CI->Order_model->cancel($target_order['id']);
            $this->CI->Table_session_model->close($target_session['id']);
        }

        $this->CI->db->where('id', $session['id'])->update('table_sessions', array('table_id' => $target_id));
        $order = $this->CI->Order_model->get_active_by_table_session($session['id']);
        if ($order)
        {
            $this->CI->db->where('order_session_id', $order['id'])->update('kitchen_tickets', array('table_id' => $target_id));
        }
        $this->CI->Table_model->set_status($table_id, 'AVAILABLE');
        if ($order) $this->CI->Order_model->sync_table_status($order['id']);

        $this->CI->Audit_log_model->log('table', 'TRANSFER', array('from' => $table_id), array('to' => $target_id), $user_id);
        return TRUE;
    }

    /** Gộp đơn của bàn này vào bàn đang phục vụ khác. Trả về id đơn đích, hoặc NULL. */
    public function merge_table($table_id, $target_table_id, $user_id)
    {
        if ((int) $table_id === (int) $target_table_id)
        {
            return NULL;
        }
        $session = $this->CI->Table_session_model->get_open_by_table($table_id);
        $target_session = $this->CI->Table_session_model->get_open_by_table($target_table_id);
        if ( ! $session || ! $target_session)
        {
            return NULL;
        }

        $source_order = $this->CI->Order_model->get_active_by_table_session($session['id']);
        $target_order = $this->CI->Order_model->get_active_by_table_session($target_session['id']);
        if ( ! $source_order || ! $target_order)
        {
            return NULL;
        }

        $this->CI->db->where('order_session_id', $source_order['id'])->update('order_items', array('order_session_id' => $target_order['id']));
        $this->CI->db->where('order_session_id', $source_order['id'])->update('kitchen_tickets', array('order_session_id' => $target_order['id'], 'table_id' => $target_table_id));
        $this->CI->Order_model->cancel($source_order['id']);
        $this->CI->Order_model->recalc_totals($target_order['id']);
        $this->CI->Table_session_model->close($session['id']);
        $this->CI->Table_model->set_status($table_id, 'AVAILABLE');
        $this->CI->Order_model->sync_table_status($target_order['id']);

        $this->CI->Audit_log_model->log('table', 'MERGE', array('from' => $table_id), array('into' => $target_table_id), $user_id);
        return (int) $target_order['id'];
    }

    /** Món đã gọi + số dòng chưa báo bếp — dùng chung cho trang đơn và API. */
    public function panel_data($order)
    {
        $this->CI->load->model('Setting_model');
        $is_active = $this->is_active($order);
        $items = $this->CI->Order_item_model->get_by_order($order['id']);
        $active_items = array_values(array_filter($items, function ($it) { return $it['status'] === 'ACTIVE'; }));

        $pending_count = 0;
        foreach ($items as $it)
        {
            $target = $it['status'] === 'ACTIVE' ? (int) $it['qty'] : 0;
            if ($target !== (int) $it['notified_qty'] || Order_item_model::note_changed($it)) $pending_count++;
        }

        return array(
            'order'         => $order,
            'is_active'     => $is_active,
            'table_label'   => $order['table_name'] ?: 'Mang đi',
            'items'         => $items,
            'active_items'  => $active_items,
            // Đơn đang phục vụ ẩn món đã hủy; đơn đã đóng hiện đủ để xem lại lịch sử.
            'visible_items' => $is_active ? $active_items : $items,
            'pending_count' => $pending_count,
            // Tài khoản nhận chuyển khoản: phiếu tạm tính in mã VietQR (giống ứng dụng POS).
            'bank_qr'       => $this->CI->Setting_model->get_bank_qr(),
            // Giá mới theo bảng giá đơn đang dùng (bảng giá + khuyến mãi chọn ở Sơ đồ bàn; trang đơn chỉ bỏ được).
            'book_prices'   => $order['price_book_id'] ? $this->CI->Price_book_model->get_prices($order['price_book_id']) : array(),
        );
    }
}
