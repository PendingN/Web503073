<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/blog-images.php';
$catalog = plant_catalog_statistics();
$collectionPlants = list_plants();
if ($collectionPlants === []) {
    require __DIR__ . '/data/plants.php';
    $collectionPlants = $plants ?? [];
}

$indoorPicks = array_values(array_filter($collectionPlants, static fn (array $plant): bool => ($plant['category'] ?? '') === 'Cây trong nhà'));
$deskPicks = array_values(array_filter($collectionPlants, static fn (array $plant): bool => ($plant['category'] ?? '') !== 'Cây trong nhà'));
$interleavedPlants = [];
$maxGroupCount = max(count($indoorPicks), count($deskPicks));
for ($i = 0; $i < $maxGroupCount; $i++) {
    if (isset($deskPicks[$i])) {
        $interleavedPlants[] = $deskPicks[$i];
    }
    if (isset($indoorPicks[$i])) {
        $interleavedPlants[] = $indoorPicks[$i];
    }
}
if ($interleavedPlants === []) {
    $interleavedPlants = $collectionPlants;
}

$heroWallColumns = [];
$wallColumnCount = 7;
$wallRowsPerColumn = 5;
$plantPoolCount = count($interleavedPlants);
if ($plantPoolCount > 0) {
    $cursor = 0;
    for ($col = 0; $col < $wallColumnCount; $col++) {
        $columnItems = [];
        for ($row = 0; $row < $wallRowsPerColumn; $row++) {
            $columnItems[] = $interleavedPlants[($cursor + ($col * 3)) % $plantPoolCount];
            $cursor++;
        }
        $heroWallColumns[] = $columnItems;
    }
}

$latestPosts = latest_blog_posts(6);
$editorialPosts = array_values(array_filter($latestPosts, static function (array $post): bool {
    return strncmp((string) ($post['slug'] ?? ''), 'bai-viet-', 9) !== 0;
}));
$blogShowcasePosts = array_slice($editorialPosts !== [] ? $editorialPosts : $latestPosts, 0, 4);
if (count($blogShowcasePosts) < 4 && count($latestPosts) > count($blogShowcasePosts)) {
    $seenSlugs = array_column($blogShowcasePosts, 'slug');
    foreach ($latestPosts as $candidatePost) {
        $candidateSlug = (string) ($candidatePost['slug'] ?? '');
        if (!in_array($candidateSlug, $seenSlugs, true)) {
            $blogShowcasePosts[] = $candidatePost;
            $seenSlugs[] = $candidateSlug;
        }
        if (count($blogShowcasePosts) >= 4) {
            break;
        }
    }
}
$marqueePlants = $interleavedPlants !== [] ? $interleavedPlants : $collectionPlants;

// Core site features highlighted in the 4-card section right below the hero.
$lalalaOfferings = [
    [
        'icon' => 'pot-hand',
        'title' => 'Bộ sưu tập cây xanh',
        'description' => 'Tra cứu ' . (int) $catalog['total'] . ' loài cây trong nhà và để bàn kèm ghi chú ánh sáng, lượng nước và đất trồng chi tiết.',
        'href' => 'collection.php',
        'aria' => 'Mở bộ sưu tập cây xanh',
    ],
    [
        'icon' => 'hands-sprout',
        'title' => 'Gợi ý chọn cây',
        'description' => 'Chọn giống cây hợp ánh sáng, góc nhà hoặc khám phá gợi ý ngũ hành, hoàng đạo và lắc quẻ mỗi ngày.',
        'href' => 'plant-advisor.php',
        'aria' => 'Mở công cụ gợi ý chọn cây',
    ],
    [
        'icon' => 'leaf-pair',
        'title' => 'Blog cộng đồng',
        'description' => 'Đọc ghi chép chăm cây thực tế từ mọi người, lưu lại bài viết hữu ích và chia sẻ câu chuyện xanh của bạn.',
        'href' => 'blog.php',
        'aria' => 'Mở blog cộng đồng yêu cây',
    ],
    [
        'icon' => 'two-pots',
        'title' => 'Góc vườn yêu thích',
        'description' => 'Đánh dấu những loài cây bạn muốn trồng, theo dõi danh sách riêng và quản lý khu vườn cá nhân gọn gàng.',
        'href' => 'favorites.php',
        'aria' => 'Xem danh sách cây yêu thích',
    ],
];

$collectionDirectory = [
    [
        'name' => 'Cây trong nhà',
        'label' => 'Trong nhà',
    ],
    [
        'name' => 'Cây để bàn',
        'label' => 'Để bàn',
    ],
];

$pageTitle = '';
$pageDescription = 'Là Lá La — Thư viện cây xanh trong nhà và để bàn. Khám phá bộ sưu tập cây cảnh, ghi chú ánh sáng, cách tưới và cảm hứng sống xanh.';
$bodyClass = 'landing-page';
$activePage = 'home';
$showSiteFooter = false;
require __DIR__ . '/includes/header.php';
?>

<a class="landing-skip-link" href="#main-content">Đến nội dung chính</a>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<main id="main-content">
    <section class="lalala-hero" aria-labelledby="lalala-hero-title" data-nav-section="light">
        <div class="lalala-hero-inner landing-content">
            <div class="lalala-hero-copy">
                <h1 id="lalala-hero-title">Là Lá La,<br>chọn xanh cho nhà</h1>
                <p class="lalala-hero-desc">
                    Lá Lá La mang một chút xanh dịu dàng vào không gian sống, nơi mỗi chiếc lá, mỗi chồi non đều kể một câu chuyện nhỏ về thiên nhiên, để bạn tìm thấy niềm vui và sự bình yên trong từng khoảnh khắc chăm sóc cây.
                </p>
                <div class="lalala-hero-actions">
                    <a class="lalala-btn lalala-btn--primary" href="collection.php">Khám phá bộ sưu tập</a>
                    <a class="lalala-btn lalala-btn--secondary" href="#lalala-features">
                        <span>Xem giới thiệu</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14m-6-6 6 6 6-6"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="lalala-wall-stage">
            <div class="lalala-wall">
                <?php foreach ($heroWallColumns as $colIndex => $columnPlants):
                    $isBackgroundCol = $colIndex < 3;
                ?>
                    <div class="lalala-wall-col lalala-wall-col--<?= $colIndex + 1 ?>"<?= $isBackgroundCol ? ' aria-hidden="true"' : '' ?>>
                        <?php foreach ($columnPlants as $plant):
                            $plantId = (int) ($plant['id'] ?? 0);
                            $plantName = (string) ($plant['name'] ?? 'Cây xanh');
                            $plantImage = (string) ($plant['image'] ?? 'anh21.jpg');
                        ?>
                            <?php if ($isBackgroundCol || $plantId <= 0): ?>
                                <div class="lalala-card lalala-card--bg">
                                    <img src="<?= e(image_url($plantImage)) ?>" alt="" width="300" height="300" loading="eager">
                                </div>
                            <?php else: ?>
                                <a class="lalala-card" href="plant-detail.php?id=<?= $plantId ?>" aria-label="<?= e('Xem chi tiết ' . $plantName) ?>">
                                    <img src="<?= e(image_url($plantImage)) ?>" alt="<?= e($plantName) ?>" width="300" height="300" loading="eager">
                                    <span class="lalala-card-label"><?= e($plantName) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="lalala-hero-veil" aria-hidden="true"></div>
    </section>

    <section class="lalala-offerings" id="lalala-features" aria-labelledby="lalala-offerings-title" data-nav-section="light">
        <div class="landing-content">
            <header class="lalala-offerings-head">
                <p class="lalala-offerings-kicker">OPEN TO NATURE</p>
                <h2 id="lalala-offerings-title">LÀ LÁ LA có gì?</h2>
            </header>
            <div class="lalala-offerings-grid">
                <?php foreach ($lalalaOfferings as $offering): ?>
                    <a class="lalala-offering-card" href="<?= e($offering['href']) ?>" aria-label="<?= e($offering['aria']) ?>">
                        <span class="lalala-offering-icon" aria-hidden="true">
                            <?php if ($offering['icon'] === 'pot-hand'): ?>
                                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M24 22V12"/><path d="M24 15c0-4.5 3.5-7 7.5-7 0 4.5-3.5 7-7.5 7Z"/><path d="M24 17c0-3.8-3-6-6.5-6 0 3.8 3 6 6.5 6Z"/><path d="M18 22h12l-1.5 8h-9L18 22Z"/><path d="M11 33h8l4 2.5h9a2.5 2.5 0 0 1 0 5H19l-8-3.5v-4Z"/></svg>
                            <?php elseif ($offering['icon'] === 'hands-sprout'): ?>
                                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M24 25V13"/><path d="M24 17c0-4.5 3.8-7 7.5-7 0 4.5-3.5 7-7.5 7Z"/><path d="M24 18c0-4.5-3.8-7-7.5-7 0 4.5 3.5 7 7.5 7Z"/><path d="M10 23v9c0 3 2.5 5 6 6l5 2"/><path d="M15 26v6c0 2 1.8 3.5 4.5 4.5"/><path d="M38 23v9c0 3-2.5 5-6 6l-5 2"/><path d="M33 26v6c0 2-1.8 3.5-4.5 4.5"/></svg>
                            <?php elseif ($offering['icon'] === 'leaf-pair'): ?>
                                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8"><ellipse cx="18.5" cy="21" rx="7.5" ry="11" transform="rotate(-18 18.5 21)"/><path d="M15.5 11.5c2 5.5 2.5 13 6 19"/><ellipse cx="29.5" cy="27" rx="7.5" ry="11" transform="rotate(24 29.5 27)"/><path d="M33 17.5c-2.5 5.5-4 13-7 19"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 26h14l-1.8 11H12.8L11 26Z"/><path d="M18 26V11"/><path d="M18 25l-5-11 4 4"/><path d="M18 25l5-12-4 5"/><path d="M28 30h10l-1.3 7H29.3L28 30Z"/><path d="M33 30v-8"/><path d="M33 25c0-3 2.5-5 5-5 0 3-2.5 5-5 5Z"/><path d="M33 26c0-2.5-2-4.2-4.5-4.2 0 2.5 2 4.2 4.5 4.2Z"/></svg>
                            <?php endif; ?>
                        </span>
                        <h3><?= e($offering['title']) ?></h3>
                        <p><?= e($offering['description']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="lalala-needs" id="plant-needs" aria-labelledby="lalala-needs-title" data-nav-section="light">
        <div class="landing-content">
            <header class="lalala-needs-head">
                <h2 id="lalala-needs-title">Những thứ mà cây cần</h2>
            </header>
        </div>

        <div class="lalala-needs-stage">
            <div class="lalala-needs-grid landing-content">
                <div class="lalala-needs-col lalala-needs-col--left">
                    <article class="lalala-need-card lalala-need-card--dark lalala-need-card--inward">
                        <h3>Ánh sáng</h3>
                        <p>Ánh sáng tán xạ dịu nhẹ giúp lá xanh mướt và quang hợp đều mỗi ngày.</p>
                    </article>
                    <article class="lalala-need-card lalala-need-card--light lalala-need-card--outward">
                        <h3>Nước &amp; Độ ẩm</h3>
                        <p>Tưới khi lớp đất mặt vừa se khô, giữ ẩm thoáng để rễ luôn khỏe.</p>
                    </article>
                </div>

                <div class="lalala-needs-center">
                    <img src="<?= e(image_url('plant-needs-hero.png')) ?>" alt="Chậu cây Monstera xanh tươi tượng trưng cho các yếu tố chăm sóc cây" width="617" height="947">
                </div>

                <div class="lalala-needs-col lalala-needs-col--right">
                    <article class="lalala-need-card lalala-need-card--light lalala-need-card--outward">
                        <h3>Nhiệt độ</h3>
                        <p>Mức nhiệt ổn định 18–28°C, tránh gió lạnh lùa hay nắng trưa gắt.</p>
                    </article>
                    <article class="lalala-need-card lalala-need-card--dark lalala-need-card--inward">
                        <h3>Đất &amp; Dinh dưỡng</h3>
                        <p>Giá thể tơi xốp thoát nước nhanh, bổ sung dinh dưỡng định kỳ vừa đủ.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="lalala-marquee-section" id="plant-carousel" aria-labelledby="lalala-marquee-title" data-nav-section="light">
        <div class="lalala-marquee-head landing-content">
            <div>
                <p class="lalala-section-kicker">BỘ SƯU TẬP HIỆN CÓ</p>
                <h2 id="lalala-marquee-title">Các loài cây trong Là Lá La</h2>
            </div>
            <div class="lalala-marquee-actions">
                <a class="lalala-btn lalala-btn--primary" href="collection.php">
                    <span>Khám phá Bộ sưu tập</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
        </div>

        <div class="lalala-marquee-viewport" data-marquee-viewport>
            <div class="lalala-marquee-track">
                <?php for ($loop = 0; $loop < 2; $loop++):
                    $isClone = $loop === 1;
                ?>
                    <div class="lalala-marquee-group"<?= $isClone ? ' aria-hidden="true"' : '' ?>>
                        <?php foreach ($marqueePlants as $plant):
                            $plantId = (int) ($plant['id'] ?? 0);
                            $plantName = (string) ($plant['name'] ?? 'Cây xanh');
                            $plantImg = (string) ($plant['image'] ?? 'anh21.jpg');
                        ?>
                            <a class="lalala-marquee-card" href="plant-detail.php?id=<?= $plantId ?>"<?= $isClone ? ' tabindex="-1"' : ' aria-label="' . e($plantName) . '"' ?>>
                                <div class="lalala-marquee-media">
                                    <img src="<?= e(image_url($plantImg)) ?>" alt="<?= $isClone ? '' : e($plantName) ?>" width="260" height="300" loading="lazy">
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <section class="lalala-blog-intro" id="blog-showcase" aria-labelledby="lalala-blog-title" data-nav-section="light">
        <div class="landing-content">
            <div class="lalala-blog-head">
                <div>
                    <p class="lalala-section-kicker">CỘNG ĐỒNG LÀ LÁ LA</p>
                    <h2 id="lalala-blog-title">Blog chia sẻ kinh nghiệm</h2>
                </div>
                <div class="lalala-blog-actions">
                    <a class="lalala-btn lalala-btn--secondary" href="blog-editor.php">Viết bài chia sẻ</a>
                    <a class="lalala-btn lalala-btn--primary" href="blog.php">Khám phá Blog</a>
                </div>
            </div>

            <div class="lalala-blog-grid">
                <?php if ($blogShowcasePosts !== []): ?>
                    <?php foreach ($blogShowcasePosts as $post):
                        $cover = blog_cover_url($post);
                        $postTag = trim((string) ($post['tag'] ?? 'Chăm cây'));
                        $postExcerpt = trim((string) ($post['excerpt'] ?? 'Ghi chép chăm cây thực tế và cảm hứng sống xanh từ cộng đồng Là Lá La.'));
                        $postAuthor = trim((string) ($post['author'] ?? 'Là Lá La'));
                    ?>
                    <a class="lalala-blog-card" href="blog-post.php?slug=<?= e(rawurlencode((string) $post['slug'])) ?>">
                        <div class="lalala-blog-card-media">
                            <img src="<?= e(asset_url($cover)) ?>" alt="<?= e((string) $post['title']) ?>" width="420" height="280" loading="lazy">
                        </div>
                        <div class="lalala-blog-card-body">
                            <span class="lalala-blog-tag"><?= e($postTag) ?></span>
                            <h3><?= e((string) $post['title']) ?></h3>
                            <p><?= e(mb_strimwidth($postExcerpt, 0, 108, '…', 'UTF-8')) ?></p>
                        </div>
                        <div class="lalala-blog-card-author">
                            <span class="lalala-blog-avatar" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a4.2 4.2 0 1 0-4.2-4.2A4.2 4.2 0 0 0 12 12Zm0 2.2c-4.1 0-7.5 2.1-7.5 4.8V21h15v-2c0-2.7-3.4-4.8-7.5-4.8Z"/></svg>
                            </span>
                            <span><?= e($postAuthor) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a class="lalala-blog-card" href="blog.php">
                        <div class="lalala-blog-card-media">
                            <img src="<?= e(image_url('anh57.jpg')) ?>" alt="Cây xanh trong nhà" width="420" height="280" loading="lazy">
                        </div>
                        <div class="lalala-blog-card-body">
                            <span class="lalala-blog-tag">Chăm cây</span>
                            <h3>Khám phá các bài viết từ cộng đồng yêu cây</h3>
                            <p>Đọc mẹo hay hằng ngày và chia sẻ câu chuyện xanh của riêng bạn.</p>
                        </div>
                        <div class="lalala-blog-card-author">
                            <span class="lalala-blog-avatar" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a4.2 4.2 0 1 0-4.2-4.2A4.2 4.2 0 0 0 12 12Zm0 2.2c-4.1 0-7.5 2.1-7.5 4.8V21h15v-2c0-2.7-3.4-4.8-7.5-4.8Z"/></svg>
                            </span>
                            <span>Là Lá La</span>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<footer class="floema-footer" data-nav-section="dark">
    <div class="floema-footer-grid">
        <div class="floema-footer-mark">
            <a class="floema-footer-brand" href="index.php" aria-label="Là Lá La — Về đầu trang">
                <svg class="floema-footer-emblem" viewBox="0 0 120 150" fill="none" aria-hidden="true">
                    <path d="M22 136C22 78 90 68 96 12c34 58 6 110-44 96M22 136C8 98 8 66 32 46c4 36 38 40 24 68M22 136c19-38 50-58 62-78"/>
                </svg>
                <span>LÀ LÁ LA<sup>®</sup></span>
            </a>
        </div>

        <div class="floema-footer-content">
            <div class="floema-footer-band floema-footer-band--top">
                <svg class="footer-line-art footer-line-art--shelf" viewBox="0 0 260 96" fill="none" aria-hidden="true">
                    <path d="M18 95h224"/>
                    <path d="M48 95V62h44v33M56 62c0-18-16-26-26-34 15-4 32 6 38 22M70 62c2-24 16-38 30-44 4 16-6 34-22 44"/>
                    <path d="M116 95V52h36v43M126 52l-8-38c10 4 16 18 16 38M136 52l6-44c8 10 8 28 2 44M144 52l14-28c2 14-4 24-12 28"/>
                    <path d="M180 95V68h34v27M197 68V40M197 54c-12 0-20-8-22-18 12-2 20 6 22 18M197 48c12-2 20-10 20-20-12 0-18 8-20 20"/>
                </svg>

                <div class="footer-columns">
                    <div>
                        <h3>Khám phá</h3>
                        <ul>
                            <li><a href="index.php">Trang chủ</a></li>
                            <li><a href="collection.php">Bộ sưu tập (<?= (int) $catalog['total'] ?>)</a></li>
                            <li><a href="plant-advisor.php">Gợi ý chọn cây</a></li>
                            <li><a href="blog.php">Blog cộng đồng</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3>Nhóm cây</h3>
                        <ul>
                            <?php foreach ($collectionDirectory as $cat): ?>
                                <li><a href="collection.php?category=<?= e(rawurlencode($cat['name'])) ?>"><?= e($cat['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div>
                        <h3>Tài khoản</h3>
                        <ul>
                            <?php if (current_user() === null): ?>
                                <li><a href="login.php">Đăng nhập</a></li>
                                <li><a href="register.php">Tạo tài khoản</a></li>
                            <?php else: ?>
                                <li><a href="favorites.php">Cây yêu thích</a></li>
                                <li><a href="profile.php">Trang cá nhân</a></li>
                            <?php endif; ?>
                            <li><a href="collection.php">Bộ sưu tập cây</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="floema-footer-band floema-footer-band--bottom">
                <svg class="footer-line-art footer-line-art--tall" viewBox="0 0 110 190" fill="none" aria-hidden="true">
                    <path d="M26 189V94h58v95"/>
                    <path d="M22 94h66v10H22z"/>
                    <path d="M40 94c-6-26-4-56 4-76 6 20 8 50 4 76M55 94c-3-34 0-66 6-88 6 22 7 56 3 88M70 94c4-24 8-50 4-68-8 14-12 42-10 68"/>
                </svg>

                <div class="footer-big-links">
                    <a href="collection.php">Bộ sưu tập</a>
                    <a href="plant-advisor.php">Gợi ý chọn cây</a>
                    <a href="blog.php">Blog cộng đồng</a>
                    <a href="profile.php">Trang cá nhân</a>
                </div>

                <div class="footer-colophon">
                    <p>Một góc nhà. Một chút xanh.</p>
                    <p>Kho dữ liệu tra cứu và chia sẻ kinh nghiệm chăm sóc cây xanh. Website không hỗ trợ mua bán hoặc giao hàng.</p>
                </div>

                <svg class="footer-line-art footer-line-art--pot" viewBox="0 0 150 94" fill="none" aria-hidden="true">
                    <path d="M36 93V42h52v51M36 52H14l-6-8M88 48c16-4 22 14 10 28-4 5-10 6-10 6M48 42v-8h28v8"/>
                    <path d="M112 93V64h26v29M125 64V44M125 54c-10 0-15-7-15-15 8 0 15 6 15 15M125 50c9 0 15-6 15-14-9 0-15 6-15 14"/>
                </svg>
            </div>
        </div>
    </div>
</footer>

<?php require __DIR__ . '/includes/footer.php'; ?>
