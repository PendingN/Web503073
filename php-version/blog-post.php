<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = find_post($slug);
if ($post === null) {
    http_response_code(404);
}
$pageTitle = $post === null ? 'Không tìm thấy bài viết' : $post['title'];
$bodyClass = 'blog-post-page';
$activePage = 'blog';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
require __DIR__ . '/includes/plant-illustration.php';
?>

<?php if ($post === null): ?>
    <main class="not-found">
        <p class="eyebrow">404 · BÀI VIẾT KHÔNG TỒN TẠI</p>
        <h1>Trang này đang để dành cho một câu chuyện khác.</h1>
        <p>Có thể bài viết đã được chuyển đi. Bạn vẫn có thể đọc những ghi chú mới nhất trong nhật ký rừng xanh.</p>
        <a class="btn-brand" href="blog.php">Về trang blog ↗</a>
    </main>
<?php else: ?>
    <main class="blog-post-main">
        <a class="back-link" href="blog.php">← Quay lại nhật ký</a>
        <header class="blog-post-header">
            <div class="post-meta"><span><?= e($post['tag']) ?></span><span><?= e($post['date']) ?> · <?= e($post['time']) ?></span></div>
            <h1><?= e($post['title']) ?></h1>
            <p><?= e($post['excerpt']) ?></p>
        </header>
        <div class="blog-post-art post-art-<?= e($post['tone']) ?>"><?php render_plant_illustration($post['illustration']); ?></div>
        <article class="blog-post-body">
            <?php foreach ($post['body'] as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?>
            <div class="blog-post-cta"><p>Muốn tìm một người bạn xanh cho không gian của mình?</p><a class="btn-brand" href="collection.php">Khám phá bộ sưu tập ↗</a></div>
        </article>
    </main>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
