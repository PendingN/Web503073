# Vũ Điệu Rừng Xanh — PHP thuần

Thư mục này chỉ dùng PHP, HTML5, CSS3 và JavaScript thuần; dữ liệu cây, blog và admin là dữ liệu demo trong các mảng PHP, không cần MySQL.

## Chạy bằng XAMPP

1. Sao chép toàn bộ thư mục `project-name` vào `C:\xampp\htdocs\project-name\`.
2. Mở XAMPP và khởi động Apache.
3. Truy cập `http://localhost/project-name/`.

Nếu đổi tên thư mục thành `green-site`, URL sẽ là `http://localhost/green-site/`.

## Chạy bằng Laragon

Đặt thư mục vào `C:\laragon\www\project-name\`, khởi động Laragon và mở `http://project-name.test/` hoặc `http://localhost/project-name/`.

## Tài khoản demo

- Email: `demo@dv03.vn`
- Mật khẩu: `123456`

Đăng nhập chỉ phục vụ minh họa. Session PHP lưu email người dùng và danh sách cây yêu thích trong cùng trình duyệt.

## Các route chính

| URL | Chức năng |
|---|---|
| `index.php` | Landing page |
| `login.php` | Đăng nhập demo |
| `actions/logout.php` | Đăng xuất |
| `dashboard.php` | Dashboard và 4 cây nổi bật |
| `collection.php` | 40 cây, tìm kiếm và lọc nhóm |
| `favorites.php` | Cây yêu thích trong PHP session |
| `profile.php` | Trang cá nhân |
| `plant-detail.php?id=22` | Chi tiết cây |
| `blog.php` | Danh sách bài viết |
| `blog-post.php?slug=...` | Chi tiết bài viết |
| `admin.php` | Giao diện quản trị demo |

Tìm kiếm dùng `collection.php?search=...&category=...`. Thao tác yêu thích dùng POST tới `actions/favorite-action.php`, có CSRF token và redirect về trang trước đó.

Các trang hiển thị nằm ở thư mục gốc. Thư mục `actions/` chứa các file xử lý thao tác (`favorite-action.php`, `logout.php`); `includes/` chứa phần giao diện dùng chung và khởi tạo ứng dụng. `login.php` hiển thị form và xử lý đăng nhập ngay trên cùng trang.

## Mapping từ Next.js

| Next.js | PHP |
|---|---|
| `app/page.tsx` + `components/Landing.tsx` | `index.php` |
| `components/SiteHeader.tsx` | `includes/navbar.php` |
| `app/login/page.tsx` + login API | `login.php`, `actions/logout.php` |
| `app/dashboard/page.tsx` | `dashboard.php` |
| Dashboard collection/favorites state | `collection.php`, `favorites.php`, `actions/favorite-action.php` |
| `data/plants_dataset.json` | `data/plants.php` |
| `app/blog/page.tsx` | `blog.php` |
| `app/blog/[slug]/page.tsx` | `blog-post.php` |
| `app/admin/page.tsx` | `admin.php` |
| `app/globals.css` + `shared/theme.css` | `css/style.css` |
| React state/event handlers | `js/main.js` và PHP session |

Các include trong `includes/` có thể tái sử dụng bằng `require`/`require_once`. Ảnh nằm trong `images/`, không phụ thuộc vào thư mục asset của Next.js.

## Kiểm tra nhanh bằng PHP CLI

Từ thư mục project, chạy:

```powershell
$phpFiles = Get-ChildItem -Recurse -Filter *.php
foreach ($file in $phpFiles) { C:\xampp\php\php.exe -l $file.FullName }
```

Hoặc chạy server PHP tích hợp:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8090 -t .
```

