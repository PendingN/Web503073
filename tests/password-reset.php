<?php
declare(strict_types=1);

// Password reset by email. Included by tests/run.php: uses its disposable database, test web server,
// and the "log" mail driver, which writes each email to $mailDir instead of sending it.

/** All emails written so far, oldest first. */
function test_mail_files(string $mailDir): array
{
    $files = glob($mailDir . DIRECTORY_SEPARATOR . '*.txt') ?: [];
    sort($files);
    return $files;
}

/** Link token and 6-digit code from the newest reset email. */
function test_latest_reset_mail(string $mailDir): array
{
    $files = test_mail_files($mailDir);
    check($files !== [], 'A reset email must be written.');
    $mail = (string) file_get_contents(end($files));
    check(preg_match('#reset-password\.php\?token=([a-f0-9]{64})#', $mail, $link) === 1, 'Reset email must contain a link.');
    check(preg_match('/^(\d{6})$/m', $mail, $code) === 1, 'Reset email must contain a 6-digit code.');
    return ['token' => $link[1], 'code' => $code[1], 'text' => $mail];
}

function test_request_reset(TestBrowser $browser, string $email): array
{
    return $browser->request('forgot-password.php', ['csrf_token' => $browser->token('forgot-password.php'), 'operation' => 'request', 'email' => $email]);
}

function test_submit_code(TestBrowser $browser, string $email, string $code): array
{
    return $browser->request('forgot-password.php?step=code', ['csrf_token' => $browser->token('forgot-password.php?step=code'),
        'operation' => 'verify', 'email' => $email, 'code' => $code]);
}

// Earlier suites leave failed logins from 127.0.0.1; start this suite with a clean throttle window.
db()->exec('DELETE FROM login_attempts');
// Earlier suites (account activation) also write mail; this suite counts only its own messages.
array_map('unlink', test_mail_files($mailDir));
$resetUserId = create_user('Reset Tester', 'reset@example.test', 'OldPassword1!', 'user', true);
$resetGuest = new TestBrowser($base);

check(str_contains($resetGuest->request('login.php')['body'], 'href="forgot-password.php"'), 'Login must link to the forgot password page.');
$response = $resetGuest->request('reset-password.php');
check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $prefix . '/forgot-password.php', 'The new password screen needs a verified reset.');
$response = $resetGuest->request('reset-password.php?token=' . str_repeat('a', 64));
check($response['status'] === 400 && str_contains($response['body'], 'Liên kết không còn hiệu lực'), 'Unknown reset links must be rejected.');
check($resetGuest->request('forgot-password.php', ['operation' => 'request', 'email' => 'reset@example.test'])['status'] === 403, 'Reset requests require a CSRF token.');

// Unknown and registered emails get the same answer; only the registered one receives mail.
$response = test_request_reset($resetGuest, 'nobody@example.test');
check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $prefix . '/forgot-password.php?step=code', 'Unknown emails must get the generic answer.');
check(test_mail_files($mailDir) === [], 'Unknown emails must not receive mail.');
$response = test_request_reset($resetGuest, 'Reset@Example.test');
check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $prefix . '/forgot-password.php?step=code', 'Registered emails must get the same generic answer.');
$codePage = $resetGuest->request('forgot-password.php?step=code')['body'];
check(str_contains($codePage, 'Nếu email này đã đăng ký') && str_contains($codePage, 'value="reset@example.test"'), 'The code step must explain the email and remember the address.');
$mail = test_latest_reset_mail($mailDir);
check(str_contains($mail['text'], 'To: reset@example.test') && str_contains($mail['text'], 'http://' . $address . $prefix . '/reset-password.php?token='), 'Reset email must go to the user with an absolute link.');
$stored = db()->query('SELECT * FROM password_resets WHERE user_id = ' . $resetUserId)->fetch();
check($stored['token_hash'] === hash('sha256', $mail['token']) && $stored['otp_hash'] !== $mail['code'] && password_verify($mail['code'], $stored['otp_hash']), 'Only hashes of the token and code may be stored.');
test_request_reset($resetGuest, 'reset@example.test');
check(count(test_mail_files($mailDir)) === 1, 'A second request within a minute must not send another email.');

// Wrong code, then the right code, from a browser that is also signed in elsewhere.
$otherDevice = new TestBrowser($base);
check($otherDevice->login('reset@example.test', 'OldPassword1!')['status'] === 303, 'The old password works before the reset.');
$response = test_submit_code($resetGuest, 'reset@example.test', $mail['code'] === '000000' ? '111111' : '000000');
check($response['status'] === 200 && str_contains($response['body'], 'Mã xác nhận chưa đúng'), 'A wrong code must be refused.');
check((int) db()->query('SELECT otp_attempts FROM password_resets WHERE user_id = ' . $resetUserId)->fetchColumn() === 1, 'Wrong codes must be counted.');
$response = test_submit_code($resetGuest, 'reset@example.test', substr($mail['code'], 0, 3) . ' ' . substr($mail['code'], 3));
check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $prefix . '/reset-password.php', 'The right code must open the new password screen.');
$resetPage = $resetGuest->request('reset-password.php');
check($resetPage['status'] === 200 && str_contains($resetPage['body'], 'reset@example.test') && !str_contains($resetPage['body'], $mail['token']), 'The new password screen must name the account and hide the token.');
$resetToken = $resetGuest->token('reset-password.php');
$response = $resetGuest->request('reset-password.php', ['csrf_token' => $resetToken, 'password' => 'NewPassword1!', 'password_confirmation' => 'Different1!']);
check($response['status'] === 200 && str_contains($response['body'], 'Mật khẩu nhập lại chưa khớp.'), 'The new password must be entered twice.');
$response = $resetGuest->request('reset-password.php', ['csrf_token' => $resetToken, 'password' => 'short', 'password_confirmation' => 'short']);
check($response['status'] === 200 && str_contains($response['body'], 'ít nhất 8 ký tự'), 'Short passwords must be refused.');
$response = $resetGuest->request('reset-password.php', ['csrf_token' => $resetToken, 'password' => 'NewPassword1!', 'password_confirmation' => 'NewPassword1!']);
check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $prefix . '/login.php', 'A successful reset must send the user to log in.');
check(str_contains($resetGuest->request('login.php')['body'], 'Đặt lại mật khẩu thành công'), 'Login must confirm the reset.');
check(password_verify('NewPassword1!', (string) find_user($resetUserId)['password_hash']), 'The new password must be saved hashed.');
check((int) db()->query('SELECT COUNT(*) FROM password_resets WHERE user_id = ' . $resetUserId)->fetchColumn() === 0, 'A used reset must be deleted.');
check($resetGuest->request('profile.php')['status'] === 303, 'Resetting must not sign the user in.');
check($otherDevice->request('profile.php')['status'] === 303, 'Resetting must sign out existing sessions.');
check($resetGuest->request('reset-password.php?token=' . $mail['token'])['status'] === 400, 'A used link must not work again.');
check($resetGuest->login('reset@example.test', 'OldPassword1!')['status'] === 200, 'The old password must stop working.');
check($resetGuest->login('reset@example.test', 'NewPassword1!')['status'] === 303, 'The new password must work.');

// The emailed link alone also leads to the new password screen, without keeping the token in the URL.
$linkBrowser = new TestBrowser($base);
test_request_reset($linkBrowser, 'reset@example.test');
$mail = test_latest_reset_mail($mailDir);
$response = $linkBrowser->request('reset-password.php?token=' . $mail['token']);
check($response['status'] === 303 && ($response['headers']['location'] ?? '') === $prefix . '/reset-password.php', 'A valid link must open the new password screen without the token.');
$response = $linkBrowser->request('reset-password.php', ['csrf_token' => $linkBrowser->token('reset-password.php'), 'password' => 'LinkPassword1!', 'password_confirmation' => 'LinkPassword1!']);
check($response['status'] === 303 && password_verify('LinkPassword1!', (string) find_user($resetUserId)['password_hash']), 'The link flow must save the new password.');

// Five wrong codes lock the reset, even for the right code afterwards.
test_request_reset($linkBrowser, 'reset@example.test');
$mail = test_latest_reset_mail($mailDir);
$wrongCode = $mail['code'] === '000000' ? '111111' : '000000';
for ($attempt = 0; $attempt < 5; $attempt++) {
    test_submit_code($linkBrowser, 'reset@example.test', $wrongCode);
}
$response = test_submit_code($linkBrowser, 'reset@example.test', $mail['code']);
check($response['status'] !== 303 && $linkBrowser->request('reset-password.php')['status'] === 303, 'The right code must be refused after five wrong attempts.');
check((int) db()->query('SELECT otp_attempts FROM password_resets WHERE user_id = ' . $resetUserId)->fetchColumn() === 5, 'Attempts must stop at the limit.');

// Expired resets and blocked accounts are refused.
db()->exec('UPDATE password_resets SET expires_at = NOW() - INTERVAL 1 SECOND WHERE user_id = ' . $resetUserId);
check($linkBrowser->request('reset-password.php?token=' . $mail['token'])['status'] === 400, 'An expired link must be refused.');
db()->exec('DELETE FROM password_resets');
db()->exec("UPDATE users SET status = 'blocked' WHERE id = " . $resetUserId);
$mailCount = count(test_mail_files($mailDir));
test_request_reset(new TestBrowser($base), 'reset@example.test');
check(count(test_mail_files($mailDir)) === $mailCount, 'Blocked accounts must not receive reset emails.');
db()->exec('DELETE FROM login_attempts');
