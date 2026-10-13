<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$catalog = plant_catalog_statistics();

$pageTitle = '';
$pageDescription = 'Một không gian nhỏ để tìm cây, học cách chăm cây và sống chậm hơn cùng thiên nhiên.';
$bodyClass = 'landing-page';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="top">
    <section class="hero-section">
        <div class="torn-paper" aria-hidden="true"></div>
        <div class="hero-overlay" aria-hidden="true"></div>
        <div class="title-container">
            <h1 class="main-title">VŨ ĐIỆU<br>RỪNG XANH</h1>
            <p class="hero-description">Cùng thiên nhiên tạo nên những giá trị xanh bền vững.</p>
            <a class="btn-brand hero-cta" href="collection.php">Khám phá bộ sưu tập</a>
            <a class="hero-about-link" href="#about">Câu chuyện của chúng mình</a>
        </div>
    </section>

    <section class="landing-about about-showcase" id="about" aria-labelledby="about-title">
        <div class="about-showcase-copy">
            <p class="eyebrow">BẮT ĐẦU TỪ MỘT GÓC NHỎ</p>
            <h2 id="about-title">Một thế giới cây xanh.<br>Một hành trình sống gần thiên nhiên hơn.</h2>
            <p>Vũ Điệu Rừng Xanh là nơi bạn có thể tìm thấy những giống cây phù hợp với không gian sống, đọc những ghi chú chăm cây đơn giản và lưu lại các lựa chọn yêu thích của mình.</p>
            <p>Bạn không cần có một khu vườn lớn để bắt đầu. Một bậu cửa sổ, một góc bàn hay khoảng sân nhỏ cũng có thể là nơi mở ra một thói quen mới: quan sát, chăm chút và dành thêm thời gian cho thiên nhiên.</p>
            <ul class="about-showcase-features">
                <li>Tra cứu đặc điểm từng loài cây</li>
                <li>Ghi chú chăm sóc dễ tìm đọc</li>
                <li>Lưu bộ sưu tập yêu thích</li>
                <li>Chia sẻ câu chuyện cùng cộng đồng</li>
            </ul>
            <div class="about-showcase-actions">
                <a class="btn-brand" href="collection.php">Khám phá bộ sưu tập</a>
                <a class="landing-text-link" href="#our-story">Câu chuyện của chúng mình</a>
            </div>
        </div>
        <div class="about-showcase-visual">
            <div class="about-stat-card">
                <p class="about-stat-label">Đa dạng để khám phá</p>
                <strong class="about-stat-value" aria-label="Hơn 40 giống cây"><span data-count-to="40" aria-hidden="true">40</span><span aria-hidden="true">+</span></strong>
                <p class="about-stat-description">Giống cây trong bộ sưu tập, từ cây trong nhà đến cây ăn quả và những tán cây xanh mát. Mỗi loài cây mở ra một câu chuyện để bạn tìm hiểu.</p>
            </div>
            <img class="about-showcase-photo" src="<?= e(image_url('anh21.jpg')) ?>" alt="Những tán lá xanh xẻ thùy của cây Trầu Bà Thanh Xuân" width="900" height="600" loading="lazy" decoding="async">
        </div>
    </section>

    <nav class="about-jump-links" aria-label="Khám phá phần giới thiệu">
        <span>Cùng đi một vòng</span>
        <a href="#our-story">Câu chuyện</a>
        <a href="#green-world">Thế giới cây xanh</a>
        <a href="#first-steps">Bắt đầu từ đâu?</a>
        <a href="#about-faq">Bạn thường hỏi</a>
    </nav>

    <section class="landing-story landing-section" id="our-story" aria-labelledby="story-title">
        <figure class="landing-story-image">
            <img src="<?= e(image_url('forest.jpg')) ?>" alt="Rừng thông xanh bên hồ nước khi ánh nắng cuối ngày xuyên qua tán cây" width="1920" height="1080" loading="lazy" decoding="async">
            <figcaption>Chậm lại một chút, để nhìn thấy nhiều hơn.</figcaption>
        </figure>
        <div class="landing-story-copy">
            <p class="eyebrow">CÂU CHUYỆN CỦA CHÚNG MÌNH</p>
            <h2 id="story-title">Một góc xanh.<br>Nhiều điều để khám phá.</h2>
            <p>Giữa những ngày bận rộn, đôi khi chúng ta đi ngang một chiếc lá mới mà không kịp nhận ra. Vũ Điệu Rừng Xanh bắt đầu từ mong muốn tạo nên một nơi để những điều nhỏ như thế được chú ý hơn.</p>
            <p>Ở đây, mỗi loài cây có một trang riêng để bạn tìm hiểu đặc điểm và cách chăm sóc. Bạn có thể đi từ một cái tên quen thuộc đến một giống cây chưa từng biết, rồi gom những lựa chọn mình thích thành một bộ sưu tập riêng.</p>
            <p>Và khi có một câu chuyện muốn kể, blog là nơi bạn chia sẻ hành trình của mình: từ lần đầu chọn cây đến niềm vui khi thấy chồi non xuất hiện.</p>
            <a class="landing-text-link" href="blog.php">Ghé đọc những câu chuyện xanh </a>
        </div>
    </section>

    <section class="landing-values landing-section" aria-labelledby="values-title">
        <div class="landing-section-heading">
            <p class="eyebrow">KHÔNG CHỈ LÀ TÌM MỘT LOÀI CÂY</p>
            <h2 id="values-title">Một nơi để tìm hiểu, lưu giữ và sẻ chia.</h2>
        </div>
        <div class="landing-value-grid">
            <article><span class="landing-number">01 / TÌM HIỂU</span><h3>Biết thêm về từng chiếc lá</h3><p>Tra cứu tên cây, đọc mô tả và ghi chú chăm sóc trong cùng một nơi. Tìm theo từ khóa hoặc nhóm cây để khám phá theo sự tò mò của bạn.</p><a class="landing-text-link" href="collection.php">Tra cứu cây</a></article>
            <article><span class="landing-number">02 / LƯU GIỮ</span><h3>Gom lại những điều mình thích</h3><p>Đăng nhập để lưu cây yêu thích và trở lại xem khi cần. Bộ sưu tập riêng giúp bạn tiếp tục khám phá từ những lựa chọn đã khiến mình dừng lại.</p><a class="landing-text-link" href="favorites.php">Bộ sưu tập của tôi</a></article>
            <article><span class="landing-number">03 / SẺ CHIA</span><h3>Để câu chuyện được nối dài</h3><p>Đọc bài viết, lưu bài muốn xem lại hoặc chia sẻ trải nghiệm của bạn. Một câu hỏi hay một bình luận cũng có thể mở đầu cuộc trò chuyện mới.</p><a class="landing-text-link" href="blog.php">Khám phá blog</a></article>
        </div>
    </section>

    <section class="landing-discover landing-section" id="green-world" aria-labelledby="discover-title">
        <div class="landing-section-heading">
            <p class="eyebrow">ĐI THEO SỰ TÒ MÒ CỦA BẠN</p>
            <h2 id="discover-title">Hôm nay, bạn muốn khám phá điều gì?</h2>
            <p>Từ những loài cây quen thuộc đến một sắc xanh mới. Chọn một nhóm để bắt đầu hành trình của riêng bạn.</p>
        </div>
        <div class="landing-category-grid">
            <?php foreach ([
                ['Cây trong nhà', 'Một điểm nhấn xanh cho không gian sống.', 'anh21.jpg'],
                ['Cây ăn quả', 'Những mùa hoa, mùa quả trong khu vườn.', 'anh1.jpg'],
                ['Cây bóng mát', 'Những tán lá rộng cho khoảng sân và khu vườn.', 'anh11.jpg'],
            ] as [$categoryName, $categoryDescription, $categoryImage]): ?>
                <a class="landing-category" href="collection.php?category=<?= e(rawurlencode($categoryName)) ?>">
                    <img src="<?= e(image_url($categoryImage)) ?>" alt="<?= e($categoryName) ?>" loading="lazy" decoding="async" width="600" height="720">
                    <div><h3><?= e($categoryName) ?> </h3><p><?= e($categoryDescription) ?></p></div>
                </a>
            <?php endforeach; ?>
        </div>
        <a class="btn-outline-brand" href="collection.php">Xem tất cả nhóm cây</a>
    </section>

    <section class="landing-start landing-section" id="first-steps" aria-labelledby="start-title">
        <div>
            <p class="eyebrow">DÀNH CHO LẦN GHÉ THĂM ĐẦU TIÊN</p>
            <h2 id="start-title">Bắt đầu nhỏ.<br>Khám phá theo nhịp của bạn.</h2>
            <p>Không cần biết hết về cây ngay hôm nay. Hãy bắt đầu bằng một điều bạn muốn tìm hiểu và để những khám phá tiếp theo đến thật tự nhiên.</p>
            <a class="btn-brand" href="collection.php">Tìm người bạn xanh đầu tiên</a>
        </div>
        <ol class="landing-steps">
            <li><h3>Nhìn lại góc nhỏ của bạn</h3><p>Bạn muốn đặt cây ở đâu? Ghi lại ánh sáng, diện tích và thời gian có thể dành cho việc chăm cây để có điểm bắt đầu khi tìm hiểu.</p></li>
            <li><h3>Dành thời gian đọc về một loài cây</h3><p>Mở bộ sưu tập, chọn một cây khiến bạn chú ý và đọc phần đặc điểm, chăm sóc. Bạn luôn có thể quay lại tìm thêm những lựa chọn khác.</p></li>
            <li><h3>Lưu lại và tiếp tục câu chuyện</h3><p>Tạo tài khoản để giữ cây yêu thích. Ghé blog để đọc thêm, đặt câu hỏi qua bình luận hoặc viết về trải nghiệm của chính bạn.</p></li>
        </ol>
    </section>

    <section class="landing-faq landing-section" id="about-faq" aria-labelledby="faq-title">
        <div class="landing-section-heading"><p class="eyebrow">BẠN THƯỜNG HỎI</p><h2 id="faq-title">Một vài điều trước khi bắt đầu.</h2></div>
        <details open><summary>Tôi chưa biết gì về cây, có thể bắt đầu ở đây không?</summary><p>Có chứ. Bạn có thể duyệt bộ sưu tập mà không cần tài khoản, tìm một cái tên quen thuộc rồi đọc mô tả và ghi chú chăm sóc trên trang chi tiết. Hãy xem đây là điểm khởi đầu để tìm hiểu thêm về từng loài cây.</p></details>
        <details><summary>Tôi có cần đăng ký để xem nội dung không?</summary><p>Bạn có thể xem cây và đọc blog ngay khi ghé thăm. Tài khoản giúp bạn lưu cây yêu thích, lưu bài viết, đăng bài và tham gia bình luận. <a href="register.php">Tạo tài khoản tại đây</a></p></details>
        <details><summary>Website có bán cây hoặc nhận đặt hàng không?</summary><p>Hiện tại, Vũ Điệu Rừng Xanh là website tra cứu cây và chia sẻ nội dung. Website chưa hỗ trợ mua bán, đặt hàng hay giao cây.</p></details>
        <details><summary>Tôi có thể chia sẻ câu chuyện trồng cây của mình ở đâu?</summary><p>Sau khi đăng nhập, bạn có thể viết bài trong mục Blog, chia sẻ trải nghiệm và trao đổi qua bình luận. Những quan sát nhỏ trong hành trình chăm cây cũng là một câu chuyện đáng kể.</p></details>
    </section>

    <section class="landing-invitation landing-section" aria-labelledby="invitation-title">
        <p class="eyebrow">HÀNH TRÌNH XANH CỦA BẠN</p>
        <h2 id="invitation-title">Chiếc lá tiếp theo trong câu chuyện<br>của bạn sẽ là gì?</h2>
        <p>Chọn một loài cây để tìm hiểu hôm nay, hoặc ghé blog và tìm một câu chuyện khiến bạn muốn đọc thêm.</p>
        <div><a class="btn-brand" href="collection.php">Khám phá cây xanh</a><a class="btn-outline-brand" href="blog.php">Đọc blog cộng đồng</a></div>
        <a class="landing-back-top" href="#top">Về đầu trang</a>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
