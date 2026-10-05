<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Doanh thu nhập tay theo tháng + danh mục (bán hàng qua POS ngoài). */
class Monthly_revenue_model extends CI_Model
{
    protected $table = 'monthly_revenue';

    const CATEGORIES = array('KHU_VUI_CHOI', 'NUOC_DO_AN', 'PHOTOBOOTH', 'GRABFOOD');

    /** Mảng [category => row] cho 1 tháng — category chưa có dữ liệu sẽ không xuất hiện trong mảng trả về. */
    public function get_by_period($period)
    {
        $rows = $this->db->where('period', $period)->get($this->table)->result_array();
        $by_category = array();
        foreach ($rows as $r)
        {
            $by_category[$r['category']] = $r;
        }
        return $by_category;
    }

    /** Mảng [category => revenue] đầy đủ 4 danh mục, 0 nếu chưa nhập, dùng để hiển thị/tính tổng+phần trăm 1 tháng. */
    public function get_revenue_by_category($period)
    {
        $existing = $this->get_by_period($period);
        $result = array();
        foreach (self::CATEGORIES as $c)
        {
            $result[$c] = isset($existing[$c]) ? (float) $existing[$c]['revenue'] : 0;
        }
        return $result;
    }

    /** Mảng [period => ['total' => N, 'by_category' => [...]]] cho 1 khoảng nhiều tháng liên tiếp — dùng cho biểu đồ xu hướng. */
    public function get_totals_for_periods($periods)
    {
        $rows = $this->db->where_in('period', $periods)->get($this->table)->result_array();

        $result = array();
        foreach ($periods as $p)
        {
            $result[$p] = array('total' => 0, 'by_category' => array_fill_keys(self::CATEGORIES, 0));
        }
        foreach ($rows as $r)
        {
            $result[$r['period']]['total'] += (float) $r['revenue'];
            $result[$r['period']]['by_category'][$r['category']] = (float) $r['revenue'];
        }
        return $result;
    }

    /** Ghi/ghi đè doanh thu 1 danh mục trong 1 tháng. */
    public function upsert($period, $category, $revenue, $note, $updated_by)
    {
        $existing = $this->db->where('period', $period)->where('category', $category)->get($this->table)->row_array();
        $data = array(
            'revenue'    => $revenue,
            'note'       => $note,
            'updated_by' => $updated_by,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if ($existing)
        {
            return $this->db->where('id', $existing['id'])->update($this->table, $data);
        }

        $data['period'] = $period;
        $data['category'] = $category;
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert($this->table, $data);
    }
}
