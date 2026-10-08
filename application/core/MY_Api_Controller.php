<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for JSON REST endpoints under /api/*. Accepts either an
 * authenticated staff browser session (/api/tables, /api/payment) or a mobile
 * app bearer token ("Authorization: Bearer <token>", /api/v1/*, see Api_token_model).
 */
class MY_Api_Controller extends CI_Controller
{
    protected $current_user;

    /** Bearer token của request hiện tại (NULL nếu đăng nhập bằng session trình duyệt). */
    protected $api_token;

    public function __construct()
    {
        parent::__construct();

        $this->current_user = $this->session->userdata('user');

        $token = $this->bearer_token();
        if ($token !== NULL)
        {
            $this->current_user = $this->_user_from_token($token);
            $this->api_token = $token;
        }
        elseif ( ! $this->current_user)
        {
            // Trình duyệt (poll sơ đồ bàn...): phiên hết hạn thì thử cookie "ghi nhớ đăng nhập"
            // như các trang thường (MY_Controller), thay vì trả 401.
            $this->current_user = attempt_remember_login();
        }

        if ( ! $this->current_user)
        {
            $this->fail(401, 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.');
        }
    }

    protected function require_role(array $roles)
    {
        if ( ! in_array($this->current_user['role'], $roles, TRUE))
        {
            $this->fail(403, 'Forbidden');
        }
    }

    /** Quyền theo "Gán quyền menu", giống màn hình web (xem api_user_can_menu()). */
    protected function require_menu($menu_key, array $fallback_roles)
    {
        $this->load->helper('api');
        if ( ! api_user_can_menu($this->current_user, $menu_key, $fallback_roles))
        {
            $this->fail(403, 'Bạn không có quyền dùng chức năng này.');
        }
    }

    protected function input_json(): array
    {
        $raw = $this->input->raw_input_stream;
        if (empty($raw))
        {
            return $this->input->post() ?: array();
        }
        $decoded = json_decode($raw, TRUE);
        return is_array($decoded) ? $decoded : array();
    }

    /** Trả JSON lỗi và dừng ngay (kể cả khi đang ở constructor). */
    protected function fail($status, $message, array $extra = array())
    {
        json_response(array_merge(array('success' => FALSE, 'message' => $message), $extra), $status);
        // json_response() chỉ ghi vào bộ đệm output của CI — phải tự flush trước khi exit.
        $this->output->_display();
        exit;
    }

    /** "Authorization: Bearer <token>" — Apache/PHP-CGI có thể đặt header ở vài chỗ khác nhau. */
    protected function bearer_token()
    {
        $header = $this->input->server('HTTP_AUTHORIZATION') ?: $this->input->server('REDIRECT_HTTP_AUTHORIZATION');
        if ( ! $header && function_exists('apache_request_headers'))
        {
            foreach (apache_request_headers() as $name => $value)
            {
                if (strtolower($name) === 'authorization') $header = $value;
            }
        }
        if ($header && preg_match('/^Bearer\s+(\S+)$/i', trim($header), $m))
        {
            return $m[1];
        }
        return NULL;
    }

    private function _user_from_token($token)
    {
        $this->load->model(array('Api_token_model', 'User_model'));
        $row = $this->Api_token_model->verify($token);
        if ( ! $row)
        {
            return NULL;
        }

        $user = $this->User_model->get_by_id($row['user_id']);
        if ( ! $user || $user['status'] !== 'ACTIVE')
        {
            return NULL;
        }
        unset($user['password']);
        return $user;
    }
}
