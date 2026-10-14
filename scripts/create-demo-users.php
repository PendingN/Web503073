<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/activity-repository.php';

$connection = null;
$file = null;
$fileCreated = false;
$committed = false;
$outputPath = __DIR__ . '/../.runtime/demo-accounts.csv';
try {
    $names = ['Nguyễn Minh An', 'Trần Gia Bảo', 'Lê Ngọc Chi', 'Phạm Anh Dũng', 'Hoàng Thùy Dương',
        'Vũ Hải Đăng', 'Đặng Minh Hà', 'Bùi Thu Hằng', 'Đỗ Quang Huy', 'Hồ Lan Hương',
        'Ngô Tuấn Kiệt', 'Dương Khánh Linh', 'Lý Hoài Nam', 'Đinh Bảo Ngọc', 'Trịnh Thanh Phong',
        'Mai Hồng Phúc', 'Tạ Anh Quân', 'Phan Minh Tâm', 'Cao Thảo Trang', 'Đoàn Quốc Việt'];
    $connection = db();
    $historyReady = activity_ready();
    foreach (range(1, 20) as $number) {
        if (email_in_use(sprintf('demo%02d@web503073.test', $number))) {
            throw new RuntimeException('Demo accounts already exist. Existing accounts and passwords will not be overwritten.');
        }
    }
    $directory = dirname($outputPath);
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
        throw new RuntimeException('Cannot create private runtime directory.');
    }
    $file = @fopen($outputPath, 'x');
    if ($file === false) {
        throw new RuntimeException('Cannot create private credential file, or it already exists: ' . $outputPath);
    }
    $fileCreated = true;
    if (fwrite($file, "\xEF\xBB\xBF") === false || fputcsv($file, ['Họ tên', 'Email', 'Mật khẩu', 'Trạng thái']) === false) {
        throw new RuntimeException('Cannot write credential file.');
    }
    $connection->beginTransaction();
    foreach ($names as $index => $name) {
        $number = $index + 1;
        $name .= ' [Demo]';
        $email = sprintf('demo%02d@web503073.test', $number);
        $password = 'Demo!' . bin2hex(random_bytes(10));
        $id = create_user($name, $email, $password);
        $subject = ['id' => $id, 'name' => $name];
        record_activity(null, 'account.created', 'Tạo tài khoản thành viên mẫu bằng CLI', $subject);
        $status = $number > 15 ? 'blocked' : 'active';
        if ($status === 'blocked') {
            $statement = $connection->prepare('UPDATE users SET status = ?, session_version = session_version + 1 WHERE id = ?');
            $statement->execute([$status, $id]);
            record_activity(null, 'user.blocked', 'Khóa tài khoản mẫu theo yêu cầu', $subject);
        }
        if (fputcsv($file, [$name, $email, $password, $status]) === false) {
            throw new RuntimeException('Cannot write demo credential row.');
        }
    }
    if (!fflush($file)) {
        throw new RuntimeException('Cannot flush credential file.');
    }
    fclose($file);
    $file = null;
    $connection->commit();
    $committed = true;
    fwrite(STDOUT, 'Created 20 ordinary accounts: 15 active, 5 blocked.' . PHP_EOL);
    fwrite(STDOUT, 'Private credentials: ' . realpath($outputPath) . PHP_EOL);
    if (!$historyReady) {
        fwrite(STDOUT, 'History is not enabled yet. Run database setup; it imports the actual account creation timestamps.' . PHP_EOL);
    }
} catch (Throwable $exception) {
    if ($connection instanceof PDO && $connection->inTransaction()) {
        $connection->rollBack();
    }
    if (is_resource($file)) {
        fclose($file);
    }
    if ($fileCreated && !$committed && is_file($outputPath)) {
        unlink($outputPath);
    }
    fwrite(STDERR, 'Cannot create demo accounts: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
