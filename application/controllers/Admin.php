<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Trang chính Quản trị (/me/admin): lưới các chức năng quản trị người dùng được phép, chia nhóm.
 * Mỗi trang quản trị có menu trái giống vậy (layout/_admin_nav.php) — thay cho menu xổ xuống khó dùng trên điện thoại.
 */
class Admin extends MY_Controller
{
    public function index()
    {
        $this->load->helper('admin_menu');
        $groups = admin_menu_groups($this->current_user);
        if ( ! $groups)
        {
            $this->output->set_status_header(403);
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            return;
        }

        $data = array(
            'page_title'   => 'Quản trị',
            'current_user' => $this->current_user,
            'groups'       => $groups,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('admin/index', $data);
        $this->load->view('layout/footer');
    }
}
