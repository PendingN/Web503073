<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Blog';
$bodyClass = 'blog-page';
$activePage = 'blog';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
require __DIR__ . '/includes/plant-illustration.php';
?>

<main>
    <section class="blog-intro">
        <div>
            <p class="eyebrow">NHẬT KÝ RỪNG XANH</p>
            <h1>Những câu chuyện<br><em>từ một góc xanh.</em></h1>
        </div>
        <p class="blog-lead">Những ghi chú ngắn về chăm cây, sắp xếp không gian và tìm một nhịp sống dịu dàng hơn cùng thiên nhiên.</p>
    </section>

    <a class="featured-post" href="blog-post.php?slug=<?= e($featuredPost['slug']) ?>">
        <div class="featured-visual post-art-<?= e($featuredPost['tone']) ?>">
            <?php render_plant_illustration($featuredPost['illustration']); ?>
            <small><?= e($featuredPost['tag']) ?></small>
        </div>
        <div class="featured-copy">
            <div class="post-meta"><span><?= e($featuredPost['date']) ?></span><span><?= e($featuredPost['time']) ?></span></div>
            <h2><?= e($featuredPost['title']) ?></h2>
            <p><?= e($featuredPost['excerpt']) ?></p>
            <span class="blog-read-link">Đọc bài viết →</span>
        </div>
    </a>

    <section class="blog-list" id="articles">
        <div class="blog-section-head"><h2>Mới nhất từ khu vườn</h2><a href="collection.php">Tìm một giống cây ↗</a></div>
        <div class="post-grid">
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <a class="post-art post-art-<?= e($post['tone']) ?>" href="blog-post.php?slug=<?= e($post['slug']) ?>">
                        <?php render_plant_illustration($post['illustration']); ?>
                    </a>
                    <div class="post-card-body">
                        <div class="post-meta"><span><?= e($post['tag']) ?></span><span><?= e($post['date']) ?></span></div>
                        <h3><a href="blog-post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
                        <p><?= e($post['excerpt']) ?></p>
                        <div class="post-card-foot"><span><?= e($post['time']) ?></span><a href="blog-post.php?slug=<?= e($post['slug']) ?>">Đọc tiếp →</a></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="blog-newsletter">
        <div><p class="eyebrow">LÁ THƯ XANH</p><h2>Một chút xanh<br>trong hộp thư.</h2><p>Nhận ghi chú chăm cây và những câu chuyện mới mỗi tháng.</p></div>
        <div><?php require __DIR__ . '/includes/newsletter.php'; ?></div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
