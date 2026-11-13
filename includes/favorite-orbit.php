<?php
// Vòng xoay cây yêu thích. Cần $favoritePlants.
// Mỗi cây là một nút .so-shoe; js/shoe-orbit.js đọc data-* và JSON bên trong.
$orbitPlants = $favoritePlants ?? [];
?>
<section class="so-sec favorite-orbit" data-so data-favorite-orbit
    data-cx="84" data-cy="138" data-r="97" data-hero="121" data-step="21" data-fade="2.5"
    data-cxm="113" data-cym="88" data-rm="95" data-herom="128" data-stepm="24" data-fadem="1.5"
    data-tilt="55" data-mirror="false" data-loop="true" data-start="1"
    data-autoplay="false" data-float="true" data-drop="true"
    data-smooth="70" data-morph="850"
    data-hdr="false" data-vh-d="78" data-vh-m="66"
    <?= $orbitPlants === [] ? 'hidden' : '' ?>
    aria-label="Vòng xoay cây yêu thích">
    <div class="so-ov" aria-hidden="true"></div>
    <div class="so-stage" data-so-stage>
        <div class="so-rings" data-so-rings aria-hidden="true">
            <div class="so-ring" data-so-ring></div>
            <div class="so-ring so-ring-in" data-so-ring2></div>
        </div>
        <?php foreach ($orbitPlants as $orbitIndex => $orbitPlant): ?>
            <?php
            $orbitId = (int) $orbitPlant['id'];
            $orbitDetailUrl = 'plant-detail.php?' . http_build_query(['id' => $orbitId, 'return_to' => 'favorites.php']);
            $orbitImage = image_url($orbitPlant['image']);
            $careSummary = preg_replace('/\s*\R\s*/u', "\n", trim((string) $orbitPlant['care']));
            $orbitJson = [
                'url' => $orbitDetailUrl,
                'desc' => trim((string) $orbitPlant['description']) . "\n\nCách chăm:\n" . $careSummary,
                'images' => [$orbitImage],
            ];
            ?>
            <button type="button" class="so-shoe" data-so-shoe data-plant-id="<?= $orbitId ?>" data-block-id="plant_<?= $orbitId ?>"
                data-name="<?= e($orbitPlant['name']) ?>" data-tag="<?= e($orbitPlant['category']) ?>"
                data-url="<?= e($orbitDetailUrl) ?>" aria-label="<?= e($orbitPlant['name']) ?>" tabindex="<?= $orbitIndex === 0 ? '0' : '-1' ?>">
                <img class="so-img" src="<?= e($orbitImage) ?>" alt="<?= e($orbitPlant['name']) ?>" width="900" height="1200" loading="<?= $orbitIndex < 3 ? 'eager' : 'lazy' ?>" decoding="async" draggable="false">
                <script type="application/json" data-so-json><?= json_encode($orbitJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
            </button>
        <?php endforeach; ?>
    </div>
    <div class="so-ui">
        <div class="so-info" aria-live="polite">
            <div class="so-meta"><span data-so-idx></span><span class="so-meta-line" aria-hidden="true"></span><span data-so-tag></span></div>
            <h2 class="so-name" data-so-name></h2>
            <a class="so-view" data-so-view href="#">Xem chi tiết<svg viewBox="0 0 16 10" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M1 5h13M10 1l4 4-4 4"/></svg></a>
        </div>
    </div>
    <div class="so-modal favorite-orbit-modal" data-so-modal role="dialog" aria-modal="true" aria-hidden="true" aria-label="Chi tiết cây">
        <div class="so-m-bg" data-so-mbg></div>
        <button class="so-m-close" type="button" data-so-mclose><span>Đóng</span><span class="so-x" aria-hidden="true"></span></button>
        <div class="so-m-inner">
            <div class="so-m-media">
                <div class="so-m-stage" data-so-mstage></div>
                <div class="so-m-under">
                    <div class="so-m-thumbs" data-so-mthumbs></div>
                    <div class="so-m-nav">
                        <button type="button" class="so-m-arrow" data-so-mprev aria-label="Cây trước"><svg viewBox="0 0 20 12" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M19 6H2M7 1L2 6l5 5"/></svg></button>
                        <span class="so-m-count" data-so-mcount></span>
                        <button type="button" class="so-m-arrow" data-so-mnext aria-label="Cây sau"><svg viewBox="0 0 20 12" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M1 6h17M13 1l5 5-5 5"/></svg></button>
                    </div>
                </div>
            </div>
            <div class="so-m-info">
                <div class="so-m-meta" data-so-mmeta></div>
                <h2 class="so-m-name" data-so-mname></h2>
                <div class="so-m-desc" data-so-mdesc></div>
                <a class="so-m-link" data-so-mlink href="#">Xem cách chăm<svg viewBox="0 0 16 10" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M1 5h13M10 1l4 4-4 4"/></svg></a>
            </div>
        </div>
    </div>
</section>
