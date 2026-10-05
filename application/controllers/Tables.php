<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tables extends MY_Controller
{
    protected $allowed_roles = array('STAFF', 'ADMIN', 'CASHIER');

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Table_model', 'Table_session_model', 'Order_model', 'Order_item_model', 'Kitchen_ticket_model', 'Setting_model'));
    }

    public function index()
    {
        $tables = $this->Table_model->get_all();

        // Bàn "Mang đi" đứng đầu sơ đồ khi bật bán mang đi; nếu đã tắt nhưng còn đơn dở thì vẫn hiện để xử lý cho xong.
        $takeaway = $this->Table_model->get_takeaway();
        if ($takeaway && ($this->Setting_model->is_takeaway_enabled() || $takeaway['status'] !== 'AVAILABLE'))
        {
            array_unshift($tables, $takeaway);
        }

        foreach ($tables as &$t)
        {
            $t['order'] = NULL;
            if ($t['status'] !== 'AVAILABLE')
            {
                $session = $this->Table_session_model->get_open_by_table($t['id']);
                if ($session)
                {
                    $t['order'] = $this->Order_model->get_active_by_table_session($session['id']);
                    $t['session_id'] = $session['id'];
                }
            }
        }

        $data = array(
            'page_title'   => 'Sơ đồ bàn',
            'current_user' => $this->current_user,
            'tables'       => $tables,
            'paid_order_id' => $this->session->flashdata('paid_order_id'),
        );
        $data = array_merge($data, $this->pos_tabs_data('tables'));
        $this->load->view('layout/header', $data);
        $this->load->view('tables/index', $data);
        $this->load->view('layout/footer');
    }

    public function open($id)
    {
        $table = $this->Table_model->get_by_id($id);
        if ( ! $table || $table['status'] !== 'AVAILABLE'
            || ($table['is_takeaway'] && ! $this->Setting_model->is_takeaway_enabled()))
        {
            redirect('me/tables');
            return;
        }

        $result = $this->Order_model->open_table_with_order($id, $this->current_user['id']);
        $order_id = $result['order_id'];

        $this->audit('table', 'OPEN_TABLE', NULL, array('table_id' => $id, 'session_id' => $result['session_id'], 'order_id' => $order_id));

        redirect('me/orders/'.$order_id);
    }

    public function detail($id)
    {
        $table = $this->Table_model->get_by_id($id);
        if ( ! $table)
        {
            show_404();
        }
        $session = $this->Table_session_model->get_open_by_table($id);
        if ( ! $session)
        {
            redirect('me/tables');
            return;
        }
        $order = $this->Order_model->get_active_by_table_session($session['id']);
        redirect('me/orders/'.$order['id']);
    }

    public function transfer($id)
    {
        $table = $this->Table_model->get_by_id($id);
        $session = $this->Table_session_model->get_open_by_table($id);

        if ($this->input->method() === 'post')
        {
            $target_id = (int) $this->input->post('target_table_id');
            $target = $this->Table_model->get_by_id($target_id);

            if ($target && $target['status'] === 'AVAILABLE' && $session)
            {
                $this->db->where('id', $session['id'])->update('table_sessions', array('table_id' => $target_id));
                $order = $this->Order_model->get_active_by_table_session($session['id']);
                if ($order)
                {
                    $this->db->where('order_session_id', $order['id'])->update('kitchen_tickets', array('table_id' => $target_id));
                }
                $this->Table_model->set_status($id, 'AVAILABLE');
                $this->Table_model->set_status($target_id, $table['status']);

                $this->audit('table', 'TRANSFER', array('from' => $id), array('to' => $target_id));
            }
            redirect('me/tables');
            return;
        }

        $available = array_filter($this->Table_model->get_all(), function ($t) { return $t['status'] === 'AVAILABLE'; });
        $data = array(
            'page_title'   => 'Chuyển bàn',
            'current_user' => $this->current_user,
            'table'        => $table,
            'available'    => $available,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('tables/transfer', $data);
        $this->load->view('layout/footer');
    }

    public function merge($id)
    {
        $table = $this->Table_model->get_by_id($id);
        $session = $this->Table_session_model->get_open_by_table($id);

        if ($this->input->method() === 'post')
        {
            $target_table_id = (int) $this->input->post('target_table_id');
            $target_session = $this->Table_session_model->get_open_by_table($target_table_id);

            if ($session && $target_session)
            {
                $source_order = $this->Order_model->get_active_by_table_session($session['id']);
                $target_order = $this->Order_model->get_active_by_table_session($target_session['id']);

                if ($source_order && $target_order)
                {
                    $this->db->where('order_session_id', $source_order['id'])->update('order_items', array('order_session_id' => $target_order['id']));
                    $this->db->where('order_session_id', $source_order['id'])->update('kitchen_tickets', array('order_session_id' => $target_order['id'], 'table_id' => $target_table_id));
                    $this->Order_model->cancel($source_order['id']);
                    $this->Order_model->recalc_totals($target_order['id']);
                    $this->Table_session_model->close($session['id']);
                    $this->Table_model->set_status($id, 'AVAILABLE');

                    $this->audit('table', 'MERGE', array('from' => $id), array('into' => $target_table_id));

                    redirect('me/orders/'.$target_order['id']);
                    return;
                }
            }
            redirect('me/tables');
            return;
        }

        $others = array_filter($this->Table_model->get_all(), function ($t) use ($id) {
            return $t['status'] === 'OPEN' && (int) $t['id'] !== (int) $id;
        });
        $data = array(
            'page_title'   => 'Gộp bàn',
            'current_user' => $this->current_user,
            'table'        => $table,
            'others'       => $others,
        );
        $this->load->view('layout/header', $data);
        $this->load->view('tables/merge', $data);
        $this->load->view('layout/footer');
    }

    public function manage()
    {
        $this->_require_admin();

        $data = array(
            'page_title'   => 'Quản lý bàn',
            'current_user' => $this->current_user,
            'tables'       => $this->Table_model->get_all(),
        );
        $this->load->view('layout/header', $data);
        $this->load->view('tables/manage', $data);
        $this->load->view('layout/footer');
    }

    public function manage_create()
    {
        $this->_require_admin();
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $code = $this->input->post('table_code', TRUE);

            if ($this->Table_model->code_exists($code))
            {
                $error = 'Mã bàn đã tồn tại.';
            }
            else
            {
                $id = $this->Table_model->create(array(
                    'table_code'     => $code,
                    'table_name'     => $this->input->post('table_name', TRUE),
                    'capacity'       => (int) $this->input->post('capacity'),
                    'sort_order'     => (int) $this->input->post('sort_order'),
                    'status'         => 'AVAILABLE',
                ));
                $this->audit('table', 'CREATE', NULL, array('id' => $id));
                redirect('me/tables/manage');
                return;
            }
        }

        $data = array('page_title' => 'Thêm bàn', 'current_user' => $this->current_user, 'table' => NULL, 'error' => $error);
        $this->load->view('layout/header', $data);
        $this->load->view('tables/manage_form', $data);
        $this->load->view('layout/footer');
    }

    public function manage_edit($id)
    {
        $this->_require_admin();
        $table = $this->Table_model->get_by_id($id);
        if ( ! $table || $table['is_takeaway']) show_404();
        $error = NULL;

        if ($this->input->method() === 'post')
        {
            $code = $this->input->post('table_code', TRUE);

            if ($this->Table_model->code_exists($code, $id))
            {
                $error = 'Mã bàn đã tồn tại.';
            }
            else
            {
                $this->Table_model->update($id, array(
                    'table_code'     => $code,
                    'table_name'     => $this->input->post('table_name', TRUE),
                    'capacity'       => (int) $this->input->post('capacity'),
                    'sort_order'     => (int) $this->input->post('sort_order'),
                ));
                $this->audit('table', 'UPDATE', $table, array('id' => $id));
                redirect('me/tables/manage');
                return;
            }
        }

        $data = array('page_title' => 'Sửa bàn', 'current_user' => $this->current_user, 'table' => $table, 'error' => $error);
        $this->load->view('layout/header', $data);
        $this->load->view('tables/manage_form', $data);
        $this->load->view('layout/footer');
    }

    public function manage_delete($id)
    {
        $this->_require_admin();
        $table = $this->Table_model->get_by_id($id);

        if ($table && ! $table['is_takeaway'] && $table['status'] === 'AVAILABLE')
        {
            $has_history = $this->db->where('table_id', $id)->get('table_sessions')->num_rows() > 0;

            if ($has_history)
            {
                $this->session->set_flashdata('error', 'Không thể xóa bàn đã có lịch sử sử dụng.');
            }
            else
            {
                $this->Table_model->delete($id);
                $this->audit('table', 'DELETE', $table, NULL);
            }
        }
        else
        {
            $this->session->set_flashdata('error', 'Chỉ có thể xóa bàn đang trống.');
        }

        redirect('me/tables/manage');
    }

    public function manage_reset_status($id)
    {
        $this->_require_admin();
        $table = $this->Table_model->get_by_id($id);

        if ($table && $table['status'] !== 'AVAILABLE')
        {
            $session = $this->Table_session_model->get_open_by_table($id);
            if ($session)
            {
                $order = $this->Order_model->get_active_by_table_session($session['id']);
                if ($order) $this->Order_model->cancel($order['id']);
                $this->Table_session_model->close($session['id']);
            }
            $this->Table_model->set_status($id, 'AVAILABLE');
            $this->audit('table', 'FORCE_RESET', $table, array('id' => $id));
        }

        redirect('me/tables/manage');
    }

    private function _require_admin()
    {
        if ($this->current_user['role'] !== 'ADMIN')
        {
            $this->output->set_status_header(403);
            $this->load->view('errors/forbidden', array('current_user' => $this->current_user));
            exit;
        }
    }
}
