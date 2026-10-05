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
            redirect($this->_home_for_role($remembered_user['role']));
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
                        $cookie_value = $this->User_remember_model->create($user['id']);
                        set_remember_cookie($cookie_value, User_remember_model::TTL_DAYS * 86400);
                    }

                    $this->load->model('Audit_log_model');
                    $this->Audit_log_model->log('auth', 'LOGIN', NULL, array('username' => $user['username']), $user['id']);

                    redirect($this->_home_for_role($user['role']));
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

            $this->load->model('User_remember_model');
            $this->User_remember_model->delete_for_user($user['id']);
        }
        $this->session->unset_userdata('user');
        $this->session->sess_destroy();
        clear_remember_cookie();
        redirect('login');
    }

    private function _home_for_role($role)
    {
        switch ($role)
        {
            case 'BARISTA': return 'me/kitchen';
            case 'CASHIER': return 'me/cashier';
            case 'STOCKTAKER': return 'me/stock/adjust';
            default: return 'me/dashboard';
        }
    }
}
