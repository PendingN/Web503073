<?php
require_once __DIR__ . '/admin-icons.php';
$adminNavigation = [
    ['id' => 'overview', 'href' => 'admin-dashboard.php', 'icon' => 'dashboard', 'label' => 'Tổng quan'],
    ['id' => 'users', 'href' => 'admin.php', 'icon' => 'users', 'label' => 'Người dùng'],
    ['id' => 'posts', 'href' => 'admin-posts.php', 'icon' => 'book', 'label' => 'Quản trị bài viết'],
    ['id' => 'history', 'href' => 'admin-history.php', 'icon' => 'calendar', 'label' => 'Lịch sử người dùng'],
    ['id' => 'plants', 'href' => 'admin-plants.php', 'icon' => 'leaf', 'label' => 'Quản lý cây'],
];
?>
<a class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded-lg focus:bg-primary-dark focus:p-3 focus:text-white" href="#admin-main">Đến nội dung chính</a>
<aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-[270px] -translate-x-full flex-col overflow-y-auto border-r border-border bg-white px-5 py-6 transition-transform duration-200 data-[open=true]:translate-x-0 motion-reduce:transition-none lg:translate-x-0" data-open="false">
    <div class="mb-10 flex items-center justify-between gap-2">
        <a class="flex items-center gap-3" href="index.php" aria-label="Vũ Điệu Rừng Xanh — Trang chủ">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary text-white"><?php admin_icon('leaf', 'size-6'); ?></span>
            <span class="text-sm font-bold leading-relaxed">VŨ ĐIỆU<br><span class="font-medium text-muted">RỪNG XANH</span></span>
        </a>
        <button class="rounded-lg p-2 text-muted hover:bg-soft lg:hidden" type="button" data-admin-menu-close aria-label="Đóng menu quản trị"><?php admin_icon('close'); ?></button>
    </div>
    <p class="mb-4 px-3 text-[10px] font-semibold tracking-[0.15em] text-muted">KHÔNG GIAN LÀM VIỆC</p>
    <nav class="space-y-1.5" aria-label="Điều hướng quản trị">
        <?php foreach ($adminNavigation as $item): ?>
            <a class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors <?= $adminActive === $item['id'] ? 'bg-primary-dark text-white shadow-sm' : 'text-muted hover:bg-primary/10 hover:text-primary-dark' ?>"<?= $adminActive === $item['id'] ? ' aria-current="page"' : '' ?> href="<?= e($item['href']) ?>">
                <?php admin_icon($item['icon']); ?><span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="mt-7 border-t border-border pt-5">
        <a class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm text-muted hover:bg-soft" href="index.php"><?php admin_icon('external'); ?>Xem website</a>
    </div>
    <div class="mt-auto pt-8">
        <a class="flex items-center gap-3 rounded-xl bg-soft p-3" href="profile.php">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/15 text-sm font-semibold text-primary-dark">AD</span>
            <span class="min-w-0"><strong class="block truncate text-xs"><?= e($admin['name']) ?></strong><span class="mt-1 block text-[11px] text-muted">Quản trị viên</span></span>
            <?php admin_icon('arrow', 'ml-auto size-4 shrink-0 text-muted'); ?>
        </a>
    </div>
</aside>
<button class="fixed inset-0 z-40 bg-ink/35 lg:hidden" type="button" data-admin-backdrop aria-label="Đóng menu quản trị" hidden></button>
