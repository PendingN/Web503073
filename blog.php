<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Blog';
$pageDescription = 'Chia sẻ kinh nghiệm và cảm hứng sống cùng cây xanh.';
$bodyClass = 'blog-page';
$activePage = 'blog';
$user = current_user();
$search = mb_substr(trim(request_string($_GET, 'search')), 0, 200, 'UTF-8');
$sort = request_string($_GET, 'sort') === 'popular' ? 'popular' : 'new';
$view = request_string($_GET, 'view');
$view = in_array($view, ['saved', 'mine'], true) ? $view : 'all';
if ($view !== 'all') {
    $user = require_login();
}
$page = max(1, (int) request_string($_GET, 'page', '1'));
$result = list_blog_posts($search, $sort, $view, (int) ($user['id'] ?? 0), $page);
$latestPosts = latest_blog_posts();
$editSlug = request_string($_GET, 'edit');
$editing = null;
if ($editSlug !== '') {
    $user = require_login();
    $editing = find_post($editSlug);
    if ($editing === null) {
        abort_request(404, 'Không tìm thấy bài viết.');
    }
    if (!can_edit_post($editing, $user)) {
        abort_request(403, 'Bạn không có quyền sửa bài viết này.');
    }
}
$draft = $_SESSION['blog_draft'] ?? null;
unset($_SESSION['blog_draft']);
$form = is_array($draft) && $draft['slug'] === $editSlug ? $draft : ($editing ?? ['title' => '', 'content' => '', 'tag' => 'Chăm cây']);
$query = ['search' => $search, 'sort' => $sort, 'view' => $view];
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
require __DIR__ . '/includes/plant-illustration.php';
?>
<main class="blog-feed-main">
    <section class="feed-hero" aria-labelledby="feed-title">
        <div class="feed-hero-copy">
            <p class="eyebrow">NHẬT KÝ RỪNG XANH</p>
            <h1 id="feed-title">Chuyện cây cỏ,<br><em>kể cùng nhau.</em></h1>
            <p class="feed-hero-lead">Chia sẻ mẹo chăm cây và góc xanh của bạn.</p>
        </div>
        <div class="feed-hero-note" aria-labelledby="latest-posts-title">
            <div class="feed-ticker-heading">
                <span class="feed-hero-note-icon" aria-hidden="true">✦</span>
                <div>
                    <strong id="latest-posts-title">Bài viết mới nhất</strong>
                </div>
            </div>
            <div class="feed-ticker-viewport">
                <div class="feed-ticker-track">
                    <?php foreach ([$latestPosts, $latestPosts] as $copyIndex => $tickerPosts): ?>
                        <div class="feed-ticker-group"<?= $copyIndex === 1 ? ' aria-hidden="true" inert' : '' ?>>
                            <?php foreach ($tickerPosts as $tickerPost): ?>
                                <a class="feed-ticker-card" href="blog-post.php?slug=<?= e(rawurlencode($tickerPost['slug'])) ?>">
                                    <span class="feed-ticker-label"><?= e($tickerPost['tag']) ?></span>
                                    <strong><?= e($tickerPost['title']) ?></strong>
                                    <time datetime="<?= e(str_replace(' ', 'T', $tickerPost['created_at'])) ?>"><?= e(date('d/m/Y', strtotime($tickerPost['created_at']))) ?></time>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php if ($flash = take_flash()): ?><p class="status-note" role="status"><?= e($flash) ?></p><?php endif; ?>
    <div class="feed-layout">
        <section class="feed-column" aria-label="Bảng tin bài viết">
            <section class="feed-composer" id="write-post" aria-label="Viết bài">
                <?php if ($user !== null): ?>
                    <details<?= $editing !== null || $draft !== null ? ' open' : '' ?>>
                        <summary><?= $editing !== null ? 'Sửa bài viết' : 'Viết bài mới' ?></summary>
                        <form class="blog-form" action="actions/blog-action.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="operation" value="<?= $editing !== null ? 'update' : 'create' ?>">
                            <input type="hidden" name="slug" value="<?= e($editSlug) ?>">
                            <label for="post-title">Tiêu đề</label>
                            <input id="post-title" name="title" minlength="3" maxlength="120" value="<?= e($form['title']) ?>" required>
                            <label for="post-tag">Chủ đề</label>
                            <select id="post-tag" name="tag">
                                <?php foreach (['Chăm cây', 'Không gian', 'Cảm hứng', 'Mẹo hay', 'Sống xanh', 'Bài nổi bật'] as $tag): ?>
                                    <option<?= $form['tag'] === $tag ? ' selected' : '' ?>><?= e($tag) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="post-content">Nội dung</label>
                            <textarea id="post-content" name="content" rows="7" minlength="10" maxlength="10000" required><?= e($form['content']) ?></textarea>
                            <p>Bài viết hiển thị công khai. Nội dung từ 10 đến 10.000 ký tự.</p>
                            <div class="blog-form-actions"><a class="btn-quiet" href="blog.php">Hủy</a><button class="btn-brand" type="submit"><?= $editing !== null ? 'Lưu thay đổi' : 'Đăng bài viết' ?></button></div>
                        </form>
                    </details>
                <?php else: ?>
                    <a class="btn-brand" href="login.php?return_to=<?= e(rawurlencode(app_url('blog.php'))) ?>">Đăng nhập để viết bài</a>
                <?php endif; ?>
            </section>
            <form class="blog-search" action="blog.php" method="get">
                <label for="blog-search">Tìm bài viết</label>
                <input id="blog-search" name="search" value="<?= e($search) ?>" placeholder="Tiêu đề, nội dung hoặc chủ đề">
                <label for="blog-sort">Sắp xếp</label>
                <select id="blog-sort" name="sort"><option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>Mới nhất</option><option value="popular"<?= $sort === 'popular' ? ' selected' : '' ?>>Được yêu thích</option></select>
                <input type="hidden" name="view" value="<?= e($view) ?>">
                <button class="btn-brand" type="submit">Tìm kiếm</button>
            </form>
            <nav class="blog-tabs" aria-label="Lọc bài viết">
                <?php foreach (['all' => 'Tất cả', 'mine' => 'Bài của tôi', 'saved' => 'Đã lưu'] as $key => $label): ?>
                    <a href="blog.php?<?= e(http_build_query(array_merge($query, ['view' => $key]))) ?>"<?= $view === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="feed-toolbar"><h2>Bài viết</h2><p><?= e($result['total']) ?> kết quả</p></div>
            <div class="feed-list">
                <?php foreach ($result['posts'] as $post): ?>
                    <article class="feed-post">
                        <header class="feed-post-head"><div class="feed-author">
                            <span class="feed-avatar feed-avatar-<?= e($post['tone']) ?>" aria-hidden="true"><?= e(mb_substr($post['author'], 0, 1)) ?></span>
                            <div><p><strong><?= e($post['author']) ?></strong></p><time datetime="<?= e(str_replace(' ', 'T', $post['created_at'])) ?>"><?= e(date('d/m/Y H:i', strtotime($post['created_at']))) ?></time></div>
                        </div></header>
                        <div class="feed-post-content">
                            <div class="feed-post-copy">
                                <span class="feed-topic"><?= e($post['tag']) ?></span>
                                <h3><a href="blog-post.php?slug=<?= e(rawurlencode($post['slug'])) ?>"><?= e($post['title']) ?></a></h3>
                                <p><?= e($post['excerpt']) ?></p>
                                <?php require __DIR__ . '/includes/blog-actions.php'; ?>
                            </div>
                            <a class="feed-post-art feed-art-<?= e($post['tone']) ?>" href="blog-post.php?slug=<?= e(rawurlencode($post['slug'])) ?>" aria-label="Đọc bài: <?= e($post['title']) ?>"><?php render_plant_illustration($post['illustration'], 'feed-plant-illustration'); ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($result['posts'] === []): ?><p class="blog-empty">Chưa có bài viết phù hợp.</p><?php endif; ?>
            </div>
            <nav class="pagination" aria-label="Phân trang bài viết">
                <?php if ($result['page'] > 1): ?><a href="blog.php?<?= e(http_build_query($query + ['page' => $result['page'] - 1])) ?>"> Trước</a><?php endif; ?>
                <span>Trang <?= e($result['page']) ?> / <?= e($result['pages']) ?></span>
                <?php if ($result['page'] < $result['pages']): ?><a href="blog.php?<?= e(http_build_query($query + ['page' => $result['page'] + 1])) ?>">Sau</a><?php endif; ?>
            </nav>
        </section>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
