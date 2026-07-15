<?php

namespace App\Services;

use App\Events\StudentNotificationCreated;
use App\Models\DotDanhGia;
use App\Models\HoatDong;
use App\Models\PhieuDanhGia;
use App\Models\StudentNotification;
use App\Models\ThongBao;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class StudentNotificationService
{
    public function evaluationOpened(DotDanhGia $period): int
    {
        $period->loadMissing(['hocKy', 'namHoc']);
        $start = $period->displayDate($period->ngay_bat_dau_sinh_vien);
        $end = $period->displayDate($period->ngay_ket_thuc_sinh_vien);

        return $this->notify(
            $this->eligibleUsers(),
            StudentNotification::TYPE_EVALUATION_OPEN,
            "Đã mở {$period->ten_dot}",
            "{$period->ten_dot} ({$period->hocKy?->ten_hoc_ky}) đã được mở. Sinh viên vui lòng thực hiện đánh giá từ {$start} đến {$end}. Trạng thái hiện tại: Đang mở đánh giá. Hãy truy cập mục Tự đánh giá để hoàn thành và gửi phiếu trước thời hạn.",
            "evaluation:{$period->id}:open",
            DotDanhGia::class,
            $period->id,
            route('sinh-vien.evaluations.index', absolute: false),
            $period->ngay_ket_thuc_sinh_vien,
        );
    }

    public function evaluationReminder(DotDanhGia $period, string $milestone): int
    {
        $label = $milestone === '24-hours' ? '24 giờ' : '3 ngày';
        $deadline = $period->displayDate($period->ngay_ket_thuc_sinh_vien);

        return $this->notify(
            $this->eligibleUsers($period, true),
            StudentNotification::TYPE_EVALUATION_REMINDER,
            "Sắp hết hạn {$period->ten_dot}",
            "Đợt đánh giá sẽ kết thúc trong {$label}. Bạn chưa hoàn thành đánh giá, vui lòng thực hiện và gửi phiếu trước {$deadline}.",
            "evaluation:{$period->id}:reminder:{$milestone}",
            DotDanhGia::class,
            $period->id,
            route('sinh-vien.evaluations.index', absolute: false),
            $period->ngay_ket_thuc_sinh_vien,
        );
    }

    public function evaluationClosed(DotDanhGia $period): int
    {
        return $this->notify(
            $this->eligibleUsers(),
            StudentNotification::TYPE_EVALUATION_CLOSED,
            "Đã kết thúc {$period->ten_dot}",
            "{$period->ten_dot} đã kết thúc. Sinh viên không thể chỉnh sửa hoặc gửi đánh giá thêm; phiếu hiện chuyển sang giai đoạn chờ duyệt và tổng hợp kết quả.",
            "evaluation:{$period->id}:closed",
            DotDanhGia::class,
            $period->id,
            route('sinh-vien.evaluations.history', absolute: false),
        );
    }

    public function activityOpened(HoatDong $activity): int
    {
        return $this->notify(
            $this->eligibleActivityUsers($activity),
            StudentNotification::TYPE_SYSTEM_ACTIVITY,
            'Hoạt động đã mở đăng ký',
            "Hoạt động “{$activity->ten_hoat_dong}” đã chính thức mở đăng ký.",
            "activity:{$activity->id}:open",
            HoatDong::class,
            $activity->id,
            route('sinh-vien.activities.show', $activity, absolute: false),
            $activity->thoi_gian_ket_thuc,
        );
    }

    public function activityOpeningSoon(HoatDong $activity): int
    {
        return $this->notify(
            $this->eligibleActivityUsers($activity),
            StudentNotification::TYPE_SYSTEM_ACTIVITY,
            'Hoạt động sắp mở đăng ký',
            "Trong vòng 24 giờ tới, hoạt động “{$activity->ten_hoat_dong}” sẽ mở đăng ký vào {$activity->displayDate($activity->open_registration_at)}.",
            "activity:{$activity->id}:opening-soon:24-hours",
            HoatDong::class,
            $activity->id,
            route('sinh-vien.activities.show', $activity, absolute: false),
            $activity->open_registration_at,
        );
    }

    public function activityRegistrationConfirmed(HoatDong $activity, User $user): int
    {
        $activity->loadMissing('creator');
        $time = $activity->displayDate($activity->thoi_gian_bat_dau);
        $organizer = $activity->creator?->name ?? 'Nhà trường';

        return $this->notify(
            User::query()->whereKey($user->id),
            StudentNotification::TYPE_SYSTEM_ACTIVITY,
            'Đăng ký hoạt động thành công',
            "Bạn đã đăng ký thành công hoạt động “{$activity->ten_hoat_dong}”. Thời gian: {$time}. Địa điểm: {$activity->dia_diem}. Người tổ chức: {$organizer}.",
            "activity:{$activity->id}:registration:{$user->id}",
            HoatDong::class,
            $activity->id,
            route('sinh-vien.activities.show', $activity, absolute: false),
            $activity->thoi_gian_ket_thuc,
        );
    }

    public function announcement(ThongBao $announcement): int
    {
        if (! $announcement->is_active || $announcement->distributed_at || ($announcement->published_at && $announcement->published_at->isFuture())) {
            return 0;
        }

        $count = $this->notify(
            $this->eligibleUsers(),
            StudentNotification::TYPE_SYSTEM_ACTIVITY,
            $announcement->tieu_de,
            $announcement->noi_dung,
            "announcement:{$announcement->id}",
            ThongBao::class,
            $announcement->id,
            route('sinh-vien.notifications.index', absolute: false),
            $announcement->het_han_at,
        );

        $announcement->forceFill(['distributed_at' => now()])->saveQuietly();
        app(AuditLogger::class)->write('notification.announcement_distributed', $announcement, ['recipients' => $count]);

        return $count;
    }

    private function eligibleUsers(?DotDanhGia $period = null, bool $incompleteOnly = false): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('sinhVien', function (Builder $query) use ($period, $incompleteOnly): void {
                $query->where('trang_thai', 'dang_hoc');

                if ($period && $incompleteOnly) {
                    $query->whereDoesntHave('phieuDanhGias', fn (Builder $forms) => $forms
                        ->where('dot_danh_gia_id', $period->id)
                        ->whereIn('trang_thai', [
                            PhieuDanhGia::STATUS_SUBMITTED,
                            PhieuDanhGia::STATUS_REVIEWED,
                            PhieuDanhGia::STATUS_APPROVED,
                            PhieuDanhGia::STATUS_LOCKED,
                        ]));
                }
            });
    }

    private function eligibleActivityUsers(HoatDong $activity): Builder
    {
        $facultyIds = $activity->khoas()->pluck('khoas.id');
        $users = $this->eligibleUsers();

        if ($facultyIds->isNotEmpty()) {
            $users->whereHas('sinhVien.lop', fn (Builder $query) => $query->whereIn('khoa_id', $facultyIds));
        }

        return $users;
    }

    private function notify(
        Builder $users,
        string $type,
        string $title,
        string $content,
        string $dedupeKey,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?string $actionUrl = null,
        $expiresAt = null,
    ): int {
        $created = 0;

        $users->select('users.id')->orderBy('users.id')->chunkById(250, function ($recipients) use (
            &$created, $type, $title, $content, $dedupeKey, $relatedType, $relatedId, $actionUrl, $expiresAt
        ): void {
            foreach ($recipients as $recipient) {
                $notification = StudentNotification::query()->firstOrCreate(
                    ['user_id' => $recipient->id, 'dedupe_key' => $dedupeKey],
                    [
                        'type' => $type,
                        'title' => $title,
                        'content' => $content,
                        'related_type' => $relatedType,
                        'related_id' => $relatedId,
                        'action_url' => $actionUrl,
                        'expires_at' => $expiresAt,
                        'is_read' => false,
                    ],
                );

                if (! $notification->wasRecentlyCreated) {
                    continue;
                }

                $created++;

                try {
                    StudentNotificationCreated::dispatch($notification);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        });

        if ($created > 0) {
            app(AuditLogger::class)->write('notification.distributed', $relatedType, [
                'related_id' => $relatedId,
                'type' => $type,
                'recipients' => $created,
            ]);
        }

        return $created;
    }
}
