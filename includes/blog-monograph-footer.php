<?php
declare(strict_types=1);
$footerUser = current_user();
?>
<footer class="mono-footer" aria-label="Thông tin cuối trang Sổ Tay Thực Vật">
    <div class="mono-footer-inner">
        <div class="mono-footer-brand-col">
            <a class="mono-footer-brand" href="index.php">
                <svg class="mono-footer-leaf" viewBox="0 0 32 40" fill="none" aria-hidden="true">
                    <path d="M6 35C6 20 24 18 25 4c9 15 1 28-11 25M6 35C2 25 2 17 8 12c1 9 10 10 6 17M6 35c5-10 13-15 16-20"/>
                </svg>
                <span>Là Lá La</span>
            </a>
            <p class="mono-footer-note">Sổ tay lưu trữ kinh nghiệm chăm cây, đọc ánh sáng và cảm hứng sống xanh cùng cộng đồng. Trang web chỉ phục vụ tra cứu và chia sẻ, không hỗ trợ mua bán.</p>
        </div>
        <nav class="mono-footer-nav" aria-label="Liên kết nhanh">
            <div class="mono-footer-group">
                <h3>Chuyên mục</h3>
                <ul>
                    <li><a href="blog.php#blog-content">Tất cả bài viết</a></li>
                    <li><a href="blog.php?search=Ch%C4%83m+c%C3%A2y#blog-content">Chăm cây</a></li>
                    <li><a href="blog.php?search=Kh%C3%B4ng+gian#blog-content">Không gian</a></li>
                    <li><a href="blog.php?search=M%E1%BA%B9o+hay#blog-content">Mẹo hay</a></li>
                </ul>
            </div>
            <div class="mono-footer-group">
                <h3>Khám phá</h3>
                <ul>
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="collection.php">Bộ sưu tập cây</a></li>
                    <li><a href="plant-advisor.php">Gợi ý chọn cây</a></li>
                    <li><a href="blog-editor.php">Viết bài chia sẻ</a></li>
                </ul>
            </div>
            <div class="mono-footer-group">
                <h3>Góc cá nhân</h3>
                <ul>
                    <?php if ($footerUser === null): ?>
                        <li><a href="login.php">Đăng nhập</a></li>
                        <li><a href="register.php">Tạo tài khoản</a></li>
                    <?php else: ?>
                        <li><a href="blog.php?view=saved#blog-content">Bài viết đã lưu</a></li>
                        <li><a href="blog.php?view=mine#blog-content">Bài viết của tôi</a></li>
                        <li><a href="profile.php">Trang cá nhân</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </div>
    <div class="mono-footer-bottom">
        <span>Sổ Tay Thực Vật — Là Lá La</span>
        <span>Cùng thiên nhiên tạo nên những giá trị xanh bền vững.</span>
    </div>
</footer>
