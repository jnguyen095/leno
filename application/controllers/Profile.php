<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('User_model', 'User_remember_model'));
    }

    public function change_password()
    {
        $error = NULL;
        $success = FALSE;

        if ($this->input->method() === 'post')
        {
            $current_password = $this->input->post('current_password');
            $new_password = $this->input->post('new_password');
            $confirm_password = $this->input->post('confirm_password');

            $user = $this->User_model->get_by_id($this->current_user['id']);

            if ( ! password_verify((string) $current_password, $user['password']))
            {
                $error = 'Mật khẩu hiện tại không đúng.';
            }
            elseif (strlen($new_password) < 6)
            {
                $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            }
            elseif ($new_password !== $confirm_password)
            {
                $error = 'Xác nhận mật khẩu mới không khớp.';
            }
            else
            {
                $this->User_model->update($this->current_user['id'], array('password' => $new_password));

                // Đổi mật khẩu xong thì huỷ mọi phiên "ghi nhớ đăng nhập" cũ — phòng trường hợp
                // mật khẩu bị lộ, cookie ghi nhớ cũ không còn dùng để đăng nhập lại được nữa.
                $this->User_remember_model->delete_for_user($this->current_user['id']);
                clear_remember_cookie();

                $this->audit('user', 'CHANGE_PASSWORD', NULL, array('id' => $this->current_user['id']));
                $success = TRUE;
            }
        }

        $data = array(
            'page_title'   => 'Đổi mật khẩu',
            'current_user' => $this->current_user,
            'error'        => $error,
            'success'      => $success,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('profile/change_password', $data);
        $this->load->view('layout/footer');
    }
}
