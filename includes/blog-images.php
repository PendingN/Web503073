<?php
declare(strict_types=1);

require_once __DIR__ . '/image-uploads.php';

function blog_cover_url(array $post): string
{
    if (!empty($post['image_path'])) {
        return $post['image_path'];
    }
    $key = trim((string) (($post['slug'] ?? '') !== '' ? $post['slug'] : ($post['title'] ?? '')));
    if ($key !== '') {
        $pool = [
            'anh21', 'anh22', 'anh23', 'anh24', 'anh25', 'anh26',
            'anh27', 'anh28', 'anh29', 'anh30', 'anh46', 'anh47',
            'anh53', 'anh57', 'anh58', 'anh62',
        ];
        $fallback = $pool[abs(crc32($key)) % count($pool)];
    } else {
        $tagMap = [
            'Chăm cây' => 'anh22',
            'Không gian' => 'anh21',
            'Cảm hứng' => 'anh26',
            'Mẹo hay' => 'anh29',
            'Sống xanh' => 'anh27',
        ];
        $fallback = $tagMap[$post['tag'] ?? ''] ?? 'anh23';
    }
    return 'images/optimized/' . $fallback . '.webp';
}

function store_blog_image(mixed $file): ?string
{
    return store_image_upload($file, 'images/blog');
}

function delete_blog_image(?string $path): void
{
    delete_image_upload($path, 'images/blog');
}
