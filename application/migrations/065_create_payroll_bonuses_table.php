<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Thưởng ngoài lương — nhiều dòng/nhân viên/tháng (khác payroll_records, chỉ
 * 1 dòng/tháng). Mỗi dòng là MỘT khoản thưởng: theo số tiền cố định (VD:
 * "Thưởng 200.000 dịp lễ 2/7") hoặc theo giờ (số giờ × đơn giá/giờ tại thời
 * điểm nhập — rate lưu lại để không bị lệch nếu sau này đổi mức lương). amount
 * luôn là số tiền cuối cùng (VND), dùng trực tiếp khi cộng vào lương — xem
 * payroll_helper::payroll_compute().
 */
class Migration_Create_payroll_bonuses_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'         => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'user_id'    => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE),
            'period'     => array('type' => 'CHAR', 'constraint' => 7), // 'YYYY-MM'
            'bonus_type' => array('type' => 'ENUM', 'constraint' => array('AMOUNT', 'HOURLY'), 'default' => 'AMOUNT'),
            'hours'      => array('type' => 'DECIMAL', 'constraint' => '5,2', 'null' => TRUE),
            'rate'       => array('type' => 'DECIMAL', 'constraint' => '12,2', 'null' => TRUE),
            'amount'     => array('type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0),
            'note'       => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('payroll_bonuses', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE payroll_bonuses ADD KEY idx_pb_user_period (user_id, period)');
    }

    public function down()
    {
        $this->dbforge->drop_table('payroll_bonuses', TRUE);
    }
}
