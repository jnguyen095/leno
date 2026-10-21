<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tổng quan: bộ lọc thời gian (Hôm nay / Hôm qua / 7 ngày / Tháng này / Tất cả) — doanh thu (so với kỳ trước),
 * biểu đồ doanh thu theo giờ / ngày / tháng, món bán chạy, doanh thu theo danh mục, hình thức thanh toán,
 * bảng giá & khuyến mãi đang áp; cột phải: hoạt động gần đây (từ Nhật ký hệ thống, tự làm mới).
 */
class Dashboard extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'CASHIER', 'ADMIN');

    const RANGES = array('today' => 'Hôm nay', 'yesterday' => 'Hôm qua', '7d' => '7 ngày qua', 'month' => 'Tháng này', 'all' => 'Tất cả');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Dashboard_model');
    }

    public function index()
    {
        $range = (string) $this->input->get('range');
        if ( ! isset(self::RANGES[$range])) $range = 'today';
        list($from, $to, $bucket, $prev_from, $prev_to, $prev_label) = $this->_range($range);

        $kpis = $this->Dashboard_model->kpis($from, $to);
        $prev = $prev_from ? $this->Dashboard_model->kpis($prev_from, $prev_to) : NULL;

        $this->load->library('pos_service');
        $this->load->model(array('Price_book_model', 'Discount_model'));

        $data = array(
            'page_title'    => 'Tổng quan',
            'current_user'  => $this->current_user,
            'range'         => $range,
            'ranges'        => self::RANGES,
            'from'          => $from,
            'to'            => $to,
            'bucket'        => $bucket,
            'kpis'          => $kpis,
            'prev'          => $prev,
            'prev_label'    => $prev_label,
            'by_method'     => $this->Dashboard_model->by_method($from, $to),
            'open_now'      => $this->Dashboard_model->open_now(),
            'series'        => $this->Dashboard_model->revenue_series($from ?: '2000-01-01 00:00:00', $to, $bucket),
            'top_products'  => $this->Dashboard_model->top_products($from, $to, 8),
            'by_category'   => $this->Dashboard_model->by_category($from, $to),
            'offer_usage'   => $this->Dashboard_model->offer_usage($from, $to),
            'shop_offers'   => $this->pos_service->shop_offers(),
            'price_books'   => $this->Price_book_model->get_applicable(),
            'discounts'     => $this->Discount_model->get_applicable(),
            // Lãi gộp (giá vốn là thông tin nội bộ) — chỉ ADMIN thấy.
            'profit'        => $this->current_user['role'] === 'ADMIN' ? $this->Dashboard_model->profit($from, $to) : NULL,
            'show_activity' => $this->_can_see_activity(),
            'activity'      => $this->_can_see_activity() ? $this->Dashboard_model->activity(25) : array(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('layout/footer');
    }

    /** GET (AJAX) ?after=<id> — hoạt động mới hơn id đó (cột phải tự làm mới). */
    public function activity()
    {
        if ( ! $this->_can_see_activity())
        {
            json_response(array('success' => FALSE), 403);
            return;
        }
        json_response(array('success' => TRUE, 'items' => $this->Dashboard_model->activity(25, (int) $this->input->get('after'))));
    }

    /** Hoạt động lấy từ Nhật ký hệ thống -> chỉ ai xem được nhật ký (ADMIN hoặc được gán quyền). */
    private function _can_see_activity()
    {
        $this->load->helper('menu_permission');
        return menu_permission_user_can_key($this->current_user, 'admin.audit_logs');
    }

    /**
     * array(from, to, bucket, prev_from, prev_to, prev_label). Kỳ so sánh có cùng độ dài, tính tới cùng thời điểm
     * (vd Hôm nay tới 10:30 so với Hôm qua tới 10:30) để so công bằng.
     */
    private function _range($range)
    {
        $now = time();
        $day = function ($t) { return date('Y-m-d 00:00:00', $t); };
        $end = function ($t) { return date('Y-m-d 23:59:59', $t); };
        switch ($range)
        {
            case 'yesterday':
                $y = strtotime('-1 day', $now);
                return array($day($y), $end($y), 'hour', $day(strtotime('-2 days', $now)), $end(strtotime('-2 days', $now)), 'hôm kia');
            case '7d':
                return array($day(strtotime('-6 days', $now)), $end($now), 'day',
                    $day(strtotime('-13 days', $now)), date('Y-m-d H:i:s', strtotime('-7 days', $now)), '7 ngày trước đó');
            case 'month':
                $prev_start = strtotime('first day of last month', $now);
                $prev_end = min(strtotime(date('Y-m-d H:i:s', strtotime('-1 month', $now))), strtotime(date('Y-m-t 23:59:59', $prev_start)));
                return array(date('Y-m-01 00:00:00', $now), $end($now), 'day', date('Y-m-01 00:00:00', $prev_start), date('Y-m-d H:i:s', $prev_end), 'cùng kỳ tháng trước');
            case 'all':
                return array(NULL, NULL, 'month', NULL, NULL, NULL);
            default: // today
                return array($day($now), $end($now), 'hour', $day(strtotime('-1 day', $now)), date('Y-m-d H:i:s', strtotime('-1 day', $now)), 'cùng giờ hôm qua');
        }
    }
}
