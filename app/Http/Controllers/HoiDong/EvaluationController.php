<?php

namespace App\Http\Controllers\HoiDong;

use App\Exports\DiemRenLuyenExport;
use App\Http\Controllers\Controller;
use App\Models\HocKy;
use App\Models\PhieuDanhGia;
use App\Services\DiemRenLuyenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EvaluationController extends Controller
{
    public function index()
    {
        $forms = PhieuDanhGia::with(['sinhVien.lop.khoa', 'hocKy', 'dotDanhGia'])
            ->whereIn('trang_thai', ['reviewed', 'approved', 'locked'])
            ->latest()
            ->paginate(20);

        return view('hoi-dong.evaluations.index', compact('forms'));
    }

    public function show(PhieuDanhGia $phieu, DiemRenLuyenService $service)
    {
        $phieu->load(['sinhVien.lop.khoa', 'hocKy', 'dotDanhGia', 'chiTietDanhGias.tieuChi', 'chiTietDanhGias.mucTieuChi', 'minhChungs.mucTieuChi']);

        return view('hoi-dong.evaluations.show', [
            'phieu' => $phieu,
            'rubric' => $service->rubricForPhieu($phieu),
        ]);
    }

    public function update(Request $request, PhieuDanhGia $phieu, DiemRenLuyenService $service)
    {
        $data = $request->validate([
            'scores' => ['required', 'array'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:1000'],
            'nhan_xet_hoi_dong' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->saveReviewerScores($phieu, $data['scores'], $request->user(), 'hoi_dong', $data['nhan_xet_hoi_dong'] ?? null, $data['notes'] ?? []);

        return back()->with('status', 'Đã lưu điểm hội đồng.');
    }

    public function approve(Request $request, PhieuDanhGia $phieu, DiemRenLuyenService $service)
    {
        if ($request->filled('scores')) {
            $data = $request->validate([
                'scores' => ['required', 'array'],
                'notes' => ['nullable', 'array'],
                'notes.*' => ['nullable', 'string', 'max:1000'],
                'nhan_xet_hoi_dong' => ['nullable', 'string', 'max:2000'],
            ]);

            $service->saveReviewerScores($phieu, $data['scores'], $request->user(), 'hoi_dong', $data['nhan_xet_hoi_dong'] ?? null, $data['notes'] ?? []);
            $phieu->refresh();
        }

        $service->approveFinal($phieu, $request->user(), $request->input('nhan_xet_hoi_dong'));

        return back()->with('status', 'Đã xác nhận điểm cuối cùng.');
    }

    public function lock(Request $request, PhieuDanhGia $phieu, DiemRenLuyenService $service)
    {
        $service->lock($phieu, $request->user());

        return back()->with('status', 'Đã khóa phiếu.');
    }

    public function exportIndex()
    {
        $hocKys = HocKy::with('namHoc')->orderByDesc('id')->get();
        return view('hoi-dong.evaluations.export', compact('hocKys'));
    }

    public function exportExcel(Request $request)
    {
        $request->validate(['hoc_ky_id' => 'required|exists:hoc_kys,id']);
        return Excel::download(new DiemRenLuyenExport(null, $request->hoc_ky_id), 'diem-ren-luyen.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $request->validate(['hoc_ky_id' => 'required|exists:hoc_kys,id']);
        $forms = PhieuDanhGia::with(['sinhVien.lop.khoa', 'hocKy.namHoc'])
            ->where('hoc_ky_id', $request->hoc_ky_id)
            ->whereIn('trang_thai', ['approved', 'locked'])
            ->get();
            
        $hocKy = HocKy::with('namHoc')->find($request->hoc_ky_id);
        
        $pdf = Pdf::loadView('exports.report-pdf', compact('forms', 'hocKy'))->setPaper('a4', 'landscape');

        return $pdf->download('bao-cao-diem-ren-luyen.pdf');
    }
}
