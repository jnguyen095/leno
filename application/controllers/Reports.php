<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Báo cáo (menu "Báo cáo" trên thanh điều hướng). Mỗi báo cáo là một mục menu trong nhóm "Báo cáo" (migration 015):
 * quản trị gán quyền cho vai trò / từng người ở Quản trị → Gán quyền menu; ADMIN luôn xem được. MY_Controller tự chặn
 * từng báo cáo theo (controller, method) của mục menu; trang danh sách (/me/reports) chỉ hiện báo cáo được phép.
 *
 * Thêm báo cáo mới: thêm vào Report_model::REPORTS + method + view, và thêm mục menu ở một migration mới.
 */
class Reports extends MY_Controller
{
    /** Khoảng thời gian nhanh: mã => nhãn. */
    const RANGES = array(
        'today' => 'Hôm nay', 'yesterday' => 'Hôm qua', '7d' => '7 ngày qua', 'month' => 'Tháng này', 'last_month' => 'Tháng trước',
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('menu_permission');
        $this->load->model('Report_model');
    }

    /** Danh sách báo cáo người dùng được xem. Chỉ có 1 báo cáo thì mở thẳng báo cáo đó. */
    public function index()
    {
        $reports = Report_model::allowed($this->current_user);
        if ( ! $reports)
        {
            $this->output->set_status_header(403);
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            exit;
        }
        if (count($reports) === 1)
        {
            redirect('me/reports/'.reset($reports)[0]);
            return;
        }
        $data = array('page_title' => 'Báo cáo', 'current_user' => $this->current_user, 'reports' => $reports);
        $this->load->view('layout/header', $data);
        $this->load->view('reports/index', $data);
        $this->load->view('layout/footer');
    }

    /** Báo cáo hàng hóa: SKU, tên món, số lượng bán, doanh thu, lãi gộp. */
    public function products()
    {
        list($period, $filters, $sort, $dir) = $this->_product_params();
        $rows = $this->Report_model->products($period['from'], $period['to'], $filters, $sort, $dir);

        $this->load->model('Category_model');
        $data = array(
            'page_title'   => 'Báo cáo hàng hóa',
            'current_user' => $this->current_user,
            'reports'      => Report_model::allowed($this->current_user),
            'period'       => $period,
            'ranges'       => self::RANGES,
            'filters'      => $filters,
            'sort'         => $sort,
            'dir'          => $dir,
            'rows'         => $rows,
            'totals'       => Report_model::product_totals($rows),
            'categories'   => $this->Category_model->get_active(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('reports/products', $data);
        $this->load->view('layout/footer');
    }

    /** Xuất Excel Báo cáo hàng hóa (cùng bộ lọc với trang). */
    public function products_export()
    {
        list($period, $filters, $sort, $dir) = $this->_product_params();
        $rows = $this->Report_model->products($period['from'], $period['to'], $filters, $sort, $dir);

        $this->load->library('xlsx_writer');
        $x = $this->xlsx_writer;
        $x->set_sheet_name('Báo cáo hàng hóa');
        $x->set_columns(array('STT' => 6, 'SKU' => 14, 'Tên món' => 32, 'Danh mục' => 18, 'Số lượng bán' => 13,
            'Doanh thu' => 15, 'Lãi gộp' => 15, 'Biên lãi (%)' => 12, 'Ghi chú' => 34));
        foreach ($rows as $i => $r)
        {
            $x->add_row(array(
                array('n' => $i + 1), $r['sku'], $r['product_name'], $r['category_name'],
                array('n' => $r['qty']), array('n' => round($r['revenue'])),
                $r['profit'] === NULL ? '' : array('n' => round($r['profit'])),
                $r['margin'] === NULL ? '' : array('n' => $r['margin']),
                $r['profit'] === NULL ? 'Chưa có giá vốn' : ($r['qty_no_cost'] ? $r['qty_no_cost'].' món bán lúc chưa có giá vốn — lãi chưa tính phần này' : ''),
            ));
        }
        if ($rows)
        {
            $x->end_data();
            $last = $x->row_count() + 1;
            $sum = function ($col) use ($last) { return array('f' => 'SUBTOTAL(109,'.$col.'2:'.$col.$last.')', 'b' => TRUE); };
            $x->add_row(array(array('t' => 'Tổng cộng ('.count($rows).' món)', 'b' => TRUE), '', '', '', $sum('E'), $sum('F'), $sum('G'), '', ''));
        }

        $this->audit('report', 'EXPORT_PRODUCTS', NULL, array('from' => $period['from'], 'to' => $period['to'], 'filters' => $filters, 'rows' => count($rows)));
        $x->download('bao_cao_hang_hoa_'.date('Ymd', strtotime($period['from'])).'_'.date('Ymd', strtotime($period['to'])).'.xlsx');
    }

    /** Báo cáo danh mục: danh mục, số món đã bán, số lượng bán, doanh thu (+ tỉ trọng), lãi gộp. */
    public function categories()
    {
        $period = $this->_period();
        list($sort, $dir) = $this->_sort(Report_model::CATEGORY_SORTS);
        $rows = $this->Report_model->categories($period['from'], $period['to'], $sort, $dir);

        $data = array(
            'page_title'   => 'Báo cáo danh mục',
            'current_user' => $this->current_user,
            'reports'      => Report_model::allowed($this->current_user),
            'period'       => $period,
            'ranges'       => self::RANGES,
            'sort'         => $sort,
            'dir'          => $dir,
            'rows'         => $rows,
            'totals'       => Report_model::product_totals($rows),
            // Bấm một danh mục -> Báo cáo hàng hóa lọc theo danh mục đó (nếu được xem báo cáo đó).
            'can_products' => menu_permission_user_can_key($this->current_user, 'reports.products'),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('reports/categories', $data);
        $this->load->view('layout/footer');
    }

    /** Xuất Excel Báo cáo danh mục. */
    public function categories_export()
    {
        $period = $this->_period();
        list($sort, $dir) = $this->_sort(Report_model::CATEGORY_SORTS);
        $rows = $this->Report_model->categories($period['from'], $period['to'], $sort, $dir);

        $this->load->library('xlsx_writer');
        $x = $this->xlsx_writer;
        $x->set_sheet_name('Báo cáo danh mục');
        $x->set_columns(array('STT' => 6, 'Danh mục' => 28, 'Số món đã bán' => 14, 'Số lượng bán' => 13, 'Doanh thu' => 15,
            'Tỉ trọng doanh thu (%)' => 20, 'Lãi gộp' => 15, 'Biên lãi (%)' => 12, 'Ghi chú' => 34));
        foreach ($rows as $i => $r)
        {
            $x->add_row(array(
                array('n' => $i + 1), $r['category_name'], array('n' => $r['products']), array('n' => $r['qty']),
                array('n' => round($r['revenue'])), array('n' => $r['share']),
                $r['profit'] === NULL ? '' : array('n' => round($r['profit'])),
                $r['margin'] === NULL ? '' : array('n' => $r['margin']),
                $r['profit'] === NULL ? 'Chưa có giá vốn' : ($r['qty_no_cost'] ? $r['qty_no_cost'].' món bán lúc chưa có giá vốn — lãi chưa tính phần này' : ''),
            ));
        }
        if ($rows)
        {
            $x->end_data();
            $last = $x->row_count() + 1;
            $sum = function ($col) use ($last) { return array('f' => 'SUBTOTAL(109,'.$col.'2:'.$col.$last.')', 'b' => TRUE); };
            $x->add_row(array(array('t' => 'Tổng cộng ('.count($rows).' danh mục)', 'b' => TRUE), '', $sum('C'), $sum('D'), $sum('E'), '', $sum('G'), '', ''));
        }

        $this->audit('report', 'EXPORT_CATEGORIES', NULL, array('from' => $period['from'], 'to' => $period['to'], 'rows' => count($rows)));
        $x->download('bao_cao_danh_muc_'.date('Ymd', strtotime($period['from'])).'_'.date('Ymd', strtotime($period['to'])).'.xlsx');
    }

    // ---- Nội bộ ----

    /** Bộ lọc Báo cáo hàng hóa từ query string: [khoảng thời gian, bộ lọc, cột sắp xếp, chiều]. */
    private function _product_params()
    {
        $period = $this->_period();
        $filters = array(
            'category_id' => (int) $this->input->get('category_id'),
            'q'           => mb_substr(trim((string) $this->input->get('q', TRUE)), 0, 100, 'UTF-8'),
        );
        list($sort, $dir) = $this->_sort(Report_model::PRODUCT_SORTS);
        return array($period, $filters, $sort, $dir);
    }

    /** ?sort=&dir= trong danh sách cột cho phép: [cột (mặc định revenue), asc | desc (mặc định desc)]. */
    private function _sort(array $allowed)
    {
        $sort = (string) $this->input->get('sort');
        if ( ! isset($allowed[$sort])) $sort = 'revenue';
        return array($sort, $this->input->get('dir') === 'asc' ? 'asc' : 'desc');
    }

    /**
     * Khoảng thời gian: ?range=today|yesterday|7d|month|last_month, hoặc ?from=Y-m-d&to=Y-m-d (tự chọn).
     * Mặc định: tháng này. Trả về [range, from (Y-m-d 00:00:00), to (Y-m-d 23:59:59), from_date, to_date, label].
     */
    private function _period()
    {
        $valid = function ($d) { $t = DateTime::createFromFormat('Y-m-d', (string) $d); return $t && $t->format('Y-m-d') === $d ? $d : NULL; };
        $from = $valid($this->input->get('from'));
        $to = $valid($this->input->get('to'));
        $range = (string) $this->input->get('range');

        if ($from || $to)
        {
            $range = 'custom';
            $from = $from ?: $to;
            $to = $to ?: $from;
            if ($from > $to) list($from, $to) = array($to, $from);
        }
        else
        {
            if ( ! isset(self::RANGES[$range])) $range = 'month';
            $today = date('Y-m-d');
            switch ($range)
            {
                case 'today':      $from = $to = $today; break;
                case 'yesterday':  $from = $to = date('Y-m-d', strtotime('-1 day')); break;
                case '7d':         $from = date('Y-m-d', strtotime('-6 days')); $to = $today; break;
                case 'last_month': $from = date('Y-m-01', strtotime('first day of last month')); $to = date('Y-m-t', strtotime('last day of last month')); break;
                default:           $from = date('Y-m-01'); $to = $today;
            }
        }
        $fmt = function ($d) { return date('d/m/Y', strtotime($d)); };
        return array(
            'range' => $range, 'from' => $from.' 00:00:00', 'to' => $to.' 23:59:59', 'from_date' => $from, 'to_date' => $to,
            'label' => $from === $to ? $fmt($from) : $fmt($from).' – '.$fmt($to),
        );
    }
}
