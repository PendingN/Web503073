<?php
declare(strict_types=1);

require_once __DIR__ . '/advisor-rules.php';

// "Lắc quẻ cây xanh": a light-hearted random plant draw on plant-advisor.php.
// Pure helpers so tests can load them without the database.

function fortune_sticks(): array
{
    return [
        'tai-loc' => ['label' => 'Tài lộc', 'color' => '#f2b705', 'wishes' => ['tai-loc'], 'verses' => [
            'Lộc lá đầy cành, ví dày như lá. Đặt cây này trên bàn làm việc, tiền vô như nước!',
            'Quẻ báo tài lộc gõ cửa. Tưới cây đều tay thì lộc cũng về đều tay.',
            'Năm nay hái ra tiền, nhớ chừa một góc xanh để may mắn ở lại lâu hơn.',
        ]],
        'binh-an' => ['label' => 'Bình an', 'color' => '#3fa45b', 'wishes' => ['binh-an'], 'verses' => [
            'Nhà yên cửa ấm, giấc ngủ êm như ru. Thêm chậu cây này cho không khí thêm trong lành.',
            'Bình an là lộc lớn nhất. Một góc xanh nhỏ, tâm trí nhẹ tênh.',
            'Quẻ lành! Cây khỏe thì người cũng khỏe, cả năm vui như Tết.',
        ]],
        'tinh-duyen' => ['label' => 'Tình duyên', 'color' => '#e8467c', 'wishes' => ['tinh-cam'], 'verses' => [
            'Hoa chưa kịp nở mà duyên đã tới gần. Chăm cây đi, người thương sắp ghé!',
            'Ai đang thương thì thương thêm, ai đang chờ thì sắp hết phải chờ.',
            'Tặng người thương một chậu cây, tình cảm xanh tươi quanh năm.',
        ]],
        'su-nghiep' => ['label' => 'Sự nghiệp', 'color' => '#2f7fd1', 'wishes' => ['su-nghiep'], 'verses' => [
            'Đường công danh rộng mở, deadline cũng hiền như cây trên bàn.',
            'Quẻ thăng tiến! Cây vươn cao tới đâu, sự nghiệp lên tới đó.',
            'Làm đâu thắng đó, họp đâu suôn đó. Đặt cây này cạnh laptop nhé!',
        ]],
        'gia-dao' => ['label' => 'Gia đạo', 'color' => '#8e44c9', 'wishes' => ['tinh-cam', 'binh-an'], 'verses' => [
            'Cả nhà sum vầy, cơm ngon canh ngọt. Cây xanh giữa nhà, tiếng cười rộn rã.',
            'Quẻ gia đạo: trong ấm ngoài êm, chậu cây nào cũng tươi.',
            'Nhà có cây xanh, ai đi đâu cũng muốn mau về.',
        ]],
    ];
}

function fortune_levels(): array
{
    return ['Đại cát', 'Thượng cát', 'Cát tường'];
}

// Plants available for each stick, using the advisor notes' wishes.
function fortune_candidates(array $plants, array $catalogNotes): array
{
    $candidates = [];
    foreach (fortune_sticks() as $key => $stick) {
        $candidates[$key] = [];
        foreach ($plants as $plant) {
            $meta = $catalogNotes[(int) $plant['id']] ?? null;
            if ($meta !== null && array_intersect($stick['wishes'], $meta['wishes']) !== []) {
                $candidates[$key][] = ['id' => (int) $plant['id'], 'name' => $plant['name'], 'image' => $plant['image'],
                    'meaning' => $meta['meaning'], 'facts' => fortune_plant_facts($plant, $meta), 'water' => fortune_water_note($plant)];
            }
        }
    }
    return array_filter($candidates);
}

// Short facts shown on the drawn plant: [label, value] pairs.
function fortune_plant_facts(array $plant, array $meta): array
{
    $options = plant_advisor_options();
    $lightLabels = ['bright' => 'Nhiều nắng', 'indirect' => 'Sáng gián tiếp', 'low' => 'Chịu bóng'];
    $positions = ['floor' => 'Góc sàn', 'shelf' => 'Bàn, kệ'];
    $join = static fn (array $keys, array $labels): string => implode(', ', array_map(static fn (string $key): string => $labels[$key], $keys));
    return [
        ['Nhóm cây', (string) $plant['category']],
        ['Ánh sáng', $join($meta['light'], $lightLabels)],
        ['Độ khó', ['easy' => 'Dễ chăm', 'medium' => 'Vừa phải', 'hard' => 'Cần chăm kỹ'][$meta['care']]],
        ['Đặt ở', $join($meta['position'], $positions)],
        ['Hợp mệnh', $join($meta['elements'], fengshui_element_labels())],
        ['Hợp nhóm cung', $join($meta['zodiac'], zodiac_element_labels())],
        ['Mang lại', $join($meta['wishes'], array_map(static fn (string $label): string => explode(',', $label)[0], $options['wish']))],
    ];
}

// The watering line from the plant's care notes, without its "Tưới nước:" prefix.
function fortune_water_note(array $plant): string
{
    foreach (preg_split('/\R+/u', trim((string) $plant['care'])) ?: [] as $line) {
        if (preg_match('/^Tưới nước:\s*(.+)$/u', trim($line), $match) === 1) {
            return $match[1];
        }
    }
    return '';
}

/** @param (callable(int, int): int)|null $random Defaults to random_int; tests inject a fixed source. */
function fortune_draw(array $candidates, ?callable $random = null): ?array
{
    if ($candidates === []) {
        return null;
    }
    $random ??= 'random_int';
    $sticks = fortune_sticks();
    $keys = array_keys($candidates);
    $key = $keys[$random(0, count($keys) - 1)];
    $plants = $candidates[$key];
    $verses = $sticks[$key]['verses'];
    $levels = fortune_levels();
    return [
        'stick' => $key,
        'label' => $sticks[$key]['label'],
        'level' => $levels[$random(0, count($levels) - 1)],
        'verse' => $verses[$random(0, count($verses) - 1)],
        'plant' => $plants[$random(0, count($plants) - 1)],
    ];
}
