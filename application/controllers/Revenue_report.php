<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Báo cáo doanh thu — nhập tay theo tháng cho từng mảng (Monthly_revenue_model::CATEGORIES; bán hàng qua POS
 * ngoài, không tự tính từ orders trong app này). Nằm trong menu "Báo cáo" (me/reports/revenue); quyền xem + nhập là
 * mục menu "reports.revenue" (migration 017) — gán ở Quản trị → Gán quyền menu, ADMIN luôn được.
 * Khôi phục từ bản đã xoá ở commit f822a82 (migration 071 cũ), cùng logic với coffeeshop.
 */
class Revenue_report extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('payroll'); // payroll_period_label() — định dạng "Tháng MM/YYYY" dùng chung
        $this->load->model(array('Monthly_revenue_model', 'Report_model'));
    }

    /** Tổng doanh thu theo tháng (xu hướng 12 tháng) + tỷ lệ theo danh mục cho 1 tháng được chọn. */
    public function index()
    {
        $period = $this->_period($this->input->get('period'));

        $periods = array();
        for ($i = 11; $i >= 0; $i--)
        {
            $periods[] = date('Y-m', strtotime($period.'-01 -'.$i.' months'));
        }
        $trend = $this->Monthly_revenue_model->get_totals_for_periods($periods);

        $by_category = $this->Monthly_revenue_model->get_revenue_by_category($period);
        $total = array_sum($by_category);

        // So với tháng trước — tháng trước luôn nằm trong $trend (12 tháng vừa lấy ở trên), không cần query thêm.
        $prev_period = date('Y-m', strtotime($period.'-01 -1 month'));
        $prev_by_category = isset($trend[$prev_period]) ? $trend[$prev_period]['by_category'] : array_fill_keys(Monthly_revenue_model::CATEGORIES, 0);

        $breakdown = array();
        foreach ($by_category as $category => $revenue)
        {
            $prev_revenue = isset($prev_by_category[$category]) ? (float) $prev_by_category[$category] : 0;
            $breakdown[] = array(
                'category'      => $category,
                'revenue'       => $revenue,
                'percent'       => $total > 0 ? ($revenue / $total) * 100 : 0,
                'change_label'  => revenue_change_label($revenue, $prev_revenue),
            );
        }

        $data = array(
            'page_title'          => 'Báo cáo doanh thu',
            'reports'             => Report_model::allowed($this->current_user), // nút quay lại danh sách Báo cáo
            'current_user'        => $this->current_user,
            'period'              => $period,
            'periods'             => $periods,
            'trend'               => $trend,
            'breakdown'           => $breakdown,
            'total'               => $total,
            'total_change_label'  => revenue_change_label($total, array_sum($prev_by_category)),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('revenue_report/index', $data);
        $this->load->view('layout/footer');
    }

    /** Nhập/sửa doanh thu các mảng cho 1 tháng. */
    public function entry()
    {
        $period = $this->_period($this->input->get('period'));
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $period = $this->_period($this->input->post('period') ?: $period);
            // Số tiền: cho phép "12.500.000" (bỏ dấu chấm / phẩy / khoảng trắng); để trống = 0.
            $values = array();
            foreach (Monthly_revenue_model::CATEGORIES as $category)
            {
                $raw = str_replace(array('.', ',', ' '), '', trim((string) $this->input->post('revenue_'.$category)));
                if ($raw !== '' && ! ctype_digit($raw))
                {
                    $error = 'Doanh thu '.revenue_category_label($category).' chỉ gồm chữ số.';
                    break;
                }
                $values[$category] = array((float) $raw, mb_substr(trim((string) $this->input->post('note_'.$category, TRUE)), 0, 255, 'UTF-8'));
            }

            if ($error === NULL)
            {
                $old = $this->Monthly_revenue_model->get_revenue_by_category($period);
                foreach ($values as $category => $v)
                {
                    $this->Monthly_revenue_model->upsert($period, $category, $v[0], $v[1] !== '' ? $v[1] : NULL, $this->current_user['id']);
                }
                $this->audit('monthly_revenue', 'UPDATE', array('period' => $period) + $old,
                    array('period' => $period) + array_map(function ($v) { return $v[0]; }, $values));
                $this->session->set_flashdata('success', 'Đã lưu doanh thu '.payroll_period_label($period).'.');
                redirect(site_url('me/reports/revenue').'?period='.$period);
                return;
            }
        }

        $data = array(
            'page_title'   => 'Nhập doanh thu',
            'current_user' => $this->current_user,
            'period'       => $period,
            'existing'     => $this->Monthly_revenue_model->get_by_period($period),
            'error'        => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('revenue_report/entry', $data);
        $this->load->view('layout/footer');
    }

    /** Tháng dạng YYYY-MM hợp lệ (2000 → năm sau), sai / trống thì lấy tháng này. */
    private function _period($value)
    {
        $value = (string) $value;
        $t = DateTime::createFromFormat('!Y-m', $value);
        return $t && $t->format('Y-m') === $value && $value >= '2000-01' && $value <= date('Y-m', strtotime('+1 year')) ? $value : date('Y-m');
    }
}
