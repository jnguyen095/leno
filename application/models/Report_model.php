<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Số liệu cho các báo cáo (controllers/Reports.php). Chỉ tính đơn ĐÃ THANH TOÁN, theo giờ thanh toán (paid_at) —
 * cùng quy tắc chốt ngày với Đơn hàng / Tổng quan.
 */
class Report_model extends CI_Model
{
    /**
     * Các báo cáo: menu_key (mục menu nhóm "Báo cáo", migration 015) => [method (me/reports/<method>), icon, tên, mô tả].
     * Thêm báo cáo mới: thêm dòng ở đây + method/view trong controllers/Reports.php + mục menu ở một migration mới.
     */
    const REPORTS = array(
        'reports.products'   => array('products', 'bi-box-seam', 'Báo cáo hàng hóa', 'Số lượng bán, doanh thu và lãi theo từng món.'),
        'reports.categories' => array('categories', 'bi-collection', 'Báo cáo danh mục', 'Số lượng bán, doanh thu và lãi theo từng danh mục món.'),
    );

    /** Các báo cáo người dùng được xem (ADMIN: tất cả): menu_key => [method, icon, tên, mô tả]. */
    public static function allowed($user)
    {
        $CI =& get_instance();
        $CI->load->helper('menu_permission');
        return array_filter(self::REPORTS, function ($key) use ($user) { return menu_permission_user_can_key($user, $key); }, ARRAY_FILTER_USE_KEY);
    }

    /** Cột được phép sắp xếp ở Báo cáo hàng hóa: tham số sort => biểu thức SQL. */
    const PRODUCT_SORTS = array(
        'sku' => 'p.sku', 'name' => 'p.product_name', 'qty' => 'qty', 'revenue' => 'revenue', 'profit' => 'profit',
    );

    /**
     * Báo cáo hàng hóa: mỗi món đã bán trong khoảng [$from, $to] (Y-m-d H:i:s).
     *  - qty: số lượng bán; revenue: doanh thu sau bảng giá và sau phần giảm giá tổng đơn chia theo tỉ lệ (chưa VAT);
     *  - profit: lãi gộp = doanh thu − giá vốn, CHỈ tính các lần bán có giá vốn (order_items.cost_price chụp lúc bán);
     *    NULL nếu chưa lần nào có giá vốn; revenue_costed: doanh thu của các lần có giá vốn (để tính biên lãi);
     *  - qty_no_cost: số lượng bán chưa có giá vốn (> 0 = lãi mới tính một phần).
     * $filters: category_id, q (tên / SKU). $sort: khóa trong PRODUCT_SORTS, $dir: asc | desc.
     */
    public function products($from, $to, array $filters = array(), $sort = 'revenue', $dir = 'desc')
    {
        // Phần giảm giá tổng đơn của một dòng món = giảm giá × (thành tiền dòng / tạm tính đơn).
        $disc = 'CASE WHEN o.subtotal > 0 THEN o.discount_amount * i.amount / o.subtotal ELSE 0 END';
        $this->db->select("p.id, p.sku, p.product_name, c.name AS category_name,
                SUM(i.qty) AS qty,
                SUM(i.amount - $disc) AS revenue,
                SUM(CASE WHEN i.cost_price IS NOT NULL THEN i.amount - $disc END) AS revenue_costed,
                SUM(CASE WHEN i.cost_price IS NOT NULL THEN i.amount - $disc - i.cost_price * i.qty END) AS profit,
                SUM(CASE WHEN i.cost_price IS NULL THEN i.qty ELSE 0 END) AS qty_no_cost", FALSE)
            ->from('order_items i')
            ->join('order_sessions o', "o.id = i.order_session_id AND o.status = 'PAID'", 'inner', FALSE)
            ->join('products p', 'p.id = i.product_id')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->where('i.status', 'ACTIVE')
            ->where('o.paid_at >=', $from)->where('o.paid_at <=', $to);
        if ( ! empty($filters['category_id'])) $this->db->where('p.category_id', (int) $filters['category_id']);
        if (isset($filters['q']) && $filters['q'] !== '')
        {
            $this->db->group_start()->like('p.product_name', $filters['q'])->or_like('p.sku', $filters['q'])->group_end();
        }
        $order = isset(self::PRODUCT_SORTS[$sort]) ? self::PRODUCT_SORTS[$sort] : 'revenue';
        $this->db->group_by(array('p.id', 'p.sku', 'p.product_name', 'c.name'))
            ->order_by($order, $dir === 'asc' ? 'ASC' : 'DESC', FALSE)
            ->order_by('p.product_name', 'ASC');

        $rows = $this->db->get()->result_array();
        foreach ($rows as &$r)
        {
            $r['qty'] = (int) $r['qty'];
            $r['qty_no_cost'] = (int) $r['qty_no_cost'];
            $r['revenue'] = (float) $r['revenue'];
            $r['profit'] = $r['profit'] === NULL ? NULL : (float) $r['profit'];
            $r['margin'] = $r['profit'] !== NULL && (float) $r['revenue_costed'] > 0 ? round($r['profit'] / (float) $r['revenue_costed'] * 100) : NULL;
        }
        return $rows;
    }

    /** Cột được phép sắp xếp ở Báo cáo danh mục. */
    const CATEGORY_SORTS = array(
        'name' => 'category_name', 'products' => 'products', 'qty' => 'qty', 'revenue' => 'revenue', 'profit' => 'profit',
    );

    /**
     * Báo cáo danh mục: như Báo cáo hàng hóa nhưng gộp theo danh mục món (món không còn danh mục: "Chưa phân loại").
     * Thêm: products = số món khác nhau đã bán trong danh mục; share = % doanh thu của cả kỳ (tính ở đây).
     */
    public function categories($from, $to, $sort = 'revenue', $dir = 'desc')
    {
        $disc = 'CASE WHEN o.subtotal > 0 THEN o.discount_amount * i.amount / o.subtotal ELSE 0 END';
        $this->db->select("c.id AS category_id, COALESCE(c.name, 'Chưa phân loại') AS category_name,
                COUNT(DISTINCT i.product_id) AS products,
                SUM(i.qty) AS qty,
                SUM(i.amount - $disc) AS revenue,
                SUM(CASE WHEN i.cost_price IS NOT NULL THEN i.amount - $disc END) AS revenue_costed,
                SUM(CASE WHEN i.cost_price IS NOT NULL THEN i.amount - $disc - i.cost_price * i.qty END) AS profit,
                SUM(CASE WHEN i.cost_price IS NULL THEN i.qty ELSE 0 END) AS qty_no_cost", FALSE)
            ->from('order_items i')
            ->join('order_sessions o', "o.id = i.order_session_id AND o.status = 'PAID'", 'inner', FALSE)
            ->join('products p', 'p.id = i.product_id')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->where('i.status', 'ACTIVE')
            ->where('o.paid_at >=', $from)->where('o.paid_at <=', $to);
        $order = isset(self::CATEGORY_SORTS[$sort]) ? self::CATEGORY_SORTS[$sort] : 'revenue';
        $this->db->group_by(array('c.id', 'c.name'))
            ->order_by($order, $dir === 'asc' ? 'ASC' : 'DESC', FALSE)
            ->order_by('category_name', 'ASC', FALSE);

        $rows = $this->db->get()->result_array();
        $total = array_sum(array_map(function ($r) { return (float) $r['revenue']; }, $rows));
        foreach ($rows as &$r)
        {
            $r['products'] = (int) $r['products'];
            $r['qty'] = (int) $r['qty'];
            $r['qty_no_cost'] = (int) $r['qty_no_cost'];
            $r['revenue'] = (float) $r['revenue'];
            $r['profit'] = $r['profit'] === NULL ? NULL : (float) $r['profit'];
            $r['margin'] = $r['profit'] !== NULL && (float) $r['revenue_costed'] > 0 ? round($r['profit'] / (float) $r['revenue_costed'] * 100) : NULL;
            $r['share'] = $total > 0 ? round($r['revenue'] / $total * 100, 1) : 0;
        }
        return $rows;
    }

    /** Dòng tổng của Báo cáo hàng hóa / danh mục. */
    public static function product_totals(array $rows)
    {
        $t = array('qty' => 0, 'revenue' => 0.0, 'profit' => NULL, 'revenue_costed' => 0.0, 'qty_no_cost' => 0);
        foreach ($rows as $r)
        {
            $t['qty'] += $r['qty'];
            $t['revenue'] += $r['revenue'];
            $t['qty_no_cost'] += $r['qty_no_cost'];
            if ($r['profit'] !== NULL)
            {
                $t['profit'] = (float) $t['profit'] + $r['profit'];
                $t['revenue_costed'] += (float) $r['revenue_costed'];
            }
        }
        $t['margin'] = $t['profit'] !== NULL && $t['revenue_costed'] > 0 ? round($t['profit'] / $t['revenue_costed'] * 100) : NULL;
        return $t;
    }
}
