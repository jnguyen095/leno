<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Công thức pha chế / sơ chế nguyên liệu (VD: Kem muối, Kem trứng, Cốt trà
 * trái cây...) — chỉ ADMIN vì liên quan trực tiếp đến cost nguyên liệu.
 * Một công thức có thể dùng công thức KHÁC làm thành phần (sơ chế lồng
 * nhau) — xem [[064_allow_recipe_as_ingredient]].
 */
class Recipes extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Recipe_model', 'Recipe_ingredient_model', 'Inventory_item_model'));
    }

    public function index()
    {
        $recipes = $this->Recipe_model->get_all();
        foreach ($recipes as &$r)
        {
            $cost = $this->Recipe_model->compute_total_cost($r['id']);
            $r['total_cost'] = $cost['total'];
            $r['cost_complete'] = $cost['complete'];
        }
        unset($r);

        $data = array(
            'page_title'   => 'Công thức pha chế',
            'current_user' => $this->current_user,
            'recipes'      => $recipes,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('recipes/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $name = $this->input->post('name', TRUE);
            if ($name === '')
            {
                $error = 'Vui lòng nhập tên món.';
            }
            else
            {
                $id = $this->Recipe_model->create(array('name' => $name, 'status' => 'ACTIVE'));
                $this->audit('recipe', 'CREATE', NULL, array('id' => $id));
                redirect('me/recipes/'.$id.'/edit');
                return;
            }
        }

        $data = array('page_title' => 'Thêm công thức', 'current_user' => $this->current_user, 'error' => $error);
        $this->load->view('layout/header', $data);
        $this->load->view('recipes/create', $data);
        $this->load->view('layout/footer');
    }

    private $valid_units = array('ml', 'g', 'cái', 'lát', 'lá', 'ly', 'gói');

    public function edit($id)
    {
        $recipe = $this->Recipe_model->get_by_id($id);
        if ( ! $recipe) show_404();
        $error = $this->session->flashdata('error');

        if ($this->input->method() === 'post')
        {
            $name = $this->input->post('name', TRUE);
            $yield_quantity = $this->input->post('yield_quantity');
            $yield_unit = $this->input->post('yield_unit');
            $yield_unit = in_array($yield_unit, $this->valid_units, TRUE) ? $yield_unit : NULL;
            $instructions = $this->input->post('instructions', TRUE);

            if ($name === '')
            {
                $error = 'Vui lòng nhập tên món.';
            }
            else
            {
                $image = $this->_handle_image_upload($error, $recipe['image']);
            }

            if ( ! $error)
            {
                $this->Recipe_model->update($id, array(
                    'name'           => $name,
                    'image'          => $image,
                    'status'         => $this->input->post('status') === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE',
                    'yield_quantity' => ($yield_quantity !== NULL && $yield_quantity !== '') ? (float) $yield_quantity : NULL,
                    'yield_unit'     => $yield_unit,
                    'instructions'   => ($instructions !== NULL && $instructions !== '') ? $instructions : NULL,
                ));
                $this->audit('recipe', 'UPDATE', $recipe, array('id' => $id));
                redirect('me/recipes/'.$id.'/edit');
                return;
            }
            $recipe = array_merge($recipe, array('name' => $name));
        }

        $ingredients = $this->Recipe_ingredient_model->get_by_recipe($id);
        $total_cost = 0;
        $has_missing_cost = FALSE;
        foreach ($ingredients as &$ing)
        {
            if ($ing['source_recipe_id'])
            {
                $sub = $this->Recipe_model->compute_total_cost($ing['source_recipe_id']);
                $yield = (float) $ing['source_yield_quantity'];
                if ($yield > 0 && $sub['complete'])
                {
                    $ing['cost'] = (float) $ing['quantity'] * ($sub['total'] / $yield);
                }
                else
                {
                    $ing['cost'] = NULL;
                }
            }
            else
            {
                $ing['cost'] = $ing['item_base_unit_cost'] !== NULL ? ((float) $ing['quantity'] * (float) $ing['item_base_unit_cost']) : NULL;
            }

            if ($ing['cost'] === NULL) $has_missing_cost = TRUE;
            else $total_cost += $ing['cost'];
        }
        unset($ing);

        $all_recipes = $this->Recipe_model->get_all();
        $other_recipes = array();
        foreach ($all_recipes as $r)
        {
            if ((int) $r['id'] !== (int) $id) $other_recipes[] = $r;
        }

        $data = array(
            'page_title'       => 'Công thức — '.$recipe['name'],
            'current_user'     => $this->current_user,
            'recipe'           => $recipe,
            'ingredients'      => $ingredients,
            'total_cost'       => $total_cost,
            'has_missing_cost' => $has_missing_cost,
            'items'            => $this->Inventory_item_model->get_all(NULL, NULL, NULL),
            'other_recipes'    => $other_recipes,
            'valid_units'      => $this->valid_units,
            'error'            => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('recipes/edit', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Ảnh công thức (tùy chọn) — trả về đường dẫn tương đối để lưu vào
     * recipes.image, hoặc giữ nguyên $existing_image nếu không chọn file
     * mới. Set $error (tham chiếu) và trả về NULL nếu upload lỗi. Cùng quy
     * ước với Inventory_items::_handle_image_upload().
     */
    private function _handle_image_upload(&$error, $existing_image = NULL)
    {
        if (empty($_FILES['image']['name']))
        {
            return $existing_image;
        }

        $upload_dir = FCPATH.'assets/uploads/recipes/';
        if ( ! is_dir($upload_dir))
        {
            mkdir($upload_dir, 0755, TRUE);
        }

        $this->load->library('upload', array(
            'upload_path'   => $upload_dir,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size'      => 2048,
            'encrypt_name'  => TRUE,
        ));

        if ( ! $this->upload->do_upload('image'))
        {
            $error = $this->upload->display_errors('', '');
            return NULL;
        }

        if ($existing_image && is_file(FCPATH.'assets/'.$existing_image))
        {
            @unlink(FCPATH.'assets/'.$existing_image);
        }

        return 'uploads/recipes/'.$this->upload->data('file_name');
    }

    /**
     * Xuất Excel để in dán tường cho nhân viên: các công thức được chọn (GET ids[] — theo thứ tự danh sách công thức),
     * mỗi công thức một khối 3 cột "Món | Thành phần (công thức) | Khối lượng (ml/g)", ô Món gộp dọc theo số thành phần.
     * ?instructions=1: thêm dòng "Cách làm" dưới mỗi công thức.
     */
    public function export()
    {
        $ids = array_filter(array_map('intval', (array) $this->input->get('ids')));
        $with_steps = $this->input->get('instructions') === '1';
        $one_per_page = $this->input->get('per_page') === '1'; // mỗi công thức một trang (dán riêng từng chỗ)
        $recipes = array_values(array_filter($this->Recipe_model->get_all(), function ($r) use ($ids) { return in_array((int) $r['id'], $ids, TRUE); }));
        if ( ! $recipes)
        {
            $this->session->set_flashdata('error', 'Chọn ít nhất một công thức để xuất Excel.');
            redirect('me/recipes');
            return;
        }

        // Định dạng số lượng: 100.000 -> "100", 1.500 -> "1,5"; kèm đơn vị (g, ml, gói, ly...).
        $qty = function ($q, $unit) {
            $n = rtrim(rtrim(number_format((float) $q, 3, ',', '.'), '0'), ',');
            return trim($n.' '.(string) $unit);
        };
        // Excel không tự giãn dòng có ô gộp — ước lượng chiều cao theo độ dài chữ / độ rộng cột (cỡ chữ 13–14).
        $lines = function ($text, $width, $chars_per_unit = 0.95) {
            $n = 0;
            foreach (explode("\n", (string) $text) as $part) $n += max(1, (int) ceil(mb_strlen($part, 'UTF-8') / max(1, $width * $chars_per_unit)));
            return $n;
        };
        $widths = array(24, 50, 18);

        $this->load->library('xlsx_print_sheet');
        $s = $this->xlsx_print_sheet;
        $s->set_sheet_name('Công thức')->set_widths($widths);
        $s->add('CÔNG THỨC PHA CHẾ', 'title', 36, 3);
        $s->add('In ngày '.date('d/m/Y').' · '.count($recipes).' công thức', 'subtitle', 20, 3);
        $s->add(array('Món', 'Thành phần (công thức)', 'Khối lượng (ml/g)'), 'header', 28)->repeat_rows(3, 3);

        $blocks = array(); // [dòng đầu, dòng cuối] của từng công thức — để ngắt trang không cắt ngang công thức
        $gap_pt = 18;      // dòng trắng (không kẻ ô) giữa 2 công thức — dễ cắt rời, không nhìn nhầm sang món bên cạnh
        foreach ($recipes as $k => $r)
        {
            if ($k > 0) $s->add(array('', '', ''), 'text', $gap_pt);
            $name = $r['name'].($r['yield_quantity'] ? "\n(".$qty($r['yield_quantity'], $r['yield_unit']).')' : '');
            $items = $this->Recipe_ingredient_model->get_by_recipe($r['id']);
            $start = $s->row_count() + 1;
            $used = 0; // tổng chiều cao (pt) các dòng của công thức — để ô Món gộp đủ chỗ cho tên
            if ( ! $items)
            {
                $s->add(array($name, '(chưa có thành phần)', ''), array('name', 'note', 'qty'), $h = max(30, 20 * $lines($name, $widths[0])));
                $used += $h;
            }
            foreach ($items as $i => $it)
            {
                $h = max(26, 19 * $lines($it['item_name'], $widths[1]));
                $s->add(array($i === 0 ? $name : '', $it['item_name'], $qty($it['quantity'], $it['unit_label'])), array('name', 'cell', 'qty'), $h);
                $used += $h;
            }
            if ($with_steps && trim((string) $r['instructions']) !== '')
            {
                $text = "Cách làm:\n".self::numbered_steps($r['instructions']);
                $h = max(26, 15 * $lines($text, $widths[1] + $widths[2], 1.25) + 8); // chữ nghiêng cỡ 11 — nhiều chữ hơn mỗi dòng
                $s->add(array('', $text, ''), array('name', 'note', 'note'), $h);
                $row = $s->row_count();
                $s->merge('B'.$row.':C'.$row);
                $used += $h;
            }
            $end = $s->row_count();
            if ($end > $start) $s->merge('A'.$start.':A'.$end);
            // Tên dài mà công thức ít dòng: kéo dòng cuối cao thêm để tên không bị cắt.
            $need = 22 * $lines($name, $widths[0]) + 8;
            if ($need > $used) $s->add_height($end, $need - $used);
            $blocks[] = array($start, $end);
        }

        // Ngắt trang: không để một công thức bị cắt sang trang sau (trừ công thức dài hơn cả trang).
        // PAGE_PT: tổng chiều cao dòng vừa 1 trang A4 dọc khi co vừa bề ngang (đo thực tế ~950pt, chừa dư).
        $page_pt = 900;
        $used = $s->row_height(1) + $s->row_height(2) + $s->row_height(3);
        foreach ($blocks as $i => $b)
        {
            $h = 0;
            for ($row = $b[0]; $row <= $b[1]; $row++) $h += $s->row_height($row);
            if ($i > 0 && ($one_per_page || $used + $gap_pt + $h > $page_pt))
            {
                // Dòng trắng phía trước nằm lại cuối trang trước; trang mới bắt đầu bằng dòng tiêu đề cột (lặp lại).
                $s->page_break_before($b[0]);
                $used = $s->row_height(3);
            }
            elseif ($i > 0)
            {
                $used += $gap_pt;
            }
            $used += $h;
        }

        $this->audit('recipe', 'EXPORT_PRINT', NULL, array('recipes' => array_column($recipes, 'name'), 'instructions' => $with_steps, 'one_per_page' => $one_per_page));
        $s->download('cong_thuc_pha_che_'.date('Ymd').'.xlsx');
    }

    /**
     * Đánh số bước cho phần Cách làm khi in: mỗi dòng là một bước "1. ...", "2. ...". Không đánh số (giữ nguyên) các dòng:
     * kẻ ngăn (=====), mục con (+, -, *, •), dòng trong ngoặc / ghi chú (Lưu ý, Chú ý), tiêu đề kết thúc bằng ":",
     * và dòng đã tự đánh số sẵn (1. / 1) / Bước 1).
     */
    public static function numbered_steps($instructions)
    {
        $out = array();
        $n = 0;
        foreach (explode("\n", str_replace(array("\r\n", "\r"), "\n", (string) $instructions)) as $line)
        {
            $line = trim($line);
            if ($line === '') continue;
            if (preg_match('/^(\d+\s*[.)]|bước\s*\d+)/iu', $line))                         { $out[] = $line; continue; } // đã có số
            if (preg_match('/^[=\-_*~.]{3,}$/u', $line)
                || preg_match('/^(\+|-|\*|•|\()/u', $line)
                || preg_match('/^(lưu ý|chú ý|ghi chú|note)\b/iu', $line)
                || preg_match('/:\s*$/u', $line))                                              { $out[] = $line; continue; } // không phải bước
            $out[] = (++$n).'. '.$line;
        }
        return implode("\n", $out);
    }

    public function delete($id)
    {
        $recipe = $this->Recipe_model->get_by_id($id);
        if ( ! $recipe) show_404();

        if ($this->Recipe_model->is_used_as_ingredient($id))
        {
            $this->session->set_flashdata('error', 'Không thể xoá — công thức này đang được dùng làm thành phần trong công thức khác.');
            redirect('me/recipes/'.$id.'/edit');
            return;
        }

        $this->Recipe_model->delete($id);
        $this->audit('recipe', 'DELETE', $recipe, NULL);
        redirect('me/recipes');
    }

    public function add_ingredient($recipe_id)
    {
        $recipe = $this->Recipe_model->get_by_id($recipe_id);
        if ( ! $recipe) show_404();

        $ref = $this->input->post('ingredient_ref');
        $quantity = (float) $this->input->post('quantity');
        $parts = $ref ? explode(':', $ref, 2) : array();

        $inventory_item_id = NULL;
        $source_recipe_id = NULL;
        $valid = FALSE;

        if (count($parts) === 2 && $quantity > 0)
        {
            list($type, $ref_id) = $parts;
            $ref_id = (int) $ref_id;

            if ($type === 'item' && $ref_id)
            {
                $item = $this->Inventory_item_model->get_by_id($ref_id);
                if ($item)
                {
                    $inventory_item_id = $ref_id;
                    $valid = TRUE;
                }
            }
            elseif ($type === 'recipe' && $ref_id)
            {
                $target = $this->Recipe_model->get_by_id($ref_id);
                if ($target && ! $this->Recipe_model->recipe_depends_on($ref_id, $recipe_id))
                {
                    $source_recipe_id = $ref_id;
                    $valid = TRUE;
                }
                else
                {
                    $this->session->set_flashdata('error', 'Không thể thêm — sẽ tạo vòng lặp giữa các công thức.');
                }
            }
        }

        if ($valid)
        {
            $this->Recipe_ingredient_model->add($recipe_id, $inventory_item_id, $source_recipe_id, $quantity);
            $this->audit('recipe_ingredient', 'CREATE', NULL, array('recipe_id' => $recipe_id, 'inventory_item_id' => $inventory_item_id, 'source_recipe_id' => $source_recipe_id));
        }
        elseif ( ! $this->session->flashdata('error'))
        {
            $this->session->set_flashdata('error', 'Vui lòng chọn thành phần và nhập định lượng hợp lệ.');
        }

        redirect('me/recipes/'.$recipe_id.'/edit');
    }

    public function delete_ingredient($recipe_id, $ingredient_id)
    {
        $this->Recipe_ingredient_model->delete($ingredient_id, $recipe_id);
        $this->audit('recipe_ingredient', 'DELETE', NULL, array('recipe_id' => $recipe_id, 'id' => $ingredient_id));
        redirect('me/recipes/'.$recipe_id.'/edit');
    }
}
