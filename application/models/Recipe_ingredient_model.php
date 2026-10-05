<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Thành phần của 1 công thức — xem [[064_allow_recipe_as_ingredient]]. Mỗi
 * dòng là MỘT trong hai loại: nguyên liệu kho (inventory_item_id) HOẶC một
 * công thức khác dùng làm thành phần/sơ chế (source_recipe_id) — không bao
 * giờ cả hai cùng lúc. Cost không lưu sẵn, tính lại mỗi lần đọc (xem
 * Recipe_model::compute_total_cost()) để không lệch khi giá nguyên liệu đổi.
 */
class Recipe_ingredient_model extends CI_Model
{
    protected $table = 'recipe_ingredients';

    /** Thành phần của 1 công thức, kèm dữ liệu cần để tính cost (dù là nguyên liệu kho hay công thức con). */
    public function get_by_recipe($recipe_id)
    {
        return $this->db->select("
                recipe_ingredients.*,
                COALESCE(inventory_items.name, recipes.name) AS item_name,
                COALESCE(inventory_items.base_unit, recipes.yield_unit) AS unit_label,
                inventory_items.base_unit_cost AS item_base_unit_cost,
                inventory_units.name AS item_unit_name,
                recipes.yield_quantity AS source_yield_quantity
            ", FALSE)
            ->from($this->table)
            ->join('inventory_items', 'inventory_items.id = recipe_ingredients.inventory_item_id', 'left')
            ->join('inventory_units', 'inventory_units.id = inventory_items.unit_id', 'left')
            ->join('recipes', 'recipes.id = recipe_ingredients.source_recipe_id', 'left')
            ->where('recipe_ingredients.recipe_id', $recipe_id)
            ->order_by('recipe_ingredients.sort_order', 'ASC')
            ->order_by('recipe_ingredients.id', 'ASC')
            ->get()->result_array();
    }

    /** Tất cả dòng có source_recipe_id = $recipe_id (tức công thức này đang được dùng làm thành phần ở nơi khác). */
    public function get_usages($recipe_id)
    {
        return $this->db->select('recipe_ingredients.*, recipes.name AS recipe_name')
            ->from($this->table)
            ->join('recipes', 'recipes.id = recipe_ingredients.recipe_id')
            ->where('recipe_ingredients.source_recipe_id', $recipe_id)
            ->get()->result_array();
    }

    public function add($recipe_id, $inventory_item_id, $source_recipe_id, $quantity)
    {
        $max_sort = $this->db->select_max('sort_order')->where('recipe_id', $recipe_id)->get($this->table)->row_array();
        $sort_order = ($max_sort && $max_sort['sort_order'] !== NULL) ? ((int) $max_sort['sort_order'] + 1) : 1;

        return $this->db->insert($this->table, array(
            'recipe_id'         => $recipe_id,
            'inventory_item_id' => $inventory_item_id,
            'source_recipe_id'  => $source_recipe_id,
            'quantity'          => $quantity,
            'sort_order'        => $sort_order,
            'created_at'        => date('Y-m-d H:i:s'),
        ));
    }

    public function delete($id, $recipe_id)
    {
        return $this->db->where('id', $id)->where('recipe_id', $recipe_id)->delete($this->table);
    }
}
