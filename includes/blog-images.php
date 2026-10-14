<?php
declare(strict_types=1);

require_once __DIR__ . '/image-uploads.php';

function store_blog_image(mixed $file): ?string
{
    return store_image_upload($file, 'images/blog');
}

function delete_blog_image(?string $path): void
{
    delete_image_upload($path, 'images/blog');
}
