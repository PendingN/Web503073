<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/notes-qa.php';
$user = require_login();

$pageTitle = 'Hỏi AI về sổ tay';
$bodyClass = 'profile-page';
$activePage = 'ask';
$pageScripts = ['js/ask.js'];
$last = $_SESSION['ask_last'] ?? null;
unset($_SESSION['ask_last']);
$noteCount = count(notes_qa_candidates((int) $user['id']));
$examples = ['Mình đã ghi gì về cách xử lý lá vàng?', 'Bộ sưu tập có những cây để bàn nào?', 'Blog có những chủ đề gì?', 'Làm sao chia sẻ bài cho bạn bè?'];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

        <main id="main-content" class="ask-page" tabindex="-1" data-ask-root>
            <header class="ask-head">
                <h1>Hỏi AI về sổ tay của bạn</h1>
                <p>Sâu đọc <strong><?= $noteCount ?></strong> bài trong sổ tay, cây bạn yêu thích, bộ sưu tập cây, blog và hướng dẫn dùng Là Lá La, rồi trả lời bạn.</p>
            </header>

            <form class="ask-form" method="post" action="actions/ask-action.php" data-ask-form>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="sr-only" for="ask-question">Câu hỏi của bạn</label>
                <textarea id="ask-question" name="question" rows="2" minlength="2" maxlength="500" required placeholder="Ví dụ: Tháng này mình đã bón phân cho cây nào?"><?= e($last['question'] ?? '') ?></textarea>
                <button class="profile-write" type="submit" data-ask-button>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                    Hỏi
                </button>
            </form>
            <div class="ask-examples" aria-label="Câu hỏi gợi ý">
                <?php foreach ($examples as $example): ?><button type="button" data-ask-example><?= e($example) ?></button><?php endforeach; ?>
            </div>

            <p class="ask-status" role="status" aria-live="polite" data-ask-status><?= isset($last['message']) ? e($last['message']) : '' ?></p>

            <section class="ask-thread" id="ask-answer" aria-label="Câu trả lời" data-ask-thread>
                <?php if (isset($last['answer'])): ?>
                    <article class="ask-turn">
                        <p class="ask-question"><?= e($last['question']) ?></p>
                        <div class="ask-answer"><?= nl2br(e($last['answer'])) ?></div>
                        <?php if (($last['sources'] ?? []) !== []): ?>
                            <p class="ask-notes"><?php foreach ($last['sources'] as $source): ?><a href="<?= e($source['url']) ?>"><?= ['blog' => '📰', 'plant' => '🌿', 'guide' => '🧭'][$source['kind'] ?? ''] ?? '📝' ?> <?= e($source['title']) ?></a><?php endforeach; ?></p>
                        <?php endif; ?>
                    </article>
                <?php endif; ?>
            </section>
        </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
