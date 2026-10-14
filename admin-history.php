<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$admin = require_admin('admin-history.php');
if (!activity_ready()) {
    abort_request(503, 'Lịch sử chưa được bật. Cần chạy scripts/setup-database.php bằng tài khoản MySQL setup để tạo bảng lịch sử.');
}
$period = request_string($_GET, 'period') === 'all' ? 'all' : '3days';
$userId = 0;
if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
    $userId = filter_var($_GET['user_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($userId === false) {
        abort_request(400, 'ID tài khoản không hợp lệ.');
    }
}
$search = mb_substr(trim(request_string($_GET, 'search')), 0, 200, 'UTF-8');
$labels = activity_labels();
$event = request_string($_GET, 'event');
$event = isset($labels[$event]) ? $event : '';
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$result = list_user_activity($period, (int) $userId, $search, $event, $page);
$query = ['period' => $period, 'user_id' => $userId ?: '', 'search' => $search, 'event' => $event];
$pageTitle = 'Lịch sử người dùng';
$bodyClass = 'admin-page';
$adminActive = 'history';
require __DIR__ . '/includes/header.php';
?>
<div class="admin-shell min-h-screen">
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>
    <div id="admin-content" class="min-w-0 lg:ml-[270px]">
        <?php require __DIR__ . '/includes/admin-topbar.php'; ?>
        <main id="admin-main" class="mx-auto w-full max-w-[1480px] space-y-6 px-4 py-6 sm:px-6 xl:px-8">
            <div><h2 class="text-xl font-semibold"><?= $userId > 0 ? 'Hoạt động liên quan tài khoản #' . (int) $userId : 'Hoạt động của toàn bộ người dùng' ?></h2><p class="mt-2 text-xs leading-relaxed text-muted">Ghi nhận đăng nhập, đăng xuất, xem trang và các thao tác thành công. Mốc tạo tài khoản cũ được lấy từ ngày tạo đã lưu; các thao tác khác chỉ ghi từ lúc bật chức năng.</p></div>
            <section class="admin-card min-w-0 overflow-hidden" aria-labelledby="history-title">
                <div class="border-b border-border p-5 sm:p-6">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><h2 class="text-base font-semibold" id="history-title"><?= $period === 'all' ? 'Toàn bộ lịch sử đã ghi' : '3 ngày gần nhất · 72 giờ' ?></h2><span class="rounded-full bg-primary/10 px-3 py-1.5 text-xs text-primary-dark"><?= (int) $result['total'] ?> hoạt động</span></div>
                    <form class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-5" method="get" action="admin-history.php">
                        <div><label class="admin-field-label" for="history-period">Thời gian</label><select class="admin-input" id="history-period" name="period"><option value="3days"<?= $period === '3days' ? ' selected' : '' ?>>3 ngày gần nhất</option><option value="all"<?= $period === 'all' ? ' selected' : '' ?>>Toàn bộ</option></select></div>
                        <div><label class="admin-field-label" for="history-user">ID tài khoản</label><input class="admin-input" id="history-user" type="number" name="user_id" min="1" value="<?= $userId > 0 ? (int) $userId : '' ?>" placeholder="Tất cả tài khoản"></div>
                        <div><label class="admin-field-label" for="history-event">Thao tác</label><select class="admin-input" id="history-event" name="event"><option value="">Tất cả thao tác</option><?php foreach ($labels as $key => $label): ?><option value="<?= e($key) ?>"<?= $event === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                        <div><label class="admin-field-label" for="history-search">Tìm kiếm</label><input class="admin-input" id="history-search" type="search" name="search" maxlength="200" value="<?= e($search) ?>" placeholder="Tên hoặc chi tiết"></div>
                        <div class="flex flex-wrap gap-2"><button class="admin-button min-h-11" type="submit">Lọc</button><a class="admin-button-secondary min-h-11" href="admin-history.php">Đặt lại</a></div>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="admin-table min-w-[900px]">
                        <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Thao tác</th><th>Tài khoản liên quan</th><th>Chi tiết</th></tr></thead>
                        <tbody>
                            <?php foreach ($result['activities'] as $item): ?>
                                <tr><td class="text-xs whitespace-nowrap text-muted"><time datetime="<?= e(str_replace(' ', 'T', $item['created_at'])) ?>"><?= e(date('d/m/Y H:i:s', strtotime($item['created_at']))) ?></time></td><td class="max-w-52 text-xs wrap-anywhere"><strong class="font-medium"><?= e($item['actor_name']) ?></strong><?php if ($item['actor_id'] !== null): ?><a class="mt-1 block text-[10px] text-primary-dark hover:underline" href="admin-history.php?<?= e(http_build_query(['period' => $period, 'user_id' => $item['actor_id']])) ?>">#<?= (int) $item['actor_id'] ?></a><?php endif; ?></td><td class="text-xs whitespace-nowrap"><?= e($labels[$item['event']] ?? $item['event']) ?></td><td class="max-w-52 text-xs wrap-anywhere"><?= e($item['subject_name'] ?? '—') ?><?php if ($item['subject_id'] !== null): ?><a class="mt-1 block text-[10px] text-primary-dark hover:underline" href="admin-history.php?<?= e(http_build_query(['period' => $period, 'user_id' => $item['subject_id']])) ?>">#<?= (int) $item['subject_id'] ?></a><?php endif; ?></td><td class="max-w-96 text-xs leading-relaxed wrap-anywhere text-muted"><?= e($item['description']) ?></td></tr>
                            <?php endforeach; ?>
                            <?php if ($result['activities'] === []): ?><tr><td class="py-10 text-center text-muted" colspan="5">Chưa có hoạt động phù hợp trong khoảng thời gian đã chọn.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-border p-5 text-xs" aria-label="Phân trang lịch sử"><span class="text-muted">Trang <?= (int) $result['page'] ?> / <?= (int) $result['pages'] ?></span><div class="flex gap-2"><?php if ($result['page'] > 1): ?><a class="admin-button-secondary" href="admin-history.php?<?= e(http_build_query($query + ['page' => $result['page'] - 1])) ?>">Trước</a><?php endif; ?><?php if ($result['page'] < $result['pages']): ?><a class="admin-button-secondary" href="admin-history.php?<?= e(http_build_query($query + ['page' => $result['page'] + 1])) ?>">Tiếp</a><?php endif; ?></div></nav>
            </section>
        </main>
    </div>
</div>
<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
