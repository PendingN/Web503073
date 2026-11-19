<?php
declare(strict_types=1);

require_once __DIR__ . '/image-uploads.php';

function blog_cover_url(array $post): string
{
    if (!empty($post['image_path'])) {
        return $post['image_path'];
    }
    $slug = trim((string) ($post['slug'] ?? ''));
    $curatedCovers = [
        'bat-dau-mot-goc-xanh-tu-dau' => 'anh21',
        '5-dau-hieu-cay-can-quan-tam' => 'anh28',
        'chon-cay-cho-can-phong-it-nang' => 'anh22',
        'ban-cong-nho-thanh-khu-vuon' => 'indoor-room',
        'tuoi-cay-dung-cach-mua-mua' => 'anh58',
        'thay-chau-khi-nao-va-the-nao' => 'anh47',
        'mot-nhip-song-cham-cung-cay' => 'anh49',
    ];
    if ($slug !== '' && isset($curatedCovers[$slug])) {
        return 'images/optimized/' . $curatedCovers[$slug] . '.webp';
    }
    $key = $slug !== '' ? $slug : trim((string) ($post['title'] ?? ''));
    if ($key !== '') {
        $pool = [
            'anh21', 'anh22', 'anh23', 'anh28', 'anh29', 'anh47',
            'anh48', 'anh49', 'anh57', 'anh58', 'indoor-room',
        ];
        $fallback = $pool[abs(crc32($key)) % count($pool)];
    } else {
        $tagMap = [
            'Chăm cây' => 'anh28',
            'Không gian' => 'anh21',
            'Cảm hứng' => 'indoor-room',
            'Mẹo hay' => 'anh58',
            'Sống xanh' => 'anh49',
            'Bài nổi bật' => 'anh22',
        ];
        $fallback = $tagMap[$post['tag'] ?? ''] ?? 'anh57';
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
