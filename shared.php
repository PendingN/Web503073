<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/blog-images.php';
$user = require_login();

$pageTitle = 'Được chia sẻ với tôi';
$bodyClass = 'profile-page';
$activePage = 'shared';
$flashMessage = take_flash();
$shares = list_shared_with((int) $user['id']);
// Opening this list is what clears the "new share" notice.
mark_shares_seen((int) $user['id']);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

        <main id="main-content" class="shared-page" tabindex="-1">
            <?php if ($flashMessage !== null): ?><div class="status-note" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <header class="shared-head">
                <h1>Được chia sẻ với tôi</h1>
                <p><?= count($shares) ?> bài viết người khác chia sẻ cho bạn</p>
            </header>

            <?php if ($shares === []): ?>
                <div class="profile-empty">
                    <h3>Chưa có bài nào được chia sẻ với bạn.</h3>
                    <p>Khi ai đó chia sẻ bài viết qua email của bạn, bài sẽ xuất hiện ở đây.</p>
                </div>
            <?php else: ?>
                <ul class="shared-list">
                    <?php foreach ($shares as $share): ?>
                        <?php $isLocked = !empty($share['lock_hash']); ?>
                        <li class="shared-item<?= $share['seen_at'] === null ? ' is-new' : '' ?>">
                            <a class="shared-cover" href="blog-post.php?slug=<?= e(rawurlencode($share['slug'])) ?>" tabindex="-1" aria-hidden="true">
                                <img src="<?= e(blog_cover_url($share)) ?>" alt="" width="160" height="110" loading="lazy" decoding="async">
                            </a>
                            <div class="shared-body">
                                <p class="shared-badges">
                                    <?php if ($share['permission'] === 'edit'): ?>
                                        <span class="post-badge is-edit">✎ Được sửa</span>
                                    <?php else: ?>
                                        <span class="post-badge">👁 Chỉ xem</span>
                                    <?php endif; ?>
                                    <?php if ($isLocked): ?><span class="post-badge is-locked">🔒 Có mật khẩu</span><?php endif; ?>
                                    <?php if ($share['seen_at'] === null): ?><span class="post-badge is-new">Mới</span><?php endif; ?>
                                </p>
                                <h2><a href="blog-post.php?slug=<?= e(rawurlencode($share['slug'])) ?>"><?= e($share['title']) ?></a></h2>
                                <?php if (!$isLocked): ?><p class="shared-excerpt"><?= e($share['excerpt']) ?></p><?php endif; ?>
                                <p class="shared-meta">
                                    <?= e($share['shared_by_name']) ?> đã chia sẻ lúc
                                    <time datetime="<?= e(str_replace(' ', 'T', $share['shared_at'])) ?>"><?= e(date('d/m/Y H:i', strtotime($share['shared_at']))) ?></time>
                                </p>
                            </div>
                            <div class="shared-actions">
                                <a class="btn-outline-brand" href="blog-post.php?slug=<?= e(rawurlencode($share['slug'])) ?>">Xem bài</a>
                                <?php if ($share['permission'] === 'edit'): ?>
                                    <a class="btn-brand" href="blog-editor.php?edit=<?= e(rawurlencode($share['slug'])) ?>">Sửa cùng</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
