<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Trang cá nhân';
$bodyClass = 'dashboard-page';
$dashboardActive = 'profile';
$userEmail = (string) ($_SESSION['user_email'] ?? 'Khách tham quan');
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
                <div class="profile-avatar"><?= e(strtoupper(substr($userEmail, 0, 1))) ?></div>
                <div><p class="eyebrow">TÀI KHOẢN HIỆN TẠI</p><h2><?= e($userEmail) ?></h2><p>Phiên làm việc được lưu trên trình duyệt này.</p></div>
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
