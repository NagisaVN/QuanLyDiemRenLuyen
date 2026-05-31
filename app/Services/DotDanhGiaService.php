<?php

namespace App\Services;

use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\PhieuDanhGia;
use App\Models\User;

class DotDanhGiaService
{
    public function current(?HocKy $hocKy = null): ?DotDanhGia
    {
        $query = DotDanhGia::query()
            ->with(['namHoc', 'hocKy'])
            ->orderByRaw("CASE trang_thai WHEN 'open' THEN 1 WHEN 'published' THEN 2 WHEN 'closed' THEN 3 ELSE 4 END")
            ->latest('id');

        if ($hocKy) {
            $query->where('hoc_ky_id', $hocKy->id);
        }

        return $query->first();
    }

    public function openForStudent(?HocKy $hocKy = null): ?DotDanhGia
    {
        return DotDanhGia::query()
            ->when($hocKy, fn ($query) => $query->where('hoc_ky_id', $hocKy->id))
            ->where('trang_thai', DotDanhGia::STATUS_OPEN)
            ->where('ngay_bat_dau_sinh_vien', '<=', now())
            ->where('ngay_ket_thuc_sinh_vien', '>=', now())
            ->latest('id')
            ->first();
    }

    public function openForGvcn(?DotDanhGia $dotDanhGia = null): bool
    {
        $dotDanhGia ??= $this->current();

        return (bool) $dotDanhGia?->isGvcnOpen();
    }

    public function canStudentEdit(PhieuDanhGia $phieu): bool
    {
        $dot = $phieu->dotDanhGia;

        if (! $dot?->isStudentOpen()) {
            return false;
        }

        return in_array($phieu->trang_thai, [PhieuDanhGia::STATUS_DRAFT, PhieuDanhGia::STATUS_SUBMITTED], true)
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
                    // Sau hạn GVCN, các phiếu chưa chốt không còn được chỉnh ở bất kỳ cấp nào.
                    $count += $dot->phieuDanhGias()
                        ->whereNotIn('trang_thai', [PhieuDanhGia::STATUS_APPROVED, PhieuDanhGia::STATUS_LOCKED])
                        ->update([
                            'trang_thai' => PhieuDanhGia::STATUS_LOCKED,
                            'locked_at' => now(),
                            'locked_by' => $actor?->id,
                        ]);
                }
            });

        return $count;
    }

    public function reopen(DotDanhGia $dotDanhGia, User $user): void
    {
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
        $dotDanhGia->update([
            'trang_thai' => DotDanhGia::STATUS_CLOSED,
            'updated_by' => $user->id,
        ]);
    }

    public function publish(DotDanhGia $dotDanhGia, User $user): void
    {
        $dotDanhGia->update([
            'trang_thai' => DotDanhGia::STATUS_PUBLISHED,
            'ngay_cong_bo' => $dotDanhGia->ngay_cong_bo ?? now(),
            'updated_by' => $user->id,
        ]);

        $dotDanhGia->phieuDanhGias()
            ->whereNotIn('trang_thai', [PhieuDanhGia::STATUS_APPROVED, PhieuDanhGia::STATUS_LOCKED])
            ->update([
                'trang_thai' => PhieuDanhGia::STATUS_LOCKED,
                'locked_at' => now(),
                'locked_by' => $user->id,
            ]);
    }
}
