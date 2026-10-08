<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Ảnh trình chiếu trên màn hình khách khi không có đơn đang hiển thị (xem migration 003). */
class Display_slide_model extends CI_Model
{
    protected $table = 'display_slides';

    public function get_all()
    {
        return $this->db->order_by('sort_order', 'ASC')->order_by('id', 'ASC')->get($this->table)->result_array();
    }

    /** Ảnh đang bật và nằm trong khoảng ngày hiển thị (nếu có đặt), theo thứ tự trình chiếu. */
    public function get_showing($today = NULL)
    {
        $today = $today ?: date('Y-m-d');
        return $this->db->where('status', 'ACTIVE')
            ->group_start()->where('start_date IS NULL', NULL, FALSE)->or_where('start_date <=', $today)->group_end()
            ->group_start()->where('end_date IS NULL', NULL, FALSE)->or_where('end_date >=', $today)->group_end()
            ->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function create($data)
    {
        $max = (int) $this->db->select_max('sort_order')->get($this->table)->row('sort_order');
        $data['sort_order'] = $max + 10;
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
    }

    /** Đổi chỗ với ảnh liền trước ($direction = -1) hoặc liền sau (+1). Trả về TRUE nếu có đổi. */
    public function move($id, $direction)
    {
        $slides = $this->get_all();
        $ids = array_map('intval', array_column($slides, 'id'));
        $i = array_search((int) $id, $ids, TRUE);
        $j = $i === FALSE ? FALSE : $i + ($direction < 0 ? -1 : 1);
        if ($i === FALSE || $j < 0 || $j >= count($ids))
        {
            return FALSE;
        }

        // Đánh lại số thứ tự liền mạch rồi đổi chỗ hai ảnh.
        list($ids[$i], $ids[$j]) = array($ids[$j], $ids[$i]);
        $this->db->trans_start();
        foreach ($ids as $pos => $slide_id)
        {
            $this->db->where('id', $slide_id)->update($this->table, array('sort_order' => ($pos + 1) * 10));
        }
        $this->db->trans_complete();
        return TRUE;
    }
}
