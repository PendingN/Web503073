<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

unset($_SESSION['user_email']);
set_flash('Bạn đã đăng xuất khỏi phiên làm việc.');
redirect_to('login.php');
