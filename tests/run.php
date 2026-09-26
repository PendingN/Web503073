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

    public function request(string $path, ?array $data = null): array
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
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data));
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
    $server = database_connection(false);
    putenv('DB_NAME=' . $testName);
    run_cli(['scripts/setup-database.php']);
    check((int) db()->query('SELECT COUNT(*) FROM plants')->fetchColumn() === 40, 'Seed must import all 40 plants.');

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
    foreach (['favorites.php', 'profile.php', 'admin.php'] as $path) {
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
    foreach (['favorite-action.php', 'profile-action.php', 'user-action.php', 'logout.php'] as $handler) {
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
    check($aliceBrowser->login('alice@example.test', 'wrong-password')['status'] === 200, 'Bad password must fail.');
    check($aliceBrowser->login('alice@example.test', 'Garden123!')['status'] === 303, 'Valid login failed.');
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
    check($response['status'] === 303 && $response['headers']['location'] === $prefix . '/dashboard.php', 'Registration allowed an external redirect.');
    $bob = find_user_by_email('bob@example.test');
    check(user_favorite_ids((int) $bob['id']) === [], 'Another user inherited favorites.');
    $bobToken = $bobBrowser->token('profile.php');
    $bobBrowser->request('actions/profile-action.php', ['csrf_token' => $bobToken, 'name' => 'Bob Garden', 'email' => 'alice-new@example.test', 'current_password' => 'BobGarden123!']);
    check(find_user((int) $bob['id'])['email'] === 'bob@example.test', 'Profile accepted a duplicate email.');

    $adminBrowser = new TestBrowser($base);
    check($adminBrowser->login('admin@example.test', 'TestAdmin123!')['status'] === 303, 'Admin login failed.');
    $adminToken = $adminBrowser->token('admin.php');
    check(str_contains($adminBrowser->request('admin.php?search=alice-new')['body'], 'Alice Updated'), 'Admin user search failed.');
    $update = ['csrf_token' => $adminToken, 'user_id' => $bob['id'], 'operation' => 'update', 'name' => 'Bob Edited', 'email' => 'bob@example.test', 'role' => 'admin'];
    check($adminBrowser->request('actions/user-action.php', $update)['status'] === 303, 'Admin edit failed.');
    check(find_user((int) $bob['id'])['name'] === 'Bob Edited' && find_user((int) $bob['id'])['role'] === 'user', 'Admin edit must persist and ignore unexpected role fields.');
    $update['email'] = 'alice-new@example.test';
    $adminBrowser->request('actions/user-action.php', $update);
    check(find_user((int) $bob['id'])['email'] === 'bob@example.test', 'Admin edit accepted duplicate email.');
    check($adminBrowser->request('admin.php?edit=' . $bob['id'])['status'] === 200, 'Admin edit form failed.');
    check($adminBrowser->request('admin.php?edit=99999')['status'] === 404, 'Missing user detail must return 404.');
    check($adminBrowser->request('actions/user-action.php', ['csrf_token' => $adminToken, 'user_id' => $admin['id'], 'operation' => 'block'])['status'] === 403, 'Administrator account could be blocked.');
    $block = ['csrf_token' => $adminToken, 'user_id' => $bob['id'], 'operation' => 'block'];
    check($adminBrowser->request('actions/user-action.php', $block)['status'] === 303 && find_user((int) $bob['id'])['status'] === 'blocked', 'Blocking failed.');
    $blockedLogin = new TestBrowser($base);
    check(str_contains($blockedLogin->login('bob@example.test', 'BobGarden123!')['body'], 'đang bị khóa'), 'Blocked user could log in.');
    check(str_contains($adminBrowser->request('admin.php?status=blocked')['body'], 'Bob Edited'), 'Blocked-user filter failed.');
    $block['operation'] = 'unblock';
    $adminBrowser->request('actions/user-action.php', $block);
    check(find_user((int) $bob['id'])['status'] === 'active', 'Unblocking failed.');
    check($bobBrowser->request('profile.php')['status'] === 303, 'Old session returned after unblock.');
    check($blockedLogin->login('bob@example.test', 'BobGarden123!')['status'] === 303, 'Unblocked user cannot log in again.');

    $statement = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    for ($index = 0; $index < 22; $index++) {
        $statement->execute(['Fixture ' . $index, 'fixture' . $index . '@example.test', $alice['password_hash']]);
    }
    check(str_contains($adminBrowser->request('admin.php?page=2')['body'], 'Trang 2 / 2'), 'User pagination failed.');
    check(str_contains($adminBrowser->request('admin.php?page=99999')['body'], 'Trang 2 / 2'), 'Pagination must clamp out-of-range pages.');
    db()->exec("UPDATE plants SET name = 'Preserved plant' WHERE id = 1");
    run_cli(['scripts/setup-database.php']);
    check(find_plant(1)['name'] === 'Preserved plant' && find_user((int) $alice['id'])['name'] === 'Alice Updated' && user_favorite_ids((int) $alice['id']) === [22], 'Repeat setup overwrote existing records.');

    // Deliberately remove only a table in this disposable database to test 503 handling.
    db()->exec('DROP TABLE favorites');
    db()->exec('DROP TABLE plants');
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
