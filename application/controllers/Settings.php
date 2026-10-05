<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Setting_model');
    }

    public function index()
    {
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $vat_percent = $this->input->post('vat_percent');

            if ( ! is_numeric($vat_percent) || $vat_percent < 0 || $vat_percent > 100)
            {
                $error = 'VAT phải là một số từ 0 đến 100.';
            }
            else
            {
                $old = $this->Setting_model->get_vat_percent();
                $this->Setting_model->set('vat_percent', (string) (float) $vat_percent);
                $this->audit('settings', 'UPDATE_VAT', array('vat_percent' => $old), array('vat_percent' => (float) $vat_percent));
                redirect('me/settings');
                return;
            }
        }

        $data = array(
            'page_title'          => 'Cài đặt',
            'current_user'        => $this->current_user,
            'vat_percent'         => $this->Setting_model->get_vat_percent(),
            'error'               => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('settings/index', $data);
        $this->load->view('layout/footer');
    }
}
