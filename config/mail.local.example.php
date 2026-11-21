<?php
declare(strict_types=1);

// Copy to mail.local.php. Leave 'host' empty to keep messages in .runtime/mail/ (development).
return [
    // Public address of the site, used in activation links. Required behind a real domain;
    // only localhost can be guessed from the request, so a forged Host header can never poison a link.
    'app_url' => 'https://example.com/Web503073',
    'host' => 'smtp.example.com',
    'port' => 587,
    // tls = STARTTLS (port 587), ssl = implicit TLS (port 465), none = plain text (only for a local test server).
    'encryption' => 'tls',
    'username' => 'REPLACE_WITH_SMTP_USERNAME',
    'password' => 'REPLACE_WITH_SMTP_PASSWORD',
    'from_email' => 'no-reply@example.com',
    'from_name' => 'Là Lá La',
];
