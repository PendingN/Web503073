<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$admin = require_admin('admin-tags.php');
$tags = list_tags(true);
$tagError = $_SESSION['tag_error'] ?? null;
unset($_SESSION['tag_error']);
$errorMessages = is_array($tagError) ? (array) ($tagError['messages'] ?? []) : [];
$newName = is_array($tagError) && ($tagError['operation'] ?? '') === 'create' ? (string) ($tagError['name'] ?? '') : '';
$pageTitle = 'Quản lý chủ đề';
$bodyClass = 'admin-page';
$adminActive = 'tags';
$flashMessage = take_flash();
require __DIR__ . '/includes/header.php';
?>
<div class="admin-shell min-h-screen">
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>
    <div id="admin-content" class="min-w-0 lg:ml-[270px]">
        <?php require __DIR__ . '/includes/admin-topbar.php'; ?>
        <main id="admin-main" class="mx-auto w-full max-w-[1480px] space-y-6 px-4 py-6 sm:px-6 xl:px-8">
            <?php if ($flashMessage !== null): ?><div class="status-note" role="status" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <?php if ($errorMessages !== []): ?><div class="login-error" role="alert"><ul><?php foreach ($errorMessages as $message): ?><li><?= e((string) $message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div><h2 class="text-lg font-bold">Chủ đề bài viết</h2><p class="mt-1 text-sm text-muted">Mỗi bài viết có thể không có, có một hoặc nhiều chủ đề. Đổi tên chủ đề cập nhật mọi bài gắn chủ đề đó; xóa chủ đề chỉ gỡ nhãn, bài viết vẫn được giữ lại.</p></div>

            <section class="admin-card p-5 sm:p-6" aria-labelledby="tag-add-title">
                <h2 class="mb-4 text-lg font-bold" id="tag-add-title">Thêm chủ đề</h2>
                <form class="grid items-end gap-4 sm:grid-cols-[minmax(0,1fr)_auto]" method="post" action="actions/tag-action.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="operation" value="create">
                    <div><label class="admin-field-label" for="tag-new-name">Tên chủ đề</label><input class="admin-input" id="tag-new-name" name="name" value="<?= e($newName) ?>" maxlength="<?= TAG_NAME_MAX_LENGTH ?>" placeholder="Ví dụ: Cây để bàn" required></div>
                    <button class="admin-button min-h-11" type="submit">Thêm chủ đề</button>
                </form>
            </section>

            <section class="admin-card min-w-0 overflow-hidden" id="tags" aria-labelledby="tags-title">
                <div class="border-b border-border p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-bold" id="tags-title">Danh sách chủ đề</h2><span class="rounded-full bg-primary/10 px-3 py-1.5 text-xs text-primary-dark"><?= count($tags) ?> chủ đề</span></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="admin-table min-w-[700px]">
                        <thead><tr><th>ID</th><th>Tên chủ đề</th><th>Số bài viết</th><th>Thao tác</th></tr></thead>
                        <tbody>
                            <?php foreach ($tags as $tag): ?>
                                <?php
                                $renameFailed = is_array($tagError) && ($tagError['operation'] ?? '') === 'rename' && (int) ($tagError['tag_id'] ?? 0) === $tag['id'];
                                $nameValue = $renameFailed ? (string) $tagError['name'] : $tag['name'];
                                ?>
                                <tr>
                                    <td class="text-xs text-muted">#<?= (int) $tag['id'] ?></td>
                                    <td>
                                        <form class="flex min-w-72 items-center gap-2" method="post" action="actions/tag-action.php">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="operation" value="rename">
                                            <input type="hidden" name="tag_id" value="<?= (int) $tag['id'] ?>">
                                            <label class="sr-only" for="tag-name-<?= (int) $tag['id'] ?>">Tên chủ đề #<?= (int) $tag['id'] ?></label>
                                            <input class="admin-input min-h-10 py-2" id="tag-name-<?= (int) $tag['id'] ?>" name="name" value="<?= e($nameValue) ?>" maxlength="<?= TAG_NAME_MAX_LENGTH ?>" required>
                                            <button class="admin-button-secondary shrink-0" type="submit">Đổi tên</button>
                                        </form>
                                    </td>
                                    <td class="text-xs"><?php if ($tag['post_count'] > 0): ?><a class="admin-panel-link" href="admin-posts.php?<?= e(http_build_query(['tag' => $tag['id']])) ?>"><?= (int) $tag['post_count'] ?> bài viết</a><?php else: ?><span class="text-muted">Chưa có bài</span><?php endif; ?></td>
                                    <td>
                                        <form method="post" action="actions/tag-action.php" data-confirm="Chủ đề “<?= e($tag['name']) ?>” sẽ được gỡ khỏi <?= (int) $tag['post_count'] ?> bài viết. Các bài viết vẫn được giữ lại." data-confirm-title="Xóa chủ đề?" data-confirm-label="Xóa chủ đề" data-confirm-tone="danger">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="operation" value="delete">
                                            <input type="hidden" name="tag_id" value="<?= (int) $tag['id'] ?>">
                                            <button class="inline-flex min-h-[36px] items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100" type="submit">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($tags === []): ?><tr><td class="py-10 text-center text-muted" colspan="4">Chưa có chủ đề nào. Thêm chủ đề đầu tiên ở trên.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
