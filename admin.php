<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Quản trị';
$bodyClass = 'admin-page';
$flashMessage = take_flash();
$statusClasses = ['Hoàn thành' => 'status-hoan-thanh', 'Đang giao' => 'status-dang-giao', 'Chờ xác nhận' => 'status-cho-xac-nhan'];
require __DIR__ . '/includes/header.php';
?>

<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="index.php"><span>↗</span><strong>VŨ ĐIỆU<br>RỪNG XANH</strong></a>
        <p class="admin-label">KHÔNG GIAN QUẢN TRỊ</p>
        <nav aria-label="Điều hướng quản trị">
            <button type="button" class="is-active" data-admin-nav="overview"><span>⌂</span>Tổng quan</button>
            <button type="button" data-admin-nav="orders"><span>▣</span>Đơn hàng</button>
            <button type="button" data-admin-nav="plants"><span>♧</span>Cây xanh</button>
            <button type="button" data-admin-nav="articles"><span>✎</span>Bài viết</button>
            <button type="button" data-admin-nav="settings"><span>⚙</span>Cài đặt</button>
        </nav>
        <div class="admin-sidebar-foot"><span class="admin-avatar">AD</span><div><strong>Admin demo</strong><small>Quản trị viên</small></div><a href="index.php">↗</a></div>
    </aside>

    <div class="admin-content">
        <header class="admin-topbar"><div><p>THỨ HAI, 14 THÁNG 9, 2026</p><h1>Chào buổi sáng, Admin.</h1></div><div class="admin-actions"><a href="dashboard.php">Xem website ↗</a><button type="button" data-admin-action="Tính năng thêm cây sẽ được mở trong bản tiếp theo.">+ Thêm nội dung</button></div></header>
        <main class="admin-main">
            <?php if ($flashMessage !== null): ?><div class="admin-toast" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <section class="admin-stats" data-admin-section="overview">
                <article><div class="stat-top"><span>Doanh thu tháng</span><i>↗</i></div><strong>12.84M</strong><small>+12.5% so với tháng trước</small></article>
                <article><div class="stat-top"><span>Đơn hàng mới</span><i>▣</i></div><strong>48</strong><small>+8 đơn trong tuần này</small></article>
                <article><div class="stat-top"><span>Khách ghé thăm</span><i>◌</i></div><strong>2,840</strong><small>+18.2% so với tuần trước</small></article>
                <article><div class="stat-top"><span>Cây đang bán</span><i>♧</i></div><strong>40</strong><small>Danh mục đang hoạt động</small></article>
            </section>

            <section class="admin-columns" data-admin-section="overview">
                <article class="admin-panel">
                    <div class="panel-head"><div><p class="eyebrow">HIỆU QUẢ KINH DOANH</p><h2>Doanh thu theo tháng</h2></div><button type="button" data-admin-action="Bộ lọc doanh thu hiện đang ở chế độ demo.">6 tháng qua⌄</button></div>
                    <div class="chart-area" aria-label="Biểu đồ doanh thu 6 tháng">
                        <?php foreach ([62, 74, 48, 82, 68, 91] as $index => $height): ?><div class="chart-column"><div class="chart-bar"><span style="height:<?= $height ?>%"></span></div><small><?= e(['T4', 'T5', 'T6', 'T7', 'T8', 'T9'][$index]) ?></small></div><?php endforeach; ?>
                    </div>
                    <div class="chart-summary"><span><i></i>Doanh thu thực tế</span><strong>12.84M ₫</strong></div>
                </article>
                <article class="admin-panel quick-panel"><div class="panel-head"><div><p class="eyebrow">CẦN XỬ LÝ</p><h2>Việc nhanh</h2></div><span class="count-badge">04</span></div><ul>
                    <li><span>▣</span><div><strong>Đơn cần xác nhận</strong><small>3 đơn mới</small></div><button type="button" data-admin-action="Đang mở danh sách đơn cần xác nhận.">→</button></li>
                    <li><span>✎</span><div><strong>Bài viết nháp</strong><small>1 bài chờ duyệt</small></div><button type="button" data-admin-action="Đang mở kho bài viết nháp.">→</button></li>
                    <li><span>♧</span><div><strong>Cây sắp hết hàng</strong><small>4 sản phẩm</small></div><button type="button" data-admin-action="Đang mở danh sách cây sắp hết hàng.">→</button></li>
                </ul></article>
            </section>

            <section class="admin-panel orders-panel" data-admin-section="orders"><div class="panel-head"><div><p class="eyebrow">GIAO DỊCH GẦN ĐÂY</p><h2>Đơn hàng mới nhất</h2></div><button type="button" data-admin-action="Đã chọn xem toàn bộ đơn hàng.">Xem tất cả →</button></div><div class="admin-table-wrap"><table><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead><tbody>
                <?php foreach ($orders as $order): ?><tr><td><strong><?= e($order['id']) ?></strong></td><td><?= e($order['customer']) ?></td><td><?= e($order['product']) ?></td><td><?= e($order['total']) ?></td><td><span class="order-status <?= e($statusClasses[$order['status']] ?? '') ?>"><?= e($order['status']) ?></span></td><td><button type="button" data-admin-action="Chi tiết <?= e($order['id']) ?> đang ở chế độ demo.">•••</button></td></tr><?php endforeach; ?>
            </tbody></table></div></section>

            <section class="admin-panel editorial-panel" data-admin-section="articles"><div><p class="eyebrow">NỘI DUNG TRANG CHỦ</p><h2>“Bắt đầu một góc xanh từ đâu?”</h2><p>Giữ cho bài viết nổi bật luôn được cập nhật để truyền cảm hứng cho những người làm vườn mới.</p></div><label class="publish-toggle"><input type="checkbox" checked data-publish-toggle><span></span><small data-publish-label>Đang xuất bản</small></label><a href="blog-post.php?slug=bat-dau-mot-goc-xanh-tu-dau">Xem bài ↗</a></section>
        </main>
    </div>
</div>

<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
