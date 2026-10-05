<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Quản lý đăng ký nhận quà Trung Thu (xem/sửa/xóa) — xem form công khai ở [[Trung_thu]]. */
class Trung_thu_admin extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Trung_thu_registration_model', 'Setting_model'));
    }

    public function index()
    {
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $open_raw = trim((string) $this->input->post('trung_thu_open_at'));
            $close_raw = trim((string) $this->input->post('trung_thu_close_at'));
            $open_at = $open_raw !== '' ? str_replace('T', ' ', $open_raw).':00' : '';
            $close_at = $close_raw !== '' ? str_replace('T', ' ', $close_raw).':00' : '';

            if ($open_at !== '' && $close_at !== '' && $close_at <= $open_at)
            {
                $error = 'Thời gian đóng đăng ký phải sau thời gian mở đăng ký.';
            }
            else
            {
                $old = array(
                    'trung_thu_open_at'  => $this->Setting_model->get_trung_thu_open_at(),
                    'trung_thu_close_at' => $this->Setting_model->get_trung_thu_close_at(),
                );
                $this->Setting_model->set('trung_thu_open_at', $open_at);
                $this->Setting_model->set('trung_thu_close_at', $close_at);
                $this->audit('settings', 'UPDATE_TRUNG_THU_HOURS', $old, array('trung_thu_open_at' => $open_at, 'trung_thu_close_at' => $close_at));
                redirect('me/trung-thu/admin');
                return;
            }
        }

        $open_at = $this->Setting_model->get_trung_thu_open_at();
        $close_at = $this->Setting_model->get_trung_thu_close_at();
        $now = date('Y-m-d H:i:s');

        if ($open_at && $now < $open_at)
        {
            $current_status = array('label' => 'Chưa mở đăng ký', 'class' => 'bg-warning text-dark');
        }
        elseif ($close_at && $now > $close_at)
        {
            $current_status = array('label' => 'Đã đóng đăng ký', 'class' => 'bg-secondary');
        }
        else
        {
            $current_status = array('label' => 'Đang mở đăng ký', 'class' => 'bg-success');
        }

        $data = array(
            'page_title'         => 'Đăng ký quà Trung Thu',
            'current_user'       => $this->current_user,
            'registrations'      => $this->Trung_thu_registration_model->get_all(),
            'total_count'        => $this->Trung_thu_registration_model->count_total(),
            'total_kids'         => $this->Trung_thu_registration_model->sum_kids(),
            'trung_thu_open_at'  => $open_at ? str_replace(' ', 'T', substr($open_at, 0, 16)) : '',
            'trung_thu_close_at' => $close_at ? str_replace(' ', 'T', substr($close_at, 0, 16)) : '',
            'open_at_label'      => $open_at ? date('d/m/Y H:i', strtotime($open_at)) : 'Không giới hạn (mở sẵn)',
            'close_at_label'     => $close_at ? date('d/m/Y H:i', strtotime($close_at)) : 'Không giới hạn (không tự đóng)',
            'current_status'     => $current_status,
            'error'              => $error,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('trung_thu_admin/index', $data);
        $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        $reg = $this->Trung_thu_registration_model->get_by_id($id);
        if ( ! $reg) show_404();
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $phone = trim((string) $this->input->post('phone', TRUE));
            $parent_name = trim((string) $this->input->post('parent_name', TRUE));
            $kid_count = (int) $this->input->post('kid_count');
            $phone_digits = preg_replace('/[^0-9]/', '', $phone);

            if ($phone === '' || $parent_name === '' || $kid_count < 1)
            {
                $error = 'Vui lòng điền đầy đủ thông tin.';
            }
            elseif (strlen($phone_digits) < 9 || strlen($phone_digits) > 11)
            {
                $error = 'Số điện thoại không hợp lệ.';
            }
            elseif ($this->Trung_thu_registration_model->phone_exists($phone_digits, $id))
            {
                $error = 'Số điện thoại này đã được đăng ký bởi 1 lượt khác.';
            }
            else
            {
                $this->Trung_thu_registration_model->update($id, array(
                    'phone'       => $phone_digits,
                    'parent_name' => $parent_name,
                    'kid_count'   => $kid_count,
                ));
                $this->audit('trung_thu_registration', 'UPDATE', $reg, array('id' => $id));
                redirect('me/trung-thu/admin');
                return;
            }

            $reg = array_merge($reg, array('phone' => $phone, 'parent_name' => $parent_name, 'kid_count' => $kid_count));
        }

        $data = array('page_title' => 'Sửa đăng ký', 'current_user' => $this->current_user, 'reg' => $reg, 'error' => $error);
        $this->load->view('layout/header', $data);
        $this->load->view('trung_thu_admin/form', $data);
        $this->load->view('layout/footer');
    }

    public function delete($id)
    {
        $reg = $this->Trung_thu_registration_model->get_by_id($id);
        $this->Trung_thu_registration_model->delete($id);
        $this->audit('trung_thu_registration', 'DELETE', $reg, NULL);
        redirect('me/trung-thu/admin');
    }

    /** Xuất Excel (CSV) toàn bộ đăng ký — cột "Đã nhận quà" để trống, dùng in ra kiểm ở sự kiện. */
    public function export()
    {
        $registrations = $this->Trung_thu_registration_model->get_all();

        $this->output->set_content_type('text/csv');
        header('Content-Disposition: attachment; filename="dang_ky_trung_thu_'.date('Ymd_His').'.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array('STT', 'Thời gian đăng ký', 'Tên Ba/Mẹ', 'Số điện thoại', 'Số bé đăng ký', 'Đã nhận quà'));

        $stt = 1;
        foreach ($registrations as $r)
        {
            fputcsv($out, array(
                $stt++,
                date('d/m/Y H:i', strtotime($r['created_at'])),
                $r['parent_name'],
                // ="..." ép Excel hiểu là text, tránh mất số 0 đầu số điện thoại.
                '="'.$r['phone'].'"',
                (int) $r['kid_count'],
                '', // Đã nhận quà - để trống, dùng khi in ra kiểm ở sự kiện
            ));
        }
        fclose($out);
    }
}
