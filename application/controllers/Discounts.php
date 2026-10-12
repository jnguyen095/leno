<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản trị → Khuyến mãi (chỉ ADMIN): giảm trên tổng đơn — % hoặc số tiền, cho đơn từ một giá trị tối thiểu,
 * theo khoảng ngày + thứ trong tuần. Trên POS người bán tự chọn cho đơn (Pos_service::set_discount).
 */
class Discounts extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            echo $this->load->view('errors/forbidden', array('current_user' => $this->current_user), TRUE);
            exit;
        }
        $this->load->model(array('Discount_model', 'Price_book_model'));
    }

    public function index()
    {
        $data = array(
            'page_title'   => 'Khuyến mãi',
            'current_user' => $this->current_user,
            'discounts'    => $this->Discount_model->get_all(),
            'applicable'   => array_column($this->Discount_model->get_applicable(), 'id'),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('discounts/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        $this->_form(NULL);
    }

    public function edit($id)
    {
        $discount = $this->Discount_model->get_by_id($id);
        if ( ! $discount) show_404();
        $this->_form($discount);
    }

    public function toggle($id)
    {
        $d = $this->_post_discount($id);
        $status = $d['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $this->Discount_model->set_status($d['id'], $status);
        $this->audit('discount', 'TOGGLE', array('status' => $d['status']), array('id' => (int) $d['id'], 'status' => $status));
        $this->session->set_flashdata('success', ($status === 'ACTIVE' ? 'Đã bật' : 'Đã tắt').' khuyến mãi "'.$d['name'].'".');
        redirect('me/discounts');
    }

    public function delete($id)
    {
        $d = $this->_post_discount($id);
        $this->Discount_model->delete($d['id']);
        $this->audit('discount', 'DELETE', $d, NULL);
        $this->session->set_flashdata('success', 'Đã xoá khuyến mãi "'.$d['name'].'". Đơn đã áp khuyến mãi này vẫn giữ nguyên.');
        redirect('me/discounts');
    }

    private function _post_discount($id)
    {
        if ($this->input->method() !== 'post') show_404();
        $d = $this->Discount_model->get_by_id($id);
        if ( ! $d) show_404();
        return $d;
    }

    private function _form($discount)
    {
        $error = NULL;
        $money = function ($v) { return $v === NULL || $v === '' ? '' : number_format((float) $v, 0, ',', '.'); };
        $values = array(
            'name'          => $discount ? $discount['name'] : '',
            'discount_type' => $discount ? $discount['discount_type'] : 'PERCENT',
            'value'         => $discount ? ($discount['discount_type'] === 'PERCENT' ? rtrim(rtrim((string) (float) $discount['value'], '0'), '.') : $money($discount['value'])) : '',
            'max_discount'  => $discount ? $money($discount['max_discount']) : '',
            'min_order'     => $discount && (float) $discount['min_order'] > 0 ? $money($discount['min_order']) : '',
            'start_date'    => $discount ? $discount['start_date'] : date('Y-m-d'),
            'end_date'      => $discount ? (string) $discount['end_date'] : '',
            'weekdays'      => $discount ? array_map('intval', explode(',', $discount['weekdays'])) : array_keys(Price_book_model::WEEKDAYS),
            'status'        => $discount ? $discount['status'] : 'ACTIVE',
        );

        if ($this->input->method() === 'post')
        {
            $values = array(
                'name'          => trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('name', TRUE))),
                'discount_type' => $this->input->post('discount_type') === 'AMOUNT' ? 'AMOUNT' : 'PERCENT',
                'value'         => trim((string) $this->input->post('value')),
                'max_discount'  => trim((string) $this->input->post('max_discount')),
                'min_order'     => trim((string) $this->input->post('min_order')),
                'start_date'    => (string) $this->input->post('start_date'),
                'end_date'      => (string) $this->input->post('end_date'),
                'weekdays'      => array_values(array_intersect(array_keys(Price_book_model::WEEKDAYS), array_map('intval', (array) $this->input->post('weekdays')))),
                'status'        => $this->input->post('status') ? 'ACTIVE' : 'INACTIVE',
            );
            // Số tiền: bỏ dấu chấm / khoảng trắng ("100.000" -> 100000). Phần trăm: cho phép số thập phân (7,5).
            $amount = function ($s) { $s = preg_replace('/[\s.,]/', '', $s); return $s === '' ? NULL : (ctype_digit($s) ? (float) $s : FALSE); };
            $is_pct = $values['discount_type'] === 'PERCENT';
            $value = $is_pct ? (is_numeric(str_replace(',', '.', $values['value'])) ? (float) str_replace(',', '.', $values['value']) : FALSE) : $amount($values['value']);
            $max = $is_pct ? $amount($values['max_discount']) : NULL;
            $min = $amount($values['min_order']);

            $valid_date = function ($d) { $t = DateTime::createFromFormat('Y-m-d', $d); return $t && $t->format('Y-m-d') === $d; };
            if ($values['name'] === '')                                    $error = 'Nhập tên khuyến mãi.';
            elseif (mb_strlen($values['name'], 'UTF-8') > 100)             $error = 'Tên khuyến mãi tối đa 100 ký tự.';
            elseif ($value === FALSE || $value === NULL || $value <= 0)    $error = $is_pct ? 'Nhập phần trăm giảm (lớn hơn 0).' : 'Nhập số tiền giảm (lớn hơn 0).';
            elseif ($is_pct && $value > 100)                               $error = 'Phần trăm giảm tối đa 100%.';
            elseif ($max === FALSE)                                        $error = 'Giảm tối đa chỉ gồm chữ số, để trống nếu không giới hạn.';
            elseif ($min === FALSE)                                        $error = 'Giá trị đơn tối thiểu chỉ gồm chữ số, để trống nếu áp cho mọi đơn.';
            elseif ( ! $valid_date($values['start_date']))                 $error = 'Ngày bắt đầu không hợp lệ.';
            elseif ($values['end_date'] !== '' && ! $valid_date($values['end_date'])) $error = 'Ngày kết thúc không hợp lệ.';
            elseif ($values['end_date'] !== '' && $values['end_date'] < $values['start_date']) $error = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
            elseif ( ! $values['weekdays'])                                $error = 'Chọn ít nhất một thứ áp dụng.';
            else
            {
                $row = array(
                    'name'          => $values['name'],
                    'discount_type' => $values['discount_type'],
                    'value'         => $value,
                    'max_discount'  => $max ?: NULL,
                    'min_order'     => $min ?: 0,
                    'start_date'    => $values['start_date'],
                    'end_date'      => $values['end_date'] !== '' ? $values['end_date'] : NULL,
                    'weekdays'      => implode(',', $values['weekdays']),
                    'status'        => $values['status'],
                );
                $id = $this->Discount_model->save($discount ? $discount['id'] : NULL, $row, $this->current_user['id']);
                $this->audit('discount', $discount ? 'UPDATE' : 'CREATE', $discount, array('id' => $id) + $row);
                $this->session->set_flashdata('success', 'Đã lưu khuyến mãi "'.$values['name'].'".');
                redirect('me/discounts');
                return;
            }
        }

        $data = array(
            'page_title'   => $discount ? 'Sửa khuyến mãi' : 'Thêm khuyến mãi',
            'current_user' => $this->current_user,
            'discount'     => $discount,
            'values'       => $values,
            'error'        => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('discounts/form', $data);
        $this->load->view('layout/footer');
    }
}
