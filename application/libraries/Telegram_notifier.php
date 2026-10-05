<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Gửi tin nhắn Telegram báo sản phẩm tụt xuống dưới ngưỡng cảnh báo ngay
 * sau khi ai đó tạo phiếu nhập/xuất/kiểm kho. Chỉ cần cấu hình bot_token +
 * chat_id trong application/config/telegram.php; để trống thì tính năng
 * tự tắt (không báo lỗi). Lỗi gọi Telegram API chỉ được ghi log — không
 * được làm hỏng luồng nhập/xuất kho của người dùng.
 */
class Telegram_notifier
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('telegram', TRUE);
    }

    /**
     * $type: 'IN' | 'OUT' | 'ADJUST'.
     * $category_names: mảng tên danh mục liên quan tới phiếu vừa tạo.
     * $low_items: mảng sản phẩm đang dưới ngưỡng, mỗi phần tử cần có
     * 'name', 'unit_name', 'qty_on_hand'.
     */
    public function notify_low_stock($user_name, $type, $category_names, $low_items)
    {
        if (empty($low_items))
        {
            return;
        }

        $token = $this->ci->config->item('telegram_bot_token', 'telegram');
        $chat_id = $this->ci->config->item('telegram_chat_id', 'telegram');
        if ( ! $token || ! $chat_id)
        {
            return; // chưa cấu hình -> bỏ qua, không báo lỗi
        }

        $type_labels = array('IN' => 'Nhập kho', 'OUT' => 'Xuất kho', 'ADJUST' => 'Kiểm kho');
        $type_icons = array('IN' => '📥', 'OUT' => '📤', 'ADJUST' => '📋');
        $action = isset($type_labels[$type]) ? $type_labels[$type] : $type;
        $icon = isset($type_icons[$type]) ? $type_icons[$type] : '📦';
        $category_text = ! empty($category_names) ? implode(', ', $category_names) : 'nhiều danh mục';

        $lines = array();
        $lines[] = $icon.' <b>'.$this->_esc($user_name).'</b> '.$this->_esc($action).' <u>'.$this->_esc($category_text).'</u>:';
        $lines[] = '';
        $lines[] = '⚠️ SL dưới mức an toàn:';

        $i = 1;
        foreach ($low_items as $it)
        {
            $qty = rtrim(rtrim(number_format($it['qty_on_hand'], 2, '.', ''), '0'), '.');
            $lines[] = $i.'. <b>'.$this->_esc($it['name']).'</b> - '.$qty.' '.$this->_esc($it['unit_name']);
            $i++;
        }

        $this->_send($token, $chat_id, implode("\n", $lines));
    }

    /**
     * Trả lời lệnh /low-stock từ Telegram — báo danh sách sản phẩm sắp hết
     * hàng (tồn < ngưỡng) trong 1 danh mục, hoặc tất cả nếu không lọc.
     * $items: mỗi phần tử cần có 'name', 'unit_name', 'qty_on_hand',
     * 'low_stock_threshold'.
     */
    public function send_low_stock_report($chat_id, $category_label, $items)
    {
        $token = $this->ci->config->item('telegram_bot_token', 'telegram');
        if ( ! $token || ! $chat_id)
        {
            return;
        }

        $count = count($items);
        if ($count === 0)
        {
            $this->_send($token, $chat_id, '✅ Không có sản phẩm nào sắp hết hàng — <u>'.$this->_esc($category_label).'</u>.');
            return;
        }

        $lines = array();
        $lines[] = '⚠️ <b>GẦN HẾT HÀNG</b> — '.$count.' sản phẩm ('.$this->_esc($category_label).')';
        $lines[] = '';

        $i = 1;
        foreach ($items as $it)
        {
            $qty = rtrim(rtrim(number_format($it['qty_on_hand'], 2, '.', ''), '0'), '.');
            $threshold = rtrim(rtrim(number_format($it['low_stock_threshold'], 2, '.', ''), '0'), '.');
            $lines[] = $i.'. <b>'.$this->_esc($it['name']).'</b>: '.$qty.' / '.$threshold.' '.$this->_esc($it['unit_name']);
            $i++;
        }

        $this->_send($token, $chat_id, implode("\n", $lines));
    }

    /** Trả lời lỗi/thông báo chung cho 1 lệnh Telegram (vd danh mục không tồn tại). */
    public function send_raw($chat_id, $text)
    {
        $token = $this->ci->config->item('telegram_bot_token', 'telegram');
        if ( ! $token || ! $chat_id)
        {
            return;
        }
        $this->_send($token, $chat_id, $text);
    }

    /**
     * Gửi bàn phím inline — mỗi nút là 1 danh mục, bấm vào là ra ngay báo
     * cáo tồn kho (không cần gõ tay tên danh mục). $categories: mỗi phần
     * tử cần có 'id', 'name'.
     */
    public function send_category_picker($chat_id, $categories)
    {
        $token = $this->ci->config->item('telegram_bot_token', 'telegram');
        if ( ! $token || ! $chat_id)
        {
            return;
        }

        $rows = array();
        $row = array();
        foreach ($categories as $c)
        {
            $row[] = array('text' => $c['name'], 'callback_data' => 'lsc:'.$c['id']);
            if (count($row) === 2)
            {
                $rows[] = $row;
                $row = array();
            }
        }
        if ( ! empty($row))
        {
            $rows[] = $row;
        }
        $rows[] = array(array('text' => '📋 Tất cả danh mục', 'callback_data' => 'lsc:0'));

        $this->_send($token, $chat_id, '📦 Chọn danh mục để xem tồn kho:', array('inline_keyboard' => $rows));
    }

    /** Tắt trạng thái "đang tải" trên nút vừa bấm — bắt buộc gọi sau mỗi callback_query. */
    public function answer_callback_query($callback_query_id)
    {
        $token = $this->ci->config->item('telegram_bot_token', 'telegram');
        if ( ! $token || ! $callback_query_id)
        {
            return;
        }

        $ch = curl_init('https://api.telegram.org/bot'.$token.'/answerCallbackQuery');
        curl_setopt_array($ch, array(
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => http_build_query(array('callback_query_id' => $callback_query_id)),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ));
        curl_exec($ch);
        curl_close($ch);
    }

    private function _esc($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }

    private function _send($token, $chat_id, $text, $reply_markup = NULL)
    {
        $fields = array(
            'chat_id'    => $chat_id,
            'text'       => $text,
            'parse_mode' => 'HTML',
        );
        if ($reply_markup !== NULL)
        {
            $fields['reply_markup'] = json_encode($reply_markup);
        }

        $ch = curl_init('https://api.telegram.org/bot'.$token.'/sendMessage');
        curl_setopt_array($ch, array(
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ));
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error)
        {
            log_message('error', 'Telegram notify: '.$curl_error);
            return;
        }

        $decoded = json_decode($response, TRUE);
        if ( ! is_array($decoded) || empty($decoded['ok']))
        {
            log_message('error', 'Telegram notify failed: '.$response);
        }
    }
}
