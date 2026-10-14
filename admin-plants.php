<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$admin = require_admin('admin-plants.php');
$plantSearch = mb_substr(trim(request_string($_GET, 'pq')), 0, 100, 'UTF-8');
$plants = list_admin_plants($plantSearch);
$categories = plant_categories();
$plantParam = request_string($_GET, 'plant');
$plantForm = null;
if ($plantParam === 'new') {
    $plantForm = ['id' => 0, 'name' => '', 'category' => '', 'description' => '', 'care' => '', 'image' => null];
} elseif ($plantParam !== '') {
    $plantId = filter_var($plantParam, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $plantForm = $plantId === false ? null : find_plant((int) $plantId);
    if ($plantForm === null) {
        abort_request(404, 'Không tìm thấy cây này.');
    }
}
$plantErrors = $_SESSION['plant_errors'] ?? [];
$plantInput = $_SESSION['plant_input'] ?? null;
unset($_SESSION['plant_errors'], $_SESSION['plant_input']);
if ($plantForm !== null) {
    if (($plantInput['id'] ?? null) === (int) $plantForm['id']) {
        $plantForm = array_merge($plantForm, $plantInput);
    } else {
        $plantErrors = [];
    }
}
$pageTitle = 'Quản lý cây';
$bodyClass = 'admin-page';
$adminActive = 'plants';
$flashMessage = take_flash();
require __DIR__ . '/includes/header.php';
?>
<div class="admin-shell min-h-screen">
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>
    <div id="admin-content" class="min-w-0 lg:ml-[270px]">
        <?php require __DIR__ . '/includes/admin-topbar.php'; ?>
        <main id="admin-main" class="mx-auto w-full max-w-[1480px] space-y-6 px-4 py-6 sm:px-6 xl:px-8">
            <?php if ($flashMessage !== null): ?><div class="status-note" role="status" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="text-xl font-semibold">Bộ sưu tập cây</h2><p class="mt-2 text-xs text-muted">Thêm, sửa và xóa các cây hiển thị trên trang bộ sưu tập.</p></div><a class="admin-button" href="admin-plants.php?plant=new#plant-form">+ Thêm cây</a></div>

            <?php if ($plantForm !== null): ?>
                <section class="admin-card scroll-mt-28 p-5 sm:p-6" id="plant-form" aria-labelledby="plant-form-title">
                    <div class="mb-6 flex items-center justify-between gap-3"><div><p class="mb-2 text-[10px] font-semibold tracking-wider text-primary-dark"><?= (int) $plantForm['id'] === 0 ? 'THÊM CÂY MỚI' : 'CÂY #' . (int) $plantForm['id'] ?></p><h2 class="text-lg font-semibold" id="plant-form-title"><?= (int) $plantForm['id'] === 0 ? 'Thêm cây vào bộ sưu tập' : 'Sửa thông tin cây' ?></h2></div><a class="admin-button-secondary" href="admin-plants.php"><?php admin_icon('close', 'size-4'); ?>Đóng</a></div>
                    <?php if ($plantErrors !== []): ?><div class="login-error mb-5" role="alert"><ul><?php foreach ($plantErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                    <form class="grid gap-5 md:grid-cols-2" method="post" action="actions/plant-action.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="operation" value="<?= (int) $plantForm['id'] === 0 ? 'create' : 'update' ?>">
                        <?php if ((int) $plantForm['id'] !== 0): ?><input type="hidden" name="plant_id" value="<?= (int) $plantForm['id'] ?>"><?php endif; ?>
                        <div><label class="admin-field-label" for="plant_name">Tên cây</label><input class="admin-input" id="plant_name" name="name" value="<?= e($plantForm['name']) ?>" minlength="2" maxlength="150" required></div>
                        <div><label class="admin-field-label" for="plant_category">Nhóm cây</label><input class="admin-input" id="plant_category" name="category" list="plant_category_list" value="<?= e($plantForm['category']) ?>" minlength="2" maxlength="100" required><datalist id="plant_category_list"><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"><?php endforeach; ?></datalist></div>
                        <div class="md:col-span-2"><label class="admin-field-label" for="plant_description">Mô tả</label><textarea class="admin-input min-h-28 leading-relaxed" id="plant_description" name="description" rows="4" minlength="10" maxlength="2000" required><?= e($plantForm['description']) ?></textarea></div>
                        <div class="md:col-span-2"><label class="admin-field-label" for="plant_care">Hướng dẫn chăm sóc</label><textarea class="admin-input min-h-28 leading-relaxed" id="plant_care" name="care" rows="4" minlength="10" maxlength="2000" required><?= e($plantForm['care']) ?></textarea></div>
                        <div class="md:col-span-2"><label class="admin-field-label" for="plant_image">Ảnh cây (JPG, PNG hoặc WebP, tối đa 2 MB)<?= (int) $plantForm['id'] === 0 ? '' : ' — để trống nếu giữ ảnh hiện tại' ?></label><input class="admin-input" id="plant_image" name="image" type="file" accept="image/jpeg,image/png,image/webp"<?= (int) $plantForm['id'] === 0 ? ' required' : '' ?>></div>
                        <div class="md:col-span-2"><button class="admin-button" type="submit"><?= (int) $plantForm['id'] === 0 ? 'Thêm cây' : 'Lưu thay đổi' ?></button></div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="admin-card min-w-0 overflow-hidden" id="plants" aria-labelledby="plants-title">
                <div class="border-b border-border p-5 sm:p-6">
                    <div class="mb-5 flex items-center justify-between gap-3"><h2 class="text-base font-semibold" id="plants-title">Danh sách cây</h2><span class="rounded-full bg-primary/10 px-3 py-1.5 text-xs text-primary-dark"><?= count($plants) ?> cây<?= $plantSearch !== '' ? ' phù hợp' : '' ?></span></div>
                    <form class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_auto]" method="get" action="admin-plants.php">
                        <div><label class="admin-field-label" for="plant-search">Tìm cây</label><input class="admin-input" id="plant-search" type="search" name="pq" value="<?= e($plantSearch) ?>" maxlength="100" placeholder="Tên hoặc nhóm cây"></div>
                        <div class="flex flex-wrap gap-2"><button class="admin-button min-h-11" type="submit">Tìm kiếm</button><?php if ($plantSearch !== ''): ?><a class="admin-button-secondary min-h-11" href="admin-plants.php">Xóa bộ lọc</a><?php endif; ?></div>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="admin-table min-w-[700px]">
                        <thead><tr><th>ID</th><th>Ảnh</th><th>Tên cây</th><th>Nhóm</th><th>Thao tác</th></tr></thead>
                        <tbody>
                            <?php foreach ($plants as $plant): ?>
                                <tr>
                                    <td class="text-xs text-muted">#<?= (int) $plant['id'] ?></td>
                                    <td><img class="size-14 rounded-lg object-cover ring-1 ring-border" src="<?= e(image_url($plant['image'])) ?>" alt="" width="56" height="56" loading="lazy"></td>
                                    <td class="text-xs font-semibold"><?= e($plant['name']) ?></td>
                                    <td><span class="rounded-md bg-soft px-2 py-1 text-[11px] text-muted"><?= e($plant['category']) ?></span></td>
                                    <td><div class="flex items-center gap-4 whitespace-nowrap">
                                        <a class="admin-panel-link" href="admin-plants.php?plant=<?= (int) $plant['id'] ?>#plant-form">Sửa</a>
                                        <form method="post" action="actions/plant-action.php" onsubmit="return confirm('Xóa cây này khỏi bộ sưu tập? Thao tác không hoàn tác.');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="plant_id" value="<?= (int) $plant['id'] ?>">
                                            <input type="hidden" name="operation" value="delete">
                                            <button class="rounded-md bg-red-50 px-2 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100" type="submit">Xóa</button>
                                        </form>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($plants === []): ?><tr><td class="py-10 text-center text-muted" colspan="5">Không tìm thấy cây phù hợp.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
