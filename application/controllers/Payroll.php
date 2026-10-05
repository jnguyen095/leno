<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lương — mọi nhân viên đã đăng nhập xem được lương của chính mình theo
 * tháng (index()); các thao tác quản trị (cấu hình lương, ngày nghỉ/giờ làm,
 * ứng lương/trạng thái chi lương) chỉ ADMIN, tự chặn bên trong từng method —
 * cùng cách Inventory_items::_require_admin().
 */
class Payroll extends MY_Controller
{
    // $allowed_roles để trống -> mọi role đã đăng nhập đều xem được lương của mình.

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('payroll');
        $this->load->model(array('Payroll_setting_model', 'Payroll_record_model', 'Payroll_hours_model', 'Payroll_absence_model', 'Payroll_bonus_model', 'Payroll_rate_history_model', 'User_model'));
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

    /** $date ('YYYY-MM-DD') có nằm trong khoảng làm việc của nhân viên không — thiếu start_date/end_date coi như không giới hạn phía đó. */
    private function _within_employment($date, $user)
    {
        if ( ! empty($user['start_date']) && $date < $user['start_date'])
        {
            return FALSE;
        }
        if ( ! empty($user['end_date']) && $date > $user['end_date'])
        {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Loại lương + mức lương CÓ HIỆU LỰC cho đúng tháng đang xem — lấy từ
     * payroll_rate_history (dòng effective_from gần nhất mà <= tháng đó),
     * không phải cấu hình "hiện tại" — nhờ vậy đổi lương "áp dụng từ tháng
     * sau" không làm sai lệch lương các tháng trước đã tính. Tháng chưa có
     * lịch sử nào áp dụng được (vd nhân viên mới, hoặc dữ liệu trước khi có
     * tính năng này) thì rơi về cấu hình hiện tại trong payroll_settings.
     */
    private function _effective_settings($user_id, $period)
    {
        $history = $this->Payroll_rate_history_model->get_effective_for_period($user_id, $period);
        if ($history)
        {
            return array(
                'salary_type'  => $history['salary_type'],
                'fixed_salary' => $history['fixed_salary'],
                'hourly_rate'  => $history['hourly_rate'],
            );
        }
        return $this->Payroll_setting_model->get_by_user_or_default($user_id);
    }

    /** $total_hours cho HOURLY, $absence_days cho FIXED — luôn tính cả 2 và bỏ qua giá trị không dùng cho gọn code gọi. */
    private function _compute($settings, $record, $user_id, $period)
    {
        $total_hours = $settings['salary_type'] === 'HOURLY' ? $this->Payroll_hours_model->sum_hours($user_id, $period) : 0;
        $absence_days = $settings['salary_type'] === 'FIXED' ? $this->Payroll_absence_model->sum_by_user_period($user_id, $period) : 0;
        $bonus_total = $this->Payroll_bonus_model->sum_by_user_period($user_id, $period);
        return payroll_compute($settings, $record, $total_hours, $absence_days, $period, $bonus_total);
    }

    /** Xem lương của chính mình theo tháng — mọi role. */
    public function index()
    {
        $period = $this->input->get('period') ?: date('Y-m');
        $user_id = $this->current_user['id'];

        $settings = $this->_effective_settings($user_id, $period);
        $record = $this->Payroll_record_model->get_by_user_period_or_default($user_id, $period);

        $data = array(
            'page_title'   => 'Lương',
            'current_user' => $this->current_user,
            'period'       => $period,
            'settings'     => $settings,
            // Thông tin ngân hàng không phụ thuộc tháng đang xem, luôn lấy
            // cấu hình hiện tại — $settings ở trên chỉ có
            // salary_type/fixed_salary/hourly_rate khi lấy từ lịch sử mức
            // lương, không có các trường ngân hàng.
            'bank_info'    => $this->Payroll_setting_model->get_by_user_or_default($user_id),
            'salary'       => $this->_compute($settings, $record, $user_id, $period),
            'bonuses'      => $this->Payroll_bonus_model->get_by_user_period($user_id, $period),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('payroll/index', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Tự cập nhật thông tin ngân hàng của chính mình (nhận lương qua chuyển
     * khoản) — mọi role, chỉ tác động lên $this->current_user['id'], không
     * bao giờ nhận user_id từ bên ngoài. Không đụng đến salary_type/mức
     * lương (vẫn chỉ ADMIN qua settings()).
     */
    public function bank_info()
    {
        $user_id = $this->current_user['id'];
        $error = NULL;
        $success = FALSE;

        if ($this->input->method() === 'post')
        {
            $data = array(
                'bank_name'           => $this->input->post('bank_name', TRUE),
                'bank_account_number' => $this->input->post('bank_account_number', TRUE),
                'bank_account_name'   => $this->input->post('bank_account_name', TRUE),
            );

            if (($data['bank_name'] || $data['bank_account_number'] || $data['bank_account_name'])
                && ( ! $data['bank_name'] || ! $data['bank_account_number'] || ! $data['bank_account_name']))
            {
                $error = 'Vui lòng điền đủ Ngân hàng, Số tài khoản và Chủ tài khoản (hoặc để trống cả 3 nếu chưa muốn cập nhật).';
            }
            else
            {
                $this->Payroll_setting_model->upsert($user_id, $data);
                $this->audit('payroll_settings', 'UPDATE', NULL, array('user_id' => $user_id, 'self_service' => TRUE));
                $success = TRUE;
            }
        }

        $data = array(
            'page_title'   => 'Thông tin ngân hàng',
            'current_user' => $this->current_user,
            'bank_info'    => $this->Payroll_setting_model->get_by_user_or_default($user_id),
            'error'        => $error,
            'success'      => $success,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('payroll/bank_form', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Chi tiết ngày làm/nghỉ của chính mình theo tháng — mọi role, chỉ xem
     * (sửa vẫn chỉ ADMIN qua hours()). HOURLY liệt kê từng ngày có giờ làm,
     * FIXED liệt kê từng ngày nghỉ (cả ngày/nửa ngày).
     */
    public function detail()
    {
        $period = $this->input->get('period') ?: date('Y-m');
        $user_id = $this->current_user['id'];
        $settings = $this->_effective_settings($user_id, $period);

        $weekday_labels = array(1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN');
        $hour_entries = array();
        $off_entries = array();

        if ($settings['salary_type'] === 'HOURLY')
        {
            $hours_by_date = $this->Payroll_hours_model->get_by_user_period($user_id, $period);
            ksort($hours_by_date);
            foreach ($hours_by_date as $date => $hours)
            {
                $hour_entries[] = array(
                    'date'          => $date,
                    'day'           => (int) date('j', strtotime($date)),
                    'weekday_label' => $weekday_labels[(int) date('N', strtotime($date))],
                    'hours'         => $hours,
                );
            }
        }
        else
        {
            $off_by_date = $this->Payroll_absence_model->get_by_user_period($user_id, $period);
            ksort($off_by_date);
            foreach ($off_by_date as $date => $fraction)
            {
                $off_entries[] = array(
                    'date'          => $date,
                    'day'           => (int) date('j', strtotime($date)),
                    'weekday_label' => $weekday_labels[(int) date('N', strtotime($date))],
                    'fraction'      => $fraction,
                );
            }
        }

        $data = array(
            'page_title'    => 'Chi tiết lương',
            'current_user'  => $this->current_user,
            'period'        => $period,
            'settings'      => $settings,
            'hour_entries'  => $hour_entries,
            'off_entries'   => $off_entries,
            'total_hours'   => $settings['salary_type'] === 'HOURLY' ? $this->Payroll_hours_model->sum_hours($user_id, $period) : 0,
            'total_off'     => $settings['salary_type'] === 'FIXED' ? $this->Payroll_absence_model->sum_by_user_period($user_id, $period) : 0,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('payroll/detail', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Bảng tổng hợp lương tất cả nhân viên trong 1 tháng — ADMIN. Nhân viên
     * đã nghỉ việc vẫn hiện ra cho tháng họ còn làm (xem
     * User_model::get_for_payroll()) để vẫn chi được lương tháng cuối,
     * biến mất kể từ tháng sau.
     */
    public function admin()
    {
        $this->_require_admin();
        $period = $this->input->get('period') ?: date('Y-m');
        $keyword = trim((string) $this->input->get('q'));
        $role_filter = $this->input->get('role');
        $salary_type_filter = $this->input->get('salary_type');
        $paid_status_filter = $this->input->get('paid_status');

        $users = $this->User_model->get_for_payroll($period, $role_filter ?: NULL, $keyword ?: NULL);
        $records_by_user = $this->Payroll_record_model->get_all_by_period($period);

        $rows = array();
        foreach ($users as $u)
        {
            $settings = $this->_effective_settings($u['id'], $period);
            $record = isset($records_by_user[$u['id']])
                ? $records_by_user[$u['id']]
                : $this->Payroll_record_model->get_by_user_period_or_default($u['id'], $period);

            $salary = $this->_compute($settings, $record, $u['id'], $period);

            if ($salary_type_filter && $salary['salary_type'] !== $salary_type_filter)
            {
                continue;
            }
            if ($paid_status_filter && $salary['paid_status'] !== $paid_status_filter)
            {
                continue;
            }

            $rows[] = array(
                'user'   => $u,
                'salary' => $salary,
            );
        }

        $total_unpaid = 0;
        $unpaid_count = 0;
        $total_net = 0;
        foreach ($rows as $r)
        {
            $total_net += $r['salary']['net_salary'];
            if ($r['salary']['paid_status'] === 'UNPAID')
            {
                $total_unpaid += $r['salary']['net_salary'];
                $unpaid_count++;
            }
        }

        $data = array(
            'page_title'         => 'Quản lý lương',
            'current_user'       => $this->current_user,
            'period'             => $period,
            'keyword'            => $keyword,
            'role_filter'        => $role_filter,
            'salary_type_filter' => $salary_type_filter,
            'paid_status_filter' => $paid_status_filter,
            'rows'               => $rows,
            'total_unpaid'       => $total_unpaid,
            'unpaid_count'       => $unpaid_count,
            'total_net'          => $total_net,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('payroll/admin_index', $data);
        $this->load->view('layout/footer');
    }

    /** Cấu hình loại lương/mức lương + thông tin ngân hàng của 1 nhân viên — ADMIN. */
    public function settings($user_id)
    {
        $this->_require_admin();
        $user = $this->User_model->get_by_id($user_id);
        if ( ! $user) show_404();

        if ($this->input->method() === 'post')
        {
            $salary_type = $this->input->post('salary_type') === 'HOURLY' ? 'HOURLY' : 'FIXED';
            $fixed_salary = (float) $this->input->post('fixed_salary');
            $hourly_rate = (float) $this->input->post('hourly_rate');
            $effective_from = $this->input->post('effective_from') ?: date('Y-m');

            $data = array(
                'salary_type'         => $salary_type,
                'fixed_salary'        => $fixed_salary,
                'hourly_rate'         => $hourly_rate,
                'bank_name'           => $this->input->post('bank_name', TRUE),
                'bank_account_number' => $this->input->post('bank_account_number', TRUE),
                'bank_account_name'   => $this->input->post('bank_account_name', TRUE),
            );
            $this->Payroll_setting_model->upsert($user_id, $data);
            $this->Payroll_rate_history_model->upsert($user_id, $salary_type, $fixed_salary, $hourly_rate, $effective_from, $this->current_user['id']);
            $this->audit('payroll_settings', 'UPDATE', NULL, array('user_id' => $user_id, 'effective_from' => $effective_from));
            $this->session->set_flashdata('success', 'Đã lưu cấu hình lương cho '.$user['fullname'].', áp dụng từ '.payroll_period_label($effective_from).'.');
            redirect('me/payroll/settings/'.$user_id);
            return;
        }

        $data = array(
            'page_title'   => 'Cấu hình lương — '.$user['fullname'],
            'current_user' => $this->current_user,
            'target_user'  => $user,
            'settings'     => $this->Payroll_setting_model->get_by_user_or_default($user_id),
            'rate_history' => $this->Payroll_rate_history_model->get_all_for_user($user_id),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('payroll/settings_form', $data);
        $this->load->view('layout/footer');
    }

    /** Ứng lương / trạng thái chi lương của 1 nhân viên trong 1 tháng — ADMIN. Ngày nghỉ/giờ làm sửa ở hours(). */
    public function record($user_id)
    {
        $this->_require_admin();
        $user = $this->User_model->get_by_id($user_id);
        if ( ! $user) show_404();

        $period = $this->input->get('period') ?: date('Y-m');

        if ($this->input->method() === 'post')
        {
            $data = array(
                'advance_amount' => (float) $this->input->post('advance_amount'),
                'paid_status'    => $this->input->post('paid_status') === 'PAID' ? 'PAID' : 'UNPAID',
                'note'           => $this->input->post('note', TRUE),
            );
            $this->Payroll_record_model->upsert($user_id, $period, $data, $this->current_user['id']);
            $this->audit('payroll_record', 'UPDATE', NULL, array('user_id' => $user_id, 'period' => $period));
            $this->session->set_flashdata('success', 'Đã lưu dữ liệu lương '.payroll_period_label($period).' cho '.$user['fullname'].'.');
            redirect('me/payroll/admin?period='.$period);
            return;
        }

        $settings = $this->_effective_settings($user_id, $period);
        $record = $this->Payroll_record_model->get_by_user_period_or_default($user_id, $period);

        $data = array(
            'page_title'   => 'Dữ liệu lương — '.$user['fullname'],
            'current_user' => $this->current_user,
            'target_user'  => $user,
            'settings'     => $settings,
            'bank_info'    => $this->Payroll_setting_model->get_by_user_or_default($user_id),
            'record'       => $record,
            'salary'       => $this->_compute($settings, $record, $user_id, $period),
            'bonuses'      => $this->Payroll_bonus_model->get_by_user_period($user_id, $period),
            'period'       => $period,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('payroll/record_form', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Thêm 1 khoản thưởng — theo số tiền cố định (VD: "Thưởng 200.000 dịp lễ
     * 2/7") hoặc theo giờ (số giờ × đơn giá/giờ hiện tại của nhân viên, lưu
     * lại rate dùng để tính — không bị lệch nếu sau này đổi mức lương). ADMIN.
     */
    public function add_bonus($user_id)
    {
        $this->_require_admin();
        $user = $this->User_model->get_by_id($user_id);
        if ( ! $user) show_404();

        $period = $this->input->post('period') ?: date('Y-m');
        $bonus_type = $this->input->post('bonus_type') === 'HOURLY' ? 'HOURLY' : 'AMOUNT';
        $note = $this->input->post('note', TRUE);

        if ($bonus_type === 'HOURLY')
        {
            $hours = (float) $this->input->post('hours');
            $settings = $this->_effective_settings($user_id, $period);
            $rate = (float) $settings['hourly_rate'];
            $data = array(
                'bonus_type' => 'HOURLY',
                'hours'      => $hours,
                'rate'       => $rate,
                'amount'     => $hours * $rate,
                'note'       => $note,
            );
            $valid = $hours > 0;
        }
        else
        {
            $amount = (float) $this->input->post('amount');
            $data = array(
                'bonus_type' => 'AMOUNT',
                'amount'     => $amount,
                'note'       => $note,
            );
            $valid = $amount > 0;
        }

        if ($valid)
        {
            $this->Payroll_bonus_model->create($user_id, $period, $data, $this->current_user['id']);
            $this->audit('payroll_bonus', 'CREATE', NULL, array('user_id' => $user_id, 'period' => $period, 'amount' => $data['amount']));
            $this->session->set_flashdata('success', 'Đã thêm khoản thưởng cho '.$user['fullname'].'.');
        }
        else
        {
            $this->session->set_flashdata('error', 'Vui lòng nhập số tiền hoặc số giờ thưởng hợp lệ.');
        }

        redirect('me/payroll/record/'.$user_id.'?period='.$period);
    }

    /** Xoá 1 khoản thưởng — ADMIN. */
    public function delete_bonus($user_id, $bonus_id)
    {
        $this->_require_admin();
        $period = $this->input->get('period') ?: date('Y-m');

        $bonus = $this->Payroll_bonus_model->get_by_id($bonus_id);
        if ($bonus && (int) $bonus['user_id'] === (int) $user_id)
        {
            $this->Payroll_bonus_model->delete($bonus_id, $user_id);
            $this->audit('payroll_bonus', 'DELETE', $bonus, array('user_id' => $user_id));
        }

        redirect('me/payroll/record/'.$user_id.'?period='.$period);
    }

    /**
     * Nhập theo từng ngày trong tháng — lương theo giờ thì nhập số giờ làm,
     * lương cố định thì đánh dấu ngày nghỉ (số ngày nghỉ = số ngày đánh dấu,
     * dùng để trừ lương). Chỉ ADMIN, giới hạn trong khoảng ngày làm việc.
     */
    public function hours($user_id)
    {
        $this->_require_admin();
        $user = $this->User_model->get_by_id($user_id);
        if ( ! $user) show_404();

        $period = $this->input->get('period') ?: date('Y-m');
        $settings = $this->_effective_settings($user_id, $period);
        $is_hourly = $settings['salary_type'] === 'HOURLY';

        $days_in_month = (int) date('t', strtotime($period.'-01'));

        if ($this->input->method() === 'post')
        {
            if ($is_hourly)
            {
                $entries = array();
                for ($d = 1; $d <= $days_in_month; $d++)
                {
                    $date = $period.'-'.str_pad($d, 2, '0', STR_PAD_LEFT);
                    if ($this->_within_employment($date, $user))
                    {
                        $entries[$date] = $this->input->post('hours_'.$d);
                    }
                }
                $this->Payroll_hours_model->save_batch($user_id, $entries, $this->current_user['id']);
            }
            else
            {
                $entries = array();
                for ($d = 1; $d <= $days_in_month; $d++)
                {
                    $date = $period.'-'.str_pad($d, 2, '0', STR_PAD_LEFT);
                    if ($this->_within_employment($date, $user))
                    {
                        // 'off_<d>' là fraction dạng chuỗi: '' (đi làm), '1' (nghỉ cả ngày), '0.5' (nghỉ nửa ngày).
                        $entries[$date] = (float) $this->input->post('off_'.$d);
                    }
                }
                $this->Payroll_absence_model->save_batch($user_id, $entries, $this->current_user['id']);
            }

            $this->audit('payroll_hours', 'UPDATE', NULL, array('user_id' => $user_id, 'period' => $period));
            $this->session->set_flashdata('success', 'Đã lưu dữ liệu '.payroll_period_label($period).' cho '.$user['fullname'].'.');
            redirect('me/payroll/hours/'.$user_id.'?period='.$period);
            return;
        }

        $weekday_labels = array(1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN');
        $existing_hours = $is_hourly ? $this->Payroll_hours_model->get_by_user_period($user_id, $period) : array();
        $existing_off = ! $is_hourly ? $this->Payroll_absence_model->get_by_user_period($user_id, $period) : array();

        $days = array();
        for ($d = 1; $d <= $days_in_month; $d++)
        {
            $date = $period.'-'.str_pad($d, 2, '0', STR_PAD_LEFT);
            $days[] = array(
                'day'           => $d,
                'date'          => $date,
                'hours'         => isset($existing_hours[$date]) ? $existing_hours[$date] : '',
                'off_value'     => isset($existing_off[$date]) ? $existing_off[$date] : 0,
                'weekday_label' => $weekday_labels[(int) date('N', strtotime($date))],
                'is_weekend'    => in_array((int) date('N', strtotime($date)), array(6, 7), TRUE),
                'in_range'      => $this->_within_employment($date, $user),
            );
        }

        $data = array(
            'page_title'    => ($is_hourly ? 'Nhập giờ làm — ' : 'Ngày nghỉ — ').$user['fullname'],
            'current_user'  => $this->current_user,
            'target_user'   => $user,
            'is_hourly'     => $is_hourly,
            'period'        => $period,
            'days'          => $days,
            'total_hours'   => $is_hourly ? $this->Payroll_hours_model->sum_hours($user_id, $period) : 0,
            'absence_days'  => ! $is_hourly ? array_sum($existing_off) : 0,
            'has_range_day' => count(array_filter($days, function($d) { return $d['in_range']; })) > 0,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('payroll/hours_form', $data);
        $this->load->view('layout/footer');
    }
}
