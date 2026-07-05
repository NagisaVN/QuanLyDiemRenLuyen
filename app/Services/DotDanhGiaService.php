<?php

namespace App\Services;

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
            ->where('trang_thai', DotDanhGia::STATUS_OPEN)
            ->where('ngay_bat_dau_sinh_vien', '<=', now())
            ->where('ngay_ket_thuc_sinh_vien', '>=', now())
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
            ->whereIn('trang_thai', [DotDanhGia::STATUS_OPEN, DotDanhGia::STATUS_CLOSED])
            ->where('ngay_bat_dau_gvcn', '<=', now())
            ->where('ngay_ket_thuc_gvcn', '>=', now())
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

    public function openForGvcn(?DotDanhGia $dotDanhGia = null): bool
    {
        $dotDanhGia ??= $this->getCurrentTeacherPeriod();

        return (bool) $dotDanhGia?->isGvcnOpen();
    }

    public function canStudentEdit(PhieuDanhGia $phieu): bool
    {
        $dot = $phieu->dotDanhGia;

        if (! $dot?->isStudentOpen()) {
            return false;
        }

        return $phieu->canStudentEditStatus()
            && ! $phieu->reviewed_at
            && ! $phieu->locked_at;
    }

    public function lockExpiredForms(?User $actor = null): int
    {
        $count = 0;

        DotDanhGia::query()
            ->whereIn('trang_thai', [DotDanhGia::STATUS_OPEN, DotDanhGia::STATUS_CLOSED, DotDanhGia::STATUS_PUBLISHED])
            ->where('ngay_ket_thuc_gvcn', '<', now())
            ->chunkById(50, function ($dots) use (&$count, $actor) {
                foreach ($dots as $dot) {
                    $dot->phieuDanhGias()
                        ->with(['sinhVien.user', 'sinhVien.lop.gvcn', 'dotDanhGia'])
                        ->whereNotIn('trang_thai', [PhieuDanhGia::STATUS_APPROVED, PhieuDanhGia::STATUS_LOCKED])
                        ->chunkById(100, function ($forms) use (&$count, $actor): void {
                            foreach ($forms as $phieu) {
                                $phieu->update([
                                    'trang_thai' => PhieuDanhGia::STATUS_LOCKED,
                                    'locked_at' => now(),
                                    'locked_by' => $actor?->id,
                                ]);

                                $count++;
                                app(EvaluationStatusBroadcaster::class)->locked($phieu->refresh(), 'Phiếu đã bị khóa do quá hạn duyệt GVCN.');
                            }
                        });
                }
            });

        return $count;
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
    }
}
