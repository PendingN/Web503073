<?php
declare(strict_types=1);

$authMode = ($authMode ?? 'login') === 'register' ? 'register' : 'login';
$isRegister = $authMode === 'register';
$returnTo = $returnTo ?? '';
$email = $email ?? '';
$name = $name ?? '';
$loginError = $loginError ?? null;
$flashMessage = $flashMessage ?? null;
$errors = $errors ?? [];
$signedInUser = current_user();

$loginTitleTag = $isRegister ? 'h2' : 'h1';
$registerTitleTag = $isRegister ? 'h1' : 'h2';
$loginAttr = static fn (string $fieldName): string => $isRegister
    ? 'data-auth-name="' . e($fieldName) . '"'
    : 'name="' . e($fieldName) . '"';
$registerAttr = static fn (string $fieldName): string => $isRegister
    ? 'name="' . e($fieldName) . '"'
    : 'data-auth-name="' . e($fieldName) . '"';
?>
<main id="main-content" class="auth-papercut-stage" tabindex="-1">
    <section
        class="auth-slider-card<?= $isRegister ? ' is-register' : '' ?>"
        data-auth-slider
        data-auth-mode="<?= e($authMode) ?>"
        aria-label="Đăng nhập và Đăng ký"
    >
        <div class="auth-form-container auth-form-container--register"<?= !$isRegister ? ' aria-hidden="true" inert' : '' ?>>
            <div class="auth-papercut-wrap">
                <<?= $registerTitleTag ?> id="register-heading" class="auth-papercut-title">Đăng ký</<?= $registerTitleTag ?>>
                <p class="auth-form-hint">Tạo tài khoản bằng Email</p>

                <?php if ($errors !== []): ?>
                    <div class="login-error" id="register-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>

                <form class="login-form auth-papercut-form" method="post" action="register.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" <?= $registerAttr('return_to') ?> value="<?= e($returnTo) ?>">
                    <div class="form-group">
                        <label class="sr-only" for="register-name">Họ tên</label>
                        <input class="form-control auth-video-input" id="register-name" <?= $registerAttr('name') ?> value="<?= e($isRegister ? $name : '') ?>" autocomplete="name" minlength="2" maxlength="100" placeholder="Họ tên" required<?= $errors !== [] ? ' aria-invalid="true" aria-describedby="register-errors"' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="register-email">Email</label>
                        <input class="form-control auth-video-input" id="register-email" <?= $registerAttr('email') ?> type="email" value="<?= e($isRegister ? $email : '') ?>" autocomplete="email" maxlength="254" placeholder="Email" required<?= $errors !== [] ? ' aria-invalid="true" aria-describedby="register-errors"' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="register-password">Mật khẩu (ít nhất 8 ký tự)</label>
                        <div class="password-field">
                            <input class="form-control auth-video-input" id="register-password" <?= $registerAttr('password') ?> type="password" autocomplete="new-password" minlength="8" maxlength="72" placeholder="Mật khẩu (ít nhất 8 ký tự)" required<?= $errors !== [] ? ' aria-invalid="true" aria-describedby="register-errors"' : '' ?>>
                            <button class="password-toggle" type="button" data-password-toggle aria-controls="register-password" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="register-password-confirmation">Nhập lại mật khẩu</label>
                        <div class="password-field">
                            <input class="form-control auth-video-input" id="register-password-confirmation" <?= $registerAttr('password_confirmation') ?> type="password" autocomplete="new-password" minlength="8" maxlength="72" placeholder="Nhập lại mật khẩu" required<?= $errors !== [] ? ' aria-invalid="true" aria-describedby="register-errors"' : '' ?>>
                            <button class="password-toggle" type="button" data-password-toggle aria-controls="register-password-confirmation" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
                        </div>
                    </div>
                    <button class="btn-brand auth-video-submit" data-auth-submit type="<?= $isRegister ? 'submit' : 'button' ?>">Đăng ký</button>
                </form>

                <p class="auth-mobile-switch">Đã có tài khoản? <a href="login.php?return_to=<?= rawurlencode($returnTo) ?>" data-auth-switch="login">Đăng nhập</a></p>
            </div>
        </div>

        <div class="auth-form-container auth-form-container--login"<?= $isRegister ? ' aria-hidden="true" inert' : '' ?>>
            <div class="auth-papercut-wrap">
                <<?= $loginTitleTag ?> id="login-heading" class="auth-papercut-title">Đăng nhập</<?= $loginTitleTag ?>>
                <p class="auth-form-hint">Đăng nhập bằng Email &amp; Mật khẩu</p>

                <?php if ($signedInUser !== null): ?>
                    <p class="login-help auth-papercut-notice">Bạn đang đăng nhập với tên <?= e($signedInUser['name']) ?>. <a href="profile.php">Mở trang cá nhân</a> hoặc dùng biểu mẫu dưới đây để đăng nhập tài khoản khác.</p>
                <?php endif; ?>

                <?php if ($flashMessage !== null): ?><div class="status-note" role="status"><?= e($flashMessage) ?></div><?php endif; ?>

                <?php if ($loginError !== null): ?>
                    <div class="login-error" id="login-error" role="alert"><?= e($loginError) ?></div>
                <?php endif; ?>

                <form class="login-form auth-papercut-form" method="post" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" <?= $loginAttr('return_to') ?> value="<?= e($returnTo) ?>">
                    <div class="form-group">
                        <label class="sr-only" for="login-email">Email</label>
                        <input class="form-control auth-video-input" id="login-email" <?= $loginAttr('email') ?> type="email" value="<?= e($isRegister ? '' : $email) ?>" autocomplete="email" maxlength="254" placeholder="Email" required<?= $loginError !== null ? ' aria-invalid="true" aria-describedby="login-error"' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="login-password">Mật khẩu</label>
                        <div class="password-field">
                            <input class="form-control auth-video-input" id="login-password" <?= $loginAttr('password') ?> type="password" autocomplete="current-password" maxlength="72" placeholder="Mật khẩu" required<?= $loginError !== null ? ' aria-invalid="true" aria-describedby="login-error"' : '' ?>>
                            <button class="password-toggle" type="button" data-password-toggle aria-controls="login-password" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
                        </div>
                        <a class="auth-forgot-link" href="forgot-password.php">Quên mật khẩu?</a>
                    </div>
                    <button class="btn-brand auth-video-submit" data-auth-submit type="<?= $isRegister ? 'button' : 'submit' ?>">Đăng nhập</button>
                </form>

                <p class="auth-mobile-switch">Chưa có tài khoản? <a href="register.php?return_to=<?= rawurlencode($returnTo) ?>" data-auth-switch="register">Đăng ký</a></p>
            </div>
        </div>

        <div class="auth-overlay-container">
            <div class="auth-overlay">
                <div class="auth-overlay-panel auth-overlay-panel--left">
                    <h2>Chào mừng trở lại!</h2>
                    <p>Đăng nhập bằng tài khoản của bạn để tiếp tục hành trình chăm cây cùng Là Lá La.</p>
                    <a class="auth-ghost-btn" href="login.php?return_to=<?= rawurlencode($returnTo) ?>" data-auth-switch="login">Đăng nhập</a>
                </div>
                <div class="auth-overlay-panel auth-overlay-panel--right">
                    <h2>Chào bạn mới!</h2>
                    <p>Đăng ký ngay để lưu những loài cây yêu thích và khám phá góc xanh của riêng bạn.</p>
                    <a class="auth-ghost-btn" href="register.php?return_to=<?= rawurlencode($returnTo) ?>" data-auth-switch="register">Đăng ký</a>
                </div>
            </div>
        </div>
    </section>
</main>
