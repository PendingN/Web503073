<?php
declare(strict_types=1);

// Copy to mail.local.php (ignored by Git). Without it, emails are written to .runtime/mail/ instead of being sent.
// Gmail: turn on 2-Step Verification, create an App Password, and paste it below (not your normal password).
return [
    'driver' => 'smtp',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-account@gmail.com',
    'password' => 'REPLACE_WITH_APP_PASSWORD',
    'from_address' => 'your-account@gmail.com',
    'from_name' => 'Là Lá La',
    // Public address of the site, used for links inside emails. Set it on a deployed server
    // (e.g. 'https://example.com/'); leave empty to build links from the current request.
    'app_url' => '',
];
