<?php

namespace App\Services;

use App\Events\EvaluationClosedEvent;
use App\Events\EvaluationOpenedEvent;
use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\PhieuDanhGia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DotDanhGiaService
{
    public function current(?HocKy $hocKy = null): ?DotDanhGia
    {
        return $this->getCurrentStudentPeriod($hocKy);
    }

    public function getCurrentStudentPeriod(?HocKy $hocKy = null): ?DotDanhGia
    {
        $query = DotDanhGia::query()
            ->with(['namHoc', 'hocKy'])
            ->where('trang_thai', '!=', DotDanhGia::STATUS_PUBLISHED)
            ->where('ngay_bat_dau_sinh_vien', '<=', now())
            ->where('ngay_ket_thuc_sinh_vien', '>', now())
            ->orderByDesc('ngay_bat_dau_sinh_vien')
            ->orderByDesc('id');

        if ($hocKy) {
            $query->where('hoc_ky_id', $hocKy->id);
        }

        return $query->first();
    }

    public function getCurrentTeacherPeriod(?HocKy $hocKy = null): ?DotDanhGia
    {
        $query = DotDanhGia::query()
            ->with(['namHoc', 'hocKy'])
            ->where('trang_thai', '!=', DotDanhGia::STATUS_PUBLISHED)
            ->where('ngay_bat_dau_gvcn', '<=', now())
            ->where('ngay_ket_thuc_gvcn', '>', now())
            ->orderByDesc('ngay_bat_dau_gvcn')
            ->orderByDesc('id');

        if ($hocKy) {
            $query->where('hoc_ky_id', $hocKy->id);
        }

        return $query->first();
    }

    public function getLatestPublishedPeriod(?HocKy $hocKy = null): ?DotDanhGia
    {
        $query = DotDanhGia::query()
            ->with(['namHoc', 'hocKy'])
            ->where('trang_thai', DotDanhGia::STATUS_PUBLISHED)
            ->orderByDesc('ngay_cong_bo')
            ->orderByDesc('id');

        if ($hocKy) {
            $query->where('hoc_ky_id', $hocKy->id);
        }

        return $query->first();
    }

    public function getPeriodForViewingResult(?DotDanhGia $dotDanhGia = null): ?DotDanhGia
    {
        if ($dotDanhGia) {
            return $dotDanhGia->trang_thai === DotDanhGia::STATUS_PUBLISHED
                ? $dotDanhGia->loadMissing(['namHoc', 'hocKy'])
                : null;
        }

        return $this->getLatestPublishedPeriod();
    }

    public function openForStudent(?HocKy $hocKy = null): ?DotDanhGia
    {
        return $this->getCurrentStudentPeriod($hocKy);
    }

    public function getNextStudentPeriod(?HocKy $hocKy = null): ?DotDanhGia
    {
        $query = DotDanhGia::query()
            ->with(['namHoc', 'hocKy'])
            ->where('trang_thai', '!=', DotDanhGia::STATUS_PUBLISHED)
            ->where('ngay_bat_dau_sinh_vien', '>', now())
            ->orderBy('ngay_bat_dau_sinh_vien')
            ->orderBy('id');

        if ($hocKy) {
            $query->where('hoc_ky_id', $hocKy->id);
        }

        return $query->first();
    }

    public function openForGvcn(?DotDanhGia $dotDanhGia = null): bool
    {
        $dotDanhGia ??= $this->getCurrentTeacherPeriod();

        return (bool) $dotDanhGia?->isGvcnOpen();
    }

    public function canStudentEdit(PhieuDanhGia $phieu): bool
    {
        $dot = $phieu->loadMissing('dotDanhGia')->dotDanhGia;

        if (! $dot?->isStudentOpen()) {
            return false;
        }

        $current = $this->getCurrentStudentPeriod($dot->hocKy);

        if (! $current?->is($dot)) {
            return false;
        }

        return $phieu->canStudentEditStatus()
            && ! $phieu->reviewed_at
            && ! $phieu->locked_at;
    }

    public function lockExpiredForms(?User $actor = null): int
    {
        return $this->syncAll($actor)['locked'];
    }

    /** @return array{periods: int, locked: int, published: int} */
    public function syncAll(?User $actor = null): array
    {
        $result = ['periods' => 0, 'locked' => 0, 'published' => 0];

        DotDanhGia::query()->orderBy('id')->chunkById(50, function ($periods) use (&$result, $actor): void {
            foreach ($periods as $period) {
                $synced = $this->syncPeriod($period, $actor);
                $result['periods']++;
                $result['locked'] += $synced['locked'];
                $result['published'] += $synced['published'] ? 1 : 0;
            }
        });

        return $result;
    }

    /** @return array{locked: int, published: bool} */
    public function syncPeriod(DotDanhGia $dotDanhGia, ?User $actor = null): array
    {
        $lockedIds = [];
        $published = false;
        $opened = false;
        $closed = false;

        DB::transaction(function () use ($dotDanhGia, $actor, &$lockedIds, &$published, &$opened, &$closed): void {
            $period = DotDanhGia::query()->lockForUpdate()->findOrFail($dotDanhGia->id);
            $desiredStatus = $period->effectiveStatus();
            $now = now();

            if ($now->greaterThanOrEqualTo($period->ngay_ket_thuc_gvcn)) {
                $forms = $period->phieuDanhGias()
                    ->whereNotIn('trang_thai', [PhieuDanhGia::STATUS_APPROVED, PhieuDanhGia::STATUS_LOCKED])
                    ->lockForUpdate()
                    ->get();

                foreach ($forms as $form) {
                    $form->update([
                        'trang_thai' => PhieuDanhGia::STATUS_LOCKED,
                        'locked_at' => $now,
                        'locked_by' => $actor?->id,
                    ]);
                    $lockedIds[] = $form->id;
                }
            }

            if ($period->trang_thai !== $desiredStatus) {
                $published = $desiredStatus === DotDanhGia::STATUS_PUBLISHED;
                $opened = $desiredStatus === DotDanhGia::STATUS_OPEN;
                $closed = $desiredStatus === DotDanhGia::STATUS_CLOSED;
                $period->update([
                    'trang_thai' => $desiredStatus,
                    'updated_by' => $actor?->id ?? $period->updated_by,
                ]);
            }
        });

        foreach (PhieuDanhGia::query()
            ->with(['sinhVien.user', 'sinhVien.lop.gvcn', 'dotDanhGia'])
            ->whereIn('id', $lockedIds)->get() as $form) {
            app(EvaluationStatusBroadcaster::class)->locked($form, 'Phiếu đã bị khóa do quá hạn duyệt GVCN.');
        }

        if ($published) {
            app(EvaluationStatusBroadcaster::class)->periodPublished($dotDanhGia->refresh());
        }

        if ($opened) {
            EvaluationOpenedEvent::dispatch($dotDanhGia->refresh());
        }

        if ($closed) {
            EvaluationClosedEvent::dispatch($dotDanhGia->refresh());
        }

        $dotDanhGia->refresh();

        return ['locked' => count($lockedIds), 'published' => $published];
    }

    public function reopen(DotDanhGia $dotDanhGia, User $user): void
    {
        if (! in_array($dotDanhGia->trang_thai, [DotDanhGia::STATUS_DRAFT, DotDanhGia::STATUS_CLOSED], true)) {
            throw ValidationException::withMessages(['dot_danh_gia' => 'Chỉ có thể mở đợt nháp hoặc đã đóng.']);
        }

        $dotDanhGia->update([
            'trang_thai' => DotDanhGia::STATUS_OPEN,
            'updated_by' => $user->id,
        ]);

        // Mở lại chỉ phục hồi phiếu bị khóa tự động, không đụng các phiếu đã approved.
        $dotDanhGia->phieuDanhGias()
            ->where('trang_thai', PhieuDanhGia::STATUS_LOCKED)
            ->whereNull('approved_at')
            ->update([
                'trang_thai' => PhieuDanhGia::STATUS_SUBMITTED,
                'locked_at' => null,
                'locked_by' => null,
            ]);
        app(AuditLogger::class)->write('evaluation_period.opened', $dotDanhGia, actorId: $user->id);

        if ($dotDanhGia->refresh()->isStudentOpen()) {
            EvaluationOpenedEvent::dispatch($dotDanhGia);
        }
    }

    public function close(DotDanhGia $dotDanhGia, User $user): void
    {
        if ($dotDanhGia->trang_thai !== DotDanhGia::STATUS_OPEN) {
            throw ValidationException::withMessages(['dot_danh_gia' => 'Chỉ có thể đóng đợt đang mở.']);
        }

        $dotDanhGia->update([
            'trang_thai' => DotDanhGia::STATUS_CLOSED,
            'updated_by' => $user->id,
        ]);
        app(AuditLogger::class)->write('evaluation_period.closed', $dotDanhGia, actorId: $user->id);
        EvaluationClosedEvent::dispatch($dotDanhGia->refresh());
    }

    public function publish(DotDanhGia $dotDanhGia, User $user): void
    {
        if ($dotDanhGia->trang_thai !== DotDanhGia::STATUS_CLOSED) {
            throw ValidationException::withMessages(['dot_danh_gia' => 'Chỉ có thể công bố đợt đã đóng.']);
        }

        DB::transaction(function () use ($dotDanhGia, $user): void {
            $lockedAt = now();

            $dotDanhGia->update([
                'trang_thai' => DotDanhGia::STATUS_PUBLISHED,
                'ngay_cong_bo' => $dotDanhGia->ngay_cong_bo ?? $lockedAt,
                'updated_by' => $user->id,
            ]);

            $dotDanhGia->phieuDanhGias()
                ->whereNotIn('trang_thai', [PhieuDanhGia::STATUS_APPROVED, PhieuDanhGia::STATUS_LOCKED])
                ->update([
                    'trang_thai' => PhieuDanhGia::STATUS_LOCKED,
                    'locked_at' => $lockedAt,
                    'locked_by' => $user->id,
                ]);

            $dotDanhGia->phieuDanhGias()
                ->whereNull('locked_at')
                ->update([
                    'locked_at' => $lockedAt,
                    'locked_by' => $user->id,
                ]);
        });

        app(EvaluationStatusBroadcaster::class)->periodPublished($dotDanhGia->refresh());
        app(AuditLogger::class)->write('evaluation_period.published', $dotDanhGia, actorId: $user->id);
    }
}
