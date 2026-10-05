<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for authenticated, browser-rendered staff/back-office screens.
 * Handles session auth + simple role-based access control (RBAC).
 */
class MY_Controller extends CI_Controller
{
    protected $current_user;

    /** @var string[] Roles allowed to access the controller. Empty = any logged-in role. */
    protected $allowed_roles = array();

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('kds');

        // Trang nội bộ chỉ phục vụ dưới /me/ (xem config/routes.php). URL cũ không
        // tiền tố (vd. /dashboard do CI default routing) -> GET thì 301 sang /me/...,
        // còn POST/khác thì 404 để không xử lý form 2 nơi.
        if ( ! is_cli() && $this->uri->segment(1) !== 'me')
        {
            if ($this->input->method() !== 'get')
            {
                show_404();
            }
            $qs = $this->input->server('QUERY_STRING');
            redirect(site_url('me/'.$this->uri->uri_string()).($qs ? '?'.$qs : ''), 'location', 301);
        }

        $this->current_user = $this->session->userdata('user');

        if ( ! $this->current_user)
        {
            // Phiên CI session (2 giờ) đã hết hạn — thử tự đăng nhập lại bằng
            // cookie "ghi nhớ đăng nhập" (1 năm) trước khi bắt về trang login.
            $this->current_user = attempt_remember_login();
        }

        if ( ! $this->current_user)
        {
            redirect('login');
            return;
        }

        // ADMIN luôn có toàn quyền — không bị RBAC động hay $allowed_roles
        // tĩnh chi phối, tránh tự khóa mình nếu lỡ cấu hình menu sai.
        if ($this->current_user['role'] === 'ADMIN')
        {
            return;
        }

        $this->load->helper('menu_permission');
        $menu_item = menu_permission_resolve($this->router->class, $this->router->method);

        if ($menu_item !== NULL)
        {
            // (controller, method) này đã được đưa vào danh mục menu gán
            // quyền động -> RBAC động là thẩm quyền DUY NHẤT cho request
            // này, không còn xét $allowed_roles tĩnh nữa.
            if ( ! menu_permission_user_can($this->current_user, $menu_item['id']))
            {
                $this->output->set_status_header(403);
                echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
                exit;
            }
            return;
        }

        // Chưa được đưa vào danh mục menu -> giữ nguyên hành vi cũ.
        if ( ! empty($this->allowed_roles) && ! in_array($this->current_user['role'], $this->allowed_roles, TRUE))
        {
            $this->output->set_status_header(403);
            // load->view() buffers into CI's output object, which only flushes at the
            // natural end of the request — exit; right after would discard it and send
            // an empty body. Render with $return = TRUE and echo it directly instead.
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            exit;
        }
    }

    protected function audit($module, $action, $old_data = NULL, $new_data = NULL)
    {
        $this->load->model('Audit_log_model');
        $this->Audit_log_model->log($module, $action, $old_data, $new_data, $this->current_user['id']);
    }

    /**
     * Dữ liệu cho thanh tab POS "Bàn | Thực đơn" (views/orders/_pos_tabs.php):
     * đơn đang chọn (ghi nhớ trong session, bỏ nếu đơn đã đóng) + mọi đơn đang phục vụ
     * để chuyển nhanh giữa các khách.
     */
    protected function pos_tabs_data($active_tab)
    {
        $this->load->model('Order_model');
        $orders = $this->Order_model->get_active_orders();

        $pos_order_id = (int) $this->session->userdata('pos_order_id');
        if ($pos_order_id && ! in_array($pos_order_id, array_map('intval', array_column($orders, 'id')), TRUE))
        {
            $this->session->unset_userdata('pos_order_id');
            $pos_order_id = 0;
        }

        return array(
            'pos_tab'      => $active_tab,
            'pos_order_id' => $pos_order_id,
            'pos_orders'   => $orders,
        );
    }
}
