<?php
declare(strict_types=1);

// Solar to Vietnamese lunar date conversion, after Hồ Ngọc Đức's published algorithm
// (astronomical new moons and solar longitude, Vietnam time zone UTC+7).

function lunar_jd_from_date(int $day, int $month, int $year): int
{
    $a = intdiv(14 - $month, 12);
    $y = $year + 4800 - $a;
    $m = $month + 12 * $a - 3;
    $jd = $day + intdiv(153 * $m + 2, 5) + 365 * $y + intdiv($y, 4) - intdiv($y, 100) + intdiv($y, 400) - 32045;
    return $jd < 2299161 ? $day + intdiv(153 * $m + 2, 5) + 365 * $y + intdiv($y, 4) - 32083 : $jd;
}

function lunar_new_moon(int $k): float
{
    $t = $k / 1236.85;
    $t2 = $t * $t;
    $t3 = $t2 * $t;
    $dr = M_PI / 180;
    $jd = 2415020.75933 + 29.53058868 * $k + 0.0001178 * $t2 - 0.000000155 * $t3
        + 0.00033 * sin((166.56 + 132.87 * $t - 0.009173 * $t2) * $dr);
    $m = 359.2242 + 29.10535608 * $k - 0.0000333 * $t2 - 0.00000347 * $t3;
    $mpr = 306.0253 + 385.81691806 * $k + 0.0107306 * $t2 + 0.00001236 * $t3;
    $f = 21.2964 + 390.67050646 * $k - 0.0016528 * $t2 - 0.00000239 * $t3;
    $c1 = (0.1734 - 0.000393 * $t) * sin($m * $dr) + 0.0021 * sin(2 * $dr * $m)
        - 0.4068 * sin($mpr * $dr) + 0.0161 * sin($dr * 2 * $mpr) - 0.0004 * sin($dr * 3 * $mpr)
        + 0.0104 * sin($dr * 2 * $f) - 0.0051 * sin($dr * ($m + $mpr)) - 0.0074 * sin($dr * ($m - $mpr))
        + 0.0004 * sin($dr * (2 * $f + $m)) - 0.0004 * sin($dr * (2 * $f - $m)) - 0.0006 * sin($dr * (2 * $f + $mpr))
        + 0.0010 * sin($dr * (2 * $f - $mpr)) + 0.0005 * sin($dr * (2 * $mpr + $m));
    $deltaT = $t < -11
        ? 0.001 + 0.000839 * $t + 0.0002261 * $t2 - 0.00000845 * $t3 - 0.000000081 * $t * $t3
        : -0.000278 + 0.000265 * $t + 0.000262 * $t2;
    return $jd + $c1 - $deltaT;
}

function lunar_new_moon_day(int $k, float $timeZone): int
{
    return (int) floor(lunar_new_moon($k) + 0.5 + $timeZone / 24);
}

function lunar_sun_longitude_sector(int $dayNumber, float $timeZone): int
{
    $t = ($dayNumber - 0.5 - $timeZone / 24 - 2451545.0) / 36525;
    $t2 = $t * $t;
    $dr = M_PI / 180;
    $m = 357.52910 + 35999.05030 * $t - 0.0001559 * $t2 - 0.00000048 * $t * $t2;
    $l0 = 280.46645 + 36000.76983 * $t + 0.0003032 * $t2;
    $dl = (1.914600 - 0.004817 * $t - 0.000014 * $t2) * sin($dr * $m)
        + (0.019993 - 0.000101 * $t) * sin($dr * 2 * $m) + 0.000290 * sin($dr * 3 * $m);
    $longitude = ($l0 + $dl) * $dr;
    $longitude -= M_PI * 2 * floor($longitude / (M_PI * 2));
    return (int) floor($longitude / M_PI * 6);
}

function lunar_month_11(int $year, float $timeZone): int
{
    $k = (int) floor((lunar_jd_from_date(31, 12, $year) - 2415021) / 29.530588853);
    $newMoon = lunar_new_moon_day($k, $timeZone);
    return lunar_sun_longitude_sector($newMoon, $timeZone) >= 9 ? lunar_new_moon_day($k - 1, $timeZone) : $newMoon;
}

function lunar_leap_month_offset(int $month11, float $timeZone): int
{
    $k = (int) floor(($month11 - 2415021.076998695) / 29.530588853 + 0.5);
    $i = 1;
    $arc = lunar_sun_longitude_sector(lunar_new_moon_day($k + $i, $timeZone), $timeZone);
    do {
        $last = $arc;
        $i++;
        $arc = lunar_sun_longitude_sector(lunar_new_moon_day($k + $i, $timeZone), $timeZone);
    } while ($arc !== $last && $i < 14);
    return $i - 1;
}

/** @return array{day: int, month: int, year: int, leap: bool} */
function solar_to_lunar(int $day, int $month, int $year, float $timeZone = 7.0): array
{
    $dayNumber = lunar_jd_from_date($day, $month, $year);
    $k = (int) floor(($dayNumber - 2415021.076998695) / 29.530588853);
    $monthStart = lunar_new_moon_day($k + 1, $timeZone);
    if ($monthStart > $dayNumber) {
        $monthStart = lunar_new_moon_day($k, $timeZone);
    }
    $a11 = lunar_month_11($year, $timeZone);
    $b11 = $a11;
    if ($a11 >= $monthStart) {
        $lunarYear = $year;
        $a11 = lunar_month_11($year - 1, $timeZone);
    } else {
        $lunarYear = $year + 1;
        $b11 = lunar_month_11($year + 1, $timeZone);
    }
    $diff = (int) floor(($monthStart - $a11) / 29);
    $lunarMonth = $diff + 11;
    $leap = false;
    if ($b11 - $a11 > 365) {
        $leapDiff = lunar_leap_month_offset($a11, $timeZone);
        if ($diff >= $leapDiff) {
            $lunarMonth = $diff + 10;
            $leap = $diff === $leapDiff;
        }
    }
    if ($lunarMonth > 12) {
        $lunarMonth -= 12;
    }
    if ($lunarMonth >= 11 && $diff < 4) {
        $lunarYear--;
    }
    return ['day' => $dayNumber - $monthStart + 1, 'month' => $lunarMonth, 'year' => $lunarYear, 'leap' => $leap];
}
