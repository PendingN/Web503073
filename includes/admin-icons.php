<?php
declare(strict_types=1);

function admin_icon(string $name, string $class = 'size-5'): void
{
    echo '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="'
        . e(asset_url('images/admin/icons.svg') . '#' . rawurlencode($name)) . '"></use></svg>';
}
