# Vũ Điệu Rừng Xanh — PHP + MySQL

Website dùng PHP 8.0+, MySQL/MariaDB, HTML, CSS và JavaScript thuần. Backend lưu tài khoản, danh mục cây và cây yêu thích trong MySQL. Bài viết vẫn nằm trong `data/content.php`; form newsletter hiện chỉ hiển thị thông báo ở giao diện.

## Chạy bằng XAMPP

1. Mở thư mục `C:\xampp\htdocs\Web503073` và khởi động **Apache** cùng **MySQL** trong XAMPP.
2. Mở terminal PowerShell tại thư mục dự án:

   ```powershell
   cd C:\xampp\htdocs\Web503073
   C:\xampp\php\php.exe scripts/setup-database.php
   ```

   Lệnh tạo database `web503073`, ba bảng `users`, `plants`, `favorites` và nhập 40 cây từ `data/plants.php`. Chạy lại lệnh sẽ giữ nguyên tài khoản, cây và mục yêu thích đã có.
3. Tạo tài khoản quản trị bằng CLI:

   ```powershell
   C:\xampp\php\php.exe scripts/create-admin.php
   ```

   Nhập họ tên, email riêng cho quản trị viên và mật khẩu ít nhất 8 ký tự, tối đa 72 byte. Mật khẩu nhập ở terminal có hiển thị. Lệnh không thay đổi hoặc nâng quyền tài khoản đã tồn tại.
4. Mở [website](http://localhost/Web503073/), [đăng ký](http://localhost/Web503073/register.php) hoặc [đăng nhập](http://localhost/Web503073/login.php). Đăng nhập bằng tài khoản quản trị rồi chọn **Quản trị** trên dashboard.

Không còn tài khoản demo dùng mật khẩu cố định. Tài khoản đăng ký trên website luôn có vai trò thành viên.

## Cấu hình database

Mặc định: host `127.0.0.1`, port `3306`, database `web503073`, user `root`, mật khẩu rỗng (XAMPP local). Nếu khác, sao chép `config/database.local.example.php` thành `config/database.local.php` rồi sửa thông tin. File cấu hình local được Git bỏ qua.

Có thể dùng biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`; chúng được ưu tiên hơn file local. CLI tạo admin cũng nhận `APP_ADMIN_NAME`, `APP_ADMIN_EMAIL`, `APP_ADMIN_PASSWORD`.

PHP cần bật `pdo_mysql` và `mbstring`; bộ kiểm tra cần thêm `curl`. XAMPP hiện tại đã có các extension này. Khi kết nối hoặc bảng database chưa sẵn sàng, website trả HTTP 503 và ghi chi tiết vào error log của server.

## Chức năng và route

| URL | Chức năng |
|---|---|
| `index.php`, `dashboard.php` | Trang chủ và tổng quan công khai |
| `collection.php?search=...&category=...` | Tìm kiếm và lọc cây từ MySQL |
| `plant-detail.php?id=22` | Chi tiết cây; ID không tồn tại trả 404 |
| `register.php`, `login.php` | Đăng ký, đăng nhập bằng mật khẩu đã băm |
| `favorites.php` | Danh sách yêu thích riêng, lưu qua các lần đăng nhập |
| `profile.php` | Sửa họ tên, email, mật khẩu; cần đăng nhập |
| `admin.php` | Thống kê, tìm kiếm, phân trang, xem/sửa/khóa/mở khóa người dùng |
| `blog.php`, `blog-post.php?slug=...` | Bài viết từ dữ liệu PHP |

Admin sửa họ tên và email; tài khoản quản trị không thể bị khóa qua website. Khóa tài khoản kết thúc các phiên đăng nhập, và mở khóa yêu cầu người dùng đăng nhập lại. Đổi email hoặc mật khẩu ở trang cá nhân cần mật khẩu hiện tại và kết thúc các phiên khác.

Các thao tác yêu thích, sửa tài khoản và đăng xuất dùng POST với CSRF token. Câu lệnh SQL dùng prepared statements; dữ liệu hiển thị được escape. Session chỉ lưu ID và phiên bản đăng nhập, dùng cookie HttpOnly, SameSite=Lax và Secure khi chạy HTTPS.

## Cấu trúc

- Trang PHP ở thư mục gốc; `actions/` xử lý POST.
- `includes/` chứa giao diện, khởi tạo, kết nối PDO, truy vấn và kiểm tra quyền.
- `config/` cấu hình kết nối; `database/schema.sql` định nghĩa bảng.
- `scripts/` chứa lệnh setup và tạo admin, chỉ chạy bằng CLI.
- `data/plants.php` là dữ liệu seed; `data/content.php` chứa bài viết.
- `css/`, `js/`, `images/` chứa tài nguyên; `tests/` chứa kiểm tra tích hợp.

## Kiểm tra

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
C:\xampp\php\php.exe tests/run.php
C:\xampp\php\php.exe tests/run.php --subdirectory
```

Bộ kiểm tra tự tạo database `web503073_test_<random>` và server PHP local, kiểm tra các luồng thực tế qua HTTP rồi xóa tài nguyên tạm. `--subdirectory` kiểm tra đường dẫn có tên thư mục như khi chạy trong XAMPP. MySQL phải đang chạy và tài khoản database cần quyền tạo/xóa database test. Dữ liệu của database chính được giữ nguyên.

Để chạy bằng server PHP tích hợp sau khi setup database:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8090 -t .
```

Mở [http://127.0.0.1:8090/](http://127.0.0.1:8090/). Với Laragon, đặt dự án trong `C:\laragon\www\Web503073`, bật web server/MySQL và chạy hai lệnh CLI bằng PHP của Laragon.

