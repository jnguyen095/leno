<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Form đăng ký nhận quà Trung Thu — công khai, không cần đăng nhập, phụ huynh
 * quét QR trên điện thoại để điền. Không kế thừa MY_Controller vì controller
 * đó bắt buộc đăng nhập (xem application/core/MY_Controller.php).
 */
class Trung_thu extends CI_Controller
{
    const EVENT_LABEL = '18:00 – 21:00 | Thứ Sáu, 25/09/2026';
    /** Giới hạn số bé mỗi lượt đăng ký công khai — admin sửa trong /trung-thu/admin thì không bị giới hạn này. */
    const MAX_KIDS_PUBLIC = 3;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Trung_thu_registration_model', 'Setting_model'));
    }

    public function index()
    {
        $open_at = $this->Setting_model->get_trung_thu_open_at();
        $close_at = $this->Setting_model->get_trung_thu_close_at();
        $now = date('Y-m-d H:i:s');

        if ($open_at && $now < $open_at)
        {
            $this->load->view('public/trung_thu/not_open', array('open_at_label' => date('H:i \n\g\à\y d/m/Y', strtotime($open_at))));
            return;
        }
        if ($close_at && $now > $close_at)
        {
            $this->load->view('public/trung_thu/ended');
            return;
        }

        $error = NULL;
        $old = array('phone' => '', 'parent_name' => '', 'kid_count' => 1);

        if ($this->input->method() === 'post')
        {
            $phone = trim((string) $this->input->post('phone', TRUE));
            $parent_name = trim((string) $this->input->post('parent_name', TRUE));
            $kid_count = (int) $this->input->post('kid_count');
            $old = array('phone' => $phone, 'parent_name' => $parent_name, 'kid_count' => $kid_count ?: 1);

            $phone_digits = preg_replace('/[^0-9]/', '', $phone);

            if ($phone === '' || $parent_name === '' || $kid_count < 1)
            {
                $error = 'Vui lòng điền đầy đủ thông tin.';
            }
            elseif ($kid_count > self::MAX_KIDS_PUBLIC)
            {
                $error = 'Mỗi lượt đăng ký tối đa '.self::MAX_KIDS_PUBLIC.' bé. Vui lòng đăng ký thêm lượt khác nếu có nhiều bé hơn.';
            }
            elseif (strlen($phone_digits) < 9 || strlen($phone_digits) > 11)
            {
                $error = 'Số điện thoại không hợp lệ.';
            }
            elseif ($this->Trung_thu_registration_model->phone_exists($phone_digits))
            {
                $error = 'Số điện thoại này đã đăng ký rồi. Mỗi số điện thoại chỉ đăng ký được 1 lần.';
            }
            else
            {
                $uuid = gen_token(32);
                $this->Trung_thu_registration_model->create(array(
                    'uuid'        => $uuid,
                    'phone'       => $phone_digits,
                    'parent_name' => $parent_name,
                    'kid_count'   => $kid_count,
                ));

                redirect('trung-thu/thank-you/'.$uuid);
                return;
            }
        }

        $data = array(
            'error'         => $error,
            'old'           => $old,
            'event_label'   => self::EVENT_LABEL,
            'max_kids'      => self::MAX_KIDS_PUBLIC,
            'og_image_url'  => base_url('assets/img/trung-thu-icon.jpg'),
            'canonical_url' => site_url('trung-thu'),
        );
        $this->load->view('public/trung_thu/form', $data);
    }

    /**
     * Trang cảm ơn — link riêng theo uuid (không phụ thuộc session) nên phụ
     * huynh có thể chia sẻ lên mạng xã hội, ai bấm vào cũng xem được.
     */
    public function thank_you($uuid = NULL)
    {
        $reg = $uuid ? $this->Trung_thu_registration_model->get_by_uuid($uuid) : NULL;
        if ( ! $reg)
        {
            redirect('trung-thu');
            return;
        }

        $data = array(
            'parent_name'   => $reg['parent_name'],
            'kid_count'     => $reg['kid_count'],
            'event_label'   => self::EVENT_LABEL,
            'share_url'     => site_url('trung-thu/thank-you/'.$reg['uuid']),
            'og_image_url'  => base_url('assets/img/trung-thu-icon.jpg'),
        );
        $this->load->view('public/trung_thu/thank_you', $data);
    }
}
