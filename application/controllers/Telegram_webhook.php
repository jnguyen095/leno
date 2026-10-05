<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Endpoint công khai (không cần đăng nhập) để Telegram gọi webhook mỗi khi
 * có tin nhắn mới hoặc nút bấm (callback_query) tới bot — cùng kiểu "public
 * API" với Api_order. Xác thực bằng header
 * X-Telegram-Bot-Api-Secret-Token (đặt khi gọi setWebhook) để tránh request
 * giả mạo, không dùng session/CSRF vì Telegram không gửi cookie của app.
 */
class Telegram_webhook extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->config->load('telegram', TRUE);
        $this->load->model(array('Inventory_item_model', 'Inventory_category_model'));
    }

    public function handle()
    {
        $expected_secret = $this->config->item('telegram_webhook_secret', 'telegram');
        $received_secret = $this->input->get_request_header('X-Telegram-Bot-Api-Secret-Token');
        if ($expected_secret && $received_secret !== $expected_secret)
        {
            $this->output->set_status_header(403);
            return;
        }

        $update = json_decode(file_get_contents('php://input'), TRUE);

        if (isset($update['callback_query']) && is_array($update['callback_query']))
        {
            $this->_handle_callback($update['callback_query']);
            return;
        }

        $message = isset($update['message']) ? $update['message'] : NULL;
        if ( ! is_array($message) || empty($message['text']) || empty($message['chat']['id']))
        {
            return;
        }

        $chat_id = $message['chat']['id'];
        $text = trim($message['text']);

        if (preg_match('/^\/low[\-_]?stock(?:@\S+)?\s*(.*)$/iu', $text, $m))
        {
            $category_arg = trim($m[1]);
            if ($category_arg === '')
            {
                // Không gõ tên danh mục -> đưa nút bấm cho nhanh, đỡ phải gõ tay.
                $this->load->library('telegram_notifier');
                $this->telegram_notifier->send_category_picker($chat_id, $this->Inventory_category_model->get_active());
            }
            else
            {
                $this->_reply_low_stock($chat_id, $category_arg);
            }
        }
        elseif (isset($text[0]) && $text[0] === '/')
        {
            // Lệnh không nhận diện được -> gợi ý lệnh đúng, tránh im lặng
            // khó debug (Telegram không cho phép "-" trong tên lệnh chuẩn).
            $this->load->library('telegram_notifier');
            $this->telegram_notifier->send_raw($chat_id,
                'Không nhận ra lệnh này. Dùng: <code>/low_stock</code> hoặc <code>/low_stock &lt;Danh mục&gt;</code>'."\n".
                'Vd: <code>/low_stock Pha Chế</code>');
        }
    }

    /** Bấm nút trong bàn phím inline gửi kèm callback_data dạng "lsc:<category_id>" (0 = tất cả). */
    private function _handle_callback($cq)
    {
        $this->load->library('telegram_notifier');

        if ( ! empty($cq['id']))
        {
            $this->telegram_notifier->answer_callback_query($cq['id']);
        }

        $chat_id = isset($cq['message']['chat']['id']) ? $cq['message']['chat']['id'] : NULL;
        $data = isset($cq['data']) ? $cq['data'] : '';
        if ( ! $chat_id || ! preg_match('/^lsc:(\d+)$/', $data, $m))
        {
            return;
        }

        $category_id = (int) $m[1];
        if ($category_id > 0)
        {
            $category = $this->Inventory_category_model->get_by_id($category_id);
            $this->_send_report($chat_id, $category_id, $category ? $category['name'] : 'Danh mục');
        }
        else
        {
            $this->_send_report($chat_id, NULL, 'Tất cả danh mục');
        }
    }

    /** Gõ tay "/low_stock <tên danh mục>" — khớp không phân biệt hoa/thường. */
    private function _reply_low_stock($chat_id, $category_arg)
    {
        $this->load->library('telegram_notifier');
        $categories = $this->Inventory_category_model->get_active();

        $needle = mb_strtolower($category_arg, 'UTF-8');
        $matched = NULL;
        foreach ($categories as $c)
        {
            if (mb_strtolower($c['name'], 'UTF-8') === $needle)
            {
                $matched = $c;
                break;
            }
        }

        if ( ! $matched)
        {
            $names = array_column($categories, 'name');
            $this->telegram_notifier->send_raw($chat_id,
                '❌ Không tìm thấy danh mục "'.htmlspecialchars($category_arg, ENT_QUOTES, 'UTF-8').'".'."\n".
                'Danh mục hợp lệ: '.htmlspecialchars(implode(', ', $names), ENT_QUOTES, 'UTF-8'));
            return;
        }

        $this->_send_report($chat_id, $matched['id'], $matched['name']);
    }

    private function _send_report($chat_id, $category_id, $category_label)
    {
        $this->load->library('telegram_notifier');
        $items = $this->Inventory_item_model->get_all($category_id, 'LOW', NULL);
        $this->telegram_notifier->send_low_stock_report($chat_id, $category_label, $items);
    }
}
