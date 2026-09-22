<?php
$showSiteFooter = $showSiteFooter ?? true;
$footerTagline = $footerTagline ?? 'Cùng thiên nhiên tạo nên những giá trị xanh bền vững.';
?>
<?php if ($showSiteFooter): ?>
    <footer class="site-footer">
        <span>Vũ Điệu Rừng Xanh</span>
        <span><?= e($footerTagline) ?></span>
    </footer>
<?php endif; ?>
<script src="js/main.js" defer></script>
</body>
</html>

