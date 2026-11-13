<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$admin = require_admin('admin-posts.php');
$search = mb_substr(trim(request_string($_GET, 'search')), 0, 200, 'UTF-8');
$tags = ['Chăm cây', 'Không gian', 'Cảm hứng', 'Mẹo hay', 'Sống xanh', 'Bài nổi bật'];
$tag = request_string($_GET, 'tag');
$tag = in_array($tag, $tags, true) ? $tag : '';
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$result = list_admin_posts($search, $tag, $page);
if (isset($_GET['edit']) || request_string($_GET, 'create') === '1') {
    redirect_to(app_url('blog-editor.php?from=admin' . (isset($_GET['edit']) ? '&edit=' . rawurlencode(request_string($_GET, 'edit')) : '')));
}
$pageTitle = 'Quản trị bài viết';
$bodyClass = 'admin-page';
$adminActive = 'posts';
$flashMessage = take_flash();
$query = ['search' => $search, 'tag' => $tag];
require __DIR__ . '/includes/header.php';
?>
<div class="admin-shell min-h-screen">
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>
    <div id="admin-content" class="min-w-0 lg:ml-[270px]">
        <?php require __DIR__ . '/includes/admin-topbar.php'; ?>
        <main id="admin-main" class="mx-auto w-full max-w-[1480px] space-y-6 px-4 py-6 sm:px-6 xl:px-8">
            <?php if ($flashMessage !== null): ?><div class="status-note" role="status" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="text-xl font-semibold">Nhật ký của cộng đồng</h2></div><a class="admin-button" href="admin-posts.php?create=1#post-editor">Viết bài mới <?php admin_icon('arrow', 'size-4'); ?></a></div>
            <section class="admin-card min-w-0 overflow-hidden" aria-labelledby="posts-title">
                <div class="border-b border-border p-5 sm:p-6">
                    <div class="mb-5 flex items-center justify-between gap-3"><h2 class="text-base font-semibold" id="posts-title">Toàn bộ bài viết</h2><span class="rounded-full bg-primary/10 px-3 py-1.5 text-xs text-primary-dark"><?= (int) $result['total'] ?> bài viết</span></div>
                    <form class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_220px_auto]" method="get" action="admin-posts.php">
                        <div><label class="admin-field-label" for="post-search">Tìm bài viết</label><input class="admin-input" id="post-search" type="search" name="search" maxlength="200" value="<?= e($search) ?>" placeholder="Tiêu đề hoặc tác giả"></div>
                        <div><label class="admin-field-label" for="post-filter-tag">Chủ đề</label><select class="admin-input" id="post-filter-tag" name="tag"><option value="">Tất cả</option><?php foreach ($tags as $option): ?><option value="<?= e($option) ?>"<?= $tag === $option ? ' selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
                        <div class="flex flex-wrap gap-2"><button class="admin-button min-h-11" type="submit">Tìm kiếm</button><?php if ($search !== '' || $tag !== ''): ?><a class="admin-button-secondary min-h-11" href="admin-posts.php">Xóa bộ lọc</a><?php endif; ?></div>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="admin-table min-w-[800px]">
                        <thead><tr><th>Bài viết</th><th>Tác giả</th><th>Chủ đề</th><th>Bình luận</th><th>Ngày đăng</th><th>Thao tác</th></tr></thead>
                        <tbody>
                            <?php foreach ($result['posts'] as $post): ?>
                                <tr><td><a class="block max-w-80 text-xs font-semibold leading-relaxed wrap-anywhere hover:text-primary-dark" href="blog-post.php?slug=<?= e(rawurlencode($post['slug'])) ?>"><?= e($post['title']) ?></a><span class="mt-1 block text-[10px] text-muted">#<?= (int) $post['id'] ?><?= (int) $post['is_hidden'] === 1 ? ' · <strong class="text-amber-700">Đang ẩn</strong>' : '' ?></span></td><td class="max-w-48 text-xs wrap-anywhere"><?= e($post['author']) ?></td><td class="text-xs whitespace-nowrap text-muted"><?= e($post['tag']) ?></td><td class="text-xs"><?= (int) $post['comment_count'] ?></td><td class="text-xs whitespace-nowrap text-muted"><?= e(date('d/m/Y', strtotime($post['created_at']))) ?></td><td>
                                    <div class="flex items-start gap-4"><a class="admin-panel-link" href="admin-posts.php?edit=<?= e(rawurlencode($post['slug'])) ?>#post-editor">Sửa</a><form method="post" action="actions/blog-action.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="slug" value="<?= e($post['slug']) ?>"><input type="hidden" name="operation" value="<?= (int) $post['is_hidden'] === 1 ? 'unhide' : 'hide' ?>"><input type="hidden" name="return_to" value="<?= e(app_url('admin-posts.php')) ?>"><button class="text-xs font-medium <?= (int) $post['is_hidden'] === 1 ? 'text-emerald-700 hover:text-emerald-800' : 'text-amber-700 hover:text-amber-800' ?>" type="submit"><?= (int) $post['is_hidden'] === 1 ? 'Hiện lại' : 'Ẩn bài' ?></button></form><details class="text-xs text-red-700"><summary class="font-medium">Xóa</summary><form class="mt-3 max-w-52 space-y-3 rounded-lg border border-red-200 bg-red-50 p-3" method="post" action="actions/blog-action.php"><p class="leading-relaxed">Xóa bài viết cùng bình luận và tương tác? Thao tác không thể hoàn tác.</p><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="operation" value="delete"><input type="hidden" name="slug" value="<?= e($post['slug']) ?>"><input type="hidden" name="return_to" value="<?= e(app_url('admin-posts.php')) ?>"><button class="rounded-lg bg-red-700 px-3 py-2 font-medium text-white hover:bg-red-800" type="submit">Xác nhận xóa</button></form></details></div>
                                </td></tr>
                            <?php endforeach; ?>
                            <?php if ($result['posts'] === []): ?><tr><td class="py-10 text-center text-muted" colspan="6">Không tìm thấy bài viết phù hợp.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-border p-5 text-xs" aria-label="Phân trang bài viết quản trị"><span class="text-muted">Trang <?= (int) $result['page'] ?> / <?= (int) $result['pages'] ?></span><div class="flex gap-2"><?php if ($result['page'] > 1): ?><a class="admin-button-secondary" href="admin-posts.php?<?= e(http_build_query($query + ['page' => $result['page'] - 1])) ?>">Trước</a><?php endif; ?><?php if ($result['page'] < $result['pages']): ?><a class="admin-button-secondary" href="admin-posts.php?<?= e(http_build_query($query + ['page' => $result['page'] + 1])) ?>">Tiếp</a><?php endif; ?></div></nav>
            </section>
        </main>
    </div>
</div>
<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
