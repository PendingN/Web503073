<?php
declare(strict_types=1);

require_once __DIR__ . '/image-uploads.php';

function blog_cover_url(array $post): string
{
    $fallback = ['Chăm cây' => 'anh22', 'Không gian' => 'anh21', 'Cảm hứng' => 'anh26', 'Mẹo hay' => 'anh29', 'Sống xanh' => 'anh27'][$post['tag']] ?? 'anh23';
    return !empty($post['image_path']) ? $post['image_path'] : 'images/optimized/' . $fallback . '.webp';
}

function store_blog_image(mixed $file): ?string
{
    return store_image_upload($file, 'images/blog');
}

function delete_blog_image(?string $path): void
{
    delete_image_upload($path, 'images/blog');
}
