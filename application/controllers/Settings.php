<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller
{
    protected $allowed_roles = array('ADMIN');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Setting_model');
    }

    public function index()
    {
        $error = NULL;

        if ($this->input->method() === 'post' && $this->input->post('form') === 'takeaway')
        {
            $old = $this->Setting_model->is_takeaway_enabled();
            $new = (bool) $this->input->post('takeaway_enabled');
            $this->Setting_model->set('takeaway_enabled', $new ? '1' : '0');
            $this->audit('settings', 'UPDATE_TAKEAWAY', array('takeaway_enabled' => $old), array('takeaway_enabled' => $new));
            $this->_saved('sales', $new ? 'Đã bật bán mang đi.' : 'Đã tắt bán mang đi.');
            return;
        }

        if ($this->input->method() === 'post' && $this->input->post('form') === 'bank_qr')
        {
            $bin = (string) $this->input->post('bank_qr_bin');
            $account_no = preg_replace('/\s+/', '', (string) $this->input->post('bank_qr_account_no'));
            // Tên chủ tài khoản: in hoa, bỏ khoảng trắng thừa (giống tên hiện trên ứng dụng ngân hàng).
            $account_name = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('bank_qr_account_name', TRUE))), 'UTF-8');
            $enabled = (bool) $this->input->post('bank_qr_enabled');

            if ($bin !== '' && ! isset(Setting_model::VIETQR_BANKS[$bin]))
            {
                $error = 'Ngân hàng không hợp lệ.';
            }
            elseif ($account_no !== '' && ! preg_match('/^[0-9]{6,19}$/', $account_no))
            {
                $error = 'Số tài khoản chỉ gồm chữ số (6–19 số).';
            }
            elseif ($enabled && ($bin === '' || $account_no === ''))
            {
                $error = 'Chọn ngân hàng và nhập số tài khoản trước khi bật in mã QR.';
            }
            else
            {
                $old = $this->Setting_model->get_bank_qr();
                $this->Setting_model->set('bank_qr_bin', $bin);
                $this->Setting_model->set('bank_qr_account_no', $account_no);
                $this->Setting_model->set('bank_qr_account_name', mb_substr($account_name, 0, 100, 'UTF-8'));
                $this->Setting_model->set('bank_qr_enabled', $enabled ? '1' : '0');
                $this->audit('settings', 'UPDATE_BANK_QR', $old, $this->Setting_model->get_bank_qr());
                $this->_saved('bank', 'Đã lưu thông tin chuyển khoản.');
                return;
            }
        }

        if ($this->input->method() === 'post' && $this->input->post('form') === 'receipt')
        {
            $clean = function ($field, $max) {
                return mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $this->input->post($field, TRUE))), 0, $max, 'UTF-8');
            };
            $new = array(
                'shop_name' => $clean('receipt_shop_name', 60),
                'address'   => $clean('receipt_address', 120),
                'phone'     => $clean('receipt_phone', 30),
                'footer'    => $clean('receipt_footer', 120),
            );
            if ($new['shop_name'] === '')
            {
                $error = 'Nhập tên quán để in trên phiếu.';
            }
            else
            {
                $old = $this->Setting_model->get_receipt_info();
                foreach ($new as $key => $value)
                {
                    $this->Setting_model->set('receipt_'.$key, $value);
                }
                $this->audit('settings', 'UPDATE_RECEIPT', $old, $new);
                $this->_saved('receipt', 'Đã lưu thông tin in phiếu.');
                return;
            }
        }

        // Form VAT (không có trường 'form').
        if ($this->input->method() === 'post' && ! $this->input->post('form'))
        {
            $vat_percent = $this->input->post('vat_percent');

            if ( ! is_numeric($vat_percent) || $vat_percent < 0 || $vat_percent > 100)
            {
                $error = 'VAT phải là một số từ 0 đến 100.';
            }
            else
            {
                $old = $this->Setting_model->get_vat_percent();
                $this->Setting_model->set('vat_percent', (string) (float) $vat_percent);
                $this->audit('settings', 'UPDATE_VAT', array('vat_percent' => $old), array('vat_percent' => (float) $vat_percent));
                $this->_saved('sales', 'Đã lưu thuế VAT.');
                return;
            }
        }

        $data = array(
            'page_title'          => 'Cài đặt',
            'current_user'        => $this->current_user,
            'vat_percent'         => $this->Setting_model->get_vat_percent(),
            'takeaway_enabled'    => $this->Setting_model->is_takeaway_enabled(),
            'bank_qr'             => $this->Setting_model->get_bank_qr(),
            'bank_qr_on'          => $this->Setting_model->get('bank_qr_enabled', '0') === '1',
            'vietqr_banks'        => Setting_model::VIETQR_BANKS,
            'receipt'             => $this->Setting_model->get_receipt_info(),
            'error'               => $error,
            // Lỗi hiện ngay trong mục vừa gửi (form VAT không có trường 'form').
            'error_section'       => self::FORM_SECTIONS[(string) $this->input->post('form')] ?? 'sales',
            'success'             => $this->session->flashdata('settings_saved'),
            'success_section'     => $this->session->flashdata('settings_section'),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('settings/index', $data);
        $this->load->view('layout/footer');
    }

    /** Trường 'form' của từng form -> id mục trên trang (#sales, #receipt, #bank). */
    const FORM_SECTIONS = array('' => 'sales', 'takeaway' => 'sales', 'receipt' => 'receipt', 'bank_qr' => 'bank');

    /** Lưu xong: báo thành công ngay trong mục đó và cuộn về đúng mục. */
    private function _saved($section, $message)
    {
        $this->session->set_flashdata('settings_saved', $message);
        $this->session->set_flashdata('settings_section', $section);
        redirect(site_url('me/settings').'#'.$section);
    }
}
