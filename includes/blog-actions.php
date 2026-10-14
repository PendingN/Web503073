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
            <button class="feed-action heart-button<?= $post['saved'] ? ' is-active' : '' ?>" name="saved" value="<?= $post['saved'] ? 0 : 1 ?>" aria-pressed="<?= $post['saved'] ? 'true' : 'false' ?>"><?php require __DIR__ . '/heart-icon.php'; ?><span data-reaction-label><?= $post['saved'] ? 'Đã lưu' : 'Lưu' ?></span></button>
        </form>
    <?php else: ?>
        <div class="feed-vote-group">
            <a class="feed-action vote-button" data-vote="1" href="login.php?return_to=<?= e(rawurlencode(app_url($postUrl))) ?>" aria-label="Đăng nhập để thích bài viết"><?php require __DIR__ . '/vote-icon.php'; ?><span><?= e($post['likes']) ?></span></a>
            <a class="feed-action vote-button" data-vote="-1" href="login.php?return_to=<?= e(rawurlencode(app_url($postUrl))) ?>" aria-label="Đăng nhập để không thích bài viết"><?php require __DIR__ . '/vote-icon.php'; ?><span><?= e($post['dislikes']) ?></span></a>
        </div>
        <a class="feed-action" href="login.php?return_to=<?= e(rawurlencode(app_url($postUrl))) ?>">Đăng nhập để lưu</a>
    <?php endif; ?>
    <a class="feed-action" href="<?= e($postUrl) ?>#comments"><?= e($post['comment_count']) ?> bình luận</a>
    <?php if (can_edit_post($post, $user)): ?>
        <a class="feed-read-link" href="blog.php?edit=<?= e(rawurlencode($post['slug'])) ?>#write-post">Sửa bài</a>
    <?php endif; ?>
</div>
