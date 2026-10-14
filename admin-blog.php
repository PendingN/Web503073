<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_admin();
$blogSearch = mb_substr(trim(request_string($_GET, 'bq')), 0, 200, 'UTF-8');
$blogPosts = list_admin_blog_posts($blogSearch);
$hiddenCount = count(array_filter($blogPosts, static fn (array $post): bool => (int) $post['is_hidden'] === 1));
$adminActive = 'blog';
$adminHeading = 'Kiểm duyệt blog';
$pageTitle = 'Kiểm duyệt blog';
require __DIR__ . '/includes/admin-start.php';
?>
            <section class="admin-panel orders-panel" id="blog">
                <div class="panel-head"><div><p class="eyebrow">KIỂM DUYỆT BLOG</p><h2><?= count($blogPosts) ?> bài viết<?= $hiddenCount > 0 ? ' · ' . $hiddenCount . ' đang ẩn' : '' ?></h2></div></div>
                <form class="filter-panel" method="get" action="admin-blog.php">
                    <label>Tìm bài viết<input type="search" name="bq" value="<?= e($blogSearch) ?>" maxlength="200" placeholder="Tiêu đề, chủ đề hoặc tác giả"></label>
                    <button type="submit">Tìm kiếm</button>
                    <?php if ($blogSearch !== ''): ?><a href="admin-blog.php">Xóa bộ lọc</a><?php endif; ?>
                </form>
                <div class="admin-table-wrap">
                    <table>
                        <thead><tr><th>Tiêu đề</th><th>Chủ đề</th><th>Tác giả</th><th>Ngày đăng</th><th>Bình luận</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                        <tbody>
                            <?php foreach ($blogPosts as $post): ?>
                                <tr class="<?= (int) $post['is_hidden'] === 1 ? 'row-hidden' : '' ?>">
                                    <td><a href="blog-post.php?slug=<?= e(rawurlencode($post['slug'])) ?>"><strong><?= e($post['title']) ?></strong></a></td>
                                    <td><span class="category-badge"><?= e($post['tag']) ?></span></td>
                                    <td><?= e($post['author']) ?></td>
                                    <td><?= e(date('d/m/Y', strtotime($post['created_at']))) ?></td>
                                    <td><?= (int) $post['comment_count'] ?></td>
                                    <td><span class="user-status <?= (int) $post['is_hidden'] === 1 ? 'status-hidden' : 'status-active' ?>"><?= (int) $post['is_hidden'] === 1 ? 'Đang ẩn' : 'Công khai' ?></span></td>
                                    <td><div class="user-actions">
                                        <form method="post" action="actions/blog-action.php">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
                                            <input type="hidden" name="operation" value="<?= (int) $post['is_hidden'] === 1 ? 'unhide' : 'hide' ?>">
                                            <input type="hidden" name="return_to" value="admin-blog.php">
                                            <button class="<?= (int) $post['is_hidden'] === 1 ? 'unblock-button' : 'hide-button' ?>" type="submit"><?= (int) $post['is_hidden'] === 1 ? 'Hiện lại' : 'Ẩn bài' ?></button>
                                        </form>
                                        <form method="post" action="actions/blog-action.php" onsubmit="return confirm('Xóa bài viết này? Thao tác không hoàn tác.');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
                                            <input type="hidden" name="operation" value="delete">
                                            <input type="hidden" name="return_to" value="admin-blog.php">
                                            <button class="block-button" type="submit">Xóa</button>
                                        </form>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($blogPosts === []): ?><tr><td colspan="7">Không tìm thấy bài viết phù hợp.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
<?php require __DIR__ . '/includes/admin-end.php'; ?>
