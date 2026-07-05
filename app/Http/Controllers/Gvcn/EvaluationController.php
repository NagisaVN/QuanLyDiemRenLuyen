<?php

namespace App\Http\Controllers\Gvcn;

use App\Http\Controllers\Controller;
use App\Models\MinhChung;
use App\Models\PhieuDanhGia;
use App\Services\DiemRenLuyenService;
use App\Services\DotDanhGiaService;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $classIds = $request->user()->lopPhuTrachs()->pluck('id');
        $forms = PhieuDanhGia::with(['sinhVien.lop', 'hocKy', 'dotDanhGia'])
            ->whereHas('sinhVien', fn ($query) => $query->whereIn('lop_id', $classIds))
            ->latest()
            ->paginate(15);

        return view('gvcn.evaluations.index', compact('forms'));
    }

    public function show(Request $request, PhieuDanhGia $phieu, DotDanhGiaService $dotService)
    {
        $this->authorizeClass($request, $phieu);
        $phieu->load(['sinhVien.lop', 'hocKy', 'dotDanhGia', 'chiTietDanhGias.tieuChi', 'chiTietDanhGias.mucTieuChi', 'minhChungs.mucTieuChi']);

        return view('gvcn.evaluations.show', [
            'phieu' => $phieu,
            'canReview' => $dotService->openForGvcn($phieu->dotDanhGia),
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

        return back()->with('status', 'Đã xác nhận phiếu cấp GVCN.');
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
