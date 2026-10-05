<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'core/MY_Api_Controller.php';

class Api_tables extends MY_Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_role(array('STAFF', 'ADMIN', 'CASHIER'));
        $this->load->model(array('Table_model', 'Table_session_model', 'Order_model'));
    }

    public function status()
    {
        $tables = $this->Table_model->get_all(TRUE);

        foreach ($tables as &$t)
        {
            $t['total_amount'] = NULL;
            if ($t['status'] !== 'AVAILABLE')
            {
                $session = $this->Table_session_model->get_open_by_table($t['id']);
                if ($session)
                {
                    $order = $this->Order_model->get_active_by_table_session($session['id']);
                    if ($order) $t['total_amount'] = (float) $order['total_amount'];
                }
            }
        }

        json_response(array('success' => TRUE, 'tables' => $tables));
    }
}
