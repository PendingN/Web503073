<?php
$showSiteFooter = $showSiteFooter ?? true;
$footerTagline = $footerTagline ?? 'Cùng thiên nhiên tạo nên những giá trị xanh bền vững.';
$pageScripts = $pageScripts ?? [];
?>
<?php if ($showSiteFooter): ?>
    <footer class="site-footer">
        <span>Vũ Điệu Rừng Xanh</span>
        <span><?= e($footerTagline) ?></span>
    </footer>
<?php endif; ?>
<?php foreach ($pageScripts as $pageScript): ?><script src="<?= e(asset_url($pageScript)) ?>" defer></script>
<?php endforeach; ?>
<script src="<?= e(asset_url(($bodyClass ?? '') === 'admin-page' ? 'js/admin.js' : 'js/main.js')) ?>" defer></script>
<?php if (($bodyClass ?? '') === 'landing-page'): ?><script src="<?= e(asset_url('js/landing.js')) ?>" defer></script><?php endif; ?>
</body>
</html>

