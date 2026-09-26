<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_post();
require_csrf();
$_SESSION = [];
session_regenerate_id(true);
set_flash('Bạn đã đăng xuất.');
redirect_to(app_url('login.php'));
