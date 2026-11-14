<?php
declare(strict_types=1);

// DeepSeek settings for the plant advisor. config/ai.local.php is ignored by Git.
// Environment variables take precedence; an empty DEEPSEEK_API_KEY disables the AI.
function load_ai_config(): array
{
    $localPath = __DIR__ . '/ai.local.php';
    $local = is_file($localPath) ? require $localPath : [];
    if (!is_array($local)) {
        throw new RuntimeException('config/ai.local.php must return an array.');
    }
    $defaults = ['api_key' => '', 'model' => 'deepseek-chat', 'base_url' => 'https://api.deepseek.com', 'timeout' => '20'];
    $config = [];
    foreach ($defaults as $key => $value) {
        $environment = getenv('DEEPSEEK_' . strtoupper($key));
        $config[$key] = trim($environment === false ? (string) ($local[$key] ?? $value) : $environment);
    }
    $config['timeout'] = max(5, min(60, (int) $config['timeout']));
    if (!str_starts_with($config['base_url'], 'https://')) {
        throw new RuntimeException('DeepSeek base_url must use HTTPS.');
    }
    $config['enabled'] = $config['api_key'] !== '' && $config['api_key'] !== 'REPLACE_WITH_DEEPSEEK_API_KEY';
    return $config;
}
