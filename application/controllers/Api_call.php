<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public, unauthenticated "call staff" API used by the customer QR menu.
 * Authorization mirrors Api_order: qr_token + session_secret, so a stale
 * bookmarked link can't ring the bell for whoever is now sitting at the table.
 */
class Api_call extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('kds');
        $this->load->model(array('Table_model', 'Table_session_model', 'Assistance_call_model'));
    }

    public function create()
    {
        $body = $this->input_json();
        $token = isset($body['token']) ? $body['token'] : NULL;
        $secret = isset($body['secret']) ? $body['secret'] : NULL;
        $type = isset($body['type']) ? $body['type'] : NULL;

        if ( ! in_array($type, array('HELP', 'PAYMENT'), TRUE))
        {
            json_response(array('success' => FALSE, 'message' => 'Yêu cầu không hợp lệ'), 400);
            return;
        }

        $table = $token ? $this->Table_model->get_by_token($token) : NULL;
        if ( ! $table)
        {
            json_response(array('success' => FALSE, 'message' => 'Bàn không hợp lệ'), 404);
            return;
        }

        $check = $this->Table_session_model->validate_session($table['id'], $secret);
        if ( ! $check['valid'])
        {
            json_response(array('success' => FALSE, 'expired' => TRUE, 'message' => 'Phiên đặt món đã kết thúc, vui lòng quét lại mã QR trên bàn.'), 409);
            return;
        }

        $call_id = $this->Assistance_call_model->create($table['id'], $type);

        if ( ! $call_id)
        {
            json_response(array('success' => TRUE, 'message' => 'Yêu cầu của bạn đang được xử lý, vui lòng đợi nhân viên.'));
            return;
        }

        $this->load->model('Audit_log_model');
        $this->Audit_log_model->log('assistance_call', 'CREATE', NULL, array('table_id' => $table['id'], 'type' => $type));

        json_response(array('success' => TRUE, 'message' => 'Đã gửi yêu cầu, nhân viên sẽ đến ngay.'));
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
}
