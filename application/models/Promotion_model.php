<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Khuyến mãi/sự kiện hiển thị ở website public — xem [[056_create_promotions_table]]. */
class Promotion_model extends CI_Model
{
    protected $table = 'promotions';

    /** Đang ACTIVE và (không đặt ngày HOẶC hôm nay còn trong khoảng start_date..end_date) — dùng cho section "Hôm nay có gì?". */
    public function get_active()
    {
        $today = date('Y-m-d');
        $this->db->where('status', 'ACTIVE')
            ->group_start()
                ->where('start_date IS NULL', NULL, FALSE)
                ->or_where('start_date <=', $today)
            ->group_end()
            ->group_start()
                ->where('end_date IS NULL', NULL, FALSE)
                ->or_where('end_date >=', $today)
            ->group_end();
        return $this->db->order_by('sort_order', 'ASC')->get($this->table)->result_array();
    }
}
