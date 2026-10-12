<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản trị → Bảng giá (chỉ ADMIN): giá bán riêng theo dịp, áp theo khoảng ngày + thứ trong tuần.
 * Trên POS người bán tự chọn bảng giá cho đơn (Pos_service::set_price_book) — không tự áp.
 */
class Price_books extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        // Luôn chỉ ADMIN, kể cả khi lỡ đưa vào RBAC động (giống Quản lý bàn).
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            exit;
        }
        $this->load->model(array('Price_book_model', 'Product_model'));
    }

    public function index()
    {
        $data = array(
            'page_title'   => 'Bảng giá',
            'current_user' => $this->current_user,
            'books'        => $this->Price_book_model->get_all(),
            'applicable'   => array_column($this->Price_book_model->get_applicable(), 'id'),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('price_books/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        $this->_form(NULL);
    }

    public function edit($id)
    {
        $book = $this->Price_book_model->get_by_id($id);
        if ( ! $book) show_404();
        $this->_form($book);
    }

    public function toggle($id)
    {
        $book = $this->_post_book($id);
        $status = $book['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $this->Price_book_model->set_status($book['id'], $status);
        $this->audit('price_book', 'TOGGLE', array('status' => $book['status']), array('id' => (int) $book['id'], 'status' => $status));
        $this->session->set_flashdata('success', ($status === 'ACTIVE' ? 'Đã bật' : 'Đã tắt').' bảng giá "'.$book['name'].'".');
        redirect('me/price-books');
    }

    public function delete($id)
    {
        $book = $this->_post_book($id);
        $this->Price_book_model->delete($book['id']);
        $this->audit('price_book', 'DELETE', $book, NULL);
        $this->session->set_flashdata('success', 'Đã xoá bảng giá "'.$book['name'].'". Đơn đã bán theo bảng giá này vẫn giữ nguyên giá.');
        redirect('me/price-books');
    }

    private function _post_book($id)
    {
        if ($this->input->method() !== 'post') show_404();
        $book = $this->Price_book_model->get_by_id($id);
        if ( ! $book) show_404();
        return $book;
    }

    /** Form thêm / sửa: thông tin bảng giá + bảng giá mới từng món (để trống = giữ giá gốc). */
    private function _form($book)
    {
        $error = NULL;
        $products_by_category = $this->Product_model->get_active_grouped_by_category();
        $prices = $book ? $this->Price_book_model->get_prices($book['id']) : array();
        $values = array(
            'name'       => $book ? $book['name'] : '',
            'start_date' => $book ? $book['start_date'] : date('Y-m-d'),
            'end_date'   => $book ? (string) $book['end_date'] : '',
            'weekdays'   => $book ? array_map('intval', explode(',', $book['weekdays'])) : array_keys(Price_book_model::WEEKDAYS),
            'status'     => $book ? $book['status'] : 'ACTIVE',
        );

        if ($this->input->method() === 'post')
        {
            $values = array(
                'name'       => trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('name', TRUE))),
                'start_date' => (string) $this->input->post('start_date'),
                'end_date'   => (string) $this->input->post('end_date'),
                'weekdays'   => array_values(array_intersect(array_keys(Price_book_model::WEEKDAYS), array_map('intval', (array) $this->input->post('weekdays')))),
                'status'     => $this->input->post('status') ? 'ACTIVE' : 'INACTIVE',
            );

            // Giá mới: chỉ các món đang hiện trên form; bỏ dấu chấm/phẩy/khoảng trắng ("25.000" -> 25000).
            $posted = (array) $this->input->post('prices');
            $prices = array();
            $bad_price = FALSE;
            foreach ($products_by_category as $products)
            {
                foreach ($products as $p)
                {
                    $raw = isset($posted[$p['id']]) ? preg_replace('/[\s.,]/', '', (string) $posted[$p['id']]) : '';
                    if ($raw !== '' && ! ctype_digit($raw)) $bad_price = TRUE;
                    $prices[(int) $p['id']] = $raw === '' ? NULL : (float) $raw;
                }
            }

            $valid_date = function ($d) { $t = DateTime::createFromFormat('Y-m-d', $d); return $t && $t->format('Y-m-d') === $d; };
            if ($values['name'] === '')                                   $error = 'Nhập tên bảng giá.';
            elseif (mb_strlen($values['name'], 'UTF-8') > 100)            $error = 'Tên bảng giá tối đa 100 ký tự.';
            elseif ( ! $valid_date($values['start_date']))                $error = 'Ngày bắt đầu không hợp lệ.';
            elseif ($values['end_date'] !== '' && ! $valid_date($values['end_date'])) $error = 'Ngày kết thúc không hợp lệ.';
            elseif ($values['end_date'] !== '' && $values['end_date'] < $values['start_date']) $error = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
            elseif ( ! $values['weekdays'])                               $error = 'Chọn ít nhất một thứ áp dụng.';
            elseif ($bad_price)                                           $error = 'Giá mới chỉ gồm chữ số (vd 25000), để trống nếu giữ giá gốc.';
            elseif ( ! array_filter($prices, function ($v) { return $v !== NULL; })) $error = 'Nhập giá mới cho ít nhất một món.';
            else
            {
                $id = $this->Price_book_model->save($book ? $book['id'] : NULL, array(
                    'name'       => $values['name'],
                    'start_date' => $values['start_date'],
                    'end_date'   => $values['end_date'] !== '' ? $values['end_date'] : NULL,
                    'weekdays'   => implode(',', $values['weekdays']),
                    'status'     => $values['status'],
                ), $prices, $this->current_user['id']);
                $this->audit('price_book', $book ? 'UPDATE' : 'CREATE', $book, array('id' => $id, 'name' => $values['name'],
                    'items' => count(array_filter($prices, function ($v) { return $v !== NULL; }))));
                $this->session->set_flashdata('success', 'Đã lưu bảng giá "'.$values['name'].'".');
                redirect('me/price-books');
                return;
            }
        }

        $data = array(
            'page_title'           => $book ? 'Sửa bảng giá' : 'Thêm bảng giá',
            'current_user'         => $this->current_user,
            'book'                 => $book,
            'values'               => $values,
            'prices'               => $prices,
            'products_by_category' => $products_by_category,
            'error'                => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('price_books/form', $data);
        $this->load->view('layout/footer');
    }
}
