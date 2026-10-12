<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bảng giá: giá bán riêng theo dịp (cuối tuần, ưu đãi phụ nữ...). Người bán tự chọn bảng giá cho đơn
 * trên POS — không tự áp. Món không có giá trong bảng giá = giữ giá gốc.
 */
class Price_book_model extends CI_Model
{
    protected $table = 'price_books';

    /** Thứ trong tuần theo ISO-8601 (date('N')): 1 = Thứ 2 ... 7 = Chủ nhật. */
    const WEEKDAYS = array(1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN');

    public function get_all()
    {
        return $this->db->select('price_books.*, (SELECT COUNT(*) FROM price_book_items i WHERE i.price_book_id = price_books.id) AS item_count', FALSE)
            ->order_by('status', 'ASC')->order_by('start_date', 'DESC')->order_by('id', 'DESC')
            ->get($this->table)->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', (int) $id)->get($this->table)->row_array();
    }

    /** product_id => giá mới. */
    public function get_prices($book_id)
    {
        $map = array();
        foreach ($this->db->where('price_book_id', (int) $book_id)->get('price_book_items')->result_array() as $r)
        {
            $map[(int) $r['product_id']] = (float) $r['price'];
        }
        return $map;
    }

    /**
     * Lưu bảng giá + giá từng món. $prices: product_id => giá mới (NULL/'' = giữ giá gốc, không lưu dòng).
     * Trả về id bảng giá.
     */
    public function save($id, array $data, array $prices, $user_id)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        if ($id)
        {
            $this->db->where('id', (int) $id)->update($this->table, $data + array('updated_at' => $now));
        }
        else
        {
            $this->db->insert($this->table, $data + array('created_by' => $user_id, 'created_at' => $now, 'updated_at' => $now));
            $id = $this->db->insert_id();
        }

        // Chỉ thay giá các món có trên form; giá của món đang ẩn (ngừng bán) vẫn giữ nguyên.
        if ($prices)
        {
            $this->db->where('price_book_id', (int) $id)->where_in('product_id', array_map('intval', array_keys($prices)))->delete('price_book_items');
        }
        $rows = array();
        foreach ($prices as $product_id => $price)
        {
            if ($price === NULL || $price === '') continue;
            $rows[] = array('price_book_id' => (int) $id, 'product_id' => (int) $product_id, 'price' => (float) $price);
        }
        if ($rows)
        {
            $this->db->insert_batch('price_book_items', $rows);
        }
        $this->db->trans_complete();
        return (int) $id;
    }

    public function set_status($id, $status)
    {
        return $this->db->where('id', (int) $id)->update($this->table, array('status' => $status, 'updated_at' => date('Y-m-d H:i:s')));
    }

    public function delete($id)
    {
        // Đơn đã dùng bảng giá này vẫn giữ tên (price_book_name) và giá đã bán.
        return $this->db->where('id', (int) $id)->delete($this->table);
    }

    /** Bảng giá có hiệu lực vào ngày $date (Y-m-d, mặc định hôm nay): đang bật, trong khoảng ngày, đúng thứ. */
    public function get_applicable($date = NULL)
    {
        $date = $date ?: date('Y-m-d');
        $rows = $this->db->where('status', 'ACTIVE')
            ->where('start_date <=', $date)
            ->group_start()->where('end_date IS NULL', NULL, FALSE)->or_where('end_date >=', $date)->group_end()
            ->order_by('name', 'ASC')
            ->get($this->table)->result_array();

        $weekday = (string) date('N', strtotime($date));
        return array_values(array_filter($rows, function ($b) use ($weekday) {
            return in_array($weekday, explode(',', (string) $b['weekdays']), TRUE);
        }));
    }

    public function is_applicable($book_id, $date = NULL)
    {
        foreach ($this->get_applicable($date) as $b)
        {
            if ((int) $b['id'] === (int) $book_id) return TRUE;
        }
        return FALSE;
    }

    /** "T2, T3, T7" / "Mọi ngày" — hiển thị các thứ áp dụng. */
    public static function weekdays_label($weekdays)
    {
        $days = array_filter(array_map('intval', explode(',', (string) $weekdays)));
        if (count($days) === 7) return 'Mọi ngày';
        sort($days);
        return implode(', ', array_map(function ($d) { return Price_book_model::WEEKDAYS[$d]; }, $days));
    }
}
