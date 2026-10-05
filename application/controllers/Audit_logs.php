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
        );
        $this->load->view('layout/header', $data);
        $this->load->view('audit_logs/index', $data);
        $this->load->view('layout/footer');
    }
}
