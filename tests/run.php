<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/repositories.php';

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

function run_cli(array $arguments): string
{
    $process = proc_open(array_merge([PHP_BINARY], $arguments), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start PHP subprocess.');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException($output . $error);
    }
    return $output;
}

final class TestBrowser
{
    private $curl;
    private string $base;

    public function __construct(string $base)
    {
        $this->base = $base;
        $this->curl = curl_init();
        curl_setopt_array($this->curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 10]);
    }

    public function request(string $path, ?array $data = null, bool $multipart = false): array
    {
        $headers = [];
        curl_setopt($this->curl, CURLOPT_URL, $this->base . '/' . $path);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, null);
        curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, static function ($curl, string $line) use (&$headers): int {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
            return strlen($line);
        });
        if ($data !== null) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $multipart ? $data : http_build_query($data));
        }
        $body = curl_exec($this->curl);
        if ($body === false) {
            throw new RuntimeException('HTTP request failed: ' . curl_error($this->curl));
        }
        check(!str_contains($body, 'SQLSTATE') && !str_contains($body, 'Fatal error') && !str_contains($body, 'Warning:</b>'), 'Page exposes a runtime error: ' . $path);
        return ['status' => curl_getinfo($this->curl, CURLINFO_HTTP_CODE), 'body' => $body, 'headers' => $headers];
    }

    public function token(string $path): string
    {
        $response = $this->request($path);
        check($response['status'] === 200, 'Cannot open form: ' . $path);
        if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $response['body'], $match)) {
            throw new RuntimeException('No CSRF token on ' . $path);
        }
        return $match[1];
    }

    public function login(string $email, string $password): array
    {
        return $this->request('login.php', ['csrf_token' => $this->token('login.php'), 'email' => $email, 'password' => $password]);
    }
}

$testName = 'web503073_test_' . bin2hex(random_bytes(6));
$previousName = getenv('DB_NAME');
$server = null;
$webProcess = null;
$webPipes = [];
$log = tempnam(sys_get_temp_dir(), 'web503073-test-');
$failure = null;
try {
    if (!extension_loaded('curl') || !extension_loaded('pdo_mysql') || !extension_loaded('mbstring')) {
        throw new RuntimeException('Tests require curl, pdo_mysql, and mbstring.');
    }
    $server = create_database_connection(load_database_config(true), false);
    putenv('DB_NAME=' . $testName);
    run_cli(['scripts/setup-database.php']);
    check((int) db()->query('SELECT COUNT(*) FROM plants')->fetchColumn() === 40, 'Seed must import all 40 plants.');
    check(array_column(plants_by_ids([22, 21, 22, 99999]), 'id') === [22, 21], 'Bulk plant lookup must preserve order, remove duplicates and skip missing IDs.');

    $adminEnvironment = ['APP_ADMIN_NAME' => 'Test Admin', 'APP_ADMIN_EMAIL' => 'admin@example.test', 'APP_ADMIN_PASSWORD' => 'TestAdmin123!'];
    $previousAdmin = [];
    foreach ($adminEnvironment as $key => $value) {
        $previousAdmin[$key] = getenv($key);
        putenv($key . '=' . $value);
    }
    try {
        run_cli(['scripts/create-admin.php']);
    } finally {
        foreach ($previousAdmin as $key => $value) {
            putenv($value === false ? $key : $key . '=' . $value);
        }
    }
    $admin = find_user_by_email('admin@example.test');
    check($admin['role'] === 'admin' && password_verify('TestAdmin123!', $admin['password_hash']), 'CLI must create a hashed administrator account.');

    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if ($socket === false) {
        throw new RuntimeException('Cannot allocate test port: ' . $error);
    }
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $subdirectory = in_array('--subdirectory', $argv, true);
    $prefix = $subdirectory ? '/' . rawurlencode(basename(dirname(__DIR__))) : '';
    $documentRoot = $subdirectory ? dirname(dirname(__DIR__)) : dirname(__DIR__);
    $base = 'http://' . $address . $prefix;
    $webProcess = proc_open([PHP_BINARY, '-S', $address, '-t', $documentRoot], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $webPipes, dirname(__DIR__));
    if (!is_resource($webProcess)) {
        throw new RuntimeException('Cannot start test web server.');
    }
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $probe = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($probe !== false) {
            fclose($probe);
            break;
        }
        usleep(100000);
    }
    $guest = new TestBrowser($base);
    foreach (['index.php', 'dashboard.php', 'collection.php', 'plant-detail.php?id=22', 'blog.php', 'blog-post.php?slug=bat-dau-mot-goc-xanh-tu-dau', 'login.php', 'register.php'] as $path) {
        check($guest->request($path)['status'] === 200, 'Public route failed: ' . $path);
    }
    foreach (['favorites.php', 'profile.php', 'admin.php', 'admin-users.php', 'admin-plants.php', 'admin-blog.php'] as $path) {
        $response = $guest->request($path);
        check($response['status'] === 303 && str_starts_with($response['headers']['location'] ?? '', $prefix . '/login.php?return_to='), 'Guest route is not protected: ' . $path);
    }
    check($guest->request('plant-detail.php?id[]=22')['status'] === 404, 'Array plant ID must be rejected.');
    check($guest->request('plant-detail.php?id=99999')['status'] === 404, 'Missing plants must return 404.');
    check(str_contains($guest->request('collection.php?search=tr%E1%BA%A7u')['body'], 'Trầu'), 'Vietnamese plant search failed.');
    check(str_contains($guest->request('collection.php?search=%25')['body'], '0 kết quả'), 'LIKE wildcards must be treated literally.');
    check(str_contains($guest->request('collection.php?search=' . rawurlencode("' OR 1=1 --"))['body'], '0 kết quả'), 'SQL search input is not literal.');
    $category = 'Cây ăn quả';
    $expected = count(list_plants('', $category));
    check(str_contains($guest->request('collection.php?category=' . rawurlencode($category))['body'], $expected . ' kết quả'), 'Category filter failed.');
    foreach (['favorite-action.php', 'profile-action.php', 'user-action.php', 'blog-action.php', 'logout.php'] as $handler) {
        check($guest->request('actions/' . $handler)['status'] === 405, 'GET must not mutate: ' . $handler);
        check($guest->request('actions/' . $handler, [])['status'] === 403, 'Missing CSRF accepted: ' . $handler);
    }
    check($guest->request('register.php', ['name' => 'Guest'])['status'] === 403, 'Registration CSRF check failed.');
    check($guest->request('login.php', ['email' => 'admin@example.test'])['status'] === 403, 'Login CSRF check failed.');
    $registerToken = $guest->token('register.php');
    $invalid = $guest->request('register.php', ['csrf_token' => $registerToken, 'name' => 'A', 'email' => 'bad', 'password' => 'short', 'password_confirmation' => 'different']);
    check($invalid['status'] === 200 && find_user_by_email('bad') === null, 'Invalid registration created a user.');
    $aliceData = ['csrf_token' => $registerToken, 'name' => 'Alice Garden', 'email' => 'Alice@example.test', 'password' => 'Garden123!', 'password_confirmation' => 'Garden123!', 'role' => 'admin', 'return_to' => $prefix . '/collection.php?category=' . rawurlencode($category)];
    $response = $guest->request('register.php', $aliceData);
    check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $aliceData['return_to'], 'Registration must preserve a local return URL.');
    $alice = find_user_by_email('alice@example.test');
    check($alice !== null && $alice['role'] === 'user' && $alice['password_hash'] !== 'Garden123!' && password_verify('Garden123!', $alice['password_hash']), 'Registration must normalize email, hash password, and prevent role escalation.');
    check($guest->request('admin.php')['status'] === 403, 'Ordinary user accessed admin.');
    $profilePage = $guest->request('profile.php');
    check(!str_contains($profilePage['body'], 'class="dashboard-shell"') && !str_contains($profilePage['body'], 'class="dashboard-sidebar"'), 'Profile must use its own layout.');
    check(str_contains($profilePage['body'], 'aria-current="page" href="profile.php"'), 'Profile must activate the shared navigation tab.');
    check(str_contains($guest->request('collection.php')['body'], 'id="site-menu"'), 'Collection must expose the shared site navigation.');
    $backToCollection = $prefix . '/collection.php?search=' . rawurlencode('Trầu Bà');
    $detailPage = $guest->request('plant-detail.php?id=21&return_to=' . rawurlencode($backToCollection));
    check(str_contains($detailPage['body'], 'class="back-link" href="' . htmlspecialchars($backToCollection, ENT_QUOTES, 'UTF-8') . '"'), 'Plant detail must preserve collection search on the back link.');
    $aliceToken = $guest->token('profile.php');
    check($guest->request('actions/user-action.php', ['csrf_token' => $aliceToken, 'user_id' => $admin['id'], 'operation' => 'block'])['status'] === 403, 'Ordinary user accessed user management.');
    check($guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 99999])['status'] === 404, 'Unknown favorite was accepted.');
    $favoriteResponse = $guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 22, 'user_id' => $admin['id'], 'return_to' => '//example.invalid']);
    check(($favoriteResponse['headers']['location'] ?? '') === $prefix . '/collection.php', 'Favorite request failed or redirected externally: ' . $favoriteResponse['status'] . ' ' . ($favoriteResponse['headers']['location'] ?? 'no redirect'));
    check(user_favorite_ids((int) $alice['id']) === [22] && user_favorite_ids((int) $admin['id']) === [], 'Favorite ownership failed.');
    $guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 22]);
    check(user_favorite_ids((int) $alice['id']) === [], 'Favorite removal failed.');
    $guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 22]);
    $guest->request('actions/logout.php', ['csrf_token' => $aliceToken]);
    check($guest->request('profile.php')['status'] === 303, 'Logout did not clear authentication.');
    $duplicate = new TestBrowser($base);
    $aliceData['csrf_token'] = $duplicate->token('register.php');
    $response = $duplicate->request('register.php', $aliceData);
    check($response['status'] === 200 && str_contains($response['body'], 'đã được đăng ký'), 'Duplicate email must be rejected.');
    $aliceBrowser = new TestBrowser($base);
    check(str_contains($aliceBrowser->login('invalid-email', 'Garden123!')['body'], 'Email hoặc mật khẩu chưa đúng.'), 'Invalid email must show the login error.');
    check($aliceBrowser->login('alice@example.test', 'wrong-password')['status'] === 200, 'Bad password must fail.');
    $loginResponse = $aliceBrowser->login('alice@example.test', 'Garden123!');
    check($loginResponse['status'] === 303 && $loginResponse['headers']['location'] === $prefix . '/profile.php', 'Direct login must open the standalone profile.');
    check($aliceBrowser->request('login.php')['status'] === 200, 'Login form must remain accessible for a signed-in user.');
    $returnPath = $prefix . '/collection.php?category=' . rawurlencode($category) . '#results';
    $response = $aliceBrowser->request('login.php', ['csrf_token' => $aliceBrowser->token('login.php'), 'email' => 'alice@example.test', 'password' => 'Garden123!', 'return_to' => $returnPath]);
    check($response['status'] === 303 && $response['headers']['location'] === $returnPath, 'Login must preserve local filters and anchors.');
    $response = $aliceBrowser->request('login.php', ['csrf_token' => $aliceBrowser->token('login.php'), 'email' => 'alice@example.test', 'password' => 'Garden123!', 'return_to' => $prefix . '/']);
    check($response['headers']['location'] === $prefix . '/index.php', 'Site root return URL must resolve to the landing page.');
    check(str_contains($aliceBrowser->request('favorites.php')['body'], 'Cây Lưỡi Hổ'), 'Favorites did not persist across sessions.');
    $oldAliceBrowser = new TestBrowser($base);
    check($oldAliceBrowser->login('alice@example.test', 'Garden123!')['status'] === 303, 'Second session failed.');
    $aliceToken = $aliceBrowser->token('profile.php');
    $profile = ['csrf_token' => $aliceToken, 'name' => '<script>alert(1)</script>', 'email' => 'alice@example.test'];
    $aliceBrowser->request('actions/profile-action.php', $profile);
    check(str_contains($aliceBrowser->request('profile.php')['body'], '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Profile output must be escaped.');
    $profile['email'] = 'alice-new@example.test';
    $aliceBrowser->request('actions/profile-action.php', $profile);
    check(find_user((int) $alice['id'])['email'] === 'alice@example.test', 'Email changed without current password.');
    $profile['current_password'] = 'Garden123!';
    $profile['new_password'] = 'NewGarden123!';
    $profile['password_confirmation'] = 'NewGarden123!';
    $profile['name'] = 'Alice Updated';
    $aliceBrowser->request('actions/profile-action.php', $profile);
    check(find_user((int) $alice['id'])['email'] === 'alice-new@example.test' && password_verify('NewGarden123!', find_user((int) $alice['id'])['password_hash']), 'Profile credentials did not persist.');
    check($aliceBrowser->request('profile.php')['status'] === 200 && $oldAliceBrowser->request('profile.php')['status'] === 303, 'Credential changes must retain current session and invalidate other sessions.');

    $bobBrowser = new TestBrowser($base);
    $response = $bobBrowser->request('register.php', ['csrf_token' => $bobBrowser->token('register.php'), 'name' => 'Bob Garden', 'email' => 'bob@example.test', 'password' => 'BobGarden123!', 'password_confirmation' => 'BobGarden123!', 'return_to' => 'https://example.invalid']);
    check($response['status'] === 303 && $response['headers']['location'] === $prefix . '/profile.php', 'Registration must reject external redirects and open the profile.');
    $bob = find_user_by_email('bob@example.test');
    check(user_favorite_ids((int) $bob['id']) === [], 'Another user inherited favorites.');
    $bobToken = $bobBrowser->token('profile.php');
    $bobBrowser->request('actions/profile-action.php', ['csrf_token' => $bobToken, 'name' => 'Bob Garden', 'email' => 'alice-new@example.test', 'current_password' => 'BobGarden123!']);
    check(find_user((int) $bob['id'])['email'] === 'bob@example.test', 'Profile accepted a duplicate email.');

    $adminBrowser = new TestBrowser($base);
    check($adminBrowser->login('admin@example.test', 'TestAdmin123!')['status'] === 303, 'Admin login failed.');
    $adminToken = $adminBrowser->token('admin.php');
    check(str_contains($adminBrowser->request('admin-users.php?search=alice-new')['body'], 'Alice Updated'), 'Admin user search failed.');
    $update = ['csrf_token' => $adminToken, 'user_id' => $bob['id'], 'operation' => 'update', 'name' => 'Bob Edited', 'email' => 'bob@example.test', 'role' => 'admin'];
    check($adminBrowser->request('actions/user-action.php', $update)['status'] === 303, 'Admin edit failed.');
    check(find_user((int) $bob['id'])['name'] === 'Bob Edited' && find_user((int) $bob['id'])['role'] === 'user', 'Admin edit must persist and ignore unexpected role fields.');
    $update['email'] = 'alice-new@example.test';
    $adminBrowser->request('actions/user-action.php', $update);
    check(find_user((int) $bob['id'])['email'] === 'bob@example.test', 'Admin edit accepted duplicate email.');
    check($adminBrowser->request('admin-users.php?edit=' . $bob['id'])['status'] === 200, 'Admin edit form failed.');
    check($adminBrowser->request('admin-users.php?edit=99999')['status'] === 404, 'Missing user detail must return 404.');
    check($adminBrowser->request('actions/user-action.php', ['csrf_token' => $adminToken, 'user_id' => $admin['id'], 'operation' => 'block'])['status'] === 403, 'Administrator account could be blocked.');
    $block = ['csrf_token' => $adminToken, 'user_id' => $bob['id'], 'operation' => 'block'];
    check($adminBrowser->request('actions/user-action.php', $block)['status'] === 303 && find_user((int) $bob['id'])['status'] === 'blocked', 'Blocking failed.');
    $blockedLogin = new TestBrowser($base);
    check(str_contains($blockedLogin->login('bob@example.test', 'wrong-password')['body'], 'Email hoặc mật khẩu chưa đúng.'), 'Wrong password must not reveal blocked account status.');
    check(str_contains($blockedLogin->login('bob@example.test', 'BobGarden123!')['body'], 'đang bị khóa'), 'Blocked user could log in.');
    check(str_contains($adminBrowser->request('admin-users.php?status=blocked')['body'], 'Bob Edited'), 'Blocked-user filter failed.');
    $block['operation'] = 'unblock';
    $adminBrowser->request('actions/user-action.php', $block);
    check(find_user((int) $bob['id'])['status'] === 'active', 'Unblocking failed.');
    check($bobBrowser->request('profile.php')['status'] === 303, 'Old session returned after unblock.');
    check($blockedLogin->login('bob@example.test', 'BobGarden123!')['status'] === 303, 'Unblocked user cannot log in again.');

    require __DIR__ . '/blog.php';

    $update['email'] = 'bob-updated@example.test';
    check($adminBrowser->request('actions/user-action.php', $update)['status'] === 303, 'Administrator email update failed.');
    check(find_user((int) $bob['id'])['email'] === $update['email'], 'Administrator email update did not persist.');
    check($blockedLogin->request('profile.php')['status'] === 303, 'Administrator email change must invalidate the target user sessions.');

    // Plant management, blog moderation and role changes.
    $bobLive = new TestBrowser($base);
    check($bobLive->login('bob-updated@example.test', 'BobGarden123!')['status'] === 303, 'Bob login after email change failed.');
    $bobLiveToken = $bobLive->token('profile.php');
    $plantFields = ['csrf_token' => $adminToken, 'operation' => 'create', 'name' => 'Cây thử nghiệm', 'category' => 'Cây thử',
        'description' => 'Mô tả cây thử nghiệm cho bài kiểm tra.', 'care' => 'Tưới nước vừa phải mỗi tuần.'];
    $adminBrowser->request('actions/plant-action.php', $plantFields, true);
    check((int) db()->query("SELECT COUNT(*) FROM plants WHERE name = 'Cây thử nghiệm'")->fetchColumn() === 0, 'Plant create accepted without an image.');
    $plantImage = new CURLFile(dirname(__DIR__) . '/images/anh1.jpg', 'image/jpeg', 'plant.jpg');
    $adminBrowser->request('actions/plant-action.php', $plantFields + ['image' => $plantImage], true);
    $newPlant = db()->query("SELECT * FROM plants WHERE name = 'Cây thử nghiệm'")->fetch();
    check($newPlant !== false && str_starts_with($newPlant['image'], 'plants/') && is_file(dirname(__DIR__) . '/images/' . $newPlant['image']), 'Plant create with image failed.');
    $plantId = (int) ($newPlant['id'] ?? 0);
    check($plantId > 0 && $adminBrowser->request('plant-detail.php?id=' . $plantId)['status'] === 200, 'New plant page failed to open.');
    check(str_contains($adminBrowser->request('admin-plants.php?pq=' . rawurlencode('Cây thử'))['body'], 'Cây thử nghiệm'), 'Admin plant search failed.');
    $plantEdit = ['csrf_token' => $adminToken, 'operation' => 'update', 'plant_id' => $plantId, 'name' => 'Cây thử đã sửa',
        'category' => 'Cây thử', 'description' => 'Mô tả đã được cập nhật cho cây.', 'care' => 'Chăm sóc đã được cập nhật.'];
    $adminBrowser->request('actions/plant-action.php', $plantEdit, true);
    check(find_plant($plantId)['name'] === 'Cây thử đã sửa' && find_plant($plantId)['image'] === $newPlant['image'], 'Plant update failed or dropped its image.');
    $adminBrowser->request('actions/plant-action.php', ['category' => 'x'] + $plantEdit, true);
    check(find_plant($plantId)['category'] === 'Cây thử', 'Plant update accepted an invalid category.');
    check($adminBrowser->request('admin-plants.php?plant=' . $plantId)['status'] === 200, 'Plant edit form failed.');
    check($adminBrowser->request('admin-plants.php?plant=99999')['status'] === 404, 'Missing plant must return 404.');
    check($bobLive->request('actions/plant-action.php', $plantEdit + ['csrf_token' => $bobLiveToken], true)['status'] === 403, 'Ordinary user changed a plant.');
    $adminBrowser->request('actions/plant-action.php', ['csrf_token' => $adminToken, 'operation' => 'delete', 'plant_id' => $plantId], true);
    check(find_plant($plantId) === null, 'Plant delete failed.');
    // PHP caches file stats; clear the cache because the server process deleted the file.
    clearstatcache();
    check(!is_file(dirname(__DIR__) . '/images/' . $newPlant['image']), 'Deleted plant left its uploaded image behind.');

    $slug = db()->query('SELECT slug FROM blog_posts WHERE is_hidden = 0 ORDER BY id LIMIT 1')->fetchColumn();
    check(is_string($slug), 'Fixture blog post missing.');
    check($bobLive->request('actions/blog-action.php', ['csrf_token' => $bobLiveToken, 'slug' => $slug, 'operation' => 'hide'])['status'] === 403, 'Ordinary user hid a blog post.');
    $adminBrowser->request('actions/blog-action.php', ['csrf_token' => $adminToken, 'slug' => $slug, 'operation' => 'hide', 'return_to' => 'admin-blog.php']);
    check((int) db()->query('SELECT is_hidden FROM blog_posts WHERE slug = ' . db()->quote($slug))->fetchColumn() === 1, 'Admin hide failed.');
    check($guest->request('blog-post.php?slug=' . rawurlencode($slug))['status'] === 404, 'Hidden blog post is visible to guests.');
    check($bobLive->request('blog-post.php?slug=' . rawurlencode($slug))['status'] === 404, 'Hidden blog post is visible to users.');
    check($adminBrowser->request('blog-post.php?slug=' . rawurlencode($slug))['status'] === 200, 'Administrator cannot open a hidden blog post.');
    check($bobLive->request('actions/blog-action.php', ['csrf_token' => $bobLiveToken, 'slug' => $slug, 'operation' => 'vote', 'value' => 1])['status'] === 404, 'Hidden blog post accepted a vote.');
    check(!str_contains($guest->request('blog.php')['body'], 'blog-post.php?slug=' . rawurlencode($slug) . '"'), 'Hidden blog post is listed publicly.');
    check(str_contains($adminBrowser->request('admin-blog.php')['body'], 'Đang ẩn'), 'Admin moderation list does not show hidden posts.');
    $adminBrowser->request('actions/blog-action.php', ['csrf_token' => $adminToken, 'slug' => $slug, 'operation' => 'unhide', 'return_to' => 'admin-blog.php']);
    check($guest->request('blog-post.php?slug=' . rawurlencode($slug))['status'] === 200, 'Unhidden blog post is still hidden.');

    $bobId = (int) $bob['id'];
    check($bobLive->request('actions/user-action.php', ['csrf_token' => $bobLiveToken, 'user_id' => $bobId, 'operation' => 'promote'])['status'] === 403, 'Ordinary user promoted an account.');
    check($adminBrowser->request('actions/user-action.php', ['csrf_token' => $adminToken, 'user_id' => $bobId, 'operation' => 'promote'])['status'] === 303
        && find_user($bobId)['role'] === 'admin', 'Promote failed.');
    check($bobLive->request('admin.php')['status'] === 200, 'Promoted user cannot open the admin page.');
    check($adminBrowser->request('actions/user-action.php', ['csrf_token' => $adminToken, 'user_id' => $admin['id'], 'operation' => 'demote'])['status'] === 403
        && find_user((int) $admin['id'])['role'] === 'admin', 'Administrator could change their own role.');
    check($adminBrowser->request('actions/user-action.php', ['csrf_token' => $adminToken, 'user_id' => $bobId, 'operation' => 'demote'])['status'] === 303
        && find_user($bobId)['role'] === 'user', 'Demote failed.');
    check($bobLive->request('admin.php')['status'] === 403, 'Demoted user still reaches the admin page.');
    check(str_contains($adminBrowser->request('admin-users.php?search=bob')['body'], 'Nâng quyền'), 'Admin role button is missing.');

    $statement = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    for ($index = 0; $index < 22; $index++) {
        $statement->execute(['Fixture ' . $index, 'fixture' . $index . '@example.test', $alice['password_hash']]);
    }
    check(str_contains($adminBrowser->request('admin-users.php?page=2')['body'], 'Trang 2 / 2'), 'User pagination failed.');
    check(str_contains($adminBrowser->request('admin-users.php?page=99999')['body'], 'Trang 2 / 2'), 'Pagination must clamp out-of-range pages.');
    db()->exec("UPDATE plants SET name = 'Preserved plant' WHERE id = 1");
    run_cli(['scripts/setup-database.php']);
    check(find_plant(1)['name'] === 'Preserved plant' && find_user((int) $alice['id'])['name'] === 'Alice Updated' && user_favorite_ids((int) $alice['id']) === [22], 'Repeat setup overwrote existing records.');

    // Deliberately remove only a table in this disposable database to test 503 handling.
    $server->exec('DROP TABLE `' . $testName . '`.favorites');
    $server->exec('DROP TABLE `' . $testName . '`.plants');
    check($guest->request('index.php')['status'] === 503, 'Database failure must give a clean 503 response.');
} catch (Throwable $exception) {
    $failure = $exception->getMessage();
} finally {
    if (is_resource($webProcess)) {
        proc_terminate($webProcess);
        foreach ($webPipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($webProcess);
    }
    if ($server instanceof PDO && preg_match('/^web503073_test_[a-f0-9]{12}$/', $testName)) {
        $server->exec('DROP DATABASE IF EXISTS `' . $testName . '`');
    }
    putenv($previousName === false ? 'DB_NAME' : 'DB_NAME=' . $previousName);
    if ($log !== false) {
        unlink($log);
    }
}
if ($failure !== null) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
    exit(1);
}
fwrite(STDOUT, 'PASS: ' . $checks . ' total checks. Temporary database and web server removed.' . PHP_EOL);
