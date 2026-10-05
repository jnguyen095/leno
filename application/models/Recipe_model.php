<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Công thức pha chế / sơ chế nguyên liệu — xem [[061_create_recipes_table]].
 * Một công thức có thể vừa dùng nguyên liệu kho, vừa dùng CÔNG THỨC KHÁC làm
 * thành phần (sơ chế lồng nhau, VD: "Trà Lài" là thành phần của "Trà Đào") —
 * xem [[064_allow_recipe_as_ingredient]].
 */
class Recipe_model extends CI_Model
{
    protected $table = 'recipes';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Recipe_ingredient_model');
    }

    public function get_all()
    {
        return $this->db->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
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

    /** Công thức này có đang được dùng làm thành phần trong công thức khác không — chặn xoá nếu có. */
    public function is_used_as_ingredient($recipe_id)
    {
        return count($this->Recipe_ingredient_model->get_usages($recipe_id)) > 0;
    }

    /**
     * $recipe_id có phụ thuộc (trực tiếp hoặc gián tiếp qua nhiều lớp sơ chế)
     * vào $target_id không — dùng để chặn vòng lặp TRƯỚC KHI cho phép thêm
     * $target_id làm thành phần của $recipe_id (nếu true nghĩa là sẽ tạo vòng lặp).
     */
    public function recipe_depends_on($recipe_id, $target_id, $visited = array())
    {
        if ((int) $recipe_id === (int) $target_id) return TRUE;
        if (isset($visited[$recipe_id])) return FALSE;
        $visited[$recipe_id] = TRUE;

        $ingredients = $this->Recipe_ingredient_model->get_by_recipe($recipe_id);
        foreach ($ingredients as $ing)
        {
            if ($ing['source_recipe_id'] && $this->recipe_depends_on($ing['source_recipe_id'], $target_id, $visited))
            {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Tổng cost của 1 mẻ công thức — cộng dồn nguyên liệu kho (quantity *
     * base_unit_cost) và công thức con (quantity * (tổng cost mẻ con /
     * yield_quantity mẻ con)). 'complete' = FALSE nếu có bất kỳ thành phần
     * nào (ở bất kỳ tầng nào) chưa đủ dữ liệu để tính giá (thiếu
     * base_unit_cost, hoặc công thức con thiếu yield_quantity/yield_unit).
     * $visited chặn vòng lặp (phòng hờ — recipe_depends_on() đã chặn từ lúc
     * thêm thành phần, đây chỉ là lưới an toàn thứ 2).
     */
    public function compute_total_cost($recipe_id, $visited = array())
    {
        if (isset($visited[$recipe_id]))
        {
            return array('total' => 0, 'complete' => FALSE);
        }
        $visited[$recipe_id] = TRUE;

        $ingredients = $this->Recipe_ingredient_model->get_by_recipe($recipe_id);
        $total = 0;
        $complete = TRUE;

        foreach ($ingredients as $ing)
        {
            if ($ing['source_recipe_id'])
            {
                $sub = $this->compute_total_cost($ing['source_recipe_id'], $visited);
                $yield = (float) $ing['source_yield_quantity'];
                if ($yield > 0 && $sub['complete'])
                {
                    $total += ((float) $ing['quantity']) * ($sub['total'] / $yield);
                }
                else
                {
                    $complete = FALSE;
                }
            }
            elseif ($ing['item_base_unit_cost'] !== NULL)
            {
                $total += (float) $ing['quantity'] * (float) $ing['item_base_unit_cost'];
            }
            else
            {
                $complete = FALSE;
            }
        }

        return array('total' => $total, 'complete' => $complete);
    }
}
