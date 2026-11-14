<?php
declare(strict_types=1);

require_once __DIR__ . '/lunar-calendar.php';

// Pure rules for the plant advisor's two modes: no database, session, or network access,
// so tests can load them directly.
//   zodiac    - "Chiêm tinh học": the 12 western zodiac signs from day and month of birth.
//   spiritual - "Một chút tâm linh": lunar year, Can Chi, Ngũ hành and the room corner's direction.

function advisor_modes(): array
{
    return [
        'zodiac' => ['label' => 'Chiêm tinh học', 'hint' => 'Tham khảo · 12 cung hoàng đạo'],
        'spiritual' => ['label' => 'Một chút tâm linh', 'hint' => 'Tham khảo · Ngũ hành & hướng đặt'],
    ];
}

function fengshui_element_labels(): array
{
    return ['kim' => 'Kim', 'thuy' => 'Thủy', 'moc' => 'Mộc', 'hoa' => 'Hỏa', 'tho' => 'Thổ'];
}

// Key generates value (tương sinh): Kim sinh Thủy, Thủy sinh Mộc, ...
function fengshui_generates(): array
{
    return ['kim' => 'thuy', 'thuy' => 'moc', 'moc' => 'hoa', 'hoa' => 'tho', 'tho' => 'kim'];
}

// Key controls value (tương khắc): Kim khắc Mộc, Mộc khắc Thổ, ...
function fengshui_controls(): array
{
    return ['kim' => 'moc', 'moc' => 'tho', 'tho' => 'thuy', 'thuy' => 'hoa', 'hoa' => 'kim'];
}

function zodiac_element_labels(): array
{
    return ['fire' => 'Lửa', 'earth' => 'Đất', 'air' => 'Khí', 'water' => 'Nước'];
}

// Fire and air feed each other; earth and water do too.
function zodiac_companion(string $element): string
{
    return ['fire' => 'air', 'air' => 'fire', 'earth' => 'water', 'water' => 'earth'][$element];
}

// Each sign starts on [month, day] and lasts until the next sign starts.
function zodiac_signs(): array
{
    return [
        ['start' => [1, 20], 'name' => 'Bảo Bình', 'element' => 'air', 'traits' => 'độc lập, sáng tạo, thích điều mới lạ'],
        ['start' => [2, 19], 'name' => 'Song Ngư', 'element' => 'water', 'traits' => 'mơ mộng, tinh tế, giàu lòng trắc ẩn'],
        ['start' => [3, 21], 'name' => 'Bạch Dương', 'element' => 'fire', 'traits' => 'nhiệt huyết, thẳng thắn, thích dẫn đầu'],
        ['start' => [4, 20], 'name' => 'Kim Ngưu', 'element' => 'earth', 'traits' => 'kiên nhẫn, chung thủy, yêu cái đẹp bền vững'],
        ['start' => [5, 21], 'name' => 'Song Tử', 'element' => 'air', 'traits' => 'hoạt bát, tò mò, giỏi giao tiếp'],
        ['start' => [6, 21], 'name' => 'Cự Giải', 'element' => 'water', 'traits' => 'tình cảm, chu đáo, gắn bó với gia đình'],
        ['start' => [7, 23], 'name' => 'Sư Tử', 'element' => 'fire', 'traits' => 'tự tin, hào phóng, thích nổi bật'],
        ['start' => [8, 23], 'name' => 'Xử Nữ', 'element' => 'earth', 'traits' => 'tỉ mỉ, ngăn nắp, cầu toàn'],
        ['start' => [9, 23], 'name' => 'Thiên Bình', 'element' => 'air', 'traits' => 'hòa nhã, công bằng, yêu sự cân bằng'],
        ['start' => [10, 23], 'name' => 'Bọ Cạp', 'element' => 'water', 'traits' => 'sâu sắc, quyết liệt, trung thành'],
        ['start' => [11, 22], 'name' => 'Nhân Mã', 'element' => 'fire', 'traits' => 'lạc quan, phóng khoáng, thích khám phá'],
        ['start' => [12, 22], 'name' => 'Ma Kết', 'element' => 'earth', 'traits' => 'kỷ luật, thực tế, bền bỉ'],
    ];
}

function zodiac_sign(int $day, int $month): array
{
    $signs = zodiac_signs();
    $current = $signs[11];
    foreach ($signs as $sign) {
        if ([$month, $day] >= $sign['start']) {
            $current = $sign;
        }
    }
    return $current;
}

function corner_directions(): array
{
    return ['any' => ['Chưa rõ hướng', ''], 'dong' => ['Hướng Đông', 'moc'], 'dong-nam' => ['Hướng Đông Nam', 'moc'],
        'nam' => ['Hướng Nam', 'hoa'], 'tay-nam' => ['Hướng Tây Nam', 'tho'], 'tay' => ['Hướng Tây', 'kim'],
        'tay-bac' => ['Hướng Tây Bắc', 'kim'], 'bac' => ['Hướng Bắc', 'thuy'], 'dong-bac' => ['Hướng Đông Bắc', 'tho']];
}

function plant_advisor_options(): array
{
    return [
        'wish' => ['tai-loc' => 'Tài lộc, may mắn', 'binh-an' => 'Bình an, sức khỏe', 'su-nghiep' => 'Sự nghiệp, học tập', 'tinh-cam' => 'Tình cảm, gia đạo'],
        'habit' => ['busy' => 'Bận rộn, hay quên tưới', 'balanced' => 'Chăm được vừa phải', 'attentive' => 'Tỉ mỉ, thích chăm cây'],
        'light' => ['bright' => 'Nhiều ánh sáng tự nhiên', 'indirect' => 'Ánh sáng gián tiếp, dịu nhẹ', 'low' => 'Ít ánh sáng tự nhiên'],
        'position' => ['any' => 'Ở đâu cũng được', 'floor' => 'Trên sàn, ở một góc phòng', 'shelf' => 'Trên bàn hoặc kệ nhỏ'],
        'direction' => array_map(static fn (array $direction): string => $direction[0], corner_directions()),
        'month' => array_combine(range(1, 12), array_map(static fn (int $month): string => 'Tháng ' . $month, range(1, 12))),
    ];
}

function lunar_year_profile(int $lunarYear): array
{
    $stems = ['Giáp', 'Ất', 'Bính', 'Đinh', 'Mậu', 'Kỷ', 'Canh', 'Tân', 'Nhâm', 'Quý'];
    $branches = ['Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ', 'Ngọ', 'Mùi', 'Thân', 'Dậu', 'Tuất', 'Hợi'];
    $napAm = ['Hải Trung Kim', 'Lư Trung Hỏa', 'Đại Lâm Mộc', 'Lộ Bàng Thổ', 'Kiếm Phong Kim', 'Sơn Đầu Hỏa',
        'Giản Hạ Thủy', 'Thành Đầu Thổ', 'Bạch Lạp Kim', 'Dương Liễu Mộc', 'Tuyền Trung Thủy', 'Ốc Thượng Thổ',
        'Tích Lịch Hỏa', 'Tùng Bách Mộc', 'Trường Lưu Thủy', 'Sa Trung Kim', 'Sơn Hạ Hỏa', 'Bình Địa Mộc',
        'Bích Thượng Thổ', 'Kim Bạch Kim', 'Phú Đăng Hỏa', 'Thiên Hà Thủy', 'Đại Trạch Thổ', 'Thoa Xuyến Kim',
        'Tang Đố Mộc', 'Đại Khê Thủy', 'Sa Trung Thổ', 'Thiên Thượng Hỏa', 'Thạch Lựu Mộc', 'Đại Hải Thủy'];
    $cycle = (($lunarYear - 4) % 60 + 60) % 60;
    $stem = $cycle % 10;
    $branch = $cycle % 12;
    // Classic shortcut: stem value + branch value, folded into 1..5.
    $value = intdiv($stem, 2) + 1 + [0, 0, 1, 1, 2, 2, 0, 0, 1, 1, 2, 2][$branch];
    $value = $value > 5 ? $value - 5 : $value;
    return [
        'lunar_year' => $lunarYear,
        'can_chi' => $stems[$stem] . ' ' . $branches[$branch],
        'element' => [1 => 'kim', 2 => 'thuy', 3 => 'hoa', 4 => 'tho', 5 => 'moc'][$value],
        'nap_am' => $napAm[intdiv($cycle, 2)],
    ];
}

// How a plant element affects another element: 4 tương sinh, 3 tương hợp, -4 tương khắc, 0 neutral.
function element_relation_score(string $plantElement, string $personElement): int
{
    if ($plantElement === $personElement) {
        return 3;
    }
    if ((fengshui_generates()[$plantElement] ?? '') === $personElement) {
        return 4;
    }
    if ((fengshui_controls()[$plantElement] ?? '') === $personElement) {
        return -4;
    }
    return 0;
}

function plant_element_score(array $meta, string $personElement): int
{
    return max(array_map(static fn (string $element): int => element_relation_score($element, $personElement), $meta['elements']));
}

// 3 when the plant shares the sign's element, 1 for the companion element, otherwise 0.
function plant_zodiac_score(array $meta, string $signElement): int
{
    return in_array($signElement, $meta['zodiac'], true) ? 3 : (in_array(zodiac_companion($signElement), $meta['zodiac'], true) ? 1 : 0);
}

function advisor_profile(array $input): array
{
    if ($input['mode'] === 'zodiac') {
        $sign = zodiac_sign($input['birth_day'], $input['birth_month']);
        return ['mode' => 'zodiac', 'birth' => sprintf('%02d/%02d', $input['birth_day'], $input['birth_month'])] + $sign;
    }
    [$year, $month, $day] = array_map('intval', explode('-', $input['birth_date']));
    $lunar = solar_to_lunar($day, $month, $year);
    $profile = ['mode' => 'spiritual', 'birth' => sprintf('%02d/%02d/%d', $day, $month, $year), 'lunar' => $lunar] + lunar_year_profile($lunar['year']);
    $person = $profile['element'];
    $profile['good_elements'] = [array_search($person, fengshui_generates(), true), $person];
    $profile['avoid_element'] = array_search($person, fengshui_controls(), true);
    $profile['direction'] = $input['direction'];
    $profile['direction_element'] = corner_directions()[$input['direction']][1];
    return $profile;
}

function normalize_advisor_input(array $source, string $mode, string $today): array
{
    $options = plant_advisor_options();
    $mode = isset(advisor_modes()[$mode]) ? $mode : 'zodiac';
    $fields = $mode === 'zodiac' ? ['birth_day', 'birth_month'] : ['birth_day', 'birth_month', 'birth_year', 'direction'];
    $values = ['mode' => $mode];
    $errors = [];
    foreach (array_merge($fields, ['wish', 'habit', 'light', 'position']) as $field) {
        $value = is_string($source[$field] ?? null) ? trim($source[$field]) : '';
        $values[$field] = mb_substr($value, 0, 10, 'UTF-8');
    }
    $values['direction'] = $mode === 'spiritual' && $values['direction'] === '' ? 'any' : ($values['direction'] ?? '');
    $values['position'] = $values['position'] === '' ? 'any' : $values['position'];

    $day = filter_var($values['birth_day'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]);
    $month = filter_var($values['birth_month'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
    if ($month === false) {
        $errors['birth_month'] = 'Hãy chọn tháng sinh.';
    }
    if ($mode === 'zodiac') {
        // 2000 is a leap year, so 29/02 stays valid.
        if ($day === false || ($month !== false && !checkdate($month, $day, 2000))) {
            $errors['birth_day'] = 'Ngày sinh không hợp lệ.';
        }
    } else {
        $currentYear = (int) substr($today, 0, 4);
        $year = preg_match('/^\d{4}$/', $values['birth_year']) === 1 ? (int) $values['birth_year'] : 0;
        if ($year < 1920 || $year > $currentYear) {
            $errors['birth_year'] = 'Nhập năm sinh từ 1920 đến ' . $currentYear . '.';
        }
        if ($day === false || ($month !== false && !checkdate($month, $day, $year >= 1920 ? $year : 2000))) {
            $errors['birth_day'] = 'Ngày sinh không hợp lệ.';
        } elseif ($month !== false && $year >= 1920 && sprintf('%04d-%02d-%02d', $year, $month, $day) > $today) {
            $errors['birth_day'] = 'Ngày sinh không được sau hôm nay.';
        }
    }
    foreach (['direction', 'wish', 'habit', 'light', 'position'] as $field) {
        if (($field !== 'direction' || $mode === 'spiritual') && !isset($options[$field][$values[$field]])) {
            $errors[$field] = 'Hãy chọn một mục trong danh sách.';
        }
    }
    $note = is_string($source['note'] ?? null) ? $source['note'] : '';
    $note = trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[\x00-\x1F\x7F]/u', ' ', $note)));
    $values['note'] = mb_substr($note, 0, 200, 'UTF-8');
    if ($errors !== []) {
        return ['values' => $values, 'errors' => $errors, 'input' => null];
    }
    $input = $values;
    $input['birth_day'] = (int) $values['birth_day'];
    $input['birth_month'] = (int) $values['birth_month'];
    if ($mode === 'spiritual') {
        $input['birth_year'] = (int) $values['birth_year'];
        $input['birth_date'] = sprintf('%04d-%02d-%02d', $input['birth_year'], $input['birth_month'], $input['birth_day']);
    }
    return ['values' => $values, 'errors' => [], 'input' => $input];
}

function plant_fits_room(array $meta, array $roomInput): bool
{
    return ($roomInput['light'] === 'all' || in_array($roomInput['light'], $meta['light'], true))
        && ($roomInput['position'] === 'any' || in_array($roomInput['position'], $meta['position'], true));
}

function plant_fits_space(array $meta, array $input): bool
{
    return $input['light'] !== 'all' && plant_fits_room($meta, $input);
}

// Score how well a plant suits the person in the chosen mode; 0 or less means it does not suit.
function plant_affinity(array $meta, array $profile): int
{
    if ($profile['mode'] === 'zodiac') {
        return plant_zodiac_score($meta, $profile['element']);
    }
    $score = plant_element_score($meta, $profile['element']);
    if ($score > 0 && $profile['direction_element'] !== ''
        && max(array_map(static fn (string $element): int => element_relation_score($element, $profile['direction_element']), $meta['elements'])) > 0) {
        $score += 2;
    }
    return $score;
}

function rule_reason(array $meta, array $input, array $profile): string
{
    $parts = [];
    if ($profile['mode'] === 'zodiac') {
        $labels = zodiac_element_labels();
        $parts[] = in_array($profile['element'], $meta['zodiac'], true)
            ? 'Mang năng lượng nhóm ' . $labels[$profile['element']] . ', đồng điệu với cung ' . $profile['name'] . '.'
            : 'Năng lượng nhóm ' . $labels[zodiac_companion($profile['element'])] . ' nâng đỡ cung ' . $profile['name'] . ' của bạn.';
    } else {
        $labels = fengshui_element_labels();
        $person = $profile['element'];
        $best = '';
        $bestScore = PHP_INT_MIN;
        foreach ($meta['elements'] as $element) {
            $score = element_relation_score($element, $person);
            if ($score > $bestScore) {
                [$best, $bestScore] = [$element, $score];
            }
        }
        $parts[] = $bestScore === 4
            ? 'Hành ' . $labels[$best] . ' sinh ' . $labels[$person] . ', tương sinh với mệnh của bạn.'
            : 'Cùng hành ' . $labels[$person] . ', tương hợp với mệnh của bạn.';
        $direction = $profile['direction_element'];
        if ($direction !== '' && plant_affinity($meta, $profile) > plant_element_score($meta, $person)) {
            $parts[] = 'Hợp với ' . mb_strtolower(corner_directions()[$profile['direction']][0], 'UTF-8') . ' (hành ' . $labels[$direction] . ').';
        }
    }
    $parts[] = $meta['meaning'];
    if ($input['habit'] === 'busy' && $meta['care'] === 'easy') {
        $parts[] = 'Dễ chăm, chịu được khi bạn lỡ quên tưới.';
    }
    return implode(' ', $parts);
}

// Deterministic fallback used when the AI is not configured, busy, or returns unusable data.
function rule_based_picks(array $plants, array $catalogNotes, array $input, array $profile, int $limit = 4): array
{
    $scored = [];
    foreach ([true, false] as $respectPosition) {
        foreach ($plants as $plant) {
            $meta = $catalogNotes[(int) $plant['id']] ?? null;
            if ($meta === null || !in_array($input['light'], $meta['light'], true)
                || ($respectPosition && !plant_fits_space($meta, $input))) {
                continue;
            }
            $affinity = plant_affinity($meta, $profile);
            if ($affinity <= 0) {
                continue;
            }
            $score = $affinity
                + (in_array($input['wish'], $meta['wishes'], true) ? 3 : 0)
                + match ([$input['habit'], $meta['care']]) {
                    ['busy', 'easy'] => 2,
                    ['busy', 'hard'] => -3,
                    ['balanced', 'hard'] => -1,
                    ['attentive', 'medium'], ['attentive', 'hard'] => 1,
                    default => 0,
                };
            if ($score > 0) {
                $scored[(int) $plant['id']] = ['plant_id' => (int) $plant['id'], 'score' => $score, 'reason' => rule_reason($meta, $input, $profile)];
            }
        }
        // Only relax the position preference when it leaves almost nothing to suggest.
        if (count($scored) >= 2) {
            break;
        }
    }
    usort($scored, static fn (array $a, array $b): int => [$b['score'], $a['plant_id']] <=> [$a['score'], $b['plant_id']]);
    return array_map(static fn (array $pick): array => ['plant_id' => $pick['plant_id'], 'reason' => $pick['reason']], array_slice($scored, 0, $limit));
}

/**
 * Option labels for the practical room-based plant advisor.
 */
function room_advisor_options(): array
{
    $options = plant_advisor_options();
    return [
        'light' => ['all' => 'Tất cả mức ánh sáng'] + $options['light'],
        'position' => $options['position'],
    ];
}

/**
 * Normalize light and position inputs from GET or form parameters for practical room filtering.
 */
function normalize_room_advisor_input(array $source): array
{
    $options = plant_advisor_options();
    $rawLight = is_string($source['light'] ?? null) ? trim($source['light']) : '';
    $rawPosition = is_string($source['position'] ?? null) ? trim($source['position']) : '';

    $light = isset($options['light'][$rawLight]) ? $rawLight : 'all';
    $position = in_array($rawPosition, ['floor', 'shelf'], true) ? $rawPosition : 'any';

    return [
        'light' => $light,
        'position' => $position,
        'has_filters' => $light !== 'all' || $position !== 'any',
    ];
}

/**
 * Format human-readable badges describing active room conditions.
 */
function room_condition_badges(array $roomInput): array
{
    $normalized = normalize_room_advisor_input($roomInput);
    $options = room_advisor_options();
    $badges = [];
    if ($normalized['light'] !== 'all') {
        $badges[] = ['label' => 'Ánh sáng', 'value' => $options['light'][$normalized['light']]];
    }
    if ($normalized['position'] !== 'any') {
        $badges[] = ['label' => 'Vị trí', 'value' => $options['position'][$normalized['position']]];
    }
    return $badges;
}

function room_join_vietnamese_or(array $items): string
{
    $items = array_values($items);
    $count = count($items);
    if ($count === 0) {
        return '';
    }
    if ($count === 1) {
        return $items[0];
    }
    if ($count === 2) {
        return $items[0] . ' hoặc ' . $items[1];
    }
    $last = array_pop($items);
    return implode(', ', $items) . ' hoặc ' . $last;
}

/**
 * Build a practical recommendation reason from light/position fit, care level, and plant characteristics.
 */
function room_rule_reason(array $meta, array $roomInput): string
{
    $normalized = normalize_room_advisor_input($roomInput);
    $lightPhrases = [
        'bright' => 'ưa góc nhiều ánh sáng tự nhiên',
        'indirect' => 'hợp nơi có ánh sáng gián tiếp dịu nhẹ',
        'low' => 'chịu được góc ít ánh sáng tự nhiên',
    ];
    $lightShort = [
        'bright' => 'nhiều sáng',
        'indirect' => 'ánh sáng gián tiếp',
        'low' => 'ít sáng',
    ];
    $positionPhrases = [
        'floor' => 'đặt đẹp trên sàn hoặc góc phòng',
        'shelf' => 'gọn vừa trên bàn làm việc hoặc kệ nhỏ',
    ];

    if ($normalized['light'] !== 'all') {
        $lightText = $lightPhrases[$normalized['light']];
    } else {
        $supportedLights = array_values(array_intersect_key($lightShort, array_flip($meta['light'] ?? [])));
        $lightText = $supportedLights !== []
            ? 'thích nghi tốt với nơi ' . room_join_vietnamese_or($supportedLights)
            : 'thích nghi tốt trong không gian trong nhà';
    }

    $positions = $meta['position'] ?? ['shelf'];
    if ($normalized['position'] !== 'any') {
        $positionText = $positionPhrases[$normalized['position']];
    } elseif (count($positions) >= 2) {
        $positionText = 'đặt được cả trên sàn lẫn trên bàn hoặc kệ nhỏ';
    } else {
        $positionText = $positionPhrases[$positions[0] ?? 'shelf'] ?? 'dễ bố trí trong phòng';
    }

    $careText = match ($meta['care'] ?? 'medium') {
        'easy' => 'Rất dễ chăm sóc, phù hợp cho người mới bắt đầu hoặc bận rộn.',
        'medium' => 'Mức chăm sóc vừa phải, chỉ cần tưới và giữ ẩm đều đặn.',
        default => 'Cần chú ý ánh sáng và độ ẩm hơn một chút để cây phát triển ổn định.',
    };

    $firstSentence = mb_strtoupper(mb_substr($lightText, 0, 1, 'UTF-8'), 'UTF-8')
        . mb_substr($lightText, 1, null, 'UTF-8')
        . ', ' . $positionText . '.';

    $parts = [$firstSentence, $careText];
    if (is_string($meta['meaning'] ?? null) && $meta['meaning'] !== '') {
        $parts[] = $meta['meaning'];
    }
    return implode(' ', $parts);
}

/**
 * Pure deterministic room-only plant recommendations without requiring astrology or feng shui data.
 */
function room_based_picks(array $plants, array $catalogNotes, array $roomInput, int $limit = 8): array
{
    $normalized = normalize_room_advisor_input($roomInput);
    if (!$normalized['has_filters'] || $limit <= 0) {
        return [];
    }

    $careWeight = ['easy' => 30, 'medium' => 20, 'hard' => 10];
    $scored = [];
    foreach ($plants as $plant) {
        $id = (int) $plant['id'];
        $meta = $catalogNotes[$id] ?? null;
        if ($meta === null || !plant_fits_room($meta, $normalized)) {
            continue;
        }

        // Prioritize easy-care plants first; prefer plants tailored to the requested light/placement.
        $score = $careWeight[$meta['care']] ?? 0;
        if ($normalized['light'] !== 'all' && count($meta['light']) === 1) {
            $score += 2;
        }
        if ($normalized['position'] !== 'any' && count($meta['position']) === 1) {
            $score += 2;
        }

        $scored[] = [
            'plant_id' => $id,
            'score' => $score,
            'reason' => room_rule_reason($meta, $normalized),
        ];
    }

    usort(
        $scored,
        static fn (array $a, array $b): int => [$b['score'], $a['plant_id']] <=> [$a['score'], $b['plant_id']]
    );

    return array_map(
        static fn (array $pick): array => ['plant_id' => $pick['plant_id'], 'reason' => $pick['reason']],
        array_slice($scored, 0, $limit)
    );
}
