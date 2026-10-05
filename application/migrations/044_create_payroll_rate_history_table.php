<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lịch sử mức lương theo thời gian — payroll_settings chỉ giữ giá trị
 * "hiện tại/mặc định" (dùng để hiển thị sẵn trên form sửa + fallback cho
 * tháng chưa có lịch sử), còn số thực sự dùng để TÍNH lương 1 tháng cụ thể
 * lấy từ đây: dòng có effective_from gần nhất mà <= tháng đó. Nhờ vậy đổi
 * lương "từ tháng sau" không làm sai lệch lương các tháng trước đã tính.
 *
 * Backfill: mỗi nhân viên đã có payroll_settings được tạo 1 dòng lịch sử
 * hiệu lực từ tháng hiện tại — các tháng TRƯỚC đó (đã tính rồi) vẫn dùng
 * payroll_settings hiện tại như hành vi cũ (không có dữ liệu lịch sử thật
 * để khôi phục chính xác), nhưng từ nay mọi thay đổi mức lương đều được ghi
 * lại đúng thời điểm áp dụng.
 */
class Migration_Create_payroll_rate_history_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'             => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'user_id'        => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'salary_type'    => array('type' => 'ENUM', 'constraint' => array('FIXED', 'HOURLY')),
            'fixed_salary'   => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'hourly_rate'    => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'effective_from' => array('type' => 'CHAR', 'constraint' => 7), // 'YYYY-MM'
            'created_by'     => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at'     => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('payroll_rate_history', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE payroll_rate_history ADD UNIQUE KEY uq_prh_user_period (user_id, effective_from)');

        $current_period = date('Y-m');
        $existing = $this->db->get('payroll_settings')->result_array();
        foreach ($existing as $s)
        {
            $this->db->insert('payroll_rate_history', array(
                'user_id'        => $s['user_id'],
                'salary_type'    => $s['salary_type'],
                'fixed_salary'   => $s['fixed_salary'],
                'hourly_rate'    => $s['hourly_rate'],
                'effective_from' => $current_period,
                'created_at'     => date('Y-m-d H:i:s'),
            ));
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('payroll_rate_history', TRUE);
    }
}
