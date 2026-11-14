<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/advisor-rules.php';
require_once __DIR__ . '/config/ai.php';
require_once __DIR__ . '/includes/fortune-sticks.php';

$modes = advisor_modes();
$options = plant_advisor_options();
$roomOptions = room_advisor_options();
$roomInput = normalize_room_advisor_input($_GET);
$roomParams = [];
if ($roomInput['light'] !== 'all') {
    $roomParams['light'] = $roomInput['light'];
}
if ($roomInput['position'] !== 'any') {
    $roomParams['position'] = $roomInput['position'];
}
$roomQuery = http_build_query($roomParams);

// Without a chosen mode the page leads with the practical room finder; astrology/fortune sit below.
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
// The landing finder or room advisor passes light and placement choices into the mode forms.
$requestedLight = request_string($_GET, 'light');
if (isset($options['light'][$requestedLight])) {
    $values['light'] = $requestedLight;
}
$requestedPosition = request_string($_GET, 'position');
if ($requestedPosition === 'all') {
    $requestedPosition = 'any';
}
if (isset($options['position'][$requestedPosition])) {
    $values['position'] = $requestedPosition;
}

$roomPickedPlants = [];
$roomReasons = [];
$roomBadges = room_condition_badges($roomInput);

// "Lắc quẻ" draws a random plant in the secondary entertainment section. JavaScript draws in the browser;
// ?lac=1 draws on the server so the button also works without JavaScript.
$fortuneData = null;
$fortune = null;
if ($mode === null) {
    $allPlants = list_plants();
    $catalogNotes = require __DIR__ . '/data/plant-fengshui.php';

    if ($roomInput['has_filters']) {
        $roomPicks = room_based_picks($allPlants, $catalogNotes, $roomInput);
        $plantsById = [];
        foreach ($allPlants as $plantItem) {
            $plantsById[(int) $plantItem['id']] = $plantItem;
        }
        foreach ($roomPicks as $pick) {
            $plantId = (int) $pick['plant_id'];
            if (isset($plantsById[$plantId])) {
                $roomReasons[$plantId] = (string) $pick['reason'];
                $roomPickedPlants[] = $plantsById[$plantId];
            }
        }
    }

    $fortuneCandidates = fortune_candidates($allPlants, $catalogNotes);
    $fortune = request_string($_GET, 'lac') === '1' ? fortune_draw($fortuneCandidates) : null;
    $fortuneReturn = app_url('plant-advisor.php') . ($roomQuery !== '' ? '?' . $roomQuery : '') . '#lac-que';
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
$pageDescription = 'Gợi ý chọn cây theo ánh sáng, vị trí đặt cây trong nhà và tham khảo thêm theo cung hoàng đạo, ngũ hành.';
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
            <?php if ($mode === null): ?>
            <header class="room-advisor-header">
                <div>
                    <p class="room-advisor-kicker">Gợi ý thực tế theo không gian</p>
                    <h1>Gợi ý cây cho góc nhà của bạn</h1>
                    <p class="lead">Chọn ánh sáng và vị trí đặt cây để xem ngay các loài phù hợp, ưu tiên những cây dễ chăm cho người mới bắt đầu.</p>
                </div>
            </header>

            <section class="room-advisor-panel" aria-label="Điều kiện góc đặt cây">
                <form class="room-advisor-form" id="room-advisor-form" method="get" action="plant-advisor.php">
                    <label for="room-advisor-light">Ánh sáng ở góc nhà
                        <select id="room-advisor-light" name="light">
                            <?php foreach ($roomOptions['light'] as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= (string) $value === $roomInput['light'] ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label for="room-advisor-position">Vị trí đặt cây
                        <select id="room-advisor-position" name="position">
                            <?php foreach ($roomOptions['position'] as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= (string) $value === $roomInput['position'] ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="room-advisor-actions">
                        <button class="btn-brand" type="submit">Xem cây phù hợp</button>
                        <?php if ($roomInput['has_filters']): ?>
                            <a class="btn-outline-brand" href="plant-advisor.php">Chọn lại điều kiện</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="advisor-results room-results" id="room-results" aria-labelledby="room-results-title">
                <?php if (!$roomInput['has_filters']): ?>
                    <div class="room-advisor-guide">
                        <h2 id="room-results-title">Chưa chọn điều kiện không gian</h2>
                        <p>Hãy chọn mức ánh sáng hoặc vị trí đặt cây ở trên để nhận danh sách gợi ý phù hợp cho góc nhà của bạn — không cần ngày sinh hay thông tin phong thủy.</p>
                        <div class="room-quick-picks" aria-label="Gợi ý nhanh theo không gian phổ biến">
                            <span class="room-quick-label">Thử nhanh:</span>
                            <a class="room-quick-chip" href="plant-advisor.php?light=low&amp;position=shelf">Bàn làm việc ít sáng</a>
                            <a class="room-quick-chip" href="plant-advisor.php?light=low&amp;position=floor">Góc sàn ít sáng</a>
                            <a class="room-quick-chip" href="plant-advisor.php?light=indirect&amp;position=floor">Góc phòng sáng dịu</a>
                            <a class="room-quick-chip" href="plant-advisor.php?light=bright&amp;position=shelf">Bệ cửa sổ nhiều nắng</a>
                        </div>
                        <p class="room-guide-browse">Hoặc <a href="collection.php">xem toàn bộ bộ sưu tập cây</a> nếu bạn muốn tự khám phá.</p>
                    </div>
                <?php elseif ($roomPickedPlants === []): ?>
                    <div class="room-condition-summary">
                        <div class="room-condition-pills" aria-label="Điều kiện đang chọn">
                            <?php foreach ($roomBadges as $badge): ?>
                                <span class="room-condition-pill"><b><?= e($badge['label']) ?>:</b> <?= e($badge['value']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="empty-state">
                        <h2 id="room-results-title">Chưa có cây phù hợp với điều kiện đã chọn</h2>
                        <p>Hiện chưa có cây nào đáp ứng đồng thời các điều kiện bạn vừa chọn. Thử đổi mức ánh sáng, để vị trí là "Ở đâu cũng được" hoặc xem toàn bộ bộ sưu tập.</p>
                        <div class="room-empty-actions">
                            <a class="btn-brand" href="plant-advisor.php">Đặt lại điều kiện</a>
                            <a class="btn-outline-brand" href="collection.php">Xem toàn bộ cây</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="room-condition-summary">
                        <div class="room-condition-pills" aria-label="Điều kiện đang chọn">
                            <?php foreach ($roomBadges as $badge): ?>
                                <span class="room-condition-pill"><b><?= e($badge['label']) ?>:</b> <?= e($badge['value']) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <p class="advisor-source">Gợi ý theo điều kiện ánh sáng &amp; vị trí thực tế</p>
                    </div>
                    <div class="section-heading">
                        <h2 id="room-results-title"><?= count($roomPickedPlants) ?> cây phù hợp với góc nhà bạn</h2>
                        <span class="sample-label">Ưu tiên cây dễ chăm · <?= current_user() ? 'Bấm tim để lưu cây' : 'Đăng nhập để lưu cây' ?></span>
                    </div>
                    <div class="plant-grid">
                        <?php
                        $returnTo = app_url('plant-advisor.php') . ($roomQuery !== '' ? '?' . $roomQuery : '') . '#room-results';
                        foreach ($roomPickedPlants as $plant):
                        ?>
                            <div class="advisor-pick">
                                <?php require __DIR__ . '/includes/plant-card.php'; ?>
                                <p class="advisor-reason"><?= e($roomReasons[(int) $plant['id']] ?? '') ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="advisor-entertainment" aria-labelledby="entertainment-title">
                <div class="advisor-entertainment-head">
                    <p class="advisor-entertainment-kicker">Góc tham khảo &amp; giải trí</p>
                    <h2 id="entertainment-title">Khám phá thêm theo chiêm tinh, ngũ hành &amp; lắc quẻ</h2>
                    <p>Những gợi ý dưới đây mang tính tham khảo cho vui bên cạnh điều kiện ánh sáng và vị trí thực tế.</p>
                </div>

                <nav class="advisor-modes" aria-label="Cách chọn cây tham khảo">
                    <?php foreach ($modes as $key => $item): ?>
                        <a class="advisor-mode advisor-mode--<?= e($key) ?>" href="plant-advisor.php?<?= e(http_build_query(['mode' => $key]) . ($roomQuery !== '' ? '&' . $roomQuery : '')) ?>" style="--mode-bg: url('<?= e(advisor_background($key)) ?>')">
                            <span class="advisor-mode-art" aria-hidden="true"></span>
                            <span class="advisor-mode-glow" aria-hidden="true"></span>
                            <span class="advisor-mode-hint"><?= e($item['hint']) ?></span>
                            <strong><?= e($item['label']) ?></strong>
                            <span><?= e($modeDescriptions[$key]) ?></span>
                            <span class="advisor-mode-cta" aria-hidden="true">Khám phá thử <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <section class="lac-que<?= $fortune !== null ? ' has-drawn' : '' ?>" id="lac-que" aria-labelledby="lac-que-title" data-lac-que>
                    <div class="lac-que-copy">
                        <p class="lac-que-kicker">Giải trí ngẫu nhiên</p>
                        <h3 id="lac-que-title">Lắc quẻ cây xanh</h3>
                        <p>Đầu ngày lắc một quẻ, rước một cây về nhà. Vui là chính, xanh là chủ yếu!</p>
                        <a class="lac-que-button" href="plant-advisor.php?<?= e(http_build_query($roomParams + ['lac' => '1'])) ?>#lac-que" data-que-shake>Lắc quẻ ngay</a>
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
                            <h4 data-que-name><?= $fortune !== null ? e($fortune['plant']['name']) : '' ?></h4>
                            <p class="que-verse" data-que-verse><?= $fortune !== null ? e($fortune['verse']) : '' ?></p>
                            <p class="que-meaning" data-que-meaning><?= $fortune !== null ? e($fortune['plant']['meaning']) : '' ?></p>
                            <dl class="que-facts" data-que-facts>
                                <?php foreach ($fortune['plant']['facts'] ?? [] as [$factLabel, $factValue]): ?><div><dt><?= e($factLabel) ?></dt><dd><?= e($factValue) ?></dd></div><?php endforeach; ?>
                            </dl>
                            <p class="que-water" data-que-water<?= ($fortune['plant']['water'] ?? '') === '' ? ' hidden' : '' ?>><b>Tưới nước:</b> <span><?= e($fortune['plant']['water'] ?? '') ?></span></p>
                            <div class="que-actions">
                                <a class="btn-brand" data-que-link href="<?= $fortune !== null ? e($fortunePlantUrl($fortune['plant'])) : 'collection.php' ?>">Xem cây này</a>
                                <a class="que-again" href="plant-advisor.php?<?= e(http_build_query($roomParams + ['lac' => '1'])) ?>#lac-que" data-que-shake>Lắc lại</a>
                            </div>
                        </div>
                    </div>
                    <script type="application/json" id="que-data"><?= json_encode($fortuneData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
                </section>
            </section>
            <?php else: ?>
            <?php $modeRoomQuery = http_build_query(['light' => $values['light'] ?: null, 'position' => $values['position'] !== 'any' ? $values['position'] : null]); ?>
            <a class="back-link advisor-back" href="plant-advisor.php<?= $modeRoomQuery !== '' ? '?' . e($modeRoomQuery) : '' ?>">← Chọn cách khác</a>
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
