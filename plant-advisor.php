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
    <label for="<?= e($id) ?>" data-field="<?= e($label) ?>"><?= e($label) ?>
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

// Radio cards for short option lists: one click to choose, and the form still posts without JavaScript.
// A label such as "Bận rộn, hay quên tưới" shows its first part as the title and the rest as a hint.
function advisor_choices(string $name, string $label, array $choices, string $selected, array $errors, array $meta, string $layout = 'cards'): void
{
    $id = 'advisor-' . $name;
    $error = $errors[$name] ?? '';
    ?>
    <fieldset class="advisor-choice advisor-choice--<?= e($layout) ?><?= $error !== '' ? ' is-invalid' : '' ?>" id="<?= e($id) ?>" data-field="<?= e($label) ?>"<?= $error !== '' ? ' aria-describedby="' . e($id) . '-error"' : '' ?>>
        <legend><?= e($label) ?></legend>
        <div class="advisor-choice-grid">
            <?php foreach ($choices as $value => $text): ?>
                <?php
                $item = $meta[$value] ?? [];
                [$title, $hint] = array_pad(explode(', ', (string) $text, 2), 2, '');
                $title = $item['title'] ?? $title;
                $hint = $item['hint'] ?? $hint;
                ?>
                <label class="choice-card" data-value="<?= e((string) $value) ?>"<?= isset($item['deg']) ? ' style="--deg: ' . (int) $item['deg'] . 'deg"' : '' ?>>
                    <input type="radio" name="<?= e($name) ?>" value="<?= e((string) $value) ?>" required<?= (string) $value === $selected ? ' checked' : '' ?><?= $text !== $title ? ' aria-label="' . e((string) $text) . '"' : '' ?>>
                    <span class="choice-body">
                        <?php if (isset($item['icon'])): ?><span class="choice-icon" aria-hidden="true"><?= $item['icon'] ?></span><?php endif; ?>
                        <span class="choice-title"><?= e($title) ?></span>
                        <?php if ($hint !== ''): ?><span class="choice-hint"><?= e($hint) ?></span><?php endif; ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php if ($error !== ''): ?><span class="field-error" id="<?= e($id) ?>-error"><?= e($error) ?></span><?php endif; ?>
    </fieldset>
    <?php
}

function advisor_direction_degrees(): array
{
    return ['bac' => 0, 'dong-bac' => 45, 'dong' => 90, 'dong-nam' => 135, 'nam' => 180, 'tay-nam' => 225, 'tay' => 270, 'tay-bac' => 315];
}

// Small stroke icons for the choice cards; the markup is static, so it is printed unescaped.
function advisor_icon(string $name, int $count = 1): string
{
    $paths = [
        'coin' => '<circle cx="12" cy="12" r="8.5"/><rect x="9.6" y="9.6" width="4.8" height="4.8" rx=".6"/>',
        'lotus' => '<path d="M12 20c-4.4 0-7-2.9-7-6.8 3 0 5.5 1.4 7 3.9 1.5-2.5 4-3.9 7-3.9 0 3.9-2.6 6.8-7 6.8Z"/><path d="M12 17c-1.9-2-2.6-4.6-2-8.2 1 .5 1.6 1.2 2 2.1.4-.9 1-1.6 2-2.1.6 3.6-.1 6.2-2 8.2Z"/>',
        'book' => '<path d="M4 5.6c2.6-1 5.4-1 8 .6 2.6-1.6 5.4-1.6 8-.6V19c-2.6-1-5.4-1-8 .6-2.6-1.6-5.4-1.6-8-.6Z"/><path d="M12 6.2v13.4"/>',
        'heart' => '<path d="M12 19.5s-7-4.3-7-9.3A3.9 3.9 0 0 1 12 8a3.9 3.9 0 0 1 7 2.2c0 5-7 9.3-7 9.3Z"/>',
        'drop' => '<path d="M12 3.5s-5.5 6-5.5 10a5.5 5.5 0 0 0 11 0c0-4-5.5-10-5.5-10Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2.8v2.4M12 18.8v2.4M2.8 12h2.4M18.8 12h2.4M5.5 5.5l1.7 1.7M16.8 16.8l1.7 1.7M5.5 18.5l1.7-1.7M16.8 7.2l1.7-1.7"/>',
        'cloud-sun' => '<path d="M8.2 8.6a4 4 0 0 1 7.3-.6M11.5 2.8v1.6M5.4 5.2l1.1 1.1M17.6 5.2l-1.1 1.1"/><path d="M7 19.5h10a3.5 3.5 0 0 0 .4-7 5 5 0 0 0-9.5 1A3.1 3.1 0 0 0 7 19.5Z"/>',
        'moon' => '<path d="M19 14.6A7.5 7.5 0 0 1 9.4 5a7.5 7.5 0 1 0 9.6 9.6Z"/><path d="M16.5 4.5v2.4M15.3 5.7h2.4"/>',
        'sparkle' => '<path d="M11 4.5l1.8 4.7 4.7 1.8-4.7 1.8L11 17.5l-1.8-4.7-4.7-1.8 4.7-1.8Z"/><path d="M18 15.5v4M16 17.5h4"/>',
        'floor' => '<path d="M8 14.5h8l-1.1 6H9.1Z"/><path d="M12 14.5V5.5M12 10c-3 0-4.6-2-4.6-4.5 3 0 4.6 2 4.6 4.5Zm0 2.2c0-2.6 1.6-4.5 4.6-4.5 0 2.6-1.6 4.5-4.6 4.5Z"/><path d="M3.5 20.5h17"/>',
        'shelf' => '<path d="M3 15h18M5.5 15v5.5M18.5 15v5.5"/><path d="M9.2 10h5.6l-.8 5h-4Z"/><path d="M12 10V7.2m0 0c-1.6 0-2.6-1-2.6-2.6 1.6 0 2.6 1 2.6 2.6Zm0 0c0-1.6 1-2.6 2.6-2.6 0 1.6-1 2.6-2.6 2.6Z"/>',
        'needle' => '<path d="M12 3.2 15 12H9Z" fill="currentColor"/><path d="M9 12h6l-3 8.8Z"/>',
        'compass' => '<circle cx="12" cy="12" r="8.5"/><path d="M9.7 9.6a2.4 2.4 0 1 1 3.2 2.3c-.6.2-.9.7-.9 1.3v.6M12 16.6v.1"/>',
    ];
    $svg = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? '') . '</svg>';
    return str_repeat($svg, max(1, $count));
}

$modeDescriptions = [
    'zodiac' => '',
    'spiritual' => '',
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
                <?php if ($modeDescriptions[$mode] !== ''): ?><p><?= e($modeDescriptions[$mode]) ?></p><?php endif; ?>
            </header>

            <?php
            $stepNames = [$mode === 'zodiac' ? 'Ngày sinh' : 'Lá số', 'Mong muốn', 'Góc nhà'];
            $directionMeta = [];
            foreach (corner_directions() as $key => [$directionLabel, $directionElement]) {
                $directionMeta[$key] = ['icon' => advisor_icon($key === 'any' ? 'compass' : 'needle'), 'title' => $key === 'any' ? 'Chưa rõ' : str_replace('Hướng ', '', $directionLabel),
                    'hint' => $directionElement !== '' ? 'Hành ' . fengshui_element_labels()[$directionElement] : '', 'deg' => advisor_direction_degrees()[$key] ?? 0];
            }
            $noteTags = ['Nhà có thú cưng', 'Có trẻ nhỏ', 'Phòng nhỏ', 'Thích cây có hoa', 'Muốn cây lọc không khí', 'Hay vắng nhà dài ngày'];
            ?>
            <form class="advisor-form advisor-form--<?= e($mode) ?>" id="advisor-form" method="post" action="actions/plant-advice-action.php" novalidate data-advisor-steps>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="mode" value="<?= e($mode) ?>">
                <?php if ($errors !== []): ?>
                    <p class="advisor-alert" role="alert">Vui lòng kiểm tra lại các mục được đánh dấu bên dưới.</p>
                <?php endif; ?>

                <ol class="advisor-progress" data-step-progress hidden>
                    <?php foreach ($stepNames as $index => $stepName): ?>
                        <li><button type="button" data-step-go="<?= $index ?>"><span class="advisor-progress-dot" aria-hidden="true"><?= $index + 1 ?></span><span><?= e($stepName) ?></span></button></li>
                    <?php endforeach; ?>
                </ol>

                <section class="advisor-step" data-step aria-labelledby="advisor-step-1">
                    <header class="advisor-step-head">
                        <p class="advisor-step-kicker">Bước 1 / 3</p>
                        <h2 id="advisor-step-1" tabindex="-1"><?= $mode === 'zodiac' ? 'Cung hoàng đạo của bạn' : 'Lá số của bạn' ?></h2>
                        <?php if ($mode !== 'zodiac'): ?><p>Ngày tháng năm sinh dương lịch giúp tính năm âm lịch và mệnh ngũ hành.</p><?php endif; ?>
                    </header>
                    <div class="advisor-birth<?= $mode === 'spiritual' ? ' advisor-birth--full' : '' ?>">
                        <?php advisor_select('birth_day', 'Ngày sinh', array_combine(range(1, 31), range(1, 31)), $values['birth_day'], $errors, 'Ngày'); ?>
                        <?php advisor_select('birth_month', 'Tháng sinh', $options['month'], $values['birth_month'], $errors, 'Tháng'); ?>
                        <?php if ($mode === 'spiritual'): ?>
                            <label for="advisor-birth_year" data-field="Năm sinh">Năm sinh (dương lịch)
                                <input id="advisor-birth_year" name="birth_year" type="text" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="bday-year" placeholder="Ví dụ: 1995" value="<?= e($values['birth_year']) ?>" required<?= isset($errors['birth_year']) ? ' aria-invalid="true" aria-describedby="advisor-birth_year-error"' : '' ?>>
                                <?php if (isset($errors['birth_year'])): ?><span class="field-error" id="advisor-birth_year-error"><?= e($errors['birth_year']) ?></span><?php endif; ?>
                            </label>
                        <?php endif; ?>
                    </div>
                    <div class="advisor-step-nav" data-step-nav hidden>
                        <p class="advisor-step-message" data-step-message aria-live="polite"></p>
                        <button class="btn-brand" type="button" data-step-next>Tiếp tục</button>
                    </div>
                </section>

                <section class="advisor-step" data-step aria-labelledby="advisor-step-2">
                    <header class="advisor-step-head">
                        <p class="advisor-step-kicker">Bước 2 / 3</p>
                        <h2 id="advisor-step-2" tabindex="-1">Bạn đang mong cầu điều gì?</h2>
                    </header>
                    <?php advisor_choices('wish', 'Điều bạn mong muốn', $options['wish'], $values['wish'], $errors, [
                        'tai-loc' => ['icon' => advisor_icon('coin'), 'hint' => ''], 'binh-an' => ['icon' => advisor_icon('lotus'), 'hint' => ''],
                        'su-nghiep' => ['icon' => advisor_icon('book'), 'hint' => ''], 'tinh-cam' => ['icon' => advisor_icon('heart'), 'hint' => ''],
                    ], 'wide'); ?>
                    <?php advisor_choices('habit', 'Thói quen chăm cây', $options['habit'], $values['habit'], $errors, [
                        'busy' => ['icon' => advisor_icon('drop', 1), 'hint' => ''], 'balanced' => ['icon' => advisor_icon('drop', 2), 'hint' => ''], 'attentive' => ['icon' => advisor_icon('drop', 3), 'hint' => ''],
                    ]); ?>
                    <div class="advisor-step-nav" data-step-nav hidden>
                        <button class="advisor-step-back" type="button" data-step-back>Quay lại</button>
                        <p class="advisor-step-message" data-step-message aria-live="polite"></p>
                        <button class="btn-brand" type="button" data-step-next>Tiếp tục</button>
                    </div>
                </section>

                <section class="advisor-step" data-step aria-labelledby="advisor-step-3">
                    <header class="advisor-step-head">
                        <p class="advisor-step-kicker">Bước 3 / 3</p>
                        <h2 id="advisor-step-3" tabindex="-1">Góc nhà đặt cây</h2>
                    </header>
                    <?php advisor_choices('light', 'Ánh sáng ở góc nhà', $options['light'], $values['light'], $errors, [
                        'bright' => ['icon' => advisor_icon('sun'), 'hint' => ''], 'indirect' => ['icon' => advisor_icon('cloud-sun'), 'hint' => ''], 'low' => ['icon' => advisor_icon('moon'), 'hint' => ''],
                    ]); ?>
                    <?php advisor_choices('position', 'Bạn muốn đặt cây ở đâu?', $options['position'], $values['position'], $errors, [
                        'any' => ['icon' => advisor_icon('sparkle'), 'hint' => ''], 'floor' => ['icon' => advisor_icon('floor'), 'hint' => ''], 'shelf' => ['icon' => advisor_icon('shelf'), 'hint' => ''],
                    ]); ?>
                    <div class="advisor-corner">
                        <?php if ($mode === 'spiritual'): ?>
                            <?php advisor_choices('direction', 'Góc nhà nằm ở hướng nào?', $options['direction'], $values['direction'], $errors, $directionMeta, 'compass'); ?>
                        <?php endif; ?>
                        <div class="advisor-note">
                            <label for="advisor-note"><span>Ghi chú thêm <span class="advisor-optional">(không bắt buộc)</span></span></label>
                            <div class="advisor-note-tags" data-note-tags hidden>
                                <?php foreach ($noteTags as $tag): ?>
                                    <button type="button" aria-pressed="false" data-note-tag="<?= e($tag) ?>"><?= e($tag) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <textarea id="advisor-note" name="note" rows="3" maxlength="200" placeholder="Ví dụ: nhà có mèo, phòng ngủ nhỏ, muốn cây có hoa" aria-describedby="advisor-note-count"><?= e($values['note']) ?></textarea>
                            <span class="advisor-note-count" id="advisor-note-count" data-note-count><?= mb_strlen($values['note'], 'UTF-8') ?>/200 ký tự</span>
                        </div>
                    </div>
                    <div class="advisor-submit">
                        <button class="advisor-step-back" type="button" data-step-back hidden>Quay lại</button>
                        <p class="advisor-step-message" data-step-message aria-live="polite"></p>
                        <button class="btn-brand" type="submit"><?= $mode === 'zodiac' ? 'Xem cây hợp cung của tôi' : 'Xem cây hợp mệnh của tôi' ?></button>
                        <p class="advisor-wait" aria-live="polite">Đang chọn cây phù hợp, có thể mất vài giây…</p>
                        <?php if ($aiEnabled): ?><p class="advisor-privacy">Ngày sinh, lựa chọn và ghi chú của bạn được gửi tới dịch vụ AI (DeepSeek) để tạo gợi ý. Chúng tôi không gửi tên, email hay tài khoản của bạn.</p><?php endif; ?>
                    </div>
                </section>
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
                        <?php if ($advice['source'] === 'ai' || $profile['mode'] !== 'zodiac'): ?><p class="advisor-source"><?= $advice['source'] === 'ai' ? 'Gợi ý bởi trợ lý AI' : 'Gợi ý theo nguyên tắc ngũ hành' ?></p><?php endif; ?>
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
                    <?php if ($mode !== 'zodiac'): ?><p class="advisor-footnote">Cung hoàng đạo và phong thủy chỉ là góc nhìn tham khảo cho vui. Điều giữ cây khỏe vẫn là ánh sáng, nước tưới và sự quan tâm của bạn.</p><?php endif; ?>
                </section>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php $showSiteFooter = true; require __DIR__ . '/includes/footer.php'; ?>
