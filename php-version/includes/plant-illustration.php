<?php
function render_plant_illustration(string $variant, string $className = ''): void
{
    $allowed = ['water', 'sun', 'grass', 'tree', 'pot', 'sprout', 'leaf'];
    if (!in_array($variant, $allowed, true)) {
        $variant = 'leaf';
    }
    ?>
    <svg class="plant-illustration <?= e($className) ?>" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
        <circle cx="60" cy="60" r="46" fill="currentColor" opacity=".12"/>
        <?php if ($variant === 'water'): ?>
            <path d="M60 25c-11 15-18 24-18 34a18 18 0 0 0 36 0c0-10-7-19-18-34Z" fill="currentColor" opacity=".78"/>
            <path d="M39 80c13 6 29 6 42 0" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" opacity=".55"/>
        <?php elseif ($variant === 'sun'): ?>
            <circle cx="60" cy="56" r="20" fill="currentColor" opacity=".72"/>
            <?php for ($index = 0; $index < 8; $index++):
                $angle = ($index * M_PI) / 4;
                $x1 = 60 + cos($angle) * 30;
                $y1 = 56 + sin($angle) * 30;
                $x2 = 60 + cos($angle) * 40;
                $y2 = 56 + sin($angle) * 40;
            ?>
                <line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" stroke="currentColor" stroke-width="4" stroke-linecap="round" opacity=".65"/>
            <?php endfor; ?>
        <?php elseif ($variant === 'grass'): ?>
            <path d="M42 83c6-22 10-33 18-49M60 83c0-24 4-37 10-50M78 83c-4-20-3-31 2-41" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
            <path d="M36 84h48" stroke="currentColor" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        <?php elseif ($variant === 'tree'): ?>
            <path d="M56 56h8v28h-8z" fill="currentColor" opacity=".68"/>
            <path d="M60 24 39 57h12L36 76h48L69 57h12Z" fill="currentColor" opacity=".78"/>
        <?php elseif ($variant === 'pot'): ?>
            <path d="M39 59h42l-5 28H44Z" fill="currentColor" opacity=".62"/>
            <path d="M36 57h48" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
            <path d="M60 57V31M60 39c-11-12-22-3-20 7 8 1 15-1 20-7Zm0 9c11-12 22-3 20 7-8 1-15-1-20-7Z" fill="currentColor" opacity=".82"/>
        <?php elseif ($variant === 'sprout'): ?>
            <path d="M60 84V48" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
            <path d="M60 54c-17-20-34-7-31 8 13 2 23-1 31-8Zm0-5c17-20 34-7 31 8-13 2-23-1-31-8Z" fill="currentColor" opacity=".78"/>
            <path d="M45 86h30" stroke="currentColor" stroke-width="6" stroke-linecap="round" opacity=".5"/>
        <?php else: ?>
            <path d="M58 86c2-17 5-31 14-45" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
            <path d="M67 48c-8-17 7-28 20-23 1 12-6 21-20 23Zm-7 13C48 48 31 55 28 69c12 5 24 1 32-8Z" fill="currentColor" opacity=".8"/>
            <path d="M47 86h28" stroke="currentColor" stroke-width="6" stroke-linecap="round" opacity=".5"/>
        <?php endif; ?>
    </svg>
    <?php
}

