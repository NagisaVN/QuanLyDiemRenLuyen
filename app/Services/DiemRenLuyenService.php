<?php

namespace App\Services;

use App\Models\ChiTietDanhGia;
use App\Models\DiemRenLuyen;
use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\LichSuChinhSuaDiem;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\TieuChi;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DiemRenLuyenService
{
    public function activeHocKy(): ?HocKy
    {
        return HocKy::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    public function xepLoai(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Xuất sắc',
            $score >= 80 => 'Tốt',
            $score >= 65 => 'Khá',
            $score >= 50 => 'Trung bình',
            $score >= 35 => 'Yếu',
            default => 'Kém',
        };
    }

    public function ensurePhieu(SinhVien $sinhVien, ?HocKy $hocKy = null): PhieuDanhGia
    {
        $hocKy ??= $this->activeHocKy();

        if (! $hocKy) {
            throw ValidationException::withMessages(['hoc_ky' => 'Chưa có học kỳ đang mở.']);
        }

        $dotService = app(DotDanhGiaService::class);
        $openDot = $dotService->openForStudent($hocKy);
        $currentDot = $openDot ?? $dotService->current($hocKy);
        $existingPhieu = PhieuDanhGia::query()
            ->where('sinh_vien_id', $sinhVien->id)
            ->where('hoc_ky_id', $hocKy->id)
            ->first();

        if (! $currentDot) {
            throw ValidationException::withMessages(['dot_danh_gia' => 'Chưa có đợt đánh giá cho học kỳ hiện tại.']);
        }

        if (! $openDot && ! $existingPhieu) {
            throw ValidationException::withMessages(['dot_danh_gia' => 'Đã hết thời hạn nộp phiếu đánh giá.']);
        }

        $phieu = PhieuDanhGia::firstOrCreate(
            ['sinh_vien_id' => $sinhVien->id, 'hoc_ky_id' => $hocKy->id],
            [
                'dot_danh_gia_id' => $currentDot->id,
                'trang_thai' => PhieuDanhGia::STATUS_DRAFT,
            ]
        );

        if (! $phieu->dot_danh_gia_id) {
            $phieu->update(['dot_danh_gia_id' => $currentDot->id]);
        }

        TieuChi::query()->where('is_active', true)->orderBy('thu_tu')->get()->each(function (TieuChi $tieuChi) use ($phieu) {
            ChiTietDanhGia::firstOrCreate([
                'phieu_danh_gia_id' => $phieu->id,
                'tieu_chi_id' => $tieuChi->id,
            ]);
        });

        return $phieu->load(['hocKy', 'dotDanhGia', 'sinhVien.lop.khoa', 'chiTietDanhGias.tieuChi', 'minhChungs']);
    }

    public function canStudentEdit(PhieuDanhGia $phieu): bool
    {
        if (in_array($phieu->trang_thai, [PhieuDanhGia::STATUS_REVIEWED, PhieuDanhGia::STATUS_APPROVED, PhieuDanhGia::STATUS_LOCKED], true)) {
            return false;
        }

        return app(DotDanhGiaService::class)->canStudentEdit($phieu->loadMissing('dotDanhGia'));
    }

    public function saveStudentScores(PhieuDanhGia $phieu, array $scores, ?User $user, ?string $note = null): PhieuDanhGia
    {
        if (! $this->canStudentEdit($phieu)) {
            throw ValidationException::withMessages(['phieu' => 'Phiếu đã được duyệt, quá hạn hoặc đã khóa.']);
        }

        $total = 0;
        foreach (TieuChi::query()->where('is_active', true)->get() as $tieuChi) {
            $raw = (int) ($scores[$tieuChi->id] ?? 0);
            $score = max(0, min($raw, (int) $tieuChi->diem_toi_da));
            $detail = ChiTietDanhGia::firstOrCreate([
                'phieu_danh_gia_id' => $phieu->id,
                'tieu_chi_id' => $tieuChi->id,
            ]);

            if ((int) $detail->diem_tu_cham !== $score) {
                $this->history($phieu, $detail, $user, 'sinh_vien_tu_cham', $detail->diem_tu_cham, $score, "Tự chấm {$tieuChi->ten_tieu_chi}", $note);
            }

            $detail->update([
                'diem_tu_cham' => $score,
                'ghi_chu' => $note,
            ]);
            $total += $score;
        }

        $phieu->update([
            'diem_tu_cham' => min(100, $total),
            'xep_loai' => $this->xepLoai(min(100, $total)),
            'nhan_xet_sinh_vien' => $note,
        ]);

        return $phieu->refresh();
    }

    public function submit(PhieuDanhGia $phieu): PhieuDanhGia
    {
        if (! $this->canStudentEdit($phieu)) {
            throw ValidationException::withMessages(['phieu' => 'Phiếu không còn được nộp hoặc chỉnh sửa.']);
        }

        $phieu->update([
            'trang_thai' => PhieuDanhGia::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return $phieu->refresh();
    }

    public function saveReviewerScores(PhieuDanhGia $phieu, array $scores, User $user, string $stage, ?string $note = null): PhieuDanhGia
    {
        $phieu->loadMissing('dotDanhGia');

        if ($phieu->trang_thai === PhieuDanhGia::STATUS_LOCKED) {
            throw ValidationException::withMessages(['phieu' => 'Phiếu đã khóa.']);
        }

        if ($phieu->dotDanhGia?->trang_thai === DotDanhGia::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['phieu' => 'Đợt đánh giá đã công bố, không thể chỉnh sửa.']);
        }

        if ($stage === 'gvcn' && ! app(DotDanhGiaService::class)->openForGvcn($phieu->dotDanhGia)) {
            throw ValidationException::withMessages(['phieu' => 'Đã hết thời hạn duyệt phiếu đánh giá.']);
        }

        $field = $stage === 'hoi_dong' ? 'diem_hoi_dong' : 'diem_gvcn';
        $total = 0;

        foreach ($phieu->chiTietDanhGias()->with('tieuChi')->get() as $detail) {
            $max = (int) $detail->tieuChi->diem_toi_da;
            $score = max(0, min((int) ($scores[$detail->tieu_chi_id] ?? $detail->{$field} ?? $detail->diem_tu_cham), $max));

            if ((int) $detail->{$field} !== $score) {
                $this->history($phieu, $detail, $user, $stage, $detail->{$field}, $score, "Chỉnh điểm {$detail->tieuChi->ten_tieu_chi}", $note);
            }

            $detail->update([$field => $score]);
            $total += $score;
        }

        $phieu->update([
            $field => min(100, $total),
            'xep_loai' => $this->xepLoai(min(100, $total)),
        ]);

        return $phieu->refresh();
    }

    public function confirmGvcn(PhieuDanhGia $phieu, User $user, ?string $note = null): PhieuDanhGia
    {
        $phieu->loadMissing('dotDanhGia');

        if (! app(DotDanhGiaService::class)->openForGvcn($phieu->dotDanhGia)) {
            throw ValidationException::withMessages(['phieu' => 'Đã hết thời hạn duyệt phiếu đánh giá.']);
        }

        // Nếu GVCN bấm xác nhận khi chưa lưu riêng điểm chi tiết,
        // dùng điểm tự chấm làm điểm GVCN để cột GVCN không bị trống.
        $phieu->chiTietDanhGias()
            ->whereNull('diem_gvcn')
            ->each(function (ChiTietDanhGia $detail) use ($phieu, $user, $note): void {
                $this->history($phieu, $detail, $user, 'gvcn', $detail->diem_gvcn, $detail->diem_tu_cham, 'Xác nhận điểm GVCN mặc định', $note);
                $detail->update(['diem_gvcn' => $detail->diem_tu_cham]);
            });

        $score = (int) ($phieu->diem_gvcn ?? $phieu->diem_tu_cham);

        $phieu->update([
            'trang_thai' => PhieuDanhGia::STATUS_REVIEWED,
            'diem_gvcn' => $score,
            'nhan_xet_gvcn' => $note,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'xep_loai' => $this->xepLoai($score),
        ]);

        return $phieu->refresh();
    }

    public function approveFinal(PhieuDanhGia $phieu, User $user, ?string $note = null): PhieuDanhGia
    {
        $phieu->loadMissing('dotDanhGia');

        if ($phieu->dotDanhGia?->trang_thai === DotDanhGia::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['phieu' => 'Đợt đánh giá đã công bố, không thể chỉnh sửa.']);
        }

        $detailCount = 0;
        $detailTotal = 0;
        $phieu->chiTietDanhGias()
            ->with('tieuChi')
            ->get()
            ->each(function (ChiTietDanhGia $detail) use ($phieu, $user, $note, &$detailCount, &$detailTotal): void {
                $detailCount++;
                $score = (int) ($detail->diem_hoi_dong ?? $detail->diem_gvcn ?? $detail->diem_tu_cham);
                $score = max(0, min($score, (int) $detail->tieuChi->diem_toi_da));

                if ($detail->diem_hoi_dong === null) {
                    $this->history($phieu, $detail, $user, 'hoi_dong', $detail->diem_hoi_dong, $score, 'Xác nhận điểm Công Tác Sinh Viên mặc định', $note);
                    $detail->update(['diem_hoi_dong' => $score]);
                }

                $detailTotal += $score;
            });

        $rubricScore = (int) ($phieu->diem_hoi_dong ?? ($detailCount > 0 ? min(100, $detailTotal) : ($phieu->diem_gvcn ?? $phieu->diem_tu_cham)));
        $activityScore = $this->activityScore($phieu->sinhVien, $phieu->hocKy);
        $finalScore = max(0, min(100, $rubricScore + $activityScore));

        $phieu->update([
            'trang_thai' => PhieuDanhGia::STATUS_APPROVED,
            'diem_hoi_dong' => $rubricScore,
            'diem_cuoi' => $finalScore,
            'nhan_xet_hoi_dong' => $note,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'xep_loai' => $this->xepLoai($finalScore),
        ]);

        DiemRenLuyen::updateOrCreate(
            ['sinh_vien_id' => $phieu->sinh_vien_id, 'hoc_ky_id' => $phieu->hoc_ky_id],
            [
                'phieu_danh_gia_id' => $phieu->id,
                'tong_diem' => $finalScore,
                'diem_hoat_dong' => $activityScore,
                'xep_loai' => $this->xepLoai($finalScore),
                'trang_thai' => 'final',
                'cong_bo_at' => $phieu->dotDanhGia?->ngay_cong_bo ?? $phieu->hocKy?->ngay_cong_bo,
            ]
        );

        return $phieu->refresh();
    }

    public function lock(PhieuDanhGia $phieu, User $user): PhieuDanhGia
    {
        $phieu->update([
            'trang_thai' => PhieuDanhGia::STATUS_LOCKED,
            'locked_by' => $user->id,
            'locked_at' => now(),
        ]);

        $phieu->diemRenLuyen?->update(['trang_thai' => 'locked']);

        return $phieu->refresh();
    }

    public function activityScore(SinhVien $sinhVien, HocKy $hocKy): int
    {
        $auto = $sinhVien->diemRenLuyens()->count();

        $checkedInPoints = $sinhVien->loadMissing('lop')
            ->newQuery()
            ->whereKey($sinhVien->id)
            ->join('diem_danh_hoat_dongs', 'sinh_viens.id', '=', 'diem_danh_hoat_dongs.sinh_vien_id')
            ->join('hoat_dongs', 'hoat_dongs.id', '=', 'diem_danh_hoat_dongs.hoat_dong_id')
            ->whereBetween('diem_danh_hoat_dongs.checked_in_at', [$hocKy->ngay_bat_dau ?? now()->subYear(), $hocKy->ngay_ket_thuc ?? now()->addYear()])
            ->sum('hoat_dongs.diem_cong');

        $manual = LichSuChinhSuaDiem::query()
            ->where('sinh_vien_id', $sinhVien->id)
            ->where('hoc_ky_id', $hocKy->id)
            ->where('nguon', 'hoat_dong_thu_cong')
            ->sum('diem_moi');

        return (int) max(-100, min(100, $checkedInPoints + $manual + ($auto * 0)));
    }

    public function history(?PhieuDanhGia $phieu, ?ChiTietDanhGia $detail, ?User $user, string $source, mixed $old, mixed $new, ?string $content = null, ?string $reason = null, array $metadata = []): void
    {
        LichSuChinhSuaDiem::create([
            'phieu_danh_gia_id' => $phieu?->id,
            'chi_tiet_danh_gia_id' => $detail?->id,
            'sinh_vien_id' => $phieu?->sinh_vien_id,
            'hoc_ky_id' => $phieu?->hoc_ky_id,
            'user_id' => $user?->id,
            'nguon' => $source,
            'diem_cu' => $old,
            'diem_moi' => $new,
            'noi_dung' => $content,
            'ly_do' => $reason,
            'metadata' => $metadata ?: null,
        ]);
    }
}
