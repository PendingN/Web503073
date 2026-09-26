<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$loginError = null;
$email = '';
$returnTo = safe_return_url($_POST['return_to'] ?? $_GET['return_to'] ?? null, 'dashboard.php');
$flashMessage = take_flash();
if (current_user() !== null) {
    redirect_to($returnTo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = strtolower(trim(request_string($_POST, 'email')));
    $password = request_string($_POST, 'password');
    $user = strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) ? find_user_by_email($email) : null;
    if ($user !== null && strlen($password) <= 72 && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'blocked') {
            $loginError = 'Tài khoản đang bị khóa. Vui lòng liên hệ quản trị viên.';
        } else {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $statement = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $statement->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            sign_in($user);
            set_flash('Đăng nhập thành công. Chào mừng bạn trở lại!');
            redirect_to($returnTo);
        }
    } else {
        $loginError = 'Email hoặc mật khẩu chưa đúng.';
    }
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

            <?php if ($flashMessage !== null): ?><div class="status-note" role="status"><?= e($flashMessage) ?></div><?php endif; ?>

            <?php if ($loginError !== null): ?>
                <div class="login-error" role="alert"><?= e($loginError) ?></div>
            <?php endif; ?>

            <form class="login-form" method="post" action="login.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" maxlength="254" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="password-field">
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" maxlength="72" required>
                        <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Hiện mật khẩu">Hiện</button>
                    </div>
                </div>
                <button class="btn-brand" type="submit">Đăng nhập ↗</button>
            </form>

            <p class="login-help">Chưa có tài khoản? <a href="register.php?return_to=<?= rawurlencode($returnTo) ?>">Đăng ký ngay</a></p>
            <p class="login-help">Chưa muốn đăng nhập? <a href="dashboard.php">Khám phá dashboard</a></p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
