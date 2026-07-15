<?php

namespace App\Services;

use App\Events\ActivityOpenedEvent;
use App\Models\HoatDong;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityLifecycleService
{
    public function syncAll(): array
    {
        $result = ['checked' => 0, 'changed' => 0];

        HoatDong::query()
            ->whereIn('trang_thai', [HoatDong::STATUS_SCHEDULED, HoatDong::STATUS_OPEN, HoatDong::STATUS_REGISTRATION_CLOSED])
            ->orderBy('id')
            ->chunkById(100, function ($activities) use (&$result): void {
                foreach ($activities as $activity) {
                    $result['checked']++;
                    $result['changed'] += $this->sync($activity, 'scheduler') ? 1 : 0;
                }
            });

        return $result;
    }

    public function sync(HoatDong $activity, string $source = 'request'): bool
    {
        return DB::transaction(function () use ($activity, $source): bool {
            $locked = HoatDong::query()->lockForUpdate()->findOrFail($activity->id);

            return $this->syncLocked($locked, $source);
        });
    }

    public function syncLocked(HoatDong $activity, string $source): bool
    {
        if (in_array($activity->trang_thai, [HoatDong::STATUS_DRAFT, HoatDong::STATUS_CANCELLED], true)) {
            return false;
        }

        $from = $activity->trang_thai;
        $to = $activity->effectiveStatus();
        if ($from === $to) {
            return false;
        }

        $activity->update(['trang_thai' => $to]);
        app(AuditLogger::class)->write('activity.status_changed', $activity, [
            'from' => $from,
            'to' => $to,
            'source' => $source,
        ]);

        if ($to === HoatDong::STATUS_OPEN) {
            ActivityOpenedEvent::dispatch($activity);
        }

        return true;
    }

    public function cancel(HoatDong $activity, User $actor): void
    {
        DB::transaction(function () use ($activity, $actor): void {
            $locked = HoatDong::query()->lockForUpdate()->findOrFail($activity->id);
            if (in_array($locked->trang_thai, [HoatDong::STATUS_COMPLETED, HoatDong::STATUS_CANCELLED], true)) {
                throw ValidationException::withMessages(['hoat_dong' => 'Không thể hủy hoạt động đã kết thúc hoặc đã hủy.']);
            }

            $from = $locked->trang_thai;
            $locked->update(['trang_thai' => HoatDong::STATUS_CANCELLED]);
            app(AuditLogger::class)->write('activity.status_changed', $locked, [
                'from' => $from,
                'to' => HoatDong::STATUS_CANCELLED,
                'source' => 'manual_cancel',
            ], actorId: $actor->id);
        });
    }
}
