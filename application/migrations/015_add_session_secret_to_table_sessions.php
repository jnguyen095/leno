<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Per-visit secret embedded in the customer's menu URL after they scan the
 * table's (permanent) qr_token. A new session always gets a fresh secret, so
 * a bookmarked/stale link from a previous visit stops working the moment the
 * table is reused — without ever having to reprint the physical QR code.
 */
class Migration_Add_session_secret_to_table_sessions extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE table_sessions ADD COLUMN session_secret CHAR(32) NULL AFTER session_no');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE table_sessions DROP COLUMN session_secret');
    }
}
