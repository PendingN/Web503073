<?php
declare(strict_types=1);

// Standalone checks for Sâu AI (retrieval + tool-calling agent): no database, network, or API key required.
// The model is replaced by a scripted fake that returns the tool calls a real model would make.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/sau-agent.php';

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

$note = static fn(int $id, string $title, string $text, string $date): array => ['id' => $id, 'slug' => 'note-' . $id, 'title' => $title,
    'tag' => 'Chăm cây', 'text' => $text, 'created_at' => $date . ' 09:00:00', 'author' => 'Lan'];
$notes = [
    $note(1, 'Lịch tưới cây', 'Lưỡi hổ tưới 2 tuần một lần.', '2026-11-18'),
    $note(2, 'Trầu bà bị vàng lá', 'Lá vàng do tưới quá nhiều, đã giảm nước.', '2026-11-10'),
];
$favorites = [['id' => 7, 'name' => 'Cây Lưỡi Hổ', 'category' => 'Cây để bàn', 'description' => 'Chịu hạn.', 'care' => 'Tưới ít.']];
$catalog = [['id' => 9, 'name' => 'Cây Trầu Bà', 'category' => 'Cây treo', 'description' => 'Dễ trồng.', 'care' => 'Ánh sáng tán xạ.'],
    ['id' => 10, 'name' => 'Cây Sen Đá', 'category' => 'Cây để bàn', 'description' => 'Ưa nắng.', 'care' => 'Nắng trực tiếp.']];
$public = [$note(20, 'Kinh nghiệm trồng sen đá', 'Sen đá cần nắng.', '2026-11-01'), $note(21, 'Cà phê sáng', 'Không liên quan.', '2026-11-02')];
$context = static fn(?Closure $web = null): SauContext => new SauContext([
    'notes' => static fn(): array => $notes, 'favorites' => static fn(): array => $favorites,
    'catalog' => static fn(): array => $catalog, 'public' => static fn(): array => $public,
    'blog_overview' => static fn(): array => ['total' => 2, 'tags' => [['id' => 1, 'name' => 'Chăm cây', 'post_count' => 2]],
        'newest' => [$public[1] + ['tags' => []], $public[0] + ['tags' => [['id' => 1, 'name' => 'Chăm cây']]]], 'popular' => [$public[0] + ['tags' => []]]],
], $web);
$call = static fn(string $name, array $arguments = []): array => ['id' => 'c' . random_int(1, 9999), 'type' => 'function',
    'function' => ['name' => $name, 'arguments' => json_encode($arguments, JSON_UNESCAPED_UNICODE)]];
/** A fake model that plays back one assistant message per step and records what it was sent. */
$script = static function (array $steps, ?array &$seen = null): Closure {
    $seen = [];
    return static function (array $messages, array $tools) use (&$steps, &$seen): array {
        $seen[] = ['messages' => $messages, 'tools' => array_map(static fn(array $t): string => $t['function']['name'], $tools)];
        return array_shift($steps) ?? ['content' => ''];
    };
};

// Retrieval helpers: accent-insensitive terms, best match first.
check(notes_qa_terms('Trầu Bà của tôi bị VÀNG lá?') === ['trau', 'ba', 'bi', 'vang'], 'Terms must drop accents, case and filler words.');
check(notes_qa_select($notes, 'trầu bà vàng lá', 2)[0]['id'] === 2, 'Most relevant note must come first.');

// Answers are short by default; details only on request.
check(str_contains(sau_system_prompt(false), 'mặc định 1 đến 3 câu') && str_contains(sau_system_prompt(false), 'Chỉ trả lời chi tiết khi'), 'Prompt must ask for short answers unless details are requested.');

// Personal data is masked before it reaches the model or the web.
check(sau_mask_personal('Gọi 0912 345 678 hoặc lan@example.com') === 'Gọi [số điện thoại] hoặc [email]', 'Phone numbers and emails must be masked.');

// A normal turn: the model calls two tools, then answers citing what they returned plus one invented ref.
$fake = $script([
    ['tool_calls' => [$call('xem_cay_yeu_thich'), $call('tim_so_tay', ['truy_van' => 'trầu bà vàng lá'])]],
    ['tool_calls' => [$call('tra_loi', ['cau_tra_loi' => 'Bạn thích **Lưỡi Hổ** (plant:7).', 'nguon' => ['plant:7', 'note:2', 'note:99', 'plant:7'], 'kien_thuc_chung' => false])]],
], $seen);
$result = sau_answer('Tôi thích cây nào? SĐT 0912345678', [['question' => 'Trước đó?', 'answer' => 'Lưỡi hổ.']], $context(), $fake);
check($result['answer'] === 'Bạn thích Lưỡi Hổ.', 'Answer must be plain text without refs or Markdown.');
check(array_column($result['sources'], 'url') === ['plant-detail.php?id=7', 'blog-post.php?slug=note-2'], 'Only refs returned by tools are kept, once each.');
check($result['sources'][0]['meta'] === 'Cây yêu thích ♥' && $result['route'] === ['xem_cay_yeu_thich', 'tim_so_tay', 'tra_loi'], 'Favourites are labelled and the route is recorded.');
$firstPrompt = $seen[0]['messages'][1]['content'];
check(str_contains($firstPrompt, '[số điện thoại]') && !str_contains($firstPrompt, '0912345678') && str_contains($firstPrompt, 'Trước đó?'), 'The model sees masked text and recent turns.');
$toolText = static fn(array $messages): string => implode(' ', array_column(array_filter($messages, static fn(array $m): bool => $m['role'] === 'tool'), 'content'));
check(str_contains($toolText($seen[1]['messages']), 'Lá vàng do tưới quá nhiều'), 'Tool results go back to the model.');
check(!in_array('tim_tren_web', $seen[0]['tools'], true), 'Web search is not offered without a Tavily key.');

// Web search through the injected Tavily client; results are cited as external links.
$webQueries = [];
$web = static function (string $query) use (&$webQueries): array {
    $webQueries[] = $query;
    return [['title' => 'Cách giâm cành trầu bà', 'url' => 'https://example.org/trau-ba', 'content' => 'Cắt cành có mắt...']];
};
$fake = $script([
    ['tool_calls' => [$call('tim_tren_web', ['truy_van' => 'giâm cành trầu bà, gọi 0912345678'])]],
    ['tool_calls' => [$call('tra_loi', ['cau_tra_loi' => 'Cắt cành có mắt rồi giâm nước.', 'nguon' => ['web:1:1'], 'kien_thuc_chung' => false])]],
], $seen);
$result = sau_answer('Giâm cành trầu bà thế nào?', [], $context($web), $fake);
check(in_array('tim_tren_web', $seen[0]['tools'], true), 'Web search is offered with a Tavily key.');
check($webQueries === ['giâm cành trầu bà, gọi [số điện thoại]'], 'Web queries must be masked.');
check($result['sources'][0]['kind'] === 'web' && $result['sources'][0]['url'] === 'https://example.org/trau-ba' && $result['sources'][0]['meta'] === 'example.org', 'Web results become external sources.');

// Out of scope: the fixed reply, never model text.
$result = sau_answer('Giá vàng hôm nay?', [], $context(), $script([['tool_calls' => [$call('ngoai_pham_vi')]]]));
check($result['answer'] === SAU_OUT_OF_SCOPE && $result['sources'] === [], 'Out-of-scope questions get the fixed reply.');

// Plain text instead of tra_loi is accepted without sources; unknown tools are reported back to the model.
$result = sau_answer('Sen đá cần gì?', [], $context(), $script([['content' => 'Sen đá cần **nhiều nắng**.']]));
check($result['answer'] === 'Sen đá cần nhiều nắng.' && $result['sources'] === [] && $result['general'], 'Text answers are kept but marked general.');
$fake = $script([
    ['tool_calls' => [$call('xoa_du_lieu')]],
    ['tool_calls' => [$call('tra_loi', ['cau_tra_loi' => 'Xong.', 'nguon' => [], 'kien_thuc_chung' => true])]],
], $seen);
sau_answer('Thử', [], $context(), $fake);
check(str_contains($toolText($seen[1]['messages']), 'Công cụ không tồn tại'), 'Unknown tools must not run.');

// The loop ends: after the step limit only the answer tools are offered, and a model that never answers returns null.
$looping = $script(array_fill(0, SAU_MAX_STEPS, ['tool_calls' => [$call('tim_blog', ['truy_van' => 'sen đá'])]]), $seen);
check(sau_answer('Lặp', [], $context(), $looping) === null, 'An endless tool loop must stop.');
check($seen[SAU_MAX_STEPS - 1]['tools'] === ['tra_loi', 'ngoai_pham_vi'], 'The last step only offers the answer tools.');

// Fallback when the model fails: keyword matches, no technical error text.
$fallback = sau_fallback('trầu bà vàng lá', $context());
check($fallback['found'] && $fallback['sources'][0]['title'] === 'Trầu bà bị vàng lá' && count($fallback['sources']) === 1 && !str_contains($fallback['answer'], 'HTTP'), 'Fallback shows matching notes.');

// Notebook search returns only matching notes; with no match it marks the newest ones as unrelated.
$ctx = $context();
check(array_column($ctx->run('tim_so_tay', ['truy_van' => 'lưỡi hổ'])['ket_qua'], 'ref') === ['note:1'], 'Notebook search returns only matching notes.');
check(str_contains($ctx->run('tim_so_tay', ['truy_van' => 'xương rồng'])['ghi_chu'], 'Không có bài nào khớp'), 'Unmatched searches are flagged.');

// Blog search only returns matching public posts.
$ctx = $context();
$blog = $ctx->run('tim_blog', ['truy_van' => 'sen đá']);
check(count($blog['ket_qua']) === 1 && $blog['ket_qua'][0]['ref'] === 'blog:20', 'Blog search must return only matching public posts.');

// The site is Là Lá La, and Sâu covers the collection, the blog and how to use the site.
check(str_contains(sau_system_prompt(false), '"Là Lá La"') && !str_contains(sau_system_prompt(false), 'Vũ Điệu') && str_contains(SAU_OUT_OF_SCOPE, 'Là Lá La'), 'Sâu must use the site name Là Lá La.');
$ctx = $context();
$guide = $ctx->run('huong_dan_su_dung', ['truy_van' => 'làm sao khoá bài bằng mật khẩu']);
check($guide['ket_qua'][0]['ref'] === 'guide:khoa-bai' && $ctx->sources['guide:khoa-bai']['kind'] === 'guide', 'Usage questions find the matching guide topic.');
check(site_guide_matches('quên mật khẩu')[0]['url'] === 'forgot-password.php', 'Password help points to the forgot-password page.');
check(isset($ctx->run('huong_dan_su_dung', ['truy_van' => 'zzz'])['cac_muc_huong_dan']), 'Unmatched usage questions list the guide topics.');
foreach (site_guide() as $topic) {
    check(is_file(__DIR__ . '/../' . $topic['url']), 'Guide page must exist: ' . $topic['url']);
}
$collection = $ctx->run('tong_quan_bo_suu_tap', []);
check($collection['tong_so_cay'] === 2 && count($collection['theo_loai']) === 2 && $collection['theo_loai'][1]['ten_cay'] === ['Cây Sen Đá'], 'Collection overview counts plants by category.');
$overview = $ctx->run('tong_quan_blog', []);
check($overview['tong_so_bai'] === 2 && $overview['chu_de'][0] === ['ten' => 'Chăm cây', 'so_bai' => 2]
    && array_column($overview['bai_moi_nhat'], 'ref') === ['blog:21', 'blog:20'] && $ctx->sources['blog:20']['kind'] === 'blog', 'Blog overview lists topics and newest posts as sources.');
$fake = $script([
    ['tool_calls' => [$call('huong_dan_su_dung', ['truy_van' => 'chia sẻ bài'])]],
    ['tool_calls' => [$call('tra_loi', ['cau_tra_loi' => 'Mở bài, nhập email ở khung Bảo mật và chia sẻ (guide:chia-se).', 'nguon' => ['guide:chia-se'], 'kien_thuc_chung' => false])]],
]);
$result = sau_answer('Chia sẻ bài thế nào?', [], $context(), $fake);
check($result['answer'] === 'Mở bài, nhập email ở khung Bảo mật và chia sẻ.' && $result['sources'][0]['url'] === 'shared.php', 'How-to answers cite the guide page without leaking its ref.');

fwrite(STDOUT, "Sâu AI checks passed: $checks" . PHP_EOL);
