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
$result = list_blog_posts($search, $sort, $view, (int) ($user['id'] ?? 0), $page);
$editSlug = request_string($_GET, 'edit');
if ($editSlug !== '') {
    redirect_to(app_url('blog-editor.php?edit=' . rawurlencode($editSlug)));
}
$query = ['search' => $search, 'sort' => $sort, 'view' => $view];
$topics = ['Chăm cây', 'Không gian', 'Cảm hứng', 'Mẹo hay', 'Sống xanh', 'Bài nổi bật'];
$totalArchivePosts = count_blog_posts();
$showEditorialShowcase = $view === 'all' && $search === '' && $sort === 'new' && $page === 1 && count($result['posts']) >= 5;

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

if (!function_exists('blog_opening_passage')) {
    function blog_opening_passage(array $post): string
    {
        $paragraphs = preg_split('/\R\s*\R/u', trim((string) ($post['content'] ?? ''))) ?: [];
        $first = trim(strip_tags((string) ($paragraphs[0] ?? '')));
        if ($first === '' || $first === trim((string) ($post['excerpt'] ?? ''))) {
            return '';
        }
        return mb_strimwidth($first, 0, 185, '…', 'UTF-8');
    }
}

if (!function_exists('render_blog_specimen_plate')) {
    function render_blog_specimen_plate(array $post, int $specimenNumber, bool $wideFolio = false): void
    {
        $postUrl = 'blog-post.php?slug=' . rawurlencode($post['slug']);
        $dateDisplay = date('d/m/Y', strtotime($post['created_at']));
        $dateTimeAttr = str_replace(' ', 'T', $post['created_at']);
        $specimenCode = sprintf('%02d', $specimenNumber);
        $minutes = blog_reading_minutes($post);
        $openingNote = $wideFolio ? blog_opening_passage($post) : '';
        ?>
        <article class="post-card feed-post<?= $wideFolio ? ' mono-folio-wide' : '' ?>" data-mono-plate>
            <a class="post-image-link" href="<?= e($postUrl) ?>" aria-label="Đọc bài: <?= e($post['title']) ?>">
                <span class="mono-card-mat">
                    <img class="post-image" src="<?= e(blog_cover_url($post)) ?>" alt="" loading="lazy" decoding="async" width="520" height="340">
                </span>
            </a>
            <div class="post-copy">
                <div class="mono-copy-main">
                    <h3><a href="<?= e($postUrl) ?>"><?= e($post['title']) ?></a></h3>
                    <p class="description"><?= e($post['excerpt']) ?></p>
                    <?php if ($openingNote !== ''): ?>
                        <p class="mono-folio-note"><?= e($openingNote) ?></p>
                    <?php endif; ?>
                </div>
                <div class="mono-card-ledger">
                    <div class="mono-card-meta-row">
                        <span class="mono-specimen-num mono-tabular"><?= e($specimenCode) ?></span>
                        <span class="tag"><?= e($post['tag']) ?></span>
                        <p class="mono-card-byline">
                            <span><?= e($post['author']) ?></span>
                            <span aria-hidden="true">·</span>
                            <time class="mono-tabular" datetime="<?= e($dateTimeAttr) ?>"><?= e($dateDisplay) ?></time>
                            <span aria-hidden="true">·</span>
                            <span><?= e($minutes) ?> phút đọc</span>
                        </p>
                    </div>
                    <a class="mono-plate-read" href="<?= e($postUrl) ?>">
                        <span>Đọc tiêu bản</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>
            </div>
        </article>
        <?php
    }
}

$leadPost = null;
$indexPosts = [];
$spotlightPost = null;
$archiveEntries = [];

if ($showEditorialShowcase) {
    $leadPost = $result['posts'][0];
    $indexPosts = array_slice($result['posts'], 1, 3);
    $spotlightPost = $result['posts'][4];
    foreach (array_slice($result['posts'], 5) as $offset => $archivePost) {
        $archiveEntries[] = ['post' => $archivePost, 'number' => 6 + $offset];
    }
} else {
    foreach ($result['posts'] as $idx => $archivePost) {
        $archiveEntries[] = ['post' => $archivePost, 'number' => (($page - 1) * 6) + $idx + 1];
    }
}

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
                <div class="mono-masthead-title">
                    <h1 id="monograph-title">Sổ Tay Thực Vật</h1>
                    <p class="mono-masthead-lead">Ghi chép thực tế về ánh sáng, nhịp tưới nước và những mảng xanh trong nhà từ cộng đồng Là Lá La.</p>
                </div>
                <dl class="mono-masthead-colophon" aria-label="Thống kê lưu trữ">
                    <div>
                        <dd class="mono-tabular"><?= e(sprintf('%02d', $totalArchivePosts)) ?></dd>
                        <dt>Bài lưu trữ</dt>
                    </div>
                    <div>
                        <dd class="mono-tabular"><?= e(sprintf('%02d', count($topics))) ?></dd>
                        <dt>Chủ đề</dt>
                    </div>
                </dl>
            </header>

            <?php if ($flash = take_flash()): ?>
                <p class="status-note" role="status"><?= e($flash) ?></p>
            <?php endif; ?>

            <?php if ($showEditorialShowcase && $leadPost !== null): ?>
                <?php
                $leadUrl = 'blog-post.php?slug=' . rawurlencode($leadPost['slug']);
                $leadDate = date('d/m/Y', strtotime($leadPost['created_at']));
                $leadDateTime = str_replace(' ', 'T', $leadPost['created_at']);
                $leadMinutes = blog_reading_minutes($leadPost);
                $leadPassage = blog_opening_passage($leadPost);
                $indexEndNum = sprintf('%02d', count($indexPosts) + 1);
                ?>
                <div class="mono-opening-spread recent-grid" aria-label="Bài viết mở đầu và chỉ mục mới nhất">
                    <article class="post-card feed-post mono-hero-plate">
                        <a class="mono-hero-media" href="<?= e($leadUrl) ?>" aria-label="Đọc bài tiêu điểm: <?= e($leadPost['title']) ?>">
                            <img src="<?= e(blog_cover_url($leadPost)) ?>" alt="<?= e($leadPost['title']) ?>" width="560" height="440" decoding="async" fetchpriority="high">
                            <span class="mono-plate-caption">
                                <span>Bản số <span class="mono-tabular">01</span></span>
                                <span><span class="mono-tabular"><?= e($leadMinutes) ?></span> phút đọc</span>
                            </span>
                        </a>
                        <div class="mono-hero-copy">
                            <div class="mono-hero-main">
                                <h3><a href="<?= e($leadUrl) ?>"><?= e($leadPost['title']) ?></a></h3>
                                <p class="mono-hero-excerpt"><?= e($leadPost['excerpt']) ?></p>
                                <?php if ($leadPassage !== ''): ?>
                                    <p class="mono-hero-passage"><?= e($leadPassage) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="mono-hero-foot">
                                <dl class="mono-hero-meta">
                                    <div>
                                        <dt>Chủ đề</dt>
                                        <dd><a class="tag" href="blog.php?<?= e(http_build_query(['search' => $leadPost['tag'], 'sort' => $sort, 'view' => $view])) ?>#blog-content"><?= e($leadPost['tag']) ?></a></dd>
                                    </div>
                                    <div>
                                        <dt>Tác giả</dt>
                                        <dd><?= e($leadPost['author']) ?></dd>
                                    </div>
                                    <div>
                                        <dt>Ghi nhận</dt>
                                        <dd><time class="mono-tabular" datetime="<?= e($leadDateTime) ?>"><?= e($leadDate) ?></time></dd>
                                    </div>
                                </dl>
                                <a class="mono-hero-cta" href="<?= e($leadUrl) ?>">
                                    <span>Đọc bài viết</span>
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                </a>
                            </div>
                        </div>
                    </article>

                    <aside class="mono-catalog-index" aria-label="Chỉ mục bài viết mới nhất">
                        <div class="mono-index-head">
                            <h2>Chỉ mục mới nhất</h2>
                            <span class="mono-index-range mono-tabular">02 — <?= e($indexEndNum) ?></span>
                        </div>
                        <div class="mono-index-list">
                            <?php foreach ($indexPosts as $idx => $indexPost): ?>
                                <?php
                                $idxUrl = 'blog-post.php?slug=' . rawurlencode($indexPost['slug']);
                                $idxDate = date('d/m/Y', strtotime($indexPost['created_at']));
                                $idxDateTime = str_replace(' ', 'T', $indexPost['created_at']);
                                $idxNum = sprintf('%02d', $idx + 2);
                                ?>
                                <article class="post-card feed-post mono-index-entry">
                                    <span class="mono-entry-num mono-tabular" aria-hidden="true"><?= e($idxNum) ?></span>
                                    <div class="mono-entry-body">
                                        <h3><a href="<?= e($idxUrl) ?>"><?= e($indexPost['title']) ?></a></h3>
                                        <p class="mono-entry-excerpt"><?= e($indexPost['excerpt']) ?></p>
                                        <p class="mono-entry-meta">
                                            <span><?= e($indexPost['tag']) ?></span>
                                            <span aria-hidden="true">·</span>
                                            <span><?= e($indexPost['author']) ?></span>
                                            <span aria-hidden="true">·</span>
                                            <time class="mono-tabular" datetime="<?= e($idxDateTime) ?>"><?= e($idxDate) ?></time>
                                        </p>
                                    </div>
                                    <a class="mono-entry-thumb" href="<?= e($idxUrl) ?>" aria-hidden="true" tabindex="-1">
                                        <img src="<?= e(blog_cover_url($indexPost)) ?>" alt="" width="74" height="74" decoding="async">
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>

            <section class="mono-controls-ledger" id="blog-content" tabindex="-1" aria-label="Tìm kiếm và lọc bài viết">
                <div class="mono-controls-top">
                    <nav class="blog-tabs" aria-label="Chế độ xem bài viết">
                        <?php foreach (['all' => 'Tất cả', 'mine' => 'Bài của tôi', 'saved' => 'Đã lưu'] as $key => $label): ?>
                            <a href="blog.php?<?= e(http_build_query(array_merge($query, ['view' => $key]))) ?>#blog-content"<?= $view === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                        <?php endforeach; ?>
                    </nav>
                    <nav class="feed-topics" aria-label="Lọc theo chủ đề">
                        <?php foreach ($topics as $topic): ?>
                            <?php
                            $isActiveTopic = $search === $topic;
                            $topicQuery = $isActiveTopic ? ['sort' => $sort, 'view' => $view] : ['search' => $topic, 'sort' => $sort, 'view' => $view];
                            ?>
                            <a class="topic-pill tag" href="blog.php?<?= e(http_build_query($topicQuery)) ?>#blog-content"<?= $isActiveTopic ? ' aria-current="page"' : '' ?>><?= e($topic) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <form class="blog-search" action="blog.php#blog-content" method="get" role="search">
                    <div class="blog-search-field">
                        <label for="blog-search">Tìm bài viết</label>
                        <input id="blog-search" type="search" name="search" value="<?= e($search) ?>" placeholder="Tiêu đề, nội dung hoặc chủ đề">
                    </div>
                    <div class="blog-sort-field">
                        <label for="blog-sort">Sắp xếp</label>
                        <select id="blog-sort" name="sort">
                            <option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>Mới nhất</option>
                            <option value="popular"<?= $sort === 'popular' ? ' selected' : '' ?>>Được yêu thích</option>
                        </select>
                    </div>
                    <input type="hidden" name="view" value="<?= e($view) ?>">
                    <button class="btn-brand" type="submit">Tìm kiếm</button>
                </form>
            </section>
        </div>
    </section>

    <?php if ($showEditorialShowcase && $spotlightPost !== null): ?>
        <?php
        $featureUrl = 'blog-post.php?slug=' . rawurlencode($spotlightPost['slug']);
        $featureDate = date('d/m/Y', strtotime($spotlightPost['created_at']));
        $featureDateTime = str_replace(' ', 'T', $spotlightPost['created_at']);
        $featureMinutes = blog_reading_minutes($spotlightPost);
        $featureQuote = blog_opening_passage($spotlightPost);
        ?>
        <section class="mono-spotlight-band feature-section" aria-labelledby="spotlight-heading">
            <div class="mono-container">
                <article class="post-card feed-post mono-spotlight-card feature-card" data-mono-plate>
                    <div class="mono-spotlight-copy">
                        <h3 id="spotlight-heading"><a href="<?= e($featureUrl) ?>"><?= e($spotlightPost['title']) ?></a></h3>
                        <p class="mono-spotlight-excerpt"><?= e($spotlightPost['excerpt']) ?></p>
                        <?php if ($featureQuote !== ''): ?>
                            <p class="mono-spotlight-quote">“<?= e($featureQuote) ?>”</p>
                        <?php endif; ?>
                        <dl class="mono-spotlight-ledger">
                            <div>
                                <dt>Tiêu bản</dt>
                                <dd class="mono-tabular">05</dd>
                            </div>
                            <div>
                                <dt>Chủ đề</dt>
                                <dd><span class="tag"><?= e($spotlightPost['tag']) ?></span></dd>
                            </div>
                            <div>
                                <dt>Ghi nhận</dt>
                                <dd><?= e($spotlightPost['author']) ?> · <time class="mono-tabular" datetime="<?= e($featureDateTime) ?>"><?= e($featureDate) ?></time> · <?= e($featureMinutes) ?> phút đọc</dd>
                            </div>
                        </dl>
                        <a class="mono-spotlight-link" href="<?= e($featureUrl) ?>">
                            <span>Mở trang đọc trọn bài</span>
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                    </div>
                    <a class="mono-spotlight-frame feature-image-wrap" href="<?= e($featureUrl) ?>" aria-label="Đọc bài: <?= e($spotlightPost['title']) ?>" data-mono-parallax>
                        <div class="mono-spotlight-aperture">
                            <img src="<?= e(blog_cover_url($spotlightPost)) ?>" alt="<?= e($spotlightPost['title']) ?>" width="720" height="450" loading="lazy" decoding="async">
                        </div>
                        <span class="mono-plate-caption">
                            <span>Bản ảnh tâm điểm · <span class="mono-tabular">05</span></span>
                            <span><?= e($spotlightPost['tag']) ?></span>
                        </span>
                    </a>
                </article>
            </div>
        </section>
    <?php endif; ?>

    <section class="mono-archive-section all-section" id="all-posts" aria-labelledby="all-heading">
        <div class="mono-container">
            <div class="all-section-head">
                <div class="all-section-meta">
                    <h2 id="all-heading"><?= e($showEditorialShowcase ? 'Tiếp nối trong kỳ lưu trữ' : ['all' => 'Tất cả bài viết', 'mine' => 'Bài của tôi', 'saved' => 'Đã lưu'][$view]) ?></h2>
                    <p class="all-section-count"><span class="mono-tabular"><?= e($result['total']) ?></span> kết quả</p>
                    <?php if ($search !== ''): ?>
                        <a class="btn-quiet blog-clear-filter" href="blog.php?<?= e(http_build_query(['sort' => $sort, 'view' => $view])) ?>#blog-content">Xóa bộ lọc</a>
                    <?php endif; ?>
                </div>
                <a class="blog-write-link" href="blog-editor.php">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    <span>Viết bài mới</span>
                </a>
            </div>

            <div class="all-grid feed-list<?= count($archiveEntries) === 1 ? ' all-grid--single' : '' ?>">
                <?php foreach ($archiveEntries as $entry): ?>
                    <?php render_blog_specimen_plate($entry['post'], $entry['number'], count($archiveEntries) === 1); ?>
                <?php endforeach; ?>

                <?php if ($result['posts'] === []): ?>
                    <div class="blog-empty">
                        <?php if ($search !== ''): ?>
                            <h3>Không có bài viết khớp “<?= e($search) ?>”.</h3>
                            <p>Thử một từ khóa ngắn hơn hoặc quay lại danh mục đầy đủ của Sổ Tay Thực Vật.</p>
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
