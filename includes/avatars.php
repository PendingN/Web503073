<?php
declare(strict_types=1);

require_once __DIR__ . '/image-uploads.php';

function user_avatar_path(array $user): ?string
{
    $path = $user['avatar_path'] ?? null;
    if (!is_string($path) || $path === '') {
        return null;
    }
    return preg_match('#^images/avatars/[a-f0-9]{32}\.(?:jpg|png|webp)$#D', $path) === 1 ? $path : null;
}

function user_initial(string $name): string
{
    $trimmed = trim($name);
    return $trimmed === '' ? '?' : mb_strtoupper(mb_substr($trimmed, 0, 1, 'UTF-8'), 'UTF-8');
}

function store_user_avatar(mixed $file): ?string
{
    return store_image_upload($file, 'images/avatars');
}

function delete_user_avatar(?string $path): void
{
    delete_image_upload($path, 'images/avatars');
}
