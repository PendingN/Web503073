<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/blog-images.php';

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
$tickerPosts = [];
if ($view === 'all' && $search === '' && $page === 1) {
    $tickerPosts = $sort === 'new'
        ? list_blog_posts('', 'popular', 'all', (int) ($user['id'] ?? 0), 1)['posts']
        : array_reverse($result['posts']);
    if (count($tickerPosts) > 1 && array_column($tickerPosts, 'slug') === array_column($result['posts'], 'slug')) {
        $tickerPosts = array_reverse($tickerPosts);
    }
}
$editSlug = request_string($_GET, 'edit');
if ($editSlug !== '') {
    redirect_to(app_url('blog-editor.php?edit=' . rawurlencode($editSlug)));
}
$query = ['search' => $search, 'sort' => $sort, 'view' => $view];
require __DIR__ . '/includes/header.php';
?>
<a class="blog-skip-link" href="#blog-content">Đến bài viết</a>
<?php
require __DIR__ . '/includes/navbar.php';
?>
<main class="blog-feed-main" id="main-content" tabindex="-1">
    <section class="feed-hero" aria-labelledby="feed-title">
        <div class="feed-hero-copy">
            <h1 id="feed-title">Chuyện cây cỏ,<br><em>kể cùng nhau.</em></h1>
            <p class="feed-hero-lead">Chia sẻ mẹo chăm cây và góc xanh của bạn.</p>
        </div>
        <?php if ($tickerPosts !== []): ?>
            <div class="feed-ticker-viewport" id="hero-articles" aria-label="Bài viết trên trang này">
                <div class="feed-ticker-track">
                    <div class="feed-ticker-group">
                        <?php foreach ($tickerPosts as $tickerPost): ?>
                            <a class="feed-ticker-card" href="blog-post.php?slug=<?= e(rawurlencode($tickerPost['slug'])) ?>">
                                <img src="<?= e(blog_cover_url($tickerPost)) ?>" alt="" width="260" height="180" decoding="async">
                                <span class="feed-ticker-label"><?= e($tickerPost['tag']) ?></span>
                                <strong><?= e($tickerPost['title']) ?></strong>
                                <time datetime="<?= e(str_replace(' ', 'T', $tickerPost['created_at'])) ?>"><?= e(date('d/m/Y', strtotime($tickerPost['created_at']))) ?></time>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <button class="feed-motion-toggle" type="button" aria-controls="hero-articles" aria-pressed="false" hidden>Tạm dừng</button>
        <?php endif; ?>
    </section>
    <?php if ($flash = take_flash()): ?><p class="status-note" role="status"><?= e($flash) ?></p><?php endif; ?>
    <div class="feed-layout">
        <section class="feed-column" aria-label="Bảng tin bài viết">
            <section class="feed-composer" id="write-post" aria-label="Viết bài">
                <a class="composer-summary" href="blog-editor.php"><span class="composer-heading"><strong>Viết bài mới</strong><small>Kể câu chuyện của bạn bằng chữ và hình ảnh</small></span><span aria-hidden="true">→</span></a>
            </section>
            <form class="blog-search" action="blog.php#blog-content" method="get" role="search">
                <div class="blog-search-field">
                    <label for="blog-search">Tìm bài viết</label>
                    <input id="blog-search" type="search" name="search" value="<?= e($search) ?>" placeholder="Tiêu đề, nội dung hoặc chủ đề">
                </div>
                <div class="blog-sort-field">
                    <label for="blog-sort">Sắp xếp</label>
                    <select id="blog-sort" name="sort"><option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>Mới nhất</option><option value="popular"<?= $sort === 'popular' ? ' selected' : '' ?>>Được yêu thích</option></select>
                </div>
                <input type="hidden" name="view" value="<?= e($view) ?>">
                <button class="btn-brand" type="submit">Tìm kiếm</button>
            </form>
            <nav class="blog-tabs" id="blog-content" tabindex="-1" aria-label="Lọc bài viết">
                <?php foreach (['all' => 'Tất cả', 'mine' => 'Bài của tôi', 'saved' => 'Đã lưu'] as $key => $label): ?>
                    <a href="blog.php?<?= e(http_build_query(array_merge($query, ['view' => $key]))) ?>#blog-content"<?= $view === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="feed-toolbar"><h2><?= e(['all' => 'Bài viết', 'mine' => 'Bài của tôi', 'saved' => 'Đã lưu'][$view]) ?></h2><p><?= e($result['total']) ?> kết quả</p><?php if ($search !== ''): ?><a class="btn-quiet" href="blog.php?<?= e(http_build_query(['sort' => $sort, 'view' => $view])) ?>#blog-content">Xóa bộ lọc</a><?php endif; ?></div>
            <div class="feed-list">
                <?php foreach ($result['posts'] as $post): ?>
                    <?php $createdAt = strtotime($post['created_at']); ?>
                    <article class="feed-post">
                        <header class="feed-post-head"><div class="feed-author">
                            <span class="feed-avatar feed-avatar-<?= e($post['tone']) ?>" aria-hidden="true"><?= e(mb_substr($post['author'], 0, 1)) ?></span>
                            <div><p><strong><?= e($post['author']) ?></strong></p><time datetime="<?= e(str_replace(' ', 'T', $post['created_at'])) ?>"><?= e(date(date('H:i', $createdAt) === '00:00' ? 'd/m/Y' : 'd/m/Y H:i', $createdAt)) ?></time></div>
                        </div></header>
                        <div class="feed-post-copy">
                            <span class="feed-topic"><?= e($post['tag']) ?></span>
                            <h3><a href="blog-post.php?slug=<?= e(rawurlencode($post['slug'])) ?>"><?= e($post['title']) ?></a></h3>
                            <p><?= e($post['excerpt']) ?></p>
                        </div>
                        <a class="feed-post-art" href="blog-post.php?slug=<?= e(rawurlencode($post['slug'])) ?>" aria-label="Đọc bài: <?= e($post['title']) ?>">
                            <img class="blog-cover" src="<?= e(blog_cover_url($post)) ?>" alt="" loading="lazy" decoding="async" width="200" height="150">
                        </a>
                        <?php require __DIR__ . '/includes/blog-actions.php'; ?>
                    </article>
                <?php endforeach; ?>
                <?php if ($result['posts'] === []): ?>
                    <div class="blog-empty">
                        <?php if ($search !== ''): ?>
                            <h3>Không có bài viết khớp “<?= e($search) ?>”.</h3>
                            <p>Thử một từ khóa khác hoặc xem lại tất cả bài viết.</p>
                        <?php elseif ($view === 'saved'): ?>
                            <h3>Bạn chưa lưu bài viết nào.</h3>
                            <p>Chọn nút lưu trên một bài viết để đọc lại sau.</p>
                        <?php elseif ($view === 'mine'): ?>
                            <h3>Bạn chưa đăng bài viết nào.</h3>
                            <p>Chọn “Viết bài mới” phía trên để đăng bài đầu tiên.</p>
                        <?php else: ?>
                            <h3>Chưa có bài viết nào.</h3>
                            <p>Hãy là người đầu tiên chia sẻ một câu chuyện.</p>
                        <?php endif; ?>
                        <a class="btn-quiet" href="blog.php#blog-content">Xem tất cả bài viết</a>
                    </div>
                <?php endif; ?>
            </div>
            <nav class="pagination" aria-label="Phân trang bài viết">
                <?php if ($result['page'] > 1): ?><a href="blog.php?<?= e(http_build_query($query + ['page' => $result['page'] - 1])) ?>#blog-content">Trước</a><?php endif; ?>
                <span>Trang <?= e($result['page']) ?> / <?= e($result['pages']) ?></span>
                <?php if ($result['page'] < $result['pages']): ?><a href="blog.php?<?= e(http_build_query($query + ['page' => $result['page'] + 1])) ?>#blog-content">Sau</a><?php endif; ?>
            </nav>
        </section>
        <aside class="feed-sidebar" aria-label="Khám phá cộng đồng">
            <section class="feed-community">
                <nav class="feed-topics" aria-label="Khám phá chủ đề">
                    <h2>Khám phá chủ đề</h2>
                    <?php foreach (['Chăm cây', 'Không gian', 'Cảm hứng', 'Mẹo hay', 'Sống xanh', 'Bài nổi bật'] as $topic): ?>
                        <a href="blog.php?<?= e(http_build_query(['search' => $topic, 'sort' => $sort, 'view' => $view])) ?>#blog-content"<?= $search === $topic ? ' aria-current="page"' : '' ?>><?= e($topic) ?><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></a>
                    <?php endforeach; ?>
                </nav>
                <div class="feed-explore">
                    <h2>Tìm cây cho không gian của bạn</h2>
                    <a href="index.php#find-plant">Chọn cây theo góc phòng</a>
                    <a href="collection.php?category=<?= e(rawurlencode('Cây trong nhà')) ?>">Khám phá cây trong nhà</a>
                </div>
            </section>
        </aside>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
