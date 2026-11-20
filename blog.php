<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/blog-images.php';

$pageTitle = 'Sổ Tay Thực Vật';
$pageDescription = 'Sổ tay lưu trữ kinh nghiệm chăm cây, đọc ánh sáng và cảm hứng sống cùng cây xanh từ cộng đồng Là Lá La.';
$bodyClass = 'blog-page';
$activePage = 'blog';
$showSiteFooter = false;
$user = current_user();
$search = mb_substr(trim(request_string($_GET, 'search')), 0, 200, 'UTF-8');
$sort = request_string($_GET, 'sort') === 'popular' ? 'popular' : 'new';
$view = request_string($_GET, 'view');
$view = in_array($view, ['saved', 'mine'], true) ? $view : 'all';
if ($view !== 'all') {
    $user = require_login();
}
$page = max(1, (int) request_string($_GET, 'page', '1'));
$editSlug = request_string($_GET, 'edit');
if ($editSlug !== '') {
    redirect_to(app_url('blog-editor.php?edit=' . rawurlencode($editSlug)));
}
// "view" already picks all/mine/saved, so the grid/list switch lives in "layout"; grid is the default
// and stays out of the URL.
$layout = request_string($_GET, 'layout') === 'list' ? 'list' : 'grid';
// Topic filter: ?tag[]=ID, any number of ids. A post matches when it carries at least one of them.
$allTags = list_tags();
$tagIds = array_values(array_intersect(parse_tag_ids($_GET['tag'] ?? [])['ids'], array_column($allTags, 'id')));
$query = ['search' => $search, 'sort' => $sort, 'view' => $view];
if ($tagIds !== []) {
    $query['tag'] = $tagIds;
}
if ($layout === 'list') {
    $query['layout'] = 'list';
}
// Topics worth offering: those with posts the reader can see, plus any already selected.
$topicTags = array_values(array_filter($allTags, static fn (array $tag): bool => $tag['post_count'] > 0 || in_array($tag['id'], $tagIds, true)));
$totalArchivePosts = count_blog_posts();
$searchSuggestions = array_map(static fn (array $post): array => [
    'name' => $post['title'],
    'category' => implode(', ', array_column($post['tags'], 'name')),
    'extra' => $post['excerpt'],
    'image' => blog_cover_url($post),
    'url' => 'blog-post.php?slug=' . rawurlencode($post['slug']),
], blog_search_suggestions());
$pageScripts = ['js/search-suggest.js'];

// The default front page opens with the newest four posts as a teaser; the list below always
// holds every post, so nothing is missing from it.
const BLOG_LEAD_POSTS = 4;
$userId = (int) ($user['id'] ?? 0);
$result = list_blog_posts($search, $sort, $view, $userId, $page, $tagIds);
$showEditorialShowcase = $view === 'all' && $search === '' && $tagIds === [] && $sort === 'new'
    && $result['page'] === 1 && $totalArchivePosts > BLOG_LEAD_POSTS;

$leadPost = null;
$indexPosts = [];
if ($showEditorialShowcase) {
    $leadPosts = array_slice(list_blog_posts('', 'new', 'all', $userId, 1)['posts'], 0, BLOG_LEAD_POSTS);
    $leadPost = $leadPosts[0] ?? null;
    $indexPosts = array_slice($leadPosts, 1);
    $showEditorialShowcase = $leadPost !== null;
}

if (!function_exists('blog_reading_minutes')) {
    function blog_reading_minutes(array $post): int
    {
        $raw = trim(strip_tags((string) ($post['content'] ?? '') . ' ' . (string) ($post['excerpt'] ?? '')));
        if ($raw === '') {
            return 2;
        }
        $words = preg_split('/\s+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $count = is_array($words) ? count($words) : 0;
        return max(2, min(18, (int) ceil($count / 170)));
    }
}

if (!function_exists('render_blog_meta')) {
    /** One quiet line: topics (none, one or several) · author (members only) · date · reading time. */
    function render_blog_meta(array $post, bool $compact = false): void
    {
        $extra = ($GLOBALS['layout'] ?? 'grid') === 'list' ? ['layout' => 'list'] : [];
        // Cards show up to three topics; the narrow teaser list shows the first one.
        $shownTags = array_slice($post['tags'] ?? [], 0, $compact ? 1 : 3);
        $dateTime = str_replace(' ', 'T', (string) $post['created_at']);
        $date = date('d/m/Y', strtotime((string) $post['created_at']));
        ?>
        <p class="mono-meta">
            <?php foreach ($shownTags as $postTag): ?>
                <a class="mono-meta-tag" href="<?= e(blog_tag_url($postTag['id'], $extra)) ?>"><?= e($postTag['name']) ?></a>
            <?php endforeach; ?>
            <?php if (!$compact && (string) $post['author'] !== 'Ban biên tập'): ?>
                <span><?= e($post['author']) ?></span>
            <?php endif; ?>
            <time datetime="<?= e($dateTime) ?>"><?= e($date) ?></time>
            <?php if (!$compact): ?>
                <span><?= e(blog_reading_minutes($post)) ?> phút đọc</span>
            <?php endif; ?>
        </p>
        <?php
    }
}

if (!function_exists('render_blog_card')) {
    function render_blog_card(array $post, bool $wide = false): void
    {
        $postUrl = 'blog-post.php?slug=' . rawurlencode($post['slug']);
        ?>
        <article class="post-card feed-post<?= $wide ? ' mono-folio-wide' : '' ?>" data-mono-plate>
            <a class="post-image-link" href="<?= e($postUrl) ?>" aria-label="Đọc bài: <?= e($post['title']) ?>">
                <span class="mono-card-mat">
                    <img class="post-image" src="<?= e(blog_cover_url($post)) ?>" alt="" loading="lazy" decoding="async" width="520" height="340">
                </span>
            </a>
            <div class="post-copy">
                <h3><?php render_post_status_icons($post); ?><a href="<?= e($postUrl) ?>"><?= e($post['title']) ?></a></h3>
                <p class="description"><?= e(post_list_excerpt($post)) ?></p>
                <?php render_blog_meta($post); ?>
            </div>
        </article>
        <?php
    }
}

$archiveHeading = ['all' => 'Tất cả bài viết', 'mine' => 'Bài của tôi', 'saved' => 'Đã lưu'][$view];

require __DIR__ . '/includes/header.php';
?>
<a class="blog-skip-link" href="#blog-content">Đến danh sách bài viết</a>
<?php
require __DIR__ . '/includes/navbar.php';
?>
<main class="blog-feed-main" id="main-content" tabindex="-1">
    <section class="mono-masthead-band" aria-labelledby="monograph-title">
        <div class="mono-container">
            <header class="mono-masthead">
                <h1 id="monograph-title">Sổ Tay Thực Vật</h1>
                <p class="mono-masthead-lead">Ghi chép thực tế về ánh sáng, nhịp tưới nước và những mảng xanh trong nhà từ cộng đồng Là Lá La.</p>
            </header>

            <?php if ($flash = take_flash()): ?>
                <p class="status-note" role="status"><?= e($flash) ?></p>
            <?php endif; ?>

            <?php if ($showEditorialShowcase): ?>
                <?php $leadUrl = 'blog-post.php?slug=' . rawurlencode($leadPost['slug']); ?>
                <div class="mono-opening-spread recent-grid" aria-label="Bài viết mới nhất">
                    <article class="post-card feed-post mono-hero-plate">
                        <a class="mono-hero-media" href="<?= e($leadUrl) ?>" aria-label="Đọc bài: <?= e($leadPost['title']) ?>">
                            <img src="<?= e(blog_cover_url($leadPost)) ?>" alt="" width="560" height="440" decoding="async" fetchpriority="high">
                        </a>
                        <div class="mono-hero-copy">
                            <h3><?php render_post_status_icons($leadPost); ?><a href="<?= e($leadUrl) ?>"><?= e($leadPost['title']) ?></a></h3>
                            <p class="mono-hero-excerpt"><?= e(post_list_excerpt($leadPost)) ?></p>
                            <?php render_blog_meta($leadPost); ?>
                            <a class="mono-hero-cta" href="<?= e($leadUrl) ?>">
                                <span>Đọc bài</span>
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </a>
                        </div>
                    </article>

                    <aside class="mono-catalog-index" aria-labelledby="latest-heading">
                        <h2 id="latest-heading" class="mono-index-head">Mới đăng</h2>
                        <div class="mono-index-list">
                            <?php foreach ($indexPosts as $indexPost): ?>
                                <?php $idxUrl = 'blog-post.php?slug=' . rawurlencode($indexPost['slug']); ?>
                                <article class="post-card feed-post mono-index-entry">
                                    <div class="mono-entry-body">
                                        <h3><?php render_post_status_icons($indexPost); ?><a href="<?= e($idxUrl) ?>"><?= e($indexPost['title']) ?></a></h3>
                                        <p class="mono-entry-excerpt"><?= e(post_list_excerpt($indexPost)) ?></p>
                                        <?php render_blog_meta($indexPost, true); ?>
                                    </div>
                                    <a class="mono-entry-thumb" href="<?= e($idxUrl) ?>" aria-hidden="true" tabindex="-1">
                                        <img src="<?= e(blog_cover_url($indexPost)) ?>" alt="" width="88" height="88" decoding="async">
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="mono-archive-section all-section" id="all-posts" aria-labelledby="all-heading">
        <div class="mono-container">
            <div class="all-section-head">
                <h2 id="all-heading"><?= e($archiveHeading) ?></h2>
                <p class="all-section-count"><?= e($result['total']) ?> kết quả</p>
                <?php if ($search !== '' || $tagIds !== []): ?>
                    <a class="btn-quiet blog-clear-filter" href="blog.php?<?= e(http_build_query(array_diff_key($query, ['search' => true, 'tag' => true]))) ?>#blog-content">Xóa bộ lọc</a>
                <?php endif; ?>
                <?php
                $layoutBase = $query;
                unset($layoutBase['layout']);
                if ($result['page'] > 1) {
                    $layoutBase['page'] = $result['page'];
                }
                $layoutOptions = [
                    'grid' => ['Lưới', $layoutBase],
                    'list' => ['Danh sách', $layoutBase + ['layout' => 'list']],
                ];
                ?>
                <div class="blog-layout-toggle" role="group" aria-label="Kiểu hiển thị bài viết">
                    <?php foreach ($layoutOptions as $key => [$label, $layoutQuery]): ?>
                        <a href="blog.php?<?= e(http_build_query($layoutQuery)) ?>#blog-content"<?= $layout === $key ? ' aria-current="true"' : '' ?> aria-label="<?= e($label) ?>" title="<?= e($label) ?>">
                            <?php if ($key === 'grid'): ?>
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="6.5" height="6.5" rx="1"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/></svg>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <section class="mono-controls" id="blog-content" tabindex="-1" aria-label="Tìm kiếm và lọc bài viết">
                <div class="mono-controls-row">
                    <nav class="blog-tabs" aria-label="Chế độ xem bài viết">
                        <?php foreach (['all' => 'Tất cả', 'mine' => 'Bài của tôi', 'saved' => 'Đã lưu'] as $key => $label): ?>
                            <a href="blog.php?<?= e(http_build_query(array_merge($query, ['view' => $key]))) ?>#blog-content"<?= $view === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                        <?php endforeach; ?>
                    </nav>
                    <form class="blog-search" action="blog.php#blog-content" method="get" role="search">
                        <div class="blog-search-field" data-search-suggest="<?= e(json_encode($searchSuggestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>" data-empty-text="Không có bài nào khớp. Nhấn Enter để tìm trong nội dung.">
                            <label class="sr-only" for="blog-search">Tìm bài viết</label>
                            <input id="blog-search" type="search" name="search" value="<?= e($search) ?>" placeholder="Tiêu đề, nội dung hoặc chủ đề" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="blog-search-list">
                            <ul class="search-suggest" id="blog-search-list" role="listbox" aria-label="Gợi ý bài viết" hidden></ul>
                        </div>
                        <div class="blog-sort-field">
                            <label class="sr-only" for="blog-sort">Sắp xếp</label>
                            <select id="blog-sort" name="sort">
                                <option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>Mới nhất</option>
                                <option value="popular"<?= $sort === 'popular' ? ' selected' : '' ?>>Được yêu thích</option>
                            </select>
                        </div>
                        <input type="hidden" name="view" value="<?= e($view) ?>">
                        <?php foreach ($tagIds as $selectedTagId): ?>
                            <input type="hidden" name="tag[]" value="<?= (int) $selectedTagId ?>">
                        <?php endforeach; ?>
                        <?php if ($layout === 'list'): ?>
                            <input type="hidden" name="layout" value="list">
                        <?php endif; ?>
                        <button class="btn-brand" type="submit">Tìm kiếm</button>
                    </form>
                </div>
                <?php if ($topicTags !== []): ?>
                    <nav class="feed-topics" aria-label="Lọc theo chủ đề (chọn một hoặc nhiều)">
                        <?php foreach ($topicTags as $topicTag): ?>
                            <?php
                            // Each pill toggles its tag in the selection; several can be on at once.
                            $isActiveTopic = in_array($topicTag['id'], $tagIds, true);
                            $nextTags = $isActiveTopic ? array_values(array_diff($tagIds, [$topicTag['id']])) : [...$tagIds, $topicTag['id']];
                            $topicQuery = array_diff_key($query, ['tag' => true]) + ($nextTags === [] ? [] : ['tag' => $nextTags]);
                            ?>
                            <a class="topic-pill" href="blog.php?<?= e(http_build_query($topicQuery)) ?>#blog-content"<?= $isActiveTopic ? ' aria-current="true"' : '' ?>><?= e($topicTag['name']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            </section>

            <?php $singleWide = $layout === 'grid' && count($result['posts']) === 1; ?>
            <div class="all-grid feed-list<?= $layout === 'list' ? ' all-grid--list' : '' ?><?= $singleWide ? ' all-grid--single' : '' ?>">
                <?php foreach ($result['posts'] as $archivePost): ?>
                    <?php render_blog_card($archivePost, $singleWide); ?>
                <?php endforeach; ?>

                <?php if ($result['posts'] === []): ?>
                    <div class="blog-empty">
                        <?php if ($search !== ''): ?>
                            <h3>Không có bài viết khớp “<?= e($search) ?>”.</h3>
                            <p>Thử một từ khóa ngắn hơn hoặc quay lại danh mục đầy đủ của Sổ Tay Thực Vật.</p>
                        <?php elseif ($tagIds !== []): ?>
                            <h3>Chưa có bài viết nào với chủ đề đã chọn.</h3>
                            <p>Bỏ bớt một chủ đề hoặc quay lại danh mục đầy đủ của Sổ Tay Thực Vật.</p>
                        <?php elseif ($view === 'saved'): ?>
                            <h3>Bạn chưa lưu bài viết nào.</h3>
                            <p>Chọn nút lưu trên một bài viết để gom vào sổ tay đọc lại sau.</p>
                        <?php elseif ($view === 'mine'): ?>
                            <h3>Bạn chưa đăng bài viết nào.</h3>
                            <p>Chọn “Viết bài mới” để ghi lại câu chuyện chăm cây đầu tiên của bạn.</p>
                        <?php else: ?>
                            <h3>Chưa có bài viết nào.</h3>
                            <p>Hãy là người đầu tiên chia sẻ một ghi chép chăm cây.</p>
                        <?php endif; ?>
                        <a class="btn-brand" href="blog.php#blog-content">Xem tất cả bài viết</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ((int) $result['pages'] > 1): ?>
                <nav class="pagination" aria-label="Phân trang bài viết">
                    <?php if ($result['page'] > 1): ?>
                        <a class="pagination-nav pagination-prev" href="blog.php?<?= e(http_build_query($query + ['page' => $result['page'] - 1])) ?>#blog-content">
                            <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.833 10H4.167M4.167 10L10 15.833M4.167 10L10 4.167"/></svg>
                            <span>Trước</span>
                        </a>
                    <?php else: ?>
                        <span class="pagination-nav pagination-prev is-disabled" aria-disabled="true">
                            <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.833 10H4.167M4.167 10L10 15.833M4.167 10L10 4.167"/></svg>
                            <span>Trước</span>
                        </span>
                    <?php endif; ?>

                    <div class="page-numbers">
                        <?php
                        $totalPages = (int) $result['pages'];
                        $currentPage = (int) $result['page'];
                        if ($totalPages <= 6) {
                            $visiblePages = range(1, $totalPages);
                        } else {
                            $visiblePages = [1, $totalPages];
                            for ($p = max(1, $currentPage - 1); $p <= min($totalPages, $currentPage + 1); $p++) {
                                $visiblePages[] = $p;
                            }
                            if ($currentPage <= 3) {
                                $visiblePages[] = 2;
                                $visiblePages[] = 3;
                            }
                            if ($currentPage >= $totalPages - 2) {
                                $visiblePages[] = $totalPages - 2;
                                $visiblePages[] = $totalPages - 1;
                            }
                            $visiblePages = array_values(array_unique(array_filter(
                                $visiblePages,
                                static fn(int $p): bool => $p >= 1 && $p <= $totalPages
                            )));
                            sort($visiblePages);
                        }
                        $previousRenderedPage = null;
                        foreach ($visiblePages as $p):
                            if ($previousRenderedPage !== null && $p - $previousRenderedPage === 2):
                                $middlePage = $previousRenderedPage + 1;
                                ?>
                                <a class="page-number<?= $middlePage === $currentPage ? ' active' : '' ?>" href="blog.php?<?= e(http_build_query($query + ['page' => $middlePage])) ?>#blog-content"<?= $middlePage === $currentPage ? ' aria-current="page"' : '' ?>><?= e($middlePage) ?></a>
                            <?php elseif ($previousRenderedPage !== null && $p - $previousRenderedPage > 2): ?>
                                <span class="ellipsis" aria-hidden="true">…</span>
                            <?php endif; ?>
                            <a class="page-number<?= $p === $currentPage ? ' active' : '' ?>" href="blog.php?<?= e(http_build_query($query + ['page' => $p])) ?>#blog-content"<?= $p === $currentPage ? ' aria-current="page"' : '' ?>><?= e($p) ?></a>
                            <?php
                            $previousRenderedPage = $p;
                        endforeach;
                        ?>
                        <span class="sr-only">Trang <?= e($currentPage) ?> / <?= e($totalPages) ?></span>
                    </div>

                    <?php if ($result['page'] < $result['pages']): ?>
                        <a class="pagination-nav pagination-next" href="blog.php?<?= e(http_build_query($query + ['page' => $result['page'] + 1])) ?>#blog-content">
                            <span>Sau</span>
                            <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.167 10h11.666M10 4.167L15.833 10L10 15.833"/></svg>
                        </a>
                    <?php else: ?>
                        <span class="pagination-nav pagination-next is-disabled" aria-disabled="true">
                            <span>Sau</span>
                            <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.167 10h11.666M10 4.167L15.833 10L10 15.833"/></svg>
                        </span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <section class="mono-contribute-band" aria-labelledby="contribute-heading">
        <div class="mono-container mono-contribute-grid">
            <div>
                <h2 id="contribute-heading">Góp một trang ghi chép vào Sổ tay.</h2>
                <p>Một lần cứu chậu cây úng nước, cách đặt góc ban công ít nắng hay thói quen tưới cây buổi sáng — câu chuyện nhỏ của bạn có thể giúp rất nhiều người mới bắt đầu.</p>
            </div>
            <div class="mono-contribute-actions">
                <a class="btn-brand" href="blog-editor.php">Viết bài chia sẻ</a>
                <a class="mono-outline-btn" href="collection.php">Khám phá bộ sưu tập cây</a>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/blog-monograph-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
