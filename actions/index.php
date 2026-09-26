<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = '';
$pageDescription = 'Một không gian nhỏ để tìm cây, học cách chăm cây và sống chậm hơn cùng thiên nhiên.';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main>
    <section class="hero-section">
        <div class="torn-paper" aria-hidden="true"></div>
        <div class="hero-overlay"></div>
        <div class="title-container">
            <h1 class="main-title">VŨ ĐIỆU<br>RỪNG XANH</h1>
            <p class="hero-description">Cùng thiên nhiên tạo nên những giá trị xanh bền vững.</p>
            <a class="btn-brand hero-cta" href="dashboard.php">Khám phá bộ sưu tập ↗</a>
        </div>
    </section>

    <section class="landing-about" id="about">
        <div>
            <p class="eyebrow">BẮT ĐẦU TỪ MỘT GÓC NHỎ</p>
            <h2>Để mỗi chiếc lá kể một câu chuyện xanh.</h2>
        </div>
        <div>
            <p>Vũ Điệu Rừng Xanh là nơi bạn có thể tìm thấy những giống cây phù hợp với không gian sống, đọc những ghi chú chăm cây đơn giản và lưu lại các lựa chọn yêu thích của mình.</p>
            <a class="btn-outline-brand" href="collection.php">Xem 40 giống cây ↗</a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
