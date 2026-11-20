<?php
declare(strict_types=1);

require_once __DIR__ . '/blog-blocks.php';

// Retrieval for Sâu AI (includes/sau-agent.php): the user's notes (own and shared posts), favourite plants,
// the plant catalog and public blog posts. Matching is accent-insensitive word overlap; the agent's tools call these.

const NOTES_QA_CONTEXT_POSTS = 6;

/** Lowercase, accent-free words of at least two letters, for matching Vietnamese text loosely. */
function notes_qa_terms(string $text): array
{
    // Strip Vietnamese tone marks without the intl extension, which XAMPP does not always enable.
    static $plainLetters = ['à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a', 'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e', 'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i', 'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o', 'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u', 'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y', 'đ' => 'd'];
    $plain = strtr(mb_strtolower($text, 'UTF-8'), $plainLetters);
    preg_match_all('/[\p{L}\p{N}]{2,}/u', $plain, $matches);
    // Very common Vietnamese words carry no meaning for retrieval.
    $stop = ['la', 'va', 'cua', 'co', 'khong', 'nhung', 'cho', 'voi', 'nao', 'gi', 'the', 'thi', 'mot', 'cac', 'duoc', 'trong', 'minh', 'toi', 'ban', 'nay', 'do', 'de', 'khi', 'nhu', 'bao', 'nhieu', 'lam', 'sao'];
    return array_values(array_diff($matches[0], $stop));
}

/** Posts the user may ask about: their own and those shared with them, locked ones only if unlocked now. */
function notes_qa_candidates(int $userId): array
{
    $statement = db()->prepare('SELECT p.id, p.slug, p.title, p.excerpt, p.content, p.content_blocks, p.lock_hash, p.created_at, p.user_id,'
        . ' COALESCE(u.name, \'Người dùng đã xóa\') AS author'
        . ' FROM blog_posts p LEFT JOIN users u ON u.id = p.user_id'
        . ' WHERE p.is_hidden = 0 AND (p.user_id = ? OR EXISTS(SELECT 1 FROM blog_shares s WHERE s.post_id = p.id AND s.user_id = ?))'
        . ' ORDER BY p.created_at DESC, p.id DESC LIMIT 200');
    $statement->execute([$userId, $userId]);
    $posts = [];
    foreach (attach_blog_tags($statement->fetchAll()) as $post) {
        if (!post_is_unlocked($post)) {
            continue;
        }
        $post['text'] = blog_blocks_text(blog_blocks($post));
        $posts[] = $post;
    }
    return $posts;
}

/**
 * Most relevant posts first. Title matches weigh more than body matches; when few posts match,
 * the newest ones fill the remaining slots so questions phrased differently from the notes still get context.
 */
function notes_qa_select(array $posts, string $question, int $limit = NOTES_QA_CONTEXT_POSTS, bool $fillWithNewest = true): array
{
    $terms = array_unique(notes_qa_terms($question));
    $scored = [];
    foreach ($posts as $index => $post) {
        $titleTerms = array_count_values(notes_qa_terms($post['title'] . ' ' . (implode(' ', array_column($post['tags'] ?? [], 'name')) ?: (string) ($post['tag'] ?? ''))));
        $bodyTerms = array_count_values(notes_qa_terms($post['text']));
        $score = 0.0;
        foreach ($terms as $term) {
            $score += 3 * ($titleTerms[$term] ?? 0) + min(5, $bodyTerms[$term] ?? 0);
        }
        $scored[] = ['score' => $score, 'index' => $index];
    }
    usort($scored, static fn(array $a, array $b): int => [$b['score'], $a['index']] <=> [$a['score'], $b['index']]);
    $picked = [];
    foreach ($scored as $item) {
        if ($item['score'] > 0 && count($picked) < $limit) {
            $picked[$item['index']] = $posts[$item['index']];
        }
    }
    foreach ($fillWithNewest ? $posts : [] as $index => $post) {
        if (count($picked) >= $limit) {
            break;
        }
        $picked[$index] ??= $post;
    }
    return array_values($picked);
}

/** Public, unlocked posts by other people; only those matching the question are sent to the AI. */
function notes_qa_public_posts(int $userId): array
{
    $statement = db()->prepare('SELECT p.id, p.slug, p.title, p.excerpt, p.content, p.content_blocks, p.created_at,'
        . ' COALESCE(u.name, \'Ban biên tập\') AS author FROM blog_posts p LEFT JOIN users u ON u.id = p.user_id'
        . ' WHERE p.is_hidden = 0 AND p.is_private = 0 AND p.lock_hash IS NULL AND (p.user_id IS NULL OR p.user_id <> ?)'
        . ' ORDER BY p.created_at DESC, p.id DESC LIMIT 200');
    $statement->execute([$userId]);
    return array_map(static fn(array $post): array => $post + ['text' => blog_blocks_text(blog_blocks($post))], attach_blog_tags($statement->fetchAll()));
}

/** The user's favourite plants, newest first. */
function notes_qa_favorites(int $userId): array
{
    $statement = db()->prepare('SELECT p.id, p.name, p.category, p.description, p.care FROM favorites f'
        . ' JOIN plants p ON p.id = f.plant_id WHERE f.user_id = ? ORDER BY f.created_at DESC, p.id LIMIT 30');
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

/** Catalog plants that match the question's words, for general questions such as "which plant suits a dark room". */
function notes_qa_catalog_matches(array $plants, string $question, int $limit = 4): array
{
    $terms = array_unique(notes_qa_terms($question));
    $scored = [];
    foreach ($plants as $plant) {
        $nameTerms = array_count_values(notes_qa_terms($plant['name'] . ' ' . $plant['category']));
        $bodyTerms = array_count_values(notes_qa_terms($plant['description'] . ' ' . $plant['care']));
        $score = 0;
        foreach ($terms as $term) {
            $score += 3 * ($nameTerms[$term] ?? 0) + min(3, $bodyTerms[$term] ?? 0);
        }
        if ($score > 0) {
            $scored[] = ['score' => $score, 'plant' => $plant];
        }
    }
    usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
    return array_column(array_slice($scored, 0, $limit), 'plant');
}
