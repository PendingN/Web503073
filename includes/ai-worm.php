<?php
// The AI caterpillar: a floating button in the bottom-right corner that opens a chat about the user's notes.
// Included by footer.php for signed-in users. Without JavaScript the caterpillar is a plain link to ask.php.
$wormExamples = ['Cây nào trong sổ tay cần ít nước nhất?', 'Mình đã ghi gì về lá vàng?', 'Tóm tắt lịch chăm cây của mình'];
?>
<div class="ai-worm" data-ai-worm>
    <section class="ai-worm-panel" id="ai-worm-panel" role="dialog" aria-modal="false" aria-labelledby="ai-worm-title" hidden data-ask-root data-ask-order="chat">
        <header class="ai-worm-head">
            <span class="ai-worm-avatar" aria-hidden="true">
                <svg viewBox="0 0 48 48" width="36" height="36"><path d="M17 12c-2-5-5-7-8-6M31 12c2-5 5-7 8-6" fill="none" stroke="#2b3a12" stroke-width="2.4" stroke-linecap="round"/><ellipse cx="24" cy="27" rx="19" ry="16" fill="#a9d84b" stroke="#2b3a12" stroke-width="2"/><ellipse cx="17" cy="27" rx="4.4" ry="5.2" fill="#6a3cc0" stroke="#2a1840" stroke-width="1.4"/><ellipse cx="31" cy="27" rx="4.4" ry="5.2" fill="#6a3cc0" stroke="#2a1840" stroke-width="1.4"/><circle cx="15.6" cy="25" r="1.5" fill="#fff"/><circle cx="29.6" cy="25" r="1.5" fill="#fff"/><ellipse cx="11" cy="33" rx="3" ry="1.8" fill="#f4a6b8"/><ellipse cx="37" cy="33" rx="3" ry="1.8" fill="#f4a6b8"/><path d="M21 33q1.5 2 3 0q1.5 2 3 0" fill="none" stroke="#2b3a12" stroke-width="1.4" stroke-linecap="round"/></svg>
            </span>
            <div>
                <h2 id="ai-worm-title">Sâu AI</h2>
                <p>Hỏi về bài của bạn và bài được chia sẻ</p>
            </div>
            <button class="ai-worm-close" type="button" aria-label="Đóng Sâu AI" data-ai-worm-close>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </header>

        <div class="ai-worm-thread" data-ask-thread aria-live="polite">
            <div class="ai-worm-hello">
                <p>Chào bạn! Mình là Sâu AI 🐛</p>
                <div class="ask-examples" aria-label="Câu hỏi gợi ý">
                    <?php foreach ($wormExamples as $example): ?><button type="button" data-ask-example><?= e($example) ?></button><?php endforeach; ?>
                </div>
            </div>
        </div>

        <p class="ai-worm-status" role="status" data-ask-status></p>
        <form class="ai-worm-form" method="post" action="actions/ask-action.php" data-ask-form>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label class="sr-only" for="ai-worm-question">Câu hỏi cho Sâu AI</label>
            <textarea id="ai-worm-question" name="question" rows="1" minlength="2" maxlength="500" required placeholder="Hỏi Sâu điều gì đó…"></textarea>
            <button type="submit" aria-label="Gửi câu hỏi" data-ask-button>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
            </button>
        </form>
    </section>

    <a class="ai-worm-button" href="ask.php" aria-controls="ai-worm-panel" aria-expanded="false" data-ai-worm-toggle>
        <span class="ai-worm-tip">Hỏi Sâu AI</span>
        <svg class="ai-worm-art" viewBox="0 0 124 112" width="104" height="94" aria-hidden="true" focusable="false">
            <defs>
                <radialGradient id="aiWormSkin" cx="35%" cy="30%" r="75%"><stop offset="0" stop-color="#cdec7a"/><stop offset="0.6" stop-color="#a9d84b"/><stop offset="1" stop-color="#86bd2f"/></radialGradient>
                <radialGradient id="aiWormIris" cx="45%" cy="40%" r="65%"><stop offset="0" stop-color="#c9a6f5"/><stop offset="0.55" stop-color="#8656d6"/><stop offset="1" stop-color="#4a2491"/></radialGradient>
            </defs>
            <ellipse class="ai-worm-shadow" cx="72" cy="104" rx="46" ry="5"/>
            <g class="ai-worm-body">
                <g class="ai-worm-seg" style="--i:3"><ellipse class="ai-worm-foot" cx="107" cy="94" rx="4.5" ry="3.5"/><ellipse cx="108" cy="72" rx="11" ry="12.5"/><ellipse class="ai-worm-shine" cx="105" cy="65" rx="3.5" ry="2"/></g>
                <g class="ai-worm-seg" style="--i:2"><ellipse class="ai-worm-foot" cx="94" cy="97" rx="5" ry="3.8"/><ellipse cx="94" cy="79" rx="14" ry="15"/><ellipse class="ai-worm-shine" cx="90" cy="70" rx="4.5" ry="2.4"/></g>
                <g class="ai-worm-seg" style="--i:1"><ellipse class="ai-worm-foot" cx="78" cy="99" rx="5.5" ry="4"/><ellipse cx="78" cy="82" rx="17" ry="17"/><ellipse class="ai-worm-shine" cx="73" cy="71" rx="5" ry="2.6"/></g>
                <g class="ai-worm-seg" style="--i:0"><ellipse class="ai-worm-foot" cx="60" cy="100" rx="5.5" ry="4"/><ellipse class="ai-worm-foot" cx="44" cy="99" rx="5.5" ry="4"/><ellipse cx="56" cy="83" rx="22" ry="17.5"/></g>
            </g>
            <g class="ai-worm-head">
                <g class="ai-worm-antenna">
                    <path class="ai-worm-antenna-line" d="M34 26C30 16 26 10 20 9c-5-1-8 3-6 7 2 3 6 2 6-1"/>
                    <path class="ai-worm-antenna-fill" d="M34 26C30 16 26 10 20 9c-5-1-8 3-6 7 2 3 6 2 6-1"/>
                    <path class="ai-worm-antenna-line" d="M58 25c3-10 7-16 13-17 5-1 8 3 6 7-2 3-6 2-6-1"/>
                    <path class="ai-worm-antenna-fill" d="M58 25c3-10 7-16 13-17 5-1 8 3 6 7-2 3-6 2-6-1"/>
                </g>
                <ellipse class="ai-worm-face" cx="46" cy="53" rx="36" ry="31"/>
                <ellipse class="ai-worm-shine" cx="32" cy="31" rx="9" ry="4.5" transform="rotate(-18 32 31)"/>
                <g class="ai-worm-eyes">
                    <ellipse class="ai-worm-eye" cx="33" cy="53" rx="8.5" ry="10"/>
                    <ellipse class="ai-worm-eye" cx="59" cy="53" rx="8.5" ry="10"/>
                    <circle class="ai-worm-pupil" cx="33" cy="55" r="3.6"/>
                    <circle class="ai-worm-pupil" cx="59" cy="55" r="3.6"/>
                    <circle class="ai-worm-glint" cx="30" cy="49" r="2.8"/><circle class="ai-worm-glint" cx="56" cy="49" r="2.8"/>
                    <circle class="ai-worm-glint" cx="36" cy="58" r="1.3"/><circle class="ai-worm-glint" cx="62" cy="58" r="1.3"/>
                </g>
                <ellipse class="ai-worm-cheek" cx="20" cy="65" rx="6" ry="3.6"/>
                <ellipse class="ai-worm-cheek" cx="72" cy="65" rx="6" ry="3.6"/>
                <path class="ai-worm-smile" d="M40 64.5q3 3.5 6 0q3 3.5 6 0"/>
            </g>
            <g class="ai-worm-spark"><path d="M110 30l1.6 4 4 1.6-4 1.6-1.6 4-1.6-4-4-1.6 4-1.6Z"/></g>
        </svg>
        <span class="sr-only">Mở Sâu AI để hỏi về sổ tay của bạn</span>
    </a>
</div>
