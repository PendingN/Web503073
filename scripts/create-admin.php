<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/auth.php';

function admin_input(string $environmentKey, string $prompt): string
{
    $value = getenv($environmentKey);
    if ($value !== false && $value !== '') {
        return $value;
    }
    fwrite(STDOUT, $prompt);
    $line = fgets(STDIN);
    if ($line === false) {
        throw new RuntimeException('Missing input.');
    }
    return rtrim($line, "\r\n");
}

try {
    $name = trim(admin_input('APP_ADMIN_NAME', 'Admin name: '));
    $email = strtolower(trim(admin_input('APP_ADMIN_EMAIL', 'Admin email: ')));
    $password = admin_input('APP_ADMIN_PASSWORD', 'Password (at least 8 characters, at most 72 bytes; input is visible): ');
    $errors = account_errors($name, $email);
    if (!valid_password($password)) {
        $errors[] = 'Password must have at least 8 characters and at most 72 bytes.';
    }
    if ($errors !== []) {
        throw new RuntimeException(implode(' ', $errors));
    }
    if (email_in_use($email)) {
        throw new RuntimeException('Email already exists. Use a separate email for the administrator.');
    }
    create_user($name, $email, $password, 'admin');
    fwrite(STDOUT, 'Administrator created: ' . $email . PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Cannot create administrator: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
