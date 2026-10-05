<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI-only entry point to run DB migrations: `php index.php migrate`
 */
class Migrate extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if ( ! $this->input->is_cli_request())
        {
            show_404();
        }

        $this->load->helper('kds');
        $this->load->library('migration');
    }

    public function index()
    {
        if ($this->migration->latest() === FALSE)
        {
            echo 'Migration failed: '.$this->migration->error_string().PHP_EOL;
            exit(1);
        }

        echo 'Migrated to the latest version successfully.'.PHP_EOL;
    }
}
