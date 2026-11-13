<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$admin = require_admin('admin-dashboard.php');
$stats = user_statistics();
$catalog = plant_catalog_statistics();
$activity = admin_activity_statistics();
$recentUsers = list_users('', '', 1, 5)['users'];
$recentPosts = latest_blog_posts();
$registrationTotal = array_sum(array_column($activity['registrations'], 'total'));
$chartMax = max(1, ...array_column($activity['registrations'], 'total'));
$pageTitle = 'Tổng quan quản trị';
$bodyClass = 'admin-page';
$adminActive = 'overview';
$flashMessage = take_flash();
require __DIR__ . '/includes/header.php';
?>
<div class="admin-shell min-h-screen">
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>
    <div id="admin-content" class="min-w-0 lg:ml-[270px]">
        <?php require __DIR__ . '/includes/admin-topbar.php'; ?>
        <main id="admin-main" class="mx-auto w-full max-w-[1480px] space-y-6 px-4 py-6 sm:px-6 xl:px-8">
            <?php if ($flashMessage !== null): ?><div class="status-note" role="status" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <section class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border bg-border xl:grid-cols-4" aria-label="Thống kê toàn website">
                <a class="bg-white p-4 transition-colors hover:bg-soft" href="admin.php"><span class="block text-sm text-muted">Tài khoản</span><strong class="mt-1 block text-2xl font-semibold" data-stat="users"><?= (int) $stats['total'] ?></strong></a>
                <a class="bg-white p-4 transition-colors hover:bg-soft" href="collection.php"><span class="block text-sm text-muted">Giống cây</span><strong class="mt-1 block text-2xl font-semibold" data-stat="plants"><?= (int) $catalog['total'] ?></strong></a>
                <a class="bg-white p-4 transition-colors hover:bg-soft" href="blog.php"><span class="block text-sm text-muted">Bài viết</span><strong class="mt-1 block text-2xl font-semibold" data-stat="posts"><?= count_blog_posts() ?></strong></a>
                <div class="bg-white p-4"><span class="block text-sm text-muted">Lượt yêu thích cây</span><strong class="mt-1 block text-2xl font-semibold" data-stat="favorites"><?= $activity['favorites'] ?></strong></div>
            </section>
            <div class="grid gap-6 xl:grid-cols-3">
                <section class="admin-card p-5 sm:p-6 xl:col-span-2" aria-labelledby="registrations-title">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-base font-semibold" id="registrations-title">Tài khoản mới</h2></div><span class="rounded-lg border border-border px-3 py-2 text-xs text-muted">7 ngày gần nhất</span></div>
                    <div class="mt-6 flex items-baseline gap-2"><strong class="text-2xl font-semibold"><?= $registrationTotal ?></strong><span class="text-2xl font-semibold text-muted">tài khoản</span></div>
                    <div class="mt-6 grid grid-cols-7 gap-3 sm:gap-5" aria-label="Số tài khoản mới theo ngày">
                        <?php foreach ($activity['registrations'] as $day): ?>
                            <div class="registration-day flex flex-col items-center gap-2">
                                <span class="text-xs font-medium text-muted"><?= $day['total'] ?></span>
                                <div class="flex h-36 w-full max-w-12 items-end overflow-hidden rounded-lg bg-primary/5" aria-hidden="true"><span class="w-full rounded-t-lg bg-primary" style="height: <?= (int) round($day['total'] / $chartMax * 100) ?>%"></span></div>
                                <time class="text-[10px] text-muted sm:text-xs" datetime="<?= e($day['date']) ?>"><?= e(date('d/m', strtotime($day['date']))) ?></time>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-5 flex items-center gap-2 text-xs text-muted"><span class="size-2 rounded-full bg-primary" aria-hidden="true"></span><?= $registrationTotal === 0 ? 'Chưa có tài khoản mới trong 7 ngày qua.' : 'Dữ liệu đăng ký thực tế của website' ?></p>
                </section>
                <section class="admin-card p-5 sm:p-6" aria-labelledby="recent-posts-title">
                    <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-base font-semibold" id="recent-posts-title">Bài viết mới nhất</h2><a class="admin-panel-link" href="admin-posts.php">Quản trị bài viết <?php admin_icon('arrow', 'size-4'); ?></a></div>
                    <ul class="mt-4 divide-y divide-border">
                        <?php foreach ($recentPosts as $post): ?>
                            <li><a class="group block py-4" href="blog-post.php?slug=<?= e(rawurlencode($post['slug'])) ?>"><span class="rounded bg-primary/10 px-2 py-1 text-xs font-medium text-primary-dark"><?= e($post['tag']) ?></span><strong class="mt-2 block text-sm leading-relaxed wrap-anywhere group-hover:text-primary-dark"><?= e($post['title']) ?></strong><time class="mt-1.5 block text-xs text-muted" datetime="<?= e(str_replace(' ', 'T', $post['created_at'])) ?>"><?= e(date('d/m/Y', strtotime($post['created_at']))) ?></time></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($recentPosts === []): ?><p class="mt-5 text-sm leading-relaxed text-muted">Chưa có bài viết. <a class="admin-panel-link" href="blog.php">Viết bài đầu tiên</a>.</p><?php endif; ?>
                </section>
            </div>
            <div class="grid gap-6">
                <section class="admin-card min-w-0 overflow-hidden" aria-labelledby="recent-users-title">
                    <div class="flex flex-wrap items-center justify-between gap-3 p-5 sm:p-6"><div><h2 class="text-base font-semibold" id="recent-users-title">Tài khoản mới nhất</h2><p class="mt-1.5 text-xs text-muted">5 tài khoản vừa tham gia khu vườn</p></div><a class="admin-panel-link" href="admin.php">Xem tất cả <?php admin_icon('arrow', 'size-4'); ?></a></div>
                    <div class="overflow-x-auto">
                        <table class="admin-table min-w-[560px]">
                            <thead><tr><th>Tài khoản</th><th>Trạng thái</th><th>Tham gia</th><th><span class="sr-only">Thao tác</span></th></tr></thead>
                            <tbody>
                                <?php foreach ($recentUsers as $member): ?>
                                    <tr><td><strong class="block max-w-64 text-sm font-semibold wrap-anywhere"><?= e($member['name']) ?></strong><span class="mt-1 block max-w-64 text-sm wrap-anywhere text-muted"><?= e($member['email']) ?></span></td><td><span class="status-<?= e($member['status']) ?>"><?= $member['status'] === 'active' ? 'Hoạt động' : 'Đã khóa' ?></span></td><td class="text-sm whitespace-nowrap text-muted"><?= e(date('d/m/Y', strtotime($member['created_at']))) ?></td><td><a class="admin-panel-link" href="admin.php?edit=<?= (int) $member['id'] ?>#edit-user" aria-label="Xem tài khoản <?= e($member['name']) ?>">Xem <?php admin_icon('arrow', 'size-3.5'); ?></a></td></tr>
                                <?php endforeach; ?>
                                <?php if ($recentUsers === []): ?><tr><td class="text-muted" colspan="4">Chưa có tài khoản.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
