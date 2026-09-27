<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Blog';
$pageDescription = 'Một bảng tin nhẹ nhàng để chia sẻ câu chuyện, kinh nghiệm và cảm hứng sống cùng cây xanh.';
$bodyClass = 'blog-page';
$activePage = 'blog';

$feedItems = [
    [
        'post' => $featuredPost,
        'community' => 'r/góc xanh',
        'author' => 'Mai Hạ',
        'initials' => 'MH',
        'time' => '2 giờ trước',
        'votes' => '248',
        'comments' => '18',
        'featured' => true,
    ],
    [
        'post' => $posts[0],
        'community' => 'r/chăm cây',
        'author' => 'Minh Anh',
        'initials' => 'MA',
        'time' => '5 giờ trước',
        'votes' => '139',
        'comments' => '24',
    ],
    [
        'post' => $posts[1],
        'community' => 'r/không gian sống',
        'author' => 'Quỳnh Nhi',
        'initials' => 'QN',
        'time' => '7 giờ trước',
        'votes' => '96',
        'comments' => '11',
    ],
    [
        'post' => $posts[2],
        'community' => 'r/ban công nhỏ',
        'author' => 'An Nhiên',
        'initials' => 'AN',
        'time' => '1 ngày trước',
        'votes' => '82',
        'comments' => '9',
    ],
    [
        'post' => $posts[3],
        'community' => 'r/chăm cây',
        'author' => 'Bảo Ngọc',
        'initials' => 'BN',
        'time' => '2 ngày trước',
        'votes' => '71',
        'comments' => '7',
    ],
    [
        'post' => $posts[4],
        'community' => 'r/góc xanh',
        'author' => 'Hoàng Nam',
        'initials' => 'HN',
        'time' => '3 ngày trước',
        'votes' => '58',
        'comments' => '6',
    ],
];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
require __DIR__ . '/includes/plant-illustration.php';
?>

<main class="blog-feed-main">
    <section class="feed-hero" aria-labelledby="feed-title">
        <div class="feed-hero-copy">
            <p class="eyebrow">NHẬT KÝ RỪNG XANH</p>
            <h1 id="feed-title">Chuyện cây cỏ,<br><em>kể cùng nhau.</em></h1>
            <p class="feed-hero-lead">Một góc nhỏ để chia sẻ những điều bạn học được, những chiếc lá mới và nhịp sống dịu dàng hơn cùng thiên nhiên.</p>
        </div>
        <div class="feed-hero-note">
            <span class="feed-hero-note-icon" aria-hidden="true">✦</span>
            <div>
                <strong>Không cần nói thật to.</strong>
                <p>Một câu chuyện nhỏ cũng có thể giúp ai đó chăm cây dễ hơn hôm nay.</p>
            </div>
        </div>
    </section>

    <div class="feed-layout">
        <section class="feed-column" aria-label="Bảng tin bài viết">
            <section class="feed-composer" id="write-post" aria-labelledby="composer-title">
                <div class="feed-composer-bar">
                    <span class="feed-avatar feed-avatar-user" aria-hidden="true">bạn</span>
                    <button class="composer-trigger" type="button" data-composer-toggle aria-expanded="false" aria-controls="demo-post-form">
                        <span id="composer-title">Bạn muốn chia sẻ điều gì với khu vườn?</span>
                    </button>
                    <button class="composer-add" type="button" data-composer-toggle aria-label="Mở khung viết bài" aria-expanded="false" aria-controls="demo-post-form">+</button>
                </div>
                <form class="demo-post-form" id="demo-post-form" data-demo-post-form hidden>
                    <p class="demo-form-note"><span aria-hidden="true">✦</span> Bản demo giao diện — bài viết chưa được lưu vào hệ thống.</p>
                    <label for="demo-post-title">Tiêu đề bài viết</label>
                    <input id="demo-post-title" name="title" type="text" maxlength="120" placeholder="Ví dụ: Chiếc lá mới đầu tiên của mình" required>
                    <label for="demo-post-content">Bạn muốn kể điều gì?</label>
                    <textarea id="demo-post-content" name="content" rows="4" maxlength="500" placeholder="Viết vài dòng về góc xanh của bạn..." required></textarea>
                    <div class="demo-form-actions">
                        <span>Hiển thị công khai · bản thử nghiệm</span>
                        <div>
                            <button class="btn-quiet" type="button" data-composer-cancel>Huỷ</button>
                            <button class="btn-brand" type="submit">Đăng thử bài viết</button>
                        </div>
                    </div>
                </form>
            </section>

            <div class="feed-toolbar">
                <div>
                    <p class="eyebrow">BẢNG TIN</p>
                    <h2>Bài viết mới</h2>
                </div>
                <div class="feed-filters" role="group" aria-label="Sắp xếp bài viết">
                    <button class="is-active" type="button" data-feed-filter="new" aria-pressed="true">Mới nhất</button>
                    <button type="button" data-feed-filter="popular" aria-pressed="false">Được yêu thích</button>
                </div>
            </div>

            <div class="feed-list">
                <?php foreach ($feedItems as $feedIndex => $item):
                    $post = $item['post'];
                    $isFeatured = $item['featured'] ?? false;
                ?>
                    <article class="feed-post<?= $isFeatured ? ' feed-post-featured' : '' ?>" data-feed-score="<?= e($item['votes']) ?>" data-feed-order="<?= e((string) $feedIndex) ?>">
                        <header class="feed-post-head">
                            <div class="feed-author">
                                <span class="feed-avatar feed-avatar-<?= e($post['tone']) ?>" aria-hidden="true"><?= e($item['initials']) ?></span>
                                <div>
                                    <p><strong><?= e($item['community']) ?></strong><span aria-hidden="true"> · </span><span><?= e($item['time']) ?></span></p>
                                    <small>Đăng bởi <?= e($item['author']) ?></small>
                                </div>
                            </div>
                            <div class="feed-post-controls">
                                <button class="feed-more" type="button" aria-label="Tuỳ chọn bài viết">•••</button>
                            </div>
                        </header>

                        <div class="feed-post-content">
                            <div class="feed-post-copy">
                                <span class="feed-topic"><?= e($post['tag']) ?></span>
                                <h3><a href="blog-post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
                                <p><?= e($post['excerpt']) ?></p>
                                <div class="feed-post-actions">
                                    <div class="feed-vote-group" data-vote-group>
                                        <button class="feed-action feed-vote" type="button" data-feed-action="up" aria-label="Thích bài viết" aria-pressed="false"><span aria-hidden="true">↑</span><span data-count><?= e($item['votes']) ?></span></button>
                                        <button class="feed-action feed-vote feed-vote-down" type="button" data-feed-action="down" aria-label="Không thích bài viết" aria-pressed="false"><span aria-hidden="true">↓</span></button>
                                    </div>
                                    <button class="feed-action" type="button" data-feed-action="comment" aria-label="Xem <?= e($item['comments']) ?> bình luận"><span aria-hidden="true">◯</span><span><?= e($item['comments']) ?></span></button>
                                    <button class="feed-action" type="button" data-feed-action="save" aria-label="Lưu bài viết" aria-pressed="false"><span aria-hidden="true">♡</span><span class="feed-action-text">Lưu</span></button>
                                    <a class="feed-read-link" href="blog-post.php?slug=<?= e($post['slug']) ?>">Đọc tiếp <span aria-hidden="true">↗</span></a>
                                </div>
                            </div>
                            <a class="feed-post-art feed-art-<?= e($post['tone']) ?>" href="blog-post.php?slug=<?= e($post['slug']) ?>" aria-label="Đọc bài: <?= e($post['title']) ?>">
                                <?php render_plant_illustration($post['illustration'], 'feed-plant-illustration'); ?>
                                <?php if ($isFeatured): ?><span class="feed-art-badge">Bài nổi bật</span><?php endif; ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="feed-end-note">
                <span class="feed-end-line" aria-hidden="true"></span>
                <p>Bạn đã đi qua những chiếc lá mới nhất.</p>
                <a href="#write-post">Viết một câu chuyện <span aria-hidden="true">↗</span></a>
            </div>
        </section>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
