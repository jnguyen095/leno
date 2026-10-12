<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Khuyến mãi trên tổng đơn: giảm % (giới hạn tối đa tuỳ chọn) hoặc số tiền cố định, cho đơn từ một giá trị tối thiểu.
 * Hiệu lực theo khoảng ngày + thứ trong tuần (giống bảng giá). Người bán tự chọn cho đơn trên POS — không tự áp.
 */
class Discount_model extends CI_Model
{
    protected $table = 'discounts';

    public function get_all()
    {
        return $this->db->order_by('status', 'ASC')->order_by('start_date', 'DESC')->order_by('id', 'DESC')
            ->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', (int) $id)->get($this->table)->row_array();
    }

    public function save($id, array $data, $user_id)
    {
        $now = date('Y-m-d H:i:s');
        if ($id)
        {
            $this->db->where('id', (int) $id)->update($this->table, $data + array('updated_at' => $now));
            return (int) $id;
        }
        $this->db->insert($this->table, $data + array('created_by' => $user_id, 'created_at' => $now, 'updated_at' => $now));
        return (int) $this->db->insert_id();
    }

    public function set_status($id, $status)
    {
        return $this->db->where('id', (int) $id)->update($this->table, array('status' => $status, 'updated_at' => date('Y-m-d H:i:s')));
    }

    public function delete($id)
    {
        // Đơn đang / đã dùng khuyến mãi này giữ nguyên điều kiện đã lưu trên đơn (order_sessions.discount_*).
        return $this->db->where('id', (int) $id)->delete($this->table);
    }

    /** Khuyến mãi có hiệu lực vào ngày $date (mặc định hôm nay): đang bật, trong khoảng ngày, đúng thứ. */
    public function get_applicable($date = NULL)
    {
        $date = $date ?: date('Y-m-d');
        $rows = $this->db->where('status', 'ACTIVE')
            ->where('start_date <=', $date)
            ->group_start()->where('end_date IS NULL', NULL, FALSE)->or_where('end_date >=', $date)->group_end()
            ->order_by('min_order', 'ASC')->order_by('name', 'ASC')
            ->get($this->table)->result_array();

        $weekday = (string) date('N', strtotime($date));
        return array_values(array_filter($rows, function ($d) use ($weekday) {
            return in_array($weekday, explode(',', (string) $d['weekdays']), TRUE);
        }));
    }

    public function find_applicable($id, $date = NULL)
    {
        foreach ($this->get_applicable($date) as $d)
        {
            if ((int) $d['id'] === (int) $id) return $d;
        }
        return NULL;
    }

    /**
     * Số tiền giảm cho tạm tính $subtotal theo điều kiện ($type PERCENT|AMOUNT, $value, $max, $min_order).
     * Chưa đủ giá trị tối thiểu = 0; không bao giờ giảm quá tạm tính. Làm tròn tới đồng.
     */
    public static function compute($type, $value, $max, $min_order, $subtotal)
    {
        $subtotal = (float) $subtotal;
        if ($subtotal <= 0 || $subtotal < (float) $min_order) return 0.0;

        if ($type === 'PERCENT')
        {
            $amount = round($subtotal * (float) $value / 100);
            if ($max !== NULL && (float) $max > 0) $amount = min($amount, (float) $max);
        }
        else
        {
            $amount = (float) $value;
        }
        return (float) min($amount, $subtotal);
    }

    /** "Giảm 15% (tối đa 50.000) · đơn từ 500.000" — mô tả ngắn hiển thị trên POS / quản trị. */
    public static function describe($type, $value, $max, $min_order)
    {
        $money = function ($v) { return number_format((float) $v, 0, ',', '.'); };
        $text = $type === 'PERCENT'
            ? 'Giảm '.rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',').'%'.($max !== NULL && (float) $max > 0 ? ' (tối đa '.$money($max).')' : '')
            : 'Giảm '.$money($value).'đ';
        if ((float) $min_order > 0) $text .= ' · đơn từ '.$money($min_order);
        return $text;
    }
}
