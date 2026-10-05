<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Thư viện ảnh website public — xem [[057_create_gallery_table]]. */
class Gallery_model extends CI_Model
{
    protected $table = 'gallery';

    public function get_active($category = NULL)
    {
        $this->db->where('status', 'ACTIVE');
        if ($category)
        {
            $this->db->where('category', $category);
        }
        return $this->db->order_by('sort_order', 'ASC')->get($this->table)->result_array();
    }
}
