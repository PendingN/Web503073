<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/advisor-rules.php';
require_once __DIR__ . '/config/ai.php';
require_once __DIR__ . '/includes/fortune-sticks.php';

$modes = advisor_modes();
$options = plant_advisor_options();
// Without a chosen mode the page shows only the two mode buttons; the form opens after a choice.
$mode = request_string($_GET, 'mode');
$mode = isset($modes[$mode]) ? $mode : null;
$state = $mode !== null && is_array($_SESSION['plant_advice'][$mode] ?? null) ? $_SESSION['plant_advice'][$mode] : [];
$errors = is_array($state['errors'] ?? null) ? $state['errors'] : [];
if ($mode !== null) {
    unset($_SESSION['plant_advice'][$mode]['errors']);
}
$advice = is_array($state['advice'] ?? null) ? $state['advice'] : null;
$aiEnabled = load_ai_config()['enabled'];
$values = array_merge(
    ['birth_day' => '', 'birth_month' => '', 'birth_year' => '', 'direction' => 'any', 'wish' => '', 'habit' => '', 'light' => '', 'position' => 'any', 'note' => ''],
    is_array($state['values'] ?? null) ? $state['values'] : []
);
// The landing finder links here with its light and placement choices.
foreach (['light', 'position'] as $field) {
    $requested = request_string($_GET, $field);
    if (isset($options[$field][$requested])) {
        $values[$field] = $requested;
    }
}
// "Lắc quẻ" draws a random plant on the mode chooser. JavaScript draws in the browser;
// ?lac=1 draws on the server so the button also works without JavaScript.
$fortuneData = null;
$fortune = null;
if ($mode === null) {
    $fortuneCandidates = fortune_candidates(list_plants(), require __DIR__ . '/data/plant-fengshui.php');
    $fortune = request_string($_GET, 'lac') === '1' ? fortune_draw($fortuneCandidates) : null;
    $fortuneReturn = app_url('plant-advisor.php') . '#lac-que';
    $fortunePlantUrl = static fn (array $plant): string => 'plant-detail.php?' . http_build_query(['id' => $plant['id'], 'return_to' => $fortuneReturn]);
    $fortuneData = ['levels' => fortune_levels(), 'sticks' => []];
    foreach (fortune_sticks() as $key => $stick) {
        if (isset($fortuneCandidates[$key])) {
            $fortuneData['sticks'][$key] = ['label' => $stick['label'], 'color' => $stick['color'], 'verses' => $stick['verses'],
                'plants' => array_map(static fn (array $plant): array => ['name' => $plant['name'], 'image' => image_url($plant['image']),
                    'meaning' => $plant['meaning'], 'facts' => $plant['facts'], 'water' => $plant['water'], 'url' => $fortunePlantUrl($plant)], $fortuneCandidates[$key])];
        }
    }
}
$pickedPlants = [];
$reasons = [];
if ($advice !== null) {
    foreach ($advice['picks'] as $pick) {
        $reasons[(int) $pick['plant_id']] = (string) $pick['reason'];
    }
    $pickedPlants = plants_by_ids(array_keys($reasons));
}

// A photo saved as images/advisor/<mode>.jpg|png|webp replaces the drawn background.
function advisor_background(string $mode): string
{
    foreach (['webp', 'jpg', 'png'] as $extension) {
        if (is_file(__DIR__ . '/images/advisor/' . $mode . '.' . $extension)) {
            return asset_url('images/advisor/' . $mode . '.' . $extension);
        }
    }
    return asset_url('images/advisor/' . ($mode === 'zodiac' ? 'zodiac-sky.svg' : 'five-elements.svg'));
}

function advisor_select(string $name, string $label, array $choices, string $selected, array $errors, string $placeholder = ''): void
{
    $id = 'advisor-' . $name;
    $error = $errors[$name] ?? '';
    ?>
    <label for="<?= e($id) ?>"><?= e($label) ?>
        <select id="<?= e($id) ?>" name="<?= e($name) ?>" required<?= $error !== '' ? ' aria-invalid="true" aria-describedby="' . e($id) . '-error"' : '' ?>>
            <?php if ($placeholder !== ''): ?><option value=""><?= e($placeholder) ?></option><?php endif; ?>
            <?php foreach ($choices as $value => $text): ?>
                <option value="<?= e($value) ?>"<?= (string) $value === $selected ? ' selected' : '' ?>><?= e($text) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($error !== ''): ?><span class="field-error" id="<?= e($id) ?>-error"><?= e($error) ?></span><?php endif; ?>
    </label>
    <?php
}

$modeDescriptions = [
    'zodiac' => 'Chọn cây theo cung hoàng đạo và tính cách của bạn.',
    'spiritual' => 'Xem mệnh theo ngày tháng năm sinh và hướng góc nhà.',
];
$pageTitle = 'Gợi ý chọn cây';
$pageDescription = 'Chọn cây theo cung hoàng đạo, mệnh ngũ hành và góc nhà của bạn.';
$bodyClass = 'dashboard-page';
$pageStyles = ['css/plant-advisor.css'];
$pageScripts = ['js/plant-advisor.js'];
$dashboardActive = 'advisor';
$activePage = 'collection';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>
    <div class="dashboard-main">
        <main id="main-content" class="dashboard-content" tabindex="-1">
            <?php $roomQuery = http_build_query(['light' => $values['light'] ?: null, 'position' => $values['position'] !== 'any' ? $values['position'] : null]); ?>
            <?php if ($mode === null): ?>
            <header>
                <div>
                    <h1>Chọn cây gì bây giờ?</h1>
                </div>
            </header>

            <nav class="advisor-modes" aria-label="Cách chọn cây">
                <?php foreach ($modes as $key => $item): ?>
                    <a class="advisor-mode advisor-mode--<?= e($key) ?>" href="plant-advisor.php?<?= e(http_build_query(['mode' => $key]) . ($roomQuery !== '' ? '&' . $roomQuery : '')) ?>" style="--mode-bg: url('<?= e(advisor_background($key)) ?>')">
                        <span class="advisor-mode-art" aria-hidden="true"></span>
                        <span class="advisor-mode-glow" aria-hidden="true"></span>
                        <span class="advisor-mode-hint"><?= e($item['hint']) ?></span>
                        <strong><?= e($item['label']) ?></strong>
                        <span><?= e($modeDescriptions[$key]) ?></span>
                        <span class="advisor-mode-cta" aria-hidden="true">Bắt đầu <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <section class="lac-que<?= $fortune !== null ? ' has-drawn' : '' ?>" id="lac-que" aria-labelledby="lac-que-title" data-lac-que>
                <div class="lac-que-copy">
                    <p class="lac-que-kicker">Chưa biết chọn cây gì?</p>
                    <h2 id="lac-que-title">Lắc quẻ cây xanh</h2>
                    <p>Đầu ngày lắc một quẻ, rước một cây về nhà. Vui là chính, xanh là chủ yếu!</p>
                    <a class="lac-que-button" href="plant-advisor.php?lac=1#lac-que" data-que-shake>Lắc quẻ ngay</a>
                </div>
                <div class="lac-que-stage" aria-hidden="true">
                    <div class="que-tube">
                        <?php $stickIndex = 0; foreach (fortune_sticks() as $key => $stick): ?>
                            <span class="que-stick<?= $fortune !== null && $fortune['stick'] === $key ? ' is-drawn' : '' ?>" style="--i: <?= $stickIndex++ ?>; --c: <?= e($stick['color']) ?>" data-que-stick="<?= e($key) ?>"><span><?= e($stick['label']) ?></span></span>
                        <?php endforeach; ?>
                        <span class="que-cup">
                            <svg viewBox="0 0 64 64" width="64" height="64"><circle cx="32" cy="32" r="29" fill="#ffd77a"/><circle cx="32" cy="32" r="24" fill="none" stroke="#b3261e" stroke-width="2"/><path d="M32 46V27m0 9c-7 0-12-5-12-12 7 0 12 5 12 12Zm0-4c0-7 5-12 12-12 0 7-5 12-12 12Z" fill="#3fa45b" stroke="#2c6e3e" stroke-width="1.5" stroke-linejoin="round"/></svg>
                        </span>
                    </div>
                </div>
                <div class="que-result" data-que-result aria-live="polite"<?= $fortune === null ? ' hidden' : '' ?>>
                    <img data-que-image<?= $fortune !== null ? ' src="' . e(image_url($fortune['plant']['image'])) . '"' : '' ?> alt="<?= $fortune !== null ? e($fortune['plant']['name']) : '' ?>" width="240" height="300" decoding="async">
                    <div>
                        <p class="que-result-tag" data-que-tag<?= $fortune !== null ? ' style="--c: ' . e(fortune_sticks()[$fortune['stick']]['color']) . '"' : '' ?>><?= $fortune !== null ? e('Quẻ ' . $fortune['label'] . ' · ' . $fortune['level']) : '' ?></p>
                        <h3 data-que-name><?= $fortune !== null ? e($fortune['plant']['name']) : '' ?></h3>
                        <p class="que-verse" data-que-verse><?= $fortune !== null ? e($fortune['verse']) : '' ?></p>
                        <p class="que-meaning" data-que-meaning><?= $fortune !== null ? e($fortune['plant']['meaning']) : '' ?></p>
                        <dl class="que-facts" data-que-facts>
                            <?php foreach ($fortune['plant']['facts'] ?? [] as [$factLabel, $factValue]): ?><div><dt><?= e($factLabel) ?></dt><dd><?= e($factValue) ?></dd></div><?php endforeach; ?>
                        </dl>
                        <p class="que-water" data-que-water<?= ($fortune['plant']['water'] ?? '') === '' ? ' hidden' : '' ?>><b>Tưới nước:</b> <span><?= e($fortune['plant']['water'] ?? '') ?></span></p>
                        <div class="que-actions">
                            <a class="btn-brand" data-que-link href="<?= $fortune !== null ? e($fortunePlantUrl($fortune['plant'])) : 'collection.php' ?>">Xem cây này</a>
                            <a class="que-again" href="plant-advisor.php?lac=1#lac-que" data-que-shake>Lắc lại</a>
                        </div>
                    </div>
                </div>
                <script type="application/json" id="que-data"><?= json_encode($fortuneData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
            </section>
            <?php else: ?>
            <a class="back-link advisor-back" href="plant-advisor.php<?= $roomQuery !== '' ? '?' . e($roomQuery) : '' ?>">← Chọn cách khác</a>
            <header class="advisor-banner advisor-mode--<?= e($mode) ?>" style="--mode-bg: url('<?= e(advisor_background($mode)) ?>')">
                <span class="advisor-mode-hint"><?= e($modes[$mode]['hint']) ?></span>
                <h1><?= e($modes[$mode]['label']) ?></h1>
                <p><?= e($modeDescriptions[$mode]) ?></p>
            </header>

            <form class="advisor-form advisor-form--<?= e($mode) ?>" id="advisor-form" method="post" action="actions/plant-advice-action.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="mode" value="<?= e($mode) ?>">
                <?php if ($errors !== []): ?>
                    <p class="advisor-alert" role="alert">Vui lòng kiểm tra lại các mục được đánh dấu bên dưới.</p>
                <?php endif; ?>

                <fieldset>
                    <legend><?= $mode === 'zodiac' ? 'Cung hoàng đạo của bạn' : 'Lá số của bạn' ?></legend>
                    <div class="advisor-birth<?= $mode === 'spiritual' ? ' advisor-birth--full' : '' ?>">
                        <?php advisor_select('birth_day', 'Ngày sinh', array_combine(range(1, 31), range(1, 31)), $values['birth_day'], $errors, 'Ngày'); ?>
                        <?php advisor_select('birth_month', 'Tháng sinh', $options['month'], $values['birth_month'], $errors, 'Tháng'); ?>
                        <?php if ($mode === 'spiritual'): ?>
                            <label for="advisor-birth_year">Năm sinh (dương lịch)
                                <input id="advisor-birth_year" name="birth_year" type="text" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="bday-year" placeholder="Ví dụ: 1995" value="<?= e($values['birth_year']) ?>" required<?= isset($errors['birth_year']) ? ' aria-invalid="true" aria-describedby="advisor-birth_year-error"' : '' ?>>
                                <?php if (isset($errors['birth_year'])): ?><span class="field-error" id="advisor-birth_year-error"><?= e($errors['birth_year']) ?></span><?php endif; ?>
                            </label>
                        <?php endif; ?>
                    </div>
                    <?php advisor_select('wish', 'Điều bạn mong muốn', $options['wish'], $values['wish'], $errors, 'Chọn mong muốn'); ?>
                    <?php advisor_select('habit', 'Thói quen chăm cây', $options['habit'], $values['habit'], $errors, 'Chọn thói quen'); ?>
                </fieldset>

                <fieldset>
                    <legend>Góc nhà đặt cây</legend>
                    <?php advisor_select('light', 'Ánh sáng ở góc nhà', $options['light'], $values['light'], $errors, 'Chọn ánh sáng'); ?>
                    <?php advisor_select('position', 'Bạn muốn đặt cây ở đâu?', $options['position'], $values['position'], $errors); ?>
                    <?php if ($mode === 'spiritual'): ?>
                        <?php advisor_select('direction', 'Góc nhà nằm ở hướng nào?', $options['direction'], $values['direction'], $errors); ?>
                    <?php endif; ?>
                    <label class="advisor-wide" for="advisor-note"><span>Ghi chú thêm <span class="advisor-optional">(không bắt buộc)</span></span>
                        <textarea id="advisor-note" name="note" rows="2" maxlength="200" placeholder="Ví dụ: nhà có mèo, phòng ngủ nhỏ, muốn cây có hoa"><?= e($values['note']) ?></textarea>
                    </label>
                </fieldset>

                <div class="advisor-submit">
                    <button class="btn-brand" type="submit"><?= $mode === 'zodiac' ? 'Xem cây hợp cung của tôi' : 'Xem cây hợp mệnh của tôi' ?></button>
                    <p class="advisor-wait" aria-live="polite">Đang chọn cây phù hợp, có thể mất vài giây…</p>
                    <?php if ($aiEnabled): ?><p class="advisor-privacy">Ngày sinh, lựa chọn và ghi chú của bạn được gửi tới dịch vụ AI (DeepSeek) để tạo gợi ý. Chúng tôi không gửi tên, email hay tài khoản của bạn.</p><?php endif; ?>
                </div>
            </form>

            <?php if ($advice !== null): ?>
                <?php $profile = $advice['profile']; ?>
                <section class="advisor-results" id="advisor-results" aria-labelledby="advisor-results-title">
                    <div class="advisor-profile">
                        <?php if ($profile['mode'] === 'zodiac'): ?>
                            <?php $zodiacLabels = zodiac_element_labels(); ?>
                            <p class="plant-category">Sinh ngày <?= e($profile['birth']) ?> · Nhóm <?= e($zodiacLabels[$profile['element']]) ?></p>
                            <h2 id="advisor-results-title">Cung <?= e($profile['name']) ?></h2>
                            <p class="advisor-traits">Bạn là người <?= e($profile['traits']) ?>. Cây mang năng lượng nhóm <?= e($zodiacLabels[$profile['element']]) ?> và <?= e($zodiacLabels[zodiac_companion($profile['element'])]) ?> sẽ hợp với bạn nhất.</p>
                        <?php else: ?>
                            <?php $labels = fengshui_element_labels(); $lunar = $profile['lunar']; ?>
                            <p class="plant-category">Sinh ngày <?= e($profile['birth']) ?> · Âm lịch <?= (int) $lunar['day'] ?>/<?= (int) $lunar['month'] ?><?= $lunar['leap'] ? ' nhuận' : '' ?> năm <?= e($profile['can_chi']) ?></p>
                            <h2 id="advisor-results-title">Mệnh <?= e($labels[$profile['element']]) ?> <span>(<?= e($profile['nap_am']) ?>)</span></h2>
                            <ul class="advisor-elements">
                                <li><b>Hợp:</b> <?= e($labels[$profile['good_elements'][0]]) ?> (tương sinh), <?= e($labels[$profile['good_elements'][1]]) ?> (tương hợp)</li>
                                <li><b>Nên tránh:</b> <?= e($labels[$profile['avoid_element']]) ?> (tương khắc)</li>
                                <?php if ($profile['direction_element'] !== ''): ?><li><b>Góc nhà:</b> <?= e(corner_directions()[$profile['direction']][0]) ?>, hành <?= e($labels[$profile['direction_element']]) ?></li><?php endif; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($advice['summary'] !== ''): ?><p class="advisor-summary"><?= e($advice['summary']) ?></p><?php endif; ?>
                        <p class="advisor-source"><?= $advice['source'] === 'ai' ? 'Gợi ý bởi trợ lý AI' : ($profile['mode'] === 'zodiac' ? 'Gợi ý theo cung hoàng đạo' : 'Gợi ý theo nguyên tắc ngũ hành') ?></p>
                        <?php if ($advice['notice'] !== ''): ?><p class="status-note"><?= e($advice['notice']) ?></p><?php endif; ?>
                    </div>

                    <?php if ($pickedPlants === []): ?>
                        <div class="empty-state">
                            <h2>Chưa có cây thật sự phù hợp</h2>
                            <p>Thử chọn mức ánh sáng khác hoặc để vị trí là "Ở đâu cũng được".</p>
                            <a class="btn-outline-brand" href="collection.php">Xem toàn bộ cây</a>
                        </div>
                    <?php else: ?>
                        <div class="section-heading">
                            <h2><?= count($pickedPlants) ?> cây dành cho bạn</h2>
                            <span class="sample-label"><?= current_user() ? 'Bấm tim để lưu cây' : 'Đăng nhập để lưu cây' ?></span>
                        </div>
                        <div class="plant-grid">
                            <?php foreach ($pickedPlants as $plant): ?>
                                <div class="advisor-pick">
                                    <?php $returnTo = app_url('plant-advisor.php') . '?mode=' . $mode . '#advisor-results'; require __DIR__ . '/includes/plant-card.php'; ?>
                                    <p class="advisor-reason"><?= e($reasons[(int) $plant['id']] ?? '') ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <p class="advisor-footnote">Cung hoàng đạo và phong thủy chỉ là góc nhìn tham khảo cho vui. Điều giữ cây khỏe vẫn là ánh sáng, nước tưới và sự quan tâm của bạn.</p>
                </section>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php $showSiteFooter = true; require __DIR__ . '/includes/footer.php'; ?>
