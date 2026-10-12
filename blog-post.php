<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$slug = trim(request_string($_GET, 'slug'));
$post = find_post($slug);
if ($post === null) {
    http_response_code(404);
}
$user = current_user();
$comments = $post === null ? null : list_blog_comments((int) $post['id'], max(1, (int) request_string($_GET, 'page', '1')));
$commentDraft = $_SESSION['comment_draft'] ?? null;
unset($_SESSION['comment_draft']);
$pageTitle = $post === null ? 'Không tìm thấy bài viết' : $post['title'];
$bodyClass = 'blog-post-page';
$activePage = 'blog';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
require __DIR__ . '/includes/plant-illustration.php';
?>

<?php if ($post === null): ?>
    <main class="not-found">
        <p class="eyebrow">404 · BÀI VIẾT KHÔNG TỒN TẠI</p>
        <h1>Trang này đang để dành cho một câu chuyện khác.</h1>
        <p>Có thể bài viết đã được chuyển đi. Bạn vẫn có thể đọc những ghi chú mới nhất trong nhật ký rừng xanh.</p>
        <a class="btn-brand" href="blog.php">Về trang blog ↗</a>
    </main>
<?php else: ?>
    <main class="blog-post-main">
        <?php if ($flash = take_flash()): ?><p class="status-note" role="status"><?= e($flash) ?></p><?php endif; ?>
        <a class="back-link" href="blog.php">← Quay lại nhật ký</a>
        <header class="blog-post-header">
            <div class="post-meta"><span><?= e($post['tag']) ?></span><span><?= e(date('d/m/Y H:i', strtotime($post['created_at']))) ?> · <?= e($post['author']) ?></span></div>
            <h1><?= e($post['title']) ?></h1>
            <p><?= e($post['excerpt']) ?></p>
        </header>
        <div class="blog-post-art post-art-<?= e($post['tone']) ?>"><?php render_plant_illustration($post['illustration']); ?></div>
        <article class="blog-post-body">
            <div class="blog-text"><?= e($post['content']) ?></div>
            <?php require __DIR__ . '/includes/blog-actions.php'; ?>
            <div class="blog-post-cta"><p>Muốn tìm một người bạn xanh cho không gian của mình?</p><a class="btn-brand" href="collection.php">Khám phá bộ sưu tập ↗</a></div>
        </article>
        <?php if (can_edit_post($post, $user)): ?>
            <details class="blog-delete">
                <summary>Xóa bài viết</summary>
                <p>Bài viết cùng bình luận, lượt bình chọn và lượt lưu sẽ bị xóa. Thao tác này không thể hoàn tác.</p>
                <form action="actions/blog-action.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="operation" value="delete">
                    <input type="hidden" name="slug" value="<?= e($slug) ?>">
                    <button class="btn-quiet" type="submit">Xác nhận xóa bài</button>
                </form>
            </details>
        <?php endif; ?>
        <section class="blog-comments" id="comments" aria-labelledby="comments-title">
            <h2 id="comments-title">Bình luận (<?= e($post['comment_count']) ?>)</h2>
            <?php if ($user !== null): ?>
                <form class="blog-form" action="actions/blog-action.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="operation" value="comment">
                    <input type="hidden" name="slug" value="<?= e($slug) ?>">
                    <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
                    <label for="comment-content">Chia sẻ ý kiến của bạn</label>
                    <textarea id="comment-content" name="content" rows="3" maxlength="2000" required><?= e(is_array($commentDraft) && $commentDraft['slug'] === $slug ? $commentDraft['content'] : '') ?></textarea>
                    <button class="btn-brand" type="submit">Gửi bình luận</button>
                </form>
            <?php else: ?>
                <p><a href="login.php?return_to=<?= e(rawurlencode(app_url('blog-post.php?slug=' . rawurlencode($slug)))) ?>">Đăng nhập để bình luận</a></p>
            <?php endif; ?>
            <?php foreach ($comments['comments'] as $comment): ?>
                <article class="blog-comment" id="comment-<?= e($comment['id']) ?>">
                    <p><strong><?= e($comment['author']) ?></strong> · <?= e(date('d/m/Y H:i', strtotime($comment['created_at']))) ?></p>
                    <p class="blog-text"><?= e($comment['content']) ?></p>
                    <?php if ($user !== null && ($user['role'] === 'admin' || (int) $comment['user_id'] === (int) $user['id'])): ?>
                        <form action="actions/blog-action.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="operation" value="delete-comment">
                            <input type="hidden" name="slug" value="<?= e($slug) ?>">
                            <button class="btn-quiet" name="comment_id" value="<?= e($comment['id']) ?>">Xóa bình luận</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ($comments['comments'] === []): ?><p class="blog-empty">Chưa có bình luận. Hãy chia sẻ ý kiến đầu tiên.</p><?php endif; ?>
            <?php if ($comments['pages'] > 1): ?>
                <nav class="pagination" aria-label="Phân trang bình luận">
                    <?php if ($comments['page'] > 1): ?><a href="blog-post.php?<?= e(http_build_query(['slug' => $slug, 'page' => $comments['page'] - 1])) ?>#comments">← Trước</a><?php endif; ?>
                    <span>Trang <?= e($comments['page']) ?> / <?= e($comments['pages']) ?></span>
                    <?php if ($comments['page'] < $comments['pages']): ?><a href="blog-post.php?<?= e(http_build_query(['slug' => $slug, 'page' => $comments['page'] + 1])) ?>#comments">Sau →</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    </main>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
