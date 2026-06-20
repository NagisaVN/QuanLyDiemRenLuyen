# Thống Kê Pages

Tài liệu này đếm page theo các route `GET` trong `routes/web.php` và `routes/auth.php`.

## Cách Đếm

- `Page` là route `GET` hiển thị giao diện Blade cho người dùng.
- Không tính các route `GET` chỉ dùng để redirect hoặc tải file.
- Với CRUD admin dạng `/admin/{module}`, tài liệu đếm cả 2 cách:
  - Theo pattern route: 4 page pattern dùng chung cho mọi module.
  - Theo page thực tế khi mở rộng 15 module: mỗi module có danh sách, tạo mới, chi tiết, chỉnh sửa.

## Kết Luận Nhanh

| Chỉ số | Số lượng | Ghi chú |
| --- | ---: | --- |
| Tổng route `GET` pattern | 38 | Bao gồm page, redirect, download/export. |
| Tổng route `GET` nếu mở rộng 15 module admin | 94 | 4 CRUD pattern admin thành 60 route theo module. |
| Page UI pattern không mở rộng module | 31 | Đã loại redirect/download/export. |
| Page UI thực tế khi mở rộng module admin | 87 | Con số nên dùng khi hỏi "app có bao nhiêu page". |
| Route `GET` không tính là page | 7 | Dashboard router, verify link, download/export/check-in. |

## Breakdown Page UI Thực Tế

| Nhóm | Số page | Route/page chính |
| --- | ---: | --- |
| Public/Auth | 6 | `/`, `/login`, `/forgot-password`, `/reset-password/{token}`, `/verify-email`, `/confirm-password`. |
| Chung sau đăng nhập | 1 | `/profile`. |
| Dashboard theo vai trò | 5 | `/admin/dashboard`, `/sinh-vien/dashboard`, `/gvcn/dashboard`, `/doan-hoi/dashboard`, `/hoi-dong/dashboard`. |
| Quản lý đợt đánh giá | 3 | `/admin/dot-danh-gia`, `/admin/dot-danh-gia/create`, `/admin/dot-danh-gia/{dotDanhGia}/edit`. |
| Admin CRUD mở rộng 15 module | 60 | 15 module x 4 page: danh sách, tạo mới, chi tiết, chỉnh sửa. |
| Sinh viên | 3 | `/sinh-vien/phieu-danh-gia`, `/sinh-vien/phieu-danh-gia/lich-su`, `/sinh-vien/hoat-dong`. |
| GVCN | 2 | `/gvcn/phieu-danh-gia`, `/gvcn/phieu-danh-gia/{phieu}`. |
| Đoàn - Hội | 5 | `/doan-hoi/activities`, `/doan-hoi/activities/create`, `/doan-hoi/activities/{hoatDong}/edit`, `/doan-hoi/activities/{hoatDong}/registrations`, `/doan-hoi/activities/{hoatDong}/qr`. |
| Hội đồng khoa | 2 | `/hoi-dong/phieu-danh-gia`, `/hoi-dong/phieu-danh-gia/{phieu}`. |

Tổng: `6 + 1 + 5 + 3 + 60 + 3 + 2 + 5 + 2 = 87 page`.

## Module CRUD Admin

Mỗi module bên dưới có 4 page pattern dùng chung:

```text
GET /admin/{module}
GET /admin/{module}/create
GET /admin/{module}/{id}
GET /admin/{module}/{id}/edit
```

| Module | Tên nghiệp vụ | Số page |
| --- | --- | ---: |
| `users` | Người dùng | 4 |
| `roles` | Vai trò | 4 |
| `permissions` | Phân quyền | 4 |
| `khoas` | Khoa | 4 |
| `lops` | Lớp | 4 |
| `sinh-viens` | Sinh viên | 4 |
| `nam-hocs` | Năm học | 4 |
| `hoc-kys` | Học kỳ | 4 |
| `tieu-chis` | Tiêu chí | 4 |
| `muc-tieu-chis` | Mức tiêu chí | 4 |
| `minh-chungs` | Minh chứng | 4 |
| `hoat-dongs` | Hoạt động | 4 |
| `thong-baos` | Thông báo | 4 |
| `logs` | Nhật ký hệ thống | 4 |
| `backups` | Sao lưu dữ liệu | 4 |

Tổng CRUD admin: `15 x 4 = 60 page`.

## Route GET Không Tính Là Page

| Route | Lý do |
| --- | --- |
| `/dashboard` | Router trung gian, redirect theo role. |
| `/sinh-vien/phieu-danh-gia/in` | Tải PDF phiếu đánh giá. |
| `/sinh-vien/hoat-dong/{hoatDong}/check-in` | Xử lý check-in QR rồi redirect. |
| `/minh-chung/{minhChung}/download` | Tải file minh chứng. |
| `/hoi-dong/export/excel` | Tải file Excel. |
| `/hoi-dong/export/pdf` | Tải file PDF. |
| `/verify-email/{id}/{hash}` | Xác thực email rồi redirect. |

## Ghi Chú

- Route `/sinh-vien/phieu-danh-gia` có thể hiển thị 2 trạng thái view: form đánh giá hoặc màn hình thông báo đã đóng/hết hạn. Khi đếm page theo route, vẫn tính là 1 page.
- View `resources/views/auth/register.blade.php` và `RegisteredUserController` đang tồn tại, nhưng `routes/auth.php` hiện chưa khai báo route `register`, nên không được tính vào page đang truy cập được.
