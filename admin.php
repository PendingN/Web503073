<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$admin = require_admin();
$stats = user_statistics();
$plantTotal = plant_catalog_statistics()['total'];
$blogTotal = count_blog_posts();
$blogHidden = (int) db()->query('SELECT COUNT(*) FROM blog_posts WHERE is_hidden = 1')->fetchColumn();
$adminActive = 'overview';
$adminHeading = 'Tổng quan';
$pageTitle = 'Tổng quan quản trị';
require __DIR__ . '/includes/admin-start.php';
?>
            <section class="admin-stats" id="overview" aria-label="Thống kê">
                <article class="tone-green"><div class="stat-top"><span>Tổng tài khoản</span></div><strong><?= (int) $stats['total'] ?></strong><small>Thành viên và quản trị viên</small></article>
                <article class="tone-teal"><div class="stat-top"><span>Đang hoạt động</span></div><strong><?= (int) $stats['active'] ?></strong><small>Có thể đăng nhập</small></article>
                <article class="tone-red"><div class="stat-top"><span>Đã khóa</span></div><strong><?= (int) $stats['blocked'] ?></strong><small>Chờ quản trị viên mở khóa</small></article>
                <article class="tone-amber"><div class="stat-top"><span>Quản trị viên</span></div><strong><?= (int) $stats['admins'] ?></strong><small>Có quyền quản trị</small></article>
            </section>

            <section class="admin-shortcuts" aria-label="Đi tới các mục quản trị">
                <a class="shortcut tone-green" href="admin-users.php">
                    <p class="eyebrow">NGƯỜI DÙNG</p>
                    <strong><?= (int) $stats['total'] ?> tài khoản</strong>
                    <span>Tìm kiếm, khóa, mở khóa, phân quyền</span>
                </a>
                <a class="shortcut tone-teal" href="admin-plants.php">
                    <p class="eyebrow">QUẢN LÝ CÂY</p>
                    <strong><?= (int) $plantTotal ?> cây</strong>
                    <span>Thêm, sửa, xóa cây trong bộ sưu tập</span>
                </a>
                <a class="shortcut tone-amber" href="admin-blog.php">
                    <p class="eyebrow">KIỂM DUYỆT BLOG</p>
                    <strong><?= (int) $blogTotal ?> bài viết<?= $blogHidden > 0 ? ' · ' . $blogHidden . ' đang ẩn' : '' ?></strong>
                    <span>Ẩn, hiện hoặc xóa bài viết</span>
                </a>
            </section>
<?php require __DIR__ . '/includes/admin-end.php'; ?>
