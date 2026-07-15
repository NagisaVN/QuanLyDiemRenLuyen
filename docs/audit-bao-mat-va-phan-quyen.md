# Audit bảo mật, phân quyền và workflow điểm rèn luyện

Ngày kiểm tra: 14/07/2026. Tài liệu phản ánh code sau đợt gia cố và dùng làm checklist regression.

## 1. Inventory hệ thống

| Thành phần | Hiện trạng |
| --- | --- |
| Backend | Laravel 13, PHP 8.3, controller/service/Eloquent |
| Frontend | Blade, Alpine.js, Tailwind/Bootstrap assets, Vite |
| Database | MySQL 8.4 production-compatible; SQLite memory chỉ dùng test nhanh |
| Authentication | Laravel session guard `web`, login bằng email hoặc `ma_dang_nhap`, rate limit 5 lần |
| Authorization | Spatie Permission; permission middleware + kiểm tra phạm vi bản ghi trong controller/service |
| Queue/cache/session | Cấu hình qua Laravel; queue database ở local, sync ở test |
| Realtime | `EvaluationStatusChanged` trên private channel `users.{id}` |
| Scheduler | `evaluations:sync-statuses` mỗi phút; `backup:database` lúc 01:00; đều chống chạy chồng và dùng one-server lock |
| Background commands | `evaluations:sync-statuses`, `evaluations:lock-expired`, `drl:sync-rubric`, `backup:database` |
| UI | 80 route pattern, gồm auth/profile, 5 dashboard, 15 module CRUD admin và các workflow nghiệp vụ |

### Ánh xạ bảng nghiệp vụ

| Tên khái niệm | Bảng/model thực tế |
| --- | --- |
| users | `users` / `User` |
| roles, permissions | `roles`, `permissions` |
| role_permissions | `role_has_permissions` |
| user_roles | `model_has_roles` với `model_type = User` |
| students | `sinh_viens` / `SinhVien` |
| teachers | Không tạo bảng trùng; `users` có permission GVCN và `lops.gvcn_id` |
| classes, faculties | `lops`, `khoas` |
| academic_years, semesters | `nam_hocs`, `hoc_kys` |
| evaluation_periods | `dot_danh_gias` |
| evaluation_forms/details | `phieu_danh_gias`, `chi_tiet_danh_gias` |
| criteria/criterion_levels | `tieu_chis`, `muc_tieu_chis` |
| activities/evidences | `hoat_dongs`, `minh_chungs` |
| audit_logs | `logs` / `SystemLog` |

## 2. Route và API inventory

### Public, auth và tài khoản

`GET /`, `GET|POST /login`, `POST /logout`, `GET|POST /forgot-password`, `GET /reset-password/{token}`, `POST /reset-password`, `GET /verify-email`, `GET /verify-email/{id}/{hash}`, `POST /email/verification-notification`, `GET|POST /confirm-password`, `PUT /password`, `GET|PATCH /profile`, `GET /health`, `GET /healthz`.

Tự đăng ký và tự xóa tài khoản không được public.

### Dashboard và admin

- `GET /dashboard` điều hướng theo permission; dashboard đích: `/admin/dashboard`, `/sinh-vien/dashboard`, `/gvcn/dashboard`, `/doan-hoi/dashboard`, `/hoi-dong/dashboard`.
- Đợt đánh giá: `GET|POST /admin/dot-danh-gia`, `GET /create`, `GET /{dotDanhGia}/edit`, `PUT|DELETE /{dotDanhGia}`, `GET /{dotDanhGia}/ket-qua`, `GET /{dotDanhGia}/export`.
- CRUD admin áp dụng cho `users`, `roles`, `permissions`, `khoas`, `lops`, `sinh-viens`, `nam-hocs`, `hoc-kys`, `tieu-chis`, `muc-tieu-chis`, `minh-chungs`, `hoat-dongs`, `thong-baos`, `logs`, `backups`: `GET /admin/{module}`, `GET /create`, `POST`, `GET /{id}`, `GET /{id}/edit`, `PUT /{id}`, `DELETE /{id}`.
- User restore: `POST /admin/users/{id}/restore`. `logs` và `backups` chỉ đọc qua CRUD UI.

### Sinh viên

`GET|PUT /sinh-vien/phieu-danh-gia`, `POST /submit`, `POST /minh-chung`, `GET /lich-su`, `GET /in`, `GET /sinh-vien/hoat-dong`, `POST /hoat-dong/{hoatDong}/dang-ky`, `POST /hoat-dong/{hoatDong}/check-in`, `GET /sinh-vien/diem-danh/scan`, `GET /minh-chung/{minhChung}/download`.

### GVCN

`GET /gvcn/phieu-danh-gia`, `GET|PUT /gvcn/phieu-danh-gia/{phieu}`, `POST|PUT /{phieu}/xac-nhan`, `POST /gvcn/minh-chung/{minhChung}/duyet`.

### Đoàn–Hội

Resource `/doan-hoi/activities` trừ show; thêm `GET /{hoatDong}/registrations`, `POST /registrations/{registration}/approve`, `POST /{hoatDong}/attendance`, `GET /{hoatDong}/qr`, `POST /{hoatDong}/manual-adjust`.

### Hội đồng/CTSV và JSON API

- Hội đồng: `GET /hoi-dong/phieu-danh-gia`, `GET|PUT /{phieu}`, `POST|PUT /{phieu}/xac-nhan`, `POST /{phieu}/khoa`, `GET /hoi-dong/export`, `/export/excel`, `/export/pdf`.
- JSON API dùng session + CSRF: `POST /api/attendance/sessions`, `POST /api/attendance/scan`, `POST /api/attendance/approve/{hoatDong}`.

## 3. Ma trận Role × chức năng × API × phạm vi

Ký hiệu: R đọc, C tạo, U sửa, D xóa/soft-delete, A duyệt/chốt, X xuất.

| Role mặc định | Chức năng | Route/API chính | Phạm vi dữ liệu | Quyền |
| --- | --- | --- | --- | --- |
| admin | User, role, permission | `/admin/users|roles|permissions` | Toàn hệ thống | R/C/U/D; restore; gán role/permission |
| admin | Dữ liệu nền, audit, backup | `/admin/{module}` | Toàn hệ thống; log/backup chỉ đọc | R/C/U/D; log/backup R |
| admin | Đợt, hoạt động, báo cáo | admin period, activity, export routes | Toàn hệ thống | R/C/U/D/A/X |
| sinh_vien | Phiếu và minh chứng | `/sinh-vien/phieu-danh-gia*` | Chỉ `sinh_vien_id` của mình | R/C/U; submit; không D/A |
| sinh_vien | Hoạt động, QR | student activity + attendance scan API | Hoạt động áp dụng cho khoa; đăng ký của mình | R/C/U check-in/out |
| gvcn | Phiếu lớp | `/gvcn/phieu-danh-gia*` | Chỉ lớp có `lops.gvcn_id = user.id` | R/U/A |
| gvcn | Minh chứng | review/download evidence | Chỉ sinh viên lớp phụ trách | R/A |
| can_bo_doan_hoi | Hoạt động | `/doan-hoi/activities*`, attendance APIs | Chỉ `hoat_dongs.user_id = user.id` | R/C/U/D/A |
| hoi_dong_khoa | Duyệt cuối | `/hoi-dong/phieu-danh-gia*` | Toàn trường | R/U/A |
| hoi_dong_khoa | Đợt và báo cáo | admin period + export routes | Toàn trường | R/C/U/D/A/X theo permission |
| role tùy chỉnh | Bất kỳ capability | Route gắn permission tương ứng | Policy phạm vi vẫn bắt buộc | Theo permission được admin gán |

### Permission mặc định

`manage users`, `manage roles`, `manage master data`, `manage activities`, `manage all activities`, `self evaluate`, `review class forms`, `approve final scores`, `export reports`, `manage_dot_danh_gia`, `open_dot_danh_gia`, `close_dot_danh_gia`, `publish_dot_danh_gia`, `view audit logs`, `manage backups`.

## 4. Phát hiện và biện pháp xử lý

| Mức độ | Trước khi sửa | Sau khi sửa / test chứng minh |
| --- | --- | --- |
| Critical | PhpSpreadsheet 1.30.4 có advisory critical | Nâng 1.30.6; Composer audit sạch |
| High | Cán bộ Đoàn–Hội thao tác được hoạt động của cán bộ khác | Kiểm tra owner ở list/edit/delete/registration/attendance/QR/API; test IDOR trả 403 |
| High | User tự xóa làm cascade dữ liệu sinh viên | Bỏ route tự xóa; user soft-delete/restore; chặn xóa admin cuối |
| High | Role middleware làm permission động không có hiệu lực thật | Route kiểm tra permission; custom role có permission truy cập được qua test |
| High | Audit table có thể bị CRUD và hầu như không ghi | Log/backup chỉ đọc; ghi auth/RBAC/user/evaluation/evidence/activity/backup |
| Medium | GET gọi `syncAll()` và ghi DB | GET chỉ đọc; scheduler thực hiện transition idempotent |
| Medium | Workflow đồng thời có thể chuyển trạng thái/cộng điểm lặp | Transaction + `lockForUpdate`, unique constraint và test gọi lặp |
| Medium | Check-in legacy thay đổi dữ liệu bằng GET | Mutation chuyển sang POST có CSRF và throttle |
| Medium | Score key ngoài rubric bị bỏ qua | Từ chối bằng validation; giới hạn điểm theo rubric |
| Medium | SQLite không phát hiện điểm âm trên cột unsigned MySQL | Migration đổi điểm chi tiết sang signed tiny integer; suite MySQL xác minh |
| Medium | Guzzle/PSR-7 có ba advisory | Nâng Guzzle 7.14.1 và PSR-7 2.12.5 |

## 5. Chạy test và triển khai an toàn

1. Sao lưu production bằng quy trình vận hành hiện hữu; không chạy PHPUnit, Playwright, `migrate:fresh` hoặc seeder trên production.
2. Copy `.env.testing.example`, đặt database riêng có hậu tố `_testing`. Test bootstrap và Playwright từ chối MySQL không có hậu tố này.
3. Chạy `php artisan test` với MySQL testing, `npm run test:e2e`, `npm run build`, Composer audit và npm audit.
4. Trên staging, chạy `php artisan migrate --pretend`, backup, rồi `php artisan migrate --force`; kiểm tra login, role/permission, workflow và scheduler.
5. Migration `000003` có rollback kỹ thuật nhưng rollback sau khi đã lưu điểm âm sẽ không hợp lệ; trong trường hợp đó phải giữ migration và sửa forward thay vì ép unsigned trở lại.

Baseline sau sửa: PHPUnit 59 test/273 assertion pass trên SQLite và MySQL 8.4; Playwright Chromium 6 test pass.
