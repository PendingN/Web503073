<header class="dashboard-toolbar">
    <form class="dashboard-search" method="get" action="collection.php" role="search">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <label class="sr-only" for="dashboard-search">Tìm tên cây</label>
        <input id="dashboard-search" name="search" type="search" placeholder="Tìm tên cây..." value="<?= e($_GET['search'] ?? '') ?>">
        <button type="submit">Tìm</button>
    </form>
    <nav class="toolbar-links" aria-label="Liên kết nhanh">
        <a href="favorites.php">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"/></svg>
            Yêu thích
        </a>
        <a href="index.php">Trang chủ ↗</a>
        <a href="blog.php">Blog ↗</a>
        <a href="admin.php">Quản trị ↗</a>
        <?php if (!empty($_SESSION['user_email'])): ?><a href="logout.php">Đăng xuất</a><?php endif; ?>
    </nav>
</header>

