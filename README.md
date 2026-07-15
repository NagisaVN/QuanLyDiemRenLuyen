# QuanLyDiemRenLuyen

## Scheduler

Workflow đợt đánh giá được đồng bộ mỗi phút bằng Laravel Scheduler. `composer dev`
tự chạy `schedule:work`; Docker/Railway chạy scheduler mặc định. Nếu dùng một cron
riêng, đặt `RUN_SCHEDULER=false` cho web container và gọi lệnh sau mỗi phút:

```bash
php artisan schedule:run
```

Thời gian lịch được lưu UTC và nhập/hiển thị theo `APP_DISPLAY_TIMEZONE`
(mặc định `Asia/Ho_Chi_Minh`).

## Kiểm thử an toàn

- PHPUnit mặc định chạy SQLite `:memory:`: `php artisan test`.
- Integration/E2E phải dùng MySQL riêng và tên database bắt buộc kết thúc bằng `_testing`; tham khảo `.env.testing.example`.
- Chromium E2E chạy bằng `npm run test:e2e`. Global setup chỉ chạy `migrate:fresh --seed` sau khi xác nhận `APP_ENV=testing` và hậu tố `_testing`.
- Không chạy test, seeder hoặc `migrate:fresh` với `.env` production/staging.

Hồ sơ audit, inventory route/schema và ma trận quyền nằm tại `docs/audit-bao-mat-va-phan-quyen.md`.
