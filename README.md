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
