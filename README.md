# Hệ thống Quản lý Điểm Rèn luyện

Ứng dụng web hỗ trợ quản lý toàn bộ quy trình đánh giá điểm rèn luyện sinh viên: sinh viên tự đánh giá, giảng viên chủ nhiệm duyệt, hội đồng chốt điểm, quản lý hoạt động ngoại khóa, điểm danh QR và công bố kết quả.

## Chức năng chính

### Quản trị viên

- Quản lý người dùng, vai trò và phân quyền.
- Quản lý khoa, lớp, năm học, học kỳ và bộ tiêu chí đánh giá.
- Tạo và quản lý các đợt đánh giá theo thời hạn.
- Quản lý thông báo, nhật ký hệ thống và bản sao lưu database.

### Sinh viên

- Tự chấm điểm rèn luyện và tải minh chứng.
- Lưu nháp, gửi phiếu và xem lịch sử đánh giá.
- Nhận cảnh báo khi đợt đánh giá còn không quá 7 ngày.
- Đăng ký hoạt động, điểm danh bằng QR và theo dõi thông báo.
- In phiếu đánh giá và xem kết quả đã công bố.

### Giảng viên chủ nhiệm

- Xem danh sách phiếu của lớp phụ trách.
- Kiểm tra minh chứng, điều chỉnh điểm và xác nhận phiếu.
- Thực hiện duyệt trong thời gian dành cho GVCN.

### Cán bộ Đoàn/Hội

- Tạo và quản lý hoạt động sinh viên.
- Quản lý đăng ký, QR điểm danh và xác nhận tham gia.
- Điều chỉnh điểm hoạt động theo quyền được cấp.

### Hội đồng/Công tác sinh viên

- Duyệt, khóa và chốt điểm cuối cùng.
- Mở, đóng và công bố đợt đánh giá.
- Xuất kết quả ra Excel hoặc PDF.

## Công nghệ sử dụng

- PHP 8.3 và Laravel 13.
- MySQL 8.
- Blade, Vite, Tailwind CSS, Alpine.js và Bootstrap/AdminLTE.
- Spatie Laravel Permission cho phân quyền.
- Laravel Echo và Pusher cho thông báo thời gian thực.
- PHPUnit cho feature/unit test và Playwright cho E2E test.
- DomPDF và Laravel Excel cho xuất báo cáo.

## Yêu cầu môi trường

- PHP `>= 8.3` với các extension PDO MySQL và GD.
- Composer.
- MySQL 8 hoặc MariaDB tương thích.
- Node.js `>= 22.12` và npm.
- `mysqldump` nếu sử dụng chức năng sao lưu database.

Trên Windows có thể dùng Laragon để cung cấp PHP, MySQL và web server.

## Cài đặt nhanh

### 1. Cài dependency

```powershell
composer install
npm install
```

### 2. Tạo file môi trường

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Nếu lệnh `php` chưa có trong PATH của Laragon, sử dụng đường dẫn PHP tương ứng, ví dụ:

```powershell
D:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe artisan key:generate
```

### 3. Tạo database

Tạo database MySQL:

```sql
CREATE DATABASE quan_ly_diem_ren_luyen
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Kiểm tra cấu hình trong `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quan_ly_diem_ren_luyen
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Khởi tạo dữ liệu

```powershell
php artisan migrate --seed
php artisan storage:link
```

Seeder tạo dữ liệu minh họa, bộ tiêu chí, đợt đánh giá, hoạt động và các tài khoản dùng thử.

### 5. Chạy ứng dụng

Cách đầy đủ cho môi trường phát triển:

```powershell
composer run dev
```

Lệnh này chạy đồng thời web server, queue worker, scheduler, log viewer và Vite.

Nếu dùng Laragon làm web server, có thể chỉ chạy frontend và các tiến trình nền:

```powershell
npm run dev
php artisan queue:work
php artisan schedule:work
```

Build frontend cho production:

```powershell
npm run build
```

## Tài khoản mẫu

Tất cả tài khoản do `DatabaseSeeder` tạo có mật khẩu `password`.

| Vai trò | Tên đăng nhập | Email |
| --- | --- | --- |
| Quản trị viên | `admin` | `admin@school.test` |
| Giảng viên chủ nhiệm | `gvcn01` | `gvcn@school.test` |
| Cán bộ Đoàn/Hội | `doanhoi01` | `doanhoi@school.test` |
| Hội đồng/Công tác sinh viên | `ctsv01` | `ctsv@school.test` |
| Sinh viên | `sv001`, `sv002`, `sv003` | `sv001@school.test`, ... |

> Chỉ sử dụng các tài khoản này trong môi trường phát triển hoặc demo. Hãy đổi mật khẩu và không chạy seeder dữ liệu mẫu trên production.

## Biến môi trường quan trọng

| Biến | Ý nghĩa |
| --- | --- |
| `APP_URL` | Địa chỉ ứng dụng. |
| `APP_DISPLAY_TIMEZONE` | Múi giờ nhập và hiển thị, mặc định `Asia/Ho_Chi_Minh`. |
| `DB_*` | Kết nối MySQL. |
| `QUEUE_CONNECTION` | Queue driver, mặc định dùng database. |
| `BROADCAST_CONNECTION` | Broadcast driver cho thông báo thời gian thực. |
| `PUSHER_*` | Thông tin Pusher khi bật realtime. |
| `GOOGLE_MAPS_BROWSER_KEY` | API key bản đồ cho chức năng vị trí hoạt động. |
| `MYSQLDUMP_PATH` | Đường dẫn tùy chỉnh đến `mysqldump`. |

Các thời điểm của đợt đánh giá được lưu bằng UTC và hiển thị theo `APP_DISPLAY_TIMEZONE`.

## Tác vụ tự động

Laravel Scheduler quản lý các tác vụ sau:

| Tần suất | Tác vụ |
| --- | --- |
| Mỗi phút | Đồng bộ trạng thái mở/đóng/công bố của đợt đánh giá. |
| Mỗi phút | Tự động cập nhật trạng thái hoạt động. |
| Mỗi giờ | Đối soát và gửi thông báo sinh viên. |
| 01:00 hằng ngày | Sao lưu database MySQL. |

Khi không dùng `composer run dev`, scheduler phải được chạy riêng:

```powershell
php artisan schedule:work
```

Trên máy chủ có thể cấu hình cron gọi lệnh sau mỗi phút:

```bash
php artisan schedule:run
```

## Sao lưu database

Chạy sao lưu thủ công:

```powershell
php artisan backup:database
```

File SQL được lưu trong `storage/app/private/backups`. Hệ thống chỉ giữ bảy bản sao lưu mới nhất và ghi lại kết quả trong bảng quản lý backup.

Nếu `mysqldump` không nằm trong PATH, thêm vào `.env`:

```dotenv
MYSQLDUMP_PATH=D:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe
```

## Kiểm thử

Chạy toàn bộ unit/feature test:

```powershell
php artisan test
```

Chạy E2E test:

```powershell
npx playwright install chromium
npm run test:e2e
```

### Quy tắc an toàn khi kiểm thử

- PHPUnit mặc định sử dụng SQLite `:memory:` theo `phpunit.xml`.
- Integration/E2E với MySQL phải dùng database riêng có tên kết thúc bằng `_testing`.
- Tham khảo `.env.testing.example` trước khi chạy E2E.
- Không chạy `migrate:fresh`, seeder hoặc E2E test trên database production/staging.

## Lệnh hữu ích

```powershell
# Xóa cache cấu hình và giao diện
php artisan optimize:clear

# Đồng bộ trạng thái các đợt đánh giá
php artisan evaluations:sync-statuses

# Đối soát thông báo sinh viên
php artisan notifications:reconcile

# Đồng bộ bộ tiêu chí điểm rèn luyện
php artisan drl:sync-rubric

# Kiểm tra định dạng PHP
vendor\bin\pint --test
```

## Tài liệu dự án

- [Tổng quan use case](docs/usecase-tong-quan.md)
- [Luồng chức năng](docs/flow-chuc-nang.md)
- [Danh sách trang](docs/pages.md)
- [Audit bảo mật và phân quyền](docs/audit-bao-mat-va-phan-quyen.md)
- [Sơ đồ use case](docs/usecase-diagram.svg)

## Lưu ý triển khai

- Đặt `APP_ENV=production`, `APP_DEBUG=false` và cấu hình `APP_URL` chính xác.
- Không commit `.env`, khóa API, mật khẩu database hoặc thông tin Pusher.
- Chạy `php artisan migrate --force` trong quy trình triển khai.
- Chạy queue worker và scheduler dưới process manager của máy chủ.
- Đảm bảo thư mục `storage` và `bootstrap/cache` có quyền ghi.
- Cấu hình HTTPS, backup ngoài máy chủ và chính sách lưu trữ log phù hợp.
