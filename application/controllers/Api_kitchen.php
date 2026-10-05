<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'core/MY_Api_Controller.php';

class Api_kitchen extends MY_Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_role(array('BARISTA', 'ADMIN'));
        $this->load->model('Kitchen_ticket_model');
    }

    public function tickets()
    {
        json_response(array(
            'success' => TRUE,
            'tickets' => $this->Kitchen_ticket_model->get_dashboard_tickets(array('NEW', 'PREPARING', 'COMPLETED')),
        ));
    }

    public function update_status($id)
    {
        $body = $this->input_json();
        $status = isset($body['status']) ? $body['status'] : NULL;

        if ( ! in_array($status, array('NEW', 'PREPARING', 'COMPLETED'), TRUE))
        {
            json_response(array('success' => FALSE, 'message' => 'Invalid status'), 400);
            return;
        }

        $this->Kitchen_ticket_model->update_ticket_status($id, $status);

        $this->load->model('Audit_log_model');
        $this->Audit_log_model->log('kitchen_ticket', 'STATUS_'.$status, NULL, array('ticket_id' => $id), $this->current_user['id']);

        json_response(array('success' => TRUE));
    }

    public function update_item_status($item_id)
    {
        $body = $this->input_json();
        $status = isset($body['status']) ? $body['status'] : NULL;

        if ( ! in_array($status, array('NEW', 'PREPARING', 'COMPLETED'), TRUE))
        {
            json_response(array('success' => FALSE, 'message' => 'Invalid status'), 400);
            return;
        }

        $ticket_status = $this->Kitchen_ticket_model->update_item_status($item_id, $status);
        json_response(array('success' => TRUE, 'ticket_status' => $ticket_status));
    }
}
