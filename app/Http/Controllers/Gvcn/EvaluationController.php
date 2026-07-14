<?php

namespace App\Http\Controllers\Gvcn;

use App\Http\Controllers\Controller;
use App\Models\DotDanhGia;
use App\Models\MinhChung;
use App\Models\PhieuDanhGia;
use App\Services\DiemRenLuyenService;
use App\Services\DotDanhGiaService;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        app(DotDanhGiaService::class)->syncAll();
        $classIds = $request->user()->lopPhuTrachs()->pluck('id');
        $currentDot = app(DotDanhGiaService::class)->getCurrentTeacherPeriod()
            ?? DotDanhGia::whereIn('trang_thai', ['open', 'closed', 'published'])->latest('id')->first();

        $forms = PhieuDanhGia::with(['sinhVien.lop', 'hocKy', 'dotDanhGia'])
            ->whereHas('sinhVien', fn ($query) => $query->whereIn('lop_id', $classIds))
            ->when(
                $currentDot,
                fn ($query) => $query
                    ->where('dot_danh_gia_id', $currentDot->id)
                    ->whereIn('trang_thai', [PhieuDanhGia::STATUS_SUBMITTED, PhieuDanhGia::STATUS_REVIEWED, PhieuDanhGia::STATUS_APPROVED, PhieuDanhGia::STATUS_LOCKED]),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->latest()
            ->paginate(15);

        return view('gvcn.evaluations.index', compact('forms', 'currentDot'));
    }

    public function show(Request $request, PhieuDanhGia $phieu, DotDanhGiaService $dotService)
    {
        $dotService->syncPeriod($phieu->loadMissing('dotDanhGia')->dotDanhGia);
        $this->authorizeClass($request, $phieu);
        $phieu->load(['sinhVien.lop', 'hocKy', 'dotDanhGia', 'chiTietDanhGias.tieuChi', 'chiTietDanhGias.mucTieuChi', 'minhChungs.mucTieuChi']);

        return view('gvcn.evaluations.show', [
            'phieu' => $phieu,
            'canReview' => $phieu->canGvcnReviewStatus() && $dotService->openForGvcn($phieu->dotDanhGia),
            'rubric' => app(DiemRenLuyenService::class)->rubricForPhieu($phieu),
        ]);
    }

    public function update(Request $request, PhieuDanhGia $phieu, DiemRenLuyenService $service)
    {
        $this->authorizeClass($request, $phieu);
        $data = $request->validate([
            'scores' => ['required', 'array'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:1000'],
            'nhan_xet_gvcn' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->saveReviewerScores($phieu, $data['scores'], $request->user(), 'gvcn', $data['nhan_xet_gvcn'] ?? null, $data['notes'] ?? []);

        return back()->with('status', 'Đã lưu điểm GVCN.');
    }

    public function confirm(Request $request, PhieuDanhGia $phieu, DiemRenLuyenService $service)
    {
        $this->authorizeClass($request, $phieu);

        if ($request->filled('scores')) {
            $data = $request->validate([
                'scores' => ['required', 'array'],
                'notes' => ['nullable', 'array'],
                'notes.*' => ['nullable', 'string', 'max:1000'],
                'nhan_xet_gvcn' => ['nullable', 'string', 'max:2000'],
            ]);

            $service->saveReviewerScores($phieu, $data['scores'], $request->user(), 'gvcn', $data['nhan_xet_gvcn'] ?? null, $data['notes'] ?? []);
            $phieu->refresh();
        }

        $service->confirmGvcn($phieu, $request->user(), $request->input('nhan_xet_gvcn'));

        return back()->with('status', 'Đã xác nhận phiếu, chờ CTSV duyệt cuối.');
    }

    public function reviewEvidence(Request $request, MinhChung $minhChung)
    {
        abort_unless($request->user()->lopPhuTrachs()->whereKey($minhChung->sinhVien->lop_id)->exists(), 403);
        $data = $request->validate([
            'trang_thai' => ['required', 'in:approved,rejected,pending'],
            'ghi_chu_duyet' => ['nullable', 'string', 'max:1000'],
        ]);

        $minhChung->update([
            ...$data,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Đã cập nhật trạng thái minh chứng.');
    }

    private function authorizeClass(Request $request, PhieuDanhGia $phieu): void
    {
        abort_unless($request->user()->lopPhuTrachs()->whereKey($phieu->sinhVien->lop_id)->exists(), 403);
    }
}
