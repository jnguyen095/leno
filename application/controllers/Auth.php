<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('kds');
        $this->load->model('User_model');
    }

    public function index()
    {
        redirect('login');
    }

    public function login()
    {
        if ($this->session->userdata('user'))
        {
            redirect('me/dashboard');
        }

        // Chưa có session (hết hạn 2 giờ) nhưng còn cookie "ghi nhớ đăng nhập" (1 năm) hợp lệ.
        $remembered_user = attempt_remember_login();
        if ($remembered_user)
        {
            redirect($this->_home_for_user($remembered_user));
            return;
        }

        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $this->form_validation->set_rules('username', 'Tên đăng nhập', 'required|trim');
            $this->form_validation->set_rules('password', 'Mật khẩu', 'required');

            if ($this->form_validation->run())
            {
                $user = $this->User_model->verify_login($this->input->post('username', TRUE), $this->input->post('password'));

                if ($user)
                {
                    unset($user['password']);
                    $this->session->set_userdata('user', $user);

                    if ($this->input->post('remember'))
                    {
                        $this->load->model('User_remember_model');
                        // Thiết bị này đăng nhập lại -> bỏ token cũ của chính nó, tránh dồn rác.
                        $this->User_remember_model->delete_by_cookie($this->input->cookie(REMEMBER_COOKIE_NAME));
                        $cookie_value = $this->User_remember_model->create($user['id']);
                        set_remember_cookie($cookie_value, User_remember_model::TTL_DAYS * 86400);
                    }

                    $this->load->model('Audit_log_model');
                    $this->Audit_log_model->log('auth', 'LOGIN', NULL, array('username' => $user['username']), $user['id']);

                    redirect($this->_home_for_user($user));
                }
                $error = 'Sai tên đăng nhập hoặc mật khẩu.';
            }
            else
            {
                $error = validation_errors();
            }
        }

        $this->load->view('auth/login', array('error' => $error));
    }

    public function logout()
    {
        $user = $this->session->userdata('user');
        if ($user)
        {
            $this->load->model('Audit_log_model');
            $this->Audit_log_model->log('auth', 'LOGOUT', NULL, NULL, $user['id']);

            // Chỉ huỷ ghi nhớ của THIẾT BỊ này; máy POS / điện thoại khác vẫn giữ đăng nhập.
            $this->load->model('User_remember_model');
            $this->User_remember_model->delete_by_cookie($this->input->cookie(REMEMBER_COOKIE_NAME));
        }
        $this->session->unset_userdata('user');
        $this->session->sess_destroy();
        clear_remember_cookie();
        redirect('login');
    }

    /**
     * Trang đầu tiên sau đăng nhập theo vai trò. Quyền menu gán động (Gán quyền menu) có thể
     * không cho vai trò đó vào trang mặc định -> lùi về trang kế tiếp được phép, tránh 403 ngay
     * sau khi đăng nhập.
     */
    private function _home_for_user($user)
    {
        // [route, menu_key] theo thứ tự ưu tiên.
        $candidates = array(
            'BARISTA'    => array(array('me/pha-che', 'pha_che')),
            'CASHIER'    => array(array('me/tables', 'tables')),
            'STOCKTAKER' => array(array('me/stock/adjust', 'inventory.stock_adjust')),
        );
        $list = isset($candidates[$user['role']]) ? $candidates[$user['role']] : array();
        $list[] = array('me/dashboard', 'dashboard');

        $this->load->helper('menu_permission');
        foreach ($list as $c)
        {
            if (menu_permission_user_can_key($user, $c[1]))
            {
                return $c[0];
            }
        }
        return 'me/dashboard';
    }
}
