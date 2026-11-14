<?php
declare(strict_types=1);

// Standalone checks for the plant advisor: no database, network, or API key required.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/plant-advisor.php';
require_once __DIR__ . '/../includes/fortune-sticks.php';

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

require __DIR__ . '/../data/plants.php';
$notes = require __DIR__ . '/../data/plant-fengshui.php';
$options = plant_advisor_options();

// Every seeded plant has well-formed notes.
check(array_keys($notes) == array_column($plants, 'id'), 'Every seeded plant needs advisor notes, and only seeded plants.');
foreach ($notes as $id => $meta) {
    check($meta['elements'] !== [] && array_diff($meta['elements'], array_keys(fengshui_element_labels())) === [], "Plant $id has unknown elements.");
    check($meta['zodiac'] !== [] && array_diff($meta['zodiac'], array_keys(zodiac_element_labels())) === [], "Plant $id has unknown zodiac groups.");
    check($meta['wishes'] !== [] && array_diff($meta['wishes'], array_keys($options['wish'])) === [], "Plant $id has unknown wishes.");
    check(in_array($meta['care'], ['easy', 'medium', 'hard'], true), "Plant $id has an unknown care level.");
    check($meta['light'] !== [] && array_diff($meta['light'], array_keys($options['light'])) === [], "Plant $id has unknown light needs.");
    check($meta['position'] !== [] && array_diff($meta['position'], ['floor', 'shelf']) === [], "Plant $id has unknown positions.");
    check(is_string($meta['meaning']) && $meta['meaning'] !== '', "Plant $id needs a meaning.");
}
foreach (array_keys($options['light']) as $light) {
    foreach (array_keys(fengshui_element_labels()) as $element) {
        $count = count(array_filter($notes, static fn (array $meta): bool => in_array($light, $meta['light'], true) && plant_element_score($meta, $element) > 0));
        check($count >= 2, "Mệnh $element with $light light needs at least two candidate plants.");
    }
    foreach (array_keys(zodiac_element_labels()) as $element) {
        $count = count(array_filter($notes, static fn (array $meta): bool => in_array($light, $meta['light'], true) && plant_zodiac_score($meta, $element) > 0));
        check($count >= 2, "Zodiac $element with $light light needs at least two candidate plants.");
    }
}

// Lunar calendar, including Vietnam's 1985 Tết and leap months.
$lunarDates = ['2024-02-10' => [1, 1, 2024, false], '2024-02-09' => [30, 12, 2023, false], '1985-01-21' => [1, 1, 1985, false],
    '1985-01-20' => [30, 12, 1984, false], '2026-02-17' => [1, 1, 2026, false], '2026-02-16' => [29, 12, 2025, false],
    '2023-03-22' => [1, 2, 2023, true], '2020-06-23' => [3, 5, 2020, false], '2000-02-05' => [1, 1, 2000, false]];
foreach ($lunarDates as $date => $expected) {
    [$year, $month, $day] = array_map('intval', explode('-', $date));
    check(array_values(solar_to_lunar($day, $month, $year)) === $expected, "Wrong lunar date for $date.");
}

// Nạp âm by lunar year, checked against the published 60-year table.
$expected = [1984 => ['kim', 'Giáp Tý', 'Hải Trung Kim'], 1986 => ['hoa', 'Bính Dần', 'Lư Trung Hỏa'], 1988 => ['moc', 'Mậu Thìn', 'Đại Lâm Mộc'],
    1990 => ['tho', 'Canh Ngọ', 'Lộ Bàng Thổ'], 1995 => ['hoa', 'Ất Hợi', 'Sơn Đầu Hỏa'], 1996 => ['thuy', 'Bính Tý', 'Giản Hạ Thủy'],
    2000 => ['kim', 'Canh Thìn', 'Bạch Lạp Kim'], 2003 => ['moc', 'Quý Mùi', 'Dương Liễu Mộc'], 2026 => ['thuy', 'Bính Ngọ', 'Thiên Hà Thủy'],
    1963 => ['kim', 'Quý Mão', 'Kim Bạch Kim'], 1977 => ['tho', 'Đinh Tỵ', 'Sa Trung Thổ']];
foreach ($expected as $year => [$element, $canChi, $napAm]) {
    $profile = lunar_year_profile($year);
    check([$profile['element'], $profile['can_chi'], $profile['nap_am']] === [$element, $canChi, $napAm], "Wrong lunar profile for $year.");
}
check(element_relation_score('thuy', 'moc') === 4 && element_relation_score('moc', 'moc') === 3, 'Generation and harmony scores.');
check(element_relation_score('kim', 'moc') === -4 && element_relation_score('moc', 'kim') === 0, 'Control applies one way only.');

// Zodiac boundaries.
foreach ([[1, 1, 'Ma Kết'], [19, 1, 'Ma Kết'], [20, 1, 'Bảo Bình'], [29, 2, 'Song Ngư'], [21, 3, 'Bạch Dương'], [22, 7, 'Cự Giải'],
    [23, 7, 'Sư Tử'], [22, 11, 'Nhân Mã'], [21, 12, 'Nhân Mã'], [22, 12, 'Ma Kết'], [31, 12, 'Ma Kết']] as [$day, $month, $name]) {
    check(zodiac_sign($day, $month)['name'] === $name, "Wrong zodiac sign for $day/$month.");
}

// Input validation for both modes.
$common = ['wish' => 'tai-loc', 'habit' => 'busy', 'light' => 'low', 'position' => 'shelf', 'note' => " Nhà có\nmèo\x07 "];
$zodiacForm = normalize_advisor_input(['birth_day' => '15', 'birth_month' => '8'] + $common, 'zodiac', '2026-11-14');
check($zodiacForm['errors'] === [] && $zodiacForm['input']['birth_day'] === 15 && $zodiacForm['input']['mode'] === 'zodiac', 'Valid zodiac input is accepted.');
check($zodiacForm['input']['note'] === 'Nhà có mèo', 'Notes are flattened and stripped of control characters.');
check(normalize_advisor_input(['birth_day' => '29', 'birth_month' => '2'] + $common, 'zodiac', '2026-11-14')['errors'] === [], '29 February is a valid birthday.');
check(normalize_advisor_input(['birth_day' => '1', 'birth_month' => '1'] + $common, 'unknown', '2026-11-14')['values']['mode'] === 'zodiac', 'Unknown modes fall back to zodiac.');
$spiritual = ['birth_day' => '25', 'birth_month' => '1', 'birth_year' => '1996', 'direction' => 'dong'] + $common;
$spiritualForm = normalize_advisor_input($spiritual, 'spiritual', '2026-11-14');
check($spiritualForm['errors'] === [] && $spiritualForm['input']['direction'] === 'dong' && $spiritualForm['input']['birth_date'] === '1996-01-25', 'Valid spiritual input is accepted.');
check(normalize_advisor_input(['birth_day' => '29', 'birth_month' => '2', 'birth_year' => '1996'] + $spiritual, 'spiritual', '2026-11-14')['errors'] === [], '29 February in a leap year is valid.');
check(mb_strlen(normalize_advisor_input(['note' => str_repeat('á', 500)] + $spiritual, 'spiritual', '2026-11-14')['input']['note'], 'UTF-8') === 200, 'Notes are bounded.');
check(normalize_advisor_input(['position' => '', 'direction' => ''] + $spiritual, 'spiritual', '2026-11-14')['input']['direction'] === 'any', 'Direction and position default to any.');
$bad = [
    ['zodiac', ['birth_day' => '31', 'birth_month' => '4'], 'birth_day'], ['zodiac', ['birth_day' => '0', 'birth_month' => '4'], 'birth_day'],
    ['zodiac', ['birth_day' => '3', 'birth_month' => '13'], 'birth_month'], ['zodiac', ['birth_day' => ['3'], 'birth_month' => '1'], 'birth_day'],
    ['spiritual', ['birth_year' => '1919'], 'birth_year'], ['spiritual', ['birth_year' => '2027'], 'birth_year'], ['spiritual', ['birth_year' => '95'], 'birth_year'],
    ['spiritual', ['birth_day' => '15', 'birth_month' => '11', 'birth_year' => '2026'], 'birth_day'], ['spiritual', ['birth_day' => '29', 'birth_month' => '2', 'birth_year' => '1995'], 'birth_day'],
    ['spiritual', ['birth_year' => ['1995']], 'birth_year'], ['spiritual', ['birth_month' => '0'], 'birth_month'],
    ['spiritual', ['direction' => 'giua'], 'direction'], ['spiritual', ['wish' => 'giau'], 'wish'], ['spiritual', ['light' => ['low']], 'light'],
];
foreach ($bad as [$mode, $override, $field]) {
    $source = $override + ($mode === 'zodiac' ? ['birth_day' => '15', 'birth_month' => '8'] + $common : $spiritual);
    check(isset(normalize_advisor_input($source, $mode, '2026-11-14')['errors'][$field]), "Invalid $field must be rejected in $mode mode.");
}

// Profiles.
$zodiacProfile = advisor_profile($zodiacForm['input']);
check($zodiacProfile['name'] === 'Sư Tử' && $zodiacProfile['element'] === 'fire' && $zodiacProfile['birth'] === '15/08', 'Zodiac profile from day and month.');
$beforeTet = advisor_profile($spiritualForm['input']);
check($beforeTet['lunar']['year'] === 1995 && $beforeTet['element'] === 'hoa' && $beforeTet['can_chi'] === 'Ất Hợi', 'Births before Tết use the previous lunar year.');
check($beforeTet['good_elements'] === ['moc', 'hoa'] && $beforeTet['avoid_element'] === 'thuy', 'Mệnh Hỏa favours Mộc and Hỏa and avoids Thủy.');
check($beforeTet['direction_element'] === 'moc', 'The east corner belongs to Mộc.');
check(advisor_profile(['birth_date' => '1996-02-19'] + $spiritualForm['input'])['element'] === 'thuy', 'Births after Tết use the solar year.');

// Rule-based suggestions respect light, position, and the chosen mode.
foreach ([$zodiacForm['input'], $spiritualForm['input']] as $input) {
    $profile = advisor_profile($input);
    $picks = rule_based_picks($plants, $notes, $input, $profile);
    check(count($picks) >= 2 && count($picks) <= 4, 'Rules suggest two to four plants in ' . $input['mode'] . ' mode.');
    foreach ($picks as $pick) {
        $meta = $notes[$pick['plant_id']];
        check(plant_fits_space($meta, $input), 'Rule picks must fit the light and position.');
        check(plant_affinity($meta, $profile) > 0, 'Rule picks must suit the person.');
        check(str_contains($pick['reason'], $meta['meaning']), 'Rule reasons explain the plant.');
    }
    check($picks === rule_based_picks($plants, $notes, $input, $profile), 'Rules are deterministic.');
}
check(str_contains(rule_based_picks($plants, $notes, $zodiacForm['input'], $zodiacProfile)[0]['reason'], 'Sư Tử'), 'Zodiac reasons mention the sign.');
$withDirection = rule_based_picks($plants, $notes, ['light' => 'indirect', 'position' => 'any'] + $spiritualForm['input'], $beforeTet);
check(str_contains(implode(' ', array_column($withDirection, 'reason')), 'hướng đông'), 'Spiritual reasons mention a matching corner direction.');
$busyPicks = array_column(rule_based_picks($plants, $notes, ['light' => 'bright', 'position' => 'any'] + $spiritualForm['input'], $beforeTet), 'plant_id');
check(!array_intersect($busyPicks, [63, 64]), 'Busy people are not offered hard-care plants first.');

// AI answers are validated against the catalog and the room.
$input = $spiritualForm['input'];
$profile = $beforeTet;
$answer = ['summary' => '<b>Chọn cây dễ chăm.</b>', 'picks' => [
    ['plant_id' => 53, 'reason' => '<script>x</script>Hợp bàn làm việc ít sáng.'],
    ['plant_id' => '59', 'reason' => 'Trúc phú quý mang tài lộc.'],
    ['plant_id' => 53, 'reason' => 'Trùng lặp.'],
    ['plant_id' => 99999, 'reason' => 'Không có trong danh mục.'],
    ['plant_id' => 63, 'reason' => 'Hoa hồng cần nắng.'],
    ['plant_id' => 46, 'reason' => ''],
]];
$validated = validate_ai_picks($answer, $plants, $notes, $input);
check(array_column($validated['picks'], 'plant_id') === [53, 59], 'Unknown, duplicate, empty, and light-mismatched picks are removed.');
check($validated['picks'][0]['reason'] === 'xHợp bàn làm việc ít sáng.' && $validated['summary'] === 'Chọn cây dễ chăm.', 'AI text is stripped of markup.');
foreach ([null, 'text', [], ['picks' => 'x'], ['picks' => [['plant_id' => 99999, 'reason' => 'x']]]] as $garbage) {
    check(validate_ai_picks($garbage, $plants, $notes, $input) === null, 'Unusable AI answers are rejected.');
}
$many = ['picks' => array_map(static fn (int $id): array => ['plant_id' => $id, 'reason' => 'Hợp.'], [22, 25, 28, 53, 59, 23])];
check(count(validate_ai_picks($many, $plants, $notes, $input)['picks']) === 4, 'At most four AI picks are kept.');
$prompt = advisor_user_prompt($input, $profile, $plants, $notes);
check(str_contains($prompt, '"plant_id":22') && str_contains($prompt, 'Nhà có mèo') && str_contains($prompt, 'Hướng Đông') && !str_contains($prompt, '@'), 'The spiritual prompt carries the catalog, corner, and note.');
$zodiacPrompt = advisor_user_prompt($zodiacForm['input'], $zodiacProfile, $plants, $notes);
check(str_contains($zodiacPrompt, 'Sư Tử') && str_contains($zodiacPrompt, '"zodiac"') && !str_contains($zodiacPrompt, '"elements"'), 'The zodiac prompt carries zodiac notes only.');
check(str_contains(advisor_system_prompt('zodiac'), 'hoàng đạo') && str_contains(advisor_system_prompt('spiritual'), 'good_elements'), 'System prompts follow the mode.');

// Orchestration: AI success, caching, failures, and rate limits.
$cache = sys_get_temp_dir() . '/plant-advisor-test-' . bin2hex(random_bytes(4));
$calls = 0;
$caller = static function (string $system, string $user) use (&$calls, $answer): array {
    $calls++;
    return $answer;
};
$result = advise_plants($input, $plants, $notes, $caller, $cache, 'test-model');
check($result['source'] === 'ai' && array_column($result['picks'], 'plant_id') === [53, 59] && $result['notice'] === '', 'Valid AI answers are used.');
$again = advise_plants($input, $plants, $notes, $caller, $cache, 'test-model');
check($calls === 1 && $again['picks'] === $result['picks'], 'Repeated questions are served from the cache.');
advise_plants(['note' => 'khác'] + $input, $plants, $notes, $caller, $cache, 'test-model');
check($calls === 2, 'Different questions miss the cache.');
$fallback = advise_plants(['wish' => 'binh-an'] + $input, $plants, $notes, static function (): never {
    throw new RuntimeException('timeout');
}, $cache, 'test-model');
check($fallback['source'] === 'rules' && $fallback['picks'] !== [] && str_contains($fallback['notice'], 'chưa phản hồi'), 'API failures fall back to rules.');
$fallback = advise_plants(['wish' => 'binh-an'] + $input, $plants, $notes, static fn (): string => 'not json', $cache, 'test-model');
check($fallback['source'] === 'rules' && $fallback['picks'] !== [], 'Invalid AI answers fall back to rules.');
$limited = advise_plants(['wish' => 'binh-an'] + $input, $plants, $notes, static function (): never {
    throw new AdvisorRateLimited('limit');
}, $cache, 'test-model');
check($limited['source'] === 'rules' && str_contains($limited['notice'], 'khá nhiều lần'), 'Rate limits explain the fallback.');
$offline = advise_plants($zodiacForm['input'], $plants, $notes);
check($offline['source'] === 'rules' && $offline['notice'] === '' && $offline['picks'] !== [] && $offline['profile']['mode'] === 'zodiac', 'Without an API key the rules answer quietly.');
check(advisor_rate_allow($cache . '/rate', 'client', 2, 60) && advisor_rate_allow($cache . '/rate', 'client', 2, 60)
    && !advisor_rate_allow($cache . '/rate', 'client', 2, 60) && advisor_rate_allow($cache . '/rate', 'other', 2, 60), 'Rate limits count per client.');
array_map('unlink', array_merge(glob($cache . '/*.json') ?: [], glob($cache . '/rate/*.json') ?: []));
@rmdir($cache . '/rate');
@rmdir($cache);

// Lắc quẻ: every stick has plants and verses, and a draw stays inside its stick.
$fortuneCandidates = fortune_candidates($plants, $notes);
check(array_keys($fortuneCandidates) === array_keys(fortune_sticks()), 'Every fortune stick needs candidate plants.');
foreach (fortune_sticks() as $key => $stick) {
    check(count($fortuneCandidates[$key]) >= 3 && count($stick['verses']) >= 2, "Fortune stick $key needs plants and verses.");
    foreach ($fortuneCandidates[$key] as $plant) {
        check(array_intersect($stick['wishes'], $notes[$plant['id']]['wishes']) !== [], "Plant {$plant['id']} does not belong to stick $key.");
        check(count($plant['facts']) === 7 && $plant['water'] !== '' && !str_starts_with($plant['water'], 'Tưới nước'), "Plant {$plant['id']} needs fortune facts and a watering note.");
    }
}
for ($round = 0; $round < 40; $round++) {
    $draw = fortune_draw($fortuneCandidates);
    check(in_array($draw['plant'], $fortuneCandidates[$draw['stick']], true) && in_array($draw['verse'], fortune_sticks()[$draw['stick']]['verses'], true)
        && in_array($draw['level'], fortune_levels(), true), 'Fortune draws stay inside the drawn stick.');
}
$first = fortune_draw($fortuneCandidates, static fn (int $min, int $max): int => $min);
check($first['stick'] === 'tai-loc' && $first['plant'] === $fortuneCandidates['tai-loc'][0] && $first['level'] === 'Đại cát', 'Fortune draws use the injected random source.');
check(fortune_draw([]) === null, 'An empty catalog draws nothing.');

echo "PASS: $checks plant advisor checks.\n";
