<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xem công thức pha chế cho nhân viên bar/bếp — CHỈ Thành phần, Định lượng,
 * Cách làm, KHÔNG có giá/cost (khác Recipes.php, màn ADMIN quản lý cost).
 * Không đặt $allowed_roles tĩnh — controller này được đăng ký trong
 * menu_items (menu_key 'pha_che', xem [[066_add_pha_che_menu_item]]) nên RBAC
 * động là thẩm quyền DUY NHẤT: mặc định chỉ ADMIN, admin tự cấp thêm cho vai
 * trò khác qua /menu-permissions — xem MY_Controller.
 */
class Pha_che extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Recipe_model', 'Recipe_ingredient_model'));
    }

    /** Danh sách công thức đang hoạt động, dạng thẻ (card) — dễ chạm trên điện thoại. */
    public function index()
    {
        $keyword = trim((string) $this->input->get('q'));

        $recipes = array_values(array_filter($this->Recipe_model->get_all(), function($r)
        {
            return $r['status'] === 'ACTIVE';
        }));

        if ($keyword !== '')
        {
            $recipes = array_values(array_filter($recipes, function($r) use ($keyword)
            {
                return mb_stripos($r['name'], $keyword) !== FALSE;
            }));
        }

        $data = array(
            'page_title'   => 'Pha chế',
            'current_user' => $this->current_user,
            'recipes'      => $recipes,
            'keyword'      => $keyword,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('pha_che/index', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Chi tiết 1 công thức — thành phần + định lượng (nhân theo mẻ ở client
     * bằng JS, không cần round-trip server) + cách làm. Cố tình không select
     * bất kỳ trường cost nào ra view.
     */
    public function view($id)
    {
        $recipe = $this->Recipe_model->get_by_id($id);
        if ( ! $recipe || $recipe['status'] !== 'ACTIVE')
        {
            show_404();
        }

        $ingredients = $this->Recipe_ingredient_model->get_by_recipe($id);
        $steps = $recipe['instructions']
            ? array_values(array_filter(array_map('trim', explode("\n", $recipe['instructions']))))
            : array();

        $data = array(
            'page_title'   => 'Pha chế — '.$recipe['name'],
            'current_user' => $this->current_user,
            'recipe'       => $recipe,
            'ingredients'  => $ingredients,
            'steps'        => $steps,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('pha_che/view', $data);
        $this->load->view('layout/footer');
    }
}
