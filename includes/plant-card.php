<?php
$favorite = is_favorite((int) $plant['id']);
$returnTo = $returnTo ?? current_request_url();
$detailUrl = 'plant-detail.php?' . http_build_query(['id' => (int) $plant['id'], 'return_to' => safe_return_url($returnTo)]);
?>
<article class="plant-card">
    <a class="plant-card-image" href="<?= e($detailUrl) ?>">
        <img src="<?= e(image_url($plant['image'])) ?>" alt="<?= e($plant['name']) ?>" loading="lazy" decoding="async" width="900" height="1200">
    </a>
    <form class="favorite-form" data-reaction="favorite" data-plant-name="<?= e($plant['name']) ?>" method="post" action="actions/favorite-action.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="plant_id" value="<?= (int) $plant['id'] ?>">
        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
        <button class="favorite-button heart-button<?= $favorite ? ' is-favorite' : '' ?>" type="submit" aria-pressed="<?= $favorite ? 'true' : 'false' ?>" aria-label="<?= $favorite ? 'Xóa' : 'Thêm' ?> <?= e($plant['name']) ?> <?= $favorite ? 'khỏi' : 'vào' ?> mục yêu thích">
            <?php require __DIR__ . '/heart-icon.php'; ?>
        </button>
    </form>
    <div class="plant-card-body">
        <p class="plant-category"><?= e($plant['category']) ?></p>
        <h3><a href="<?= e($detailUrl) ?>"><?= e($plant['name']) ?></a></h3>
        <p><?= e($plant['description']) ?></p>
        <a class="plant-card-link" href="<?= e($detailUrl) ?>">Xem cách chăm</a>
    </div>
</article>

