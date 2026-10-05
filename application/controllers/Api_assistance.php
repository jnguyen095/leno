<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'core/MY_Api_Controller.php';

class Api_assistance extends MY_Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_role(array('STAFF', 'CASHIER', 'ADMIN'));
        $this->load->model('Assistance_call_model');
    }

    public function pending()
    {
        json_response(array('success' => TRUE, 'calls' => $this->Assistance_call_model->get_pending()));
    }

    public function resolve($id)
    {
        $this->Assistance_call_model->resolve($id, $this->current_user['id']);

        $this->load->model('Audit_log_model');
        $this->Audit_log_model->log('assistance_call', 'RESOLVE', NULL, array('call_id' => $id), $this->current_user['id']);

        json_response(array('success' => TRUE));
    }
}
