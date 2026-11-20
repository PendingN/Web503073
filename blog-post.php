<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/blog-images.php';
require_once __DIR__ . '/includes/blog-blocks.php';
$pageStyles = ['css/blog-editor.css'];

$slug = trim(request_string($_GET, 'slug'));
$post = find_post($slug);
if ($post === null) {
    http_response_code(404);
}
$user = current_user();
$comments = $post === null ? null : list_blog_comments((int) $post['id'], max(1, (int) request_string($_GET, 'page', '1')));
$commentDraft = $_SESSION['comment_draft'] ?? null;
unset($_SESSION['comment_draft']);
$flash = $post === null ? null : take_flash();
$isCommentFlash = $flash !== null && (str_contains($flash, 'bình luận') || str_contains($flash, 'Bình luận'));
$isCommentError = $isCommentFlash && ($commentDraft !== null || str_contains($flash, 'cần có'));
$pageTitle = $post === null ? 'Không tìm thấy bài viết' : $post['title'];
$bodyClass = 'blog-post-page';
$activePage = 'blog';
$showSiteFooter = false;

$relatedPosts = [];
$readingMinutes = 2;
if ($post !== null) {
    $rawContent = trim(strip_tags((string) ($post['content'] ?? '') . ' ' . (string) ($post['excerpt'] ?? '')));
    $words = $rawContent === '' ? [] : preg_split('/\s+/u', $rawContent, -1, PREG_SPLIT_NO_EMPTY);
    $wordCount = is_array($words) ? count($words) : 0;
    $readingMinutes = max(2, min(18, (int) ceil($wordCount / 170)));
    $candidates = latest_blog_posts();
    foreach ($candidates as $candidate) {
        if ((string) ($candidate['slug'] ?? '') !== $slug) {
            $relatedPosts[] = $candidate;
        }
        if (count($relatedPosts) >= 3) {
            break;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<a class="blog-skip-link" href="#article-content">Đến bài viết</a>
<?php if ($post !== null): ?>
    <div class="mono-reading-progress" data-reading-progress aria-hidden="true"></div>
<?php endif; ?>
<?php
require __DIR__ . '/includes/navbar.php';
?>

<?php if ($post === null): ?>
    <main class="mono-not-found not-found" id="article-content" tabindex="-1">
        <div class="mono-not-found-sheet">
            <h1>Trang này đang để dành cho một câu chuyện khác.</h1>
            <p>Có thể bài viết đã được chuyển đi hoặc tạm ẩn. Bạn vẫn có thể đọc những ghi chép mới nhất trong Sổ Tay Thực Vật.</p>
            <a class="btn-brand" href="blog.php">Về trang blog</a>
        </div>
    </main>
<?php else: ?>
    <?php
    $postCreatedAt = strtotime($post['created_at']);
    $postDateDisplay = date(date('H:i', $postCreatedAt) === '00:00' ? 'd/m/Y' : 'd/m/Y H:i', $postCreatedAt);
    $postDateTimeAttr = str_replace(' ', 'T', $post['created_at']);
    ?>
    <main class="blog-post-main" id="article-content" tabindex="-1">
        <div class="mono-article-shell">
            <a class="back-link" href="blog.php">Sổ Tay Thực Vật</a>

            <?php if ($flash !== null && !$isCommentFlash): ?>
                <p class="status-note" role="status"><?= e($flash) ?></p>
            <?php endif; ?>

            <header class="blog-post-header">
                <h1><?= e($post['title']) ?></h1>
                <p class="mono-article-deck"><?= e($post['excerpt']) ?></p>
                <div class="mono-article-ledger post-meta">
                    <?php foreach ($post['tags'] as $postTag): ?>
                        <a class="tag" href="<?= e(blog_tag_url($postTag['id'])) ?>"><?= e($postTag['name']) ?></a>
                    <?php endforeach; ?>
                    <?php if ((string) $post['author'] !== 'Ban biên tập'): ?>
                        <span><?= e($post['author']) ?></span>
                    <?php endif; ?>
                    <time datetime="<?= e($postDateTimeAttr) ?>"><?= e($postDateDisplay) ?></time>
                    <span><?= e($readingMinutes) ?> phút đọc</span>
                </div>
            </header>

            <figure class="blog-post-art">
                <img class="blog-cover" src="<?= e(blog_cover_url($post)) ?>" alt="<?= !empty($post['image_path']) ? e($post['title']) : '' ?>" width="1000" height="560" decoding="async" fetchpriority="high">
            </figure>

            <article class="blog-post-body" data-reading-article>
                <div class="block-article"><?= render_blog_blocks(blog_blocks($post)) ?></div>

                <div class="mono-reactions-shelf">
                    <?php require __DIR__ . '/includes/blog-actions.php'; ?>
                </div>

                <div class="blog-post-cta">
                    <div>
                        <p>Muốn tìm một người bạn xanh phù hợp với ánh sáng trong phòng?</p>
                    </div>
                    <a class="btn-brand" href="collection.php">Khám phá bộ sưu tập</a>
                </div>
            </article>

            <?php if (can_edit_post($post, $user)): ?>
                <div class="blog-delete">
                    <form action="actions/blog-action.php" method="post" data-confirm="Bài viết “<?= e($post['title']) ?>” cùng bình luận, lượt bình chọn và lượt lưu sẽ bị xóa. Thao tác này không thể hoàn tác." data-confirm-title="Xóa bài viết?" data-confirm-label="Xóa bài viết" data-confirm-tone="danger">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="operation" value="delete">
                        <input type="hidden" name="slug" value="<?= e($slug) ?>">
                        <button class="btn-quiet btn-danger-quiet" type="submit">Xóa bài viết</button>
                    </form>
                </div>
            <?php endif; ?>

            <section class="blog-comments" id="comments" aria-labelledby="comments-title">
                <div class="mono-comments-head">
                    <h2 id="comments-title">Bình luận<?= (int) $post['comment_count'] > 0 ? ' (' . e($post['comment_count']) . ')' : '' ?></h2>
                </div>
                <?php if ($isCommentFlash): ?>
                    <p class="status-note blog-comment-status<?= $isCommentError ? ' is-error' : '' ?>" role="<?= $isCommentError ? 'alert' : 'status' ?>"><?= e($flash) ?></p>
                <?php endif; ?>

                <?php if ($user !== null): ?>
                    <form class="blog-form" action="actions/blog-action.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="operation" value="comment">
                        <input type="hidden" name="slug" value="<?= e($slug) ?>">
                        <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
                        <label for="comment-content">Chia sẻ kinh nghiệm hoặc câu hỏi của bạn</label>
                        <textarea id="comment-content" name="content" rows="3" maxlength="2000" required placeholder="Ghi lại góc nhìn hoặc cách chăm cây của riêng bạn..."><?= e(is_array($commentDraft) && $commentDraft['slug'] === $slug ? $commentDraft['content'] : '') ?></textarea>
                        <button class="btn-brand" type="submit">Gửi bình luận</button>
                    </form>
                <?php else: ?>
                    <p class="mono-comment-gate"><a href="login.php?return_to=<?= e(rawurlencode(app_url('blog-post.php?slug=' . rawurlencode($slug)))) ?>">Đăng nhập để bình luận</a></p>
                <?php endif; ?>

                <div class="mono-comment-list">
                    <?php foreach ($comments['comments'] as $comment): ?>
                        <article class="blog-comment" id="comment-<?= e($comment['id']) ?>">
                            <p class="mono-comment-meta"><strong><?= e($comment['author']) ?></strong> <span aria-hidden="true">·</span> <time class="mono-tabular"><?= e(date('d/m/Y H:i', strtotime($comment['created_at']))) ?></time></p>
                            <p class="blog-text"><?= e($comment['content']) ?></p>
                            <?php if ($user !== null && ($user['role'] === 'admin' || (int) $comment['user_id'] === (int) $user['id'])): ?>
                                <form action="actions/blog-action.php" method="post" data-confirm="Bình luận này sẽ bị xóa khỏi bài viết. Thao tác này không thể hoàn tác." data-confirm-title="Xóa bình luận?" data-confirm-label="Xóa bình luận" data-confirm-tone="danger">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="operation" value="delete-comment">
                                    <input type="hidden" name="slug" value="<?= e($slug) ?>">
                                    <button class="btn-quiet btn-danger-quiet" name="comment_id" value="<?= e($comment['id']) ?>">Xóa bình luận</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($comments['pages'] > 1): ?>
                    <nav class="pagination" aria-label="Phân trang bình luận">
                        <?php if ($comments['page'] > 1): ?><a href="blog-post.php?<?= e(http_build_query(['slug' => $slug, 'page' => $comments['page'] - 1])) ?>#comments">Trước</a><?php endif; ?>
                        <span class="mono-tabular">Trang <?= e($comments['page']) ?> / <?= e($comments['pages']) ?></span>
                        <?php if ($comments['page'] < $comments['pages']): ?><a href="blog-post.php?<?= e(http_build_query(['slug' => $slug, 'page' => $comments['page'] + 1])) ?>#comments">Sau</a><?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>
        </div>

        <?php if ($relatedPosts !== []): ?>
            <section class="mono-related-band" aria-labelledby="related-heading">
                <div class="mono-container">
                    <div class="mono-related-head">
                        <h2 id="related-heading">Đọc tiếp</h2>
                        <a class="mono-related-all" href="blog.php">
                            <span>Tất cả bài viết</span>
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                    </div>
                    <div class="mono-related-grid">
                        <?php foreach ($relatedPosts as $related): ?>
                            <?php
                            $rUrl = 'blog-post.php?slug=' . rawurlencode((string) $related['slug']);
                            $rDate = date('d/m/Y', strtotime((string) $related['created_at']));
                            ?>
                            <article class="mono-related-card" data-mono-plate>
                                <a class="mono-related-thumb" href="<?= e($rUrl) ?>" aria-label="Đọc bài: <?= e((string) $related['title']) ?>">
                                    <span class="mono-card-mat">
                                        <img src="<?= e(blog_cover_url($related)) ?>" alt="" loading="lazy" decoding="async" width="420" height="260">
                                    </span>
                                </a>
                                <div class="mono-related-body">
                                    <h3><a href="<?= e($rUrl) ?>"><?= e((string) $related['title']) ?></a></h3>
                                    <p><?= e((string) $related['excerpt']) ?></p>
                                    <div class="mono-related-meta">
                                        <?php if ((string) $related['tag'] !== ''): ?><span class="tag"><?= e((string) $related['tag']) ?></span><?php endif; ?>
                                        <time class="mono-tabular"><?= e($rDate) ?></time>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
<?php endif; ?>

<?php require __DIR__ . '/includes/blog-monograph-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
