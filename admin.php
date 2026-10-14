<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$admin = require_admin();
$search = mb_substr(trim(request_string($_GET, 'search')), 0, 200, 'UTF-8');
$status = request_string($_GET, 'status');
if (!in_array($status, ['active', 'blocked'], true)) {
    $status = '';
}
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$result = list_users($search, $status, $page);
$stats = user_statistics();
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editUser = $editId === false ? null : find_user((int) $editId);
if (isset($_GET['edit']) && $editUser === null) {
    abort_request(404, 'Không tìm thấy tài khoản này.');
}
$errors = $_SESSION['admin_errors'] ?? [];
$input = $_SESSION['admin_input'] ?? null;
unset($_SESSION['admin_errors'], $_SESSION['admin_input']);
if ($editUser !== null && ($input['id'] ?? null) !== (int) $editUser['id']) {
    $input = ['name' => $editUser['name'], 'email' => $editUser['email']];
    $errors = [];
}
$pageTitle = 'Quản lý người dùng';
$bodyClass = 'admin-page';
$adminActive = 'users';
$flashMessage = take_flash();
require __DIR__ . '/includes/header.php';
?>
<div class="admin-shell min-h-screen">
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>
    <div id="admin-content" class="min-w-0 lg:ml-[270px]">
        <?php require __DIR__ . '/includes/admin-topbar.php'; ?>
        <main id="admin-main" class="mx-auto w-full max-w-[1480px] space-y-6 px-4 py-6 sm:px-6 xl:px-8">
            <?php if ($flashMessage !== null): ?><div class="status-note" role="status" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div><h2 class="text-xl font-semibold">Thành viên khu vườn</h2></div>
                <a class="admin-button-secondary" href="admin-dashboard.php"><?php admin_icon('dashboard', 'size-4'); ?>Tổng quan</a>
            </div>
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" id="overview" aria-label="Thống kê tài khoản">
                <article class="rounded-xl bg-blue-50 p-5"><div class="flex items-center justify-between text-primary-dark"><span class="text-xs font-medium">Tổng tài khoản</span><?php admin_icon('users'); ?></div><strong class="mt-3 block text-3xl font-semibold text-primary-dark"><?= (int) $stats['total'] ?></strong></article>
                <article class="rounded-xl bg-emerald-50 p-5"><div class="flex items-center justify-between text-emerald-700"><span class="text-xs font-medium">Đang hoạt động</span><?php admin_icon('check'); ?></div><strong class="mt-3 block text-3xl font-semibold text-emerald-800"><?= (int) $stats['active'] ?></strong></article>
                <article class="rounded-xl bg-rose-50 p-5"><div class="flex items-center justify-between text-rose-700"><span class="text-xs font-medium">Đã khóa</span><?php admin_icon('lock'); ?></div><strong class="mt-3 block text-3xl font-semibold text-rose-800"><?= (int) $stats['blocked'] ?></strong></article>
                <article class="rounded-xl bg-amber-50 p-5"><div class="flex items-center justify-between text-amber-700"><span class="text-xs font-medium">Quản trị viên</span><?php admin_icon('shield'); ?></div><strong class="mt-3 block text-3xl font-semibold text-amber-800"><?= (int) $stats['admins'] ?></strong></article>
            </section>
            <?php if ($editUser !== null): ?>
                <section class="admin-card scroll-mt-28 p-5 sm:p-6" id="edit-user" aria-labelledby="edit-user-title">
                    <div class="flex items-start justify-between gap-4"><div><p class="mb-2 text-[10px] font-semibold tracking-wider text-primary-dark">TÀI KHOẢN #<?= (int) $editUser['id'] ?></p><h2 class="text-lg font-semibold" id="edit-user-title">Thông tin người dùng</h2></div><a class="admin-button-secondary" href="admin.php#users"><?php admin_icon('close', 'size-4'); ?>Đóng</a></div>
                    <p class="my-5 text-xs leading-loose text-muted"><?= $editUser['role'] === 'admin' ? 'Quản trị viên' : 'Thành viên' ?> · <?= $editUser['status'] === 'active' ? 'Đang hoạt động' : 'Đã khóa' ?> · Tham gia <?= e(date('d/m/Y', strtotime($editUser['created_at']))) ?> · <?= count(user_favorite_ids((int) $editUser['id'])) ?> cây yêu thích · <a class="admin-panel-link" href="admin-history.php?user_id=<?= (int) $editUser['id'] ?>">Lịch sử 3 ngày</a></p>
                    <?php if ($errors !== []): ?><div class="login-error mb-5" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                    <form class="grid gap-5 md:grid-cols-2" method="post" action="actions/user-action.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="user_id" value="<?= (int) $editUser['id'] ?>">
                        <input type="hidden" name="operation" value="update">
                        <input type="hidden" name="return_to" value="admin.php?edit=<?= (int) $editUser['id'] ?>">
                        <div><label class="admin-field-label" for="user_name">Họ tên</label><input class="admin-input" id="user_name" name="name" value="<?= e($input['name']) ?>" minlength="2" maxlength="100" required></div>
                        <div><label class="admin-field-label" for="user_email">Email</label><input class="admin-input" id="user_email" name="email" type="email" value="<?= e($input['email']) ?>" maxlength="254" required></div>
                        <div class="md:col-span-2"><button class="admin-button" type="submit">Lưu thông tin</button></div>
                    </form>
                </section>
            <?php endif; ?>
            <section class="admin-card min-w-0 scroll-mt-28 overflow-hidden" id="users" aria-labelledby="users-title">
                <div class="border-b border-border p-5 sm:p-6">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><h2 class="text-base font-semibold" id="users-title">Danh sách tài khoản</h2><span class="rounded-full bg-primary/10 px-3 py-1.5 text-xs font-medium text-primary-dark"><?= (int) $result['total'] ?> tài khoản</span></div>
                    <form class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_200px_auto]" method="get" action="admin.php">
                        <div><label class="admin-field-label" for="user-search">Tìm người dùng</label><input class="admin-input" id="user-search" type="search" name="search" value="<?= e($search) ?>" maxlength="200" placeholder="Họ tên hoặc email"></div>
                        <div><label class="admin-field-label" for="user-status">Trạng thái</label><select class="admin-input" id="user-status" name="status"><option value="">Tất cả</option><option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Đang hoạt động</option><option value="blocked"<?= $status === 'blocked' ? ' selected' : '' ?>>Đã khóa</option></select></div>
                        <div class="flex flex-wrap gap-2"><button class="admin-button min-h-11" type="submit"><?php admin_icon('search', 'size-4'); ?>Tìm kiếm</button><?php if ($search !== '' || $status !== ''): ?><a class="admin-button-secondary min-h-11" href="admin.php">Xóa bộ lọc</a><?php endif; ?></div>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="admin-table min-w-[850px]">
                        <thead><tr><th>ID</th><th>Họ tên / Email</th><th>Vai trò</th><th>Trạng thái</th><th>Yêu thích</th><th>Ngày tham gia</th><th>Thao tác</th></tr></thead>
                        <tbody>
                            <?php foreach ($result['users'] as $member): ?>
                                <tr>
                                    <td class="text-xs text-muted">#<?= (int) $member['id'] ?></td>
                                    <td><strong class="block max-w-64 text-xs font-semibold wrap-anywhere"><?= e($member['name']) ?></strong><span class="mt-1 block max-w-64 text-[11px] wrap-anywhere text-muted"><?= e($member['email']) ?></span></td>
                                    <td><span class="rounded-md bg-soft px-2 py-1 text-[11px] whitespace-nowrap text-muted"><?= $member['role'] === 'admin' ? 'Quản trị viên' : 'Thành viên' ?></span></td>
                                    <td><span class="status-<?= e($member['status']) ?>"><?= $member['status'] === 'active' ? 'Đang hoạt động' : 'Đã khóa' ?></span></td>
                                    <td class="text-xs"><?= (int) $member['favorite_count'] ?></td>
                                    <td class="text-xs whitespace-nowrap text-muted"><?= e(date('d/m/Y', strtotime($member['created_at']))) ?></td>
                                    <td><div class="flex items-center gap-3 whitespace-nowrap">
                                        <a class="admin-panel-link" href="admin.php?edit=<?= (int) $member['id'] ?>#edit-user">Xem / sửa</a>
                                        <a class="admin-panel-link" href="admin-history.php?user_id=<?= (int) $member['id'] ?>">Lịch sử</a>
                                        <?php if ($member['role'] !== 'admin'): ?>
                                            <form method="post" action="actions/user-action.php">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                                                <input type="hidden" name="operation" value="<?= $member['status'] === 'active' ? 'block' : 'unblock' ?>">
                                                <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
                                                <button class="rounded-md px-2 py-1.5 text-xs font-medium <?= $member['status'] === 'active' ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>" type="submit"><?= $member['status'] === 'active' ? 'Khóa' : 'Mở khóa' ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($result['users'] === []): ?><tr><td class="py-10 text-center text-muted" colspan="7">Không tìm thấy tài khoản phù hợp.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-border p-5 text-xs sm:px-6" aria-label="Phân trang người dùng">
                    <span class="text-muted">Trang <?= (int) $result['page'] ?> / <?= (int) $result['pages'] ?></span>
                    <div class="flex gap-2">
                        <?php if ($result['page'] > 1): ?><a class="admin-button-secondary" href="admin.php?<?= e(http_build_query(['search' => $search, 'status' => $status, 'page' => $result['page'] - 1])) ?>#users">Trước</a><?php endif; ?>
                        <?php if ($result['page'] < $result['pages']): ?><a class="admin-button-secondary" href="admin.php?<?= e(http_build_query(['search' => $search, 'status' => $status, 'page' => $result['page'] + 1])) ?>#users">Tiếp</a><?php endif; ?>
                    </div>
                </nav>
            </section>
            <p class="pb-2 text-center text-[10px] text-muted">Vũ Điệu Rừng Xanh · Không gian quản trị</p>
        </main>
    </div>
</div>
<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
