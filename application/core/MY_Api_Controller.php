<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for internal JSON REST endpoints under /api/kitchen, /api/tables,
 * which require an authenticated staff session. Public ordering APIs
 * (Api_Order, Api_Payment) intentionally do NOT extend this — they are
 * authorized by the QR token + open table_session instead of a login session.
 */
class MY_Api_Controller extends CI_Controller
{
    protected $current_user;

    public function __construct()
    {
        parent::__construct();

        $this->current_user = $this->session->userdata('user');

        if ( ! $this->current_user)
        {
            json_response(array('success' => FALSE, 'message' => 'Unauthenticated'), 401);
            exit;
        }
    }

    protected function require_role(array $roles)
    {
        if ( ! in_array($this->current_user['role'], $roles, TRUE))
        {
            json_response(array('success' => FALSE, 'message' => 'Forbidden'), 403);
            exit;
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
}
