<?php

namespace App\Services;

use App\Models\ChiTietDanhGia;
use App\Models\ConductPointLog;
use App\Models\DiemRenLuyen;
use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\LichSuChinhSuaDiem;
use App\Models\MucTieuChi;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\TieuChi;
use App\Models\User;
use App\Support\DrlRubric;
use Illuminate\Support\Collection;
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
        DrlRubric::syncIfMissing();

        $dotService = app(DotDanhGiaService::class);
        $currentDot = $hocKy
            ? $dotService->getCurrentStudentPeriod($hocKy)
            : $dotService->getCurrentStudentPeriod();

        if (! $currentDot) {
            throw ValidationException::withMessages(['dot_danh_gia' => 'Hiện chưa có đợt đánh giá đang mở.']);
        }

        $hocKy = $currentDot->hocKy;

        if (! $hocKy) {
            throw ValidationException::withMessages(['hoc_ky' => 'Đợt đánh giá chưa gắn học kỳ hợp lệ.']);
        }

        $phieu = PhieuDanhGia::query()
            ->where('sinh_vien_id', $sinhVien->id)
            ->where('dot_danh_gia_id', $currentDot->id)
            ->first();

        if (! $phieu) {
            $phieu = PhieuDanhGia::query()
                ->where('sinh_vien_id', $sinhVien->id)
                ->where('hoc_ky_id', $hocKy->id)
                ->whereNull('dot_danh_gia_id')
                ->first();

            if ($phieu) {
                $phieu->update(['dot_danh_gia_id' => $currentDot->id]);
            }
        }

        if (! $phieu) {
            $phieu = PhieuDanhGia::create([
                'sinh_vien_id' => $sinhVien->id,
                'hoc_ky_id' => $hocKy->id,
                'dot_danh_gia_id' => $currentDot->id,
                'trang_thai' => PhieuDanhGia::STATUS_DRAFT,
            ]);
        }

        if (! $phieu->dot_danh_gia_id) {
            $phieu->update(['dot_danh_gia_id' => $currentDot->id]);
        }

        $this->ensureRubricDetails($phieu);

        return $this->loadEvaluation($phieu);
    }

    public function canStudentEdit(PhieuDanhGia $phieu): bool
    {
        if (in_array($phieu->trang_thai, [PhieuDanhGia::STATUS_REVIEWED, PhieuDanhGia::STATUS_APPROVED, PhieuDanhGia::STATUS_LOCKED], true)) {
            return false;
        }

        return app(DotDanhGiaService::class)->canStudentEdit($phieu->loadMissing('dotDanhGia'));
    }

    public function rubricForPhieu(PhieuDanhGia $phieu): Collection
    {
        $this->ensureRubricDetails($phieu);
        $phieu = $this->loadEvaluation($phieu);

        $details = $phieu->chiTietDanhGias
            ->filter(fn (ChiTietDanhGia $detail): bool => $detail->muc_tieu_chi_id !== null)
            ->keyBy('muc_tieu_chi_id');

        $evidence = $phieu->minhChungs->groupBy('muc_tieu_chi_id');

        return TieuChi::query()
            ->with(['mucTieuChis' => fn ($query) => $query
                ->whereNotNull('ma_muc')
                ->where('is_active', true)
                ->orderBy('thu_tu')])
            ->where('is_active', true)
            ->whereHas('mucTieuChis', fn ($query) => $query->whereNotNull('ma_muc')->where('is_active', true))
            ->orderBy('thu_tu')
            ->get()
            ->map(function (TieuChi $criterion) use ($details, $evidence): array {
                $rows = $criterion->mucTieuChis->map(fn (MucTieuChi $item): array => [
                    'item' => $item,
                    'detail' => $details->get($item->id),
                    'evidence' => $evidence->get($item->id, collect()),
                ]);

                return [
                    'criterion' => $criterion,
                    'rows' => $rows,
                    'totals' => [
                        'student' => $this->criterionTotalFromRows($criterion, $rows, ['diem_tu_cham']),
                        'gvcn' => $this->criterionTotalFromRows($criterion, $rows, ['diem_gvcn', 'diem_tu_cham']),
                        'hoi_dong' => $this->criterionTotalFromRows($criterion, $rows, ['diem_hoi_dong', 'diem_gvcn', 'diem_tu_cham']),
                    ],
                ];
            });
    }

    public function saveStudentScores(PhieuDanhGia $phieu, array $scores, ?User $user, ?string $note = null, array $itemNotes = []): PhieuDanhGia
    {
        if (! $this->canStudentEdit($phieu)) {
            throw ValidationException::withMessages(['phieu' => 'Phiếu đã được duyệt, quá hạn hoặc đã khóa.']);
        }

        $this->ensureRubricDetails($phieu);
        $criterionScores = [];

        foreach ($this->activeRubricItems() as $item) {
            $detail = $this->detailForItem($phieu, $item);
            $score = $this->normalizeScore($item, $scores[$item->id] ?? 0);
            $criterionScores[$item->tieu_chi_id] = ($criterionScores[$item->tieu_chi_id] ?? 0) + $score;

            if ((int) $detail->diem_tu_cham !== $score) {
                $this->history($phieu, $detail, $user, 'sinh_vien_tu_cham', $detail->diem_tu_cham, $score, "Tự chấm {$item->ten_muc}", $note);
            }

            $detail->update([
                'diem_tu_cham' => $score,
                'ghi_chu' => $note,
                'ghi_chu_sinh_vien' => array_key_exists($item->id, $itemNotes) ? $itemNotes[$item->id] : $detail->ghi_chu_sinh_vien,
            ]);
        }

        $total = $this->totalFromCriterionScores($criterionScores);

        $phieu->update([
            'diem_tu_cham' => $total,
            'xep_loai' => $this->xepLoai($total),
            'nhan_xet_sinh_vien' => $note,
        ]);

        return $this->loadEvaluation($phieu->refresh());
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

        $phieu = $this->loadEvaluation($phieu->refresh());
        app(EvaluationStatusBroadcaster::class)->submitted($phieu);

        return $phieu;
    }

    public function saveReviewerScores(PhieuDanhGia $phieu, array $scores, User $user, string $stage, ?string $note = null, array $itemNotes = []): PhieuDanhGia
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

        $this->ensureRubricDetails($phieu);

        $field = $stage === 'hoi_dong' ? 'diem_hoi_dong' : 'diem_gvcn';
        $noteField = $stage === 'hoi_dong' ? 'ghi_chu_hoi_dong' : 'ghi_chu_gvcn';
        $fallbackFields = $stage === 'hoi_dong' ? ['diem_gvcn', 'diem_tu_cham'] : ['diem_tu_cham'];
        $criterionScores = [];

        foreach ($this->activeRubricItems() as $item) {
            $detail = $this->detailForItem($phieu, $item);
            $raw = $scores[$item->id] ?? $detail->{$field} ?? $this->firstScore($detail, $fallbackFields);
            $score = $this->normalizeScore($item, $raw);
            $criterionScores[$item->tieu_chi_id] = ($criterionScores[$item->tieu_chi_id] ?? 0) + $score;

            if ((int) $detail->{$field} !== $score) {
                $this->history($phieu, $detail, $user, $stage, $detail->{$field}, $score, "Chỉnh điểm {$item->ten_muc}", $note);
            }

            $detail->update([
                $field => $score,
                $noteField => array_key_exists($item->id, $itemNotes) ? $itemNotes[$item->id] : $detail->{$noteField},
            ]);
        }

        $total = $this->totalFromCriterionScores($criterionScores);
        $updates = [
            $field => $total,
            'xep_loai' => $this->xepLoai($total),
        ];

        if ($stage === 'hoi_dong') {
            $updates['nhan_xet_hoi_dong'] = $note;
        } else {
            $updates['nhan_xet_gvcn'] = $note;
        }

        $phieu->update($updates);

        return $this->loadEvaluation($phieu->refresh());
    }

    public function confirmGvcn(PhieuDanhGia $phieu, User $user, ?string $note = null): PhieuDanhGia
    {
        $phieu->loadMissing('dotDanhGia');

        if (! app(DotDanhGiaService::class)->openForGvcn($phieu->dotDanhGia)) {
            throw ValidationException::withMessages(['phieu' => 'Đã hết thời hạn duyệt phiếu đánh giá.']);
        }

        $this->ensureRubricDetails($phieu);

        foreach ($this->activeRubricItems() as $item) {
            $detail = $this->detailForItem($phieu, $item);

            if ($detail->diem_gvcn === null) {
                $score = $this->normalizeScore($item, $detail->diem_tu_cham);
                $this->history($phieu, $detail, $user, 'gvcn', $detail->diem_gvcn, $score, 'Xác nhận điểm GVCN mặc định', $note);
                $detail->update(['diem_gvcn' => $score]);
            }
        }

        $score = $this->totalForStage($phieu->refresh(), ['diem_gvcn', 'diem_tu_cham']);

        $phieu->update([
            'trang_thai' => PhieuDanhGia::STATUS_REVIEWED,
            'diem_gvcn' => $score,
            'nhan_xet_gvcn' => $note,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'xep_loai' => $this->xepLoai($score),
        ]);

        $phieu = $this->loadEvaluation($phieu->refresh());
        app(EvaluationStatusBroadcaster::class)->reviewedByGvcn($phieu);

        return $phieu;
    }

    public function approveFinal(PhieuDanhGia $phieu, User $user, ?string $note = null): PhieuDanhGia
    {
        $phieu->loadMissing('dotDanhGia');

        if ($phieu->dotDanhGia?->trang_thai === DotDanhGia::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['phieu' => 'Đợt đánh giá đã công bố, không thể chỉnh sửa.']);
        }

        $this->ensureRubricDetails($phieu);

        foreach ($this->activeRubricItems() as $item) {
            $detail = $this->detailForItem($phieu, $item);

            if ($detail->diem_hoi_dong === null) {
                $score = $this->normalizeScore($item, $detail->diem_gvcn ?? $detail->diem_tu_cham);
                $this->history($phieu, $detail, $user, 'hoi_dong', $detail->diem_hoi_dong, $score, 'Xác nhận điểm Công Tác Sinh Viên mặc định', $note);
                $detail->update(['diem_hoi_dong' => $score]);
            }
        }

        $rubricScore = $this->totalForStage($phieu->refresh(), ['diem_hoi_dong', 'diem_gvcn', 'diem_tu_cham']);
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
            ['phieu_danh_gia_id' => $phieu->id],
            [
                'sinh_vien_id' => $phieu->sinh_vien_id,
                'hoc_ky_id' => $phieu->hoc_ky_id,
                'tong_diem' => $finalScore,
                'diem_hoat_dong' => $activityScore,
                'xep_loai' => $this->xepLoai($finalScore),
                'trang_thai' => 'final',
                'cong_bo_at' => $phieu->dotDanhGia?->ngay_cong_bo ?? $phieu->hocKy?->ngay_cong_bo,
            ],
        );

        $phieu = $this->loadEvaluation($phieu->refresh());
        app(EvaluationStatusBroadcaster::class)->approved($phieu);

        return $phieu;
    }

    public function lock(PhieuDanhGia $phieu, User $user): PhieuDanhGia
    {
        $phieu->update([
            'trang_thai' => PhieuDanhGia::STATUS_LOCKED,
            'locked_by' => $user->id,
            'locked_at' => now(),
        ]);

        $phieu->diemRenLuyen?->update(['trang_thai' => 'locked']);

        $phieu = $this->loadEvaluation($phieu->refresh());
        app(EvaluationStatusBroadcaster::class)->locked($phieu);

        return $phieu;
    }

    public function activityScore(SinhVien $sinhVien, HocKy $hocKy): int
    {
        $auto = $sinhVien->diemRenLuyens()->count();

        $checkedInPoints = ConductPointLog::query()
            ->where('sinh_vien_id', $sinhVien->id)
            ->whereBetween('created_at', [$hocKy->ngay_bat_dau ?? now()->subYear(), $hocKy->ngay_ket_thuc ?? now()->addYear()])
            ->sum('point');

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

    private function loadEvaluation(PhieuDanhGia $phieu): PhieuDanhGia
    {
        return $phieu->load([
            'hocKy.namHoc',
            'dotDanhGia',
            'sinhVien.lop.khoa',
            'chiTietDanhGias.tieuChi',
            'chiTietDanhGias.mucTieuChi',
            'minhChungs.tieuChi',
            'minhChungs.mucTieuChi',
        ]);
    }

    private function ensureRubricDetails(PhieuDanhGia $phieu): void
    {
        DrlRubric::syncIfMissing();

        foreach ($this->activeRubricItems() as $item) {
            ChiTietDanhGia::query()->firstOrCreate(
                [
                    'phieu_danh_gia_id' => $phieu->id,
                    'muc_tieu_chi_id' => $item->id,
                ],
                [
                    'tieu_chi_id' => $item->tieu_chi_id,
                ],
            );
        }
    }

    private function activeRubricItems(): Collection
    {
        return MucTieuChi::query()
            ->with('tieuChi')
            ->whereNotNull('ma_muc')
            ->where('loai', MucTieuChi::TYPE_ITEM)
            ->where('is_active', true)
            ->orderBy('tieu_chi_id')
            ->orderBy('thu_tu')
            ->get();
    }

    private function detailForItem(PhieuDanhGia $phieu, MucTieuChi $item): ChiTietDanhGia
    {
        return ChiTietDanhGia::query()->firstOrCreate(
            [
                'phieu_danh_gia_id' => $phieu->id,
                'muc_tieu_chi_id' => $item->id,
            ],
            [
                'tieu_chi_id' => $item->tieu_chi_id,
            ],
        );
    }

    private function normalizeScore(MucTieuChi $item, mixed $raw): int
    {
        $value = is_numeric($raw) ? (int) $raw : 0;
        $limit = (int) $item->diem_toi_da;

        if ($limit < 0) {
            return min(0, max($limit, $value));
        }

        return max(0, min($limit, $value));
    }

    private function firstScore(ChiTietDanhGia $detail, array $fields): int
    {
        foreach ($fields as $field) {
            if ($detail->{$field} !== null) {
                return (int) $detail->{$field};
            }
        }

        return 0;
    }

    private function totalForStage(PhieuDanhGia $phieu, array $fields): int
    {
        $this->loadEvaluation($phieu);
        $criterionScores = [];

        foreach ($phieu->chiTietDanhGias as $detail) {
            $item = $detail->mucTieuChi;

            if (! $item || $item->loai !== MucTieuChi::TYPE_ITEM || ! $item->is_active) {
                continue;
            }

            $criterionScores[$item->tieu_chi_id] = ($criterionScores[$item->tieu_chi_id] ?? 0)
                + $this->normalizeScore($item, $this->firstScore($detail, $fields));
        }

        return $this->totalFromCriterionScores($criterionScores);
    }

    private function totalFromCriterionScores(array $criterionScores): int
    {
        $criteria = TieuChi::query()
            ->whereIn('id', array_keys($criterionScores))
            ->pluck('diem_toi_da', 'id');

        $total = 0;

        foreach ($criterionScores as $criterionId => $score) {
            $max = (int) ($criteria[$criterionId] ?? 0);
            $total += max(0, min((int) $score, $max));
        }

        return max(0, min(100, $total));
    }

    private function criterionTotalFromRows(TieuChi $criterion, Collection $rows, array $fields): int
    {
        $score = $rows->sum(function (array $row) use ($fields): int {
            $item = $row['item'];
            $detail = $row['detail'];

            if ($item->loai !== MucTieuChi::TYPE_ITEM || ! $detail) {
                return 0;
            }

            return $this->normalizeScore($item, $this->firstScore($detail, $fields));
        });

        return max(0, min((int) $score, (int) $criterion->diem_toi_da));
    }
}
