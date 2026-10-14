<?php
declare(strict_types=1);

// Stores an uploaded JPG, PNG or WebP under $directory (relative to the project root).
function store_image_upload(mixed $file, string $directory): ?string
{
    if ($file === null || (is_array($file) && ($file['error'] ?? null) === UPLOAD_ERR_NO_FILE)) {
        return null;
    }
    if (!is_array($file) || !is_int($file['error'] ?? null)) {
        throw new InvalidArgumentException('Ảnh tải lên không hợp lệ.');
    }
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        throw new InvalidArgumentException('Ảnh không được vượt quá 2 MB.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Không tải được ảnh. Vui lòng chọn lại ảnh.');
    }
    $temporary = $file['tmp_name'];
    if (filesize($temporary) > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Ảnh không được vượt quá 2 MB.');
    }
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
    $size = @getimagesize($temporary);
    if (!isset($extensions[$mime]) || $size === false || ($size['mime'] ?? '') !== $mime) {
        throw new InvalidArgumentException('Vui lòng chọn ảnh JPG, PNG hoặc WebP hợp lệ.');
    }
    if ($size[0] > 8000 || $size[1] > 8000 || $size[0] * $size[1] > 20000000) {
        throw new InvalidArgumentException('Ảnh tối đa 20 triệu điểm ảnh, mỗi chiều không quá 8.000 px.');
    }
    $absoluteDirectory = dirname(__DIR__) . '/' . $directory;
    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0755, true) && !is_dir($absoluteDirectory)) {
        throw new RuntimeException('Cannot create image directory: ' . $directory);
    }
    $path = $directory . '/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($temporary, dirname(__DIR__) . '/' . $path)) {
        throw new RuntimeException('Cannot store image in ' . $directory . '. Check directory permissions.');
    }
    return $path;
}

// Deletes only files this application created in $directory, never seed images.
function delete_image_upload(?string $path, string $directory): void
{
    if ($path !== null && preg_match('#^' . preg_quote($directory, '#') . '/[a-f0-9]{32}\.(?:jpg|png|webp)$#D', $path)) {
        $file = dirname(__DIR__) . '/' . $path;
        if (is_file($file) && !unlink($file)) {
            error_log('Cannot remove image: ' . $path);
        }
    }
}
