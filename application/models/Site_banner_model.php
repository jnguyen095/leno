<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Banner trang chủ (băng chuyền ảnh đầu trang chủ) — xem migration 013. */
class Site_banner_model extends CI_Model
{
    protected $table = 'site_banners';

    const DEFAULT_SECONDS = 5;

    public function get_all()
    {
        return $this->db->order_by('sort_order', 'ASC')->order_by('id', 'ASC')->get($this->table)->result_array();
    }

    /** Banner đang hiện, theo thứ tự chạy. */
    public function get_showing()
    {
        return $this->db->where('status', 'ACTIVE')
            ->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', (int) $id)->get($this->table)->row_array();
    }

    /** Thêm vào cuối danh sách; trả về id. */
    public function create(array $data)
    {
        $max = (int) $this->db->select_max('sort_order')->get($this->table)->row('sort_order');
        $data['sort_order'] = $max + 10;
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', (int) $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', (int) $id)->delete($this->table);
    }

    /** Đổi chỗ với banner liền trước ($direction = -1) hoặc liền sau (+1). Trả về TRUE nếu có đổi. */
    public function move($id, $direction)
    {
        $ids = array_map('intval', array_column($this->get_all(), 'id'));
        $i = array_search((int) $id, $ids, TRUE);
        $j = $i === FALSE ? FALSE : $i + ($direction < 0 ? -1 : 1);
        if ($i === FALSE || $j < 0 || $j >= count($ids))
        {
            return FALSE;
        }

        // Đánh lại số thứ tự liền mạch rồi đổi chỗ hai banner.
        list($ids[$i], $ids[$j]) = array($ids[$j], $ids[$i]);
        $this->db->trans_start();
        foreach ($ids as $pos => $banner_id)
        {
            $this->db->where('id', $banner_id)->update($this->table, array('sort_order' => ($pos + 1) * 10));
        }
        $this->db->trans_complete();
        return TRUE;
    }
}
