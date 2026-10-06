<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Product_model', 'Category_model', 'Inventory_category_model'));
    }

    public function index()
    {
        $status = $this->input->get('status');
        $filters = array(
            'category_id' => (int) $this->input->get('category_id'),
            'q'           => trim((string) $this->input->get('q', TRUE)),
            'status'      => in_array($status, array('ACTIVE', 'INACTIVE'), TRUE) ? $status : '',
        );

        $data = array(
            'page_title'   => 'Sản phẩm',
            'current_user' => $this->current_user,
            'products'     => $this->Product_model->get_all($filters),
            'categories'   => $this->Category_model->get_active(),
            'filters'      => $filters,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('products/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            // SKU tự sinh theo danh mục (CPE-01, CPE-02...): nếu người dùng giữ nguyên SKU gợi ý
            // (sku_auto=1) hoặc để trống thì sinh lại ngay lúc lưu, để 2 người thêm cùng lúc không trùng.
            $sku = trim((string) $this->input->post('sku', TRUE));
            if ($this->input->post('sku_auto') === '1' || $sku === '')
            {
                $sku = $this->Product_model->generate_sku((int) $this->input->post('category_id'));
            }
            elseif ($this->Product_model->sku_exists($sku))
            {
                $error = 'Mã SKU "'.htmlspecialchars($sku).'" đã tồn tại.';
            }

            if ( ! $error)
            {
                $image = $this->_handle_image_upload($error);
            }

            if ( ! $error)
            {
                $data = $this->_form_data($image);
                $data['sku'] = $sku;
                $id = $this->Product_model->create($data);
                $this->audit('product', 'CREATE', NULL, array('id' => $id, 'sku' => $sku));
                redirect('me/products');
                return;
            }
        }

        $categories = $this->Category_model->get_active();
        $data = array(
            'page_title'          => 'Thêm sản phẩm',
            'current_user'        => $this->current_user,
            'product'             => NULL,
            'categories'          => $categories,
            'inventory_categories' => $this->Inventory_category_model->get_active(),
            'suggested_sku'       => $categories ? $this->Product_model->generate_sku($categories[0]['id']) : '',
            'error'               => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('products/form', $data);
        $this->load->view('layout/footer');
    }

    /** JSON: SKU gợi ý kế tiếp cho danh mục đang chọn ở form Thêm sản phẩm. */
    public function next_sku()
    {
        $category_id = (int) $this->input->get('category_id');
        json_response(array('sku' => $category_id ? $this->Product_model->generate_sku($category_id) : ''));
    }

    public function edit($id)
    {
        $product = $this->Product_model->get_by_id($id);
        if ( ! $product) show_404();
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $image = $this->_handle_image_upload($error, $product['image']);

            if ( ! $error)
            {
                $this->Product_model->update($id, $this->_form_data($image));
                $this->audit('product', 'UPDATE', $product, array('id' => $id));
                redirect('me/products');
                return;
            }
        }

        $data = array(
            'page_title'          => 'Sửa sản phẩm',
            'current_user'        => $this->current_user,
            'product'             => $product,
            'categories'          => $this->Category_model->get_active(),
            'inventory_categories' => $this->Inventory_category_model->get_active(),
            'error'               => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('products/form', $data);
        $this->load->view('layout/footer');
    }

    public function delete($id)
    {
        $product = $this->Product_model->get_by_id($id);
        $this->Product_model->delete($id);
        $this->audit('product', 'DELETE', $product, NULL);
        redirect('me/products');
    }

    public function import()
    {
        $categories = $this->Category_model->get_active();
        $cat_by_name = array();
        foreach ($categories as $c)
        {
            $cat_by_name[mb_strtolower($c['name'])] = $c['id'];
        }

        $inv_categories = $this->Inventory_category_model->get_active();
        $inv_cat_by_name = array();
        foreach ($inv_categories as $c)
        {
            $inv_cat_by_name[mb_strtolower($c['name'])] = $c['id'];
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
                    $existing = $this->Product_model->get_by_sku($row['sku']);
                    $product_data = array(
                        'category_id'           => (int) $row['category_id'],
                        'sku'                   => $row['sku'],
                        'product_name'          => $row['product_name'],
                        'price'                 => (float) $row['price'],
                        'description'           => $row['description'],
                        'inventory_category_id' => $row['inventory_category_id'] ? (int) $row['inventory_category_id'] : NULL,
                        'track_inventory'       => $row['track_inventory'] ? 1 : 0,
                        'status'                => 'ACTIVE',
                    );
                    if ($existing)
                    {
                        // Import không đụng tới ảnh sản phẩm hiện có (Excel không mang được file ảnh).
                        $this->Product_model->update($existing['id'], $product_data);
                        $updated++;
                    }
                    else
                    {
                        $product_data['image'] = NULL;
                        $this->Product_model->create($product_data);
                        $created++;
                    }
                }
            }

            $this->audit('product', 'IMPORT', NULL, array('created' => $created, 'updated' => $updated));
            $this->session->set_flashdata('success', 'Đã thêm mới '.$created.' sản phẩm, cập nhật '.$updated.' sản phẩm.');
            redirect('me/products');
            return;
        }

        if ($this->input->method() === 'post' && ! empty($_FILES['file']['name']))
        {
            try
            {
                $preview = $this->_parse_import_file($_FILES['file'], $cat_by_name, $inv_cat_by_name);
            }
            catch (Exception $e)
            {
                $error = $e->getMessage();
            }
        }

        $data = array(
            'page_title'   => 'Import sản phẩm',
            'current_user' => $this->current_user,
            'preview'      => $preview,
            'error'        => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('products/import', $data);
        $this->load->view('layout/footer');
    }

    public function import_template()
    {
        $this->output->set_content_type('text/csv');
        header('Content-Disposition: attachment; filename="mau_import_san_pham.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array('SKU', 'Tên sản phẩm', 'Danh mục', 'Giá', 'Mô tả', 'Danh mục kho (tuỳ chọn)', 'Quản lý kho (YES/NO)', 'Trạng thái'));
        fputcsv($out, array('CF001', 'Cà phê đen đá', 'Cà phê', '25000', 'Cà phê phin truyền thống', 'Pha Chế', 'YES', 'ACTIVE'));
        fputcsv($out, array('CK001', 'Bánh croissant', 'Bánh ngọt', '25000', 'Bơ Pháp giòn xốp', '', 'NO', 'ACTIVE'));
        fclose($out);
    }

    /**
     * Đọc file upload (.xlsx/.csv), trả về mảng ['valid' => [...], 'invalid' => [...]].
     * Cột: SKU, Tên sản phẩm, Danh mục, Giá, Mô tả, Danh mục kho, Quản lý kho, Trạng thái.
     */
    private function _parse_import_file($file, $cat_by_name, $inv_cat_by_name)
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
            $price = isset($r[3]) ? trim($r[3]) : '';
            $description = isset($r[4]) ? trim($r[4]) : '';
            $inv_cat_name = isset($r[5]) ? trim($r[5]) : '';
            $track_inventory_raw = isset($r[6]) ? strtoupper(trim($r[6])) : 'NO';
            $status = isset($r[7]) ? strtoupper(trim($r[7])) : 'ACTIVE';

            if ($sku === '' && $name === '')
            {
                continue; // dòng trống
            }

            $category_id = isset($cat_by_name[mb_strtolower($cat_name)]) ? $cat_by_name[mb_strtolower($cat_name)] : NULL;
            $inventory_category_id = NULL;

            $errors = array();
            if ($sku === '') $errors[] = 'Thiếu SKU';
            if ($name === '') $errors[] = 'Thiếu tên sản phẩm';
            if ( ! $category_id) $errors[] = 'Không tìm thấy danh mục "'.$cat_name.'"';
            if ($price === '' || ! is_numeric($price) || $price < 0) $errors[] = 'Giá không hợp lệ';
            if ($inv_cat_name !== '')
            {
                if (isset($inv_cat_by_name[mb_strtolower($inv_cat_name)]))
                {
                    $inventory_category_id = $inv_cat_by_name[mb_strtolower($inv_cat_name)];
                }
                else
                {
                    $errors[] = 'Không tìm thấy danh mục kho "'.$inv_cat_name.'"';
                }
            }
            if ( ! in_array($track_inventory_raw, array('YES', 'NO', ''), TRUE)) $errors[] = 'Quản lý kho phải là YES hoặc NO';
            if ( ! in_array($status, array('ACTIVE', 'INACTIVE'), TRUE)) $errors[] = 'Trạng thái phải là ACTIVE hoặc INACTIVE';
            if ($sku !== '' && isset($seen_skus[$sku])) $errors[] = 'SKU trùng với dòng '.$seen_skus[$sku].' trong file';

            if (count($errors) > 0)
            {
                $preview['invalid'][] = array('line' => $line, 'sku' => $sku, 'name' => $name, 'errors' => $errors);
                continue;
            }

            $seen_skus[$sku] = $line;
            $preview['valid'][] = array(
                'line'                  => $line,
                'sku'                   => $sku,
                'product_name'          => $name,
                'category_id'           => $category_id,
                'category_name'         => $cat_name,
                'price'                 => (float) $price,
                'description'           => $description,
                'inventory_category_id' => $inventory_category_id,
                'inventory_category_name' => $inv_cat_name,
                'track_inventory'       => $track_inventory_raw === 'YES',
                'status'                => $status,
            );
        }

        return $preview;
    }

    private function _form_data($image)
    {
        $inventory_category_id = (int) $this->input->post('inventory_category_id');

        return array(
            'category_id'           => (int) $this->input->post('category_id'),
            'sku'                   => $this->input->post('sku', TRUE),
            'product_name'          => $this->input->post('product_name', TRUE),
            'price'                 => (float) $this->input->post('price'),
            'description'           => $this->input->post('description', TRUE),
            'status'                => $this->input->post('status') ?: 'ACTIVE',
            'image'                 => $image,
            'inventory_category_id' => $inventory_category_id ?: NULL,
            'track_inventory'       => $this->input->post('track_inventory') ? 1 : 0,
        );
    }

    /**
     * Handles the optional product image upload. Returns the relative path to
     * store in products.image, or the existing path unchanged when no new
     * file was chosen. Sets $error (by reference) and returns NULL on failure.
     */
    private function _handle_image_upload(&$error, $existing_image = NULL)
    {
        if (empty($_FILES['image']['name']))
        {
            return $existing_image;
        }

        $upload_dir = FCPATH.'assets/uploads/products/';
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

        return 'uploads/products/'.$this->upload->data('file_name');
    }
}
