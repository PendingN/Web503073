<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../data/content.php';
require_once __DIR__ . '/../data/plants.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }

    return $_SESSION['csrf_token'];
}

function valid_csrf_token(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function favorite_ids(): array
{
    $favorites = $_SESSION['favorites'] ?? [];
    return is_array($favorites) ? array_values(array_map('intval', $favorites)) : [];
}

function is_favorite(int $plantId): bool
{
    return in_array($plantId, favorite_ids(), true);
}

function find_plant(int $plantId): ?array
{
    global $plants;

    foreach ($plants as $plant) {
        if ($plant['id'] === $plantId) {
            return $plant;
        }
    }

    return null;
}

function find_post(string $slug): ?array
{
    global $featuredPost, $posts;

    foreach ([$featuredPost, ...$posts] as $post) {
        if ($post['slug'] === $slug) {
            return $post;
        }
    }

    return null;
}

function set_flash(string $message): void
{
    $_SESSION['flash_message'] = $message;
}

function take_flash(): ?string
{
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return is_string($message) ? $message : null;
}

function safe_return_url(?string $returnTo, string $fallback = 'collection.php'): string
{
    if (!is_string($returnTo) || $returnTo === '' || str_contains($returnTo, "\r") || str_contains($returnTo, "\n")) {
        return $fallback;
    }

    $parts = parse_url($returnTo);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || str_starts_with($returnTo, '//')) {
        return $fallback;
    }

    return $returnTo;
}

function current_request_url(): string
{
    return $_SERVER['REQUEST_URI'] ?? 'collection.php';
}

