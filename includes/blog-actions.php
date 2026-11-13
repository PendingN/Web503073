<?php $postUrl = 'blog-post.php?slug=' . rawurlencode($post['slug']); ?>
<div class="feed-post-actions">
    <?php if ($user !== null): ?>
        <form class="feed-vote-group" data-reaction="vote" action="actions/blog-action.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="operation" value="vote">
            <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
            <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
            <button class="feed-action vote-button<?= (int) $post['my_vote'] === 1 ? ' is-active' : '' ?>" data-vote="1" name="value" value="<?= (int) $post['my_vote'] === 1 ? 0 : 1 ?>" title="Thích bài viết" aria-label="Thích bài viết" aria-pressed="<?= (int) $post['my_vote'] === 1 ? 'true' : 'false' ?>"><?php require __DIR__ . '/vote-icon.php'; ?><span data-vote-count><?= e($post['likes']) ?></span></button>
            <button class="feed-action vote-button<?= (int) $post['my_vote'] === -1 ? ' is-active' : '' ?>" data-vote="-1" name="value" value="<?= (int) $post['my_vote'] === -1 ? 0 : -1 ?>" title="Không thích bài viết" aria-label="Không thích bài viết" aria-pressed="<?= (int) $post['my_vote'] === -1 ? 'true' : 'false' ?>"><?php require __DIR__ . '/vote-icon.php'; ?><span data-vote-count><?= e($post['dislikes']) ?></span></button>
        </form>
        <form data-reaction="save" action="actions/blog-action.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="operation" value="save">
            <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
            <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
            <button class="feed-action heart-button<?= $post['saved'] ? ' is-active' : '' ?>" name="saved" value="<?= $post['saved'] ? 0 : 1 ?>" aria-pressed="<?= $post['saved'] ? 'true' : 'false' ?>"><svg class="bookmark-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg><span data-reaction-label><?= $post['saved'] ? 'Đã lưu' : 'Lưu' ?></span></button>
        </form>
    <?php else: ?>
        <div class="feed-vote-group">
            <a class="feed-action vote-button" data-vote="1" href="login.php?return_to=<?= e(rawurlencode(app_url($postUrl))) ?>" aria-label="Đăng nhập để thích bài viết"><?php require __DIR__ . '/vote-icon.php'; ?><span><?= e($post['likes']) ?></span></a>
            <a class="feed-action vote-button" data-vote="-1" href="login.php?return_to=<?= e(rawurlencode(app_url($postUrl))) ?>" aria-label="Đăng nhập để không thích bài viết"><?php require __DIR__ . '/vote-icon.php'; ?><span><?= e($post['dislikes']) ?></span></a>
        </div>
        <a class="feed-action heart-button" href="login.php?return_to=<?= e(rawurlencode(app_url($postUrl))) ?>" aria-label="Đăng nhập để lưu bài viết"><svg class="bookmark-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg><span>Lưu</span></a>
    <?php endif; ?>
    <a class="feed-action" href="<?= e($postUrl) ?>#comments"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 10 10 0 0 1-4-.8L3 21l1.8-5.5a10 10 0 0 1-.8-4A8.5 8.5 0 0 1 12.5 3a8.5 8.5 0 0 1 8.5 8.5Z"/></svg><span><?= e($post['comment_count']) ?> bình luận</span></a>
    <?php if (can_edit_post($post, $user)): ?>
        <a class="feed-read-link" href="blog-editor.php?edit=<?= e(rawurlencode($post['slug'])) ?>">Sửa bài</a>
    <?php endif; ?>
</div>
