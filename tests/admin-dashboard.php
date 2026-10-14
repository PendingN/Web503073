<?php
declare(strict_types=1);

$dashboardGuest = new TestBrowser($base);
$response = $dashboardGuest->request('admin-dashboard.php');
check($response['status'] === 303 && $response['headers']['location'] === $prefix . '/login.php?return_to=' . rawurlencode($prefix . '/admin-dashboard.php'), 'Dashboard login must preserve its return URL.');
check($aliceBrowser->request('admin-dashboard.php')['status'] === 403, 'Ordinary users must not access the admin dashboard.');
$response = $adminBrowser->request('admin-dashboard.php');
check($response['status'] === 200 && str_contains($response['body'], 'aria-current="page" href="admin-dashboard.php"'), 'Dashboard must open for an admin and activate its navigation.');
check(str_contains($response['body'], '/css/admin.css?v=') && str_contains($response['body'], '/js/admin.js?v=')
    && !str_contains($response['body'], '/css/style.css'), 'Admin must load its compiled Tailwind assets separately from the public site.');
check(str_contains($adminBrowser->request('admin.php')['body'], '/css/admin.css?v='), 'User management must use the Tailwind admin stylesheet.');
check(str_contains($adminBrowser->request('profile.php')['body'], '/css/style.css?v=')
    && !str_contains($adminBrowser->request('profile.php')['body'], '/css/admin.css'), 'Public pages must keep their existing stylesheet.');
foreach (['users' => 'users', 'plants' => 'plants', 'posts' => 'blog_posts', 'favorites' => 'favorites'] as $stat => $table) {
    $expected = (int) db()->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
    check(str_contains($response['body'], 'data-stat="' . $stat . '">' . $expected . '</strong>'), 'Dashboard count must match the database: ' . $stat);
}
$returnBrowser = new TestBrowser($base);
$response = $returnBrowser->request('login.php', ['csrf_token' => $returnBrowser->token('login.php'), 'email' => 'admin@example.test', 'password' => 'TestAdmin123!', 'return_to' => $prefix . '/profile.php']);
check($response['headers']['location'] === $prefix . '/profile.php', 'Admin login must preserve an explicit local return URL.');
$response = $returnBrowser->request('login.php', ['csrf_token' => $returnBrowser->token('login.php'), 'email' => 'admin@example.test', 'password' => 'TestAdmin123!', 'return_to' => 'https://example.invalid']);
check($response['headers']['location'] === $prefix . '/admin-dashboard.php', 'Admin login must reject an external return URL.');
check(str_contains($adminBrowser->request('profile.php')['body'], 'href="admin-dashboard.php">Quản trị</a>'), 'Shared navigation must link admins to the dashboard.');

$previousTimezone = date_default_timezone_get();
date_default_timezone_set('Asia/Ho_Chi_Minh');
db()->beginTransaction();
try {
    $today = new DateTimeImmutable('today');
    $start = $today->modify('-6 days');
    db()->exec("UPDATE users SET created_at = '2000-01-01 00:00:00'");
    $statement = db()->prepare('UPDATE users SET created_at = ? WHERE id = ?');
    $statement->execute([$today->format('Y-m-d'), $admin['id']]);
    $statement->execute([$start->format('Y-m-d'), $alice['id']]);
    $statement->execute([$today->modify('+1 day')->format('Y-m-d'), $bob['id']]);
    $activity = admin_activity_statistics();
    check(count($activity['registrations']) === 7, 'Registration chart must always have seven days.');
    check($activity['registrations'][0] === ['date' => $start->format('Y-m-d'), 'total' => 1]
        && $activity['registrations'][6] === ['date' => $today->format('Y-m-d'), 'total' => 1], 'Registration chart must include both date boundaries.');
    check($activity['registrations'][3]['total'] === 0 && array_sum(array_column($activity['registrations'], 'total')) === 2, 'Registration chart must fill empty days and exclude older and future accounts.');
} finally {
    db()->rollBack();
    date_default_timezone_set($previousTimezone);
}

$unsafeName = '<script>alert(99)</script>';
$fixtureId = create_user($unsafeName, 'dashboard-escape@example.test', 'Dashboard123!');
try {
    $body = $adminBrowser->request('admin-dashboard.php')['body'];
    check(str_contains($body, htmlspecialchars($unsafeName, ENT_QUOTES, 'UTF-8')) && !str_contains($body, $unsafeName), 'Recent account names must be escaped on the dashboard.');
} finally {
    $statement = db()->prepare('DELETE FROM users WHERE id = ?');
    $statement->execute([$fixtureId]);
}

// The runner discards this database immediately after these empty-state checks.
db()->exec('DELETE FROM blog_posts');
db()->exec('DELETE FROM favorites');
$body = $adminBrowser->request('admin-dashboard.php')['body'];
check(str_contains($body, 'Chưa có bài viết.') && str_contains($body, 'data-stat="posts">0</strong>')
    && str_contains($body, 'data-stat="favorites">0</strong>'), 'Dashboard must render empty posts and favorites without errors.');
