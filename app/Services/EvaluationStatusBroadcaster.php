<?php

namespace App\Services;

use App\Events\EvaluationStatusChanged;
use App\Models\DotDanhGia;
use App\Models\PhieuDanhGia;
use App\Models\User;

class EvaluationStatusBroadcaster
{
    public function submitted(PhieuDanhGia $phieu): void
    {
        $phieu->loadMissing(['sinhVien.user', 'sinhVien.lop.gvcn', 'dotDanhGia']);

        $this->send($phieu->sinhVien->user_id, 'submitted', 'Đã nộp phiếu đánh giá', 'Phiếu tự đánh giá của bạn đã được ghi nhận.', route('sinh-vien.evaluations.index'), $phieu);
        $this->send($phieu->sinhVien->lop?->gvcn_id, 'submitted', 'Sinh viên đã nộp phiếu', "{$phieu->sinhVien->ho_ten} đã nộp phiếu đánh giá rèn luyện.", route('gvcn.evaluations.show', $phieu), $phieu);
    }

    public function reviewedByGvcn(PhieuDanhGia $phieu): void
    {
        $phieu->loadMissing(['sinhVien.user', 'sinhVien.lop.gvcn', 'dotDanhGia']);

        $this->send($phieu->sinhVien->user_id, 'reviewed', 'GVCN đã duyệt phiếu', 'Phiếu đánh giá rèn luyện của bạn đã được GVCN xác nhận.', route('sinh-vien.evaluations.index'), $phieu);

        foreach ($this->hoiDongUserIds() as $userId) {
            $this->send($userId, 'reviewed', 'Phiếu chờ Hội đồng chốt', "{$phieu->sinhVien->ho_ten} đã được GVCN duyệt.", route('hoi-dong.evaluations.show', $phieu), $phieu);
        }
    }

    public function approved(PhieuDanhGia $phieu): void
    {
        $phieu->loadMissing(['sinhVien.user', 'sinhVien.lop.gvcn', 'dotDanhGia']);

        $this->send($phieu->sinhVien->user_id, 'approved', 'Đã chốt điểm rèn luyện', 'Phiếu đánh giá rèn luyện của bạn đã được Hội đồng xác nhận.', route('sinh-vien.evaluations.index'), $phieu);
        $this->send($phieu->sinhVien->lop?->gvcn_id, 'approved', 'Hội đồng đã chốt phiếu', "{$phieu->sinhVien->ho_ten} đã được chốt điểm rèn luyện.", route('gvcn.evaluations.show', $phieu), $phieu);
    }

    public function locked(PhieuDanhGia $phieu, string $message = 'Phiếu đánh giá rèn luyện đã bị khóa.'): void
    {
        $phieu->loadMissing(['sinhVien.user', 'sinhVien.lop.gvcn', 'dotDanhGia']);

        $this->send($phieu->sinhVien->user_id, 'locked', 'Phiếu đã khóa', $message, route('sinh-vien.evaluations.index'), $phieu);
        $this->send($phieu->sinhVien->lop?->gvcn_id, 'locked', 'Phiếu đã khóa', "{$phieu->sinhVien->ho_ten}: {$message}", route('gvcn.evaluations.show', $phieu), $phieu);

        foreach ($this->hoiDongUserIds() as $userId) {
            $this->send($userId, 'locked', 'Phiếu đã khóa', "{$phieu->sinhVien->ho_ten}: {$message}", route('hoi-dong.evaluations.show', $phieu), $phieu);
        }
    }

    public function periodPublished(DotDanhGia $dotDanhGia): void
    {
        $dotDanhGia->phieuDanhGias()
            ->with(['sinhVien.user', 'sinhVien.lop.gvcn', 'dotDanhGia'])
            ->chunkById(100, function ($forms) use ($dotDanhGia): void {
                foreach ($forms as $phieu) {
                    $message = "Kết quả {$dotDanhGia->ten_dot} đã được công bố.";
                    $this->send($phieu->sinhVien->user_id, 'published', 'Đã công bố kết quả', $message, route('sinh-vien.evaluations.history'), $phieu);
                    $this->send($phieu->sinhVien->lop?->gvcn_id, 'published', 'Đã công bố kết quả', "{$phieu->sinhVien->ho_ten}: {$message}", route('gvcn.evaluations.show', $phieu), $phieu);
                }
            });
    }

    private function send(?int $userId, string $type, string $title, string $message, string $url, PhieuDanhGia $phieu): void
    {
        if (! $userId) {
            return;
        }

        event(new EvaluationStatusChanged(
            userId: $userId,
            type: $type,
            title: $title,
            message: $message,
            url: $url,
            phieuId: $phieu->id,
            dotDanhGiaId: $phieu->dot_danh_gia_id,
            status: $phieu->trang_thai,
            timestamp: now()->toIso8601String(),
        ));
    }

    private function hoiDongUserIds()
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'hoi_dong_khoa'))
            ->pluck('id');
    }
}
