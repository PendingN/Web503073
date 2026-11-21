<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/activity-repository.php';

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

/** Id of a seeded blog tag by name; tests post tag ids, never tag text. */
function test_tag_id(string $name): int
{
    $statement = db()->prepare('SELECT id FROM tags WHERE name = ?');
    $statement->execute([$name]);
    $id = $statement->fetchColumn();
    if ($id === false) {
        throw new RuntimeException('Missing test tag: ' . $name);
    }
    return (int) $id;
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

/** Links to $page the log mail driver wrote for $email, oldest first, as absolute URLs. */
function mailed_links(string $directory, string $email, string $page): array
{
    $files = glob($directory . DIRECTORY_SEPARATOR . '*.eml') ?: [];
    sort($files);
    $links = [];
    foreach ($files as $file) {
        $content = (string) file_get_contents($file);
        if (str_starts_with($content, "To: $email\r\n") && preg_match('#https?://\S+/' . preg_quote($page, '#') . '\?token=[a-f0-9]{64}#', $content, $match) === 1) {
            $links[] = $match[0];
        }
    }
    return $links;
}

function mailed_activation_links(string $directory, string $email): array
{
    return mailed_links($directory, $email, 'verify-email.php');
}

function mailed_reset_links(string $directory, string $email): array
{
    return mailed_links($directory, $email, 'reset-password.php');
}

/** Opens the newest activation link mailed to $email, the way a member would from their inbox. */
function open_activation_link(TestBrowser $browser, string $directory, string $base, string $email): array
{
    $links = mailed_activation_links($directory, $email);
    check($links !== [] && str_starts_with(end($links), $base . '/verify-email.php?token='), 'No activation link was mailed to ' . $email);
    return $browser->request(substr(end($links), strlen($base) + 1));
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

    public function request(string $path, ?array $data = null, bool $multipart = false, bool $json = false): array
    {
        $headers = [];
        curl_setopt($this->curl, CURLOPT_URL, $this->base . '/' . $path);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $json ? ['Accept: application/json'] : []);
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
$mailDir = null;
try {
    if (!extension_loaded('curl') || !extension_loaded('pdo_mysql') || !extension_loaded('mbstring')) {
        throw new RuntimeException('Tests require curl, pdo_mysql, and mbstring.');
    }
    $server = create_database_connection(load_database_config(true), false);
    putenv('DB_NAME=' . $testName);
    run_cli(['scripts/setup-database.php']);
    check((int) db()->query('SELECT COUNT(*) FROM plants')->fetchColumn() === 29, 'Seed must import all 29 plants.');
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
    // Never call the real AI from tests; the advisor must answer from its rules.
    putenv('DEEPSEEK_API_KEY=');
    // Never send real email from tests: activation messages go to a throwaway folder the tests read back.
    $mailDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'web503073-mail-' . bin2hex(random_bytes(6));
    putenv('MAIL_DRIVER=log');
    putenv('MAIL_HOST=');
    putenv('MAIL_LOG_DIR=' . $mailDir);
    putenv('APP_URL=' . $base);
    $webProcess = proc_open([PHP_BINARY, '-S', $address, '-t', $documentRoot, 'server-router.php'], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $webPipes, dirname(__DIR__));
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
    foreach (['index.php', 'collection.php', 'plant-detail.php?id=22', 'plant-advisor.php', 'blog.php', 'blog-post.php?slug=bat-dau-mot-goc-xanh-tu-dau', 'login.php', 'register.php'] as $path) {
        check($guest->request($path)['status'] === 200, 'Public route failed: ' . $path);
    }
    foreach (['favorites.php', 'profile.php', 'admin.php', 'admin-dashboard.php'] as $path) {
        $response = $guest->request($path);
        check($response['status'] === 303 && str_starts_with($response['headers']['location'] ?? '', $prefix . '/login.php?return_to='), 'Guest route is not protected: ' . $path);
    }
    check($guest->request('plant-detail.php?id[]=22')['status'] === 404, 'Array plant ID must be rejected.');
    check($guest->request('plant-detail.php?id=99999')['status'] === 404, 'Missing plants must return 404.');
    check(str_contains($guest->request('collection.php?search=tr%E1%BA%A7u')['body'], 'Trầu'), 'Vietnamese plant search failed.');
    check(str_contains($guest->request('collection.php?search=%25')['body'], '0 sản phẩm'), 'LIKE wildcards must be treated literally.');
    check(str_contains($guest->request('collection.php?search=' . rawurlencode("' OR 1=1 --"))['body'], '0 sản phẩm'), 'SQL search input is not literal.');
    $advisorData = ['mode' => 'spiritual', 'birth_day' => '25', 'birth_month' => '1', 'birth_year' => '1996', 'direction' => 'dong', 'wish' => 'tai-loc', 'habit' => 'busy', 'light' => 'low', 'position' => 'shelf', 'note' => '<b>Nhà có mèo</b>'];
    check($guest->request('actions/plant-advice-action.php', $advisorData)['status'] === 403, 'Plant advice requires a CSRF token.');
    $response = $guest->request('actions/plant-advice-action.php', ['csrf_token' => $guest->token('plant-advisor.php?mode=spiritual')] + $advisorData);
    check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $prefix . '/plant-advisor.php?mode=spiritual#advisor-results', 'Plant advice must redirect to its results.');
    $advisorPage = $guest->request('plant-advisor.php?mode=spiritual')['body'];
    check(str_contains($advisorPage, 'Mệnh Hỏa') && str_contains($advisorPage, 'Ất Hợi') && str_contains($advisorPage, 'Gợi ý theo nguyên tắc ngũ hành') && str_contains($advisorPage, 'class="advisor-reason"'), 'Spiritual advice must show rule-based picks without an API key.');
    check(str_contains($advisorPage, '&lt;b&gt;Nhà có mèo&lt;/b&gt;') && !str_contains($advisorPage, '<b>Nhà có mèo</b>'), 'Advisor notes must be escaped.');
    $zodiacData = ['mode' => 'zodiac', 'birth_day' => '15', 'birth_month' => '8'] + $advisorData;
    $response = $guest->request('actions/plant-advice-action.php', ['csrf_token' => $guest->token('plant-advisor.php?mode=spiritual')] + $zodiacData);
    check(str_ends_with($response['headers']['location'] ?? '', '?mode=zodiac#advisor-results'), 'Zodiac advice must redirect to its results.');
    $advisorPage = $guest->request('plant-advisor.php?mode=zodiac')['body'];
    check(str_contains($advisorPage, 'Cung Sư Tử') && str_contains($advisorPage, 'Gợi ý theo cung hoàng đạo'), 'The zodiac mode shows its results.');
    check(!str_contains($advisorPage, 'class="advisor-mode ') && str_contains($advisorPage, 'Chọn cách khác'), 'A chosen mode hides the mode buttons and offers a way back.');
    $advisorPage = $guest->request('plant-advisor.php')['body'];
    check(substr_count($advisorPage, 'class="advisor-mode ') === 2 && !str_contains($advisorPage, 'id="advisor-form"'), 'The advisor opens with only the two mode buttons.');
    check(str_contains($advisorPage, 'id="lac-que"') && str_contains($advisorPage, 'data-que-result aria-live="polite" hidden'), 'The fortune draw waits for a shake.');
    $landingPrefilled = $guest->request('index.php?light=low&position=shelf');
    check($landingPrefilled['status'] === 200 && str_contains($landingPrefilled['body'], '<option value="low" selected>')
        && str_contains($landingPrefilled['body'], '<option value="shelf" selected>'), 'Landing finder preserves light and position query selections.');
    $fortunePage = $guest->request('plant-advisor.php?lac=1')['body'];
    check(preg_match('/class="que-result-tag"[^>]*>Quẻ [^<]+ · [^<]+</u', $fortunePage) === 1 && str_contains($fortunePage, 'plant-detail.php?id=') && str_contains($fortunePage, '<dt>Hợp mệnh</dt>') && str_contains($fortunePage, '<b>Tưới nước:</b>'), 'Shaking without JavaScript draws a plant with its facts on the server.');
    check(str_contains($guest->request('plant-advisor.php?mode=spiritual')['body'], 'Mệnh Hỏa'), 'Each mode keeps its own results.');
    $response = $guest->request('actions/plant-advice-action.php', ['csrf_token' => $guest->token('plant-advisor.php?mode=spiritual'), 'birth_year' => '1800'] + $advisorData);
    check(str_ends_with($response['headers']['location'] ?? '', '?mode=spiritual#advisor-form'), 'Invalid advice requests return to the form.');
    $advisorPage = $guest->request('plant-advisor.php?mode=spiritual')['body'];
    check(str_contains($advisorPage, 'aria-invalid="true"') && str_contains($advisorPage, 'value="1800"'), 'Advisor errors must keep input and mark fields.');
    check(!str_contains($guest->request('plant-advisor.php?mode=spiritual')['body'], 'aria-invalid="true"'), 'Advisor errors show once.');
    check(str_contains($guest->request('plant-advisor.php?mode=zodiac&light=bright&position=floor')['body'], '<option value="bright" selected>'), 'The landing finder prefills the advisor.');
    $category = 'Cây trong nhà';
    $expected = count(list_plants('', $category));
    check(str_contains($guest->request('collection.php?category=' . rawurlencode($category))['body'], $expected . ' sản phẩm'), 'Category filter failed.');
    foreach (['favorite-action.php', 'plant-advice-action.php', 'profile-action.php', 'avatar-action.php', 'user-action.php', 'blog-action.php', 'logout.php'] as $handler) {
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
    check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $prefix . '/login.php?return_to=' . rawurlencode($aliceData['return_to']),
        'Registration must send the new member to the login page and preserve the local return URL.');
    $alice = find_user_by_email('alice@example.test');
    check($alice !== null && $alice['role'] === 'user' && $alice['password_hash'] !== 'Garden123!' && password_verify('Garden123!', $alice['password_hash']), 'Registration must normalize email, hash password, and prevent role escalation.');

    // Account activation by email.
    check($alice['email_verified_at'] === null, 'A new account must start unverified.');
    check(str_contains($guest->request('login.php')['body'], 'đã gửi email kích hoạt tới alice@example.test'), 'The login page must tell the new member to check their email.');
    check($guest->request('profile.php')['status'] === 303, 'Registration must not sign the new member in.');
    $aliceLinks = mailed_activation_links($mailDir, 'alice@example.test');
    check(count($aliceLinks) === 1 && str_starts_with($aliceLinks[0], $base . '/verify-email.php?token='), 'Registration must email one activation link built from the site address.');
    $aliceFirstToken = substr($aliceLinks[0], -64);
    $storedTokens = db()->query('SELECT token_hash FROM email_verifications WHERE user_id = ' . (int) $alice['id'])->fetchAll(PDO::FETCH_COLUMN);
    check($storedTokens === [hash('sha256', $aliceFirstToken)], 'Only the hash of the activation token may be stored.');
    $unverifiedGuest = new TestBrowser($base);
    check(!str_contains($unverifiedGuest->login('alice@example.test', 'wrong-password')['body'], 'chưa được kích hoạt'), 'A wrong password must not reveal that the account is unverified.');
    $blockedUnverified = $unverifiedGuest->login('alice@example.test', 'Garden123!');
    check($blockedUnverified['status'] === 200 && str_contains($blockedUnverified['body'], 'chưa được kích hoạt')
        && str_contains($blockedUnverified['body'], 'actions/resend-verification.php'), 'An unverified member must not sign in and must be offered a new link.');
    check($unverifiedGuest->request('profile.php')['status'] === 303, 'An unverified login must not create a session.');
    $resend = ['csrf_token' => $unverifiedGuest->token('login.php'), 'email' => 'alice@example.test'];
    check($unverifiedGuest->request('actions/resend-verification.php', ['csrf_token' => 'bad'] + $resend)['status'] === 403, 'Resend must require a CSRF token.');
    $genericResend = $unverifiedGuest->request('actions/resend-verification.php', $resend);
    check($genericResend['status'] === 303 && count(mailed_activation_links($mailDir, 'alice@example.test')) === 1, 'A resend within a minute must not send another email.');
    check(str_contains($unverifiedGuest->request('login.php')['body'], 'Nếu email này đang chờ kích hoạt'), 'Resend must answer with a neutral message.');
    $unverifiedGuest->request('actions/resend-verification.php', ['csrf_token' => $unverifiedGuest->token('login.php'), 'email' => 'nobody@example.test']);
    check(mailed_activation_links($mailDir, 'nobody@example.test') === [], 'Resend must not email an address that has no pending account.');
    check(str_contains($unverifiedGuest->request('login.php')['body'], 'Nếu email này đang chờ kích hoạt'), 'Resend must answer the same for unknown addresses.');
    db()->exec('UPDATE email_verifications SET created_at = created_at - INTERVAL 2 MINUTE');
    $unverifiedGuest->request('actions/resend-verification.php', ['csrf_token' => $unverifiedGuest->token('login.php'), 'email' => 'alice@example.test']);
    $aliceLinks = mailed_activation_links($mailDir, 'alice@example.test');
    check(count($aliceLinks) === 2, 'Resend after the cooldown must send a new link.');
    $oldLink = $unverifiedGuest->request(substr($aliceLinks[0], strlen($base) + 1));
    check($oldLink['status'] === 303 && find_user((int) $alice['id'])['email_verified_at'] === null, 'A replaced link must stop working.');
    check(str_contains($unverifiedGuest->request('login.php')['body'], 'không hợp lệ, đã được dùng hoặc đã hết hạn'), 'A dead link must explain itself.');
    foreach (['bad-token', str_repeat('0', 64), str_repeat('A', 64)] as $badToken) {
        $unverifiedGuest->request('verify-email.php?token=' . $badToken);
        check(find_user((int) $alice['id'])['email_verified_at'] === null, 'A malformed or unknown token must not activate an account.');
    }
    db()->exec('UPDATE email_verifications SET expires_at = NOW() - INTERVAL 1 MINUTE');
    open_activation_link($unverifiedGuest, $mailDir, $base, 'alice@example.test');
    check(find_user((int) $alice['id'])['email_verified_at'] === null, 'An expired link must not activate an account.');
    db()->exec('UPDATE email_verifications SET expires_at = NOW() + INTERVAL 1 HOUR');
    $activated = open_activation_link($unverifiedGuest, $mailDir, $base, 'alice@example.test');
    check($activated['status'] === 303 && ($activated['headers']['location'] ?? '') === $prefix . '/login.php'
        && find_user((int) $alice['id'])['email_verified_at'] !== null, 'The emailed link must activate the account.');
    check(str_contains($unverifiedGuest->request('login.php')['body'], 'Đã kích hoạt tài khoản'), 'Activation must confirm on the login page.');
    check($unverifiedGuest->request('profile.php')['status'] === 303, 'Activating must not sign the member in by itself.');
    check((int) db()->query('SELECT COUNT(*) FROM email_verifications WHERE user_id = ' . (int) $alice['id'])->fetchColumn() === 0, 'Activation must discard the links.');
    open_activation_link($unverifiedGuest, $mailDir, $base, 'alice@example.test');
    check(str_contains($unverifiedGuest->request('login.php')['body'], 'không hợp lệ, đã được dùng hoặc đã hết hạn'), 'An activation link must work only once.');
    check($guest->login('alice@example.test', 'Garden123!')['status'] === 303, 'An activated member must be able to sign in.');

    // An email nobody confirmed can be registered again: the newest registration owns it.
    $squatter = new TestBrowser($base);
    $squatter->request('register.php', ['csrf_token' => $squatter->token('register.php'), 'name' => 'Squatter', 'email' => 'carol@example.test', 'password' => 'Squatter123!', 'password_confirmation' => 'Squatter123!']);
    $carolId = (int) find_user_by_email('carol@example.test')['id'];
    $carolBrowser = new TestBrowser($base);
    $carolBrowser->request('register.php', ['csrf_token' => $carolBrowser->token('register.php'), 'name' => 'Carol Garden', 'email' => 'carol@example.test', 'password' => 'Carol123!Garden', 'password_confirmation' => 'Carol123!Garden']);
    $carol = find_user_by_email('carol@example.test');
    check((int) $carol['id'] === $carolId && $carol['name'] === 'Carol Garden' && password_verify('Carol123!Garden', $carol['password_hash'])
        && !password_verify('Squatter123!', $carol['password_hash']), 'Registering an unconfirmed email again must replace the earlier registration.');
    check((int) db()->query("SELECT COUNT(*) FROM users WHERE email = 'carol@example.test'")->fetchColumn() === 1
        && (int) db()->query('SELECT COUNT(*) FROM email_verifications WHERE user_id = ' . $carolId)->fetchColumn() === 1, 'Re-registering must keep one account and one live link.');
    open_activation_link($carolBrowser, $mailDir, $base, 'carol@example.test');
    check($carolBrowser->login('carol@example.test', 'Squatter123!')['status'] === 200 && $carolBrowser->login('carol@example.test', 'Carol123!Garden')['status'] === 303, 'Only the newest password may sign in.');
    $duplicateRegistration = new TestBrowser($base);
    $response = $duplicateRegistration->request('register.php', ['csrf_token' => $duplicateRegistration->token('register.php'), 'name' => 'Takeover', 'email' => 'carol@example.test', 'password' => 'Takeover123!', 'password_confirmation' => 'Takeover123!']);
    check($response['status'] === 200 && str_contains($response['body'], 'đã được đăng ký') && password_verify('Carol123!Garden', find_user($carolId)['password_hash']),
        'A confirmed account must not be taken over by registering its email again.');
    // Password reset by email.
    $forgot = new TestBrowser($base);
    check(str_contains($forgot->request('login.php')['body'], 'href="forgot-password.php"'), 'The login form must link to the password reset.');
    check($forgot->request('forgot-password.php')['status'] === 200 && $forgot->request('forgot-password.php', ['email' => 'carol@example.test'])['status'] === 403, 'The reset request form must require a CSRF token.');
    $forgotInvalid = $forgot->request('forgot-password.php', ['csrf_token' => $forgot->token('forgot-password.php'), 'email' => 'not-an-email']);
    check($forgotInvalid['status'] === 200 && str_contains($forgotInvalid['body'], 'email hợp lệ'), 'An invalid email must be rejected on the reset form.');
    $forgot->request('forgot-password.php', ['csrf_token' => $forgot->token('forgot-password.php'), 'email' => 'Carol@example.test']);
    $resetLinks = mailed_reset_links($mailDir, 'carol@example.test');
    check(count($resetLinks) === 1 && str_starts_with($resetLinks[0], $base . '/reset-password.php?token='), 'A reset request must email one link built from the site address.');
    $neutral = 'Nếu email này đã đăng ký';
    check(str_contains($forgot->request('login.php')['body'], $neutral), 'The reset request must answer with a neutral message.');
    check(db()->query('SELECT token_hash FROM password_resets WHERE user_id = ' . $carolId)->fetchAll(PDO::FETCH_COLUMN) === [hash('sha256', substr($resetLinks[0], -64))], 'Only the hash of the reset token may be stored.');
    $forgot->request('forgot-password.php', ['csrf_token' => $forgot->token('forgot-password.php'), 'email' => 'nobody@example.test']);
    check(str_contains($forgot->request('login.php')['body'], $neutral) && mailed_reset_links($mailDir, 'nobody@example.test') === [] && mailed_activation_links($mailDir, 'nobody@example.test') === [], 'An unknown address must get the same answer and no email.');
    $forgot->request('forgot-password.php', ['csrf_token' => $forgot->token('forgot-password.php'), 'email' => 'carol@example.test']);
    check(count(mailed_reset_links($mailDir, 'carol@example.test')) === 1, 'A second request within a minute must not send another email.');
    db()->exec('UPDATE password_resets SET created_at = created_at - INTERVAL 2 MINUTE');
    $forgot->request('forgot-password.php', ['csrf_token' => $forgot->token('forgot-password.php'), 'email' => 'carol@example.test']);
    $resetLinks = mailed_reset_links($mailDir, 'carol@example.test');
    check(count($resetLinks) === 2, 'A request after the cooldown must send a new link.');
    $oldResetPath = substr($resetLinks[0], strlen($base) + 1);
    $resetPath = substr($resetLinks[1], strlen($base) + 1);
    check($forgot->request($oldResetPath)['status'] === 303 && str_contains($forgot->request('forgot-password.php')['body'], 'không hợp lệ, đã được dùng hoặc đã hết hạn'), 'A replaced reset link must stop working.');
    foreach (['bad-token', str_repeat('0', 64)] as $badToken) {
        check($forgot->request('reset-password.php?token=' . $badToken)['status'] === 303, 'A malformed or unknown reset token must not show the form.');
    }
    db()->exec('UPDATE password_resets SET expires_at = NOW() - INTERVAL 1 MINUTE');
    check($forgot->request($resetPath)['status'] === 303, 'An expired reset link must not show the form.');
    db()->exec('UPDATE password_resets SET expires_at = NOW() + INTERVAL 1 HOUR');
    $resetForm = $forgot->request($resetPath);
    check($resetForm['status'] === 200 && str_contains($resetForm['body'], 'name="password_confirmation"'), 'A live reset link must show the new password form.');
    $resetFields = ['csrf_token' => $forgot->token($resetPath), 'token' => substr($resetLinks[1], -64), 'password' => 'Brand-new123!', 'password_confirmation' => 'Brand-new123!'];
    check($forgot->request('reset-password.php', ['csrf_token' => 'bad'] + $resetFields)['status'] === 403, 'Setting a new password must require a CSRF token.');
    $mismatch = $forgot->request('reset-password.php', ['password_confirmation' => 'Different123!'] + $resetFields);
    $tooShort = $forgot->request('reset-password.php', ['password' => 'short', 'password_confirmation' => 'short'] + $resetFields);
    check($mismatch['status'] === 200 && str_contains($mismatch['body'], 'chưa khớp') && $tooShort['status'] === 200 && str_contains($tooShort['body'], 'ít nhất 8 ký tự')
        && password_verify('Carol123!Garden', find_user($carolId)['password_hash']), 'A bad new password must be refused and keep the old one and the link.');
    $carolSessionBefore = $carolBrowser->request('profile.php')['status'];
    $done = $forgot->request('reset-password.php', $resetFields);
    check($carolSessionBefore === 200 && $done['status'] === 303 && ($done['headers']['location'] ?? '') === $prefix . '/login.php'
        && password_verify('Brand-new123!', find_user($carolId)['password_hash']), 'A valid reset must set the new password and send the member to the login page.');
    check(str_contains($forgot->request('login.php')['body'], 'Đã đặt lại mật khẩu'), 'The login page must confirm the reset.');
    check($forgot->request('profile.php')['status'] === 303, 'Resetting a password must not sign anyone in.');
    check($carolBrowser->request('profile.php')['status'] === 303, 'Resetting a password must end the sessions that were open.');
    check((int) db()->query('SELECT COUNT(*) FROM password_resets WHERE user_id = ' . $carolId)->fetchColumn() === 0, 'A reset must discard the links.');
    check($forgot->request($resetPath)['status'] === 303 && $forgot->request('reset-password.php', ['password' => 'Takeover123!', 'password_confirmation' => 'Takeover123!'] + $resetFields)['status'] === 303
        && password_verify('Brand-new123!', find_user($carolId)['password_hash']), 'A reset link must work only once.');
    $carolAgain = new TestBrowser($base);
    check($carolAgain->login('carol@example.test', 'Carol123!Garden')['status'] === 200 && $carolAgain->login('carol@example.test', 'Brand-new123!')['status'] === 303, 'Only the new password may sign in after a reset.');

    // A member who never activated gets the activation link instead of a reset link.
    $erin = new TestBrowser($base);
    $erin->request('register.php', ['csrf_token' => $erin->token('register.php'), 'name' => 'Erin Garden', 'email' => 'erin@example.test', 'password' => 'ErinGarden123!', 'password_confirmation' => 'ErinGarden123!']);
    db()->exec('UPDATE email_verifications SET created_at = created_at - INTERVAL 2 MINUTE');
    $erin->request('forgot-password.php', ['csrf_token' => $erin->token('forgot-password.php'), 'email' => 'erin@example.test']);
    check(mailed_reset_links($mailDir, 'erin@example.test') === [] && count(mailed_activation_links($mailDir, 'erin@example.test')) === 2, 'An unverified account must receive an activation link, never a reset link.');
    check($guest->request('admin.php')['status'] === 403, 'Ordinary user accessed admin.');
    $profilePage = $guest->request('profile.php');
    check(!str_contains($profilePage['body'], 'class="dashboard-shell"') && !str_contains($profilePage['body'], 'class="dashboard-sidebar"'), 'Profile must use its own layout.');
    check(str_contains($profilePage['body'], 'aria-current="page" href="profile.php"'), 'Profile must activate the shared navigation tab.');
    check(str_contains($guest->request('collection.php')['body'], 'id="site-menu"'), 'Collection must expose the shared site navigation.');
    $backToCollection = $prefix . '/collection.php?search=' . rawurlencode('Trầu Bà');
    $detailPage = $guest->request('plant-detail.php?id=21&return_to=' . rawurlencode($backToCollection));
    check(str_contains($detailPage['body'], 'class="back-link" href="' . htmlspecialchars($backToCollection, ENT_QUOTES, 'UTF-8') . '"'), 'Plant detail must preserve collection search on the back link.');
    $backToAdvisor = $prefix . '/plant-advisor.php?light=low&position=shelf#room-results';
    $detail22 = $guest->request('plant-detail.php?id=22&return_to=' . rawurlencode($backToAdvisor));
    $detail22Body = $detail22['body'];
    check($detail22['status'] === 200 && str_contains($detail22Body, 'class="back-link" href="' . htmlspecialchars($backToAdvisor, ENT_QUOTES, 'UTF-8') . '">Quay lại gợi ý chọn cây</a>'), 'Plant detail must label the back link for plant-advisor returns.');
    $copyPos = strpos($detail22Body, 'class="detail-copy"');
    $favPos = strpos($detail22Body, 'class="favorite-form"');
    $imagePos = strpos($detail22Body, 'class="detail-image"');
    $carePos = strpos($detail22Body, 'class="care-box"');
    check($copyPos !== false && $favPos !== false && $imagePos !== false && $carePos !== false
        && $copyPos < $favPos && $favPos < $imagePos && $imagePos < $carePos, 'Plant detail must order header/copy and favorite button before image and care guide.');
    check(str_contains($detail22Body, 'class="care-list care-list--grid"') && str_contains($detail22Body, 'class="care-item care-item--card"')
        && str_contains($detail22Body, 'Ánh sáng:</b>') && str_contains($detail22Body, 'Tưới nước:</b>'), 'Plant detail 22 must render structured care cards.');
    $aliceToken = $guest->token('profile.php');
    check($guest->request('actions/user-action.php', ['csrf_token' => $aliceToken, 'user_id' => $admin['id'], 'operation' => 'block'])['status'] === 403, 'Ordinary user accessed user management.');
    check($guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 99999])['status'] === 404, 'Unknown favorite was accepted.');
    $favoriteResponse = $guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 22, 'user_id' => $admin['id'], 'return_to' => '//example.invalid']);
    check(($favoriteResponse['headers']['location'] ?? '') === $prefix . '/collection.php', 'Favorite request failed or redirected externally: ' . $favoriteResponse['status'] . ' ' . ($favoriteResponse['headers']['location'] ?? 'no redirect'));
    check(user_favorite_ids((int) $alice['id']) === [22] && user_favorite_ids((int) $admin['id']) === [], 'Favorite ownership failed.');
    $guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 22]);
    check(user_favorite_ids((int) $alice['id']) === [], 'Favorite removal failed.');
    foreach (['1', '1', '0'] as $desiredFavorite) {
        $response = $guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 22, 'favorite' => $desiredFavorite], false, true);
        $state = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        check($response['status'] === 200 && !isset($response['headers']['location']) && $state['favorite'] === ($desiredFavorite === '1')
            && $state['count'] === (int) $desiredFavorite, 'Async favorites must return persisted state without redirect and tolerate retries.');
    }
    check($guest->request('actions/favorite-action.php', ['csrf_token' => 'invalid', 'plant_id' => 22], false, true)['status'] === 403, 'Async favorites must require CSRF.');
    check($guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 22, 'favorite' => 'bad'], false, true)['status'] === 400, 'Invalid async favorite state was accepted.');
    $guest->request('actions/favorite-action.php', ['csrf_token' => $aliceToken, 'plant_id' => 22]);
    $guest->request('actions/logout.php', ['csrf_token' => $aliceToken]);
    $guestToken = $guest->token('login.php');
    $expired = $guest->request('actions/favorite-action.php', ['csrf_token' => $guestToken, 'plant_id' => 22], false, true);
    check($expired['status'] === 401 && isset(json_decode($expired['body'], true)['login_url']), 'Async guest action must return a login destination.');
    check($guest->request('profile.php')['status'] === 303, 'Logout did not clear authentication.');
    $duplicate = new TestBrowser($base);
    $aliceData['csrf_token'] = $duplicate->token('register.php');
    $response = $duplicate->request('register.php', $aliceData);
    check($response['status'] === 200 && str_contains($response['body'], 'đã được đăng ký'), 'Duplicate email must be rejected.');
    $aliceBrowser = new TestBrowser($base);
    check(str_contains($aliceBrowser->login('invalid-email', 'Garden123!')['body'], 'Email hoặc mật khẩu chưa đúng.'), 'Invalid email must show the login error.');
    check($aliceBrowser->login('alice@example.test', 'wrong-password')['status'] === 200, 'Bad password must fail.');
    $securityHeaders = $guest->request('login.php')['headers'];
    $contentPolicy = $securityHeaders['content-security-policy'] ?? '';
    check(($securityHeaders['x-content-type-options'] ?? '') === 'nosniff' && ($securityHeaders['x-frame-options'] ?? '') === 'DENY'
        && isset($securityHeaders['referrer-policy']) && isset($securityHeaders['permissions-policy']) && !isset($securityHeaders['x-powered-by'])
        && str_contains($contentPolicy, "script-src 'self'") && str_contains($contentPolicy, "frame-ancestors 'none'") && str_contains($contentPolicy, "object-src 'none'"),
        'Pages must send the security headers and a strict content security policy.');

    // Login throttling: five wrong passwords lock that account for this client, even against the right password.
    db()->exec('DELETE FROM login_attempts');
    $guesser = new TestBrowser($base);
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $wrong = $guesser->login('alice@example.test', 'wrong-' . $attempt);
        check($wrong['status'] === 200 && str_contains($wrong['body'], 'Email hoặc mật khẩu chưa đúng.'), 'Attempts within the limit must show the normal error.');
    }
    $locked = $guesser->login('alice@example.test', 'Garden123!');
    check($locked['status'] === 429 && (int) ($locked['headers']['retry-after'] ?? 0) > 0 && str_contains($locked['body'], 'thử lại sau'), 'The right password must be refused while locked out.');
    check($guesser->request('profile.php')['status'] === 303, 'A locked-out login must not sign the guesser in.');
    check($guesser->login('bob@example.test', 'wrong-password')['status'] === 200, 'Failures on one account must not lock another account.');
    db()->exec('DELETE FROM login_attempts');
    for ($attempt = 0; $attempt < 4; $attempt++) {
        $guesser->login('alice@example.test', 'wrong-password');
    }
    check($guesser->login('alice@example.test', 'Garden123!')['status'] === 303, 'The right password within the limit must sign in.');
    for ($attempt = 0; $attempt < 4; $attempt++) {
        check($guesser->login('alice@example.test', 'wrong-password')['status'] === 200, 'A successful sign-in must reset the failure count.');
    }
    db()->exec('DELETE FROM login_attempts');
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

    // Re-entering the current password on the profile page is throttled as well.
    $guess = ['csrf_token' => $aliceBrowser->token('profile.php'), 'name' => 'Alice Updated', 'email' => 'alice-new@example.test', 'new_password' => 'AnotherGarden123!', 'password_confirmation' => 'AnotherGarden123!'];
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $aliceBrowser->request('actions/profile-action.php', $guess + ['current_password' => 'wrong-' . $attempt]);
    }
    $aliceBrowser->request('actions/profile-action.php', $guess + ['current_password' => 'NewGarden123!']);
    check(str_contains($aliceBrowser->request('profile.php')['body'], 'thử lại sau') && password_verify('NewGarden123!', find_user((int) $alice['id'])['password_hash']),
        'Guessing the current password on the profile page must be throttled, even with the right password.');
    db()->exec('DELETE FROM login_attempts');

    // User avatar upload, validation, replacement, and removal.
    require_once __DIR__ . '/../includes/avatars.php';
    $avatarFixture = tempnam(sys_get_temp_dir(), 'avatar-test-');
    $avatarPaths = [];
    $avatarPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6foAAAAASUVORK5CYII=');
    try {
        $avatarToken = $aliceBrowser->token('account-settings.php');
        $emptyAvatar = $aliceBrowser->request('actions/avatar-action.php', ['csrf_token' => $avatarToken, 'operation' => 'upload'], true);
        check($emptyAvatar['status'] === 303 && find_user((int) $alice['id'])['avatar_path'] === null, 'Empty avatar upload must be rejected.');

        file_put_contents($avatarFixture, '<?php echo "bad"; ?>');
        $badAvatarFile = new CURLFile($avatarFixture, 'image/png', '../../bad.php.png');
        $aliceBrowser->request('actions/avatar-action.php', ['csrf_token' => $avatarToken, 'operation' => 'upload', 'avatar' => $badAvatarFile], true);
        check(find_user((int) $alice['id'])['avatar_path'] === null, 'Invalid avatar upload must not persist.');

        file_put_contents($avatarFixture, $avatarPng);
        $validAvatarFile = new CURLFile($avatarFixture, 'image/png', '../../avatar.php.png');
        $uploadResponse = $aliceBrowser->request('actions/avatar-action.php', ['csrf_token' => $avatarToken, 'operation' => 'upload', 'avatar' => $validAvatarFile, 'return_to' => 'account-settings.php?section=avatar'], true);
        $firstAvatar = find_user((int) $alice['id'])['avatar_path'];
        if (is_string($firstAvatar)) {
            $avatarPaths[] = $firstAvatar;
        }
        check($uploadResponse['status'] === 303 && is_string($firstAvatar) && preg_match('#^images/avatars/[a-f0-9]{32}\.png$#', $firstAvatar) === 1, 'Valid avatar upload must store a safe filename under images/avatars.');
        $servedAvatar = $aliceBrowser->request($firstAvatar);
        check($servedAvatar['status'] === 200 && $servedAvatar['body'] === $avatarPng && str_contains($servedAvatar['headers']['content-type'] ?? '', 'image/png'), 'Uploaded avatar must be served as an image.');
        check(str_contains($aliceBrowser->request('profile.php')['body'], 'src="' . $firstAvatar . '"'), 'Uploaded avatar must appear on the profile page.');
        check(str_contains($aliceBrowser->request('account-settings.php?section=avatar')['body'], 'src="' . $firstAvatar . '"'), 'Uploaded avatar must appear in account settings.');

        $aliceBrowser->request('actions/avatar-action.php', ['csrf_token' => $avatarToken, 'operation' => 'upload', 'avatar' => $validAvatarFile], true);
        $secondAvatar = find_user((int) $alice['id'])['avatar_path'];
        if (is_string($secondAvatar)) {
            $avatarPaths[] = $secondAvatar;
        }
        clearstatcache();
        check(is_string($secondAvatar) && $secondAvatar !== $firstAvatar && !is_file(dirname(__DIR__) . '/' . $firstAvatar) && is_file(dirname(__DIR__) . '/' . $secondAvatar), 'Replacing an avatar must delete the previous avatar file.');

        $aliceBrowser->request('actions/avatar-action.php', ['csrf_token' => $avatarToken, 'operation' => 'remove']);
        clearstatcache();
        check(find_user((int) $alice['id'])['avatar_path'] === null && !is_file(dirname(__DIR__) . '/' . $secondAvatar), 'Removing an avatar must clear avatar_path and delete the file.');
    } finally {
        if ($avatarFixture !== false && is_file($avatarFixture)) {
            unlink($avatarFixture);
        }
        foreach ($avatarPaths as $path) {
            delete_user_avatar($path);
        }
    }

    $bobBrowser = new TestBrowser($base);
    $response = $bobBrowser->request('register.php', ['csrf_token' => $bobBrowser->token('register.php'), 'name' => 'Bob Garden', 'email' => 'bob@example.test', 'password' => 'BobGarden123!', 'password_confirmation' => 'BobGarden123!', 'return_to' => 'https://example.invalid']);
    check($response['status'] === 303 && $response['headers']['location'] === $prefix . '/login.php?return_to=' . rawurlencode($prefix . '/profile.php'), 'Registration must reject external redirects and fall back to the profile.');
    open_activation_link($bobBrowser, $mailDir, $base, 'bob@example.test');
    check($bobBrowser->login('bob@example.test', 'BobGarden123!')['status'] === 303, 'Bob could not sign in after activating.');
    $bob = find_user_by_email('bob@example.test');
    check(user_favorite_ids((int) $bob['id']) === [], 'Another user inherited favorites.');
    $bobToken = $bobBrowser->token('profile.php');
    $bobBrowser->request('actions/profile-action.php', ['csrf_token' => $bobToken, 'name' => 'Bob Garden', 'email' => 'alice-new@example.test', 'current_password' => 'BobGarden123!']);
    check(find_user((int) $bob['id'])['email'] === 'bob@example.test', 'Profile accepted a duplicate email.');

    $adminBrowser = new TestBrowser($base);
    $adminLogin = $adminBrowser->login('admin@example.test', 'TestAdmin123!');
    check($adminLogin['status'] === 303 && $adminLogin['headers']['location'] === $prefix . '/admin-dashboard.php', 'Direct admin login must open the admin dashboard.');
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
    check(str_contains($blockedLogin->login('bob@example.test', 'wrong-password')['body'], 'Email hoặc mật khẩu chưa đúng.'), 'Wrong password must not reveal blocked account status.');
    check(str_contains($blockedLogin->login('bob@example.test', 'BobGarden123!')['body'], 'đang bị khóa'), 'Blocked user could log in.');
    $blockedForgot = new TestBrowser($base);
    $blockedForgot->request('forgot-password.php', ['csrf_token' => $blockedForgot->token('forgot-password.php'), 'email' => 'bob@example.test']);
    check(mailed_reset_links($mailDir, 'bob@example.test') === [], 'A blocked account must not receive a reset link.');
    check(str_contains($adminBrowser->request('admin.php?status=blocked')['body'], 'Bob Edited'), 'Blocked-user filter failed.');
    $block['operation'] = 'unblock';
    $adminBrowser->request('actions/user-action.php', $block);
    check(find_user((int) $bob['id'])['status'] === 'active', 'Unblocking failed.');
    check($bobBrowser->request('profile.php')['status'] === 303, 'Old session returned after unblock.');
    check($blockedLogin->login('bob@example.test', 'BobGarden123!')['status'] === 303, 'Unblocked user cannot log in again.');

    // Manual activation by an administrator, for members whose email never arrives.
    $daveBrowser = new TestBrowser($base);
    $daveBrowser->request('register.php', ['csrf_token' => $daveBrowser->token('register.php'), 'name' => 'Dave Garden', 'email' => 'dave@example.test', 'password' => 'DaveGarden123!', 'password_confirmation' => 'DaveGarden123!']);
    $dave = find_user_by_email('dave@example.test');
    $pendingList = $adminBrowser->request('admin.php?search=dave')['body'];
    check(str_contains($pendingList, 'Chưa kích hoạt email') && str_contains($pendingList, 'value="verify"'), 'The admin list must flag unverified members and offer activation.');
    check(!str_contains($adminBrowser->request('admin.php?search=alice-new')['body'], 'Chưa kích hoạt email'), 'Verified members must not be flagged.');
    check($blockedLogin->request('actions/user-action.php', ['csrf_token' => $blockedLogin->token('profile.php'), 'user_id' => $dave['id'], 'operation' => 'verify'])['status'] === 403 && find_user((int) $dave['id'])['email_verified_at'] === null, 'An ordinary user must not activate accounts.');
    check($adminBrowser->request('actions/user-action.php', ['csrf_token' => $adminToken, 'user_id' => $dave['id'], 'operation' => 'verify'])['status'] === 303
        && find_user((int) $dave['id'])['email_verified_at'] !== null && (int) db()->query('SELECT COUNT(*) FROM email_verifications WHERE user_id = ' . (int) $dave['id'])->fetchColumn() === 0, 'Administrator activation failed.');
    check($daveBrowser->login('dave@example.test', 'DaveGarden123!')['status'] === 303, 'A manually activated member could not sign in.');
    check(!str_contains($adminBrowser->request('admin.php?search=dave')['body'], 'value="verify"'), 'An activated member must lose the activation button.');

    require __DIR__ . '/blog.php';
    require __DIR__ . '/tags.php';

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
    $plantImage = new CURLFile(dirname(__DIR__) . '/images/anh21.jpg', 'image/jpeg', 'plant.jpg');
    $adminBrowser->request('actions/plant-action.php', $plantFields + ['image' => $plantImage], true);
    $newPlant = db()->query("SELECT * FROM plants WHERE name = 'Cây thử nghiệm'")->fetch();
    check($newPlant !== false && str_starts_with($newPlant['image'], 'plants/') && is_file(dirname(__DIR__) . '/images/' . $newPlant['image']), 'Plant create with image failed.');
    $plantId = (int) ($newPlant['id'] ?? 0);
    $newPlantDetail = $plantId > 0 ? $adminBrowser->request('plant-detail.php?id=' . $plantId) : ['status' => 0, 'body' => ''];
    check($plantId > 0 && $newPlantDetail['status'] === 200, 'New plant page failed to open.');
    check(str_contains($newPlantDetail['body'], 'class="care-item care-item--plain"') && !str_contains($newPlantDetail['body'], 'care-list--grid')
        && !str_contains($newPlantDetail['body'], 'Ánh sáng:</b>'), 'Plain-text care must render cleanly without inventing categories.');
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
    $adminBrowser->request('actions/blog-action.php', ['csrf_token' => $adminToken, 'slug' => $slug, 'operation' => 'hide', 'return_to' => 'admin-posts.php']);
    check((int) db()->query('SELECT is_hidden FROM blog_posts WHERE slug = ' . db()->quote($slug))->fetchColumn() === 1, 'Admin hide failed.');
    check($guest->request('blog-post.php?slug=' . rawurlencode($slug))['status'] === 404, 'Hidden blog post is visible to guests.');
    check($bobLive->request('blog-post.php?slug=' . rawurlencode($slug))['status'] === 404, 'Hidden blog post is visible to users.');
    check($adminBrowser->request('blog-post.php?slug=' . rawurlencode($slug))['status'] === 200, 'Administrator cannot open a hidden blog post.');
    check($bobLive->request('actions/blog-action.php', ['csrf_token' => $bobLiveToken, 'slug' => $slug, 'operation' => 'vote', 'value' => 1])['status'] === 404, 'Hidden blog post accepted a vote.');
    check(!str_contains($guest->request('blog.php')['body'], 'blog-post.php?slug=' . rawurlencode($slug) . '"'), 'Hidden blog post is listed publicly.');
    check(!str_contains($guest->request('blog.php')['body'], 'blog-post.php?slug=' . rawurlencode($slug) . '&quot;'), 'Hidden blog post leaks into the search suggestions.');
    check(str_contains($adminBrowser->request('admin-posts.php')['body'], 'Đang ẩn'), 'Admin moderation list does not show hidden posts.');
    $adminBrowser->request('actions/blog-action.php', ['csrf_token' => $adminToken, 'slug' => $slug, 'operation' => 'unhide', 'return_to' => 'admin-posts.php']);
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
    check(str_contains($adminBrowser->request('admin.php?search=bob')['body'], 'Nâng quyền'), 'Admin role button is missing.');


    $statement = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    for ($index = 0; $index < 22; $index++) {
        $statement->execute(['Fixture ' . $index, 'fixture' . $index . '@example.test', $alice['password_hash']]);
    }
    check(str_contains($adminBrowser->request('admin.php?page=2')['body'], 'Trang 2 / 2'), 'User pagination failed.');
    check(str_contains($adminBrowser->request('admin.php?page=99999')['body'], 'Trang 2 / 2'), 'Pagination must clamp out-of-range pages.');
    db()->exec("UPDATE plants SET name = 'Preserved plant' WHERE id = 21");
    run_cli(['scripts/setup-database.php']);
    check(find_plant(21)['name'] === 'Preserved plant' && find_user((int) $alice['id'])['name'] === 'Alice Updated' && user_favorite_ids((int) $alice['id']) === [22], 'Repeat setup overwrote existing records.');
    check(find_user_by_email('fixture0@example.test')['email_verified_at'] === null && find_user((int) $alice['id'])['email_verified_at'] !== null, 'Repeat setup must not activate accounts that are still waiting for their email link.');
    // A database from before activation existed has no column: the upgrade must keep its members able to sign in.
    db()->exec('ALTER TABLE users DROP COLUMN email_verified_at');
    check($adminBrowser->request('profile.php')['status'] === 200 && $adminBrowser->login('admin@example.test', 'TestAdmin123!')['status'] === 303, 'Sign-in must keep working on a schema without the activation column until setup runs.');
    run_cli(['scripts/setup-database.php']);
    check((int) db()->query('SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL')->fetchColumn() === 0, 'Upgrading an existing database must treat its current members as verified.');

    require __DIR__ . '/admin-management.php';
    require __DIR__ . '/admin-dashboard.php';

    $server->exec('DROP TABLE `' . $testName . '`.user_activity');
    check($adminBrowser->login('admin@example.test', 'TestAdmin123!')['status'] === 303, 'Missing history migration must not break existing login.');

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
    if (is_string($mailDir) && str_contains(basename($mailDir), 'web503073-mail-') && is_dir($mailDir)) {
        array_map('unlink', glob($mailDir . DIRECTORY_SEPARATOR . '*.eml') ?: []);
        rmdir($mailDir);
    }
    if ($log !== false) {
        unlink($log);
    }
}
if ($failure !== null) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
    exit(1);
}
fwrite(STDOUT, 'PASS: ' . $checks . ' total checks. Temporary database and web server removed.' . PHP_EOL);
