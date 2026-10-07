<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Token đăng nhập cho ứng dụng di động/tablet (Flutter) gọi /api/v1/*.
 * Chỉ lưu SHA-256 của token; token gốc chỉ trả về một lần lúc đăng nhập.
 */
class Migration_Api_tokens extends CI_Migration
{
    public function up()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_at_token_hash` (`token_hash`),
  KEY `idx_at_user` (`user_id`),
  CONSTRAINT `fk_at_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `api_tokens`');
    }
}
