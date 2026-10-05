<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Doanh thu nhập tay theo tháng + danh mục — bán hàng qua POS ngoài, không
 * còn tính tự động từ orders trong app này (xem thay thế cho Reports cũ).
 * 4 danh mục cố định: KHU_VUI_CHOI, NUOC_DO_AN, PICKLEBALL, PHOTOBOOTH.
 */
class Migration_Create_monthly_revenue_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id'         => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'period'     => array('type' => 'CHAR', 'constraint' => 7), // 'YYYY-MM'
            'category'   => array('type' => 'ENUM', 'constraint' => array('KHU_VUI_CHOI', 'NUOC_DO_AN', 'PICKLEBALL', 'PHOTOBOOTH')),
            'revenue'    => array('type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0),
            'note'       => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => TRUE),
            'created_at' => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at' => array('type' => 'DATETIME', 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('monthly_revenue', TRUE, array('ENGINE' => 'InnoDB'));
        $this->db->query('ALTER TABLE monthly_revenue ADD UNIQUE KEY uq_mr_period_category (period, category)');
    }

    public function down()
    {
        $this->dbforge->drop_table('monthly_revenue', TRUE);
    }
}
