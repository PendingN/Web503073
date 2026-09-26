<?php
$favorite = is_favorite((int) $plant['id']);
$returnTo = $returnTo ?? current_request_url();
?>
<article class="plant-card">
    <a class="plant-card-image" href="plant-detail.php?id=<?= (int) $plant['id'] ?>">
        <img src="images/<?= e($plant['image']) ?>" alt="<?= e($plant['name']) ?>" loading="lazy" width="900" height="1200">
    </a>
    <form class="favorite-form" method="post" action="actions/favorite-action.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="plant_id" value="<?= (int) $plant['id'] ?>">
        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
        <button class="favorite-button<?= $favorite ? ' is-favorite' : '' ?>" type="submit" aria-pressed="<?= $favorite ? 'true' : 'false' ?>" aria-label="<?= $favorite ? 'Xóa' : 'Thêm' ?> <?= e($plant['name']) ?> <?= $favorite ? 'khỏi' : 'vào' ?> mục yêu thích">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="<?= $favorite ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"/></svg>
        </button>
    </form>
    <div class="plant-card-body">
        <p class="plant-category"><?= e($plant['category']) ?></p>
        <h3><a href="plant-detail.php?id=<?= (int) $plant['id'] ?>"><?= e($plant['name']) ?></a></h3>
        <p><?= e($plant['description']) ?></p>
        <a class="plant-card-link" href="plant-detail.php?id=<?= (int) $plant['id'] ?>">Xem cách chăm →</a>
    </div>
</article>

