<?php

namespace App\Console\Commands;

use App\Events\ActivityOpeningSoonEvent;
use App\Models\HoatDong;
use App\Services\ActivityLifecycleService;
use Illuminate\Console\Command;

class AutoUpdateActivityStatuses extends Command
{
    protected $signature = 'activities:auto-status';
    protected $description = 'Tự động cập nhật trạng thái hoạt động theo lịch đăng ký và thời gian diễn ra';

    public function handle(ActivityLifecycleService $service): int
    {
        $result = $service->syncAll();

        HoatDong::query()
            ->where('trang_thai', HoatDong::STATUS_SCHEDULED)
            ->where('open_registration_at', '>', now())
            ->where('open_registration_at', '<=', now()->addHours(24))
            ->orderBy('id')
            ->chunkById(100, function ($activities): void {
                foreach ($activities as $activity) {
                    ActivityOpeningSoonEvent::dispatch($activity);
                }
            });

        $this->info("Đã kiểm tra {$result['checked']} hoạt động, cập nhật {$result['changed']} hoạt động.");

        return self::SUCCESS;
    }
}
