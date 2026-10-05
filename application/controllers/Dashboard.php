<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tổng quan kho hàng — tồn kho theo danh mục, sản phẩm sắp hết hàng, lịch sử nhập/xuất/kiểm kho gần đây. */
class Dashboard extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'CASHIER', 'ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Inventory_item_model', 'Inventory_category_model', 'Stock_transaction_model'));
    }

    public function index()
    {
        $low_stock_items = $this->Inventory_item_model->get_low_stock();

        $data = array(
            'page_title'        => 'Tổng quan',
            'current_user'      => $this->current_user,
            'total_items'       => $this->Inventory_item_model->count_active(),
            'total_low_stock'   => count($low_stock_items),
            'total_categories'  => count($this->Inventory_category_model->get_active()),
            'category_summary'  => $this->Inventory_item_model->get_category_stock_summary(),
            'top_low_stock'     => array_slice($low_stock_items, 0, 8),
            'recent_batches'    => $this->Stock_transaction_model->get_recent_batches(array(), 8, 0),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('layout/footer');
    }
}
