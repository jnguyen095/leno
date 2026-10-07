<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * POST /api/v1/auth/login — đăng nhập cho ứng dụng di động/tablet, trả về bearer token.
 * Body JSON: {"username": "...", "password": "...", "device_name": "iPad quầy"}.
 * Đăng xuất và các chức năng khác nằm ở Api_pos (cần token).
 */
class Api_auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('kds', 'api'));
        $this->load->model(array('User_model', 'Api_token_model', 'Audit_log_model'));
    }

    public function login()
    {
        if ($this->input->method() !== 'post')
        {
            json_response(array('success' => FALSE, 'message' => 'Method not allowed'), 405);
            return;
        }

        $body = json_decode($this->input->raw_input_stream, TRUE);
        if ( ! is_array($body)) $body = $this->input->post() ?: array();

        $username = isset($body['username']) ? trim((string) $body['username']) : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';
        $device = isset($body['device_name']) ? trim((string) $body['device_name']) : NULL;

        if ($username === '' || $password === '')
        {
            json_response(array('success' => FALSE, 'message' => 'Vui lòng nhập tên đăng nhập và mật khẩu.'), 422);
            return;
        }

        $user = $this->User_model->verify_login($username, $password);
        if ( ! $user)
        {
            json_response(array('success' => FALSE, 'message' => 'Sai tên đăng nhập hoặc mật khẩu.'), 401);
            return;
        }
        unset($user['password']);

        $permissions = api_pos_permissions($user);
        if ( ! in_array(TRUE, $permissions, TRUE))
        {
            json_response(array('success' => FALSE, 'message' => 'Tài khoản này không có quyền dùng ứng dụng bán hàng.'), 403);
            return;
        }

        $token = $this->Api_token_model->create($user['id'], $device ?: NULL);
        $this->Audit_log_model->log('auth', 'LOGIN', NULL,
            array('username' => $user['username'], 'via' => 'app', 'device' => $device), $user['id']);

        json_response(array(
            'success' => TRUE,
            'token'   => $token,
            'user'    => api_user($user, $permissions),
        ));
    }
}
