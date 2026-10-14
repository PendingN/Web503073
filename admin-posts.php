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
$editSlug = request_string($_GET, 'edit');
$editing = $editSlug !== '' ? find_post($editSlug) : null;
if (isset($_GET['edit']) && $editing === null) {
    abort_request(404, 'Không tìm thấy bài viết.');
}
$showEditor = $editing !== null || request_string($_GET, 'create') === '1';
$draft = $_SESSION['blog_draft'] ?? null;
unset($_SESSION['blog_draft']);
$form = is_array($draft) && $draft['slug'] === $editSlug ? $draft : ($editing ?? ['title' => '', 'content' => '', 'tag' => 'Chăm cây']);
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
            <div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="text-xl font-semibold">Nhật ký của cộng đồng</h2><p class="mt-2 text-xs text-muted">Xem, tìm kiếm, tạo, sửa và xóa bài viết của toàn bộ người dùng.</p></div><a class="admin-button" href="admin-posts.php?create=1#post-editor">Viết bài mới <?php admin_icon('arrow', 'size-4'); ?></a></div>
            <?php if ($showEditor): ?>
                <section class="admin-card scroll-mt-28 p-5 sm:p-6" id="post-editor" aria-labelledby="post-editor-title">
                    <div class="mb-6 flex items-center justify-between gap-3"><h2 class="text-lg font-semibold" id="post-editor-title"><?= $editing !== null ? 'Sửa bài viết' : 'Viết bài mới' ?></h2><a class="admin-button-secondary" href="admin-posts.php">Đóng <?php admin_icon('close', 'size-4'); ?></a></div>
                    <form class="space-y-5" method="post" action="actions/blog-action.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="operation" value="<?= $editing !== null ? 'update' : 'create' ?>">
                        <input type="hidden" name="slug" value="<?= e($editSlug) ?>">
                        <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
                        <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_220px]">
                            <div><label class="admin-field-label" for="post-title">Tiêu đề</label><input class="admin-input" id="post-title" name="title" value="<?= e($form['title']) ?>" minlength="3" maxlength="120" required></div>
                            <div><label class="admin-field-label" for="post-tag">Chủ đề</label><select class="admin-input" id="post-tag" name="tag"><?php foreach ($tags as $option): ?><option value="<?= e($option) ?>"<?= $form['tag'] === $option ? ' selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div><label class="admin-field-label" for="post-image">Ảnh bài viết</label><input type="hidden" name="MAX_FILE_SIZE" value="2097152"><input class="admin-input" id="post-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-describedby="post-image-hint"><p class="mt-2 text-xs text-muted" id="post-image-hint">JPG, PNG, WebP tối đa 2 MB. Không chọn ảnh mới sẽ giữ ảnh hiện tại.</p><?php if (!empty($editing['image_path'])): ?><img class="mt-3 max-h-48 rounded-lg object-contain" src="<?= e($editing['image_path']) ?>" alt="Ảnh bài viết hiện tại"><?php endif; ?></div>
                        <div><label class="admin-field-label" for="post-content">Nội dung</label><textarea class="admin-input min-h-56 leading-relaxed" id="post-content" name="content" minlength="10" maxlength="10000" rows="9" required><?= e($form['content']) ?></textarea></div>
                        <button class="admin-button" type="submit"><?= $editing !== null ? 'Lưu thay đổi' : 'Đăng bài viết' ?></button>
                    </form>
                </section>
            <?php endif; ?>
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
                                <tr><td><a class="block max-w-80 text-xs font-semibold leading-relaxed wrap-anywhere hover:text-primary-dark" href="blog-post.php?slug=<?= e(rawurlencode($post['slug'])) ?>"><?= e($post['title']) ?></a><span class="mt-1 block text-[10px] text-muted">#<?= (int) $post['id'] ?></span></td><td class="max-w-48 text-xs wrap-anywhere"><?= e($post['author']) ?></td><td class="text-xs whitespace-nowrap text-muted"><?= e($post['tag']) ?></td><td class="text-xs"><?= (int) $post['comment_count'] ?></td><td class="text-xs whitespace-nowrap text-muted"><?= e(date('d/m/Y', strtotime($post['created_at']))) ?></td><td>
                                    <div class="flex items-start gap-4"><a class="admin-panel-link" href="admin-posts.php?edit=<?= e(rawurlencode($post['slug'])) ?>#post-editor">Sửa</a><details class="text-xs text-red-700"><summary class="font-medium">Xóa</summary><form class="mt-3 max-w-52 space-y-3 rounded-lg border border-red-200 bg-red-50 p-3" method="post" action="actions/blog-action.php"><p class="leading-relaxed">Xóa bài viết cùng bình luận và tương tác? Thao tác không thể hoàn tác.</p><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="operation" value="delete"><input type="hidden" name="slug" value="<?= e($post['slug']) ?>"><input type="hidden" name="return_to" value="<?= e(app_url('admin-posts.php')) ?>"><button class="rounded-lg bg-red-700 px-3 py-2 font-medium text-white hover:bg-red-800" type="submit">Xác nhận xóa</button></form></details></div>
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
