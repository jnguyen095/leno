<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public customer-facing QR ordering screen.
 *
 * The physical QR code printed on a table always encodes the permanent
 * /menu/{qr_token} entry URL. Visiting it redirects to a per-visit URL
 * /menu/{qr_token}/{session_secret} — that secret is what the customer's
 * browser actually keeps open/bookmarked. A new table_session always gets a
 * fresh secret, so once the table turns over (payment, or a new session
 * supersedes it) an old bookmarked link stops working immediately, without
 * ever needing to reprint the physical QR code.
 */
class Menu extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('kds');
        $this->load->model(array('Table_model', 'Table_session_model', 'Order_model', 'Product_model'));
    }

    /** Entry point: what's actually printed on the table's QR code. */
    public function index($token)
    {
        $table = $this->Table_model->get_by_token($token);
        if ( ! $table)
        {
            show_404();
        }

        if ($table['status'] === 'AVAILABLE')
        {
            $this->_auto_open_table($table);
            $session = $this->Table_session_model->get_open_by_table($table['id']);
            redirect('menu/'.$token.'/'.$session['session_secret']);
            return;
        }

        if (in_array($table['status'], array('WAIT_PAYMENT', 'PAID'), TRUE))
        {
            $this->load->view('public/menu/closed', array('table' => $table));
            return;
        }

        // OPEN: always send them to the current session's own link.
        $session = $this->Table_session_model->get_open_by_table($table['id']);
        redirect('menu/'.$token.'/'.$session['session_secret']);
    }

    /** The actual ordering page, bound to one specific visit/session. */
    public function visit($token, $secret)
    {
        $table = $this->Table_model->get_by_token($token);
        if ( ! $table)
        {
            show_404();
        }

       /* if ($table['status'] === 'AVAILABLE')
        {
            redirect('menu/'.$token);
            return;
        } */

        if (in_array($table['status'], array('WAIT_PAYMENT', 'PAID'), TRUE))
        {
            $this->load->view('public/menu/closed', array('table' => $table));
            return;
        }

        $check = $this->Table_session_model->validate_session($table['id'], $secret);

        if ( ! $check['valid'])
        {
            $messages = array(
                'NOT_OPEN'   => 'Bàn hiện chưa mở, vui lòng gọi nhân viên hoặc quét lại mã QR trên bàn.',
                'SUPERSEDED' => 'Phiên đặt món này đã kết thúc (bàn đã phục vụ khách mới). Vui lòng quét lại mã QR trên bàn.',
                'EXPIRED'    => 'Phiên đặt món đã hết hạn do quá lâu chưa thanh toán. Vui lòng gọi nhân viên hoặc quét lại mã QR trên bàn.',
                'CLOSED'     => 'Bàn đã thanh toán, cảm ơn quý khách và sớm gặp lại'
            );
            $this->load->view('public/menu/session_ended', array(
                'table'   => $table,
                'message' => $messages[$check['reason']],
            ));
            return;
        }

        $data = array(
            'table'                => $table,
            'token'                => $token,
            'secret'               => $secret,
            'products_by_category' => $this->Product_model->get_active_grouped_by_category(),
        );
        $this->load->view('public/menu/index', $data);
    }

    public function cart($token)
    {
        redirect('menu/'.$token);
    }

    public function history($token)
    {
        redirect('menu/'.$token);
    }

    /**
     * Customer scanned the QR of a free table: open it for them automatically,
     * no staff action required. opened_by is NULL (no logged-in user involved).
     */
    private function _auto_open_table(&$table)
    {
        $result = $this->Order_model->open_table_with_order($table['id']);

        $this->load->model('Audit_log_model');
        $this->Audit_log_model->log('table', 'AUTO_OPEN_TABLE', NULL, array('table_id' => $table['id'], 'session_id' => $result['session_id']));

        $table['status'] = 'OPEN';
    }
}
