<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$user = require_login();

$pageTitle = 'Cài đặt tài khoản';
$bodyClass = 'profile-page';
$activePage = 'profile';
$errors = $_SESSION['profile_errors'] ?? [];
$input = $_SESSION['profile_input'] ?? ['name' => $user['name'], 'email' => $user['email']];
$openSection = $_SESSION['profile_section'] ?? '';
unset($_SESSION['profile_errors'], $_SESSION['profile_input'], $_SESSION['profile_section']);
$flashMessage = take_flash();
$describedBy = $errors !== [] ? ' aria-describedby="profile-errors"' : '';

function settings_error_box(array $errors): void
{
    if ($errors === []) {
        return;
    }
    echo '<div class="login-error" id="profile-errors" role="alert"><ul>';
    foreach ($errors as $error) {
        echo '<li>' . e($error) . '</li>';
    }
    echo '</ul></div>';
}

function password_input(string $id, string $name, string $label, string $autocomplete, string $describedBy, bool $required): void
{
    ?>
    <div class="form-group">
        <label class="form-label" for="<?= e($id) ?>"><?= e($label) ?></label>
        <div class="password-field">
            <input class="form-control" id="<?= e($id) ?>" name="<?= e($name) ?>" type="password" autocomplete="<?= e($autocomplete) ?>" maxlength="72"<?= $autocomplete === 'new-password' ? ' minlength="8"' : '' ?><?= $required ? ' required' : '' ?><?= $describedBy ?>>
            <button class="password-toggle" type="button" data-password-toggle aria-controls="<?= e($id) ?>" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
        </div>
    </div>
    <?php
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

        <main id="main-content" class="settings-page" tabindex="-1">
            <a class="settings-back" href="profile.php">← Trang cá nhân</a>
            <h1>Cài đặt tài khoản</h1>
            <?php if ($flashMessage !== null): ?><div class="status-note" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>

            <section class="settings-card" aria-labelledby="settings-account-title">
                <h2 id="settings-account-title">Tài khoản</h2>
                <p class="settings-card-lead">Quản lý thông tin cá nhân và cách bạn đăng nhập.</p>

                <details class="settings-row"<?= $openSection === 'info' ? ' open' : '' ?>>
                    <summary>
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        <span><strong>Thông tin cá nhân</strong><small><?= e($user['name']) ?> · <?= e($user['email']) ?></small></span>
                        <svg class="settings-chevron" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </summary>
                    <form class="settings-form" method="post" action="actions/profile-action.php">
                        <?php if ($openSection === 'info') { settings_error_box($errors); } ?>
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="section" value="info">
                        <div class="form-group">
                            <label class="form-label" for="name">Họ tên</label>
                            <input class="form-control" id="name" name="name" value="<?= e($openSection === 'info' ? $input['name'] : $user['name']) ?>" autocomplete="name" minlength="2" maxlength="100" required<?= $describedBy ?>>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" id="email" name="email" type="email" value="<?= e($openSection === 'info' ? $input['email'] : $user['email']) ?>" autocomplete="email" maxlength="254" required<?= $describedBy ?>>
                        </div>
                        <?php password_input('current_password', 'current_password', 'Mật khẩu hiện tại (chỉ cần khi đổi email)', 'current-password', $describedBy, false); ?>
                        <button class="btn-brand" type="submit">Lưu thông tin</button>
                    </form>
                </details>

                <details class="settings-row"<?= $openSection === 'password' ? ' open' : '' ?>>
                    <summary>
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        <span><strong>Đổi mật khẩu</strong><small>Ít nhất 8 ký tự</small></span>
                        <svg class="settings-chevron" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </summary>
                    <form class="settings-form" method="post" action="actions/profile-action.php">
                        <?php if ($openSection === 'password') { settings_error_box($errors); } ?>
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="section" value="password">
                        <input type="hidden" name="name" value="<?= e($user['name']) ?>">
                        <input type="hidden" name="email" value="<?= e($user['email']) ?>">
                        <?php password_input('pw_current', 'current_password', 'Mật khẩu hiện tại', 'current-password', $describedBy, true); ?>
                        <?php password_input('new_password', 'new_password', 'Mật khẩu mới', 'new-password', $describedBy, true); ?>
                        <?php password_input('password_confirmation', 'password_confirmation', 'Nhập lại mật khẩu mới', 'new-password', $describedBy, true); ?>
                        <p class="settings-note">Sau khi đổi, các thiết bị khác sẽ tự đăng xuất.</p>
                        <button class="btn-brand" type="submit">Đổi mật khẩu</button>
                    </form>
                </details>

                <form class="settings-row settings-row-action" method="post" action="actions/logout.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                        <span><strong>Đăng xuất</strong><small>Thoát khỏi trình duyệt này</small></span>
                        <svg class="settings-chevron" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </button>
                </form>
            </section>
        </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
