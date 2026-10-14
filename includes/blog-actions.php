<?php $postUrl = 'blog-post.php?slug=' . rawurlencode($post['slug']); ?>
<div class="feed-post-actions">
    <?php if ($user !== null): ?>
        <form class="feed-vote-group" action="actions/blog-action.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="operation" value="vote">
            <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
            <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
            <button class="feed-action<?= (int) $post['my_vote'] === 1 ? ' is-active' : '' ?>" name="value" value="<?= (int) $post['my_vote'] === 1 ? 0 : 1 ?>" aria-label="Thích bài viết" aria-pressed="<?= (int) $post['my_vote'] === 1 ? 'true' : 'false' ?>">Thích · <?= e($post['score']) ?></button>
            <button class="feed-action<?= (int) $post['my_vote'] === -1 ? ' is-active' : '' ?>" name="value" value="<?= (int) $post['my_vote'] === -1 ? 0 : -1 ?>" aria-label="Không thích bài viết" aria-pressed="<?= (int) $post['my_vote'] === -1 ? 'true' : 'false' ?>">Không thích</button>
        </form>
        <form action="actions/blog-action.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="operation" value="save">
            <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
            <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
            <button class="feed-action<?= $post['saved'] ? ' is-active' : '' ?>" name="saved" value="<?= $post['saved'] ? 0 : 1 ?>" aria-pressed="<?= $post['saved'] ? 'true' : 'false' ?>"><?= $post['saved'] ? '♥ Đã lưu' : '♡ Lưu' ?></button>
        </form>
    <?php else: ?>
        <a class="feed-action" href="login.php?return_to=<?= e(rawurlencode(app_url($postUrl))) ?>">Đăng nhập để thích / lưu · <?= e($post['score']) ?></a>
    <?php endif; ?>
    <a class="feed-action" href="<?= e($postUrl) ?>#comments"><?= e($post['comment_count']) ?> bình luận</a>
    <?php if (can_edit_post($post, $user)): ?>
        <a class="feed-read-link" href="blog.php?edit=<?= e(rawurlencode($post['slug'])) ?>#write-post">Sửa bài</a>
    <?php endif; ?>
</div>
