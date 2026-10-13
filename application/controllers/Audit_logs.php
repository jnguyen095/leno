<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Nhật ký hệ thống (audit_logs) — xem/lọc mọi hành động đã ghi lại, kể cả đăng nhập/xuất. Chỉ ADMIN. */
class Audit_logs extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    const PER_PAGE = 30;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Audit_log_model', 'User_model'));
    }

    public function index()
    {
        $module = $this->input->get('module');
        $action = $this->input->get('action');
        $created_by = $this->input->get('created_by');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');

        $filters = array();
        if ($module) $filters['module'] = $module;
        if ($action) $filters['action'] = $action;
        if ($created_by) $filters['user_id'] = $created_by;
        if ($date_from) $filters['date_from'] = $date_from;
        if ($date_to) $filters['date_to'] = $date_to;

        $total = $this->Audit_log_model->count_filtered($filters);
        $total_pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($total_pages, (int) $this->input->get('page')));
        $offset = ($page - 1) * self::PER_PAGE;

        $data = array(
            'page_title'   => 'Nhật ký hệ thống',
            'current_user' => $this->current_user,
            'logs'         => $this->Audit_log_model->get_filtered($filters, self::PER_PAGE, $offset),
            'modules'      => $this->Audit_log_model->get_distinct_modules(),
            'actions'      => $this->Audit_log_model->get_distinct_actions(),
            'users'        => $this->User_model->get_all(),
            'module'       => $module,
            'action'       => $action,
            'created_by'   => $created_by,
            'date_from'    => $date_from,
            'date_to'      => $date_to,
            'page'         => $page,
            'total_pages'  => $total_pages,
            'total'        => $total,
            'per_page'     => self::PER_PAGE,
            // Dọn dẹp: mỗi mốc -> ngày cắt + số bản ghi sẽ xoá.
            'purge_options' => $this->_purge_options(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('audit_logs/index', $data);
        $this->load->view('layout/footer');
    }

    /** Các mốc dọn dẹp cho phép (tháng) — tối thiểu 1 tháng để không lỡ xoá nhật ký gần đây. */
    const PURGE_MONTHS = array(1, 3, 6, 12);

    /**
     * POST — xoá nhật ký cũ hơn N tháng (months ∈ PURGE_MONTHS). Giữ "Thông báo" (báo bếp) của đơn còn mở.
     * Ghi lại một dòng nhật ký về lần dọn này (ai, xoá bao nhiêu, trước ngày nào).
     */
    public function purge()
    {
        if ($this->input->method() !== 'post') show_404();
        // Xem nhật ký có thể được gán quyền cho vai trò khác; xoá thì chỉ ADMIN.
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            exit;
        }
        $months = (int) $this->input->post('months');
        if ( ! in_array($months, self::PURGE_MONTHS, TRUE))
        {
            $this->session->set_flashdata('error', 'Mốc thời gian không hợp lệ.');
            redirect('me/audit-logs');
            return;
        }
        $before = date('Y-m-d 00:00:00', strtotime('-'.$months.' months'));
        $deleted = $this->Audit_log_model->purge_before($before);
        $this->audit('audit_log', 'PURGE', NULL, array('months' => $months, 'before' => $before, 'deleted' => $deleted));
        $this->session->set_flashdata('success', $deleted
            ? 'Đã xoá '.number_format($deleted, 0, ',', '.').' bản ghi trước ngày '.date('d/m/Y', strtotime($before)).'.'
            : 'Không có bản ghi nào trước ngày '.date('d/m/Y', strtotime($before)).'.');
        redirect('me/audit-logs');
    }

    private function _purge_options()
    {
        $out = array();
        foreach (self::PURGE_MONTHS as $m)
        {
            $before = date('Y-m-d 00:00:00', strtotime('-'.$m.' months'));
            $out[] = array('months' => $m, 'before' => $before, 'count' => $this->Audit_log_model->count_before($before));
        }
        return $out;
    }
}
