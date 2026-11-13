<?php
declare(strict_types=1);

function blog_inline(string $html): string
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $render = static function (DOMNode $node) use (&$render): string {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if (in_array(strtolower($node->nodeName), ['script', 'style', 'iframe', 'object', 'svg', 'math'], true)) {
            return '';
        }
        $children = '';
        foreach ($node->childNodes as $child) {
            $children .= $render($child);
        }
        $tag = strtolower($node->nodeName);
        if ($tag === 'br') {
            return '<br>';
        }
        if (in_array($tag, ['strong', 'b', 'em', 'i', 'u'], true)) {
            return '<' . $tag . '>' . $children . '</' . $tag . '>';
        }
        if ($tag === 'a' && $node instanceof DOMElement) {
            $href = $node->getAttribute('href');
            if (preg_match('#^https?://#i', $href) && filter_var($href, FILTER_VALIDATE_URL)) {
                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" rel="nofollow noopener">' . $children . '</a>';
            }
        }
        return $children . (in_array($tag, ['div', 'p'], true) ? '<br>' : '');
    };
    $result = '';
    foreach ($document->getElementsByTagName('body')->item(0)->childNodes as $child) {
        $result .= $render($child);
    }
    return preg_replace('/(<br>)+$/', '', $result);
}

function blog_blocks(array $post): array
{
    $data = json_decode($post['content_blocks'] ?? '', true);
    if (is_array($data) && ($data['version'] ?? null) === 1 && is_array($data['blocks'] ?? null)) {
        return $data['blocks'];
    }
    return array_map(static fn(string $text): array => ['type' => 'paragraph', 'html' => nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false)], preg_split('/\R\s*\R/u', $post['content'] ?? ''));
}

function blog_block_images(array $blocks): array
{
    return array_values(array_unique(array_column(array_filter($blocks, static fn(array $block): bool => $block['type'] === 'image'), 'src')));
}

function normalize_blog_blocks(string $json, array $allowedImages, array $uploads = []): array
{
    if (strlen($json) > 250000) {
        throw new InvalidArgumentException('Nội dung định dạng quá lớn.');
    }
    $data = json_decode($json, true);
    if (!is_array($data) || ($data['version'] ?? null) !== 1 || !is_array($data['blocks'] ?? null) || count($data['blocks']) > 200) {
        throw new InvalidArgumentException('Nội dung bài viết không hợp lệ (tối đa 200 khối).');
    }
    $result = [];
    foreach ($data['blocks'] as $block) {
        if (!is_array($block) || !is_string($block['type'] ?? null)) {
            throw new InvalidArgumentException('Khối nội dung không hợp lệ.');
        }
        $type = $block['type'];
        $clean = ['type' => $type];
        if (in_array($type, ['paragraph', 'h2', 'h3', 'quote'], true)) {
            if (!is_string($block['html'] ?? null)) {
                throw new InvalidArgumentException('Văn bản không hợp lệ.');
            }
            $clean['html'] = blog_inline($block['html']);
        } elseif ($type === 'ul' || $type === 'ol') {
            if (!is_array($block['items'] ?? null) || count($block['items']) > 100) {
                throw new InvalidArgumentException('Danh sách không hợp lệ.');
            }
            $clean['items'] = [];
            foreach ($block['items'] as $item) {
                if (!is_string($item)) {
                    throw new InvalidArgumentException('Mục danh sách không hợp lệ.');
                }
                $clean['items'][] = blog_inline($item);
            }
        } elseif ($type === 'image') {
            $src = $block['src'] ?? '';
            if (!is_string($src) || (!in_array($src, $allowedImages, true) && !array_key_exists($src, $uploads))) {
                throw new InvalidArgumentException('Ảnh không thuộc bài viết hoặc cần được chọn lại.');
            }
            $clean['src'] = $uploads[$src] ?? $src;
            foreach (['alt', 'caption'] as $field) {
                if (!is_string($block[$field] ?? '')) {
                    throw new InvalidArgumentException('Chú thích ảnh không hợp lệ.');
                }
                $clean[$field] = mb_substr($block[$field] ?? '', 0, 300, 'UTF-8');
            }
        } elseif ($type !== 'divider') {
            throw new InvalidArgumentException('Loại khối không được hỗ trợ.');
        }
        $result[] = $clean;
    }
    return $result;
}

function blog_blocks_text(array $blocks): string
{
    $parts = [];
    foreach ($blocks as $block) {
        $parts[] = html_entity_decode(strip_tags(str_replace('<br>', "\n", $block['html'] ?? implode("\n", $block['items'] ?? [$block['caption'] ?? '']))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return trim(implode("\n\n", $parts));
}

function render_blog_blocks(array $blocks): string
{
    $output = '';
    foreach ($blocks as $block) {
        $type = $block['type'];
        if ($type === 'image') {
            $src = $block['src'];
            if (!preg_match('#^images/blog/[a-f0-9]{32}\.(jpg|png|webp)$#D', $src)) {
                continue;
            }
            $captionHtml = trim((string) ($block['caption'] ?? '')) !== '' ? '<figcaption>' . e((string) $block['caption']) . '</figcaption>' : '';
            $output .= '<figure><img src="' . e(app_url($src)) . '" alt="' . e((string) ($block['alt'] ?? '')) . '" loading="lazy">' . $captionHtml . '</figure>';
        } elseif ($type === 'divider') {
            $output .= '<hr>';
        } elseif ($type === 'ul' || $type === 'ol') {
            $output .= '<' . $type . '>';
            foreach ($block['items'] as $item) {
                $output .= '<li>' . blog_inline($item) . '</li>';
            }
            $output .= '</' . $type . '>';
        } elseif (in_array($type, ['paragraph', 'h2', 'h3', 'quote'], true)) {
            $tag = ['paragraph' => 'p', 'quote' => 'blockquote'][$type] ?? $type;
            $output .= '<' . $tag . '>' . blog_inline($block['html']) . '</' . $tag . '>';
        }
    }
    return $output;
}
