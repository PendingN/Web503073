<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$catalog = plant_catalog_statistics();

// Editorial starting points; the catalog remains the source for full care notes.
$roomPlants = [
    ['name' => 'Bàng Singapore', 'image' => 'anh26.jpg', 'light' => 'bright', 'position' => 'floor', 'note' => 'Một dáng cây cao, một điểm nhấn cho phòng khách.'],
    ['name' => 'Trầu Bà Thanh Xuân', 'image' => 'anh21.jpg', 'light' => 'bright indirect', 'position' => 'floor', 'note' => 'Tán lá xòe rộng, làm mềm những góc thẳng trong nhà.'],
    ['name' => 'Lưỡi Hổ', 'image' => 'anh22.jpg', 'light' => 'bright indirect low', 'position' => 'floor shelf', 'note' => 'Gọn gàng, thẳng dáng, hợp cả góc sàn lẫn bàn nhỏ.'],
    ['name' => 'Kim Tiền', 'image' => 'anh23.jpg', 'light' => 'indirect low', 'position' => 'floor shelf', 'note' => 'Những nhánh lá xanh bóng cho góc làm việc dịu lại.'],
];
$indoorUrl = 'collection.php?category=' . rawurlencode('Cây trong nhà');
$pageTitle = '';
$pageDescription = 'Một góc nhà, một chút xanh. Khám phá cây trong nhà và tìm chiếc lá phù hợp với ánh sáng, không gian sống của bạn.';
$bodyClass = 'landing-page';
$activePage = 'home';
$footerTagline = 'Một góc nhà. Một chút xanh.';
require __DIR__ . '/includes/header.php';
?>

<a class="landing-skip-link" href="#main-content">Đến nội dung chính</a>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<main id="main-content">
    <section class="room-hero" aria-labelledby="room-title">
        <div class="room-copy">
            <h1 id="room-title">Một góc nhà.<br><em>Một chút xanh.</em></h1>
            <p>Thêm một chiếc lá, thêm một lý do để yêu không gian mình sống. Tìm người bạn xanh hợp với góc nhà của bạn.</p>
            <a class="room-button" href="#find-plant">Tìm cây cho góc nhà <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
            <a class="room-story-link" href="#first-steps">Chưa từng trồng cây? Bắt đầu ở đây</a>
        </div>

        <div class="room-scene" id="room-scene" aria-label="Khám phá cây trong một góc phòng">
            <div class="room-stage">
                <img class="room-image" src="<?= e(image_url('indoor-room.png')) ?>" alt="Góc phòng sáng với ghế vải, bàn gỗ và ba chậu Bàng Singapore, Trầu Bà Thanh Xuân, Lưỡi Hổ bên cửa sổ" width="1586" height="992" fetchpriority="high" decoding="async">

                <details class="room-spot room-spot--fig" name="room-plants">
                    <summary aria-label="Tìm hiểu cây Bàng Singapore"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></summary>
                    <div class="room-plant-note">
                        <h2>Bàng Singapore</h2>
                        <p>Lá to, dáng cao. Một điểm nhấn tự nhiên cho góc phòng nhiều ánh sáng.</p>
                        <span>Gần cửa sổ · Tưới khi mặt đất khô</span>
                        <a href="<?= e($indoorUrl . '&search=' . rawurlencode('Bàng Singapore')) ?>">Tìm hiểu cây này <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
                    </div>
                </details>
                <details class="room-spot room-spot--philodendron" name="room-plants">
                    <summary aria-label="Tìm hiểu cây Trầu Bà Thanh Xuân"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></summary>
                    <div class="room-plant-note">
                        <h2>Trầu Bà Thanh Xuân</h2>
                        <p>Những đường xẻ lá đầy cá tính, làm góc đọc sách thêm mềm mại và sống động.</p>
                        <span>Ánh sáng gián tiếp · Tránh nắng gắt</span>
                        <a href="<?= e($indoorUrl . '&search=' . rawurlencode('Trầu Bà Thanh Xuân')) ?>">Tìm hiểu cây này <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
                    </div>
                </details>
                <details class="room-spot room-spot--snake" name="room-plants">
                    <summary aria-label="Tìm hiểu cây Lưỡi Hổ"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></summary>
                    <div class="room-plant-note">
                        <h2>Lưỡi Hổ</h2>
                        <p>Vài chiếc lá thẳng, một chậu cây gọn. Dành cho góc nhỏ và nhịp sống bận rộn.</p>
                        <span>Chịu ít sáng · Để đất khô giữa các lần tưới</span>
                        <a href="<?= e($indoorUrl . '&search=' . rawurlencode('Lưỡi Hổ')) ?>">Tìm hiểu cây này <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
                    </div>
                </details>
            </div>
            <p class="room-hint"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Chạm vào dấu cộng để gặp từng chiếc lá</p>
        </div>
        <a class="room-scroll" href="#find-plant"><span>Góc xanh của bạn bắt đầu ở đây</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v15m-6-6 6 6 6-6"/></svg></a>
    </section>

    <section class="plant-finder landing-content" id="find-plant" aria-labelledby="finder-title">
        <div class="finder-heading">
            <h2 id="finder-title">Không gian của bạn.<br><em>Chiếc lá của bạn.</em></h2>
            <p>Góc phòng ngập nắng hay chiếc bàn nhỏ?<br>Bắt đầu từ ánh sáng và nơi bạn muốn đặt cây.</p>
        </div>
        <form class="finder-form" data-plant-finder hidden>
            <label for="room-light">Ánh sáng ở góc nhà bạn
                <select id="room-light" name="light">
                    <option value="all">Chọn ánh sáng</option>
                    <option value="bright">Nhiều ánh sáng tự nhiên</option>
                    <option value="indirect">Ánh sáng gián tiếp, dịu nhẹ</option>
                    <option value="low">Ít ánh sáng tự nhiên</option>
                </select>
            </label>
            <label for="room-position">Bạn muốn đặt cây ở đâu?
                <select id="room-position" name="position">
                    <option value="all">Chọn vị trí</option>
                    <option value="floor">Trên sàn, ở một góc phòng</option>
                    <option value="shelf">Trên bàn hoặc kệ nhỏ</option>
                </select>
            </label>
            <button class="room-button" type="submit">Gợi ý cây cho tôi <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></button>
        </form>
        <div class="finder-caption">
            <p data-finder-status role="status">Bốn gợi ý để bắt đầu một góc xanh.</p>
            <a class="room-text-link" href="<?= e($indoorUrl) ?>">Xem cây trong nhà <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
        </div>
        <div class="plant-picks" data-plant-picks>
            <?php foreach ($roomPlants as $roomPlant): ?>
            <article class="plant-pick" data-light="<?= e($roomPlant['light']) ?>" data-position="<?= e($roomPlant['position']) ?>">
                <a href="<?= e($indoorUrl . '&search=' . rawurlencode($roomPlant['name'])) ?>">
                    <div class="plant-pick-image"><img src="<?= e(image_url($roomPlant['image'])) ?>" alt="Cây <?= e($roomPlant['name']) ?>" width="600" height="700" loading="lazy" decoding="async"></div>
                    <h3><?= e($roomPlant['name']) ?><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 18 12-12M6 6h12v12"/></svg></h3>
                </a>
                <p><?= e($roomPlant['note']) ?></p>
            </article>
            <?php endforeach; ?>
        </div>
        <p class="finder-empty" data-finder-empty hidden>Chưa có gợi ý cho góc này. Thử đổi ánh sáng, vị trí hoặc <a href="<?= e($indoorUrl) ?>">xem tất cả cây trong nhà</a>.</p>
        <p class="finder-footnote">Cây vẫn cần ánh sáng tự nhiên, kể cả những loài chịu ít sáng. Đọc thêm hướng dẫn chăm sóc để chọn cây phù hợp.</p>
    </section>

    <section class="room-beginnings" id="first-steps" aria-labelledby="beginnings-title">
        <div class="beginnings-photo">
            <img src="<?= e(image_url('anh21.jpg')) ?>" alt="Chậu cây Trầu Bà Thanh Xuân lá xẻ xanh tươi đặt cạnh bình xịt nước trên nền tường sáng" width="900" height="1200" loading="lazy" decoding="async">
        </div>
        <div class="beginnings-copy">
            <h2 id="beginnings-title">Chăm một chiếc lá.<br><em>Yêu thêm góc nhà.</em></h2>
            <p>Bạn không cần giỏi trồng cây để bắt đầu. Một chút quan sát mỗi ngày đã là một khởi đầu tốt.</p>
            <ol class="room-care-steps">
                <li><h3>Để ý một vệt nắng</h3><p>Quan sát góc nhà vào những thời điểm khác nhau. Ánh sáng giúp bạn thu hẹp lựa chọn đầu tiên.</p></li>
                <li><h3>Chọn cây, rồi chọn chậu</h3><p>Đọc về dáng cây và cách chăm. Chọn chậu thoát nước tốt, vừa với cây và hài hòa với đồ đạc sẵn có.</p></li>
                <li><h3>Chạm vào đất trước khi tưới</h3><p>Mỗi loài có nhu cầu khác nhau. Kiểm tra độ ẩm và đọc ghi chú chăm sóc thay vì tưới theo thói quen.</p></li>
            </ol>
            <a class="room-text-link" href="blog.php">Đọc thêm những câu chuyện chăm cây <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
        </div>
    </section>

    <section class="room-questions landing-content" aria-labelledby="questions-title">
        <div><h2 id="questions-title">Trước khi mang<br><em>một chút xanh về nhà.</em></h2><p>Một vài điều bạn có thể đang băn khoăn.</p></div>
        <div class="room-answers">
            <details><summary>Tôi chưa từng trồng cây, bắt đầu được không?</summary><p>Được chứ. Chọn điều kiện ánh sáng và vị trí ở phần gợi ý phía trên, rồi mở một loài cây để đọc đặc điểm và cách chăm. Hãy bắt đầu với một chậu nhỏ, vừa với không gian và thời gian của bạn.</p></details>
            <details><summary>Phòng ít sáng có trồng cây được không?</summary><p>Một số cây như Lưỡi Hổ hay Kim Tiền có thể chịu ánh sáng yếu hơn. Tuy vậy, cây vẫn cần ánh sáng để phát triển. Nếu phòng không có ánh sáng tự nhiên, bạn cần tìm hiểu thêm về đèn trồng cây.</p></details>
            <details><summary>Tôi có thể mua cây ở đây không?</summary><p>Vũ Điệu Rừng Xanh là nơi tra cứu cây và chia sẻ kinh nghiệm. Website hiện chưa hỗ trợ mua bán, đặt hàng hay giao cây.</p></details>
            <details><summary>Làm sao để lưu lại những cây mình thích?</summary><p><a href="register.php">Tạo tài khoản</a> hoặc <a href="login.php">đăng nhập</a>, sau đó bấm trái tim trên cây bạn muốn giữ lại. Bạn có thể xem lại trong <a href="favorites.php">bộ sưu tập yêu thích</a> và chia sẻ câu chuyện ở Blog.</p></details>
        </div>
    </section>

    <section class="room-invitation" aria-labelledby="invitation-title">
        <svg class="invitation-leaf" viewBox="0 0 64 80" fill="none" aria-hidden="true"><path d="M14 70C14 40 47 35 49 9c17 28 4 51-17 48M14 70c-8-18-9-34 3-46 2 17 17 19 15 33M14 70c10-20 22-27 31-41"/></svg>
        <h2 id="invitation-title">Nhà vẫn là nhà.<br><em>Chỉ là xanh hơn một chút.</em></h2>
        <a class="room-button" href="<?= e($indoorUrl) ?>">Tìm chiếc lá đầu tiên <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a>
        <a class="invitation-catalog" href="collection.php">Còn <?= (int) $catalog['total'] ?> loài cây để bạn khám phá trong bộ sưu tập</a>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
