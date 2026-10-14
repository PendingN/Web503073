<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_admin();
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
$adminActive = 'plants';
$adminHeading = 'Quản lý cây';
$pageTitle = 'Quản lý cây';
require __DIR__ . '/includes/admin-start.php';
?>
            <?php if ($plantForm !== null): ?>
                <section class="admin-panel account-panel" id="plant-form">
                    <div class="panel-head"><div><p class="eyebrow"><?= (int) $plantForm['id'] === 0 ? 'THÊM CÂY MỚI' : 'CÂY #' . (int) $plantForm['id'] ?></p><h2><?= (int) $plantForm['id'] === 0 ? 'Thêm cây vào bộ sưu tập' : 'Sửa thông tin cây' ?></h2></div><a href="admin-plants.php">Đóng ×</a></div>
                    <?php if ($plantErrors !== []): ?><div class="login-error" role="alert"><ul><?php foreach ($plantErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                    <form class="account-form" method="post" action="actions/plant-action.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="operation" value="<?= (int) $plantForm['id'] === 0 ? 'create' : 'update' ?>">
                        <?php if ((int) $plantForm['id'] !== 0): ?><input type="hidden" name="plant_id" value="<?= (int) $plantForm['id'] ?>"><?php endif; ?>
                        <div class="form-group"><label class="form-label" for="plant_name">Tên cây</label><input class="form-control" id="plant_name" name="name" value="<?= e($plantForm['name']) ?>" minlength="2" maxlength="150" required></div>
                        <div class="form-group"><label class="form-label" for="plant_category">Nhóm cây</label><input class="form-control" id="plant_category" name="category" list="plant_category_list" value="<?= e($plantForm['category']) ?>" minlength="2" maxlength="100" required><datalist id="plant_category_list"><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"><?php endforeach; ?></datalist></div>
                        <div class="form-group"><label class="form-label" for="plant_description">Mô tả</label><textarea class="form-control" id="plant_description" name="description" rows="4" minlength="10" maxlength="2000" required><?= e($plantForm['description']) ?></textarea></div>
                        <div class="form-group"><label class="form-label" for="plant_care">Hướng dẫn chăm sóc</label><textarea class="form-control" id="plant_care" name="care" rows="4" minlength="10" maxlength="2000" required><?= e($plantForm['care']) ?></textarea></div>
                        <div class="form-group"><label class="form-label" for="plant_image">Ảnh cây (JPG, PNG hoặc WebP, tối đa 2 MB)<?= (int) $plantForm['id'] === 0 ? '' : ' — để trống nếu giữ ảnh hiện tại' ?></label><input class="form-control" id="plant_image" name="image" type="file" accept="image/jpeg,image/png,image/webp"<?= (int) $plantForm['id'] === 0 ? ' required' : '' ?>></div>
                        <div><button class="btn-brand" type="submit"><?= (int) $plantForm['id'] === 0 ? 'Thêm cây' : 'Lưu thay đổi' ?></button></div>
                    </form>
                </section>
            <?php endif; ?>
            <section class="admin-panel orders-panel" id="plants">
                <div class="panel-head"><div><p class="eyebrow">BỘ SƯU TẬP</p><h2><?= count($plants) ?> cây<?= $plantSearch !== '' ? ' phù hợp' : '' ?></h2></div><a class="btn-brand" href="admin-plants.php?plant=new#plant-form">+ Thêm cây</a></div>
                <form class="filter-panel" method="get" action="admin-plants.php">
                    <label>Tìm cây<input type="search" name="pq" value="<?= e($plantSearch) ?>" maxlength="100" placeholder="Tên hoặc nhóm cây"></label>
                    <button type="submit">Tìm kiếm</button>
                    <?php if ($plantSearch !== ''): ?><a href="admin-plants.php">Xóa bộ lọc</a><?php endif; ?>
                </form>
                <div class="admin-table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Ảnh</th><th>Tên cây</th><th>Nhóm</th><th>Thao tác</th></tr></thead>
                        <tbody>
                            <?php foreach ($plants as $plant): ?>
                                <tr>
                                    <td>#<?= (int) $plant['id'] ?></td>
                                    <td><img class="plant-thumb" src="<?= e(image_url($plant['image'])) ?>" alt="" width="56" height="56" loading="lazy"></td>
                                    <td><strong><?= e($plant['name']) ?></strong></td>
                                    <td><span class="category-badge"><?= e($plant['category']) ?></span></td>
                                    <td><div class="user-actions">
                                        <a href="admin-plants.php?plant=<?= (int) $plant['id'] ?>#plant-form">Sửa</a>
                                        <form method="post" action="actions/plant-action.php" onsubmit="return confirm('Xóa cây này khỏi bộ sưu tập? Thao tác không hoàn tác.');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="plant_id" value="<?= (int) $plant['id'] ?>">
                                            <input type="hidden" name="operation" value="delete">
                                            <button class="block-button" type="submit">Xóa</button>
                                        </form>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($plants === []): ?><tr><td colspan="5">Không tìm thấy cây phù hợp.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
<?php require __DIR__ . '/includes/admin-end.php'; ?>
