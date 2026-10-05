<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kitchen extends MY_Controller
{
    protected $allowed_roles = array('BARISTA', 'ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Kitchen_ticket_model');
    }

    public function index()
    {
        $data = array(
            'page_title'   => 'Bếp (KDS)',
            'current_user' => $this->current_user,
            'tickets'      => $this->Kitchen_ticket_model->get_dashboard_tickets(array('NEW', 'PREPARING', 'COMPLETED')),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('kitchen/index', $data);
        $this->load->view('layout/footer');
    }

    public function ticket($id)
    {
        $ticket = $this->Kitchen_ticket_model->get_ticket($id);
        if ( ! $ticket)
        {
            show_404();
        }
        $data = array(
            'page_title'   => 'Ticket #'.$id,
            'current_user' => $this->current_user,
            'ticket'       => $ticket,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('kitchen/ticket', $data);
        $this->load->view('layout/footer');
    }

    public function update_status($id)
    {
        $status = $this->input->post('status');
        if (in_array($status, array('NEW', 'PREPARING', 'COMPLETED'), TRUE))
        {
            $this->Kitchen_ticket_model->update_ticket_status($id, $status);
            $this->audit('kitchen_ticket', 'STATUS_'.$status, NULL, array('ticket_id' => $id));
        }
        redirect($this->input->post('redirect_to') ?: 'me/kitchen');
    }
}
