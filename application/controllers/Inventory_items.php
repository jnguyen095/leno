<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Danh sách + quản lý Sản phẩm kho. index()/search()/by_category() mở cho
 * mọi role đang thao tác nhập/xuất/kiểm kho (STAFF/BARISTA/CASHIER/ADMIN/
 * STOCKTAKER — STOCKTAKER cần by_category() để màn Kiểm kho tải được sản
 * phẩm); các thao tác quản trị (thêm/sửa/xoá/import/next_sku) tự chặn về
 * ADMIN bên trong từng method.
 */
class Inventory_items extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'BARISTA', 'CASHIER', 'ADMIN', 'STOCKTAKER');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Inventory_item_model', 'Inventory_category_model', 'Inventory_unit_model'));
    }

    private function _require_admin()
    {
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            exit;
        }
    }

    public function index()
    {
        $category_id = $this->input->get('category_id');
        $stock_status = $this->input->get('stock_status'); // 'LOW' | 'OK' | rỗng = tất cả
        $keyword = trim((string) $this->input->get('q'));
        // Lọc theo trạng thái (xem/khôi phục sản phẩm đã xoá) chỉ ADMIN mới
        // thấy được trên UI — role khác luôn chỉ thấy sản phẩm ACTIVE.
        $status = $this->current_user['role'] === 'ADMIN' ? ($this->input->get('status') ?: 'ACTIVE') : 'ACTIVE';

        $data = array(
            'page_title'    => 'Sản phẩm kho',
            'current_user'  => $this->current_user,
            'items'         => $this->Inventory_item_model->get_all($category_id ?: NULL, $stock_status ?: NULL, $keyword ?: NULL, $status),
            'categories'    => $this->Inventory_category_model->get_active(),
            'category_id'   => $category_id,
            'stock_status'  => $stock_status,
            'keyword'       => $keyword,
            'status'        => $status,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_items/index', $data);
        $this->load->view('layout/footer');
    }

    /** Xuất Excel (CSV) danh sách sản phẩm kho, tôn trọng bộ lọc đang xem trên màn hình. */
    public function export()
    {
        $category_id = $this->input->get('category_id');
        $stock_status = $this->input->get('stock_status');
        $keyword = trim((string) $this->input->get('q'));

        $items = $this->Inventory_item_model->get_all($category_id ?: NULL, $stock_status ?: NULL, $keyword ?: NULL);

        $this->output->set_content_type('text/csv');
        header('Content-Disposition: attachment; filename="san_pham_kho_'.date('Ymd_His').'.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array('STT', 'Tên', 'Danh Mục', 'ĐVT', 'Tồn Kho', 'SL'));

        $stt = 1;
        foreach ($items as $it)
        {
            fputcsv($out, array(
                $stt++,
                $it['name'],
                $it['category_name'],
                $it['unit_name'],
                rtrim(rtrim(number_format($it['qty_on_hand'], 2, '.', ''), '0'), '.'),
                '', // SL - để trống, dùng khi in ra kiểm đếm tay
            ));
        }
        fclose($out);
    }

    /** Trang in danh sách sản phẩm kho (khổ A4), tôn trọng bộ lọc đang xem trên màn hình. */
    public function print_list()
    {
        $category_id = $this->input->get('category_id');
        $stock_status = $this->input->get('stock_status');
        $keyword = trim((string) $this->input->get('q'));

        $category_name = NULL;
        if ($category_id)
        {
            $category = $this->Inventory_category_model->get_by_id($category_id);
            $category_name = $category ? $category['name'] : NULL;
        }

        $data = array(
            'items'         => $this->Inventory_item_model->get_all($category_id ?: NULL, $stock_status ?: NULL, $keyword ?: NULL),
            'category_name' => $category_name,
            'stock_status'  => $stock_status,
            'keyword'       => $keyword,
        );
        $this->load->view('inventory_items/print', $data);
    }

    public function create()
    {
        $this->_require_admin();
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $sku = $this->input->post('sku', TRUE);
            if ($sku === '')
            {
                $sku = $this->Inventory_item_model->generate_sku((int) $this->input->post('category_id'));
            }

            if ($this->Inventory_item_model->sku_exists($sku))
            {
                $error = 'SKU đã tồn tại.';
            }
            else
            {
                $image = $this->_handle_image_upload($error);

                if ( ! $error)
                {
                    $id = $this->Inventory_item_model->create($this->_form_data($sku, $image));
                    $this->audit('inventory_item', 'CREATE', NULL, array('id' => $id));
                    redirect('me/inventory/items');
                    return;
                }
            }
        }

        $data = array(
            'page_title'   => 'Thêm sản phẩm kho',
            'current_user' => $this->current_user,
            'item'         => NULL,
            'categories'   => $this->Inventory_category_model->get_active(),
            'units'        => $this->Inventory_unit_model->get_active(),
            'error'        => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_items/form', $data);
        $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        $this->_require_admin();
        $item = $this->Inventory_item_model->get_by_id($id);
        if ( ! $item) show_404();
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $sku = $this->input->post('sku', TRUE);
            if ($sku === '')
            {
                $sku = $item['sku'];
            }

            if ($this->Inventory_item_model->sku_exists($sku, $id))
            {
                $error = 'SKU đã tồn tại.';
            }
            else
            {
                $image = $this->_handle_image_upload($error, $item['image']);

                if ( ! $error)
                {
                    $this->Inventory_item_model->update($id, $this->_form_data($sku, $image));
                    $this->audit('inventory_item', 'UPDATE', $item, array('id' => $id));
                    redirect('me/inventory/items');
                    return;
                }
            }
        }

        $data = array(
            'page_title'   => 'Sửa sản phẩm kho',
            'current_user' => $this->current_user,
            'item'         => $item,
            'categories'   => $this->Inventory_category_model->get_active(),
            'units'        => $this->Inventory_unit_model->get_active(),
            'error'        => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_items/form', $data);
        $this->load->view('layout/footer');
    }

    public function delete($id)
    {
        $this->_require_admin();
        $item = $this->Inventory_item_model->get_by_id($id);
        $this->Inventory_item_model->delete($id);
        $this->audit('inventory_item', 'DELETE', $item, NULL);
        redirect('me/inventory/items');
    }

    /** JSON — ô tìm sản phẩm ở màn Nhập/Xuất kho (mọi role trong $allowed_roles). */
    public function search()
    {
        $q = trim($this->input->get('q'));
        $this->output->set_content_type('application/json');
        echo json_encode(strlen($q) > 0 ? $this->Inventory_item_model->search($q) : array());
    }

    /**
     * JSON — sản phẩm còn hoạt động cho màn Nhập/Xuất kho. category_id để
     * trống/0 = tất cả danh mục; q lọc theo SKU/tên.
     */
    public function by_category()
    {
        $category_id = (int) $this->input->get('category_id');
        $keyword = trim((string) $this->input->get('q'));
        $this->output->set_content_type('application/json');
        echo json_encode($this->Inventory_item_model->get_by_category($category_id ?: NULL, $keyword ?: NULL));
    }

    /** JSON — gợi ý SKU tự sinh theo danh mục, chỉ để hiển thị trước khi lưu (ADMIN, dùng ở form thêm mới). */
    public function next_sku()
    {
        $this->output->set_content_type('application/json');
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            echo json_encode(array('error' => 'forbidden'));
            return;
        }

        $category_id = (int) $this->input->get('category_id');
        echo json_encode(array('sku' => $category_id ? $this->Inventory_item_model->generate_sku($category_id) : ''));
    }

    public function import()
    {
        $this->_require_admin();

        $categories = $this->Inventory_category_model->get_active();
        $cat_by_name = array();
        foreach ($categories as $c)
        {
            $cat_by_name[mb_strtolower($c['name'])] = $c['id'];
        }

        $units = $this->Inventory_unit_model->get_active();
        $unit_by_name = array();
        foreach ($units as $u)
        {
            $unit_by_name[mb_strtolower($u['name'])] = $u['id'];
        }

        $preview = NULL;
        $error = NULL;

        if ($this->input->method() === 'post' && $this->input->post('rows_json') !== NULL)
        {
            $rows = json_decode($this->input->post('rows_json'), TRUE);
            $created = 0;
            $updated = 0;

            if (is_array($rows))
            {
                foreach ($rows as $row)
                {
                    $existing = $this->Inventory_item_model->get_by_sku($row['sku']);
                    $item_data = array(
                        'category_id'         => (int) $row['category_id'],
                        'sku'                 => $row['sku'],
                        'name'                => $row['name'],
                        'unit_id'             => (int) $row['unit_id'],
                        'storage_type'        => $row['storage_type'],
                        'low_stock_threshold' => (float) $row['low_stock_threshold'],
                        'status'              => 'ACTIVE',
                    );
                    if ($existing)
                    {
                        $this->Inventory_item_model->update($existing['id'], $item_data);
                        $updated++;
                    }
                    else
                    {
                        $this->Inventory_item_model->create($item_data);
                        $created++;
                    }
                }
            }

            $this->audit('inventory_item', 'IMPORT', NULL, array('created' => $created, 'updated' => $updated));
            $this->session->set_flashdata('success', 'Đã thêm mới '.$created.' sản phẩm, cập nhật '.$updated.' sản phẩm.');
            redirect('me/inventory/items');
            return;
        }

        if ($this->input->method() === 'post' && ! empty($_FILES['file']['name']))
        {
            try
            {
                $preview = $this->_parse_import_file($_FILES['file'], $cat_by_name, $unit_by_name);
            }
            catch (Exception $e)
            {
                $error = $e->getMessage();
            }
        }

        $data = array(
            'page_title'   => 'Import sản phẩm kho',
            'current_user' => $this->current_user,
            'preview'      => $preview,
            'error'        => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('inventory_items/import', $data);
        $this->load->view('layout/footer');
    }

    public function import_template()
    {
        $this->_require_admin();

        $this->output->set_content_type('text/csv');
        header('Content-Disposition: attachment; filename="mau_import_san_pham_kho.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array('SKU (để trống sẽ tự sinh)', 'Tên sản phẩm', 'Danh mục', 'Đơn vị tính', 'Bảo quản (COLD/DRY)', 'Ngưỡng cảnh báo'));
        fputcsv($out, array('', 'Cà phê hạt Robusta', 'Pha Chế', 'Kg', 'DRY', '5'));
        fputcsv($out, array('', 'Sữa tươi tiệt trùng', 'Pha Chế', 'Lít', 'COLD', '10'));
        fclose($out);
    }

    private function _form_data($sku, $image)
    {
        $base_unit = $this->input->post('base_unit');
        $base_unit = in_array($base_unit, array('ml', 'g', 'cái', 'lát', 'lá', 'ly', 'gói'), TRUE) ? $base_unit : NULL;
        $base_unit_cost = $this->input->post('base_unit_cost');

        return array(
            'category_id'         => (int) $this->input->post('category_id'),
            'sku'                 => $sku,
            'name'                => $this->input->post('name', TRUE),
            'image'               => $image,
            'unit_id'             => (int) $this->input->post('unit_id'),
            'base_unit'           => $base_unit,
            'base_unit_cost'      => ($base_unit && $base_unit_cost !== '') ? (float) $base_unit_cost : NULL,
            'storage_type'        => $this->input->post('storage_type') === 'COLD' ? 'COLD' : 'DRY',
            'low_stock_threshold' => (float) $this->input->post('low_stock_threshold'),
            'status'              => $this->input->post('status') ?: 'ACTIVE',
        );
    }

    /**
     * Xử lý ảnh sản phẩm kho (tùy chọn) — trả về đường dẫn tương đối để lưu
     * vào inventory_items.image, hoặc giữ nguyên $existing_image nếu không
     * chọn file mới. Set $error (tham chiếu) và trả về NULL nếu upload lỗi.
     * Cùng quy ước với Products::_handle_image_upload().
     */
    private function _handle_image_upload(&$error, $existing_image = NULL)
    {
        if (empty($_FILES['image']['name']))
        {
            return $existing_image;
        }

        $upload_dir = FCPATH.'assets/uploads/inventory_items/';
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

        return 'uploads/inventory_items/'.$this->upload->data('file_name');
    }

    /**
     * Đọc file upload (.xlsx/.csv), trả về mảng ['valid' => [...], 'invalid' => [...]].
     * Cột: SKU (để trống sẽ tự sinh), Tên sản phẩm, Danh mục, Đơn vị tính, Bảo quản, Ngưỡng cảnh báo.
     */
    private function _parse_import_file($file, $cat_by_name, $unit_by_name)
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ( ! in_array($ext, array('xlsx', 'csv'), TRUE))
        {
            throw new Exception('Chỉ hỗ trợ file .xlsx hoặc .csv.');
        }

        // tmp_name do PHP tạo không có đuôi file — Xlsx_reader cần đuôi đúng
        // để biết cách đọc, nên copy sang 1 file tạm có đuôi phù hợp.
        $tmp_with_ext = $file['tmp_name'].'.'.$ext;
        copy($file['tmp_name'], $tmp_with_ext);

        $this->load->library('xlsx_reader');
        $rows = $this->xlsx_reader->read($tmp_with_ext);
        @unlink($tmp_with_ext);

        if (count($rows) > 0)
        {
            array_shift($rows); // bỏ dòng tiêu đề
        }

        $preview = array('valid' => array(), 'invalid' => array());
        $seen_skus = array();

        foreach ($rows as $i => $r)
        {
            $line = $i + 2;
            $sku = isset($r[0]) ? trim($r[0]) : '';
            $name = isset($r[1]) ? trim($r[1]) : '';
            $cat_name = isset($r[2]) ? trim($r[2]) : '';
            $unit_name = isset($r[3]) ? trim($r[3]) : '';
            $storage = isset($r[4]) ? strtoupper(trim($r[4])) : 'DRY';
            $threshold = isset($r[5]) ? trim($r[5]) : '0';

            if ($sku === '' && $name === '')
            {
                continue; // dòng trống
            }

            $category_id = isset($cat_by_name[mb_strtolower($cat_name)]) ? $cat_by_name[mb_strtolower($cat_name)] : NULL;

            // SKU để trống sẽ tự sinh theo danh mục ngay khi xem trước, để
            // hiển thị cho người dùng biết mã sẽ được lưu là gì. Loại trừ các
            // SKU đã sinh trong chính file này (chưa có trong DB nên
            // sku_exists() một mình không phát hiện được trùng).
            if ($sku === '' && $category_id)
            {
                $sku = $this->Inventory_item_model->generate_sku($category_id, array_keys($seen_skus));
            }

            $errors = array();
            if ($sku === '') $errors[] = 'Thiếu SKU và không xác định được danh mục để tự sinh';
            if ($name === '') $errors[] = 'Thiếu tên sản phẩm';
            if ($unit_name === '') $errors[] = 'Thiếu đơn vị tính';
            elseif ( ! isset($unit_by_name[mb_strtolower($unit_name)])) $errors[] = 'Không tìm thấy đơn vị tính "'.$unit_name.'"';
            if ( ! $category_id) $errors[] = 'Không tìm thấy danh mục "'.$cat_name.'"';
            if ( ! in_array($storage, array('COLD', 'DRY'), TRUE)) $errors[] = 'Bảo quản phải là COLD hoặc DRY';
            if ($threshold === '' || ! is_numeric($threshold) || $threshold < 0) $errors[] = 'Ngưỡng cảnh báo không hợp lệ';
            if ($sku !== '' && isset($seen_skus[$sku])) $errors[] = 'SKU trùng với dòng '.$seen_skus[$sku].' trong file';

            if (count($errors) > 0)
            {
                $preview['invalid'][] = array('line' => $line, 'sku' => $sku, 'name' => $name, 'errors' => $errors);
                continue;
            }

            $seen_skus[$sku] = $line;
            $preview['valid'][] = array(
                'line'                => $line,
                'sku'                 => $sku,
                'name'                => $name,
                'category_id'         => $category_id,
                'category_name'       => $cat_name,
                'unit_id'             => $unit_by_name[mb_strtolower($unit_name)],
                'unit_name'           => $unit_name,
                'storage_type'        => $storage,
                'low_stock_threshold' => (float) $threshold,
            );
        }

        return $preview;
    }
}
