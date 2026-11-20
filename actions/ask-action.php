<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/sau-agent.php';
require_once __DIR__ . '/../includes/plant-advisor.php';
require_once __DIR__ . '/../config/ai.php';
require_post();
require_csrf();
$user = require_login(app_url('ask.php'));

$question = trim(preg_replace('/\s+/u', ' ', request_string($_POST, 'question')) ?? '');
$respond = static function (int $status, array $data) use ($question): void {
    unset($data['route'], $data['general']);
    // Only links to the user's own notebook are shown (criterion 28 asks for them); plants, blog and web stay internal.
    if (isset($data['sources'])) {
        $data['sources'] = array_values(array_filter($data['sources'], static fn(array $source): bool => $source['kind'] === 'note'));
    }
    if (wants_json()) {
        json_response($data, $status);
    }
    // Without JavaScript the page shows the latest exchange from the session.
    $_SESSION['ask_last'] = ['question' => $question] + $data;
    redirect_to(app_url('ask.php') . '#ask-answer');
};

$length = mb_strlen($question, 'UTF-8');
if ($length < 2 || $length > 500) {
    $respond(422, ['message' => 'Câu hỏi cần từ 2 đến 500 ký tự.']);
}
$config = load_ai_config();
if (!$config['enabled']) {
    $respond(503, ['message' => 'Tính năng hỏi đáp AI chưa được bật trên máy chủ này.']);
}
$runtime = dirname(__DIR__) . '/.runtime/ask-rate';
if (!advisor_rate_allow($runtime, 'session:' . session_id(), 15, 600)
    || !advisor_rate_allow($runtime, 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 60, 3600)) {
    $respond(429, ['message' => 'Bạn đã hỏi khá nhiều lần. Vui lòng thử lại sau ít phút.']);
}
// A question may take a few model calls and one web search.
set_time_limit(90);

$userId = (int) $user['id'];
$tavilyKey = load_tavily_key();
$context = new SauContext([
    'notes' => static fn(): array => notes_qa_candidates($userId),
    'favorites' => static fn(): array => notes_qa_favorites($userId),
    'catalog' => static fn(): array => list_plants(),
    'public' => static fn(): array => notes_qa_public_posts($userId),
], $tavilyKey === '' ? null : static fn(string $query): ?array => tavily_search($tavilyKey, $query));
$history = is_array($_SESSION['ask_history'] ?? null) ? $_SESSION['ask_history'] : [];

$started = hrtime(true);
$error = null;
try {
    $result = sau_answer($question, $history, $context,
        static fn(array $messages, array $tools): array => deepseek_chat_tools($config, $messages, $tools));
    if ($result === null) {
        $error = 'model gave no usable answer';
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
    error_log('Sâu AI failed: ' . $error);
    $result = null;
}
// Like the README's TF-IDF fallback: when the model fails, show matching notes and plants instead of an error.
$result ??= sau_fallback($question, $context);
sau_log($userId, $question, $result, $error, (int) ((hrtime(true) - $started) / 1e6));

// Remember the last three exchanges so follow-up questions ("and the other plant?") make sense.
$history[] = ['question' => $question, 'answer' => mb_substr($result['answer'], 0, 600, 'UTF-8')];
$_SESSION['ask_history'] = array_slice($history, -3);
$respond(200, $result);
