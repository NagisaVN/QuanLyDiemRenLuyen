<?php

namespace App\Console\Commands;

use App\Events\EvaluationClosingSoonEvent;
use App\Events\EvaluationOpenedEvent;
use App\Events\SystemAnnouncementPublishedEvent;
use App\Models\DotDanhGia;
use App\Models\ThongBao;
use Illuminate\Console\Command;
use Throwable;

class ReconcileStudentNotifications extends Command
{
    protected $signature = 'notifications:reconcile';

    protected $description = 'Đối soát thông báo đợt đánh giá và thông báo hệ thống cho sinh viên';

    public function handle(): int
    {
        try {
            DotDanhGia::query()
                ->where('ngay_bat_dau_sinh_vien', '<=', now())
                ->where('ngay_ket_thuc_sinh_vien', '>', now())
                ->where('trang_thai', '!=', DotDanhGia::STATUS_PUBLISHED)
                ->orderBy('id')
                ->chunkById(50, function ($periods): void {
                    foreach ($periods as $period) {
                        EvaluationOpenedEvent::dispatch($period);

                        if ($period->ngay_ket_thuc_sinh_vien->lessThanOrEqualTo(now()->addHours(72))) {
                            EvaluationClosingSoonEvent::dispatch($period, EvaluationClosingSoonEvent::THREE_DAYS);
                        }

                        if ($period->ngay_ket_thuc_sinh_vien->lessThanOrEqualTo(now()->addHours(24))) {
                            EvaluationClosingSoonEvent::dispatch($period, EvaluationClosingSoonEvent::TWENTY_FOUR_HOURS);
                        }
                    }
                });

            ThongBao::query()
                ->where('is_active', true)
                ->whereNull('distributed_at')
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->orderBy('id')
                ->chunkById(50, function ($announcements): void {
                    foreach ($announcements as $announcement) {
                        SystemAnnouncementPublishedEvent::dispatch($announcement);
                    }
                });
        } catch (Throwable $exception) {
            app(\App\Services\AuditLogger::class)->write('notification.reconcile_failed', 'notifications', ['error' => $exception->getMessage()]);
            report($exception);

            return self::FAILURE;
        }

        $this->info('Đã đối soát thông báo sinh viên.');

        return self::SUCCESS;
    }
}
