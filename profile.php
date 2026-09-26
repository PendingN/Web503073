<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$user = require_login();

$pageTitle = 'Trang cá nhân';
$bodyClass = 'dashboard-page';
$dashboardActive = 'profile';
$userEmail = $user['email'];
$errors = $_SESSION['profile_errors'] ?? [];
$input = $_SESSION['profile_input'] ?? ['name' => $user['name'], 'email' => $userEmail];
unset($_SESSION['profile_errors'], $_SESSION['profile_input']);
$flashMessage = take_flash();
require __DIR__ . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>
    <div class="dashboard-main">
        <?php require __DIR__ . '/includes/dashboard-toolbar.php'; ?>
        <main class="dashboard-content profile-content">
            <?php if ($flashMessage !== null): ?><div class="status-note" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <header>
                <div>
                    <p class="eyebrow">GÓC RIÊNG CỦA BẠN</p>
                    <h1>Trang cá nhân</h1>
                    <p class="lead">Quản lý thông tin cơ bản và những lựa chọn xanh bạn đã lưu lại.</p>
                </div>
            </header>
            <section class="profile-card">
                <div class="profile-avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
                <div><p class="eyebrow">TÀI KHOẢN HIỆN TẠI</p><h2><?= e($user['name']) ?></h2><p><?= e($userEmail) ?> · <?= $user['role'] === 'admin' ? 'Quản trị viên' : 'Thành viên' ?></p></div>
            </section>
            <section class="account-panel">
                <h2>Thông tin tài khoản</h2>
                <?php if ($errors !== []): ?><div class="login-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <form class="account-form" method="post" action="actions/profile-action.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="form-group"><label class="form-label" for="name">Họ tên</label><input class="form-control" id="name" name="name" value="<?= e($input['name']) ?>" autocomplete="name" minlength="2" maxlength="100" required></div>
                    <div class="form-group"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" value="<?= e($input['email']) ?>" autocomplete="email" maxlength="254" required></div>
                    <div class="form-group"><label class="form-label" for="current_password">Mật khẩu hiện tại</label><input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" maxlength="72"><small>Cần nhập khi đổi email hoặc mật khẩu.</small></div>
                    <div class="form-group"><label class="form-label" for="new_password">Mật khẩu mới</label><input class="form-control" id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" maxlength="72"><small>Để trống nếu bạn chỉ cập nhật thông tin.</small></div>
                    <div class="form-group"><label class="form-label" for="password_confirmation">Nhập lại mật khẩu mới</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72"></div>
                    <div><button class="btn-brand" type="submit">Lưu thay đổi</button></div>
                </form>
            </section>
            <section class="profile-summary">
                <article><strong><?= count(favorite_ids()) ?></strong><span>Cây yêu thích</span><a href="favorites.php">Xem danh sách →</a></article>
                <article><strong><?= count($plants) ?></strong><span>Giống cây trong vườn</span><a href="collection.php">Khám phá thêm →</a></article>
                <article><strong><?= count($posts) + 1 ?></strong><span>Bài viết chăm cây</span><a href="blog.php">Đọc nhật ký →</a></article>
            </section>
        </main>
    </div>
</div>

<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
