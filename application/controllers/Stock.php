<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nhập kho / Xuất kho / Kiểm kho nhanh từ điện thoại — mở cho mọi nhân viên
 * đã đăng nhập (STAFF/BARISTA/CASHIER/ADMIN/STOCKTAKER), không riêng ADMIN,
 * vì đây là thao tác hàng ngày tại quầy/bếp/kho. Luồng: chọn danh mục
 * (xuất kho thì chọn thêm điểm xuất) -> hiện tất cả sản phẩm của danh mục
 * đó -> chỉ cần điền số lượng cho những sản phẩm cần nhập/xuất, để trống
 * thì bỏ qua.
 */
class Stock extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'BARISTA', 'CASHIER', 'ADMIN', 'STOCKTAKER');

    const PER_PAGE = 20;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Inventory_item_model', 'Inventory_category_model', 'Dispense_point_model', 'Stock_transaction_model', 'User_model'));
    }

    public function in()
    {
        $error = NULL;
        $success = NULL;

        if ($this->input->method() === 'post')
        {
            $qtys = $this->input->post('qty'); // mảng liên kết: [item_id => số lượng]
            $note = $this->input->post('note', TRUE);

            if (empty($qtys) || ! is_array($qtys))
            {
                $error = 'Vui lòng chọn danh mục.';
            }
            else
            {
                $batch_id = $this->Stock_transaction_model->new_batch_id();
                $count = 0;
                $lines = array();
                $processed_item_ids = array();

                foreach ($qtys as $item_id => $qty)
                {
                    $qty = trim((string) $qty);
                    if ($qty === '' || ! is_numeric($qty) || $qty <= 0)
                    {
                        continue; // bỏ trống -> bỏ qua sản phẩm này
                    }

                    $item = $this->Inventory_item_model->get_by_id((int) $item_id);
                    if ( ! $item)
                    {
                        continue;
                    }

                    $result = $this->Stock_transaction_model->create_in((int) $item_id, (float) $qty, $note, $this->current_user['id'], 'MANUAL', $batch_id);
                    if ($result === TRUE)
                    {
                        $count++;
                        $lines[] = $item['name'].': +'.$qty.' '.$item['unit_name'];
                        $processed_item_ids[] = (int) $item_id;
                    }
                }

                if ($count > 0)
                {
                    $this->audit('stock_transaction', 'STOCK_IN_BATCH', NULL, array('count' => $count));
                    $success = 'Đã nhập kho '.$count.' sản phẩm — '.implode('; ', $lines).'.';
                    $this->_notify_low_stock($processed_item_ids, 'IN');
                }
                else
                {
                    $error = 'Vui lòng điền số lượng cho ít nhất 1 sản phẩm.';
                }
            }
        }

        $data = array(
            'page_title'   => 'Nhập kho',
            'current_user' => $this->current_user,
            'categories'   => $this->Inventory_category_model->get_active(),
            'error'        => $error,
            'success'      => $success,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('stock/in', $data);
        $this->load->view('layout/footer');
    }

    public function out()
    {
        $error = NULL;
        $success = NULL;
        $dispense_points = $this->Dispense_point_model->get_active();

        if ($this->input->method() === 'post')
        {
            $qtys = $this->input->post('qty'); // mảng liên kết: [item_id => số lượng]
            $dispense_point_id = (int) $this->input->post('dispense_point_id');
            $note = $this->input->post('note', TRUE);

            if ( ! $dispense_point_id)
            {
                $error = 'Vui lòng chọn điểm xuất kho.';
            }
            elseif (empty($qtys) || ! is_array($qtys))
            {
                $error = 'Vui lòng chọn danh mục.';
            }
            else
            {
                $batch_id = $this->Stock_transaction_model->new_batch_id();
                $ok_count = 0;
                $ok_lines = array();
                $fail_lines = array();
                $processed_item_ids = array();

                foreach ($qtys as $item_id => $qty)
                {
                    $qty = trim((string) $qty);
                    if ($qty === '' || ! is_numeric($qty) || $qty <= 0)
                    {
                        continue;
                    }

                    $item = $this->Inventory_item_model->get_by_id((int) $item_id);
                    if ( ! $item)
                    {
                        continue;
                    }

                    $result = $this->Stock_transaction_model->create_out((int) $item_id, (float) $qty, $dispense_point_id, $note, $this->current_user['id'], $batch_id);
                    if ($result === TRUE)
                    {
                        $ok_count++;
                        $ok_lines[] = $item['name'].': -'.$qty.' '.$item['unit_name'];
                        $processed_item_ids[] = (int) $item_id;
                    }
                    else
                    {
                        $fail_lines[] = $item['name'].': '.$result;
                    }
                }

                if ($ok_count > 0)
                {
                    $this->audit('stock_transaction', 'STOCK_OUT_BATCH', NULL, array('count' => $ok_count, 'dispense_point_id' => $dispense_point_id));
                    $success = 'Đã xuất kho '.$ok_count.' sản phẩm — '.implode('; ', $ok_lines).'.';
                    $this->_notify_low_stock($processed_item_ids, 'OUT');
                }
                if (count($fail_lines) > 0)
                {
                    $error = implode('; ', $fail_lines);
                }
                elseif ($ok_count === 0)
                {
                    $error = 'Vui lòng điền số lượng cho ít nhất 1 sản phẩm.';
                }
            }
        }

        $data = array(
            'page_title'      => 'Xuất kho',
            'current_user'    => $this->current_user,
            'categories'      => $this->Inventory_category_model->get_active(),
            'dispense_points' => $dispense_points,
            'error'           => $error,
            'success'         => $success,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('stock/out', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Kiểm kho: staff điền số lượng đếm thực tế, hệ thống tự tính chênh
     * lệch so với tồn hệ thống và ghi 1 dòng ADJUST (có thể +/-) — khác
     * nhập/xuất, cho phép điền 0 (đếm được đúng 0) và không skip khi bằng 0.
     */
    public function adjust()
    {
        $error = NULL;
        $success = NULL;

        if ($this->input->method() === 'post')
        {
            $qtys = $this->input->post('qty'); // mảng liên kết: [item_id => số lượng đếm được]
            $note = $this->input->post('note', TRUE);

            if (empty($qtys) || ! is_array($qtys))
            {
                $error = 'Vui lòng chọn danh mục.';
            }
            else
            {
                $batch_id = $this->Stock_transaction_model->new_batch_id();
                $changed_count = 0;
                $checked_count = 0;
                $lines = array();
                $processed_item_ids = array();

                foreach ($qtys as $item_id => $qty)
                {
                    $qty = trim((string) $qty);
                    if ($qty === '' || ! is_numeric($qty) || $qty < 0)
                    {
                        continue; // bỏ trống -> chưa kiểm sản phẩm này, bỏ qua
                    }

                    $item = $this->Inventory_item_model->get_by_id((int) $item_id);
                    if ( ! $item)
                    {
                        continue;
                    }

                    $checked_count++;
                    $result = $this->Stock_transaction_model->create_adjust((int) $item_id, (float) $qty, $note, $this->current_user['id'], $batch_id);
                    if ($result === TRUE)
                    {
                        $changed_count++;
                        $delta = (float) $qty - (float) $item['qty_on_hand'];
                        $sign = $delta > 0 ? '+' : '';
                        $lines[] = $item['name'].': '.$item['qty_on_hand'].' → '.$qty.' ('.$sign.rtrim(rtrim(number_format($delta, 2, '.', ''), '0'), '.').')';
                        $processed_item_ids[] = (int) $item_id;
                    }
                    elseif ($result !== 'NO_CHANGE')
                    {
                        $lines[] = $item['name'].': '.$result;
                    }
                }

                if ($checked_count > 0)
                {
                    $this->audit('stock_transaction', 'STOCK_ADJUST_BATCH', NULL, array('checked' => $checked_count, 'changed' => $changed_count));
                    $success = 'Đã kiểm '.$checked_count.' sản phẩm, '.$changed_count.' sản phẩm có chênh lệch'.($lines ? ' — '.implode('; ', $lines) : '').'.';
                    $this->_notify_low_stock($processed_item_ids, 'ADJUST');
                }
                else
                {
                    $error = 'Vui lòng điền số lượng đếm được cho ít nhất 1 sản phẩm.';
                }
            }
        }

        $data = array(
            'page_title'   => 'Kiểm kho',
            'current_user' => $this->current_user,
            'categories'   => $this->Inventory_category_model->get_active(),
            'error'        => $error,
            'success'      => $success,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('stock/adjust', $data);
        $this->load->view('layout/footer');
    }

    public function in_import()
    {
        $preview = NULL;
        $error = NULL;

        if ($this->input->method() === 'post' && $this->input->post('rows_json') !== NULL)
        {
            $rows = json_decode($this->input->post('rows_json'), TRUE);
            $count = 0;
            $batch_id = $this->Stock_transaction_model->new_batch_id();

            if (is_array($rows))
            {
                foreach ($rows as $row)
                {
                    $this->Stock_transaction_model->create_in((int) $row['item_id'], (float) $row['qty'], $row['note'], $this->current_user['id'], 'EXCEL', $batch_id);
                    $count++;
                }
            }

            $this->audit('stock_transaction', 'STOCK_IN_IMPORT', NULL, array('count' => $count));
            $this->session->set_flashdata('success', 'Đã nhập kho '.$count.' dòng từ file.');
            redirect('me/stock/in');
            return;
        }

        if ($this->input->method() === 'post' && ! empty($_FILES['file']['name']))
        {
            try
            {
                $preview = $this->_parse_stock_in_file($_FILES['file']);
            }
            catch (Exception $e)
            {
                $error = $e->getMessage();
            }
        }

        $data = array(
            'page_title'   => 'Import nhập kho',
            'current_user' => $this->current_user,
            'preview'      => $preview,
            'error'        => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('stock/in_import', $data);
        $this->load->view('layout/footer');
    }

    public function in_import_template()
    {
        $this->output->set_content_type('text/csv');
        header('Content-Disposition: attachment; filename="mau_nhap_kho.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array('SKU', 'Số lượng', 'Ghi chú'));
        fputcsv($out, array('CF-001', '20', 'Nhập từ nhà cung cấp A'));
        fclose($out);
    }

    public function history()
    {
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $created_by = $this->input->get('created_by');
        $type = $this->input->get('type');

        $filters = array();
        if ($date_from) $filters['date_from'] = $date_from;
        if ($date_to) $filters['date_to'] = $date_to;
        if ($created_by) $filters['created_by'] = $created_by;
        if ($type) $filters['type'] = $type;

        $total = $this->Stock_transaction_model->count_batches($filters);
        $total_pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($total_pages, (int) $this->input->get('page')));
        $offset = ($page - 1) * self::PER_PAGE;

        $data = array(
            'page_title'   => 'Lịch sử nhập/xuất kho',
            'current_user' => $this->current_user,
            'batches'      => $this->Stock_transaction_model->get_recent_batches($filters, self::PER_PAGE, $offset),
            'users'        => $this->User_model->get_by_roles(array('STOCKTAKER', 'ADMIN')),
            'date_from'    => $date_from,
            'date_to'      => $date_to,
            'created_by'   => $created_by,
            'type'         => $type,
            'page'         => $page,
            'total_pages'  => $total_pages,
            'total'        => $total,
            'per_page'     => self::PER_PAGE,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('stock/history', $data);
        $this->load->view('layout/footer');
    }

    /** Đọc file bulk nhập kho (.xlsx/.csv): cột SKU, Số lượng, Ghi chú. */
    private function _parse_stock_in_file($file)
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ( ! in_array($ext, array('xlsx', 'csv'), TRUE))
        {
            throw new Exception('Chỉ hỗ trợ file .xlsx hoặc .csv.');
        }

        $tmp_with_ext = $file['tmp_name'].'.'.$ext;
        copy($file['tmp_name'], $tmp_with_ext);

        $this->load->library('xlsx_reader');
        $rows = $this->xlsx_reader->read($tmp_with_ext);
        @unlink($tmp_with_ext);

        if (count($rows) > 0)
        {
            array_shift($rows);
        }

        $preview = array('valid' => array(), 'invalid' => array());

        foreach ($rows as $i => $r)
        {
            $line = $i + 2;
            $sku = isset($r[0]) ? trim($r[0]) : '';
            $qty = isset($r[1]) ? trim($r[1]) : '';
            $note = isset($r[2]) ? trim($r[2]) : '';

            if ($sku === '' && $qty === '')
            {
                continue;
            }

            $errors = array();
            $item = ($sku !== '') ? $this->Inventory_item_model->get_by_sku($sku) : NULL;
            if ($sku === '') $errors[] = 'Thiếu SKU';
            elseif ( ! $item) $errors[] = 'Không tìm thấy sản phẩm với SKU "'.$sku.'"';
            if ($qty === '' || ! is_numeric($qty) || $qty <= 0) $errors[] = 'Số lượng không hợp lệ';

            if (count($errors) > 0)
            {
                $preview['invalid'][] = array('line' => $line, 'sku' => $sku, 'errors' => $errors);
                continue;
            }

            $preview['valid'][] = array(
                'line'    => $line,
                'item_id' => $item['id'],
                'sku'     => $sku,
                'name'    => $item['name'],
                'unit'    => $item['unit_name'],
                'qty'     => (float) $qty,
                'note'    => $note,
            );
        }

        return $preview;
    }

    /**
     * Sau khi nhập/xuất/kiểm kho xong, kiểm tra các sản phẩm vừa xử lý có
     * sản phẩm nào tụt xuống dưới ngưỡng cảnh báo không rồi báo qua
     * Telegram. Lỗi gửi Telegram (chưa cấu hình, mất mạng...) chỉ được ghi
     * log — không được làm hỏng luồng nhập/xuất/kiểm kho của người dùng.
     */
    private function _notify_low_stock($item_ids, $type)
    {
        if (empty($item_ids))
        {
            return;
        }

        $low_items = array();
        $category_names = array();

        foreach (array_unique($item_ids) as $item_id)
        {
            $item = $this->Inventory_item_model->get_by_id($item_id);
            if ( ! $item)
            {
                continue;
            }
            if ($item['category_name'] && ! in_array($item['category_name'], $category_names, TRUE))
            {
                $category_names[] = $item['category_name'];
            }
            if ($item['qty_on_hand'] < $item['low_stock_threshold'])
            {
                $low_items[] = $item;
            }
        }

        if (empty($low_items))
        {
            return;
        }

        try
        {
            $this->load->library('telegram_notifier');
            $this->telegram_notifier->notify_low_stock($this->current_user['fullname'], $type, $category_names, $low_items);
        }
        catch (Exception $e)
        {
            log_message('error', 'Telegram notify exception: '.$e->getMessage());
        }
    }
}
