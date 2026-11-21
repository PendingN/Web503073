<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/blog-images.php';
$user = require_login();

$pageTitle = 'Trang cá nhân';
$bodyClass = 'profile-page';
$activePage = 'profile';
$flashMessage = take_flash();

$sortOptions = user_post_sort_options();
$sort = request_string($_GET, 'sort', 'newest');
if (!array_key_exists($sort, $sortOptions)) {
    $sort = 'newest';
}
$search = trim(request_string($_GET, 'search'));

$userId = (int) $user['id'];
$stats = user_profile_statistics($userId);
$posts = list_user_posts($userId, $search, $sort);

// Vietnamese given names come last: "Nguyễn Thanh Loan" -> "Loan".
$nameParts = preg_split('/\s+/u', trim((string) $user['name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$givenName = $nameParts === [] ? 'bạn' : (string) end($nameParts);
$initial = mb_strtoupper(mb_substr((string) $user['name'], 0, 1, 'UTF-8'), 'UTF-8');

$icons = [
    'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"/>',
    'pen' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    'bookmark' => '<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>',
    'chat' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
];
$statRows = [
    ['icon' => 'heart', 'label' => 'Cây yêu thích', 'value' => $stats['favorites'], 'href' => 'favorites.php'],
    ['icon' => 'pen', 'label' => 'Bài đã viết', 'value' => $stats['posts'], 'href' => 'profile.php#profile-posts'],
    ['icon' => 'bookmark', 'label' => 'Bài đã lưu', 'value' => $stats['saved'], 'href' => 'blog.php?view=saved#blog-content'],
    ['icon' => 'chat', 'label' => 'Bình luận đã gửi', 'value' => $stats['comments'], 'href' => 'blog.php'],
    ['icon' => 'users', 'label' => 'Được chia sẻ với tôi', 'value' => count(list_shared_with($userId)), 'href' => 'shared.php'],
];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

        <main id="main-content" class="profile-layout" tabindex="-1">
            <h1 class="sr-only">Trang cá nhân của <?= e($user['name']) ?></h1>

            <aside class="profile-side" aria-label="Hồ sơ">
                <div class="profile-side-inner">
                    <div class="profile-id">
                        <div class="profile-avatar" aria-hidden="true"><?= e($initial) ?></div>
                        <h2><?= e($user['name']) ?></h2>
                        <p class="profile-role"><?= $user['role'] === 'admin' ? 'Quản trị viên' : 'Thành viên Là Lá La' ?></p>
                        <a class="profile-settings-link" href="account-settings.php">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                            Cài đặt tài khoản
                        </a>
                    </div>

                    <ul class="profile-stat-list">
                        <?php foreach ($statRows as $row): ?>
                            <li>
                                <a href="<?= e($row['href']) ?>">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons[$row['icon']] ?></svg>
                                    <span><small><?= e($row['label']) ?></small><strong><?= (int) $row['value'] ?></strong></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <section class="profile-main" id="profile-posts" aria-labelledby="profile-main-title">
                <?php if ($flashMessage !== null): ?><div class="status-note" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
                <header class="profile-main-head">
                    <div>
                        <h2 id="profile-main-title">Bài viết của <?= e($givenName) ?></h2>
                        <p><?= (int) $stats['posts'] ?> bài chia sẻ về cây và góc xanh</p>
                    </div>
                </header>

                <form class="profile-toolbar" method="get" action="profile.php#profile-posts" role="search">
                    <div class="profile-search">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <label class="sr-only" for="profile-search">Tìm trong bài viết của bạn</label>
                        <input id="profile-search" type="search" name="search" value="<?= e($search) ?>" placeholder="Tìm trong bài viết của bạn...">
                    </div>
                    <label class="sr-only" for="profile-sort">Sắp xếp</label>
                    <select id="profile-sort" name="sort" data-auto-submit>
                        <?php foreach ($sortOptions as $key => $option): ?>
                            <option value="<?= e($key) ?>"<?= $sort === $key ? ' selected' : '' ?>><?= e($option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button class="btn-outline-brand" type="submit">Lọc</button></noscript>
                    <a class="profile-write" href="blog-editor.php">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Viết bài
                    </a>
                </form>

                <?php if ($posts === []): ?>
                    <div class="profile-empty">
                        <?php if ($search !== ''): ?>
                            <h3>Không có bài nào khớp “<?= e($search) ?>”.</h3>
                            <a href="profile.php#profile-posts">Xem tất cả bài của bạn</a>
                        <?php else: ?>
                            <h3>Bạn chưa viết bài nào.</h3>
                            <p>Kể về góc xanh của bạn để mọi người cùng học hỏi nhé.</p>
                            <a class="profile-write" href="blog-editor.php">Viết bài đầu tiên</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <ul class="profile-post-grid">
                        <?php foreach ($posts as $post): ?>
                            <?php
                            $isHidden = (int) $post['is_hidden'] === 1;
                            $canOpen = !$isHidden || is_admin();
                            $postUrl = 'blog-post.php?slug=' . rawurlencode($post['slug']);
                            ?>
                            <li class="profile-post-card<?= $isHidden ? ' is-hidden' : '' ?>">
                                <div class="profile-post-cover">
                                    <img src="<?= e(blog_cover_url($post)) ?>" alt="" width="400" height="260" loading="lazy" decoding="async">
                                    <?php if ((string) $post['tag'] !== ''): ?><span class="profile-post-tag"><?= e($post['tag']) ?></span><?php endif; ?>
                                    <?php if ($isHidden): ?><span class="profile-post-badge">Đang bị ẩn</span><?php endif; ?>
                                        <span class="profile-post-icons"><?php render_post_status_icons($post); ?></span>
                                </div>
                                <div class="profile-post-body">
                                    <h3>
                                        <?php if ($canOpen): ?><a href="<?= e($postUrl) ?>"><?= e($post['title']) ?></a>
                                        <?php else: ?><?= e($post['title']) ?><?php endif; ?>
                                    </h3>
                                    <p class="profile-post-meta">
                                        <time datetime="<?= e(str_replace(' ', 'T', (string) $post['created_at'])) ?>"><?= e(date('d/m/Y', strtotime((string) $post['created_at']))) ?></time>
                                        <span><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons['heart'] ?></svg><?= (int) $post['likes'] ?><span class="sr-only"> lượt thích</span></span>
                                        <span><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons['chat'] ?></svg><?= (int) $post['comment_count'] ?><span class="sr-only"> bình luận</span></span>
                                    </p>
                                    <a class="profile-post-edit" href="blog-editor.php?edit=<?= e(rawurlencode($post['slug'])) ?>">Sửa bài<span class="sr-only">: <?= e($post['title']) ?></span></a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
