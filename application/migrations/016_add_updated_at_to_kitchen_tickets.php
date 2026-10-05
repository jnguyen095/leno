<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tracks when a ticket last changed status, so the KDS "COMPLETED" column can
 * be sorted by most-recently-finished instead of oldest-created.
 */
class Migration_Add_updated_at_to_kitchen_tickets extends CI_Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE kitchen_tickets ADD COLUMN updated_at DATETIME NULL AFTER created_at');
        $this->db->query('UPDATE kitchen_tickets SET updated_at = created_at WHERE updated_at IS NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE kitchen_tickets DROP COLUMN updated_at');
    }
}
