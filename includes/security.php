<?php
declare(strict_types=1);

/**
 * Sends the browser-hardening headers every PHP response carries.
 *
 * The CSP matches what the site actually loads: its own scripts, stylesheets, fonts and images
 * (blob: and data: for client-side image previews), no inline scripts, no third-party hosts,
 * and no framing. Inline styles stay allowed because pages and error screens use style attributes.
 */
function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: accelerometer=(), camera=(), geolocation=(), gyroscope=(), microphone=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Content-Security-Policy: ' . implode('; ', [
        "default-src 'self'",
        "script-src 'self'",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: blob:",
        "font-src 'self'",
        "connect-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
    ]));
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000');
    }
}
