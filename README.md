# Vũ Điệu Rừng Xanh

Website quản lý và tra cứu cây cảnh, xây dựng bằng PHP, MySQL/MariaDB, HTML, CSS và JavaScript thuần. Ứng dụng có tài khoản người dùng, tìm kiếm cây, bộ sưu tập yêu thích, trang cá nhân và khu vực quản trị.

Đây là dự án PHP server-rendered dùng cho môi trường học tập và chạy local. GitHub lưu mã nguồn; GitHub Pages không chạy được PHP hoặc MySQL.

## 1. Công nghệ và yêu cầu

- Windows và PowerShell.
- XAMPP hoặc môi trường tương đương có Apache, MySQL/MariaDB và PHP.
- PHP 8.0 trở lên.
- Các extension PHP bắt buộc:
  - <code>pdo_mysql</code>: kết nối MySQL/MariaDB.
  - <code>mbstring</code>: xử lý chuỗi tiếng Việt.
  - <code>curl</code>: cần cho bộ kiểm tra tích hợp.
- Git nếu làm việc theo nhóm.

Kiểm tra PHP và các extension bằng PowerShell:

~~~powershell
C:/xampp/php/php.exe -v
C:/xampp/php/php.exe -m | Select-String 'curl|mbstring|pdo_mysql'
~~~

Nếu lệnh không tìm thấy PHP, thay <code>C:/xampp/php/php.exe</code> bằng đường dẫn PHP của máy bạn.

## 2. Lấy mã nguồn từ GitHub

Thay <code>&lt;URL_REPOSITORY&gt;</code> bằng URL repository của nhóm:

~~~powershell
git clone <URL_REPOSITORY> C:/xampp/htdocs/Web503073
Set-Location C:/xampp/htdocs/Web503073
~~~

Nếu thư mục đã tồn tại:

~~~powershell
Set-Location C:/xampp/htdocs/Web503073
git pull
~~~

Mỗi thành viên phải tự tạo cấu hình và tài khoản database trên máy của mình. Không chép <code>config/database.local.php</code> lên GitHub.

## 3. Chạy lần đầu bằng XAMPP

### 3.1. Khởi động dịch vụ

1. Mở XAMPP Control Panel.
2. Start **Apache**.
3. Start **MySQL**.
4. Mở PowerShell tại thư mục dự án:

~~~powershell
Set-Location C:/xampp/htdocs/Web503073
~~~

Trang chính sẽ chạy tại:

~~~text
http://localhost/Web503073/
~~~

### 3.2. Tạo database và hai tài khoản riêng

Ứng dụng không dùng <code>root</code> và không chấp nhận mật khẩu rỗng.

- <code>web503073_app</code>: tài khoản mà website sử dụng khi chạy. Tài khoản này chỉ có quyền đọc/ghi dữ liệu của ứng dụng.
- <code>web503073_setup</code>: tài khoản chỉ dùng trong PowerShell để tạo database, bảng, seed và database test. Không dùng tài khoản này trong file cấu hình website.

Mở MySQL client:

~~~powershell
C:/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -u root
~~~

Nếu tài khoản <code>root</code> có mật khẩu, thêm <code>-p</code> rồi nhập mật khẩu khi được hỏi. Trong màn hình MySQL/MariaDB, chạy SQL sau. Thay hai giá trị <code>APP_PASSWORD</code> và <code>SETUP_PASSWORD</code> bằng mật khẩu riêng, đủ dài; không dùng mật khẩu thật của tài khoản khác.

~~~sql
CREATE DATABASE IF NOT EXISTS `web503073`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'web503073_app'@'127.0.0.1'
    IDENTIFIED BY 'APP_PASSWORD';
ALTER USER 'web503073_app'@'127.0.0.1'
    IDENTIFIED BY 'APP_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE
    ON `web503073`.* TO 'web503073_app'@'127.0.0.1';

-- Chỉ áp dụng cho database test có tiền tố web503073_.
-- Không cấp quyền DDL cho database thật cho tài khoản website.
GRANT ALL PRIVILEGES
    ON `web503073\_%`.* TO 'web503073_app'@'127.0.0.1';

CREATE USER IF NOT EXISTS 'web503073_setup'@'127.0.0.1'
    IDENTIFIED BY 'SETUP_PASSWORD';
ALTER USER 'web503073_setup'@'127.0.0.1'
    IDENTIFIED BY 'SETUP_PASSWORD';
GRANT CREATE, DROP ON *.*
    TO 'web503073_setup'@'127.0.0.1';
GRANT ALL PRIVILEGES
    ON `web503073`.* TO 'web503073_setup'@'127.0.0.1';
GRANT ALL PRIVILEGES
    ON `web503073\_%`.* TO 'web503073_setup'@'127.0.0.1';

FLUSH PRIVILEGES;
~~~

Trong SQL trên, dấu gạch dưới được escape trong pattern database để chỉ khớp tiền tố cố định, không phải wildcard một ký tự. Nhờ vậy quyền bổ sung chỉ áp dụng cho các database test như <code>web503073_test_abc123</code>.

### 3.3. Tạo file cấu hình local

Sao chép file mẫu:

~~~powershell
Copy-Item config/database.local.example.php config/database.local.php
~~~

Mở <code>config/database.local.php</code> và thay <code>REPLACE_WITH_A_LONG_RANDOM_PASSWORD</code> bằng đúng <code>APP_PASSWORD</code> đã đặt trong SQL:

~~~php
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'web503073',
    'user' => 'web503073_app',
    'password' => 'APP_PASSWORD',
];
~~~

File <code>config/database.local.php</code> đã được <code>.gitignore</code> bỏ qua. Mỗi thành viên có thể dùng password khác nhau.

Có thể dùng biến môi trường thay cho file local. Biến môi trường được ưu tiên hơn file:

~~~powershell
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '3306'
$env:DB_NAME = 'web503073'
$env:DB_USER = 'web503073_app'
$env:DB_PASSWORD = 'APP_PASSWORD'
~~~

### 3.4. Tạo bảng và dữ liệu mẫu

Tài khoản setup chỉ được đặt tạm trong phiên PowerShell:

~~~powershell
$env:DB_SETUP_USER = 'web503073_setup'
$env:DB_SETUP_PASSWORD = 'SETUP_PASSWORD'
C:/xampp/php/php.exe scripts/setup-database.php
Remove-Item Env:DB_SETUP_USER, Env:DB_SETUP_PASSWORD
~~~

Lệnh này:

- tạo database <code>web503073</code> nếu chưa có;
- tạo ba bảng <code>users</code>, <code>plants</code>, <code>favorites</code> từ <code>database/schema.sql</code>;
- nhập 40 cây từ <code>data/plants.php</code>;
- giữ lại tài khoản, cây và lượt yêu thích đã tồn tại khi chạy lại.

Nếu thiếu <code>DB_SETUP_USER</code> hoặc <code>DB_SETUP_PASSWORD</code>, lệnh sẽ dừng và không dùng <code>root</code> thay thế.

### 3.5. Tạo tài khoản quản trị

Chạy:

~~~powershell
C:/xampp/php/php.exe scripts/create-admin.php
~~~

Nhập tên, email và password khi được hỏi. Password phải dài tối thiểu 8 ký tự và tối đa 72 byte. Có thể truyền bằng biến môi trường:

~~~powershell
$env:APP_ADMIN_NAME = 'Quản trị viên'
$env:APP_ADMIN_EMAIL = 'admin@example.test'
$env:APP_ADMIN_PASSWORD = 'DO_NOT_COMMIT_THIS_PASSWORD'
C:/xampp/php/php.exe scripts/create-admin.php
Remove-Item Env:APP_ADMIN_NAME, Env:APP_ADMIN_EMAIL, Env:APP_ADMIN_PASSWORD
~~~

Website không có tài khoản demo với password cố định. Đăng ký công khai luôn tạo role <code>user</code>; không thể tự đăng ký role <code>admin</code>.

### 3.6. Mở website

- Trang chủ: <http://localhost/Web503073/>
- Đăng ký: <http://localhost/Web503073/register.php>
- Đăng nhập: <http://localhost/Web503073/login.php>
- Quản trị: đăng nhập bằng tài khoản admin rồi chọn **Quản trị** trên dashboard.

## 4. Chạy bằng PHP built-in server

PHP built-in server không đọc <code>.htaccess</code>, vì vậy luôn chạy kèm router bảo vệ:

~~~powershell
Set-Location C:/xampp/htdocs/Web503073
C:/xampp/php/php.exe -S 127.0.0.1:8090 -t . server-router.php
~~~

Mở <http://127.0.0.1:8090/>. Dừng server bằng <code>Ctrl+C</code>.

Không dùng lệnh sau cho môi trường này vì nó có thể phục vụ file nội bộ:

~~~powershell
# Không khuyến nghị
C:/xampp/php/php.exe -S 127.0.0.1:8090 -t .
~~~

<code>server-router.php</code> chặn <code>.git</code>, <code>.runtime</code>, <code>.env</code>, <code>.htaccess</code>, <code>config/</code>, <code>database/</code>, <code>scripts/</code>, <code>tests/</code>, README và các file log/backup. Khi Apache chạy, <code>.htaccess</code> thực hiện việc tương tự.


## 5. Các chức năng và route chính

| Route | Chức năng | Quyền |
|---|---|---|
| <code>index.php</code> | Trang chủ và cây nổi bật | Công khai |
| <code>dashboard.php</code> | Tổng quan sau khi đăng nhập | Công khai / tùy nội dung |
| <code>collection.php?search=...&category=...</code> | Tìm kiếm và lọc cây | Công khai |
| <code>plant-detail.php?id=22</code> | Chi tiết một cây | Công khai |
| <code>register.php</code> | Đăng ký thành viên | Công khai |
| <code>login.php</code> | Đăng nhập | Công khai |
| <code>favorites.php</code> | Danh sách cây yêu thích | Cần đăng nhập |
| <code>profile.php</code> | Sửa thông tin và password | Cần đăng nhập |
| <code>admin.php</code> | Thống kê, tìm kiếm, phân trang và quản lý user | Chỉ admin |
| <code>blog.php</code> | Danh sách bài viết | Công khai |
| <code>blog-post.php?slug=...</code> | Chi tiết bài viết | Công khai |
| <code>actions/favorite-action.php</code> | Thêm/xóa yêu thích | POST + CSRF + đăng nhập |
| <code>actions/profile-action.php</code> | Cập nhật cá nhân | POST + CSRF + đăng nhập |
| <code>actions/user-action.php</code> | Sửa/khóa/mở khóa user | POST + CSRF + admin |
| <code>actions/logout.php</code> | Đăng xuất | POST + CSRF |

Các ID không hợp lệ trả <code>404</code>. Request GET tới handler thay đổi dữ liệu trả <code>405</code>; request POST thiếu CSRF token trả <code>403</code>.


## 6. Bảo mật quan trọng

- SQL dùng PDO prepared statements; không nối trực tiếp dữ liệu người dùng vào câu lệnh SQL.
- Tìm kiếm escape wildcard % và _ trước khi dùng trong <code>LIKE</code>.
- Output HTML được escape bằng helper <code>e()</code>.
- Form thay đổi dữ liệu dùng POST và CSRF token.
- Session dùng cookie <code>HttpOnly</code>, <code>SameSite=Lax</code>, <code>Secure</code> khi chạy HTTPS.
- Session mới nằm ngoài document root, mặc định trong <code>%TEMP%/web503073-sessions</code>.
- Apache và PHP router chặn metadata Git, secret, cấu hình, database schema, script CLI và runtime state.
- App user không phải <code>root</code>, không có password rỗng và không được dùng password placeholder.
- Khóa user làm mất hiệu lực các session cũ; đổi email hoặc password cũng kết thúc các session khác.

Không commit các file sau:

- <code>config/database.local.php</code>;
- <code>.env</code> và <code>.env.*</code>;
- password, private key, certificate private, credential JSON;
- <code>.runtime/</code>, log, cache, database dump và file tạm.

<code>.gitignore</code> chỉ ngăn file chưa được track. Nếu một secret đã từng commit, cần đổi secret và xóa khỏi lịch sử Git; chỉ thêm tên file vào <code>.gitignore</code> là chưa đủ.


## 7. Kiểm tra và test

### 7.1. Kiểm tra cú pháp PHP

~~~powershell
Set-Location C:/xampp/htdocs/Web503073
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    C:/xampp/php/php.exe -l $_.FullName
}
~~~

### 7.2. Test tích hợp HTTP

Đảm bảo MySQL đang chạy, PHP có <code>curl</code>, <code>pdo_mysql</code>, <code>mbstring</code>, và <code>web503073_setup</code> có quyền trên database chính cùng database test có tiền tố <code>web503073_</code>.

~~~powershell
$env:DB_SETUP_USER = 'web503073_setup'
$env:DB_SETUP_PASSWORD = 'SETUP_PASSWORD'
C:/xampp/php/php.exe tests/run.php
C:/xampp/php/php.exe tests/run.php --subdirectory
Remove-Item Env:DB_SETUP_USER, Env:DB_SETUP_PASSWORD
~~~

Bộ test kiểm tra các luồng:

- đăng ký, đăng nhập và password hashing;
- bảo vệ route riêng tư và admin;
- CSRF và cấm GET trên handler thay đổi dữ liệu;
- tìm kiếm tiếng Việt và xử lý wildcard/chuỗi SQL injection thử nghiệm;
- yêu thích, profile, đổi password và vô hiệu hóa session;
- khóa/mở khóa user, phân trang admin;
- setup lặp lại không ghi đè dữ liệu cũ;
- lỗi database trả response an toàn.

Kết quả thành công có dạng:

~~~text
PASS: 161 total checks. Temporary database and web server removed.
~~~

Database test có dạng <code>web503073_test_&lt;random&gt;</code> và sẽ được xóa khi test kết thúc bình thường. Nếu dừng test bằng <code>Ctrl+C</code>, kiểm tra và xóa các database test còn sót sau khi chắc chắn không còn test server đang chạy.


### 7.3. Kiểm tra thủ công

Sau khi test hoặc thay đổi giao diện, kiểm tra ít nhất:

- trang chủ, collection, chi tiết cây và blog;
- trạng thái chưa đăng nhập, đã đăng nhập và admin;
- kết quả tìm kiếm rỗng, ký tự tiếng Việt, %, _ và dấu nháy;
- ID cây/user không tồn tại;
- form thiếu CSRF hoặc gửi bằng GET;
- session sau logout, đổi password và khóa user.

## 8. Cấu trúc thư mục

~~~text
Web503073/
├── actions/                 # POST handlers và thao tác thay đổi dữ liệu
├── config/                  # database.local.example.php; local config bị ignore
├── database/                # database/schema.sql
├── data/                    # plants.php và content.php
├── includes/                # init, auth, PDO, repository và template dùng chung
├── scripts/                 # setup-database.php, create-admin.php; chỉ chạy CLI
├── tests/                   # test tích hợp HTTP
├── css/                     # stylesheet
├── js/                      # JavaScript thuần
├── images/                  # hình ảnh giao diện
├── .htaccess                # bảo vệ khi chạy Apache
├── server-router.php        # bảo vệ khi chạy PHP built-in server
├── AGENTS.md                # hướng dẫn làm việc trong repository
└── README.md                # tài liệu này
~~~

Các trang PHP công khai như <code>index.php</code>, <code>collection.php</code>, <code>profile.php</code> và <code>admin.php</code> nằm ở thư mục gốc.


## 9. Làm việc nhóm với GitHub

Trước khi commit:

~~~powershell
git status
git diff --check
git add .
git status
~~~

Kiểm tra chắc chắn <code>config/database.local.php</code>, <code>.env</code> và password không xuất hiện trong danh sách staged. Sau đó:

~~~powershell
git commit -m "Update project documentation"
git push
~~~

Thành viên khác chỉ cần clone repository, cài XAMPP, tạo credential local riêng và chạy các bước ở mục 3. Không gửi password qua commit, README, issue hoặc chat nhóm công khai.

## 10. Xử lý lỗi thường gặp

### Website trả 500 với Configure a dedicated database user...

Chưa có <code>config/database.local.php</code>, hoặc <code>DB_USER</code>/<code>DB_PASSWORD</code> chưa được đặt. Tạo file từ <code>config/database.local.example.php</code> và dùng user không phải <code>root</code> với password không rỗng.

### Setup báo Database setup requires DB_SETUP_USER and DB_SETUP_PASSWORD

Đặt hai biến môi trường trong đúng PowerShell đang chạy lệnh:

~~~powershell
$env:DB_SETUP_USER = 'web503073_setup'
$env:DB_SETUP_PASSWORD = 'SETUP_PASSWORD'
C:/xampp/php/php.exe scripts/setup-database.php
Remove-Item Env:DB_SETUP_USER, Env:DB_SETUP_PASSWORD
~~~

### Lỗi SQLSTATE[HY000] [1044] Access denied

User đang dùng không có quyền trên database được chỉ định. Kiểm tra <code>DB_NAME</code>, user trong <code>config/database.local.php</code> và các lệnh <code>GRANT</code> ở mục 3.2. Với test, app user phải có quyền trên tiền tố <code>web503073_</code>.

### Apache mở nhưng trang không vào được

Kiểm tra Apache có chạy đúng port 80 và URL có đúng thư mục:

~~~text
http://localhost/Web503073/
~~~

Nếu dùng port khác, mở đúng port được hiển thị trong XAMPP. Xem <code>C:/xampp/apache/logs/error.log</code> để biết lỗi PHP hoặc quyền truy cập.

### PHP built-in server không mở được file hoặc route

Đảm bảo lệnh có <code>server-router.php</code> ở cuối:

~~~powershell
C:/xampp/php/php.exe -S 127.0.0.1:8090 -t . server-router.php
~~~

Các URL tới <code>.git</code>, <code>config</code>, <code>database</code>, <code>scripts</code>, <code>tests</code> và secret trả <code>404</code>/<code>403</code> là hành vi bảo vệ bình thường.

### Không tìm thấy extension PHP

Mở <code>C:/xampp/php/php.ini</code>, bật các dòng tương ứng với <code>pdo_mysql</code>, <code>mbstring</code> và <code>curl</code>, sau đó restart Apache. Kiểm tra lại bằng <code>php -m</code>.

## 11. Quy ước phát triển

- Dùng bốn space trong PHP.
- Entry point PHP dùng <code>declare(strict_types=1);</code>.
- Dùng prepared statements cho SQL.
- Escape output bằng <code>e()</code>.
- State-changing request dùng POST + CSRF.
- Redirect nội bộ dùng <code>safe_return_url()</code>.
- Không commit credential thật.
- Sau backend change chạy lint và test; sau frontend change kiểm tra cả trạng thái bình thường, rỗng và không hợp lệ.

Dự án hiện không có package manager, build step hoặc formatter bắt buộc.
