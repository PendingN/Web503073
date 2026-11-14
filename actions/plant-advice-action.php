<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/plant-advisor.php';
require_once __DIR__ . '/../config/ai.php';
require_post();
require_csrf();

$form = normalize_advisor_input($_POST, request_string($_POST, 'mode'), date('Y-m-d'));
$mode = $form['values']['mode'];
$pageUrl = app_url('plant-advisor.php') . '?mode=' . $mode;
if ($form['input'] === null) {
    $_SESSION['plant_advice'][$mode] = ['values' => $form['values'], 'errors' => $form['errors']];
    redirect_to($pageUrl . '#advisor-form');
}

$input = $form['input'];
$plants = list_plants();
$catalogNotes = require __DIR__ . '/../data/plant-fengshui.php';
$runtime = dirname(__DIR__) . '/.runtime';
$config = load_ai_config();
$aiCaller = null;
if ($config['enabled']) {
    // Only cache misses reach this caller, so repeated questions do not count against the limit.
    $aiCaller = static function (string $system, string $user) use ($config, $runtime): mixed {
        $client = 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!advisor_rate_allow($runtime . '/plant-advice-rate', 'session:' . session_id(), 6, 600)
            || !advisor_rate_allow($runtime . '/plant-advice-rate', $client, 30, 3600)) {
            throw new AdvisorRateLimited('Plant advisor rate limit reached.');
        }
        return deepseek_chat_json($config, $system, $user);
    };
}
$advice = advise_plants($input, $plants, $catalogNotes, $aiCaller, $runtime . '/plant-advice', $config['model']);

$_SESSION['plant_advice'][$mode] = ['values' => $form['values'], 'errors' => [], 'advice' => $advice];
redirect_to($pageUrl . '#advisor-results');
