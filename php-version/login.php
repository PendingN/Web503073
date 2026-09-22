<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$loginError = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === 'demo@dv03.vn' && $password === '123456') {
        session_regenerate_id(true);
        $_SESSION['user_email'] = $email;
        set_flash('Đăng nhập thành công. Chào mừng bạn trở lại!');
        redirect_to('dashboard.php');
    }

    $loginError = 'Email hoặc mật khẩu chưa đúng. Hãy dùng tài khoản demo bên dưới.';
}

$pageTitle = 'Đăng nhập';
$pageDescription = 'Đăng nhập vào không gian xanh của bạn.';
$bodyClass = 'login-page';
$activePage = 'login';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main class="login-layout">
    <section class="brand-pane" aria-label="Giới thiệu Vũ Điệu Rừng Xanh">
        <div style="position:relative;z-index:1;max-width:520px;margin:auto;">
            <p class="eyebrow" style="color:#dfe8d2;">VŨ ĐIỆU RỪNG XANH</p>
            <h1 style="font-size:clamp(38px,6vw,76px);margin:0 0 18px;">Một góc xanh<br>cho mỗi ngày.</h1>
            <p style="max-width:430px;line-height:1.9;color:rgba(255,255,255,.84);">Lưu lại những giống cây bạn yêu thích và khám phá cách chăm sóc phù hợp với không gian của bạn.</p>
        </div>
    </section>
    <section class="login-form-panel">
        <div class="login-form-wrap">
            <p class="eyebrow">CHÀO MỪNG TRỞ LẠI</p>
            <h1>Đăng nhập</h1>
            <p>Tiếp tục hành trình chăm cây của bạn.</p>

            <?php if ($loginError !== null): ?>
                <div class="login-error" role="alert"><?= e($loginError) ?></div>
            <?php endif; ?>

            <form class="login-form" method="post" action="login.php">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="password-field">
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Hiện mật khẩu">Hiện</button>
                    </div>
                </div>
                <button class="btn-brand" type="submit">Đăng nhập ↗</button>
            </form>

            <div class="demo-note" style="margin-top:22px;">
                <strong>Tài khoản demo</strong><br>
                Email: demo@dv03.vn<br>
                Mật khẩu: 123456
            </div>
            <p class="login-help">Chưa muốn đăng nhập? <a href="dashboard.php">Khám phá dashboard</a></p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
