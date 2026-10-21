<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Số liệu trang Tổng quan. Doanh thu theo NGÀY THANH TOÁN (paid_at) — khớp danh sách Đơn hàng và Sổ quỹ:
 * đơn mở hôm trước, thanh toán hôm nay tính vào hôm nay. $from / $to: 'Y-m-d H:i:s' hoặc NULL (không giới hạn).
 */
class Dashboard_model extends CI_Model
{
    private function _paid_range($from, $to, $col = 'o.paid_at')
    {
        $w = "o.status = 'PAID'";
        if ($from !== NULL) $w .= ' AND '.$col.' >= '.$this->db->escape($from);
        if ($to !== NULL)   $w .= ' AND '.$col.' <= '.$this->db->escape($to);
        return $w;
    }

    /** Doanh thu, số đơn đã thanh toán, giá trị TB / đơn, số món bán, tổng giảm giá (khuyến mãi + bảng giá). */
    public function kpis($from, $to)
    {
        $w = $this->_paid_range($from, $to);
        $r = $this->db->query("SELECT COUNT(*) AS orders, COALESCE(SUM(o.total_amount),0) AS revenue,
                COALESCE(SUM(o.discount_amount),0) AS discount FROM order_sessions o WHERE $w")->row_array();
        $items = $this->db->query("SELECT COALESCE(SUM(i.qty),0) AS qty,
                COALESCE(SUM(CASE WHEN i.original_price IS NOT NULL AND i.original_price > i.price THEN (i.original_price - i.price) * i.qty ELSE 0 END),0) AS book_saving
                FROM order_items i JOIN order_sessions o ON o.id = i.order_session_id WHERE i.status = 'ACTIVE' AND $w")->row_array();
        $orders = (int) $r['orders'];
        return array(
            'revenue'     => (float) $r['revenue'],
            'orders'      => $orders,
            'avg'         => $orders ? (float) $r['revenue'] / $orders : 0.0,
            'items'       => (int) round((float) $items['qty']),
            'discount'    => (float) $r['discount'],
            'book_saving' => (float) $items['book_saving'],
        );
    }

    /**
     * Lãi gộp của đơn đã thanh toán trong khoảng: chỉ tính các món có giá vốn (order_items.cost_price chụp lúc bán).
     * Mỗi đơn: doanh thu món có giá vốn − giá vốn − phần giảm giá tổng đơn chia theo tỉ lệ doanh thu của các món đó.
     * VAT không tính vào doanh thu (không phải tiền của quán).
     *  - profit: lãi gộp; revenue: doanh thu (sau giảm giá) của các món có giá vốn; cost: tổng giá vốn;
     *  - margin: profit / revenue (%); coverage: tỉ lệ doanh thu món đã có giá vốn (%) — < 100 thì lãi thật chưa đủ.
     */
    public function profit($from, $to)
    {
        $w = $this->_paid_range($from, $to);
        $r = $this->db->query("SELECT
                COALESCE(SUM(t.sub_cost - t.cost - CASE WHEN t.sub_all > 0 THEN t.discount * t.sub_cost / t.sub_all ELSE 0 END), 0) AS profit,
                COALESCE(SUM(t.sub_cost - CASE WHEN t.sub_all > 0 THEN t.discount * t.sub_cost / t.sub_all ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(t.cost), 0) AS cost, COALESCE(SUM(t.sub_cost), 0) AS sub_cost, COALESCE(SUM(t.sub_all), 0) AS sub_all
            FROM (
                SELECT o.id, o.discount_amount AS discount,
                    SUM(i.amount) AS sub_all,
                    SUM(CASE WHEN i.cost_price IS NOT NULL THEN i.amount ELSE 0 END) AS sub_cost,
                    SUM(CASE WHEN i.cost_price IS NOT NULL THEN i.cost_price * i.qty ELSE 0 END) AS cost
                FROM order_sessions o JOIN order_items i ON i.order_session_id = o.id AND i.status = 'ACTIVE'
                WHERE $w GROUP BY o.id, o.discount_amount
            ) t")->row_array();
        $revenue = (float) $r['revenue'];
        return array(
            'profit'   => (float) $r['profit'],
            'revenue'  => $revenue,
            'cost'     => (float) $r['cost'],
            'margin'   => $revenue > 0 ? round((float) $r['profit'] / $revenue * 100) : NULL,
            'coverage' => (float) $r['sub_all'] > 0 ? (int) floor((float) $r['sub_cost'] / (float) $r['sub_all'] * 100) : NULL,
        );
    }

    /** Tiền đã thu theo hình thức (bảng payments, theo giờ thanh toán): method => total. */
    public function by_method($from, $to)
    {
        $w = $this->_paid_range($from, $to, 'p.paid_at');
        $out = array_fill_keys(array_keys(payment_methods()), 0.0);
        foreach ($this->db->query("SELECT p.payment_method, COALESCE(SUM(p.amount),0) AS total FROM payments p
                JOIN order_sessions o ON o.id = p.order_session_id WHERE $w GROUP BY p.payment_method")->result_array() as $row)
        {
            $out[$row['payment_method']] = (float) $row['total'];
        }
        return $out;
    }

    /** Đơn đang phục vụ ngay lúc này (không theo bộ lọc thời gian). */
    public function open_now()
    {
        $r = $this->db->query("SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS total FROM order_sessions o
            WHERE o.status IN ('OPEN','WAIT_PAYMENT') AND EXISTS (SELECT 1 FROM order_items i WHERE i.order_session_id = o.id AND i.status = 'ACTIVE')")->row_array();
        return array('count' => (int) $r['n'], 'total' => (float) $r['total']);
    }

    /**
     * Doanh thu theo cột thời gian: $bucket = hour (0-23) | day (từng ngày from..to) | month (từng tháng).
     * Trả về [['key', 'label', 'tip', 'value', 'orders'], ...] — đủ mọi cột (cột trống = 0).
     */
    public function revenue_series($from, $to, $bucket)
    {
        $fmt = array('hour' => '%H', 'day' => '%Y-%m-%d', 'month' => '%Y-%m')[$bucket];
        $w = $this->_paid_range($from, $to);
        $rows = array();
        foreach ($this->db->query("SELECT DATE_FORMAT(o.paid_at, '$fmt') AS k, SUM(o.total_amount) AS v, COUNT(*) AS n
                FROM order_sessions o WHERE $w GROUP BY k ORDER BY k")->result_array() as $r)
        {
            $rows[$r['k']] = array((float) $r['v'], (int) $r['n']);
        }

        $keys = array();
        if ($bucket === 'hour')
        {
            for ($h = 0; $h < 24; $h++) $keys[] = sprintf('%02d', $h);
        }
        elseif ($bucket === 'day')
        {
            for ($t = strtotime(substr($from, 0, 10)); $t <= strtotime(substr($to, 0, 10)); $t = strtotime('+1 day', $t)) $keys[] = date('Y-m-d', $t);
        }
        else
        {
            // Tất cả: từ tháng có đơn thanh toán đầu tiên tới tháng này (tối đa 24 tháng gần nhất).
            $first = $rows ? min(array_keys($rows)) : date('Y-m');
            $start = max(strtotime($first.'-01'), strtotime(date('Y-m-01', strtotime('-23 months'))));
            for ($t = $start; $t <= strtotime(date('Y-m-01')); $t = strtotime('+1 month', $t)) $keys[] = date('Y-m', $t);
        }

        $out = array();
        foreach ($keys as $k)
        {
            if ($bucket === 'hour')      { $label = (int) $k.'h'; $tip = $k.':00 – '.$k.':59'; }
            elseif ($bucket === 'day')   { $label = date('d/m', strtotime($k)); $tip = date('d/m/Y', strtotime($k)); }
            else                         { $label = date('m/y', strtotime($k.'-01')); $tip = 'Tháng '.date('m/Y', strtotime($k.'-01')); }
            $out[] = array('key' => $k, 'label' => $label, 'tip' => $tip,
                'value' => isset($rows[$k]) ? $rows[$k][0] : 0.0, 'orders' => isset($rows[$k]) ? $rows[$k][1] : 0);
        }
        return $out;
    }

    /** Món bán chạy (theo số lượng, món còn hiệu lực trong đơn đã thanh toán). */
    public function top_products($from, $to, $limit = 8)
    {
        $w = $this->_paid_range($from, $to);
        return $this->db->query("SELECT p.id, p.product_name AS name, SUM(i.qty) AS qty, SUM(i.amount) AS revenue
                FROM order_items i JOIN order_sessions o ON o.id = i.order_session_id JOIN products p ON p.id = i.product_id
                WHERE i.status = 'ACTIVE' AND $w GROUP BY p.id, p.product_name ORDER BY qty DESC, revenue DESC LIMIT ".(int) $limit)->result_array();
    }

    /** Doanh thu theo danh mục món. */
    public function by_category($from, $to)
    {
        $w = $this->_paid_range($from, $to);
        return $this->db->query("SELECT COALESCE(c.name, 'Khác') AS name, SUM(i.amount) AS revenue, SUM(i.qty) AS qty
                FROM order_items i JOIN order_sessions o ON o.id = i.order_session_id JOIN products p ON p.id = i.product_id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE i.status = 'ACTIVE' AND $w GROUP BY c.id, c.name ORDER BY revenue DESC")->result_array();
    }

    /** Số đơn đã thanh toán có dùng bảng giá / khuyến mãi trong kỳ. */
    public function offer_usage($from, $to)
    {
        $w = $this->_paid_range($from, $to);
        return $this->db->query("SELECT SUM(o.price_book_id IS NOT NULL) AS book_orders, SUM(o.discount_amount > 0) AS discount_orders
            FROM order_sessions o WHERE $w")->row_array();
    }

    // ---------- Hoạt động gần đây (từ Nhật ký hệ thống) ----------

    /** Hành động đưa vào dòng hoạt động (bỏ các thao tác vụn như thêm từng món, đổi số lượng). */
    const FEED_ACTIONS = array(
        'payment.PAY', 'table.OPEN_TABLE', 'order.NOTIFY_KITCHEN', 'order.DELETE_ORDER', 'table.MERGE', 'table.TRANSFER',
        'auth.LOGIN', 'auth.LOGOUT', 'cashbook.CREATE', 'cashbook.UPDATE', 'cashbook.DELETE',
        'stock_transaction.STOCK_IN_BATCH', 'stock_transaction.STOCK_OUT_BATCH', 'stock_transaction.STOCK_ADJUST_BATCH',
        'pos.SET_SHOP_OFFERS', 'price_book.CREATE', 'price_book.UPDATE', 'price_book.TOGGLE', 'price_book.DELETE',
        'discount.CREATE', 'discount.UPDATE', 'discount.TOGGLE', 'discount.DELETE', 'product.CREATE', 'product.UPDATE',
        'order_item.CANCEL_ITEM', 'audit_log.PURGE', 'stock.PURGE_HISTORY',
    );

    /** Hoạt động mới nhất (id > $after_id nếu có), đã chuyển thành câu dễ đọc. */
    public function activity($limit = 25, $after_id = 0)
    {
        $pairs = array();
        foreach (self::FEED_ACTIONS as $a) { list($m, $ac) = explode('.', $a); $pairs[] = '(a.module = '.$this->db->escape($m).' AND a.action = '.$this->db->escape($ac).')'; }
        $rows = $this->db->query("SELECT a.id, a.module, a.action, a.old_data, a.new_data, a.created_at, u.fullname
                FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                WHERE (".implode(' OR ', $pairs).")".($after_id ? ' AND a.id > '.(int) $after_id : '')."
                ORDER BY a.id DESC LIMIT ".(int) $limit)->result_array();

        // Tra cứu một lần: đơn (mã + tổng tiền), bàn (tên).
        $order_ids = array(); $table_ids = array();
        foreach ($rows as &$r)
        {
            $r['new'] = json_decode((string) $r['new_data'], TRUE) ?: array();
            $r['old'] = json_decode((string) $r['old_data'], TRUE) ?: array();
            if (isset($r['new']['order_id'])) $order_ids[] = (int) $r['new']['order_id'];
            foreach (array('table_id', 'into', 'to') as $k) if (isset($r['new'][$k])) $table_ids[] = (int) $r['new'][$k];
            if (isset($r['old']['from'])) $table_ids[] = (int) $r['old']['from'];
        }
        unset($r);
        $orders = $order_ids ? array_column($this->db->select('id, order_no, total_amount')->where_in('id', array_unique($order_ids))->get('order_sessions')->result_array(), NULL, 'id') : array();
        $tables = $table_ids ? array_column($this->db->select('id, table_name')->where_in('id', array_unique($table_ids))->get('cafe_tables')->result_array(), 'table_name', 'id') : array();

        $out = array();
        foreach ($rows as $r)
        {
            list($icon, $tone, $text) = $this->_sentence($r, $orders, $tables);
            $out[] = array('id' => (int) $r['id'], 'icon' => $icon, 'tone' => $tone, 'text' => $text, 'at' => $r['created_at']);
        }
        return $out;
    }

    private function _sentence(array $r, array $orders, array $tables)
    {
        $who = $r['fullname'] ?: 'Hệ thống';
        $n = $r['new']; $o = $r['old'];
        $money = function ($v) { return number_format((float) $v, 0, ',', '.').' đ'; };
        $order = isset($n['order_id'], $orders[(int) $n['order_id']]) ? $orders[(int) $n['order_id']] : NULL;
        $table = function ($id) use ($tables) { return isset($tables[(int) $id]) ? $tables[(int) $id] : 'bàn #'.(int) $id; };

        switch ($r['module'].'.'.$r['action'])
        {
            case 'payment.PAY':
                return array('bi-cash-coin', 'good', $who.' vừa bán đơn hàng'.($order ? ' '.$order['order_no'].' trị giá '.$money($order['total_amount']) : '')
                    .(isset($n['method']) ? ' ('.payment_method_label($n['method']).')' : ''));
            case 'table.OPEN_TABLE':
                return array('bi-door-open', 'info', $who.' mở '.(isset($n['table_id']) ? $table($n['table_id']) : 'bàn mới'));
            case 'order.NOTIFY_KITCHEN':
                $count = isset($n['send']) && is_array($n['send']) ? array_sum(array_column($n['send'], 'qty')) : 0;
                return array('bi-megaphone', 'info', $who.' báo bếp'.($count ? ' '.$count.' món' : '').($order ? ' — đơn '.$order['order_no'] : ''));
            case 'order.DELETE_ORDER':
                return array('bi-trash', 'bad', $who.' xoá đơn '.(isset($o['order']['order_no']) ? $o['order']['order_no'] : ''));
            case 'order_item.CANCEL_ITEM':
                return array('bi-x-circle', 'bad', $who.' huỷ một món trong đơn');
            case 'table.MERGE':
                return array('bi-union', 'info', $who.' gộp '.(isset($o['from']) ? $table($o['from']) : 'bàn').' vào '.(isset($n['into']) ? $table($n['into']) : 'bàn khác'));
            case 'table.TRANSFER':
                return array('bi-arrow-left-right', 'info', $who.' chuyển '.(isset($o['from']) ? $table($o['from']) : 'bàn').' sang '.(isset($n['to']) ? $table($n['to']) : 'bàn khác'));
            case 'auth.LOGIN':  return array('bi-box-arrow-in-right', 'muted', $who.' đăng nhập');
            case 'auth.LOGOUT': return array('bi-box-arrow-right', 'muted', $who.' đăng xuất');
            case 'cashbook.CREATE':
                $in = isset($n['type']) && $n['type'] === 'IN';
                return array($in ? 'bi-arrow-down-circle' : 'bi-arrow-up-circle', $in ? 'good' : 'bad',
                    $who.' lập phiếu '.($in ? 'thu' : 'chi').(isset($n['entry_no']) ? ' '.$n['entry_no'] : '').(isset($n['amount']) ? ' '.$money($n['amount']) : ''));
            case 'cashbook.UPDATE': return array('bi-journal-bookmark', 'muted', $who.' sửa phiếu thu / chi');
            case 'cashbook.DELETE': return array('bi-journal-x', 'bad', $who.' xoá phiếu '.(isset($o['entry_no']) ? $o['entry_no'] : 'thu / chi'));
            case 'stock_transaction.STOCK_IN_BATCH':  return array('bi-box-arrow-in-down', 'info', $who.' nhập kho '.(isset($n['count']) ? (int) $n['count'].' mặt hàng' : ''));
            case 'stock_transaction.STOCK_OUT_BATCH': return array('bi-box-arrow-up', 'info', $who.' xuất kho '.(isset($n['count']) ? (int) $n['count'].' mặt hàng' : ''));
            case 'stock_transaction.STOCK_ADJUST_BATCH':
                return array('bi-clipboard-check', 'info', $who.' kiểm kho'.(isset($n['checked']) ? ' '.(int) $n['checked'].' mặt hàng'.(isset($n['changed']) ? ', lệch '.(int) $n['changed'] : '') : ''));
            case 'pos.SET_SHOP_OFFERS': return array('bi-tags', 'info', $who.' đổi bảng giá / khuyến mãi cho các bàn'.(isset($n['orders']) ? ' ('.(int) $n['orders'].' đơn đang mở)' : ''));
            case 'price_book.CREATE': case 'price_book.UPDATE': case 'price_book.TOGGLE': case 'price_book.DELETE':
                return array('bi-tags', 'muted', $who.' '.array('CREATE' => 'tạo', 'UPDATE' => 'sửa', 'TOGGLE' => 'bật / tắt', 'DELETE' => 'xoá')[$r['action']].' bảng giá'
                    .(isset($n['name']) ? ' "'.$n['name'].'"' : (isset($o['name']) ? ' "'.$o['name'].'"' : '')));
            case 'discount.CREATE': case 'discount.UPDATE': case 'discount.TOGGLE': case 'discount.DELETE':
                return array('bi-percent', 'muted', $who.' '.array('CREATE' => 'tạo', 'UPDATE' => 'sửa', 'TOGGLE' => 'bật / tắt', 'DELETE' => 'xoá')[$r['action']].' khuyến mãi'
                    .(isset($n['name']) ? ' "'.$n['name'].'"' : (isset($o['name']) ? ' "'.$o['name'].'"' : '')));
            case 'product.CREATE': return array('bi-cup-hot', 'muted', $who.' thêm món'.(isset($n['product_name']) ? ' "'.$n['product_name'].'"' : ''));
            case 'product.UPDATE': return array('bi-cup-hot', 'muted', $who.' sửa món'.(isset($n['product_name']) ? ' "'.$n['product_name'].'"' : ''));
            case 'audit_log.PURGE':    return array('bi-trash3', 'muted', $who.' dọn nhật ký cũ'.(isset($n['deleted']) ? ' ('.(int) $n['deleted'].' bản ghi)' : ''));
            case 'stock.PURGE_HISTORY': return array('bi-trash3', 'muted', $who.' dọn lịch sử kho cũ');
        }
        return array('bi-dot', 'muted', $who.' — '.$r['module'].' '.$r['action']);
    }
}
