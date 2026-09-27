<?php
declare(strict_types=1);

// PHP's built-in server ignores .htaccess. Keep the development server from
// serving repository metadata, local secrets, or server-only directories.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($requestPath)) {
    http_response_code(400);
    exit;
}

$path = ltrim(str_replace('\\', '/', rawurldecode($requestPath)), '/');
$blockedPatterns = [
    '#(?:^|/)\.git(?:/|$)#i',
    '#(?:^|/)\.runtime(?:/|$)#i',
    '#(?:^|/)(?:\.env(?:\.[^/]*)?|\.gitignore|\.htaccess)(?:/|$)#i',
    '#(?:^|/)(?:AGENTS|README)(?:\.[^/]*)?$#i',
    '#(?:^|/)(?:config|database|scripts|tests)(?:/|$)#i',
    '#\.(?:bak|conf|dist|ini|log|old|orig|save|sql|sql\.gz|swp)$#i',
];

foreach ($blockedPatterns as $pattern) {
    if (preg_match($pattern, $path) === 1) {
        http_response_code(404);
        exit;
    }
}

return false;
