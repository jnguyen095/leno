<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sổ quỹ: lập phiếu Thu / Chi (hình thức Tiền mặt / Chuyển khoản, loại thu / chi, người nộp / nhận, nhân viên thu / chi,
 * số tiền, ghi chú) + tiền bán hàng tự động từ đơn đã thanh toán (chỉ xem). Tồn quỹ đầu kỳ / cuối kỳ theo hình thức.
 * Quyền: ADMIN, hoặc vai trò được gán menu "Sổ quỹ"; sửa / xoá phiếu và quản lý loại thu chi: chỉ ADMIN.
 */
class Cashbook extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    const PER_PAGE = 30;
    const EXPORT_LIMIT = 20000;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Cashbook_model', 'User_model'));
    }

    // ---------- Sổ quỹ ----------

    public function index()
    {
        list($filters, $range_key) = $this->_filters();

        $total = $this->Cashbook_model->count_list($filters);
        $total_pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($total_pages, (int) $this->input->get('page')));

        $totals = $this->Cashbook_model->totals($filters);
        $opening = $this->Cashbook_model->opening_balance($filters);
        // Tồn cuối kỳ = đầu kỳ + toàn bộ thu - chi trong kỳ (không theo bộ lọc loại / danh mục).
        $period = $this->Cashbook_model->totals(array_intersect_key($filters, array_flip(array('date_from', 'date_to', 'method', 'handled_by'))));
        $closing = array();
        foreach (array('CASH', 'TRANSFER', 'ALL') as $m) $closing[$m] = $opening[$m] + $period['IN'][$m] - $period['OUT'][$m];

        $data = array(
            'page_title'     => 'Sổ quỹ',
            'current_user'   => $this->current_user,
            'is_admin'       => $this->current_user['role'] === 'ADMIN',
            'filters'        => $filters,
            'range_key'      => $range_key,
            'entries'        => $this->Cashbook_model->get_list($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'totals'         => $totals,
            'opening'        => $opening,
            'closing'        => $closing,
            'categories_in'  => $this->Cashbook_model->get_categories('IN'),
            'categories_out' => $this->Cashbook_model->get_categories('OUT'),
            'users'          => $this->User_model->get_all(NULL, 'ACTIVE'),
            'counterparties' => $this->Cashbook_model->get_counterparties(),
            'page'           => $page,
            'total_pages'    => $total_pages,
            'total'          => $total,
            'per_page'       => self::PER_PAGE,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('cashbook/index', $data);
        $this->load->view('layout/footer');
    }

    /** POST — lập phiếu thu / chi mới (id rỗng), hoặc sửa phiếu (id, chỉ ADMIN). */
    public function save()
    {
        if ($this->input->method() !== 'post') show_404();
        $id = (int) $this->input->post('id');
        $existing = NULL;
        if ($id)
        {
            $this->_require_admin();
            $existing = $this->Cashbook_model->get_entry($id);
            if ( ! $existing) show_404();
        }

        $type = $existing ? $existing['type'] : ($this->input->post('type') === 'OUT' ? 'OUT' : 'IN');
        $label = $type === 'IN' ? 'thu' : 'chi';
        $method = (string) $this->input->post('payment_method');
        $category_id = (int) $this->input->post('category_id');
        $category = $category_id ? $this->Cashbook_model->get_category($category_id) : NULL;
        $amount_raw = preg_replace('/[\s.,]/', '', (string) $this->input->post('amount'));
        $amount = ctype_digit($amount_raw) ? (float) $amount_raw : 0;
        $handled_by = (int) $this->input->post('handled_by');
        $date = (string) $this->input->post('entry_date');
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $date);
        $clean = function ($field, $max) { return mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $this->input->post($field, TRUE))), 0, $max, 'UTF-8'); };

        $error = NULL;
        if ( ! array_key_exists($method, payment_methods()))                 $error = 'Chọn hình thức (Tiền mặt / Chuyển khoản).';
        elseif ( ! $category || $category['type'] !== $type)                 $error = 'Chọn loại '.$label.'.';
        elseif ($amount <= 0)                                                 $error = 'Nhập số tiền '.$label.' (lớn hơn 0).';
        elseif ($amount > 999999999999)                                       $error = 'Số tiền quá lớn.';
        elseif ( ! $dt)                                                       $error = 'Thời gian không hợp lệ.';
        elseif ($handled_by && ! $this->User_model->get_by_id($handled_by))   $error = 'Nhân viên '.$label.' không hợp lệ.';
        if ($error)
        {
            $this->session->set_flashdata('error', $error);
            redirect($this->_back_url());
            return;
        }

        $row = array(
            'type'           => $type,
            'entry_date'     => $dt->format('Y-m-d H:i:00'),
            'payment_method' => $method,
            'category_id'    => (int) $category['id'],
            'counterparty'   => $clean('counterparty', 150) ?: NULL,
            'handled_by'     => $handled_by ?: (int) $this->current_user['id'],
            'amount'         => $amount,
            'note'           => $clean('note', 255) ?: NULL,
        );
        if ($existing)
        {
            $this->Cashbook_model->update_entry($id, $row);
            $this->audit('cashbook', 'UPDATE', $existing, array('id' => $id) + $row);
            $this->session->set_flashdata('success', 'Đã cập nhật phiếu '.$existing['entry_no'].'.');
        }
        else
        {
            $new_id = $this->Cashbook_model->create_entry($row, $this->current_user['id']);
            $entry = $this->Cashbook_model->get_entry($new_id);
            $this->audit('cashbook', 'CREATE', NULL, $entry);
            $this->session->set_flashdata('success', 'Đã lập phiếu '.$label.' '.$entry['entry_no'].': '.money_format_vnd($amount).'đ.');
        }
        redirect($this->_back_url());
    }

    /** POST — xoá phiếu thu / chi nhập tay (chỉ ADMIN). Tiền bán hàng không xoá ở đây. */
    public function delete($id)
    {
        if ($this->input->method() !== 'post') show_404();
        $this->_require_admin();
        $entry = $this->Cashbook_model->get_entry($id);
        if ( ! $entry) show_404();
        $this->Cashbook_model->delete_entry($id);
        $this->audit('cashbook', 'DELETE', $entry, NULL);
        $this->session->set_flashdata('success', 'Đã xoá phiếu '.$entry['entry_no'].'.');
        redirect($this->_back_url());
    }

    /** Xuất Excel đúng bộ lọc đang xem. */
    public function export()
    {
        list($filters) = $this->_filters();
        $rows = $this->Cashbook_model->get_list($filters, self::EXPORT_LIMIT, 0);

        $this->load->library('xlsx_writer');
        $x = $this->xlsx_writer;
        $x->set_sheet_name('Sổ quỹ');
        $x->set_columns(array(
            'Mã phiếu' => 18, 'Thời gian' => 17, 'Loại' => 8, 'Hình thức' => 14, 'Loại thu / chi' => 22,
            'Người nộp / nhận' => 24, 'Nhân viên' => 18, 'Thu' => 14, 'Chi' => 14, 'Ghi chú' => 30,
        ));
        foreach ($rows as $r)
        {
            $x->add_row(array(
                $r['code'], array('d' => $r['dt']), $r['type'] === 'IN' ? 'Thu' : 'Chi', payment_method_label($r['payment_method']),
                $r['category_name'], $r['counterparty'], $r['handled_name'],
                $r['type'] === 'IN' ? array('n' => $r['amount']) : '', $r['type'] === 'OUT' ? array('n' => $r['amount']) : '',
                $r['note'],
            ));
        }
        if ($rows)
        {
            $x->end_data();
            $last = $x->row_count() + 1;
            $sum = function ($col) use ($last) { return array('f' => 'SUBTOTAL(109,'.$col.'2:'.$col.$last.')', 'b' => TRUE); };
            $x->add_row(array(array('t' => 'Tổng cộng ('.count($rows).' phiếu)', 'b' => TRUE), '', '', '', '', '', '', $sum('H'), $sum('I'), ''));
        }
        $this->audit('cashbook', 'EXPORT_EXCEL', NULL, array('filters' => $filters, 'rows' => count($rows)));
        $x->download('so_quy_'.str_replace('-', '', ($filters['date_from'] ?? 'dau').'_'.($filters['date_to'] ?? 'nay')).'.xlsx');
    }

    // ---------- Loại thu / chi (ADMIN) ----------

    public function categories()
    {
        $this->_require_admin();
        $data = array(
            'page_title'   => 'Loại thu chi',
            'current_user' => $this->current_user,
            'categories'   => $this->Cashbook_model->get_categories(NULL, FALSE),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('cashbook/categories', $data);
        $this->load->view('layout/footer');
    }

    public function category_save()
    {
        if ($this->input->method() !== 'post') show_404();
        $this->_require_admin();
        $id = (int) $this->input->post('id');
        $type = $this->input->post('type') === 'OUT' ? 'OUT' : 'IN';
        $name = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('name', TRUE))), 0, 100, 'UTF-8');
        $old = $id ? $this->Cashbook_model->get_category($id) : NULL;
        if ($id && ! $old) show_404();
        if ($name === '')
        {
            $this->session->set_flashdata('error', 'Nhập tên loại thu / chi.');
        }
        else
        {
            $saved = $this->Cashbook_model->save_category($id, $old ? $old['type'] : $type, $name);
            $this->audit('cashbook_category', $id ? 'UPDATE' : 'CREATE', $old, array('id' => $saved, 'name' => $name));
            $this->session->set_flashdata('success', 'Đã lưu "'.$name.'".');
        }
        redirect('me/cashbook/categories');
    }

    public function category_toggle($id)
    {
        if ($this->input->method() !== 'post') show_404();
        $this->_require_admin();
        $c = $this->Cashbook_model->get_category($id);
        if ( ! $c) show_404();
        $status = $c['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $this->Cashbook_model->set_category_status($id, $status);
        $this->audit('cashbook_category', 'TOGGLE', array('status' => $c['status']), array('id' => (int) $id, 'status' => $status));
        $this->session->set_flashdata('success', ($status === 'ACTIVE' ? 'Đã hiện' : 'Đã ẩn').' "'.$c['name'].'". Phiếu cũ vẫn giữ loại này.');
        redirect('me/cashbook/categories');
    }

    // ---------- Hỗ trợ ----------

    /** Bộ lọc từ query string; mặc định tháng này. Trả về array($filters, $range_key). */
    private function _filters()
    {
        $today = date('Y-m-d');
        $ranges = array(
            'today'      => array($today, $today),
            '7d'         => array(date('Y-m-d', strtotime('-6 days')), $today),
            'month'      => array(date('Y-m-01'), $today),
            'last_month' => array(date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))),
        );
        $range_key = (string) $this->input->get('range');
        if (isset($ranges[$range_key]))
        {
            list($from, $to) = $ranges[$range_key];
        }
        else
        {
            $from = (string) $this->input->get('date_from');
            $to = (string) $this->input->get('date_to');
            $valid = function ($d) { $t = DateTime::createFromFormat('Y-m-d', $d); return $t && $t->format('Y-m-d') === $d; };
            if ( ! $valid($from) && ! $valid($to)) { $range_key = 'month'; list($from, $to) = $ranges['month']; }
            else { $range_key = 'custom'; $from = $valid($from) ? $from : ''; $to = $valid($to) ? $to : ''; }
        }

        $filters = array('date_from' => $from, 'date_to' => $to);
        $type = $this->input->get('type');
        if (in_array($type, array('IN', 'OUT'), TRUE)) $filters['type'] = $type;
        $method = $this->input->get('method');
        if (array_key_exists((string) $method, payment_methods())) $filters['method'] = $method;
        $category = (string) $this->input->get('category');
        if ($category === Cashbook_model::SALE_CATEGORY || ctype_digit($category)) $filters['category'] = $category;
        $handled_by = (int) $this->input->get('handled_by');
        if ($handled_by) $filters['handled_by'] = $handled_by;
        return array(array_filter($filters, function ($v) { return $v !== ''; }), $range_key);
    }

    /** Quay lại đúng trang / bộ lọc đang xem (back_qs gửi kèm form). */
    private function _back_url()
    {
        $qs = (string) $this->input->post('back_qs');
        return site_url('me/cashbook').($qs !== '' && strpos($qs, '//') === FALSE ? '?'.$qs : '');
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
}
