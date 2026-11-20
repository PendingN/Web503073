<?php
// Shown by blog-post.php instead of the article while a password-locked post is not unlocked in this session.
// Expects $post, $user and $flash. Only the title is revealed.
$pageTitle = 'Bài viết đã khóa';
$bodyClass = 'blog-post-page';
$activePage = 'blog';
$showSiteFooter = false;
$pageScripts = [];
require __DIR__ . '/header.php';
require __DIR__ . '/navbar.php';
?>
<main class="blog-post-main post-lock-main" id="article-content" tabindex="-1">
    <div class="post-lock-dialog" role="dialog" aria-modal="false" aria-labelledby="post-lock-title" aria-describedby="post-lock-desc">
        <div class="post-lock-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
        </div>
        <h1 id="post-lock-title"><?= e($post['title']) ?></h1>
        <p id="post-lock-desc">Bài viết này được khóa bằng mật khẩu riêng. Nhập mật khẩu để xem, sửa hoặc xóa.</p>
        <?php if ($flash !== null): ?><p class="post-lock-error" role="alert"><?= e($flash) ?></p><?php endif; ?>
        <?php if ($user === null): ?>
            <a class="btn-brand" href="login.php?<?= e(http_build_query(['return_to' => 'blog-post.php?slug=' . $post['slug']])) ?>">Đăng nhập để mở khóa</a>
        <?php else: ?>
            <form method="post" action="actions/post-lock-action.php" class="post-lock-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="operation" value="unlock">
                <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
                <label class="form-label" for="unlock-password">Mật khẩu của bài</label>
                <div class="password-field">
                    <input class="form-control" id="unlock-password" name="password" type="password" autocomplete="off" maxlength="72" required autofocus>
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="unlock-password" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
                </div>
                <button class="btn-brand" type="submit">Mở khóa</button>
            </form>
        <?php endif; ?>
        <a class="post-lock-back" href="blog.php">← Về danh sách bài viết</a>
    </div>
</main>
<?php require __DIR__ . '/footer.php'; ?>
