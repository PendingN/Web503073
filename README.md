# Vũ Điệu Rừng Xanh

Bài tập website 5 trang được xây dựng bằng Next.js, React, TypeScript, Bootstrap và Tailwind CSS. Toàn bộ trang dùng chung phong cách xanh ô liu, nền sáng và bố cục responsive.

## Chạy dự án

Tại thư mục gốc, chạy:

```sh
npm install
npm run dev
```

Mở `http://localhost:3000` và kiểm tra 5 trang:

| Trang | Đường dẫn | Nội dung |
| --- | --- | --- |
| Trang chủ | `/` | Giới thiệu thương hiệu và điều hướng chính |
| Đăng nhập | `/login` | Form đăng nhập demo có kiểm tra dữ liệu |
| Sản phẩm | `/dashboard` | Bộ sưu tập, sản phẩm, tìm kiếm và giỏ hàng demo |
| Blog | `/blog` | Bài nổi bật, danh sách bài và đăng ký nhận tin |
| Quản trị | `/admin` | Thống kê, đơn hàng và quản lý bài viết demo |

## Tài khoản demo

- Email: `demo@dv03.vn`
- Mật khẩu: `123456`

## Kiểm tra trước khi nộp

```sh
npm run typecheck
npm run build
npm start
```

## Cấu trúc mã nguồn

```text
app/                 5 trang, layout và API đăng nhập
components/          Thành phần giao diện dùng lại
public/images/       Hình ảnh tĩnh
shared/theme.css     Màu sắc, kiểu chữ và responsive dùng chung
```

Ứng dụng chỉ chạy từ thư mục gốc, không có dự án con hoặc mã nguồn tham chiếu bên ngoài.

## Phạm vi demo

Đây là bài minh họa giao diện nên chưa có phiên đăng nhập hoặc cơ sở dữ liệu thật. Số liệu, đơn hàng, giá và giỏ hàng sẽ được đặt lại khi tải lại trang. Ảnh rừng nằm tại `public/images/forest.jpg`. Font Google cần Internet và đã có font sans-serif dự phòng.

## Đóng gói bài nộp

Nén các thư mục `app`, `components`, `public`, `shared` cùng các file cấu hình ở thư mục gốc. Không đưa `node_modules`, `.next`, file `*.tsbuildinfo` hoặc file ZIP cũ vào bài nộp.
