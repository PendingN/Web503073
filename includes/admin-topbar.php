<header class="sticky top-0 z-30 flex min-h-20 items-center justify-between gap-3 border-b border-border bg-white/95 px-4 py-3 backdrop-blur sm:px-6 xl:px-8">
    <div class="flex min-w-0 items-center gap-3">
        <button class="shrink-0 rounded-lg p-2 text-muted hover:bg-soft lg:hidden" type="button" data-admin-menu-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Mở menu quản trị"><?php admin_icon('menu'); ?></button>
        <div class="min-w-0"><p class="mb-1 text-[10px] text-muted">KHÔNG GIAN QUẢN TRỊ</p><h1 class="truncate text-sm font-semibold sm:text-lg"><?= e($pageTitle) ?></h1></div>
    </div>
    <div class="flex shrink-0 items-center gap-1 sm:gap-3">
        <form class="relative hidden xl:block" method="get" action="admin.php" role="search">
            <label class="sr-only" for="admin-quick-search">Tìm người dùng</label>
            <?php admin_icon('search', 'pointer-events-none absolute top-3 left-3 size-4 text-muted'); ?>
            <input class="admin-input w-56 pl-9" id="admin-quick-search" type="search" name="search" maxlength="200" placeholder="Tìm người dùng…" value="<?= e(request_string($_GET, 'search')) ?>">
        </form>
        <span class="hidden items-center gap-2 text-xs text-muted 2xl:flex"><?php admin_icon('calendar', 'size-4'); ?><?= e(date('d/m/Y')) ?></span>
        <a class="inline-flex items-center gap-2 rounded-lg p-2.5 text-xs text-muted hover:bg-soft" href="index.php" aria-label="Xem website"><?php admin_icon('external', 'size-4'); ?><span class="hidden sm:inline">Xem website</span></a>
        <form method="post" action="actions/logout.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="inline-flex items-center gap-2 rounded-lg bg-primary/10 p-2.5 text-xs font-medium text-primary-dark hover:bg-primary/20" type="submit" aria-label="Đăng xuất"><?php admin_icon('logout', 'size-4'); ?><span class="hidden sm:inline">Đăng xuất</span></button>
        </form>
    </div>
</header>
