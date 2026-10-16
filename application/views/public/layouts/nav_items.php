<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Menu website public — dùng chung cho header, footer và trang chủ (khối dịch vụ).
 * Mỗi mục: [đường dẫn (rỗng = trang chủ), icon Bootstrap, tên]. Trang /page/<code>.html soạn ở Quản trị → Trang nội dung.
 * Dùng: $nav = include APPPATH.'views/public/layouts/nav_items.php';
 */
return array(
    array('',                   'bi-house-door',   'Trang chủ'),
    array('page/khu-vui-choi',  'bi-balloon',      'Khu vui chơi'),
    array('page/cafe-anvat',    'bi-cup-hot',      'Cà phê & Ăn vặt'),
    array('page/photobooth',    'bi-camera',       'Photobooth'),
    array('page/pickleball',    'bi-trophy',       'Pickleball'),
    array('page/khuyen-mai',    'bi-gift',         'Khuyến mãi'),
);
