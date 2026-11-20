<?php
declare(strict_types=1);

require_once __DIR__ . '/notes-qa.php';

// Sâu AI: a small tool-calling agent. The model decides which tools to call (notes, favourite plants,
// catalog, public blog, web search through Tavily), PHP runs them, and the model finishes with the
// tra_loi tool. Every tool result carries a reference such as "note:12" or "web:2"; the final answer
// may only cite references that a tool really returned in this request, so the model cannot invent sources.
// Out-of-scope questions get a fixed reply written here, not by the model.

const SAU_MAX_STEPS = 4;
const SAU_NOTE_CHARS = 1500;
const SAU_WEB_CHARS = 700;
const SAU_OUT_OF_SCOPE = 'Mình là Sâu AI của Vũ Điệu Rừng Xanh nên chỉ trả lời về cây cảnh, cách chăm cây và sổ tay của bạn thôi. Bạn hỏi mình về cây nhé! 🌿';

/** Masks personal data before anything reaches the model or the web search (phone, email, card or ID numbers). */
function sau_mask_personal(string $text): string
{
    $text = preg_replace('/[\w.+-]+@[\w-]+(?:\.[\w-]+)+/u', '[email]', $text) ?? $text;
    $text = preg_replace('/(?<!\d)(?:\d[ .-]?){12,19}(?!\d)/u', '[số thẻ/giấy tờ]', $text) ?? $text;
    return preg_replace('/(?<!\d)(?:\+?84|0)(?:[ .-]?\d){8,10}(?!\d)/u', '[số điện thoại]', $text) ?? $text;
}

function sau_system_prompt(bool $webEnabled): string
{
    $web = $webEnabled
        ? '- tim_tren_web: tìm trên Internet cho câu hỏi chung về cây cảnh mà dữ liệu của website không có. Chỉ đưa vào truy vấn tên cây và vấn đề, không đưa dữ liệu cá nhân.'
        : '- (Không có tìm kiếm web.) Với câu hỏi chung mà dữ liệu website không có, được trả lời bằng kiến thức phổ biến, đáng tin cậy và đặt kien_thuc_chung = true.';
    return <<<PROMPT
Bạn là "Sâu AI", trợ lý thân thiện của website cây cảnh "Vũ Điệu Rừng Xanh". Người dùng hỏi về cây cảnh, cách chăm cây và sổ tay của họ.
Hãy gọi công cụ để lấy dữ liệu trước khi trả lời. Có thể gọi nhiều công cụ trong một lượt.
- tim_so_tay: sổ tay của người dùng (bài họ viết và bài được chia sẻ). Nguồn chính cho mọi câu hỏi về việc họ đã làm, đã ghi.
- xem_cay_yeu_thich: các cây người dùng đã bấm yêu thích. Dùng cho câu hỏi về sở thích ("tôi thích cây nào").
- tra_danh_muc_cay: thông tin cây trong danh mục của website (mô tả, cách chăm).
- tim_blog: bài blog công khai của cộng đồng.
{$web}
Khi đủ thông tin, gọi tra_loi với câu trả lời và danh sách mã nguồn ("ref") đã dùng, lấy đúng từ kết quả công cụ.
Nếu câu hỏi không liên quan tới cây cảnh, chăm sóc cây hay website, gọi ngoai_pham_vi.
Quy tắc:
- Không bao giờ bịa về dữ liệu riêng của người dùng: sổ tay hay cây yêu thích không có thì nói là chưa có.
- Trả lời thẳng vào trọng tâm: mặc định 1 đến 3 câu ngắn (dưới 60 từ), nêu ngay điều người dùng cần, không chào hỏi, không mở bài, không nhắc lại câu hỏi, không liệt kê thêm điều họ không hỏi.
- Chỉ trả lời chi tiết khi người dùng yêu cầu rõ (ví dụ "chi tiết", "cụ thể", "từng bước", "hướng dẫn", "giải thích kỹ", "tại sao"); khi đó mới dùng gạch đầu dòng "- ".
- Viết tiếng Việt, thân thiện, văn bản thường (không dùng Markdown như ** hay #).
- Không nhắc mã ref trong câu trả lời; gọi nguồn bằng tên bài hoặc tên cây.
- Phần "conversation" trong tin nhắn là vài lượt hỏi đáp trước, dùng để hiểu câu hỏi nối tiếp.
PROMPT;
}

/** OpenAI-compatible function definitions (DeepSeek accepts the same format). */
function sau_tools(bool $webEnabled): array
{
    $tool = static fn(string $name, string $description, array $properties = [], array $required = []): array => [
        'type' => 'function',
        'function' => ['name' => $name, 'description' => $description, 'parameters' => [
            'type' => 'object', 'properties' => (object) $properties, 'required' => $required, 'additionalProperties' => false,
        ]],
    ];
    $query = ['truy_van' => ['type' => 'string', 'description' => 'Từ khóa tìm kiếm tiếng Việt, ví dụ "trầu bà vàng lá".']];
    $tools = [
        $tool('tim_so_tay', 'Tìm trong sổ tay của người dùng: bài họ viết và bài được chia sẻ với họ.', $query, ['truy_van']),
        $tool('xem_cay_yeu_thich', 'Danh sách cây người dùng đã yêu thích, kèm mô tả và cách chăm.'),
        $tool('tra_danh_muc_cay', 'Tra cây trong danh mục của website theo tên, nhu cầu ánh sáng, cách chăm.', $query, ['truy_van']),
        $tool('tim_blog', 'Tìm bài blog công khai của cộng đồng.', $query, ['truy_van']),
    ];
    if ($webEnabled) {
        $tools[] = $tool('tim_tren_web', 'Tìm thông tin chăm sóc cây cảnh trên Internet.', $query, ['truy_van']);
    }
    $tools[] = $tool('tra_loi', 'Gửi câu trả lời cuối cùng cho người dùng.', [
        'cau_tra_loi' => ['type' => 'string', 'description' => 'Câu trả lời tiếng Việt, văn bản thường.'],
        'nguon' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Các mã ref đã dùng, lấy từ kết quả công cụ.'],
        'kien_thuc_chung' => ['type' => 'boolean', 'description' => 'true nếu có dùng kiến thức chung ngoài kết quả công cụ.'],
    ], ['cau_tra_loi', 'nguon', 'kien_thuc_chung']);
    $tools[] = $tool('ngoai_pham_vi', 'Câu hỏi không liên quan tới cây cảnh, chăm sóc cây hay website.');
    return $tools;
}

function sau_post_item(array $post, string $kind): array
{
    $text = $post['text'];
    if (mb_strlen($text, 'UTF-8') > SAU_NOTE_CHARS) {
        $text = mb_substr($text, 0, SAU_NOTE_CHARS, 'UTF-8') . ' […]';
    }
    return ['ref' => $kind . ':' . (int) $post['id'], 'title' => $post['title'], 'tag' => $post['tag'],
        'date' => date('d/m/Y', strtotime((string) $post['created_at'])), 'author' => $post['author'], 'content' => $text];
}

function sau_plant_item(array $plant): array
{
    return ['ref' => 'plant:' . (int) $plant['id'], 'name' => $plant['name'], 'category' => $plant['category'],
        'description' => mb_substr((string) $plant['description'], 0, 400, 'UTF-8'), 'care' => mb_substr((string) $plant['care'], 0, 600, 'UTF-8')];
}

/**
 * Data the tools read. Loaded lazily so a question that needs only favourites never queries posts.
 * Keys: notes, favorites, catalog, public (callables returning arrays), web (callable(string): array|null).
 */
final class SauContext
{
    /** @var array<string, array> */
    private array $cache = [];
    /** Every reference a tool returned, with how to link it: ref => source. */
    public array $sources = [];
    /** Tool names in the order they ran, for the log. */
    public array $route = [];

    public function __construct(private array $loaders, private ?Closure $webSearch)
    {
    }

    public function webEnabled(): bool
    {
        return $this->webSearch !== null;
    }

    private function load(string $key): array
    {
        return $this->cache[$key] ??= ($this->loaders[$key])();
    }

    /** Runs one tool and returns what the model sees. */
    public function run(string $name, array $arguments): array
    {
        $this->route[] = $name;
        $query = sau_mask_personal(trim((string) ($arguments['truy_van'] ?? '')));
        switch ($name) {
            case 'tim_so_tay':
                $notes = $this->load('notes');
                if ($notes === []) {
                    return ['ket_qua' => [], 'ghi_chu' => 'Sổ tay chưa có bài nào.'];
                }
                $matches = notes_qa_select($notes, $query, NOTES_QA_CONTEXT_POSTS, false);
                if ($matches === []) {
                    // Nothing matches the words: show the newest notes, marked so the model does not cite them as evidence.
                    $newest = array_map(fn(array $post): array => $this->remember(sau_post_item($post, 'note'), $post, 'note'), array_slice($notes, 0, 3));
                    return ['ket_qua' => $newest, 'ghi_chu' => 'Không có bài nào khớp từ khóa; đây chỉ là các bài mới nhất, chỉ trích nếu thật sự liên quan.'];
                }
                return ['ket_qua' => array_map(fn(array $post): array => $this->remember(sau_post_item($post, 'note'), $post, 'note'), $matches)];
            case 'xem_cay_yeu_thich':
                $items = array_map(fn(array $plant): array => $this->remember(sau_plant_item($plant), $plant, 'favorite'), $this->load('favorites'));
                return $items === [] ? ['ket_qua' => [], 'ghi_chu' => 'Người dùng chưa yêu thích cây nào.'] : ['ket_qua' => $items];
            case 'tra_danh_muc_cay':
                $catalog = $this->load('catalog');
                $matches = notes_qa_catalog_matches($catalog, $query, 5);
                $items = array_map(fn(array $plant): array => $this->remember(sau_plant_item($plant), $plant, 'plant'), $matches);
                // Without a match the model still sees every plant name, so it can try a better query.
                return $items !== [] ? ['ket_qua' => $items] : ['ket_qua' => [], 'ten_cac_cay' => array_column($catalog, 'name')];
            case 'tim_blog':
                $items = array_map(fn(array $post): array => $this->remember(sau_post_item($post, 'blog'), $post, 'blog'),
                    notes_qa_select($this->load('public'), $query, 3, false));
                return ['ket_qua' => $items];
            case 'tim_tren_web':
                if ($this->webSearch === null || $query === '') {
                    return ['loi' => 'Tìm kiếm web không khả dụng.'];
                }
                $items = [];
                foreach (array_slice(($this->webSearch)($query) ?? [], 0, 5) as $index => $result) {
                    $ref = 'web:' . ($index + 1) . ':' . count($this->route);
                    $this->sources[$ref] = ['kind' => 'web', 'url' => $result['url'], 'title' => $result['title'],
                        'meta' => parse_url($result['url'], PHP_URL_HOST) ?: 'Web'];
                    $items[] = ['ref' => $ref, 'title' => $result['title'], 'url' => $result['url'],
                        'content' => mb_substr($result['content'], 0, SAU_WEB_CHARS, 'UTF-8')];
                }
                return ['ket_qua' => $items];
            default:
                return ['loi' => 'Công cụ không tồn tại.'];
        }
    }

    private function remember(array $item, array $row, string $kind): array
    {
        if ($kind === 'note' || $kind === 'blog') {
            $this->sources[$item['ref']] = ['kind' => $kind === 'note' ? 'note' : 'blog', 'url' => 'blog-post.php?slug=' . rawurlencode($row['slug']), 'title' => $row['title'],
                'meta' => ($kind === 'blog' ? 'Blog · ' : '') . $row['tag'] . ' · ' . date('d/m/Y', strtotime((string) $row['created_at']))];
        } else {
            $this->sources[$item['ref']] = ['kind' => 'plant', 'url' => 'plant-detail.php?id=' . (int) $row['id'], 'title' => $row['name'],
                'meta' => $kind === 'favorite' ? 'Cây yêu thích ♥' : $row['category']];
        }
        return $item;
    }
}

/** Plain-text answer: Markdown emphasis and leaked references are removed. */
function sau_clean_answer(string $answer): string
{
    $answer = preg_replace(['/\*\*(.+?)\*\*/su', '/\s*\(?\b(?:note|plant|blog|web)(?::\d+)+\)?/u', '/^#+\s*/mu'], ['$1', '', ''], trim($answer));
    return mb_substr(trim((string) $answer), 0, 3000, 'UTF-8');
}

/**
 * Runs the agent. $chat receives the message list and tool definitions and returns the assistant message
 * (OpenAI format: content and/or tool_calls). Returns null when the model could not produce an answer.
 *
 * @param callable(array, array): array $chat
 * @return array{answer: string, found: bool, general: bool, sources: list<array>, route: list<string>}|null
 */
function sau_answer(string $question, array $history, SauContext $context, callable $chat): ?array
{
    $conversation = array_map(static fn(array $turn): array => [
        'hoi' => sau_mask_personal((string) $turn['question']), 'dap' => sau_mask_personal((string) $turn['answer']),
    ], array_slice($history, -3));
    $messages = [
        ['role' => 'system', 'content' => sau_system_prompt($context->webEnabled())],
        ['role' => 'user', 'content' => json_encode(['conversation' => $conversation, 'cau_hoi' => sau_mask_personal($question)], JSON_UNESCAPED_UNICODE)],
    ];
    $tools = sau_tools($context->webEnabled());
    for ($step = 0; $step < SAU_MAX_STEPS; $step++) {
        // The last round only offers the answer tools, so the loop always ends with an answer.
        $offered = $step === SAU_MAX_STEPS - 1
            ? array_values(array_filter($tools, static fn(array $t): bool => in_array($t['function']['name'], ['tra_loi', 'ngoai_pham_vi'], true)))
            : $tools;
        $message = $chat($messages, $offered);
        $calls = is_array($message['tool_calls'] ?? null) ? $message['tool_calls'] : [];
        if ($calls === []) {
            // The model wrote text instead of calling tra_loi: accept it, but without sources.
            $text = is_string($message['content'] ?? null) ? sau_clean_answer($message['content']) : '';
            return $text === '' ? null : ['answer' => $text, 'found' => false, 'general' => true, 'sources' => [], 'route' => $context->route];
        }
        $messages[] = ['role' => 'assistant', 'content' => $message['content'] ?? '', 'tool_calls' => $calls];
        foreach ($calls as $call) {
            $name = (string) ($call['function']['name'] ?? '');
            $arguments = json_decode((string) ($call['function']['arguments'] ?? '{}'), true);
            $arguments = is_array($arguments) ? $arguments : [];
            if ($name === 'ngoai_pham_vi') {
                $context->route[] = $name;
                return ['answer' => SAU_OUT_OF_SCOPE, 'found' => false, 'general' => false, 'sources' => [], 'route' => $context->route];
            }
            if ($name === 'tra_loi') {
                $context->route[] = $name;
                $answer = sau_clean_answer((string) ($arguments['cau_tra_loi'] ?? ''));
                if ($answer === '') {
                    return null;
                }
                $sources = [];
                foreach (is_array($arguments['nguon'] ?? null) ? $arguments['nguon'] : [] as $ref) {
                    // Only references that a tool returned in this request are accepted.
                    if (is_string($ref) && isset($context->sources[$ref]) && !isset($sources[$ref])) {
                        $sources[$ref] = $context->sources[$ref];
                    }
                }
                return ['answer' => $answer, 'found' => $sources !== [], 'general' => ($arguments['kien_thuc_chung'] ?? false) === true,
                    'sources' => array_values($sources), 'route' => $context->route];
            }
            $result = $context->run($name, $arguments);
            $messages[] = ['role' => 'tool', 'tool_call_id' => (string) ($call['id'] ?? ''), 'content' => json_encode($result, JSON_UNESCAPED_UNICODE)];
        }
    }
    return null;
}

/** Used when the model fails: keyword matches only, never an error message. */
function sau_fallback(string $question, SauContext $context): array
{
    $context->route[] = 'fallback';
    $context->run('tim_so_tay', ['truy_van' => $question]);
    $sources = array_slice(array_values(array_filter($context->sources, static fn(array $s): bool => $s['kind'] === 'note')), 0, 3);
    $answer = $sources === []
        ? 'Sâu đang hơi mệt nên chưa trả lời được. Bạn thử hỏi lại sau ít phút nhé.'
        : 'Sâu chưa soạn được câu trả lời đầy đủ, nhưng đây là những bài trong sổ tay có thể liên quan:';
    return ['answer' => $answer, 'found' => $sources !== [], 'general' => false, 'sources' => $sources, 'route' => $context->route];
}

/** One DeepSeek chat completion with tools. Returns the assistant message. */
function deepseek_chat_tools(array $config, array $messages, array $tools): array
{
    $curl = curl_init(rtrim($config['base_url'], '/') . '/chat/completions');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $config['api_key']],
        CURLOPT_POSTFIELDS => json_encode(['model' => $config['model'], 'messages' => $messages, 'tools' => $tools,
            'tool_choice' => 'auto', 'max_tokens' => 1500, 'stream' => false], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false || $status !== 200) {
        throw new RuntimeException('DeepSeek request failed: ' . ($body === false ? $error : 'HTTP ' . $status));
    }
    $message = json_decode((string) $body, true)['choices'][0]['message'] ?? null;
    if (!is_array($message)) {
        throw new RuntimeException('DeepSeek returned no message.');
    }
    return $message;
}

/** Tavily web search. Returns [{title, url, content}] or null when the search failed. */
function tavily_search(string $apiKey, string $query): ?array
{
    $curl = curl_init('https://api.tavily.com/search');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_POSTFIELDS => json_encode(['query' => $query, 'search_depth' => 'basic', 'max_results' => 5,
            'include_answer' => false], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $status !== 200) {
        error_log('Tavily search failed: ' . ($body === false ? 'network' : 'HTTP ' . $status));
        return null;
    }
    $results = [];
    foreach (json_decode((string) $body, true)['results'] ?? [] as $result) {
        $url = (string) ($result['url'] ?? '');
        if (preg_match('#^https?://#i', $url) && is_string($result['title'] ?? null)) {
            $results[] = ['title' => mb_substr($result['title'], 0, 200, 'UTF-8'), 'url' => $url, 'content' => (string) ($result['content'] ?? '')];
        }
    }
    return $results;
}

/** Records one question for monitoring (masked text, tools used, error). Missing table is ignored. */
function sau_log(int $userId, string $question, ?array $result, ?string $error, int $milliseconds): void
{
    try {
        db()->prepare('INSERT INTO ai_query_logs (user_id, question, route, source_count, error, duration_ms) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$userId, mb_substr(sau_mask_personal($question), 0, 500, 'UTF-8'), implode(',', $result['route'] ?? []),
                count($result['sources'] ?? []), $error === null ? null : mb_substr($error, 0, 255, 'UTF-8'), $milliseconds]);
    } catch (PDOException $exception) {
        if ((int) ($exception->errorInfo[1] ?? 0) !== 1146) {
            throw $exception;
        }
    }
}
