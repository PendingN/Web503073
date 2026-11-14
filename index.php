<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/blog-images.php';
$catalog = plant_catalog_statistics();
$latestPosts = latest_blog_posts(6);
$editorialPosts = array_values(array_filter($latestPosts, static function (array $post): bool {
    return strncmp((string) ($post['slug'] ?? ''), 'bai-viet-', 9) !== 0;
}));
$recentPosts = array_slice($editorialPosts !== [] ? $editorialPosts : $latestPosts, 0, 2);

// Core site features highlighted in the horizontal feature showcase.
$webFeatures = [
    [
        'type' => 'advisor-ui',
        'title' => 'Gợi ý cây theo ánh sáng.',
        'description' => 'Chọn mức sáng và vị trí đặt để nhận ngay danh sách giống cây dễ chăm, phù hợp đúng không gian sống của bạn.',
        'href' => 'plant-advisor.php',
        'aria' => 'Mở công cụ gợi ý chọn cây theo ánh sáng và vị trí',
    ],
    [
        'type' => 'elements-art',
        'title' => 'Ngũ hành, Hoàng đạo & Lắc quẻ.',
        'description' => 'Khám phá loài cây hợp bản mệnh ngũ hành, xem lịch âm hoặc rút một quẻ cây xanh thư giãn mỗi ngày.',
        'href' => 'plant-advisor.php?mode=elements',
        'aria' => 'Khám phá gợi ý theo ngũ hành, cung hoàng đạo và lắc quẻ',
    ],
    [
        'type' => 'catalog-photo',
        'title' => 'Thư viện ' . (int) $catalog['total'] . ' loài cây xanh.',
        'description' => 'Tìm nhanh theo tên hoặc 5 nhóm cây kèm ghi chú tưới nước, đất trồng, ánh sáng và độ ẩm chi tiết.',
        'href' => 'collection.php',
        'aria' => 'Mở thư viện bộ sưu tập cây xanh',
        'image' => 'anh21.jpg',
        'alt' => 'Lá xanh tươi tốt của cây Trầu Bà Thanh Xuân trong thư viện cây xanh',
    ],
    [
        'type' => 'blog-type',
        'title' => 'Blog chia sẻ kinh nghiệm.',
        'description' => 'Đọc ghi chép chăm cây thực tế từ cộng đồng, lưu lại bài viết hữu ích và tự đăng câu chuyện xanh của bạn.',
        'href' => 'blog.php',
        'aria' => 'Mở blog cộng đồng yêu cây',
    ],
    [
        'type' => 'favorites-studio',
        'title' => 'Lưu cây yêu thích & Hồ sơ.',
        'description' => 'Đánh dấu những loài cây muốn trồng, theo dõi danh sách riêng và quản lý khu vườn cá nhân gọn gàng.',
        'href' => 'favorites.php',
        'aria' => 'Xem danh sách cây yêu thích và trang cá nhân',
    ],
];

// Eighteen distinct, unwatermarked local photos radiating from the hero vanishing point.
$heroTiles = [
    ['image' => 'anh21.jpg', 'variant' => '1', 'tone' => 'plain'],
    ['image' => 'anh48.jpg', 'variant' => '2', 'tone' => 'coral'],
    ['image' => 'anh57.jpg', 'variant' => '3', 'tone' => 'plain'],
    ['image' => 'anh27.jpg', 'variant' => '4', 'tone' => 'celadon'],
    ['image' => 'anh22.jpg', 'variant' => '5', 'tone' => 'plain'],
    ['image' => 'anh23.jpg', 'variant' => '6', 'tone' => 'ivory'],
    ['image' => 'anh47.jpg', 'variant' => '7', 'tone' => 'plain'],
    ['image' => 'anh49.jpg', 'variant' => '8', 'tone' => 'kraft'],
    ['image' => 'anh28.jpg', 'variant' => '9', 'tone' => 'plain'],
    ['image' => 'anh25.jpg', 'variant' => '10', 'tone' => 'celadon'],
    ['image' => 'anh58.jpg', 'variant' => '11', 'tone' => 'plain'],
    ['image' => 'anh29.jpg', 'variant' => '12', 'tone' => 'plain'],
    ['image' => 'anh30.jpg', 'variant' => '13', 'tone' => 'ivory'],
    ['image' => 'anh35.jpg', 'variant' => '14', 'tone' => 'plain'],
    ['image' => 'anh46.jpg', 'variant' => '15', 'tone' => 'coral'],
    ['image' => 'anh51.jpg', 'variant' => '16', 'tone' => 'plain'],
    ['image' => 'anh54.jpg', 'variant' => '17', 'tone' => 'celadon'],
    ['image' => 'anh59.jpg', 'variant' => '18', 'tone' => 'kraft'],
];

$categoryCounts = $catalog['counts'] ?? [
    'Cây trong nhà' => 10,
    'Cây để bàn' => 19,
    'Cây bóng mát' => 10,
    'Cây ăn quả' => 10,
    'Cây nông nghiệp' => 10,
];

$collectionDirectory = [
    [
        'name' => 'Cây trong nhà',
        'label' => 'Trong nhà',
        'image' => 'anh57.jpg',
        'frames' => ['anh57.jpg', 'anh21.jpg', 'anh22.jpg', 'anh23.jpg', 'anh24.jpg'],
        'layout' => 'after-name',
        'icon' => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    ],
    [
        'name' => 'Cây để bàn',
        'label' => 'Để bàn',
        'image' => 'anh47.jpg',
        'frames' => ['anh47.jpg', 'anh46.jpg', 'anh48.jpg', 'anh49.jpg', 'anh51.jpg'],
        'layout' => 'before-name',
        'icon' => '<path d="M7 13h10l-1.5 8h-7z"/><path d="M12 13V7"/><path d="M12 9c0-3 2.5-5 5.5-5 0 3-2.5 5-5.5 5"/><path d="M12 10c0-2.5-2-4.5-5-4.5 0 2.5 2 4.5 5 4.5"/>',
    ],
    [
        'name' => 'Cây bóng mát',
        'label' => 'Bóng mát',
        'image' => 'anh14.jpg',
        'frames' => ['anh14.jpg', 'anh12.jpg', 'anh13.jpg', 'anh15.jpg', 'anh16.jpg'],
        'layout' => 'after-count',
        'icon' => '<path d="M12 21v-8"/><path d="M12 13c4 0 6.5-2.5 6.5-6A6.5 6.5 0 0 0 5.5 7c0 3.5 2.5 6 6.5 6Z"/><path d="M8 21h8"/>',
    ],
    [
        'name' => 'Cây ăn quả',
        'label' => 'Ăn quả',
        'image' => 'anh4.jpg',
        'frames' => ['anh4.jpg', 'anh3.jpg', 'anh6.jpg', 'anh8.jpg', 'anh9.jpg'],
        'layout' => 'before-name',
        'icon' => '<circle cx="12" cy="14" r="6"/><path d="M12 8V4"/><path d="M12 6c2-2 4.5-2 6-1-1 2-3.5 2.5-6 1"/>',
    ],
    [
        'name' => 'Cây nông nghiệp',
        'label' => 'Nông nghiệp',
        'image' => 'anh35.jpg',
        'frames' => ['anh35.jpg', 'anh31.jpg', 'anh32.jpg', 'anh33.jpg', 'anh34.jpg'],
        'layout' => 'after-count',
        'icon' => '<path d="M12 21V9"/><path d="M12 14c-3.5 0-6-2-6-5.5 3.5 0 6 2 6 5.5Z"/><path d="M12 11c3.5 0 6-2 6-5.5-3.5 0-6 2-6 5.5Z"/>',
    ],
];

$indoorUrl = 'collection.php?category=' . rawurlencode('Cây trong nhà');

$pageTitle = '';
$pageDescription = 'Một góc nhà, một chút xanh. Khám phá cây trong nhà và tìm chiếc lá phù hợp với ánh sáng, không gian sống của bạn.';
$bodyClass = 'landing-page';
$activePage = 'home';
$showSiteFooter = false;
require __DIR__ . '/includes/header.php';
?>

<a class="landing-skip-link" href="#main-content">Đến nội dung chính</a>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<main id="main-content">
    <section class="room-hero" aria-labelledby="room-title" data-nav-section="light">
        <div class="hero-gallery" aria-hidden="true">
            <?php foreach ($heroTiles as $tile): ?>
                <div class="hero-tile hero-tile--<?= e($tile['variant']) ?> hero-tile--tone-<?= e($tile['tone']) ?>">
                    <img src="<?= e(image_url($tile['image'])) ?>" alt="" width="300" height="380">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="room-copy">
            <h1 id="room-title">Dành một góc<br>cho màu xanh.</h1>
        </div>
        <div class="hero-baseline">
            <span>Một góc nhà. Một chút xanh.</span>
            <a href="#room-scene">Cuộn để khám phá <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v16m-6-6 6 6 6-6"/></svg></a>
            <span><?= (int) $catalog['total'] ?> loài cây để tìm hiểu</span>
        </div>
    </section>

    <section class="room-scene" id="room-scene" aria-labelledby="scene-title" data-nav-section="light">
        <div class="room-stage">
            <img class="room-image" src="<?= e(image_url('indoor-room.png')) ?>" alt="Góc phòng sáng với ghế vải, bàn gỗ và ba chậu Bàng Singapore, Trầu Bà Thanh Xuân, Lưỡi Hổ bên cửa sổ" width="1586" height="992" fetchpriority="high">

            <div class="room-scene-rail" aria-hidden="true">
                <span class="room-scene-index">01</span>
                <span class="room-scene-rule"></span>
                <span class="room-scene-sub">Không gian sống</span>
            </div>

            <div class="room-scene-copy">
                <h2 id="scene-title">Một chiếc lá.<br>Một góc nhà khác.</h2>
                <p>Chọn số trên ảnh để xem ánh sáng và cách chăm từng loài cây trong phòng.</p>
                <div class="room-scene-actions">
                    <a class="floema-cta" href="<?= e($indoorUrl) ?>">
                        <span class="floema-cta-orb" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M5 19C5 11 13 9 19 5c-1 8-5 14-14 14Z"/><path d="M5 19c4-4 8-7 11-10"/></svg>
                        </span>
                        <span class="floema-cta-label">Xem cây trong nhà</span>
                    </a>
                    <span class="fl-pill fl-pill--coral">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
                        3 cây mẫu
                    </span>
                </div>
            </div>

            <details class="room-spot room-spot--fig" name="room-plants">
                <summary aria-label="Tìm hiểu cây Bàng Singapore"><span aria-hidden="true">01</span></summary>
                <div class="room-plant-note">
                    <h3>Bàng Singapore</h3>
                    <p>Lá to, dáng cao. Một điểm nhấn tự nhiên cho góc phòng nhiều ánh sáng.</p>
                    <span>Gần cửa sổ · Tưới khi mặt đất khô</span>
                    <a href="<?= e($indoorUrl . '&search=' . rawurlencode('Bàng Singapore')) ?>">Tìm hiểu cây này <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
                </div>
            </details>
            <details class="room-spot room-spot--philodendron" name="room-plants">
                <summary aria-label="Tìm hiểu cây Trầu Bà Thanh Xuân"><span aria-hidden="true">02</span></summary>
                <div class="room-plant-note">
                    <h3>Trầu Bà Thanh Xuân</h3>
                    <p>Những đường xẻ lá đầy cá tính, làm góc đọc sách thêm mềm mại và sống động.</p>
                    <span>Ánh sáng gián tiếp · Tránh nắng gắt</span>
                    <a href="<?= e($indoorUrl . '&search=' . rawurlencode('Trầu Bà Thanh Xuân')) ?>">Tìm hiểu cây này <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
                </div>
            </details>
            <details class="room-spot room-spot--snake" name="room-plants">
                <summary aria-label="Tìm hiểu cây Lưỡi Hổ"><span aria-hidden="true">03</span></summary>
                <div class="room-plant-note">
                    <h3>Lưỡi Hổ</h3>
                    <p>Vài chiếc lá thẳng, một chậu cây gọn. Dành cho góc nhỏ và nhịp sống bận rộn.</p>
                    <span>Chịu ít sáng · Để đất khô giữa các lần tưới</span>
                    <a href="<?= e($indoorUrl . '&search=' . rawurlencode('Lưỡi Hổ')) ?>">Tìm hiểu cây này <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
                </div>
            </details>

            <a class="room-guide-card" href="#find-plant">
                <div class="room-guide-meta">
                    <strong>Cẩm nang<br>góc nhà</strong>
                    <span>Tìm theo ánh sáng <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 4v16m-6-6 6 6 6-6"/></svg></span>
                </div>
                <div class="room-guide-thumb" aria-hidden="true">
                    <img src="<?= e(image_url('anh47.jpg')) ?>" alt="" width="120" height="144">
                </div>
            </a>
        </div>
        <p class="room-hint">Chọn số trên ảnh để tìm hiểu từng cây.</p>
    </section>

    <section class="manifesto-section" aria-labelledby="manifesto-label" data-nav-section="light">
        <div class="leaf-shadow-field leaf-shadow-field--manifesto" aria-hidden="true">
            <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice">
                <g opacity="0.54">
                    <path d="M-60 -40 C160 15 360 95 495 255 C345 295 150 265 -60 165 Z"/>
                    <path d="M510 15 C645 65 765 155 835 285 C695 275 575 190 510 15 Z"/>
                    <path d="M720 555 C855 510 990 545 1085 645 C950 710 805 675 720 555 Z"/>
                    <path d="M1160 -30 C1305 60 1420 190 1495 335 L1495 -30 Z"/>
                    <path d="M1180 675 C1290 615 1410 615 1500 665 L1500 940 L1065 940 C1085 820 1125 730 1180 675 Z"/>
                </g>
                <g opacity="0.92">
                    <path d="M-45 195 C130 240 285 325 445 455 L425 474 C270 348 115 268 -50 222 Z"/>
                    <path d="M78 218 C44 108 108 22 208 -2 C232 96 175 182 78 218 Z"/>
                    <path d="M198 272 C195 162 272 86 382 74 C388 176 310 254 198 272 Z"/>
                    <path d="M148 266 C52 292 -12 375 -22 480 C82 465 145 380 148 266 Z"/>
                    <path d="M278 345 C190 390 144 484 158 585 C255 548 302 455 278 345 Z"/>
                    <path d="M408 448 C488 402 580 410 658 464 C584 530 488 524 408 448 Z"/>
                    <path d="M625 26 C695 92 752 178 765 272 C672 248 612 162 625 26 Z"/>
                    <path d="M792 65 C875 112 938 188 965 278 C868 272 802 192 792 65 Z"/>
                    <path d="M565 735 C662 678 768 688 852 762 C760 825 650 812 565 735 Z"/>
                    <path d="M695 820 C795 765 905 780 992 865 C892 922 780 902 695 820 Z"/>
                    <path d="M88 690 C190 645 298 665 385 745 C288 798 175 778 88 690 Z"/>
                    <path d="M25 815 C140 760 272 782 365 875 C242 925 115 900 25 815 Z"/>
                    <path d="M1495 160 C1350 212 1225 295 1132 410 L1152 424 C1242 315 1362 234 1500 185 Z"/>
                    <path d="M1382 202 C1322 108 1235 55 1134 62 C1168 158 1260 216 1382 202 Z"/>
                    <path d="M1270 264 C1198 184 1100 150 1005 174 C1054 260 1158 296 1270 264 Z"/>
                    <path d="M1342 242 C1416 315 1445 410 1420 512 C1330 456 1304 356 1342 242 Z"/>
                    <path d="M1192 346 C1114 368 1058 426 1038 512 C1128 504 1188 442 1192 346 Z"/>
                </g>
            </svg>
        </div>
        <div class="manifesto-inner landing-content">
        <div class="manifesto-main">
            <div id="manifesto-label" class="manifesto-brand">Vũ Điệu Rừng Xanh<br>Cẩm nang cây sống</div>
            <p class="manifesto-copy" data-manifesto-copy>
                <span class="manifesto-inset"><img src="<?= e(image_url('anh27.jpg')) ?>" alt="Chậu cây Ngũ Gia Bì xanh tốt trong chậu đất nung đặt trên bàn gỗ" width="320" height="400"></span><span class="manifesto-phrase is-lit">Một chậu cây đặt đúng góc sáng</span>
                <span class="manifesto-phrase">có thể làm dịu lại cả nhịp sống trong nhà.</span>
                <span class="manifesto-phrase">Từ tán lá lớn nơi phòng khách đến chiếc chậu nhỏ trên bàn làm việc,</span>
                <span class="manifesto-phrase">mỗi loài đều có nhịp thở và nhu cầu ánh sáng riêng —</span>
                <span class="manifesto-phrase">chúng tôi lưu giữ đặc điểm, cách tưới và câu chuyện chăm cây để bạn chọn đúng sắc xanh cho không gian sống.</span>
            </p>
        </div>

        <aside class="manifesto-journal" aria-label="Bài viết mới từ Blog">
            <div class="journal-header-row">
                <a class="journal-more-link" href="blog.php">Bài viết mới <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
            </div>
            <div class="journal-grid">
                <?php if ($recentPosts !== []): ?>
                    <?php foreach ($recentPosts as $post):
                        $cover = blog_cover_url($post);
                        $dateLabel = !empty($post['created_at']) ? date('d/m/Y', strtotime((string) $post['created_at'])) : '';
                    ?>
                    <article class="journal-card">
                        <a href="blog-post.php?slug=<?= e(rawurlencode((string) $post['slug'])) ?>">
                            <div class="journal-card-media">
                                <img src="<?= e(asset_url($cover)) ?>" alt="<?= e((string) $post['title']) ?>" width="360" height="440">
                            </div>
                            <h3><?= e((string) $post['title']) ?></h3>
                            <?php if ($dateLabel !== ''): ?><time datetime="<?= e(substr((string) $post['created_at'], 0, 10)) ?>"><?= e($dateLabel) ?></time><?php endif; ?>
                        </a>
                    </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <article class="journal-card">
                        <a href="blog.php">
                            <div class="journal-card-media">
                                <img src="<?= e(image_url('anh57.jpg')) ?>" alt="Góc đọc bài viết chăm sóc cây xanh" width="360" height="440">
                            </div>
                            <h3>Khám phá góc chia sẻ kinh nghiệm chăm cây</h3>
                        </a>
                    </article>
                <?php endif; ?>
            </div>
        </aside>
        </div>
    </section>

    <section class="feature-showcase" id="web-features" aria-labelledby="features-title" data-nav-section="light" data-feature-showcase>
        <div class="leaf-shadow-field leaf-shadow-field--features" aria-hidden="true">
            <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice">
                <g opacity="0.56">
                    <path d="M-40 -30 C130 25 280 105 395 235 C255 260 95 215 -40 115 Z"/>
                    <path d="M840 -40 C990 20 1155 105 1285 245 C1115 270 945 210 840 -40 Z"/>
                    <path d="M-50 280 C95 250 230 305 325 410 C205 465 75 435 -50 355 Z"/>
                    <path d="M580 650 C760 605 950 645 1095 768 C920 852 720 820 580 650 Z"/>
                </g>
                <g opacity="0.92">
                    <path d="M140 28 C215 95 270 175 295 268 C202 245 140 162 140 28 Z"/>
                    <path d="M312 18 C395 68 462 145 492 238 C392 235 322 152 312 18 Z"/>
                    <path d="M535 10 C618 68 682 150 708 242 C608 228 545 142 535 10 Z"/>
                    <path d="M688 52 C772 95 838 165 868 255 C772 248 705 170 688 52 Z"/>
                    <path d="M1485 105 C1305 138 1148 205 1008 308 L1026 328 C1162 228 1315 162 1490 132 Z"/>
                    <path d="M1365 135 C1285 48 1180 12 1075 32 C1130 126 1242 166 1365 135 Z"/>
                    <path d="M1225 184 C1138 116 1032 100 935 140 C1002 220 1116 238 1225 184 Z"/>
                    <path d="M1395 160 C1458 245 1474 348 1434 448 C1352 384 1344 275 1395 160 Z"/>
                    <path d="M1110 246 C1030 260 968 312 930 392 C1020 395 1090 342 1110 246 Z"/>
                    <path d="M-40 620 C85 570 215 555 340 580 L335 602 C215 578 90 594 -35 644 Z"/>
                    <path d="M58 588 C32 490 68 395 150 342 C192 432 155 528 58 588 Z"/>
                    <path d="M175 568 C185 470 245 398 338 372 C355 468 292 545 175 568 Z"/>
                    <path d="M132 602 C58 648 20 725 30 818 C118 785 158 702 132 602 Z"/>
                    <path d="M265 588 C288 675 352 742 445 772 C432 678 368 608 265 588 Z"/>
                    <path d="M510 778 C605 718 712 728 795 802 C702 862 595 848 510 778 Z"/>
                    <path d="M775 790 C870 728 982 740 1068 818 C972 878 862 864 775 790 Z"/>
                    <path d="M1180 780 C1270 708 1385 692 1485 735 C1408 820 1295 838 1180 780 Z"/>
                </g>
            </svg>
        </div>
        <div class="feature-head landing-content">
            <h2 id="features-title">Công cụ dành cho góc xanh của bạn.</h2>
            <div class="feature-controls" aria-label="Điều hướng các tính năng trang web">
                <button class="feature-nav-btn" type="button" data-feature-prev aria-label="Tính năng trước" disabled>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6 9 12l6 6"/></svg>
                </button>
                <button class="feature-nav-btn" type="button" data-feature-next aria-label="Tính năng tiếp theo">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                </button>
            </div>
        </div>

        <div class="feature-track" data-feature-track tabindex="0" role="region" aria-label="Giới thiệu các tính năng chính của trang web">
            <?php foreach ($webFeatures as $feature): ?>
            <article class="feature-card">
                <a class="feature-card-link" href="<?= e($feature['href']) ?>" aria-label="<?= e($feature['aria']) ?>">
                    <div class="feature-card-visual feature-card-visual--<?= e($feature['type']) ?>" aria-hidden="true">
                        <?php if ($feature['type'] === 'advisor-ui'): ?>
                            <div class="feature-ui-stage">
                                <span class="feature-ui-eyebrow">Gợi ý thông minh</span>
                                <div class="feature-ui-chips">
                                    <span class="feature-ui-chip is-selected">Sáng dịu nhẹ</span>
                                    <span class="feature-ui-chip">Ít sáng</span>
                                    <span class="feature-ui-chip is-selected">Bàn &amp; kệ</span>
                                </div>
                                <div class="feature-ui-result">
                                    <span class="feature-ui-dot"></span>
                                    <div>
                                        <strong>Trầu Bà &amp; Lưỡi Hổ</strong>
                                        <span>Ưu tiên giống dễ chăm</span>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($feature['type'] === 'elements-art'): ?>
                            <img class="feature-elements-svg" src="<?= e(image_url('advisor/five-elements.svg')) ?>" alt="" width="800" height="500">
                        <?php elseif ($feature['type'] === 'catalog-photo'): ?>
                            <img class="feature-photo-img" src="<?= e(image_url($feature['image'])) ?>" alt="" width="600" height="750">
                        <?php elseif ($feature['type'] === 'blog-type'): ?>
                            <div class="feature-type-stage">
                                <span class="feature-type-halo feature-type-halo--top"></span>
                                <span class="feature-type-words">Chăm cây<br>Mẹo hay<br>Sống xanh</span>
                                <span class="feature-type-halo feature-type-halo--bottom"></span>
                            </div>
                        <?php else: ?>
                            <div class="feature-favorites-stage">
                                <img class="feature-fav-photo feature-fav-photo--left" src="<?= e(image_url('anh47.jpg')) ?>" alt="" width="240" height="300">
                                <img class="feature-fav-photo feature-fav-photo--right" src="<?= e(image_url('anh57.jpg')) ?>" alt="" width="240" height="300">
                                <span class="feature-fav-heart">
                                    <svg viewBox="0 0 24 24"><path d="M12 20.5s-7-4.5-9-8.8C1.2 8.2 3.7 5 7 5c2 0 3.7 1.1 5 2.9C13.3 6.1 15 5 17 5c3.3 0 5.8 3.2 4 6.7-2 4.3-9 8.8-9 8.8Z"/></svg>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="feature-card-copy"><strong><?= e($feature['title']) ?></strong> <?= e($feature['description']) ?></p>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="collections-overview" aria-labelledby="collections-title" data-nav-section="light">
        <div class="leaf-shadow-field" aria-hidden="true">
            <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice">
                <g class="leaf-shadow-branch leaf-shadow-branch--1">
                    <path d="M-80 120 C160 60 360 190 540 120 C420 260 220 310 -40 240 Z"/>
                    <path d="M120 20 C260 140 340 300 440 430 C270 390 120 280 40 90 Z"/>
                    <path d="M290 60 C470 110 610 210 730 330 C540 340 380 240 250 110 Z"/>
                </g>
                <g class="leaf-shadow-branch leaf-shadow-branch--2">
                    <path d="M1520 240 C1240 190 1050 320 870 290 C990 420 1210 470 1490 380 Z"/>
                    <path d="M1360 460 C1140 490 960 610 810 760 C1020 740 1230 630 1430 520 Z"/>
                    <path d="M460 540 C650 480 810 560 930 670 C730 710 540 660 390 580 Z"/>
                </g>
            </svg>
        </div>
        <div class="collections-inner">
            <h2 id="collections-title" class="collections-heading">Các nhóm cây</h2>
            <nav class="collections-list" aria-label="Danh mục các nhóm cây">
                <?php foreach ($collectionDirectory as $cat):
                    $catCount = (int) ($categoryCounts[$cat['name']] ?? 0);
                    $countText = '(' . str_pad((string) $catCount, 2, '0', STR_PAD_LEFT) . ')';
                    $catUrl = 'collection.php?category=' . rawurlencode($cat['name']);
                    $catFrames = $cat['frames'] ?? [$cat['image']];
                    $renderThumb = static function (array $frames): string {
                        $html = '<span class="collection-thumb" aria-hidden="true">';
                        foreach ($frames as $frameImg) {
                            $html .= '<img src="' . e(image_url((string) $frameImg)) . '" alt="" width="212" height="96" decoding="sync">';
                        }
                        $html .= '</span>';
                        return $html;
                    };
                ?>
                <a class="collection-row collection-row--<?= e($cat['layout']) ?>" href="<?= e($catUrl) ?>" aria-label="<?= e($cat['name']) ?> (<?= (int) $catCount ?>)">
                    <?php if ($cat['layout'] === 'before-name'): ?>
                        <?= $renderThumb($catFrames) ?>
                        <span class="collection-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><?= $cat['icon'] ?></svg></span>
                        <span class="collection-name"><?= e($cat['label']) ?></span>
                        <span class="collection-count" aria-hidden="true"><?= e($countText) ?></span>
                    <?php elseif ($cat['layout'] === 'after-name'): ?>
                        <span class="collection-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><?= $cat['icon'] ?></svg></span>
                        <span class="collection-name"><?= e($cat['label']) ?></span>
                        <?= $renderThumb($catFrames) ?>
                        <span class="collection-count" aria-hidden="true"><?= e($countText) ?></span>
                    <?php else: ?>
                        <span class="collection-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><?= $cat['icon'] ?></svg></span>
                        <span class="collection-name"><?= e($cat['label']) ?></span>
                        <span class="collection-count" aria-hidden="true"><?= e($countText) ?></span>
                        <?= $renderThumb($catFrames) ?>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </section>

    <section class="forest-highlight" aria-labelledby="forest-title" data-nav-section="dark">
        <div class="forest-grid">
            <div class="forest-profile">
                <div class="forest-mini-photo">
                    <img src="<?= e(image_url('anh47.jpg')) ?>" alt="Chậu Sen Đá nhỏ trong chậu gốm tối màu dưới ánh sáng ấm" width="320" height="320">
                </div>
                <div>
                    <h2 id="forest-title">Góc tư vấn<br>chọn cây</h2>
                    <p>Theo ánh sáng &amp; không gian</p>
                </div>
            </div>
            <div class="forest-story">
                <p>Vũ Điệu Rừng Xanh tổng hợp thông tin thực tế về <?= (int) $catalog['total'] ?> loài cây từ cây trong nhà, cây để bàn đến cây ăn quả và cây bóng mát, giúp bạn tra cứu nhanh nhu cầu nắng, nước tưới và độ ẩm.</p>
                <p>Nếu chưa biết bắt đầu từ chậu nào, công cụ gợi ý sẽ kết hợp ánh sáng, vị trí đặt cây cùng các bài chia sẻ từ cộng đồng để thu hẹp lựa chọn phù hợp nhất cho bạn.</p>
                <a class="forest-link" href="plant-advisor.php">Mở công cụ gợi ý cây <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
            </div>
            <div class="forest-feature-photo">
                <img src="<?= e(image_url('anh22.jpg')) ?>" alt="Cây Lưỡi Hổ xanh viền vàng nổi bật trên nền gỗ sẫm màu" width="760" height="920">
            </div>
        </div>
    </section>

    <section class="bottom-showcase" id="first-steps" aria-labelledby="beginnings-title" data-nav-section="light">
        <div class="leaf-shadow-field leaf-shadow-field--bottom" aria-hidden="true">
            <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice">
                <g class="leaf-shadow-branch leaf-shadow-branch--3">
                    <path d="M160 760 C340 620 560 560 760 610 C610 740 420 820 190 830 Z"/>
                    <path d="M680 680 C880 540 1120 520 1320 620 C1140 730 920 770 710 740 Z"/>
                    <path d="M1020 260 C1180 220 1340 280 1480 390 C1280 410 1110 360 990 300 Z"/>
                </g>
            </svg>
        </div>

        <div class="care-pillars landing-content">
            <h2 id="beginnings-title" class="sr-only">Ba bước quan sát trước lần tưới đầu tiên</h2>
            <article class="care-pillar care-pillar--tall">
                <div class="care-pillar-media">
                    <img src="<?= e(image_url('anh21.jpg')) ?>" alt="Chậu cây Trầu Bà Thanh Xuân đặt trong góc phòng sáng" width="600" height="440">
                </div>
                <div class="care-pillar-copy">
                    <h3>Để ý vệt nắng</h3>
                    <p>Quan sát góc nhà vào nhiều khung giờ trong ngày để biết ánh sáng mạnh, dịu hay ít sáng tự nhiên.</p>
                </div>
            </article>
            <article class="care-pillar care-pillar--mid">
                <div class="care-pillar-media">
                    <img src="<?= e(image_url('anh58.jpg')) ?>" alt="Cây Trường Sinh lá tròn xanh mướt trong chậu tre mộc mạc" width="600" height="360">
                </div>
                <div class="care-pillar-copy">
                    <h3>Chọn cây, chọn chậu</h3>
                    <p>Đọc kỹ dáng cây và kích thước tán. Ưu tiên chậu có lỗ thoát nước và hài hòa với không gian sẵn có.</p>
                </div>
            </article>
            <article class="care-pillar care-pillar--short">
                <div class="care-pillar-media">
                    <img src="<?= e(image_url('anh49.jpg')) ?>" alt="Chậu Hương Thảo bọc giấy kraft mộc mạc" width="600" height="240">
                </div>
                <div class="care-pillar-copy">
                    <h3>Chạm vào đất trước</h3>
                    <p>Kiểm tra độ ẩm của lớp đất mặt và đọc ghi chú từng loài thay vì tưới nước theo thói quen cố định.</p>
                </div>
            </article>
        </div>

        <div class="room-invitation landing-content">
            <p class="invitation-title">Góc xanh của bạn<br>bắt đầu từ đây.</p>
            <div class="invitation-side">
                <p>Tra cứu <?= (int) $catalog['total'] ?> loài cây trong kho dữ liệu, lưu lại giống cây yêu thích hoặc đọc chia sẻ từ cộng đồng.</p>
                <div class="invitation-actions">
                    <a class="invitation-pill" href="collection.php">
                        <span>Khám phá bộ sưu tập · <?= (int) $catalog['total'] ?> loài cây</span>
                        <span class="invitation-pill-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" width="17" height="17"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></span>
                    </a>
                    <a class="room-text-link" href="blog.php">Đọc câu chuyện chăm cây <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="floema-footer" data-nav-section="yellow">
    <div class="floema-footer-grid">
        <div class="floema-footer-mark">
            <a class="floema-footer-brand" href="index.php" aria-label="Vũ Điệu Rừng Xanh — Về đầu trang">
                <svg class="floema-footer-emblem" viewBox="0 0 120 150" fill="none" aria-hidden="true">
                    <path d="M22 136C22 78 90 68 96 12c34 58 6 110-44 96M22 136C8 98 8 66 32 46c4 36 38 40 24 68M22 136c19-38 50-58 62-78"/>
                </svg>
                <span>VŨ ĐIỆU<br>RỪNG XANH<sup>®</sup></span>
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
                            <li><a href="dashboard.php">Tổng quan vườn</a></li>
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
