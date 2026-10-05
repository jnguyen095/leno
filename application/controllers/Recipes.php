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
