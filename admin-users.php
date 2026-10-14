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
$adminActive = 'users';
$adminHeading = 'Người dùng';
$pageTitle = 'Quản lý người dùng';
require __DIR__ . '/includes/admin-start.php';
?>
            <?php if ($editUser !== null): ?>
                <section class="admin-panel account-panel" id="edit-user">
                    <div class="panel-head"><div><p class="eyebrow">TÀI KHOẢN #<?= (int) $editUser['id'] ?></p><h2>Thông tin người dùng</h2></div><a href="admin-users.php">Đóng ×</a></div>
                    <p class="account-meta"><?= $editUser['role'] === 'admin' ? 'Quản trị viên' : 'Thành viên' ?> · <?= $editUser['status'] === 'active' ? 'Đang hoạt động' : 'Đã khóa' ?> · Tham gia <?= e(date('d/m/Y', strtotime($editUser['created_at']))) ?> · <?= count(user_favorite_ids((int) $editUser['id'])) ?> cây yêu thích</p>
                    <?php if ($errors !== []): ?><div class="login-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                    <form class="account-form" method="post" action="actions/user-action.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="user_id" value="<?= (int) $editUser['id'] ?>">
                        <input type="hidden" name="operation" value="update">
                        <input type="hidden" name="return_to" value="admin-users.php?edit=<?= (int) $editUser['id'] ?>">
                        <div class="form-group"><label class="form-label" for="user_name">Họ tên</label><input class="form-control" id="user_name" name="name" value="<?= e($input['name']) ?>" minlength="2" maxlength="100" required></div>
                        <div class="form-group"><label class="form-label" for="user_email">Email</label><input class="form-control" id="user_email" name="email" type="email" value="<?= e($input['email']) ?>" maxlength="254" required></div>
                        <div><button class="btn-brand" type="submit">Lưu thông tin</button></div>
                    </form>
                </section>
            <?php endif; ?>
            <section class="admin-panel orders-panel users-panel" id="users">
                <div class="panel-head"><div><p class="eyebrow">THÀNH VIÊN KHU VƯỜN</p><h2><?= (int) $result['total'] ?> tài khoản</h2></div></div>
                <form class="filter-panel" method="get" action="admin-users.php">
                    <label>Tìm người dùng<input type="search" name="search" value="<?= e($search) ?>" maxlength="200" placeholder="Họ tên hoặc email"></label>
                    <label>Trạng thái<select name="status"><option value="">Tất cả</option><option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Đang hoạt động</option><option value="blocked"<?= $status === 'blocked' ? ' selected' : '' ?>>Đã khóa</option></select></label>
                    <button type="submit">Tìm kiếm</button>
                    <?php if ($search !== '' || $status !== ''): ?><a href="admin-users.php">Xóa bộ lọc</a><?php endif; ?>
                </form>
                <div class="admin-table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Họ tên / Email</th><th>Vai trò</th><th>Trạng thái</th><th>Yêu thích</th><th>Ngày tham gia</th><th>Thao tác</th></tr></thead>
                        <tbody>
                            <?php foreach ($result['users'] as $member): ?>
                                <tr>
                                    <td>#<?= (int) $member['id'] ?></td>
                                    <td><strong><?= e($member['name']) ?></strong><small class="user-email"><?= e($member['email']) ?></small></td>
                                    <td><span class="role-badge <?= $member['role'] === 'admin' ? 'role-admin' : 'role-member' ?>"><?= $member['role'] === 'admin' ? 'Quản trị viên' : 'Thành viên' ?></span></td>
                                    <td><span class="user-status status-<?= e($member['status']) ?>"><?= $member['status'] === 'active' ? 'Đang hoạt động' : 'Đã khóa' ?></span></td>
                                    <td><?= (int) $member['favorite_count'] ?></td>
                                    <td><?= e(date('d/m/Y', strtotime($member['created_at']))) ?></td>
                                    <td><div class="user-actions">
                                        <a href="admin-users.php?edit=<?= (int) $member['id'] ?>#edit-user">Xem / sửa</a>
                                        <?php if ((int) $member['id'] !== (int) $admin['id']): ?>
                                            <form method="post" action="actions/user-action.php">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                                                <input type="hidden" name="operation" value="<?= $member['role'] === 'admin' ? 'demote' : 'promote' ?>">
                                                <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
                                                <button class="role-button" type="submit"><?= $member['role'] === 'admin' ? 'Hạ quyền' : 'Nâng quyền' ?></button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($member['role'] !== 'admin'): ?>
                                            <form method="post" action="actions/user-action.php">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                                                <input type="hidden" name="operation" value="<?= $member['status'] === 'active' ? 'block' : 'unblock' ?>">
                                                <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
                                                <button class="<?= $member['status'] === 'active' ? 'block-button' : 'unblock-button' ?>" type="submit"><?= $member['status'] === 'active' ? 'Khóa' : 'Mở khóa' ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($result['users'] === []): ?><tr><td colspan="7">Không tìm thấy tài khoản phù hợp.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="pagination" aria-label="Phân trang người dùng">
                    <span>Trang <?= (int) $result['page'] ?> / <?= (int) $result['pages'] ?></span>
                    <?php if ($result['page'] > 1): ?><a href="admin-users.php?<?= e(http_build_query(['search' => $search, 'status' => $status, 'page' => $result['page'] - 1])) ?>"> Trước</a><?php endif; ?>
                    <?php if ($result['page'] < $result['pages']): ?><a href="admin-users.php?<?= e(http_build_query(['search' => $search, 'status' => $status, 'page' => $result['page'] + 1])) ?>">Tiếp</a><?php endif; ?>
                </nav>
            </section>
<?php require __DIR__ . '/includes/admin-end.php'; ?>
