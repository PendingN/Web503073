<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$returnTo = safe_return_url($_POST['return_to'] ?? $_GET['return_to'] ?? null, 'dashboard.php');
if (current_user() !== null) {
    redirect_to($returnTo);
}
$errors = [];
$name = '';
$email = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $name = trim(request_string($_POST, 'name'));
    $email = strtolower(trim(request_string($_POST, 'email')));
    $password = request_string($_POST, 'password');
    $confirmation = request_string($_POST, 'password_confirmation');
    $errors = account_errors($name, $email);
    if (!valid_password($password)) {
        $errors[] = 'Mật khẩu cần ít nhất 8 ký tự và tối đa 72 byte (ký tự có dấu chiếm nhiều byte).';
    }
    if ($password !== $confirmation) {
        $errors[] = 'Mật khẩu nhập lại chưa khớp.';
    }
    if ($errors === [] && email_in_use($email)) {
        $errors[] = 'Email này đã được đăng ký.';
    }
    if ($errors === []) {
        try {
            // Public registration always creates an ordinary user.
            $id = create_user($name, $email, $password);
            sign_in(find_user($id));
            set_flash('Đăng ký thành công. Chào mừng bạn đến với khu vườn!');
            redirect_to($returnTo);
        } catch (PDOException $exception) {
            if (!duplicate_email_error($exception)) {
                throw $exception;
            }
            $errors[] = 'Email này đã được đăng ký.';
        }
    }
}
$pageTitle = 'Đăng ký';
$bodyClass = 'login-page';
$activePage = 'login';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main class="login-layout">
    <section class="brand-pane" aria-label="Giới thiệu Vũ Điệu Rừng Xanh">
        <div class="auth-brand-copy">
            <p class="eyebrow">VŨ ĐIỆU RỪNG XANH</p>
            <h1>Một góc xanh<br>của riêng bạn.</h1>
            <p>Tạo tài khoản để lưu những giống cây yêu thích và tiếp tục hành trình chăm cây từ bất kỳ thiết bị nào.</p>
        </div>
    </section>
    <section class="login-form-panel">
        <div class="login-form-wrap">
            <p class="eyebrow">BẮT ĐẦU HÀNH TRÌNH</p>
            <h1>Đăng ký</h1>
            <p>Chào đón một người làm vườn mới.</p>
            <?php if ($errors !== []): ?>
                <div class="login-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form class="login-form" method="post" action="register.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <div class="form-group">
                    <label class="form-label" for="name">Họ tên</label>
                    <input class="form-control" id="name" name="name" value="<?= e($name) ?>" autocomplete="name" minlength="2" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" maxlength="254" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu (ít nhất 8 ký tự)</label>
                    <div class="password-field">
                        <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                        <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Hiện mật khẩu">Hiện</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Nhập lại mật khẩu</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                </div>
                <button class="btn-brand" type="submit">Tạo tài khoản ↗</button>
            </form>
            <p class="login-help">Đã có tài khoản? <a href="login.php?return_to=<?= rawurlencode($returnTo) ?>">Đăng nhập</a></p>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
